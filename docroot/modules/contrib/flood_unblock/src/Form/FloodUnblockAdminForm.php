<?php

namespace Drupal\flood_unblock\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flood_unblock\FloodUnblockManager;

/**
 * Admin form of Flood Unblock.
 */
class FloodUnblockAdminForm extends FormBase {

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The FloodUnblockManager service.
   *
   * @var \Drupal\flood_unblock\FloodUnblockManager
   */
  protected $floodUnblockManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * FloodUnblockAdminForm constructor.
   *
   * @param \Drupal\flood_unblock\FloodUnblockManager $floodUnblockManager
   *   The FloodUnblockManager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(FloodUnblockManager $floodUnblockManager, Connection $database, DateFormatterInterface $date_formatter) {
    $this->floodUnblockManager = $floodUnblockManager;
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood_unblock.flood_unblock_manager'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flood_unblock_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Fetches the limit from the form.
    $limit = $form_state->getValue('limit') ?? 33;

    // Fetches the identifier from the form.
    $identifier = $form_state->getValue('identifier');

    // Provides introduction to the table.
    $form['top_markup'] = [
      '#markup' => $this->t("<p>List of IP addresses and user ID's that are blocked after multiple failed login attempts. You can remove separate entries.</p>"),
    ];

    // Provides table filters.
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter'),
      '#open' => FALSE,
      'limit' => [
        '#type' => 'number',
        '#title' => $this->t('Amount'),
        '#description' => $this->t("Number of lines shown in table."),
        '#size' => 5,
        '#min' => 1,
        '#steps' => 10,
        '#default_value' => $limit,
      ],
      'identifier' => [
        '#type' => 'textfield',
        '#title' => $this->t('Identifier'),
        '#default_value' => $identifier,
        '#size' => 20,
        '#description' => $this->t('(Part of) identifier: IP address or UID'),
        '#maxlength' => 256,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Filter'),
      ],
    ];

    // Provides header for tableselect element.
    $header = [
      'identifier' => [
        'data' => $this->t('Identifier'),
        'field' => 'identifier',
        'sort' => 'asc',
      ],
      'blocked' => $this->t('Status'),
      'event' => [
        'data' => $this->t('Event'),
        'field' => 'event',
        'sort' => 'asc',
      ],
      'timestamp' => [
        'data' => $this->t('Timestamp'),
        'field' => 'timestamp',
        'sort' => 'asc',
      ],
      'expiration' => [
        'data' => $this->t('Expiration'),
        'field' => 'expiration',
        'sort' => 'asc',
      ],
    ];

    $options = [];

    // Fetches items from flood table.
    if ($this->database->schema()->tableExists('flood')) {
      $query = $this->database->select('flood', 'f')
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($header);
      $query->fields('f');
      if ($identifier) {
        $query->condition('identifier', "%" . $this->database->escapeLike($identifier) . "%", 'LIKE');
      }
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($limit);
      $execute = $pager->execute();
      $results = $execute->fetchAll();
      $results_identifiers = array_column($results, 'identifier', 'fid');

      // Fetches user names or location string for identifiers.
      $identifiers = $this->floodUnblockManager->fetchIdentifiers(array_unique($results_identifiers));

      // Gets list of all events.
      $events = $this->floodUnblockManager->getEvents();

      foreach ($results as $result) {

        // Gets event type and label.
        $event = $events[$result->event];

        // Gets status of identifier.
        $is_blocked = $this->floodUnblockManager->isBlocked($result->identifier, $result->event);

        // Defines list of options for tableselect element.
        $options[$result->fid] = [
          'identifier' => $identifiers[$result->identifier],
          'blocked' => $is_blocked ? $this->t('Blocked') : $this->t('Not blocked'),
          'event' => $event['label'],
          'timestamp' => $this->dateFormatter->format($result->timestamp, 'short'),
          'expiration' => $this->dateFormatter->format($result->expiration, 'short'),
        ];
      }
    }

    // Provides the tableselect element.
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('There are no failed logins at this time.'),
    ];

    // Provides the remove submit button.
    $form['remove'] = [
      '#type' => 'submit',
      '#value' => $this->t('Removed selected items from the flood table'),
      '#validate' => ['::validateRemoveItems'],
    ];
    if (count($options) == 0) {
      $form['remove']['#disabled'] = TRUE;
    }

    // Provides the pager element.
    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Validates that items have been selected for removal.
   */
  public function validateRemoveItems(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entries = $form_state->getValue('table');
    $selected_entries = array_filter($entries, function ($selected) {
      return $selected !== 0;
    });
    if (empty($selected_entries)) {
      $form_state->setErrorByName('table', $this->t('Please make a selection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('table') as $fid) {
      if ($fid !== 0) {
        $this->floodUnblockManager->floodUnblockClearEvent($fid);
      }
    }
    $form_state->setRebuild();
  }

}

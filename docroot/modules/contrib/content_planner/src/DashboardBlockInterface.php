<?php

namespace Drupal\content_planner;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for dashboard block plugins.
 */
interface DashboardBlockInterface extends PluginInspectionInterface {

  /**
   * Return the name of the block.
   *
   * @return string
   *   The name of the block.
   */
  public function getName();

  /**
   * Check if the plugin is configurable.
   *
   * @return bool
   *   TRUE if the block is configuratble, FALSE otherwise.
   */
  public function isConfigurable();

  /**
   * Get Configuration passed in by Plugin Manager.
   *
   * @return array
   *   The block configuration .
   */
  public function getConfiguration();

  /**
   * Build the block and return a renderable array.
   *
   * @return array
   *   The render array for the block.
   */
  public function build();

  /**
   * Add additonal form elements specific to the Plugin.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param array $block_configuration
   *   The block configuration.
   *
   * @return mixed
   *   Gets the config form fields.
   */
  public function getConfigSpecificFormFields(FormStateInterface &$form_state, Request &$request, array $block_configuration);

  /**
   * Validates teh plugin config form.
   *
   * @param array $form
   *   The form array passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface &$form_state);

  /**
   * Submit form handler.
   *
   * @param array $form
   *   The form array passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitSettingsForm(array &$form, FormStateInterface &$form_state);

}

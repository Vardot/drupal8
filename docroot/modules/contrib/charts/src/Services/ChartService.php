<?php

namespace Drupal\charts\Services;

/**
 * Service class for getting and setting the currently selected library's state.
 *
 * @package Drupal\charts\Services
 */
class ChartService implements ChartServiceInterface {

  /**
   * The selected library.
   *
   * @var string
   */
  private $librarySelected;

  /**
   * {@inheritdoc}
   */
  public function getLibrarySelected() {
    return $this->librarySelected;
  }

  /**
   * {@inheritdoc}
   */
  public function setLibrarySelected($librarySelected = '') {
    $this->librarySelected = $librarySelected;
  }

}

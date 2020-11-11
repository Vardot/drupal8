<?php

namespace Drupal\flood_unblock;

/**
 * Interface for FloodUnblockManager.
 */
interface FloodUnblockManagerInterface {

  /**
   * Gets the user link or location string for an identifier.
   *
   * @param string $identifiers
   *   An array containing the identifiers from the flood table.
   *
   * @return array
   *   List of identifiers, keyed by the original identifier, containing
   *   user link or location string or just the unchanged identifier.
   */
  public function fetchIdentifiers($fid);

  /**
   * Removes rows from flood table.
   *
   * @param string $fid
   *   The flood table entry ID.
   */
  public function floodUnblockClearEvent($fid);

  /**
   * Gets metadata about events.
   *
   * @return array
   *   List of events, keyed by the Drupal flood event name containing
   *   type and label.
   */
  public function getEvents();

  /**
   * Provides identifier's flood status.
   *
   * @param string $event
   *   The flood event name.
   * @param string $identifier
   *   The identifier: IP address and/or UID.
   *
   * @return bool
   *   Whether the identifier is blocked.
   */
  public function isBlocked($event, $identifier);

}

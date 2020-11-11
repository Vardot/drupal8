<?php

namespace Drupal\drd_agent\Agent\Remote;

/**
 * Interface for remote classes.
 */
interface BaseInterface {

  /**
   * Collect the security review results.
   *
   * @return array
   *   List of all the security review results.
   */
  public function collect(): array;

}

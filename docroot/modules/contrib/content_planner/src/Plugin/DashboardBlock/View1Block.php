<?php

namespace Drupal\content_planner\Plugin\DashboardBlock;

/**
 * Provides a view block for Content Planner Dashboard.
 *
 * @DashboardBlock(
 *   id = "view_1_block",
 *   name = @Translation("Views Widget 1")
 * )
 */
class View1Block extends ViewBlockBase {

  protected $blockID = 'view_1';

}

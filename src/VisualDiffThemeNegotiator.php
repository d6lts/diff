<?php

namespace Drupal\diff;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Theme\AdminNegotiator;

/**
 * Visual inline layout theme negotiator.
 *
 * @package Drupal\diff
 */
class VisualDiffThemeNegotiator extends AdminNegotiator {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() === 'diff.revisions_diff') {
      if ($route_match->getParameter('filter') === 'visual_inline') {
        if ($this->configFactory->get('diff.settings')->get('general_settings.visual_inline_theme') === 'standard') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('system.theme')->get('default');
  }

}

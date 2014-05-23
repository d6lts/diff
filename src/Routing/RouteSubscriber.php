<?php

/**
 * @file
 * Contains \Drupal\diff\Routing\RouteSubscriber.
 */

namespace Drupal\diff\Routing;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('node.revision_overview');
    $route->addDefaults(
      array(
        '_content' => '\Drupal\diff\Controller\NodeRevisionController::revisionOverview'
      )
    );
  }

}

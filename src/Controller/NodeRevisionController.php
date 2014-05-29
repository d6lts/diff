<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\RevisionController.
 */

namespace Drupal\diff\Controller;

use Drupal\node\NodeInterface;

/**
 * Returns responses for Node Revision routes.
 */
class NodeRevisionController extends EntityComparisonBase {

  /**
   * @param NodeInterface $node The node whose revisions are inspected.
   * @return array Render array containing the revisions table for $node.
   */
  public function revisionOverview(NodeInterface $node) {
    return $this->formBuilder()->getForm('Drupal\diff\Form\RevisionOverviewForm', $node);
  }

  /**
   * @param NodeInterface $node The node whose revisions are compared.
   * @param $left_vid vid of the node revision.
   * @param $right_vid vid of the node revision.
   */
  public function compareNodeRevisions(NodeInterface $node, $left_vid, $right_vid) {
    $entity_type = 'node';
    $left_node = $this->entityManager()->getStorage($entity_type)->loadRevision($left_vid);
    $right_node = $this->entityManager()->getStorage($entity_type)->loadRevision($right_vid);

    // Only perform comparison if both node revisions loaded successfully.
    if ($left_node != FALSE && $right_node != FALSE) {
      dsm($this->compareRevisions($left_node, $right_node));
    }
    else {
      drupal_set_message($this->t('Selected node revisions could not be loaded.'), 'error');
    }

  }

}

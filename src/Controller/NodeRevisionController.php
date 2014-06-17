<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\NodeRevisionController.
 */

namespace Drupal\diff\Controller;

use Drupal\node\NodeInterface;
use Drupal\diff\EntityComparisonBase;

/**
 * Returns responses for Node Revision routes.
 */
class NodeRevisionController extends EntityComparisonBase {

  /**
   * Returns a form for revision overview page.
   *
   * @todo This might be changed to a view when the issue at this link is
   * resolved: https://drupal.org/node/1863906
   *
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
   *
   * @return array Table with the diff between the two revisions.
   */
  public function compareNodeRevisions(NodeInterface $node, $left_vid, $right_vid) {
    $entity_type = 'node';
    $left_revision = $this->entityManager()->getStorage($entity_type)->loadRevision($left_vid);
    $right_revision = $this->entityManager()->getStorage($entity_type)->loadRevision($right_vid);

    // Perform comparison only if both node revisions loaded successfully.
    if ($left_revision != FALSE && $right_revision != FALSE) {
      $diff_rows = array();

      $content = $this->compareRevisions($left_revision, $right_revision);

      foreach ($content as $value) {
        // Show field name.
        // @todo Add field name only of there are changes.
        $diff_rows[] = array(
          array(
            'data' => $this->t('Changes to %name', array('%name' => $value['#name'])),
            'colspan' => 4
          ),
        );
        $diff_rows = array_merge($diff_rows, $this->getRows(
          $value['#states']['raw']['#left'],
          $value['#states']['raw']['#right']
        ));
      }

      $build = array();

      // Add the CSS for the inline diff.
      $build['#attached']['css'][] = drupal_get_path('module', 'diff') . '/css/diff.default.css';

      // @todo #header will be replaced with revision logs and navigation between revisions.
      $build['diff'] = array(
        '#type' => 'table',
        '#header' => array(
          array('data' => t('Old'), 'colspan' => '2'),
          array('data' => t('New'), 'colspan' => '2'),
        ),
        '#rows' => $diff_rows,
        '#empty' => $this->t('No visible changes'),
      );

      $build['back'] = array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'back',
          ),
        ),
        '#title' => "Back to 'Revision overview' page.",
        '#href' => 'node/' . $node->id() . '/revisions',
      );

      return $build;
    }
    else {
      drupal_set_message($this->t('Selected node revisions could not be loaded.'), 'error');
    }

  }

}

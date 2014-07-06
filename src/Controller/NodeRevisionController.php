<?php

/**
 * @file
 * Contains \Drupal\diff\Controller\NodeRevisionController.
 */

namespace Drupal\diff\Controller;

use Drupal\node\NodeInterface;
use Drupal\diff\Diff\Entity\EntityComparisonBase;
use Drupal\Component\Utility\Xss;


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
   * Returns a table which shows the differences between two node revisions.
   *
   * @param NodeInterface $node
   *   The node whose revisions are compared.
   * @param $left_vid
   *   Vid of the node revision from the left.
   * @param $right_vid
   *   Vid of the node revision from the right.
   * @param $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If filter == 'raw-plain' markdown function is applied to the text before comparison.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function compareNodeRevisions(NodeInterface $node, $left_vid, $right_vid, $filter) {
    $diff_rows = array();
    $build = array();
    if (!in_array($filter, array('raw', 'raw-plain'))) {
      $filter = 'raw';
    }
    else if ($filter == 'raw-plain') {
      $filter = 'raw_plain';
    }
    // Node storage service.
    $storage = $this->entityManager()->getStorage('node');
    $left_revision = $storage->loadRevision($left_vid);
    $right_revision = $storage->loadRevision($right_vid);
    $vids = $storage->revisionIds($node);
    $diff_rows[] = $this->buildRevisionsNavigation($node->id(), $vids, $left_vid, $right_vid);
    $diff_rows[] = $this->buildMarkdownNavigation($node->id(), $left_vid, $right_vid, $filter);
    $diff_header = $this->buildTableHeader($left_revision, $right_revision);

    // Perform comparison only if both node revisions loaded successfully.
    if ($left_revision != FALSE && $right_revision != FALSE) {
      $fields = $this->compareRevisions($left_revision, $right_revision);

      // Build the diff rows for each field and append the field rows to the table rows.
      foreach ($fields as $field) {
        $field_label_row = '';
        if (!empty($field['#name'])) {
          $field_label_row = array(
            'data' => $this->t('Changes to %name', array('%name' => $field['#name'])),
            'colspan' => 4,
          );
        }
        // @todo We need to make sure that the diff is not passed through check_plain
        $field_diff_rows = $this->getRows(
          $field['#states'][$filter]['#left'],
          $field['#states'][$filter]['#right']
        );
        // Add the field label to the table only if there are changes to that field.
        if (!empty($field_diff_rows) && !empty($field_label_row)) {
          $diff_rows[] = array($field_label_row);
        }

        // Add field diff rows to the table rows.
        $diff_rows = array_merge($diff_rows, $field_diff_rows);
      }

      // Add the CSS for the diff.
      $build['#attached']['css'][] = drupal_get_path('module', 'diff') . '/css/diff.default.css';

      $build['diff'] = array(
        '#type' => 'table',
        '#header' => $diff_header,
        '#rows' => $diff_rows,
        '#empty' => $this->t('No visible changes'),
      );

      $build['back'] = array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'button',
            'diff-button',
          ),
        ),
        '#title' => $this->t('Back to Revision Overview'),
        '#href' => 'node/' . $node->id() . '/revisions',
      );

      return $build;
    }
    else {
      drupal_set_message($this->t('Selected node revisions could not be loaded.'), 'error');
    }
  }

  /**
   * Build the header for the diff table.
   *
   * @param $left_revision Revision from the left hand side.
   * @param $right_revision Revision from the right hand side.
   * @return array Header for Diff table
   */
  protected function buildTableHeader($left_revision, $right_revision) {
    $header = array();
    $revisions = array($left_revision, $right_revision);

    foreach ($revisions as $revision) {
      $revision_log = '&nbsp';

      if ($revision->revision_log->value != '') {
        $revision_log = Xss::filter($revision->revision_log->value);
      }
      $username = array(
        '#theme' => 'username',
        '#account' => $revision->uid->entity,
      );
      $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
      $revision_link = $this->t($revision_log . '!date', array(
        '!date' => $this->l($revision_date, 'node.revision_show', array(
            'node' => $revision->id(),
            'node_revision' => $revision->getRevisionId()
          )),
      ));
      // @todo This needs to be fixed.
//      $header[] = array(
//        'data' => $this->t('by' . '!username', array('!username' => drupal_render($username))),
//        'colspan' => 1,
//      );
      $header[] = array(
        'data' => '&nbsp',
        'colspan' => 1,
      );
      $header[] = array(
        'data' => $revision_link,
        'colspan' => 1,
      );
    }

    return $header;
  }

  /**
   * Returns the navigation row for diff table.
   */
  protected function buildRevisionsNavigation($nid, $vids, $left_vid, $right_vid) {
    $i = 0;
    $revisions_count = count($vids);

    $row = array();
    // First column.
    $row[] = '&nbsp';
    // Find the previous revision.
    while ($left_vid > $vids[$i]) {
      $i += 1;
    }
    if ($i != 0) {
      // Second column.
      $row[] = array(
        'data' => $this->l(
            $this->t('< Previous difference'),
            'diff.revisions_diff',
            array(
              'node' => $nid,
              'left_vid' => $vids[$i - 1],
              'right_vid' => $left_vid
            )
          ),
        'colspan' => 1,
      );
    }
    else {
      // Second column.
      $row[] = '&nbsp';
    }
    // Third column.
    $row[] = '&nbsp';
    // Find the next revision.
    $i = 0;
    while ($i < $revisions_count && $right_vid >= $vids[$i]) {
      $i += 1;
    }
    if ($revisions_count != $i && $vids[$i - 1] != $vids[$revisions_count - 1]) {
      // Forth column.
      $row[] = array(
        'data' => $this->l(
            $this->t('Next difference >'),
            'diff.revisions_diff',
            array(
              'node' => $nid,
              'left_vid' => $right_vid,
              'right_vid' => $vids[$i],
            )
          ),
        'colspan' => 1,
      );
    }
    else {
      // Forth column
      $row[] = '&nbsp';
    }

    // If there are only 2 revision return an empty row.
    if ($revisions_count == 2) {
      return array();
    }
    else {
      return $row;
    }
  }

  /**
   * Builds a table row with navigation between raw and raw-plain formats.
   */
  protected function buildMarkdownNavigation($nid, $left_vid, $right_vid, $active_filter) {
    $links['raw'] = array(
      'title' => $this->t('Standard'),
      'route_name' => 'diff.revisions_diff',
      'route_parameters' => array(
        'node' => $nid,
        'left_vid' => $left_vid,
        'right_vid' => $right_vid,
      ),
    );
    $links['raw_plain'] = array(
      'title' => $this->t('Markdown'),
      'route_name' => 'diff.revisions_diff',
      'route_parameters' => array(
        'node' => $nid,
        'left_vid' => $left_vid,
        'right_vid' => $right_vid,
        'filter' => 'raw-plain',
      ),
    );

    // Set as the first element the current filter.
    $filter = $links[$active_filter];
    unset($links[$active_filter]);
    array_unshift($links, $filter);

    $row[] = '&nbsp';
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
      'colspan' => 3,
    );

    return $row;
  }

}

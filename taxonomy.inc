<?php
// $Id$

/**
 * @file Proposed hook_diff() implementation for taxonomy.
 *
 * Unfortunately, taxonomy doesn't account for revisions! This should
 * be a Bug report/Feature request for 6.0. For now, this file is not
 * included by diff.module, and is just here for reference.
 */

function taxonomy_diff(&$old_node, &$new_node) {
  $result = array();
  // TODO: make taxonomy by category not only by whole taxonomy?
  $old_taxonomy = array();
  $new_taxonomy = array();
  if($old_node->taxonomy) {
    foreach($old_node->taxonomy as $term) {
      $old_taxonomy[] = $term->name;
    }
  }
  if ($new_node->taxonomy) {
    foreach($new_node->taxonomy as $term) {
      $new_taxonomy[] = $term->name;
    }
  }
  $result[] = array(
    'name' => t('Taxonomy'),
    'old' => $old_taxonomy,
    'new' => $new_taxonomy,
  );
  return $result;
}

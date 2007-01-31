<?php
// $Id$

/**
 * Implementation of hook_diff() for node.module (body and title).
 */
function node_diff(&$old_node, &$new_node) {
  $result = array();
  $result[] = array(
    'name' => t('Title'),
    'old' => array($old_node->title),
    'new' => array($new_node->title),
    'format' => array(
      'show_header' => false,
    )
  );
  $result[] = array(
    'name' => t('Body'),
    'old' => explode("\n", $old_node->body),
    'new' => explode("\n", $new_node->body),
  );
  return $result;	
}

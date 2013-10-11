<?php

/**
 * @file
 * Provide diff functions for the node module.
 */

/**
 * Implements hook_entity_diff() on behalf of the Node module.
 */
function node_entity_diff($old_node, $new_node, $context) {
  $result = array();
  if ($context['entity_type'] == 'node') {
    $type = node_type_get_type($new_node);
    $result['title'] = array(
      '#name' => $type->title_label,
      '#states' => array(),
      '#weight' => -5,
      '#settings' => array(
        // Global setting - 'diff_show_header_' . $entity_type
        'show_header' => variable_get('diff_show_header_node', 1),
      ),
    );
    foreach ($context['states'] as $state) {
      switch ($state) {
        case 'rendered':
          $result['title']['#states'][$state] = array(
            '#old' => l($old_node->title, 'node/' . $old_node->title),
            '#new' => l($new_node->title, 'node/' . $new_node->title),
          );
          break;

        // We specify a default so that the title is allows compared.
        case 'raw':
        default:
          $result['title']['#states'][$state] = array(
            '#old' => array($old_node->title),
            '#new' => array($new_node->title),
          );
          break;
      }
    }
  }
  return $result;
}

<?php

namespace Drupal\diff\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\diff\DiffEntityComparison;

/**
 * Base class for controllers that return responses on entity revision routes.
 */
class PluginRevisionController extends ControllerBase {

  /**
   * Wrapper object for writing/reading configuration from diff.plugins.yml
   */
  protected $config;

  /**
   * The diff entity comparison service.
   */
  protected $entityComparison;

  /**
   * The field diff layout plugin manager service.
   *
   * @var \Drupal\diff\DiffLayoutManager
   */
  protected $diffLayoutManager;

  /**
   * Constructs a PluginRevisionController object.
   *
   * @param DiffEntityComparison $entity_comparison
   *   DiffEntityComparison service.
   * @param DiffLayoutManager $diff_layout_manager
   *   DiffLayoutManager service.
   */
  public function __construct(DiffEntityComparison $entity_comparison, DiffLayoutManager $diff_layout_manager) {
    $this->config = $this->config('diff.settings');
    $this->diffLayoutManager = $diff_layout_manager;
    $this->entityComparison = $entity_comparison;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.entity_comparison'),
      $container->get('plugin.manager.diff.layout')
    );
  }

  /**
   * Get all the revision ids of given entity id.
   *
   * @param $storage
   *   The entity storage manager.
   * @param $entity_id
   *   The entity to find revisions of.
   *
   * @return array
   */
  public function getRevisionIds(EntityStorageInterface $storage, $entity_id) {
    $result = $storage->getQuery()
      ->allRevisions()
      ->condition($storage->getEntityType()->getKey('id'), $entity_id)
      ->execute();
    $result_array = array_keys($result);
    sort($result_array);
    return $result_array;
  }

  /**
   * Returns a table which shows the differences between two entity revisions.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   The left revision.
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   The right revision.
   * @param string $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If filter == 'raw-plain' markdown function is applied to the text before comparison.
   *
   * @return array
   *   Table showing the diff between the two entity revisions.
   */
  public function compareEntityRevisions(RouteMatchInterface $route_match, EntityInterface $left_revision, EntityInterface $right_revision, $filter) {
    $entity_type_id = $left_revision->getEntityTypeId();
    $entity = $route_match->getParameter($entity_type_id);

    $entity_type_id = $entity->getEntityTypeId();
    $storage = $this->entityTypeManager()->getStorage($entity_type_id);
    // Get language from the entity context.
    $langcode = $entity->language()->getId();

    // Get left and right revision in current language.
    $left_revision = $left_revision->getTranslation($langcode);
    $right_revision = $right_revision->getTranslation($langcode);

    $revisions_ids = [];
    // Filter revisions of current translation and where the translation is
    // affected.
    foreach ($this->getRevisionIds($storage, $entity->id()) as $revision_id) {
      $revision = $storage->loadRevision($revision_id);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $revisions_ids[] = $revision_id;
      }
    }

    $build = array(
      '#title' => $this->t('Changes to %title', ['%title' => $entity->label()]),
    );

    // Build the layout filter.
    $build['diff_layout'] = [
      '#type' => 'item',
      '#title' => $this->t('Layout'),
    ];
    $build['diff_layout']['filter'] = $this->buildLayoutNavigation($entity, $left_revision->getRevisionId(), $right_revision->getRevisionId(), $filter);

    // Build the navigation links.
    $build['diff_navigation'] = $this->buildRevisionsNavigation($entity, $revisions_ids, $left_revision->getRevisionId(), $right_revision->getRevisionId());

    // Perform comparison only if both entity revisions loaded successfully.
    if ($left_revision != FALSE && $right_revision != FALSE) {
      // Build the diff comparison with the plugin.
      if ($plugin = $this->diffLayoutManager->createInstance($filter)) {
        $build += $plugin->build($left_revision, $right_revision, $entity);
      }
    }

    $build['#attached']['library'][] = 'diff/diff.general';
    return $build;
  }

  /**
   * Builds a table row with navigation between the layout plugins.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be compared.
   * @param $left_revision_id
   *   Revision id of the left revision.
   * @param $right_revision_id
   *   Revision id of the right revision.
   * @param $active_filter
   *   The active filter.
   *
   * @return array
   *   The layout filter.
   */
  protected function buildLayoutNavigation(EntityInterface $entity, $left_revision_id, $right_revision_id, $active_filter) {
    $links = [];
    foreach ($this->diffLayoutManager->getDefinitions() as $key => $value) {
      $links[$key] = array(
        'title' => $value['label'],
        'url' => $this->diffRoute($entity, $left_revision_id, $right_revision_id, $key),
      );
    }

    // Set as the first element the current filter.
    $filter = $links[$active_filter];
    unset($links[$active_filter]);
    array_unshift($links, $filter);

    $filter = [
      '#type' => 'operations',
      '#links' => $links,
      '#prefix' => '<div class="diff-filter">',
      '#suffix' => '</div>',
    ];

    return $filter;
  }

  /**
   * Creates an url object for diff.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be compared.
   * @param $revision_ids
   *   The revision ids.
   * @param $left_revision_id
   *   Revision id of the left revision.
   * @param $right_revision_id
   *   Revision id of the right revision.
   *
   * @return array
   *   The revision navigation links.
   */
  protected function buildRevisionsNavigation(EntityInterface $entity, $revision_ids, $left_revision_id, $right_revision_id) {
    $revisions_count = count($revision_ids);
    // If there are only 2 revision return an empty row.
    if ($revisions_count == 2) {
      return [];
    }
    else {
      $left_link = $right_link = '';
      $element['diff_navigation'] = [
        '#type' => 'item',
        '#title' => $this->t('Navigation'),
      ];
      $i = 0;
      // Find the previous revision.
      while ($left_revision_id > $revision_ids[$i]) {
        $i += 1;
      }
      if ($i != 0) {
        // build the left link.
        $left_link = $this->l($this->t('< Previous difference'), $this->diffRoute($entity, $revision_ids[$i - 1], $left_revision_id));
      }
      $element['diff_navigation']['left'] = [
        '#type' => 'markup',
        '#markup' => $left_link,
        '#prefix' => '<span class="navigation-link">',
        '#suffix' => '</span>',
      ];
      // Find the next revision.
      $i = 0;
      while ($i < $revisions_count && $right_revision_id >= $revision_ids[$i]) {
        $i += 1;
      }
      if ($revisions_count != $i && $revision_ids[$i - 1] != $revision_ids[$revisions_count - 1]) {
        // Build the right link.
        $right_link = $this->l($this->t('Next difference >'), $this->diffRoute($entity, $right_revision_id, $revision_ids[$i]));
      }
      $element['diff_navigation']['right'] = [
        '#type' => 'markup',
        '#markup' => $right_link,
        '#prefix' => '<span class="navigation-link">',
        '#suffix' => '</span>',
      ];
      return $element;
    }
  }

  /**
   * Creates an url object for diff.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be compared.
   * @param $left_revision_id
   *   Revision id of the left revision.
   * @param $right_revision_id
   *   Revision id of the right revision.
   * @param $filter
   *   (optional) The filter added to the route.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function diffRoute(EntityInterface $entity, $left_revision_id, $right_revision_id, $filter = NULL) {
    $entity_type_id = $entity->getEntityTypeId();
    // @todo Remove the diff.revisions_diff route so we avoid adding extra cases.
    if ($entity->getEntityTypeId() == 'node') {
      $route_name = 'diff.revisions_diff';
    }
    else {
      $route_name = "entity.$entity_type_id.revisions_diff";
    }
    $route_parameters = [
      $entity_type_id => $entity->id(),
      'left_revision' => $left_revision_id,
      'right_revision' => $right_revision_id,
    ];
    if ($filter) {
      $route_parameters['filter'] = $filter;
    }
    return Url::fromRoute($route_name, $route_parameters);
  }

}
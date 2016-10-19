<?php

namespace Drupal\diff;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\diff\Controller\PluginRevisionController;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class DiffLayoutBase extends PluginBase implements DiffLayoutInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity parser.
   *
   * @var \Drupal\diff\DiffEntityParser
   */
  protected $entityParser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * Constructs a DiffLayoutBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\diff\DiffEntityParser $entity_parser
   *   The entity parser.
   * @param \Drupal\Core\DateTime\DateFormatter $date
   *   The date service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entity_parser, DateFormatter $date) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityParser = $entity_parser;
    $this->date = $date;
    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('diff.entity_parser'),
      $container->get('date.formatter')
    );
  }

  /**
   * Build the revision link for a revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $revision
   *   A revision where to add a link.
   *
   * @return \Drupal\Core\Link
   *   Header link for a revision in the table.
   */
  protected function buildRevisionLink(EntityInterface $revision) {
    $entity_type_id = $revision->getEntityTypeId();
    if ($revision instanceof EntityRevisionLogInterface || $revision instanceof NodeInterface) {
      $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
      $route_name = $entity_type_id != 'node' ? "entity.$entity_type_id.revisions_diff" : 'entity.node.revision';
      $revision_link = Link::fromTextAndUrl($revision_date, Url::fromRoute($route_name, [
        $entity_type_id => $revision->id(),
        $entity_type_id . '_revision' => $revision->getRevisionId(),
      ]))->toString();
    }
    else {
      $revision_link = Link::fromTextAndUrl($revision->label(), $revision->toUrl('revision'))
        ->toString();
    }
    return $revision_link;
  }

  /**
   * Build the revision link for the compared revisions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   Left revision that is compared.
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   Right revision that is compared.
   *
   * @return array
   *   Header link for a revision in the revision comparison display.
   */
  public function buildRevisionsData(EntityInterface $left_revision, EntityInterface $right_revision) {
    // Show the revisions that are compared.
    $build['diff_revisions'] = [
      '#type' => 'item',
      '#title' => $this->t('Comparing'),
      '#weight' => 1,
      '#prefix' => '<div class="revisions-data">',
      '#suffix' => '</div>',
    ];

    $build['diff_revisions']['right_revision'] = $this->buildRevisionData($right_revision);
    $build['diff_revisions']['right_revision']['#prefix'] = '<div class="comparison-flex-container">';
    $build['diff_revisions']['right_revision']['#suffix'] = '</div>';
    $build['diff_revisions']['left_revision'] = $this->buildRevisionData($left_revision);
    $build['diff_revisions']['left_revision']['#prefix'] = '<div class="comparison-flex-container">';
    $build['diff_revisions']['left_revision']['#suffix'] = '</div>';

    return $build;
  }

  /**
   * Build the revision link for a revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $revision
   *   Left revision that is compared.
   *
   * @return array
   *   Revision data about author, creation date and log.
   */
  protected function buildRevisionData(EntityInterface $revision) {
    $entity_type_id = $revision->getEntityTypeId();
    if ($revision instanceof EntityRevisionLogInterface || $revision instanceof NodeInterface) {
      $revision_log = '';

      if ($revision instanceof EntityRevisionLogInterface) {
        $revision_log = Xss::filter($revision->getRevisionLogMessage());
      }
      elseif ($revision instanceof NodeInterface) {
        $revision_log = $revision->revision_log->value;
      }
      $user_id = $revision->getRevisionUserId();
      $route_name = $entity_type_id != 'node' ? "entity.$entity_type_id.revisions_diff" : 'entity.node.revision';

      $revision_link['date'] = [
        '#type' => 'link',
        '#title' => $this->date->format($revision->getRevisionCreationTime(), 'short'),
        '#url' => Url::fromRoute($route_name, [
          $entity_type_id => $revision->id(),
          $entity_type_id . '_revision' => $revision->getRevisionId(),
        ]),
        '#prefix' => '<div class="comparison-flex-item-date">',
        '#suffix' => '</div>',
      ];

      $revision_link['author'] = [
        '#type' => 'link',
        '#title' => $revision->getRevisionUser()->getDisplayName(),
        '#url' => Url::fromUri(\Drupal::request()->getUriForPath('/user/' . $user_id)),
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
        '#prefix' => '<div class="comparison-flex-item-author">',
        '#suffix' => '</div>',
      ];

      if ($revision_log) {
        $revision_link['message'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="comparison-flex-item-message">',
          '#suffix' => '</div>',
          '#markup' => $revision_log,
        ];
      }
    }
    else {
      $revision_link['label'] = [
        '#type' => 'link',
        '#title' => $revision->label(),
        '#url' => $revision->toUrl('revision'),
        '#prefix' => '<div class="comparison-flex-item-date">',
        '#suffix' => '</div>',
      ];
    }
    return $revision_link;
  }

  /**
   * Build the filter navigation for the diff comparison.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   Revision from the left side.
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   Revision from the right side.
   * @param string $layout
   *   The layout plugin selected.
   * @param string $active_filter
   *   The active filter.
   *
   * @return array
   *   The filter options.
   */
  protected function buildFilterNavigation(EntityInterface $entity, EntityInterface $left_revision, EntityInterface $right_revision, $layout, $active_filter) {
    // Build the view modes filter.
    $options['raw'] = [
      'title' => $this->t('Raw'),
      'url' => PluginRevisionController::diffRoute($entity,
        $left_revision->getRevisionId(),
        $right_revision->getRevisionId(),
        $layout,
        ['filter' => 'raw']
      ),
    ];

    $options['strip_tags'] = [
      'title' => $this->t('Strip tags'),
      'url' => PluginRevisionController::diffRoute($entity,
         $left_revision->getRevisionId(),
         $right_revision->getRevisionId(),
         $layout,
         ['filter' => 'strip_tags']
      ),
    ];

    $filter = $options[$active_filter];
    unset($options[$active_filter]);
    array_unshift($options, $filter);

    $build['options'] = [
      '#type' => 'operations',
      '#links' => $options,
      '#prefix' => '<div class="diff-filter">',
      '#suffix' => '</div>',
    ];
    return $build;
  }

  /**
   * Applies a markdown function to a string.
   *
   * @param $markdown
   *   Key of the markdown function to be applied to the items.
   *   One of drupal_html_to_text, filter_xss, filter_xss_all.
   * @param $items
   *   String to be processed.
   *
   * @return array|string
   *   Result after markdown was applied on $items.
   */
  protected function applyMarkdown($markdown, $items) {
    if (!$markdown) {
      return $items;
    }

    if ($markdown == 'drupal_html_to_text') {
      return trim(MailFormatHelper::htmlToText($items), "\n");
    }
    elseif ($markdown == 'filter_xss') {
      return trim(Xss::filter($items), "\n");
    }
    elseif ($markdown == 'filter_xss_all') {
      return trim(Xss::filter($items, []), "\n");
    }
    else {
      return $items;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->getEditable('diff.layout_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->configFactory->getEditable('diff.layout_plugins');
    $config->set($this->pluginId, $configuration);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }
}

<?php

namespace Drupal\diff\Plugin\diff\Layout;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\diff\DiffEntityComparison;
use Drupal\diff\DiffEntityParser;
use Drupal\diff\DiffLayoutBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @DiffLayoutBuilder(
 *   id = "markdown",
 *   label = @Translation("Markdown"),
 * )
 */
class MarkdownDiffLayout extends DiffLayoutBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The diff entity comparison service.
   */
  protected $entityComparison;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * Constructs a FieldDiffBuilderBase object.
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
   *   The entity manager.
   * @param \Drupal\diff\DiffEntityParser $entityParser
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entityParser, RendererInterface $renderer, DiffEntityComparison $entityComparison, DateFormatter $date) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config, $entity_type_manager, $entityParser);
    $this->renderer = $renderer;
    $this->entityComparison = $entityComparison;
    $this->date = $date;
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
      $container->get('entity.manager'),
      $container->get('diff.entity_parser'),
      $container->get('renderer'),
      $container->get('diff.entity_comparison'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $left_revision, EntityInterface $right_revision, EntityInterface $entity) {
    $diff_header = $this->buildTableHeader($left_revision, $right_revision);
    // Perform comparison only if both entity revisions loaded successfully.
    $fields = $this->entityComparison->compareRevisions($left_revision, $right_revision);
    // Build the diff rows for each field and append the field rows
    // to the table rows.
    $diff_rows = [];
    foreach ($fields as $field) {
      $field_label_row = '';
      if (!empty($field['#name'])) {
        $field_label_row = [
          'data' => $this->t('%name', ['%name' => $field['#name']]),
          'colspan' => 4,
          'class' => ['field-name'],
        ];
      }
      $field_settings = $field['#settings'];
      if (!empty($field_settings['settings']['markdown'])) {
        $field['#data']['#left'] = $this->applyMarkdown($field_settings['settings']['markdown'], $field['#data']['#left']);
        $field['#data']['#right'] = $this->applyMarkdown($field_settings['settings']['markdown'], $field['#data']['#right']);
      }
      // In case the settings are not loaded correctly use drupal_html_to_text
      // to avoid any possible notices when a user clicks on markdown.
      else {
        $field['#data']['#left'] = $this->applyMarkdown('drupal_html_to_text', $field['#data']['#left']);
        $field['#data']['#right'] = $this->applyMarkdown('drupal_html_to_text', $field['#data']['#right']);
      }

      // Process the array (split the strings into single line strings)
      // and get line counts per field.
      $this->entityComparison->processStateLine($field);

      $field_diff_rows = $this->entityComparison->getRows(
        $field['#data']['#left'],
        $field['#data']['#right']
      );

      // Add the field label to the table only if there are changes to that field.
      if (!empty($field_diff_rows) && !empty($field_label_row)) {
        $diff_rows[] = [$field_label_row];
      }

      // Add field diff rows to the table rows.
      $diff_rows = array_merge($diff_rows, $field_diff_rows);
    }

    $build['diff'] = [
      '#type' => 'table',
      '#header' => $diff_header,
      '#rows' => $diff_rows,
      '#empty' => $this->t('No visible changes'),
      '#attributes' => [
        'class' => ['diff'],
      ],
    ];

    $build['#attached']['library'][] = 'diff/diff.github';
    return $build;
  }


  /**
   * Build the header for the diff table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   Revision from the left hand side.
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   Revision from the right hand side.
   *
   * @return array
   *   Header for Diff table.
   */
  protected function buildTableHeader(EntityInterface $left_revision, EntityInterface $right_revision) {
    $header = [];
    $header[] = [
      'data' => ['#markup' => $this->buildRevisionLink($left_revision)],
      'colspan' => 2,
    ];
    $header[] = [
      'data' => ['#markup' => $this->buildRevisionLink($right_revision)],
      'colspan' => 2,
    ];

    return $header;
  }

  /**
   * Build the revision link for a revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $revision
   *   A revision where to add a link.
   *
   * @return \Drupal\Core\GeneratedLink
   *   Header link for a revision in the table.
   */
  protected function buildRevisionLink(EntityInterface $revision) {
    $entity_type_id = $revision->getEntityTypeId();
    if ($revision instanceof EntityRevisionLogInterface || $revision instanceof NodeInterface) {
      $revision_log = '';

      if ($revision instanceof EntityRevisionLogInterface) {
        $revision_log = Xss::filter($revision->getRevisionLogMessage());
      }
      elseif ($revision instanceof NodeInterface) {
        $revision_log = $revision->revision_log->value;
      }
      $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
      $route_name = $entity_type_id != 'node' ? "entity.$entity_type_id.revisions_diff" : 'entity.node.revision';
      $revision_link = $this->t($revision_log . '@date', [
        '@date' => \Drupal::l($revision_date, Url::fromRoute($route_name, [
          $entity_type_id => $revision->id(),
          $entity_type_id . '_revision' => $revision->getRevisionId(),
        ])),
      ]);
    }
    else {
      $revision_link = \Drupal::l($revision->label(), $revision->toUrl('revision'));
    }
    return $revision_link;
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

}

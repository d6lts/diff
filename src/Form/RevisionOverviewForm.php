<?php

/**
 * @file
 * Contains \Drupal\diff\Form\RevisionOverviewForm
 * The form displays all revisions of a node and allows the user two select
 * two of them and compare.
 */

namespace Drupal\diff\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for revision overview page.
 */
class RevisionOverviewForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * Wrapper object for writing/reading simple configuration from diff.settings.yml
   */
  protected $config;


  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatter $date
   *   The date service.
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $currentUser, DateFormatter $date) {
    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
    $this->date = $date;
    $this->config = $this->config('diff.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'revision_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $account = $this->currentUser;
    $node_storage = $this->entityManager->getStorage('node');
    $type = $node->getType();

    $build = array(
      '#title' => $this->t('Revisions for %title', array('%title' => $node->label())),
      'nid' => array(
        '#type' => 'hidden',
        '#value' => $node->nid->value,
      ),
    );

    $table_header = array(
      'revision' => $this->t('Revision'),
      'select_column_one' => '',
      'select_column_two' => '',
      'operations' => $this->t('Operations'),
    );

    $rev_revert_perm = $account->hasPermission("revert $type revisions") ||
      $account->hasPermission('revert all revisions') ||
      $account->hasPermission('administer nodes');
    $rev_delete_perm = $account->hasPermission("delete $type revisions") ||
      $account->hasPermission('delete all revisions') ||
      $account->hasPermission('administer nodes');
    $revert_permission = $rev_revert_perm && $node->access('update');
    $delete_permission = $rev_delete_perm && $node->access('delete');

    // Contains the table listing the revisions.
    $build['node_revisions_table'] = array(
      '#type' => 'table',
      '#header' => $table_header,
      '#attributes' => array('class' => array('diff-revisions')),
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'diff') . '/js/diff.js',
          array(
            'data' => array('diffRevisionRadios' => $this->config->get('general_settings.radio_behavior')),
            'type' => 'setting',
          ),
        ),
        'css' => array(
          drupal_get_path('module', 'diff') . '/css/diff.general.css',
        ),
      ),
    );

    $vids = array_reverse($node_storage->revisionIds($node));
    // Add rows to the table.
    foreach ($vids as $vid) {
      if ($revision = $node_storage->loadRevision($vid)) {
        // Markup for revision log.
        if ($revision->revision_log->value != '') {
          $revision_log = '<p class="revision-log">' . Xss::filter($revision->revision_log->value) . '</p>';
        }
        else {
          $revision_log = '';
        }
        // Username to be rendered.
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->uid->entity,
        );
        $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');

        // Default revision.
        if ($revision->isDefaultRevision()) {
          $date_username_markup = $this->t('!date by !username', array(
            '!date' => $this->l($revision_date, new Url('entity.node.canonical', array('node' => $node->id()))),
            '!username' => drupal_render($username),
            )
          );

          $row = array(
            'revision' => array(
              '#markup' => $date_username_markup . $revision_log,
            ),
            'select_column_one' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_left',
              '#return_value' => $vid,
              '#default_value' => FALSE,
            ),
            'select_column_two' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_right',
              '#default_value' => $vid,
              '#return_value' => $vid,
            ),
            'operations' => array(
              '#markup' => String::placeholder($this->t('current revision')),
            ),
            '#attributes' => array(
              'class' => array('revision-current'),
            ),
          );
        }
        else {
          // Add links based on permissions.
          if ($revert_permission) {
            $links['revert'] = array(
              'title' => $this->t('Revert'),
              'route_name' => 'node.revision_revert_confirm',
              'route_parameters' => array(
                'node' => $node->id(),
                'node_revision' => $vid,
              ),
            );
          }
          if ($delete_permission) {
            $links['delete'] = array(
              'title' => $this->t('Delete'),
              'route_name' => 'node.revision_delete_confirm',
              'route_parameters' => array(
                'node' => $node->id(),
                'node_revision' => $vid,
              ),
            );
          }

          $date_username_markup = $this->t('!date by !username', array(
            '!date' => $this->l($revision_date, new Url('node.revision_show', array(
                  'node' => $node->id(),
                  'node_revision' => $vid,
                )
              )),
            '!username' => drupal_render($username),
            )
          );

          $row = array(
            'revision' => array(
              '#markup' => $date_username_markup . $revision_log,
            ),
            'select_column_one' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_left',
              '#return_value' => $vid,
              '#default_value' => isset ($vids[1]) ? $vids[1] : FALSE,
            ),
            'select_column_two' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_right',
              '#return_value' => $vid,
              '#default_value' => FALSE,
            ),
            'operations' => array(
              '#type' => 'operations',
              '#links' => $links,
            ),
          );
        }
        // Add the row to the table.
        $build['node_revisions_table'][] = $row;
      }
    }

    $build['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Compare'),
      '#attributes' => array(
        'class' => array(
          'diff-button',
        ),
      ),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    if ($vid_left == $vid_right || !$vid_left || !$vid_right) {
      // @todo Radio-boxes selection resets if there are errors.
      $form_state->setErrorByName('node_revisions_table', $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $nid = $input['nid'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }
    // Builds the redirect Url.
    $redirect_url = new Url(
      'diff.revisions_diff',
      array(
        'node' => $nid,
        'left_vid' => $vid_left,
        'right_vid' => $vid_right,
      )
    );
    $form_state->setRedirectUrl($redirect_url);
  }

}

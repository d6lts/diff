<?php

namespace Drupal\diff\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\Date;
use Drupal\Component\Utility\String;

/**
 * Provides a test form object.
 */
class RevisionOverviewForm extends FormBase implements ContainerInjectionInterface {

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
   * @var \Drupal\Core\Datetime\Date
   */
  protected $date;


  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Datetime\Date $date
   *   The date service.
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $currentUser, Date $date) {
    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
    $this->date = $date;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('date')
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
  public function buildForm(array $form, array &$form_state, $node = NULL) {
    $account = $this->currentUser;
    $node_storage = $this->entityManager->getStorage('node');
    $type = $node->getType();

    $build = array();
    $build['#title'] = $this->t('Revisions for %title', array('%title' => $node->label()));

    $header = array($this->t('Revision'), '', '', $this->t('Operations'));

    $revert_permission = ((
        $account->hasPermission("revert $type revisions") ||
        $account->hasPermission('revert all revisions') ||
        $account->hasPermission('administer nodes')) &&
      $node->access('update')
    );
    $delete_permission = ((
        $account->hasPermission("delete $type revisions") ||
        $account->hasPermission('delete all revisions') ||
        $account->hasPermission('administer nodes')) &&
      $node->access('delete'));

    $rows = array();

    $vids = $node_storage->revisionIds($node);
    foreach (array_reverse($vids) as $vid) {
      if ($revision = $node_storage->loadRevision($vid)) {
        $row = array();

        $revision_log = '';
        if ($revision->log->value != '') {
          $revision_log = '<p class="revision-log">' . Xss::filter($revision->log->value) . '</p>';
        }
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->uid->entity,
        );
        $revision_date = $this->date->format($revision->revision_timestamp->value, 'short');

        // Current revision.
        if ($vid == $node->getRevisionId()) {
          // @todo make sure it's ok to use l() function like this.
          $row[] = array(
            'data' => $this->t('!date by !username', array(
                '!date' => l($revision_date, 'node.view', array('node' => $node->id())),
                '!username' => drupal_render($username),
              )) . $revision_log,
            'class' => array('revision-current'),
          );
          // @todo add #default_value for radio buttons.
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#title_display' => 'invisible',
              '#name' => 'radios_left',
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#name' => 'radios_right',
            ),
          );
          $row[] = array(
            'data' => String::placeholder($this->t('current revision')),
            'class' => array('revision-current')
          );
        }
        else {
          $row[] = $this->t('!date by !username', array(
              '!date' => l($revision_date, 'node.revision_show', array(
                'node' => $node->id(),
                'node_revision' => $vid
              )),
              '!username' => drupal_render($username)
            )) . $revision_log;

          if ($revert_permission) {
            $links['revert'] = array(
              'title' => $this->t('Revert'),
              'route_name' => 'node.revision_revert_confirm',
              'route_parameters' => array(
                'node' => $node->id(),
                'node_revision' => $vid
              ),
            );
          }

          if ($delete_permission) {
            $links['delete'] = array(
              'title' => $this->t('Delete'),
              'route_name' => 'node.revision_delete_confirm',
              'route_parameters' => array(
                'node' => $node->id(),
                'node_revision' => $vid
              ),
            );
          }

          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#name' => 'radios_left',
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'radio',
              '#name' => 'radios_right',
            ),
          );
          $row[] = array(
            'data' => array(
              '#type' => 'operations',
              '#links' => $links,
            ),
          );
        }

        $rows[] = $row;
      }
    }

    $build['node_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );
    $build['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Compare'),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

  }
}

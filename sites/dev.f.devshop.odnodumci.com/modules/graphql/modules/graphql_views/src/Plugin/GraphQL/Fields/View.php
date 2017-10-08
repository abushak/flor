<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_core\GraphQL\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Expose views as root fields.
 *
 * @GraphQLField(
 *   id = "view",
 *   nullable = true,
 *   multi = true,
 *   types = {"Root"},
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\ViewDeriver"
 * )
 */
class View extends FieldPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    $storage = $this->entityTypeManager->getStorage('view');
    $definition = $this->getPluginDefinition();

    /** @var \Drupal\views\Entity\View $view */
    if ($view = $storage->load($definition['view'])) {
      $executable = $view->getExecutable();
      $executable->setDisplay($definition['display']);

      // Prepare arguments for use as exposed form input.
      $input = array_filter([
        // Sorting arguments.
        'sort_by' => isset($args['sortBy']) ? $args['sortBy'] : NULL,
        'sort_order' => isset($args['sortDirection']) ? $args['sortDirection'] : NULL,
      ]);

      // If some filters are missing from the input, set them to an empty string
      // explicitly. Otherwise views module generates "Undefined index" notice.
      $filters = $executable->getDisplay()->getOption('filters');
      foreach (array_keys($filters) as $filter) {
        $input[$filter] = isset($args['filter'][$filter]) ? $args['filter'][$filter] : '';
      }

      $executable->setExposedInput($input);

      if ($definition['paged']) {
        // Set paging parameters.
        $executable->setItemsPerPage($args['pageSize']);
        $executable->setCurrentPage($args['page']);
        $executable->execute();
        yield $executable;
      }
      else {
        $executable->execute();
        foreach ($executable->result as $row) {
          yield $row->_entity;
        }
      }
    }
  }

}

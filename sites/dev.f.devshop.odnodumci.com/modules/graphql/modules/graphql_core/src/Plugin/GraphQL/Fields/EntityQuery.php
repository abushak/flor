<?php

namespace Drupal\graphql_core\Plugin\GraphQL\Fields;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_core\GraphQL\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Retrieve a list of entities through an entity query.
 *
 * @GraphQLField(
 *   id = "entity_query",
 *   name = "entityQuery",
 *   type = "EntityQueryResult",
 *   nullable = false,
 *   weight = -1,
 *   arguments = {
 *     "offset" = {
 *       "type" = "Int",
 *       "nullable" = true,
 *       "default" = 0
 *     },
 *     "limit" = {
 *       "type" = "Int",
 *       "nullable" = true,
 *       "default" = 10
 *     }
 *   },
 *   deriver = "\Drupal\graphql_core\Plugin\Deriver\EntityQueryDeriver"
 * )
 */
class EntityQuery extends FieldPluginBase implements ContainerFactoryPluginInterface {
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
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager) {
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
  protected function getCacheDependencies($result, $value, array $args) {
    $entityTypeId = $this->pluginDefinition['entity_type'];
    $type = $this->entityTypeManager->getDefinition($entityTypeId);

    $metadata = new CacheableMetadata();
    $metadata->addCacheTags($type->getListCacheTags());
    $metadata->addCacheContexts($type->getListCacheContexts());

    return [$metadata];
  }

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    $entityTypeId = $this->pluginDefinition['entity_type'];
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    $type = $this->entityTypeManager->getDefinition($entityTypeId);

    $query = $storage->getQuery();
    $query->range($args['offset'], $args['limit']);
    $query->sort($type->getKey('id'));

    if (array_key_exists('filter', $args) && $args['filter']) {
      foreach ($args['filter'] as $key => $arg) {
        $query->condition($key, $arg);
      }
    }

    yield $query;
  }

}

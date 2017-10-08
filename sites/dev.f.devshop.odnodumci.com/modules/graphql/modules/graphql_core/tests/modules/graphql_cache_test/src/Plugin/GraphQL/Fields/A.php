<?php

namespace Drupal\graphql_cache_test\Plugin\GraphQL\Fields;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql_cache_test\Counter;
use Drupal\graphql_core\GraphQL\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * An uncacheable counter field.
 *
 * @GraphQLField(
 *   id = "a",
 *   name = "a",
 *   type = "Int",
 *   types = {"Root", "Object"},
 *   cache_tags = {"a"},
 *   cache_contexts = {"graphql_test"}
 * )
 */
class A extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The counter service.
   *
   * @var \Drupal\graphql_cache_test\Counter
   */
  protected $counter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static($configuration, $pluginId, $pluginDefinition, $container->get('graphql_test.counter'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, Counter $counter) {
    $this->counter = $counter;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveInfo $info) {
    yield $this->counter->count();
  }

}

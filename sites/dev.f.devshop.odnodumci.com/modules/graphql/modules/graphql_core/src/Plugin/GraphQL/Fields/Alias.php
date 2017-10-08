<?php

namespace Drupal\graphql_core\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Retrieve a routes canonical path.
 *
 * @GraphQLField(
 *   id = "url_alias",
 *   name = "alias",
 *   type = "String",
 *   types = {"Url"}
 * )
 */
class Alias extends Path implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * Instance of an alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof Url) {
      if ($value->isRouted()) {
        foreach (parent::resolveValues($value, $args, $info) as $url) {
          yield $this->aliasManager->getAliasByPath($url);
        }
      }
      else {
        yield $value->toString();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, AliasManagerInterface $aliasManager) {
    $this->aliasManager = $aliasManager;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static($configuration, $pluginId, $pluginDefinition, $container->get('path.alias_manager'));
  }

}

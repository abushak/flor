<?php

namespace Drupal\graphql_content\Plugin\GraphQL\Interfaces;

use Drupal\graphql_core\Plugin\GraphQL\Interfaces\Entity;

/**
 * Plugin for GraphQL interfaces derived from Drupal entity types.
 *
 * @GraphQLInterface(
 *   id = "entity_type",
 *   weight = -1,
 *   cache_tags = {"entity_types"},
 *   interfaces = {"Entity"},
 *   deriver = "Drupal\graphql_content\Plugin\Deriver\EntityTypeDeriver"
 * )
 */
class EntityType extends Entity {

}

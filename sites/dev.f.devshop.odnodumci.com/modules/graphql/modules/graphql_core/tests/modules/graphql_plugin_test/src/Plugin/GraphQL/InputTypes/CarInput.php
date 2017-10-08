<?php

namespace Drupal\graphql_plugin_test\Plugin\GraphQL\InputTypes;

use Drupal\graphql_core\GraphQL\InputTypePluginBase;

/**
 * Car input type.
 *
 * @GraphQLInputType(
 *   id = "car_input",
 *   name = "CarInput",
 *   fields = {
 *     "engine" = "String"
 *   }
 * )
 */
class CarInput extends InputTypePluginBase {

}

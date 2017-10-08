<?php

namespace Drupal\Tests\graphql_core\Kernel;

use Drupal\graphql_plugin_test\GarageInterface;

/**
 * Test plugin based schema generation.
 *
 * @group graphql_core
 */
class RecursiveTypeTest extends GraphQLFileTestBase {
  public static $modules = [
    'graphql_plugin_test',
  ];

  /**
   * Test if the schema is created properly.
   */
  public function testRecursiveType() {
    $vehicles = [
      [
        'type' => 'Car',
        'backupCar' => [
          'type' => 'Car',
          'engine' => 'electric',
        ],
      ],
    ];

    $prophecy = $this->prophesize(GarageInterface::class);
    $prophecy->getVehicles()->willReturn($vehicles);
    $this->container->set('graphql_test.garage', $prophecy->reveal());

    $values = $this->executeQueryFile('recursive_garage.gql');
    $this->assertArrayNotHasKey('errors', $values);
    $this->assertArraySubset($values['data']['garage'], $vehicles);
  }

}

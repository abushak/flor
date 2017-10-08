<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\commerce_order\Plugin\Commerce\Condition\OrderItemQuantity;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\Role;

/**
 * Tests the 'commerce_plugin_item' field type.
 *
 * @group commerce
 */
class PluginItemTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    Role::create(['id' => 'test_role', 'name' => $this->randomString()])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_conditions',
      'entity_type' => 'entity_test',
      'type' => 'commerce_plugin_item:commerce_condition',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_conditions',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();
  }

  /**
   * Tests the plugin item field.
   */
  public function testField() {
    $plugin_configuration = [
      'operator' => '>',
      'quantity' => 10,
    ];
    $entity = EntityTest::create([
      'test_conditions' => [
        [
          'target_plugin_id' => 'order_item_quantity',
          'target_plugin_configuration' => $plugin_configuration,
        ],
      ],
    ]);
    $entity->save();
    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $condition_field */
    $condition_field = $entity->test_conditions->first();

    $condition = $condition_field->getTargetInstance();
    $this->assertInstanceOf(OrderItemQuantity::class, $condition);
    $this->assertEquals($condition->getConfiguration(), $plugin_configuration);
    $this->assertEquals($condition->getPluginDefinition(), $condition_field->getTargetDefinition());
  }

}

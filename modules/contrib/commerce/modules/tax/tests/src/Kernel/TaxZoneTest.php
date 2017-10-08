<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use CommerceGuys\Addressing\Address;
use Drupal\commerce_tax\TaxZone;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\TaxZone
 * @group commerce
 */
class TaxZoneTest extends CommerceKernelTestBase {

  /**
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMissingProperty() {
    $definition = [
      'id' => 'test',
    ];
    $zone = new TaxZone($definition);
  }

  /**
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidTerritories() {
    $definition = [
      'id' => 'test',
      'label' => 'Test',
      'display_label' => 'VAT',
      'territories' => 'WRONG',
    ];
    $zone = new TaxZone($definition);
  }

  /**
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidRates() {
    $definition = [
      'id' => 'test',
      'label' => 'Test',
      'display_label' => 'VAT',
      'territories' => [
        ['country_code' => 'RS'],
      ],
      'rates' => 'WRONG',
    ];
    $zone = new TaxZone($definition);
  }

  /**
   * @covers ::__construct
   * @covers ::getId
   * @covers ::getLabel
   * @covers ::getDisplayLabel
   * @covers ::getTerritories
   * @covers ::getRates
   * @covers ::match
   */
  public function testValid() {
    // Can't use a unit test because DrupalDateTime objects use \Drupal.
    $definition = [
      'id' => 'ie',
      'label' => 'Ireland',
      'display_label' => 'VAT',
      'territories' => [
        ['country_code' => 'IE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => 'Standard',
          'amounts' => [
            ['amount' => '0.23', 'start_date' => '2012-01-01'],
          ],
          'default' => TRUE,
        ],
      ],
    ];
    $zone = new TaxZone($definition);

    $this->assertEquals($definition['id'], $zone->getId());
    $this->assertEquals($definition['label'], $zone->getLabel());
    $this->assertEquals($definition['display_label'], $zone->getDisplayLabel());
    $this->assertCount(1, $zone->getTerritories());
    $this->assertEquals($definition['territories'][0]['country_code'], $zone->getTerritories()[0]->getCountryCode());
    $this->assertCount(1, $zone->getRates());
    $this->assertEquals($definition['rates'][0]['id'], $zone->getRates()[0]->getId());

    $irish_address = new Address('IE');
    $serbian_address = new Address('RS');
    $this->assertTrue($zone->match($irish_address));
    $this->assertFalse($zone->match($serbian_address));
  }

}

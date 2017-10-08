<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Product entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\Product
 *
 * @group commerce
 */
class ProductTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
  ];

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::isPublished
   * @covers ::setPublished
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getStores
   * @covers ::setStores
   * @covers ::getStoreIds
   * @covers ::setStoreIds
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   */
  public function testProduct() {
    $product = Product::create([
      'type' => 'default',
    ]);
    $product->save();

    $product->setTitle('My title');
    $this->assertEquals('My title', $product->getTitle());

    $this->assertEquals(TRUE, $product->isPublished());
    $product->setPublished(FALSE);
    $this->assertEquals(FALSE, $product->isPublished());

    $product->setCreatedTime(635879700);
    $this->assertEquals(635879700, $product->getCreatedTime());

    $product->setStores([$this->store]);
    $this->assertEquals([$this->store], $product->getStores());
    $this->assertEquals([$this->store->id()], $product->getStoreIds());
    $product->setStores([]);
    $this->assertEquals([], $product->getStores());
    $product->setStoreIds([$this->store->id()]);
    $this->assertEquals([$this->store], $product->getStores());
    $this->assertEquals([$this->store->id()], $product->getStoreIds());

    $product->setOwner($this->user);
    $this->assertEquals($this->user, $product->getOwner());
    $this->assertEquals($this->user->id(), $product->getOwnerId());
    $product->setOwnerId(0);
    $this->assertEquals(NULL, $product->getOwner());
    $product->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $product->getOwner());
    $this->assertEquals($this->user->id(), $product->getOwnerId());
  }

  /**
   * @covers ::getVariationIds
   * @covers ::getVariations
   * @covers ::setVariations
   * @covers ::hasVariations
   * @covers ::addVariation
   * @covers ::removeVariation
   * @covers ::hasVariation
   * @covers ::getDefaultVariation
   */
  public function testVariationMethods() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();

    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation2->save();

    $product = Product::create([
      'type' => 'default',
    ]);
    $product->save();

    // An initially saved variation won't be the same as the loaded one.
    $variation1 = ProductVariation::load($variation1->id());
    $variation2 = ProductVariation::load($variation2->id());

    $variations = [$variation1, $variation2];
    $this->assertEmpty($product->hasVariations());
    $product->setVariations($variations);
    $this->assertNotEmpty($product->hasVariations());
    $variations_match = $variations == $product->getVariations();
    $this->assertNotEmpty($variations_match);
    $variation_ids = [$variation1->id(), $variation2->id()];
    $variation_ids_match = $variation_ids == $product->getVariationIds();
    $this->assertNotEmpty($variation_ids_match);

    $this->assertNotEmpty($product->hasVariation($variation1));
    $product->removeVariation($variation1);
    $this->assertEmpty($product->hasVariation($variation1));
    $product->addVariation($variation1);
    $this->assertNotEmpty($product->hasVariation($variation1));

    $this->assertEquals($product->getDefaultVariation(), $variation2);
    $this->assertNotEquals($product->getDefaultVariation(), $variation1);
  }

  /**
   * Tests variation's canonical URL.
   */
  public function testCanonicalVariationLink() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation1],
    ]);
    $product->save();

    $product_url = $product->toUrl()->toString();
    $variation_url = $variation1->toUrl()->toString();
    $this->assertEquals($product_url . '?v=' . $variation1->id(), $variation_url);
  }

}

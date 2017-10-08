<?php

/**
 * @file
 * Contains Drupal\views_details\Tests\Plugin\StyleDetailsTest.
 */

namespace Drupal\views_details\Tests\Plugin;

use Drupal\views\Tests\Plugin\StyleTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the Details style plugin.
 *
 * @group views_details
 * @see \Drupal\views_details\Plugin\views\style\DetailsStyle
 */
class StyleDetailsTest extends StyleTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_details', 'views_details_test_config');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_style_details');

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('views_details_test_config'));
  }

  /**
   * Make sure that the Details style markup is correct.
   */
  public function testDefaultRowClasses() {
    $view = Views::getView('test_style_details');

    // Check default settings and output of the view style plugin.
    $output = $view->preview();
    $this->storeViewPreview(\Drupal::service('renderer')->render($output));

    /** @var \SimpleXMLElement[] $rows */
    $rows = $this->elements->body->div->div->details;
    $count = 0;
    $count_result = count($view->result);
    foreach ($rows as $row) {
      $count++;
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertTrue(strpos($class, 'views-row') !== FALSE, 'Make sure that the views row has default class.');
      $open = (string) $attributes['open'][0];
      $this->assertTrue(strpos($open, 'open') !== FALSE, 'Make sure that the views details are open by default.');
      $summary = $row->xpath('//summary[@role="button"]');
      $this->assertFalse(empty($summary));
      $wrapper = $row->xpath('//div[@class="details-wrapper"]');
      $this->assertFalse(empty($wrapper));
      $description = $row->xpath('//div/div[@class="details-description"]');
      $this->assertFalse(empty($description));
    }
    $this->assertEqual($count, $count_result);

    // Set all collapsed in style options.
    $view->style_plugin->options['collapsed'] = TRUE;
    $view->style_plugin->options['open_first'] = TRUE;

    $output = $view->preview();
    $this->storeViewPreview(\Drupal::service('renderer')->render($output));
    $count = 0;
    /** @var \SimpleXMLElement[] $rows */
    $rows = $this->elements->body->div->div->details;
    foreach ($rows as $row) {
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertTrue(strpos($class, 'views-row') !== FALSE, 'Make sure that the views row has default class.');
      $count++;
      if ($count != 1) {
        $this->assertFalse(isset($attributes['open']), 'Other then first views details all the other are closed by default.');
      }
      else {
        $open = (string) $attributes['open'][0];
        $this->assertTrue(strpos($open, 'open') !== FALSE, 'Only first views details is open by default.');
      }
    }

    // Set open first FALSE in style options.
    $view->style_plugin->options['open_first'] = FALSE;

    $output = $view->preview();
    $this->storeViewPreview(\Drupal::service('renderer')->render($output));
    /** @var \SimpleXMLElement[] $rows */
    $rows = $this->elements->body->div->div->details;
    foreach ($rows as $row) {
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertTrue(strpos($class, 'views-row') !== FALSE, 'Make sure that the views row has default class.');
      $this->assertFalse(isset($attributes['open']), 'All the views details are closed by default.');
    }

    // Set wrapper class in style options.
    $view->style_plugin->options['row_class'] = 'class';

    $output = $view->preview();
    $this->storeViewPreview(\Drupal::service('renderer')->render($output));
    /** @var \SimpleXMLElement[] $rows */
    $rows = $this->elements->body->div->div->details;
    foreach ($rows as $row) {
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertTrue(strpos($class, 'class') !== FALSE, 'Make sure that the views row class is set right.');
    }

  }

}

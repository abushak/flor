<?php
/**
 * @file
 * ArrayDataItemIterator class file.
 */

namespace Drupal\views_ef_fieldset;

use ArrayIterator;
use RecursiveIterator;

/**
 * Class ArrayDataItemIterator
 */
class ArrayDataItemIterator extends ArrayIterator implements RecursiveIterator {
  /**
   * @return \DataItemIterator
   */
  public function getChildren() {
    $item = $this->current();
    return new ArrayDataItemIterator($item['children']);
  }

  /**
   * @return bool
   */
  public function hasChildren() {
    $item = $this->current();
    return !empty($item['children']);
  }
}

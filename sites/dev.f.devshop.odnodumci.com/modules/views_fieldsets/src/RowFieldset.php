<?php

namespace Drupal\views_fieldsets;

use Drupal\Component\Utility\Html;
use Drupal\views\ResultRow;
use Drupal\views_fieldsets\Plugin\views\field\Fieldset;

class RowFieldset {

  public $row;
  public $properties = [];
  public $children = [];

  /**
   *
   */
  public function __construct($field, ResultRow $row) {
    $this->row = $row;
    $this->properties = get_object_vars($field);
  }

  /**
   *
   */
  public function __isset($name) {
    return TRUE;
  }

  /**
   *
   */
  public function __get($name) {
    if (is_callable($method = [$this, "get_$name"])) {
      return call_user_func($method);
    }

    return @$this->properties[$name];
  }

  /**
   *
   */
  public function get_content() {
    return $this->render();
  }

  /**
   *
   */
  public function get_wrapper_element() {
    return '';
  }

  /**
   *
   */
  public function get_element_type() {
    return '';
  }

  /**
   *
   */
  public function render() {
    // @todo Theme hook suggestions!
    $element = [
      '#theme' => 'views_fieldsets_' . $this->getWrapperType(),
      '#fields' => $this->children,
      '#legend' => $this->getLegend(),
      '#collapsible' => (bool) $this->handler->options['collapsible'],
      '#collapsed' => (bool) $this->handler->options['collapsed'],
      '#classes' => $this->getClasses(),
    ];
    return render($element);
  }

  /**
   *
   */
  protected function getWrapperType() {
    $allowed = Fieldset::getWrapperTypes();
    $wrapper = $this->handler->options['wrapper'];
    if (isset($allowed[$wrapper])) {
      return $wrapper;
    }

    reset($allowed);
    return key($allowed);
  }

  /**
   *
   */
  protected function getLegend() {
    return $this->tokenize($this->handler->options['legend']);
  }

  /**
   *
   */
  protected function getClasses() {
    $classes = explode('  ', $this->handler->options['classes']);
    $classes = array_map(function($class) {
      return Html::getClass($this->tokenize($class));
    }, $classes);
    return implode(' ', $classes);
  }

  /**
   *
   */
  protected function tokenize($string) {
    return $this->handler->tokenizeValue($string, $this->row->index);
  }

  /**
   *
   */
  public function addChild(array $fields, $field_name) {
    $this->children[$field_name] = $fields[$field_name];
  }

}

<?php

namespace Drupal\views_fieldsets\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_fieldsets\RowFieldset;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("fieldset")
 */
class Fieldset extends FieldPluginBase {

  /**
   *
   */
  static public function getUIFieldParents(array $fields, $field_name) {
    $parents = [];
    $current_field = $field_name;
    while ($parent = self::getUIFieldParent($fields, $current_field)) {
      $parents[] = $parent;
      $current_field = $parent;
    }

    return $parents;
  }

  /**
   *
   */
  static public function getUIFieldParent(array $fields, $field_name) {
    return $fields[$field_name];
  }

  /**
   *
   */
  static public function getFieldParents(ViewExecutable $view, $field_name) {
    $parents = [];
    $current_field = $field_name;
    while ($parent = self::getFieldParent($view, $current_field)) {
      $parents[] = $parent;
      $current_field = $parent;
    }

    return $parents;
  }

  /**
   *
   */
  static public function getFieldParent(ViewExecutable $view, $field_name) {
    $fieldsets = self::getAllFieldsets($view);
    foreach ($fieldsets as $fieldset_name => $fieldset) {
      if (in_array($field_name, $fieldset->getChildren())) {
        return $fieldset_name;
      }
    }
  }

  /**
   *
   */
  static public function getWrapperTypes() {
    $types = &drupal_static(__METHOD__);
    if (!$types) {
      // @todo Get from hook_theme() definitions?
      $types = ['details' => 'details', 'fieldset' => 'fieldset', 'div' => 'div'];
    }

    return $types;
  }

  /**
   *
   */
  static public function isFieldsetView(ViewExecutable $view) {
    foreach ($view->field as $field) {
      if ($field instanceof self) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   *
   */
  static public function getAllFieldsets(ViewExecutable $view) {
    return array_filter($view->field, function($field) {
      return $field instanceof self;
    });
  }

  /**
   *
   */
  static public function replaceFieldsetHandlers(ViewExecutable $view, array &$fields, ResultRow $row) {
    $fieldsets = self::getAllFieldsets($view);

    // Replace Fieldsets.
    foreach ($fields as $name => $field) {
      if (isset($fieldsets[$name])) {
        $fields[$name] = new RowFieldset($field, $row);
      }
    }

    // Move Children.
    $moved = [];
    foreach ($fieldsets as $fieldset_name => $fieldset) {
      foreach ($fieldset->getChildren() as $child_name) {
        if (isset($fields[$child_name])) {
          $fields[$fieldset_name]->addChild($fields, $child_name);
          $moved[$child_name] = $child_name;
        }
      }
    }

    // Remove moved Children.
    $fields = array_diff_key($fields, $moved);

    return $fieldsets;
  }

  /**
   *
   */
  public function getChildren() {
    return $this->options['fields'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = ['default' => []];
    $options['wrapper'] = ['default' => 'fieldset'];
    $options['legend'] = ['default' => ''];
    $options['classes'] = ['default' => ''];
    $options['collapsible'] = ['default' => TRUE];
    $options['collapsed'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fake_form = [];
    parent::buildOptionsForm($fake_form, $form_state);
    $form['admin_label'] = $fake_form['admin_label'];

    $form['fields'] = [
      '#type' => 'value',
      '#value' => $this->options['fields'],
    ];

    $help_tokenized = $this->t('With row tokens, eg. <code>{{ title }}</code>.');

    $form['wrapper'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper type'),
      '#options' => self::getWrapperTypes(),
      '#default_value' => $this->options['wrapper'],
      '#required' => TRUE,
    ];

    $form['legend'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fieldset legend'),
      '#default_value' => $this->options['legend'],
      '#description' => $help_tokenized,
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper classes'),
      '#default_value' => $this->options['classes'],
      '#description' => $help_tokenized . ' ' . $this->t('Separate classes with DOUBLE SPACES. Single spaces and much else will be converted to valid class name.'),
    ];

    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsible'),
      '#default_value' => $this->options['collapsible'],
    ];

    $form['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsed'),
      '#default_value' => $this->options['collapsed'],
    ];

    // Available tokens list. Not as pretty as FieldPluginBase, because it doesn't have a reusable method.
    $form['tokens'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Replacement patterns'),
      '#items' => array_map(function($token) {
          return Markup::create("<code>$token</code>");
          // return [
          //   '#type' => 'inline_template',
          //   '#template' => '<code>{{ token }}</code>',
          //   '#context' => ['token' => $token],
          // ];
      }, array_keys($this->getRenderTokens([]))),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // This will be overridden in RowFieldset::render(), which is called by magic through $field->content.
    return '[' . implode('|', $this->getChildren()) . ']';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Don't add this non-field to the query.
  }

  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

}

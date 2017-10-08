<?php

/**
 * @file
 * Contains \Drupal\views_details\Plugin\views\style\DetailsStyle.
 */

namespace Drupal\views_details\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Details style plugin to render rows as details.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "details",
 *   title = @Translation("Details"),
 *   help = @Translation("Displays rows as details."),
 *   theme = "views_view_details",
 *   display_types = {"normal"}
 * )
 */
class DetailsStyle extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {

    $options = parent::defineOptions();
    $options['collapsed'] = array('default' => FALSE);
    $options['open_first'] = array('default' => TRUE);
    $options['title'] = array('default' => '');
    $options['description'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);

    $form['collapsed'] = array(
      '#title' => $this->t('Collapsed by default'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check to show details collapsed.'),
      '#default_value' => $this->options['collapsed'],
      '#weight' => -49,
    );

    $form['open_first'] = array(
      '#title' => $this->t('Leave first fieldset open'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check to leave first fieldset open.'),
      '#default_value' => $this->options['open_first'],
      '#weight' => -48,
      '#states' => array(
        'invisible' => array(
          ':input[name="style_options[collapsed]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $options = array('' => $this->t('- None -'));
    $field_labels = $this->displayHandler->getFieldLabels(TRUE);
    $options += $field_labels;

    $form['title'] = array(
      '#type' => 'select',
      '#title' => $this->t('Details Title'),
      '#options' => $options,
      '#default_value' => $this->options['title'],
      '#description' => $this->t('Choose the title of details.'),
      '#weight' => -47,
    );

    $form['description'] = array(
      '#type' => 'select',
      '#title' => $this->t('Details Description'),
      '#options' => $options,
      '#default_value' => $this->options['description'],
      '#description' => $this->t('Optional details description.'),
      '#weight' => -46,
    );
  }

}

<?php

namespace Drupal\core_views_facets\Form;

use Drupal\facets\Form\FacetForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\core_views_facets\Plugin\facets\facet_source\CoreViewsFacetSourceBase;

/**
 * Provides a form for configuring the processors of a facet.
 */
class ViewsFacetForm extends FacetForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->entity;

    $hard_limit_options = [1, 3, 5, 10, 15, 20, 30, 40, 50, 75, 100, 250, 500];
    $form['facet_settings']['hard_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Hard limit'),
      '#default_value' => $facet->getHardLimit(),
      '#options' => [0 => $this->t('No limit')] + array_combine($hard_limit_options, $hard_limit_options),
      '#description' => $this->t('Display no more than this number of facet items.'),
    ];

    if (!$facet->getFacetSource() instanceof SearchApiDisplay
      && !$facet->getFacetSource() instanceof CoreViewsFacetSourceBase) {
      $form['facet_settings']['hard_limit']['#disabled'] = TRUE;
      $form['facet_settings']['hard_limit']['#description'] .= '<br />';
      $form['facet_settings']['hard_limit']['#description'] .= $this->t('This setting only works with Search API based facets.');
    }

    return $form;
  }

}

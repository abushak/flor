<?php

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class StoreForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $this->entity;

    /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
    $store_storage = $this->entityTypeManager->getStorage('commerce_store');
    $default_store = $store_storage->loadDefault();
    $isDefault = TRUE;
    if ($default_store && $default_store->uuid() != $store->uuid()) {
      $isDefault = FALSE;
    }
    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $isDefault,
      '#disabled' => $isDefault || empty($default_store),
      '#weight' => 99,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    if ($form_state->getValue('default')) {
      /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
      $store_storage = $this->entityTypeManager->getStorage('commerce_store');
      $store_storage->markAsDefault($this->entity);
    }
    drupal_set_message($this->t('Saved the %label store.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_store.collection');
  }

}

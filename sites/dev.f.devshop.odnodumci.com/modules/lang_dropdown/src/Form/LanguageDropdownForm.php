<?php

/**
 * @file
 * Contains \Drupal\lang_dropdown\Form\LanguageDropdownForm.
 */

namespace Drupal\lang_dropdown\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

/**
 * Language Switch Form.
 */
class LanguageDropdownForm extends FormBase {

  protected $languages;
  protected $type;
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lang_dropdown_form';
  }

  /**
   * Constructs a \Drupal\lang_dropdown\Form\LanguageDropdownForm object.
   *
   * @param $languages
   *   The languages for the switcher.
   * @param $type
   *   The type of negotiation.
   * @param $settings
   *   The configuration for the switcher form.
   */
  public function __construct($languages, $type, $settings) {
    $this->languages = $languages;
    $this->type = $type;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $language_url = \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_URL);

    $unique_id = uniqid();

    $options  = $hidden_elements = array();
    $js_settings = array(
      'key' => $unique_id,
    );

    $selected_option_language_icon = $language_selected = $language_session_selected = '';

    $form['lang_dropdown_type'] = array(
      '#type' => 'value',
      '#default_value' => $this->type,
    );
    $form['lang_dropdown_tohome'] = array(
      '#type' => 'value',
      '#default_value' => $this->settings['tohome'],
    );

    $language_objects = \Drupal::languageManager()->getLanguages();

    if ($domain_locale_exists = \Drupal::moduleHandler()->moduleExists('domain_locale')) {
      // TODO: Add domain_locale support when the module is ready for Drupal 8.
      // global $_domain;
      // $domain_languages = domain_locale_lookup($_domain['domain_id']);
    }

    // Now we iterate on $languages to build the needed options for the select element.
    foreach ($this->languages as $lang_code => $lang_options) {

      // The language is not enabled on this domain
      // if ($domain_locale_exists && !array_key_exists($lang_code, $domain_languages)) continue;

      // There is no translation for this language and not all languages are shown
      if (in_array('locale-untranslated', $lang_options['attributes']['class']) && !$this->settings['showall']) continue;

      // Build the options in an associative array, so it will be ready for #options in select form element.
      switch ($this->settings['display']) {
        case LANGDROPDOWN_DISPLAY_TRANSLATED:
        default:
          $options += array($lang_code => $this->t($lang_options['language']->getName()));
          break;
        case LANGDROPDOWN_DISPLAY_NATIVE:
          $options += array($lang_code => $lang_options['language']->getName());
          break;
        case LANGDROPDOWN_DISPLAY_LANGCODE:
          $options += array($lang_code => $lang_code);
          break;
      }

      // Identify selected language
      if (isset($lang_options['url'])) {
        /** @var \Drupal\Core\Url $url */
        $url = $lang_options['url'];
        if ($url->isRouted()) {
          $route_name = $url->getRouteName();
          $is_current_path = ($route_name == '<current>') || ($route_name == \Drupal::routeMatch()->getRouteName()) || ($route_name == '<front>' && \Drupal::service('path.matcher')->isFrontPage());
          $is_current_language = (empty($lang_options['language']) || $lang_options['language']->getId() == $language_url->getId());
          if ($is_current_path && $is_current_language) {
            $language_selected = $lang_code;
          }
        }
      }

      // Identify if session negotiation had set session-active class
      if (in_array('session-active', $lang_options['attributes']['class'])) {
        $language_session_selected = $lang_code;
      }

      // Now we build our hidden form inputs to handle the redirections.
      $url = (isset($lang_options['url']) && $this->settings['tohome'] == 0) ? $lang_options['url'] : Url::fromRoute('<front>');
      if (!isset($lang_options['query'])) {
        $lang_options['query'] = \Drupal::request()->query->all();
      }
      $hidden_elements[$lang_code] = array(
        '#type' => 'hidden',
        '#value' => $url->setOptions($url->getOptions() + $lang_options)->toString(),
      );

      // Handle flags with Language icons module using JS widget.
      if (\Drupal::moduleHandler()->moduleExists('languageicons') && $this->settings['widget']) {
        $languageicons_config = $this->configFactory()->get('languageicons.settings');
        $languageicons_path = $languageicons_config->get('path');
        $js_settings['languageicons'][$lang_code] = file_create_url(str_replace('*', $lang_code, $languageicons_path));
      }
    }

    // If session-active is set that's the selected language otherwise rely on $language_selected
    $selected_option = ($language_session_selected == '') ? $language_selected : $language_session_selected;

    // Icon for the selected language
    if (\Drupal::moduleHandler()->moduleExists('languageicons') && !$this->settings['widget']) {
      // TODO: Update for Drupal 8
//      $selected_option_language_icon = theme('languageicons_icon', array(
//        'language' => (object) array('language' => $selected_option),
//        'title' => $language_names[$selected_option],
//      ));
    }

    // Add required files and settings for JS widget.
    if ($this->settings['widget'] == LANGDROPDOWN_MSDROPDOWN) {
      $js_settings += array(
        'widget' => 'msdropdown',
        'visibleRows' => $this->settings['msdropdown']['visible_rows'],
        'roundedCorner' => $this->settings['msdropdown']['rounded'],
        'animStyle' => $this->settings['msdropdown']['animation'],
        'event' => $this->settings['msdropdown']['event'],
      );

      $selected_skin = $this->settings['msdropdown']['skin'];
      if ($selected_skin == 'custom') {
        $custom_skin = Html::escape($this->settings['msdropdown']['custom_skin']);
        $form['#attached']['library'][] = 'ms-dropdown';
        $js_settings += array(
          'mainCSS' => $custom_skin,
        );
      } else {
        $skins = _lang_dropdown_get_msdropdown_skins();
        $skin_data = $skins[$selected_skin];
        $form['#attached']['library'][] = 'lang_dropdown/' . $selected_skin;
        $js_settings += array(
          'mainCSS' => $skin_data['mainCSS'],
        );
      }
      $form['#attached']['library'][] = 'lang_dropdown/ms-dropdown';
      $form['#attached']['drupalSettings']['lang_dropdown']['ms-dropdown'] = $js_settings;
    }
    else if ($this->settings['widget'] == LANGDROPDOWN_CHOSEN) {
      $js_settings += array(
        'widget' => 'chosen',
        'disable_search' => $this->settings['chosen']['disable_search'],
        'no_results_text' => $this->settings['chosen']['no_results_text'],
      );

      $form['#attached']['library'][] = 'lang_dropdown/chosen';
      $form['#attached']['drupalSettings']['lang_dropdown']['chosen'] = $js_settings;
    }
    else if ($this->settings['widget'] == LANGDROPDOWN_DDSLICK) {
      $form['#attached']['library'][] = 'lang_dropdown/ddslick';
      $selected_skin = $this->settings['ddslick']['skin'];
      $js_settings += array(
        'widget' => 'ddslick',
        'width' => $this->settings['width'],
        'height' => $this->settings['ddslick']['ddslick_height'],
        'showSelectedHTML' => $this->settings['ddslick']['showSelectedHTML'],
        'imagePosition' => $this->settings['ddslick']['imagePosition'],
      );
      $form['#attributes']['class'][] = ($selected_skin == 'custom') ?
        Html::escape($this->settings['ddslick']['custom_skin']) : $selected_skin;
      $form['#attached']['library'][] = 'lang_dropdown/' . $selected_skin;
      $form['#attached']['drupalSettings']['lang_dropdown']['ddslick'] = $js_settings;
    }

    $flag_position = ($this->settings['languageicons']['flag_position']) ? '#suffix' : '#prefix';

    // Now we build the $form array.
    $form['lang_dropdown_select'] = array(
      '#title' => $this->t('Select your language'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#default_value' => isset($selected_option) ? $selected_option : key($options),
      '#options' => $options,
      '#attributes' => array(
        'style' => 'width:' . $this->settings['width'] . 'px',
        'class' => array('lang-dropdown-select-element'),
        'id' => 'lang-dropdown-select-' . $unique_id,
      ),
      '#attached' => array(
        'library' => array('lang_dropdown/lang-dropdown-form'),
      ),
    );

    if (empty($hidden_elements)) return array();

    $form += $hidden_elements;
    if (\Drupal::moduleHandler()->moduleExists('languageicons')) {
      $form['lang_dropdown_select'][$flag_position] = $selected_option_language_icon;
    }

    $form['#attributes']['class'][] = 'lang_dropdown_form';
    $form['#attributes']['class'][] = $this->type;
    $form['#attributes']['id'] = 'lang_dropdown_form_' . $unique_id;
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Go'),
      '#noscript' => TRUE,
      // The below prefix & suffix for gracefull fallback if JavaScript was disabled
      '#prefix' => new FormattableMarkup("<noscript><div>", []),
      '#suffix' => new FormattableMarkup("</div></noscript>", []),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $language_code = $form_state->getValue('lang_dropdown_select');
    $type = $form_state->getValue('lang_dropdown_type');
    $tohome = $form_state->getValue('lang_dropdown_tohome');

    $language_codes = \Drupal::languageManager()->getLanguages();
    if (!array_key_exists($language_code, $language_codes)) return;

    $types = \Drupal::languageManager()->getDefinedLanguageTypesInfo();
    if (!array_key_exists($type, $types)) return;

    $route = \Drupal::service('path.matcher')->isFrontPage() ? '<front>' : '<current>';
    $url = Url::fromRoute($route);
    $languages = \Drupal::languageManager()->getLanguageSwitchLinks($type, $url);

    $language = $languages->links[$language_code];

    $newurl = (isset($language['url']) && $tohome == 0) ? $language['url'] : Url::fromRoute('<front>');

    if (!isset($language['query'])) {
      $language['query'] = \Drupal::request()->query->all();
    }

    $form_state->setRedirect($newurl->getRouteName(), $newurl->getRouteParameters(), $language);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\lang_dropdown\Plugin\Block\LanguageDropdownBlock.
 */

namespace Drupal\lang_dropdown\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\lang_dropdown\Form\LanguageDropdownForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Language dropdown switcher' block.
 *
 * @Block(
 *   id = "language_dropdown_block",
 *   admin_label = @Translation("Language dropdown switcher"),
 *   category = @Translation("System"),
 *   deriver = "Drupal\lang_dropdown\Plugin\Derivative\LanguageDropdownBlock"
 * )
 */
class LanguageDropdownBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * Constructs an LanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.matcher'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'showall' => 0,
      'tohome' => 0,
      'width' => 165,
      'display' => LANGDROPDOWN_DISPLAY_NATIVE,
      'widget' => LANGDROPDOWN_SIMPLE_SELECT,
      'msdropdown' => array(
        'visible_rows' => 5,
        'rounded' => 1,
        'animation' => 'slideDown',
        'event' => 'click',
        'skin' => 'ldsSkin',
        'custom_skin' => '',
      ),
      'chosen' => array(
        'disable_search' => 1,
        'no_results_text' => t('No language match'),
      ),
      'ddslick' => array(
        'ddslick_height' => 0,
        'showSelectedHTML' => 1,
        'imagePosition' => LANGDROPDOWN_DDSLICK_LEFT,
        'skin' => 'ddsDefault',
        'custom_skin' => '',
      ),
      'languageicons' => array(
        'flag_position' => LANGDROPDOWN_FLAG_POSITION_AFTER,
      ),
      'hidden_languages' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['lang_dropdown'] = array(
      '#type' => 'fieldset',
      '#title' => t('Language switcher dropdown settings'),
      '#weight' => 1,
      '#tree' => TRUE,
    );

    $form['lang_dropdown']['showall'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show all enabled languages'),
      '#description' => t('Show all languages in the switcher no matter if there is a translation for the node or not. For languages without translation the switcher will redirect to homepage.'),
      '#default_value' => $this->configuration['showall'],
    );

    $form['lang_dropdown']['tohome'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect to home on switch'),
      '#description' => t('When you change language the switcher will redirect to homepage.'),
      '#default_value' => $this->configuration['tohome'],
    );

    $form['lang_dropdown']['width'] = array(
      '#type' => 'number',
      '#title' => t('Width of dropdown element'),
      '#size' => 8,
      '#maxlength' => 3,
      '#required' => TRUE,
      '#field_suffix' => 'px',
      '#default_value' => $this->configuration['width'],
    );

    $form['lang_dropdown']['display'] = array(
      '#type' => 'select',
      '#title' => t('Display format'),
      '#options' => array(
        LANGDROPDOWN_DISPLAY_TRANSLATED => t('Translated into Current Language'),
        LANGDROPDOWN_DISPLAY_NATIVE => t('Language Native Name'),
        LANGDROPDOWN_DISPLAY_LANGCODE => t('Language Code'),
      ),
      '#default_value' => $this->configuration['display'],
    );

    $form['lang_dropdown']['widget'] = array(
      '#type' => 'select',
      '#title' => t('Output type'),
      '#options' => array(
        LANGDROPDOWN_SIMPLE_SELECT => t('Simple HTML select'),
        LANGDROPDOWN_MSDROPDOWN => t('Marghoob Suleman Dropdown jquery library'),
        LANGDROPDOWN_CHOSEN => t('Chosen jquery library'),
        LANGDROPDOWN_DDSLICK => t('ddSlick library'),
      ),
      '#default_value' => $this->configuration['widget'],
    );

    $form['lang_dropdown']['msdropdown'] = array(
      '#type' => 'fieldset',
      '#title' => t('Marghoob Suleman Dropdown Settings'),
      '#weight' => 1,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[lang_dropdown][widget]"]' => array('value' => LANGDROPDOWN_MSDROPDOWN),
        ),
      ),
    );

    if (!$this->moduleHandler->moduleExists('languageicons')) {
      $form['lang_dropdown']['msdropdown']['#description'] = $this->t('This looks better with <a href=":link">language icons</a> module.', array(':link' => LANGDROPDOWN_LANGUAGEICONS_MOD_URL));
    }

    $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'ms-dropdown');
    if (!empty($library)) {
      $num_rows = array(2, 3, 4, 5 , 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
      $form['lang_dropdown']['msdropdown']['visible_rows'] = array(
        '#type' => 'select',
        '#title' => t('Maximum number of visible rows'),
        '#options' => array_combine($num_rows, $num_rows),
        '#default_value' => $this->configuration['msdropdown']['visible_rows'],
      );

      $form['lang_dropdown']['msdropdown']['rounded'] = array(
        '#type' => 'checkbox',
        '#title' => t('Rounded corners.'),
        '#default_value' => $this->configuration['msdropdown']['rounded'],
      );

      $form['lang_dropdown']['msdropdown']['animation'] = array(
        '#type' => 'select',
        '#title' => t('Animation style for dropdown'),
        '#options' => array(
          'slideDown' => t('Slide down'),
          'fadeIn' => t('Fade in'),
          'show' => t('Show'),
        ),
        '#default_value' => $this->configuration['msdropdown']['animation'],
      );

      $form['lang_dropdown']['msdropdown']['event'] = array(
        '#type' => 'select',
        '#title' => t('Event that opens the menu'),
        '#options' => array('click' => t('Click'), 'mouseover' => t('Mouse Over')),
        '#default_value' => $this->configuration['msdropdown']['event'],
      );

      $msdSkinOptions = array();
      foreach (_lang_dropdown_get_msdropdown_skins() as $key => $value) {
        $msdSkinOptions[$key] = $value['text'];
      }
      $form['lang_dropdown']['msdropdown']['skin'] = array(
        '#type' => 'select',
        '#title' => t('Skin'),
        '#options' => $msdSkinOptions,
        '#default_value' => $this->configuration['msdropdown']['skin'],
      );

      $form['lang_dropdown']['msdropdown']['custom_skin'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom skin name'),
        '#size' => 80,
        '#maxlength' => 55,
        '#default_value' => $this->configuration['msdropdown']['custom_skin'],
        '#states' => array(
          'visible' => array(
            ':input[name="settings[lang_dropdown][msdropdown][skin]"]' => array('value' => 'custom'),
          ),
        ),
      );
    }
    else {
      // todo: fix instructions
      $form['lang_dropdown']['msdropdown']['#description'] = $this->t('You need to download the <a href=":link">Marghoob Suleman Dropdown JavaScript library</a> and extract the entire contents of the archive into the %path directory on your server.', array(':link' => LANGDROPDOWN_MSDROPDOWN_URL, '%path' => 'drupal_root/libraries'));
      $form['lang_dropdown']['msdropdown']['visible_rows'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['msdropdown']['visible_rows'],
      );
      $form['lang_dropdown']['msdropdown']['rounded'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['msdropdown']['rounded'],
      );
      $form['lang_dropdown']['msdropdown']['animation'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['msdropdown']['animation'],
      );
      $form['lang_dropdown']['msdropdown']['event'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['msdropdown']['event'],
      );
      $form['lang_dropdown']['msdropdown']['skin'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['msdropdown']['skin'],
      );
      $form['lang_dropdown']['msdropdown']['custom_skin'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['msdropdown']['custom_skin'],
      );
    }

    $form['lang_dropdown']['languageicons'] = array(
      '#type' => 'fieldset',
      '#title' => t('Language icons settings'),
      '#weight' => 1,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[lang_dropdown][widget]"]' => array('value' => LANGDROPDOWN_SIMPLE_SELECT),
        ),
      ),
    );

    if ($this->moduleHandler->moduleExists('languageicons')) {
      $form['lang_dropdown']['languageicons']['flag_position'] = array(
        '#type' => 'select',
        '#title' => t('Position of the flag when the dropdown is show just as a select'),
        '#options' => array(
          LANGDROPDOWN_FLAG_POSITION_BEFORE => t('Before'),
          LANGDROPDOWN_FLAG_POSITION_AFTER => t('After'),
        ),
        '#default_value' => $this->configuration['languageicons']['flag_position'],
      );
    }
    else {
      $form['lang_dropdown']['languageicons']['#description'] = t('Enable <a href=":link">language icons</a> module to show a flag of the selected language before or after the select box.', array(':link' => LANGDROPDOWN_LANGUAGEICONS_MOD_URL));
      $form['lang_dropdown']['languageicons']['flag_position'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['languageicons']['flag_position'],
      );
    }

    $form['lang_dropdown']['chosen'] = array(
      '#type' => 'fieldset',
      '#title' => t('Chosen settings'),
      '#weight' => 2,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[lang_dropdown][widget]"]' => array('value' => LANGDROPDOWN_CHOSEN),
        ),
      ),
    );

    $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'chosen');
    if (!$this->moduleHandler->moduleExists('chosen') && !empty($library)) {
      $form['lang_dropdown']['chosen']['disable_search'] = array(
        '#type' => 'checkbox',
        '#title' => t('Disable search box'),
        '#default_value' => $this->configuration['chosen']['disable_search'],
      );

      $form['lang_dropdown']['chosen']['no_results_text'] = array(
        '#type' => 'textfield',
        '#title' => t('No Result Text'),
        '#description' => t('Text to show when no result is found on search.'),
        '#default_value' => $this->configuration['chosen']['no_results_text'],
        '#states' => array(
          'visible' => array(
            ':input[name="settings[lang_dropdown][chosen][disable_search]"]' => array('checked' => FALSE),
          ),
        ),
      );
    }
    else {
      $form['lang_dropdown']['chosen']['disable_search'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['chosen']['disable_search'],
      );
      $form['lang_dropdown']['chosen']['no_results_text'] = array(
        '#type' => 'hidden',
        '#value' => $this->configuration['chosen']['no_results_text'],
      );
      if ($this->moduleHandler->moduleExists('chosen')) {
        $form['lang_dropdown']['chosen']['#description'] = t('If you are already using the !chosenmod you must just choose to output language dropdown as a simple HTML select and allow <a href=":link">Chosen module</a> to turn it into a chosen style select.', array(':link' => LANGDROPDOWN_CHOSEN_MOD_URL));
      } else {
        // todo: fix instructions
        $form['lang_dropdown']['chosen']['#description'] = t('You need to download the <a href=":link">Chosen library</a> and extract the entire contents of the archive into the %path directory on your server.', array(':link' => LANGDROPDOWN_CHOSEN_WEB_URL, '%path' => 'drupal_root/libraries'));
      }
    }

    $form['lang_dropdown']['ddslick'] = array(
      '#type' => 'fieldset',
      '#title' => t('ddSlick settings'),
      '#weight' => 3,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[lang_dropdown][widget]"]' => array('value' => LANGDROPDOWN_DDSLICK),
        ),
      ),
    );

    $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'ddslick');
    if (!empty($library)) {
      $form['lang_dropdown']['ddslick']['ddslick_height'] = array(
        '#type' => 'number',
        '#title' => t('Height'),
        '#description' => t('Height in px for the drop down options i.e. 300. The scroller will automatically be added if options overflows the height. Use 0 for full height.'),
        '#size' => 8,
        '#maxlength' => 3,
        '#field_suffix' => 'px',
        '#default_value' => $this->configuration['ddslick']['ddslick_height'],
      );

      if ($this->moduleHandler->moduleExists('languageicons')) {
        $form['lang_dropdown']['ddslick']['showSelectedHTML'] = array(
          '#type' => 'checkbox',
          '#title' => t('Show Flag'),
          '#default_value' => $this->configuration['ddslick']['showSelectedHTML'],
        );

        $form['lang_dropdown']['ddslick']['imagePosition'] = array(
          '#type' => 'select',
          '#title' => t('Flag Position'),
          '#options' => array(
            LANGDROPDOWN_DDSLICK_LEFT => t('left'),
            LANGDROPDOWN_DDSLICK_RIGHT => t('right'),
          ),
          '#default_value' => $this->configuration['ddslick']['imagePosition'],
          '#states' => array(
            'visible' => array(
              ':input[name="settings[lang_dropdown][ddslick][showSelectedHTML]"]' => array('checked' => TRUE),
            ),
          ),
        );
      }
      else {
        //$form['lang_dropdown']['ddslick']['#description'] = t('This looks better with !languageicons module.', array('!languageicons' => l(t('language icons'), LANGDROPDOWN_LANGUAGEICONS_MOD_URL)));
        $form['lang_dropdown']['ddslick']['showSelectedHTML'] = array(
          "#type" => 'hidden',
          "#value" => $this->configuration['ddslick']['showSelectedHTML'],
        );
        $form['lang_dropdown']['ddslick']['imagePosition'] = array(
          "#type" => 'hidden',
          "#value" => $this->configuration['ddslick']['imagePosition'],
        );
      }

      $ddsSkinOptions = array();
      foreach (_lang_dropdown_get_ddslick_skins() as $key => $value) {
        $ddsSkinOptions[$key] = $value['text'];
      }
      $form['lang_dropdown']['ddslick']['skin'] = array(
        '#type' => 'select',
        '#title' => t('Skin'),
        '#options' => $ddsSkinOptions,
        '#default_value' => $this->configuration['ddslick']['skin'],
      );

      $form['lang_dropdown']['ddslick']['custom_skin'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom skin name'),
        '#size' => 80,
        '#maxlength' => 55,
        '#default_value' => $this->configuration['ddslick']['custom_skin'],
        '#states' => array(
          'visible' => array(
            ':input[name="settings[lang_dropdown][ddslick][skin]"]' => array('value' => 'custom'),
          ),
        ),
      );

    }
    else {
      //$form['lang_dropdown']['ddslick']['#description'] = t('You need to download the !ddslick and extract the entire contents of the archive into the %path directory on your server.', array('!ddslick' => l(t('ddSlick library'), LANGDROPDOWN_DDSLICK_WEB_URL), '%path' => 'sites/all/libraries/ddslick'));
      $form['lang_dropdown']['ddslick']['ddslick_height'] = array(
        "#type" => 'hidden',
        "#value" => $this->configuration['ddslick']['ddslick_height'],
      );
      $form['lang_dropdown']['ddslick']['showSelectedHTML'] = array(
        "#type" => 'hidden',
        "#value" => $this->configuration['ddslick']['showSelectedHTML'],
      );
      $form['lang_dropdown']['ddslick']['imagePosition'] = array(
        "#type" => 'hidden',
        "#value" => $this->configuration['ddslick']['imagePosition'],
      );
      $form['lang_dropdown']['ddslick']['skin'] = array(
        "#type" => 'hidden',
        "#value" => $this->configuration['ddslick']['skin'],
      );
      $form['lang_dropdown']['ddslick']['custom_skin'] = array(
        "#type" => 'hidden',
        "#value" => $this->configuration['ddslick']['custom_skin'],
      );
    }

    // configuration options that allow to hide a specific language to specific roles
    $form['lang_dropdown']['hideout'] = array(
      '#type' => 'fieldset',
      '#title' => t('Hide language settings'),
      '#description' => t('Select which languages you want to hide to specific roles.'),
      '#weight' => 4,
    );

    $languages = $this->languageManager->getLanguages();
    $roles = user_roles();

    $role_names = array();
    $role_languages = array();
    foreach ($roles as $rid => $role) {
      // Retrieve role names for columns.
      $role_names[$rid] = new FormattableMarkup($role->label(), []);
      // Fetch languages for the roles.
      $role_languages[$rid] = isset($this->configuration['hidden_languages'][$rid]) ? $this->configuration['hidden_languages'][$rid] : array();
    }

    // Store $role_names for use when saving the data.
    $form['lang_dropdown']['hideout']['role_names'] = array(
      '#type' => 'value',
      '#value' => $role_names,
    );

    $form['lang_dropdown']['hideout']['languages'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Languages')),
      '#id' => 'hidden_languages_table',
      '#sticky' => TRUE,
    );

    foreach ($role_names as $name) {
      $form['lang_dropdown']['hideout']['languages']['#header'][] = array(
        'data' => $name,
        'class' => array('checkbox'),
      );
    }

    foreach ($languages as $code => $language) {
      $options[$code] = '';
      $form['lang_dropdown']['hideout']['languages'][$code]['language'] = array(
        '#type' => 'item',
        '#markup' => $language->getName(),
      );

      foreach ($role_names as $rid => $role) {
        $form['lang_dropdown']['hideout']['languages'][$code][$rid] = array(
          '#title' => $rid . ': ' . $language->getName(),
          '#title_display' => 'invisible',
          '#wrapper_attributes' => array(
            'class' => array('checkbox'),
          ),
          '#type' => 'checkbox',
          '#default_value' => in_array($code,$role_languages[$rid]) ? 1 : 0,
          '#attributes' => array('class' => array('rid-' . $rid)),
          // TODO: review why parents and tree doesn't work properly
          //'#parents' => array($rid, $code),
        );
      }
    }

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockValidate().
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $widget = $form_state->getValue('lang_dropdown')['widget'];
    switch ($widget) {
      case LANGDROPDOWN_MSDROPDOWN:
        $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'ms-dropdown');
        if (empty($library) || (isset($library['js']) && !file_exists($library['js'][0]['data']))) {
          $form_state->setErrorByName('settings', $this->t('You can\'t use <a href=":link">Marghoob Suleman Dropdown</a> output. You don\'t have the library installed.', array(':link' => LANGDROPDOWN_MSDROPDOWN_URL)));
        }
        break;

      case LANGDROPDOWN_CHOSEN:
        $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'chosen');
        if (empty($library) || (isset($library['js']) && !file_exists($library['js'][0]['data']))) {
          $form_state->setErrorByName('settings', $this->t('You can\'t use <a href=":link">Chosen</a> output. You don\'t have the library installed.', array(':link' => LANGDROPDOWN_CHOSEN_MOD_URL)));
        }
        break;

      case LANGDROPDOWN_DDSLICK:
        $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'ddslick');
        if (empty($library) || (isset($library['js']) && !file_exists($library['js'][0]['data']))) {
          $form_state->setErrorByName('settings', $this->t('You can\'t use <a href=":link">ddSlick</a> output. You don\'t have the library installed.', array(':link' => LANGDROPDOWN_DDSLICK_WEB_URL)));
        }
        break;

      default:
        break;
    }
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $lang_dropdown = $form_state->getValue('lang_dropdown');
    $this->configuration['showall'] = $lang_dropdown['showall'];
    $this->configuration['tohome'] = $lang_dropdown['tohome'];
    $this->configuration['width'] = $lang_dropdown['width'];
    $this->configuration['display'] = $lang_dropdown['display'];
    $this->configuration['widget'] = $lang_dropdown['widget'];
    $this->configuration['msdropdown'] = array(
      'visible_rows' => $lang_dropdown['msdropdown']['visible_rows'],
      'rounded' => $lang_dropdown['msdropdown']['rounded'],
      'animation' => $lang_dropdown['msdropdown']['animation'],
      'event' => $lang_dropdown['msdropdown']['event'],
      'skin' => $lang_dropdown['msdropdown']['skin'],
      'custom_skin' => $lang_dropdown['msdropdown']['custom_skin'],
    );
    $this->configuration['chosen'] = array(
      'disable_search' => $lang_dropdown['chosen']['disable_search'],
      'no_results_text' => $lang_dropdown['chosen']['no_results_text'],
    );
    $this->configuration['ddslick'] = array(
      'ddslick_height' => $lang_dropdown['ddslick']['ddslick_height'],
      'showSelectedHTML' => $lang_dropdown['ddslick']['showSelectedHTML'],
      'imagePosition' => $lang_dropdown['ddslick']['imagePosition'],
      'skin' => $lang_dropdown['ddslick']['skin'],
      'custom_skin' => $lang_dropdown['ddslick']['custom_skin'],
    );
    $this->configuration['languageicons'] = array(
      'flag_position' => $lang_dropdown['languageicons']['flag_position'],
    );

    $this->configuration['hidden_languages'] = array();
    foreach($lang_dropdown['hideout']['languages'] as $code => $values) {
      unset($values['language']);
      foreach($values as $rid => $value) {
        if ($value) { $this->configuration['hidden_languages'][$rid][] = $code; }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $library = array();
    switch ($this->configuration['widget']) {
      case LANGDROPDOWN_MSDROPDOWN:
        $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'ms-dropdown');
        break;

      case LANGDROPDOWN_CHOSEN:
        $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'chosen');
        break;

      case LANGDROPDOWN_DDSLICK:
        $library = \Drupal::service('library.discovery')->getLibraryByName('lang_dropdown', 'ddslick');
        break;
    }

    if (empty($library) && ($this->configuration['widget'] != LANGDROPDOWN_SIMPLE_SELECT)) {
      return array();
    }

    $route = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $url = Url::fromRoute($route);
    list(, $type) = explode(':', $this->getPluginId());
    $languages = $this->languageManager->getLanguageSwitchLinks($type, $url);
    $user = \Drupal::currentUser();
    $roles = $user->getRoles();

    foreach ($languages->links as $langcode => $link) {
      $hide_language = true;
      foreach($roles as $key => $role) {
        if (!isset($this->configuration['hidden_languages'][$role]) || !in_array($langcode, $this->configuration['hidden_languages'][$role])) {
          $hide_language = false;
          break;
        }
      }
      if ($hide_language) {
        unset($languages->links[$langcode]['href']);
        $languages->links[$langcode]['attributes']['class'][] = 'locale-untranslated';
      }
    }

    if (empty($languages->links)) { return array(); }

    $lang_dropdown_form = new LanguageDropdownForm($languages->links, $type, $this->configuration);
    $form = \Drupal::formBuilder()->getForm($lang_dropdown_form);

    return array(
      'lang_dropdown_form' => $form,
    );
  }

}

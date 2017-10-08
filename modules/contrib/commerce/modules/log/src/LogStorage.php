<?php

namespace Drupal\commerce_log;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LogStorage extends CommerceContentEntityStorage implements LogStorageInterface {

  /**
   * The log template manager.
   *
   * @var \Drupal\commerce_log\LogTemplateManagerInterface
   */
  protected $logTemplateManager;

  /**
   * Constructs a new CommerceContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_log\LogTemplateManagerInterface $log_template_manager
   *   The log template manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher, LogTemplateManagerInterface $log_template_manager) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $event_dispatcher);
    $this->logTemplateManager = $log_template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.commerce_log_template')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generate(ContentEntityInterface $source, $template_id, array $params = []) {
    $template_plugin = $this->logTemplateManager->getDefinition($template_id);
    $log = $this->create([
      'category_id' => $template_plugin['category'],
      'template_id' => $template_id,
      'source_entity_id' => $source->id(),
      'source_entity_type' => $source->getEntityTypeId(),
      'params' => $params,
    ]);
    return $log;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(ContentEntityInterface $entity) {
    return $this->loadByProperties([
      'source_entity_id' => $entity->id(),
      'source_entity_type' => $entity->getEntityTypeId(),
    ]);
  }

}

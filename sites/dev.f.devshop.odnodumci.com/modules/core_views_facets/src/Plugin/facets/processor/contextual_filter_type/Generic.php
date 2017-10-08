<?php

namespace Drupal\core_views_facets\Plugin\facets\processor\contextual_filter_type;

use Drupal\core_views_facets\CoreViewsFacetsFilterType;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\facets\FacetInterface;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\ViewExecutable;

/**
 * A generic filter type for core views.
 *
 * @CoreViewsFacetsContextualFilterType(
 *   id = "generic",
 *   label = "Generic solution"
 * )
 */
class Generic extends CoreViewsFacetsFilterType {

  /**
   * {@inheritdoc}
   */
  public function prepareQuery(ViewExecutable &$view, HandlerBase $handler, FacetInterface $facet) {
    try {
      return parent::prepareQuery($view, $handler, $facet);
    }
    catch (\Exception $e) {
      watchdog_exception(
        'facets',
        $e,
        t("The core_views_facets module tried at least once to generically handle the unknown views filter type %filter_type and failed."),
        ['%filter_type' => $handler->pluginId],
        RfcLogLevel::NOTICE
      );
      return NULL;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function processDatabaseRow(\stdClass $row, HandlerBase $handler, FacetInterface $facet) {
    $result = parent::processDatabaseRow($row, $handler, $facet);
    $exception = NULL;
    if ($entity_type = $handler->getEntityType()) {
      try {
        switch ($entity_type) {
          case 'taxonomy_term':
            $entity_field = $handler->definition['field_name'];
            break;

          default:
            $entity_field = $handler->realField;
            break;

        }
        $entities = $this->entityTypeManager()->getStorage($entity_type)->loadByProperties([
          $entity_field => $result->getRawValue(),
        ]);
        $entity = reset($entities);
        if ($entity) {
          if ($entity->bundle() != $entity->getEntityTypeId() && $entity->bundle() == $result->getRawValue()) {
            $result->setDisplayValue($result->getRawValue());
          }
          else {
            $result->setDisplayValue($entity->label());
          }
        }
      }
      catch (\Exception $e) {
        $exception = $e;
      }
    }

    if ($exception) {
      watchdog_exception(
        'facets',
        $exception,
        t("The core_views_facets module tried at least once to generically handle the unknown views filter type %filter_type and failed."),
        ['%filter_type' => $handler->pluginId],
        RfcLogLevel::NOTICE
      );
    }

    return $result;
  }

}

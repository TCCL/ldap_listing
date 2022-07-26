<?php

/**
 * DirectorySectionFieldFormatter.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\ldap_listing\Exception;
use Drupal\ldap_listing\DirectoryQuery;

/**
 * Plugin implementation for LDAP Directory Section field formatter.
 *
 * @FieldFormatter(
 *   id = "ldap_listing_directory_section",
 *   label = "LDAP Directory Section Formatter",
 *   field_types = {
 *     "ldap_listing_directory_section"
 *   }
 * )
 */
class DirectorySectionFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items,$langcode = null) {
    $query = \Drupal::service('ldap_listing.query');
    $query->bind();

    $elements = [];
    foreach ($items as $index => $item) {
      try {
        $sectionId = $item->value;
        $section = $query->querySectionCached($sectionId);
      } catch (Exception $ex) {
        $section = [
          'id' => $sectionId,
          'label' => "Error: Failed loading '$sectionId'",
          'abbrev' => '!',
          'error' => true,
          'header' => [],
          'body' => [],
          'footer' => [],
          'weight' => -1,
        ];
      }

      $elements[$index] = [
        '#theme' => 'ldap_listing_directory_listing_field',
        '#attached' => [
          'library' => ['ldap_listing/directory-listing-no-js'],
        ],
        '#cache' => [
          'tags' => [
            DirectoryQuery::CACHE_TAG,
          ],
        ],
        '#section' => $section,
      ];
    }

    return $elements;
  }
}

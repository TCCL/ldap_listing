<?php

/**
 * DirectorySection.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ldap_listing\Support\LineParser;

/**
 * Defines the directory_section entity.
 *
 * @ConfigEntityType(
 *   id = "ldap_listing_directory_section",
 *   label = @Translation("Directory Listing Section"),
 *   handlers = {
 *     "list_builder" = "Drupal\ldap_listing\DirectorySectionEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ldap_listing\Form\DirectorySectionForm",
 *       "edit" = "Drupal\ldap_listing\Form\DirectorySectionForm",
 *       "delete" = "Drupal\ldap_listing\Form\DirectorySectionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   config_prefix = "directory_section",
 *   admin_permission = "administer ldap_listing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ldap_listing_directory_section/add",
 *     "edit-form" = "/admin/structure/ldap_listing_directory_section/{ldap_listing_directory_section}/edit",
 *     "delete-form" = "/admin/structure/ldap_listing_directory_section/{ldap_listing_directory_section}/delete",
 *     "collection" = "/admin/structure/ldap_listing_directory_section"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "abbrev",
 *     "group_dn",
 *     "header_entries",
 *     "footer_entries",
 *     "weight"
 *   }
 * )
 */
class DirectorySection extends ConfigEntityBase implements DirectorySectionInterface {
  /**
   * The weight value used to sort the configuration entity in a list.
   *
   * @var int
   */
  protected $weight;

  /**
   * Creates a new DirectorySection instance.
   *
   * @param array $values
   *   Values.
   * @param string $entity_type
   *   Entity Type.
   */
  public function __construct(array $values,$entity_type) {
    parent::__construct($values,$entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupDN() : string {
    return $this->get('group_dn')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupDN(string $groupDN) {
    $this->set('group_dn',$groupDN);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderEntriesText() : string {
    $entries = $this->get('header_entries');
    return self::makeTextFromEntries($entries);
  }

  /**
   * {@inheritdoc}
   */
  public function setHeaderEntriesFromText(string $text) {
    $entries = self::parseEntriesFromText($text);
    $this->set('header_entries',$entries);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFooterEntriesText() : string {
    $entries = $this->get('footer_entries');
    return self::makeTextFromEntries($entries);
  }

  /**
   * {@inheritdoc}
   */
  public function setFooterEntriesFromText(string $text) {
    $entries = self::parseEntriesFromText($text);
    $this->set('footer_entries',$entries);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() : int {
    return $this->weight ?? 0;
  }

  private static function makeTextFromEntries($entries) : string {
    if (!is_array($entries)) {
      return '';
    }

    $entry2text = function($entry) {
      if (strpos($entry,',') !== false) {
        return '"' . $entry . '"';
      }

      return $entry;
    };

    $text = '';
    foreach ($entries as $entry) {
      $text .= implode(',',array_map($entry2text,$entry));
      $text .= PHP_EOL;
    }

    return $text;
  }

  private static function parseEntriesFromText(string $text) : array {
    $lines = preg_split('/\r\n|\r|\n/',$text);

    $entries = [];
    foreach ($lines as $line) {
      $parser = new LineParser($line);
      $entry = $parser->getTokens();

      if (count(array_filter($entry)) == 0) {
        continue;
      }

      $entries[] = $entry;
    }

    return $entries;
  }
}

<?php

/**
 * DirectorySectionEntityListBuilder.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class DirectorySectionEntityListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Heading');
    $header['abbrev'] = $this->t('Abbreviation');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['label'] = $entity->label();
    $row['abbrev'] = $entity->get('abbrev');

    return $row + parent::buildRow($entity);
  }
}

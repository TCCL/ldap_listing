<?php

/**
 * TweakEntityListBuilder.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ldap_listing\Entity\TweakInterface;

class TweakEntityListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Tweak');
    $header['user'] = $this->t('User');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($entity instanceof TweakInterface) {
      $row = [];

      $row['label'] = $entity->label();
      $row['user'] = $entity->userDn();

      return $row + parent::buildRow($entity);
    }

    return parent::buildRow($entity);
  }
}

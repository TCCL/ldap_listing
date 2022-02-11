<?php

/**
 * TweakManager.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ldap_listing\Entity\Tweak;

class TweakManager {
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Array of lists of tweaks bucketed under section ID.
   *
   * @var array
   */
  private $tweaks;

  /**
   * Creates a new TweakManager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->loadTweaks();
  }

  /**
   * Applies tweaks to the indicated section.
   *
   * @param array &$entries
   *  List of entries to modify by applying tweaks.
   * @param string $sectionId
   *  The section to apply.
   */
  public function applyTweaksToSection(array &$entries,string $sectionId) : void {
    $tweaks = $this->tweaks[$sectionId] ?? [];
    if (empty($tweaks)) {
      return;
    }

    $copy = $entries;
    foreach (array_keys($copy) as $index) {
      $this->applyTweaksToEntryImpl($entries,$copy[$index],$tweaks);
    }
  }

  private function applyTweaksToEntryImpl(array &$entries,
                                          array &$entry,
                                          array $tweaks) : void
  {
    foreach ($tweaks as $tweak) {
      if ($tweak->userDn() != $entry['dn']) {
        continue;
      }

      $tweakInfo = $tweak->getTweakInfo();

      $this->applyOverridesImpl($entry,$tweakInfo['overrides']);

      if (!empty($tweakInfo['relativeToUser']['before'])) {
        $this->applyRelativePositionImpl(
          $entries,
          $entry,
          $tweakInfo['relativeToUser']['before'],
          0
        );
      }

      if (!empty($tweakInfo['relativeToUser']['after'])) {
        $this->applyRelativePositionImpl(
          $entries,
          $entry,
          $tweakInfo['relativeToUser']['after'],
          1
        );
      }

      if (is_numeric($tweakInfo['absolutePosition']) && $tweakInfo['absolutePosition'] >= 0) {
        $this->applyAbsolutePositionImpl($entries,$entry,$tweakInfo['absolutePosition']);
      }

      if ($tweakInfo['isExcluded']) {
        $this->applyExcludeImpl($entries,$entry);
      }
    }
  }

  private function applyOverridesImpl(array &$entry,array $overrideInfo) : void {
    static $MAPPING = [
      'name' => 'name',
      'phone' => 'phone',
      'email' => 'email',
      'jobTitle' => 'title',
    ];

    foreach ($MAPPING as $overrideKey => $entryKey) {
      if (!empty($overrideInfo[$overrideKey])) {
        $entry[$entryKey] = $overrideInfo[$overrideKey];
      }
    }
  }

  private function applyRelativePositionImpl(array &$entries,
                                             array &$entry,
                                             string $matchDn,
                                             int $offset) : void
  {
    foreach ($entries as $index => &$adjacent) {
      if ($adjacent['dn'] == $matchDn) {
        $pos = self::arraySearchKey($entry,$entries,'dn');
        if ($pos !== false) {
          array_splice($entries,$pos,1);
        }

        array_splice($entries,$index + $offset,0,[$entry]);
        break;
      }
    }
  }

  private function applyAbsolutePositionImpl(array &$entries,array &$entry,int $newIndex) {
    $currentIndex = self::arraySearchKey($entry,$entries,'dn');
    if ($currentIndex !== false) {
      array_splice($entries,$currentIndex,1);
    }

    array_splice($entries,$newIndex,0,[$entry]);
  }

  private function applyExcludeImpl(array &$entries,array &$entry) {
    $pos = self::arraySearchKey($entry,$entries,'dn');
    if ($pos !== false) {
      array_splice($entries,$pos,1);
    }
  }

  private function loadTweaks() : void {
    $this->tweaks = [];

    $storage = $this->entityTypeManager->getStorage('ldap_listing_tweak');
    $tweakIds = $storage->getQuery()->execute();

    $configObjects = Tweak::loadMultiple($tweakIds);
    foreach ($configObjects as $id => $tweak) {
      $this->tweaks[ $tweak->sectionId() ][] = $tweak;
    }
  }

  private static function arraySearchKey(array $needle,array $haystack,$key) {
    foreach ($haystack as $pos => $value) {
      if ($value[$key] === $needle[$key]) {
        return $pos;
      }
    }

    return false;
  }
}

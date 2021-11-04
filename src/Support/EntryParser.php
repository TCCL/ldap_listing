<?php

/**
 * EntryParser.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Support;

class EntryParser {
  /**
   * @var string[]|string[][]
   */
  private $entries;

  /**
   * Creates a new EntryParser instance.
   *
   * @param array $entries
   *  Initial entries to set on the parser.
   */
  public function __construct(array $entries = []) {
    $this->entries = $entries;
  }

  /**
   * Gets the entries currently stored in the parser.
   *
   * @return array
   */
  public function getEntries() : array {
    return $this->entries;
  }

  /**
   * Adds an entry to the parser's internal list.
   *
   * @param mixed $entry
   */
  public function addEntry($entry) : void {
    $this->entries[] = $entry;
  }

  /**
   * Parses line entries from a string of text. Each line entry is stored as an
   * array of column entries.
   *
   * @param string $text
   *  The text to parse.
   */
  public function parseText(string $text,bool $keepEmpty = true) : void {
    $lines = preg_split('/\r\n|\r|\n/',$text);

    $entries = [];
    foreach ($lines as $line) {
      $parser = new LineParser($line);
      $entry = $parser->getTokens();
      $filtered = array_filter($entry);

      if (count($filtered) == 0) {
        continue;
      }

      if ($keepEmpty) {
        $entries[] = $entry;
      }
      else {
        $entries[] = $filtered;
      }
    }

    $this->entries = array_merge($this->entries,$entries);
  }

  /**
   * Parses line entries from a string of text. Each column entry is added to
   * the top-level entry list to produce a flat list.
   *
   * @param string $text
   *  The text to parse.
   */
  public function parseTextFlat(string $text,bool $keepEmpty = true) : void {
    $lines = preg_split('/\r\n|\r|\n/',$text);

    $entries = [];
    foreach ($lines as $line) {
      $parser = new LineParser($line);
      $entry = $parser->getTokens();
      $filtered = array_filter($entry);

      if (count($filtered) == 0) {
        continue;
      }

      foreach (( $keepEmpty ? $entry : $filtered ) as $item) {
        $entries[] = $item;
      }
    }

    $this->entries = array_merge($this->entries,$entries);
  }

  /**
   * Generates the text representation of the entries in the parser.
   *
   * @return string
   */
  public function makeText() {
    if (!is_array($this->entries)) {
      return '';
    }

    $entry2text = function($entry) {
      if (strpos($entry,',') !== false) {
        return '"' . $entry . '"';
      }

      return $entry;
    };

    $text = '';
    foreach ($this->entries as $entry) {
      if (is_array($entry)) {
        $text .= implode(',',array_map($entry2text,$entry));
      }
      else {
        $text .= $entry;
      }
      $text .= PHP_EOL;
    }

    return $text;
  }
}

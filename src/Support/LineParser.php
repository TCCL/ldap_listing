<?php

/**
 * LineParser.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Support;

/**
 * Parses input lines used to encode additional directory section entries.
 */
class LineParser {
  private array $tokens = [];

  /**
   * Creates a new LineParser instance.
   *
   * @param string $line
   *  The line to parse.
   */
  public function __construct(string $line) {
    $this->makeTokens(str_split($line));
  }

  /**
   * Gets the list of tokens that was parsed.
   *
   * @return array
   */
  public function getTokens() {
    return $this->tokens;
  }

  private function makeTokens(array $chrs) {
    $i = 0;
    $n = count($chrs);

    $ws = '';
    $token = "";
    $processWhitespace = function() use(&$ws,&$token) {
      if (!empty($token)) {
        $token .= $ws;
      }
      $ws = '';
    };

    while ($i < $n) {
      $c = $chrs[$i];

      if ($c == '"') {
        $processWhitespace();

        $i += 1;
        while ($i < $n && $chrs[$i] != '"') {
          $c = $chrs[$i++];
          if ($c == '\\') {
            if ($i+1 >= $n) {
              throw new LineParserException('Invalid quoted string');
            }
            $escape = $chrs[$i++];
            $token .= $escape;
          }
          else {
            $token .= $c;
          }
        }

        if ($i >= $n) {
          throw new LineParserException('Invalid quoted string');
        }
      }
      else if ($c == ',') {
        if (!empty($token)) {
          $this->tokens[] = $token;
        }
        $token = '';
      }
      else if (ctype_space($c)) {
        $ws .= $c;
      }
      else {
        $processWhitespace();
        $token .= $c;
      }

      $i += 1;
    }

    if (!empty($token)) {
      $this->tokens[] = $token;
    }
  }
}

<?php

/**
 * DirectoryPdf.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Document;

use DateTime;
use TCPDF;

class DirectoryPdf extends TCPDF implements DirectoryPdfInterface, DirectoryPdfHeaderInterface {
  const PORTRAIT = 'P';
  const LANDSCAPE = 'L';

  /**
   * The timestamp denoting when the instance was created.
   *
   * @var DateTime
   */
  private $directoryPdfTs;

  /**
   * File system path of image file to load for header region.
   *
   * @var string
   */
  private $directoryPdfHeaderImagePath;

  /**
   * The provided title text to use in the header.
   *
   * @var string
   */
  private $directoryPdfHeaderTitle;

  /**
   * Creates a new DirectoryPdf instance.
   *
   * @param string $orient
   *  Denotes the orientation of the document. Default is portrait.
   */
  public function __construct(string $orient = self::PORTRAIT) {
    parent::__construct($orient,'in','USLETTER',true,'UTF-8');
    $this->directoryPdfTs = new DateTime;
  }

  /**
   * Implements DirectoryPdfInterface::getFileName().
   */
  public function getFileName() : string {
    $default = 'Directory';

    if (empty($this->directoryPdfHeaderTitle)) {
      $title = $default;
    }
    else {
      $title = $this->directoryPdfHeaderTitle;
      $title = preg_replace('/\s+/','-',$title);
      $title = preg_replace('/[^\-a-zA-Z0-9._]/','',$title);
      $title = preg_replace('/[^a-zA-Z0-9]+$/','',$title);

      if (empty($title)) {
        $title = $default;
      }
    }

    $ts = $this->directoryPdfTs->format('Ymd');
    $name = "$title-$ts.pdf";

    return $name;
  }

  /**
   * Implements DirectoryPdfInterface::generate().
   */
  public function generate() : void {

  }

  /**
   * Implements DirectoryPdfInterface::outputDocument().
   */
  public function outputDocument(string $file = '') : void {
    if (!empty($file)) {
      $this->Output($file,'F');
    }
    else {
      $this->Output($this->getFileName());
    }
  }

  /**
   * Implements DirectoryPdfHeaderInterface::setHeaderImage().
   */
  public function setHeaderImage(string $file) : void {
    $this->DirectoryPdfHeaderImagePath = $file;
  }

  /**
   * Implements DirectoryPdfHeaderInterface::setHeaderTitle().
   */
  public function setHeaderTitle(string $title) : void {
    $this->directoryPdfHeaderTitle = $title;
  }

  /**
   * Overrides TCPDF::Header().
   */
  public function Header() {

  }

  /**
   * Overrides TCPDF::Footer().
   */
  public function Footer() {

  }
}

<?php

/**
 * DirectoryPdf.php
 *
 * ldap_listing
 */

namespace Drupal\ldap_listing\Document;

use DateTime;
use TCPDF;

/**
 * Provides a default DirectoryPdfInterface implementation using TCPDF.
 */
class DirectoryPdf extends TCPDF implements DirectoryPdfInterface, DirectoryPdfHeaderInterface {
  use TCPDFUtilsTrait;

  const PORTRAIT = 'P';
  const LANDSCAPE = 'L';

  const HEADER_HEIGHT = 0.18;
  const ROW_HEIGHT = 0.0833333;
  const ROW_PADDING = 0.025;

  /**
   * The current Drupal user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $directoryPdfCurrentUser;

  /**
   * The timestamp denoting when the instance was created.
   *
   * @var DateTime
   */
  private $directoryPdfTs;

  /**
   * List of sections to render.
   *
   * @var array
   */
  private $directoryPdfSections = [];

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
  private $directoryPdfHeaderTitle = 'Employee Directory';

  /**
   * Creates a new DirectoryPdf instance.
   *
   * @param string $orient
   *  Denotes the orientation of the document. Default is portrait.
   */
  public function __construct(string $orient = 'P') {
    parent::__construct($orient,'in','USLETTER',true,'UTF-8');

    $this->directoryPdfCurrentUser = \Drupal::currentUser();
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
    $TMARGIN = 1.5;
    $BMARGIN = 0.5;
    $LMARGIN = 0.5;
    $RMARGIN = 0.5;

    if ($this->directoryPdfCurrentUser) {
      $name = $this->directoryPdfCurrentUser->getDisplayName();
      $this->SetAuthor("Generated by $name");
    }
    $this->SetTitle($this->directoryPdfHeaderTitle);
    $this->SetSubject('LDAP Listing Employee Directory Document');
    $this->SetKeywords('directory','employee','staff','LDAP');
    $this->SetMargins($LMARGIN,$TMARGIN,$RMARGIN,true);
    $this->SetAutoPageBreak(false);

    $WIDTH = $this->getPageWidth() - $LMARGIN - $RMARGIN;
    $COLUMN_WIDTH = $WIDTH / 2.0 - 0.0625;
    $HEIGHT = $this->getPageHeight() - $TMARGIN - $BMARGIN;
    $LEFT = $LMARGIN;
    $MIDDLE = $LMARGIN + $WIDTH/2.0 + 0.0625;
    $TOP = $TMARGIN;
    $BOT = $this->getPageHeight() - $BMARGIN;

    // Create 'entries' bucket to consolidate all extra entries for height
    // calculation.
    $sections = [];
    foreach ($this->directoryPdfSections as $section) {
      $section['entries'] = array_merge(
        $section['header'],
        $section['body'],
        $section['footer']
      );

      $sections[] = $section;
    }

    // Render sections.
    $index = 0;
    while (true) {
      // Queue up next page to render.
      $queue = [];
      $this->queueSections($queue,$index,$sections,$LEFT,$MIDDLE,$TOP,$BOT);

      if (empty($queue)) {
        break;
      }

      // Render page.
      $this->AddPage();
      foreach ($queue as $entry) {
        list($x,$y,$section) = $entry;
        $this->renderSection($x,$y,$section,$COLUMN_WIDTH);
      }
    }

    // foreach ($this->directoryPdfSections as $section) {
    //   $i = $index++ % 1;

    //   $x =& $px[$i];
    //   $y =& $py[$i];

    //   if ($y + $eh > $BOT_MARGIN) {
    //     if ($y == $TOP) {

    //     }

    //     if ($x != $MIDDLE) {
    //       $x = $MIDDLE;
    //       $y = $TOP;
    //     }
    //     else {
    //       $this->AddPage();
    //       $x = $LEFT;
    //       $y = $TOP;
    //     }
    //   }
    // }
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
   * Implements DirectoryPdfInterface::setDirectorySections().
   */
  public function setDirectorySections(array $sections) : void {
    $this->directoryPdfSections = $sections;
  }

  /**
   * Implements DirectoryPdfHeaderInterface::setHeaderImage().
   */
  public function setHeaderImage(string $file) : void {
    $this->directoryPdfHeaderImagePath = $file;
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
    $w = $this->getPageWidth();
    $x = 0.5;

    // Draw header image if provided.
    if ($this->directoryPdfHeaderImagePath) {
      $this->Image($this->directoryPdfHeaderImagePath,0.5,0.5,2.5);
      $x += 3.0;
    }

    // Draw header title.
    $this->pushFont('b',22.0);
    $this->SetXY($x,0.85);
    $this->drawText($this->directoryPdfHeaderTitle,'L',0);

    // Draw generated message and pagination.

    $this->popFont();
    $this->pushFont('b',10.0);
    $this->SetXY(-3.5,0.5);
    $dt = $this->directoryPdfTs->format('F jS, Y');
    $generatedMessage = "Generated on $dt";
    $this->drawTextHavingWidth($generatedMessage,3.0,'R',0);
    $this->Ln();

    $this->popFont();
    $this->pushFont('',9.0);
    $currentPage = $this->getAliasNumPage();
    $totalPages = $this->getAliasNbPages();
    $pagination = "Page $currentPage of $totalPages";
    // NOTE: When formatting page numbers, TCPDF uses placeholder strings that
    // interfere with string width calculation. To work around this, we use a
    // fixed string, assuming the total page count will never exceed single
    // digits.
    $width = min(3.0,$this->GetStringWidth("Page 1 of 1"));
    $this->SetX(-0.5 - $width);
    $this->drawTextHavingWidth($pagination,$width,'L',0);

    $this->popFont();

    // Draw separator line.

    $this->SetLineStyle(['width' => 0.02, 'color' => [0xdf]]);
    $this->Line(0.5,1.375,$w - 0.5,1.375);
  }

  /**
   * Overrides TCPDF::Footer().
   */
  public function Footer() {

  }

  private function queueSections(array &$queue,int &$index,array &$sections,float $x1,float $x2,float $top,float $bot) {
    $y1 = $top;
    $y2 = $top;

    $done = 0;

    $i = 0;
    while ($index < count($sections)) {
      $i = ($i % 2)+1;
      $vx = "x$i";
      $vy = "y$i";

      $section = $sections[$index];

      // Estimate the height required for the section.
      $hh = (1 + !empty($section['description'])) * self::HEADER_HEIGHT;
      $eh = $hh + (count($section['entries']) + empty($section['entries'])) * (self::ROW_HEIGHT + self::ROW_PADDING);

      if ($$vy + $eh > $bot) {
        // Split section if too big and exceeds half the available height.
        $h = $bot - $$vy;
        if ($h >= ($bot - $top) / 2.0) {
          $n = min(
            (int)(($h - $hh) / (self::ROW_HEIGHT+self::ROW_PADDING)),
            count($section['entries'])
          );

          if ($n >= 2) {
            $first = $section;
            array_splice($first['entries'],$n-1);
            $first['entries'][] = ['cont' => true];

            $second = $section;
            $second['cont'] = true;
            array_splice($second['entries'],0,$n-1);
            $sections[$index] = $second;

            $section = $first;
            $index -= 1;
          }
        }
        else if ($done > 1) {
          break;
        }
        else {
          $done += 1;
          continue;
        }
      }

      $queue[] = [$$vx,$$vy,$section];

      // Add in estimated height plus some padding for spacing.
      $$vy += $eh + 0.15;
      $index += 1;
      $done = 0;
    }
  }

  private function renderSection(float $x,float $y,array $section,float $width) {
    // Render header.
    if (!isset($section['cont'])) {
      $h = self::HEADER_HEIGHT * (1 + !empty($section['description']));
      $this->setFillColorArray([0xee]);
      $this->Rect($x,$y,$width,$h,'FD');
      $this->pushFont('b',10.0);
      $this->SetXY($x,$y);
      $this->drawTextHavingWidth($section['label'],$width/2,'L',0);
      $this->SetXY($x + $width/2,$y);
      $this->drawTextHavingWidth($section['abbrev'],$width/2,'R',0);
      $this->popFont();
      if (!empty($section['description'])) {
        $this->pushFont('b',8.0);
        $this->SetXY($x,$y + self::HEADER_HEIGHT);
        $this->drawTextHavingWidth($section['description'],$width);
        $this->popFont();
      }
    }
    else {
      $h = 0;
    }

    $py = $y + $h + 0.05;

    // Render entries.
    $this->pushFont('',6.0);
    if (empty($section['entries'])) {
      $this->pushFont('i',6.0);
      $this->SetXY($x,$py);
      $this->drawTextHavingWidth('No entries',$width,'C');
      $this->popFont();
    }
    foreach ($section['entries'] as $i => $entry) {
      $this->SetXY($x,$py);

      if (isset($entry['dn'])) {
        $px = $x;
        $c1 = 0.25;

        // Render employee entry.
        $w = $width * $c1;
        $name = $this->fitText($entry['name'],$w);
        $this->drawTextHavingDims($name,$w,self::ROW_HEIGHT,'L',0);
        $px += $w;

        $w = $width * (1-$c1) * 0.75;
        $title = $this->fitText($entry['title'] ?? 'n/a',$w);
        $this->SetX($px);
        $this->drawTextHavingDims($title,$w,self::ROW_HEIGHT,'L',0);
        $px += $w;

        $w = $width * (1-$c1) * 0.25;
        $phone = $this->fitText($entry['phone'] ?? 'n/a',$w);
        $this->SetX($px);
        $this->drawTextHavingDims($phone,$w,self::ROW_HEIGHT,'R',0);
      }
      else if (isset($entry['cont'])) {
        // Render continued note.
        $this->drawTextHavingDims('(Continued on next page)',$width,self::ROW_HEIGHT);
      }
      else {
        $px = $x + $width;

        $static_entry = array_values(array_filter($entry));
        foreach (array_reverse($static_entry) as $index => $item) {
          if ($index == count($static_entry)-1) {
            $style = 'b';
            $this->pushFont($style,6.0);
          }
          else {
            $style = '';
          }

          $length = $this->GetStringWidth($item,'',$style);
          $px -= $length;

          if ($px < $x) {
            break;
          }

          $this->SetX($px);
          $this->drawTextHavingDims($item,$length,self::ROW_HEIGHT,'R',0);
          $px -= 0.1;

          if ($index == count($static_entry)-1) {
            $this->popFont();
          }
        }
      }

      $py += self::ROW_HEIGHT + self::ROW_PADDING;
    }
    $this->popFont();
  }
}

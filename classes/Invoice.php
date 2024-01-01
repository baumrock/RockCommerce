<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFile;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\NullPage;
use ProcessWire\Page;
use ProcessWire\Pagefiles;
use ProcessWire\RockMigrations;
use ProcessWire\RockPdf;
use RockMigrations\MagicPage;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;

class Invoice extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_invoice";
  const prefix = "rockcommerce_invoice_";
  const folders = [
    '/site/templates/RockCommerce/invoice',
    '/site/modules/RockCommerce/invoice',
  ];

  const field_pdf = self::prefix . "pdf";
  const field_num = self::prefix . "num";
  const field_number = self::prefix . "number";

  /** magic */

  public function editFormContent(InputfieldWrapper $form): void
  {
    $rm = rockmigrations();
    $order = $this->getOrder();

    // create PDF on page load
    $sudo = $this->wire->user->isSuperuser();
    if ($this->wire->config->debug and $sudo) $this->createPdf();

    if ($order->id) {
      // add field to show order
      $url = $order->editUrl();
      $form->prepend($rm->inputfield([
        'type' => 'markup',
        'label' => 'Order',
        'icon' => 'check',
        'value' => "<a href='$url'>$url</a>",
        'notes' => 'API: $invoice->getOrder()',
        'columnWidth' => 40,
      ]));
    }

    if ($f = $form->get(self::field_pdf)) {
      $pdf = $f->value;
      if ($pdf instanceof Pagefiles and $pdf->count()) {
        $path = $f->value->first()->filename;
        $url = $rm->toUrl($path, true);
        $f->appendMarkup =
          "<iframe src=$url style='width:100%;height:1200px'></iframe>";
      } else {
        $f->appendMarkup = "No PDF found";
      }
    }
  }

  public function onCreate(): void
  {
    $this->status = 1;
    $this->title = 'Invoice';
    $this->name = $this->wire->pages->names()->uniqueRandomPageName([
      'min' => 4,
      'max' => 12,
    ]);
  }

  public function onSaved(): void
  {
    // create pdf after the page has been saved so that the invoice
    // number and all necessary data is already available
    $this->createPdf();
  }

  public function onSaveReady(): void
  {
    if (!$this->num(1)) {
      // invoice number is empty --> create a new one
      $num = $this->getNextNum();
      $this->set(self::field_num, $num);
      $this->set(self::field_number, $this->getFullNumber($num));
    }
  }

  /**
   * Create the PDF document for this invoice page
   */
  public function createPdf(): void
  {
    // dont create pdf for pages in the trash
    if ($this->isTrash()) return;

    // on the very first run the order is not yet available
    if (!$this->getOrder()->id) return;

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');

    $tpl = $rm->getFile('default.latte', self::folders);
    $html = $this->wire->config->debug
      ? $this->wire->config->paths->assets . "RockPdf/invoice.html"
      : false;

    /** @var RockPdf $pdf */
    $pdf = $this->wire->modules->get('RockPdf');
    if (!$pdf) return;
    $pdf->addStyle(__DIR__ . "/../invoice/_global.less");
    $pdf
      ->addFontFromField(
        page: $this->parent,
        field: InvoiceGroup::field_font,
        name: 'default',
        default: true,
      )
      ->load($tpl, [
        'rockcommerce' => rockcommerce(),
        'order' => $this->getOrder(),
        'invoice' => $this,
      ])
      ->saveHTML($html)
      ->saveToField(
        $this,
        self::field_pdf,
        "{$this->number()}-{$this->name}.pdf"
      );
  }

  /** frontend */

  /**
   * Get the invoice logo
   *
   * This will either return the logo that is uploaded to the invoice group
   * of if that one is not set it will return the logo of the rockcommerce
   * rootPage.
   */
  public function logo()
  {
    return $this->parent->logo(1) ?: rockcommerce()->rootPage()->logo(1);
  }

  /** backend */

  /**
   * Get the full invoice number including the pattern
   */
  public function getFullNumber(): string
  {
    $num = $this->getFormatted(self::field_num);
    $pattern = $this->parent->pattern(1);
    $len = substr_count($pattern, "#");
    $num = str_pad($num, $len, 0, STR_PAD_LEFT);
    $numreplace = str_pad("#", $len, "#");
    $replace = [
      '{y}' => date("y"),
      $numreplace => $num,
    ];
    $number = str_replace(
      array_keys($replace),
      array_values($replace),
      $pattern
    );
    return $number;
  }

  /**
   * Get next invoice number
   * Starts at 001 on new years
   */
  public function getNextNum($start = 1): int
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $last = $this->wire->pages->get([
      'parent' => $this->parent,
      'sort' => "-" . self::field_num,
      ['created', '>=', $rm->firstSecond()],
      ['created', '<=', $rm->lastSecond()],
    ]);
    if (!$last->id) return $start;
    return $last->num(1) + 1;
  }

  /**
   * Get the order that is related to this invoice
   */
  public function getOrder(): Order|NullPage
  {
    return $this->wire->pages->get([
      'template' => Order::tpl,
      Order::field_invoice => $this,
    ]);
  }

  /**
   * Show badges in page list
   */
  public function getPageListLabel()
  {
    $rc = rockcommerce();
    $order = $this->getOrder();
    $label = $rc->badge($this->number(1) ?: 'Invoice');
    if (!"$order") return $label;
    return $label
      . $rc->badge($order->title)
      . $rc->badge($order->net())
      . $rc->badge($order->name());
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_pdf => [
          'type' => 'file',
          'label' => 'PDF',
          'maxFiles' => 1,
          'descriptionRows' => 0,
          'extensions' => 'pdf',
          'icon' => 'file-pdf-o',
          'outputFormat' => FieldtypeFile::outputFormatSingle,
          'collapsed' => Inputfield::collapsedNoLocked,
        ],
        self::field_num => [
          'type' => 'integer',
          'label' => 'Number',
          'icon' => 'sort-numeric-asc',
          'collapsed' => Inputfield::collapsedNoLocked,
          'columnWidth' => 30,
          'notes' => 'API: $invoice->num()',
        ],
        self::field_number => [
          'type' => 'text',
          'label' => 'Full Number',
          'icon' => 'sort-numeric-asc',
          'collapsed' => Inputfield::collapsedNoLocked,
          'columnWidth' => 30,
          'notes' => 'API: $invoice->number()',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_num,
            self::field_number,
            self::field_pdf,
          ],
          'noChildren' => true,
          'noSettings' => true,
          'noParents' => 1,
          'icon' => 'file-pdf-o',
        ],
      ],
    ]);
  }
}

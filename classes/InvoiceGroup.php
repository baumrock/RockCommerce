<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFile;
use ProcessWire\Inputfield;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class InvoiceGroup extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_invoicegroup";
  const prefix = "rockcommerce_invoicegroup_";
  const pattern = "{y}###";

  const field_template = self::prefix . "template";
  const field_font = self::prefix . "font";
  const field_pattern = self::prefix . "pattern";

  /** magic */

  public function onCreate(): void
  {
    $this->title = "Invoice Group";
    $this->status = 1;
    $this->set(self::field_pattern, self::pattern);
  }

  public function onSaveReady(): void
  {
    // set default value for invoice template file
    $tpl = $this->getFormatted(self::field_template);
    if (!$tpl) $this->set(self::field_template, "default.latte");

    // set default pattern for invoice numbers
    $pattern = $this->getFormatted(self::field_pattern);
    if (!$pattern) $this->set(self::field_pattern, self::pattern);
  }

  /** backend */

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_template => [
          'type' => 'text',
          'label' => 'Invoice Template File',
          'icon' => 'code',
        ],
        self::field_font => [
          'type' => 'file',
          'label' => 'Invoice Font',
          'maxFiles' => 0,
          'descriptionRows' => 1,
          'noLang' => true,
          'extensions' => 'ttf',
          'icon' => 'font',
          'outputFormat' => FieldtypeFile::outputFormatArray,
          'notes' => 'Use B and R as font description for bold and regular font file.',
        ],
        self::field_pattern => [
          'type' => 'text',
          'label' => 'Number Pattern',
          'icon' => 'sort-numeric-asc',
          'notes' => 'Default is ' . self::pattern,
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'label' => $this->_('Title'),
              'notes' => '',
              'collapsed' => Inputfield::collapsedNo,
              'columnWidth' => 100,
            ],
            self::field_template,
            self::field_pattern,
            self::field_font,
            RootPage::field_logo => [
              'notes' => 'Logo used for invoices under this parent. When empty the global logo will be used.'
                . "\nAPI: \$invoice->logo()",
            ],
          ],
          'noChildren' => true, // only via api
          'noParents' => false,
          'parentTemplates' => [
            Invoices::tpl,
          ],
          'childTemplates' => [
            Invoice::tpl,
          ],
          'icon' => 'files-o',
          'sortfield' => '-id',
        ],
      ],
    ]);
  }
}

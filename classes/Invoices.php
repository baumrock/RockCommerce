<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Invoices extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_invoices";
  const prefix = "rockcommerce_invoices_";

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
          ],
          'childTemplates' => [
            InvoiceGroup::tpl,
          ],
          'noLang' => 1, // single language
          'noChildren' => false,
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'money',
        ],
      ],
    ]);
  }
}

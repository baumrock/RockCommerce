<?php

namespace RockCommerce;

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
          'childTemplates' => [
            InvoiceGroup::tpl,
          ],
          'noLang' => 1, // single language
          'sortfield' => '-id',
          'icon' => 'cubes',
        ],
      ],
    ]);
  }
}

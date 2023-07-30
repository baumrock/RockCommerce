<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class InvoiceGroup extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_invoicegroup";
  const prefix = "rockcommerce_invoicegroup_";

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'noChildren' => false,
          'childTemplates' => [
            Invoice::tpl,
          ],
          'icon' => 'cubes',
        ],
      ],
    ]);
  }
}

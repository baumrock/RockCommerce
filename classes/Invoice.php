<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Invoice extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_invoice";
  const prefix = "rockcommerce_invoice_";

  /** magic */

  public function onCreate(): void
  {
    $this->status = 1;
  }

  /** backend */

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'noChildren' => true,
          'icon' => 'file-pdf-o',
        ],
      ],
    ]);
  }
}

<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Orders extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_orders";
  const prefix = "rockcommerce_orders_";

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'childTemplates' => [
            Order::tpl,
          ],
          'noLang' => 1, // single language
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'check',
        ],
      ],
    ]);
  }
}

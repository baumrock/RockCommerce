<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class RootPage extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_rootpage";
  const prefix = "rockcommerce_rootpage_";

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'noChildren' => 1, // no children allowed
          'noLang' => 1, // single language
        ],
      ],
    ]);
  }
}

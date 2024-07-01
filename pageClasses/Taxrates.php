<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Taxrates extends Page
{
  use MagicPage;

  const tpl = 'rockcommerce_taxrates';

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title',
          ],
          'childTemplates' => [
            Taxrate::tpl,
          ],
          'noParents' => 1,
        ],
      ],
    ]);
  }
}

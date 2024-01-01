<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Variation extends Page
{
  use MagicPage;

  const tpl = "variation";
  const prefix = "variation_";

  /** magic */

  public function onCreate(): void
  {
    $this->status = 1;
  }

  /** backend */

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
          'noChildren' => 1,
          'parentTemplates' => [
            Variations::tpl,
          ],
          'icon' => 'cube',
        ],
      ],
    ]);
  }
}

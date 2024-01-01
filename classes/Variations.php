<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Variations extends Page
{
  use MagicPage;

  const tpl = "variations";
  const prefix = "variations_";

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
          'childTemplates' => [
            Variation::tpl,
          ],
          'sortfield' => 'title',
          'noChildren' => 0,
          'noParents' => -1, // only one page allowed
          'icon' => 'cubes',
        ],
      ],
    ]);
  }
}

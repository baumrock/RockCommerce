<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Variation extends Page
{
  use MagicPage;

  const tpl = "variation";
  const prefix = "variation_";

  const field_options = self::prefix . "options";

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
      'fields' => [
        self::field_options => [
          'label' => 'Options',
          'type' => 'FieldtypeRepeater',
          'fields' => [
            'title',
          ],
          'repeaterTitle' => '#n: {title}',
          'familyFriendly' => 1,
          'repeaterDepth' => 0,
          'tags' => 'RockCommerce',
          'repeaterAddLabel' => 'Add New Option',
          'columnWidth' => 100,
          'icon' => 'cubes',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title',
            self::field_options,
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

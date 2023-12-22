<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Carts extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_carts";
  const prefix = "rockcommerce_carts_";

  const field_removeafter = self::prefix . "removeafter";

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_removeafter => [
          'type' => 'integer',
          'label' => 'Remove carts after ... days',
          'icon' => 'trash-o',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_removeafter,
          ],
          'childTemplates' => [
            Cart::tpl,
          ],
          'noLang' => 1, // single language
          'noChildren' => 1, // add orders only via api
          'noMove' => 1,
          'noChangeTemplate' => 1,
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'shopping-cart',
        ],
      ],
    ]);
  }
}

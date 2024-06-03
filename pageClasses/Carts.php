<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Carts extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_carts";
  const prefix = "rockcommerce_carts_";

  const removefilled = 60;
  const removeempty = 1;

  const field_removefilled = self::prefix . "removefilled";
  const field_removeempty = self::prefix . "removeempty";

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    if ($f = $form->get(self::field_removefilled)) {
      $f->notes = "0 = Never; Default: " . self::removefilled;
    }
    if ($f = $form->get(self::field_removeempty)) {
      $f->notes = "0 = Never; Default: " . self::removeempty;
    }
  }

  /** frontend */
  /** backend */

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_removeempty => [
          'type' => 'integer',
          'label' => 'Remove empty carts after ... days',
          'icon' => 'trash-o',
          'columnWidth' => 50,
          'inputType' => 'number',
          'size' => 3,
          'min' => 0,
          'max' => 365,
          'zeroNotEmpty' => 1, // 0 != blank
        ],
        self::field_removefilled => [
          'type' => 'integer',
          'label' => 'Remove filled carts after ... days',
          'icon' => 'trash-o',
          'columnWidth' => 50,
          'inputType' => 'number',
          'size' => 3,
          'min' => 0,
          'max' => 365,
          'zeroNotEmpty' => 1, // 0 != blank
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields-' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_removeempty,
            self::field_removefilled,
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

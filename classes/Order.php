<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldForm;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class Order extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_order";
  const prefix = "rockcommerce_order_";

  /** magic */

  public function editForm(InputfieldForm $form): void
  {
    if ($f = $form->get('title')) {
      $f->collapsed = Inputfield::collapsedHidden;
    }
  }

  public function onCreate(): void
  {
    $this->status = 1;
    $this->name = $this->wire->pages->names()->uniqueRandomPageName([
      'min' => 20,
      'max' => 40,
    ]);
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
          'parentTemplates' => [
            Orders::tpl,
          ],
          'noLang' => 1, // single language
        ],
      ],
    ]);
  }
}

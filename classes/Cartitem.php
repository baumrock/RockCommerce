<?php

namespace RockCommerce;

use ProcessWire\Page;
use RockMigrations\MagicPage;

class Cartitem extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_cartitem";
  const prefix = "rockcommerce_cartitem_";

  public function __construct($parent = false)
  {
    try {
      $this->template = $this->wire->templates->get(self::tpl);
      $this->parent = $parent;
    } catch (\Throwable $th) {
    }
    parent::__construct();
  }

  public function onCreate(): void
  {
    $this->name = $this->wire->pages->names->uniqueRandomPageName([
      'page' => $this->parent,
      'min' => 5,
      'max' => 5,
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
          'fields-' => [],
          'parentTemplates' => [
            Cart::tpl,
          ],
          'noLang' => 1, // single language
          'noChildren' => 1,
          'noMove' => 1,
          'noChangeTemplate' => 1,
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'cube',
          'noSettings' => 1,
        ],
      ],
    ]);
  }
}

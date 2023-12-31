<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\Page;
use RockMigrations\MagicPage;

class Cartitem extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_cartitem";
  const prefix = "rockcommerce_cartitem_";

  const field_item = self::prefix . "item";
  const field_amount = self::prefix . "amount";

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
      'fields' => [
        self::field_item => [
          'type' => 'page',
          'label' => 'Item',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageAutocomplete',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
        ],
        self::field_amount => [
          'type' => 'integer',
          'label' => 'Amount',
          'icon' => 'list-ol',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields-' => [
            self::field_item => ['columnWidth' => 50],
            self::field_amount => ['columnWidth' => 50],
          ],
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

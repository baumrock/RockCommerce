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

  const field_page = self::prefix . "page";
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

  public function onSaved(): void
  {
    if ($this->isTrash()) return;
    $this->meta('rockcommerce-cart', $this->parent->id);
  }

  /** frontend */

  public function cart(): Cart
  {
    if ($this->isTrash()) {
      $cartID = $this->meta('rockcommerce-cart');
      return $this->wire->pages->get($cartID);
    }
    return $this->parent;
  }

  public function page(): Page|false
  {
    return $this->getFormatted(self::field_page);
  }

  /** backend */

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_page => [
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
            self::field_page => ['columnWidth' => 50],
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

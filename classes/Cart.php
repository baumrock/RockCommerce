<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\PageArray;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\rockcommerce;

class Cart extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_cart";
  const prefix = "rockcommerce_cart_";

  const field_items = self::prefix . "items";

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    $form->process->headline("Cart #" . $this->name);

    $f = $form->get(self::field_items);
    if ($f && $this->wire->user->isSuperuser()) {
      $f->notes = "<a href='/rc/show-cart?id={$this->name}'>Show this cart on the frontend</a>";
      $f->entityEncodeText = false;
    }
  }

  public function onCreate(): void
  {
    $this->name = $this->wire->pages->names->uniqueRandomPageName([
      'page' => rockcommerce()->cartsPage(),
      'max' => 10,
      'min' => 5,
    ]);
  }

  public function pageListLabel()
  {
    return $this->name;
  }

  /** frontend */

  public function items(): PageArray
  {
    return $this->children();
  }

  /** backend */

  public function addItem(): Cartitem
  {
    $item = new Cartitem($this);
    $item->save();
    return $item;
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_items => [
          'type' => 'RockGrid',
          'label' => 'Cart Items',
          'grid' => 'Cartitems',
          'icon' => 'shopping-cart',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_items,
          ],
          'parentTemplates' => [
            Carts::tpl,
          ],
          'childTemplates' => [
            Cartitem::tpl,
          ],
          'noLang' => 1, // single language
          'noChildren' => 1,
          'noMove' => 1,
          'noChangeTemplate' => 1,
          'noParents' => 1,
          'sortfield' => '-id',
          'icon' => 'shopping-basket',
          'noSettings' => 1,
        ],
      ],
    ]);
  }
}

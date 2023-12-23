<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\PageArray;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;

class Cart extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_cart";
  const prefix = "rockcommerce_cart_";

  const field_items = self::prefix . "items";
  const field_order = self::prefix . "order";

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    $form->process->headline("Cart #" . $this->name);
    $rm = rockmigrations();

    // modify items field
    $f = $form->get(self::field_items);
    if ($f) {
      if ($this->items()->count()) {
        if ($this->wire->user->isSuperuser()) {
          $f->notes = "<a href='/rc/show-cart?id={$this->name}'>Show this cart on the frontend</a>";
          $f->entityEncodeText = false;
        }
      } else {
        $form->insertBefore($rm->inputfield([
          'type' => 'markup',
          'label' => $f->label,
          'icon' => $f->icon,
          'value' => "<div class='uk-alert uk-alert-primary uk-margin-remove uk-text-center'>This cart is empty.</div>",
        ]), $f);
        $form->remove($f);
      }
    }

    // add info field
    if ($f = $form->get(self::field_order)) {
      $f->columnWidth = 50;
      $form->insertBefore($rm->inputfield([
        'type' => 'markup',
        'label' => 'Info',
        'icon' => 'info',
        'value' => "Created: " . date("d.m.Y H:i:s", $this->created) . "<br>"
          . "Modified: " . date("d.m.Y H:i:s", $this->modified()),
        'columnWidth' => 50,
      ]), $f);
    }
  }

  public function onCreate(): void
  {
    // set random pagename
    $this->name = $this->wire->pages->names->uniqueRandomPageName([
      'page' => rockcommerce()->cartsPage(),
      'min' => 10,
      'max' => 20,
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

  /**
   * Get modified timestamp of this cart
   */
  public function modified(): int
  {
    $pages = $this->items()->add($this);
    return $pages->sort("modified")->last()->modified;
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
        self::field_order => [
          'type' => 'page',
          'label' => 'Order',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageAutocomplete',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'collapsed' => Inputfield::collapsedNoLocked,
          'icon' => 'shopping-bag',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            self::field_order,
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

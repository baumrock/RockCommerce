<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\NullPage;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmigrations;
use function ProcessWire\rockmoney;
use function ProcessWire\wire;

class Cart extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_cart";
  const prefix = "rockcommerce_cart_";

  const field_order = self::prefix . "order";

  private $cachedItems;

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    $form->process->headline("Cart #" . $this->name);
    $rm = rockmigrations();

    // modify items field
    // $f = $form->get(self::field_items);
    // if ($f) {
    //   if ($this->items()->count()) {
    //     if ($this->wire->user->isSuperuser()) {
    //       $f->notes = "<a href='/rc/show-cart?id={$this->name}'>Show this cart on the frontend</a>";
    //       $f->entityEncodeText = false;
    //     }
    //   } else {
    //     $form->insertBefore($rm->inputfield([
    //       'type' => 'markup',
    //       'label' => $f->label,
    //       'icon' => $f->icon,
    //       'value' => "<div class='uk-alert uk-alert-primary uk-margin-remove uk-text-center'>This cart is empty.</div>",
    //     ]), $f);
    //     $form->remove($f);
    //   }
    // }

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

  public function ___totalNet(): Money
  {
    return $this->itemsNet()->plus($this->shippingNet());
  }

  public function totalVat(): Money
  {
    return $this->totalNet()->times($this->taxrate());
  }

  public function totalGross(): Money
  {
    return $this->totalNet()->plus($this->totalVat());
  }

  public function ___shippingNet(): Money
  {
    return rockmoney()->parse(0);
  }

  public function shippingVat(): Money
  {
    return $this->shippingNet()->times($this->taxrate());
  }

  public function shippingGross(): Money
  {
    return $this->shippingNet()->plus($this->shippingVat());
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

  public function ___getJsonArray(): array
  {
    return [
      'id' => $this->name,
      'count' => $this->itemsCount(),
      'items' => $this->items()->getJsonArray(),
      'addedAmount' => $this->addedAmount,

      // item prices
      'itemsNet' => $this->itemsNet()->format(),
      'itemsVat' => $this->itemsVat()->format(),
      'itemsGross' => $this->itemsGross()->format(),

      // final prices (all items + shipping + coupons)
      'totalNet' => $this->totalNet()->format(),
      'totalVat' => $this->totalVat()->format(),
      'totalGross' => $this->totalGross()->format(),

      // shipping prices
      'shippingNet' => $this->shippingNet()->format(),
      'shippingVat' => $this->shippingVat()->format(),
      'shippingGross' => $this->shippingGross()->format(),
    ];
  }

  public function getItem(int|string|Page $id): Item|NullPage
  {
    return wire()->pages->get([
      'parent' => $this,
      'id' => (string)$id,
    ]);
  }

  public function item(string $variation = null): Page|false
  {
    // create selector as string
    // this is to make it match pipes in the value and not treat them as OR
    $selector = Item::field_variation . "='$variation'";
    $item = $this->wire->pages->get("parent={$this},$selector");
    if (!$item->id) return false;
    return $item;
  }

  public function items(): ItemsArray
  {
    if ($this->cachedItems) return $this->cachedItems;
    $this->cachedItems = (new ItemsArray())->import($this->children());
    return $this->cachedItems;
  }

  /**
   * Get total number of items in the cart
   *
   * This is different than $cart->items()->count() as it will also
   * take every items's amount into account.
   */
  public function itemsCount(): int
  {
    $total = 0;
    foreach ($this->items() as $item) {
      $total += $item->amount();
    }
    return $total;
  }

  /**
   * Get modified timestamp of this cart
   */
  public function modified(): int
  {
    $pages = $this->items()->add($this);
    return $pages->sort("modified")->last()->modified;
  }

  public function addItem(
    string|int|Page $product,
    int $amount = null,
    string $variation = null,
  ): Item {
    if (!$amount) $amount = 1;
    $product = rockcommerce()->getProduct($product);

    // try to find existing item
    $item = $this->item($variation);
    if ($item) {
      // increase amount
      $item->of(false);
      $item->set(Item::field_amount, $item->amount() + $amount);
    } else {
      // create new item
      $item = new Item($this);
      $item->set(Item::field_product, $product);
      $item->set(Item::field_amount, $amount);
      $item->set(Item::field_variation, $variation);
    }
    $item->save();

    // $this->wire->session->rcAddedAmount = $amount;
    // $this->wire->session->rcAddedProduct = $product->id;
    $item->addedAmount = $amount;

    return $item;
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        // self::field_items => [
        //   'type' => 'RockGrid',
        //   'label' => 'Cart Items',
        //   'grid' => 'Cartitems',
        //   'icon' => 'shopping-cart',
        // ],
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
            Taxrate::field_taxrate,
            // self::field_items,
          ],
          'parentTemplates' => [
            Carts::tpl,
          ],
          'childTemplates' => [
            Item::tpl,
          ],
          'noLang' => 1, // single language
          'noChildren' => 0, // may have children
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

  public function setTaxrate(float $taxrate): void
  {
    $this->setAndSave(Taxrate::field_taxrate, $taxrate);
  }

  /**
   * Return taxrate, eg 0.2 for 20%
   */
  public function taxrate(): float
  {
    return ($this->getFormatted(Taxrate::field_taxrate) ?: 0) / 100;
  }

  /**
   * Total of all items (without shipping or coupons)
   */
  public function itemsNet(): Money
  {
    $total = rockmoney()->parse(0);
    foreach ($this->items() as $item) {
      $total = $total->plus($item->totalNet());
    }
    return $total;
  }

  /**
   * Total of all items (without shipping or coupons)
   */
  public function itemsVat(): Money
  {
    $total = rockmoney()->parse(0);
    foreach ($this->items() as $item) {
      $total = $total->plus($item->totalVat());
    }
    return $total;
  }

  /**
   * Total of all items (without shipping or coupons)
   */
  public function itemsGross(): Money
  {
    $total = rockmoney()->parse(0);
    foreach ($this->items() as $item) {
      $total = $total->plus($item->totalGross());
    }
    return $total;
  }
}

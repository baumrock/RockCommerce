<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\NullPage;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use ProcessWire\WireData;
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
  const field_taxid = self::prefix . "taxid";

  private $cachedItems;


  public function addItem(
    string|int|Page $product,
    int $amount = null,
    string $variation = null,
  ): Item {
    if (!$amount) $amount = 1;
    $product = rockcommerce()->getProduct($product);

    // try to find existing item
    $item = $this->item($product, $variation);
    // bd($item, 'item in additem');
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

  public function createOrder(
    string $subject = '',
    string $email = '',
    array $pageProperties = [],
    array $details = [],
  ): Order {

    $_details = ['cartData' => $this->getJsonArray()];
    $mergedDetails = array_merge($_details, $details);

    $order = rockcommerce()->createOrder(
      subject: $subject,
      net: $this->totalNet(),
      vat: $this->totalVat(),
      total: $this->totalGross(),
      email: $email,
      pageProperties: $pageProperties,
      details: $mergedDetails,
    );
    $order->setAndSave(Order::field_cart, $this);
    return $order;
  }

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

  /**
   * Array data used for AJAX calls and to store as persistent data
   * on the order page.
   */
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

      // shipping prices
      'shippingNet' => $this->shippingNet()->format(),
      'shippingVat' => $this->shippingVat()->format(),
      'shippingGross' => $this->shippingGross()->format(),

      // final prices (all items + shipping + coupons)
      'totalNet' => $this->totalNet()->format(),
      'totalVat' => $this->totalVat()->format(),
      'totalGross' => $this->totalGross()->format(),
    ];
  }

  public function getJsonData(): WireData
  {
    return (new WireData())->setArray($this->getJsonArray());
  }

  public function getItem(int|string|Page $id): Item|NullPage
  {
    return wire()->pages->get([
      'parent' => $this,
      'id' => (string)$id,
    ]);
  }

  public function item(int|string|Page $product, string $variation = null): Page|false
  {
    // create selector as string
    // this is to make it match pipes in the value and not treat them as OR
    $selector = [
      'parent' => $this,
      Item::field_product => (string)$product,
    ];
    if ($variation) $selector[Item::field_variation] = $variation;
    $item = $this->wire->pages->get($selector);
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
   * Minimalistic HTML table of items to be used in the checkout summary
   * or order confirmation email. Use hooks to modify to your needs!
   *
   * $wire->addHookBefore('Cart::itemsTable', function(HookEvent $event) {
   *   $event->replace = true;
   *   $cart = $event->object;
   *   $event->return = "modified table";
   * });
   */
  public function ___itemsTable(): string
  {
    $str = "<table class='uk-table uk-table-small uk-table-striped'>";
    foreach ($this->items() as $item) {
      $str .= "<tr>";
      $str .= "<td>
        <div><strong>{$item->product()->title}</strong></div>
        <div>{$item->variationList()}</div>
        <div>{$item->amount()}x {$item->itemNet()}</div>
      </td>";
      $str .= "</tr>";
    }
    $str .= "</table>";
    return $str;
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
   * Get modified timestamp of this cart
   */
  public function modified(): int
  {
    $pages = $this->items()->add($this);
    return $pages->sort("modified")->last()->modified;
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
        self::field_taxid => [
          'type' => 'page',
          'label' => 'Taxrate',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageListSelect',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'collapsed' => Inputfield::collapsedNoLocked,
          'icon' => 'percent',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            self::field_order,
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_taxid,
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
          // sort by id
          // this is to make sure items are always in the same order
          // and later added items are further down in the list
          'sortfield' => 'id',
          'icon' => 'shopping-basket',
          'noSettings' => 1,
        ],
      ],
    ]);
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

  public function setTaxID(int|string $id): void
  {
    $taxrate = wire()->pages->get([
      'id' => $id,
      'template' => Taxrate::tpl,
      'parent' => rockcommerce()->taxratesPage(),
    ]);
    $this->setAndSave(self::field_taxid, $taxrate);
  }

  /**
   * Minimalistic summary of the cart
   * To be used in the checkout summary or order confirmation email.
   * Use hooks or RockFrontend's domtools to modify the output.
   */
  public function ___summary(): string
  {
    return "<table>
      <tr class='items'>
        <td>Subtotal</td>
        <td>{$this->itemsNet()}</td>
      </tr>
      <tr class='shipping'>
        <td>Shipping</td><td>{$this->shippingNet()}</td>
      </tr>
      <tr class='vat'>
        <td>VAT</td>
        <td>{$this->totalVat()}</td>
      </tr>
      <tr class='total'>
        <td>Total</td>
        <td>{$this->totalGross()}</td>
      </tr>
    </table>";
  }

  public function taxID(): int
  {
    return (int)$this->getFormatted(self::field_taxid . ".first")?->id;
  }

  /**
   * Return taxrate, eg 0.2 for 20%
   */
  public function taxrate(): float
  {
    $taxPage = wire()->pages->get($this->taxID());
    if (!$taxPage instanceof Taxrate) return 0;
    return $taxPage->taxrate();
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
}

<?php

namespace RockCommerce;

use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\Template;
use RockFrontend\FieldMethod;
use RockMigrations\MagicPage;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockfrontend;
use function ProcessWire\rockmigrations;
use function ProcessWire\rockmoney;

/**
 * RockCommerce Cart or Order Item
 */

class Item extends Page
{
  use MagicPage;
  use FieldMethod;

  const tpl = "rockcommerce_item";
  const prefix = "rockcommerce_item_";

  const field_product = self::prefix . "product";
  const field_amount = self::prefix . "amount";
  const field_variation = self::prefix . "variation";

  public function __construct($parent = false)
  {
    try {
      $this->template = $this->wire->templates->get(self::tpl);
      $this->parent = $parent;
    } catch (\Throwable $th) {
    }
    parent::__construct();
  }

  /** magic */

  public function editForm(InputfieldWrapper $form): void
  {
    if ($f = $form->get(self::field_product)) {
      $product = $f->value;
      if ($product && $product->id) {
        $f->entityEncodeText = false;
        $f->notes = "<a href={$product->editUrl()} target=_blank class=uk-margin-small-right>" . $this->_('edit') . "</a>"
          . "<a href={$product->url()} target=_blank>" . $this->_('view') . "</a>";
      }
    }
  }

  public function onCreate(): void
  {
    $this->name = $this->wire->pages->names->uniqueRandomPageName([
      'min' => 10,
      'max' => 15,
    ]);
  }

  public function onSaved(): void
  {
    if ($this->isTrash()) return;
    $this->meta('rockcommerce-cart', $this->parent->id);
  }

  /** end magic */

  public function amount(): int
  {
    return $this->field('amount', 'int') ?: 1;
  }

  public function cart(): Cart
  {
    if ($this->isTrash()) {
      $cartID = $this->meta('rockcommerce-cart');
      return $this->wire->pages->get($cartID);
    }
    return $this->parent;
  }

  /**
   * Hookable method that returns the title of the cart item
   * as shown in the cart. By default this will be the title of the
   * linked product.
   * @return string
   */
  public function ___cartTitle(): string
  {
    $product = $this->field('product', 'first');
    if (!$product) return '';
    return (string)$product->title;
  }

  /**
   * Hookable method that returns the description of the cart item
   * as shown in the cart
   * @return string
   */
  public function ___cartDescription(): string
  {
    return "";
  }

  public function fullUrl(): string
  {
    return $this->field('product', 'first')->url()
      . '#' . $this->variationString()
      . '-amount:' . $this->amount();
  }

  /**
   * Returns the cart item as an array (eg for ajax endpoints)
   * @return array
   */
  public function ___getJsonArray(): array
  {
    /**
     * Data sent to the frontend and stored in localstorage
     */
    return [
      'id' => $this->id,
      'title' => $this->cartTitle(),
      'description' => $this->cartDescription(),
      'product' => $this->field('product', 'int'),
      'amount' => $this->amount(),
      'variation' => $this->variationString(),
      'variationTable' => $this->variationTable(),

      // urls to item's referenced product
      'url' => $this->fullUrl(),
      'shortUrl' => $this->shortUrl(),
      // has to be populated via hook
      // this is individual for each project
      'pic' => '',

      // prices
      'itemNet' => $this->itemNet()->format(),
      'itemVat' => $this->itemVat()->format(),
      'itemGross' => $this->itemGross()->format(),
      'totalNet' => $this->totalNet()->format(),
      'totalVat' => $this->totalVat()->format(),
      'totalGross' => $this->totalGross()->format(),
    ];
  }

  public function html($str)
  {
    return rockfrontend()->html($str);
  }

  /**
   * Net price of this item (amount=1)
   */
  public function itemNet(): Money
  {
    // get base price from product
    $product = $this->field('product', 'first');
    return $product->priceNet($this->variationString());
  }

  /**
   * Vat of this item (amount=1)
   */
  public function itemVat(): Money
  {
    return $this->itemNet()->times($this->parent->taxrate());
  }

  /**
   * Gross price of this item (amount=1)
   */
  public function itemGross(): Money
  {
    return $this->itemNet()->plus($this->itemVat());
  }

  public function migrate()
  {
    $rm = rockmigrations();
    $rm->migrate([
      'fields' => [
        self::field_product => [
          'type' => 'page',
          'label' => 'Product',
          'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
          'inputfield' => 'InputfieldPageAutocomplete',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'collapsed' => Inputfield::collapsedNo,
          'icon' => 'link',
        ],
        self::field_amount => [
          'type' => 'integer',
          'label' => 'Amount',
          'icon' => 'list-ol',
        ],
        self::field_variation => [
          'type' => 'text',
          'label' => 'Variation',
          'icon' => 'cubes',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            // hide the title field
            // removing it causes an error even when flag system is set :(
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            self::field_product => ['columnWidth' => 50],
            self::field_amount => ['columnWidth' => 50],
            self::field_variation,
          ],
          'parentTemplates' => [
            Cart::tpl,
            Order::tpl,
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

  /**
   * Set the amount of the item
   * Can later get checks if the amount is valid
   */
  public function setAmount(int $amount): void
  {
    $this->setAndSave(self::field_amount, $amount);
  }

  public function shortUrl(): string
  {
    return $this->field('product', 'first')->url();
  }

  public function totalNet(): Money
  {
    return $this->itemNet()->times($this->amount());
  }

  public function totalVat(): Money
  {
    return $this->itemVat()->times($this->amount(1));
  }

  public function totalGross(): Money
  {
    return $this->itemGross()->times($this->amount(1));
  }

  public function variationString(): string
  {
    return $this->field('variation', 'string');
  }

  public function variationTable(): array
  {
    $str = $this->field('variation', 'string');
    return rockcommerce()->variationTable($str);
  }
}

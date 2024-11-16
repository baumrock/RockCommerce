<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockFrontend\FieldMethod;
use RockMigrations\MagicPage;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockfrontend;
use function ProcessWire\rockmigrations;

/**
 * RockCommerce Cart or Order Item
 */

class Item extends Page
{
  use MagicPage;
  use FieldMethod;

  const tpl = "rockcommerce_item";

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
    if ($f = $form->get(RockCommerce::field_product)) {
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

  public function getPageListLabel()
  {
    return $this->amount() . 'x ' . ($this->product()->title ?: $this->name);
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
      'variationTableData' => $this->variationTableData(),

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
      'templates' => [
        self::tpl => [
          'fields' => [
            // hide the title field
            // removing it causes an error even when flag system is set :(
            'title' => [
              'collapsed' => Inputfield::collapsedHidden,
            ],
            RockCommerce::field_product => ['columnWidth' => 50],
            RockCommerce::field_amount => ['columnWidth' => 50],
            RockCommerce::field_variationstr,
          ],
          'parentTemplates' => [
            RockCommerce::template_cart,
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

  public function product(): Page|false
  {
    return $this->field('product', 'first');
  }

  /**
   * Set the amount of the item
   * Can later get checks if the amount is valid
   */
  public function setAmount(int $amount): void
  {
    $this->setAndSave(RockCommerce::field_amount, $amount);
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

  /**
   * List of variations to be used in cart summary and order confirmation
   *
   * Example:
   * - Color: Red
   * - Size: M
   */
  public function variationList(): string
  {
    $data = $this->variationTableData();
    $str = '';
    foreach ($data as $var) {
      $str .= " - " . $var['variation'] . ": " . implode('; ', $var['options']) . "\n";
    }
    return $str;
  }

  /**
   * Return variation string like 2030:4040,3020,3029
   */
  public function variationString(): string
  {
    return (string)$this->getFormatted(RockCommerce::field_variationstr);
  }

  /**
   * Get array representation of variation string with translated
   * variation and option labels
   */
  public function variationTableData(): array
  {
    return rockcommerce()->variationTableData(
      $this->variationString()
    );
  }
}

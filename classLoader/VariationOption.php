<?php

namespace RockCommerce;

use ProcessWire\RepeaterPage;
use ProcessWire\RockMoney;
use ProcessWire\WireData;
use ProcessWire\WireException;

use function ProcessWire\rockcommerce;
use function ProcessWire\rockmoney;

class VariationOption extends WireData
{

  public $option;

  /** @var ProductPage $product */
  public $product;

  /** @var RockMoney $price */
  public $price;

  public $title;

  /** @var Variation $variation */
  public $variation;

  public function __construct($id)
  {
    $this->option = $this->wire->pages->get((string)$id);
    if (!$this->option instanceof RepeaterPage) {
      throw new WireException("Invalid option id provided in VariationOption");
    }
    $this->variation = $this->option->getForPage();
  }

  /**
   * AlpineJS Attributes
   * @return string
   */
  public function rcAttributes(): string
  {
    return rockcommerce()->attrs('RcVariationOption', "
      rc-pid='{$this->option}'
      rc-price='{$this->price->getFloat()}'
    ");
  }

  public function setPrice($id, $prices): void
  {
    if (!is_array($prices)) $prices = [];
    if (!array_key_exists($id, $prices)) $prices[$id] = 0;
    $this->price = rockmoney()->parse($prices[$id]);
  }

  public function __debugInfo(): array
  {
    return [
      'option' => $this->option,
      'product' => $this->product,
      'price' => $this->price,
      'title' => $this->title,
    ];
  }
}

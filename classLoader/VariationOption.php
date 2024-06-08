<?php

namespace RockCommerce;

use ProcessWire\RockMoney;
use ProcessWire\WireData;

use function ProcessWire\rockmoney;

class VariationOption extends WireData
{

  public $option;

  /** @var ProductPage $product */
  public $product;

  /** @var RockMoney $price */
  public $price;

  public $title;

  /**
   * AlpineJS Attributes
   * @return string
   */
  public function attrs(): string
  {
    return "
      x-data='rcvariationoption'
      rc-id='{$this->option}'
      rc-price='{$this->price->getFloat()}'
    ";
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

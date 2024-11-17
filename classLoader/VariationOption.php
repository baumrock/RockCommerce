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
      rc-image='{$this->image}'
    ");
  }

  public function wakeup($data, $id): void
  {
    // set price
    $this->price = rockmoney()->parse(0);
    $prices = $data->prices ?: [];
    if (array_key_exists($id, $prices)) {
      $this->price = rockmoney()->parse($prices[$id]);
    }

    // set image
    $this->image = '';
    $images = $data->images ?: [];
    if (array_key_exists($id, $images)) $this->image = $images[$id];
  }

  public function __debugInfo(): array
  {
    return [
      'option' => $this->option,
      'product' => $this->product,
      'price' => $this->price,
      'title' => $this->title,
      'image' => $this->image,
    ];
  }
}

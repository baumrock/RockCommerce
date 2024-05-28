<?php

namespace RockCommerce;

use ProcessWire\HookEvent;
use ProcessWire\RepeaterPage;
use RockMoney\Money;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class Accessory extends RepeaterPage
{
  const field = 'TBD';

  /** @var Money */
  public $gross;

  /** @var Money */
  public $net;

  /** @var Money */
  public $price;

  /** @var ProductPage */
  public $product;

  public function init(): void
  {
    wire()->addHookAfter("Page::loaded", $this, "hookPageLoaded");
    wire()->addHookProperty("Page::label", $this, "hookLabel");
  }

  public function gross(): Money
  {
    return $this->getFormatted(ProductPage::field_gross);
  }

  public function hookLabel(HookEvent $event): void
  {
    $page = $event->object;
    if (!$page instanceof self) return;
    $event->return = $page->title . ": " . $page->price;
  }

  protected function hookPageLoaded(HookEvent $event): void
  {
    $page = $event->object;
    if (!$page instanceof self) return;
    $page->product = $page->getForPage();
    $page->gross = $page->gross();
    $page->net = $page->net();
    $page->price = $page->price();
  }

  public function net(): Money
  {
    return $this->getFormatted(ProductPage::field_net);
  }

  /**
   * Get either net or gross price based on module settings
   */
  public function price(): Money
  {
    return rockcommerce()->priceType() == 'net'
      ? $this->net
      : $this->gross;
  }

  public function __debugInfo()
  {
    return [
      'net' => $this->net,
      'gross' => $this->gross,
      'price' => $this->price,
    ];
  }
}

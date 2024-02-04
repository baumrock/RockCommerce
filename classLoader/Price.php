<?php

namespace RockCommerce;

use ProcessWire\WireData;
use RockMoney\Money;

class Price extends WireData
{
  public $net;
  public $vat;
  public $gross;
  public $taxrate;

  public function __construct($net = 0, $taxrate = 0)
  {
    $this->net = new Money($net);
    $this->taxrate = $taxrate;
    $this->vat = $this->net->times($taxrate / 100);
    $this->gross = $this->net->plus($this->vat);
  }

  public function renderGross(): string
  {
    return (string)$this->gross;
  }

  public function renderNet(): string
  {
    return (string)$this->net;
  }

  public function __toString()
  {
    return $this->toString();
  }

  public function ___toString($type = null): string
  {
    if (!$type) $type = $this->wire->config->rcRenderType;
    if ($type == "gross") return (string)$this->gross;
    return (string)$this->net;
  }

  public function __debugInfo()
  {
    return [
      'net' => $this->net,
      'vat' => $this->vat,
      'gross' => $this->gross,
      'taxrate' => $this->taxrate,
      'toString()' => (string)$this,
    ];
  }
}

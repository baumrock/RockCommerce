<?php

namespace RockCommerce;

use ProcessWire\WireData;

class PriceMulti extends WireData
{
  /** @var Price $ */
  public $from;

  /** @var Price $ */
  public $to;

  public function __construct($from = null, $to = null, $taxrate = 0)
  {
    $this->from = new Price($from, $taxrate);
    $this->to = new Price($to, $taxrate);
  }

  public function renderGross(): string
  {
    return $this->toString("gross");
  }

  public function renderNet(): string
  {
    return $this->toString("net");
  }

  public function __toString()
  {
    return $this->toString();
  }

  public function ___toString($type = null): string
  {
    if ($this->from->net->isEqual($this->to->net)) {
      return $this->from->toString($type);
    }
    return $this->from->toString($type) . " - " . $this->to->toString($type);
  }

  public function __debugInfo()
  {
    return [
      'from' => $this->from,
      'to' => $this->to,
      'toString()' => $this->toString(),
    ];
  }
}

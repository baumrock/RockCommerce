<?php

namespace RockCommerce;

use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;

class Taxrate extends Page
{
  use MagicPage;

  public function loaded(): void
  {
    // make all properties of getJsonArray available as properties of the page
    // this is to support selectors like
    // rockcommerce()->taxrates()->get('isDefault=1')
    foreach ($this->getJsonArray() as $k => $v) {
      if ($k === 'id') continue;
      $this->$k = $v;
    }
  }

  public function __debugInfo()
  {
    return $this->getJsonArray();
  }

  public function ___getJsonArray(): array
  {
    return [
      'id' => $this->id,
      'label' => $this->title,
      'labelLong' => $this->labelLong(),
      'percent' => $this->percent(),
      'percentLong' => $this->percentLong(),
      'taxrate' => $this->taxrate(),
    ];
  }

  public function label(): string
  {
    return $this->title;
  }

  public function labelLong(): string
  {
    return $this->title . ' (' . $this->percentLong() . ')';
  }

  public function taxrate(): float
  {
    $val = $this->getFormatted(RockCommerce::field_taxrate);
    return (float)$val / 100;
  }

  public function percent(): float
  {
    return $this->taxrate() * 100;
  }

  public function percentLong(): string
  {
    return $this->percent() . '%';
  }
}

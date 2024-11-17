<?php

namespace RockCommerce;

use ProcessWire\Wire;

use function ProcessWire\rockcommerce;

class TaxSelect extends Wire
{

  public function attrs(): string
  {
    rockcommerce()->loadJS('RcTaxselect');
    return "x-data=RcTaxselect";
  }
}

<?php

namespace RockCommerce;

use ProcessWire\Wire;

use function ProcessWire\rockcommerce;

class TaxSelect extends Wire
{

  public function attrs(): string
  {
    rockcommerce()->loadJS('rctaxselect');
    $default = rockcommerce()->getDefaultTaxrate();
    return "x-data=rctaxselect rc-default-taxrate='$default'";
  }
}

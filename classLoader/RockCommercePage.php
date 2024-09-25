<?php

namespace RockCommerce;

use ProcessWire\RockCommerce;

trait RockCommercePage
{

  public function rockcommerce(): RockCommerce
  {
    return $this->wire->modules->get('RockCommerce');
  }
}

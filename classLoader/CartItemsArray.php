<?php

namespace RockCommerce;

use ProcessWire\PageArray;

class CartItemsArray extends PageArray
{
  public function getJsonArray(): array
  {
    $arr = [];
    foreach ($this as $item) {
      if (!$item instanceof Cartitem) continue;
      $arr[] = $item->getArray();
    }
    return $arr;
  }
}

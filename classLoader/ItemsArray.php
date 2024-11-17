<?php

namespace RockCommerce;

use ProcessWire\PageArray;

class ItemsArray extends PageArray
{
  public function getJsonArray(): array
  {
    $arr = [];
    foreach ($this as $item) {
      if (!$item instanceof Item) continue;
      $arr[] = $item->getJsonArray();
    }
    return $arr;
  }
}

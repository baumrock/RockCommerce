<?php

namespace RockCommerce;

use ProcessWire\PageArray;

class TaxratesArray extends PageArray
{
  public function ___getJsonArray(): array
  {
    $arr = [];
    foreach ($this as $page) {
      $arr[$page->id] = $page->getJsonArray();
    }
    return $arr;
  }
}

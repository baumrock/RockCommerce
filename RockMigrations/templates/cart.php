<?php

namespace ProcessWire;

use RockCommerce\Carts;
use RockCommerce\Item;

return [
  'pageClass' => '\RockCommerce\Cart',
  'fields' => [
    RockCommerce::field_order,
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
    RockCommerce::field_taxid,
  ],
  'parentTemplates' => [
    Carts::tpl,
  ],
  'childTemplates' => [
    Item::tpl,
  ],
  'noLang' => 1, // single language
  'noChildren' => 0, // may have children
  'noMove' => 1,
  'noChangeTemplate' => 1,
  'noParents' => 1,
  // sort by id
  // this is to make sure items are always in the same order
  // and later added items are further down in the list
  'sortfield' => 'id',
  'icon' => 'shopping-basket',
  'noSettings' => 1,
];

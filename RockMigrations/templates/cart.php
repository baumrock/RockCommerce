<?php

namespace ProcessWire;

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
    RockCommerce::template_carts,
  ],
  'childTemplates' => [
    RockCommerce::template_item,
  ],
  'noLang' => true, // single language
  'noChildren' => 0, // may have children
  'noMove' => true,
  'noChangeTemplate' => true,
  'noParents' => true,
  // sort by id
  // this is to make sure items are always in the same order
  // and later added items are further down in the list
  'sortfield' => 'id',
  'icon' => 'shopping-basket',
  'noSettings' => true,
];

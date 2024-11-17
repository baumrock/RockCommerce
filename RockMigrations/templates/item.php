<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Item',
  'fields' => [
    // hide the title field
    // removing it causes an error even when flag system is set :(
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
    RockCommerce::field_product => ['columnWidth' => 50],
    RockCommerce::field_amount => ['columnWidth' => 50],
    RockCommerce::field_variationstr,
  ],
  'parentTemplates' => [
    RockCommerce::template_cart,
    RockCommerce::template_order,
  ],
  'noLang' => true, // single language
  'noChildren' => true,
  'noMove' => true,
  'noChangeTemplate' => true,
  'noParents' => true,
  'sortfield' => '-id',
  'icon' => 'cube',
  'noSettings' => true,
];

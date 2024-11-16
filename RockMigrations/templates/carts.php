<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Carts',
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
    RockCommerce::field_removeempty,
    RockCommerce::field_removefilled,
  ],
  'childTemplates' => [
    RockCommerce::template_cart,
  ],
  'noLang' => 1, // single language
  'noChildren' => 1, // add orders only via api
  'noMove' => 1,
  'noChangeTemplate' => 1,
  'noParents' => 1,
  'sortfield' => '-id',
  'icon' => 'shopping-cart',
];

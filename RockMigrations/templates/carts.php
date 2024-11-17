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
  'noLang' => true, // single language
  'noChildren' => true, // add orders only via api
  'noMove' => true,
  'noChangeTemplate' => true,
  'noParents' => true,
  'sortfield' => '-id',
  'icon' => 'shopping-cart',
];

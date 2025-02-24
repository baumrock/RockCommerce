<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\DiscountsPage',
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
  ],
  'childTemplates' => [
    RockCommerce::template_discount,
  ],
  'noLang' => false,
  'noSettings' => true,
  'icon' => 'percent',
  'noParents' => true,
  'noMove' => true,
  // 'sortfield' => 'title',
];

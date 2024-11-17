<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\RootPage',
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
  ],
  'noChildren' => true, // no children allowed
  'noLang' => false,
  'noSettings' => true,
  'icon' => 'shopping-cart',
  'noParents' => true,
  'noMove' => true,
  // 'sortfield' => 'title',
];

<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Orders',
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
  ],
  'childTemplates' => [
    RockCommerce::template_order,
  ],
  'noLang' => true, // single language
  'noChildren' => true, // add orders only via api
  'noMove' => true,
  'noChangeTemplate' => true,
  'noParents' => true,
  'sortfield' => '-id',
  'icon' => 'check',
];

<?php

namespace ProcessWire;

return [
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
  ],
  'childTemplates' => [
    RockCommerce::template_order,
  ],
  'noLang' => 1, // single language
  'noChildren' => 1, // add orders only via api
  'noMove' => 1,
  'noChangeTemplate' => 1,
  'noParents' => 1,
  'sortfield' => '-id',
  'icon' => 'check',
];

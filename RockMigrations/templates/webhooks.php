<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Webhooks',
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
    RockCommerce::field_max,
  ],
  'childTemplates' => [
    RockCommerce::template_webhook,
  ],
  'sortfield' => '-id',
  'noChildren' => true,
  'noParents' => true,
  'icon' => 'anchor',
];

<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Webhook',
  'fields' => [
    'title' => [
      'collapsed' => Inputfield::collapsedHidden,
    ],
  ],
  'parentTemplates' => [
    RockCommerce::template_webhooks,
  ],
  'noLang' => true, // only english
  'noChildren' => true,
  'noSettings' => true,
  'icon' => 'anchor',
];

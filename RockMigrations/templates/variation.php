<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Variation',
  'fields' => [
    'title' => [
      'label' => 'Variation Label',
      'columnWidth' => 50,
    ],
    RockCommerce::field_comment => [
      'columnWidth' => 50,
    ],
    // this will be added after installation to ugly workaround this issue:
    // https://github.com/processwire/processwire-issues/issues/1995
    // See RockCommerce::initWhenInstalled for more details
    // RockCommerce::field_variationoptions,
  ],
  'noChildren' => true,
  'noUnpublish' => true,
  'parentTemplates' => [
    RockCommerce::template_variations,
  ],
  'nameContentTab' => 0,
  'noSettings' => true,
  'icon' => 'cube',
];

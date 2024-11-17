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
    RockCommerce::field_variationoptions,
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

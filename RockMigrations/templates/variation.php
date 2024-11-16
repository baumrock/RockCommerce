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
  'noChildren' => 1,
  'noUnpublish' => 1,
  'parentTemplates' => [
    RockCommerce::template_variations,
  ],
  'nameContentTab' => 0,
  'noSettings' => 1,
  'icon' => 'cube',
];

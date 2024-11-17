<?php

namespace ProcessWire;

return [
  'type' => 'FieldtypeRepeater',
  'label' => 'Variations',
  'icon' => 'cubes',
  'fields-' => [
    RockCommerce::field_variationcomment,
    RockCommerce::field_variation,
    RockCommerce::field_min => [
      'label' => 'Minimum number of selected options',
      'columnWidth' => 50,
      'notes' => 'Empty means no minimum',
    ],
    RockCommerce::field_max => [
      'label' => 'Maximum number of selected options',
      'columnWidth' => 50,
      'notes' => 'Empty means 1',
    ],
  ],
  'repeaterTitle' => '{variationRepeaterLabel}',
  'familyFriendly' => true,
  'repeaterDepth' => 0,
  'repeaterCollapse' => '0', // New items open, existing items collapsed (recommended for most cases)
  'repeaterAddLabel' => 'Add New Variation',
  'columnWidth' => 100,
];

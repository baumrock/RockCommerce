<?php

namespace ProcessWire;

return [
  'type' => 'FieldtypeRepeater',
  'label' => '[DONE] Variations',
  'icon' => 'cubes',
  'fields' => [
    RockCommerce::field_variationcomment,
    RockCommerce::field_variation,
    RockCommerce::field_minoptions,
    RockCommerce::field_maxoptions,
  ],
  'repeaterTitle' => '{variationRepeaterLabel}',
  'familyFriendly' => 1,
  'repeaterDepth' => 0,
  'repeaterCollapse' => '0', // New items open, existing items collapsed (recommended for most cases)
  'repeaterAddLabel' => 'Add New Variation',
  'columnWidth' => 100,
];

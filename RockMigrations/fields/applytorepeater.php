<?php

namespace ProcessWire;

return [
  'label' => 'Select Pages',
  'icon' => 'filter',
  'type' => 'FieldtypeRepeater',
  'fields' => [
    RockCommerce::field_applyto,
    RockCommerce::field_pagesautocomplete,
    RockCommerce::field_pages,
  ],
  'repeaterTitle' => '#n',
  'familyFriendly' => 1,
  'repeaterDepth' => 0,
  'repeaterAddLabel' => 'Add New Item',
  'columnWidth' => 100,
];

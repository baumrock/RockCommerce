<?php

namespace ProcessWire;

// return [
//   'name' => 'tmp_variationoptions_' . time(),
// ];

return [
  'label' => 'Options',
  'type' => 'FieldtypeRepeater',
  'fields' => [
    'title' => [
      'label' => 'Option Name',
      'required' => true,
    ],
  ],
  'repeaterTitle' => '{title}',
  'familyFriendly' => 1,
  'repeaterDepth' => 0,
  'repeaterAddLabel' => 'Add New Option',
  'columnWidth' => 100,
  'icon' => 'cubes',
];

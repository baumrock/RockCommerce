<?php

namespace ProcessWire;

return [
  'type' => 'integer',
  'label' => '[DONE] Remove empty carts after ... days',
  'icon' => 'trash-o',
  'inputType' => 'number',
  'size' => 3,
  'min' => 0,
  'max' => 365,
  'zeroNotEmpty' => 1, // 0 != blank
];

<?php

namespace ProcessWire;

return [
  'type' => 'integer',
  'label' => 'Remove empty carts after ... days',
  'icon' => 'trash-o',
  'inputType' => 'number',
  'size' => 3,
  'min' => 0,
  'max' => 365,
  'zeroNotEmpty' => true, // 0 != blank
];

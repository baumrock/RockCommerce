<?php

namespace ProcessWire;

return [
  'type' => 'options',
  'label' => 'Type of Discount',
  'icon' => 'percent',
  'options' => [
    10 => 'percentage|Percentage',

    // we do not allow fixed discount at the moment
    // this is because calculations are more complex (when having taxes)
    // 20 => 'fixed|Fixed Amount',
  ],
  'required' => true,
  'defaultValue' => 10,
];

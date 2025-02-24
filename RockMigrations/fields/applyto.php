<?php

namespace ProcessWire;

return [
  'type' => 'options',
  'label' => 'Apply to ...',
  'icon' => 'filter',
  'options' => [
    10 => 'single|Selected Pages only',
    20 => 'children|Only direct children of selected pages',
    30 => 'descendants|Only descendants of selected pages',
    40 => 'pages+children|Selected Pages and all their direct children',
    50 => 'pages+descendants|Selected Pages and all their descendants',
  ],
  'required' => true,
  'defaultValue' => 10,
];

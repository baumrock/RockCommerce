<?php

namespace ProcessWire;

$info = [
  'title' => 'RockCommerce',
  'version' => json_decode(file_get_contents(__DIR__ . "/package.json"))->version,
  'summary' => 'E-Commerce Module for ProcessWire',
  'autoload' => true,
  'singular' => true,
  'icon' => 'money',
  // PHP8.2 for constants in traits
  // PW 3.0.234 for repeater classes
  // RockFrontend for AJAX endpoints
  'requires' => [
    'PHP>=8.2',
    'ProcessWire>=3.0.234',
    'RockMigrations>=5.4.1',
    'RockFrontend>=3.22.0',
    'RockMoney>=2.0.3',
  ],
  'installs' => [
    'ProcessRockCommerce',
    'InputfieldRcPricing',
    'FieldtypeRcVariations',
    'InputfieldRcVariations',
  ],
];

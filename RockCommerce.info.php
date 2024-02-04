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
  'requires' => [
    'PHP>=8.2',
    'ProcessWire>=3.0.234',
    'RockFrontend>=3.3.0',
    'RockMigrations>=3.36',
    'RockMoney>=1.0.1',
    'RockMollie>=1.0.0',
    'RockGrid>=2.2.0',
  ],
  'installs' => [
    'ProcessRockCommerce',
    'InputfieldRcPricing',
    'FieldtypeRcVariations',
    'InputfieldRcVariations',
  ],
];

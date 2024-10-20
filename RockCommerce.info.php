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
  // RockLoaders 1.1.0 for add() short syntax
  // Note: RockAssets is only needed for development, no global requirement
  'requires' => [
    'PHP>=8.2',
    'ProcessWire>=3.0.234',
    'RockMigrations>=4.3.0',
    'RockFrontend>=3.17.0',
    'RockMoney>=1.0.1',
    'RockMollie>=1.2.0',
    'RockGrid>=1.0.1',
    'Less>=0.0.5',
    'RockLoaders>=1.1.0',
  ],
];

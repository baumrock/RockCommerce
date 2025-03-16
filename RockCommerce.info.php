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
    'RockMigrations>=6.1.0',
    'RockFrontend>=4.0.0',
    'RockMoney>=2.0.3',
    'RockJavaScriptHooks>=1.0.1',
  ],
  'installs' => [
    'ProcessRockCommerce',
  ],
];

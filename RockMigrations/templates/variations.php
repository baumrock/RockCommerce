<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Variations',
  'fields' => [
    'title',
  ],
  'childTemplates' => [
    RockCommerce::template_variation,
  ],
  'sortfield' => 'title',
  'noChildren' => 0,
  'noParents' => -1, // only one page allowed
  'icon' => 'cubes',
];

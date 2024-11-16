<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Taxrates',
  'fields' => [
    'title',
    RockCommerce::field_defaulttaxrate,
  ],
  'childTemplates' => [
    RockCommerce::template_taxrate,
  ],
  'noParents' => 1,
  'icon' => 'percent',
];

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
  'childNameFormat' => 'title',
  'noParents' => -1,
  'noChildren' => false,
  'icon' => 'percent',
  'noSettings' => true,
];

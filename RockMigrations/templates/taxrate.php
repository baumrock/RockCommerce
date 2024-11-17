<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\Taxrate',
  'fields' => [
    RockCommerce::field_taxrate => [
      'columnWidth' => 30,
    ],
    'title' => [
      'label' => 'Description',
      'required' => false,
      'columnWidth' => 70,
    ],
  ],
  'parentTemplates' => [
    RockCommerce::template_taxrates,
  ],
  'noUnpublish' => true, // Don't allow unpublished pages
  'pageLabelField' => "{" . RockCommerce::field_taxrate . "}% ({title})",
  'icon' => 'percent',
  'noSettings' => true,
  'noChildren' => true,
];

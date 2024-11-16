<?php

namespace ProcessWire;

use RockCommerce\Taxrate;

return [
  'type' => 'page',
  'label' => '[DONE] Default Taxrate',
  'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
  'inputfield' => 'InputfieldSelect',
  'findPagesSelector' => 'template=' . RockCommerce::template_taxrate,
  'labelFieldName' => 'title',
  'icon' => 'percent',
];

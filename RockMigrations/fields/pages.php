<?php

namespace ProcessWire;

return [
  'type' => 'page',
  'label' => 'Select Pages (from tree)',
  'derefAsPage' => FieldtypePage::derefAsPageArray,
  'inputfield' => 'InputfieldPageListSelectMultiple',
  'findPagesSelector' => 'id>0,template!=admin',
  'labelFieldName' => 'title',
  'icon' => 'filter',
];

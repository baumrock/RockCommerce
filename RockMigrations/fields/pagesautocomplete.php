<?php

namespace ProcessWire;

return [
  'type' => 'page',
  'label' => 'Select Pages (by title)',
  'derefAsPage' => FieldtypePage::derefAsPageArray,
  'inputfield' => 'InputfieldPageAutocomplete',
  'findPagesSelector' => 'id>0,template!=admin',
  'labelFieldName' => 'title',
  'icon' => 'filter',
];

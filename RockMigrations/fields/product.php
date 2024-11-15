<?php

namespace ProcessWire;

return [
  'type' => 'page',
  'label' => '[DONE] Product',
  'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
  'inputfield' => 'InputfieldPageAutocomplete',
  'findPagesSelector' => 'id>0,template!=admin',
  'labelFieldName' => 'title',
  'collapsed' => Inputfield::collapsedNo,
  'icon' => 'link',
];

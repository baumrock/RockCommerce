<?php

namespace ProcessWire;

return [
  'type' => 'page',
  'label' => '[DONE] Order',
  'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
  'inputfield' => 'InputfieldPageAutocomplete',
  'findPagesSelector' => 'id>0,template!=admin',
  'labelFieldName' => 'title',
  'collapsed' => Inputfield::collapsedNoLocked,
  'icon' => 'shopping-bag',
];

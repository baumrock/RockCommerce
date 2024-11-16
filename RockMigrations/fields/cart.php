<?php

namespace ProcessWire;

return [
  'type' => 'page',
  'label' => '[DONE] Cart',
  'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
  'inputfield' => 'InputfieldPageAutocomplete',
  'findPagesSelector' => 'id>0,template!=admin',
  'labelFieldName' => 'title',
  'collapsed' => Inputfield::collapsedBlankLocked,
  'icon' => 'shopping-cart',
];

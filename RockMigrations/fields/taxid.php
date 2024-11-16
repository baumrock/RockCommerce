<?php

namespace ProcessWire;

return [
  'type' => 'page',
  'label' => '[DONE] Taxrate',
  'derefAsPage' => FieldtypePage::derefAsPageOrFalse,
  'inputfield' => 'InputfieldPageListSelect',
  'findPagesSelector' => 'id>0,template!=admin',
  'labelFieldName' => 'title',
  'collapsed' => Inputfield::collapsedNoLocked,
  'icon' => 'percent',
];

<?php

namespace ProcessWire;

return [
  'pageClass' => '\RockCommerce\DiscountPage',
  'fields' => [
    'title' => [
      'label' => 'Discount Name',
      'columnWidth' => 100,
      'required' => false,
      'collapsed' => Inputfield::collapsedHidden,
    ],
    RockCommerce::field_datefrom => [
      'label' => 'Discount active from',
      'columnWidth' => 50,
      'required' => true,
    ],
    RockCommerce::field_dateto => [
      'label' => 'Discount active until',
      'columnWidth' => 50,
      'required' => true,
    ],
    RockCommerce::field_discounttype => [
      'columnWidth' => 50,
    ],
    RockCommerce::field_discount => [
      'columnWidth' => 50,
    ],
    RockCommerce::field_applytorepeater,
  ],
  'parentTemplates' => [
    RockCommerce::template_discounts,
  ],
  'noChildren' => true,
  'noUnpublish' => true,
  'noSettings' => true,
  'noLang' => true, // single language
  'icon' => 'gavel',
];

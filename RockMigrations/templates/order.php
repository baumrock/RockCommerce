<?php

namespace ProcessWire;

use RockCommerce\Item;
use RockCommerce\Orders;

return [
  'pageClass' => '\RockCommerce\Order',
  'fields' => [
    'title' => [
      'required' => false,
      'collapsed' => Inputfield::collapsedHidden,
      'columnWidth' => 50,
    ],
    RockMigrations::field_email => [
      'collapsed' => Inputfield::collapsedNoLocked,
      'columnWidth' => 50,
    ],

    RockCommerce::field_net => [
      'columnWidth' => 33,
      'collapsed' => Inputfield::collapsedNoLocked,
    ],
    RockCommerce::field_vat => [
      'columnWidth' => 33,
      'collapsed' => Inputfield::collapsedNoLocked,
    ],
    RockCommerce::field_total => [
      'columnWidth' => 33,
      'collapsed' => Inputfield::collapsedNoLocked,
    ],

    RockCommerce::field_paymentid => [
      'columnWidth' => 33,
      'collapsed' => Inputfield::collapsedNoLocked,
    ],
    RockCommerce::field_paymentstatus => [
      'columnWidth' => 33,
      'collapsed' => Inputfield::collapsedNoLocked,
    ],
    RockCommerce::field_paymentdate => [
      'columnWidth' => 33,
    ],
    RockCommerce::field_cart,
    RockCommerce::field_orderfields,
  ],
  'parentTemplates' => [
    Orders::tpl,
  ],
  'childTemplates' => [
    Item::tpl,
  ],
  'noParents' => 1,
  'noLang' => 1, // single language
  'icon' => 'shopping-bag',
];

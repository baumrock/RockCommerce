<?php

namespace ProcessWire;

$rm = rockmigrations();
$rm->createPage(
  template: RockCommerce::template_discounts,
  parent: rockcommerce()->rootPage(),
  title: 'Discounts',
  name: 'discounts',
);

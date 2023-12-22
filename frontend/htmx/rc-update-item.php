<?php

namespace ProcessWire;

use RockCommerce\Cartitem;

$rf = rockfrontend();
$rc = rockcommerce();

$item = $pages->get($input->get('id', 'int'));
if (!$item instanceof Cartitem) throw new WireException("Invalid Item ID");

echo $rc->view('partials/cart-item', ['item' => $item]);
echo $rc->swap([
  'partials/cart-sidebar',
]);

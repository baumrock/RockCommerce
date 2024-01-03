<?php

namespace ProcessWire;

use RockCommerce\Cartitem;

$rc = rockcommerce();

$item = $pages->get([
  'template' => Cartitem::tpl,
  'name' => $input->get('item', 'string'),
]);
if (!$item instanceof Cartitem) throw new WireException("Invalid Item ID");

// check if we know the correct cart
if ($item->cart()->name !== $input->get('cart', 'string')) {
  throw new WireException("Invalid Cart ID");
}

$amount = wire()->input->post('amount', 'int');
if ($amount > 0) {
  $item->setAndSave(Cartitem::field_amount, $amount);
  return $rc->view('partials/cart-item', ['item' => $item]);
} else {
  $item->meta('rockcommerce-cart', $item->cart()->id);
  $item->trash();
  return $rc->view('partials/cart-item-removed', ['item' => $item]);
}

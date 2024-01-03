<?php

namespace ProcessWire;

use RockCommerce\Cart;

$wire = wire();
$rc = rockcommerce();

if (!$wire->user->isSuperuser()) throw new Wire404Exception("No direct access");

$cart = $rc->getCart($wire->input->get('id', 'string'));
if ($cart instanceof Cart) {
  $rc->setCart($cart);
  $wire->session->redirect("/rc/cart");
}

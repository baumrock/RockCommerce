<?php

namespace ProcessWire;

use RockCommerce\Cartitem;

$rc = rockcommerce();

$item = $pages->get($input->get('id', 'int'));
if (!$item instanceof Cartitem) throw new WireException("Invalid Item ID");

return $rc->view('partials/cart-item', ['item' => $item]);

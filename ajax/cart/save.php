<?php

namespace ProcessWire;

// get cart of the current user
$cart = rockcommerce()->cart();

// save cart to the database
$cart->save();

return [
  'success' => 'Cart has been saved',
];

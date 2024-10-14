<?php

namespace ProcessWire;

// access: anybody can access this

$cart = rockcommerce()->cart();
$cart->setTaxrate($input->taxrate);

return $cart->getJsonArray();

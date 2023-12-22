<?php

namespace ProcessWire;

$rf = rockfrontend();
$rc = rockcommerce();

echo $rf->view('partials/cart-item');
echo $rc->swap([
  'partials/cart-sidebar',
]);

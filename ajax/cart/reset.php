<?php

namespace ProcessWire;

// access control
// nothing to do - anybody may add items to their cart

rockcommerce()->cart()->reset();

return [
  'success' => 1,
];

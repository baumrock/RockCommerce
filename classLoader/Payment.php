<?php

namespace RockCommerce;

use ProcessWire\WireData;

/**
 * Generic Payment class that holds the unique payment id
 * and the reference to the payment provider's payment object.
 */
class Payment extends WireData
{
  public string $id;
  public mixed $ref;
  public string $status;

  public function __construct(
    string $id,
    mixed $ref,
    string $status = '',
  ) {
    parent::__construct();
    $this->id = $id;
    $this->ref = $ref;
    $this->status = $status;
  }

  public function __debugInfo(): array
  {
    return [
      'id' => $this->id,
      'status' => $this->status,
      'ref' => $this->ref,
    ];
  }
}

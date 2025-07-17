<?php

namespace RockCommerce;

interface PaymentProvider
{
  public function getPayment(string $id): Payment|false;
  public function createPayment(Order $order, array $data = []): Payment|false;
}

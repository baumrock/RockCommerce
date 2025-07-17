# Payment Providers

RockCommerce makes it easy to integrate any payment provider you want. All you have to do is to create a new ProcessWire module that implements the `\RockCommerce\PaymentProvider` interface.

This interface defines two methods that you need to implement:

- `getPayment(string $id): Payment|false`
- `createPayment(Order $order, array $data = []): Payment|false`

Let's have a look at the bundled `Mollie.module.php` to see how this works in practice.

## Example: Mollie Payment Provider

The bundled Mollie payment provider can be found in the file `/site/modules/RockCommerce/Mollie.module.php`.

### Module Info

Every payment provider is a regular ProcessWire module. The only special thing is that it implements the `PaymentProvider` interface.

```php
class Mollie
extends WireData
implements Module, PaymentProvider
{
  public static function getModuleInfo(): array
  {
    return [
      'title' => 'Payment Provider Mollie',
      'version' => '1.0.0',
      'summary' => 'Mollie Payment Provider for RockCommerce',
      'icon' => 'money',
      'autoload' => true,
    ];
  }
```

### getPayment()

The `getPayment()` method is used to get an existing payment from the payment provider. This is used for example when a webhook is received to update the payment status of an order.

```php
/**
 * Get an existing payment from Mollie
 */
public function getPayment(string $id): Payment|false
{
  /** @var RockMollie $mollie */
  $mollie = wire()->modules->get('RockMollie');
  $payment = $mollie->api()->payments->get($id);
  if (!$payment instanceof MolliePayment) return false;
  return new Payment(
    id: $payment->id,
    ref: $payment,
    status: $payment->status,
  );
}
```

As you can see, the method gets the payment from the Mollie API and returns a `RockCommerce\Payment` object. This object is a simple data container that holds the payment's ID, a reference to the original payment object of the provider, and the payment status.

### The Payment Class

The `Payment` class is a simple data container that holds the following properties:

- `id`: The unique ID of the payment from the payment provider.
- `ref`: A reference to the original payment object of the provider.
- `status`: The status of the payment (e.g., `paid`, `open`, `failed`).

This makes sure that RockCommerce can work with a standardized payment object, no matter which payment provider is used.

### createPayment()

The `createPayment()` method is used to create a new payment for an order. This is usually called when the user clicks the "buy now" button in the checkout.

```php
/**
 * Create a new payment for an order
 */
public function createPayment(
  Order $order,
  array $data = [],
): Payment|false {
  if ($order->paymentId()) {
    throw new WireException("A payment for this order has already been created.");
  }

  // create mollie payment
  /** @var RockMollie $mollie */
  $mollie = wire()->modules->get('RockMollie');
  try {
    $total = $order->total(1);
    if (!$total instanceof Money) {
      throw new WireException("Invalid Price");
    }
    $data = [
      "amount" => [
        "currency" => (string)$total->money->getCurrency(), // EUR
        "value" => $total->formatMollie(), // 39.50
      ],
      "description" => (string)$order->title,
      "redirectUrl" => rockcommerce()->url($data['redirectUrl'] ?? '', true),
      "webhookUrl" => rockcommerce()->webhookUrl(),
      "metadata" => ["orderid" => $order->name],
    ];
    $molliePayment = $mollie->api()->payments->create($data);

    // Create and return Payment object
    $payment = new Payment(
      id: $molliePayment->id,
      ref: $molliePayment,
      status: $molliePayment->status,
    );

    // Save payment to order
    $order->setPayment($payment);

    return $payment;
  } catch (\Throwable $th) {
    rockcommerce()->log($th->getMessage());
  }
  return false;
}
```

This method creates a new payment using the Mollie API, creates a `RockCommerce\Payment` object, saves the payment details to the order, and finally returns the payment object.

The `$data` array can be used to pass additional data to the payment provider. For example, you could pass a `redirectUrl` to redirect the user to a specific page after the payment is completed.

## Setup

Once you have created your payment provider module, you need to install it and then select it in the RockCommerce module settings. Go to Modules > RockCommerce and select your payment provider from the radio list.

## Conclusion

As you can see, creating a custom payment provider is fairly simple. All you need to do is to create a new module that implements the `PaymentProvider` interface and you are good to go. This makes it possible to integrate any payment provider you want with RockCommerce.

# Orders

Once a user has finished the checkout process, you can turn a cart into an order.

## Creating an Order

This is typically done when the final step of the checkout process is completed. When using RockForms for the checkout form, you can do this in the `processSuccess` method:

```php
public function processSuccess()
{
  // get form data from the multi-step form
  $data = $this->steps()->getData();

  // create order
  rockcommerce()
    ->cart()
    ->createOrder(
      subject: "Neue Bestellung ({$data->contact->mail})",
      email: $data->contact->mail,
    );
}
```

In the example above, the order will be created from the current cart. If you want to create an order from different data, you can use `rockcommerce()->createOrder()` instead of `rockcommerce()->cart()->createOrder()`:

```php
$order = rockcommerce()->createOrder(
  subject: 'Demo Order',
  net: 10,
  vat: 2,
  total: 12,
  email: 'foo@bar.com',
  details: [
    'foo' => 'Foo!',
    'bar' => 'Bar!',
  ],
);
```

<img src=https://i.imgur.com/GyDCHHU.png class=blur height=700>

## Sending E-Mails

There are probably a thousand ways to send e-mails, but for the sake of simplicity, we'll use `WireMail` in this example. You can use any other mail service you want, of course.


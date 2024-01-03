# Orders

## Creating Orders

```php
$rc = rockcommerce();
$order = $rc->createOrder(
  subject: 'Demo Order',
  net: 10,
  vat: 2,
  total: 12,
  email: 'foo@bar.com',
  meta: [
    'foo' => 'Foo!',
    'bar' => 'Bar!',
  ],
);
```

<img src=create-order.png class=blur>

# Cart PHP API

## Get Cart

```php
// get the current user's cart or create a new one
// this is the recommended version to be used on the frontend
$cart = rockcommerce()->cart();

// get specific cart by page id
// returns false if not found!
$cart = rockcommerce()->getCart(123);

// get cart by page name
// returns false if not found!
$cart = rockcommerce()->getCart("0w4esdk");
```

## Create a new cart

```php
// create a new cart and save it to the DB
$cart = rockcommerce()->newCart();
```

## Add item to cart

```php
$cart->addItem(
  item: 123, // page id of item
  amount: 2,
);
```

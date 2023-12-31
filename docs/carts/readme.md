# Carts

## Get Cart

```php
// get cart by page id
$cart = rockcommerce()->getCart(123);

// get cart by page name
$cart = rockcommerce()->getCart("0w4esdk");
```

## Creating new Carts

```php
// create a new cart and save it to the DB
$cart = rockcommerce()->newCart();
```

## Adding Items

```php
$cart->addItem(
  item: 123, // page id of item
  amount: 2,
);
```
# Cart PHP API

Every cart in rockcommerce is a ProcessWire Page with a custom class `RockCommerce\Cart`. That means you can use the cart just like any other ProcessWire Page plus the methods defined in this API.

Cart items are also ProcessWire Pages with a custom class `RockCommerce\Item` and are direct children of the cart page.

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

## Get cart items

```php
// get all items of the cart
$items = $cart->getItems();

// get item by id
$item = $cart->getItem(123);
```

## Add item to cart

```php
$cart->addItem(
  item: 123, // page id of item
  amount: 2,
);
```

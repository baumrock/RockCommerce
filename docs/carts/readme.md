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

## Modifying the Cart Frontend

You have two options to customize the cart:

1. Hooks
2. Overwrite view-files

### Using hooks

Place your hooks in `/site/init.php` (not ready.php!):

```php
// modify the cartitem's title:
$wire->addHookAfter("Cartitem::cartTitle", function ($event) {
  $item = $event->object;
  $product = $item->product(); // get the item's referenced product
  $event->return = $product->title;
});

// modify the cartitem's description:
$wire->addHookAfter("Cartitem::cartDescription", function ($event) {
  $item = $event->object;
  $product = $item->product(); // get the item's referenced product

  // if you want to render html markup you need to use
  // the html() helper to prevent latte from escaping your markup
  // note: do this only on save markup!
  $event->return = $item->html($product->body);
});
```

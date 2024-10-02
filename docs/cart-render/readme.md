# Cart Rendering

A basic cart can look like this:

```html
<div x-data=rccart>
  <div>
    <!-- cart items -->
  </div>
  <div>
    <!-- cart summary -->
  </div>
</div>
```

Note the `x-data=rccart` attribute. This is necessary to initialize the `rccart` Alpine component.

## Customizing the cart

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

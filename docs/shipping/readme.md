# Shipping

Are you planning to build a store with physical products? You are in the right place!

<div class='uk-alert'>Please check out the docs about the cart to understand the underlying concepts!</div>

**Problem**

Shipping costs can be a challenge! There are so many options that might influence your shipping costs that building a UI for it is at least a huge task. On the other hand, it might be very simple, for example if you don't sell physical products or you only allow store pickup.

**Solution**

RockCommerce leaves calculating shipping costs totally up to you as a developer of the store. Maybe you want to calculate shipping costs based on the products sizes. Maybe you want to charge based on the weight. Maybe you want to charge a flat rate. Maybe you want to offer free shipping when a certain cart subtotal is reached.

Just add the fields you need and hook into the shipping calculation process of the cart.

## Example 1: Free Shipping

Let's check out a more realistic example. Let's say we want to offer free shipping when the cart subtotal is over 100€.

`label: /site/rockcommerce.php`
```php
wire()->addHookAfter('Cart::shippingNet', function (HookEvent $event) {
  $cart = $event->object;
  $itemsNet = $cart->itemsNet();

  // if items net price is less than 100€, don't modify the shipping costs
  if($itemsNet->isLessThan(100)) return;

  // otherwise set the shipping costs to 0
  $event->return = rockmoney()->parse(0);
});
```

## Example 2: Shipping based on User Data

Sorry for this stupid example, but I can't think of anything better. Let's say we want to charge 10€ for shipping on Mondays and 20€ for shipping on every other day.

`label: /site/rockcommerce.php`
```php
wire()->addHookAfter('Cart::shippingNet', function (HookEvent $event) {
  $isMonday = date('N') === 1;
  $event->return = $isMonday
    ? rockmoney()->parse(10)
    : rockmoney()->parse(20);
});
```

### Adding Shipping Options to Settings

You might want to avoid hardcoding the shipping costs in your code. You can - for example - add the shipping cost options to RockCommerce's settings page. Just create two fields, eg `shipping_monday` and `shipping_otherday` and add them to the `rockcommerce_settings` template.

Then adjust the hook as follows:

`label: /site/rockcommerce.php`
```php
wire()->addHookAfter('Cart::shippingNet', function (HookEvent $event) {
  $isMonday = date('N') === 1;
  $settings = rockcommerce()->settings();
  $event->return = $isMonday
    ? rockmoney()->parse($settings->shipping_monday)
    : rockmoney()->parse($settings->shipping_otherday);
});
```

### Calculate Shipping Costs based on Checkout Data

A common situation is that we have to calculate the shipping costs based on data that the user has entered during the checkout process. When using RockForms for the checkout, you can access the checkout data through `rockcommerce()->checkout()` method:

`label: /site/rockcommerce.php`
```php
wire()->addHookAfter('Cart::shippingNet', function (HookEvent $event) {
  $checkout = rockcommerce()->checkout();

  // get the data entered in the "shipping" step of the multi-step checkout
  $data = $checkout->steps->getData('shipping');

  // no shipping costs for store pickup
  if ($data->shipping === 'shop') return;

  // set default shipping costs
  $event->return = rockmoney()->parse(10);

  // override shipping costs based on the "shipping" input of the checkout data
  if($data->shipping === 'foo') $event->return = rockmoney()->parse(20);
  elseif($data->shipping === 'bar') $event->return = rockmoney()->parse(30);
  elseif($data->shipping === 'baz') $event->return = rockmoney()->parse(40);
});
```
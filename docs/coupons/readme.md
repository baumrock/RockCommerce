# Coupons

Welcome to the rabbit hole of coupons!

At the moment, RockCommerce does not offer a UI for coupons. However, you can hook into the price calculation process of the cart!

<div class='uk-alert'>Please check out the docs about the cart to understand the underlying concepts!</div>

**Problem**

Again, there are a thousand ways how you could want to implement coupons for your store. Maybe you want to send out a link to existing customers and store the coupon in the session of that user (this is what I'm doing for offerinc reduced prices when renewing my module licenses). Or maybe you want to show a popup when the user views your frontpage. Or maybe you want to add a coupon field to the cart page. Or to the checkout form. You name it.

**Solution**

The solution is similar to calculating shipping costs, so be sure to also check out the docs for Shipping!

## Example

Let's say we want to send out a 50% campaign via E-Mail. And let's say we don't want to use individual coupons, but just one global code.

Let's say, that the code is `DEMO50`, so we send out the following link:

`example.com/?coupon=DEMO50`

### Storing the coupon in the session

We need to store the coupon in the session of the user, so that RockCommerce can use it later during the checkout process.

We could add the following to our `/site/rockcommerce.php` file:

`label: /site/rockcommerce.php`
```php
// execute this if ?coupon=... is present in the URL
if(wire()->input->get('coupon') === 'DEMO50') {
  // store coupon to session
  wire()->session->rockcommerce_coupon = 'DEMO50';

  // optionally redirect to remove the coupon from the URL
  wire()->session->redirect('./');
}
```

### Hooking into the Cart

Next, we want to update the price of the cart. We have to decide: Do we want the coupon to reduce the price of the items only or the total price (including shipping costs)?

Let's say we only reduce the price of the items:

`label: /site/rockcommerce.php`
```php
wire()->addHookAfter('Cart::itemsNet', function (HookEvent $event) {
  // get the coupon from the session
  $coupon = wire()->session->rockcommerce_coupon;

  // check for the correct coupon
  if($coupon !== 'DEMO50') return;

  // reduce the price of the items by 50%
  $event->return = $event->return->times(0.5);

  // alternative syntax: divide by 2
  // $event->return = $event->return->by(2);
});
```

> That's it! You have learned how to add basic coupons to your store!

You can now get creative and add more complex coupon features. For example you can limit the validity of the coupon to a certain date range. Or you can limit the number of times the coupon can be used. Or you can add a minimum cart subtotal requirement. The sky is the limit!

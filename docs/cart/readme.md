# Cart

RockCommerce will automatically create a cart as soon as it is necessary. This could be a user adding a product to the cart or a user clicking on the cart menu item.

When that happens, RockCommerce not only creates a cart, but also creates a persistent ProcessWire cart page. This makes it possible to enricth the cart with additional data or use ProcessWire hooks to fully customize the process to your needs.

## Prices

An important part of a cart is the total price. Unfortunately we don't have a single total price, because we have a net price, value added tax, and gross price. And unfortunately we might have some shipping costs as well. Or a coupon discount.

You get it. E-Commerce is a rabbit hole!

## Cart Totals

To understand how RockCommerce works, let's inspect what data it sends to the frontend via AJAX:

<div class='uk-alert'>Please note that all `net` prices (itemsNet, shippingNet, totalNet) are hookable! VAT and Gross prices will be calculated based on those and based on your tax settings.</div>

```php
return [
  'id' => $this->name,
  'count' => $this->itemsCount(),
  'items' => $this->items()->getJsonArray(),
  'addedAmount' => $this->addedAmount,

  // item prices
  'itemsNet' => $this->itemsNet()->format(),
  'itemsVat' => $this->itemsVat()->format(),
  'itemsGross' => $this->itemsGross()->format(),

  // shipping prices
  'shippingNet' => $this->shippingNet()->format(),
  'shippingVat' => $this->shippingVat()->format(),
  'shippingGross' => $this->shippingGross()->format(),

  // final prices (all items + shipping + coupons)
  'totalNet' => $this->totalNet()->format(),
  'totalVat' => $this->totalVat()->format(),
  'totalGross' => $this->totalGross()->format(),
];
```

As you can see it returns a bunch of different prices - and you can choose which one is the right for your need.

> Please also see the docs for Shipping and Coupons!

## Abandoned Carts

Again, this can mean so many things. But luckily all carts are ProcessWire pages, so you can do anything you want!

By default, RockCommerce offers a way to clean up abandoned carts. This is to prevent the database from growing larger and larger over time as new carts are created but never deleted.

These two settings look like this:

<img src=https://i.imgur.com/LRlxO5e.png class=blur alt='Cart Cleanup'>

Using ProcessWire hooks this should already open up a lot of possibilities. If you have any custom needs or need help, please let me know and ask in the ProcessWire forum!


# Cart

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

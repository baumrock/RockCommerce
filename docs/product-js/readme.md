# Product JS

## Hooks

### Changing image based on selected color

Consider this Product:

<img src=https://i.imgur.com/qu9N3og.png class=blur height=500>

What if we wanted to change the image when a different color is selected from the options?

We can do so quite easily with a hook:

```js
// set image according to selected option
ProcessWire.addHookAfter("RcVariationOption::enable", (event) => {
  // get the RcVariationOption instance
  const option = event.object;

  // get the image number from client input that is stored in rc-image attribute
  const image = parseInt(option.$rcattr("rc-image"));
  if (isNaN(image)) return;

  // find the thumbnail link and click it
  const index = image - 1;
  const link = document.querySelector(
    ".shop-gallery [uk-slideshow-item='" + index + "'] a"
  );
  if (link) link.click();
});
```

<img src=https://i.imgur.com/2DxKMcF.gif class=blur>

### Changing the product price based on a selected taxrate

<img src=https://i.imgur.com/ccABmGY.gif class=blur>

Looks complicated? It's not:

```js
ProcessWire.addHookAfter("RcProduct::priceTotal", (event) => {
  // get the money object
  // it's not a float, because 0.1 + 0.2 !== 0.3 in binary world!
  const money = event.return;

  // return the initial price plus the added tax
  event.return = money.times(1 + RockCommerce.getTaxrate() / 100);
});
```

The `RcProduct` component has a hookable `priceTotal` method that returns the total price of the product. By hooking after that and modifying the returned value of that method we can instantly show prices for all available taxrates.

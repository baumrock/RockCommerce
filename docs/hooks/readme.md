# Hooks

## Watching properties via Alpine

If you only want to react to changing properties:

```js
RockCommerce.after("addComponent", (event) => {
  const component = event.return;
  if (component.$rctype !== "rcproduct") return;
  // use alpine magic $watch
  // triggers the callback whenever the price property of the product changes
  component.$watch("price", (price) => {
    console.log(price);
  });
});
```

## Watching + modifying return values

Example: Show prices including Austrian TAX (20%)

```js
RockCommerce.before("ProductPriceTotal", (event) => {
  let price = event.return; // read price from hookevent's return property
  price = price.times(1.2); // use currency.js for calculations!
  event.return = price; // write back the new value
});
```

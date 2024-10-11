# Product JS

## Hooks

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

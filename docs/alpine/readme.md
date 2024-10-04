# AlpineJS

To make the RockCommerce frontend as flexible as possible with minimum effort and easy-to-use syntax, we utilize AlpineJS components. This approach ensures an easy setup with no cluttered files, relying solely on HTML data attributes.

The main concept is that we register a global `RockCommerce` object, which has access to various components like `rccart` or `rcproduct`.

If you are new to AlpineJS, please read the [AlpineJS docs](https://alpinejs.dev/start-here) to get familiar with the basics.

At the moment, we use AlpineJS v3.x.

## raw() helper

AlpineJS is all about so called "Proxies". This is what provides all the reactivity. However, when logging components, this is counterproductive, because it hides the data that you want to inspect.

RockCommerce has a helper function to log the raw data of the component:

```js
// Proxy(Object) {objects: Array(3)}
console.log(RockCommerce.cart);

// {items: Array(2), count: 6, ajax: true}
console.log(RockCommerce.raw(RockCommerce.cart));
```

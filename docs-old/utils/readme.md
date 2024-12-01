# Utilities

## Loading indicator

```js
// show loading indicator
RockCommerce.loading();

// hide loading indicator
RockCommerce.loading(false);
```

## Logging Alpine Components

```js
// Proxy(Object) {objects: Array(3)}
console.log(RockCommerce.cart);

// {items: Array(2), count: 6, ajax: true}
console.log(RockCommerce.raw(RockCommerce.cart));
```


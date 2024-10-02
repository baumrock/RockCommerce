# Cart

RockCommerce provides all the tools to manage customers' carts.

On the server side, every cart is a `RockCommerce\Cart` object. This object has all the necessary methods to manage the cart and its items.

On the JS side a cart is a `rccart` Alpine component that you can interact with.

Finally, both the frontend and backend cart can communicate with each other over some AJAX endpoints at `/rockcommerce/cart`.

## Example Workflow

Consider a basic example workflow where a customer adds a product to their cart. All necessary steps involve the following:

### Step 1: Add product to the JS cart

```js
RockCommerce.cart.add({
  pid: 123, // product id (PW page id)
  amount: 2,
});
```

### Step 2: Save cart to the backend

```js
RockCommerce.cart.save();
```

### Step 3: Load cart from the backend

## Show cart count on the frontend

Showing the cart count on the frontend is easy. Just add `rc-cart-count` to an element and the cart count will be shown there and will be updated automatically whenever the cart changes. You can even have multiple cart count elements on the frontend.

```html
<div rc-cart-count>0</div>
```

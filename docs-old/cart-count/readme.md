# Cart Count

To show a cart indicator with the items count, you can use the `rc-cart-count` attribute on an element.

<img src=https://i.imgur.com/A7zj2IL.png height=40>

To add the cart count to any element, just add the `rc-cart-count` attribute:

```html
<div rc-cart-count></div>
```

RockCommerce will automatically update the cart count whenever the cart changes.

<img src=https://i.imgur.com/FlJJDYQ.png height=40>

If you want to hide the cart count when the cart is empty, you can add the `rc-autohide` attribute to the element:

```html
<div rc-cart-count hidden rc-autohide></div>
```

## Example

Here is the markup that I use in my UIkit + Tailwind CSS setup. The icon is from https://tablericons.com:

```html
<a
  href='/cart/'
  class='relative'
  title='Go to cart'
  uk-tooltip="pos:bottom"
>
  <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M4 19a2 2 0 1 0 4 0a2 2 0 1 0-4 0m11 0a2 2 0 1 0 4 0a2 2 0 1 0-4 0"/><path d="M17 17H6V3H4"/><path d="m6 5l14 1l-1 7H6"/></g></svg>
  <span
    rc-cart-count
    hidden rc-autohide
    class="uk-badge absolute top-[-7px] right-[-7px]"
  ></span>
</a>
```

# Product Variations

To use the product variations feature you need to install the RockGrid module as this module does the heavy lifting of all the variation inputs.

## Setup

Go to the RockCommerce config page and check the box to enable product variations. If you don't have RockGrid installed it will show a warning like this:

<img src=https://i.imgur.com/so7EHNZ.png class=blur>

Once you have installed RockGrid check that the RockCommerce grids are listed under Setup > RockGrid:

<img src=https://i.imgur.com/muRAtjy.png class=blur>

Next, add the `rockcommerce_variations` field to your product template (and also the `net` field if not yet done):

<img src=https://i.imgur.com/lHrkrWc.png class=blur>

Now edit a product and it should look something like this:

<img src=https://i.imgur.com/VjKWVy8.png class=blur>

To add a new variation click on the `+ New Variation` button. A modal will pop up and you can define the variation's name and its options:

<img src=https://i.imgur.com/dYEXO3n.png class=blur>

Close the modal (and maybe reload the page) and you can edit variations for your product.

## Variation Selection

<img src=https://i.imgur.com/mxnwxwx.gif class=blur>

To apply variations to your product you have to select them from the list. This is to make variations reusable across your shop, for example you might want to add the variation "Color (red/green/blue)" that you can use for all your T-Shirts.

You can either select all options of the variation by clicking on the variation's name in the "variation" column or you can enable/disable certain options by clicking on the option in the "option" column. This makes it possible to provide many global options but still define customized options for each product in a very fast and convenient way.

## Prices

<img src=https://i.imgur.com/OLu4s8Q.gif class=blur>

Each variation has a price. You can either use "0" as base price and enter the variation price for each variation (eg, 10€ / 20€ / 30€) or sometimes you might want to set a total price. To save you or your clients from the hassle of calculating all price differences there is the "total" column that you can use and RockCommerce will calculate the difference for you.

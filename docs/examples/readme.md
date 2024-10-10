# Example Hooks

## Helping Tooltip for Product Variations

<img src=https://i.imgur.com/yM8w5zH.png class=blur height=250>

```php
// show tooltip if max options are reached
ProcessWire.addHookBefore("RcVariation::optionsChanged", (event) => {
  // get the variation component
  const variation = event.object;

  // get the variation dom element via Alpine's magic $el attribute
  const el = variation.$el;

  // find all options and loop through them
  const options = el.querySelectorAll("[x-data=RcVariationOption]");
  options.forEach((option) => {
    // remove all existing tooltips
    const existingTooltip = option.querySelector(".tm-tooltip");
    if (existingTooltip) option.removeChild(existingTooltip);

    // do not add tooltip, if ...
    if (!variation.maxOptionsReached) return;
    if (option.classList.contains("rc-active")) return;

    // if maxoptions is less than 2, do not add tooltip
    // because every click will just change the active option
    if (variation.maxoptions < 2) return;

    // find all x-data=RcVariationOption elements
    // add div with uk-tooltip
    // add classes .uk-position-cover and .tm-tooltip separately
    const tooltip = document.createElement("div");
    tooltip.classList.add("uk-position-cover");
    tooltip.classList.add("tm-tooltip");
    tooltip.setAttribute(
      "uk-tooltip",
      "Maximum number reached - to select this option, please remove another option first."
    );

    // add tooltip dom element
    option.appendChild(tooltip);
  });
});
```

<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */
/** @var RockCommerce $rc */
$rc = $this->wire->modules->get('RockCommerce');
$segment = $input->urlSegment1;
if (!$segment) $session->redirect("./cart");

// variables
$modulePath = $config->paths->root . "site/modules/RockCommerce";

// set some defaults
$rc->lang = "en";
$rc->pageTitle = $segment ? ucfirst($segment) : "RockCommerce";

$rf = rockfrontend();
$rf->styles('rockcommerce')
  ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/src/less/uikit.theme.less')
  ->add("$modulePath/less/frontend.less")
  ->addAll("$modulePath/frontend/")
  ->addAll("/site/templates/RockCommerce/frontend/")
  ->minify(!$config->debug);

$rf->scripts()
  ->add('/site/templates/uikit/dist/js/uikit.min.js')
  ->add("$modulePath/lib/htmx.min.js")
  ->add("$modulePath/RockCommerceFrontend.min.js", "defer")
  ->addIf("$modulePath/Cart.min.js", $segment == "cart", "defer");

// include the init.php file
// this is the file where you can inject custom functionality
// examples: $rc->lang, $rc->pageTitle, $rc->headTop, $rc->headBottom, ...
$rc->view("_init.php");

// prerender site content, so that we can set properties from within our files
// eg in /site/templates/RockCommerce/main/checkout.latte: $rc->pageTitle = 'foo';
// see section "set some defaults" above
// you can also add custom scripts and styles in these files
$layout = $rc->view("endpoints/$segment") ?: $rc->view('endpoints/default');
?>
<!DOCTYPE html>
<html lang="<?= $rc->lang ?>">

<head>
  <?= $rc->headTop ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $rc->pageTitle ?></title>
  <?= $rc->headBottom ?>
</head>

<body hx-headers='{"X-Requested-With": "XMLHttpRequest"}'>
  <?= $layout ?>
</body>

</html>
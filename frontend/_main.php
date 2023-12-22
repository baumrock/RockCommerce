<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */
/** @var RockCommerce $rc */
$rc = $this->wire->modules->get('RockCommerce');
$segment = $input->urlSegment1;
if (!$segment) $session->redirect("./cart");

// set some defaults
$rc->lang = "en";
$rc->pageTitle = $segment ? ucfirst($segment) : "RockCommerce";

$rf = rockfrontend();
$rf->styles('rockcommerce')
  ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/src/less/uikit.theme.less')
  ->add('/site/modules/RockCommerce/less/frontend.less')
  ->addAll('/site/modules/RockCommerce/frontend/')
  ->addAll('/site/templates/RockCommerce/frontend/')
  ->minify(!$config->debug);

rockmigrations()->minify(__DIR__ . "/main.js");
$rf->scripts()
  ->add('/site/templates/uikit/dist/js/uikit.min.js')
  ->add('/site/modules/RockCommerce/lib/htmx.min.js')
  ->add(__DIR__ . "/main.min.js", "defer");

// include the init.php file
// this is the file where you can inject custom functionality
// examples: $rc->lang, $rc->pageTitle, $rc->headTop, $rc->headBottom, ...
$rf->view("_init.php");

// prerender site content, so that we can set properties from within our files
// eg in /site/templates/RockCommerce/main/checkout.latte: $rc->pageTitle = 'foo';
// see section "set some defaults" above
// you can also add custom scripts and styles in these files
$layout = $rf->view("layouts/$segment") ?: $rf->view('layouts/default');
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

<body>
  <?= $layout ?>
</body>

</html>
<?php

namespace ProcessWire;

use RockCommerce\RootPage;

/** @var RockFrontend $rockfrontend */
/** @var RockCommerce $rc */
$rc = $this->wire->modules->get('RockCommerce');
/** @var RockFrontend $rf */
$rf = $this->wire->modules->get('RockFrontend');
$rf->viewCheck404();

$rf->styles('rockcommerce')
  ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/src/less/uikit.theme.less')
  ->add('/site/modules/RockCommerce/less/frontend.less')
  ->addAll('/site/modules/RockCommerce/frontend/less/')
  ->addAll('/site/templates/RockCommerce/less/')
  ->minify(!$config->debug);

$rf->scripts()
  ->add('/site/templates/uikit/dist/js/uikit.min.js');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?= $rf->view('head_top') ?>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page->get(RootPage::field_pagetitle) ?: 'Checkout' ?></title>
  <?= $rf->view('head_bottom') ?>
</head>

<body>
  <?= $rf->view('body_top') ?>
  <?= $rf->view('header') ?>
  <?= $rf->view('main') ?>
  <?= $rf->view('footer') ?>
  <?= $rf->view('body_bottom') ?>
</body>

</html>
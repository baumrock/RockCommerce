<?php

namespace ProcessWire;

use RockCommerce\Order;

/**
 * @author Bernhard Baumrock, 10.11.2023
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 */
class ProcessRockCommerce extends Process
{

  const permission = 'rockcommerce-dashboard';

  public static function getModuleInfo()
  {
    return [
      'title' => 'RockCommerce',
      'version' => '0.0.1',
      'summary' => 'Dashboard for the RockCommerce Shop',
      'icon' => 'bar-chart',
      'requires' => [
        'RockCommerce',
      ],
      'installs' => [],

      'permission' => self::permission,
      'permissions' => [self::permission => 'May view RockCommerce GUI Endpoints'],

      // page that you want created to execute this module
      'page' => [
        'name' => 'rockcommerce',
        'parent' => 2,
        'title' => 'Shop'
      ],

      // optional extra navigation that appears in admin
      // if you change this, you'll need to a Modules > Refresh to see changes
      'nav' => [
        [
          'url' => '',
          'label' => 'Dashboard',
          'icon' => 'bar-chart',
        ],
        [
          'url' => 'settings/',
          'label' => 'Settings',
          'icon' => 'cogs',
        ],
      ],
    ];
  }

  public function init()
  {
    parent::init(); // always remember to call the parent init

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    if ($rm) $rm->saveCSS(
      __DIR__ . "/ProcessRockCommerce.less",
      minify: true,
      keepCSS: false,
    );
  }

  /**
   * Dashboard
   */
  public function execute()
  {
    $this->headline('RockCommerce Dashboard');
    $this->browserTitle('RockCommerce Dashboard');
    /** @var InputfieldForm $form */
    $form = $this->wire->modules->get('InputfieldForm');

    /** @var RockFrontend $rf */
    $rf = $this->wire->modules->get('RockFrontend');
    $style = $rf->toUrl(__DIR__ . "/ProcessRockCommerce.min.css");
    $this->wire->config->styles->add($style);

    // add echarts library
    $this->wire->config->scripts->add($rf->toUrl(__DIR__ . "/lib/echarts.min.js"));

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $form->add([
      'type' => 'markup',
      'label' => 'Net Revenue',
      'name' => 'net-rev',
      'value' => '<span id=net-rev>&nbsp;</span>',
      'icon' => 'money',
    ]);
    $form->add([
      'type' => 'markup',
      'label' => 'Number of Orders',
      'name' => 'num-orders',
      'value' => '<span id=num-orders>&nbsp;</span>',
      'icon' => 'files-o',
    ]);
    $form->add([
      'type' => 'markup',
      'label' => 'AVG Net Revenue per Order',
      'name' => 'avg-net',
      'value' => '<span id=avg-net>&nbsp;</span>',
      'icon' => 'money',
    ]);

    $cumulate = new InputfieldCheckbox();
    $cumulate->id = 'cumulate';
    $cumulate->label = ' Cumulative';

    $selected = new InputfieldCheckbox();
    $selected->id = 'selected';
    $selected->label = ' Only Selected';

    $form->add([
      'type' => 'markup',
      'name' => 'chart',
      'value' => $cumulate->render() .
        '<span class=uk-margin-small-left>' . $selected->render() . '</span>' .
        '<div id="chart" style="width: 100%; height: 400px;"></div>',
    ]);
    $rm->wrapFields($form, [
      'net-rev' => ['columnWidth' => 33],
      'num-orders' => ['columnWidth' => 33],
      'avg-net' => ['columnWidth' => 33],
      'chart',
    ], [
      'label' => 'Stats for paid and open orders',
      'icon' => 'bar-chart',
    ]);

    $form->add([
      'type' => 'RockGrid',
      'grid' => 'RockCommerce',
      'label' => 'Orders',
      'icon' => 'check',
    ]);

    return $form->render();
  }

  public function executeEditStatus(): string
  {
    $id = $this->wire->input->post->id ?: $this->wire->input->get->id;
    $order = $this->wire->pages->get($id);
    if (!$order instanceof Order) throw new WireException("Invalid ID $id");

    $setStatus = $this->wire->input->post('status', Order::statuses());
    if ($setStatus) {
      $order->setAndSave(Order::field_paymentstatus, $setStatus);
      $this->wire->session->redirect($order->editUrl());
    }

    $this->headline("Edit Status of Order #$order ({$order->title})");
    $this->browserTitle("Edit Status of Order #$order ({$order->title})");

    /** @var InputfieldForm $form */
    $form = $this->wire->modules->get('InputfieldForm');

    $f = new InputfieldSelect();
    $f->name = 'status';
    $f->label = "Status";
    $f->value = $order->paymentstatus();
    $f->required = true;
    foreach (Order::statuses() as $status) {
      $f->addOption($status);
    }
    $form->add($f);

    $form->add([
      'type' => 'hidden',
      'name' => 'id',
      'value' => $id,
    ]);

    $form->add([
      'type' => 'submit',
    ]);

    return $form->render();
  }

  public function executeSettings()
  {
    $url = $this->wire->pages->get(2)->url;
    $p = rockcommerce()->rootPage();
    $this->wire->session->redirect($url . "page/?id=$p");
  }
}

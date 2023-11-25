<?php

namespace ProcessWire;

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
      'title' => 'RockCommerce Dashboard',
      'version' => '0.0.1',
      'summary' => '',
      'icon' => '',
      'requires' => [
        'RockCommerce',
      ],
      'installs' => [],

      'permission' => self::permission,
      'permissions' => [self::permission => 'May run the RockCommerce Dashboard module'],

      // page that you want created to execute this module
      'page' => [
        'name' => 'rockcommerce',
        'parent' => 2,
        'title' => 'RockCommerce'
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
   *
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
    $this->wire->config->scripts->add($rf->toUrl(__DIR__ . "/echarts.min.js"));

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

    $form->add([
      'type' => 'markup',
      'name' => 'chart',
      'value' => $cumulate->render() .
        '<div id="chart" style="width: 100%; height: 400px;"></div>',
    ]);
    $rm->wrapFields($form, [
      'net-rev' => ['columnWidth' => 33],
      'num-orders' => ['columnWidth' => 33],
      'avg-net' => ['columnWidth' => 33],
      'chart',
    ], [
      'label' => 'Stats',
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
}

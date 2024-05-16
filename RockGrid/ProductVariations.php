<?php

namespace RockCommerce;

use RockGrid\Grid;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class ProductVariations extends Grid
{
  public function getData(): array
  {
    // find repeater items
    $options = Variation::field_options . '.title';
    $raw = wire()->pages->findRaw(
      [
        'parent' => rockcommerce()->variationsPage(),
      ],
      [
        'title',
        Variation::field_comment => 'comment',
        $options,
      ],
      [
        'flat' => true,
        'nulls' => true,
      ]
    );

    // convert array to rockgrid format
    $data = [];
    foreach ($raw as $varID => $variation) {
      foreach ($variation[$options] as $optID => $opt) {
        $data[] = [
          'id' => $optID,
          'Variation' => $variation['title'],
          'Option' => $opt,
          'Comment' => $variation['comment'],
          'var:id' => $varID,
        ];
      }
    }
    return $data;
  }
}

<?php

namespace RockCommerce;

use RockGrid\Grid;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class ProductVariations extends Grid
{
  public function buildUI(): void
  {
    $this->buttonsLeft->add([
      'name' => 'newVariation',
      'icon' => 'plus',
      'label' => 'New Variation',
      'href' => rockcommerce()->addVariationsUrl(),
      'attrs' => [
        'rg-modal',
        'rg-modal-reload',
      ],
    ]);

    $this->buttonsRight->add([
      'name' => 'refresh',
      'icon' => 'refresh',
      'tooltip' => 'Reload data',
    ]);
    $this->buttonsRight->add([
      'name' => 'removeFilters',
      'label' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 3l18 18M9 5h9.5a1 1 0 0 1 .5 1.5l-4.049 4.454M14 14v5l-4-3v-4L5 6.5a1 1 0 0 1 .18-1.316"/></svg>',
      'tooltip' => 'Remove all filters',
      'hidden' => true,
    ]);
    $this->buttonsRight->add([
      'name' => 'selectFiltered',
      'label' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.18 20.274L9 21v-8.5L4.52 7.572A2 2 0 0 1 4 6.227V4h16v2.172a2 2 0 0 1-.586 1.414L15 12v3m0 4l2 2l4-4"/></svg>',
      'tooltip' => 'Select filtered rows',
      'hidden' => true,
    ]);
    // $this->buttonsRight->add([
    //   'name' => 'unselectFiltered',
    //   'label' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 20l-3 1v-8.5L4.52 7.572A2 2 0 0 1 4 6.227V4h16v2.172a2 2 0 0 1-.586 1.414L15 12v1.5m1 5.5a3 3 0 1 0 6 0a3 3 0 1 0-6 0m1 2l4-4"/></svg>',
    //   'tooltip' => 'Unselect filtered rows',
    //   'hidden' => true,
    // ]);
    // $this->buttonsRight->add([
    //   'name' => 'selectAll',
    //   'label' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.5 5.5L5 7l2.5-2.5m-4 7L5 13l2.5-2.5m-4 7L5 19l2.5-2.5M11 6h9m-9 6h9m-9 6h9"/></svg>',
    //   'tooltip' => 'Select all rows',
    //   'hidden' => true,
    // ]);
    $this->buttonsRight->add([
      'name' => 'unselectAll',
      'label' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5h8m-8 4h5m-5 6h8m-8 4h5M3 5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1zm0 10a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/></svg>',
      'tooltip' => 'Unselect all rows',
      'hidden' => true,
    ]);
  }
  public function getData(): array
  {
    // find repeater items
    $options = Variation::field_options . '.title';
    $raw = wire()->pages->findRaw(
      [
        'parent' => rockcommerce()->variationsPage(),

        // workaround to select only pages that have options
        // findRaw() fails if this check is removed!
        Variation::field_options . '.count>' => 0,
      ],
      [
        'title',
        Variation::field_comment => 'comment',
        $options, // options.title
      ],
      [
        'flat' => true,
        'nulls' => true,
      ]
    );

    // convert array to rockgrid format
    $data = [];
    foreach ($raw as $varID => $variation) {
      $opts = $variation[$options];
      if (!is_array($opts)) $opts = [];
      foreach ($opts as $optID => $opt) {
        $data[] = [
          'id' => $optID,
          'Variation' => $variation['title'],
          'Option' => $opt,
          'Comment' => $variation['comment'],
          'varID' => $varID,
        ];
      }
    }
    return $data;
  }
}

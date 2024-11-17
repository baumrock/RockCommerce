<?php

namespace RockCommerce;

use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockCommerce;
use RockMigrations\MagicPage;

class Variation extends Page
{
  use MagicPage;

  private $optionsArray;

  public function editForm(InputfieldWrapper $form): void
  {
    // add API hint to title field
    if ($f = $form->get('title')) {
      if ($f->value) {
        $f->notes = "API: \$rockcommerce->variations('{$this->name}')->title";
      } else {
        $f->notes = "Enter a label. A unique ID based on the label will be generated on the first save.";
      }
      $f->columnWidth = 50;
    }
  }

  /**
   * Get label for this option
   * Falls back to ID if title is not set.
   */
  public function label(): string
  {
    return $this->title ?: $this->variationID();
  }

  public function onCreate(): void
  {
    $this->status = 1;
  }

  public function options()
  {
    return $this->getFormatted(RockCommerce::field_variationoptions);
  }
}

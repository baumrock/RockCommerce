<?php

namespace RockCommerce;

use ProcessWire\WireData;

class Step extends WireData
{
  public $active = false;

  /** @var Checkout */
  public $checkout;

  public $content = false;
  public $done = false;
  public $headline;
  public $name;
  public $num;
  public $sidebar = false;
  public $url;

  public function __construct(string $name, array $data = [])
  {
    $this->name = $name;
    $this->url = "./?step=$name";
    $this->content = "partials/$name.latte";
    $this->sidebar = "partials/$name-sidebar.latte";
    foreach ($data as $k => $v) $this->$k = $v;
  }

  public function init(Checkout $checkout): void
  {
    $stepname = $this->wire->input->get('step', 'string');
    $this->checkout = $checkout;
    $this->num = $checkout->steps->count();

    // set active step
    // if not yet set, we set it to the current item (first item)
    if (!$checkout->activeStep || ($stepname && $stepname === $this->name)) {
      $checkout->activeStep = $this;
    }
  }

  public function prev(): Step|false
  {
    return false;
  }

  public function next(): Step|false
  {
    return false;
  }

  public function nextViewable(): Step|false
  {
    $step = false;
    foreach ($this->checkout->steps as $s) {
      if (!$s instanceof Step) continue;
      if (!$s->viewable()) return $step;
      $step = $s;
    }
    return $step;
  }

  public function viewable(): bool
  {
    return true;
  }

  public function clickable(): bool
  {
    return $this->viewable() && !$this->active;
  }

  public function __debugInfo()
  {
    return [
      'num' => $this->num,
      'name' => $this->name,
      'url' => $this->url,
      'headline' => $this->headline,
      'content' => $this->content,
      'sidebar' => $this->sidebar,
    ];
  }
}

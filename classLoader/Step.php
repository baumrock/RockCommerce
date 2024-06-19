<?php

namespace RockCommerce;

use ProcessWire\WireData;

use function ProcessWire\rockcommerce;

class Step extends WireData
{
  const sessionName = 'rc-step-';

  public $active = false;

  /** @var Checkout */
  public $checkout;

  public $content = false;
  public $done = false;

  /** @var Frontend */
  public $frontend;

  public $headline;
  public $name;
  public $num;
  public $url;

  public function __construct(string $name, array $data = [])
  {
    $this->frontend = rockcommerce()->frontend;
    $this->name = $name;
    $this->url = "./?step=$name";
    $this->content = "partials/$name.latte";
    foreach ($data as $k => $v) $this->$k = $v;
    $this->content = $this->frontend->getFile($this->content);

    // load data from session storage
    foreach ($this->getData() as $k => $v) {
      $this->$k = $v;
    }
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

  // ##### regular methods #####

  public function clickable(): bool
  {
    return $this->viewable() && !$this->active;
  }

  /**
   * Get step-data from session storage
   */
  public function getData(): WireData
  {
    $data = new WireData();
    $raw = $this->wire->session->get(self::sessionName . $this->name);
    if (!is_array($raw)) $raw = [];
    $data->setArray($raw);
    return $data;
  }

  public function next(): Step|null
  {
    return $this->checkout->steps->getNext($this);
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

  public function prev(): Step|null
  {
    return $this->checkout->steps->getPrev($this);
  }

  /**
   * Set properties and save data to session
   *
   * Usage:
   * $step->save([
   *   'done' => true,
   *   'firstname' => 'John',
   *   'lastname' => 'Doe',
   * ]);
   */
  public function save(array $data): self
  {
    $_data = $this->getData();
    foreach ($data as $k => $v) $_data->$k = $v;
    $this->wire->session->set(
      self::sessionName . $this->name,
      $_data->getArray()
    );
    return $this;
  }

  /**
   * Redirect to next step
   * @return void
   */
  public function toNext(): void
  {
    $next = $this->next();
    if ($next) $this->wire->session->redirect($next->url);
    $this->wire->session->redirect('./');
  }

  public function viewable(): bool
  {
    return true;
  }

  public function __debugInfo()
  {
    return [
      'num' => $this->num,
      'name' => $this->name,
      'url' => $this->url,
      'headline' => $this->headline,
      'content' => $this->content,
      'done' => $this->done,
    ];
  }
}

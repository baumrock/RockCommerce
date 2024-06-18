<?php

namespace RockCommerce;

use ProcessWire\FilenameArray;
use ProcessWire\Wire;

class Checkout extends Wire
{
  public $activeStep;

  /** @var FilenameArray */
  private $folders;

  /** @var StepsArray */
  public $steps;

  public function __construct()
  {
    $this->steps = new StepsArray();
    $this->folders = new FilenameArray();
    $this->addFolder(__DIR__ . '/../frontend');
  }

  public function addFolder($folder): self
  {
    $filename = $this->getFilename($folder);
    $this->folders->add($filename);
    return $this;
  }

  public function addSteps(array $steps): self
  {
    foreach ($steps as $name => $data) {
      if (is_int($name) && is_string($data)) {
        // we have a name-only step
        $name = $data;
        $data = [];
      }
      $step = new Step($name, $data);
      $this->steps->add($step);
      $step->init($this);
    }
    $this->activeStep->active = true;
    return $this;
  }

  public function getFilename($file): string
  {
    return 'tbd';
  }

  public function getStep(): Step|false
  {
    if (!$this->steps->count()) return false;
    if ($step = $this->wire->input->get('step', 'string')) {
      $step = $this->steps->get("name=$step");
      if (!$step instanceof Step) return false;
      if (!$step->viewable()) {
        // $this->wire->session->redirect($step->lastViewable()->url);
      }
      return $step;
    }
    return $this->steps->first();
  }

  public function __debugInfo(): array
  {
    return [
      'steps' => $this->steps,
      'activeStep' => $this->activeStep,
    ];
  }
}

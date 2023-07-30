<?php

namespace RockForms;

use ProcessWire\ModulePage;
use ProcessWire\RockCommerce;
use ProcessWire\RockMigrations;
use ProcessWire\WireException;
use RockPageBuilder\Block;

class Checkout extends RockForm
{

  public function buildForm()
  {
    // check get parameters for validity
    if ($this->checkInput() === false) return;

    // variables
    $price = $this->license()->price(1);

    // setup form
    $form = $this;
    $form->setRockFormsRenderer('UIkit');

    $form->addInteger("amount")
      ->setDefaultValue(1)
      ->setRequired()
      ->setHtmlAttribute("min", 1)
      ->setHtmlAttribute("onkeyup", "updatePrice(event)")
      ->setHtmlAttribute("onchange", "updatePrice(event)");

    $form->addText("forename", __("Vorname"))->setRequired();
    $form->addText("surname", __("Nachname"))->setRequired();

    $file = $this->rockcommerce()->viewFile("main/.checkoutform.html");
    $markup = $this->wire->files->render($file);
    $markup = str_replace('{price}', $price, $markup);
    $markup = str_replace('{subject}', $this->subject(1), $markup);
    $form->addMarkup($markup);

    $form->addSubmit("submit", __("Kostenpflichtig bestellen"))
      ->getControlPrototype()
      ->addClass('uk-width-1-1');

    $form->addMarkup('<script>
      function updatePrice(e) {
        let util = UIkit.util;
        let amount = e.target.value;
        let $price = util.$("#price");
        let EUR = new Intl.NumberFormat("de-DE", {
          style: "currency",
          currency: "EUR",
        });
        $price.innerText = EUR.format(amount * util.data($price, "price"));
      }
      </script>');
  }

  public function processInput()
  {
    $input = $this->wire->input;
    $module = $this->wire->pages->get($input->get('module', 'int'));
    if (!$module instanceof ModulePage) throw new WireException("Invalid value for module");
    $license = $this->wire->pages->get($input->get('license', 'int'));
    if (!$license instanceof Block) throw new WireException("Invalid value for license");

    // prepare data
    $values = $this->getValues();
    $values->module = $module->id;
    $values->license = $license->id;
    $price = $license->price(1) * $values->amount;
    // bd($values);

    // create order and payment
    $order = false;
    $payment = false;
    /** @var RockCommerce $rc */
    $rc = $this->wire->modules->get('RockCommerce');
    try {
      $order = $rc->createOrder($this->subject(), $price);
      $order->meta('details', (array)$values);
      $payment = $order->createPayment();
    } catch (\Throwable $th) {
      $this->addError("Something went wrong - error has been logged");
      $this->wire->log->save($rc, $th->getMessage());
    }

    // redirect to mollie checkout
    if ($payment) {
      $this->wire->session->redirect($payment->_links->checkout->href);
    } else {
      if ($order and $order->id) $order->delete();
      $this->addError("Something went wrong - please try again!");
    }
  }

  public function renderSuccess($values)
  {
    $name = $values->forename;
    if ($values->surname) $name .= " " . $values->surname;
    return "<div class='uk-alert uk-alert-success'>Thank you for submitting the form, $name!</div>";
  }

  /* helpers */

  /**
   * Check if input parameters are valid
   * If not they throw an exception and this method makes sure that the exception
   * does not expose any sensitive information to guest users.
   */
  private function checkInput()
  {
    try {
      $this->module();
      $this->license();
    } catch (\Throwable $th) {
      $this->addMarkup("<div class='uk-alert uk-alert-danger uk-text-center uk-margin-remove'>Invalid Module or License</div>");
      return false;
    }
  }

  public function rockcommerce(): RockCommerce
  {
    return $this->wire->modules->get('RockCommerce');
  }

  public function subject(): string
  {
    return $this->module()->title . " - " . $this->license()->name(1);
  }

  private function module(): ModulePage
  {
    return $this->wire->pages->get($this->wire->input->get('module', 'int'));
  }
  private function license(): Block
  {
    return $this->wire->pages->get($this->wire->input->get('license', 'int'));
  }
}

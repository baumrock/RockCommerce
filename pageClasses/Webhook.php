<?php

namespace RockCommerce;

use ProcessWire\Inputfield;
use ProcessWire\InputfieldWrapper;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

use function ProcessWire\rockcommerce;
use function ProcessWire\wire;

class Webhook extends Page
{
  use MagicPage;

  const prefix = "rockcommerce_webhook_";

  const meta_info = self::prefix . "info";
  const meta_input = self::prefix . "input";
  const meta_output = self::prefix . "output";
  const meta_debugoutput = self::prefix . "debugoutput";

  /** magic */

  public function editFormContent(InputfieldWrapper $form): void
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $form->add([
      'type' => 'markup',
      'label' => 'Request Info',
      'value' => $rm->dump($this->meta(self::meta_info)),
    ]);
    $form->add([
      'type' => 'markup',
      'label' => 'Request Input',
      'value' => $rm->dump($this->meta(self::meta_input)),
      'notes' => 'The input data received from the webhook.',
    ]);
    $form->add([
      'type' => 'markup',
      'label' => 'Request Output',
      'value' => $rm->dump($this->meta(self::meta_output)),
      'notes' => 'The actual output sent as response of the webhook.',
    ]);
    $form->add([
      'type' => 'markup',
      'label' => 'Debug Output',
      'value' => $rm->dump($this->meta(self::meta_debugoutput)),
      'notes' => 'The debug output as if $config->debug was enabled.',
    ]);
  }

  public function onCreate(): void
  {
    $this->status = 1;
    $this->title = date("d.m.Y H:i:s");
  }

  /** backend */

  public function echo($data, $force = false): void
  {
    ob_start();
    if (is_string($data)) echo $data . "\n";
    else var_dump($data);
    $dump = ob_get_clean();

    // always log to the logs
    wire()->log->save('rockcommerce-webhooks', $dump, [
      'url' => '/rockcommerce/webhooks',
    ]);

    // only echo if debug is enabled
    if ($force || wire()->config->debug) {
      // echo to output
      echo $dump;

      // append to output
      $out = $this->meta(self::meta_output);
      $this->meta(self::meta_output, $out . $dump);
    }

    // always append to debugoutput
    $out = $this->meta(self::meta_debugoutput);
    $this->meta(self::meta_debugoutput, $out . $dump);
  }

  public function getPageListLabel()
  {
    return rockcommerce()->badge($this->title);
  }

  public function requestInfo(): array
  {
    $data = [
      'method' => $_SERVER['REQUEST_METHOD'],
      'request' => $_SERVER['REQUEST_URI'] . " " . $_SERVER['SERVER_PROTOCOL'],
      'host' => $_SERVER['HTTP_HOST'],
      'IP' => $_SERVER['REMOTE_ADDR'],
      'content_type' => $_SERVER['CONTENT_TYPE'],
      'content_length' => $_SERVER['CONTENT_LENGTH']
    ];

    if (isset($_SERVER['HTTP_VIA'])) $data['via'] = $_SERVER['HTTP_VIA'];
    if (isset($_SERVER['HTTP_ACCEPT'])) $data['accept'] = $_SERVER['HTTP_ACCEPT'];
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) $data['accept_encoding'] = $_SERVER['HTTP_ACCEPT_ENCODING'];

    return $data;
  }
}

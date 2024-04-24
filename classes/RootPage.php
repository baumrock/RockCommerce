<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFile;
use ProcessWire\InputfieldForm;
use ProcessWire\Page;
use ProcessWire\RockMigrations;
use RockMigrations\MagicPage;

class RootPage extends Page
{
  use MagicPage;

  const tpl = "rockcommerce_rootpage";
  const prefix = "rockcommerce_rootpage_";

  const field_logo = self::prefix . "logo";
  const field_trailingslash = self::prefix . "trailingslash";
  const field_terms = self::prefix . "terms";
  const field_pagetitle = self::prefix . "pagetitle";

  public $noSitemap = true;

  public function editForm(InputfieldForm $form): void
  {
    $form->remove('title');
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $rm->migrate([
      'fields' => [
        self::field_logo => [
          'type' => 'image',
          'label' => 'Logo',
          'maxFiles' => 1,
          'descriptionRows' => 1,
          'extensions' => 'jpg jpeg gif png svg',
          'maxSize' => 3, // max 3 megapixels
          'okExtensions' => ['svg'],
          'icon' => 'picture-o',
          'outputFormat' => FieldtypeFile::outputFormatSingle,
          'gridMode' => 'list', // left, list
          'notes' => 'Description will be used as ALT text.',
        ],
        self::field_trailingslash => [
          'type' => 'toggle',
          'label' => 'Trailing Slash for Frontend Pages',
          'formatType' => 0, // integer 0/1
          'labelType' => 0, // yes/no
          'inputfieldClass' => 0, // yes/no
          'defaultOption' => 'no',
          'notes' => 'This will only affect pages under /rockcommerce'
            . "\nNote that the default checkout uses links without trailing slashes, so it will be slightly more efficient if you leave them disabled.",
        ],
        self::field_pagetitle => [
          'type' => $this->wire->languages ? 'textLanguage' : 'language',
          'label' => 'Page Title',
          'icon' => 'align-left',
          'textformatters' => [
            'TextformatterEntities',
          ],
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields' => [
            self::field_pagetitle,
            self::field_logo,
            self::field_trailingslash,
            self::field_terms,
          ],
          'noChildren' => 1, // no children allowed
          'noLang' => 0,
          'urlSegments' => true,
          'noAppendTemplateFile' => true,
          'noSettings' => true,
          'icon' => 'shopping-cart',
          'noParents' => 1,
        ],
      ],
    ]);
    $rm->createViewFile(self::tpl);
  }
}

<?php

namespace RockCommerce;

use ProcessWire\FieldtypeFile;
use ProcessWire\FieldtypePage;
use ProcessWire\Inputfield;
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
  const field_footerlinks = self::prefix . "footerlinks";

  public function editForm(InputfieldForm $form): void
  {
    $form->remove('title');
  }

  public function migrate()
  {
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');

    // cleanup
    $rm->deleteField(self::prefix . "pagetitle", true);

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
          'label' => 'Trailing Slash for Frontend Pages?',
          'formatType' => 0, // integer 0/1
          'labelType' => 0, // yes/no
          'inputfieldClass' => 0, // yes/no
          'defaultOption' => 'no',
          'notes' => 'When enabled all requests under /rc without a slash will be redirected to the page with trailing slash.'
            . "\nNote that the default files use links without trailing slashes, so it will be slightly more efficient if you leave them disabled.",
          'icon' => 'share',
        ],
        self::field_footerlinks => [
          'type' => 'page',
          'label' => 'Footer-Links',
          'derefAsPage' => FieldtypePage::derefAsPageArray,
          'inputfield' => 'InputfieldPageListSelectMultiple',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'icon' => 'sitemap',
          'notes' => 'These pages will be shown in the footer (eg Privacy Policy, Imprint).',
        ],
      ],
      'templates' => [
        self::tpl => [
          'fields-' => [
            'title' => ['collapsed' => Inputfield::collapsedHidden],
            self::field_logo,
            self::field_trailingslash,
            self::field_footerlinks,
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
    $root = rtrim($this->wire->config->paths->root, "/");
    $this->wire->files->copy(
      "$root/site/modules/RockCommerce/stubs/rockcommerce_rootpage.php",
      "$root/site/templates"
    );
  }
}

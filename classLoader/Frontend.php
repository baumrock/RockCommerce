<?php

namespace RockCommerce;

use ProcessWire\FilenameArray;
use ProcessWire\Paths;
use ProcessWire\Wire;

class Frontend extends Wire
{
  /** @var FilenameArray */
  private $folders;

  public function __construct()
  {
    $this->folders = new FilenameArray();
    $this->addFolder('/');
    $this->addFolder('/site/modules/RockCommerce/frontend');
    $this->addFolder('/site/templates/RockCommerce/frontend');
  }

  /**
   * Add folder to array of folders to look for files
   *
   * Folder can either be an absolute path or relative to site root.
   * Later added folders have priority over earlier added folders!
   *
   * @param string $folder
   * @return void
   */
  public function addFolder(string $folder): void
  {
    // make sure we have no trailing slash on folders
    $folder = rtrim(Paths::normalizeSeparators($folder), '/');
    $root = $this->wire->config->paths->root;
    $inRoot = $this->wire->files->fileInPath($folder, $root);

    if ($inRoot && is_dir($folder)) {
      // we have an absolute path inside pw
      $this->folders->prepend(realpath($folder));
    } else {
      // relative path, try to find it in pw root
      $f = rtrim($root, '/') . $folder;
      if (is_dir($f)) $this->folders->prepend($f);
    }
  }

  public function getFile($file): string|false
  {
    $file = Paths::normalizeSeparators($file);
    $root = $this->wire->config->paths->root;
    $inRoot = $this->wire->files->fileInPath($file, $root);
    // we have an absolute path inside pw
    if (is_file($file) && $inRoot) return $file;
    else {
      $file = ltrim($file, '/');
      // relative path, try to find it in folders
      foreach ($this->folders as $folder) {
        $f = "$folder/$file";
        if (is_file($f)) return $f;
      }
    }
    return false;
  }

  public function __debugInfo(): array
  {
    $folders = [];
    foreach ($this->folders as $f) $folders[] = $f;
    return [
      'folders' => $folders,
    ];
  }
}

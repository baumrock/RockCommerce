# RockCommerce Alpine Components

RockCommerce will automatically minify all files in this folder. It will also load the file on the frontend, if you tell it to:

```php
$this->loadJS('rccart');
```

This is not necessary if you use the `attrs()` helper:

```php
rockcommerce()->attrs('rcproduct', "
  x-data='rcproduct'
  rc-pid=$this
  rc-price='{$this->price()->getFloat()}'
  rc-minamount='{$this->minamount()}'
  rc-maxamount='{$this->maxamount()}'
");
```

In that case the loadJS() call is fired automatically in the background.

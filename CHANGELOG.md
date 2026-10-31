## [3.1.0](https://github.com/baumrock/RockCommerce/compare/v3.0.0...v3.1.0) (2026-10-31)


### Features

* allow disabling webhooks and custom webhook path ([c2ba158](https://github.com/baumrock/RockCommerce/commit/c2ba158e12221111362770547e6c4818585fbe40))


### Bug Fixes

* explicit nullable types for PHP 8.4+ ([f0ecf02](https://github.com/baumrock/RockCommerce/commit/f0ecf0266ef7327872fe497fef865ab5982ac49c))
* **security:** bind cart session to ID and HMAC token ([22d7353](https://github.com/baumrock/RockCommerce/commit/22d735385d60b966d350faff622398f6e34234e1))
* **security:** build host URLs from config httpHosts ([ce186ac](https://github.com/baumrock/RockCommerce/commit/ce186ace7a2261ec65c9e83e1ade2749275d66e4))
* **security:** enforce product minamount and maxamount server-side ([54600db](https://github.com/baumrock/RockCommerce/commit/54600dba80cee28cbf9507cc459d08aa8c49e612))
* **security:** rate-limit webhooks and add verify hook ([3196e8f](https://github.com/baumrock/RockCommerce/commit/3196e8fba86e40227dcf1b6b3254ee524a2f3361))
* **security:** reject non-positive cart item quantities ([67b6354](https://github.com/baumrock/RockCommerce/commit/67b6354dc89c34842014afb1a1636f110d44374e))
* **security:** scope cart delete to parent cart item ([c4853c2](https://github.com/baumrock/RockCommerce/commit/c4853c20cbb121efb09b94643bb099b6e8197662))
* **security:** scope getCart lookup to carts container ([c024c61](https://github.com/baumrock/RockCommerce/commit/c024c61b698ec0ba8e2f9b6151f5a8b5483ffbfe))
* **security:** validate cart tax rate selection ([7de9f70](https://github.com/baumrock/RockCommerce/commit/7de9f7081f96e04a114d56727bdd7c550901ec68))

## [3.0.0](https://github.com/baumrock/RockCommerce/compare/v2.0.0...v3.0.0) (2026-01-21)


### ⚠ BREAKING CHANGES

* change license to MIT

### Bug Fixes

* change license to MIT ([e039bbf](https://github.com/baumrock/RockCommerce/commit/e039bbf99e1b727f4f9cd85e357b328f9ae68cae))

## [2.0.0](https://github.com/baumrock/RockCommerce/compare/v1.4.1...v2.0.0) (2025-07-25)


### ⚠ BREAKING CHANGES

* move mollie payment provider to RockMollie module

### Miscellaneous Chores

* move mollie payment provider to RockMollie module ([d795474](https://github.com/baumrock/RockCommerce/commit/d795474e7f92b72574f4f45ff595cfb0f17e8a26))

## [1.4.1](https://github.com/baumrock/RockCommerce/compare/v1.4.0...v1.4.1) (2025-07-17)


### Bug Fixes

* remove double slashfrom require call ([97bb1e9](https://github.com/baumrock/RockCommerce/commit/97bb1e9f4040e8934495542dfad5e9e757a01a67))

## [1.4.0](https://github.com/baumrock/RockCommerce/compare/v1.3.2...v1.4.0) (2025-07-17)


### Features

* add support for other payment providers ([83f0f29](https://github.com/baumrock/RockCommerce/commit/83f0f29d6e185f3a207c83bcefa90f95d081e4cf))


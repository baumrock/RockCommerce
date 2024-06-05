// RockCommerce JS Base Class
class _RockCommerce {
  constructor(config) {
    for (let key in config) {
      this[key] = config[key];
    }
  }
}

var RockCommerce = new _RockCommerce(RockCommerceConfig);

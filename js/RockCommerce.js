// RockCommerce JS Base Class
class _RockCommerce {
  events = {};
  timer = null;

  constructor(config) {
    for (let key in config) {
      this[key] = config[key];
    }
  }
}

/**
 * Method to attach hooks to RockCommerce
 * @param event
 * @param handler
 */
_RockCommerce.prototype.on = function (event, handler) {
  if (!this.events[event]) {
    this.events[event] = [];
  }
  this.events[event].push(handler);
};

/**
 * Method to throttle the execution of a function
 * https://dev.to/jeetvora331/throttling-in-javascript-easiest-explanation-1081
 */
_RockCommerce.prototype.throttle = function (func, delay = 50) {
  clearTimeout(this.timer);
  this.timer = setTimeout(() => {
    func();
  }, delay);
};

/**
 * Method that executes all attached hooks and returns the final value
 * @param event
 * @param args
 * @returns
 */
_RockCommerce.prototype.trigger = function (event, ...args) {
  if (this.events[event]) {
    return this.events[event].reduce((result, handler) => {
      return handler(result);
    }, ...args);
  }
  return args[0];
};

var RockCommerce = new _RockCommerce(RockCommerceConfig);

/*  Prototype JavaScript framework, version 1.6.1
 *  (c) 2005-2009 Sam Stephenson
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://www.prototypejs.org/
 *
 *--------------------------------------------------------------------------*/

var Prototype = {
  Version: '1.6.1',

  Browser: (function(){
    var ua = navigator.userAgent;
    var isOpera = Object.prototype.toString.call(window.opera) == '[object Opera]';
    return {
      IE:             !!window.attachEvent && !isOpera,
      Opera:          isOpera,
      WebKit:         ua.indexOf('AppleWebKit/') > -1,
      Gecko:          ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
      MobileSafari:   /Apple.*Mobile.*Safari/.test(ua)
    }
  })(),

  BrowserFeatures: {
    XPath: !!document.evaluate,
    SelectorsAPI: !!document.querySelector,
    ElementExtensions: (function() {
      var constructor = window.Element || window.HTMLElement;
      return !!(constructor && constructor.prototype);
    })(),
    SpecificElementExtensions: (function() {
      if (typeof window.HTMLDivElement !== 'undefined')
        return true;

      var div = document.createElement('div');
      var form = document.createElement('form');
      var isSupported = false;

      if (div['__proto__'] && (div['__proto__'] !== form['__proto__'])) {
        isSupported = true;
      }

      div = form = null;

      return isSupported;
    })()
  },

  ScriptFragment: '<script[^>]*>([\\S\\s]*?)<\/script>',
  JSONFilter: /^\/\*-secure-([\s\S]*)\*\/\s*$/,

  emptyFunction: function() { },
  K: function(x) { return x }
};

if (Prototype.Browser.MobileSafari)
  Prototype.BrowserFeatures.SpecificElementExtensions = false;


var Abstract = { };


var Try = {
  these: function() {
    var returnValue;

    for (var i = 0, length = arguments.length; i < length; i++) {
      var lambda = arguments[i];
      try {
        returnValue = lambda();
        break;
      } catch (e) { }
    }

    return returnValue;
  }
};

/* Based on Alex Arnell's inheritance implementation. */

var Class = (function() {
  function subclass() {};
  function create() {
    var parent = null, properties = $A(arguments);
    if (Object.isFunction(properties[0]))
      parent = properties.shift();

    function klass() {
      this.initialize.apply(this, arguments);
    }

    Object.extend(klass, Class.Methods);
    klass.superclass = parent;
    klass.subclasses = [];

    if (parent) {
      subclass.prototype = parent.prototype;
      klass.prototype = new subclass;
      parent.subclasses.push(klass);
    }

    for (var i = 0; i < properties.length; i++)
      klass.addMethods(properties[i]);

    if (!klass.prototype.initialize)
      klass.prototype.initialize = Prototype.emptyFunction;

    klass.prototype.constructor = klass;
    return klass;
  }

  function addMethods(source) {
    var ancestor   = this.superclass && this.superclass.prototype;
    var properties = Object.keys(source);

    if (!Object.keys({ toString: true }).length) {
      if (source.toString != Object.prototype.toString)
        properties.push("toString");
      if (source.valueOf != Object.prototype.valueOf)
        properties.push("valueOf");
    }

    for (var i = 0, length = properties.length; i < length; i++) {
      var property = properties[i], value = source[property];
      if (ancestor && Object.isFunction(value) &&
          value.argumentNames().first() == "$super") {
        var method = value;
        value = (function(m) {
          return function() { return ancestor[m].apply(this, arguments); };
        })(property).wrap(method);

        value.valueOf = method.valueOf.bind(method);
        value.toString = method.toString.bind(method);
      }
      this.prototype[property] = value;
    }

    return this;
  }

  return {
    create: create,
    Methods: {
      addMethods: addMethods
    }
  };
})();
(function() {

  var _toString = Object.prototype.toString;

  function extend(destination, source) {
    for (var property in source)
      destination[property] = source[property];
    return destination;
  }

  function inspect(object) {
    try {
      if (isUndefined(object)) return 'undefined';
      if (object === null) return 'null';
      return object.inspect ? object.inspect() : String(object);
    } catch (e) {
      if (e instanceof RangeError) return '...';
      throw e;
    }
  }

  function toJSON(object) {
    var type = typeof object;
    switch (type) {
      case 'undefined':
      case 'function':
      case 'unknown': return;
      case 'boolean': return object.toString();
    }

    if (object === null) return 'null';
    if (object.toJSON) return object.toJSON();
    if (isElement(object)) return;

    var results = [];
    for (var property in object) {
      var value = toJSON(object[property]);
      if (!isUndefined(value))
        results.push(property.toJSON() + ': ' + value);
    }

    return '{' + results.join(', ') + '}';
  }

  function toQueryString(object) {
    return $H(object).toQueryString();
  }

  function toHTML(object) {
    return object && object.toHTML ? object.toHTML() : String.interpret(object);
  }

  function keys(object) {
    var results = [];
    for (var property in object)
      results.push(property);
    return results;
  }

  function values(object) {
    var results = [];
    for (var property in object)
      results.push(object[property]);
    return results;
  }

  function clone(object) {
    return extend({ }, object);
  }

  function isElement(object) {
    return !!(object && object.nodeType == 1);
  }

  function isArray(object) {
    return _toString.call(object) == "[object Array]";
  }


  function isHash(object) {
    return object instanceof Hash;
  }

  function isFunction(object) {
    return typeof object === "function";
  }

  function isString(object) {
    return _toString.call(object) == "[object String]";
  }

  function isNumber(object) {
    return _toString.call(object) == "[object Number]";
  }

  function isUndefined(object) {
    return typeof object === "undefined";
  }

  extend(Object, {
    extend:        extend,
    inspect:       inspect,
    toJSON:        toJSON,
    toQueryString: toQueryString,
    toHTML:        toHTML,
    keys:          keys,
    values:        values,
    clone:         clone,
    isElement:     isElement,
    isArray:       isArray,
    isHash:        isHash,
    isFunction:    isFunction,
    isString:      isString,
    isNumber:      isNumber,
    isUndefined:   isUndefined
  });
})();
Object.extend(Function.prototype, (function() {
  var slice = Array.prototype.slice;

  function update(array, args) {
    var arrayLength = array.length, length = args.length;
    while (length--) array[arrayLength + length] = args[length];
    return array;
  }

  function merge(array, args) {
    array = slice.call(array, 0);
    return update(array, args);
  }

  function argumentNames() {
    var names = this.toString().match(/^[\s\(]*function[^(]*\(([^)]*)\)/)[1]
      .replace(/\/\/.*?[\r\n]|\/\*(?:.|[\r\n])*?\*\//g, '')
      .replace(/\s+/g, '').split(',');
    return names.length == 1 && !names[0] ? [] : names;
  }

  function bind(context) {
    if (arguments.length < 2 && Object.isUndefined(arguments[0])) return this;
    var __method = this, args = slice.call(arguments, 1);
    return function() {
      var a = merge(args, arguments);
      return __method.apply(context, a);
    }
  }

  function bindAsEventListener(context) {
    var __method = this, args = slice.call(arguments, 1);
    return function(event) {
      var a = update([event || window.event], args);
      return __method.apply(context, a);
    }
  }

  function curry() {
    if (!arguments.length) return this;
    var __method = this, args = slice.call(arguments, 0);
    return function() {
      var a = merge(args, arguments);
      return __method.apply(this, a);
    }
  }

  function delay(timeout) {
    var __method = this, args = slice.call(arguments, 1);
    timeout = timeout * 1000
    return window.setTimeout(function() {
      return __method.apply(__method, args);
    }, timeout);
  }

  function defer() {
    var args = update([0.01], arguments);
    return this.delay.apply(this, args);
  }

  function wrap(wrapper) {
    var __method = this;
    return function() {
      var a = update([__method.bind(this)], arguments);
      return wrapper.apply(this, a);
    }
  }

  function methodize() {
    if (this._methodized) return this._methodized;
    var __method = this;
    return this._methodized = function() {
      var a = update([this], arguments);
      return __method.apply(null, a);
    };
  }

  return {
    argumentNames:       argumentNames,
    bind:                bind,
    bindAsEventListener: bindAsEventListener,
    curry:               curry,
    delay:               delay,
    defer:               defer,
    wrap:                wrap,
    methodize:           methodize
  }
})());


Date.prototype.toJSON = function() {
  return '"' + this.getUTCFullYear() + '-' +
    (this.getUTCMonth() + 1).toPaddedString(2) + '-' +
    this.getUTCDate().toPaddedString(2) + 'T' +
    this.getUTCHours().toPaddedString(2) + ':' +
    this.getUTCMinutes().toPaddedString(2) + ':' +
    this.getUTCSeconds().toPaddedString(2) + 'Z"';
};


RegExp.prototype.match = RegExp.prototype.test;

RegExp.escape = function(str) {
  return String(str).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
};
var PeriodicalExecuter = Class.create({
  initialize: function(callback, frequency) {
    this.callback = callback;
    this.frequency = frequency;
    this.currentlyExecuting = false;

    this.registerCallback();
  },

  registerCallback: function() {
    this.timer = setInterval(this.onTimerEvent.bind(this), this.frequency * 1000);
  },

  execute: function() {
    this.callback(this);
  },

  stop: function() {
    if (!this.timer) return;
    clearInterval(this.timer);
    this.timer = null;
  },

  onTimerEvent: function() {
    if (!this.currentlyExecuting) {
      try {
        this.currentlyExecuting = true;
        this.execute();
        this.currentlyExecuting = false;
      } catch(e) {
        this.currentlyExecuting = false;
        throw e;
      }
    }
  }
});
Object.extend(String, {
  interpret: function(value) {
    return value == null ? '' : String(value);
  },
  specialChar: {
    '\b': '\\b',
    '\t': '\\t',
    '\n': '\\n',
    '\f': '\\f',
    '\r': '\\r',
    '\\': '\\\\'
  }
});

Object.extend(String.prototype, (function() {

  function prepareReplacement(replacement) {
    if (Object.isFunction(replacement)) return replacement;
    var template = new Template(replacement);
    return function(match) { return template.evaluate(match) };
  }

  function gsub(pattern, replacement) {
    var result = '', source = this, match;
    replacement = prepareReplacement(replacement);

    if (Object.isString(pattern))
      pattern = RegExp.escape(pattern);

    if (!(pattern.length || pattern.source)) {
      replacement = replacement('');
      return replacement + source.split('').join(replacement) + replacement;
    }

    while (source.length > 0) {
      if (match = source.match(pattern)) {
        result += source.slice(0, match.index);
        result += String.interpret(replacement(match));
        source  = source.slice(match.index + match[0].length);
      } else {
        result += source, source = '';
      }
    }
    return result;
  }

  function sub(pattern, replacement, count) {
    replacement = prepareReplacement(replacement);
    count = Object.isUndefined(count) ? 1 : count;

    return this.gsub(pattern, function(match) {
      if (--count < 0) return match[0];
      return replacement(match);
    });
  }

  function scan(pattern, iterator) {
    this.gsub(pattern, iterator);
    return String(this);
  }

  function truncate(length, truncation) {
    length = length || 30;
    truncation = Object.isUndefined(truncation) ? '...' : truncation;
    return this.length > length ?
      this.slice(0, length - truncation.length) + truncation : String(this);
  }

  function strip() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
  }

  function stripTags() {
    return this.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi, '');
  }

  function stripScripts() {
    return this.replace(new RegExp(Prototype.ScriptFragment, 'img'), '');
  }

  function extractScripts() {
    var matchAll = new RegExp(Prototype.ScriptFragment, 'img');
    var matchOne = new RegExp(Prototype.ScriptFragment, 'im');
    return (this.match(matchAll) || []).map(function(scriptTag) {
      return (scriptTag.match(matchOne) || ['', ''])[1];
    });
  }

  function evalScripts() {
    return this.extractScripts().map(function(script) { return eval(script) });
  }

  function escapeHTML() {
    return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function unescapeHTML() {
    return this.stripTags().replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
  }


  function toQueryParams(separator) {
    var match = this.strip().match(/([^?#]*)(#.*)?$/);
    if (!match) return { };

    return match[1].split(separator || '&').inject({ }, function(hash, pair) {
      if ((pair = pair.split('='))[0]) {
        var key = decodeURIComponent(pair.shift());
        var value = pair.length > 1 ? pair.join('=') : pair[0];
        if (value != undefined) value = decodeURIComponent(value);

        if (key in hash) {
          if (!Object.isArray(hash[key])) hash[key] = [hash[key]];
          hash[key].push(value);
        }
        else hash[key] = value;
      }
      return hash;
    });
  }

  function toArray() {
    return this.split('');
  }

  function succ() {
    return this.slice(0, this.length - 1) +
      String.fromCharCode(this.charCodeAt(this.length - 1) + 1);
  }

  function times(count) {
    return count < 1 ? '' : new Array(count + 1).join(this);
  }

  function camelize() {
    var parts = this.split('-'), len = parts.length;
    if (len == 1) return parts[0];

    var camelized = this.charAt(0) == '-'
      ? parts[0].charAt(0).toUpperCase() + parts[0].substring(1)
      : parts[0];

    for (var i = 1; i < len; i++)
      camelized += parts[i].charAt(0).toUpperCase() + parts[i].substring(1);

    return camelized;
  }

  function capitalize() {
    return this.charAt(0).toUpperCase() + this.substring(1).toLowerCase();
  }

  function underscore() {
    return this.replace(/::/g, '/')
               .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
               .replace(/([a-z\d])([A-Z])/g, '$1_$2')
               .replace(/-/g, '_')
               .toLowerCase();
  }

  function dasherize() {
    return this.replace(/_/g, '-');
  }

  function inspect(useDoubleQuotes) {
    var escapedString = this.replace(/[\x00-\x1f\\]/g, function(character) {
      if (character in String.specialChar) {
        return String.specialChar[character];
      }
      return '\\u00' + character.charCodeAt().toPaddedString(2, 16);
    });
    if (useDoubleQuotes) return '"' + escapedString.replace(/"/g, '\\"') + '"';
    return "'" + escapedString.replace(/'/g, '\\\'') + "'";
  }

  function toJSON() {
    return this.inspect(true);
  }

  function unfilterJSON(filter) {
    return this.replace(filter || Prototype.JSONFilter, '$1');
  }

  function isJSON() {
    var str = this;
    if (str.blank()) return false;
    str = this.replace(/\\./g, '@').replace(/"[^"\\\n\r]*"/g, '');
    return (/^[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]*$/).test(str);
  }

  function evalJSON(sanitize) {
    var json = this.unfilterJSON();
    try {
      if (!sanitize || json.isJSON()) return eval('(' + json + ')');
    } catch (e) { }
    throw new SyntaxError('Badly formed JSON string: ' + this.inspect());
  }

  function include(pattern) {
    return this.indexOf(pattern) > -1;
  }

  function startsWith(pattern) {
    return this.indexOf(pattern) === 0;
  }

  function endsWith(pattern) {
    var d = this.length - pattern.length;
    return d >= 0 && this.lastIndexOf(pattern) === d;
  }

  function empty() {
    return this == '';
  }

  function blank() {
    return /^\s*$/.test(this);
  }

  function interpolate(object, pattern) {
    return new Template(this, pattern).evaluate(object);
  }

  return {
    gsub:           gsub,
    sub:            sub,
    scan:           scan,
    truncate:       truncate,
    strip:          String.prototype.trim ? String.prototype.trim : strip,
    stripTags:      stripTags,
    stripScripts:   stripScripts,
    extractScripts: extractScripts,
    evalScripts:    evalScripts,
    escapeHTML:     escapeHTML,
    unescapeHTML:   unescapeHTML,
    toQueryParams:  toQueryParams,
    parseQuery:     toQueryParams,
    toArray:        toArray,
    succ:           succ,
    times:          times,
    camelize:       camelize,
    capitalize:     capitalize,
    underscore:     underscore,
    dasherize:      dasherize,
    inspect:        inspect,
    toJSON:         toJSON,
    unfilterJSON:   unfilterJSON,
    isJSON:         isJSON,
    evalJSON:       evalJSON,
    include:        include,
    startsWith:     startsWith,
    endsWith:       endsWith,
    empty:          empty,
    blank:          blank,
    interpolate:    interpolate
  };
})());

var Template = Class.create({
  initialize: function(template, pattern) {
    this.template = template.toString();
    this.pattern = pattern || Template.Pattern;
  },

  evaluate: function(object) {
    if (object && Object.isFunction(object.toTemplateReplacements))
      object = object.toTemplateReplacements();

    return this.template.gsub(this.pattern, function(match) {
      if (object == null) return (match[1] + '');

      var before = match[1] || '';
      if (before == '\\') return match[2];

      var ctx = object, expr = match[3];
      var pattern = /^([^.[]+|\[((?:.*?[^\\])?)\])(\.|\[|$)/;
      match = pattern.exec(expr);
      if (match == null) return before;

      while (match != null) {
        var comp = match[1].startsWith('[') ? match[2].replace(/\\\\]/g, ']') : match[1];
        ctx = ctx[comp];
        if (null == ctx || '' == match[3]) break;
        expr = expr.substring('[' == match[3] ? match[1].length : match[0].length);
        match = pattern.exec(expr);
      }

      return before + String.interpret(ctx);
    });
  }
});
Template.Pattern = /(^|.|\r|\n)(#\{(.*?)\})/;

var $break = { };

var Enumerable = (function() {
  function each(iterator, context) {
    var index = 0;
    try {
      this._each(function(value) {
        iterator.call(context, value, index++);
      });
    } catch (e) {
      if (e != $break) throw e;
    }
    return this;
  }

  function eachSlice(number, iterator, context) {
    var index = -number, slices = [], array = this.toArray();
    if (number < 1) return array;
    while ((index += number) < array.length)
      slices.push(array.slice(index, index+number));
    return slices.collect(iterator, context);
  }

  function all(iterator, context) {
    iterator = iterator || Prototype.K;
    var result = true;
    this.each(function(value, index) {
      result = result && !!iterator.call(context, value, index);
      if (!result) throw $break;
    });
    return result;
  }

  function any(iterator, context) {
    iterator = iterator || Prototype.K;
    var result = false;
    this.each(function(value, index) {
      if (result = !!iterator.call(context, value, index))
        throw $break;
    });
    return result;
  }

  function collect(iterator, context) {
    iterator = iterator || Prototype.K;
    var results = [];
    this.each(function(value, index) {
      results.push(iterator.call(context, value, index));
    });
    return results;
  }

  function detect(iterator, context) {
    var result;
    this.each(function(value, index) {
      if (iterator.call(context, value, index)) {
        result = value;
        throw $break;
      }
    });
    return result;
  }

  function findAll(iterator, context) {
    var results = [];
    this.each(function(value, index) {
      if (iterator.call(context, value, index))
        results.push(value);
    });
    return results;
  }

  function grep(filter, iterator, context) {
    iterator = iterator || Prototype.K;
    var results = [];

    if (Object.isString(filter))
      filter = new RegExp(RegExp.escape(filter));

    this.each(function(value, index) {
      if (filter.match(value))
        results.push(iterator.call(context, value, index));
    });
    return results;
  }

  function include(object) {
    if (Object.isFunction(this.indexOf))
      if (this.indexOf(object) != -1) return true;

    var found = false;
    this.each(function(value) {
      if (value == object) {
        found = true;
        throw $break;
      }
    });
    return found;
  }

  function inGroupsOf(number, fillWith) {
    fillWith = Object.isUndefined(fillWith) ? null : fillWith;
    return this.eachSlice(number, function(slice) {
      while(slice.length < number) slice.push(fillWith);
      return slice;
    });
  }

  function inject(memo, iterator, context) {
    this.each(function(value, index) {
      memo = iterator.call(context, memo, value, index);
    });
    return memo;
  }

  function invoke(method) {
    var args = $A(arguments).slice(1);
    return this.map(function(value) {
      return value[method].apply(value, args);
    });
  }

  function max(iterator, context) {
    iterator = iterator || Prototype.K;
    var result;
    this.each(function(value, index) {
      value = iterator.call(context, value, index);
      if (result == null || value >= result)
        result = value;
    });
    return result;
  }

  function min(iterator, context) {
    iterator = iterator || Prototype.K;
    var result;
    this.each(function(value, index) {
      value = iterator.call(context, value, index);
      if (result == null || value < result)
        result = value;
    });
    return result;
  }

  function partition(iterator, context) {
    iterator = iterator || Prototype.K;
    var trues = [], falses = [];
    this.each(function(value, index) {
      (iterator.call(context, value, index) ?
        trues : falses).push(value);
    });
    return [trues, falses];
  }

  function pluck(property) {
    var results = [];
    this.each(function(value) {
      results.push(value[property]);
    });
    return results;
  }

  function reject(iterator, context) {
    var results = [];
    this.each(function(value, index) {
      if (!iterator.call(context, value, index))
        results.push(value);
    });
    return results;
  }

  function sortBy(iterator, context) {
    return this.map(function(value, index) {
      return {
        value: value,
        criteria: iterator.call(context, value, index)
      };
    }).sort(function(left, right) {
      var a = left.criteria, b = right.criteria;
      return a < b ? -1 : a > b ? 1 : 0;
    }).pluck('value');
  }

  function toArray() {
    return this.map();
  }

  function zip() {
    var iterator = Prototype.K, args = $A(arguments);
    if (Object.isFunction(args.last()))
      iterator = args.pop();

    var collections = [this].concat(args).map($A);
    return this.map(function(value, index) {
      return iterator(collections.pluck(index));
    });
  }

  function size() {
    return this.toArray().length;
  }

  function inspect() {
    return '#<Enumerable:' + this.toArray().inspect() + '>';
  }









  return {
    each:       each,
    eachSlice:  eachSlice,
    all:        all,
    every:      all,
    any:        any,
    some:       any,
    collect:    collect,
    map:        collect,
    detect:     detect,
    findAll:    findAll,
    select:     findAll,
    filter:     findAll,
    grep:       grep,
    include:    include,
    member:     include,
    inGroupsOf: inGroupsOf,
    inject:     inject,
    invoke:     invoke,
    max:        max,
    min:        min,
    partition:  partition,
    pluck:      pluck,
    reject:     reject,
    sortBy:     sortBy,
    toArray:    toArray,
    entries:    toArray,
    zip:        zip,
    size:       size,
    inspect:    inspect,
    find:       detect
  };
})();
function $A(iterable) {
  if (!iterable) return [];
  if ('toArray' in Object(iterable)) return iterable.toArray();
  var length = iterable.length || 0, results = new Array(length);
  while (length--) results[length] = iterable[length];
  return results;
}

function $w(string) {
  if (!Object.isString(string)) return [];
  string = string.strip();
  return string ? string.split(/\s+/) : [];
}

Array.from = $A;


(function() {
  var arrayProto = Array.prototype,
      slice = arrayProto.slice,
      _each = arrayProto.forEach; // use native browser JS 1.6 implementation if available

  function each(iterator) {
    for (var i = 0, length = this.length; i < length; i++)
      iterator(this[i]);
  }
  if (!_each) _each = each;

  function clear() {
    this.length = 0;
    return this;
  }

  function first() {
    return this[0];
  }

  function last() {
    return this[this.length - 1];
  }

  function compact() {
    return this.select(function(value) {
      return value != null;
    });
  }

  function flatten() {
    return this.inject([], function(array, value) {
      if (Object.isArray(value))
        return array.concat(value.flatten());
      array.push(value);
      return array;
    });
  }

  function without() {
    var values = slice.call(arguments, 0);
    return this.select(function(value) {
      return !values.include(value);
    });
  }

  function reverse(inline) {
    return (inline !== false ? this : this.toArray())._reverse();
  }

  function uniq(sorted) {
    return this.inject([], function(array, value, index) {
      if (0 == index || (sorted ? array.last() != value : !array.include(value)))
        array.push(value);
      return array;
    });
  }

  function intersect(array) {
    return this.uniq().findAll(function(item) {
      return array.detect(function(value) { return item === value });
    });
  }


  function clone() {
    return slice.call(this, 0);
  }

  function size() {
    return this.length;
  }

  function inspect() {
    return '[' + this.map(Object.inspect).join(', ') + ']';
  }

  function toJSON() {
    var results = [];
    this.each(function(object) {
      var value = Object.toJSON(object);
      if (!Object.isUndefined(value)) results.push(value);
    });
    return '[' + results.join(', ') + ']';
  }

  function indexOf(item, i) {
    i || (i = 0);
    var length = this.length;
    if (i < 0) i = length + i;
    for (; i < length; i++)
      if (this[i] === item) return i;
    return -1;
  }

  function lastIndexOf(item, i) {
    i = isNaN(i) ? this.length : (i < 0 ? this.length + i : i) + 1;
    var n = this.slice(0, i).reverse().indexOf(item);
    return (n < 0) ? n : i - n - 1;
  }

  function concat() {
    var array = slice.call(this, 0), item;
    for (var i = 0, length = arguments.length; i < length; i++) {
      item = arguments[i];
      if (Object.isArray(item) && !('callee' in item)) {
        for (var j = 0, arrayLength = item.length; j < arrayLength; j++)
          array.push(item[j]);
      } else {
        array.push(item);
      }
    }
    return array;
  }

  Object.extend(arrayProto, Enumerable);

  if (!arrayProto._reverse)
    arrayProto._reverse = arrayProto.reverse;

  Object.extend(arrayProto, {
    _each:     _each,
    clear:     clear,
    first:     first,
    last:      last,
    compact:   compact,
    flatten:   flatten,
    without:   without,
    reverse:   reverse,
    uniq:      uniq,
    intersect: intersect,
    clone:     clone,
    toArray:   clone,
    size:      size,
    inspect:   inspect,
    toJSON:    toJSON
  });

  var CONCAT_ARGUMENTS_BUGGY = (function() {
    return [].concat(arguments)[0][0] !== 1;
  })(1,2)

  if (CONCAT_ARGUMENTS_BUGGY) arrayProto.concat = concat;

  if (!arrayProto.indexOf) arrayProto.indexOf = indexOf;
  if (!arrayProto.lastIndexOf) arrayProto.lastIndexOf = lastIndexOf;
})();
function $H(object) {
  return new Hash(object);
};

var Hash = Class.create(Enumerable, (function() {
  function initialize(object) {
    this._object = Object.isHash(object) ? object.toObject() : Object.clone(object);
  }

  function _each(iterator) {
    for (var key in this._object) {
      var value = this._object[key], pair = [key, value];
      pair.key = key;
      pair.value = value;
      iterator(pair);
    }
  }

  function set(key, value) {
    return this._object[key] = value;
  }

  function get(key) {
    if (this._object[key] !== Object.prototype[key])
      return this._object[key];
  }

  function unset(key) {
    var value = this._object[key];
    delete this._object[key];
    return value;
  }

  function toObject() {
    return Object.clone(this._object);
  }

  function keys() {
    return this.pluck('key');
  }

  function values() {
    return this.pluck('value');
  }

  function index(value) {
    var match = this.detect(function(pair) {
      return pair.value === value;
    });
    return match && match.key;
  }

  function merge(object) {
    return this.clone().update(object);
  }

  function update(object) {
    return new Hash(object).inject(this, function(result, pair) {
      result.set(pair.key, pair.value);
      return result;
    });
  }

  function toQueryPair(key, value) {
    if (Object.isUndefined(value)) return key;
    return key + '=' + encodeURIComponent(String.interpret(value));
  }

  function toQueryString() {
    return this.inject([], function(results, pair) {
      var key = encodeURIComponent(pair.key), values = pair.value;

      if (values && typeof values == 'object') {
        if (Object.isArray(values))
          return results.concat(values.map(toQueryPair.curry(key)));
      } else results.push(toQueryPair(key, values));
      return results;
    }).join('&');
  }

  function inspect() {
    return '#<Hash:{' + this.map(function(pair) {
      return pair.map(Object.inspect).join(': ');
    }).join(', ') + '}>';
  }

  function toJSON() {
    return Object.toJSON(this.toObject());
  }

  function clone() {
    return new Hash(this);
  }

  return {
    initialize:             initialize,
    _each:                  _each,
    set:                    set,
    get:                    get,
    unset:                  unset,
    toObject:               toObject,
    toTemplateReplacements: toObject,
    keys:                   keys,
    values:                 values,
    index:                  index,
    merge:                  merge,
    update:                 update,
    toQueryString:          toQueryString,
    inspect:                inspect,
    toJSON:                 toJSON,
    clone:                  clone
  };
})());

Hash.from = $H;
Object.extend(Number.prototype, (function() {
  function toColorPart() {
    return this.toPaddedString(2, 16);
  }

  function succ() {
    return this + 1;
  }

  function times(iterator, context) {
    $R(0, this, true).each(iterator, context);
    return this;
  }

  function toPaddedString(length, radix) {
    var string = this.toString(radix || 10);
    return '0'.times(length - string.length) + string;
  }

  function toJSON() {
    return isFinite(this) ? this.toString() : 'null';
  }

  function abs() {
    return Math.abs(this);
  }

  function round() {
    return Math.round(this);
  }

  function ceil() {
    return Math.ceil(this);
  }

  function floor() {
    return Math.floor(this);
  }

  return {
    toColorPart:    toColorPart,
    succ:           succ,
    times:          times,
    toPaddedString: toPaddedString,
    toJSON:         toJSON,
    abs:            abs,
    round:          round,
    ceil:           ceil,
    floor:          floor
  };
})());

function $R(start, end, exclusive) {
  return new ObjectRange(start, end, exclusive);
}

var ObjectRange = Class.create(Enumerable, (function() {
  function initialize(start, end, exclusive) {
    this.start = start;
    this.end = end;
    this.exclusive = exclusive;
  }

  function _each(iterator) {
    var value = this.start;
    while (this.include(value)) {
      iterator(value);
      value = value.succ();
    }
  }

  function include(value) {
    if (value < this.start)
      return false;
    if (this.exclusive)
      return value < this.end;
    return value <= this.end;
  }

  return {
    initialize: initialize,
    _each:      _each,
    include:    include
  };
})());



var Ajax = {
  getTransport: function() {
    return Try.these(
      function() {return new XMLHttpRequest()},
      function() {return new ActiveXObject('Msxml2.XMLHTTP')},
      function() {return new ActiveXObject('Microsoft.XMLHTTP')}
    ) || false;
  },

  activeRequestCount: 0
};

Ajax.Responders = {
  responders: [],

  _each: function(iterator) {
    this.responders._each(iterator);
  },

  register: function(responder) {
    if (!this.include(responder))
      this.responders.push(responder);
  },

  unregister: function(responder) {
    this.responders = this.responders.without(responder);
  },

  dispatch: function(callback, request, transport, json) {
    this.each(function(responder) {
      if (Object.isFunction(responder[callback])) {
        try {
          responder[callback].apply(responder, [request, transport, json]);
        } catch (e) { }
      }
    });
  }
};

Object.extend(Ajax.Responders, Enumerable);

Ajax.Responders.register({
  onCreate:   function() { Ajax.activeRequestCount++ },
  onComplete: function() { Ajax.activeRequestCount-- }
});
Ajax.Base = Class.create({
  initialize: function(options) {
    this.options = {
      method:       'post',
      asynchronous: true,
      contentType:  'application/x-www-form-urlencoded',
      encoding:     'UTF-8',
      parameters:   '',
      evalJSON:     true,
      evalJS:       true
    };
    Object.extend(this.options, options || { });

    this.options.method = this.options.method.toLowerCase();

    if (Object.isString(this.options.parameters))
      this.options.parameters = this.options.parameters.toQueryParams();
    else if (Object.isHash(this.options.parameters))
      this.options.parameters = this.options.parameters.toObject();
  }
});
Ajax.Request = Class.create(Ajax.Base, {
  _complete: false,

  initialize: function($super, url, options) {
    $super(options);
    this.transport = Ajax.getTransport();
    this.request(url);
  },

  request: function(url) {
    this.url = url;
    this.method = this.options.method;
    var params = Object.clone(this.options.parameters);

    if (!['get', 'post'].include(this.method)) {
      params['_method'] = this.method;
      this.method = 'post';
    }

    this.parameters = params;

    if (params = Object.toQueryString(params)) {
      if (this.method == 'get')
        this.url += (this.url.include('?') ? '&' : '?') + params;
      else if (/Konqueror|Safari|KHTML/.test(navigator.userAgent))
        params += '&_=';
    }

    try {
      var response = new Ajax.Response(this);
      if (this.options.onCreate) this.options.onCreate(response);
      Ajax.Responders.dispatch('onCreate', this, response);

      this.transport.open(this.method.toUpperCase(), this.url,
        this.options.asynchronous);

      if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

      this.transport.onreadystatechange = this.onStateChange.bind(this);
      this.setRequestHeaders();

      this.body = this.method == 'post' ? (this.options.postBody || params) : null;
      this.transport.send(this.body);

      /* Force Firefox to handle ready state 4 for synchronous requests */
      if (!this.options.asynchronous && this.transport.overrideMimeType)
        this.onStateChange();

    }
    catch (e) {
      this.dispatchException(e);
    }
  },

  onStateChange: function() {
    var readyState = this.transport.readyState;
    if (readyState > 1 && !((readyState == 4) && this._complete))
      this.respondToReadyState(this.transport.readyState);
  },

  setRequestHeaders: function() {
    var headers = {
      'X-Requested-With': 'XMLHttpRequest',
      'X-Prototype-Version': Prototype.Version,
      'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'
    };

    if (this.method == 'post') {
      headers['Content-type'] = this.options.contentType +
        (this.options.encoding ? '; charset=' + this.options.encoding : '');

      /* Force "Connection: close" for older Mozilla browsers to work
       * around a bug where XMLHttpRequest sends an incorrect
       * Content-length header. See Mozilla Bugzilla #246651.
       */
      if (this.transport.overrideMimeType &&
          (navigator.userAgent.match(/Gecko\/(\d{4})/) || [0,2005])[1] < 2005)
            headers['Connection'] = 'close';
    }

    if (typeof this.options.requestHeaders == 'object') {
      var extras = this.options.requestHeaders;

      if (Object.isFunction(extras.push))
        for (var i = 0, length = extras.length; i < length; i += 2)
          headers[extras[i]] = extras[i+1];
      else
        $H(extras).each(function(pair) { headers[pair.key] = pair.value });
    }

    for (var name in headers)
      this.transport.setRequestHeader(name, headers[name]);
  },

  success: function() {
    var status = this.getStatus();
    return !status || (status >= 200 && status < 300);
  },

  getStatus: function() {
    try {
      return this.transport.status || 0;
    } catch (e) { return 0 }
  },

  respondToReadyState: function(readyState) {
    var state = Ajax.Request.Events[readyState], response = new Ajax.Response(this);

    if (state == 'Complete') {
      try {
        this._complete = true;
        (this.options['on' + response.status]
         || this.options['on' + (this.success() ? 'Success' : 'Failure')]
         || Prototype.emptyFunction)(response, response.headerJSON);
      } catch (e) {
        this.dispatchException(e);
      }

      var contentType = response.getHeader('Content-type');
      if (this.options.evalJS == 'force'
          || (this.options.evalJS && this.isSameOrigin() && contentType
          && contentType.match(/^\s*(text|application)\/(x-)?(java|ecma)script(;.*)?\s*$/i)))
        this.evalResponse();
    }

    try {
      (this.options['on' + state] || Prototype.emptyFunction)(response, response.headerJSON);
      Ajax.Responders.dispatch('on' + state, this, response, response.headerJSON);
    } catch (e) {
      this.dispatchException(e);
    }

    if (state == 'Complete') {
      this.transport.onreadystatechange = Prototype.emptyFunction;
    }
  },

  isSameOrigin: function() {
    var m = this.url.match(/^\s*https?:\/\/[^\/]*/);
    return !m || (m[0] == '#{protocol}//#{domain}#{port}'.interpolate({
      protocol: location.protocol,
      domain: document.domain,
      port: location.port ? ':' + location.port : ''
    }));
  },

  getHeader: function(name) {
    try {
      return this.transport.getResponseHeader(name) || null;
    } catch (e) { return null; }
  },

  evalResponse: function() {
    try {
      return eval((this.transport.responseText || '').unfilterJSON());
    } catch (e) {
      this.dispatchException(e);
    }
  },

  dispatchException: function(exception) {
    (this.options.onException || Prototype.emptyFunction)(this, exception);
    Ajax.Responders.dispatch('onException', this, exception);
  }
});

Ajax.Request.Events =
  ['Uninitialized', 'Loading', 'Loaded', 'Interactive', 'Complete'];








Ajax.Response = Class.create({
  initialize: function(request){
    this.request = request;
    var transport  = this.transport  = request.transport,
        readyState = this.readyState = transport.readyState;

    if((readyState > 2 && !Prototype.Browser.IE) || readyState == 4) {
      this.status       = this.getStatus();
      this.statusText   = this.getStatusText();
      this.responseText = String.interpret(transport.responseText);
      this.headerJSON   = this._getHeaderJSON();
    }

    if(readyState == 4) {
      var xml = transport.responseXML;
      this.responseXML  = Object.isUndefined(xml) ? null : xml;
      this.responseJSON = this._getResponseJSON();
    }
  },

  status:      0,

  statusText: '',

  getStatus: Ajax.Request.prototype.getStatus,

  getStatusText: function() {
    try {
      return this.transport.statusText || '';
    } catch (e) { return '' }
  },

  getHeader: Ajax.Request.prototype.getHeader,

  getAllHeaders: function() {
    try {
      return this.getAllResponseHeaders();
    } catch (e) { return null }
  },

  getResponseHeader: function(name) {
    return this.transport.getResponseHeader(name);
  },

  getAllResponseHeaders: function() {
    return this.transport.getAllResponseHeaders();
  },

  _getHeaderJSON: function() {
    var json = this.getHeader('X-JSON');
    if (!json) return null;
    json = decodeURIComponent(escape(json));
    try {
      return json.evalJSON(this.request.options.sanitizeJSON ||
        !this.request.isSameOrigin());
    } catch (e) {
      this.request.dispatchException(e);
    }
  },

  _getResponseJSON: function() {
    var options = this.request.options;
    if (!options.evalJSON || (options.evalJSON != 'force' &&
      !(this.getHeader('Content-type') || '').include('application/json')) ||
        this.responseText.blank())
          return null;
    try {
      return this.responseText.evalJSON(options.sanitizeJSON ||
        !this.request.isSameOrigin());
    } catch (e) {
      this.request.dispatchException(e);
    }
  }
});

Ajax.Updater = Class.create(Ajax.Request, {
  initialize: function($super, container, url, options) {
    this.container = {
      success: (container.success || container),
      failure: (container.failure || (container.success ? null : container))
    };

    options = Object.clone(options);
    var onComplete = options.onComplete;
    options.onComplete = (function(response, json) {
      this.updateContent(response.responseText);
      if (Object.isFunction(onComplete)) onComplete(response, json);
    }).bind(this);

    $super(url, options);
  },

  updateContent: function(responseText) {
    var receiver = this.container[this.success() ? 'success' : 'failure'],
        options = this.options;

    if (!options.evalScripts) responseText = responseText.stripScripts();

    if (receiver = $(receiver)) {
      if (options.insertion) {
        if (Object.isString(options.insertion)) {
          var insertion = { }; insertion[options.insertion] = responseText;
          receiver.insert(insertion);
        }
        else options.insertion(receiver, responseText);
      }
      else receiver.update(responseText);
    }
  }
});

Ajax.PeriodicalUpdater = Class.create(Ajax.Base, {
  initialize: function($super, container, url, options) {
    $super(options);
    this.onComplete = this.options.onComplete;

    this.frequency = (this.options.frequency || 2);
    this.decay = (this.options.decay || 1);

    this.updater = { };
    this.container = container;
    this.url = url;

    this.start();
  },

  start: function() {
    this.options.onComplete = this.updateComplete.bind(this);
    this.onTimerEvent();
  },

  stop: function() {
    this.updater.options.onComplete = undefined;
    clearTimeout(this.timer);
    (this.onComplete || Prototype.emptyFunction).apply(this, arguments);
  },

  updateComplete: function(response) {
    if (this.options.decay) {
      this.decay = (response.responseText == this.lastText ?
        this.decay * this.options.decay : 1);

      this.lastText = response.responseText;
    }
    this.timer = this.onTimerEvent.bind(this).delay(this.decay * this.frequency);
  },

  onTimerEvent: function() {
    this.updater = new Ajax.Updater(this.container, this.url, this.options);
  }
});



function $(element) {
  if (arguments.length > 1) {
    for (var i = 0, elements = [], length = arguments.length; i < length; i++)
      elements.push($(arguments[i]));
    return elements;
  }
  if (Object.isString(element))
    element = document.getElementById(element);
  return Element.extend(element);
}

if (Prototype.BrowserFeatures.XPath) {
  document._getElementsByXPath = function(expression, parentElement) {
    var results = [];
    var query = document.evaluate(expression, $(parentElement) || document,
      null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
    for (var i = 0, length = query.snapshotLength; i < length; i++)
      results.push(Element.extend(query.snapshotItem(i)));
    return results;
  };
}

/*--------------------------------------------------------------------------*/

if (!window.Node) var Node = { };

if (!Node.ELEMENT_NODE) {
  Object.extend(Node, {
    ELEMENT_NODE: 1,
    ATTRIBUTE_NODE: 2,
    TEXT_NODE: 3,
    CDATA_SECTION_NODE: 4,
    ENTITY_REFERENCE_NODE: 5,
    ENTITY_NODE: 6,
    PROCESSING_INSTRUCTION_NODE: 7,
    COMMENT_NODE: 8,
    DOCUMENT_NODE: 9,
    DOCUMENT_TYPE_NODE: 10,
    DOCUMENT_FRAGMENT_NODE: 11,
    NOTATION_NODE: 12
  });
}


(function(global) {

  var SETATTRIBUTE_IGNORES_NAME = (function(){
    var elForm = document.createElement("form");
    var elInput = document.createElement("input");
    var root = document.documentElement;
    elInput.setAttribute("name", "test");
    elForm.appendChild(elInput);
    root.appendChild(elForm);
    var isBuggy = elForm.elements
      ? (typeof elForm.elements.test == "undefined")
      : null;
    root.removeChild(elForm);
    elForm = elInput = null;
    return isBuggy;
  })();

  var element = global.Element;
  global.Element = function(tagName, attributes) {
    attributes = attributes || { };
    tagName = tagName.toLowerCase();
    var cache = Element.cache;
    if (SETATTRIBUTE_IGNORES_NAME && attributes.name) {
      tagName = '<' + tagName + ' name="' + attributes.name + '">';
      delete attributes.name;
      return Element.writeAttribute(document.createElement(tagName), attributes);
    }
    if (!cache[tagName]) cache[tagName] = Element.extend(document.createElement(tagName));
    return Element.writeAttribute(cache[tagName].cloneNode(false), attributes);
  };
  Object.extend(global.Element, element || { });
  if (element) global.Element.prototype = element.prototype;
})(this);

Element.cache = { };
Element.idCounter = 1;

Element.Methods = {
  visible: function(element) {
    return $(element).style.display != 'none';
  },

  toggle: function(element) {
    element = $(element);
    Element[Element.visible(element) ? 'hide' : 'show'](element);
    return element;
  },


  hide: function(element) {
    element = $(element);
    element.style.display = 'none';
    return element;
  },

  show: function(element) {
    element = $(element);
    element.style.display = '';
    return element;
  },

  remove: function(element) {
    element = $(element);
    element.parentNode.removeChild(element);
    return element;
  },

  update: (function(){

    var SELECT_ELEMENT_INNERHTML_BUGGY = (function(){
      var el = document.createElement("select"),
          isBuggy = true;
      el.innerHTML = "<option value=\"test\">test</option>";
      if (el.options && el.options[0]) {
        isBuggy = el.options[0].nodeName.toUpperCase() !== "OPTION";
      }
      el = null;
      return isBuggy;
    })();

    var TABLE_ELEMENT_INNERHTML_BUGGY = (function(){
      try {
        var el = document.createElement("table");
        if (el && el.tBodies) {
          el.innerHTML = "<tbody><tr><td>test</td></tr></tbody>";
          var isBuggy = typeof el.tBodies[0] == "undefined";
          el = null;
          return isBuggy;
        }
      } catch (e) {
        return true;
      }
    })();

    var SCRIPT_ELEMENT_REJECTS_TEXTNODE_APPENDING = (function () {
      var s = document.createElement("script"),
          isBuggy = false;
      try {
        s.appendChild(document.createTextNode(""));
        isBuggy = !s.firstChild ||
          s.firstChild && s.firstChild.nodeType !== 3;
      } catch (e) {
        isBuggy = true;
      }
      s = null;
      return isBuggy;
    })();

    function update(element, content) {
      element = $(element);

      if (content && content.toElement)
        content = content.toElement();

      if (Object.isElement(content))
        return element.update().insert(content);

      content = Object.toHTML(content);

      var tagName = element.tagName.toUpperCase();

      if (tagName === 'SCRIPT' && SCRIPT_ELEMENT_REJECTS_TEXTNODE_APPENDING) {
        element.text = content;
        return element;
      }

      if (SELECT_ELEMENT_INNERHTML_BUGGY || TABLE_ELEMENT_INNERHTML_BUGGY) {
        if (tagName in Element._insertionTranslations.tags) {
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          Element._getContentFromAnonymousElement(tagName, content.stripScripts())
            .each(function(node) {
              element.appendChild(node)
            });
        }
        else {
          element.innerHTML = content.stripScripts();
        }
      }
      else {
        element.innerHTML = content.stripScripts();
      }

      content.evalScripts.bind(content).defer();
      return element;
    }

    return update;
  })(),

  replace: function(element, content) {
    element = $(element);
    if (content && content.toElement) content = content.toElement();
    else if (!Object.isElement(content)) {
      content = Object.toHTML(content);
      var range = element.ownerDocument.createRange();
      range.selectNode(element);
      content.evalScripts.bind(content).defer();
      content = range.createContextualFragment(content.stripScripts());
    }
    element.parentNode.replaceChild(content, element);
    return element;
  },

  insert: function(element, insertions) {
    element = $(element);

    if (Object.isString(insertions) || Object.isNumber(insertions) ||
        Object.isElement(insertions) || (insertions && (insertions.toElement || insertions.toHTML)))
          insertions = {bottom:insertions};

    var content, insert, tagName, childNodes;

    for (var position in insertions) {
      content  = insertions[position];
      position = position.toLowerCase();
      insert = Element._insertionTranslations[position];

      if (content && content.toElement) content = content.toElement();
      if (Object.isElement(content)) {
        insert(element, content);
        continue;
      }

      content = Object.toHTML(content);

      tagName = ((position == 'before' || position == 'after')
        ? element.parentNode : element).tagName.toUpperCase();

      childNodes = Element._getContentFromAnonymousElement(tagName, content.stripScripts());

      if (position == 'top' || position == 'after') childNodes.reverse();
      childNodes.each(insert.curry(element));

      content.evalScripts.bind(content).defer();
    }

    return element;
  },

  wrap: function(element, wrapper, attributes) {
    element = $(element);
    if (Object.isElement(wrapper))
      $(wrapper).writeAttribute(attributes || { });
    else if (Object.isString(wrapper)) wrapper = new Element(wrapper, attributes);
    else wrapper = new Element('div', wrapper);
    if (element.parentNode)
      element.parentNode.replaceChild(wrapper, element);
    wrapper.appendChild(element);
    return wrapper;
  },

  inspect: function(element) {
    element = $(element);
    var result = '<' + element.tagName.toLowerCase();
    $H({'id': 'id', 'className': 'class'}).each(function(pair) {
      var property = pair.first(), attribute = pair.last();
      var value = (element[property] || '').toString();
      if (value) result += ' ' + attribute + '=' + value.inspect(true);
    });
    return result + '>';
  },

  recursivelyCollect: function(element, property) {
    element = $(element);
    var elements = [];
    while (element = element[property])
      if (element.nodeType == 1)
        elements.push(Element.extend(element));
    return elements;
  },

  ancestors: function(element) {
    return Element.recursivelyCollect(element, 'parentNode');
  },

  descendants: function(element) {
    return Element.select(element, "*");
  },

  firstDescendant: function(element) {
    element = $(element).firstChild;
    while (element && element.nodeType != 1) element = element.nextSibling;
    return $(element);
  },

  immediateDescendants: function(element) {
    if (!(element = $(element).firstChild)) return [];
    while (element && element.nodeType != 1) element = element.nextSibling;
    if (element) return [element].concat($(element).nextSiblings());
    return [];
  },

  previousSiblings: function(element) {
    return Element.recursivelyCollect(element, 'previousSibling');
  },

  nextSiblings: function(element) {
    return Element.recursivelyCollect(element, 'nextSibling');
  },

  siblings: function(element) {
    element = $(element);
    return Element.previousSiblings(element).reverse()
      .concat(Element.nextSiblings(element));
  },

  match: function(element, selector) {
    if (Object.isString(selector))
      selector = new Selector(selector);
    return selector.match($(element));
  },

  up: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return $(element.parentNode);
    var ancestors = Element.ancestors(element);
    return Object.isNumber(expression) ? ancestors[expression] :
      Selector.findElement(ancestors, expression, index);
  },

  down: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return Element.firstDescendant(element);
    return Object.isNumber(expression) ? Element.descendants(element)[expression] :
      Element.select(element, expression)[index || 0];
  },

  previous: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return $(Selector.handlers.previousElementSibling(element));
    var previousSiblings = Element.previousSiblings(element);
    return Object.isNumber(expression) ? previousSiblings[expression] :
      Selector.findElement(previousSiblings, expression, index);
  },

  next: function(element, expression, index) {
    element = $(element);
    if (arguments.length == 1) return $(Selector.handlers.nextElementSibling(element));
    var nextSiblings = Element.nextSiblings(element);
    return Object.isNumber(expression) ? nextSiblings[expression] :
      Selector.findElement(nextSiblings, expression, index);
  },


  select: function(element) {
    var args = Array.prototype.slice.call(arguments, 1);
    return Selector.findChildElements(element, args);
  },

  adjacent: function(element) {
    var args = Array.prototype.slice.call(arguments, 1);
    return Selector.findChildElements(element.parentNode, args).without(element);
  },

  identify: function(element) {
    element = $(element);
    var id = Element.readAttribute(element, 'id');
    if (id) return id;
    do { id = 'anonymous_element_' + Element.idCounter++ } while ($(id));
    Element.writeAttribute(element, 'id', id);
    return id;
  },

  readAttribute: function(element, name) {
    element = $(element);
    if (Prototype.Browser.IE) {
      var t = Element._attributeTranslations.read;
      if (t.values[name]) return t.values[name](element, name);
      if (t.names[name]) name = t.names[name];
      if (name.include(':')) {
        return (!element.attributes || !element.attributes[name]) ? null :
         element.attributes[name].value;
      }
    }
    return element.getAttribute(name);
  },

  writeAttribute: function(element, name, value) {
    element = $(element);
    var attributes = { }, t = Element._attributeTranslations.write;

    if (typeof name == 'object') attributes = name;
    else attributes[name] = Object.isUndefined(value) ? true : value;

    for (var attr in attributes) {
      name = t.names[attr] || attr;
      value = attributes[attr];
      if (t.values[attr]) name = t.values[attr](element, value);
      if (value === false || value === null)
        element.removeAttribute(name);
      else if (value === true)
        element.setAttribute(name, name);
      else element.setAttribute(name, value);
    }
    return element;
  },

  getHeight: function(element) {
    return Element.getDimensions(element).height;
  },

  getWidth: function(element) {
    return Element.getDimensions(element).width;
  },

  classNames: function(element) {
    return new Element.ClassNames(element);
  },

  hasClassName: function(element, className) {
    if (!(element = $(element))) return;
    var elementClassName = element.className;
    return (elementClassName.length > 0 && (elementClassName == className ||
      new RegExp("(^|\\s)" + className + "(\\s|$)").test(elementClassName)));
  },

  addClassName: function(element, className) {
    if (!(element = $(element))) return;
    if (!Element.hasClassName(element, className))
      element.className += (element.className ? ' ' : '') + className;
    return element;
  },

  removeClassName: function(element, className) {
    if (!(element = $(element))) return;
    element.className = element.className.replace(
      new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
    return element;
  },

  toggleClassName: function(element, className) {
    if (!(element = $(element))) return;
    return Element[Element.hasClassName(element, className) ?
      'removeClassName' : 'addClassName'](element, className);
  },

  cleanWhitespace: function(element) {
    element = $(element);
    var node = element.firstChild;
    while (node) {
      var nextNode = node.nextSibling;
      if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
        element.removeChild(node);
      node = nextNode;
    }
    return element;
  },

  empty: function(element) {
    return $(element).innerHTML.blank();
  },

  descendantOf: function(element, ancestor) {
    element = $(element), ancestor = $(ancestor);

    if (element.compareDocumentPosition)
      return (element.compareDocumentPosition(ancestor) & 8) === 8;

    if (ancestor.contains)
      return ancestor.contains(element) && ancestor !== element;

    while (element = element.parentNode)
      if (element == ancestor) return true;

    return false;
  },

  scrollTo: function(element) {
    element = $(element);
    var pos = Element.cumulativeOffset(element);
    window.scrollTo(pos[0], pos[1]);
    return element;
  },

  getStyle: function(element, style) {
    element = $(element);
    style = style == 'float' ? 'cssFloat' : style.camelize();
    var value = element.style[style];
    if (!value || value == 'auto') {
      var css = document.defaultView.getComputedStyle(element, null);
      value = css ? css[style] : null;
    }
    if (style == 'opacity') return value ? parseFloat(value) : 1.0;
    return value == 'auto' ? null : value;
  },

  getOpacity: function(element) {
    return $(element).getStyle('opacity');
  },

  setStyle: function(element, styles) {
    element = $(element);
    var elementStyle = element.style, match;
    if (Object.isString(styles)) {
      element.style.cssText += ';' + styles;
      return styles.include('opacity') ?
        element.setOpacity(styles.match(/opacity:\s*(\d?\.?\d*)/)[1]) : element;
    }
    for (var property in styles)
      if (property == 'opacity') element.setOpacity(styles[property]);
      else
        elementStyle[(property == 'float' || property == 'cssFloat') ?
          (Object.isUndefined(elementStyle.styleFloat) ? 'cssFloat' : 'styleFloat') :
            property] = styles[property];

    return element;
  },

  setOpacity: function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1 || value === '') ? '' :
      (value < 0.00001) ? 0 : value;
    return element;
  },

  getDimensions: function(element) {
    element = $(element);
    var display = Element.getStyle(element, 'display');
    if (display != 'none' && display != null) // Safari bug
      return {width: element.offsetWidth, height: element.offsetHeight};

    var els = element.style;
    var originalVisibility = els.visibility;
    var originalPosition = els.position;
    var originalDisplay = els.display;
    els.visibility = 'hidden';
    if (originalPosition != 'fixed') // Switching fixed to absolute causes issues in Safari
      els.position = 'absolute';
    els.display = 'block';
    var originalWidth = element.clientWidth;
    var originalHeight = element.clientHeight;
    els.display = originalDisplay;
    els.position = originalPosition;
    els.visibility = originalVisibility;
    return {width: originalWidth, height: originalHeight};
  },

  makePositioned: function(element) {
    element = $(element);
    var pos = Element.getStyle(element, 'position');
    if (pos == 'static' || !pos) {
      element._madePositioned = true;
      element.style.position = 'relative';
      if (Prototype.Browser.Opera) {
        element.style.top = 0;
        element.style.left = 0;
      }
    }
    return element;
  },

  undoPositioned: function(element) {
    element = $(element);
    if (element._madePositioned) {
      element._madePositioned = undefined;
      element.style.position =
        element.style.top =
        element.style.left =
        element.style.bottom =
        element.style.right = '';
    }
    return element;
  },

  makeClipping: function(element) {
    element = $(element);
    if (element._overflow) return element;
    element._overflow = Element.getStyle(element, 'overflow') || 'auto';
    if (element._overflow !== 'hidden')
      element.style.overflow = 'hidden';
    return element;
  },

  undoClipping: function(element) {
    element = $(element);
    if (!element._overflow) return element;
    element.style.overflow = element._overflow == 'auto' ? '' : element._overflow;
    element._overflow = null;
    return element;
  },

  cumulativeOffset: function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      element = element.offsetParent;
    } while (element);
    return Element._returnOffset(valueL, valueT);
  },

  positionedOffset: function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      element = element.offsetParent;
      if (element) {
        if (element.tagName.toUpperCase() == 'BODY') break;
        var p = Element.getStyle(element, 'position');
        if (p !== 'static') break;
      }
    } while (element);
    return Element._returnOffset(valueL, valueT);
  },

  absolutize: function(element) {
    element = $(element);
    if (Element.getStyle(element, 'position') == 'absolute') return element;

    var offsets = Element.positionedOffset(element);
    var top     = offsets[1];
    var left    = offsets[0];
    var width   = element.clientWidth;
    var height  = element.clientHeight;

    element._originalLeft   = left - parseFloat(element.style.left  || 0);
    element._originalTop    = top  - parseFloat(element.style.top || 0);
    element._originalWidth  = element.style.width;
    element._originalHeight = element.style.height;

    element.style.position = 'absolute';
    element.style.top    = top + 'px';
    element.style.left   = left + 'px';
    element.style.width  = width + 'px';
    element.style.height = height + 'px';
    return element;
  },

  relativize: function(element) {
    element = $(element);
    if (Element.getStyle(element, 'position') == 'relative') return element;

    element.style.position = 'relative';
    var top  = parseFloat(element.style.top  || 0) - (element._originalTop || 0);
    var left = parseFloat(element.style.left || 0) - (element._originalLeft || 0);

    element.style.top    = top + 'px';
    element.style.left   = left + 'px';
    element.style.height = element._originalHeight;
    element.style.width  = element._originalWidth;
    return element;
  },

  cumulativeScrollOffset: function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.scrollTop  || 0;
      valueL += element.scrollLeft || 0;
      element = element.parentNode;
    } while (element);
    return Element._returnOffset(valueL, valueT);
  },

  getOffsetParent: function(element) {
    if (element.offsetParent) return $(element.offsetParent);
    if (element == document.body) return $(element);

    while ((element = element.parentNode) && element != document.body)
      if (Element.getStyle(element, 'position') != 'static')
        return $(element);

    return $(document.body);
  },

  viewportOffset: function(forElement) {
    var valueT = 0, valueL = 0;

    var element = forElement;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;

      if (element.offsetParent == document.body &&
        Element.getStyle(element, 'position') == 'absolute') break;

    } while (element = element.offsetParent);

    element = forElement;
    do {
      if (!Prototype.Browser.Opera || (element.tagName && (element.tagName.toUpperCase() == 'BODY'))) {
        valueT -= element.scrollTop  || 0;
        valueL -= element.scrollLeft || 0;
      }
    } while (element = element.parentNode);

    return Element._returnOffset(valueL, valueT);
  },

  clonePosition: function(element, source) {
    var options = Object.extend({
      setLeft:    true,
      setTop:     true,
      setWidth:   true,
      setHeight:  true,
      offsetTop:  0,
      offsetLeft: 0
    }, arguments[2] || { });

    source = $(source);
    var p = Element.viewportOffset(source);

    element = $(element);
    var delta = [0, 0];
    var parent = null;
    if (Element.getStyle(element, 'position') == 'absolute') {
      parent = Element.getOffsetParent(element);
      delta = Element.viewportOffset(parent);
    }

    if (parent == document.body) {
      delta[0] -= document.body.offsetLeft;
      delta[1] -= document.body.offsetTop;
    }

    if (options.setLeft)   element.style.left  = (p[0] - delta[0] + options.offsetLeft) + 'px';
    if (options.setTop)    element.style.top   = (p[1] - delta[1] + options.offsetTop) + 'px';
    if (options.setWidth)  element.style.width = source.offsetWidth + 'px';
    if (options.setHeight) element.style.height = source.offsetHeight + 'px';
    return element;
  }
};

Object.extend(Element.Methods, {
  getElementsBySelector: Element.Methods.select,

  childElements: Element.Methods.immediateDescendants
});

Element._attributeTranslations = {
  write: {
    names: {
      className: 'class',
      htmlFor:   'for'
    },
    values: { }
  }
};

if (Prototype.Browser.Opera) {
  Element.Methods.getStyle = Element.Methods.getStyle.wrap(
    function(proceed, element, style) {
      switch (style) {
        case 'left': case 'top': case 'right': case 'bottom':
          if (proceed(element, 'position') === 'static') return null;
        case 'height': case 'width':
          if (!Element.visible(element)) return null;

          var dim = parseInt(proceed(element, style), 10);

          if (dim !== element['offset' + style.capitalize()])
            return dim + 'px';

          var properties;
          if (style === 'height') {
            properties = ['border-top-width', 'padding-top',
             'padding-bottom', 'border-bottom-width'];
          }
          else {
            properties = ['border-left-width', 'padding-left',
             'padding-right', 'border-right-width'];
          }
          return properties.inject(dim, function(memo, property) {
            var val = proceed(element, property);
            return val === null ? memo : memo - parseInt(val, 10);
          }) + 'px';
        default: return proceed(element, style);
      }
    }
  );

  Element.Methods.readAttribute = Element.Methods.readAttribute.wrap(
    function(proceed, element, attribute) {
      if (attribute === 'title') return element.title;
      return proceed(element, attribute);
    }
  );
}

else if (Prototype.Browser.IE) {
  Element.Methods.getOffsetParent = Element.Methods.getOffsetParent.wrap(
    function(proceed, element) {
      element = $(element);
      try { element.offsetParent }
      catch(e) { return $(document.body) }
      var position = element.getStyle('position');
      if (position !== 'static') return proceed(element);
      element.setStyle({ position: 'relative' });
      var value = proceed(element);
      element.setStyle({ position: position });
      return value;
    }
  );

  $w('positionedOffset viewportOffset').each(function(method) {
    Element.Methods[method] = Element.Methods[method].wrap(
      function(proceed, element) {
        element = $(element);
        try { element.offsetParent }
        catch(e) { return Element._returnOffset(0,0) }
        var position = element.getStyle('position');
        if (position !== 'static') return proceed(element);
        var offsetParent = element.getOffsetParent();
        if (offsetParent && offsetParent.getStyle('position') === 'fixed')
          offsetParent.setStyle({ zoom: 1 });
        element.setStyle({ position: 'relative' });
        var value = proceed(element);
        element.setStyle({ position: position });
        return value;
      }
    );
  });

  Element.Methods.cumulativeOffset = Element.Methods.cumulativeOffset.wrap(
    function(proceed, element) {
      try { element.offsetParent }
      catch(e) { return Element._returnOffset(0,0) }
      return proceed(element);
    }
  );

  Element.Methods.getStyle = function(element, style) {
    element = $(element);
    style = (style == 'float' || style == 'cssFloat') ? 'styleFloat' : style.camelize();
    var value = element.style[style];
    if (!value && element.currentStyle) value = element.currentStyle[style];

    if (style == 'opacity') {
      if (value = (element.getStyle('filter') || '').match(/alpha\(opacity=(.*)\)/))
        if (value[1]) return parseFloat(value[1]) / 100;
      return 1.0;
    }

    if (value == 'auto') {
      if ((style == 'width' || style == 'height') && (element.getStyle('display') != 'none'))
        return element['offset' + style.capitalize()] + 'px';
      return null;
    }
    return value;
  };

  Element.Methods.setOpacity = function(element, value) {
    function stripAlpha(filter){
      return filter.replace(/alpha\([^\)]*\)/gi,'');
    }
    element = $(element);
    var currentStyle = element.currentStyle;
    if ((currentStyle && !currentStyle.hasLayout) ||
      (!currentStyle && element.style.zoom == 'normal'))
        element.style.zoom = 1;

    var filter = element.getStyle('filter'), style = element.style;
    if (value == 1 || value === '') {
      (filter = stripAlpha(filter)) ?
        style.filter = filter : style.removeAttribute('filter');
      return element;
    } else if (value < 0.00001) value = 0;
    style.filter = stripAlpha(filter) +
      'alpha(opacity=' + (value * 100) + ')';
    return element;
  };

  Element._attributeTranslations = (function(){

    var classProp = 'className';
    var forProp = 'for';

    var el = document.createElement('div');

    el.setAttribute(classProp, 'x');

    if (el.className !== 'x') {
      el.setAttribute('class', 'x');
      if (el.className === 'x') {
        classProp = 'class';
      }
    }
    el = null;

    el = document.createElement('label');
    el.setAttribute(forProp, 'x');
    if (el.htmlFor !== 'x') {
      el.setAttribute('htmlFor', 'x');
      if (el.htmlFor === 'x') {
        forProp = 'htmlFor';
      }
    }
    el = null;

    return {
      read: {
        names: {
          'class':      classProp,
          'className':  classProp,
          'for':        forProp,
          'htmlFor':    forProp
        },
        values: {
          _getAttr: function(element, attribute) {
            return element.getAttribute(attribute);
          },
          _getAttr2: function(element, attribute) {
            return element.getAttribute(attribute, 2);
          },
          _getAttrNode: function(element, attribute) {
            var node = element.getAttributeNode(attribute);
            return node ? node.value : "";
          },
          _getEv: (function(){

            var el = document.createElement('div');
            el.onclick = Prototype.emptyFunction;
            var value = el.getAttribute('onclick');
            var f;

            if (String(value).indexOf('{') > -1) {
              f = function(element, attribute) {
                attribute = element.getAttribute(attribute);
                if (!attribute) return null;
                attribute = attribute.toString();
                attribute = attribute.split('{')[1];
                attribute = attribute.split('}')[0];
                return attribute.strip();
              };
            }
            else if (value === '') {
              f = function(element, attribute) {
                attribute = element.getAttribute(attribute);
                if (!attribute) return null;
                return attribute.strip();
              };
            }
            el = null;
            return f;
          })(),
          _flag: function(element, attribute) {
            return $(element).hasAttribute(attribute) ? attribute : null;
          },
          style: function(element) {
            return element.style.cssText.toLowerCase();
          },
          title: function(element) {
            return element.title;
          }
        }
      }
    }
  })();

  Element._attributeTranslations.write = {
    names: Object.extend({
      cellpadding: 'cellPadding',
      cellspacing: 'cellSpacing'
    }, Element._attributeTranslations.read.names),
    values: {
      checked: function(element, value) {
        element.checked = !!value;
      },

      style: function(element, value) {
        element.style.cssText = value ? value : '';
      }
    }
  };

  Element._attributeTranslations.has = {};

  $w('colSpan rowSpan vAlign dateTime accessKey tabIndex ' +
      'encType maxLength readOnly longDesc frameBorder').each(function(attr) {
    Element._attributeTranslations.write.names[attr.toLowerCase()] = attr;
    Element._attributeTranslations.has[attr.toLowerCase()] = attr;
  });

  (function(v) {
    Object.extend(v, {
      href:        v._getAttr2,
      src:         v._getAttr2,
      type:        v._getAttr,
      action:      v._getAttrNode,
      disabled:    v._flag,
      checked:     v._flag,
      readonly:    v._flag,
      multiple:    v._flag,
      onload:      v._getEv,
      onunload:    v._getEv,
      onclick:     v._getEv,
      ondblclick:  v._getEv,
      onmousedown: v._getEv,
      onmouseup:   v._getEv,
      onmouseover: v._getEv,
      onmousemove: v._getEv,
      onmouseout:  v._getEv,
      onfocus:     v._getEv,
      onblur:      v._getEv,
      onkeypress:  v._getEv,
      onkeydown:   v._getEv,
      onkeyup:     v._getEv,
      onsubmit:    v._getEv,
      onreset:     v._getEv,
      onselect:    v._getEv,
      onchange:    v._getEv
    });
  })(Element._attributeTranslations.read.values);

  if (Prototype.BrowserFeatures.ElementExtensions) {
    (function() {
      function _descendants(element) {
        var nodes = element.getElementsByTagName('*'), results = [];
        for (var i = 0, node; node = nodes[i]; i++)
          if (node.tagName !== "!") // Filter out comment nodes.
            results.push(node);
        return results;
      }

      Element.Methods.down = function(element, expression, index) {
        element = $(element);
        if (arguments.length == 1) return element.firstDescendant();
        return Object.isNumber(expression) ? _descendants(element)[expression] :
          Element.select(element, expression)[index || 0];
      }
    })();
  }

}

else if (Prototype.Browser.Gecko && /rv:1\.8\.0/.test(navigator.userAgent)) {
  Element.Methods.setOpacity = function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1) ? 0.999999 :
      (value === '') ? '' : (value < 0.00001) ? 0 : value;
    return element;
  };
}

else if (Prototype.Browser.WebKit) {
  Element.Methods.setOpacity = function(element, value) {
    element = $(element);
    element.style.opacity = (value == 1 || value === '') ? '' :
      (value < 0.00001) ? 0 : value;

    if (value == 1)
      if(element.tagName.toUpperCase() == 'IMG' && element.width) {
        element.width++; element.width--;
      } else try {
        var n = document.createTextNode(' ');
        element.appendChild(n);
        element.removeChild(n);
      } catch (e) { }

    return element;
  };

  Element.Methods.cumulativeOffset = function(element) {
    var valueT = 0, valueL = 0;
    do {
      valueT += element.offsetTop  || 0;
      valueL += element.offsetLeft || 0;
      if (element.offsetParent == document.body)
        if (Element.getStyle(element, 'position') == 'absolute') break;

      element = element.offsetParent;
    } while (element);

    return Element._returnOffset(valueL, valueT);
  };
}

if ('outerHTML' in document.documentElement) {
  Element.Methods.replace = function(element, content) {
    element = $(element);

    if (content && content.toElement) content = content.toElement();
    if (Object.isElement(content)) {
      element.parentNode.replaceChild(content, element);
      return element;
    }

    content = Object.toHTML(content);
    var parent = element.parentNode, tagName = parent.tagName.toUpperCase();

    if (Element._insertionTranslations.tags[tagName]) {
      var nextSibling = element.next();
      var fragments = Element._getContentFromAnonymousElement(tagName, content.stripScripts());
      parent.removeChild(element);
      if (nextSibling)
        fragments.each(function(node) { parent.insertBefore(node, nextSibling) });
      else
        fragments.each(function(node) { parent.appendChild(node) });
    }
    else element.outerHTML = content.stripScripts();

    content.evalScripts.bind(content).defer();
    return element;
  };
}

Element._returnOffset = function(l, t) {
  var result = [l, t];
  result.left = l;
  result.top = t;
  return result;
};

Element._getContentFromAnonymousElement = function(tagName, html) {
  var div = new Element('div'), t = Element._insertionTranslations.tags[tagName];
  if (t) {
    div.innerHTML = t[0] + html + t[1];
    t[2].times(function() { div = div.firstChild });
  } else div.innerHTML = html;
  return $A(div.childNodes);
};

Element._insertionTranslations = {
  before: function(element, node) {
    element.parentNode.insertBefore(node, element);
  },
  top: function(element, node) {
    element.insertBefore(node, element.firstChild);
  },
  bottom: function(element, node) {
    element.appendChild(node);
  },
  after: function(element, node) {
    element.parentNode.insertBefore(node, element.nextSibling);
  },
  tags: {
    TABLE:  ['<table>',                '</table>',                   1],
    TBODY:  ['<table><tbody>',         '</tbody></table>',           2],
    TR:     ['<table><tbody><tr>',     '</tr></tbody></table>',      3],
    TD:     ['<table><tbody><tr><td>', '</td></tr></tbody></table>', 4],
    SELECT: ['<select>',               '</select>',                  1]
  }
};

(function() {
  var tags = Element._insertionTranslations.tags;
  Object.extend(tags, {
    THEAD: tags.TBODY,
    TFOOT: tags.TBODY,
    TH:    tags.TD
  });
})();

Element.Methods.Simulated = {
  hasAttribute: function(element, attribute) {
    attribute = Element._attributeTranslations.has[attribute] || attribute;
    var node = $(element).getAttributeNode(attribute);
    return !!(node && node.specified);
  }
};

Element.Methods.ByTag = { };

Object.extend(Element, Element.Methods);

(function(div) {

  if (!Prototype.BrowserFeatures.ElementExtensions && div['__proto__']) {
    window.HTMLElement = { };
    window.HTMLElement.prototype = div['__proto__'];
    Prototype.BrowserFeatures.ElementExtensions = true;
  }

  div = null;

})(document.createElement('div'))

Element.extend = (function() {

  function checkDeficiency(tagName) {
    if (typeof window.Element != 'undefined') {
      var proto = window.Element.prototype;
      if (proto) {
        var id = '_' + (Math.random()+'').slice(2);
        var el = document.createElement(tagName);
        proto[id] = 'x';
        var isBuggy = (el[id] !== 'x');
        delete proto[id];
        el = null;
        return isBuggy;
      }
    }
    return false;
  }

  function extendElementWith(element, methods) {
    for (var property in methods) {
      var value = methods[property];
      if (Object.isFunction(value) && !(property in element))
        element[property] = value.methodize();
    }
  }

  var HTMLOBJECTELEMENT_PROTOTYPE_BUGGY = checkDeficiency('object');

  if (Prototype.BrowserFeatures.SpecificElementExtensions) {
    if (HTMLOBJECTELEMENT_PROTOTYPE_BUGGY) {
      return function(element) {
        if (element && typeof element._extendedByPrototype == 'undefined') {
          var t = element.tagName;
          if (t && (/^(?:object|applet|embed)$/i.test(t))) {
            extendElementWith(element, Element.Methods);
            extendElementWith(element, Element.Methods.Simulated);
            extendElementWith(element, Element.Methods.ByTag[t.toUpperCase()]);
          }
        }
        return element;
      }
    }
    return Prototype.K;
  }

  var Methods = { }, ByTag = Element.Methods.ByTag;

  var extend = Object.extend(function(element) {
    if (!element || typeof element._extendedByPrototype != 'undefined' ||
        element.nodeType != 1 || element == window) return element;

    var methods = Object.clone(Methods),
        tagName = element.tagName.toUpperCase();

    if (ByTag[tagName]) Object.extend(methods, ByTag[tagName]);

    extendElementWith(element, methods);

    element._extendedByPrototype = Prototype.emptyFunction;
    return element;

  }, {
    refresh: function() {
      if (!Prototype.BrowserFeatures.ElementExtensions) {
        Object.extend(Methods, Element.Methods);
        Object.extend(Methods, Element.Methods.Simulated);
      }
    }
  });

  extend.refresh();
  return extend;
})();

Element.hasAttribute = function(element, attribute) {
  if (element.hasAttribute) return element.hasAttribute(attribute);
  return Element.Methods.Simulated.hasAttribute(element, attribute);
};

Element.addMethods = function(methods) {
  var F = Prototype.BrowserFeatures, T = Element.Methods.ByTag;

  if (!methods) {
    Object.extend(Form, Form.Methods);
    Object.extend(Form.Element, Form.Element.Methods);
    Object.extend(Element.Methods.ByTag, {
      "FORM":     Object.clone(Form.Methods),
      "INPUT":    Object.clone(Form.Element.Methods),
      "SELECT":   Object.clone(Form.Element.Methods),
      "TEXTAREA": Object.clone(Form.Element.Methods)
    });
  }

  if (arguments.length == 2) {
    var tagName = methods;
    methods = arguments[1];
  }

  if (!tagName) Object.extend(Element.Methods, methods || { });
  else {
    if (Object.isArray(tagName)) tagName.each(extend);
    else extend(tagName);
  }

  function extend(tagName) {
    tagName = tagName.toUpperCase();
    if (!Element.Methods.ByTag[tagName])
      Element.Methods.ByTag[tagName] = { };
    Object.extend(Element.Methods.ByTag[tagName], methods);
  }

  function copy(methods, destination, onlyIfAbsent) {
    onlyIfAbsent = onlyIfAbsent || false;
    for (var property in methods) {
      var value = methods[property];
      if (!Object.isFunction(value)) continue;
      if (!onlyIfAbsent || !(property in destination))
        destination[property] = value.methodize();
    }
  }

  function findDOMClass(tagName) {
    var klass;
    var trans = {
      "OPTGROUP": "OptGroup", "TEXTAREA": "TextArea", "P": "Paragraph",
      "FIELDSET": "FieldSet", "UL": "UList", "OL": "OList", "DL": "DList",
      "DIR": "Directory", "H1": "Heading", "H2": "Heading", "H3": "Heading",
      "H4": "Heading", "H5": "Heading", "H6": "Heading", "Q": "Quote",
      "INS": "Mod", "DEL": "Mod", "A": "Anchor", "IMG": "Image", "CAPTION":
      "TableCaption", "COL": "TableCol", "COLGROUP": "TableCol", "THEAD":
      "TableSection", "TFOOT": "TableSection", "TBODY": "TableSection", "TR":
      "TableRow", "TH": "TableCell", "TD": "TableCell", "FRAMESET":
      "FrameSet", "IFRAME": "IFrame"
    };
    if (trans[tagName]) klass = 'HTML' + trans[tagName] + 'Element';
    if (window[klass]) return window[klass];
    klass = 'HTML' + tagName + 'Element';
    if (window[klass]) return window[klass];
    klass = 'HTML' + tagName.capitalize() + 'Element';
    if (window[klass]) return window[klass];

    var element = document.createElement(tagName);
    var proto = element['__proto__'] || element.constructor.prototype;
    element = null;
    return proto;
  }

  var elementPrototype = window.HTMLElement ? HTMLElement.prototype :
   Element.prototype;

  if (F.ElementExtensions) {
    copy(Element.Methods, elementPrototype);
    copy(Element.Methods.Simulated, elementPrototype, true);
  }

  if (F.SpecificElementExtensions) {
    for (var tag in Element.Methods.ByTag) {
      var klass = findDOMClass(tag);
      if (Object.isUndefined(klass)) continue;
      copy(T[tag], klass.prototype);
    }
  }

  Object.extend(Element, Element.Methods);
  delete Element.ByTag;

  if (Element.extend.refresh) Element.extend.refresh();
  Element.cache = { };
};


document.viewport = {

  getDimensions: function() {
    return { width: this.getWidth(), height: this.getHeight() };
  },

  getScrollOffsets: function() {
    return Element._returnOffset(
      window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
      window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop);
  }
};

(function(viewport) {
  var B = Prototype.Browser, doc = document, element, property = {};

  function getRootElement() {
    if (B.WebKit && !doc.evaluate)
      return document;

    if (B.Opera && window.parseFloat(window.opera.version()) < 9.5)
      return document.body;

    return document.documentElement;
  }

  function define(D) {
    if (!element) element = getRootElement();

    property[D] = 'client' + D;

    viewport['get' + D] = function() { return element[property[D]] };
    return viewport['get' + D]();
  }

  viewport.getWidth  = define.curry('Width');

  viewport.getHeight = define.curry('Height');
})(document.viewport);


Element.Storage = {
  UID: 1
};

Element.addMethods({
  getStorage: function(element) {
    if (!(element = $(element))) return;

    var uid;
    if (element === window) {
      uid = 0;
    } else {
      if (typeof element._prototypeUID === "undefined")
        element._prototypeUID = [Element.Storage.UID++];
      uid = element._prototypeUID[0];
    }

    if (!Element.Storage[uid])
      Element.Storage[uid] = $H();

    return Element.Storage[uid];
  },

  store: function(element, key, value) {
    if (!(element = $(element))) return;

    if (arguments.length === 2) {
      Element.getStorage(element).update(key);
    } else {
      Element.getStorage(element).set(key, value);
    }

    return element;
  },

  retrieve: function(element, key, defaultValue) {
    if (!(element = $(element))) return;
    var hash = Element.getStorage(element), value = hash.get(key);

    if (Object.isUndefined(value)) {
      hash.set(key, defaultValue);
      value = defaultValue;
    }

    return value;
  },

  clone: function(element, deep) {
    if (!(element = $(element))) return;
    var clone = element.cloneNode(deep);
    clone._prototypeUID = void 0;
    if (deep) {
      var descendants = Element.select(clone, '*'),
          i = descendants.length;
      while (i--) {
        descendants[i]._prototypeUID = void 0;
      }
    }
    return Element.extend(clone);
  }
});
/* Portions of the Selector class are derived from Jack Slocum's DomQuery,
 * part of YUI-Ext version 0.40, distributed under the terms of an MIT-style
 * license.  Please see http://www.yui-ext.com/ for more information. */

var Selector = Class.create({
  initialize: function(expression) {
    this.expression = expression.strip();

    if (this.shouldUseSelectorsAPI()) {
      this.mode = 'selectorsAPI';
    } else if (this.shouldUseXPath()) {
      this.mode = 'xpath';
      this.compileXPathMatcher();
    } else {
      this.mode = "normal";
      this.compileMatcher();
    }

  },

  shouldUseXPath: (function() {

    var IS_DESCENDANT_SELECTOR_BUGGY = (function(){
      var isBuggy = false;
      if (document.evaluate && window.XPathResult) {
        var el = document.createElement('div');
        el.innerHTML = '<ul><li></li></ul><div><ul><li></li></ul></div>';

        var xpath = ".//*[local-name()='ul' or local-name()='UL']" +
          "//*[local-name()='li' or local-name()='LI']";

        var result = document.evaluate(xpath, el, null,
          XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);

        isBuggy = (result.snapshotLength !== 2);
        el = null;
      }
      return isBuggy;
    })();

    return function() {
      if (!Prototype.BrowserFeatures.XPath) return false;

      var e = this.expression;

      if (Prototype.Browser.WebKit &&
       (e.include("-of-type") || e.include(":empty")))
        return false;

      if ((/(\[[\w-]*?:|:checked)/).test(e))
        return false;

      if (IS_DESCENDANT_SELECTOR_BUGGY) return false;

      return true;
    }

  })(),

  shouldUseSelectorsAPI: function() {
    if (!Prototype.BrowserFeatures.SelectorsAPI) return false;

    if (Selector.CASE_INSENSITIVE_CLASS_NAMES) return false;

    if (!Selector._div) Selector._div = new Element('div');

    try {
      Selector._div.querySelector(this.expression);
    } catch(e) {
      return false;
    }

    return true;
  },

  compileMatcher: function() {
    var e = this.expression, ps = Selector.patterns, h = Selector.handlers,
        c = Selector.criteria, le, p, m, len = ps.length, name;

    if (Selector._cache[e]) {
      this.matcher = Selector._cache[e];
      return;
    }

    this.matcher = ["this.matcher = function(root) {",
                    "var r = root, h = Selector.handlers, c = false, n;"];

    while (e && le != e && (/\S/).test(e)) {
      le = e;
      for (var i = 0; i<len; i++) {
        p = ps[i].re;
        name = ps[i].name;
        if (m = e.match(p)) {
          this.matcher.push(Object.isFunction(c[name]) ? c[name](m) :
            new Template(c[name]).evaluate(m));
          e = e.replace(m[0], '');
          break;
        }
      }
    }

    this.matcher.push("return h.unique(n);\n}");
    eval(this.matcher.join('\n'));
    Selector._cache[this.expression] = this.matcher;
  },

  compileXPathMatcher: function() {
    var e = this.expression, ps = Selector.patterns,
        x = Selector.xpath, le, m, len = ps.length, name;

    if (Selector._cache[e]) {
      this.xpath = Selector._cache[e]; return;
    }

    this.matcher = ['.//*'];
    while (e && le != e && (/\S/).test(e)) {
      le = e;
      for (var i = 0; i<len; i++) {
        name = ps[i].name;
        if (m = e.match(ps[i].re)) {
          this.matcher.push(Object.isFunction(x[name]) ? x[name](m) :
            new Template(x[name]).evaluate(m));
          e = e.replace(m[0], '');
          break;
        }
      }
    }

    this.xpath = this.matcher.join('');
    Selector._cache[this.expression] = this.xpath;
  },

  findElements: function(root) {
    root = root || document;
    var e = this.expression, results;

    switch (this.mode) {
      case 'selectorsAPI':
        if (root !== document) {
          var oldId = root.id, id = $(root).identify();
          id = id.replace(/([\.:])/g, "\\$1");
          e = "#" + id + " " + e;
        }

        results = $A(root.querySelectorAll(e)).map(Element.extend);
        root.id = oldId;

        return results;
      case 'xpath':
        return document._getElementsByXPath(this.xpath, root);
      default:
       return this.matcher(root);
    }
  },

  match: function(element) {
    this.tokens = [];

    var e = this.expression, ps = Selector.patterns, as = Selector.assertions;
    var le, p, m, len = ps.length, name;

    while (e && le !== e && (/\S/).test(e)) {
      le = e;
      for (var i = 0; i<len; i++) {
        p = ps[i].re;
        name = ps[i].name;
        if (m = e.match(p)) {
          if (as[name]) {
            this.tokens.push([name, Object.clone(m)]);
            e = e.replace(m[0], '');
          } else {
            return this.findElements(document).include(element);
          }
        }
      }
    }

    var match = true, name, matches;
    for (var i = 0, token; token = this.tokens[i]; i++) {
      name = token[0], matches = token[1];
      if (!Selector.assertions[name](element, matches)) {
        match = false; break;
      }
    }

    return match;
  },

  toString: function() {
    return this.expression;
  },

  inspect: function() {
    return "#<Selector:" + this.expression.inspect() + ">";
  }
});

if (Prototype.BrowserFeatures.SelectorsAPI &&
 document.compatMode === 'BackCompat') {
  Selector.CASE_INSENSITIVE_CLASS_NAMES = (function(){
    var div = document.createElement('div'),
     span = document.createElement('span');

    div.id = "prototype_test_id";
    span.className = 'Test';
    div.appendChild(span);
    var isIgnored = (div.querySelector('#prototype_test_id .test') !== null);
    div = span = null;
    return isIgnored;
  })();
}

Object.extend(Selector, {
  _cache: { },

  xpath: {
    descendant:   "//*",
    child:        "/*",
    adjacent:     "/following-sibling::*[1]",
    laterSibling: '/following-sibling::*',
    tagName:      function(m) {
      if (m[1] == '*') return '';
      return "[local-name()='" + m[1].toLowerCase() +
             "' or local-name()='" + m[1].toUpperCase() + "']";
    },
    className:    "[contains(concat(' ', @class, ' '), ' #{1} ')]",
    id:           "[@id='#{1}']",
    attrPresence: function(m) {
      m[1] = m[1].toLowerCase();
      return new Template("[@#{1}]").evaluate(m);
    },
    attr: function(m) {
      m[1] = m[1].toLowerCase();
      m[3] = m[5] || m[6];
      return new Template(Selector.xpath.operators[m[2]]).evaluate(m);
    },
    pseudo: function(m) {
      var h = Selector.xpath.pseudos[m[1]];
      if (!h) return '';
      if (Object.isFunction(h)) return h(m);
      return new Template(Selector.xpath.pseudos[m[1]]).evaluate(m);
    },
    operators: {
      '=':  "[@#{1}='#{3}']",
      '!=': "[@#{1}!='#{3}']",
      '^=': "[starts-with(@#{1}, '#{3}')]",
      '$=': "[substring(@#{1}, (string-length(@#{1}) - string-length('#{3}') + 1))='#{3}']",
      '*=': "[contains(@#{1}, '#{3}')]",
      '~=': "[contains(concat(' ', @#{1}, ' '), ' #{3} ')]",
      '|=': "[contains(concat('-', @#{1}, '-'), '-#{3}-')]"
    },
    pseudos: {
      'first-child': '[not(preceding-sibling::*)]',
      'last-child':  '[not(following-sibling::*)]',
      'only-child':  '[not(preceding-sibling::* or following-sibling::*)]',
      'empty':       "[count(*) = 0 and (count(text()) = 0)]",
      'checked':     "[@checked]",
      'disabled':    "[(@disabled) and (@type!='hidden')]",
      'enabled':     "[not(@disabled) and (@type!='hidden')]",
      'not': function(m) {
        var e = m[6], p = Selector.patterns,
            x = Selector.xpath, le, v, len = p.length, name;

        var exclusion = [];
        while (e && le != e && (/\S/).test(e)) {
          le = e;
          for (var i = 0; i<len; i++) {
            name = p[i].name
            if (m = e.match(p[i].re)) {
              v = Object.isFunction(x[name]) ? x[name](m) : new Template(x[name]).evaluate(m);
              exclusion.push("(" + v.substring(1, v.length - 1) + ")");
              e = e.replace(m[0], '');
              break;
            }
          }
        }
        return "[not(" + exclusion.join(" and ") + ")]";
      },
      'nth-child':      function(m) {
        return Selector.xpath.pseudos.nth("(count(./preceding-sibling::*) + 1) ", m);
      },
      'nth-last-child': function(m) {
        return Selector.xpath.pseudos.nth("(count(./following-sibling::*) + 1) ", m);
      },
      'nth-of-type':    function(m) {
        return Selector.xpath.pseudos.nth("position() ", m);
      },
      'nth-last-of-type': function(m) {
        return Selector.xpath.pseudos.nth("(last() + 1 - position()) ", m);
      },
      'first-of-type':  function(m) {
        m[6] = "1"; return Selector.xpath.pseudos['nth-of-type'](m);
      },
      'last-of-type':   function(m) {
        m[6] = "1"; return Selector.xpath.pseudos['nth-last-of-type'](m);
      },
      'only-of-type':   function(m) {
        var p = Selector.xpath.pseudos; return p['first-of-type'](m) + p['last-of-type'](m);
      },
      nth: function(fragment, m) {
        var mm, formula = m[6], predicate;
        if (formula == 'even') formula = '2n+0';
        if (formula == 'odd')  formula = '2n+1';
        if (mm = formula.match(/^(\d+)$/)) // digit only
          return '[' + fragment + "= " + mm[1] + ']';
        if (mm = formula.match(/^(-?\d*)?n(([+-])(\d+))?/)) { // an+b
          if (mm[1] == "-") mm[1] = -1;
          var a = mm[1] ? Number(mm[1]) : 1;
          var b = mm[2] ? Number(mm[2]) : 0;
          predicate = "[((#{fragment} - #{b}) mod #{a} = 0) and " +
          "((#{fragment} - #{b}) div #{a} >= 0)]";
          return new Template(predicate).evaluate({
            fragment: fragment, a: a, b: b });
        }
      }
    }
  },

  criteria: {
    tagName:      'n = h.tagName(n, r, "#{1}", c);      c = false;',
    className:    'n = h.className(n, r, "#{1}", c);    c = false;',
    id:           'n = h.id(n, r, "#{1}", c);           c = false;',
    attrPresence: 'n = h.attrPresence(n, r, "#{1}", c); c = false;',
    attr: function(m) {
      m[3] = (m[5] || m[6]);
      return new Template('n = h.attr(n, r, "#{1}", "#{3}", "#{2}", c); c = false;').evaluate(m);
    },
    pseudo: function(m) {
      if (m[6]) m[6] = m[6].replace(/"/g, '\\"');
      return new Template('n = h.pseudo(n, "#{1}", "#{6}", r, c); c = false;').evaluate(m);
    },
    descendant:   'c = "descendant";',
    child:        'c = "child";',
    adjacent:     'c = "adjacent";',
    laterSibling: 'c = "laterSibling";'
  },

  patterns: [
    { name: 'laterSibling', re: /^\s*~\s*/ },
    { name: 'child',        re: /^\s*>\s*/ },
    { name: 'adjacent',     re: /^\s*\+\s*/ },
    { name: 'descendant',   re: /^\s/ },

    { name: 'tagName',      re: /^\s*(\*|[\w\-]+)(\b|$)?/ },
    { name: 'id',           re: /^#([\w\-\*]+)(\b|$)/ },
    { name: 'className',    re: /^\.([\w\-\*]+)(\b|$)/ },
    { name: 'pseudo',       re: /^:((first|last|nth|nth-last|only)(-child|-of-type)|empty|checked|(en|dis)abled|not)(\((.*?)\))?(\b|$|(?=\s|[:+~>]))/ },
    { name: 'attrPresence', re: /^\[((?:[\w-]+:)?[\w-]+)\]/ },
    { name: 'attr',         re: /\[((?:[\w-]*:)?[\w-]+)\s*(?:([!^$*~|]?=)\s*((['"])([^\4]*?)\4|([^'"][^\]]*?)))?\]/ }
  ],

  assertions: {
    tagName: function(element, matches) {
      return matches[1].toUpperCase() == element.tagName.toUpperCase();
    },

    className: function(element, matches) {
      return Element.hasClassName(element, matches[1]);
    },

    id: function(element, matches) {
      return element.id === matches[1];
    },

    attrPresence: function(element, matches) {
      return Element.hasAttribute(element, matches[1]);
    },

    attr: function(element, matches) {
      var nodeValue = Element.readAttribute(element, matches[1]);
      return nodeValue && Selector.operators[matches[2]](nodeValue, matches[5] || matches[6]);
    }
  },

  handlers: {
    concat: function(a, b) {
      for (var i = 0, node; node = b[i]; i++)
        a.push(node);
      return a;
    },

    mark: function(nodes) {
      var _true = Prototype.emptyFunction;
      for (var i = 0, node; node = nodes[i]; i++)
        node._countedByPrototype = _true;
      return nodes;
    },

    unmark: (function(){

      var PROPERTIES_ATTRIBUTES_MAP = (function(){
        var el = document.createElement('div'),
            isBuggy = false,
            propName = '_countedByPrototype',
            value = 'x'
        el[propName] = value;
        isBuggy = (el.getAttribute(propName) === value);
        el = null;
        return isBuggy;
      })();

      return PROPERTIES_ATTRIBUTES_MAP ?
        function(nodes) {
          for (var i = 0, node; node = nodes[i]; i++)
            node.removeAttribute('_countedByPrototype');
          return nodes;
        } :
        function(nodes) {
          for (var i = 0, node; node = nodes[i]; i++)
            node._countedByPrototype = void 0;
          return nodes;
        }
    })(),

    index: function(parentNode, reverse, ofType) {
      parentNode._countedByPrototype = Prototype.emptyFunction;
      if (reverse) {
        for (var nodes = parentNode.childNodes, i = nodes.length - 1, j = 1; i >= 0; i--) {
          var node = nodes[i];
          if (node.nodeType == 1 && (!ofType || node._countedByPrototype)) node.nodeIndex = j++;
        }
      } else {
        for (var i = 0, j = 1, nodes = parentNode.childNodes; node = nodes[i]; i++)
          if (node.nodeType == 1 && (!ofType || node._countedByPrototype)) node.nodeIndex = j++;
      }
    },

    unique: function(nodes) {
      if (nodes.length == 0) return nodes;
      var results = [], n;
      for (var i = 0, l = nodes.length; i < l; i++)
        if (typeof (n = nodes[i])._countedByPrototype == 'undefined') {
          n._countedByPrototype = Prototype.emptyFunction;
          results.push(Element.extend(n));
        }
      return Selector.handlers.unmark(results);
    },

    descendant: function(nodes) {
      var h = Selector.handlers;
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        h.concat(results, node.getElementsByTagName('*'));
      return results;
    },

    child: function(nodes) {
      var h = Selector.handlers;
      for (var i = 0, results = [], node; node = nodes[i]; i++) {
        for (var j = 0, child; child = node.childNodes[j]; j++)
          if (child.nodeType == 1 && child.tagName != '!') results.push(child);
      }
      return results;
    },

    adjacent: function(nodes) {
      for (var i = 0, results = [], node; node = nodes[i]; i++) {
        var next = this.nextElementSibling(node);
        if (next) results.push(next);
      }
      return results;
    },

    laterSibling: function(nodes) {
      var h = Selector.handlers;
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        h.concat(results, Element.nextSiblings(node));
      return results;
    },

    nextElementSibling: function(node) {
      while (node = node.nextSibling)
        if (node.nodeType == 1) return node;
      return null;
    },

    previousElementSibling: function(node) {
      while (node = node.previousSibling)
        if (node.nodeType == 1) return node;
      return null;
    },

    tagName: function(nodes, root, tagName, combinator) {
      var uTagName = tagName.toUpperCase();
      var results = [], h = Selector.handlers;
      if (nodes) {
        if (combinator) {
          if (combinator == "descendant") {
            for (var i = 0, node; node = nodes[i]; i++)
              h.concat(results, node.getElementsByTagName(tagName));
            return results;
          } else nodes = this[combinator](nodes);
          if (tagName == "*") return nodes;
        }
        for (var i = 0, node; node = nodes[i]; i++)
          if (node.tagName.toUpperCase() === uTagName) results.push(node);
        return results;
      } else return root.getElementsByTagName(tagName);
    },

    id: function(nodes, root, id, combinator) {
      var targetNode = $(id), h = Selector.handlers;

      if (root == document) {
        if (!targetNode) return [];
        if (!nodes) return [targetNode];
      } else {
        if (!root.sourceIndex || root.sourceIndex < 1) {
          var nodes = root.getElementsByTagName('*');
          for (var j = 0, node; node = nodes[j]; j++) {
            if (node.id === id) return [node];
          }
        }
      }

      if (nodes) {
        if (combinator) {
          if (combinator == 'child') {
            for (var i = 0, node; node = nodes[i]; i++)
              if (targetNode.parentNode == node) return [targetNode];
          } else if (combinator == 'descendant') {
            for (var i = 0, node; node = nodes[i]; i++)
              if (Element.descendantOf(targetNode, node)) return [targetNode];
          } else if (combinator == 'adjacent') {
            for (var i = 0, node; node = nodes[i]; i++)
              if (Selector.handlers.previousElementSibling(targetNode) == node)
                return [targetNode];
          } else nodes = h[combinator](nodes);
        }
        for (var i = 0, node; node = nodes[i]; i++)
          if (node == targetNode) return [targetNode];
        return [];
      }
      return (targetNode && Element.descendantOf(targetNode, root)) ? [targetNode] : [];
    },

    className: function(nodes, root, className, combinator) {
      if (nodes && combinator) nodes = this[combinator](nodes);
      return Selector.handlers.byClassName(nodes, root, className);
    },

    byClassName: function(nodes, root, className) {
      if (!nodes) nodes = Selector.handlers.descendant([root]);
      var needle = ' ' + className + ' ';
      for (var i = 0, results = [], node, nodeClassName; node = nodes[i]; i++) {
        nodeClassName = node.className;
        if (nodeClassName.length == 0) continue;
        if (nodeClassName == className || (' ' + nodeClassName + ' ').include(needle))
          results.push(node);
      }
      return results;
    },

    attrPresence: function(nodes, root, attr, combinator) {
      if (!nodes) nodes = root.getElementsByTagName("*");
      if (nodes && combinator) nodes = this[combinator](nodes);
      var results = [];
      for (var i = 0, node; node = nodes[i]; i++)
        if (Element.hasAttribute(node, attr)) results.push(node);
      return results;
    },

    attr: function(nodes, root, attr, value, operator, combinator) {
      if (!nodes) nodes = root.getElementsByTagName("*");
      if (nodes && combinator) nodes = this[combinator](nodes);
      var handler = Selector.operators[operator], results = [];
      for (var i = 0, node; node = nodes[i]; i++) {
        var nodeValue = Element.readAttribute(node, attr);
        if (nodeValue === null) continue;
        if (handler(nodeValue, value)) results.push(node);
      }
      return results;
    },

    pseudo: function(nodes, name, value, root, combinator) {
      if (nodes && combinator) nodes = this[combinator](nodes);
      if (!nodes) nodes = root.getElementsByTagName("*");
      return Selector.pseudos[name](nodes, value, root);
    }
  },

  pseudos: {
    'first-child': function(nodes, value, root) {
      for (var i = 0, results = [], node; node = nodes[i]; i++) {
        if (Selector.handlers.previousElementSibling(node)) continue;
          results.push(node);
      }
      return results;
    },
    'last-child': function(nodes, value, root) {
      for (var i = 0, results = [], node; node = nodes[i]; i++) {
        if (Selector.handlers.nextElementSibling(node)) continue;
          results.push(node);
      }
      return results;
    },
    'only-child': function(nodes, value, root) {
      var h = Selector.handlers;
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        if (!h.previousElementSibling(node) && !h.nextElementSibling(node))
          results.push(node);
      return results;
    },
    'nth-child':        function(nodes, formula, root) {
      return Selector.pseudos.nth(nodes, formula, root);
    },
    'nth-last-child':   function(nodes, formula, root) {
      return Selector.pseudos.nth(nodes, formula, root, true);
    },
    'nth-of-type':      function(nodes, formula, root) {
      return Selector.pseudos.nth(nodes, formula, root, false, true);
    },
    'nth-last-of-type': function(nodes, formula, root) {
      return Selector.pseudos.nth(nodes, formula, root, true, true);
    },
    'first-of-type':    function(nodes, formula, root) {
      return Selector.pseudos.nth(nodes, "1", root, false, true);
    },
    'last-of-type':     function(nodes, formula, root) {
      return Selector.pseudos.nth(nodes, "1", root, true, true);
    },
    'only-of-type':     function(nodes, formula, root) {
      var p = Selector.pseudos;
      return p['last-of-type'](p['first-of-type'](nodes, formula, root), formula, root);
    },

    getIndices: function(a, b, total) {
      if (a == 0) return b > 0 ? [b] : [];
      return $R(1, total).inject([], function(memo, i) {
        if (0 == (i - b) % a && (i - b) / a >= 0) memo.push(i);
        return memo;
      });
    },

    nth: function(nodes, formula, root, reverse, ofType) {
      if (nodes.length == 0) return [];
      if (formula == 'even') formula = '2n+0';
      if (formula == 'odd')  formula = '2n+1';
      var h = Selector.handlers, results = [], indexed = [], m;
      h.mark(nodes);
      for (var i = 0, node; node = nodes[i]; i++) {
        if (!node.parentNode._countedByPrototype) {
          h.index(node.parentNode, reverse, ofType);
          indexed.push(node.parentNode);
        }
      }
      if (formula.match(/^\d+$/)) { // just a number
        formula = Number(formula);
        for (var i = 0, node; node = nodes[i]; i++)
          if (node.nodeIndex == formula) results.push(node);
      } else if (m = formula.match(/^(-?\d*)?n(([+-])(\d+))?/)) { // an+b
        if (m[1] == "-") m[1] = -1;
        var a = m[1] ? Number(m[1]) : 1;
        var b = m[2] ? Number(m[2]) : 0;
        var indices = Selector.pseudos.getIndices(a, b, nodes.length);
        for (var i = 0, node, l = indices.length; node = nodes[i]; i++) {
          for (var j = 0; j < l; j++)
            if (node.nodeIndex == indices[j]) results.push(node);
        }
      }
      h.unmark(nodes);
      h.unmark(indexed);
      return results;
    },

    'empty': function(nodes, value, root) {
      for (var i = 0, results = [], node; node = nodes[i]; i++) {
        if (node.tagName == '!' || node.firstChild) continue;
        results.push(node);
      }
      return results;
    },

    'not': function(nodes, selector, root) {
      var h = Selector.handlers, selectorType, m;
      var exclusions = new Selector(selector).findElements(root);
      h.mark(exclusions);
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        if (!node._countedByPrototype) results.push(node);
      h.unmark(exclusions);
      return results;
    },

    'enabled': function(nodes, value, root) {
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        if (!node.disabled && (!node.type || node.type !== 'hidden'))
          results.push(node);
      return results;
    },

    'disabled': function(nodes, value, root) {
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        if (node.disabled) results.push(node);
      return results;
    },

    'checked': function(nodes, value, root) {
      for (var i = 0, results = [], node; node = nodes[i]; i++)
        if (node.checked) results.push(node);
      return results;
    }
  },

  operators: {
    '=':  function(nv, v) { return nv == v; },
    '!=': function(nv, v) { return nv != v; },
    '^=': function(nv, v) { return nv == v || nv && nv.startsWith(v); },
    '$=': function(nv, v) { return nv == v || nv && nv.endsWith(v); },
    '*=': function(nv, v) { return nv == v || nv && nv.include(v); },
    '~=': function(nv, v) { return (' ' + nv + ' ').include(' ' + v + ' '); },
    '|=': function(nv, v) { return ('-' + (nv || "").toUpperCase() +
     '-').include('-' + (v || "").toUpperCase() + '-'); }
  },

  split: function(expression) {
    var expressions = [];
    expression.scan(/(([\w#:.~>+()\s-]+|\*|\[.*?\])+)\s*(,|$)/, function(m) {
      expressions.push(m[1].strip());
    });
    return expressions;
  },

  matchElements: function(elements, expression) {
    var matches = $$(expression), h = Selector.handlers;
    h.mark(matches);
    for (var i = 0, results = [], element; element = elements[i]; i++)
      if (element._countedByPrototype) results.push(element);
    h.unmark(matches);
    return results;
  },

  findElement: function(elements, expression, index) {
    if (Object.isNumber(expression)) {
      index = expression; expression = false;
    }
    return Selector.matchElements(elements, expression || '*')[index || 0];
  },

  findChildElements: function(element, expressions) {
    expressions = Selector.split(expressions.join(','));
    var results = [], h = Selector.handlers;
    for (var i = 0, l = expressions.length, selector; i < l; i++) {
      selector = new Selector(expressions[i].strip());
      h.concat(results, selector.findElements(element));
    }
    return (l > 1) ? h.unique(results) : results;
  }
});

if (Prototype.Browser.IE) {
  Object.extend(Selector.handlers, {
    concat: function(a, b) {
      for (var i = 0, node; node = b[i]; i++)
        if (node.tagName !== "!") a.push(node);
      return a;
    }
  });
}

function $$() {
  return Selector.findChildElements(document, $A(arguments));
}

var Form = {
  reset: function(form) {
    form = $(form);
    form.reset();
    return form;
  },

  serializeElements: function(elements, options) {
    if (typeof options != 'object') options = { hash: !!options };
    else if (Object.isUndefined(options.hash)) options.hash = true;
    var key, value, submitted = false, submit = options.submit;

    var data = elements.inject({ }, function(result, element) {
      if (!element.disabled && element.name) {
        key = element.name; value = $(element).getValue();
        if (value != null && element.type != 'file' && (element.type != 'submit' || (!submitted &&
            submit !== false && (!submit || key == submit) && (submitted = true)))) {
          if (key in result) {
            if (!Object.isArray(result[key])) result[key] = [result[key]];
            result[key].push(value);
          }
          else result[key] = value;
        }
      }
      return result;
    });

    return options.hash ? data : Object.toQueryString(data);
  }
};

Form.Methods = {
  serialize: function(form, options) {
    return Form.serializeElements(Form.getElements(form), options);
  },

  getElements: function(form) {
    var elements = $(form).getElementsByTagName('*'),
        element,
        arr = [ ],
        serializers = Form.Element.Serializers;
    for (var i = 0; element = elements[i]; i++) {
      arr.push(element);
    }
    return arr.inject([], function(elements, child) {
      if (serializers[child.tagName.toLowerCase()])
        elements.push(Element.extend(child));
      return elements;
    })
  },

  getInputs: function(form, typeName, name) {
    form = $(form);
    var inputs = form.getElementsByTagName('input');

    if (!typeName && !name) return $A(inputs).map(Element.extend);

    for (var i = 0, matchingInputs = [], length = inputs.length; i < length; i++) {
      var input = inputs[i];
      if ((typeName && input.type != typeName) || (name && input.name != name))
        continue;
      matchingInputs.push(Element.extend(input));
    }

    return matchingInputs;
  },

  disable: function(form) {
    form = $(form);
    Form.getElements(form).invoke('disable');
    return form;
  },

  enable: function(form) {
    form = $(form);
    Form.getElements(form).invoke('enable');
    return form;
  },

  findFirstElement: function(form) {
    var elements = $(form).getElements().findAll(function(element) {
      return 'hidden' != element.type && !element.disabled;
    });
    var firstByIndex = elements.findAll(function(element) {
      return element.hasAttribute('tabIndex') && element.tabIndex >= 0;
    }).sortBy(function(element) { return element.tabIndex }).first();

    return firstByIndex ? firstByIndex : elements.find(function(element) {
      return /^(?:input|select|textarea)$/i.test(element.tagName);
    });
  },

  focusFirstElement: function(form) {
    form = $(form);
    form.findFirstElement().activate();
    return form;
  },

  request: function(form, options) {
    form = $(form), options = Object.clone(options || { });

    var params = options.parameters, action = form.readAttribute('action') || '';
    if (action.blank()) action = window.location.href;
    options.parameters = form.serialize(true);

    if (params) {
      if (Object.isString(params)) params = params.toQueryParams();
      Object.extend(options.parameters, params);
    }

    if (form.hasAttribute('method') && !options.method)
      options.method = form.method;

    return new Ajax.Request(action, options);
  }
};

/*--------------------------------------------------------------------------*/


Form.Element = {
  focus: function(element) {
    $(element).focus();
    return element;
  },

  select: function(element) {
    $(element).select();
    return element;
  }
};

Form.Element.Methods = {

  serialize: function(element) {
    element = $(element);
    if (!element.disabled && element.name) {
      var value = element.getValue();
      if (value != undefined) {
        var pair = { };
        pair[element.name] = value;
        return Object.toQueryString(pair);
      }
    }
    return '';
  },

  getValue: function(element) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    return Form.Element.Serializers[method](element);
  },

  setValue: function(element, value) {
    element = $(element);
    var method = element.tagName.toLowerCase();
    Form.Element.Serializers[method](element, value);
    return element;
  },

  clear: function(element) {
    $(element).value = '';
    return element;
  },

  present: function(element) {
    return $(element).value != '';
  },

  activate: function(element) {
    element = $(element);
    try {
      element.focus();
      if (element.select && (element.tagName.toLowerCase() != 'input' ||
          !(/^(?:button|reset|submit)$/i.test(element.type))))
        element.select();
    } catch (e) { }
    return element;
  },

  disable: function(element) {
    element = $(element);
    element.disabled = true;
    return element;
  },

  enable: function(element) {
    element = $(element);
    element.disabled = false;
    return element;
  }
};

/*--------------------------------------------------------------------------*/

var Field = Form.Element;

var $F = Form.Element.Methods.getValue;

/*--------------------------------------------------------------------------*/

Form.Element.Serializers = {
  input: function(element, value) {
    switch (element.type.toLowerCase()) {
      case 'checkbox':
      case 'radio':
        return Form.Element.Serializers.inputSelector(element, value);
      default:
        return Form.Element.Serializers.textarea(element, value);
    }
  },

  inputSelector: function(element, value) {
    if (Object.isUndefined(value)) return element.checked ? element.value : null;
    else element.checked = !!value;
  },

  textarea: function(element, value) {
    if (Object.isUndefined(value)) return element.value;
    else element.value = value;
  },

  select: function(element, value) {
    if (Object.isUndefined(value))
      return this[element.type == 'select-one' ?
        'selectOne' : 'selectMany'](element);
    else {
      var opt, currentValue, single = !Object.isArray(value);
      for (var i = 0, length = element.length; i < length; i++) {
        opt = element.options[i];
        currentValue = this.optionValue(opt);
        if (single) {
          if (currentValue == value) {
            opt.selected = true;
            return;
          }
        }
        else opt.selected = value.include(currentValue);
      }
    }
  },

  selectOne: function(element) {
    var index = element.selectedIndex;
    return index >= 0 ? this.optionValue(element.options[index]) : null;
  },

  selectMany: function(element) {
    var values, length = element.length;
    if (!length) return null;

    for (var i = 0, values = []; i < length; i++) {
      var opt = element.options[i];
      if (opt.selected) values.push(this.optionValue(opt));
    }
    return values;
  },

  optionValue: function(opt) {
    return Element.extend(opt).hasAttribute('value') ? opt.value : opt.text;
  }
};

/*--------------------------------------------------------------------------*/


Abstract.TimedObserver = Class.create(PeriodicalExecuter, {
  initialize: function($super, element, frequency, callback) {
    $super(callback, frequency);
    this.element   = $(element);
    this.lastValue = this.getValue();
  },

  execute: function() {
    var value = this.getValue();
    if (Object.isString(this.lastValue) && Object.isString(value) ?
        this.lastValue != value : String(this.lastValue) != String(value)) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  }
});

Form.Element.Observer = Class.create(Abstract.TimedObserver, {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.Observer = Class.create(Abstract.TimedObserver, {
  getValue: function() {
    return Form.serialize(this.element);
  }
});

/*--------------------------------------------------------------------------*/

Abstract.EventObserver = Class.create({
  initialize: function(element, callback) {
    this.element  = $(element);
    this.callback = callback;

    this.lastValue = this.getValue();
    if (this.element.tagName.toLowerCase() == 'form')
      this.registerFormCallbacks();
    else
      this.registerCallback(this.element);
  },

  onElementEvent: function() {
    var value = this.getValue();
    if (this.lastValue != value) {
      this.callback(this.element, value);
      this.lastValue = value;
    }
  },

  registerFormCallbacks: function() {
    Form.getElements(this.element).each(this.registerCallback, this);
  },

  registerCallback: function(element) {
    if (element.type) {
      switch (element.type.toLowerCase()) {
        case 'checkbox':
        case 'radio':
          Event.observe(element, 'click', this.onElementEvent.bind(this));
          break;
        default:
          Event.observe(element, 'change', this.onElementEvent.bind(this));
          break;
      }
    }
  }
});

Form.Element.EventObserver = Class.create(Abstract.EventObserver, {
  getValue: function() {
    return Form.Element.getValue(this.element);
  }
});

Form.EventObserver = Class.create(Abstract.EventObserver, {
  getValue: function() {
    return Form.serialize(this.element);
  }
});
(function() {

  var Event = {
    KEY_BACKSPACE: 8,
    KEY_TAB:       9,
    KEY_RETURN:   13,
    KEY_ESC:      27,
    KEY_LEFT:     37,
    KEY_UP:       38,
    KEY_RIGHT:    39,
    KEY_DOWN:     40,
    KEY_DELETE:   46,
    KEY_HOME:     36,
    KEY_END:      35,
    KEY_PAGEUP:   33,
    KEY_PAGEDOWN: 34,
    KEY_INSERT:   45,

    cache: {}
  };

  var docEl = document.documentElement;
  var MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED = 'onmouseenter' in docEl
    && 'onmouseleave' in docEl;

  var _isButton;
  if (Prototype.Browser.IE) {
    var buttonMap = { 0: 1, 1: 4, 2: 2 };
    _isButton = function(event, code) {
      return event.button === buttonMap[code];
    };
  } else if (Prototype.Browser.WebKit) {
    _isButton = function(event, code) {
      switch (code) {
        case 0: return event.which == 1 && !event.metaKey;
        case 1: return event.which == 1 && event.metaKey;
        default: return false;
      }
    };
  } else {
    _isButton = function(event, code) {
      return event.which ? (event.which === code + 1) : (event.button === code);
    };
  }

  function isLeftClick(event)   { return _isButton(event, 0) }

  function isMiddleClick(event) { return _isButton(event, 1) }

  function isRightClick(event)  { return _isButton(event, 2) }

  function element(event) {
    event = Event.extend(event);

    var node = event.target, type = event.type,
     currentTarget = event.currentTarget;

    if (currentTarget && currentTarget.tagName) {
      if (type === 'load' || type === 'error' ||
        (type === 'click' && currentTarget.tagName.toLowerCase() === 'input'
          && currentTarget.type === 'radio'))
            node = currentTarget;
    }

    if (node.nodeType == Node.TEXT_NODE)
      node = node.parentNode;

    return Element.extend(node);
  }

  function findElement(event, expression) {
    var element = Event.element(event);
    if (!expression) return element;
    var elements = [element].concat(element.ancestors());
    return Selector.findElement(elements, expression, 0);
  }

  function pointer(event) {
    return { x: pointerX(event), y: pointerY(event) };
  }

  function pointerX(event) {
    var docElement = document.documentElement,
     body = document.body || { scrollLeft: 0 };

    return event.pageX || (event.clientX +
      (docElement.scrollLeft || body.scrollLeft) -
      (docElement.clientLeft || 0));
  }

  function pointerY(event) {
    var docElement = document.documentElement,
     body = document.body || { scrollTop: 0 };

    return  event.pageY || (event.clientY +
       (docElement.scrollTop || body.scrollTop) -
       (docElement.clientTop || 0));
  }


  function stop(event) {
    Event.extend(event);
    event.preventDefault();
    event.stopPropagation();

    event.stopped = true;
  }

  Event.Methods = {
    isLeftClick: isLeftClick,
    isMiddleClick: isMiddleClick,
    isRightClick: isRightClick,

    element: element,
    findElement: findElement,

    pointer: pointer,
    pointerX: pointerX,
    pointerY: pointerY,

    stop: stop
  };


  var methods = Object.keys(Event.Methods).inject({ }, function(m, name) {
    m[name] = Event.Methods[name].methodize();
    return m;
  });

  if (Prototype.Browser.IE) {
    function _relatedTarget(event) {
      var element;
      switch (event.type) {
        case 'mouseover': element = event.fromElement; break;
        case 'mouseout':  element = event.toElement;   break;
        default: return null;
      }
      return Element.extend(element);
    }

    Object.extend(methods, {
      stopPropagation: function() { this.cancelBubble = true },
      preventDefault:  function() { this.returnValue = false },
      inspect: function() { return '[object Event]' }
    });

    Event.extend = function(event, element) {
      if (!event) return false;
      if (event._extendedByPrototype) return event;

      event._extendedByPrototype = Prototype.emptyFunction;
      var pointer = Event.pointer(event);

      Object.extend(event, {
        target: event.srcElement || element,
        relatedTarget: _relatedTarget(event),
        pageX:  pointer.x,
        pageY:  pointer.y
      });

      return Object.extend(event, methods);
    };
  } else {
    Event.prototype = window.Event.prototype || document.createEvent('HTMLEvents').__proto__;
    Object.extend(Event.prototype, methods);
    Event.extend = Prototype.K;
  }

  function _createResponder(element, eventName, handler) {
    var registry = Element.retrieve(element, 'prototype_event_registry');

    if (Object.isUndefined(registry)) {
      CACHE.push(element);
      registry = Element.retrieve(element, 'prototype_event_registry', $H());
    }

    var respondersForEvent = registry.get(eventName);
    if (Object.isUndefined(respondersForEvent)) {
      respondersForEvent = [];
      registry.set(eventName, respondersForEvent);
    }

    if (respondersForEvent.pluck('handler').include(handler)) return false;

    var responder;
    if (eventName.include(":")) {
      responder = function(event) {
        if (Object.isUndefined(event.eventName))
          return false;

        if (event.eventName !== eventName)
          return false;

        Event.extend(event, element);
        handler.call(element, event);
      };
    } else {
      if (!MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED &&
       (eventName === "mouseenter" || eventName === "mouseleave")) {
        if (eventName === "mouseenter" || eventName === "mouseleave") {
          responder = function(event) {
            Event.extend(event, element);

            var parent = event.relatedTarget;
            while (parent && parent !== element) {
              try { parent = parent.parentNode; }
              catch(e) { parent = element; }
            }

            if (parent === element) return;

            handler.call(element, event);
          };
        }
      } else {
        responder = function(event) {
          Event.extend(event, element);
          handler.call(element, event);
        };
      }
    }

    responder.handler = handler;
    respondersForEvent.push(responder);
    return responder;
  }

  function _destroyCache() {
    for (var i = 0, length = CACHE.length; i < length; i++) {
      Event.stopObserving(CACHE[i]);
      CACHE[i] = null;
    }
  }

  var CACHE = [];

  if (Prototype.Browser.IE)
    window.attachEvent('onunload', _destroyCache);

  if (Prototype.Browser.WebKit)
    window.addEventListener('unload', Prototype.emptyFunction, false);


  var _getDOMEventName = Prototype.K;

  if (!MOUSEENTER_MOUSELEAVE_EVENTS_SUPPORTED) {
    _getDOMEventName = function(eventName) {
      var translations = { mouseenter: "mouseover", mouseleave: "mouseout" };
      return eventName in translations ? translations[eventName] : eventName;
    };
  }

  function observe(element, eventName, handler) {
    element = $(element);

    var responder = _createResponder(element, eventName, handler);

    if (!responder) return element;

    if (eventName.include(':')) {
      if (element.addEventListener)
        element.addEventListener("dataavailable", responder, false);
      else {
        element.attachEvent("ondataavailable", responder);
        element.attachEvent("onfilterchange", responder);
      }
    } else {
      var actualEventName = _getDOMEventName(eventName);

      if (element.addEventListener)
        element.addEventListener(actualEventName, responder, false);
      else
        element.attachEvent("on" + actualEventName, responder);
    }

    return element;
  }

  function stopObserving(element, eventName, handler) {
    element = $(element);

    var registry = Element.retrieve(element, 'prototype_event_registry');

    if (Object.isUndefined(registry)) return element;

    if (eventName && !handler) {
      var responders = registry.get(eventName);

      if (Object.isUndefined(responders)) return element;

      responders.each( function(r) {
        Element.stopObserving(element, eventName, r.handler);
      });
      return element;
    } else if (!eventName) {
      registry.each( function(pair) {
        var eventName = pair.key, responders = pair.value;

        responders.each( function(r) {
          Element.stopObserving(element, eventName, r.handler);
        });
      });
      return element;
    }

    var responders = registry.get(eventName);

    if (!responders) return;

    var responder = responders.find( function(r) { return r.handler === handler; });
    if (!responder) return element;

    var actualEventName = _getDOMEventName(eventName);

    if (eventName.include(':')) {
      if (element.removeEventListener)
        element.removeEventListener("dataavailable", responder, false);
      else {
        element.detachEvent("ondataavailable", responder);
        element.detachEvent("onfilterchange",  responder);
      }
    } else {
      if (element.removeEventListener)
        element.removeEventListener(actualEventName, responder, false);
      else
        element.detachEvent('on' + actualEventName, responder);
    }

    registry.set(eventName, responders.without(responder));

    return element;
  }

  function fire(element, eventName, memo, bubble) {
    element = $(element);

    if (Object.isUndefined(bubble))
      bubble = true;

    if (element == document && document.createEvent && !element.dispatchEvent)
      element = document.documentElement;

    var event;
    if (document.createEvent) {
      event = document.createEvent('HTMLEvents');
      event.initEvent('dataavailable', true, true);
    } else {
      event = document.createEventObject();
      event.eventType = bubble ? 'ondataavailable' : 'onfilterchange';
    }

    event.eventName = eventName;
    event.memo = memo || { };

    if (document.createEvent)
      element.dispatchEvent(event);
    else
      element.fireEvent(event.eventType, event);

    return Event.extend(event);
  }


  Object.extend(Event, Event.Methods);

  Object.extend(Event, {
    fire:          fire,
    observe:       observe,
    stopObserving: stopObserving
  });

  Element.addMethods({
    fire:          fire,

    observe:       observe,

    stopObserving: stopObserving
  });

  Object.extend(document, {
    fire:          fire.methodize(),

    observe:       observe.methodize(),

    stopObserving: stopObserving.methodize(),

    loaded:        false
  });

  if (window.Event) Object.extend(window.Event, Event);
  else window.Event = Event;
})();

(function() {
  /* Support for the DOMContentLoaded event is based on work by Dan Webb,
     Matthias Miller, Dean Edwards, John Resig, and Diego Perini. */

  var timer;

  function fireContentLoadedEvent() {
    if (document.loaded) return;
    if (timer) window.clearTimeout(timer);
    document.loaded = true;
    document.fire('dom:loaded');
  }

  function checkReadyState() {
    if (document.readyState === 'complete') {
      document.stopObserving('readystatechange', checkReadyState);
      fireContentLoadedEvent();
    }
  }

  function pollDoScroll() {
    try { document.documentElement.doScroll('left'); }
    catch(e) {
      timer = pollDoScroll.defer();
      return;
    }
    fireContentLoadedEvent();
  }

  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fireContentLoadedEvent, false);
  } else {
    document.observe('readystatechange', checkReadyState);
    if (window == top)
      timer = pollDoScroll.defer();
  }

  Event.observe(window, 'load', fireContentLoadedEvent);
})();

Element.addMethods();

/*------------------------------- DEPRECATED -------------------------------*/

Hash.toQueryString = Object.toQueryString;

var Toggle = { display: Element.toggle };

Element.Methods.childOf = Element.Methods.descendantOf;

var Insertion = {
  Before: function(element, content) {
    return Element.insert(element, {before:content});
  },

  Top: function(element, content) {
    return Element.insert(element, {top:content});
  },

  Bottom: function(element, content) {
    return Element.insert(element, {bottom:content});
  },

  After: function(element, content) {
    return Element.insert(element, {after:content});
  }
};

var $continue = new Error('"throw $continue" is deprecated, use "return" instead');

var Position = {
  includeScrollOffsets: false,

  prepare: function() {
    this.deltaX =  window.pageXOffset
                || document.documentElement.scrollLeft
                || document.body.scrollLeft
                || 0;
    this.deltaY =  window.pageYOffset
                || document.documentElement.scrollTop
                || document.body.scrollTop
                || 0;
  },

  within: function(element, x, y) {
    if (this.includeScrollOffsets)
      return this.withinIncludingScrolloffsets(element, x, y);
    this.xcomp = x;
    this.ycomp = y;
    this.offset = Element.cumulativeOffset(element);

    return (y >= this.offset[1] &&
            y <  this.offset[1] + element.offsetHeight &&
            x >= this.offset[0] &&
            x <  this.offset[0] + element.offsetWidth);
  },

  withinIncludingScrolloffsets: function(element, x, y) {
    var offsetcache = Element.cumulativeScrollOffset(element);

    this.xcomp = x + offsetcache[0] - this.deltaX;
    this.ycomp = y + offsetcache[1] - this.deltaY;
    this.offset = Element.cumulativeOffset(element);

    return (this.ycomp >= this.offset[1] &&
            this.ycomp <  this.offset[1] + element.offsetHeight &&
            this.xcomp >= this.offset[0] &&
            this.xcomp <  this.offset[0] + element.offsetWidth);
  },

  overlap: function(mode, element) {
    if (!mode) return 0;
    if (mode == 'vertical')
      return ((this.offset[1] + element.offsetHeight) - this.ycomp) /
        element.offsetHeight;
    if (mode == 'horizontal')
      return ((this.offset[0] + element.offsetWidth) - this.xcomp) /
        element.offsetWidth;
  },


  cumulativeOffset: Element.Methods.cumulativeOffset,

  positionedOffset: Element.Methods.positionedOffset,

  absolutize: function(element) {
    Position.prepare();
    return Element.absolutize(element);
  },

  relativize: function(element) {
    Position.prepare();
    return Element.relativize(element);
  },

  realOffset: Element.Methods.cumulativeScrollOffset,

  offsetParent: Element.Methods.getOffsetParent,

  page: Element.Methods.viewportOffset,

  clone: function(source, target, options) {
    options = options || { };
    return Element.clonePosition(target, source, options);
  }
};

/*--------------------------------------------------------------------------*/

if (!document.getElementsByClassName) document.getElementsByClassName = function(instanceMethods){
  function iter(name) {
    return name.blank() ? null : "[contains(concat(' ', @class, ' '), ' " + name + " ')]";
  }

  instanceMethods.getElementsByClassName = Prototype.BrowserFeatures.XPath ?
  function(element, className) {
    className = className.toString().strip();
    var cond = /\s/.test(className) ? $w(className).map(iter).join('') : iter(className);
    return cond ? document._getElementsByXPath('.//*' + cond, element) : [];
  } : function(element, className) {
    className = className.toString().strip();
    var elements = [], classNames = (/\s/.test(className) ? $w(className) : null);
    if (!classNames && !className) return elements;

    var nodes = $(element).getElementsByTagName('*');
    className = ' ' + className + ' ';

    for (var i = 0, child, cn; child = nodes[i]; i++) {
      if (child.className && (cn = ' ' + child.className + ' ') && (cn.include(className) ||
          (classNames && classNames.all(function(name) {
            return !name.toString().blank() && cn.include(' ' + name + ' ');
          }))))
        elements.push(Element.extend(child));
    }
    return elements;
  };

  return function(className, parentElement) {
    return $(parentElement || document.body).getElementsByClassName(className);
  };
}(Element.Methods);

/*--------------------------------------------------------------------------*/

Element.ClassNames = Class.create();
Element.ClassNames.prototype = {
  initialize: function(element) {
    this.element = $(element);
  },

  _each: function(iterator) {
    this.element.className.split(/\s+/).select(function(name) {
      return name.length > 0;
    })._each(iterator);
  },

  set: function(className) {
    this.element.className = className;
  },

  add: function(classNameToAdd) {
    if (this.include(classNameToAdd)) return;
    this.set($A(this).concat(classNameToAdd).join(' '));
  },

  remove: function(classNameToRemove) {
    if (!this.include(classNameToRemove)) return;
    this.set($A(this).without(classNameToRemove).join(' '));
  },

  toString: function() {
    return $A(this).join(' ');
  }
};

Object.extend(Element.ClassNames.prototype, Enumerable);

/*--------------------------------------------------------------------------*/


// script.aculo.us scriptaculous.js v1.8.3, Thu Oct 08 11:23:33 +0200 2009

// Copyright (c) 2005-2009 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
// LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
// OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
//
// For details, see the script.aculo.us web site: http://script.aculo.us/

var Scriptaculous = {
  Version: '1.8.3',
  require: function(libraryName) {
    try{
      // inserting via DOM fails in Safari 2.0, so brute force approach
      document.write('<script type="text/javascript" src="'+libraryName+'"><\/script>');
    } catch(e) {
      // for xhtml+xml served content, fall back to DOM methods
      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = libraryName;
      document.getElementsByTagName('head')[0].appendChild(script);
    }
  },
  REQUIRED_PROTOTYPE: '1.6.0.3',
  load: function() {
    function convertVersionString(versionString) {
      var v = versionString.replace(/_.*|\./g, '');
      v = parseInt(v + '0'.times(4-v.length));
      return versionString.indexOf('_') > -1 ? v-1 : v;
    }

    if((typeof Prototype=='undefined') ||
       (typeof Element == 'undefined') ||
       (typeof Element.Methods=='undefined') ||
       (convertVersionString(Prototype.Version) <
        convertVersionString(Scriptaculous.REQUIRED_PROTOTYPE)))
       throw("script.aculo.us requires the Prototype JavaScript framework >= " +
        Scriptaculous.REQUIRED_PROTOTYPE);

    /*var js = /scriptaculous\.js(\?.*)?$/;
    $$('head script[src]').findAll(function(s) {
      return s.src.match(js);
    }).each(function(s) {
      var path = s.src.replace(js, ''),
      includes = s.src.match(/\?.*load=([a-z,]*)/);
      (includes ? includes[1] : 'builder,effects,dragdrop,controls,slider,sound').split(',').each(
       function(include) { Scriptaculous.require(path+include+'.js') });
    });*/
  }
};

Scriptaculous.load();

// script.aculo.us effects.js v1.8.3, Thu Oct 08 11:23:33 +0200 2009

// Copyright (c) 2005-2009 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
// Contributors:
//  Justin Palmer (http://encytemedia.com/)
//  Mark Pilgrim (http://diveintomark.org/)
//  Martin Bialasinki
//
// script.aculo.us is freely distributable under the terms of an MIT-style license.
// For details, see the script.aculo.us web site: http://script.aculo.us/

// converts rgb() and #xxx to #xxxxxx format,
// returns self (or first argument) if not convertable
String.prototype.parseColor = function() {
  var color = '#';
  if (this.slice(0,4) == 'rgb(') {
    var cols = this.slice(4,this.length-1).split(',');
    var i=0; do { color += parseInt(cols[i]).toColorPart() } while (++i<3);
  } else {
    if (this.slice(0,1) == '#') {
      if (this.length==4) for(var i=1;i<4;i++) color += (this.charAt(i) + this.charAt(i)).toLowerCase();
      if (this.length==7) color = this.toLowerCase();
    }
  }
  return (color.length==7 ? color : (arguments[0] || this));
};

/*--------------------------------------------------------------------------*/

Element.collectTextNodes = function(element) {
  return $A($(element).childNodes).collect( function(node) {
    return (node.nodeType==3 ? node.nodeValue :
      (node.hasChildNodes() ? Element.collectTextNodes(node) : ''));
  }).flatten().join('');
};

Element.collectTextNodesIgnoreClass = function(element, className) {
  return $A($(element).childNodes).collect( function(node) {
    return (node.nodeType==3 ? node.nodeValue :
      ((node.hasChildNodes() && !Element.hasClassName(node,className)) ?
        Element.collectTextNodesIgnoreClass(node, className) : ''));
  }).flatten().join('');
};

Element.setContentZoom = function(element, percent) {
  element = $(element);
  element.setStyle({fontSize: (percent/100) + 'em'});
  if (Prototype.Browser.WebKit) window.scrollBy(0,0);
  return element;
};

Element.getInlineOpacity = function(element){
  return $(element).style.opacity || '';
};

Element.forceRerendering = function(element) {
  try {
    element = $(element);
    var n = document.createTextNode(' ');
    element.appendChild(n);
    element.removeChild(n);
  } catch(e) { }
};

/*--------------------------------------------------------------------------*/

var Effect = {
  _elementDoesNotExistError: {
    name: 'ElementDoesNotExistError',
    message: 'The specified DOM element does not exist, but is required for this effect to operate'
  },
  Transitions: {
    linear: Prototype.K,
    sinoidal: function(pos) {
      return (-Math.cos(pos*Math.PI)/2) + .5;
    },
    reverse: function(pos) {
      return 1-pos;
    },
    flicker: function(pos) {
      var pos = ((-Math.cos(pos*Math.PI)/4) + .75) + Math.random()/4;
      return pos > 1 ? 1 : pos;
    },
    wobble: function(pos) {
      return (-Math.cos(pos*Math.PI*(9*pos))/2) + .5;
    },
    pulse: function(pos, pulses) {
      return (-Math.cos((pos*((pulses||5)-.5)*2)*Math.PI)/2) + .5;
    },
    spring: function(pos) {
      return 1 - (Math.cos(pos * 4.5 * Math.PI) * Math.exp(-pos * 6));
    },
    none: function(pos) {
      return 0;
    },
    full: function(pos) {
      return 1;
    }
  },
  DefaultOptions: {
    duration:   1.0,   // seconds
    fps:        100,   // 100= assume 66fps max.
    sync:       false, // true for combining
    from:       0.0,
    to:         1.0,
    delay:      0.0,
    queue:      'parallel'
  },
  tagifyText: function(element) {
    var tagifyStyle = 'position:relative';
    if (Prototype.Browser.IE) tagifyStyle += ';zoom:1';

    element = $(element);
    $A(element.childNodes).each( function(child) {
      if (child.nodeType==3) {
        child.nodeValue.toArray().each( function(character) {
          element.insertBefore(
            new Element('span', {style: tagifyStyle}).update(
              character == ' ' ? String.fromCharCode(160) : character),
              child);
        });
        Element.remove(child);
      }
    });
  },
  multiple: function(element, effect) {
    var elements;
    if (((typeof element == 'object') ||
        Object.isFunction(element)) &&
       (element.length))
      elements = element;
    else
      elements = $(element).childNodes;

    var options = Object.extend({
      speed: 0.1,
      delay: 0.0
    }, arguments[2] || { });
    var masterDelay = options.delay;

    $A(elements).each( function(element, index) {
      new effect(element, Object.extend(options, { delay: index * options.speed + masterDelay }));
    });
  },
  PAIRS: {
    'slide':  ['SlideDown','SlideUp'],
    'blind':  ['BlindDown','BlindUp'],
    'appear': ['Appear','Fade']
  },
  toggle: function(element, effect, options) {
    element = $(element);
    effect  = (effect || 'appear').toLowerCase();
    
    return Effect[ Effect.PAIRS[ effect ][ element.visible() ? 1 : 0 ] ](element, Object.extend({
      queue: { position:'end', scope:(element.id || 'global'), limit: 1 }
    }, options || {}));
  }
};

Effect.DefaultOptions.transition = Effect.Transitions.sinoidal;

/* ------------- core effects ------------- */

Effect.ScopedQueue = Class.create(Enumerable, {
  initialize: function() {
    this.effects  = [];
    this.interval = null;
  },
  _each: function(iterator) {
    this.effects._each(iterator);
  },
  add: function(effect) {
    var timestamp = new Date().getTime();

    var position = Object.isString(effect.options.queue) ?
      effect.options.queue : effect.options.queue.position;

    switch(position) {
      case 'front':
        // move unstarted effects after this effect
        this.effects.findAll(function(e){ return e.state=='idle' }).each( function(e) {
            e.startOn  += effect.finishOn;
            e.finishOn += effect.finishOn;
          });
        break;
      case 'with-last':
        timestamp = this.effects.pluck('startOn').max() || timestamp;
        break;
      case 'end':
        // start effect after last queued effect has finished
        timestamp = this.effects.pluck('finishOn').max() || timestamp;
        break;
    }

    effect.startOn  += timestamp;
    effect.finishOn += timestamp;

    if (!effect.options.queue.limit || (this.effects.length < effect.options.queue.limit))
      this.effects.push(effect);

    if (!this.interval)
      this.interval = setInterval(this.loop.bind(this), 15);
  },
  remove: function(effect) {
    this.effects = this.effects.reject(function(e) { return e==effect });
    if (this.effects.length == 0) {
      clearInterval(this.interval);
      this.interval = null;
    }
  },
  loop: function() {
    var timePos = new Date().getTime();
    for(var i=0, len=this.effects.length;i<len;i++)
      this.effects[i] && this.effects[i].loop(timePos);
  }
});

Effect.Queues = {
  instances: $H(),
  get: function(queueName) {
    if (!Object.isString(queueName)) return queueName;

    return this.instances.get(queueName) ||
      this.instances.set(queueName, new Effect.ScopedQueue());
  }
};
Effect.Queue = Effect.Queues.get('global');

Effect.Base = Class.create({
  position: null,
  start: function(options) {
    if (options && options.transition === false) options.transition = Effect.Transitions.linear;
    this.options      = Object.extend(Object.extend({ },Effect.DefaultOptions), options || { });
    this.currentFrame = 0;
    this.state        = 'idle';
    this.startOn      = this.options.delay*1000;
    this.finishOn     = this.startOn+(this.options.duration*1000);
    this.fromToDelta  = this.options.to-this.options.from;
    this.totalTime    = this.finishOn-this.startOn;
    this.totalFrames  = this.options.fps*this.options.duration;

    this.render = (function() {
      function dispatch(effect, eventName) {
        if (effect.options[eventName + 'Internal'])
          effect.options[eventName + 'Internal'](effect);
        if (effect.options[eventName])
          effect.options[eventName](effect);
      }

      return function(pos) {
        if (this.state === "idle") {
          this.state = "running";
          dispatch(this, 'beforeSetup');
          if (this.setup) this.setup();
          dispatch(this, 'afterSetup');
        }
        if (this.state === "running") {
          pos = (this.options.transition(pos) * this.fromToDelta) + this.options.from;
          this.position = pos;
          dispatch(this, 'beforeUpdate');
          if (this.update) this.update(pos);
          dispatch(this, 'afterUpdate');
        }
      };
    })();

    this.event('beforeStart');
    if (!this.options.sync)
      Effect.Queues.get(Object.isString(this.options.queue) ?
        'global' : this.options.queue.scope).add(this);
  },
  loop: function(timePos) {
    if (timePos >= this.startOn) {
      if (timePos >= this.finishOn) {
        this.render(1.0);
        this.cancel();
        this.event('beforeFinish');
        if (this.finish) this.finish();
        this.event('afterFinish');
        return;
      }
      var pos   = (timePos - this.startOn) / this.totalTime,
          frame = (pos * this.totalFrames).round();
      if (frame > this.currentFrame) {
        this.render(pos);
        this.currentFrame = frame;
      }
    }
  },
  cancel: function() {
    if (!this.options.sync)
      Effect.Queues.get(Object.isString(this.options.queue) ?
        'global' : this.options.queue.scope).remove(this);
    this.state = 'finished';
  },
  event: function(eventName) {
    if (this.options[eventName + 'Internal']) this.options[eventName + 'Internal'](this);
    if (this.options[eventName]) this.options[eventName](this);
  },
  inspect: function() {
    var data = $H();
    for(property in this)
      if (!Object.isFunction(this[property])) data.set(property, this[property]);
    return '#<Effect:' + data.inspect() + ',options:' + $H(this.options).inspect() + '>';
  }
});

Effect.Parallel = Class.create(Effect.Base, {
  initialize: function(effects) {
    this.effects = effects || [];
    this.start(arguments[1]);
  },
  update: function(position) {
    this.effects.invoke('render', position);
  },
  finish: function(position) {
    this.effects.each( function(effect) {
      effect.render(1.0);
      effect.cancel();
      effect.event('beforeFinish');
      if (effect.finish) effect.finish(position);
      effect.event('afterFinish');
    });
  }
});

Effect.Tween = Class.create(Effect.Base, {
  initialize: function(object, from, to) {
    object = Object.isString(object) ? $(object) : object;
    var args = $A(arguments), method = args.last(),
      options = args.length == 5 ? args[3] : null;
    this.method = Object.isFunction(method) ? method.bind(object) :
      Object.isFunction(object[method]) ? object[method].bind(object) :
      function(value) { object[method] = value };
    this.start(Object.extend({ from: from, to: to }, options || { }));
  },
  update: function(position) {
    this.method(position);
  }
});

Effect.Event = Class.create(Effect.Base, {
  initialize: function() {
    this.start(Object.extend({ duration: 0 }, arguments[0] || { }));
  },
  update: Prototype.emptyFunction
});

Effect.Opacity = Class.create(Effect.Base, {
  initialize: function(element) {
    this.element = $(element);
    if (!this.element) throw(Effect._elementDoesNotExistError);
    // make this work on IE on elements without 'layout'
    if (Prototype.Browser.IE && (!this.element.currentStyle.hasLayout))
      this.element.setStyle({zoom: 1});
    var options = Object.extend({
      from: this.element.getOpacity() || 0.0,
      to:   1.0
    }, arguments[1] || { });
    this.start(options);
  },
  update: function(position) {
    this.element.setOpacity(position);
  }
});

Effect.Move = Class.create(Effect.Base, {
  initialize: function(element) {
    this.element = $(element);
    if (!this.element) throw(Effect._elementDoesNotExistError);
    var options = Object.extend({
      x:    0,
      y:    0,
      mode: 'relative'
    }, arguments[1] || { });
    this.start(options);
  },
  setup: function() {
    this.element.makePositioned();
    this.originalLeft = parseFloat(this.element.getStyle('left') || '0');
    this.originalTop  = parseFloat(this.element.getStyle('top')  || '0');
    if (this.options.mode == 'absolute') {
      this.options.x = this.options.x - this.originalLeft;
      this.options.y = this.options.y - this.originalTop;
    }
  },
  update: function(position) {
    this.element.setStyle({
      left: (this.options.x  * position + this.originalLeft).round() + 'px',
      top:  (this.options.y  * position + this.originalTop).round()  + 'px'
    });
  }
});

// for backwards compatibility
Effect.MoveBy = function(element, toTop, toLeft) {
  return new Effect.Move(element,
    Object.extend({ x: toLeft, y: toTop }, arguments[3] || { }));
};

Effect.Scale = Class.create(Effect.Base, {
  initialize: function(element, percent) {
    this.element = $(element);
    if (!this.element) throw(Effect._elementDoesNotExistError);
    var options = Object.extend({
      scaleX: true,
      scaleY: true,
      scaleContent: true,
      scaleFromCenter: false,
      scaleMode: 'box',        // 'box' or 'contents' or { } with provided values
      scaleFrom: 100.0,
      scaleTo:   percent
    }, arguments[2] || { });
    this.start(options);
  },
  setup: function() {
    this.restoreAfterFinish = this.options.restoreAfterFinish || false;
    this.elementPositioning = this.element.getStyle('position');

    this.originalStyle = { };
    ['top','left','width','height','fontSize'].each( function(k) {
      this.originalStyle[k] = this.element.style[k];
    }.bind(this));

    this.originalTop  = this.element.offsetTop;
    this.originalLeft = this.element.offsetLeft;

    var fontSize = this.element.getStyle('font-size') || '100%';
    ['em','px','%','pt'].each( function(fontSizeType) {
      if (fontSize.indexOf(fontSizeType)>0) {
        this.fontSize     = parseFloat(fontSize);
        this.fontSizeType = fontSizeType;
      }
    }.bind(this));

    this.factor = (this.options.scaleTo - this.options.scaleFrom)/100;

    this.dims = null;
    if (this.options.scaleMode=='box')
      this.dims = [this.element.offsetHeight, this.element.offsetWidth];
    if (/^content/.test(this.options.scaleMode))
      this.dims = [this.element.scrollHeight, this.element.scrollWidth];
    if (!this.dims)
      this.dims = [this.options.scaleMode.originalHeight,
                   this.options.scaleMode.originalWidth];
  },
  update: function(position) {
    var currentScale = (this.options.scaleFrom/100.0) + (this.factor * position);
    if (this.options.scaleContent && this.fontSize)
      this.element.setStyle({fontSize: this.fontSize * currentScale + this.fontSizeType });
    this.setDimensions(this.dims[0] * currentScale, this.dims[1] * currentScale);
  },
  finish: function(position) {
    if (this.restoreAfterFinish) this.element.setStyle(this.originalStyle);
  },
  setDimensions: function(height, width) {
    var d = { };
    if (this.options.scaleX) d.width = width.round() + 'px';
    if (this.options.scaleY) d.height = height.round() + 'px';
    if (this.options.scaleFromCenter) {
      var topd  = (height - this.dims[0])/2;
      var leftd = (width  - this.dims[1])/2;
      if (this.elementPositioning == 'absolute') {
        if (this.options.scaleY) d.top = this.originalTop-topd + 'px';
        if (this.options.scaleX) d.left = this.originalLeft-leftd + 'px';
      } else {
        if (this.options.scaleY) d.top = -topd + 'px';
        if (this.options.scaleX) d.left = -leftd + 'px';
      }
    }
    this.element.setStyle(d);
  }
});

Effect.Highlight = Class.create(Effect.Base, {
  initialize: function(element) {
    this.element = $(element);
    if (!this.element) throw(Effect._elementDoesNotExistError);
    var options = Object.extend({ startcolor: '#ffff99' }, arguments[1] || { });
    this.start(options);
  },
  setup: function() {
    // Prevent executing on elements not in the layout flow
    if (this.element.getStyle('display')=='none') { this.cancel(); return; }
    // Disable background image during the effect
    this.oldStyle = { };
    if (!this.options.keepBackgroundImage) {
      this.oldStyle.backgroundImage = this.element.getStyle('background-image');
      this.element.setStyle({backgroundImage: 'none'});
    }
    if (!this.options.endcolor)
      this.options.endcolor = this.element.getStyle('background-color').parseColor('#ffffff');
    if (!this.options.restorecolor)
      this.options.restorecolor = this.element.getStyle('background-color');
    // init color calculations
    this._base  = $R(0,2).map(function(i){ return parseInt(this.options.startcolor.slice(i*2+1,i*2+3),16) }.bind(this));
    this._delta = $R(0,2).map(function(i){ return parseInt(this.options.endcolor.slice(i*2+1,i*2+3),16)-this._base[i] }.bind(this));
  },
  update: function(position) {
    this.element.setStyle({backgroundColor: $R(0,2).inject('#',function(m,v,i){
      return m+((this._base[i]+(this._delta[i]*position)).round().toColorPart()); }.bind(this)) });
  },
  finish: function() {
    this.element.setStyle(Object.extend(this.oldStyle, {
      backgroundColor: this.options.restorecolor
    }));
  }
});

Effect.ScrollTo = function(element) {
  var options = arguments[1] || { },
  scrollOffsets = document.viewport.getScrollOffsets(),
  elementOffsets = $(element).cumulativeOffset();

  if (options.offset) elementOffsets[1] += options.offset;

  return new Effect.Tween(null,
    scrollOffsets.top,
    elementOffsets[1],
    options,
    function(p){ scrollTo(scrollOffsets.left, p.round()); }
  );
};

/* ------------- combination effects ------------- */

Effect.Fade = function(element) {
  element = $(element);
  var oldOpacity = element.getInlineOpacity();
  var options = Object.extend({
    from: element.getOpacity() || 1.0,
    to:   0.0,
    afterFinishInternal: function(effect) {
      if (effect.options.to!=0) return;
      effect.element.hide().setStyle({opacity: oldOpacity});
    }
  }, arguments[1] || { });
  return new Effect.Opacity(element,options);
};

Effect.Appear = function(element) {
  element = $(element);
  var options = Object.extend({
  from: (element.getStyle('display') == 'none' ? 0.0 : element.getOpacity() || 0.0),
  to:   1.0,
  // force Safari to render floated elements properly
  afterFinishInternal: function(effect) {
    effect.element.forceRerendering();
  },
  beforeSetup: function(effect) {
    effect.element.setOpacity(effect.options.from).show();
  }}, arguments[1] || { });
  return new Effect.Opacity(element,options);
};

Effect.Puff = function(element) {
  element = $(element);
  var oldStyle = {
    opacity: element.getInlineOpacity(),
    position: element.getStyle('position'),
    top:  element.style.top,
    left: element.style.left,
    width: element.style.width,
    height: element.style.height
  };
  return new Effect.Parallel(
   [ new Effect.Scale(element, 200,
      { sync: true, scaleFromCenter: true, scaleContent: true, restoreAfterFinish: true }),
     new Effect.Opacity(element, { sync: true, to: 0.0 } ) ],
     Object.extend({ duration: 1.0,
      beforeSetupInternal: function(effect) {
        Position.absolutize(effect.effects[0].element);
      },
      afterFinishInternal: function(effect) {
         effect.effects[0].element.hide().setStyle(oldStyle); }
     }, arguments[1] || { })
   );
};

Effect.BlindUp = function(element) {
  element = $(element);
  element.makeClipping();
  return new Effect.Scale(element, 0,
    Object.extend({ scaleContent: false,
      scaleX: false,
      restoreAfterFinish: true,
      afterFinishInternal: function(effect) {
        effect.element.hide().undoClipping();
      }
    }, arguments[1] || { })
  );
};

Effect.BlindDown = function(element) {
  element = $(element);
  var elementDimensions = element.getDimensions();
  return new Effect.Scale(element, 100, Object.extend({
    scaleContent: false,
    scaleX: false,
    scaleFrom: 0,
    scaleMode: {originalHeight: elementDimensions.height, originalWidth: elementDimensions.width},
    restoreAfterFinish: true,
    afterSetup: function(effect) {
      effect.element.makeClipping().setStyle({height: '0px'}).show();
    },
    afterFinishInternal: function(effect) {
      effect.element.undoClipping();
    }
  }, arguments[1] || { }));
};

Effect.SwitchOff = function(element) {
  element = $(element);
  var oldOpacity = element.getInlineOpacity();
  return new Effect.Appear(element, Object.extend({
    duration: 0.4,
    from: 0,
    transition: Effect.Transitions.flicker,
    afterFinishInternal: function(effect) {
      new Effect.Scale(effect.element, 1, {
        duration: 0.3, scaleFromCenter: true,
        scaleX: false, scaleContent: false, restoreAfterFinish: true,
        beforeSetup: function(effect) {
          effect.element.makePositioned().makeClipping();
        },
        afterFinishInternal: function(effect) {
          effect.element.hide().undoClipping().undoPositioned().setStyle({opacity: oldOpacity});
        }
      });
    }
  }, arguments[1] || { }));
};

Effect.DropOut = function(element) {
  element = $(element);
  var oldStyle = {
    top: element.getStyle('top'),
    left: element.getStyle('left'),
    opacity: element.getInlineOpacity() };
  return new Effect.Parallel(
    [ new Effect.Move(element, {x: 0, y: 100, sync: true }),
      new Effect.Opacity(element, { sync: true, to: 0.0 }) ],
    Object.extend(
      { duration: 0.5,
        beforeSetup: function(effect) {
          effect.effects[0].element.makePositioned();
        },
        afterFinishInternal: function(effect) {
          effect.effects[0].element.hide().undoPositioned().setStyle(oldStyle);
        }
      }, arguments[1] || { }));
};

Effect.Shake = function(element) {
  element = $(element);
  var options = Object.extend({
    distance: 20,
    duration: 0.5
  }, arguments[1] || {});
  var distance = parseFloat(options.distance);
  var split = parseFloat(options.duration) / 10.0;
  var oldStyle = {
    top: element.getStyle('top'),
    left: element.getStyle('left') };
    return new Effect.Move(element,
      { x:  distance, y: 0, duration: split, afterFinishInternal: function(effect) {
    new Effect.Move(effect.element,
      { x: -distance*2, y: 0, duration: split*2,  afterFinishInternal: function(effect) {
    new Effect.Move(effect.element,
      { x:  distance*2, y: 0, duration: split*2,  afterFinishInternal: function(effect) {
    new Effect.Move(effect.element,
      { x: -distance*2, y: 0, duration: split*2,  afterFinishInternal: function(effect) {
    new Effect.Move(effect.element,
      { x:  distance*2, y: 0, duration: split*2,  afterFinishInternal: function(effect) {
    new Effect.Move(effect.element,
      { x: -distance, y: 0, duration: split, afterFinishInternal: function(effect) {
        effect.element.undoPositioned().setStyle(oldStyle);
  }}); }}); }}); }}); }}); }});
};

Effect.SlideDown = function(element) {
  element = $(element).cleanWhitespace();
  // SlideDown need to have the content of the element wrapped in a container element with fixed height!
  var oldInnerBottom = element.down().getStyle('bottom');
  var elementDimensions = element.getDimensions();
  return new Effect.Scale(element, 100, Object.extend({
    scaleContent: false,
    scaleX: false,
    scaleFrom: window.opera ? 0 : 1,
    scaleMode: {originalHeight: elementDimensions.height, originalWidth: elementDimensions.width},
    restoreAfterFinish: true,
    afterSetup: function(effect) {
      effect.element.makePositioned();
      effect.element.down().makePositioned();
      if (window.opera) effect.element.setStyle({top: ''});
      effect.element.makeClipping().setStyle({height: '0px'}).show();
    },
    afterUpdateInternal: function(effect) {
      effect.element.down().setStyle({bottom:
        (effect.dims[0] - effect.element.clientHeight) + 'px' });
    },
    afterFinishInternal: function(effect) {
      effect.element.undoClipping().undoPositioned();
      effect.element.down().undoPositioned().setStyle({bottom: oldInnerBottom}); }
    }, arguments[1] || { })
  );
};

Effect.SlideUp = function(element) {
  element = $(element).cleanWhitespace();
  var oldInnerBottom = element.down().getStyle('bottom');
  var elementDimensions = element.getDimensions();
  return new Effect.Scale(element, window.opera ? 0 : 1,
   Object.extend({ scaleContent: false,
    scaleX: false,
    scaleMode: 'box',
    scaleFrom: 100,
    scaleMode: {originalHeight: elementDimensions.height, originalWidth: elementDimensions.width},
    restoreAfterFinish: true,
    afterSetup: function(effect) {
      effect.element.makePositioned();
      effect.element.down().makePositioned();
      if (window.opera) effect.element.setStyle({top: ''});
      effect.element.makeClipping().show();
    },
    afterUpdateInternal: function(effect) {
      effect.element.down().setStyle({bottom:
        (effect.dims[0] - effect.element.clientHeight) + 'px' });
    },
    afterFinishInternal: function(effect) {
      effect.element.hide().undoClipping().undoPositioned();
      effect.element.down().undoPositioned().setStyle({bottom: oldInnerBottom});
    }
   }, arguments[1] || { })
  );
};

// Bug in opera makes the TD containing this element expand for a instance after finish
Effect.Squish = function(element) {
  return new Effect.Scale(element, window.opera ? 1 : 0, {
    restoreAfterFinish: true,
    beforeSetup: function(effect) {
      effect.element.makeClipping();
    },
    afterFinishInternal: function(effect) {
      effect.element.hide().undoClipping();
    }
  });
};

Effect.Grow = function(element) {
  element = $(element);
  var options = Object.extend({
    direction: 'center',
    moveTransition: Effect.Transitions.sinoidal,
    scaleTransition: Effect.Transitions.sinoidal,
    opacityTransition: Effect.Transitions.full
  }, arguments[1] || { });
  var oldStyle = {
    top: element.style.top,
    left: element.style.left,
    height: element.style.height,
    width: element.style.width,
    opacity: element.getInlineOpacity() };

  var dims = element.getDimensions();
  var initialMoveX, initialMoveY;
  var moveX, moveY;

  switch (options.direction) {
    case 'top-left':
      initialMoveX = initialMoveY = moveX = moveY = 0;
      break;
    case 'top-right':
      initialMoveX = dims.width;
      initialMoveY = moveY = 0;
      moveX = -dims.width;
      break;
    case 'bottom-left':
      initialMoveX = moveX = 0;
      initialMoveY = dims.height;
      moveY = -dims.height;
      break;
    case 'bottom-right':
      initialMoveX = dims.width;
      initialMoveY = dims.height;
      moveX = -dims.width;
      moveY = -dims.height;
      break;
    case 'center':
      initialMoveX = dims.width / 2;
      initialMoveY = dims.height / 2;
      moveX = -dims.width / 2;
      moveY = -dims.height / 2;
      break;
  }

  return new Effect.Move(element, {
    x: initialMoveX,
    y: initialMoveY,
    duration: 0.01,
    beforeSetup: function(effect) {
      effect.element.hide().makeClipping().makePositioned();
    },
    afterFinishInternal: function(effect) {
      new Effect.Parallel(
        [ new Effect.Opacity(effect.element, { sync: true, to: 1.0, from: 0.0, transition: options.opacityTransition }),
          new Effect.Move(effect.element, { x: moveX, y: moveY, sync: true, transition: options.moveTransition }),
          new Effect.Scale(effect.element, 100, {
            scaleMode: { originalHeight: dims.height, originalWidth: dims.width },
            sync: true, scaleFrom: window.opera ? 1 : 0, transition: options.scaleTransition, restoreAfterFinish: true})
        ], Object.extend({
             beforeSetup: function(effect) {
               effect.effects[0].element.setStyle({height: '0px'}).show();
             },
             afterFinishInternal: function(effect) {
               effect.effects[0].element.undoClipping().undoPositioned().setStyle(oldStyle);
             }
           }, options)
      );
    }
  });
};

Effect.Shrink = function(element) {
  element = $(element);
  var options = Object.extend({
    direction: 'center',
    moveTransition: Effect.Transitions.sinoidal,
    scaleTransition: Effect.Transitions.sinoidal,
    opacityTransition: Effect.Transitions.none
  }, arguments[1] || { });
  var oldStyle = {
    top: element.style.top,
    left: element.style.left,
    height: element.style.height,
    width: element.style.width,
    opacity: element.getInlineOpacity() };

  var dims = element.getDimensions();
  var moveX, moveY;

  switch (options.direction) {
    case 'top-left':
      moveX = moveY = 0;
      break;
    case 'top-right':
      moveX = dims.width;
      moveY = 0;
      break;
    case 'bottom-left':
      moveX = 0;
      moveY = dims.height;
      break;
    case 'bottom-right':
      moveX = dims.width;
      moveY = dims.height;
      break;
    case 'center':
      moveX = dims.width / 2;
      moveY = dims.height / 2;
      break;
  }

  return new Effect.Parallel(
    [ new Effect.Opacity(element, { sync: true, to: 0.0, from: 1.0, transition: options.opacityTransition }),
      new Effect.Scale(element, window.opera ? 1 : 0, { sync: true, transition: options.scaleTransition, restoreAfterFinish: true}),
      new Effect.Move(element, { x: moveX, y: moveY, sync: true, transition: options.moveTransition })
    ], Object.extend({
         beforeStartInternal: function(effect) {
           effect.effects[0].element.makePositioned().makeClipping();
         },
         afterFinishInternal: function(effect) {
           effect.effects[0].element.hide().undoClipping().undoPositioned().setStyle(oldStyle); }
       }, options)
  );
};

Effect.Pulsate = function(element) {
  element = $(element);
  var options    = arguments[1] || { },
    oldOpacity = element.getInlineOpacity(),
    transition = options.transition || Effect.Transitions.linear,
    reverser   = function(pos){
      return 1 - transition((-Math.cos((pos*(options.pulses||5)*2)*Math.PI)/2) + .5);
    };

  return new Effect.Opacity(element,
    Object.extend(Object.extend({  duration: 2.0, from: 0,
      afterFinishInternal: function(effect) { effect.element.setStyle({opacity: oldOpacity}); }
    }, options), {transition: reverser}));
};

Effect.Fold = function(element) {
  element = $(element);
  var oldStyle = {
    top: element.style.top,
    left: element.style.left,
    width: element.style.width,
    height: element.style.height };
  element.makeClipping();
  return new Effect.Scale(element, 5, Object.extend({
    scaleContent: false,
    scaleX: false,
    afterFinishInternal: function(effect) {
    new Effect.Scale(element, 1, {
      scaleContent: false,
      scaleY: false,
      afterFinishInternal: function(effect) {
        effect.element.hide().undoClipping().setStyle(oldStyle);
      } });
  }}, arguments[1] || { }));
};

Effect.Morph = Class.create(Effect.Base, {
  initialize: function(element) {
    this.element = $(element);
    if (!this.element) throw(Effect._elementDoesNotExistError);
    var options = Object.extend({
      style: { }
    }, arguments[1] || { });

    if (!Object.isString(options.style)) this.style = $H(options.style);
    else {
      if (options.style.include(':'))
        this.style = options.style.parseStyle();
      else {
        this.element.addClassName(options.style);
        this.style = $H(this.element.getStyles());
        this.element.removeClassName(options.style);
        var css = this.element.getStyles();
        this.style = this.style.reject(function(style) {
          return style.value == css[style.key];
        });
        options.afterFinishInternal = function(effect) {
          effect.element.addClassName(effect.options.style);
          effect.transforms.each(function(transform) {
            effect.element.style[transform.style] = '';
          });
        };
      }
    }
    this.start(options);
  },

  setup: function(){
    function parseColor(color){
      if (!color || ['rgba(0, 0, 0, 0)','transparent'].include(color)) color = '#ffffff';
      color = color.parseColor();
      return $R(0,2).map(function(i){
        return parseInt( color.slice(i*2+1,i*2+3), 16 );
      });
    }
    this.transforms = this.style.map(function(pair){
      var property = pair[0], value = pair[1], unit = null;

      if (value.parseColor('#zzzzzz') != '#zzzzzz') {
        value = value.parseColor();
        unit  = 'color';
      } else if (property == 'opacity') {
        value = parseFloat(value);
        if (Prototype.Browser.IE && (!this.element.currentStyle.hasLayout))
          this.element.setStyle({zoom: 1});
      } else if (Element.CSS_LENGTH.test(value)) {
          var components = value.match(/^([\+\-]?[0-9\.]+)(.*)$/);
          value = parseFloat(components[1]);
          unit = (components.length == 3) ? components[2] : null;
      }

      var originalValue = this.element.getStyle(property);
      return {
        style: property.camelize(),
        originalValue: unit=='color' ? parseColor(originalValue) : parseFloat(originalValue || 0),
        targetValue: unit=='color' ? parseColor(value) : value,
        unit: unit
      };
    }.bind(this)).reject(function(transform){
      return (
        (transform.originalValue == transform.targetValue) ||
        (
          transform.unit != 'color' &&
          (isNaN(transform.originalValue) || isNaN(transform.targetValue))
        )
      );
    });
  },
  update: function(position) {
    var style = { }, transform, i = this.transforms.length;
    while(i--)
      style[(transform = this.transforms[i]).style] =
        transform.unit=='color' ? '#'+
          (Math.round(transform.originalValue[0]+
            (transform.targetValue[0]-transform.originalValue[0])*position)).toColorPart() +
          (Math.round(transform.originalValue[1]+
            (transform.targetValue[1]-transform.originalValue[1])*position)).toColorPart() +
          (Math.round(transform.originalValue[2]+
            (transform.targetValue[2]-transform.originalValue[2])*position)).toColorPart() :
        (transform.originalValue +
          (transform.targetValue - transform.originalValue) * position).toFixed(3) +
            (transform.unit === null ? '' : transform.unit);
    this.element.setStyle(style, true);
  }
});

Effect.Transform = Class.create({
  initialize: function(tracks){
    this.tracks  = [];
    this.options = arguments[1] || { };
    this.addTracks(tracks);
  },
  addTracks: function(tracks){
    tracks.each(function(track){
      track = $H(track);
      var data = track.values().first();
      this.tracks.push($H({
        ids:     track.keys().first(),
        effect:  Effect.Morph,
        options: { style: data }
      }));
    }.bind(this));
    return this;
  },
  play: function(){
    return new Effect.Parallel(
      this.tracks.map(function(track){
        var ids = track.get('ids'), effect = track.get('effect'), options = track.get('options');
        var elements = [$(ids) || $$(ids)].flatten();
        return elements.map(function(e){ return new effect(e, Object.extend({ sync:true }, options)) });
      }).flatten(),
      this.options
    );
  }
});

Element.CSS_PROPERTIES = $w(
  'backgroundColor backgroundPosition borderBottomColor borderBottomStyle ' +
  'borderBottomWidth borderLeftColor borderLeftStyle borderLeftWidth ' +
  'borderRightColor borderRightStyle borderRightWidth borderSpacing ' +
  'borderTopColor borderTopStyle borderTopWidth bottom clip color ' +
  'fontSize fontWeight height left letterSpacing lineHeight ' +
  'marginBottom marginLeft marginRight marginTop markerOffset maxHeight '+
  'maxWidth minHeight minWidth opacity outlineColor outlineOffset ' +
  'outlineWidth paddingBottom paddingLeft paddingRight paddingTop ' +
  'right textIndent top width wordSpacing zIndex');

Element.CSS_LENGTH = /^(([\+\-]?[0-9\.]+)(em|ex|px|in|cm|mm|pt|pc|\%))|0$/;

String.__parseStyleElement = document.createElement('div');
String.prototype.parseStyle = function(){
  var style, styleRules = $H();
  if (Prototype.Browser.WebKit)
    style = new Element('div',{style:this}).style;
  else {
    String.__parseStyleElement.innerHTML = '<div style="' + this + '"></div>';
    style = String.__parseStyleElement.childNodes[0].style;
  }

  Element.CSS_PROPERTIES.each(function(property){
    if (style[property]) styleRules.set(property, style[property]);
  });

  if (Prototype.Browser.IE && this.include('opacity'))
    styleRules.set('opacity', this.match(/opacity:\s*((?:0|1)?(?:\.\d*)?)/)[1]);

  return styleRules;
};

if (document.defaultView && document.defaultView.getComputedStyle) {
  Element.getStyles = function(element) {
    var css = document.defaultView.getComputedStyle($(element), null);
    return Element.CSS_PROPERTIES.inject({ }, function(styles, property) {
      styles[property] = css[property];
      return styles;
    });
  };
} else {
  Element.getStyles = function(element) {
    element = $(element);
    var css = element.currentStyle, styles;
    styles = Element.CSS_PROPERTIES.inject({ }, function(results, property) {
      results[property] = css[property];
      return results;
    });
    if (!styles.opacity) styles.opacity = element.getOpacity();
    return styles;
  };
}

Effect.Methods = {
  morph: function(element, style) {
    element = $(element);
    new Effect.Morph(element, Object.extend({ style: style }, arguments[2] || { }));
    return element;
  },
  visualEffect: function(element, effect, options) {
    element = $(element);
    var s = effect.dasherize().camelize(), klass = s.charAt(0).toUpperCase() + s.substring(1);
    new Effect[klass](element, options);
    return element;
  },
  highlight: function(element, options) {
    element = $(element);
    new Effect.Highlight(element, options);
    return element;
  }
};

$w('fade appear grow shrink fold blindUp blindDown slideUp slideDown '+
  'pulsate shake puff squish switchOff dropOut').each(
  function(effect) {
    Effect.Methods[effect] = function(element, options){
      element = $(element);
      Effect[effect.charAt(0).toUpperCase() + effect.substring(1)](element, options);
      return element;
    };
  }
);

$w('getInlineOpacity forceRerendering setContentZoom collectTextNodes collectTextNodesIgnoreClass getStyles').each(
  function(f) { Effect.Methods[f] = Element[f]; }
);

Element.addMethods(Effect.Methods);

/*
 * e107 website system
 *
 * Copyright (C) 2001-2008 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * e107 Javascript API
 *
 * $Source: /cvsroot/e107/e107_0.8/e107_files/jslib/e107.js.php,v $
 * $Revision: 1.23 $
 * $Date: 2009/01/16 17:57:57 $
 * $Author: secretr $
 *
*/

var e107API = {
	Version: '1.0.1',
	ServerVersion: '0.8.1'
}

/*
 * Old stuff
 * FIXME ASAP
 */
var nowLocal = new Date();		/* time at very beginning of js execution */
var localTime = Math.floor(nowLocal.getTime()/1000);	/* time, in ms -- recorded at top of jscript */
/**
 * NOTE: if serverDelta is needed for js functions, you must pull it from
 * the cookie (as calculated during a previous page load!)
 * The value calculated in SyncWithServerTime is not known until after the
 * entire page has been processed.
 */
function SyncWithServerTime(serverTime)
{
	if (serverTime)
	{
	  	/* update time difference cookie */
		var serverDelta=Math.floor(localTime-serverTime);
	  	document.cookie = 'e107_tdOffset='+serverDelta+'; path=/';
	  	document.cookie = 'e107_tdSetTime='+(localTime-serverDelta)+'; path=/'; /* server time when set */
	}

	var tzCookie = 'e107_tzOffset=';
//	if (document.cookie.indexOf(tzCookie) < 0) {
		/* set if not already set */
		var timezoneOffset = nowLocal.getTimezoneOffset(); /* client-to-GMT in minutes */
		document.cookie = tzCookie + timezoneOffset+'; path=/';
//	}
}

// -------------------------------------------------------------------

/**
 * Prototype Xtensions
 * @author    Simon Martins
 * @copyright (c) 2008 Netatoo SARL <http://www.netatoo.fr>
 * @license   MIT License <http://www.prototypextensions.com/#main=license>
 *
 * @desc Retrieve the browser version
 */
(function() {
    var nav       = navigator;
    var userAgent = ua = navigator.userAgent;
    var v         = nav.appVersion;
    var version   = parseFloat(v);

    e107API.Browser = {
        IE      : (Prototype.Browser.IE)    ? parseFloat(v.split("MSIE ")[1]) || 0 : 0,
        Firefox : (Prototype.Browser.Gecko) ? parseFloat(ua.split("Firefox/")[1]) || 0 : 0,
        Camino  : (Prototype.Browser.Gecko) ? parseFloat(ua.split("Camino/")[1]) || 0 : 0,
        Flock   : (Prototype.Browser.Gecko) ? parseFloat(ua.split("Flock/")[1]) || 0 : 0,
        Opera   : (Prototype.Browser.Opera) ? version : 0,
        AIR     : (ua.indexOf("AdobeAIR") >= 0) ? 1 : 0,
        Mozilla : (Prototype.Browser.Gecko || !this.Khtml) ? version : 0,
        Khtml   : (v.indexOf("Konqueror") >= 0 && this.safari) ? version : 0,
        Safari  : (function() {
            var safari = Math.max(v.indexOf("WebKit"), v.indexOf("Safari"), 0);
            return (safari) ? (
                parseFloat(v.split("Version/")[1]) || ( ( parseFloat(v.substr(safari+7)) >= 419.3 ) ? 3 : 2 ) || 2
            ) : 0;
        })()
    };
})();

// -------------------------------------------------------------------

/**
 * Main registry object
 */
var e107Registry = {

    //System Path
    Path: {
        e_IMAGE:    '/e107_images/',
        e_COMPAT: '/e107_plugins/cl_widgets/jslib/compat/',
        e_COMPAT_IMAGE: '/e107_plugins/cl_widgets/jslib/compat/images/',
        e_IMAGE_PACK:    '/e107_images/',
        e_PLUGIN:   '/e107_plugins/',
        e_FILE:     '/e107_files/',
        e_ADMIN: 	'',
        e_THEME:    '/e107_themes/',
        THEME:      '/e107_themes/rael_responsive/'
    },

    //Language Constants
    Lan: {},

    //Global Templates
    Template: {
    	Core: {
    		//e107Helper#duplicateHTML method
	        duplicateHTML:	'<div><div class="clear"><!-- --></div>' +
                           		'#{duplicateBody}' +
                           		'<a href="#" id="#{removeId}"><img src="#{e_IMAGE_PACK}admin_images/delete_16.png" class="icon action" style="vertical-align: middle" /></a>' +
                           	'</div>'
    	},

    	//e107Helper#LoadingStatus class
    	CoreLoading:   {
    		template: 		'<div id="loading-mask">' +
								'<p id="loading-mask-loader" class="loader">' +
									'<img src="#{e_COMPAT_IMAGE}loading_32.gif" alt="#{JSLAN_CORE_LOADING_ALT}" />' +
									'<br /> <span class="loading-text">#{JSLAN_CORE_LOADING_TEXT}</span>' +
								'<p>' +
							'</div>'
    	}
    },

    //Cache
    Cache: new Hash,

    //Cached vars
    CachedVars: new Hash,

    //Global Preferences
    Pref: {
    	Core: {
    		zIndex: 5 //base system z-index
    	}
    }
}

// -------------------------------------------------------------------

/**
 * Global helpers - server side clonings
 */
var isset = function(varname) {
    return !Object.isUndefined(varname);
}

var varset = function(varname) {
    if(Object.isUndefined(varname)) {
        return (Object.isUndefined(arguments[1]) ? null : arguments[1]);
    }
    return varname;
}

var varsettrue = function(varname) {
    if(Object.isUndefined(varname) || !varname) {
        return (Object.isUndefined(arguments[1]) ? null : arguments[1]);
    }
    return varname;
}

var cachevars = function(varname, data) {
    e107Registry.CachedVars.set(data)
}

var getcachedvars = function(varname, destroy) {
	if(destroy)
		return clearcachedvars(varname);
    return e107Registry.CachedVars.get(varname);
}

var clearcachedvars = function(varname) {
    return e107Registry.CachedVars.unset(varname);
}

var echo = Prototype.emptyFunction, print_a = Prototype.emptyFunction, var_dump = Prototype.emptyFunction;

// -------------------------------------------------------------------


/**
 * e107 custom events
 */
var e107Event = {

    fire: function(eventName, memo, element) {
    	if ((!element || element == document) && !document.createEvent)
    	{
    		element = $(document.documentElement);
    	}
    	else
    		element = $(element) || document;
    	memo = memo || {};
    	return element.fire('e107:' + eventName, memo);
    },

    observe: function(eventName, handler, element) {
    	element = $(element) || document;
    	element.observe('e107:' + eventName, handler);
    	return this;
    },

    stopObserving: function(eventName, handler, element) {
    	element = $(element) || document;
    	element.stopObserving('e107:' + eventName, handler);
    	return this;
    },

    //Server side - e107_event aliases
    trigger: function(eventName, memo, element) {
    	this.fire(eventName, memo, element);
    },

    register: function(eventName, handler, element) {
    	this.observe(eventName, handler, element);
    },

    unregister:  function(eventName, handler, element) {
    	this.stopObserving(eventName, handler, element);
    }
}



/**
 * EventManager
 * Prototype Xtensions http://www.prototypextensions.com
 *
 * @desc Create custom events on your own class
 */
var e107EventManager = Class.create({

    /**
     * Initialize
     *
     * @desc Set scope and events hash
     */
    initialize: function(scope) {
        this.scope  = scope;
        this.events = new Hash();
    },

    /**
     * addListener
     *
     * @desc Add event observer
     */
    addObserver: function(name) {
        return this.events.set(name, new Hash());
    },

    /**
     * observe
     *
     * @desc Add a callback for listener 'name'
     */
    observe: function(name, callback) {
        var observers = this.events.get(name);

        if(!observers) observers = this.addObserver(name);

        if(!Object.isFunction(callback)) {
            //throw('e107EventManager.observe : callback must be an js function');
            //surpess error
            return this;
        }

        var i = this.events.get(name).keys().length;
        observers.set(i, callback.bind(this.scope));

        return this;
    },

    /**
     * stopObserving (class improvements)
     *
     * @desc Remove callback for listener 'name'
     */
    stopObserving: function(name, callback) {
        var observers = this.events.get(name);

        if(!observers) return this;
        observers.each( function(pair) {
        	if(pair.value == callback) {
        		observers.unset(pair.key);
        		$break;
        	}
        });
        return this;
    },

    /**
     * notify
     *
     * @desc Launch all callbacks for listener 'name'
     */
    notify: function(name) {
        var observers = this.events.get(name);
        //console.log('notifying ' + name);
        if(observers) {
            var args = $A(arguments).slice(1);
            //Fix - preserve order
            observers.keys().sort().each( function(key) {
            	var callback = observers.get(key);
                if(Object.isFunction(callback)) {
                    callback.apply(this.scope, args);
                }
            });
        }

        return this;
    }

});

// -------------------------------------------------------------------


/**
 * Base e107 Object - interacts with the registry object
 */
var e107Base = {

    setPath: function(path_object) {
        e107Registry.Path = Object.extend( this.getPathVars(), path_object || {});
        return this;
    },

    addPath: function(path_var, path) {
    	//don't allow overwrite
        if(!e107Registry.Path[path_var]) e107Registry.Path[path_var] = path;
        return this;
    },

    getPathVars: function() {
        return e107Registry.Path;
    },

    getPath: function(path_name) {
        return varset(e107Registry.Path[path_name]);
    },

    _addLan: function(lan_name, lan_value) {
        e107Registry.Lan[lan_name] = lan_value;
        return this;
    },

    _getLan: function(lan_name) {
        return varsettrue(e107Registry.Lan[lan_name], lan_name);
    },

    setLan: function(lan_object) {
    	if(!arguments[1]) {
	        Object.keys(lan_object).each(function(key) {
	            this.addLan(key, lan_object[key]);
	        }, this);
	        return this
    	}
        Object.extend(e107Registry.Lan, (lan_object || {}));
        return this;
    },

    addLan: function(lan_name, lan_value) {
        this._addLan(this.toLanName(lan_name), lan_value);
        return this;
    },

    setModLan: function(mod, lan_object) {
    	Object.keys(lan_object).each( function(key) {
    		this.addModLan(mod, key, lan_object[key]);
    	}, this);
    	return this;
    },

    addModLan: function(mod, lan_name, lan_value) {
        return this._addLan(this.toModLanName(mod, lan_name), lan_value);
    },

    getLan: function(lan_name) {
        return this._getLan(this.toLanName(lan_name));
    },

    getModLan: function(mod, lan_name) {
    	return this._getLan(this.toModLanName(mod, lan_name));
    },

    getLanVars: function() {
        return e107Registry.Lan;
    },

    getModLanVars: function(mod) {
    	return this.getLanFilter(this.toModLanName(mod));
    },

    //Example e107.getLanRange('lan1 lan2 ...'); -- { LAN1: 'lan value1', LAN2: 'lan value2', ... }
    getLanRange: function(lan_keys) {
        var ret = {};
        $w(lan_keys).each( function(key) {
            this[key.toUpperCase()] = e107.getLan(key);
        }, ret);
        return ret;
    },

    //Example e107.getLanFilter('lan_myplug'); -- { LAN_MYPLUG_1: 'lan value1', LAN_MYPLUG_2: 'lan value2', ... }
    getLanFilter: function(filter) {
        var ret = {};
        filter = filter.toUpperCase();
        $H(e107Registry.Lan).keys().each( function(key) {
            if(key.startsWith(filter)) {
                this[key] = e107Registry.Lan[key];
            }
        }, ret);

        return ret;
    },

    setTemplate: function(mod, tmpl_object) {
        mod = this.toModName(mod);
        if(!varset(e107Registry.Template[mod])) {
            e107Registry.Template[mod] = {};
        }
        Object.extend(e107Registry.Template[mod], (tmpl_object || {}));

        return this;
    },

    addTemplate: function(mod, name, tmpl_string) {
        mod = this.toModName(mod);
        if(!varset(e107Registry.Template[mod])) {
            e107Registry.Template[mod] = {};
        }
        e107Registry.Template[mod][name] = tmpl_string;

        return this;
    },

    getTemplates: function(mod) {
        return varsettrue(e107Registry.Template[this.toModName(mod)], {});
    },

    getTemplate: function(mod, name) {
        mod = this.toModName(mod);

        if(varset(e107Registry.Template[mod])) {
            return varsettrue(e107Registry.Template[mod][name], '');
        }

        return '';
    },

    setPrefs: function(mod, pref_object) {
        mod = this.toModName(mod);
        if(!varset(e107Registry.Pref[mod])) {
            e107Registry.Pref[mod] = {};
        }
        Object.extend(e107Registry.Pref[mod], (pref_object || {}));

        return this;
    },

    addPref: function(mod, pref_name, pref_value) {
        mod = this.toModName(mod);
        if(!varset(e107Registry.Pref[mod])) {
            e107Registry.Pref[mod] = {};
        }
        e107Registry.Pref[mod][pref_name] = pref_value;

        return this;
    },

    getPrefs: function(mod) {
        return varsettrue(e107Registry.Pref[this.toModName(mod)], {});
    },

    getPref: function(mod, pref_name, def) {
        mod = this.toModName(mod);
        if(varset(e107Registry.Pref[mod])) {
            return varsettrue(e107Registry.Pref[mod][pref_name], varset(def, null));
        }
        return varset(def, null);
    },

    setCache: function(cache_str, cache_item) {
    	this.clearCache(cache_str);
        e107Registry.Cache['cache-' + cache_str] = cache_item;
        return this;
    },

    getCache: function(cache_str, def) {
        return varset(e107Registry.Cache['cache-' + cache_str], def);
    },

    clearCache: function(cache_str, nodestroy) {
    	var cached = this.getCache(cache_str);
    	if(!nodestroy && cached && Object.isFunction(cached['destroy'])) cached.destroy();
    	e107Registry.Cache['cache-' + cache_str] = null;
    	delete e107Registry.Cache['cache-' + cache_str];
    	return this;
    },

    parseTemplate: function(mod, name, data) {
        var cacheStr = mod + '_' + name;
        var cached = this.getCache(cacheStr);
        if(null === cached) {
            var tmp = this.getTemplate(mod, name);
            cached = new Template(tmp);
            this.setCache(cacheStr, cached);
        }

        if(varsettrue(arguments[3])) {
            data = this.getParseData(Object.clone(data || {}));
        }

        try{
           return cached.evaluate(data || {});
        } catch(e) {
            return '';
        }
    },

    getParseData: function (data) {
        Object.extend(data || {},
          Object.extend(this.getLanVars(), this.getPathVars())
        );

        return data;
    },

    parseLan: function(str) {
        return String(str).interpolate(this.getLanVars());
    },

    parsePath: function(str) {
        return String(str).interpolate(this.getPathVars());
    },

    toModName: function(mod, raw) {
    	return raw ? mod.dasherize() : mod.dasherize().camelize().ucfirst();
    },

    toLanName: function(lan) {
    	return 'JSLAN_' + lan.underscore().toUpperCase();
    },

    toModLanName: function(raw_mod, lan) {
    	return this.toLanName(raw_mod + '_' + varset(lan, ''));
    }
};

// -------------------------------------------------------------------

/**
 * String Extensions
 *
 * Methods used later in the core + e107base shorthands
 */
Object.extend(String.prototype, {

	//php like
    ucfirst: function() {
        return this.charAt(0).toUpperCase() + this.substring(1);
    },

	//Create element from string - Prototype UI
	createElement: function() {
	    var wrapper = new Element('div'); wrapper.innerHTML = this;
	    return wrapper.down();
	},

	parseToElement: function(data) {
		return this.parseTemplate(data).createElement();
	},

	parseTemplate: function(data) {
		return this.interpolate(e107Base.getParseData(data || {}));
	},

	parsePath: function() {
		return e107Base.parsePath(this);
	},

	parseLan: function() {
		return e107Base.parseLan(this);
	},

    addLan: function(lan_name) {
    	if(lan_name)
        	e107Base.addLan(lan_name, this);
        return e107Base.toLanName(lan_name);
    },

    addModLan: function(mod, lan_name) {
    	if(mod && lan_name)
        	e107Base.addModLan(mod, lan_name, this);
        return e107Base.toModLanName(mod, lan_name);
    },

    getLan: function() {
        return e107Base.getLan(this);
    },

    getModLan: function(mod) {
    	if(mod)
    		return e107Base.getModLan(mod, this);
    	return this;
    }
});

// -------------------------------------------------------------------

/**
 * e107WidgetAbstract Class
 */
var e107WidgetAbstract = Class.create(e107Base);
var e107WidgetAbstract = Class.create(e107WidgetAbstract, {

    initMod: function(modId, options, inherit) {

        this.mod = e107Base.toModName(modId, true);
        if(!this.mod) {
            throw 'Illegal Mod ID';
        }

		var methods = 'setTemplate addTemplate getTemplate parseTemplate setPrefs addPref getPref getPrefs getLan getLanVars addLan setLan';
		var that = this;

		//Some magic
		$w(methods).each(function(method){
			var mod_method = method.gsub(/^(set|get|add|parse)(.*)$/, function(match){
				return match[1] + 'Mod' + match[2];
			});
			var parent_method = !e107Base[mod_method] ? method : mod_method;
			this[mod_method] = e107Base[parent_method].bind(this, this.mod);
		}.bind(that));

		Object.extend(that, {
			getModName: function(raw) {
				return raw ? this.mod : e107Base.toModName(this.mod);
			},

		    parseModLan: function(str) {
		        return String(str).interpolate(e107Base.getModLan(this.mod));
		    },

		    setModCache: function(cache_str, cache_item) {
		    	e107Base.setCache(this.getModName(true) + varsettrue(cache_str, ''), cache_item);
		    	return this;
		    },

		    getModCache: function(cache_str) {
		    	return e107Base.getCache(this.getModName(true) + varsettrue(cache_str, ''));
		    },

		    clearModCache: function(cache_str) {
		    	e107Base.clearCache(this.getModName(true) + varsettrue(cache_str, ''));
		    	return this;
		    }
		});

        //Merge option object (recursive)
        this.setOptions(options, inherit);

        return this;
    },


    setOptions: function(options, inherit) {
        this.options = {};

        var c = this.constructor;

        if (c.superclass && inherit) {
            var chain = [], klass = c;

            while (klass = klass.superclass)
                chain.push(klass);

            chain = chain.reverse();
            for (var i = 0, len = chain.length; i < len; i++) {
                if(!chain[i].getModPrefs) chain[i].getModPrefs = Prototype.emptyFunction;
                //global options if available
                Object.extend(this.options, chain[i].getModPrefs() || {});
            }
        }

        //global options if available
        if(!this.getModPrefs) { this.getModPrefs = Prototype.emptyFunction; }

        Object.extend(this.options, this.getModPrefs() || {});
        return Object.extend(this.options, options || {});
    }

});

// -------------------------------------------------------------------

/**
 * Core - everything's widget!
 */
var e107Core = Class.create(e107WidgetAbstract, {
    initialize: function() {
        this.initMod('core');
    },

    /**
     * e107:loaded Event observer
     */
    runOnLoad: function(handler, element, reload) {
    	e107Event.register('loaded', handler, element || document);
    	if(reload)
    		this.runOnReload(handler, element);

    	return this;
    },

    /**
     * Ajax after update Event observer
     */
    runOnReload: function(handler, element) {
    	e107Event.register('ajax_update_after', handler, element || document);
    	return this;
    }

});

//e107Core instance
var e107 = new e107Core();

// -------------------------------------------------------------------

/*
 * Widgets namespace
 * @descr should contain only high-level classes
 */
 var e107Widgets = {};

/**
 * Utils namespace
 * @descr contains low-level classes and non-widget high-level classes/objects
 */
var e107Utils = {}

/**
 * Helper namespace
 * @descr includes all old e107 functions + some new helper methods/classes
 */
var e107Helper = {
    fxToggle: function(el, fx) {
    	var opt = Object.extend( { effect: 'blind' , options: {duration: 0.5} }, fx || {});
        Effect.toggle(el, opt.effect, opt.options);
    }
}

// -------------------------------------------------------------------

/*
 * Element extension
 */
Element.addMethods( {
	fxToggle: function(element, options) {
	    e107Helper.fxToggle(element, options);
	}
});

// -------------------------------------------------------------------

/**
 * Backward compatibility
 */
Object.extend(e107Helper, {

	toggle: function(el) {
		var eltoggle;
		/**
		 * (SecretR) Notice
		 *
		 * Logic mismatch!
		 * Passed element/string should be always the target element (which will be toggled)
		 *  OR
		 * anchor: <a href="#some-id"> where 'some-id' is the id of the target element
		 * This method will be rewritten after the core is cleaned up. After this point
		 * the target element will be auto-hidden (no need of class="e-hideme")
		 */

        if(false === Object.isString(el) || (
        	($(el) && $(el).nodeName.toLowerCase() == 'a' && $(el).readAttribute('href'))
        		||
        	($(el) && $(el).readAttribute('type') && $(el).readAttribute('type').toLowerCase() == 'input') /* deprecated */
        )) {
        	eltoggle = (function(el) {
	    		return Try.these(
	    		    function() { var ret = $(el.readAttribute('href').substr(1));  if(ret) { return ret; } throw 'Error';}, //This will be the only valid case in the near future
                    function() { var ret = el.next('.e-expandme'); if(ret) { return ret; } throw 'Error';},// maybe this too?
                    function() { var ret = el.next('div'); if(ret) { return ret; } throw 'Error'; }, //backward compatibality - DEPRECATED
                    function() { return null; } //break
	    		) || false;
        	})($(el));
        } else {
            var eltoggle = $(el);
        }

        if(!eltoggle) return false;

		var fx = varset(arguments[1], null);

		if(false !== fx)
		    this.fxToggle(eltoggle, fx || {});
		else
		    $(eltoggle).toggle();

		return true;
	},

    /**
     * added as Element method below
     * No toggle effects!
     */
    downToggle: function(element, selector) {
    	$(element).select(varsettrue(selector, '.e-expandme')).invoke('toggle');
    	return element;
    },

	/**
	 * Event listener - e107:loaded|e107:ajax_update_after
	 * @see e107Core#addOnLoad
	 */
    toggleObserver: function(event) {
    	var element = event.memo['element'] ? $(event.memo.element) : $$('body')[0];
        Element.select(element, '.e-expandit').invoke('observe', 'click', function(e) {
            var element = e.findElement('a');
            if(!element) element = e.element();
            if(this.toggle(element, {})) e.stop();
        }.bindAsEventListener(e107Helper));
    },

    /**
     * Add fx scroll on click event
     * on all <a href='#something" class="scroll-to"> elements
     */
    scrollToObserver: function(event) {
    	var element = event.memo['element'] ? $(event.memo.element) : $$('body')[0];
		Element.select(element, 'a[href^=#].scroll-to:not([href=#])').invoke('observe', 'click', function(e) {
			new Effect.ScrollTo(e.element().hash.substr(1));
			e.stop();
		});
    },

    /**
     * added as Element method below
     */
    downHide: function(element, selector) {
    	$(element).select(varsettrue(selector, '.e-hideme')).invoke('hide');
    	return element;
    },

    /**
     * added as Element method below
     */
    downShow: function(element, selector) {
    	$(element).select(varsettrue(selector, '.e-hideme')).invoke('show');
    	return element;
    },

    //event listener
    autoHide: function(event) {
    	var hideunder = event.memo['element'] ? $(event.memo.element) : $$('body')[0];
        if(hideunder) hideunder.downHide();
    },

    /**
     * added as Element method below
     * autocomplete="off" - all major browsers except Opera(?!)
     */
    noHistory: function(element) {
        $(element).writeAttribute('autocomplete', 'off');
        return element;
    },

    /**
     * added as Element method below
     */
    downNoHistory: function(element, selector) {
    	$(element).select(varsettrue(selector, 'input.e-nohistory')).invoke('noHistory');
    	return element;
    },

    //event listener
    autoNoHistory: function(event) {
    	var down = event.memo['element'] ? $(event.memo.element) : $$('body')[0];
        if(down) down.downNoHistory();
    },

    /**
     * added as Element method below
     */
	externalLink: function (element) {
	    $(element).writeAttribute('target', '_blank');
	    return element;
	},

    /**
     * added as Element method below
     */
    downExternalLinks: function(element) {
    	$(element).select('a[rel~=external]').invoke('externalLink');
    	return element;
    },

    //event listener
	autoExternalLinks: function (event) {
		//event.element() works for IE now!
		//TODO - remove memo.element references
		//event.memo['element'] ? $(event.memo.element) : $$('body')[0];
		var down = event.element() != document ? event.element() : $$('body')[0];
	    if(down) down.downExternalLinks();
	},

	urlJump: function(url) {
	    top.window.location = url;
	},

	//TODO Widget - e107Window#confirm;
    confirm: function(thetext) {
    	return confirm(thetext);
    },

    autoConfirm: function(event) {

    },

	imagePreload: function(ejs_path, ejs_imageString) {
	    var ejs_imageArray = ejs_imageString.split(',');
	    for(var ejs_loadall = 0, len = ejs_imageArray.length; ejs_loadall < len; ejs_loadall++){
	        var ejs_LoadedImage = new Image();
	        ejs_LoadedImage.src=ejs_path + ejs_imageArray[ejs_loadall];
	    }
	},

	toggleChecked: function(form, state, selector, byId) {
		form = $(form); if(!form) { return; }
		if(byId) selector = 'id^=' + selector;
		$A(form.select('input[type=checkbox][' + selector + ']')).each(function(element) { if(!element.disabled) element.checked=state });
	},

	//This will be replaced later with upload_ui.php own JS method
	//and moved to a separate class
    __dupCounter: 1,
    __dupTmpTemplate: '',
	//FIXME
	duplicateHTML: function(copy, paste, baseid) {
        if(!$(copy) || !$(paste)) { return; }
        this.__dupCounter++;
        var source = $($(copy).cloneNode(true)), newentry, newid, containerId, clearB;

        source.writeAttribute('id', source.readAttribute('id') + this.__dupCounter);
        newid = (baseid || 'duplicated') + '-' + this.__dupCounter;

        var tmpl = this.getDuplicateTemplate();
        if(tmpl) {
        	var sourceInnerHTML = source.innerHTML;
        	source = source.update(tmpl.parseToElement({
                duplicateBody: sourceInnerHTML,
                removeId: 'remove-' + newid,
                baseId: baseid || '',
                newId: newid,
                counter: this.__dupCounter
            })).down().hide();
        	clearB = $(source.select('#remove-' + newid)[0]);
        } else {
        	//see clear, clearL and clearR CSS definitions
        	clearB = new Element('input', { 'class': 'button', 'value': 'x', 'type': 'button', 'id': 'remove-' + newid }); //backward compat. - subject of removal
        	source.insert({
        		top: new Element('div', {'class': 'clear'}),
        		bottom: clearB
        	}).hide();
        }
        if(baseid) {
            source.innerHTML = source.innerHTML.replace(new RegExp(baseid, 'g'), newid);
        }
        var containerId = source.identify();
        $(paste).insert(source);
        //Again - the EVIL IE6
        if(!clearB) { clearB = $('remove-' + newid); }

        clearB.observe('click', function(e) {
        	e.stop();
        	var el = e.element().up('#'+containerId);
	        el.fxToggle({
	            effect: 'appear',
	            options: {
	            	duration: 0.4,
	                afterFinish: function(o) { o.element.remove(); }
	            }
	        });
        }.bind(this));

        source.fxToggle({
        	effect: 'appear',
        	options: { duration: 0.5 }
        });
	},

    getDuplicateTemplate: function() {
    	if(this.__dupTmpTemplate) {
    		var tmpl = this.__dupTmpTemplate;
    		this.__dupTmpTemplate = '';
    		return tmpl;
    	}
    	return e107.getModTemplate('duplicateHTML');
    },

    setDuplicateTemplate: function(tmpl) {
        return this.__dupTmpTemplate = tmpl;
    },

	previewImage: function(src_val, img_path, not_found) {
	   $(src_val + '_prev').src = $(src_val).value ? img_path + $(src_val).value : not_found;
	    return;
	},

	insertText: function(str, tagid, display) {
	    $(tagid).value = str.escapeHTML();
	    if($(display)) {
	        $(display).fxToggle();
	    }
	},

	appendText: function(str, tagid, display) {
	    $(tagid).focus().value += str.escapeHTML();
	    if($(display)) {
	        $(display).fxToggle();
	    }
	},

	//by Lokesh Dhakar - http://www.lokeshdhakar.com
    getPageSize: function() {

	     var xScroll, yScroll;

		if (window.innerHeight && window.scrollMaxY) {
			xScroll = window.innerWidth + window.scrollMaxX;
			yScroll = window.innerHeight + window.scrollMaxY;
		} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
			xScroll = document.body.scrollWidth;
			yScroll = document.body.scrollHeight;
		} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
			xScroll = document.body.offsetWidth;
			yScroll = document.body.offsetHeight;
		}

		var windowWidth, windowHeight;

		if (self.innerHeight) {	// all except Explorer
			if(document.documentElement.clientWidth){
				windowWidth = document.documentElement.clientWidth;
			} else {
				windowWidth = self.innerWidth;
			}
			windowHeight = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
			windowWidth = document.documentElement.clientWidth;
			windowHeight = document.documentElement.clientHeight;
		} else if (document.body) { // other Explorers
			windowWidth = document.body.clientWidth;
			windowHeight = document.body.clientHeight;
		}

		// for small pages with total height less then height of the viewport
		if(yScroll < windowHeight){
			pageHeight = windowHeight;
		} else {
			pageHeight = yScroll;
		}

		// for small pages with total width less then width of the viewport
		if(xScroll < windowWidth){
			pageWidth = xScroll;
		} else {
			pageWidth = windowWidth;
		}

		return [pageWidth,pageHeight];
	}
});


// -------------------------------------------------------------------

/*
 * Element extensions
 */
Element.addMethods( {
	downNoHistory: e107Helper.downNoHistory,
	downHide: e107Helper.downHide,
	downShow: e107Helper.downShow,
	downToggle: e107Helper.downToggle,
	downExternalLinks: e107Helper.downExternalLinks
});

Element.addMethods('INPUT', {
	noHistory: e107Helper.noHistory
});

Element.addMethods('A', {
	externalLink: e107Helper.externalLink
});

Element.addMethods('FORM', {
	toggleChecked: e107Helper.toggleChecked
});

// -------------------------------------------------------------------

/**
 * e107BB helper
 */
e107Helper.BB = {

	__selectedInputArea: null,

	store: function(textAr){
	    this.__selectedInputArea = $(textAr);
	},

	/**
	 * New improved version - fixed scroll to top behaviour when inserting BBcodes
	 * @TODO - improve it further
	 */
	insert: function(text, emote) {
	    if (!this.__selectedInputArea) {
	    	return; //[SecretR] TODO - alert the user
	    }
	    var eField = this.__selectedInputArea, tags = this.parse(text, emote);
        if(this.insertIE(eField, text, tags)) return;

	    var scrollPos = eField.scrollTop, sel = (eField.value).substring(eField.selectionStart, eField.selectionEnd);
	    if (eField.selectionEnd <= 2 && typeof(eField.textLength) != 'undefined') {
	        eField.selectionEnd = eField.textLength;
	    }

	    var newStart = eField.selectionStart + tags.start.length + sel.length + tags.end.length;
	    eField.value = (eField.value).substring(0, eField.selectionStart) + tags.start + sel + tags.end + (eField.value).substring(eField.selectionEnd, eField.textLength);

	    eField.focus(); eField.selectionStart = newStart; eField.selectionEnd = newStart; eField.scrollTop = scrollPos;
	    return;

	},

	insertIE: function(area, text, tags) {
        // IE fix
        if (!document.selection) return false;
        var eSelection = document.selection.createRange().text;
        area.focus();
        if (eSelection) {
            document.selection.createRange().text = tags.start + eSelection + tags.end;
        } else {
            document.selection.createRange().text = tags.start + tags.end;
        }
        eSelection = ''; area.blur(); area.focus();
        return true;
	},

	parse: function(text, isEmote) {
		var tOpen = text, tClose = '';
        if (isEmote != true) {  // Split if its a paired bbcode
            var tmp = text.split('][', 2);
            tOpen = varset(tmp[1]) ? tmp[0] + ']' : text;
            tClose = varset(tmp[1]) ? '[' + tmp[1] : '';
        }
        return { start: tOpen, end: tClose };
	},

	//TODO VERY BAD - make it right ASAP!
	help_old: function(help, tagid, nohtml){
		if(nohtml) { help = help.escapeHTML(); }
		if($(tagid)) { $(tagid).value = help; }
		else if($('helpb')) {
			$('helpb').value = help;
		}
	},

	//FIXME - The new BB help system
	help: function(help, tagid, nohtml){
		if(nohtml) { help = help.escapeHTML(); }
		if(!$(tagid)) return;
		if(help) {
			var wrapper = new Element('div', {'style': 'position: relative'}).update(help);
			$(tagid).update(wrapper).fxToggle();
		} else {
			$(tagid).update('').fxToggle();
		}
	}
};

//Iframe Shim - from PrototypeUI
e107Utils.IframeShim = Class.create({
	initialize: function() {
		this.element = new Element('iframe',{
			style: 'position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);display:none;',
			src: 'javascript:void(0);',
			frameborder: 0
		});
		$(document.body).insert(this.element);
	},
	hide: function() {
		this.element.hide();
		return this;
	},
	show: function() {
		this.element.show();
		return this;
	},
	positionUnder: function(element) {
		var element = $(element);
		var offset = element.cumulativeOffset();
		var dimensions = element.getDimensions();

		this.element.setStyle({
			left: offset[0] + 'px',
			top: offset[1] + 'px',
			width: dimensions.width + 'px',
			height: dimensions.height + 'px',
			zIndex: element.getStyle('zIndex') - 1
		}).show();

		return this;
	},
	setBounds: function(bounds) {
		for(prop in bounds)
			bounds[prop] += 'px';
		this.element.setStyle(bounds);
		return this;
	},
	destroy: function() {
		if(this.element)
			this.element.remove();
		return this;
	}
});

// -------------------------------------------------------------------

/**
 * Show Page/Element loading status (during AJAX call)
 *
 * @class e107Utils.LoadingStatus
 * @widget: core-loading
 * @version 1.0
 * @author SecretR
 * @extends e107WidgetAbstract
 * @template: 'template'
 * @cache_string: 'instance-loading-status'
 */


('Loading')				   .addModLan('core-loading', 'alt');
('Loading, please wait...').addModLan('core-loading', 'text');

e107Utils.LoadingStatus = Class.create(e107WidgetAbstract, {

	initialize: function(dest_element, options) {
		this.initMod('core-loading', options);
		this.cacheStr = 'instance-loading-status';

		this.loading_mask_loader = false;
		this.loading_mask = $('loading-mask');
		this.iframeShim = this.getModCache(this.cacheStr + '-iframe');
		this.destElement = ($(dest_element) || $$('body')[0]);

		//this.addModLan('loading_text', 'Loading, please wait...').addModLan('loading_alt', 'Loading');
		this.re_center = this.recenter.bindAsEventListener(this);

		this.create();
	    if(this.options.show_auto)
	    	this.show();
	},

	startObserving: function() {
		Event.observe(window,"resize", this.re_center);
    	if(e107API.Browser.IE && e107API.Browser.IE <= 7)
    		Event.observe(window,"scroll", this.re_center);
    	return this;
	},

	stopObserving:  function() {
		Event.stopObserving(window, "resize", this.re_center);
    	if(e107API.Browser.IE && e107API.Browser.IE <= 7)
    		Event.stopObserving(window, "scroll", this.re_center);
    	return this;
	},

	set_destination: function(dest_element) {
		this.destElement = $(dest_element) || $$('body')[0];
		return this;
	},

	create: function() {
		if(!this.loading_mask) {
			var objBody = $$('body')[0];
			this.loading_mask = this.getModTemplate('template').parseToElement().hide();

			objBody.insert({
				bottom: this.loading_mask
			});
			this.loading_mask.setStyle( { 'opacity': 0.8, zIndex: 9000 } );
		}

		this.loading_mask_loader = this.loading_mask.down('#loading-mask-loader');
		this.loading_mask_loader.setStyle( { /*'position': 'fixed', */zIndex: 9100 } );
		//Create iframeShim if required
		this.createShim();
		return this;
 	},

	show: function () {
		if(this.loading_mask.visible()) return;
		this.startObserving();
		this.center();
		this.loading_mask.show();
		return this;
	},

	hide: function () {
		this.loading_mask.hide();
		this.stopObserving().positionShim(true);
		return this;
	},

	center: function() {
		//Evil IE6
		if(!this.iecenter()) {
			Element.clonePosition(this.loading_mask, this.destElement);
			this.fixBody().positionShim(false);
		}
		return this;

	},

	recenter: function() {
		if(!this.iecenter()) {
			Element.clonePosition(this.loading_mask, this.destElement);
			this.fixBody().positionShim(false);
		}
		return this;
	},

	iecenter: function() {
		//TODO - actually ie7 should work without this - investigate
		if(e107API.Browser.IE && e107API.Browser.IE <= 7) {
			//The 'freezing' problem solved (opacity = 1 ?!)
			this.loading_mask.show();
			var offset = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop;
			var destdim = document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;

			if(!this.lmh) this.lmh = this.loading_mask_loader.getHeight();
			var eldim = this.lmh;
			var toph = parseInt(destdim/2 - eldim/2 + offset );
			this.loading_mask.setStyle({top: 0, left: 0, 'opacity': 1});
			this.fixBody(true);
			this.loading_mask_loader.setStyle( {
				'position': 'absolute',
				'top': toph + 'px',
				'opacity': 1
			});

			this.positionShim(false);
			return true;
		}
		return false;
	},

	fixBody: function(force) {
		if(force || this.destElement.nodeName.toLowerCase() == 'body') {
			var ps = e107Helper.getPageSize();
			this.loading_mask.setStyle({ 'width': parseInt(ps[0]) + 'px', 'height': parseInt(ps[1]) + 'px' });
		}
		return this;
	},

	createShim: function() {
		if(e107API.Browser.IE && e107API.Browser.IE <= 7 && !this.iframeShim) {
			this.iframeShim = new e107Utils.IframeShim().hide();
			this.setModCache(this.cacheStr +'-iframe', this.iframeShim);
		}

		return this;
	},

	positionShim: function(hide) {
		if(!e107API.Browser.IE || e107API.Browser.IE > 6) return this;
		if(hide) {
			this.iframeShim.hide(); return this;
		}
		this.iframeShim.positionUnder(this.loading_mask).show();
		return this;
	}
});

/**
 * Register page loading core events
 */
e107Event.register('ajax_loading_start', function(event) {
	var loadingObj = e107.getModCache('ajax-loader');
	if(!loadingObj) {
		loadingObj = new e107Utils.LoadingStatus(false, { show_auto: false });
		e107.setModCache('ajax-loader', loadingObj);
	}
	loadingObj.set_destination(event.memo.overlayPage).show();
});

e107Event.register('ajax_loading_end', function(event) {
	var loadingObj = e107.getModCache('ajax-loader');
	if(loadingObj) {
		window.setTimeout( function(){ loadingObj.hide() }, 200);
	}
});

/**
 * e107Utils.LoadingElement
 * based on Protoload by Andreas Kalsch
 */
e107Base.setPrefs('core-loading-element', {
	overlayDelay: 50,
	opacity: 0.8,
	zIndex: 10,
	className: 'element-loading-mask',
	backgroundImage: '#{e_COMPAT_IMAGE}loading_32.gif'
});

e107Utils.LoadingElement = {
	startLoading: function(element, options) {
		if(!options) options = {};
		Object.extend(options, e107Base.getPrefs('core-loading-element') || {});
		element = $(element);

		var zindex = parseInt(e107.getModPref('zIndex')) + parseInt(options.zIndex);
		var cacheStr = 'core-loading-element-' + $(element).identify();
		element._waiting = true;
		//can't use element._eloading for storing objects because of IE6 memory leak
		var _eloading = e107Base.getCache(cacheStr);

		if (!_eloading) {
			_eloading = new Element('div', { 'class': options.className }).setStyle({
				position: 'absolute',
				opacity: options.opacity,
				zIndex: zindex
				//backgroundImage: 'url(' + options.backgroundImage.parsePath() + ')'
			});

			$$('body')[0].insert({ bottom: _eloading });
			var imgcheck = _eloading.getStyle('background-image');
			//console.log(options.backgroundImage.parsePath());
			if(!imgcheck || imgcheck == 'none') //only if not specified by CSS
				_eloading.setStyle( {backgroundImage: 'url(' + options.backgroundImage.parsePath() + ')'});
			e107Base.setCache(cacheStr, _eloading);
		}
		window.setTimeout(( function() {
			if (this._waiting) {
				Element.clonePosition(_eloading, this);
				_eloading.show();
			}
		}).bind(element), options.overlayDelay);

	},

	stopLoading: function(element) {
		if (element._waiting) {
			element._waiting = false;
			var cacheStr = 'core-loading-element-' + $(element).identify(), _eloading = e107Base.getCache(cacheStr);
			if($(_eloading)) $(_eloading).hide();//remove it or not?
			//e107Base.clearCache(cacheStr);
		}
	}
};

Element.addMethods(e107Utils.LoadingElement);

/**
 * Register element loading core events
 */
e107Event.register('ajax_loading_element_start', function(event) {
	var element = $(event.memo.overlayElement);
	if(element) element.startLoading();
});

e107Event.register('ajax_loading_element_end', function(event) {
	var element = $(event.memo.overlayElement);
	if(element)  window.setTimeout( function(){ element.stopLoading() }.bind(element), 50);
});

// -------------------------------------------------------------------

// ###### START DEPRECATED - subject of removal!!! ######

//@see e107Helper#toggle, e107Helper#autoToggle
var expandit = function(curobj, hide) {
	e107Helper.toggle(curobj, {});

    if(hide) { //don't use it - will be removed
        hide.replace(/[\s]?,[\s]?/, ' ').strip();
        $w(hide).each(function(h) {
            if(Object.isElement($(h))) { $(h).hide(); }
        });
    }
}

//Use Prototype JS instead: $(id).update(txt);
var setInner = function(id, txt) {
    $(id).update(txt);
}

//@see e107Helper#confirm TODO @see e107ModalConfirm#confirm
var jsconfirm = function(thetext){
        return e107Helper.confirm(thetext);
}

//Use Prototype JS instead e.g.: $(tagid).value = str; $(display).hide();
var insertext = function(str, tagid, display) {
    e107Helper.insertText(str, tagid, display);
}

//Use Prototype JS instead e.g.: $(tagid).focus().value += str; $(display).hide();
var appendtext = function(str, tagid, display) {
    e107Helper.appendText(str, tagid, display);
}

//TODO - e107Window class, e107Helper#openWindow proxy
var open_window = function(url, wth, hgt) {
    if('full' == wth){
        pwindow = window.open(url);
    } else {
    	mywidth = varset(wth, 600);
    	myheight = varset(wth, 400);
        pwindow = window.open(url,'Name', 'top=100,left=100,resizable=yes,width='+mywidth+',height='+myheight+',scrollbars=yes,menubar=yes')
    }
    pwindow.focus();
}

//TODO Window class
var closeWindow = function(form){
    if((window.opener!=null)&&(!window.opener.closed)){
        window.opener.location.reload();
    }
    if(window.opener!=null) {
        window.close();
    }else{setWinType(form);form.whatAction.value="Close";form.submit();}
}


//@see e107Helper#urljump
var urljump = function(url) {
    e107Helper.urlJump(url);
}

//@see e107Helper#imagePreload
var ejs_preload = function(ejs_path, ejs_imageString){
    e107Helper.imagePreload(ejs_path, ejs_imageString)
}

//Use Prototype JS e.g.: $(cntfield).value = $(field).value.length;
var textCounter = function(field,cntfield) {
    cntfield.value = field.value.length;
}

//Not used anymore - seek & remove
/*
function openwindow() {
    opener = window.open("htmlarea/index.php", "popup","top=50,left=100,resizable=no,width=670,height=520,scrollbars=no,menubar=no");
    opener.focus();
}
*/

//@see e107Helper#toggleChecked
var setCheckboxes = function(the_form, do_check, the_cb) { //backward compatibility
    e107Helper.toggleChecked(the_form, do_check, 'name^=' + the_cb.gsub(/[\[\]]/, ''), false);
}

//@see e107Helper.BB#storeCaret
var storeCaret = function(textAr) {
	e107Helper.BB.store(textAr); return;
}

//@see e107Helper.BB#insert
var addtext = function(text, emote) {
    e107Helper.BB.insert(text, emote); return;
}

//@see e107Helper.BB#help
var help = function(help,tagid) {
    e107Helper.BB.help_old(help, tagid, true);
}

//Use Prototype JS e.g.: $(object).addClassName(over); $(object).removeClassName(over);
var eover = function(object, over) {
    $(object).writeAttribute('class', over);
}

//@see e107Helper#duplicateHTML
var duplicateHTML = function(copy, paste, baseid) {
    e107Helper.duplicateHTML(copy,paste,baseid);
}

var preview_image = function(src_val,img_path, not_found) {
    e107Helper.previewImage(src_val, img_path, not_found)
}

var externalLinks = function () {
    //e107Helper.externalLinks();
};
// ###### END DEPRECATED ######

// -------------------------------------------------------------------

/**
 * e107History
 *
 * Prototype Xtensions http://www.prototypextensions.com/
 */
var e107History = {
    __altered: false,
    __currentHash: null,
    __previousHash: null,
    __iframe: false,
    __title: false,

    /**
     * init()
     * @desc Initialize the hash. Call this method in first
     */
    init: function() {
        var inst  = this;
        var hash  = location.hash.substring(1);
        this.hash = $H(hash.toQueryParams());
        this.__currentHash  = hash;
        this.__previousHash = hash;

        this.__title = document.title;

        if(e107API.Browser.IE && e107API.Browser.IE < 8) {
            document.observe('dom:loaded', function(e) {
                if(!$('e107-px-historyframe')) {
                    e107History.__iframe = new Element('iframe', {
                        name   : 'e107-px-historyframe',
                        id     : 'e107-px-historyframe',
                        src    : '',
                        width  : '0',
                        height : '0',
                        style  : {
                            visibility: 'hidden'
                        }
                    });

                    document.body.appendChild(e107History.__iframe);

                    e107History.setHashOnIframe(inst.hash.toQueryString());
                }
            });
        }
    },

    /**
     * set( string name, string value )
     *
     * @desc Set new value value for parameter name
     */
    set: function(name, value) {
        this.__previousHash = this.hash.toQueryString();
        this.hash.set(name, value);
        this.apply();
    },

    /**
     * get( string $name )
     *
     * @desc Get value parameter $name
     */
    get: function(name) {
        return this.hash.get(name);
    },

    /**
     * unset( string $name )
     *
     * @desc Unset parameter $name
     */
    unset: function(name) {
        this.hash.unset(name);
        this.apply();
    },

    /**
     * update()
     *
     * @desc Updates this.hash with the current hash
     */
    update: function() {
        this.__previousHash = this.hash.toQueryString();
        var hash = window.location.hash.substring(1);

        // If IE, look in the iframe if the hash is updated
        if(e107API.Browser.IE && e107API.Browser.IE < 8 && this.__iframe) {
            var hashInFrame = this.getHashOnIframe();

            if(hashInFrame != hash) {
                hash = hashInFrame;
            }
        }

        this.hash = $H(hash.toQueryParams());
        this.__currentHash = hash;
    },

    /**
     * apply()
     *
     * @desc Apply this.hash to location.hash
     */
    apply: function() {
        var newHash = this.hash.toQueryString();

        // set new hash
        window.location.hash = newHash;

        // If IE, apply new hash to frame for history
        if(e107API.Browser.IE && e107API.Browser.IE < 8 && this.__iframe) {
            if(this.__currentHash != newHash)
            {
                this.setHashOnIframe(newHash);
            }
            else if(newHash != this.getHashOnIframe())
            {
                this.setHashOnIframe(newHash);
            }
        }
    },

    /**
     * isAltered()
     *
     * @desc Return true if current hash is different of previous hash.
     * this.__altered allows to force the dispatch.
     */
    isAltered: function() {
        if(this.__altered) {
            return true;
        }
        this.__altered = false;

        return (e107History.__currentHash != e107History.__previousHash);
    },

    /**
     * setHashOnIframe()
     *
     * @use  For IE compatibility
     * @desc Set hash value on iframe
     */
    setHashOnIframe: function(hash) {
        try {
            var doc = e107History.__iframe.contentWindow.document;
            doc.open();
            doc.write('<html><body id="history">' + hash + '</body></html>');
            doc.close();
        } catch(e) {}
    },

    /**
     * getHashOnIframe()
     *
     * @use  For IE compatibility
     * @desc Get hash value on iframe
     */
    getHashOnIframe: function() {
        var doc = this.__iframe.contentWindow.document;
        if (doc && doc.body.id == 'history') {
            return doc.body.innerText;
        } else {
            return this.hash.toQueryString();
        }
    },

    /**
     * setTitle()
     *
     * @desc Set a new title for window
     */
    setTitle: function(title) {
        if(document.title) {
            document.title = title;
        }
    },

    /**
     * getTitle()
     *
     * @desc Return current window title
     */
    getTitle: function() {
        return this.__title;
    }
};

e107History.init();

/**
 * History.Registry
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc Used to register a callback for a parameter
 */
e107History.Registry =
{
    /**
     * @desc Hash
     */
    hash : new Hash(),

    /**
     * set( string $config )
     *
     * @desc Set new value historyId for parameter config
     */
    set: function(config) {

        if(typeof(config) != 'object') {
            throw('e107History.Registry.set : config must be an javascript object');
        }

        // id
        if(!config.id || !Object.isString(config.id)) {
            throw('e107History.Registry.set : config.id must be an string');
        }

        // onChange
        if(!config.onStateChange || !Object.isFunction(config.onStateChange)) {
            throw('e107History.Registry.set : config.onStateChange '
                + 'must be an javascript callback function');
        }

        // defaultValue
        if(!config.defaultValue || !Object.isString(config.defaultValue)) {
            config.defaultValue = '';
        }

        this.hash.set(config.id, config);
    },

    /**
     * flat version of set method
     *
     * @desc Register callback function for historyId
     */
    register: function(historyId, callback, defval) {
        var config = {
        	id: historyId,
        	onStateChange: callback,
        	defaultValue: defval
        };
        this.set(config);
    },

    /**
     * get( string $id )
     *
     * @desc Get value parameter $id
     */
    get: function(id) {
        return this.hash.get(id);
    },

    /**
     * unset( string $id )
     *
     * @desc Unset parameter $id
     */
    unset: function(id) {
        this.hash.unset(id);
    }
}

/**
 * History.Observer
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc Used to perform actions defined in the registry,
 * according to the hash of the url.
 */
e107History.Observer = {

    /**
     * @desc Interval delay in seconds
     */
    delay : 0.4,

    /**
     * @desc Interval timer instance
     */
    interval : null,

    /**
     * @desc If interval is started : true, else false
     */
    started : false,

    /**
     * start()
     *
     * @desc Start a interval timer
     */
    start: function() {
        if(this.started) return;
        this.interval = new PeriodicalExecuter(e107History.Observer.dispatch, this.delay);
        this.started = true;
    },

    /**
     * stop()
     *
     * @desc Stop the interval timer
     */
    stop: function() {
        if(!this.started) return;
        this.interval.stop();
        this.started = false;
    },

    /**
     * dispatch()
     *
     * @desc This method is called each time interval,
     * the dispatch of the registry is implemented only if
     * the hash has been amended (optimisiation)
     */
    dispatch: function() {
        // Update the hash
        e107History.update();

        // Dispatch only if location.hash has been altered
        if(e107History.isAltered()) {
        	var oldstate = String(e107History.__previousHash).toQueryParams();
        	//FIXME - possible bugs/performance issues here - investigate further
            e107History.hash.each(function(pair)  {
                var registry = e107History.Registry.get(pair.key);
                //Bugfix - notify callbacks only when required
                if(registry && (e107History.__altered === pair.key || oldstate[pair.key] !== pair.value)) {
                   registry.onStateChange.bind(e107History)( pair.value );
                }
            });
        }
    }
};

// -------------------------------------------------------------------

/*
 * AJAX related
 */
var e107Ajax = {};

/**
 * Ajax.History
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc Provides core methods to easily manage browsing history
 * with Ajax.History.Request / Updater.
 */
e107Ajax.History = {

    /**
     * @desc Allowed Ajax.History prefix (for validation)
     */
    types : ['Request', 'Updater'],

    cacheString: 'ajax-history-',

    /**
     * observe( string type, string id, string url, object options )
     *
     * @desc This method helps manage the browsing history
     */
    observe: function(type, id, url, options) {

        var getter         = e107.getModCache(this.cacheString + id);
        var currentVersion = 0;
        var output         = false;

        // Type validation
        if(this.types.indexOf(type) == -1) {
            throw('e107Ajax.History.observer: type ' + type + ' is invalid !');
        }

        // Registry management
        if(!getter) {
            currentVersion = (options.history.state) ? options.history.state : 0;
            var hash = new Hash();
            hash.set(currentVersion, options);
            e107.setModCache(this.cacheString + id, hash);
            //console.log(id,  e107.getModCache(this.cacheString + id));
        } else {
            currentVersion = (options.history.state)
                ? options.history.state : this.getCurrentVersion(id);
            getter.set(currentVersion, options);
        }

        // add handler on registry
        this.addCallback(type, id);

        return currentVersion;
    },

    /**
     * addCallback( string type, string id )
     *
     * @desc This method adds a state for request on History.Registry
     */
    addCallback: function(type, id) {

        e107History.Observer.start();
        // Set history altered state to true : force dispatch
        e107History.__altered = id;

        // Return void if registry is already set
        if(!Object.isUndefined(e107History.Registry.get(id))) return;

        // Add this id to history registry
        var cacheS = this.cacheString + id;
        e107History.Registry.set({
            id: id,
            onStateChange: function(state) {
                var options = e107.getModCache(cacheS).get(state.toString());
                var request = null;

                if(Object.isUndefined(options)) return;

                if(options.history.cache == true && options.history.__request) {
                    new Ajax.Cache(options.history.__request);
                } else {

                	//make a request
                    if(type == 'Request') {
                        request = new Ajax.Request(options.history.__url, options);
                    } else if(type == 'Updater') {
                        request = new Ajax.Updater(options.container, options.history.__url, options);
                    }
                    options.history.__request = request;
                }

                e107History.__altered = false;

                if (Object.isFunction(options.history.onStateChange)) {
                    options.history.onStateChange(state);
                }
            }
        });
    },

    /**
     * getCurrentVersion( string id )
     *
     * @desc This method returns the current state in history
     * (if the state is not defined)
     */
    getCurrentVersion: function(id) {
        var getter = e107.getModCache(this.cacheString + id);
        return Object.isUndefined(getter) ? 0 : getter.keys().length;
    }
};

e107Ajax.ObjectMap = {
    id              : null,    // set custom history value for this instance
    state           : false,   // set custom state value for this instance
    cache           : false,   // enable/disable history cache
    onStateChange   : null,    // handler called on history change
    __url           : null,
    __request       : null
};

/**
 * Ajax.Cache
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc Ajax.Cache can "simulate" an Ajax request from an
 * Ajax.Request/Updater made beforehand.
 */
Ajax.Cache = Class.create(Ajax.Base, {
    _complete: false,
    initialize: function($super, request) {
        $super(request.options);
        request._complete = false;
        this.transport = request.transport;
        this.request(request.url);
        return this;
    },

    request: function(url) {
        this.url = url;
        this.method = this.options.method;
        var params = Object.clone(this.options.parameters);

        try {
            var response = new Ajax.Response(this);

            if (this.options.onCreate) this.options.onCreate(response);
            Ajax.Responders.dispatch('onCreate', this, response);

            if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

            this.onStateChange();
        }
        catch (e) {
            this.dispatchException(e);
        }
    }
});

Object.extend(Ajax.Cache.prototype, {
    respondToReadyState : Ajax.Request.prototype.respondToReadyState,
    onStateChange       : Ajax.Request.prototype.onStateChange,
    success             : Ajax.Request.prototype.getStatus,
    getStatus           : Ajax.Request.prototype.getStatus,
    isSameOrigin        : Ajax.Request.prototype.isSameOrigin,
    getHeader           : Ajax.Request.prototype.getHeader,
    evalResponse        : Ajax.Request.prototype.evalResponse,
    dispatchException   : Ajax.Request.prototype.dispatchException
});

/**
 * Ajax.Request Extended
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc Just a small change: now Ajax.Request return self scope.
 * It is required by Ajax.Cache
 */
Ajax.Request = Class.create(Ajax.Request, {
    initialize: function($super, url, options) {
        $super(url, options);
        return this;
    }
});

Ajax.Request.Events =
  ['Uninitialized', 'Loading', 'Loaded', 'Interactive', 'Complete'];

/**
 * Ajax.Updater Extended
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc Just a small change: now Ajax.Updater return self scope
 * It is required by Ajax.Cache
 */
Ajax.Updater = Class.create(Ajax.Updater, {
    initialize: function($super, container, url, options) {
        $super(container, url, options);
        return this;
    }
});



//Register Ajax Responder
(function() {

		var e_responder = {
				onCreate: function(request) {
					if(request.options['updateElement']) {
						request.options.element = request.options.updateElement;
						e107Event.trigger('ajax_update_before', request.options, request.options.updateElement);
					}
					if(request.options['overlayPage']){
						e107Event.trigger('ajax_loading_start', request.options, request.options.overlayPage);
					} else if(request.options['overlayElement']) {
						e107Event.trigger('ajax_loading_element_start', request.options, request.options.overlayElement);
					}
				},

				onComplete: function(request) {
					/*Ajax.activeRequestCount == 0 && */
					if(request.options['overlayPage']) {
						e107Event.trigger('ajax_loading_end', request.options, request.options.overlayPage);
					} else if(request.options['overlayElement']) {
						e107Event.trigger('ajax_loading_element_end', request.options, request.options.overlayElement);
					}

					if(request.options['updateElement']) {
						request.options.element = request.options.updateElement;
						e107Event.trigger('ajax_update_after', request.options, request.options.updateElement);
					}
				},

				onException: function(request, e) {
					//TODO handle exceptions
					alert('e107Ajax Exception: ' + e);
				}
		}

		Ajax.Responders.register(e_responder);
})();

/**
 * e107AjaxAbstract
 */
var e107AjaxAbstract = Class.create ({
	_processResponse: function(transport) {
		if(null !== transport.responseXML) {
			this._handleXMLResponse(transport.responseXML);
		} else if(null !== transport.responseJSON) {
			this._handleJSONResponse(transport.responseJSON);
		} else {
			this._handleTextResponse(transport.responseText);
		}

	},

	_handleXMLResponse: function (response) {
		var xfields = $A(response.getElementsByTagName('e107response')[0].childNodes);
		var parsed = {};
		xfields.each( function(el) {
			if (el.nodeType == 1 && el.nodeName == 'e107action' && el.getAttribute('name') && el.childNodes) {

				var action = el.getAttribute('name'), items = el.childNodes;
				if(!varsettrue(parsed[action])) {
					parsed[action] = {};
				}

				for(var i=0, len=items.length; i<len; i++) {
					var field = items[i];

					if(field.nodeType!=1)
						continue;

					if(field.getAttribute('name')) {
						var type = field.getAttribute('type'), //not used yet
							name = field.getAttribute('name'),
							eldata = field.firstChild;
						parsed[action][name] = eldata ? eldata.data : '';
					}

				}
			}
		}.bind(this));
		this._handleResponse(parsed);
	},

	_handleJSONResponse: function (response) {
		this._handleResponse(response);
	},

	_handleTextResponse: function (response) {
		this._handleResponse({ 'auto': response} );
	},

	_handleResponse: function(parsed) {

		Object.keys(parsed).each(function(method) {
			try{
				this['_processResponse' + ('-' + method).camelize()](parsed[method]);
			} catch(e) {
				//
			}
		}.bind(this));

	},

	_processResponseAuto: function(response) {
		//find by keys as IDs & update
		Object.keys(response).each(function(key) {
			this._updateElement(key, response[key]);
		}.bind(this));
	},

	/**
	 * Reset checked property of form elements by selector name attribute (checkbox, radio)
	 */
	_processResponseResetChecked: function(response) {
		Object.keys(response).each(function(key) {
			var checked = parseInt(response[key]) ? true : false;
			$$('input[name^=' + key + ']').each( function(felement) {
				var itype = String(felement.type);
				if(itype && 'checkbox radio'.include(itype.toLowerCase()))
					felement.checked = checked;
			});
		}.bind(this));
	},

	/**
	 * Invoke methods on element or element collections by id
	 *
	 * Examples:
	 * {'show': 'id1,id2,id3'} -> show elements with id id1,id2 and id3
	 * {'writeAttribute,rel,external': 'id1,id2,id3'} -> invoke writeAttribute('rel', 'external') on elements with id id1,id2 and id3
	 */
	_processResponseElementInvokeById: function(response) {
		//response.key is comma separated list representing method -> args to be invoked on every element
		Object.keys(response).each(function(key) {
			var tmp = $A(key.split(',')),
				method = tmp[0],
				args = tmp.slice(1);

			//search for boolean type
			$A(args).each( function(arg, i) {
				switch(arg) {
					case 'false': args[i] = false; break;
					case 'true': args[i] = true; break;
					case 'null': args[i] = null; break;
				}
			});
			//response.value is comma separated element id list
			$A(response[key].split(',')).each( function(el) {
				el = el ? $(el.strip()) : null;
				if(!el) return;

				if(Object.isFunction(el[method]))
					el[method].apply(el, args);
				else if(typeof el[method] !== 'undefined') {
					//XXX - should we allow adding values to undefined yet properties? At this time not allowed
					el[method] = varset(args[0], null);
				}
			});
		});
	},

	/**
	 * Update element by type
	 */
	_updateElement: function(el, data) {
		el = $(el); if(!el) return;
		var type = el.nodeName.toLowerCase(), itype = el.type;
        if(type == 'input' || type == 'textarea') {
        	if(itype) itype = itype.toLowerCase();
        	switch (itype) {
        		case 'checkbox':
        		case 'radio':
        			el.checked = (el.value == data);
        			break;
        		default:
        			el.value = data.unescapeHTML(); //browsers doesn't unescape entities on JS update, why?!
        			break;
        	}

        } else if(type == 'select') {
            if(el.options) {
                var opt = $A(el.options).find( function(op, ind) {
                    return op.value == data;
                });
                if(opt)
                	el.selectedIndex = opt.index;
            }
        } else if(type == 'img') {
        	el.writeAttribute('src', data).show(); //show if hidden
        }else if(el.nodeType == 1) {
        	el.update(data);
        }
	}
});

// -------------------------------------------------------------------

/**
 * e107Ajax.Request
 * Prototype Xtensions http://www.prototypextensions.com/
 *
 * @desc @desc e107Ajax.Update wrapper, used to execute an Ajax.Request by integrating
 * the management of browsing history
 */
e107Ajax.Request = Class.create({
    initialize: function(url, options) {

        this.options = {};
        Object.extend(this.options, options || {});
        if(!this.options['parameters'])
        	this.options['parameters'] = { 'ajax_used': 1 }
        else if(!this.options.parameters['ajax_used'])
        	this.options['parameters']['ajax_used'] = 1;

        // only if required
        if(this.options['history']) {
            var tmpOpt = Object.clone(e107Ajax.ObjectMap);
            Object.extend(tmpOpt, this.options.history);
            this.options.history = tmpOpt;
            this.options.history.__url = url;

            // History id
            if(Object.isUndefined(options.history.id))
                throw('e107Ajax.Request error : you must define historyId');

            var id = this.options.history.id;

            // Enable history observer
            var version = e107Ajax.History.observe('Request', id, url, this.options);

            // Set current version value for container
            e107History.set(id, version);

        } else {
            return new Ajax.Request(url, this.options);
        }
    }
});

/**
 * e107Ajax.Updater
 *
 * @desc e107Ajax.Updater wrapper, used to execute an Ajax.Updater by integrating
 * the management of browsing history
 */
e107Ajax.Updater = Class.create({
    initialize: function(container, url, options) {

        this.options = {};

        Object.extend(this.options, options || {});
        if(!this.options['parameters'])
        	this.options['parameters'] = { 'ajax_used': 1 }
        else if(!this.options.parameters['ajax_used'])
        	this.options['parameters']['ajax_used'] = 1;

		//required for ajax_update event trigger
		this.options.updateElement = container;

        // only if required
        if(this.options['history']) {
            var tmpOpt = Object.clone(e107Ajax.ObjectMap);
            Object.extend(tmpOpt, this.options.history);
            this.options.history = tmpOpt;
            this.options.history.__url = url;

            // History id
            if(Object.isUndefined(options.history.id)) {
                var id = (Object.isString(container)) ? container : container.identify();
                this.options.history.id = id;
            } else {
                var id = this.options.history.id;
            }
            // Add container to this.options
            this.options.container = container;

            // Enable history observer
            var version = e107Ajax.History.observe('Updater', id, url, this.options);

            // Set current version value for container
            e107History.set(id, version);

        } else {
            return new Ajax.Updater(container, url, this.options);
        }
    }
});

Object.extend(e107Ajax, {

	/**
	 * Ajax Submit Form method
	 *
	 * @descr e107 analog to Prototpye native Form.request method
	 */
	submitForm: function(form, container, options, handler) {
		var parm = $(form).serialize(true),
			opt = Object.clone(options || {}),
			url = !handler ? $(form).readAttribute('action') : String(handler).parsePath();

		if(!opt.parameters) opt.parameters = {};
		Object.extend(opt.parameters, parm || {});
		opt.method = 'post';

		if ($(form).hasAttribute('method') && !opt.method)
		      opt.method = $(form).method;
		
		if(container)
			return new e107Ajax.Updater(container, url, opt);

		return new e107Ajax.Request(url, opt);
	},

	/**
	 * Ajax Submit Form method and auto-replace SC method
	 */
	submitFormSC: function(form, sc, scfile, container) {
		var handler = ('#{e_COMPAT}e_ajax.php'), parm = { 'ajax_sc': sc, 'ajax_scfile': scfile };
		return this.submitForm(form, varsettrue(container, sc), { parameters: parm, overlayElement: varsettrue(container, sc) }, handler);
	},

	toggleUpdate: function(toggle, container, url, cacheid, options) {
		container = $(container);
		toggle = $(toggle);
		opt = Object.clone(options || {});
		opt.method = 'post';

		if(!toggle) return;

		if(!toggle.visible())
		{

			if(cacheid && $(cacheid)) return toggle.fxToggle();
			var oldOnComplete = opt['onComplete'];
			
			opt.onComplete = function(t) { toggle.fxToggle(); if(Object.isFunction(oldOnComplete)) oldOnComplete(t) };
			if(url.startsWith('sc:'))
			{
				return e107Ajax.scUpdate(url.substring(3), container, opt);
			}
			return new e107Ajax.Updater(container, url, opt);
		}

		return toggle.fxToggle();
	},

	scUpdate: function(sc, container, options) {
		var handler = ('#{e_COMPAT}e_ajax.php').parsePath(), parm = { 'ajax_sc': sc };
		opt = Object.clone(options || {});
		opt.method = 'post';
		if(!opt.parameters) opt.parameters = {};
		Object.extend(opt.parameters, parm || {});
		return new e107Ajax.Updater(container, handler, opt);
	}
});

/**
 * e107Ajax.fillForm
 *
 * @desc
 */
e107Ajax.fillForm = Class.create(e107AjaxAbstract, {

	initialize: function(form, overlay_dest, options) {
		//TODO - options
		this.options = Object.extend({
			start: true
		}, options || {});

		this.form = $(form);
		if(!this.form) return;

		if(this.options['start'])
			this.start(overlay_dest);
	},

	start: function(overlay_dest) {
		e107Event.trigger("ajax_fillForm_start", {form: this.form});
		var destEl = $(overlay_dest) || false;
		var C = this;

		//Ajax history is NOT supported (and shouldn't be)
		var options = {
			overlayPage: destEl,

			history: false,

			onSuccess: function(transport) {
				try {
					this._processResponse(transport);
				} catch(e) {
					var err_obj = { message: 'Callback Error!', extended: e, code: -1 }
					e107Event.trigger("ajax_fillForm_error", {form: this.form, error: err_obj});
				}
			}.bind(C),

			onFailure: function(transport) {
				//We don't use transport.statusText only because of Safari!!!
				var err = transport.getHeader('e107ErrorMessage') || '';
				//TODO - move error messages to the ajax responder object, convert it to an 'error' object (message, extended, code)
				//Add Ajax option e.g. printErrors (true|false)
				var err_obj = { message: err, extended: transport.responseText, code: transport.status }
				e107Event.trigger("ajax_fillForm_error", {form: this.form, error: err_obj });
			}.bind(C)
		}
		Object.extend(options, this.options.request || {}); //update - allow passing request options

		this.form.submitForm(null, options, this.options.handler);
	},

	_processResponseFillForm: function(response) {
		if(!response || !this.form) return;
		var C = this, left_response = Object.clone(response);
		this.form.getElements().each(function(el) {
			var elid = el.identify(), elname = el.readAttribute('name'), data, elnameid = String(elname).gsub(/[\[\]\_]/, '-');

			if(isset(response[elname])) {
				data = response[elname];
				if(left_response[elname]) delete left_response[elname];
			} else if(isset(response[elnameid])) {
				data = response[elnameid];
				if(left_response[elnameid]) delete left_response[elnameid];
			} else if(isset(response[elid])) {
				data = response[elid];
				if(left_response[elid]) delete left_response[elid];
			} else {
				return;
			}
            this._updateElement(el, data);
		}.bind(C));

		if(left_response) { //update non-form elements (by id)
			Object.keys(left_response).each( function(el) {
				this._updateElement(el, left_response[el]);
			}.bind(C));
		}

		e107Event.trigger("ajax_fillForm_success", {form: this.form});
	}

});

Element.addMethods('FORM', {

	submitForm: e107Ajax.submitForm.bind(e107Ajax),

	submitFormSC: e107Ajax.submitFormSC.bind(e107Ajax),

	fillForm: function(form, overlay_element, options) {
		new e107Ajax.fillForm(form, overlay_element, options);
	}
});

// -------------------------------------------------------------------

//DEPRECATED!!! Use e107Ajax.submitFormSC() || form.submitFormSC() instead
function replaceSC(sc, form, container, scfile) {
		$(form).submitFormSC(sc, scfile, container);
}

//DEPRECATED!!! Use e107Ajax.submitForm() || form.submitForm() instead
function sendInfo(handler, container, form) {
	if(form)
		$(form).submitForm(container, null, handler);
	else
		new e107Ajax.Updater(container, handler);
}

// -------------------------------------------------------------------

/*
 * Core Auto-load
 */
$w('autoExternalLinks autoNoHistory autoHide toggleObserver scrollToObserver').each( function(f) {
	e107.runOnLoad(e107Helper[f], null, true);
});

/*
 * e107 website system
 * 
 * Copyright (c) 2001-2008 e107 Developers (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://gnu.org).
 * 
 * DECORATE HTML LIST ELEMENTS
 * Inspired by Magento' decorate JS functions (www.magentocommerce.com) 
 * 
 * $Source: /cvsroot/e107/e107_0.8/e107_files/jslib/core/decorate.js,v $
 * $Revision: 1.3 $
 * $Date: 2008/11/17 17:43:57 $
 * $Author: secretr $
 * 
*/

e107Utils.Decorate = {
	
	/**
	 * Decorate table rows and cells, tbody etc
	 * @see e107Utils.Decorate._decorate()
	 */
	 table: function(table) {
	    var table = $(table);
	    if (!table) return;
	    
	    //default options
	    this._options = {
	        'tbody': false,
	        'tbody_tr': 'odd even first last',
	        'thead_tr': 'first last',
	        'tfoot_tr': 'first last',
	        'tr_td': false
	    };
	    
	    // overload options
	    Object.extend(this._options, (arguments[1] || {}));

	    // decorate
	    if (this._options['tbody']) {
	        this._decorate(table.select('tbody'), this._options['tbody']);
	    }
	    if (this._options['tbody_tr']) {
	        this._decorate(table.select('tbody tr:not([class~=no-decorate])'), this._options['tbody_tr']);
	    }
	    if (this._options['thead_tr']) {
	        this._decorate(table.select('thead tr:not([class~=no-decorate])'), this._options['thead_tr']);
	    }
	    if (this._options['tfoot_tr']) {
	        this._decorate(table.select('tfoot tr:not([class~=no-decorate])'), this._options['tfoot_tr']);
	    }
	    if (this._options['tr_td']) {
	        table.select('tr').each( function(tr) {
	            this._decorate(tr.select('td:not([class~=no-decorate])'), this._options['tr_td']);
	        }.bind(this));
	    }
	},
	
	/**
	 * Decorate list (ul)
	 * Default decorate CSS classes for list items are "odd", "even" and "last" 
	 * 
	 * Examples: 
	 *  e107Utils.Decorate.list('mylist'); //default decorate options over element with id 'mylist'
	 *  e107Utils.Decorate.list('mylist', 'odd even'); //decorate options odd and even only over element with id 'mylist'
	 * 
	 * @param list - id/DOM object of list element (ul) to be decorated
	 * [@param options] - string|array decorate options - @see e107Utils.Decorate._decorate()
	 * [@param recursive] - boolean decorate all childs if present
	 */
	list: function(list) {
	    list = $(list);
	    if (list) {
	        if (!varset(arguments[2])) {
	            var items = list.select('li:not([class~=no-decorate])');
	        } else {
	            var items = list.childElements();
	        }
	        this._decorate(items, (arguments[1] || 'odd even last'));
	    }
	},
	
	/**
	 * Set "odd", "even" and "last" CSS classes for list items
	 * 
	 * Examples: 
	 *  e107Utils.Decorate.dataList('mydatalist'); //default decorate options over element with id 'mydatalist'
	 *  e107Utils.Decorate.dataList('mydatalist', 'odd even'); //decorate options odd and even for dt elements, default for dd elements
	 * 
	 * [@param dt_options] - string|array dt element decorate options - @see e107Utils.Decorate._decorate()
	 * [@param dd_options] - string|array dd element decorate options - @see e107Utils.Decorate._decorate()
	 */
	dataList: function(list) {
	    list = $(list);
	    if (list) {
	        this._decorate(list.select('dt:not([class~=no-decorate])'), (arguments[1] || 'odd even last'));
	        this._decorate(list.select('dd:not([class~=no-decorate])'), (arguments[2] || 'odd even last'));
	    }
	},
	
	/**
	 * Add classes to specified elements.
	 * Supported classes are: 'odd', 'even', 'first', 'last'
	 *
	 * @param elements - array of elements to be decorated
	 * [@param decorateParams] - array of classes to be set. If omitted or empty, all available will be used
	 */
	_decorate: function(elements) {
	    var decorateAllParams = $w('odd even first last');
	    this.decorateParams = $A();
	    this.params = {};
	    
	    if (!elements.length)  return;
	    
	    if(!varset(arguments[1])) {
	        this.decorateParams = decorateAllParams;
	    } else if(typeof(arguments[1]) == 'string') {
	        this.decorateParams = $w(arguments[1]);
	    } else {
	        this.decorateParams = arguments[1];
	    }
	    
	    decorateAllParams.each( function(v) {
	        this.params[v] = this.decorateParams.include(v);
	    }.bind(this));

	    // decorate first
	    if(this.params.first) {
	        Element.addClassName(elements[0], 'first');
	    }
	    // decorate last
	    if(this.params.last) {
	        Element.addClassName(elements[elements.length-1], 'last');
	    }
	    
	    if(!this.params.even && !this.params.odd) {
	        return;
	    }

	    var selections = elements.partition(this._isEven);

	    if(this.params.even) {
	        selections[0].invoke('addClassName', 'even');
	    }
	    if(this.params.odd) {
	        selections[1].invoke('addClassName', 'odd');
	    }
	},
	
    /**
     * Select/Reject/Partition callback function
     * 
     * @see e107Utils.Decorate._decorate()
     */
    _isEven: function(dummy, i) {
        return ((i+1) % 2 == 0);
    }
}

/*
 * e107 website system
 * 
 * Copyright (c) 2001-2008 e107 Developers (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://gnu.org).
 * 
 * e107Widget.Tabs Class
 * 
 * Create tabs, supports ajax/inline content, browser history & bookmarks
 * (unobtrusive Javascript)
 * 
 * $Source: /cvsroot/e107/e107_0.8/e107_files/jslib/core/tabs.js,v $
 * $Revision: 1.3 $
 * $Date: 2008/12/18 16:55:46 $
 * $Author: secretr $
 * 
*/

/**
 * Global prefs
 */
e107Base.setPrefs('core-tabs', {
	tabsClassName: 'e-tabs',
	bookmarkFix: true,
	historyNavigation: false,
	pageOverlay: false,
	overlayElement: false,
	ajaxCache: true,
	ajaxEvalScripts: false
});
 
e107Widgets.Tabs = Class.create(e107WidgetAbstract, {
	
	initialize: function(container, options) {
		this.Version = '1.0';
		
		this.events = new e107EventManager(this);
		var optHandlers = {
			show: this.show,
			hide: this.hide
		}
		Object.extend(optHandlers , options || {});

		this.global = this;
		this.initMod('core-tabs', optHandlers).__initTabData(container);
		
	},	
	
	__initTabData: function(container) {
		var cstring, celement = $(container);
		if(null === celement)
			throw('e107Widgets.Tabs: invalid value for container'); //TODO Lan
			
		if(Object.isString(container)) {
			cstring = container; 
		} else if(Object.isElement(container)) {
			cstring = celement.identify();
		}
		
		this.histotyHash = ('etab-' + cstring).camelize(); 
		if(!this.getModCache('-data')) {
			this.setModCache('-data', {});
		}
		
		this.tabData = this.getModCache('-data')['ref-' + cstring];
		this._observer = this.observer.bindAsEventListener(this); //click observer
 
		if(!this.tabData && !this.___initialized) {
			if(this.options.bookmarkFix || this.options.historyNavigation) this.options.history = true;
			
			this.___methods = $w('show hide select tabSelect ajaxSelect visible getPanelId getMenuId startObserve stopObserve getPanelId getPanel getMenuId getMenu');
			this.tabData = {
				container: celement,
				list: $A()
			}

			if(celement.nodeName.toLowerCase() != 'ul') 
				var celements = celement.select('ul.' + this.options.tabsClassName + ' > li');
			else 
				var celements = $$('#' + cstring + ' > li');
				
			
			celements.inject(this.tabData.list, function(arr, elitem, i) {
				var mid = elitem.identify(),
					a = elitem.select('a')[0],
					act = a.hash ? a.hash.substr(1) : '',
					cid = $(act);
				
				if(!celement)
					return;
					
				var that = this;
				arr[i] = { Index: i, menuId: mid, menu: elitem, menuAction: act, actionItem: a, panel: cid, panelId: cid.id, ajaxUrl: a.readAttribute('rel'), global: that, exec: that._exec.bind(that, i) };
				this._extendTab(arr[i]);
				
				return arr;
			}.bind(this));
			
			this.exec_recursive('hide').getDefault().select();
			this.startEvents();
			this.___initialized = true;
			this.getModCache('-data')['ref-' + cstring] = this.tabData;
		}
	},
	
	_extendTab: function(data) {
		this.___methods.inject(data, function(obj, method) {
			obj[method] = this[method].bind(this, obj);
			return obj;
		}.bind(this));
		data.events = new e107EventManager(this);
		data.options = Object.clone(this.options);
		data.histotyHash = this.histotyHash;

		return this._detectLoad(data);
	},
	
	_detectLoad: function(tab) {
		if(tab.ajaxUrl) {
			var lopts = $w(tab.ajaxUrl).detect(function (values) {
				return values.startsWith('ajax-tab');
			});
			if(lopts) { 
				var link = tab.actionItem.readAttribute('href').split('#')[0]; //link url
				tab.ajaxUrl = link ? link : document.location.href.split('#')[0]; //self url
			}
			return tab;
		}
		tab.ajaxUrl = false;
		return tab;
	},
	
	_exec: function(index, method, options) {
		if(!this.___methods.include(method) || !this.tabData.list[index]) {
			throw('e107Widgets.Tabs._exec: wrong method or object not found!');
		}
		this.tabData.list[index][method](options);
		return this.tabData.list[index];
	},
	
	/**
	 * Available only in instance' global scope
	 */
	exec: function(index, method, options) {
		this.tabData.list[index].exec(method, options || {});
		return this;
	},
	
	/**
	 * Available only in instance' global scope
	 */
	exec_recursive: function(method, except, options) {
		if(except)
			this.tabData.list.without(except).invoke('exec', method, options || {});
		else 
			this.tabData.list.invoke('exec', method, options || {});
		return this;
	},
	
	_getTabByIdnex: function(index) {
		return this.tabData.list[index] || null;
	},
	
	_getTabByPanelId: function(name) {
		return this.tabData.list.find(function(tab_obj) { return tab_obj.getPanelId() == name }) || null;
	},
	
	/**
	 * Available only in instance' global scope
	 */
	get: function(tab) {
		if(Object.isNumber(tab))
			return this._getTabByIdnex(tab);
		else if(Object.isString(tab))
			return this._getTabByPanelId(tab);
		return tab;
	},
	
	getPanelId: function(tab_obj) {
		return tab_obj.panelId;
	},
	
	getPanel: function(tab_obj) {
		return tab_obj.panel;
	},
	
	getMenuId: function(tab_obj) {
		return tab_obj.menuId;
	},
	
	getMenu: function(tab_obj) {
		return tab_obj.menu;
	},
	
	/**
	 * Available only in instance' global scope
	 */
	getDefault: function() {
		var current = e107History.get(this.histotyHash);
		if(current) {
			var tab = this.get(current) || this.tabData.list[0];
			this._active = tab.Index;
			return tab;
		}
		
		this._active = 0; 
		return this.tabData.list[0];
	},
	
	getActive: function() {
		if(!this.global._active) {
			var _active = this.tabData.list.find(function(tab_obj) { return tab_obj.visible(); }) || null;
			if(_active) {
				this.global._active = _active.Index;
			}
		}
		return this.get(this.global._active);
	},
	
	visible: function(tab) {
		return tab.getPanel().visible();
	},

	show: function(tab) {
		tab.getMenu().addClassName('active');
		tab.getPanel().addClassName('active').show(); 
		if(tab.global.options.history)
			e107History.set(tab.histotyHash, tab.getPanelId());
		return tab;
	},
	
	hide: function(tab) {
		tab.getMenu().removeClassName('active');
		tab.getPanel().removeClassName('active').hide();
		
		return tab;
	},

	select: function(tab) {
		if(!tab.visible()) {
			if(tab.ajaxUrl) 
				return tab.ajaxSelect();
			return tab.tabSelect();
		}
		return tab;
	},
	
	ajaxSelect: function(tab) {
		if(!tab.ajaxUrl || (this.global.options.ajaxCache && tab.options['ajaxCached']))  {
			return tab.tabSelect();
		}
		var ovel = this.global.options.overlayElement === true ? tab.getPanel() : $(this.global.options.overlayElement);
		tab.getMenu().addClassName('active'); 
		var opts = {
			overlayPage: this.options.pageOverlay ? tab.getPanel() : false,
			evalScripts: this.options.ajaxEvalScripts,
			overlayElement: ovel || false,
			onComplete: function() { tab.options.ajaxCached = this.global.options.ajaxCache; tab.tabSelect();  }.bind(this)
		}

		new e107Ajax.Updater(tab.getPanel(), tab.ajaxUrl, opts);
		
		return tab;
	},
	
	tabSelect: function(tab) {
		
		this.global.events.notify('hideActive', this.global.getActive()); //global trigger
		tab.events.notify('hide', this.global.getActive()); // tab object trigger
		this.options.hide(this.global.getActive());
		
		this.global.events.notify('showSelected', tab); //global trigger
		tab.events.notify('show', tab); // tab object trigger
		this.options.show(tab);
		
		this.global._active = tab.Index;
		
		return tab;
	},

	startEvents: function() {
		this.exec_recursive('startObserve'); 
		this._startHistory();
		return this;
	},
	
	stopEvents: function() {
		this.exec_recursive('stopObserve'); 
		this._stopHistory();
		return this;
	},
	
	startObserve: function(tab) {
		tab.actionItem.observe('click', this._observer); return this;
	},
	
	stopObserve: function(tab) {
		tab.actionItem.stopObserving('click', this._observer); return this;
	},
	
	observer: function(event) {
		var el = event.findElement('a');
		if(el) {
			event.stop();
			
			this.get(el.hash.substr(1)).select();
		}
	},
	
	eventObserve: function(method, callback) {
		this.events.observe(method, callback); return this;
	},
	
	eventStopObserve: function() {
		this.events.stopObserving(method, callback); return this;
	},
	
	_startHistory: function() {
		if(this.options.historyNavigation) {
            e107History.Observer.start();
            var that = this;
            // set handler for this instance
            e107History.Registry.set({
                id: this.histotyHash,
                onStateChange: function(tab) { 
                    that.get(String(tab)).select();
                }
            });
		}
	},
	
	_stopHistory: function() {
        e107History.Observer.stop();
        e107History.Registry.unset(this.histotyHash);
	}
});


//carousel
/*
Copyright (c) 2009 Victor Stanciu - http://www.victorstanciu.ro

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/

Carousel = Class.create(Abstract, {
	initialize: function (scroller, slides, controls, options) {
		this.scrolling	= false;
		this.scroller	= $(scroller);
		this.slides		= slides;
		this.controls	= controls;
		

		this.options    = Object.extend({
            duration:           1,
            auto:               false,
            frequency:          3,
            visibleSlides:      1,
            controlClassName:   'carousel-control',
            jumperClassName:    'carousel-jumper',
            disabledClassName:  'carousel-disabled',
            selectedClassName:  'carousel-selected',
            circular:           false,
            wheel:              true,
            effect:             'scroll',
            transition:         'sinoidal'
			
        }, options || {});
        
		
		
        if (this.options.effect == 'fade') {
            this.options.circular = true;
        }
		
		//scroll-circular fix by SecretR @ free-source.net
        if (this.options.effect == 'scroll' && this.options.circular && this.slides.length > 1) {
			var fixel = Element.clone(this.slides[0], true);
			fixel.identify();
			this.slides[this.slides.length] = fixel;
			Element.insert(this.slides[0].up(), {
				bottom: fixel
			});
        }

		this.slides.each(function(slide, index) {
			slide._index = index;
        });

		if (this.controls) {
            this.controls.invoke('observe', 'click', this.click.bind(this));
        }
        
        if (this.options.wheel) {            
            this.scroller.observe('mousewheel', this.wheel.bindAsEventListener(this)).observe('DOMMouseScroll', this.wheel.bindAsEventListener(this));;
        }

        if (this.options.auto) {
            this.start();
        }

		if (this.options.initial) {
			
			var initialIndex = this.slides.indexOf($(this.options.initial));
			
			if (initialIndex > (this.options.visibleSlides - 1) && this.options.visibleSlides > 1) {               
				if (initialIndex > this.slides.length - (this.options.visibleSlides + 1)) {
					initialIndex = this.slides.length - this.options.visibleSlides;
				}
			}
			
            this.moveTo(this.slides[initialIndex]);
		}
		
		if (this.options.container) {
			this.container = $(this.options.container);
			this.jumpers = this.container.select('a.'+this.options.jumperClassName);
		} else {
			this.jumpers = $$('a.'+ this.options.jumperClassName);
		}
		
		//this.current = this.slides[0];
	},

	click: function (event) {
		this.stop();

		var element = event.findElement('a');

		if (!element.hasClassName(this.options.disabledClassName)) {
			if (element.hasClassName(this.options.controlClassName)) {
				eval("this." + element.rel + "()");
            } else if (element.hasClassName(this.options.jumperClassName)) {
                this.moveTo(element.rel);
            }
        }

		this.deactivateControls();

		event.stop();
    },

	moveTo: function (element) {
		if (this.slides.length > 1) { 
			if (this.options.selectedClassName && this.jumpers) {
				this.jumpers.each(function(jump,b){
						if (jump.hasClassName(this.options.selectedClassName)) {
							jump.removeClassName(this.options.selectedClassName);
						}

						if (jump.rel == element || jump.rel == element.id ) {
							jump.addClassName(this.options.selectedClassName);
						}
						
				}.bind(this));
			}
			
			if (this.options.beforeMove && (typeof this.options.beforeMove == 'function')) {
				this.options.beforeMove();
			}
	
			this.previous = this.current ? this.current : this.slides[0];
			this.current  = $(element);
	
			var scrollerOffset = this.scroller.cumulativeOffset();
			var elementOffset  = this.current.cumulativeOffset();
	
			if (this.scrolling) {
				this.scrolling.cancel();
			}
	
			switch (this.options.effect) {
				case 'fade':               
					this.scrolling = new Effect.Opacity(this.scroller, {
						from:   1.0,
						to:     0,
						duration: this.options.duration,
						afterFinish: (function () {
							this.scroller.scrollLeft = elementOffset[0] - scrollerOffset[0];
							this.scroller.scrollTop  = elementOffset[1] - scrollerOffset[1];

							new Effect.Opacity(this.scroller, {
								from: 0,
								to: 1.0,
								duration: this.options.duration,
								afterFinish: (function () {
									if (this.controls) {
										this.activateControls();
									}
									if (this.options.afterMove && (typeof this.options.afterMove == 'function')) {
										this.options.afterMove();
									}
								}).bind(this)
							});
						}
					).bind(this)});
				break;
				case 'scroll':
				default:
					var transition;
					switch (this.options.transition) {
						case 'spring':
							transition = Effect.Transitions.spring;
							break;
						case 'sinoidal':
						default:
							transition = Effect.Transitions.sinoidal;
							break;
					}
	
					this.scrolling = new Effect.SmoothScroll(this.scroller, {
						duration: this.options.duration,
						x: (elementOffset[0] - scrollerOffset[0]),
						y: (elementOffset[1] - scrollerOffset[1]),
						transition: transition,
						afterFinish: (function () {
												
							if (this.controls) {
								this.activateControls();
							}
							if (this.options.afterMove && (typeof this.options.afterMove == 'function')) {
								this.options.afterMove();
							}                        
							this.scrolling = false;
						}).bind(this)});
				break;
			}
	
			return false;
		}
	},

	prev: function () {
		if (this.current) {
			var currentIndex = this.current._index;
			var prevIndex = (currentIndex == 0) ? (this.options.circular ? this.slides.length - 1 : 0) : currentIndex - 1;
        } else {
            var prevIndex = (this.options.circular ? this.slides.length - 1 : 0);
        }

		if (prevIndex == (this.slides.length - 1) && this.options.circular && this.options.effect != 'fade') {
			this.scroller.scrollLeft =  (this.slides.length - 1) * this.slides.first().getWidth();
			this.scroller.scrollTop =  (this.slides.length - 1) * this.slides.first().getHeight();
			prevIndex = this.slides.length - 2;
        }

		this.moveTo(this.slides[prevIndex]);
	},

	next: function () {
		if (this.current) {
			var currentIndex = this.current._index;
			var nextIndex = (this.slides.length - 1 == currentIndex) ? (this.options.circular ? 0 : currentIndex) : currentIndex + 1;
        } else {
            var nextIndex = 1;
        }
		
		if (nextIndex == 0 && this.options.circular && this.options.effect != 'fade') {
			this.scroller.scrollLeft = 0;
			this.scroller.scrollTop  = 0;
			nextIndex = 1;
        }

		if (nextIndex > this.slides.length - (this.options.visibleSlides + 1)) {
			nextIndex = this.slides.length - this.options.visibleSlides;
		}		

		this.moveTo(this.slides[nextIndex]);
	},

	first: function () {
		this.moveTo(this.slides[0]);
    },

	last: function () {
		this.moveTo(this.slides[this.slides.length - 1]);
    },

	toggle: function () {
		if (this.previous) {
			this.moveTo(this.slides[this.previous._index]);
        } else {
            return false;
        }
    },

	stop: function () {
		if (this.timer) {
			clearTimeout(this.timer);
		}
	},

	start: function () { 
        this.periodicallyUpdate();
    },

	pause: function () {
		this.stop();
		this.activateControls();
    },

	resume: function (event) {
		if (event) {
			var related = event.relatedTarget || event.toElement;
			if (!related || (!this.slides.include(related) && !this.slides.any(function (slide) { return related.descendantOf(slide); }))) {
				this.start();
            }
        } else {
            this.start();
        }
    },

	periodicallyUpdate: function () {
		if (this.timer != null) {
			clearTimeout(this.timer);
			this.next();
        }
		this.timer = setTimeout(this.periodicallyUpdate.bind(this), this.options.frequency * 1000);
    },
    
    wheel: function (event) {
        event.cancelBubble = true;
        event.stop();
        
		var delta = 0;
		if (!event) {
            event = window.event;
        }
		if (event.wheelDelta) {
			delta = event.wheelDelta / 120; 
		} else if (event.detail) { 
            delta = -event.detail / 3;	
        }        
       
        if (!this.scrolling) {
            this.deactivateControls();
            if (delta > 0) {
                this.prev();
            } else {
                this.next();
            }            
        }
        
		return Math.round(delta); //Safari Round
    },

	deactivateControls: function () {
		this.controls.invoke('addClassName', this.options.disabledClassName);
    },

	activateControls: function () {
		this.controls.invoke('removeClassName', this.options.disabledClassName);
    }
});


Effect.SmoothScroll = Class.create();
Object.extend(Object.extend(Effect.SmoothScroll.prototype, Effect.Base.prototype), {
	initialize: function (element) {
		this.element = $(element);
		var options = Object.extend({ x: 0, y: 0, mode: 'absolute' } , arguments[1] || {});
		this.start(options);
    },

	setup: function () {
		if (this.options.continuous && !this.element._ext) {
			this.element.cleanWhitespace();
			this.element._ext = true;
			this.element.appendChild(this.element.firstChild);
        }

		this.originalLeft = this.element.scrollLeft;
		this.originalTop  = this.element.scrollTop;

		if (this.options.mode == 'absolute') {
			this.options.x -= this.originalLeft;
			this.options.y -= this.originalTop;
        }
    },

	update: function (position) {
		this.element.scrollLeft = this.options.x * position + this.originalLeft;
		this.element.scrollTop  = this.options.y * position + this.originalTop;
    }
});

var FSfader = Class.create({
	initialize: function(wrapper,fader,items,options){
		this.wrapper = $(wrapper);
		this.fader = $(fader);
		this.items = $$(items);
		
		this.options =  Object.extend({
			layout: 'vertical',
			itemstyle: 'top',
			toShow: 4,
			jumperClass: 'jump',
			transition: Effect.Transitions.EaseTo
		}, arguments[3] || {});
		
		this.controls = $$( '#'+wrapper + ' a.'+ this.options.jumperClass);
		this.controls.invoke('observe', 'click', this.click.bind(this));
		
		this.current = 0;
		this.p = new Effect.Parallel([]);
		if (!this.options.itemWidth) { this.options.itemWidth = this.items[0].getWidth(); }
			
		this.space = Math.round((this.fader.getWidth() - this.options.itemWidth*this.options.toShow)/(this.options.toShow+1));
		
		var a=0, b=0;
		this.arrGroup = new Array;
		
		this.items.each(function(item,i){
			item.hide();
			
			if (a >= this.options.toShow) {
				a=0;
				b++;
			}
			
			if (typeof this.arrGroup[b] == 'undefined') this.arrGroup[b] = new Array;
			this.arrGroup[b].push(item);
			a++;
		}.bind(this));
		this.showGroup(this.arrGroup[0]);
	},
	
	showGroup: function(group) {
		group.each(function(item,i){
			item.setStyle({"left": (this.options.itemWidth*i + this.space*(i+1)) + "px"});
		}.bind(this));
		new Effect.multiple(group,Effect.Appear,{ speed: 0.1, duration: 0.4});
		
	},
	
	hideGroup: function(group) {
		this.p.cancel();
		group.each(function(item,i){
			var eff = new Effect.Fade(item, {duration: 0.3, from: 1, to: 0, delay: 0.1*i, sync: true});
			this.p.effects.push(eff);
		}.bind(this));
		this.p.start({
					 afterFinish: function () { 
						this.showGroup(this.arrGroup[this.toShow]);
						this.current=this.toShow;
					}.bind(this)
		});
	},
	
	
	next: function() {
		if (!this.toShow || this.toShow != this.current+1) {
			if (this.current != this.arrGroup.length-1 ) {
				this.toShow = this.current + 1;
			} else {
				this.toShow = 0;
			}
			this.hideGroup(this.arrGroup[this.current])
		}
	},
	
	prev: function() {
		if (!this.toShow || this.toShow != this.current-1) {
			if (this.current != 0 ) {
				this.toShow = this.current - 1;
			} else {
				this.toShow = this.arrGroup.length-1;
			}
			this.hideGroup(this.arrGroup[this.current])
		}
	},
	
	click: function (event) {
		event.stop();
		var element = event.findElement('a');
		if (!this.running) {
			eval("this." + element.rel + "()");
			}		
	}
});


//shadowbox
var Shadowbox=function(){var ua=navigator.userAgent.toLowerCase(),S={version:"3.0b",adapter:null,current:-1,gallery:[],cache:[],content:null,dimensions:null,plugins:null,path:"",options:{adapter:null,animate:true,animateFade:true,autoplayMovies:true,autoDimensions:false,continuous:false,counterLimit:10,counterType:"default",displayCounter:true,displayNav:true,ease:function(x){return 1+Math.pow(x-1,3)},enableKeys:true,errors:{fla:{name:"Flash",url:"http://www.adobe.com/products/flashplayer/"},qt:{name:"QuickTime",url:"http://www.apple.com/quicktime/download/"},wmp:{name:"Windows Media Player",url:"http://www.microsoft.com/windows/windowsmedia/"},f4m:{name:"Flip4Mac",url:"http://www.flip4mac.com/wmv_download.htm"}},ext:{img:["png","jpg","jpeg","gif","bmp"],swf:["swf"],flv:["flv"],qt:["dv","mov","moov","movie","mp4"],wmp:["asf","wm","wmv"],qtwmp:["avi","mpg","mpeg"],iframe:["asp","aspx","cgi","cfm","htm","html","jsp","pl","php","php3","php4","php5","phtml","rb","rhtml","shtml","txt","vbs"]},fadeDuration:0.35,flashParams:{bgcolor:"#000000",allowFullScreen:true},flashVars:{},flashVersion:"9.0.115",handleOversize:"resize",handleUnsupported:"link",initialHeight:160,initialWidth:320,language:"en",modal:false,onChange:null,onClose:null,onFinish:null,onOpen:null,overlayColor:"#000",overlayOpacity:0.8,players:["img"],resizeDuration:0.35,showOverlay:true,showMovieControls:true,skipSetup:false,slideshowDelay:0,useSizzle:true,viewportPadding:20},client:{isIE:ua.indexOf("msie")>-1,isIE6:ua.indexOf("msie 6")>-1,isIE7:ua.indexOf("msie 7")>-1,isGecko:ua.indexOf("gecko")>-1&&ua.indexOf("safari")==-1,isWebkit:ua.indexOf("applewebkit/")>-1,isWindows:ua.indexOf("windows")>-1||ua.indexOf("win32")>-1,isMac:ua.indexOf("macintosh")>-1||ua.indexOf("mac os x")>-1,isLinux:ua.indexOf("linux")>-1},regex:{domain:/:\/\/(.*?)[:\/]/,inline:/#(.+)$/,rel:/^(light|shadow)box/i,gallery:/^(light|shadow)box\[(.*?)\]/i,unsupported:/^unsupported-(\w+)/,param:/\s*([a-z_]*?)\s*=\s*(.+)\s*/},libraries:{Prototype:"prototype",jQuery:"jquery",MooTools:"mootools",YAHOO:"yui",dojo:"dojo",Ext:"ext"},applyOptions:function(opts){if(opts){default_options=apply({},S.options);apply(S.options,opts)}},buildCacheObj:function(link,opts){var href=link.href,obj={el:link,title:link.getAttribute("title"),options:apply({},opts||{}),content:href};each(["player","title","height","width","gallery"],function(o){if(typeof obj.options[o]!="undefined"){obj[o]=obj.options[o];delete obj.options[o]}});if(!obj.player){obj.player=getPlayer(href)}var rel=link.getAttribute("rel");if(rel){var m=rel.match(S.regex.gallery);if(m){obj.gallery=escape(m[2])}each(rel.split(";"),function(p){m=p.match(S.regex.param);if(m){if(m[1]=="options"){eval("apply(obj.options,"+m[2]+")")}else{obj[m[1]]=m[2]}}})}return obj},change:function(n){if(!S.gallery){return}if(!S.gallery[n]){if(!S.options.continuous){return}else{n=n<0?S.gallery.length-1:0}}S.current=n;if(typeof slide_timer=="number"){clearTimeout(slide_timer);slide_timer=null;slide_delay=slide_start=0}if(S.options.onChange){S.options.onChange()}loadContent()},clearCache:function(){each(S.cache,function(obj){if(obj.el){S.lib.removeEvent(obj.el,"click",handleClick)}});S.cache=[]},close:function(){if(!active){return}active=false;listenKeys(false);if(S.content){S.content.remove();S.content=null}if(typeof slide_timer=="number"){clearTimeout(slide_timer)}slide_timer=null;slide_delay=0;if(S.options.onClose){S.options.onClose()}S.skin.onClose();S.revertOptions();each(v_cache,function(c){c[0].style.visibility=c[1]})},contentId:function(){return content_id},getCounter:function(){var len=S.gallery.length;if(S.options.counterType=="skip"){var c=[],i=0,end=len,limit=parseInt(S.options.counterLimit)||0;if(limit<len&&limit>2){var h=Math.floor(limit/2);i=S.current-h;if(i<0){i+=len}end=S.current+(limit-h);if(end>len){end-=len}}while(i!=end){if(i==len){i=0}c.push(i++)}}else{var c=(S.current+1)+" "+S.lang.of+" "+len}return c},getCurrent:function(){return S.current>-1?S.gallery[S.current]:null},hasNext:function(){return S.gallery.length>1&&(S.current!=S.gallery.length-1||S.options.continuous)},init:function(opts){if(initialized){return}initialized=true;opts=opts||{};init_options=opts;if(opts){apply(S.options,opts)}for(var e in S.options.ext){S.regex[e]=new RegExp(".("+S.options.ext[e].join("|")+")s*$","i")}if(!S.path){var path_re=/(.+)shadowbox\.js/i,path;each(document.getElementsByTagName("script"),function(s){if((path=path_re.exec(s.src))!=null){S.path=path[1];return false}})}if(S.options.adapter){S.adapter=S.options.adapter}else{for(var lib in S.libraries){if(typeof window[lib]!="undefined"){S.adapter=S.libraries[lib];break}}if(!S.adapter){S.adapter="base"}}if(S.options.useSizzle&&!window.Sizzle){U.include(S.path+"libraries/sizzle/sizzle.js")}if(!S.lang){U.include(S.path+"languages/shadowbox-"+S.options.language+".js")}each(S.options.players,function(p){if((p=="swf"||p=="flv")&&!window.swfobject){U.include(S.path+"libraries/swfobject/swfobject.js")}if(!S[p]){U.include(S.path+"players/shadowbox-"+p+".js")}});if(!S.lib){U.include(S.path+"adapters/shadowbox-"+S.adapter+".js")}},isActive:function(){return active},isPaused:function(){return slide_timer=="paused"},load:function(){if(S.skin.options){apply(S.options,S.skin.options);apply(S.options,init_options)}var markup=S.skin.markup.replace(/\{(\w+)\}/g,function(m,p){return S.lang[p]});S.lib.append(document.body,markup);if(S.skin.init){S.skin.init()}var id;S.lib.addEvent(window,"resize",function(){if(id){clearTimeout(id);id=null}if(active){id=setTimeout(function(){if(S.skin.onWindowResize){S.skin.onWindowResize()}var c=S.content;if(c&&c.onWindowResize){c.onWindowResize()}},50)}});if(!S.options.skipSetup){S.setup()}},next:function(){S.change(S.current+1)},open:function(obj){if(U.isLink(obj)){obj=S.buildCacheObj(obj)}if(obj.constructor==Array){S.gallery=obj;S.current=0}else{if(!obj.gallery){S.gallery=[obj];S.current=0}else{S.current=null;S.gallery=[];each(S.cache,function(c){if(c.gallery&&c.gallery==obj.gallery){if(S.current==null&&c.content==obj.content&&c.title==obj.title){S.current=S.gallery.length}S.gallery.push(c)}});if(S.current==null){S.gallery.unshift(obj);S.current=0}}}obj=S.getCurrent();if(obj.options){S.revertOptions();S.applyOptions(obj.options)}var g,r,m,s,a,oe=S.options.errors,msg,el;for(var i=0;i<S.gallery.length;++i){g=S.gallery[i]=apply({},S.gallery[i]);r=false;if(g.player=="unsupported"){r=true}else{if(m=S.regex.unsupported.exec(g.player)){if(S.options.handleUnsupported=="link"){g.player="html";switch(m[1]){case"qtwmp":s="either";a=[oe.qt.url,oe.qt.name,oe.wmp.url,oe.wmp.name];break;case"qtf4m":s="shared";a=[oe.qt.url,oe.qt.name,oe.f4m.url,oe.f4m.name];break;default:s="single";if(m[1]=="swf"||m[1]=="flv"){m[1]="fla"}a=[oe[m[1]].url,oe[m[1]].name]}msg=S.lang.errors[s].replace(/\{(\d+)\}/g,function(m,n){return a[n]});g.content='<div class="sb-message">'+msg+"</div>"}else{r=true}}else{if(g.player=="inline"){m=S.regex.inline.exec(g.content);if(m){var el=U.get(m[1]);if(el){g.content=el.innerHTML}else{throw"Cannot find element with id "+m[1]}}else{throw"Cannot find element id for inline content"}}else{if(g.player=="swf"||g.player=="flv"){var version=(g.options&&g.options.flashVersion)||S.options.flashVersion;if(!swfobject.hasFlashPlayerVersion(version)){g.width=310;g.height=177}}}}}if(r){S.gallery.splice(i,1);if(i<S.current){--S.current}else{if(i==S.current){S.current=i>0?i-1:i}}--i}}if(S.gallery.length){if(!active){if(typeof S.options.onOpen=="function"&&S.options.onOpen(obj)===false){return}v_cache=[];each(["select","object","embed","canvas"],function(tag){each(document.getElementsByTagName(tag),function(el){v_cache.push([el,el.style.visibility||"visible"]);el.style.visibility="hidden"})});var h=S.options.autoDimensions&&"height" in obj?obj.height:S.options.initialHeight;var w=S.options.autoDimensions&&"width" in obj?obj.width:S.options.initialWidth;S.skin.onOpen(h,w,loadContent)}else{loadContent()}active=true}},pause:function(){if(typeof slide_timer!="number"){return}var time=new Date().getTime();slide_delay=Math.max(0,slide_delay-(time-slide_start));if(slide_delay){clearTimeout(slide_timer);slide_timer="paused";if(S.skin.onPause){S.skin.onPause()}}},play:function(){if(!S.hasNext()){return}if(!slide_delay){slide_delay=S.options.slideshowDelay*1000}if(slide_delay){slide_start=new Date().getTime();slide_timer=setTimeout(function(){slide_delay=slide_start=0;S.next()},slide_delay);if(S.skin.onPlay){S.skin.onPlay()}}},previous:function(){S.change(S.current-1)},revertOptions:function(){apply(S.options,default_options)},setDimensions:function(height,width,max_h,max_w,tb,lr,resizable){var h=height=parseInt(height),w=width=parseInt(width),pad=parseInt(S.options.viewportPadding)||0;var extra_h=2*pad+tb;if(h+extra_h>=max_h){h=max_h-extra_h}var extra_w=2*pad+lr;if(w+extra_w>=max_w){w=max_w-extra_w}var resize_h=height,resize_w=width,change_h=(height-h)/height,change_w=(width-w)/width,oversized=(change_h>0||change_w>0);if(resizable&&oversized&&S.options.handleOversize=="resize"){if(change_h>change_w){w=Math.round((width/height)*h)}else{if(change_w>change_h){h=Math.round((height/width)*w)}}resize_w=w;resize_h=h}S.dimensions={height:h+tb,width:w+lr,inner_h:h,inner_w:w,top:(max_h-(h+extra_h))/2+pad,left:(max_w-(w+extra_w))/2+pad,oversized:oversized,resize_h:resize_h,resize_w:resize_w};return S.dimensions},setup:function(links,opts){if(!links){var links=[],rel;each(document.getElementsByTagName("a"),function(a){rel=a.getAttribute("rel");if(rel&&S.regex.rel.test(rel)){links.push(a)}})}else{var len=links.length;if(len){if(window.Sizzle){if(typeof links=="string"){links=Sizzle(links)}else{if(len==2&&links.push&&typeof links[0]=="string"&&links[1].nodeType){links=Sizzle(links[0],links[1])}}}}else{links=[links]}}each(links,function(link){if(typeof link.shadowboxCacheKey=="undefined"){link.shadowboxCacheKey=S.cache.length;S.lib.addEvent(link,"click",handleClick)}S.cache[link.shadowboxCacheKey]=S.buildCacheObj(link,opts)})}},U=S.util={animate:function(el,p,to,d,cb){var from=parseFloat(S.lib.getStyle(el,p));if(isNaN(from)){from=0}var delta=to-from;if(delta==0){if(cb){cb()}return}var op=p=="opacity";function fn(ease){var to=from+ease*delta;if(op){U.setOpacity(el,to)}else{el.style[p]=to+"px"}}if(!d||(!op&&!S.options.animate)||(op&&!S.options.animateFade)){fn(1);if(cb){cb()}return}d*=1000;var begin=new Date().getTime(),end=begin+d,time,timer=setInterval(function(){time=new Date().getTime();if(time>=end){clearInterval(timer);fn(1);if(cb){cb()}}else{fn(S.options.ease((time-begin)/d))}},10)},apply:function(o,e){for(var p in e){o[p]=e[p]}return o},clearOpacity:function(el){var s=el.style;if(window.ActiveXObject){if(typeof s.filter=="string"&&(/alpha/i).test(s.filter)){s.filter=s.filter.replace(/[\w\.]*alpha\(.*?\);?/i,"")}}else{s.opacity=""}},each:function(obj,fn,scope){for(var i=0,len=obj.length;i<len;++i){if(fn.call(scope||obj[i],obj[i],i,obj)===false){return}}},get:function(id){return document.getElementById(id)},include:function(){var includes={};return function(file){if(includes[file]){return}includes[file]=true;document.write('<script type="text/javascript" src="'+file+'"><\/script>')}}(),isLink:function(obj){if(!obj||!obj.tagName){return false}var up=obj.tagName.toUpperCase();return up=="A"||up=="AREA"},removeChildren:function(el){while(el.firstChild){el.removeChild(el.firstChild)}},setOpacity:function(el,o){var s=el.style;if(window.ActiveXObject){s.zoom=1;s.filter=(s.filter||"").replace(/\s*alpha\([^\)]*\)/gi,"")+(o==1?"":" alpha(opacity="+(o*100)+")")}else{s.opacity=o}}},apply=U.apply,each=U.each,init_options,initialized=false,default_options={},content_id="sb-content",active=false,slide_timer,slide_start,slide_delay=0,v_cache=[];if(navigator.plugins&&navigator.plugins.length){var names=[];each(navigator.plugins,function(p){names.push(p.name)});names=names.join();var detectPlugin=function(n){return names.indexOf(n)>-1};var f4m=detectPlugin("Flip4Mac");S.plugins={fla:detectPlugin("Shockwave Flash"),qt:detectPlugin("QuickTime"),wmp:!f4m&&detectPlugin("Windows Media"),f4m:f4m}}else{function detectPlugin(n){try{var axo=new ActiveXObject(n)}catch(e){}return !!axo}S.plugins={fla:detectPlugin("ShockwaveFlash.ShockwaveFlash"),qt:detectPlugin("QuickTime.QuickTime"),wmp:detectPlugin("wmplayer.ocx"),f4m:false}}function getPlayer(url){var re=S.regex,p=S.plugins,m=url.match(re.domain),d=m&&document.domain==m[1];if(url.indexOf("#")>-1&&d){return"inline"}var q=url.indexOf("?");if(q>-1){url=url.substring(0,q)}if(re.img.test(url)){return"img"}if(re.swf.test(url)){return p.fla?"swf":"unsupported-swf"}if(re.flv.test(url)){return p.fla?"flv":"unsupported-flv"}if(re.qt.test(url)){return p.qt?"qt":"unsupported-qt"}if(re.wmp.test(url)){if(p.wmp){return"wmp"}if(p.f4m){return"qt"}if(S.client.isMac){return p.qt?"unsupported-f4m":"unsupported-qtf4m"}return"unsupported-wmp"}if(re.qtwmp.test(url)){if(p.qt){return"qt"}if(p.wmp){return"wmp"}return S.client.isMac?"unsupported-qt":"unsupported-qtwmp"}if(!d||re.iframe.test(url)){return"iframe"}return"unsupported"}function handleClick(e){var link;if(U.isLink(this)){link=this}else{link=S.lib.getTarget(e);while(!U.isLink(link)&&link.parentNode){link=link.parentNode}}if(link){var key=link.shadowboxCacheKey;if(typeof key!="undefined"&&typeof S.cache[key]!="undefined"){link=S.cache[key]}S.open(link);if(S.gallery.length){S.lib.preventDefault(e)}}}function listenKeys(on){if(!S.options.enableKeys){return}S.lib[(on?"add":"remove")+"Event"](document,"keydown",handleKey)}function handleKey(e){var code=S.lib.keyCode(e);S.lib.preventDefault(e);switch(code){case 81:case 88:case 27:S.close();break;case 37:S.previous();break;case 39:S.next();break;case 32:S[(typeof slide_timer=="number"?"pause":"play")]()}}function loadContent(){var obj=S.getCurrent();if(!obj){return}var p=obj.player=="inline"?"html":obj.player;if(typeof S[p]!="function"){throw"Unknown player: "+p}var change=false;if(S.content){S.content.remove();change=true;S.revertOptions();if(obj.options){S.applyOptions(obj.options)}}U.removeChildren(S.skin.bodyEl());S.content=new S[p](obj);listenKeys(false);S.skin.onLoad(S.content,change,function(){if(!S.content){return}if(typeof S.content.ready!="undefined"){var id=setInterval(function(){if(S.content){if(S.content.ready){clearInterval(id);id=null;S.skin.onReady(contentReady)}}else{clearInterval(id);id=null}},100)}else{S.skin.onReady(contentReady)}});if(S.gallery.length>1){var next=S.gallery[S.current+1]||S.gallery[0];if(next.player=="img"){var a=new Image();a.src=next.content}var prev=S.gallery[S.current-1]||S.gallery[S.gallery.length-1];if(prev.player=="img"){var b=new Image();b.src=prev.content}}}function contentReady(){if(!S.content){return}S.content.append(S.skin.bodyEl(),content_id,S.dimensions);S.skin.onFinish(finishContent)}function finishContent(){if(!S.content){return}if(S.content.onLoad){S.content.onLoad()}if(S.options.onFinish){S.options.onFinish()}if(!S.isPaused()){S.play()}listenKeys(true)}return S}();Shadowbox.skin=function(){var e=Shadowbox,d=e.util,o=false,k=["sb-nav-close","sb-nav-next","sb-nav-play","sb-nav-pause","sb-nav-previous"];function l(){d.get("sb-container").style.top=document.documentElement.scrollTop+"px"}function g(p){var q=d.get("sb-overlay"),r=d.get("sb-container"),t=d.get("sb-wrapper");if(p){if(e.client.isIE6){l();e.lib.addEvent(window,"scroll",l)}if(e.options.showOverlay){o=true;q.style.backgroundColor=e.options.overlayColor;d.setOpacity(q,0);if(!e.options.modal){e.lib.addEvent(q,"click",e.close)}t.style.display="none"}r.style.visibility="visible";if(o){var s=parseFloat(e.options.overlayOpacity);d.animate(q,"opacity",s,e.options.fadeDuration,p)}else{p()}}else{if(e.client.isIE6){e.lib.removeEvent(window,"scroll",l)}e.lib.removeEvent(q,"click",e.close);if(o){t.style.display="none";d.animate(q,"opacity",0,e.options.fadeDuration,function(){r.style.display="";t.style.display="";d.clearOpacity(q)})}else{r.style.visibility="hidden"}}}function b(r,p){var q=d.get("sb-nav-"+r);if(q){q.style.display=p?"":"none"}}function i(r,q){var t=d.get("sb-loading"),v=e.getCurrent().player,u=(v=="img"||v=="html");if(r){function s(){d.clearOpacity(t);if(q){q()}}d.setOpacity(t,0);t.style.display="";if(u){d.animate(t,"opacity",1,e.options.fadeDuration,s)}else{s()}}else{function s(){t.style.display="none";d.clearOpacity(t);if(q){q()}}if(u){d.animate(t,"opacity",0,e.options.fadeDuration,s)}else{s()}}}function a(s){var u=e.getCurrent();d.get("sb-title-inner").innerHTML=u.title||"";var x,r,t,y,q;if(e.options.displayNav){x=true;var w=e.gallery.length;if(w>1){if(e.options.continuous){r=q=true}else{r=(w-1)>e.current;q=e.current>0}}if(e.options.slideshowDelay>0&&e.hasNext()){y=!e.isPaused();t=!y}}else{x=r=t=y=q=false}b("close",x);b("next",r);b("play",t);b("pause",y);b("previous",q);var x="";if(e.options.displayCounter&&e.gallery.length>1){var v=e.getCounter();if(typeof v=="string"){x=v}else{d.each(v,function(p){x+='<a onclick="Shadowbox.change('+p+');"';if(p==e.current){x+=' class="sb-counter-current"'}x+=">"+(p+1)+"</a>"})}}d.get("sb-counter").innerHTML=x;s()}function h(r,q){var w=d.get("sb-wrapper"),z=d.get("sb-title"),s=d.get("sb-info"),p=d.get("sb-title-inner"),x=d.get("sb-info-inner"),y=parseInt(e.lib.getStyle(p,"height"))||0,v=parseInt(e.lib.getStyle(x,"height"))||0;function u(){p.style.visibility=x.style.visibility="hidden";a(q)}if(r){d.animate(z,"height",0,0.35);d.animate(s,"height",0,0.35);d.animate(w,"paddingTop",y,0.35);d.animate(w,"paddingBottom",v,0.35,u)}else{z.style.height=s.style.height="0px";w.style.paddingTop=y+"px";w.style.paddingBottom=v+"px";u()}}function j(r){var q=d.get("sb-wrapper"),u=d.get("sb-title"),s=d.get("sb-info"),x=d.get("sb-title-inner"),w=d.get("sb-info-inner"),v=parseInt(e.lib.getStyle(x,"height"))||0,p=parseInt(e.lib.getStyle(w,"height"))||0;x.style.visibility=w.style.visibility="";if(x.innerHTML!=""){d.animate(u,"height",v,0.35);d.animate(q,"paddingTop",0,0.35)}d.animate(s,"height",p,0.35);d.animate(q,"paddingBottom",0,0.35,r)}function c(q,x,w,p){var y=d.get("sb-body"),v=d.get("sb-wrapper"),u=parseInt(q),r=parseInt(x);if(w){d.animate(y,"height",u,e.options.resizeDuration);d.animate(v,"top",r,e.options.resizeDuration,p)}else{y.style.height=u+"px";v.style.top=r+"px";if(p){p()}}}function f(u,x,v,p){var t=d.get("sb-wrapper"),r=parseInt(u),q=parseInt(x);if(v){d.animate(t,"width",r,e.options.resizeDuration);d.animate(t,"left",q,e.options.resizeDuration,p)}else{t.style.width=r+"px";t.style.left=q+"px";if(p){p()}}}function n(p){var r=e.content;if(!r){return}var q=m(r.height,r.width,r.resizable);switch(e.options.animSequence){case"hw":c(q.inner_h,q.top,true,function(){f(q.width,q.left,true,p)});break;case"wh":f(q.width,q.left,true,function(){c(q.inner_h,q.top,true,p)});break;default:f(q.width,q.left,true);c(q.inner_h,q.top,true,p)}}function m(p,s,r){var q=d.get("sb-body-inner");sw=d.get("sb-wrapper"),so=d.get("sb-overlay"),tb=sw.offsetHeight-q.offsetHeight,lr=sw.offsetWidth-q.offsetWidth,max_h=so.offsetHeight,max_w=so.offsetWidth;return e.setDimensions(p,s,max_h,max_w,tb,lr,r)}return{markup:'<div id="sb-container"><div id="sb-overlay"></div><div id="sb-wrapper"><div id="sb-title"><div id="sb-title-inner"></div></div><div id="sb-body"><div id="sb-body-inner"></div><div id="sb-loading"><a onclick="Shadowbox.close()">{cancel}</a></div></div><div id="sb-info"><div id="sb-info-inner"><div id="sb-counter"></div><div id="sb-nav"><a id="sb-nav-close" title="{close}" onclick="Shadowbox.close()"></a><a id="sb-nav-next" title="{next}" onclick="Shadowbox.next()"></a><a id="sb-nav-play" title="{play}" onclick="Shadowbox.play()"></a><a id="sb-nav-pause" title="{pause}" onclick="Shadowbox.pause()"></a><a id="sb-nav-previous" title="{previous}" onclick="Shadowbox.previous()"></a></div><div style="clear:both"></div></div></div></div></div>',options:{animSequence:"sync"},init:function(){if(e.client.isIE6){d.get("sb-body").style.zoom=1;var r,p,q=/url\("(.*\.png)"\)/;d.each(k,function(s){r=d.get(s);if(r){p=e.lib.getStyle(r,"backgroundImage").match(q);if(p){r.style.backgroundImage="none";r.style.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,src="+p[1]+",sizingMethod=scale);"}}})}},bodyEl:function(){return d.get("sb-body-inner")},onOpen:function(r,q,p){d.get("sb-container").style.display="block";var s=m(r,q);c(s.inner_h,s.top,false);f(s.width,s.left,false);g(p)},onLoad:function(q,r,p){i(true);h(r,function(){if(!q){return}if(!r){d.get("sb-wrapper").style.display=""}p()})},onReady:function(p){n(function(){j(p)})},onFinish:function(p){i(false,p)},onClose:function(){g(false)},onPlay:function(){b("play",false);b("pause",true)},onPause:function(){b("pause",false);b("play",true)},onWindowResize:function(){var r=e.content;if(!r){return}var q=m(r.height,r.width,r.resizable);f(q.width,q.left,false);c(q.inner_h,q.top,false);var p=d.get(e.contentId());if(p){if(r.resizable&&e.options.handleOversize=="resize"){p.height=q.resize_h;p.width=q.resize_w}}}}}();

//shadowbox-extend
if(typeof e107_selectedInputArea=="undefined"){var e107_selectedInputArea}Object.extend(Shadowbox,{getSelectedInputArea:function(){return typeof e107Helper==="object"?e107Helper.BB.__selectedInputArea:e107_selectedInputArea||null},e107addText:function(a){if(!this.getSelectedInputArea()){alert(this.options.alertSelected);return}addtext(this.implodeContentFields(a));this.close();return false},implodeContentFields:function(a){return this.runImplodeMod(a)},runImplodeMod:function(b){var a,c=$(b.form);switch(b.mod){case"advanced_sgallery":case"default_sgallery":a=this.runImplodeSgallery(b.mod,c);break;default:a=this.runImplodeDefault(b.mod,c);break}return a},runImplodeDefault:function(e,d){var i="",a="",b="",c="",f="",h="",g="";d.getElements().each(function(j){switch(j.name){case"sb_img":i=j.value;break;case"sb_thimg":f=j.value;break;case"sb_thtext":if(!f&&j.value){f=j.value}break;case"sb_title":a=j.value;break;case"sb_caption":h=j.value;break;case"sb_params":g=j.value;break;case"sb_group":b=j.value;break;case"sb_float":c=j.value;break}});a+="::"+h;if(g){a+="::"+g}return"[shadowbox="+i+"|"+a+"|"+b+"|"+c+"]"+f+"[/shadowbox]"},runImplodeSgallery:function(g,f){var k="",a="",c="",d="",h="",e="",b="",j="",i="";f.getElements().each(function(l){switch(l.name){case"sb_img":k=l.value;break;case"sb_thw":h=l.value;break;case"sb_thh":e=l.value;break;case"sb_thfar":b=l.value;break;case"sb_title":a=l.value;break;case"sb_caption":j=l.value;break;case"sb_params":i=l.value;break;case"sb_group":c=l.value;break;case"sb_float":d=l.value;break}});a+="::"+j;if(i){a+="::"+i}return"[thumb="+h+","+e+","+b+"|"+a+"|"+c+"|"+d+"]"+k+"[/thumb]"}});

Shadowbox.skin.markup = '<div id="sb-container"><div id="sb-overlay"></div><div id="sb-wrapper"><div id="sb-title"><div id="sb-title-inner" style="width:50%;float:left"></div><div id="sb-close-box" style="width:50%;float:left;position:relative;display:block;"><a id="sb-nav-close" title="{close}" onclick="Shadowbox.close()"></a></div></div><div id="sb-body"><div id="sb-body-inner"></div><div id="sb-loading"><a onclick="Shadowbox.close()">{cancel}</a></div></div><div id="sb-info"><div id="sb-info-inner"><div id="sb-counter"></div><div id="sb-nav"><a id="sb-nav-next" title="{next}" onclick="Shadowbox.next()"></a><a id="sb-nav-play" title="{play}" onclick="Shadowbox.play()"></a><a id="sb-nav-pause" title="{pause}" onclick="Shadowbox.pause()"></a><a id="sb-nav-previous" title="{previous}" onclick="Shadowbox.previous()"></a></div><div style="clear:both"></div></div></div></div></div>';

//adapters/shadowbox-prototype
if(typeof Prototype=="undefined"){throw"Unable to load Shadowbox adapter, Prototype not found"}if(typeof Shadowbox=="undefined"){throw"Unable to load Shadowbox adapter, Shadowbox not found"}Shadowbox.lib={getStyle:function(b,a){return Element.getStyle(b,a)},remove:function(a){Element.remove(a)},getTarget:function(a){return Event.element(a)},getPageXY:function(b){var a=Event.pointer(b);return[a.x,a.y]},preventDefault:function(a){Event.stop(a)},keyCode:function(a){return a.keyCode},addEvent:function(c,a,b){Event.observe(c,a,b)},removeEvent:function(c,a,b){Event.stopObserving(c,a,b)},append:function(b,a){Element.insert(b,a)}};document.observe("dom:loaded",Shadowbox.load);

//libraries/sizzle/sizzle
/*
 * Sizzle CSS Selector Engine - v1.0
 *  Copyright 2009, The Dojo Foundation
 *  Released under the MIT, BSD, and GPL Licenses.
 *  More information: http://sizzlejs.com/
 */
(function(){var p=/((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^[\]]*\]|['"][^'"]*['"]|[^[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?/g,i=0,d=Object.prototype.toString,n=false;var b=function(D,t,A,v){A=A||[];var e=t=t||document;if(t.nodeType!==1&&t.nodeType!==9){return[]}if(!D||typeof D!=="string"){return A}var B=[],C,y,G,F,z,s,r=true,w=o(t);p.lastIndex=0;while((C=p.exec(D))!==null){B.push(C[1]);if(C[2]){s=RegExp.rightContext;break}}if(B.length>1&&j.exec(D)){if(B.length===2&&f.relative[B[0]]){y=g(B[0]+B[1],t)}else{y=f.relative[B[0]]?[t]:b(B.shift(),t);while(B.length){D=B.shift();if(f.relative[D]){D+=B.shift()}y=g(D,y)}}}else{if(!v&&B.length>1&&t.nodeType===9&&!w&&f.match.ID.test(B[0])&&!f.match.ID.test(B[B.length-1])){var H=b.find(B.shift(),t,w);t=H.expr?b.filter(H.expr,H.set)[0]:H.set[0]}if(t){var H=v?{expr:B.pop(),set:a(v)}:b.find(B.pop(),B.length===1&&(B[0]==="~"||B[0]==="+")&&t.parentNode?t.parentNode:t,w);y=H.expr?b.filter(H.expr,H.set):H.set;if(B.length>0){G=a(y)}else{r=false}while(B.length){var u=B.pop(),x=u;if(!f.relative[u]){u=""}else{x=B.pop()}if(x==null){x=t}f.relative[u](G,x,w)}}else{G=B=[]}}if(!G){G=y}if(!G){throw"Syntax error, unrecognized expression: "+(u||D)}if(d.call(G)==="[object Array]"){if(!r){A.push.apply(A,G)}else{if(t&&t.nodeType===1){for(var E=0;G[E]!=null;E++){if(G[E]&&(G[E]===true||G[E].nodeType===1&&h(t,G[E]))){A.push(y[E])}}}else{for(var E=0;G[E]!=null;E++){if(G[E]&&G[E].nodeType===1){A.push(y[E])}}}}}else{a(G,A)}if(s){b(s,e,A,v);b.uniqueSort(A)}return A};b.uniqueSort=function(r){if(c){n=false;r.sort(c);if(n){for(var e=1;e<r.length;e++){if(r[e]===r[e-1]){r.splice(e--,1)}}}}};b.matches=function(e,r){return b(e,null,null,r)};b.find=function(x,e,y){var w,u;if(!x){return[]}for(var t=0,s=f.order.length;t<s;t++){var v=f.order[t],u;if((u=f.match[v].exec(x))){var r=RegExp.leftContext;if(r.substr(r.length-1)!=="\\"){u[1]=(u[1]||"").replace(/\\/g,"");w=f.find[v](u,e,y);if(w!=null){x=x.replace(f.match[v],"");break}}}}if(!w){w=e.getElementsByTagName("*")}return{set:w,expr:x}};b.filter=function(A,z,D,t){var s=A,F=[],x=z,v,e,w=z&&z[0]&&o(z[0]);while(A&&z.length){for(var y in f.filter){if((v=f.match[y].exec(A))!=null){var r=f.filter[y],E,C;e=false;if(x==F){F=[]}if(f.preFilter[y]){v=f.preFilter[y](v,x,D,F,t,w);if(!v){e=E=true}else{if(v===true){continue}}}if(v){for(var u=0;(C=x[u])!=null;u++){if(C){E=r(C,v,u,x);var B=t^!!E;if(D&&E!=null){if(B){e=true}else{x[u]=false}}else{if(B){F.push(C);e=true}}}}}if(E!==undefined){if(!D){x=F}A=A.replace(f.match[y],"");if(!e){return[]}break}}}if(A==s){if(e==null){throw"Syntax error, unrecognized expression: "+A}else{break}}s=A}return x};var f=b.selectors={order:["ID","NAME","TAG"],match:{ID:/#((?:[\w\u00c0-\uFFFF_-]|\\.)+)/,CLASS:/\.((?:[\w\u00c0-\uFFFF_-]|\\.)+)/,NAME:/\[name=['"]*((?:[\w\u00c0-\uFFFF_-]|\\.)+)['"]*\]/,ATTR:/\[\s*((?:[\w\u00c0-\uFFFF_-]|\\.)+)\s*(?:(\S?=)\s*(['"]*)(.*?)\3|)\s*\]/,TAG:/^((?:[\w\u00c0-\uFFFF\*_-]|\\.)+)/,CHILD:/:(only|nth|last|first)-child(?:\((even|odd|[\dn+-]*)\))?/,POS:/:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^-]|$)/,PSEUDO:/:((?:[\w\u00c0-\uFFFF_-]|\\.)+)(?:\((['"]*)((?:\([^\)]+\)|[^\2\(\)]*)+)\2\))?/},attrMap:{"class":"className","for":"htmlFor"},attrHandle:{href:function(e){return e.getAttribute("href")}},relative:{"+":function(x,e,w){var u=typeof e==="string",y=u&&!/\W/.test(e),v=u&&!y;if(y&&!w){e=e.toUpperCase()}for(var t=0,s=x.length,r;t<s;t++){if((r=x[t])){while((r=r.previousSibling)&&r.nodeType!==1){}x[t]=v||r&&r.nodeName===e?r||false:r===e}}if(v){b.filter(e,x,true)}},">":function(w,r,x){var u=typeof r==="string";if(u&&!/\W/.test(r)){r=x?r:r.toUpperCase();for(var s=0,e=w.length;s<e;s++){var v=w[s];if(v){var t=v.parentNode;w[s]=t.nodeName===r?t:false}}}else{for(var s=0,e=w.length;s<e;s++){var v=w[s];if(v){w[s]=u?v.parentNode:v.parentNode===r}}if(u){b.filter(r,w,true)}}},"":function(t,r,v){var s=i++,e=q;if(!r.match(/\W/)){var u=r=v?r:r.toUpperCase();e=m}e("parentNode",r,s,t,u,v)},"~":function(t,r,v){var s=i++,e=q;if(typeof r==="string"&&!r.match(/\W/)){var u=r=v?r:r.toUpperCase();e=m}e("previousSibling",r,s,t,u,v)}},find:{ID:function(r,s,t){if(typeof s.getElementById!=="undefined"&&!t){var e=s.getElementById(r[1]);return e?[e]:[]}},NAME:function(s,v,w){if(typeof v.getElementsByName!=="undefined"){var r=[],u=v.getElementsByName(s[1]);for(var t=0,e=u.length;t<e;t++){if(u[t].getAttribute("name")===s[1]){r.push(u[t])}}return r.length===0?null:r}},TAG:function(e,r){return r.getElementsByTagName(e[1])}},preFilter:{CLASS:function(t,r,s,e,w,x){t=" "+t[1].replace(/\\/g,"")+" ";if(x){return t}for(var u=0,v;(v=r[u])!=null;u++){if(v){if(w^(v.className&&(" "+v.className+" ").indexOf(t)>=0)){if(!s){e.push(v)}}else{if(s){r[u]=false}}}}return false},ID:function(e){return e[1].replace(/\\/g,"")},TAG:function(r,e){for(var s=0;e[s]===false;s++){}return e[s]&&o(e[s])?r[1]:r[1].toUpperCase()},CHILD:function(e){if(e[1]=="nth"){var r=/(-?)(\d*)n((?:\+|-)?\d*)/.exec(e[2]=="even"&&"2n"||e[2]=="odd"&&"2n+1"||!/\D/.test(e[2])&&"0n+"+e[2]||e[2]);e[2]=(r[1]+(r[2]||1))-0;e[3]=r[3]-0}e[0]=i++;return e},ATTR:function(u,r,s,e,v,w){var t=u[1].replace(/\\/g,"");if(!w&&f.attrMap[t]){u[1]=f.attrMap[t]}if(u[2]==="~="){u[4]=" "+u[4]+" "}return u},PSEUDO:function(u,r,s,e,v){if(u[1]==="not"){if(u[3].match(p).length>1||/^\w/.test(u[3])){u[3]=b(u[3],null,null,r)}else{var t=b.filter(u[3],r,s,true^v);if(!s){e.push.apply(e,t)}return false}}else{if(f.match.POS.test(u[0])||f.match.CHILD.test(u[0])){return true}}return u},POS:function(e){e.unshift(true);return e}},filters:{enabled:function(e){return e.disabled===false&&e.type!=="hidden"},disabled:function(e){return e.disabled===true},checked:function(e){return e.checked===true},selected:function(e){e.parentNode.selectedIndex;return e.selected===true},parent:function(e){return !!e.firstChild},empty:function(e){return !e.firstChild},has:function(s,r,e){return !!b(e[3],s).length},header:function(e){return/h\d/i.test(e.nodeName)},text:function(e){return"text"===e.type},radio:function(e){return"radio"===e.type},checkbox:function(e){return"checkbox"===e.type},file:function(e){return"file"===e.type},password:function(e){return"password"===e.type},submit:function(e){return"submit"===e.type},image:function(e){return"image"===e.type},reset:function(e){return"reset"===e.type},button:function(e){return"button"===e.type||e.nodeName.toUpperCase()==="BUTTON"},input:function(e){return/input|select|textarea|button/i.test(e.nodeName)}},setFilters:{first:function(r,e){return e===0},last:function(s,r,e,t){return r===t.length-1},even:function(r,e){return e%2===0},odd:function(r,e){return e%2===1},lt:function(s,r,e){return r<e[3]-0},gt:function(s,r,e){return r>e[3]-0},nth:function(s,r,e){return e[3]-0==r},eq:function(s,r,e){return e[3]-0==r}},filter:{PSEUDO:function(w,s,t,x){var r=s[1],u=f.filters[r];if(u){return u(w,t,s,x)}else{if(r==="contains"){return(w.textContent||w.innerText||"").indexOf(s[3])>=0}else{if(r==="not"){var v=s[3];for(var t=0,e=v.length;t<e;t++){if(v[t]===w){return false}}return true}}}},CHILD:function(e,t){var w=t[1],r=e;switch(w){case"only":case"first":while(r=r.previousSibling){if(r.nodeType===1){return false}}if(w=="first"){return true}r=e;case"last":while(r=r.nextSibling){if(r.nodeType===1){return false}}return true;case"nth":var s=t[2],z=t[3];if(s==1&&z==0){return true}var v=t[0],y=e.parentNode;if(y&&(y.sizcache!==v||!e.nodeIndex)){var u=0;for(r=y.firstChild;r;r=r.nextSibling){if(r.nodeType===1){r.nodeIndex=++u}}y.sizcache=v}var x=e.nodeIndex-z;if(s==0){return x==0}else{return(x%s==0&&x/s>=0)}}},ID:function(r,e){return r.nodeType===1&&r.getAttribute("id")===e},TAG:function(r,e){return(e==="*"&&r.nodeType===1)||r.nodeName===e},CLASS:function(r,e){return(" "+(r.className||r.getAttribute("class"))+" ").indexOf(e)>-1},ATTR:function(v,t){var s=t[1],e=f.attrHandle[s]?f.attrHandle[s](v):v[s]!=null?v[s]:v.getAttribute(s),w=e+"",u=t[2],r=t[4];return e==null?u==="!=":u==="="?w===r:u==="*="?w.indexOf(r)>=0:u==="~="?(" "+w+" ").indexOf(r)>=0:!r?w&&e!==false:u==="!="?w!=r:u==="^="?w.indexOf(r)===0:u==="$="?w.substr(w.length-r.length)===r:u==="|="?w===r||w.substr(0,r.length+1)===r+"-":false},POS:function(u,r,s,v){var e=r[2],t=f.setFilters[e];if(t){return t(u,s,r,v)}}}};var j=f.match.POS;for(var l in f.match){f.match[l]=new RegExp(f.match[l].source+/(?![^\[]*\])(?![^\(]*\))/.source)}var a=function(r,e){r=Array.prototype.slice.call(r);if(e){e.push.apply(e,r);return e}return r};try{Array.prototype.slice.call(document.documentElement.childNodes)}catch(k){a=function(u,t){var r=t||[];if(d.call(u)==="[object Array]"){Array.prototype.push.apply(r,u)}else{if(typeof u.length==="number"){for(var s=0,e=u.length;s<e;s++){r.push(u[s])}}else{for(var s=0;u[s];s++){r.push(u[s])}}}return r}}var c;if(document.documentElement.compareDocumentPosition){c=function(r,e){var s=r.compareDocumentPosition(e)&4?-1:r===e?0:1;if(s===0){n=true}return s}}else{if("sourceIndex" in document.documentElement){c=function(r,e){var s=r.sourceIndex-e.sourceIndex;if(s===0){n=true}return s}}else{if(document.createRange){c=function(t,r){var s=t.ownerDocument.createRange(),e=r.ownerDocument.createRange();s.selectNode(t);s.collapse(true);e.selectNode(r);e.collapse(true);var u=s.compareBoundaryPoints(Range.START_TO_END,e);if(u===0){n=true}return u}}}}(function(){var r=document.createElement("div"),s="script"+(new Date).getTime();r.innerHTML="<a name='"+s+"'/>";var e=document.documentElement;e.insertBefore(r,e.firstChild);if(!!document.getElementById(s)){f.find.ID=function(u,v,w){if(typeof v.getElementById!=="undefined"&&!w){var t=v.getElementById(u[1]);return t?t.id===u[1]||typeof t.getAttributeNode!=="undefined"&&t.getAttributeNode("id").nodeValue===u[1]?[t]:undefined:[]}};f.filter.ID=function(v,t){var u=typeof v.getAttributeNode!=="undefined"&&v.getAttributeNode("id");return v.nodeType===1&&u&&u.nodeValue===t}}e.removeChild(r)})();(function(){var e=document.createElement("div");e.appendChild(document.createComment(""));if(e.getElementsByTagName("*").length>0){f.find.TAG=function(r,v){var u=v.getElementsByTagName(r[1]);if(r[1]==="*"){var t=[];for(var s=0;u[s];s++){if(u[s].nodeType===1){t.push(u[s])}}u=t}return u}}e.innerHTML="<a href='#'></a>";if(e.firstChild&&typeof e.firstChild.getAttribute!=="undefined"&&e.firstChild.getAttribute("href")!=="#"){f.attrHandle.href=function(r){return r.getAttribute("href",2)}}})();if(document.querySelectorAll){(function(){var e=b,s=document.createElement("div");s.innerHTML="<p class='TEST'></p>";if(s.querySelectorAll&&s.querySelectorAll(".TEST").length===0){return}b=function(w,v,t,u){v=v||document;if(!u&&v.nodeType===9&&!o(v)){try{return a(v.querySelectorAll(w),t)}catch(x){}}return e(w,v,t,u)};for(var r in e){b[r]=e[r]}})()}if(document.getElementsByClassName&&document.documentElement.getElementsByClassName){(function(){var e=document.createElement("div");e.innerHTML="<div class='test e'></div><div class='test'></div>";if(e.getElementsByClassName("e").length===0){return}e.lastChild.className="e";if(e.getElementsByClassName("e").length===1){return}f.order.splice(1,0,"CLASS");f.find.CLASS=function(r,s,t){if(typeof s.getElementsByClassName!=="undefined"&&!t){return s.getElementsByClassName(r[1])}}})()}function m(r,w,v,A,x,z){var y=r=="previousSibling"&&!z;for(var t=0,s=A.length;t<s;t++){var e=A[t];if(e){if(y&&e.nodeType===1){e.sizcache=v;e.sizset=t}e=e[r];var u=false;while(e){if(e.sizcache===v){u=A[e.sizset];break}if(e.nodeType===1&&!z){e.sizcache=v;e.sizset=t}if(e.nodeName===w){u=e;break}e=e[r]}A[t]=u}}}function q(r,w,v,A,x,z){var y=r=="previousSibling"&&!z;for(var t=0,s=A.length;t<s;t++){var e=A[t];if(e){if(y&&e.nodeType===1){e.sizcache=v;e.sizset=t}e=e[r];var u=false;while(e){if(e.sizcache===v){u=A[e.sizset];break}if(e.nodeType===1){if(!z){e.sizcache=v;e.sizset=t}if(typeof w!=="string"){if(e===w){u=true;break}}else{if(b.filter(w,[e]).length>0){u=e;break}}}e=e[r]}A[t]=u}}}var h=document.compareDocumentPosition?function(r,e){return r.compareDocumentPosition(e)&16}:function(r,e){return r!==e&&(r.contains?r.contains(e):true)};var o=function(e){return e.nodeType===9&&e.documentElement.nodeName!=="HTML"||!!e.ownerDocument&&e.ownerDocument.documentElement.nodeName!=="HTML"};var g=function(e,x){var t=[],u="",v,s=x.nodeType?[x]:x;while((v=f.match.PSEUDO.exec(e))){u+=v[0];e=e.replace(f.match.PSEUDO,"")}e=f.relative[e]?e+"*":e;for(var w=0,r=s.length;w<r;w++){b(e,s[w],t)}return b.filter(u,t)};window.Sizzle=b})();

//players/shadowbox-img
(function(h){var e=h.util,i,k,j="sb-drag-layer",d;function b(){i={x:0,y:0,start_x:null,start_y:null}}function c(m,o,l){if(m){b();var n=["position:absolute","height:"+o+"px","width:"+l+"px","cursor:"+(h.client.isGecko?"-moz-grab":"move"),"background-color:"+(h.client.isIE?"#fff;filter:alpha(opacity=0)":"transparent")].join(";");h.lib.append(h.skin.bodyEl(),'<div id="'+j+'" style="'+n+'"></div>');h.lib.addEvent(e.get(j),"mousedown",g)}else{var p=e.get(j);if(p){h.lib.removeEvent(p,"mousedown",g);h.lib.remove(p)}k=null}}function g(m){h.lib.preventDefault(m);var l=h.lib.getPageXY(m);i.start_x=l[0];i.start_y=l[1];k=e.get(h.contentId());h.lib.addEvent(document,"mousemove",f);h.lib.addEvent(document,"mouseup",a);if(h.client.isGecko){e.get(j).style.cursor="-moz-grabbing"}}function a(){h.lib.removeEvent(document,"mousemove",f);h.lib.removeEvent(document,"mouseup",a);if(h.client.isGecko){e.get(j).style.cursor="-moz-grab"}}function f(o){var q=h.content,p=h.dimensions,n=h.lib.getPageXY(o);var m=n[0]-i.start_x;i.start_x+=m;i.x=Math.max(Math.min(0,i.x+m),p.inner_w-q.width);k.style.left=i.x+"px";var l=n[1]-i.start_y;i.start_y+=l;i.y=Math.max(Math.min(0,i.y+l),p.inner_h-q.height);k.style.top=i.y+"px"}h.img=function(m){this.obj=m;this.resizable=true;this.ready=false;var l=this;d=new Image();d.onload=function(){l.height=m.height?parseInt(m.height,10):d.height;l.width=m.width?parseInt(m.width,10):d.width;l.ready=true;d.onload="";d=null};d.src=m.content};h.img.prototype={append:function(l,o,n){this.id=o;var m=document.createElement("img");m.id=o;m.src=this.obj.content;m.style.position="absolute";m.setAttribute("height",n.resize_h);m.setAttribute("width",n.resize_w);l.appendChild(m)},remove:function(){var l=e.get(this.id);if(l){h.lib.remove(l)}c(false);if(d){d.onload="";d=null}},onLoad:function(){var l=h.dimensions;if(l.oversized&&h.options.handleOversize=="drag"){c(true,l.resize_h,l.resize_w)}},onWindowResize:function(){if(k){var p=h.content,o=h.dimensions,n=parseInt(h.lib.getStyle(k,"top")),m=parseInt(h.lib.getStyle(k,"left"));if(n+p.height<o.inner_h){k.style.top=o.inner_h-p.height+"px"}if(m+p.width<o.inner_w){k.style.left=o.inner_w-p.width+"px"}}}}})(Shadowbox);

//players/shadowbox-innerhtml
(function(a){a.innerhtml=function(b){this.obj=b;this.width=b.width?parseInt(b.width,10):500;this.height=b.height?parseInt(b.height,10):300};a.innerhtml.prototype={append:function(b,f,d){this.id=f;var e=document.createElement("div"),c=this.obj.content.match("#")?a.util.get(this.obj.content.split("#")[1]):a.util.get(this.obj.content);e.id=f;e.className="html";e.innerHTML=c?c.innerHTML:"";b.appendChild(e)},remove:function(){var b=document.getElementById(this.id);if(b){a.lib.remove(b)}}}})(Shadowbox);

//players/shadowbox-html
(function(a){a.html=function(b){this.obj=b;this.height=b.height?parseInt(b.height,10):300;this.width=b.width?parseInt(b.width,10):500};a.html.prototype={append:function(b,e,c){this.id=e;var d=document.createElement("div");d.id=e;d.className="html";d.innerHTML=this.obj.content;b.appendChild(d)},remove:function(){var b=document.getElementById(this.id);if(b){a.lib.remove(b)}}}})(Shadowbox);

//players/shadowbox-ajax
(function(b){var a=b.util,c;b.ajax=function(f){this.obj=f;if(b.options.handleOversize=="drag"){b.options.handleOversize="resize"}b.options.enableKeys=false;this.resizable=false;this.ready=false;if(typeof Ajax==="undefined"||!Ajax.Updater){alert("Error! Ajax Player is supported only in Prototype framework environment at this time.");return}this.width=f.width?parseInt(f.width,10):500;this.height=f.height?parseInt(f.height,10):300;var e=this,g={};new Ajax.Request(f.content,{method:"post",onComplete:function(h){wrapper=new Element("div");var d=h.responseText.length?h.responseText:"Empty response";wrapper.innerHTML=d;c=wrapper.down()?wrapper.down():wrapper;if(!f.width||!f.height){$(c).setStyle({display:"none"});document.body.appendChild(c);g=c.getDimensions();b.lib.remove(c);$(c).setStyle({display:""});if(!f.width&&g.width){e.width=parseInt(g.width,10)}if(!f.height&&g.height){e.height=parseInt(g.height,10)}}e.ready=true}})};b.ajax.prototype={append:function(e,g,f){this.id=g;c.id=g;c.className="html";c.setAttribute("height",f.resize_h);c.setAttribute("width",f.resize_w);e.appendChild(c)},remove:function(){var d=a.get(this.id);if(d){b.lib.remove(d)}if(c){c=null}}}})(Shadowbox);

//players/shadowbox-iframe
(function(a){a.iframe=function(c){this.obj=c;var b=document.getElementById("sb-overlay");this.height=c.height?parseInt(c.height,10):b.offsetHeight;this.width=c.width?parseInt(c.width,10):b.offsetWidth};a.iframe.prototype={append:function(b,e,d){this.id=e;var c='<iframe id="'+e+'" name="'+e+'" height="100%" width="100%" frameborder="0" marginwidth="0" marginheight="0" scrolling="auto"';if(a.client.isIE){c+=' allowtransparency="true"';if(a.client.isIE6){c+=" src=\"javascript:false;document.write('');\""}}c+="></iframe>";b.innerHTML=c},remove:function(){var b=document.getElementById(this.id);if(b){a.lib.remove(b);if(a.client.isGecko){delete window.frames[this.id]}}},onLoad:function(){var b=a.client.isIE?document.getElementById(this.id).contentWindow:window.frames[this.id];b.location.href=this.obj.content}}})(Shadowbox);

//players/shadowbox-swf
(function(b){var a=b.util;b.swf=function(c){this.obj=c;this.resizable=true;this.height=c.height?parseInt(c.height,10):300;this.width=c.width?parseInt(c.width,10):300};b.swf.prototype={append:function(k,d,m){this.id=d;var i=document.createElement("div");i.id=d;k.appendChild(i);var j=m.resize_h,n=m.resize_w,e=this.obj.content,l=b.options.flashVersion,c=b.path+"libraries/swfobject/expressInstall.swf",f=b.options.flashVars,g=b.options.flashParams;swfobject.embedSWF(e,d,n,j,l,c,f,g)},remove:function(){swfobject.expressInstallCallback();swfobject.removeSWF(this.id)}}})(Shadowbox);

//players/shadowbox-qt
(function(a){var b=16;a.qt=function(c){this.obj=c;this.height=c.height?parseInt(c.height,10):300;if(a.options.showMovieControls==true){this.height+=b}this.width=c.width?parseInt(c.width,10):300};a.qt.prototype={append:function(l,e,n){this.id=e;var f=a.options,g=String(f.autoplayMovies),o=String(f.showMovieControls);var k="<object",i={id:e,name:e,height:this.height,width:this.width,kioskmode:"true"};if(a.client.isIE){i.classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B";i.codebase="http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0"}else{i.type="video/quicktime";i.data=this.obj.content}for(var h in i){k+=" "+h+'="'+i[h]+'"'}k+=">";var j={src:this.obj.content,scale:"aspect",controller:o,autoplay:g};for(var c in j){k+='<param name="'+c+'" value="'+j[c]+'">'}k+="</object>";l.innerHTML=k},remove:function(){var f=this.id;try{document[f].Stop()}catch(d){}var c=document.getElementById(f);if(c){a.lib.remove(c)}}}})(Shadowbox);

//players/shadowbox-wmp
(function(a){var b=(a.client.isIE?70:45);a.wmp=function(c){this.obj=c;this.height=c.height?parseInt(c.height,10):300;if(a.options.showMovieControls){this.height+=b}this.width=c.width?parseInt(c.width,10):300};a.wmp.prototype={append:function(c,j,i){this.id=j;var e=a.options,f=e.autoplayMovies?1:0;var d='<object id="'+j+'" name="'+j+'" height="'+this.height+'" width="'+this.width+'"',h={autostart:e.autoplayMovies?1:0};if(a.client.isIE){d+=' classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6"';h.url=this.obj.content;h.uimode=e.showMovieControls?"full":"none"}else{d+=' type="video/x-ms-wmv"';d+=' data="'+this.obj.content+'"';h.showcontrols=e.showMovieControls?1:0}d+=">";for(var g in h){d+='<param name="'+g+'" value="'+h[g]+'">'}d+="</object>";c.innerHTML=d},remove:function(){var f=this.id;if(a.client.isIE){try{window[f].controls.stop();window[f].URL="non-existent.wmv";window[f]=function(){}}catch(d){}}var c=document.getElementById(f);if(c){setTimeout(function(){a.lib.remove(c)},10)}}}})(Shadowbox);

//languages/shadowbox-ja
if(typeof Shadowbox=="undefined"){throw"Unable to load Shadowbox language file, Shadowbox not found."}Shadowbox.lang={code:"ja",of:"/",loading:"読込中",cancel:"取消",next:"次",previous:"前",play:"再生",pause:"一時停止",close:"閉じる",errors:{single:'このコンテンツを表示するにはプラグイン <a href="{0}">{1}</a> を追加する必要があります。',shared:'このコンテンツを表示するにはプラグイン <a href="{0}">{1}</a> と <a href="{2}">{3}</a> を追加する必要があります。',either:'このコンテンツを表示するにはプラグイン <a href="{0}">{1}</a> または <a href="{2}">{3}</a> を追加する必要があります。'}};

//rael
/*
 * Copyright (C) 2006-2009 Corllete ltd (clabteam.com), License - http://www.free-source.net/licenses/design.txt)
 * Download and update at http://www.free-source.net/
 * $Id: ncore.js 377 2009-08-15 13:43:54Z secretr $
 *
 * Theme Rael - JS init
*/  
document.observe('dom:loaded', function() {	
		e107Base.setPrefs('core-loading-element', {
			overlayDelay: 50,
			opacity: 0.8,
			zIndex: 10,
			className: 'element-loading-mask',
			backgroundImage: '#{THEME}images/ajax-loader.gif'
		});

	/* Download List */
	if ($('dl-list')) {
		var boxes = $('dl-list').select('.dl-list-box');
		for ( var cnt=1; cnt <= boxes.length; cnt++ ) {
			
			if (cnt %3 == 0) {
				boxes[cnt-1].addClassName('last');
				boxes[cnt-1].next().show();
			} else if (cnt %3 == 1 ) {
				boxes[cnt-1].addClassName('first');
				
			}
		}
	}
	if ($('dl-view')) {
		image = $('dl-image').select('img.dl_image');
		
		if ($('dl-image').select('img.dl_image').length > 0) {
			$('dl-info').addClassName('dl-has-image');
		}
			
	}
	
	if($('login-box')) {
		$('login-box').hide();
		$('login-link').observe('click', function(event){
			if (!$('login-link-wrapper').hasClassName('active')) { $('login-link-wrapper').addClassName('active'); } else { $('login-link-wrapper').removeClassName('active')}
			$('login-box').toggle();
		});
	}
	
	if($('language-box')) {
		$('language-box').hide();
		$('language-link').observe('click', function(event){
			if (!$('language-link-wrapper').hasClassName('active')) { $('language-link-wrapper').addClassName('active'); } else { $('language-link-wrapper').removeClassName('active')}
			$('language-box').toggle();
		});
	}
	
	if($('country-box')) {
		$('country-box').hide();
		$('country-link').observe('click', function(event){
			if (!$('country-link-wrapper').hasClassName('active')) { $('country-link-wrapper').addClassName('active'); } else { $('country-link-wrapper').removeClassName('active')}
			$('country-box').toggle();
		});
	}			
	
	if ($('tab-container')) {
		//show tab navaigation
		$('tab-container').select('ul.e-tabs').each( function(el){
			el.show();
			el.removeClassName('e-hideme');//prevent hideme re-register (e.g. ajax load)
		});
		
		//init tabs
		new e107Widgets.Tabs($('tab-container'), {
			bookmarkFix: false,
			historyNavigation: false,
			pageOverlay: false,
			overlayElement: 'tabs-wrapper',
			ajaxCache: true,
			ajaxEvalScripts: false
		});
	}
		
	if($('gal-scroller-slider')) {
		new Carousel($('gal-scroller-slider'), $$('#gal-scroller-slider .slide'), $$('#gal-scroller a.carousel-control', '#gal-scroller a.carousel-jumper'),
		{
			wheel: false,
			effect: 'scroll',
			duration: 2.5,
			visibleSlides: 1,

			auto: true,
			frequency: 10,
			circular: true
			
		});
	}
	
});


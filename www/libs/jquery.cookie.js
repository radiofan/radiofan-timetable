/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		// CommonJS
		factory(require('jquery'));
	} else {
		// Browser globals
		factory(jQuery);
	}
}(function ($) {

	var pluses = /\+/g;

	function encode(s, enc = 0) {
		return (config.raw || enc) ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function recursiveDeleteCookie(key, cookies){
		for(let i in cookies){
			if(typeof cookies[i] === 'object' || $.isArray(cookies[i])){
				recursiveDeleteCookie(key + '[' + encode(i) + ']', cookies[i]);
			}else{
				$.cookie(key + '[' + encode(i) + ']', '', {expires: -1, raw:1});
			}
		}
	}

	function recursiveAddCookie(key, value, options){
		if(typeof value === 'object' || $.isArray(value)){
			for(let i in value){
				recursiveAddCookie(key + '[' + encode(i) + ']', value[i], options);
			}
		}else{
			$.cookie(key, value, options);
		}
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		let value = config.raw ? s : parseCookieValue(s);
		return $.isFunction(converter) ? converter(value) : value;
	}

	var config = $.cookie = function (key, value, options) {

		// Write
		options = $.extend({}, config.defaults, options);
		options.array = options.hasOwnProperty('array') && options.array ? 1 : 0;

		if (value !== undefined && !$.isFunction(value)) {

			if (typeof options.expires === 'number') {
				let days = options.expires, t = options.expires = new Date();
				t.setTime(+t + days * 864e+5);
			}

			options.raw = options.raw ? 1 : 0;
			if(options.array){
				options.array = 0;
				recursiveAddCookie(key, value, options);
			}else{
				return (document.cookie = [
					encode(key, options.raw), '=', stringifyCookieValue(value),
					options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
					options.path    ? '; path=' + options.path : '',
					options.domain  ? '; domain=' + options.domain : '',
					options.secure  ? '; secure' : ''
				].join(''));
			}
		}

		// Read

		let result = key ? undefined : {};

		// To prevent the for loop in the first place assign an empty array
		// in case there are no cookies at all. Also prevents odd result when
		// calling $.cookie().
		let cookies = document.cookie ? document.cookie.split('; ') : [];

		if(options.array){
			result = {};
			let isRes = false;
			for(let i = 0; i < cookies.length; i++){
				let parts = cookies[i].split('=');
				let name = decode(parts.shift());
				if(name.substr(0, key.length) !== key)
					continue;
				let tmp_name = name.substr(key.length);
				let f = 0;
				let keys = tmp_name.split(']').slice(0, -1);
				for(let i1=0; i1 < keys.length; i1++){
					keys[i1] = keys[i1].substring(1);
					if(keys[i1] === ''){
						f = 1;
						break;
					}
				}
				if(f)
					continue;
				if('[' + keys.join('][') + ']' !== tmp_name)
					continue;
				let cookie = parts.join('=');
					// If second argument (value) is a function it's a converter...
				let tmp_result = result;
				for(let i1=0; i1 < keys.length-1; i1++){
					if(!tmp_result.hasOwnProperty(keys[i1]))
						tmp_result[keys[i1]] = {};
					tmp_result = tmp_result[keys[i1]];
				}
				tmp_result[keys[keys.length-1]] = read(cookie, value);
				isRes = true;
			}
			return isRes ? result : false;
		}else{
			for(let i = 0, l = cookies.length; i < l; i++){
				let parts = cookies[i].split('=');
				let name = decode(parts.shift());
				let cookie = parts.join('=');

				if(key && key === name){
					// If second argument (value) is a function it's a converter...
					result = read(cookie, value);
					break;
				}

				// Prevent storing a cookie that we couldn't decode.
				if(!key && (cookie = read(cookie)) !== undefined){
					result[name] = cookie;
				}
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function(key, options){
		options = $.extend({}, options, {expires: -1});
		if(!(options.hasOwnProperty('array') && options.array)){
			if($.cookie(key) === undefined){
				return false;
			}

			$.cookie(key, '', options);
			return !$.cookie(key);
		}

		let cookies = $.cookie(key, undefined, options);
		recursiveDeleteCookie(key, cookies);

	};

}));

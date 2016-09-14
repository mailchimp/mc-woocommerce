var mailchimp;
var mailchimp_cart;
var mailchimp_public_data;

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var requestTransport = null;
	var scriptTagCounter = 1, head;
	var storageLife = "30";
	var clientIP = null;
	var saved_ip;
	var script;

	mailchimp_public_data = public_data || {site_url:document.location.origin};

	function invokeJsonp(fullUrl, cacheOk)
	{
		var c = cacheOk || true;
		script = buildScriptTag(fullUrl, c);
		if (typeof head != 'object') {
			head = document.getElementsByTagName("head").item(0);
		}
		head.appendChild(script);
		return script;
	}

	function removeTag(tag)
	{
		if (typeof head != 'object') {
			head = document.getElementsByTagName("head").item(0);
		}
		head.removeChild(script);
	}

	function buildScriptTag(url, cacheOk)
	{
		var element = document.createElement("script"),
			additionalQueryParams, conjunction,
			actualUrl = url,
			elementId = 'jsonp-script-' + scriptTagCounter++;
		if (!cacheOk) {
			additionalQueryParams = '_=' + (new Date()).getTime();
			conjunction = (url.indexOf('?') == -1) ? '?' : '&';
			actualUrl = url + conjunction + additionalQueryParams;
		}
		element.setAttribute("type", "text/javascript");
		element.setAttribute("src", actualUrl);
		element.setAttribute("id", elementId);
		return element;
	}

	var mailchimpUtils =
	{
		extend:function (e, t) {
			for (var n in t || {}) {
				if (t.hasOwnProperty(n)) {
					e[n] = t[n]
				}
			}
			return e
		},
		getQueryStringVars:function ()
		{
			var e = window.location.search || "";
			var t = [];
			var n = {};
			e = e.substr(1);
			if (e.length) {
				t = e.split("&");
				for (var r in t)
				{
					var i = t[r];
					if(typeof i !== 'string'){continue;}
					var s = i.split("=");
					var o = s[0];
					var u = s[1];
					if (!o.length)continue;
					if (typeof n[o] === "undefined") {
						n[o] = []
					}
					n[o].push(u)
				}
			}
			return n
		},
		unEscape:function (e) {
			return decodeURIComponent(e)
		},
		escape:function (e) {
			return encodeURIComponent(e)
		},
		createDate:function (e, t) {
			if (!e) {
				e = 0
			}
			var n = new Date;
			var r = t ? n.getDate() - e : n.getDate() + e;
			n.setDate(r);
			return n
		},
		arrayUnique:function (e) {
			var t = e.concat();
			for (var n = 0; n < t.length; ++n) {
				for (var r = n + 1; r < t.length; ++r) {
					if (t[n] === t[r]) {
						t.splice(r, 1)
					}
				}
			}
			return t
		},
		objectCombineUnique:function (e) {
			var t = e[0];
			for (var n = 1; n < e.length; n++) {
				var r = e[n];
				for (var i in r) {
					t[i] = r[i]
				}
			}
			return t
		}
	};

	var mailchimpStorage = function(e, t)
	{
		var n = function (e, t, r) {
			return 1 === arguments.length ? n.get(e) : n.set(e, t, r)
		};
		n.get = function (t, r) {
			e.cookie !== n._cacheString && n._populateCache();
			return n._cache[t] == undefined ? r : n._cache[t]
		};
		n.defaults = {path:"/"};
		n.set = function (r, i, s) {
			s = {path:s && s.path || n.defaults.path, domain:s && s.domain || n.defaults.domain, expires:s && s.expires || n.defaults.expires, secure:s && s.secure !== t ? s.secure : n.defaults.secure};
			i === t && (s.expires = -1);
			switch (typeof s.expires) {
				case"number":
					s.expires = new Date((new Date).getTime() + 1e3 * s.expires);
					break;
				case"string":
					s.expires = new Date(s.expires)
			}
			r = encodeURIComponent(r) + "=" + (i + "").replace(/[^!#-+\--:<-\[\]-~]/g, encodeURIComponent);
			r += s.path ? ";path=" + s.path : "";
			r += s.domain ? ";domain=" + s.domain : "";
			r += s.expires ? ";expires=" + s.expires.toGMTString() : "";
			r += s.secure ? ";secure" : "";
			e.cookie = r;
			return n
		};
		n.expire = function (e, r) {
			return n.set(e, t, r)
		};
		n._populateCache = function () {
			n._cache = {};
			try {
				n._cacheString = e.cookie;
				for (var r = n._cacheString.split("; "), i = 0; i < r.length; i++) {
					var s = r[i].indexOf("="), o = decodeURIComponent(r[i].substr(0, s)), s = decodeURIComponent(r[i].substr(s + 1));
					n._cache[o] === t && (n._cache[o] = s)
				}
			} catch (e) {
				console.log(e);
			}
		};
		n.enabled = function () {
			var e = "1" === n.set("cookies.js", "1").get("cookies.js");
			n.expire("cookies.js");
			return e
		}();
		return n;
	}(document);

	var Jsonp = {invoke : invokeJsonp, removeTag: removeTag};

	mailchimp =
	{
		storage : mailchimpStorage,
		utils : mailchimpUtils
	};

	function MailChimpCart() {

		this.email_types = "input[type=email]";
		this.regex_email = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		this.current_email = null;
		this.previous_email = null;

		this.expireUser = function () {
			this.current_email = null;
			mailchimp.storage.expire('mailchimp.cart.current_email');
		};

		this.expireSaved = function () {
			mailchimp.storage.expire('mailchimp.cart.items');
		};

		this.setEmail = function (email) {
			if (this.valueEmail(email)) {
				this.setPreviousEmail(this.getEmail());
				this.current_email = email;
				mailchimp.storage.set('mailchimp.cart.current_email', email);
			}
		};
		this.getEmail = function () {
			if (this.current_email) {
				return this.current_email;
			}
			var current_email = mailchimp.storage.get('mailchimp.cart.current_email', false);
			if (!current_email || !this.valueEmail(current_email)) {
				return false;
			}
			this.current_email = current_email;
			return current_email;
		};
		this.setPreviousEmail = function (prev_email) {
			if (this.valueEmail(prev_email)) {
				mailchimp.storage.set('mailchimp.cart.previous_email', prev_email);
				this.previous_email = prev_email;
			}
		};
		this.valueEmail = function (email) {
			return this.regex_email.test(email);
		};

		$(document).on("blur", "#billing_email",function() {
			var user = $("#billing_email").val();
			if (!mailchimp_cart.valueEmail(user)) {
				return false;
			}
			mailchimp_cart.setEmail(user);

			$.ajax({
				beforeSend: function (xhrObj) {
					xhrObj.setRequestHeader("Content-Type", "application/json");
					xhrObj.setRequestHeader("Accept", "application/json");
				},
				crossDomain: true,
				dataType: "json",
				type: 'POST',
				url: mailchimp_public_data.site_url+'?mailchimp-woocommerce[action]=submit-email&mailchimp-woocommerce[submission][email]='+user,
				data: {},
				success: function (responseData, textStatus, jqXHR) {
					console.log('email saved', responseData);
				},
				error: function (responseData, textStatus, errorThrown) {
					mailchimp_cart.post_error = errorThrown;
					console.log('error while saving email', responseData);
				}
			});
		});

		return this;
	}

	mailchimp_cart = new MailChimpCart();

	var qsc = mailchimpUtils.getQueryStringVars();

	// MailChimp Data //
	if (qsc.mc_cid !== undefined && qsc.mc_eid !== undefined) {
		$.ajax({
			beforeSend: function (xhrObj) {
				xhrObj.setRequestHeader("Content-Type", "application/json");
				xhrObj.setRequestHeader("Accept", "application/json");
			},
			crossDomain: true,
			dataType: "json",
			type: 'POST',
			url: mailchimp_public_data.site_url+'?mailchimp-woocommerce[action]=track-campaign&mailchimp-woocommerce[submission][campaign_id]='+qsc.mc_cid[0]+'&mailchimp-woocommerce[submission][email_id]='+qsc.mc_eid[0],
			data: {},
			success: function (responseData, textStatus, jqXHR) {
				console.log('campaign data saved', responseData);
			},
			error: function (responseData, textStatus, errorThrown) {
				mailchimp_cart.post_error = errorThrown;
				console.log('error while saving campaign data', responseData);
			}
		});
	}

})( jQuery );

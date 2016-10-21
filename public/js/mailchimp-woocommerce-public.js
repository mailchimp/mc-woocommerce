var mailchimp;
var mailchimp_cart;
var mailchimp_public_data;
var mailchimp_billing_email;

var mailchimpReady = function(f){
	/in/.test(document.readyState)?setTimeout('mailchimpReady('+f+')',9):f()
};

function mailchimpGetCurrentUserByHash(hash) {
	try {
		var get_email_url = mailchimp_public_data.site_url+
			'?mailchimp-woocommerce[action]=parse-email&mailchimp-woocommerce[submission][hash]='+hash;

		var get_email_request = new XMLHttpRequest();

		get_email_request.open('POST', get_email_url, true);
		get_email_request.onload = function() {
			if (get_email_request.status >= 200 && get_email_request.status < 400) {
				var response_json = JSON.parse(get_email_request.responseText);
				if (mailchimp_cart.valueEmail(response_json.email)) {
					mailchimp_cart.setEmail(response_json.email);
					console.log('mailchimp', 'setting '+response_json.email+' as the current user');
				}
			} else {
				console.log('error', get_email_request.responseText);
			}
		};

		get_email_request.onerror = function() {
			console.log('get email error', get_email_request.responseText);
		};

		get_email_request.setRequestHeader('Content-Type', 'application/json');
		get_email_request.setRequestHeader('Accept', 'application/json');
		get_email_request.send();
	} catch (e) {console.log('mailchimp.get_email_by_hasn.error', e);}
}

function mailchimpHandleBillingEmail() {

	var billing_email = document.querySelector('#billing_email');
	var user = undefined !== billing_email ? billing_email.value : '';

	if (!mailchimp_cart.valueEmail(user)) {
		return false;
	}

	mailchimp_cart.setEmail(user);

	try {
		var submit_email_url = mailchimp_public_data.site_url+
			'?mailchimp-woocommerce[action]=submit-email&mailchimp-woocommerce[submission][email]='+user;

		var submit_email_request = new XMLHttpRequest();

		submit_email_request.open('POST', submit_email_url, true);

		submit_email_request.onload = function() {
			if (submit_email_request.status >= 200 && submit_email_request.status < 400) {
				console.log('success', submit_email_request.responseText);
			} else {
				console.log('error', submit_email_request.responseText);
			}
		};

		submit_email_request.onerror = function() {
			console.log('submit email error', submit_email_request.responseText);
		};

		submit_email_request.setRequestHeader('Content-Type', 'application/json');
		submit_email_request.setRequestHeader('Accept', 'application/json');
		submit_email_request.send();

	} catch (e) {console.log('mailchimp_campaign_tracking.error', e);}
}

(function() {
	'use strict';
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
		this.regex_email = /^([A-Za-z0-9_+\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
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

		return this;
	}

	mailchimp_cart = new MailChimpCart();
})();

mailchimpReady(function(){

	var qsc = mailchimp.utils.getQueryStringVars();

	if (qsc.mc_cart_id !== undefined) {
		mailchimpGetCurrentUserByHash(qsc.mc_cart_id);
	}

	// MailChimp Data //
	if (qsc.mc_cid !== undefined && qsc.mc_eid !== undefined) {
		var post_campaign_tracking_url = mailchimp_public_data.site_url+
			'?mailchimp-woocommerce[action]=track-campaign&mailchimp-woocommerce[submission][campaign_id]='+
			qsc.mc_cid[0]+
			'&mailchimp-woocommerce[submission][email_id]='+
			qsc.mc_eid[0];

		try {
			var post_campaign_request = new XMLHttpRequest();
			post_campaign_request.open('POST', post_campaign_tracking_url, true);
			post_campaign_request.setRequestHeader('Content-Type', 'application/json');
			post_campaign_request.setRequestHeader('Accept', 'application/json');
			post_campaign_request.send(data);
		} catch (e) {console.log('mailchimp_campaign_tracking.error', e);}
	}

	mailchimp_billing_email = document.querySelector('#billing_email');

	if (mailchimp_billing_email) {
		mailchimp_billing_email.onblur = function() {
			mailchimpHandleBillingEmail();
		};
		mailchimp_billing_email.onfocus = function() {
			mailchimpHandleBillingEmail();
		};
	}
});

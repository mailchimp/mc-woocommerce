/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/checkout-newsletter-subscription-block/attributes.js":
/*!************************************************************************!*\
  !*** ./assets/js/checkout-newsletter-subscription-block/attributes.js ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

const {
  optinDefaultText,
  gdprHeadline,
  gdprFields,
  gdprStatus
} = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_0__.getSetting)('mailchimp-newsletter_data', '');
/* harmony default export */ __webpack_exports__["default"] = ({
  text: {
    type: 'string',
    default: optinDefaultText
  },
  gdprHeadline: {
    type: 'string',
    default: gdprHeadline
  },
  gdpr: {
    type: 'array',
    default: gdprFields
  },
  gdprStatus: {
    type: 'string',
    default: gdprStatus
  }
});

/***/ }),

/***/ "./assets/js/checkout-newsletter-subscription-block/block.js":
/*!*******************************************************************!*\
  !*** ./assets/js/checkout-newsletter-subscription-block/block.js ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);


/**
 * External dependencies
 */




const Block = _ref => {
  let {
    cart,
    extensions,
    text,
    gdprHeadline,
    gdprStatus,
    gdpr,
    checkoutExtensionData
  } = _ref;
  let defaultGDPR = {};

  if (gdpr && gdpr.length) {
    gdpr.forEach(item => {
      defaultGDPR[item.marketing_permission_id] = false;
    });
  }

  if (gdprStatus === 'hide') return '';
  const status = gdprStatus === 'check';
  const [checked, setChecked] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(status);
  const [gdprFields] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const {
    setExtensionData
  } = checkoutExtensionData;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setExtensionData('mailchimp-newsletter', 'optin', checked);
  }, [checked, setExtensionData]);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_1__.CheckboxControl, {
    id: "subscribe-to-newsletter",
    checked: checked,
    onChange: setChecked
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    dangerouslySetInnerHTML: {
      __html: text
    }
  })), gdpr && gdpr.length ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)(gdprHeadline, 'mailchimp-for-woocommerce') : '', gdpr && gdpr.length ? gdpr.map(gdprItem => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_1__.CheckboxControl, {
      id: 'gdpr_' + gdprItem.marketing_permission_id,
      checked: gdprFields[gdprItem.marketing_permission_id],
      onChange: e => {
        gdprFields[gdprItem.marketing_permission_id] = !gdprFields[gdprItem.marketing_permission_id];
        setExtensionData('mailchimp-newsletter', 'gdprFields', gdprFields);
      }
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      dangerouslySetInnerHTML: {
        __html: gdprItem.text
      }
    }));
  }) : '');
};

/* harmony default export */ __webpack_exports__["default"] = (Block);

/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ (function(module) {

module.exports = window["wc"]["blocksCheckout"];

/***/ }),

/***/ "@woocommerce/shared-hocs":
/*!********************************************!*\
  !*** external ["wc","wcBlocksSharedHocs"] ***!
  \********************************************/
/***/ (function(module) {

module.exports = window["wc"]["wcBlocksSharedHocs"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ (function(module) {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ (function(module) {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./assets/js/checkout-newsletter-subscription-block/block.json":
/*!*********************************************************************!*\
  !*** ./assets/js/checkout-newsletter-subscription-block/block.json ***!
  \*********************************************************************/
/***/ (function(module) {

module.exports = JSON.parse('{"apiVersion":2,"name":"woocommerce/mailchimp-newsletter-subscription","version":"1.0.0","title":"Mailchimp Newsletter!","category":"woocommerce","description":"Adds a newsletter subscription checkbox to the checkout.","supports":{"html":true,"align":false,"multiple":false,"reusable":false},"parent":["woocommerce/checkout-contact-information-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}}},"textdomain":"mailchimp-woocommerce","editorStyle":"file:../../../build/style-newsletter-block.css"}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************************************************************!*\
  !*** ./assets/js/checkout-newsletter-subscription-block/frontend.js ***!
  \**********************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_shared_hocs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @woocommerce/shared-hocs */ "@woocommerce/shared-hocs");
/* harmony import */ var _woocommerce_shared_hocs__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_shared_hocs__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block */ "./assets/js/checkout-newsletter-subscription-block/block.js");
/* harmony import */ var _attributes__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./attributes */ "./assets/js/checkout-newsletter-subscription-block/attributes.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./block.json */ "./assets/js/checkout-newsletter-subscription-block/block.json");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




(0,_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_0__.registerCheckoutBlock)({
  metadata: _block_json__WEBPACK_IMPORTED_MODULE_4__,
  component: (0,_woocommerce_shared_hocs__WEBPACK_IMPORTED_MODULE_1__.withFilteredAttributes)(_attributes__WEBPACK_IMPORTED_MODULE_3__["default"])(_block__WEBPACK_IMPORTED_MODULE_2__["default"])
});
}();
/******/ })()
;
//# sourceMappingURL=newsletter-block-frontend.js.map
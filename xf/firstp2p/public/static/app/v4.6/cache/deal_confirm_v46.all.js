;(function () {
	'use strict';

	/**
	 * @preserve FastClick: polyfill to remove click delays on browsers with touch UIs.
	 *
	 * @codingstandard ftlabs-jsv2
	 * @copyright The Financial Times Limited [All Rights Reserved]
	 * @license MIT License (see LICENSE.txt)
	 */

	/*jslint browser:true, node:true*/
	/*global define, Event, Node*/


	/**
	 * Instantiate fast-clicking listeners on the specified layer.
	 *
	 * @constructor
	 * @param {Element} layer The layer to listen on
	 * @param {Object} [options={}] The options to override the defaults
	 */
	function FastClick(layer, options) {
		var oldOnClick;

		options = options || {};

		/**
		 * Whether a click is currently being tracked.
		 *
		 * @type boolean
		 */
		this.trackingClick = false;


		/**
		 * Timestamp for when click tracking started.
		 *
		 * @type number
		 */
		this.trackingClickStart = 0;


		/**
		 * The element being tracked for a click.
		 *
		 * @type EventTarget
		 */
		this.targetElement = null;


		/**
		 * X-coordinate of touch start event.
		 *
		 * @type number
		 */
		this.touchStartX = 0;


		/**
		 * Y-coordinate of touch start event.
		 *
		 * @type number
		 */
		this.touchStartY = 0;


		/**
		 * ID of the last touch, retrieved from Touch.identifier.
		 *
		 * @type number
		 */
		this.lastTouchIdentifier = 0;


		/**
		 * Touchmove boundary, beyond which a click will be cancelled.
		 *
		 * @type number
		 */
		this.touchBoundary = options.touchBoundary || 10;


		/**
		 * The FastClick layer.
		 *
		 * @type Element
		 */
		this.layer = layer;

		/**
		 * The minimum time between tap(touchstart and touchend) events
		 *
		 * @type number
		 */
		this.tapDelay = options.tapDelay || 200;

		/**
		 * The maximum time for a tap
		 *
		 * @type number
		 */
		this.tapTimeout = options.tapTimeout || 700;

		if (FastClick.notNeeded(layer)) {
			return;
		}

		// Some old versions of Android don't have Function.prototype.bind
		function bind(method, context) {
			return function() { return method.apply(context, arguments); };
		}


		var methods = ['onMouse', 'onClick', 'onTouchStart', 'onTouchMove', 'onTouchEnd', 'onTouchCancel'];
		var context = this;
		for (var i = 0, l = methods.length; i < l; i++) {
			context[methods[i]] = bind(context[methods[i]], context);
		}

		// Set up event handlers as required
		if (deviceIsAndroid) {
			layer.addEventListener('mouseover', this.onMouse, true);
			layer.addEventListener('mousedown', this.onMouse, true);
			layer.addEventListener('mouseup', this.onMouse, true);
		}

		layer.addEventListener('click', this.onClick, true);
		layer.addEventListener('touchstart', this.onTouchStart, false);
		layer.addEventListener('touchmove', this.onTouchMove, false);
		layer.addEventListener('touchend', this.onTouchEnd, false);
		layer.addEventListener('touchcancel', this.onTouchCancel, false);

		// Hack is required for browsers that don't support Event#stopImmediatePropagation (e.g. Android 2)
		// which is how FastClick normally stops click events bubbling to callbacks registered on the FastClick
		// layer when they are cancelled.
		if (!Event.prototype.stopImmediatePropagation) {
			layer.removeEventListener = function(type, callback, capture) {
				var rmv = Node.prototype.removeEventListener;
				if (type === 'click') {
					rmv.call(layer, type, callback.hijacked || callback, capture);
				} else {
					rmv.call(layer, type, callback, capture);
				}
			};

			layer.addEventListener = function(type, callback, capture) {
				var adv = Node.prototype.addEventListener;
				if (type === 'click') {
					adv.call(layer, type, callback.hijacked || (callback.hijacked = function(event) {
						if (!event.propagationStopped) {
							callback(event);
						}
					}), capture);
				} else {
					adv.call(layer, type, callback, capture);
				}
			};
		}

		// If a handler is already declared in the element's onclick attribute, it will be fired before
		// FastClick's onClick handler. Fix this by pulling out the user-defined handler function and
		// adding it as listener.
		if (typeof layer.onclick === 'function') {

			// Android browser on at least 3.2 requires a new reference to the function in layer.onclick
			// - the old one won't work if passed to addEventListener directly.
			oldOnClick = layer.onclick;
			layer.addEventListener('click', function(event) {
				oldOnClick(event);
			}, false);
			layer.onclick = null;
		}
	}

	/**
	* Windows Phone 8.1 fakes user agent string to look like Android and iPhone.
	*
	* @type boolean
	*/
	var deviceIsWindowsPhone = navigator.userAgent.indexOf("Windows Phone") >= 0;

	/**
	 * Android requires exceptions.
	 *
	 * @type boolean
	 */
	var deviceIsAndroid = navigator.userAgent.indexOf('Android') > 0 && !deviceIsWindowsPhone;


	/**
	 * iOS requires exceptions.
	 *
	 * @type boolean
	 */
	var deviceIsIOS = /iP(ad|hone|od)/.test(navigator.userAgent) && !deviceIsWindowsPhone;


	/**
	 * iOS 4 requires an exception for select elements.
	 *
	 * @type boolean
	 */
	var deviceIsIOS4 = deviceIsIOS && (/OS 4_\d(_\d)?/).test(navigator.userAgent);


	/**
	 * iOS 6.0-7.* requires the target element to be manually derived
	 *
	 * @type boolean
	 */
	var deviceIsIOSWithBadTarget = deviceIsIOS && (/OS [6-7]_\d/).test(navigator.userAgent);

	/**
	 * BlackBerry requires exceptions.
	 *
	 * @type boolean
	 */
	var deviceIsBlackBerry10 = navigator.userAgent.indexOf('BB10') > 0;

	/**
	 * Determine whether a given element requires a native click.
	 *
	 * @param {EventTarget|Element} target Target DOM element
	 * @returns {boolean} Returns true if the element needs a native click
	 */
	FastClick.prototype.needsClick = function(target) {
		switch (target.nodeName.toLowerCase()) {

		// Don't send a synthetic click to disabled inputs (issue #62)
		case 'button':
		case 'select':
		case 'textarea':
			if (target.disabled) {
				return true;
			}

			break;
		case 'input':

			// File inputs need real clicks on iOS 6 due to a browser bug (issue #68)
			if ((deviceIsIOS && target.type === 'file') || target.disabled) {
				return true;
			}

			break;
		case 'label':
		case 'iframe': // iOS8 homescreen apps can prevent events bubbling into frames
		case 'video':
			return true;
		}

		return (/\bneedsclick\b/).test(target.className);
	};


	/**
	 * Determine whether a given element requires a call to focus to simulate click into element.
	 *
	 * @param {EventTarget|Element} target Target DOM element
	 * @returns {boolean} Returns true if the element requires a call to focus to simulate native click.
	 */
	FastClick.prototype.needsFocus = function(target) {
		switch (target.nodeName.toLowerCase()) {
		case 'textarea':
			return true;
		case 'select':
			return !deviceIsAndroid;
		case 'input':
			switch (target.type) {
			case 'button':
			case 'checkbox':
			case 'file':
			case 'image':
			case 'radio':
			case 'submit':
				return false;
			}

			// No point in attempting to focus disabled inputs
			return !target.disabled && !target.readOnly;
		default:
			return (/\bneedsfocus\b/).test(target.className);
		}
	};


	/**
	 * Send a click event to the specified element.
	 *
	 * @param {EventTarget|Element} targetElement
	 * @param {Event} event
	 */
	FastClick.prototype.sendClick = function(targetElement, event) {
		var clickEvent, touch;

		// On some Android devices activeElement needs to be blurred otherwise the synthetic click will have no effect (#24)
		if (document.activeElement && document.activeElement !== targetElement) {
			document.activeElement.blur();
		}

		touch = event.changedTouches[0];

		// Synthesise a click event, with an extra attribute so it can be tracked
		clickEvent = document.createEvent('MouseEvents');
		clickEvent.initMouseEvent(this.determineEventType(targetElement), true, true, window, 1, touch.screenX, touch.screenY, touch.clientX, touch.clientY, false, false, false, false, 0, null);
		clickEvent.forwardedTouchEvent = true;
		targetElement.dispatchEvent(clickEvent);
	};

	FastClick.prototype.determineEventType = function(targetElement) {

		//Issue #159: Android Chrome Select Box does not open with a synthetic click event
		if (deviceIsAndroid && targetElement.tagName.toLowerCase() === 'select') {
			return 'mousedown';
		}

		return 'click';
	};


	/**
	 * @param {EventTarget|Element} targetElement
	 */
	FastClick.prototype.focus = function(targetElement) {
		var length;

		// Issue #160: on iOS 7, some input elements (e.g. date datetime month) throw a vague TypeError on setSelectionRange. These elements don't have an integer value for the selectionStart and selectionEnd properties, but unfortunately that can't be used for detection because accessing the properties also throws a TypeError. Just check the type instead. Filed as Apple bug #15122724.
		if (deviceIsIOS && targetElement.setSelectionRange && targetElement.type.indexOf('date') !== 0 && targetElement.type !== 'time' && targetElement.type !== 'month') {
			length = targetElement.value.length;
			targetElement.setSelectionRange(length, length);
		} else {
			targetElement.focus();
		}
	};


	/**
	 * Check whether the given target element is a child of a scrollable layer and if so, set a flag on it.
	 *
	 * @param {EventTarget|Element} targetElement
	 */
	FastClick.prototype.updateScrollParent = function(targetElement) {
		var scrollParent, parentElement;

		scrollParent = targetElement.fastClickScrollParent;

		// Attempt to discover whether the target element is contained within a scrollable layer. Re-check if the
		// target element was moved to another parent.
		if (!scrollParent || !scrollParent.contains(targetElement)) {
			parentElement = targetElement;
			do {
				if (parentElement.scrollHeight > parentElement.offsetHeight) {
					scrollParent = parentElement;
					targetElement.fastClickScrollParent = parentElement;
					break;
				}

				parentElement = parentElement.parentElement;
			} while (parentElement);
		}

		// Always update the scroll top tracker if possible.
		if (scrollParent) {
			scrollParent.fastClickLastScrollTop = scrollParent.scrollTop;
		}
	};


	/**
	 * @param {EventTarget} targetElement
	 * @returns {Element|EventTarget}
	 */
	FastClick.prototype.getTargetElementFromEventTarget = function(eventTarget) {

		// On some older browsers (notably Safari on iOS 4.1 - see issue #56) the event target may be a text node.
		if (eventTarget.nodeType === Node.TEXT_NODE) {
			return eventTarget.parentNode;
		}

		return eventTarget;
	};


	/**
	 * On touch start, record the position and scroll offset.
	 *
	 * @param {Event} event
	 * @returns {boolean}
	 */
	FastClick.prototype.onTouchStart = function(event) {
		var targetElement, touch, selection;

		// Ignore multiple touches, otherwise pinch-to-zoom is prevented if both fingers are on the FastClick element (issue #111).
		if (event.targetTouches.length > 1) {
			return true;
		}

		targetElement = this.getTargetElementFromEventTarget(event.target);
		touch = event.targetTouches[0];

		if (deviceIsIOS) {

			// Only trusted events will deselect text on iOS (issue #49)
			selection = window.getSelection();
			if (selection.rangeCount && !selection.isCollapsed) {
				return true;
			}

			if (!deviceIsIOS4) {

				// Weird things happen on iOS when an alert or confirm dialog is opened from a click event callback (issue #23):
				// when the user next taps anywhere else on the page, new touchstart and touchend events are dispatched
				// with the same identifier as the touch event that previously triggered the click that triggered the alert.
				// Sadly, there is an issue on iOS 4 that causes some normal touch events to have the same identifier as an
				// immediately preceeding touch event (issue #52), so this fix is unavailable on that platform.
				// Issue 120: touch.identifier is 0 when Chrome dev tools 'Emulate touch events' is set with an iOS device UA string,
				// which causes all touch events to be ignored. As this block only applies to iOS, and iOS identifiers are always long,
				// random integers, it's safe to to continue if the identifier is 0 here.
				if (touch.identifier && touch.identifier === this.lastTouchIdentifier) {
					event.preventDefault();
					return false;
				}

				this.lastTouchIdentifier = touch.identifier;

				// If the target element is a child of a scrollable layer (using -webkit-overflow-scrolling: touch) and:
				// 1) the user does a fling scroll on the scrollable layer
				// 2) the user stops the fling scroll with another tap
				// then the event.target of the last 'touchend' event will be the element that was under the user's finger
				// when the fling scroll was started, causing FastClick to send a click event to that layer - unless a check
				// is made to ensure that a parent layer was not scrolled before sending a synthetic click (issue #42).
				this.updateScrollParent(targetElement);
			}
		}

		this.trackingClick = true;
		this.trackingClickStart = event.timeStamp;
		this.targetElement = targetElement;

		this.touchStartX = touch.pageX;
		this.touchStartY = touch.pageY;

		// Prevent phantom clicks on fast double-tap (issue #36)
		if ((event.timeStamp - this.lastClickTime) < this.tapDelay) {
			event.preventDefault();
		}

		return true;
	};


	/**
	 * Based on a touchmove event object, check whether the touch has moved past a boundary since it started.
	 *
	 * @param {Event} event
	 * @returns {boolean}
	 */
	FastClick.prototype.touchHasMoved = function(event) {
		var touch = event.changedTouches[0], boundary = this.touchBoundary;

		if (Math.abs(touch.pageX - this.touchStartX) > boundary || Math.abs(touch.pageY - this.touchStartY) > boundary) {
			return true;
		}

		return false;
	};


	/**
	 * Update the last position.
	 *
	 * @param {Event} event
	 * @returns {boolean}
	 */
	FastClick.prototype.onTouchMove = function(event) {
		if (!this.trackingClick) {
			return true;
		}

		// If the touch has moved, cancel the click tracking
		if (this.targetElement !== this.getTargetElementFromEventTarget(event.target) || this.touchHasMoved(event)) {
			this.trackingClick = false;
			this.targetElement = null;
		}

		return true;
	};


	/**
	 * Attempt to find the labelled control for the given label element.
	 *
	 * @param {EventTarget|HTMLLabelElement} labelElement
	 * @returns {Element|null}
	 */
	FastClick.prototype.findControl = function(labelElement) {

		// Fast path for newer browsers supporting the HTML5 control attribute
		if (labelElement.control !== undefined) {
			return labelElement.control;
		}

		// All browsers under test that support touch events also support the HTML5 htmlFor attribute
		if (labelElement.htmlFor) {
			return document.getElementById(labelElement.htmlFor);
		}

		// If no for attribute exists, attempt to retrieve the first labellable descendant element
		// the list of which is defined here: http://www.w3.org/TR/html5/forms.html#category-label
		return labelElement.querySelector('button, input:not([type=hidden]), keygen, meter, output, progress, select, textarea');
	};


	/**
	 * On touch end, determine whether to send a click event at once.
	 *
	 * @param {Event} event
	 * @returns {boolean}
	 */
	FastClick.prototype.onTouchEnd = function(event) {
		var forElement, trackingClickStart, targetTagName, scrollParent, touch, targetElement = this.targetElement;

		if (!this.trackingClick) {
			return true;
		}

		// Prevent phantom clicks on fast double-tap (issue #36)
		if ((event.timeStamp - this.lastClickTime) < this.tapDelay) {
			this.cancelNextClick = true;
			return true;
		}

		if ((event.timeStamp - this.trackingClickStart) > this.tapTimeout) {
			return true;
		}

		// Reset to prevent wrong click cancel on input (issue #156).
		this.cancelNextClick = false;

		this.lastClickTime = event.timeStamp;

		trackingClickStart = this.trackingClickStart;
		this.trackingClick = false;
		this.trackingClickStart = 0;

		// On some iOS devices, the targetElement supplied with the event is invalid if the layer
		// is performing a transition or scroll, and has to be re-detected manually. Note that
		// for this to function correctly, it must be called *after* the event target is checked!
		// See issue #57; also filed as rdar://13048589 .
		if (deviceIsIOSWithBadTarget) {
			touch = event.changedTouches[0];

			// In certain cases arguments of elementFromPoint can be negative, so prevent setting targetElement to null
			targetElement = document.elementFromPoint(touch.pageX - window.pageXOffset, touch.pageY - window.pageYOffset) || targetElement;
			targetElement.fastClickScrollParent = this.targetElement.fastClickScrollParent;
		}

		targetTagName = targetElement.tagName.toLowerCase();
		if (targetTagName === 'label') {
			forElement = this.findControl(targetElement);
			if (forElement) {
				this.focus(targetElement);
				if (deviceIsAndroid) {
					return false;
				}

				targetElement = forElement;
			}
		} else if (this.needsFocus(targetElement)) {

			// Case 1: If the touch started a while ago (best guess is 100ms based on tests for issue #36) then focus will be triggered anyway. Return early and unset the target element reference so that the subsequent click will be allowed through.
			// Case 2: Without this exception for input elements tapped when the document is contained in an iframe, then any inputted text won't be visible even though the value attribute is updated as the user types (issue #37).
			if ((event.timeStamp - trackingClickStart) > 100 || (deviceIsIOS && window.top !== window && targetTagName === 'input')) {
				this.targetElement = null;
				return false;
			}

			this.focus(targetElement);
			this.sendClick(targetElement, event);

			// Select elements need the event to go through on iOS 4, otherwise the selector menu won't open.
			// Also this breaks opening selects when VoiceOver is active on iOS6, iOS7 (and possibly others)
			if (!deviceIsIOS || targetTagName !== 'select') {
				this.targetElement = null;
				event.preventDefault();
			}

			return false;
		}

		if (deviceIsIOS && !deviceIsIOS4) {

			// Don't send a synthetic click event if the target element is contained within a parent layer that was scrolled
			// and this tap is being used to stop the scrolling (usually initiated by a fling - issue #42).
			scrollParent = targetElement.fastClickScrollParent;
			if (scrollParent && scrollParent.fastClickLastScrollTop !== scrollParent.scrollTop) {
				return true;
			}
		}

		// Prevent the actual click from going though - unless the target node is marked as requiring
		// real clicks or if it is in the whitelist in which case only non-programmatic clicks are permitted.
		if (!this.needsClick(targetElement)) {
			event.preventDefault();
			this.sendClick(targetElement, event);
		}

		return false;
	};


	/**
	 * On touch cancel, stop tracking the click.
	 *
	 * @returns {void}
	 */
	FastClick.prototype.onTouchCancel = function() {
		this.trackingClick = false;
		this.targetElement = null;
	};


	/**
	 * Determine mouse events which should be permitted.
	 *
	 * @param {Event} event
	 * @returns {boolean}
	 */
	FastClick.prototype.onMouse = function(event) {

		// If a target element was never set (because a touch event was never fired) allow the event
		if (!this.targetElement) {
			return true;
		}

		if (event.forwardedTouchEvent) {
			return true;
		}

		// Programmatically generated events targeting a specific element should be permitted
		if (!event.cancelable) {
			return true;
		}

		// Derive and check the target element to see whether the mouse event needs to be permitted;
		// unless explicitly enabled, prevent non-touch click events from triggering actions,
		// to prevent ghost/doubleclicks.
		if (!this.needsClick(this.targetElement) || this.cancelNextClick) {

			// Prevent any user-added listeners declared on FastClick element from being fired.
			if (event.stopImmediatePropagation) {
				event.stopImmediatePropagation();
			} else {

				// Part of the hack for browsers that don't support Event#stopImmediatePropagation (e.g. Android 2)
				event.propagationStopped = true;
			}

			// Cancel the event
			event.stopPropagation();
			event.preventDefault();

			return false;
		}

		// If the mouse event is permitted, return true for the action to go through.
		return true;
	};


	/**
	 * On actual clicks, determine whether this is a touch-generated click, a click action occurring
	 * naturally after a delay after a touch (which needs to be cancelled to avoid duplication), or
	 * an actual click which should be permitted.
	 *
	 * @param {Event} event
	 * @returns {boolean}
	 */
	FastClick.prototype.onClick = function(event) {
		var permitted;

		// It's possible for another FastClick-like library delivered with third-party code to fire a click event before FastClick does (issue #44). In that case, set the click-tracking flag back to false and return early. This will cause onTouchEnd to return early.
		if (this.trackingClick) {
			this.targetElement = null;
			this.trackingClick = false;
			return true;
		}

		// Very odd behaviour on iOS (issue #18): if a submit element is present inside a form and the user hits enter in the iOS simulator or clicks the Go button on the pop-up OS keyboard the a kind of 'fake' click event will be triggered with the submit-type input element as the target.
		if (event.target.type === 'submit' && event.detail === 0) {
			return true;
		}

		permitted = this.onMouse(event);

		// Only unset targetElement if the click is not permitted. This will ensure that the check for !targetElement in onMouse fails and the browser's click doesn't go through.
		if (!permitted) {
			this.targetElement = null;
		}

		// If clicks are permitted, return true for the action to go through.
		return permitted;
	};


	/**
	 * Remove all FastClick's event listeners.
	 *
	 * @returns {void}
	 */
	FastClick.prototype.destroy = function() {
		var layer = this.layer;

		if (deviceIsAndroid) {
			layer.removeEventListener('mouseover', this.onMouse, true);
			layer.removeEventListener('mousedown', this.onMouse, true);
			layer.removeEventListener('mouseup', this.onMouse, true);
		}

		layer.removeEventListener('click', this.onClick, true);
		layer.removeEventListener('touchstart', this.onTouchStart, false);
		layer.removeEventListener('touchmove', this.onTouchMove, false);
		layer.removeEventListener('touchend', this.onTouchEnd, false);
		layer.removeEventListener('touchcancel', this.onTouchCancel, false);
	};


	/**
	 * Check whether FastClick is needed.
	 *
	 * @param {Element} layer The layer to listen on
	 */
	FastClick.notNeeded = function(layer) {
		var metaViewport;
		var chromeVersion;
		var blackberryVersion;
		var firefoxVersion;

		// Devices that don't support touch don't need FastClick
		if (typeof window.ontouchstart === 'undefined') {
			return true;
		}

		// Chrome version - zero for other browsers
		chromeVersion = +(/Chrome\/([0-9]+)/.exec(navigator.userAgent) || [,0])[1];

		if (chromeVersion) {

			if (deviceIsAndroid) {
				metaViewport = document.querySelector('meta[name=viewport]');

				if (metaViewport) {
					// Chrome on Android with user-scalable="no" doesn't need FastClick (issue #89)
					if (metaViewport.content.indexOf('user-scalable=no') !== -1) {
						return true;
					}
					// Chrome 32 and above with width=device-width or less don't need FastClick
					if (chromeVersion > 31 && document.documentElement.scrollWidth <= window.outerWidth) {
						return true;
					}
				}

			// Chrome desktop doesn't need FastClick (issue #15)
			} else {
				return true;
			}
		}

		if (deviceIsBlackBerry10) {
			blackberryVersion = navigator.userAgent.match(/Version\/([0-9]*)\.([0-9]*)/);

			// BlackBerry 10.3+ does not require Fastclick library.
			// https://github.com/ftlabs/fastclick/issues/251
			if (blackberryVersion[1] >= 10 && blackberryVersion[2] >= 3) {
				metaViewport = document.querySelector('meta[name=viewport]');

				if (metaViewport) {
					// user-scalable=no eliminates click delay.
					if (metaViewport.content.indexOf('user-scalable=no') !== -1) {
						return true;
					}
					// width=device-width (or less than device-width) eliminates click delay.
					if (document.documentElement.scrollWidth <= window.outerWidth) {
						return true;
					}
				}
			}
		}

		// IE10 with -ms-touch-action: none or manipulation, which disables double-tap-to-zoom (issue #97)
		if (layer.style.msTouchAction === 'none' || layer.style.touchAction === 'manipulation') {
			return true;
		}

		// Firefox version - zero for other browsers
		firefoxVersion = +(/Firefox\/([0-9]+)/.exec(navigator.userAgent) || [,0])[1];

		if (firefoxVersion >= 27) {
			// Firefox 27+ does not have tap delay if the content is not zoomable - https://bugzilla.mozilla.org/show_bug.cgi?id=922896

			metaViewport = document.querySelector('meta[name=viewport]');
			if (metaViewport && (metaViewport.content.indexOf('user-scalable=no') !== -1 || document.documentElement.scrollWidth <= window.outerWidth)) {
				return true;
			}
		}

		// IE11: prefixed -ms-touch-action is no longer supported and it's recomended to use non-prefixed version
		// http://msdn.microsoft.com/en-us/library/windows/apps/Hh767313.aspx
		if (layer.style.touchAction === 'none' || layer.style.touchAction === 'manipulation') {
			return true;
		}

		return false;
	};


	/**
	 * Factory method for creating a FastClick object
	 *
	 * @param {Element} layer The layer to listen on
	 * @param {Object} [options={}] The options to override the defaults
	 */
	FastClick.attach = function(layer, options) {
		return new FastClick(layer, options);
	};


	if (typeof define === 'function' && typeof define.amd === 'object' && define.amd) {

		// AMD. Register as an anonymous module.
		define(function() {
			return FastClick;
		});
	} else if (typeof module !== 'undefined' && module.exports) {
		module.exports = FastClick.attach;
		module.exports.FastClick = FastClick;
	} else {
		window.FastClick = FastClick;
	}
}());
;(function() {
    var keyhtml, input_html = "",
        $thisinput, newvalue = "";
    var virtualKey = function(element, opts) {
        var _this = this;
        var options = {
            delayHiden: function() {},
            focusFn: function() {},
            changeFn: function() {},
            defaultwidth: "100%",
            defaultheigh: "30px",
            placeholder: "请输入",
            defaulvalue:null
        }

        _this.el = element;
        $.extend(options, opts);
        _this.options = options;
        _this.init();
        if (!$(element).length) {
            return;
        } else {
            $(element).append(input_html);
        }
        if ($(".key_ul").length == 0) {
            $("body").append(keyhtml);
        }
        //点击事件
        _this.keyboardshow();
        _this.keyboardhide();
        _this.kbAddvalue();
        $(".key_bg").bind("touchend", function() {
            _this.keyboardhide();

        });
    };

    virtualKey.prototype = {
        //初始化
        init: function() {
            //绘制键盘展示
            keyhtml = "<div class=\"key_ul\"><div class=\"key_bg disnone\"></div>";
            keyhtml += "<ul class=\"clearfix\">";
            keyhtml += "  <li data-key=1><span>1</span></li>";
            keyhtml += "  <li data-key=2><span>2</span></li>";
            keyhtml += "  <li data-key=3><span>3</span></li>";
            keyhtml += "  <li data-key=4><span>4</span></li>";
            keyhtml += "  <li data-key=5><span>5</span></li>";
            keyhtml += "  <li data-key=6><span>6</span></li>";
            keyhtml += "  <li data-key=7><span>7</span></li>";
            keyhtml += "  <li data-key=8><span>8</span></li>";
            keyhtml += "  <li data-key=9><span>9</span></li>";
            keyhtml += "  <li data-key=.><span>.</span></li>";
            keyhtml += "  <li data-key=0><span>0</span></li>";
            keyhtml += "  <li data-key=\"hide\"><span><i class=\"bg_hide\"></i></span></li>";
            keyhtml += "</ul>";
            keyhtml += " <div class=\"key_right\">";
            keyhtml += "<div class=\"key_del\" data-key=\"del\"><span><i class=\"bg_del\"></i></span></div>";
            keyhtml += "<div class=\"key_sure\" data-key=\"sure\"><span>确定</span></div>";
            keyhtml += "</div>";
            keyhtml += "</div>";

            var _this = this;
            //绘制输入框
            input_html += "<span class=\"inp_main\">";
            input_html += _this.options.defaulvalue == null ? "<span class=\"btn_key\"></span>" : "<span class=\"btn_key\">" + _this.options.defaulvalue + "</span>";
            input_html += "<i class=\"disnone\">&nbsp;</i>";

            //判断是否有默认值和placeholder

            if (_this.options.placeholder != null && _this.options.defaulvalue == null) {
                input_html += " <span class=\"inp_text\">" + _this.options.placeholder + "</span>";
            } else {
                input_html += " <span class=\"inp_text\"></span>";
            }


            input_html += "</span>";
        },
        //键盘弹出
        keyboardshow: function() {
            var _this = this;
            var $inp_main = $(_this.el),
                $key_ul = $(".key_ul");
            $(".key_bg").height(document.body.scrollHeight);
            $inp_main.bind("touchend", function(event) {
                event.preventDefault();
                _this.options.focusFn();
                $inp_main.find("i").addClass("disnone");
                $(this).find("i").removeClass("disnone");
                $(".key_bg").removeClass("disnone");
                $(this).find(".inp_text").addClass("disnone");
                if (!$key_ul.hasClass('show')) {
                    $key_ul.addClass('show');
                }
                // if (!$key_ul.find("li").hasClass("msg_show")) {
                //     $key_ul.find("li").removeClass("msg_hide").addClass("msg_show");
                //     $(".key_del").removeClass("btn_hide").addClass("btn_show");
                //     $(".key_sure").removeClass("btn_hide").addClass("btn_show");
                //     $(".key_right").addClass("mdiv_show");
                // }
                $thisinput = $(this).find(".btn_key");
                newvalue = $thisinput.html();
            });
        },
        //键盘隐藏
        keyboardhide: function() {
            var _this = this;
            var $key_ul = $(".key_ul");
            var $key_btn = $(".btn_key");
            var keyVal;
            //$(".key_ul").addClass("disnone");

            if ($key_ul.hasClass('show')) {
                $key_ul.removeClass('show');
            }
            // if ($(".key_ul").find("li").hasClass("msg_show")) {
            //     $(".key_ul").find("li").removeClass("msg_show").addClass("msg_hide");
            //     $(".key_del").removeClass("btn_show").addClass("btn_hide");
            //     $(".key_sure").removeClass("btn_show").addClass("btn_hide");
            //     $(".key_right").removeClass("mdiv_show");
            // }
            $(".inp_main").find("i").addClass("disnone");
            keyVal = $key_btn.html();
            if (keyVal.slice(-1) == '.') {
                keyVal = keyVal.slice(0, -1);
                $key_btn.html(keyVal);
            }
            $key_btn.each(function() {
                if ($(this).html().length == 0) {
                    $(this).parent().find(".inp_text").removeClass("disnone");
                }
            });
            setTimeout(function () {
                _this.options.delayHiden();
            }, 250);

            setTimeout(function () {
                $(".key_bg").addClass("disnone");
            }, 500);
        },
        //点击赋值
        kbAddvalue: function() {
            var _this = this;
            var $key_ul = $(".key_ul"),
                keyboard_key;
            $key_ul.find("ul>li").on("touchstart", function() {
                $(this).addClass("tap_color").siblings().removeClass('tap_color');
            });
            $key_ul.find("ul>li").bind("touchend", function() {
                $(this).removeClass("tap_color");
                keyboard_key = $(this).attr("data-key");
                if (keyboard_key == "del") {
                    newvalue != "" ? newvalue = newvalue.substring(0, newvalue.length - 1) : null;
                    $thisinput.html(newvalue);
                    _this.options.changeFn();
                } else if (keyboard_key == "sure") {
                    _this.keyboardhide();
                } else if (keyboard_key == "hide") {
                    _this.keyboardhide();
                } else {
                    var sval = $(this).attr("data-key");
                    if (sval == "." && newvalue.indexOf(".") == "-1") {
                        newvalue += sval;
                    }
                    if (/^(([0-9]|([1-9][0-9]{0,7}))((\.[0-9]{1,2})?))$/.test(newvalue + sval)) {
                        newvalue += sval;
                    }
                    $thisinput.html(newvalue);
                    _this.options.changeFn();
                }
            });
            $(".key_right>div").on("touchstart", function() {
                $(this).addClass("tap_color").siblings().removeClass('tap_color');

            });
            $(".key_right>div").bind("touchend", function(event) {
                event.preventDefault();
                $(this).removeClass("tap_color");
                keyboard_key = $(this).attr("data-key");
                if (keyboard_key == "del") {
                    newvalue != "" ? newvalue = newvalue.substring(0, newvalue.length - 1) : null;
                    $thisinput.html(newvalue);
                    _this.options.changeFn();
                } else {
                    _this.keyboardhide();
                }
            });
            ////长按删除所有
            $(".key_del>span").bind("longTap", function() {
                newvalue = "";
                $thisinput.html("");
                _this.options.changeFn();
            });
            $(".bg_del").bind("longTap", function() {
                newvalue = "";
                $thisinput.html("");
                _this.options.changeFn();
            });
        }
    }
    window.virtualKey = virtualKey;
    // 页面滚动后不触发touchend事件
    function stopTouchendPropagationAfterScroll(){
        var locked = false;
    
        window.addEventListener('touchmove', function(ev){
            locked || (locked = true, window.addEventListener('touchend', stopTouchendPropagation, true));
        }, true);
        function stopTouchendPropagation(ev){
            ev.stopPropagation();
            window.removeEventListener('touchend', stopTouchendPropagation, true);
            locked = false;
        }
    }
    stopTouchendPropagationAfterScroll();
})();
;/*
 *_nowMoney  传入金额
 *formatClass  ul class
 *activeClass  当前选中class
*/
$.getformatMoney = function (_nowMoney, formatClass, activeClass) {
    var html="";
    var MoneyArr =[ '个', '十', '百', '千', '万', '十万', '百万', '千万'];
    if (_nowMoney != "" && !isNaN(_nowMoney)) {
        _nowMoney=Number(_nowMoney);
        html = '<ul class="' + formatClass + ' clearfix">';
        //转换
        var _intlength = parseInt(_nowMoney).toString().length;
        if (_intlength > 8) {
            _intlength = 8;
        }
        for (var i = _intlength-1; i >=0; i--) {
            if (i == _intlength - 1) {
                html += '<li class="'+ activeClass +' money_li'+ i +'">'+ MoneyArr[i] +'</li>';
            } else {
                html += '<li class=' +' money_li'+ i +'>' + MoneyArr[i] + '</li>';
            }
        }
        html += '</ul>';
    } else {
        html = '';
    }
    return html;

};/**
 *
 */
var P2PWAP = {};

P2PWAP.Const = {
    COOKIE_HIDE_APPDOWNLOAD: 'mp2p_hide_appdownload',
    AJAX_SIGN: window['_AJAXSIGN_']
};
P2PWAP.Common = {};

P2PWAP.util = {};

P2PWAP.util.setCookie = function(name, value, expiredays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate()+expiredays)
    document.cookie=name+ "=" +escape(value)+ ";path=/" +
    ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
};

P2PWAP.util.request = function(requrl, success_callback, errorCallback, opt_method, opt_data) {
    var senddata = (typeof opt_data == 'object') ? opt_data : {};
    senddata['asgn'] = P2PWAP.Const.AJAX_SIGN;
    return $.ajax({
        url: requrl,
        type: opt_method == "post" ? "post" : "get",
        dataType: 'json',
        data: senddata,
        success: function(json) {
            if (!json) {
                errorCallback.call(null, '服务器忙,请稍后重试');
                return;
            }
            if (json['errno'] != 0) {
                errorCallback.call(null, json['error']);
                return;
            }
            success_callback.call(null, json['data']);
        },
        error: function() {
            errorCallback.call(null, '您的网络貌似不给力,请稍后重试');
        }
    });
};

P2PWAP.util.dataFormat = function(timestamp,type){
    var data = new Date(timestamp * 1000);
    var year = data.getFullYear();
    var month = data.getMonth() + 1;
    var day = data.getDate();
    var hour = data.getHours();
    var minute = data.getMinutes();
    var second = data.getSeconds();
    //加0
    if(month < 10)  month = '0' + month;
    if(day < 10)    day = '0' + day;
    if(hour < 10)   hour = '0' + hour;
    if(minute < 10) minute = '0' + minute;
    if(second < 10) second = '0' + second;

    if (typeof type == 'undefined' || type == '') {
        return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
    } else {
        return type.replace('y',year).replace('m',month).replace('d',day).replace('h',hour).replace('i',minute).replace('s',second);
    }
};

P2PWAP.util.wxJudge = function(){
    var userAgentString = window.navigator ? window.navigator.userAgent : "";
    var weixinreg = /MicroMessenger/i;
    return weixinreg.test(userAgentString);
};

P2PWAP.util.checkMobile = function(val) {
    return /^0?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/.test(val);
};

P2PWAP.ui = {};

P2PWAP.ui.confirm = function(title, content, opt_confirmCallback, opt_cancelCallback) {
    var popup = document.createElement("div");
    popup.className = "ui_confirm";
    var html = "";
    html += '<div class="opacity"></div>';
    html += '<div class="confirm_donate">';
    html += '    <p class="confirm_donate_title">' + title + '</p>';
    html += '    <p class="confirm_donate_text">' + content + '</p>';
    html += '    <div class="confirm_donate_but">';
    html += '        <input type="button" class="JS-cancel confirm_donate_but_del" value="取消">';
    html += '        <input type="button" class="JS-confirm confirm_donate_but_yes" value="确认">';
    html += '    </div>';
    html += '</div>';
    popup.innerHTML = html;
    $("body").append(popup);
    $(popup).find(".JS-cancel").bind("click", function() {
        $(popup).remove();
        if (typeof opt_cancelCallback == "function") {
            opt_cancelCallback.call(null);
        }
    });
    $(popup).find(".JS-confirm").bind("click", function() {
        $(popup).remove();
        if (typeof opt_confirmCallback == "function") {
            opt_confirmCallback.call(null);
        }
    });
};

P2PWAP.ui.showErrorInstance_ = null;
P2PWAP.ui.showErrorInstanceTimer_ = null;
P2PWAP.ui.showErrorTip = function(msg) {
    if (P2PWAP.ui.showErrorInstance_) {
        clearTimeout(P2PWAP.ui.showErrorInstanceTimer_);
        P2PWAP.ui.showErrorInstance_.updateContent(msg);
    } else {
        P2PWAP.ui.showErrorInstance_ = new P2PWAP.ui.ErrorToaster_(msg);
        P2PWAP.ui.showErrorInstance_.show();
    }
    P2PWAP.ui.showErrorInstanceTimer_ = setTimeout(function() {
        P2PWAP.ui.showErrorInstance_.dispose();
        P2PWAP.ui.showErrorInstance_ = null;
        P2PWAP.ui.showErrorInstanceTimer_ = null;
    }, 2000);
};
P2PWAP.ui.toast = P2PWAP.ui.showErrorTip;

P2PWAP.ui.ErrorToaster_ = function(msgHtml) {
    this.ele = null;
    this.msgHtml = msgHtml;
};

P2PWAP.ui.ErrorToaster_.prototype.createDom = function() {
    this.ele = document.createElement("div");
    this.ele.innerHTML = "<span class='set_rem' style=\"display: inline-block;color:white;max-width:250px;min-width:100px;word-break:break-word;padding:10px;background:rgba(0,0,0,0.7);border-radius:5px;\">" + this.msgHtml + "</span>";
    this.ele.setAttribute("style", "z-index:1002;position:fixed;width:100%;text-align:center;top:50%;-webkit-transition:opacity linear 0.5s;opacity:0;");
    document.body.appendChild(this.ele);
};

P2PWAP.ui.ErrorToaster_.prototype.updateContent = function(msgHtml) {
    this.msgHtml = msgHtml;
    if (!this.ele) return;
    $(this.ele).find("span").html(this.msgHtml);
};

P2PWAP.ui.ErrorToaster_.prototype.show = function() {
    if (!this.ele) {
        this.createDom();
    }
    var pThis = this;
    setTimeout(function() {
        if (!pThis.ele) return;
        pThis.ele.style.opacity = "1";
    }, 1);
};

P2PWAP.ui.ErrorToaster_.prototype.hide = function() {
    if (!this.ele) return;
    this.ele.style.opacity = "0";
    var ele = this.ele;
    delete this.ele;
    setTimeout(function() {
        document.body.removeChild(ele);
    }, 500);
};

P2PWAP.ui.ErrorToaster_.prototype.dispose = function() {
    this.hide();
};

P2PWAP.ui.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_numperpage, opt_hasmore) {
    this.urlbase = urlbase;
    this.page = opt_page > 1 ? opt_page : 1;
    this.loading = false;
    this.xhr = null;
    this.hasNoMore = opt_hasmore == true;
    this.numPerPage = opt_numperpage > 1 ? opt_numperpage : 10;
    this.container = container;
    this.loadmorepanel = loadmorepanel;
    this.updateLoadMoreBtn();
};
P2PWAP.ui.P2PLoadMore.prototype.loadNextPage = function() {
    if (this.loading) {
        return;
    }
    this.setLoading(true);
    var pThis = this;
    this.xhr = P2PWAP.util.request(this.urlbase,
            function(rawData){
                pThis.setLoading(false);
                pThis.processData(rawData);
            },
            function(errorMsg){
                pThis.setLoading(false);
                P2PWAP.ui.toast(errorMsg);
            },
            'get', {'p': this.page});
};
P2PWAP.ui.P2PLoadMore.prototype.setLoading = function(loading) {
    this.loading = loading;
    if (this.loading == false && this.xhr) {
        delete this.xhr;
    }
    this.updateLoadMoreBtn();
};
P2PWAP.ui.P2PLoadMore.prototype.updateLoadMoreBtn = function(){
    if (this.loading) {
        this.loadmorepanel.innerHTML = '<div class="ui_loading"><div class="bar1"></div><div class="bar2"></div><div class="bar3"></div><div class="bar4"></div><div class="bar5"></div><div class="bar6"></div><div class="bar7"></div><div class="bar8"></div><div class="bar9"></div><div class="bar10"></div><div class="bar11"></div><div class="bar12"></div></div>&nbsp;&nbsp;正在加载';
    } else if(this.hasNoMore) {
        this.loadmorepanel.innerHTML = "没有更多了";
    } else {
        this.loadmorepanel.innerHTML = '<a href="javascript:void(0)">点击加载更多</a>';
        var pThis = this;
        $(this.loadmorepanel).find("a").unbind("click").bind("click", function(){
            pThis.loadNextPage();
        });
    }
};
P2PWAP.ui.P2PLoadMore.prototype.processData = function (rawData) {
    this.page++;
    var length = rawData ? rawData.length : 0;
    if (rawData && length > 0) {
        for (var i = 0; i < length; i++) {
            var addDom = this.createItem(rawData[i]);
            if($.isArray(addDom)){
                for(var j = 0; j < addDom.length; j++){
                    this.container.appendChild(addDom[j]);
                }
            } else {
                this.container.appendChild(addDom);
            }
        }
    }
    if (!(length >= this.numPerPage)) {
        this.hasNoMore = true;
    }
    this.updateLoadMoreBtn();
};
P2PWAP.ui.P2PLoadMore.prototype.preloadPage = function(rawData, page) {
    this.page = page;
    this.processData(rawData);
};
P2PWAP.ui.P2PLoadMore.prototype.refresh = function(){
    if (this.xhr) {
        this.xhr.abort();
        delete this.xhr;
    }
    this.page = 1;
    this.hasNoMore = false;
    this.loading = false;
    this.container.innerHTML = '';
    this.updateLoadMoreBtn();
    this.loadNextPage();
};
P2PWAP.ui.P2PLoadMore.prototype.createItem = function(dataItem) {
    var div = document.createElement("div");
    return div;
};

P2PWAP.cache = {};
P2PWAP.cache._StoreKey_ = "_P2PCACHEKEY_";
P2PWAP.cache._CacheData_ = null;

P2PWAP.cache._init_ = function() {
    var json = localStorage.getItem(P2PWAP.cache._StoreKey_);
    if (!json) json = JSON.stringify({});
    var cacheObj = JSON.parse(json);
    var nowDate = (new Date()).getTime();
    for (var i in cacheObj) {
        if (cacheObj[i]['timeStamp'] < nowDate) delete cacheObj[i];
    }
    P2PWAP.cache._CacheData_ = cacheObj;
    P2PWAP.cache._store_();
};
P2PWAP.cache._store_ = function() {
    try {
        localStorage.setItem(P2PWAP.cache._StoreKey_, JSON.stringify(P2PWAP.cache._CacheData_));
    } catch (e) {

    }
};
P2PWAP.cache.get = function(key) {
    P2PWAP.cache._init_();
    if (P2PWAP.cache._CacheData_[key]) {
        return P2PWAP.cache._CacheData_[key]['value'];
    }
    return undefined;
};

P2PWAP.cache.set = function(key, value, during) {
    if (!during) during = 60;
    P2PWAP.cache._init_();
    var timeStamp = (new Date()).getTime() + during * 1000;
    P2PWAP.cache._CacheData_[key] = {"value": value, "timeStamp": timeStamp};
    P2PWAP.cache._store_();
};
P2PWAP.cache.del = function(key) {
    P2PWAP.cache._init_();
    if (!P2PWAP.cache._CacheData_[key]) return;
    delete P2PWAP.cache._CacheData_[key];
    P2PWAP.cache._store_();
};

P2PWAP.ui.instanceTextClip = function(lineDom, selfFn, showFn, hideFn, option) {
    if ($(lineDom).attr("data-textclip") == "true") return;
    if (typeof selfFn != 'function') selfFn = function() {};
    if (typeof showFn != 'function') showFn = function() {};
    if (typeof hideFn != 'function') hideFn = function() {};
    var _instance = new P2PWAP.ui.textClip(lineDom, showFn, hideFn, option);
    selfFn.call(null, _instance);
    _instance.init();
    return _instance;
};
P2PWAP.ui.textClip = function(lineDom, showFn, hideFn, option) {
    var option = (typeof option == 'object') ? option : {};
    this.showFn = showFn;
    this.hideFn = hideFn;
    this.lineDom = $(lineDom);
    this.opt = $.extend({}, option);
};
P2PWAP.ui.textClip.prototype.init = function() {
    $(this.lineDom).attr("data-textclip", "true");
    this.judgeHeight();
};
P2PWAP.ui.textClip.prototype.judgeHeight = function() {
    var _this = this;
    _this.lineDom.css({
        "white-space": "nowrap",
        "overflow": "hidden"
    });
    _this.lineHeight = _this.lineDom[0].clientHeight;
    _this.lineDom.css({
        "white-space": "normal",
        "overflow": "visible"
    });
    if (_this.lineDom[0].clientHeight > _this.lineHeight) {
        _this.neddClip = true;
        _this.setDom();
    }
};
P2PWAP.ui.textClip.prototype.setDom = function() {
    var _this = this;
    _this.lineDom.addClass('__textClip__');
    _this.lineDom.css({
        "white-space": "nowrap",
        "overflow": "hidden",
        "text-overflow": "ellipsis"
    });
    _this.createArrow();
    _this.arrowIsDown = true;
    _this.arrowEvent();
};
P2PWAP.ui.textClip.prototype.createArrow = function() {
    this.lineDom.append('<span class="__textClipArrow__ __textClipArrowDown__"></span>');
    this.arrowEle = this.lineDom.find('.__textClipArrow__');
};
P2PWAP.ui.textClip.prototype.arrowEvent = function() {
    var _this = this;
    $(_this.arrowEle).click(function(event) {
        // event.preventDefault();
        if (_this.arrowIsDown) {
            _this.arrowIsDown = false;
            $(this).removeClass('__textClipArrowDown__').addClass('__textClipArrowUp__');
            _this.lineDom.css({
                "white-space": "normal",
                "overflow": "visible",
                "text-overflow": "clip"
            });
            _this.showFn.call(null, _this);
        } else {
            _this.arrowIsDown = true;
            $(this).removeClass('__textClipArrowUp__').addClass('__textClipArrowDown__');
            _this.lineDom.css({
                "white-space": "nowrap",
                "overflow": "hidden",
                "text-overflow": "ellipsis"
            });
            _this.hideFn.call(null, _this);
        }
    });
}

$(function(){
    //初始化header
    $("#JS-downloadPanelClose").bind("click", function(){
      P2PWAP.util.setCookie(P2PWAP.Const.COOKIE_HIDE_APPDOWNLOAD, 'true', 3 * 24 * 3600);
      $("#JS-headPanel").addClass("down_app_none");
    });
  });;if (typeof WXP2P == "undefined") {
  WXP2P = {};
}
if (typeof WXP2P.APP == "undefined") {
  WXP2P.APP = {};
}
// proto
// WXP2P.APP.browser_model = false;
WXP2P.APP.browser_model = false;
WXP2P.APP.warpAnchorSchema = function(anchor) {
  if (this.browser_model == true) return;
  if(anchor.href == 'javascript:void(0);') return;
  var proto = anchor.getAttribute("data-proto");
  if (proto == null || proto == "") {
    return;
  }
  var url = proto + "?url=" + encodeURIComponent(anchor.href);
  var stringArr = ['title','backtype', 'backid', 'type','identity', 'needcloseall'];
  var booleanArr = ['needback','needrefresh'];
  for(var i = 0; i < stringArr.length; i++){
    var str = anchor.getAttribute('data-' + stringArr[i]);
    if (str != null && str != "") {
      url += "&" + stringArr[i] + "=" + encodeURIComponent(str);
    }
  }
  for(var i = 0; i < booleanArr.length; i++){
    var boolean = anchor.getAttribute('data-' + booleanArr[i]);
    if (boolean == "true") {
      url += "&" + booleanArr[i] + "=true";
    }else{
      url += "&" + booleanArr[i] + "=false";
    }
  }
  anchor.href = url;
};

WXP2P.APP.batchWarpAnchorSchema = function(el){
  $(el).each(function(k,v){
    WXP2P.APP.warpAnchorSchema($(v)[0]);
  });
}

if (typeof WXP2P.UI == "undefined") {
  WXP2P.UI = {};
}

WXP2P.APP.setCookie = function(name, value, expiredays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate()+expiredays)
    document.cookie=name+ "=" +escape(value)+ ";path=/" +
    ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
};

WXP2P.APP.getCookie = function(name) {
    var cookiestr = document.cookie;
    if (cookiestr == null || cookiestr == "") return null;
    var cookieArrs = cookiestr.split(";");
    for (var i = cookieArrs.length - 1; i >= 0; i--) {
        var cookiekvstr = $.trim(cookieArrs[i]);
        var kv = cookiekvstr.split("=");
        var key = kv[0];
        var value = decodeURIComponent(kv[1]);
        if (key == name) {
            return value;
        }
    }
    return null;
};

// loadMore
WXP2P.UI.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_type, opt_numperpage) {
    var pThis = this;
    pThis.urlbase = urlbase;
    pThis.page = opt_page > 1 ? opt_page : 1;
    pThis.loading = false;
    pThis.ajaxType = opt_type == "post" ? "post" : "get";
    pThis.numPerPage = opt_numperpage > 1 ? opt_numperpage : 10;
    pThis.container = container;
    pThis.loadmorepanel = loadmorepanel;
    $(pThis.loadmorepanel).find("a").bind("click", function(){
      pThis.loadNextPage();
    });
};

WXP2P.UI.P2PLoadMore.prototype.loadNextPage = function() {
    var pThis = this;
    if (pThis.loading) {
        return;
    }
    pThis.setLoading(true);
    $.ajax({
        url: pThis.urlbase + "&page=" + pThis.page,
        type: pThis.ajaxType,
        dataType: 'json',
        success: function(rawData) {
            pThis.setLoading(false);
            pThis.processData(rawData);
        },
        error: function() {
            pThis.setLoading(false);
            P2PWAP.ui.showErrorTip('<p>网络错误</p>');
        }
    });
};

WXP2P.UI.P2PLoadMore.prototype.setLoading = function(loading) {
    this.loading = loading;
    this.loadmorepanel.innerHTML = "加载中...";
};

WXP2P.UI.P2PLoadMore.prototype.preProcessData = function(ajaxData) {
    return ajaxData;
};

WXP2P.UI.P2PLoadMore.prototype.processData = function(ajaxData) {
    var pThis = this;
    ajaxData = this.preProcessData(ajaxData);
    if (!ajaxData.data) {
        //NOTE: 添加处理错误
        return;
    }
    pThis.page++;
    var listDataItem = ajaxData.data;
    if (listDataItem.length > 0) {
        for(var index = 0; index < listDataItem.length; index++) {
            pThis.container.appendChild(pThis.createItem(listDataItem[index]));
        }
    }
    if (!(listDataItem.length >= pThis.numPerPage)) {
        pThis.loadmorepanel.innerHTML = "没有更多了";
    }else{
        pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
        $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
          pThis.loadNextPage();
        });
    }
};

WXP2P.UI.P2PLoadMore.prototype.createItem = function(dataItem) {
    var div = document.createElement("div");
    return div;
};

// 轮询
if (typeof WXP2P.UTIL == "undefined") {
  WXP2P.UTIL = {};
}
WXP2P.UTIL._notificationScriptTagMap = {};
WXP2P.UTIL.longLoopLink = function (link, onError, key){
	if (WXP2P.UTIL._notificationScriptTagMap[key] != null) {
		document.body.removeChild(WXP2P.UTIL._notificationScriptTagMap[key]);
		delete WXP2P.UTIL._notificationScriptTagMap[key];
	}
	WXP2P.UTIL._notificationScriptTagMap[key] = document.createElement("script");
	WXP2P.UTIL._notificationScriptTagMap[key].src = link;
	WXP2P.UTIL._notificationScriptTagMap[key].onerror = function(err){
		document.body.removeChild(WXP2P.UTIL._notificationScriptTagMap[key]);
		delete WXP2P.UTIL._notificationScriptTagMap[key];
		WXP2P.UTIL._notificationScriptTagMap[key] = null;
		onError.call(null);
	}
	WXP2P.UTIL._notificationScriptTagMap[key].onload = function() {
		document.body.removeChild(WXP2P.UTIL._notificationScriptTagMap[key]);
		delete WXP2P.UTIL._notificationScriptTagMap[key];
		WXP2P.UTIL._notificationScriptTagMap[key] = null;
	}
	document.body.appendChild(WXP2P.UTIL._notificationScriptTagMap[key]);
}

WXP2P.UTIL.dataFormat = function(timestamp,type,format){
    var data = new Date(timestamp * 1000);
    var year = data.getFullYear();
    var month = data.getMonth() + 1;
    var day = data.getDate();
    var hour = data.getHours();
    var minute = data.getMinutes();
    var second = data.getSeconds();
    //加0
    if(month < 10)  month = '0' + month;
    if(day < 10)    day = '0' + day;
    if(hour < 10)   hour = '0' + hour;
    if(minute < 10) minute = '0' + minute;
    if(second < 10) second = '0' + second;

    if (typeof type == 'undefined' || type == '') {
      if(format==1){
        return month + '-' + day + ' ' + hour + ':' + minute;
      }else{
        return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
      }
    } else {
        if(format==1){
          return type.replace('m',month).replace('d',day).replace('h',hour).replace('i',minute);
        }else{
            return type.replace('y',year).replace('m',month).replace('d',day).replace('h',hour).replace('i',minute).replace('1s',second);
          }
        }

};



WXP2P.UI.showErrorInstance_ = null;
WXP2P.UI.showErrorInstanceTimer_ = null;
WXP2P.UI.showErrorTip = function(msg) {
    if (WXP2P.UI.showErrorInstance_) {
        clearTimeout(WXP2P.UI.showErrorInstanceTimer_);
        WXP2P.UI.showErrorInstance_.updateContent(msg);
    } else {
        WXP2P.UI.showErrorInstance_ = new WXP2P.UI.ErrorToaster_(msg);
        WXP2P.UI.showErrorInstance_.show();
    }
    WXP2P.UI.showErrorInstanceTimer_ = setTimeout(function() {
        WXP2P.UI.showErrorInstance_.dispose();
        WXP2P.UI.showErrorInstance_ = null;
        WXP2P.UI.showErrorInstanceTimer_ = null;
    }, 2000);
};
WXP2P.UI.toast = WXP2P.UI.showErrorTip;

WXP2P.UI.ErrorToaster_ = function(msgHtml) {
    this.ele = null;
    this.msgHtml = msgHtml;
};

WXP2P.UI.ErrorToaster_.prototype.createDom = function() {
    this.ele = document.createElement("div");
    this.ele.innerHTML = "<span style=\"display: inline-block;color:white;max-width:250px;min-width:100px;word-break:break-word;padding:10px;background:rgba(0,0,0,0.7);border-radius:5px;\">" + this.msgHtml + "</span>";
    this.ele.setAttribute("style", "z-index:1002;position:fixed;width:100%;text-align:center;top:45%;-webkit-transition:opacity linear 0.5s;opacity:0;");
    document.body.appendChild(this.ele);
};

WXP2P.UI.ErrorToaster_.prototype.updateContent = function(msgHtml) {
    this.msgHtml = msgHtml;
    if (!this.ele) return;
    $(this.ele).find("span").html(this.msgHtml);
};

WXP2P.UI.ErrorToaster_.prototype.show = function() {
    if (!this.ele) {
        this.createDom();
    }
    var pThis = this;
    setTimeout(function() {
        if (!pThis.ele) return;
        pThis.ele.style.opacity = "1";
    }, 1);
};

WXP2P.UI.ErrorToaster_.prototype.hide = function() {
    if (!this.ele) return;
    this.ele.style.opacity = "0";
    var ele = this.ele;
    delete this.ele;
    setTimeout(function() {
        document.body.removeChild(ele);
    }, 500);
};

WXP2P.UI.ErrorToaster_.prototype.dispose = function() {
    this.hide();
};

$(function(){
  // 进入页面拼写proto
    WXP2P.APP.batchWarpAnchorSchema('body a');
    var meta = "<meta name=\"format-detection\" content=\"telephone=no\" />";
    $("head").append(meta);
});;$(function() {
    // tofixed
    Number.prototype.toFixed = function(len) {
        if (len <= 0) {
            return parseInt(Number(this));
        }
        var tmpNum1 = Number(this) * Math.pow(10, len);
        var tmpNum2 = parseInt(tmpNum1) / Math.pow(10, len);
        if (tmpNum2.toString().indexOf('.') == '-1') {
            tmpNum2 = tmpNum2.toString() + '.';
        }
        var dotLen = tmpNum2.toString().split('.')[1].length;
        if (dotLen < len) {
            for (var i = 0; i < len - dotLen; i++) {
                tmpNum2 = tmpNum2.toString() + '0';
            }
        }
        return tmpNum2;
    };
    // 三位显示逗号
    function showDou(val) {
        var arr = val.toString().split("."),
            arrInt = arr[0].split("").reverse(),
            temp = 0,
            j = arrInt.length / 3;
        for (var i = 1; i < j; i++) {
            arrInt.splice(i * 3 + temp, 0, ",");
            temp++;
        }
        return arrInt.reverse().concat(".", arr[1]).join("");
    };

    // 小数变整乘法
    function accMul(arg1, arg2) {
        var m = 0,
            s1 = arg1.toString(),
            s2 = arg2.toString();
        try {
            m += s1.split(".")[1].length
        } catch (e) {};
        try {
            m += s2.split(".")[1].length
        } catch (e) {};
        return Number(s1.replace(".", "")) * Number(s2.replace(".", "")) / Math.pow(10, m);
    }

    var investmentID,addParams,discount_goodprice,discountListNum,discountListTotalPage=0;
    var moneyValidate = false,investChooseValidate = false;
    var isChoose = $("input[name='isChoose']").val();
    var val_discount_id = $(".val_discount_id").html();
    var code,couponIsFixed ;
    function cancleDefault(evt) {
      if(!evt._isScroller) {
        evt.preventDefault();
      }
    }

    //获取url参数
    function GetRequestURL(hrefD) {
       var url = hrefD; //获取url中"?"符后的字串
       var theRequest = new Object();
       if (url.indexOf("?") != -1) {
          var str = url.substr(1);
          strs = str.split("&");
          for(var i = 0; i < strs.length; i ++) {
             theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
          }
       }
       return theRequest;
    }

    // 更新按钮状态和链接
    function updateState() {
        investmentID = $(".investmentID").html();
        var int_merry = $(".ui_input .btn_key").html() * 1;
        if($(".icon_select").length>0){
            var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
            var Request = new Object();
            Request = GetRequestURL(hrefD);
            var discount_id = Request["discount_id"];
            var discount_group_id = Request["discount_group_id"];
            var discount_sign = Request["discount_sign"];
            var discount_type = Request["discount_type"];
            var discountbidAmount = Request["discount_bidAmount"];
        }else{
            var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
        }
        var deal_min = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, discountbidAmount);
        code = $('.val_code').html();
        couponIsFixed = $('.is_fixed').html();
        var _perpent = $(".perpent").html();
        var per = 0;
        if (_perpent != "") {
            per = parseFloat($(".perpent").html());
        }
        if(window['deal_type'] == 0){
          $(".inp_text").html(deal_min + '元起');
        }else{
          $(".inp_text").html(deal_min + '元起投');
        }

        // 金额判断
        if (int_merry == '') {
            $(".dit_yq").html("");
            moneyValidate = false;
        } else if (/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(int_merry)) {
            if (int_merry < deal_min) {
                if(window['deal_type'] == 0){
                  $(".dit_yq").html('最低出借金额为' + deal_min + '元').addClass("ai_color");
                }else{
                  $(".dit_yq").html('起投金额为' + deal_min + '元').addClass("ai_color");
                }
                moneyValidate = false;
            } else {
                moneyValidate = true;
            }
        } else {
            $(".dit_yq").html("输入有误").addClass("ai_color");
            moneyValidate = false;
        }

        addParams = "&money=" + int_merry + "&code=" + code + "&couponIsFixed=" + couponIsFixed;
        if($(".icon_select").length>0){
            addParams = addParams + "&discount_id=" + discount_id + "&discount_group_id=" + discount_group_id
            + "&discount_type=" + discount_type
            + "&discount_sign=" + discount_sign
            + "&discount_bidAmount=" + discountbidAmount
            + "&discount_goodprice=" + $(".val_discount_goodprice").html();
        }else{
            addParams = addParams + "&discount_id=" + $(".val_discount_id").html() + "&discount_group_id=" + $(".val_discount_group_id").html()
            + "&discount_type=" + $(".val_discount_type").html()
            + "&discount_sign=" + $(".val_discount_sign").html()
            + "&discount_bidAmount=" + $(".val_discount_bidAmount").html()
            + "&discount_goodprice=" + $(".val_discount_goodprice").html();
        }

        $('.ditf_list a.to_coupon').attr('href', 'invest://api?type=searchCoupon&id=' + investmentID + addParams);
        $('a.to_recharge').attr('href', 'invest://api?type=recharge&id=' + investmentID + addParams);
        $('a.to_contractList').attr('href', 'invest://api?type=contractList&id=' + investmentID + addParams);
        $('a.to_youhuiquanList').attr('href', 'invest://api?type=selectCoupon&deal_id=' + investmentID + addParams + "&discount_type=0");
        if (moneyValidate) {
            // 收益
            if ($(".istongzhi").html() != "1") {
                var _earning = showDou((accMul(int_merry, per) / 100).toFixed(2));
                if(window['deal_type'] == 0){
                    $(".dit_yq").html("借款利息" + _earning + "元").removeClass("ai_color");
                }else{
                    $(".dit_yq").html("预期收益" + _earning + "元").removeClass("ai_color");
                }
            } else {
                $(".dit_yq").html("");
            }
            if(int_merry > totalMoney){
                investChooseValidate = false;
            }
            //投资按钮
            // $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("disabled" , "disabled");
            // if(val_discount_id || int_merry > totalMoney || (val_discount_id && int_merry <= totalMoney)){
            //     if(int_merry > totalMoney){
            //         investChooseValidate = false;
            //     }
            //     if(window["_needForceAssess_"] == 0 || window["_is_check_risk_"] == 0){//0代表不需要强制测评或者不需要校验个人评级
            //         $(".sub_btn").attr("href", "invest://api?type=invest&id=" + investmentID + addParams);
            //     }
            // }else{
            //     $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
            // }
            $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
        } else {
            //投资按钮
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("disabled" , "disabled");
        }
        //拼接开户url
        if(window['isBankcard'] == 1){//已绑卡
            var _is_open_p2p_param = '{"srv":"register" , "return_url":"storemanager://api?type=closecgpages"}';//开户参数
        }else{//未绑卡
            var _is_open_p2p_param = '{"srv":"registerStandard" , "return_url":"storemanager://api?type=closecgpages"}';//开户参数
        }
        var _openp2pUlr = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_p2p_param);
        //开户url
        $(".JS_open_p2p_btn").attr({"href":'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url='+encodeURIComponent(_openp2pUlr)});

        //拼接授权url
        var _is_freePayment_param = '{"return_url":"storemanager://api?type=closecgpages","srv":"freePaymentQuickBid"}'
        var _freePayment = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_freePayment_param);
        $(".JS_is_freepayment_btn").attr("href",'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url=' + encodeURIComponent(_freePayment));
    };
    
    // 判断选择加息券，调接口计算实时收益
    function getPrice(int_merry,val_discount_id){
        $.ajax({
            type: "post",
            dataType: "json",
            async: false,
            url: "/discount/AjaxExpectedEarningInfo?token=" + $('.token').html() + '&id=' + $('.investmentID').html() + '&money=' + int_merry + "&discount_id=" + val_discount_id,
            success: function(json){
                if(!!json.data){
                    $(".can_use").hide();
                    $(".JS-couponnum_label").html("已选择");
                    $(".JS-selected_discount").show();
                    $(".coupon_detail .con").html(json.data.discountDetail);
                    $(".val_discount_goodprice").html(json.data.discountGoodPrice);
                    discount_goodprice = $(".val_discount_goodprice").html();
                    updateState();
                }
            }
        });
    }
    var computeIncome = function() {
        discountListTotalPage = 0;
        var int_merry = $(".ui_input .btn_key").html();
        if(val_discount_id){
            getPrice(int_merry,val_discount_id);
        }
        //投资券选择优化     start
        //投资券列表弹框 展示
        var _level = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1);
        int_merry = parseFloat(int_merry);
        val_discount_id = $('.val_discount_id').html();
        var o2oDiscountSwitch = $("#o2oDiscountSwitch").val();
        if((int_merry != 0 && int_merry >= _level && int_merry <= totalMoney && o2oDiscountSwitch == 1) || int_merry > totalMoney ){
            if(!val_discount_id){
                investChooseValidate = true;
                $(".load_box").empty();
                $(".tab_con").scrollTop(0);
                loadmoreC = new WXP2P.UI.P2PLoadMore($(".load_box")[0], $('.tb0-more')[0], '/discount/AjaxConfirmPickList?token='+usertoken+'&deal_id='+ discount_id +'&site_id='+siteId+'&money='+int_merry, 1, 'post', 10);
                loadmoreC.createItem = function(item){
                    var href = 'invest://api?type=invest&id='+ discount_id+'&money='+int_merry +'&code='+code + "&couponIsFixed=" + couponIsFixed;
                    href += '&discount_id=' + item.id + '&discount_group_id=' + item.discountGroupId + '&discount_sign=' + item.sign
                        + '&discount_bidAmount=' + item.bidAmount + '&discount_type=' + item.type;

                    var icon_select = "";
                    var dl = document.createElement("div");
                    var html = "";
                    html +='<div class="con"> ';
                    html += '<a class="j-selectA" data-id="'+ item.id +'" href="javascript:;" data-href="'+ href + '" data-profit="'+ item.goodsPrice +'" data-goodstype="'+ item.goodsType + '" data-type="' + item.type + '">';
                    html += '<dl>';
                    if(item.recommend ==1){//不等于一的时候表示可赠送
                        if(item.type == 1){
                            html += '    <div class="icon_kzs_blue">';
                        }else if(item.type == 2){
                            html += '    <div class="icon_kzs_yellow">';
                        }else if(item.type == 3){
                            html += '<div class="icon_kzs_gold">'
                        }
                        html += '</div>'
                    }
                    if (discount_id == item.id) {
                        icon_select = " icon_select" ;
                    }
                    html += '<div class="j-icon-select'+ icon_select +'"></div>';
                    html += '<dt>';
                    if(item.type == 1){
                        html += '        <h2><span class="f28">'+ item.goodsPrice+'</span>元</h2>返现券';
                    }else if(item.type == 2){
                        html += '        <h2>+<span class="f28">'+ item.goodsPrice+'</span>%</h2>加息券';
                    }else if(item.type == 3){
                        html += '        <h2><span class="f28">'+ item.goodsPrice+'</span>克</h2>黄金券';
                    }
                    html +='</dt>';
                    html +='<dd>';
                    html +='<p>'+item.name+'</p>';
                    if(item.type == 1){
                        html +='<p class="color_blue">';
                    }else if(item.type == 2){
                        html +='<p class="color_yellow">';
                    }else if(item.type == 3){
                        html +='<p class="color_gold">';
                    }

                    if(item.bidDayLimit != "" && item.bidDayLimit > 0) {
                        if(window['deal_type'] == 0){
                            html += '出借满'+item.bidAmount+'元，期限满'+item.bidDayLimit+'天可用';
                        }else{
                            html += '金额满'+item.bidAmount+'元，期限满'+item.bidDayLimit+'天可用';
                        }
                    }else{
                        if(window['deal_type'] == 0){
                            html += '出借满'+item.bidAmount+'元可用';
                        }else{
                            html += '金额满'+item.bidAmount+'元可用';
                        }
                    }
                    html +='</p><p>'+WXP2P.UTIL.dataFormat(item.useStartTime,"", 1)+'至'+WXP2P.UTIL.dataFormat(item.useEndTime,"", 1)+'有效</p>';
                    html +='</dd>';
                    html +='</dl>';
                    html +='</a>';
                    dl.innerHTML = html;
                    if(item.type == 1){
                        dl.className="card";
                    }else if(item.type == 2){
                        dl.className="card rate_increases";
                    }else if(item.type == 3){
                        dl.className="card rate_gold";
                    }
                    return dl;
                };
                loadmoreC.preProcessData = function(ajaxData) {
                    discountListNum = ajaxData['data']['list'].length;
                    discountListTotalPage = ajaxData['data']['totalPage'];

                    // if(pThis.page == 1){
                    //     pThis.container.innerHTML="";
                    // }
                    if(!discountListNum){
                        // setTimeout(function(){
                        //     $(".sub_btn").removeClass("sub_gay").addClass("sub_red");
                        // })
                        if(window["_needForceAssess_"] == 0 || window["_is_check_risk_"] == 0){//0代表不需要强制测评或者不需要校验个人评级
                            $(".sub_btn").attr("href", "invest://api?type=invest&id=" + investmentID + addParams);
                        }
                    }
                    var listItems = ajaxData['data'] ? ajaxData['data']['list'] : [];
                    return {"data": listItems, "errno": ajaxData['errno'], "error": ajaxData["error"]}
                };
                loadmoreC.processData = function(ajaxData) {
                    var pThis = this;
                    ajaxData = this.preProcessData(ajaxData);
                    if (!ajaxData.data) {
                        //NOTE: 添加处理错误
                        return;
                    }
                    pThis.page++;
                    var listDataItem = ajaxData.data;
                    if (listDataItem.length > 0) {
                        for(var index = 0; index < listDataItem.length; index++) {
                            pThis.container.appendChild(pThis.createItem(listDataItem[index]));
                        }
                    }
                    if (this.page > discountListTotalPage) {
                        pThis.loadmorepanel.innerHTML = "没有更多了";
                    }else{
                        pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
                        $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
                          pThis.loadNextPage();
                        });
                    }
                };
                loadmoreC.loadNextPage();
                var tabHash = {
                    'tab0': loadmoreC
                };
                var overscroll = function(el) {
                  el.addEventListener('touchstart', function() {
                    var top = el.scrollTop
                      , totalScroll = el.scrollHeight
                      , currentScroll = top + el.offsetHeight
                    if(top === 0) {
                      el.scrollTop = 1
                    } else if(currentScroll === totalScroll) {
                      el.scrollTop = top - 1
                    }
                  })
                  el.addEventListener('touchmove', function(evt) {
                    if(el.offsetHeight < el.scrollHeight){
                        evt._isScroller = true;
                    }
                    document.body.addEventListener('touchmove', cancleDefault);
                  });
                }
                overscroll(document.querySelector('.tab_con'));
                var investBg = document.querySelector('.investBg');
                var investChoose = document.querySelector('.investChoose');
                var investList  = document.querySelector('.investList');
                investBg.addEventListener('touchmove', cancleDefault);
                investChoose.addEventListener('touchmove', cancleDefault);
                investList.addEventListener('touchmove', cancleDefault);
            }

        }
    };
    $(".tab_con").on("tap" , ".j-selectA" , function(){
        var int_merry = $(".ui_input .btn_key").html();
        var $t = $(this),
        href = $t.data("href"),
        val_discount_id = $t.data("id");
        $(".j-icon-select").removeClass('icon_select');
        $t.find(".j-icon-select").addClass('icon_select');
        href = href +"&discount_goodprice="+discount_goodprice+"&fromOptimize=1";
        // $(".chooseYes").addClass("chooseConfirm").attr("href", href);

        $(".chooseYes").addClass("chooseConfirm").unbind("click").bind("click",function(event) {
            investChooseValidate = false;
            $(".investBg").addClass('disnone');
            $(".investChoose").addClass('disnone');
            $(".investList").removeClass('show');
            $(".val_discount_id").html(val_discount_id);
            $(".val_discount_goodprice").html(discount_goodprice);
            $(".sub_btn").attr("href" , href);
            getPrice(int_merry,val_discount_id);
        });

    });
    //去抖函数，相邻操作500ms内禁止发请求,防止频繁发送请求
    var getPrice_debounce=function(idle,action){
        var last=null;
        return function(){
            var ctx = this, args = arguments;
            clearTimeout(last);
            last = setTimeout(function(){
                action.apply(ctx, args);
            }, idle);
        }
    }(500,function (int_merry,val_discount_id) {
        getPrice(int_merry,val_discount_id);
    });
    if(window['deal_type'] == 0){
      var qitou_text = '起';
    }else{
      var qitou_text = '起投';
    }
    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1) + qitou_text,
        delayHiden: function() {
            // computeIncome();
            updateState();
            document.body.removeEventListener('touchmove', cancleDefault);
            var ipt_val = $(".ui_input .btn_key").html();
            if(ipt_val == ''){
                $('.input_deal').removeClass('borer_yellow');
            }
        },
        focusFn: function() {
            discountListTotalPage = 0;
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("disabled", 'disabled');
            $('.input_deal').addClass('borer_yellow');
        },
        changeFn: function() {
            iptChangeFn();
            var int_merry = $(".ui_input .btn_key").html()*1;
            val_discount_id = $(".val_discount_id").html();
            if(val_discount_id){
                // getPrice(int_merry,val_discount_id);
                getPrice_debounce(int_merry,val_discount_id);//由原来的直接调用，改为调用去抖函数
            }
        }
    });
    function iptChangeFn() {
        var ipt_val = $(".ui_input .btn_key").html();
        $(".show_daxie").empty().append($.getformatMoney(ipt_val, "show_money_ul", "active"));
    }
    // 初始化金额
    var val_money = $(".val_money").html();
    if (val_money > 0) {
        $(" .ui_input .btn_key").html(val_money);
        $(".inp_text").addClass("disnone");
    }
    iptChangeFn();
    updateState();
    function start(){
      //全投
        var wait = 2;
        $("#quantou_all").bind("click", function() {
            time();
            var yuer = $(".ketou_money").html().trim();
            yuer = yuer.replace(/,/g,'');
            console.log(yuer);
            var dealLeft = $(".deal_money").html().trim();
            $(".ui_input .btn_key").html(Math.min(dealLeft, yuer));
            $(".inp_text").addClass("disnone");
            iptChangeFn();
            computeIncome();
            updateState();
        });

        function time() {
            if (wait == 0) {
                $("#quantou_all")[0].removeAttribute("disabled");
                wait = 2;
            } else {
                $("#quantou_all")[0].setAttribute("disabled", true);
                wait--;
                setTimeout(function() {
                    time()
                }, 1000)
            }
        }

    }
    start();


    function getDiscountNum() {
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxAvaliableCount?token=" + $('.token').html() + '&deal_id=' + $('.investmentID').html(),
            success: function(json){
                investChooseValidate = true;
                $(".JS-couponnum_label").html("未选择");
                //$(".JS-couponnum_label").show();
                $(".can_use").show();
                $(".JS_coupon_num").text(json.data);
                if (json.data < 1) {
                    $(".JS_coupon_num").removeClass('num_canuse');
                    $(".can_use").removeClass('color_red');
                }
                if(json.data > 0){
                    // $(".JS-couponnum_label , .JS_coupon_num").css({
                    //     color : "#ee4634"
                    // });
                    var _TOUZIQUAN_GUIDE_COOKIE_NAME_ = '_app_touziquanguide_';
                    function tryShowTouziQuanGuide() {
                        var guidecokkiestr = WXP2P.APP.getCookie(_TOUZIQUAN_GUIDE_COOKIE_NAME_);
                        var guideList = guidecokkiestr != null && guidecokkiestr != "" ? guidecokkiestr.split(",") : [];
                        for (var i = guideList.length - 1; i>= 0; i--) {
                            if (guideList[i] == window['_userid_']) return;
                        }
                        $('.JS-touziyindao').show();
                        $('.ui_mask_white').click(function() {
                            $('.JS-touziyindao').hide();
                        });
                        guideList.push(window['_userid_']);
                        WXP2P.APP.setCookie(_TOUZIQUAN_GUIDE_COOKIE_NAME_, guideList.join(","), 365);
                    }
                    tryShowTouziQuanGuide();
                }
            }
        })
    }
    if(val_discount_id){
        var int_merry = $(".ui_input .btn_key").html() * 1
        getPrice(int_merry,val_discount_id);
    }else{
        getDiscountNum();
    }
    //删除优惠券
    $('.JS-selected_discount .JS_close').bind('click', function() {
        $('.JS-selected_discount').hide();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        $(".card").remove();
        $(".j-icon-select").removeClass('icon_select');
        val_discount_id='';
        deal_min = $('.val_mini').html().replace(/\,|元/g, '') * 1;
        computeIncome();
        updateState();
        getDiscountNum();
    });

    function cleardiscount(){
        $(".investBg").addClass('disnone');
        $(".investChoose").addClass('disnone');
        $(".investList").removeClass('show');
        $('.JS-selected_discount').hide();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        val_discount_id='';
        deal_min = $('.val_mini').html().replace(/\,|元/g, '') * 1;
        // oldMoney = newMoney;
        computeIncome();
        updateState();
        $(".chooseYes").removeClass("chooseConfirm").attr("href", "javascript:void(0);");
        document.body.removeEventListener('touchmove', cancleDefault);
        $(".j-icon-select").removeClass('icon_select');
        $(".tab_con").scrollTop(0);
    }

    $("#closeInvest,.investBg").bind('click', function(event) {
        investChooseValidate = true;
        cleardiscount();
    });

    //存管逻辑
    function supervision(){
        var showDiscount = $(".sub_btn").attr("data-showDiscount");
        computeIncome();
        $.ajax({
            url: '/deal/pre_bid?token='+ $('.token').html() + '&id=' + $(".investmentID").html() + '&money=' + $(".ui_input .btn_key").html() + '&coupon=' + $('.val_code').html(),
            type: 'post',
            dataType: 'json',
            success: function(json){
                $(".JS_bid_btn").remove();
                //开户url
                if(json.data.status == 3 || json.data.status == 6){//验密划转，需要去银行验密页面划转 
                    $(".JS_is_transfer").show();
                    $(".JS_trans_money").html(json.data.data.transfer+"元");
                    $(".remain_m").html(json.data.data.remain);
                    //拼接划转url
                    if(json.data.status == 3){//网贷-网信 专享标
                        var _is_transfer_param = '{"srv":"transfer" , "amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                    }else if(json.data.status == 6){ //网信-网贷 p2p
                        var _is_transfer_param = '{"srv":"transferWx","amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                    }
                    var _en_is_transfer_param = encodeURIComponent(_is_transfer_param);
                    var _istransferUlr = location.origin + "/payment/Transit?params=" + _en_is_transfer_param;
                    $(".JS_transfer_btn").attr({"href":'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url='+encodeURIComponent(_istransferUlr)});
                }else if(json.data.status == 7){
                    $(".JS_is_open_p2p").show();
                }else if(json.data.status == 2){
                    var _is_bid_param = '{"srv":"bid" , "money":"'+$(".ui_input .btn_key").html()+'" , "dealId":"'+ $(".investmentID").html() +'" , "couponId":"'+ $('.val_code').html() +'" , "couponIsFixed":"'+ $('.is_fixed').html() +'" , "discountId":"'+ $(".val_discount_id").html() +'" , "discount_group_id":"'+ $(".val_discount_group_id").html() +'" ,"discountType":"'+$(".val_discount_type").html()+'" , "discountSign":"'+ $(".val_discount_sign").html() +'" , "discountGoodsPrice":"'+ $(".val_discount_goodprice").html() +'","return_url":"storemanager://api?type=cginvest"}';

                    // var discount_goodprice = Request["discount_goodprice"];
                    if($(".icon_select").length>0){
                        var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
                        function GetRequest() {
                          var url = hrefD; //获取url中"?"符后的字串
                           var theRequest = new Object();
                           if (url.indexOf("?") != -1) {
                              var str = url.substr(1);
                              strs = str.split("&");
                              for(var i = 0; i < strs.length; i ++) {
                                 theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
                              }
                           }
                           return theRequest;
                        }
                        var Request = new Object();
                        Request = GetRequest();
                        var discount_id = Request["discount_id"];
                        var discount_group_id = Request["discount_group_id"];
                        var discount_sign = Request["discount_sign"];
                        var discount_bidAmount = Request["discount_bidAmount"];
                        var discount_type = Request["discount_type"];
                        _is_bid_param = '{"srv":"bid" , "money":"'+$(".ui_input .btn_key").html()+'" , "dealId":"'+ $(".investmentID").html() +'" , "couponId":"'+ $('.val_code').html() +'" , "couponIsFixed":"'+ $('.is_fixed').html() +'" , "discountId":"'+ discount_id +'" , "discountGroupId":"'+ discount_group_id +'" ,"discountType":"'+ discount_type +'" , "discountSign":"'+ discount_sign +'" , "discountGoodsPrice":"'+ discount_goodprice +'","return_url":"storemanager://api?type=cginvest"}';
                    }
                    //开户参数
                    var _en_bid_param = encodeURIComponent(_is_bid_param);
                    var _bidUlr = location.origin + "/payment/Transit?params=" + _en_bid_param;
                    var p2pbid_href = 'storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(_bidUlr);
                    

                    if(window["_BIDTYPE_"] == "7") {
                        $('.JS-gongyiconfirm.ui_mask').show();
                        $('#JS-confirmdonate').show();
                        $('#JS-confirmdonate .J_ok').attr("href", p2pbid_href);
                        $('#JS-confirmdonate .J_no').click(function() {
                            $('#JS-confirmdonate').hide();
                            $('.JS-gongyiconfirm.ui_mask').hide();
                            $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                        });
                    }else if(!val_discount_id && investChooseValidate && discountListTotalPage>0 && showDiscount != 1 && window['siteId'] != 100){
                        $(".investBg").removeClass('disnone');
                        $(".investChoose").removeClass('disnone');
                        $(".investList").addClass('show');
                        $(".chooseNo").click(function(event) {
                            investChooseValidate = false;
                            $(".sub_btn").attr("data-showDiscount","1");
                            cleardiscount();
                        });
                        return false;
                    }else{
                        console.log(p2pbid_href,"p2pbid_href");
                        $("body").append('<a href="'+p2pbid_href+'" class="JS_bid_btn"></a>');
                        $(".JS_bid_btn").click();
                    }

                }else if(json.data.status == 4 || json.data.status == 5){
                    $(".JS_is_transfer_tips").show();
                    $(".JS_is_transfer_tips .JS_trans_money").html(json.data.data.transfer+"元");
                    $(".JS_is_transfer_tips .remain_m").html(json.data.data.remain);
                    var transfer_type = "";
                    if(json.data.status == 4){
                        transfer_type = 1;
                        $(".JS_close_transfer_tips").wrap('<a href="javascript:void(0);" class="MD_trans_to_p2p_cancel"></a>');
                        $(".JS_select_point").wrap('<a href="javascript:void(0);" class="MD_trans_to_p2p_ok"></a>');
                    }else{
                        transfer_type = 2;
                        $(".JS_close_transfer_tips").wrap('<a href="javascript:void(0);" class="MD_trans_to_super_cancel"></a>');
                        $(".JS_select_point").wrap('<a href="javascript:void(0);" class="MD_trans_to_super_ok"></a>');
                    }
                    $(".JS_is_transfer_tips .JS_transfer_btn").unbind("click");
                    $(".JS_is_transfer_tips .JS_transfer_btn").bind("click" ,function(){
                        $.ajax({
                            url:"/payment/Transfer?money=" + json.data.data.transfer + "&type=" + transfer_type + "&dontTip=" + $(".no_tip_checkbox").val() +"&token=" + $('.token').html(),
                            type: 'post',
                            dataType: 'json',
                            beforeSend:function(){
                                $(".JS_is_transfer_tips .JS_transfer_btn").attr("disabled","disabled");
                            },
                            success:function(subjosn){
                                if(subjosn.errno == 0){
                                    WXP2P.UI.showErrorTip("余额划转成功");
                                    var val_svBalance = $(".val_svBalance").html();
                                    var val_wxMoney = $(".val_wxMoney").html();
                                    val_svBalance = val_svBalance.replace(/,/g,'');
                                    val_wxMoney = val_wxMoney.replace(/,/g,'');
                                    if(json.data.status == 4){
                                         val_svBalance = (val_svBalance*1 + json.data.data.transfer*1);
                                        $(".val_svBalance").html(showDou((val_svBalance).toFixed(2)));
                                        $(".val_wxMoney").html(showDou(json.data.data.remain));
                                    }else{
                                        val_wxMoney = (val_wxMoney*1 + json.data.data.transfer*1);
                                        $(".val_wxMoney").html(showDou((val_wxMoney).toFixed(2)));
                                        $(".val_svBalance").html(showDou(json.data.data.remain));
                                    }
                                }else{
                                    WXP2P.UI.showErrorTip(subjosn.error);
                                }
                                $(".JS_is_transfer_tips").hide();
                                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                                $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                            },
                            error:function() {
                                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                                $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                            }
                        })
                    })
                }else if(json.data.status == 1){
                    var href = "invest://api?type=invest&id=" + investmentID + addParams
                    var icon_select = $(".icon_select").length;
                    if($(".icon_select").length>0){
                        href += "&discount_goodprice="+discount_goodprice;
                    }
                    
                    if(window["_BIDTYPE_"] == "7") {
                        $('.JS-gongyiconfirm.ui_mask').show();
                        $('#JS-confirmdonate').show();
                        $('#JS-confirmdonate .J_ok').attr("href", href);
                        $('#JS-confirmdonate .J_no').click(function() {
                            $('#JS-confirmdonate').hide();
                            $('.JS-gongyiconfirm.ui_mask').hide();
                            $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                        });
                    }else if(!val_discount_id && investChooseValidate && discountListTotalPage>0 && showDiscount != 1 && window['siteId'] != 100){
                        $(".investBg").removeClass('disnone');
                        $(".investChoose").removeClass('disnone');
                        $(".investList").addClass('show');
                        $(".chooseNo").click(function(event) {
                            investChooseValidate = false;
                            $(".sub_btn").attr("data-showDiscount","1");
                            cleardiscount();
                        });
                        return false;
                    }else{
                        $("body").append('<a href="'+href+'" class="JS_bid_btn"></a>');
                        $(".JS_bid_btn").click();
                    }
                }else{
                    WXP2P.UI.showErrorTip(json.data.data);
                    $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                }
            },
            error:function() {
                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
            }
        })
    }
    //不在提示划转弹窗
    $(".JS_is_transfer_tips .tips_icon").removeClass('JS_active');
    $(".no_tip_checkbox").val(0);
    $(".JS_is_transfer_tips").on("click",".tips_icon",function(event) {
        $(".tips_icon").toggleClass('JS_active');
        if($(".no_tip_checkbox").is(':checked')){
            $(".no_tip_checkbox").val(1);
        }else{
            $(".no_tip_checkbox").val(0);
        }
    });

    // 公益标
    $(".sub_btn").bind("click", function(event) {
        var $t = $(this);
        if (!moneyValidate) return true;
        if(window['allowBid'] != 1){//非投资户不可投资
            if(window['deal_type'] == 0){
              WXP2P.UI.showErrorTip("非投资账户不允许出借");
            }else{
              WXP2P.UI.showErrorTip("非投资账户不允许投资");
            }
            return false;
        }
        $t.attr("disabled","disabled");
        if (window['_needForceAssess_']==1) { //强制风险测评弹窗
            $(".is_eval").show();
            $("#JS-is-evaluate").show();
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $(".eval_btn").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $(".no_eval,.eval_btn").click(function(){
                $(".is_eval").hide();
                $("#JS-is-evaluate").hide();
                $t.removeAttr("disabled");
            });
            return false;
        } else if(window['siteId'] == 100 && ($(".ui_input .btn_key").html()*1 > $(".JS_remian_money").html().replace(/,/g,'')*1)){
            //输入金额大于网贷账户余额加红包
            WXP2P.UI.showErrorTip("余额不足，请充值");
            return false;
        } else if(window['_is_check_risk_']==1){
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $("#ui_conf_risk").css('display','block');
            $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $("#JS-cancel,#JS-know,#JS-confirm").click(function(){
              $("#ui_conf_risk").hide();
              //返回上一级页面firstp2p://api?type=closeall
               $("#JS_cancel_container,#JS_know_container").attr("href","firstp2p://api?type=closeall")
            });
            $t.removeAttr("disabled");
            return false;
        } else{
            $t.removeAttr("disabled");
            if($('#dealType').val()==0){//判断是不是p2p
                if (!singleLimit()){
                    return false;
                }
            }
            supervision();
            return false;
        }
        event.preventDefault();
        return true;
    });

    $(".point_open").click(function() {
        $(".account_money").toggle();
        $(this).toggleClass('down_img');
    });


    //关闭划转
    $(".JS_close_transfer").click(function(event) {
        $(".JS_is_transfer").hide();
    });

    $(".JS_close_transfer_tips").click(function(){
        $(".JS_is_transfer_tips").hide();
    });
    $(".JS_close_open_p2p").click(function(){
        $(".JS_is_open_p2p").hide();
    })


    function switchToNum(str) {
        if (!isNaN(str)){
            str=Number(str);
        }else{
            str=0;
        }
        return str;
    }
    //单笔限额的判断的函数
    function singleLimit() {
        var returnVal=true;
        var canTest=false;//是否可以重新测试
        var bidmoney = $(".ui_input .btn_key").html();
        bidmoney=switchToNum($.trim(bidmoney));
        var dataJson=function () {
            var data={};
            var moneyVal=$('#limitMoney').val();
            var levelName=$('#levelName').val();
            var num=$('#remainingAssessNum').val();
            if (moneyVal === "" | levelName === "") {
                data=null;
            }else{
                data.limitMoney=switchToNum(moneyVal);
                data.levelName=levelName;
                if (num !== "") {
                    data.remainingAssessNum=num;
                }
            }
            return data;
        }();
        var promptStr ='';//弹层上面的html布局

        if (dataJson != null) {
            if (dataJson.limitMoney < bidmoney) {
                returnVal=false;
                dataJson.levelName=function () {
                    var str=dataJson.levelName;
                    if (str.charAt(str.length-1)=="型"){
                        str=str.slice(0,-1);
                    }
                    return str;
                }();
                if(window['deal_type'] == 0){
                  promptStr='您的风险承受能力为 '+dataJson.levelName+' 型,<br/>单笔最高出借额为 '+dataJson.limitMoney/10000+' 万元';
                }else{
                  promptStr='您的风险承受能力为 '+dataJson.levelName+' 型,<br/>单笔最高投资额度为 '+dataJson.limitMoney/10000+' 万元';
                }
                if($.type(dataJson.remainingAssessNum)!='undefined'){
                    promptStr+='<br/><span class="sy_num color_gray f13">本年度剩余评估'+dataJson.remainingAssessNum+'次</span>';
                    if (dataJson.remainingAssessNum>0){
                        canTest=true;
                    }
                }else{
                    canTest=true;
                }
                $('#ui_confirm').find('.confirm_donate_text').html(promptStr);
                var btns=$('#ui_confirm .confirm_donate_but a');
                btns.unbind('click').hide();
                btns.on('click',function () {
                    $('#ui_confirm').hide();
                });
                btns.eq(1).on('click',function () {
                    var l_origin = location.origin;
                    var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
                    $(this).attr('href','firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
                });
                if(canTest){
                    btns.eq(0).add(btns.eq(1)).show();
                }else{
                    btns.eq(2).show();
                }
                $('#ui_confirm').css('display','block');
            }
        }
        return returnVal;
    }




    //阻止弹窗滚动
    $(".cunguan_bg").bind("touchmove",function(event){
        event.preventDefault();
    });
    $(".alert_evaluate").on('touchstart',function(){
        $(".alert_evaluate").on('touchmove',function(event) {
        event.preventDefault();
        }, false);
    })
    $(".alert_evaluate").on('touchend',function(){
        $(".alert_evaluate").unbind('touchmove');
    });
});



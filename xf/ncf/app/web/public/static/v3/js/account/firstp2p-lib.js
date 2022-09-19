 var queue = function(list, callback, exec) {
    if (!list.length) {
        if (typeof callback == 'function') {
           callback();
        } else {
            console.log('queue is finish');
        }
        return false;
    }
    var val = list.shift();
    exec(val, function(){
    	queue(list, callback, exec);
    })
}


/*
定义firstp2p全局对象
*/
var Firstp2p = {
	/**
	 * js, css 异步加载
	 * @method preLoad
	 * @param "url" 地址  cb function 
	 * 加入防止重复加载
	 */
	preLoad : function(url, cb) {
		var _arr_indexof = function(arr, item) {
		for (var i = 0; i < arr.length; i++) {
			if (arr[i] === item) {
				return i
			}
		}
		return -1
		};


		//轮询
		var poll = function(node, callback) {
			var isLoaded

			// for WebKit < 536
			if (isOldWebKit) {
			  if (node['sheet']) {
			    isLoaded = true
			  }
			}
			// for Firefox < 9.0
			else if (node['sheet']) {
			  try {
			    if (node['sheet'].cssRules) {
			      isLoaded = true
			    }
			  } catch (ex) {
			    // The value of `ex.name` is changed from
			    // 'NS_ERROR_DOM_SECURITY_ERR' to 'SecurityError' since Firefox 13.0
			    // But Firefox is less than 9.0 in here, So it is ok to just rely on
			    // 'NS_ERROR_DOM_SECURITY_ERR'
			    if (ex.name === 'NS_ERROR_DOM_SECURITY_ERR') {
			      isLoaded = true
			    }
			  }
			}

			setTimeout(function() {
			  if (isLoaded) {
			    // Place callback in here due to giving time for style rendering.
			    callback()
			  } else {
			    poll(node, callback)
			  }
			}, 1)
		}

	    var assetOnload = function(node, callback) {
	        if (node.nodeName === 'SCRIPT') {
	            scriptOnload(node, callback)
	        } else {
	            styleOnload(node, callback)
	        }
	    }

	    var scriptOnload = function(node, callback) {
	        var config = {"debug": false}
	        node.onload = node.onerror = node.onreadystatechange = function() {
	            if (READY_STATE_RE.test(node.readyState)) {

	            // Ensure only run once and handle memory leak in IE
	            node.onload = node.onerror = node.onreadystatechange = null

	            // Remove the script to reduce memory leak
	            if (node.parentNode && !config.debug) {
	                //head.removeChild(node)
	            }
	            // Dereference the node
	            node = undefined
	            callback()
	            }
	        }
	    }
	    var styleOnload = function(node, callback) {
	        // for Old WebKit and Old Firefox
	        if (isOldWebKit || isOldFirefox) {
	          util.log('Start poll to fetch css')
	          setTimeout(function() {
	            poll(node, callback)
	          }, 1) // Begin after node insertion
	        }
	        else {
	          node.onload = node.onerror = function() {
	            node.onload = node.onerror = null
	            node = undefined
	            callback()
	          }
	        }
	    }

	    //防止重复加载
		if (window.firstp2pLoadHash && _arr_indexof(window.firstp2pLoadHash, url) != -1) {
			cb();
			//console.log("alreday load!");
			return false;
		}

		window.firstp2pLoadHash = window.firstp2pLoadHash || [];
		window.firstp2pLoadHash.push(url);

	    var UA = navigator.userAgent

	    // `onload` event is supported in WebKit since 535.23
	    // Ref:
	    //  - https://bugs.webkit.org/show_activity.cgi?id=38995
	    var isOldWebKit = Number(UA.replace(/.*AppleWebKit\/(\d+)\..*/, '$1')) < 536

	    // `onload/onerror` event is supported since Firefox 9.0
	    // Ref:
	    //  - https://bugzilla.mozilla.org/show_bug.cgi?id=185236
	    //  - https://developer.mozilla.org/en/HTML/Element/link#Stylesheet_load_events
	    var isOldFirefox = UA.indexOf('Firefox') > 0 &&
	      !('onload' in document.createElement('link'))
	    var IS_CSS_RE = /\.css(?:\?|$)/i
	    var READY_STATE_RE = /loaded|complete|undefined/;
	    var doc = document
	    var head = doc.head ||
	        doc.getElementsByTagName('head')[0] ||
	        doc.documentElement
	    var isCSS = IS_CSS_RE.test(url);
	    var node = document.createElement(isCSS ? 'link' : 'script')
	    if (isCSS) {
	        node.rel = 'stylesheet'
	        node.href = url
	    } else {
	        node.async = 'async'
	        node.src = url
	    }
	    head.appendChild(node)
	    cb = (typeof cb == "function") ? cb : function(){};
	    assetOnload(node, cb);
	},
	queue: queue
};

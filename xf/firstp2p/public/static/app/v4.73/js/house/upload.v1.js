if(typeof Firstp2p == "undefined"){
   window.Firstp2p = {};
}

//闭包
(function(){
    "use strict";
    /*
    *
    * 文件上传组件
    * 支持三种上传方式，html5、flash和iframe
    * 目的是以简单并且通用的方式解决上传问题
    * @author xuyong <xuyong@ucfgroup.com>
    * 在原有组件的基础上更换了flash部分，对局部做了修改, 脱离wx依赖
    * 修改为Class形式, html5和iframe形式使用同一种配置，flash单独使用一种配置
    * @createTime 2014-03-18
    * @modify 2014-08-18
    * @modify author snowNorth
    * @version v1.0
    */
    //程序入口
    var Upload = function (ele, opts) {
        //是否为jQuery对象
        ele = !(ele instanceof $) ? $(ele) : ele;
        if(!ele.length) {
            return;
        }

        var options = {
            "html5":{
                _switch : "html5", //上传方式选择 html5 flash normal
                static : ["./css/html5.v1.css"], //需要额外加载的静态资源 css和js
                datatype : "json", //返回值类型
                onsuccess : function(ele, data){ //上传成功回调
                   //console.log('data, ele', data, ele);
                },
                onerror : function(e){ //产生错误回调
                   //console.log('error', e);
                },
                progress : function(per, ele){ //上传进度条回调, 参数 per 代表百分比
                    var pele = ele.parent().parent().parent().find(".progress");
                    pele.css({"display": "block", "width": per + "%"});
                    if (per>=100) {
                        pele.hide(100);
                    }
                },
                upload_url : "./ww-upload.php", //上传地址
                //文件类型限制
                type : "'jpeg|jpg|png|gif'", //允许上传类型
                size : 1 * 1024 * 1024, // 1M 单个文件大小限制
                post_params: {"test": "html5"} //post参数
            },
            "flash":{
                _switch : "flash", //上传方式选择 html5 flash normal
                static : ["./css/swfupload.v1.css", "./swfupload/swfupload.v1.js"], //需要额外加载的静态资源 css和js
                flash_url : "./swfupload/swfupload.swf", //swfupload.swf地址
                upload_url : "../ww-upload.php", //上传地址,建议使用绝对地址，相对地址是相对于swfupload.swf所在目录的地址
                flash_settings : "./swfupload/settings.json",
                onsuccess : function(ele, data){ //上传成功回调
                   //console.log('data, ele', data, ele);
                },
                onerror : function(file, errorCode, message){ //产生错误回调
                   //console.log(file, errorCode, message);
                }
            },
            "normal":{
                //ie 测试
                _switch: "normal", //上传方式选择 html5 flash normal
                static : ["./css/html5.v1.css"], 
                datatype : "json",//返回值类型
                onsuccess : function(ele, data){ //上传成功回调
                   //console.log('data, ele', data, ele);
                },
                onerror : function(e){ //产生错误回调
                   //console.log(e);
                },
                post_params: {"test": "normal"}, //其他传递参数
                upload_url : "./ww-upload.php"
            },
            "auto":{
                //ie 测试
                datatype : "json",//返回值类型
                onsuccess : function(ele, data){ //上传成功回调
                   //console.log('data, ele', data, ele);
                },
                onerror : function(e){ //产生错误回调
                   //console.log(e);
                },
                post_params: {"test": "normal"}, //其他传递参数
                upload_url : "./ww-upload.php"
            }
        };
        var keys = 0;
        for ( var  key in opts) {
            keys++;
            if (keys > 1) {
                break;
            }
        }
        if (keys == 1 && opts._switch) {
            //覆盖
            opts = options[opts._switch];
        }

        var defallt = {
            //模板
            template : {
                "html5"   : '<div class="progress"></div><div class="upfile_root">'
                            +   '<div class="upfile_c">'
                            +   '<a class="upfile_word" href="#">选择文件</a>'
                            +   '<input id="Filedata" class="ui_file" type="file" name="Filedata" multiple="multiple" />'
                            +   ''
                            +   '</div>' +
                            '</div>',
                "flash" :'<div id="fsUploadProgress">' +
                        '</div>' +
                        '<div>' +
                            '<span id="spanButtonPlaceHolder"></span>' +
                            '<input id="btnCancel" type="hidden" value="取消所有上传" disabled="disabled" />' +
                        '</div>',
                "normal" : '<div class="upfile_root">'
                            +   '<div class="upfile_c">'
                            +   '<a class="upfile_word" href="#">选择文件</a>'
                            +   '<input id="Filedata" class="ui_file" type="file" name="Filedata" multiple="multiple" />'
                            +   ''
                            +   '</div>' +
                            '</div>'
            },
            //是否支持html5
            html5 : window.FormData !== undefined ? true : false,
            //附带参数
            post_params : {}, //弃用字符串形式，改用对象形式
            //上传完成回调
            /*
                params data ajax返回json, ele jquery对象
            */
            onsuccess : function(ele, data){
               //console.log('data, ele', data, ele);
            },
            onerror : function(e){
               //console.log('error', e);
            },
            //上传前回调
            before : null,
            //上传进度条回调, 参数 per 代表百分比
            progress : function(per, ele){
                var pele = ele.parent().find(".progress");
                pele.css({"display": "block", "width": per + "%"});
                if (per>=100) {
                    pele.hide(100);
                }
            },
            //上传地址最好使用绝对地址,使用相对地址时flash的上传路径会产生问题，因为flash的上传路径是相对swf文件来确定的，其他方法是相对当前html文件
            upload_url : "./ww-upload.php",
            //文件类型限制
            type : "'jpeg|jpg|png|gif'",
            //单个文件大小限制
            size : 1 * 1024 * 1024, // 1M
            //flash上传设置
            flash_settings : {
                flash_url : "../swfupload/swfupload.swf", //swfupload.swf地址
                upload_url : "./ww-upload.php",
                post_params: {}, //上传的其他参数
                file_post_name : "Filedata",
                file_size_limit : "1 MB",
                file_types : "*.jpg;*.gif", //允许上传的文件类型
                file_types_description : "All Files",
                file_upload_limit : 10,  //限定用户一次性最多上传多少个文件，在上传过程中，该数字会累加，如果设置为“0”，则表示没有限制
                file_queue_limit : 0, //上传队列数量限制，该项通常不需设置，会根据file_upload_limit自动赋
                custom_settings : {
                    progressTarget : "fsUploadProgress",
                    cancelButtonId : "btnCancel"
                },
                debug: false, //是否显示调试信息
                button_image_url: "../images/TestImageNoText_65x29.png", //按钮背景图地址
                button_width: "102", //上传按钮宽度
                button_height: "30", //上传按钮
                button_placeholder_id: "spanButtonPlaceHolder", //占位ID
                button_text: '<span class="theFont">浏览</span>', //占位文本
                button_text_style: ".theFont { font-size: 16; }", //文本样式
                button_text_left_padding: 12,
                button_text_top_padding: 3,
                onsuccess : function(ele, data){
                   //console.log('data, ele', data, ele);
                },
                onerror : function(file, errorCode, message){
                   //console.log(file, errorCode, message);
                }
            },
            //begin处理样式
            begin: function(ele){
                var word = ele.find('.upfile_c');
                var link = word.find('.upfile_word')[0];
                if (!link.getAttribute('_init')) {
                    link.setAttribute("_init", link.innerHTML);
                    link.innerHTML = '上传中..';
                }
                word.addClass('upfiling');
                ele.find('input[type=file]').css('display', 'none');
            },
            end: function(ele){
                var word = ele.find('.upfile_c');
                var link = word.find('.upfile_word')[0];
                link.innerHTML = link.getAttribute('_init');
                word.removeClass('upfiling');
                ele.find('input[type=file]').css('display', 'block');
            }

        };

        this._opts = opts || {};
        var self = this;
        this.ele = ele;
        //初始化配置
        this._opts = $.extend(defallt, opts);
        this.init();
    };

    $.extend(Upload.prototype, {
        /**
         * 初始化 如果需要则加载异步资源,由static指定
         * @method init
         */
        init: function() {
            var ele = this.ele;
            var self = this;
            switch (this._opts._switch) {
                case "html5" :
                    self._html5();
                    break;
                case "flash" :
                    self._flash();
                    break;
                case "normal" :
                    self._normal();
                    break;
                default:
                    //自动选择

                    if (this._opts.html5) {
                        //ie9对html5上传支持不足bug
                        this.detectIE(9, 9) ? self._normal() : self._html5();
                    } else if (ele.attr("flash")) {
                        self._flash();
                    } else {
                        self._normal();
                    }
                    break;
            }
        },
        /**
         * html5中转接口
         * @private
         * @method _html5
         */
        _html5: function() {
            var ele = this.ele, self = this;
            ele.html(this._opts.template["html5"]);
            var exec = function() {
                ele.find("input[type=file]").change(function(){

                    if (self._opts.begin) {
                        self._opts.begin(self.ele);
                    }
                    self._do_html5(this);
                });
            };
            if (this._opts.static) {
                this._queue(this._opts.static, function(){
                    exec();
                });
            } else {
                exec();
            }
        },
        /**
         * flash中转接口
         * @private
         * @method _flash
         */
        _flash : function() {
            var self = this, ele = this.ele;
            ele.html(self._opts.template["flash"]);
            var settings = this._opts.flash_settings;
            var exec = function(settings) {
                settings.onsuccess = self._opts.onsuccess;
                settings.onerror = self._opts.onerror;
                settings.flash_url = self._opts.flash_url;
                settings.upload_url = self._opts.upload_url;
                if (self._opts.static) {
                    self._queue(self._opts.static, function(){
                        self._do_flash(settings);
                    });
                } else {
                    self._do_flash(settings);
                }
            }
            if (typeof settings === 'string') {
                $.getJSON( settings, function(json) {
                    exec(json);
                });
            } else {
                exec(settings);
            }
        },
        /**
         * 普通上传中转接口
         * @private
         * @method _normal
         */
        _normal: function() {
            var self = this, ele = this.ele;
            var exec = function() {
                var input = ele.find("input[type=file]");
                input.removeAttr("multiple");
                input.change(function(){
                    if (self._opts.begin) {
                        self._opts.begin(self.ele);
                    }
                    self._do_normal(this);
                });
            };
            ele.html(this._opts.template["normal"]);
            if (this._opts.static) {
                this._queue(this._opts.static, function(){
                    exec();
                });
            } else {
                exec();
            }
        },
        /**
         * html5接口
         * @private
         * @method _do_html5
         * @param {options} 配置 
         */
        _do_html5 : function(el) {

            var fd = null,
                xhr = null,
                files = el.files,
                $input = $(el),
                self = this,
                options = this._opts;

            if (!this._before(options, $input)) return;
            var addParam = function(fd) {
                if (options.post_params) {
                    for ( var key in options.post_params) {
                        fd.append(key, options.post_params[key]);
                    }
                }
            }

            var bindEvent = function(xhr) {
                if (options.progress) {
                    xhr.addEventListener("progress", function (evt) {
                        if (evt.lengthComputable) {
                            options.progress(Math.round(evt.loaded * 100 / evt.total).toString(), $input);
                        } else {
                           //console.log('unable to compute');
                        }
                    }, false);
                }
                xhr.addEventListener("load", function (evt) { self._complete(evt.target.responseText, options, $input); }, false);
                xhr.addEventListener("error", function (error) { self._complete('{"status:0",error:"' + error + '"}', options, $input); }, false);
            }
            //ie9不支持files属性，兼容
            if (typeof files == 'undefined') {
                alert("不支持files属性, 请使用normal模式");
                if (this._opts.end) {
                    this._opts.end(this.ele);
                }
                return false;
            }

            for (var i = 0; i < files.length; i++) {
                if (options.size && files[i].size > options.size) {
                    alert("上传的文件太大，请压缩后重新上传");
                    if (this._opts.end) {
                        this._opts.end(this.ele);
                    }

                    continue;
                } else if (options.type && options.type.indexOf(files[i].name.match(/\.(\w+)$/)[1].toLowerCase()) === -1 ) {
                    alert("文件格式不符" + files[i].name);
                    if (this._opts.end) {
                        this._opts.end(this.ele);
                    }

                    continue;
                }
                fd = new FormData();
                xhr = new XMLHttpRequest();
                xhr.open("POST", options.upload_url);
                bindEvent(xhr);
                addParam(fd);
                fd.append(el.name, files[i]);
                xhr.send(fd);
                fd = xhr = null;
            }

        },
        /**
         * iframe上传接口
         * @private
         * @method _do_normal
         * @param {el} dom 
         */
        _do_normal : function(el) {
            var $iframe = null,
                self = this,
                $input = $(el),
                $inputC = $input.clone(),
                options = this._opts,
                time = new Date().getTime(),
                $form = $('<form id="wx-upload-form' + time + '" method="post" action="' + options.upload_url + '" enctype="multipart/form-data" target="wx-upload-iframe' + time + '"></form>');
            if (!this._before(options, $input)) return;
            if (this.detectIE(6, 6)) {
                var io = document.createElement('<iframe id="wx-upload-iframe' + time + '" name="wx-upload-iframe" />');
                io.src = 'javascript:false';
                io.style.top = '-1000px';
                io.style.left = '-1000px';
                io.style.position = 'absolute';
                $iframe = $(io);
            } else {
                $iframe = $('<iframe name="wx-upload-iframe' + time + '" style="display:none"></iframe>');
            }
            $input.parent().append($inputC);
            $form.css({ "display": "none", "position": "absolute", "top": "-1000px", "left": "-1000px" }).append($input);
            $iframe.appendTo('body');
            $form.appendTo('body');
            if (options.post_params) {
                var param = "";
                for ( var key in options.post_params) {
                    param += '<input name="' + key + '" value="' + options.post_params[key] + '" type="hidden">';
                }
                $form.append(param);
            }
            $iframe.on("load", function () {
                var content = this.contentWindow ? this.contentWindow : this.contentDocument,
                    reponse = content.document.body ? content.document.body.innerHTML : null;
                self._complete(reponse, options, $input);
                $form.remove();
                $iframe.remove();
                $inputC.data("opt", options);
                $inputC.unbind("change").change(function(){
                    self._do_normal(this);
                });
            });
            $form.submit();
        },
        //url hash 防止同一资源加载多次
        _preload_hash : [],
        /**
         * 数组indexOf方法
         * @method _arr_indexof
         * @param [arr] 队列数组  "item" 要查找的项目
         */
        _arr_indexof : function(arr, item) {
            for (var i = 0; i < arr.length; i++) {
              if (arr[i] === item) {
                return i
              }
            }
            return -1
        },
        /**
         * 异步加载队列
         * @method _queue
         * @param [list] 队列数组  callback() 回调
         */
        _queue: function(list, callback) {
            var self = this;
            if (!list.length) {
                if (typeof callback == 'function') {
                   callback();
                } else {
                   //console.log('queue is finish');
                }
                return false;
            }
            var val = list.shift();
            self.preLoad(val, {cb: function(){
                self._queue(list, callback);
            }});
        },
        /**
         * js, css 异步加载
         * @method preLoad
         * @param "url" 地址  {obj} 回调_{cb: function(){}}
         */
        preLoad : function(url, obj) {
            if (this._arr_indexof(this._preload_hash, url) != -1) {
                if (typeof obj.cb == "function") {
                    obj.cb();
                }
                return false;
            }
            this._preload_hash.push(url);
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
            assetOnload(node, obj.cb);
        },
        /**
         * ie版本检测
         * @method _normal
         * @param min 最小版本值  max 最大版本值
         */
        detectIE : function(min, max) {
            if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)){ //test for MSIE x.x;
                var ieversion=new Number(RegExp.$1) // capture x.x portion and store as a number
                min = min || 5.5; 
                max = max || 8;
                if (ieversion>=min && ieversion<=max) {
                    return true;
                }
            }
            return false;
        },
        /**
         * 上传前回调
         * @private
         * @method _before
         * @param options 配置  $input jquery对象
         */
        _before : function(options, $input) {
            var result = true;
            if (options.before) {
                result = options.before($input);
            }
            if (result && options.loading) {
               //console.log("正在上传...");
            }
            return result;
        },
        /**
         * 上传完成处理
         * @private
         * @method _complete
         * @param responseText ajax返回值 options 配置 $input jquery对象
         */
        _complete: function(responseText, options, $input) {

            if (this._opts.end) {
                this._opts.end(this.ele);
            }

            try {
                var data = responseText;
                if (options.datatype && options.datatype == "json") {
                   data = this.str2code("(" + responseText + ")");
                }
                if (options.loading) {
                   //console.log("close loading!");
                }
                if (options.onsuccess) {
                    options.onsuccess($input, data);
                }
            }
            catch (e) {
                alert("uploadComplete error " + e);
                if (options.onerror) {
                    options.onerror(e);
                }
            }
        },
        /**
         * 工具函数 执行字符串
         * @method str2code
         * @param "str"
         */
        str2code: function(str) {
            return (new Function("return " + str))();
        },
        /**
         * swf上传初始化
         * @private
         * @method _do_flash
         * @param opts flash配置
         * tag 整理swf脚本
         */
        _do_flash: function(opts) {
            var self = this;
        /*
                [Leo.C, Studio] (C)2004 - 2008
                
                $Hanization: LeoChung $
                $E-Mail: who@imll.net $
                $HomePage: http://imll.net $
                $Date: 2008/11/8 18:02 $
        */
        /*
            Queue Plug-in
            
            Features:
                *Adds a cancelQueue() method for cancelling the entire queue.
                *All queued files are uploaded when startUpload() is called.
                *If false is returned from uploadComplete then the queue upload is stopped.
                 If false is not returned (strict comparison) then the queue upload is continued.
                *Adds a QueueComplete event that is fired when all the queued files have finished uploading.
                 Set the event handler with the queue_complete_handler setting.
            */
        if (typeof(SWFUpload) === "function") {
            SWFUpload.queue = {};
            SWFUpload.prototype.initSettings = (function (oldInitSettings) {
                return function () {
                    if (typeof(oldInitSettings) === "function") {
                        oldInitSettings.call(this);
                    }
                    this.customSettings.queue_cancelled_flag = false;
                    this.customSettings.queue_upload_count = 0;
                    this.settings.user_upload_complete_handler = this.settings.upload_complete_handler;
                    this.settings.upload_complete_handler = SWFUpload.queue.uploadCompleteHandler;
                    this.settings.queue_complete_handler = this.settings.queue_complete_handler || null;
                };
            })(SWFUpload.prototype.initSettings);
            SWFUpload.prototype.startUpload= function (fileID) {
                this.customSettings.queue_cancelled_flag = false;
                this.callFlash("StartUpload", false, [fileID]);
            };
            SWFUpload.prototype.cancelQueue = function () {
                this.customSettings.queue_cancelled_flag = true;
                this.stopUpload();
                var stats = this.getStats();
                while (stats.files_queued > 0) {
                    this.cancelUpload();
                    stats = this.getStats();
                }
            };
            SWFUpload.queue.uploadCompleteHandler = function (file) {
                var user_upload_complete_handler = this.settings.user_upload_complete_handler;
                var continueUpload;
                if (file.filestatus === SWFUpload.FILE_STATUS.COMPLETE) {
                    this.customSettings.queue_upload_count++;
                }

                if (typeof(user_upload_complete_handler) === "function") {
                    continueUpload = (user_upload_complete_handler.call(this, file) === false) ? false : true;
                } else {
                    continueUpload = true;
                }

                if (continueUpload) {
                    var stats = this.getStats();
                    if (stats.files_queued > 0 && this.customSettings.queue_cancelled_flag === false) {
                        this.startUpload();
                    } else if (this.customSettings.queue_cancelled_flag === false) {
                        this.queueEvent("queue_complete_handler", [this.customSettings.queue_upload_count]);
                        this.customSettings.queue_upload_count = 0;
                    } else {
                        this.customSettings.queue_cancelled_flag = false;
                        this.customSettings.queue_upload_count = 0;
                    }
                }
            };
        }

        /*
                [Leo.C, Studio] (C)2004 - 2008
                
                $Hanization: LeoChung $
                $E-Mail: who@imll.net $
                $HomePage: http://imll.net $
                $Date: 2008/11/8 18:02 $
        */
        /* Demo Note:  This demo uses a FileProgress class that handles the UI for displaying the file name and percent complete.
        The FileProgress class is not part of SWFUpload.
        */

        /* **********************
           Event Handlers
           These are my custom event handlers to make my
           web application behave the way I went when SWFUpload
           completes different tasks.  These aren't part of the SWFUpload
           package.  They are part of my application.  Without these none
           of the actions SWFUpload makes will show up in my application.
           ********************** */
        var fileQueued = function(file) {
            try {
                var progress = new FileProgress(file, this.customSettings.progressTarget);
                progress.setStatus("正在等待...");
                progress.toggleCancel(true, this);
            } catch (ex) {
                this.debug(ex);
            }
        }
        //队列错误
        var fileQueueError = function(file, errorCode, message) {
            try {
                if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
                    alert("您正在上传的文件队列过多.\n" + (message === 0 ? "您已达到上传限制" : "您最多能选择 " + (message > 1 ? "上传 " + message + " 文件." : "一个文件.")));
                    return;
                }

                var progress = new FileProgress(file, this.customSettings.progressTarget);
                progress.setError();
                progress.toggleCancel(false);

                switch (errorCode) {
                case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
                    progress.setStatus("文件尺寸过大.");
                    this.debug("错误代码: 文件尺寸过大, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
                    progress.setStatus("无法上传零字节文件.");
                    this.debug("错误代码: 零字节文件, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
                    progress.setStatus("不支持的文件类型.");
                    this.debug("错误代码: 不支持的文件类型, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                default:
                    if (file !== null) {
                        progress.setStatus("未处理的错误");
                    }
                    this.debug("错误代码: " + errorCode + ", 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                }
            } catch (ex) {
                this.debug(ex);
            }
        }

        var fileDialogComplete = function(numFilesSelected, numFilesQueued) {
            try {
                if (numFilesSelected > 0) {
                    document.getElementById(this.customSettings.cancelButtonId).disabled = false;
                }
                
                /* I want auto start the upload and I can do that here */
                this.startUpload();
            } catch (ex)  {
                this.debug(ex);
            }
        }

        var uploadStart = function(file) {
            try {
                /* I don't want to do any file validation or anything,  I'll just update the UI and
                return true to indicate that the upload should start.
                It's important to update the UI here because in Linux no uploadProgress events are called. The best
                we can do is say we are uploading.
                 */
                var progress = new FileProgress(file, this.customSettings.progressTarget);
                progress.setStatus("正在上传...");
                progress.toggleCancel(true, this);
            }
            catch (ex) {}
            
            return true;
        }

        var uploadProgress = function(file, bytesLoaded, bytesTotal) {
            try {
                var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);

                var progress = new FileProgress(file, this.customSettings.progressTarget);
                progress.setProgress(percent);
                progress.setStatus("正在上传...");
            } catch (ex) {
                this.debug(ex);
            }
        }

        var uploadSuccess = function(file, serverData) {
            try {
                var progress = new FileProgress(file, this.customSettings.progressTarget);
                progress.setComplete();
                progress.setStatus("上传成功");
                progress.toggleCancel(false);
                if (settings.onsuccess) {
                    var data = serverData;
                    if (settings.datatype && settings.datatype =='json') {
                        data = self.str2code("(" + serverData + ")");
                    }
                    settings.onsuccess(file, data);
                }
            } catch (ex) {
                if (settings.onerror) {
                    settings.onerror(ex);
                }
                this.debug(ex);
            }
        }
        //上传错误
        var uploadError = function(file, errorCode, message) {
            try {
                var progress = new FileProgress(file, this.customSettings.progressTarget);
                progress.setError();
                progress.toggleCancel(false);
                if (settings.onerror) {
                    settings.onerror(file, errorCode, message);
                    return;
                }
                switch (errorCode) {
                case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
                    progress.setStatus("上传错误: " + message);
                    this.debug("错误代码: HTTP错误, 文件名: " + file.name + ", 信息: " + message);
                    break;
                case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
                    progress.setStatus("上传失败");
                    this.debug("错误代码: 上传失败, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                case SWFUpload.UPLOAD_ERROR.IO_ERROR:
                    progress.setStatus("服务器 (IO) 错误");
                    this.debug("错误代码: IO 错误, 文件名: " + file.name + ", 信息: " + message);
                    break;
                case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
                    progress.setStatus("安全错误");
                    this.debug("错误代码: 安全错误, 文件名: " + file.name + ", 信息: " + message);
                    break;
                case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
                    progress.setStatus("超出上传限制.");
                    this.debug("错误代码: 超出上传限制, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
                    progress.setStatus("无法验证.  跳过上传.");
                    this.debug("错误代码: 文件验证失败, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
                    // If there aren't any files left (they were all cancelled) disable the cancel button
                    if (this.getStats().files_queued === 0) {
                        document.getElementById(this.customSettings.cancelButtonId).disabled = true;
                    }
                    progress.setStatus("取消");
                    progress.setCancelled();
                    break;
                case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
                    progress.setStatus("停止");
                    break;
                default:
                    progress.setStatus("未处理的错误: " + errorCode);
                    this.debug("错误代码: " + errorCode + ", 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
                    break;
                }
            } catch (ex) {
                this.debug(ex);
            }
        }

        var uploadComplete = function(file) {
            if (this.getStats().files_queued === 0) {
                document.getElementById(this.customSettings.cancelButtonId).disabled = true;
            }
        }

        // This event comes from the Queue Plugin
        var queueComplete = function(numFilesUploaded) {
            //ok
        }
        /*
                [Leo.C, Studio] (C)2004 - 2008
                
                $Hanization: LeoChung $
                $E-Mail: who@imll.net $
                $HomePage: http://imll.net $
                $Date: 2008/11/8 18:02 $
        */
        /*
            A simple class for displaying file information and progress
            Note: This is a demonstration only and not part of SWFUpload.
            Note: Some have had problems adapting this class in IE7. It may not be suitable for your application.
        */

        // Constructor
        // file is a SWFUpload file object
        // targetID is the HTML element id attribute that the FileProgress HTML structure will be added to.
        // Instantiating a new FileProgress object with an existing file will reuse/update the existing DOM elements
        var FileProgress = function(file, targetID) {
            this.fileProgressID = file.id;

            this.opacity = 100;
            this.height = 0;

            this.fileProgressWrapper = document.getElementById(this.fileProgressID);
            if (!this.fileProgressWrapper) {
                this.fileProgressWrapper = document.createElement("div");
                this.fileProgressWrapper.className = "progressWrapper";
                this.fileProgressWrapper.id = this.fileProgressID;

                this.fileProgressElement = document.createElement("div");
                this.fileProgressElement.className = "progressContainer";

                var progressCancel = document.createElement("a");
                progressCancel.className = "progressCancel";
                progressCancel.href = "#";
                progressCancel.style.visibility = "hidden";
                progressCancel.appendChild(document.createTextNode(" "));

                var progressText = document.createElement("div");
                progressText.className = "progressName";
                progressText.appendChild(document.createTextNode(file.name));

                var progressBar = document.createElement("div");
                progressBar.className = "progressBarInProgress";

                var progressStatus = document.createElement("div");
                progressStatus.className = "progressBarStatus";
                progressStatus.innerHTML = "&nbsp;";

                this.fileProgressElement.appendChild(progressCancel);
                this.fileProgressElement.appendChild(progressText);
                this.fileProgressElement.appendChild(progressStatus);
                this.fileProgressElement.appendChild(progressBar);

                this.fileProgressWrapper.appendChild(this.fileProgressElement);

                document.getElementById(targetID).appendChild(this.fileProgressWrapper);
            } else {
                this.fileProgressElement = this.fileProgressWrapper.firstChild;
            }

            this.height = this.fileProgressWrapper.offsetHeight;

        }
        FileProgress.prototype.setProgress = function (percentage) {
            this.fileProgressElement.className = "progressContainer green";
            this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
            this.fileProgressElement.childNodes[3].style.width = percentage + "%";
        };
        FileProgress.prototype.setComplete = function () {
            this.fileProgressElement.className = "progressContainer blue";
            this.fileProgressElement.childNodes[3].className = "progressBarComplete";
            this.fileProgressElement.childNodes[3].style.width = "";

            var oSelf = this;
            setTimeout(function () {
                oSelf.disappear();
            }, 100);
        };
        FileProgress.prototype.setError = function () {
            this.fileProgressElement.className = "progressContainer red";
            this.fileProgressElement.childNodes[3].className = "progressBarError";
            this.fileProgressElement.childNodes[3].style.width = "";

            var oSelf = this;
            setTimeout(function () {
                oSelf.disappear();
            }, 5000);
        };
        FileProgress.prototype.setCancelled = function () {
            this.fileProgressElement.className = "progressContainer";
            this.fileProgressElement.childNodes[3].className = "progressBarError";
            this.fileProgressElement.childNodes[3].style.width = "";

            var oSelf = this;
            setTimeout(function () {
                oSelf.disappear();
            }, 2000);
        };
        FileProgress.prototype.setStatus = function (status) {
            this.fileProgressElement.childNodes[2].innerHTML = status;
        };

        // Show/Hide the cancel button
        FileProgress.prototype.toggleCancel = function (show, swfUploadInstance) {
            this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
            if (swfUploadInstance) {
                var fileID = this.fileProgressID;
                this.fileProgressElement.childNodes[0].onclick = function () {
                    swfUploadInstance.cancelUpload(fileID);
                    return false;
                };
            }
        };

        // Fades out and clips away the FileProgress box.
        FileProgress.prototype.disappear = function () {

            var reduceOpacityBy = 15;
            var reduceHeightBy = 4;
            var rate = 30;  // 15 fps

            if (this.opacity > 0) {
                this.opacity -= reduceOpacityBy;
                if (this.opacity < 0) {
                    this.opacity = 0;
                }

                if (this.fileProgressWrapper.filters) {
                    try {
                        this.fileProgressWrapper.filters.item("DXImageTransform.Microsoft.Alpha").opacity = this.opacity;
                    } catch (e) {
                        // If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
                        this.fileProgressWrapper.style.filter = "progid:DXImageTransform.Microsoft.Alpha(opacity=" + this.opacity + ")";
                    }
                } else {
                    this.fileProgressWrapper.style.opacity = this.opacity / 100;
                }
            }

            if (this.height > 0) {
                this.height -= reduceHeightBy;
                if (this.height < 0) {
                    this.height = 0;
                }

                this.fileProgressWrapper.style.height = this.height + "px";
            }

            if (this.height > 0 || this.opacity > 0) {
                var oSelf = this;
                setTimeout(function () {
                    oSelf.disappear();
                }, rate);
            } else {
                this.fileProgressWrapper.style.display = "none";
            }
        };

        /*
                [Leo.C, Studio] (C)2004 - 2008
                
                $Hanization: LeoChung $
                $E-Mail: who@imll.net $
                $HomePage: http://imll.net $
                $Date: 2008/11/8 18:02 $
        */
        /*
            Queue Plug-in
            
            Features:
                *Adds a cancelQueue() method for cancelling the entire queue.
                *All queued files are uploaded when startUpload() is called.
                *If false is returned from uploadComplete then the queue upload is stopped.
                 If false is not returned (strict comparison) then the queue upload is continued.
                *Adds a QueueComplete event that is fired when all the queued files have finished uploading.
                 Set the event handler with the queue_complete_handler setting.
                
            */
            //默认配置
            var settings = {
                flash_url : "../swfupload/swfupload.swf", //swfupload.swf地址
                upload_url : "./ww-upload.php",
                post_params: {}, //上传的其他参数
                file_post_name : "Filedata",
                file_size_limit : "1 MB",
                file_types : "*.jpg;*.gif", //允许上传的文件类型
                file_types_description : "All Files",
                file_upload_limit : 10,  //限定用户一次性最多上传多少个文件，在上传过程中，该数字会累加，如果设置为“0”，则表示没有限制
                file_queue_limit : 0, //上传队列数量限制，该项通常不需设置，会根据file_upload_limit自动赋
                custom_settings : {
                    progressTarget : "fsUploadProgress",
                    cancelButtonId : "btnCancel"
                },
                debug: false, //是否显示调试信息
                button_image_url: "../images/TestImageNoText_65x29.png", //按钮背景图地址
                button_width: "65", //上传按钮宽度
                button_height: "29", //上传按钮
                button_placeholder_id: "spanButtonPlaceHolder", //占位ID
                button_text: '<span class="theFont">浏览</span>', //占位文本
                button_text_style: ".theFont { font-size: 16; }", //文本样式
                button_text_left_padding: 12,
                button_text_top_padding: 3,
                file_queued_handler : fileQueued,
                file_queue_error_handler : fileQueueError,
                file_dialog_complete_handler : fileDialogComplete,
                upload_start_handler : uploadStart,
                upload_progress_handler : uploadProgress,
                upload_error_handler : uploadError,
                upload_success_handler : uploadSuccess,
                upload_complete_handler : uploadComplete,
                queue_complete_handler : queueComplete,
                onsuccess: function(file, data){
                   //console.log('flash upload', file, data);
                },
                onerror: function(file, errorCode, message){
                   //console.log(file, errorCode, message);
                }
            }
            $.extend(settings, opts)
            var swfUp = new SWFUpload(settings);
        }
    });
    Firstp2p.upload = function(el,opts) {
        return new Upload(el,opts);
    }

})();

/*demo  switch W3C flash normal
Firstp2p.upload($('#content'), {
    "switch": "W3C", 
    "progress": function(per){
        console.log(per);
    },
    "onfinish": function(data, file){
        console.log("上传完成回调", data, file);
    }
});
*/
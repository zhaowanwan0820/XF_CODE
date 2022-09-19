p2popen = {};
p2popen.bindFun = function(fn, selfObj, var_args) {
    return fn.call.apply(fn.bind, arguments);
};
p2popen.util = {};
p2popen.util.dateformat = function(timestamp, format) {
    var date = new Date(timestamp);
    var o = {
        "y": date.getFullYear(),
        "m": date.getMonth() + 1, // month
        "d": date.getDate(), // day
        "h": date.getHours(), // hour
        "i": date.getMinutes(), // minute
        "s": date.getSeconds()
    };
    for (var k in o) {
        format = format.replace(k, o[k] > 9 ? o[k] : ("0" + "" + o[k]));
    }
    return format;
};
p2popen.util.getTimeByDateStr = function(dateStr) {
    var year = parseInt(dateStr.substring(0, 4));
    var month = parseInt(dateStr.substring(5, 7), 10) - 1;
    var day = parseInt(dateStr.substring(8, 10), 10);
    return new Date(year, month, day).getTime();
};
p2popen.util.getNextUniqueIdCount_ = 0;
p2popen.util.getNextUniqueId = function() {
    p2popen.util.getNextUniqueIdCount_++;
    return "_idp_" + p2popen.util.getNextUniqueIdCount_;
};
p2popen.util.getUrlHashParam = function(key) {
    var hash = window.location.hash;
    var hashSplit = hash.split("#");
    if (hashSplit.length < 2) {
        return null;
    }
    var hashKVstrs = hashSplit[1].split("&");
    for (var i = hashKVstrs.length - 1; i >= 0; i--) {
        var kvpieces = hashKVstrs[i].split("=");
        if (kvpieces[0] == key) {
            return kvpieces[1];
        }
    }
    return null;
};
p2popen.util.fileajax = function(url, input, sucbak, errbak) {
    var fileinputstore = null;
    var fileinputparent = null;
    var iframeId = p2popen.util.getNextUniqueId() + 'ajaxiframeId';
    var iframe = document.createElement('iframe');
    iframe.src = 'javascript:false;';
    iframe.style.display = 'none';
    iframe.id = iframeId;
    iframe.name = iframeId;
    var form = document.createElement('form');
    form.setAttribute('action', url);
    form.setAttribute('method', 'POST');
    form.setAttribute('enctype', 'multipart/form-data');
    form.setAttribute('target', iframeId);
    form.setAttribute('style', 'display:hidden');
    fileinputstore = input;
    fileinputparent = input.parentNode;

    if (undefined != window['_SiteId_'] && '' != window['_SiteId_']) {
        var inputSiteId = document.createElement("input");
        $(inputSiteId).attr("type", "hidden").attr("name", "siteId").val(window['_SiteId_']);
        form.appendChild(inputSiteId);
    }

    if (url == "/Upload/file") {
        var schema = document.createElement("input");
        $(schema).attr("type", "hidden").attr("name", "schema").val("1");
        form.appendChild(schema);
    }


    form.appendChild(input);
    document.body.appendChild(form);
    document.body.appendChild(iframe);
    iframe.onload = function() {
        var response = null;
        if (iframe.contentWindow) {
            response = iframe.contentWindow.document.body ? $(iframe.contentWindow.document.body).text() : null;
        } else if (this.iframe_.contentDocument) {
            response = iframe.contentDocument.document.body ? $(iframe.contentDocument.document.body).text() : null;
        }
        sucbak.call(null, response);
        document.body.removeChild(form);
        document.body.removeChild(iframe);
        fileinputparent.appendChild(fileinputstore);
    };
    iframe.onerror = function() {
        document.body.removeChild(form);
        document.body.removeChild(iframe);
        fileinputparent.appendChild(fileinputstore);
    };
    form.submit();
};
p2popen.util.jsonP = function(urlbase, paramkey, suc_callback, opt_err_callback, opt_timeout) {
    var jsonpCallbackIndex = p2popen.util.getNextUniqueId();
    var scripttag = document.createElement("script");
    var succallbackkey = "_P2PJsonPPrefixSuc_" + jsonpCallbackIndex;
    var errcallbackkey = "_P2PJsonPPrefixErr_" + jsonpCallbackIndex;
    window[succallbackkey] = function(result) {
        suc_callback.call(null, result);
        delete window[succallbackkey];
        delete window[errcallbackkey];
        document.body.removeChild(scripttag);
    };
    window[errcallbackkey] = function(errormsg) {
        if (typeof opt_err_callback == "function") {
            opt_err_callback.call(null, errormsg);
        }
        delete window[succallbackkey];
        delete window[errcallbackkey];
        document.body.removeChild(scripttag);
    };
    scripttag.onload = function() {
        if (window[succallbackkey]) {
            window[errcallbackkey].call(null, "数据异常");
        }
    }
    scripttag.onerror = function() {
        if (window[errcallbackkey]) {
            window[errcallbackkey].call(null, "标签加载异常");
        }
    };
    setTimeout(function() {
        if (window[errcallbackkey]) {
            window[errcallbackkey].call(null, "链接超时");
        }
    }, (opt_timeout > 0.001 ? opt_timeout : 30) * 1000);
    scripttag.src = urlbase + (urlbase.indexOf("?") == -1 ? "?" : "&") + paramkey + "=" + succallbackkey;
    document.body.appendChild(scripttag);
};

var UPLOADER_SERVER = '/Upload/image';


p2popen.ui = {};
p2popen.ui.errorTip = function(msg) {
    //alert(msg);
    // open_error("id_4", "提示", msg);
};
p2popen.ui.alert = function(msg, opt_callback) {
    alert(msg);
    if (typeof opt_callback == 'function') {
        opt_callback.call(null);
    }
}
p2popen.ui.confim = function(msg, opt_callback) {
        if (confirm(msg)) {
            if (typeof opt_callback == 'function') {
                opt_callback.call(null);
            }
        }
    }
    /**
     * @param {Element} element 上传panel.
     */
p2popen.ui.ImagePicker = function(element, iptname, suc_callback) {
    this.element = element;
    this.iptname = iptname;
    this._fileInput_ = null;
    this._bindInput_ = null;
    this._loadingEl_ = null;
    this._loading_ = false;
    this._pickerIndex_ = null;
    this._suc_callback = suc_callback;
    this._init_();
};
p2popen.ui.ImagePicker.prototype._init_ = function() {
    $(this.element).addClass("ui-imagepicker");
    this._fileInput_ = document.createElement("input");
    var uodata_file = $(this.element).attr("uodata-file");
    if(uodata_file == "" || uodata_file == undefined){
        $(this._fileInput_).attr("class", "fileinput").attr("type", "file").attr("name", "image").bind("change", p2popen.bindFun(this._uploadImage_, this));
    }else{
        $(this._fileInput_).attr("class", "fileinput").attr("type", "file").attr("name", "file").bind("change", p2popen.bindFun(this._uploadImage_, this));
    }
    $(this.element).append(this._fileInput_);
    this._bindInput_ = document.createElement("input");
    $(this._bindInput_).attr("type", "hidden").attr("name", this.iptname);
    if ($(this.element).attr("data-validate") == "required") {
        $(this._bindInput_).attr("data-rule", "required").attr("data-msg", "请选择图片");
    }
    $(this.element).append(this._bindInput_);
};
p2popen.ui.ImagePicker.prototype.updateLoading = function(loading) {
    this._loading_ = loading;
    if (this._loading_) {
        $(this._fileInput_).hide();
    } else {
        $(this._fileInput_).show();
    }
};
p2popen.ui.ImagePicker.prototype.setImage = function(src, opt_localurl, opt_width, opt_height) {
    if (typeof src != "string" || $.trim(src) == "") {
        return;
    }
    this._bindInput_.value = src;
    if (opt_width > 0 && opt_height > 0) {
        $(this._bindInput_).attr("data-imgwidth", opt_width).attr("data-imgheight", opt_height);
    } else {
        $(this._bindInput_).removeAttr("data-imgwidth").removeAttr("data-imgheight");
    }
    $(this._bindInput_).attr("data-showimageurl", typeof opt_localurl == "string" ? opt_localurl : src);
    this.element.style.background = "url(" + (typeof opt_localurl == "string" ? opt_localurl : src) + ") center no-repeat";
    this.element.style.backgroundSize = "100% 100%";
    if ($(this.element).attr("data-validate") == "required") {
        $(this._bindInput_).trigger("blur");
    }
    $(this.element).trigger("finish");
};
p2popen.ui.ImagePicker.prototype._uploadImage_ = function() {
    var typeLimit = $(this.element).attr("data-limittype");
    if (typeof typeLimit == "string" && typeLimit != "") {
        var str = $(this._fileInput_).val();
        var allowPieces = typeLimit.split(",");
        var result = false;
        var filetype = str.split(".");
        filetype = filetype.length < 2 ? null : filetype[filetype.length - 1].toLowerCase();
        for (var i = 0; i < allowPieces.length; i++) {
            if (allowPieces[i] == filetype) {
                result = true;
                break;
            }
        }
        if (result == false) {
            this._fileInput_.value = "";
            p2popen.ui.errorTip("图片必须要是" + typeLimit + "格式");
            return;
        }
    }
    this.updateLoading(true);
    var file = $(this.element).attr("data-file");
    var uodata_file = $(this.element).attr("uodata-file");
    if (file != "" &&file != undefined) {
        UPLOADER_SERVER = '/Upload/file';
    }else if(uodata_file != "" && uodata_file != undefined){
        UPLOADER_SERVER = ROOT+'?m=SmsTask&a=upload';
    } else {
        UPLOADER_SERVER = '/Upload/image';
    }
    p2popen.util.fileajax(UPLOADER_SERVER, this._fileInput_,
        p2popen.bindFun(this._onuploadSuccess_, this),
        p2popen.bindFun(this._onuploadError_, this));
};
p2popen.ui.ImagePicker.prototype._onuploadSuccess_ = function(jsonstr) {
    var localurl = URL.createObjectURL(this._fileInput_.files[0]);
    this._fileInput_.value = "";
    var rpcresult = null;
    try { rpcresult = $.parseJSON(jsonstr); } catch (e) { rpcresult = {}; }
    if (rpcresult['errorCode'] == 0) {
        var data = rpcresult['data'];
        if (this._suc_callback != undefined) {
            this._suc_callback.call(null,data);
        } else {
            this.setImage(data['src'], localurl, data['width'], data['height']);
        }

    } else if (rpcresult['errorMsg']) {
        p2popen.ui.errorTip(rpcresult['errorMsg']);
    } else {
        p2popen.ui.errorTip("服务器忙，请重试");
    }
    this.updateLoading(false);
};
p2popen.ui.ImagePicker.prototype._onuploadError_ = function(errorMsg) {
    this._fileInput_.value = "";
    p2popen.ui.errorTip(errorMsg);
    this.updateLoading(false);
};
p2popen.ui.ImagePicker._ImageInstances_ = {};
p2popen.ui.ImagePicker.decorateInstance = function(element, opt_image, opt_width, opt_height) {
    element = $(element)[0];
    var pickerIndex = $(element).attr("data-imgpicker_index");
    var imageins = pickerIndex ? p2popen.ui.ImagePicker._ImageInstances_[pickerIndex] : null;
    if (!imageins) {
        pickerIndex = p2popen.util.getNextUniqueId();
        imageins = new p2popen.ui.ImagePicker(element, $(element).attr("data-bindinput"));
        $(element).attr("data-imgpicker_index", pickerIndex);
        p2popen.ui.ImagePicker._ImageInstances_[pickerIndex] = imageins;
    }
    if (typeof opt_image == 'string') {
        imageins.setImage(opt_image, undefined, opt_width, opt_height);
    }
    return imageins;
};
p2popen.ui.ImagePicker.setImage = function(element, opt_image, opt_width, opt_height) {
    p2popen.ui.ImagePicker.decorateInstance(element, opt_image, opt_width, opt_height);
};

p2popen.ui.ModalView = function(contentEl) {
    this.element = null;
    this.contentElement = contentEl;
    this.escBindFun = null;
    this.escBindFun = p2popen.bindFun(function(e) {
        if (e.keyCode == 27) {
            this.hide();
        }
    }, this);
};
p2popen.ui.ModalView.prototype._adjustSize_ = function() {
    console.log();
};
p2popen.ui.ModalView.prototype._warpView_ = function() {
    this.element = document.createElement("div");
    $(this.contentElement).addClass("ui_modal_content").css({ "display": "block", "visibility": "hidden" });
    $(this.element).addClass("ui_modal").hide().append(this.contentElement);
    $(document.body).append($(this.element));
};
p2popen.ui.ModalView.prototype.show = function(opt_box) {
    if (this.element == null) {
        this._warpView_();
    }
    $(this.contentElement).trigger("openmodal.beforeshow");
    $(document.body).addClass("ui_modal_openbody");
    $(this.contentElement).css("visibility", "hidden");
    //    if (opt_box['top'] || opt_box['left'] || opt_box['width'] || opt_box['height']) {
    // $(this.element).css({ "top": opt_box['top'], "left": opt_box['left'], "width": opt_box['width'],"height"})
    //    }
    $(this.element).show();
    this._adjustSize_();
    $(this.contentElement).css("visibility", "visible");
    $(this.contentElement).trigger("openmodal.show");
    $(document).bind('keydown.fb', this.escBindFun);
};
p2popen.ui.ModalView.prototype.hide = function() {
    $(document).unbind('keydown.fb', this.escBindFun);
    $(this.contentElement).trigger("openmodal.beforehide");
    $(this.element).hide();
    $(document.body).removeClass("ui_modal_openbody");
    $(this.contentElement).trigger("openmodal.hide");
};
p2popen.ui.ModalView._modalInstances_ = {};
p2popen.ui.ModalView._warpInstance_ = function(element) {
    element = $(element)[0];
    if (!element) return null;
    var pickerIndex = $(element).attr("data-modalview_index");
    var modalins = pickerIndex ? p2popen.ui.ModalView._modalInstances_[pickerIndex] : null;
    if (!modalins) {
        pickerIndex = p2popen.util.getNextUniqueId();
        modalins = new p2popen.ui.ModalView(element);
        $(element).attr("data-modalview_index", pickerIndex);
        p2popen.ui.ModalView._modalInstances_[pickerIndex] = modalins;
    }
    return modalins;
};
p2popen.ui.ModalView.showModal = function(element, opt_box) {
    var modalins = p2popen.ui.ModalView._warpInstance_(element);
    if (modalins) {
        modalins.show(opt_box);
    }
    return modalins;
};
p2popen.ui.ModalView.hideModal = function(element) {
    var modalins = p2popen.ui.ModalView._warpInstance_(element);
    if (modalins) {
        modalins.hide();
    }
    return modalins;
};

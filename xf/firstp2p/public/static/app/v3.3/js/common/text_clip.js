if (typeof P2PWidget == 'undefined') P2PWidget = {};
if (typeof P2PWidget.ui == 'undefined') P2PWidget.ui = {};
;
P2PWidget.ui.instanceTextClip = function(lineDom, selfFn, showFn, hideFn, option) {
    if ($(lineDom).attr("data-textclip") == "true") return;
    if (typeof selfFn != 'function') selfFn = function() {};
    if (typeof showFn != 'function') showFn = function() {};
    if (typeof hideFn != 'function') hideFn = function() {};
    var _instance = new P2PWidget.ui.textClip(lineDom, showFn, hideFn, option);
    selfFn.call(null, _instance);
    _instance.init();
    return _instance;
};
P2PWidget.ui.textClip = function(lineDom, showFn, hideFn, option) {
    var option = (typeof option == 'object') ? option : {};
    this.showFn = showFn;
    this.hideFn = hideFn;
    this.lineDom = $(lineDom);
    this.opt = $.extend({}, option);
};
P2PWidget.ui.textClip.prototype.init = function() {
    $(this.lineDom).attr("data-textclip", "true");
    this.judgeHeight();
};
P2PWidget.ui.textClip.prototype.judgeHeight = function() {
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
P2PWidget.ui.textClip.prototype.setDom = function() {
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
P2PWidget.ui.textClip.prototype.createArrow = function() {
    this.lineDom.append('<span class="__textClipArrow__ __textClipArrowDown__"></span>');
    this.arrowEle = this.lineDom.find('.__textClipArrow__');
};
P2PWidget.ui.textClip.prototype.arrowEvent = function() {
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
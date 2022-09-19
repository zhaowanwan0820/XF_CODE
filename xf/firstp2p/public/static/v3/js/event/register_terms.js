 ;(function($){
    $(function() { 
        $('#JS-regpanel .JS-regterm').click(function (event) {
            var _rootDomain = 'firstp2p.com';
            if (window['rootDomain'] && window['rootDomain'] != undefined && window['rootDomain'] != "") {
                _rootDomain = window['rootDomain'];
            }
            var _explain = '备案号为：京ICP证130046号，以下简称网信';
            if (window['explain'] && window['explain'] != undefined && window['explain'] != "") {
                _explain = window['explain'];
            }
            P2PWAP.ui.showNoticeDialog('注册协议', $("#register_protocol").val());
            $("#domain_name").html(rootDomain);
            $("#explain").html(explain);
        });
    });
})(Zepto);
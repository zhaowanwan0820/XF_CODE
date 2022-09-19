(function($) {
    $(function() {

        if (typeof Firstp2p == 'undefined') {
            Firstp2p = {};
        }
        Firstp2p.getData = function(options) {
            var defaultSettings = {
                    scriptId: 'mymedal_list',
                    container: $('#mymedal_container')
                },
                settings = $.extend(true, defaultSettings, options),
                cerHtml = function(data) {
                    var jsondata = {};
                    jsondata.list = data;
                    // console.log(jsondata.list);
                    var html = template(settings.scriptId, jsondata);
                    settings.container.html(html);
                }
            cerHtml(datajson);
        };

        //我的勋章
         Firstp2p.getData(); 
    });
})(jQuery);
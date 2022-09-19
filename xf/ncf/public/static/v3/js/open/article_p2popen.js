p2popen_arc = {};
p2popen_arc.util = {};
p2popen_arc.util.getNextUniqueIdCount_ = 0;
p2popen_arc.util.getNextUniqueId = function () {
    p2popen_arc.util.getNextUniqueIdCount_++;
    return "_p2popen_arc_" + p2popen_arc.util.getNextUniqueIdCount_;
};
p2popen_arc.article = {};


p2popen_arc.article.show = function (data, opt_container) {
    if (!opt_container) {
        var conid = p2popen_arc.util.getNextUniqueId();
        document.write("<div id='" + conid + "'></div>");
        opt_container = document.getElementById(conid);
    }
    var container = opt_container;

    //var arcLen = data.length;
    //if (!(arcLen > 0)) {
    //    $(container).find('.ui_arctile_show').hide();
    //    return;
    //}


    function _arcAjax_() {
        $.ajax({
            url: "/article/articletag",
            //url: "http://localhost:25819/static/js/arc_show.js",
            type: "GET",
            dataType: "JSON",
            data:data,
            success: function (data) {
                var newdata = data;
                var html = "";
                if (newdata.errorCode == 0) {
                    html += "<ul>";
                    for (var i = 0; i < newdata.data.length; i++) {
                        html += "<li><a data-id=" + newdata.data[i].id + "  data-appid=" + newdata.data[i].appId + " href=" + newdata.data[i].url + " title=" + newdata.data[i].title + ">" + newdata.data[i].title + "</a></li>";
                    }
                    html += "</ul>";
                }
              $(container).append(html);
            },
            error: function () {
                alert("请求失败");
            }
        });
    }

    $(function() {
         _arcAjax_();
    });
};

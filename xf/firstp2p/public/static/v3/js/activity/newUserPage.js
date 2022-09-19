$(function () {

    //投资用户滚动条
    (function () {
        var scrollListUl=$('#scrollListUl');
        var list=scrollListUl.data('list');
        var len=list.length;
        var html=template('scrollUl_tpl',{
            arr:list
        });
        function getMarTop() {
            return Number(scrollListUl.css('marginTop').replace(/[^\d+-]/g,''));
        }
        scrollListUl.append(html);

        function ani() {
            scrollListUl.animate({
                "marginTop":getMarTop()-45
            },600,'linear',function () {
                if(getMarTop()<=-len*45){
                    scrollListUl.css({
                        "marginTop":0
                    })
                }
                ani();
            })
        }
        ani();
    })();

    //用户进度
    (function () {
        var liList=$('#stepUl .item');
        var isRegister=$('#isRegister').val();
        var isInvest=$('#isInvest').val();
        var isInvite=$('#isInvite').val();
        var arr=[isRegister,isInvest,isInvite];
        for (var i=0;i<arr.length;i++){
            if (arr[i] == 1) {
                liList.eq(i).addClass('active');
            }
        }
    })();

//    复制，分享功能
    (function () {

        try{
            var copyLink=$('#copyLink');
            //复制链接
            var ZeroClip = new ZeroClipboard(copyLink, {
                moviePath: "../static/v1/js/vendor/ZeroClipboard.swf",
                trustedDomains: ['*'],
                allowScriptAccess: "always"
            });
            ZeroClip.on("load", function(client) {
                client.on("complete", function() {
                    $.showErr("邀请链接已复制到剪切板", "", "提示");
                });
            });

            //分享插件参数配置
            var share_default_title = '网信，助力人生go up！100元起投，历史平均年化收益8%~12%，期限灵活。注册首投皆有礼，勋章、投资券等，活动玩法持续升级，在这里，收获的不仅是收益，更多惊喜在等你哦！';
            window.jiathis_config = {}; //jiathis_config必须是全局对象
            var jiaThisBox = $('#jiaThisShareBox');
            var jiaThisBoxObj = jiaThisBox.data('shareData');
            var url=jiathis_config.url = jiaThisBoxObj.url; //初始化分享的url
            var summary=jiathis_config.summary = jiaThisBoxObj.summary; //初始化分享的summary

            //    微信分享弹出框
            Firstp2p.share($("#share32"), {
                "type" : "bds_tools_32",
                "share_con" : {
                    "url" : url,
                    "title" : share_default_title,
                    "content" : summary
                }
            });
            //    社交平台分享
            jiaThisBox.find('.j-jiathis').each(function () {
                var platName=$(this).data('platname');
                if (platName.indexOf("weixin")<0){
                    $(this).attr({
                        href:'http://api.bshare.cn/share/'+ platName +'?url='+ url +'&summary=' + summary,
                        target:"_blank"
                    });
                }
            });
        }catch (ex){

        }

        //登录后跳转回来
        $('#toInvite').attr('href',function () {
            var href=$(this).attr('href');
            return href+'?backurl='+encodeURIComponent(location.href);
        })

    })();

    //根据cookie判断是否注册
    function getCookie() {
        var cookiestr = document.cookie
        if (cookiestr == null || cookiestr == "")
        return null
        var cookieArrs = cookiestr.split(";")
        for (var i = cookieArrs.length - 1; i >= 0; i--) {
            var cookiekvstr = cookieArrs[i].replace(/^\s+|\s+$/g, '')
            var kv = cookiekvstr.split("=")
            var key = kv[0]
            var value = decodeURIComponent(kv[1])
            if (key == name) {
                return value
            }
        }
        return null
    }
    window.onload = function (){
        var timer =null;
        if($('#ifm').length>0){
            $('#ifm')[0].contentWindow.window.$('#reg_v2').submit(function (){
                timer = setInterval(function (){
                    if (getCookie("modal_login_succ") != null) {
                        window.location.reload();
                    }
                },500)
            })
        }       
    }

});
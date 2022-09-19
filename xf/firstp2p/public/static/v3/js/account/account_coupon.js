(function($) {
    $(function() {
        (function() {
            template.helper('dateFormat',function (str) {
                var html="";
                var arr=[];
                if (str){
                    arr=str.split(/\s+/);
                    html='<p>'+arr[0]+'</p>'+'<p>'+arr[1]+'</p>';
                }else{
                    html='--';
                }
                return html;
            });
             var share_default_title = '网信，助力人生go up！100元起投，历史平均年化收益8%~12%，期限灵活。注册首投皆有礼，勋章、投资券等，活动玩法持续升级，在这里，收获的不仅是收益，更多惊喜在等你哦！';
            /******微信分享 点击出分享弹框*************/
            //获取分享地址
            var weixinUrl = $(".jiathis_style_32x32").data("url");
            var weixinContent = $(".jiathis_style_32x32").data("summary");
            Firstp2p.share($("#share32"), {
                "type" : "bds_tools_32",
                "share_con" : {
                    "url" : weixinUrl,
                    "title" : share_default_title,
                    "content" : weixinContent
                }
            });
            /******************************* end********************/

            $("#jiaThisShareBox").on("click" , ".j-jiathis" ,function(){
                 var $t = $(this),
                 $p = $t.parent();
                 var _platname = $t.data("platname");
                 if(_platname.indexOf("weixin")<0){
                     $t.prop({
                         href : 'http://api.bshare.cn/share/'+ $t.data("platname") +'?url='+ $p.data("url") +'&summary=' + (!!$p.data("summary") ? $p.data("summary") : share_default_title),
                         target : "_blank"
                     });
                 }
             });

            var tabItem=$('#tabCont .item');
            var tabTitle=$('#tabTitle li');
            var tabsUl = $("#tabTitle");

            function setpro(input) {
                var errorTip=input.siblings('.errorTip');
                var isBigUser=$.trim(input.siblings('.is_big_user').val());
                var userVerify = false;
                var userVal = $.trim(input.val());
                if('1'==isBigUser){
                    userVerify = (userVal == "") || /^1[3456789]\d{9}$/.test(userVal);
                }else{
                    userVerify = (userVal == "") || /^[\u0391-\uFFE5]{2,12}$|^1[3456789]\d{9}$/.test(userVal);
                }

                input.val(userVal);
                if (!userVerify) {
                    errorTip.show();
                    return false;
                } else {
                    errorTip.hide();
                     return true;
                }
            };
            $("#tabCont .searchInput").on('blur',function() {
                setpro($(this));
            });

            // tab事件绑定
            tabsUl.on("click", "li", function() {
                var index=$(this).index();
                tabTitle.removeClass('active');
                $(this).addClass('active');
                tabItem.hide().eq(index).show();
                var type = $(this).data('tabname');
                if(type == "p2p"){
                  $(".inviteAwardTr").show();
                  $(".historyInviteAwardTr").hide();
                } else {
                  $(".inviteAwardTr").hide();
                  $(".historyInviteAwardTr").show();
                }
            });

            // 点击查询按钮
            $("#tabCont .searchBtn").on("click", function() {
                var input=$(this).siblings('.searchInput');
                var curItem=$(this).parents('.item');
                var index = curItem.index();
                var type=tabTitle.eq(index).data('tabname');
                if(setpro(input)){
                    input.data('content',$.trim(input.val()));
                    reqData(curItem,type,1);
                }
            });

            // ajax请求
            function reqData(curItem,type,page) {
                var curInput=null;
                var content="";
                var ajaxData={
                    'type':type,
                    'page':page,
                    "dataType":1
                };
                if (type != "reg"){
                    curInput=curItem.find('.searchInput');
                    content=curInput.data('content');
                    ajaxData.content=content;
                }
                var _lock = curItem.data('lock');
                if(_lock == "1"){
                   return;
                }
                curItem.data('lock','1');
                var url = type == 'p2p' ? '/account/inviteaward' : '/coupon/lists'
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: ajaxData,
                    dataType: 'json',
                    success: function(result) {
                      investHtml(curItem,result,type);
                      curItem.data('lock','0');
                    },
                    error: function() {
                      curItem.data('lock','0');
                    }
                });
            }

            // 数据拼接
            function investHtml(curItem,result,type) {
                var curTbody=curItem.find('tbody');
                var noRecord=curItem.find('.no_record');
                var curTable=curItem.find('table');
                var pagination=curItem.find('.pagination');
                // 邀请奖励初始渲染列表html:odd奇数行样式类名，even偶数行样式类名
                if(type == 'p2p'){
                  var p2pTypeHtml = '';
                  for(var i=0;i<result.list.length;i++){
                    var odd_even = (i%2 == 0) ? 'odd' : 'even';
                    var awardTime = result.list[i].create_time.split(" ");
                    var awardContent = result.list[i].type < 3 ? '元' : '';
                    var beizhuText = result.list[i].type <= 3 ? '奖励完成' : '已领取';
                    p2pTypeHtml += '<tr class="'+odd_even+' inviteAwardTr">'
                                      + '<td>邀请奖励</td>'
                                      + '<td><div class="dataTr"><p>'+awardTime[0]+'</p><p>'+awardTime[1]+'</p></div></td>'
                                      + '<td>'+result.list[i].type_name+'</td>'
                                      + '<td>'+result.list[i].remark+awardContent+'</td>'
                                      + '<td>'+beizhuText+'</td>'
                                    + '</tr>'
                  }
                }

              // end
                var html =(type == 'p2p') ? p2pTypeHtml :template(type=='reg'?'reg_data':'invest_data', result);

                curTbody.html(html);

                if(result.pagecount <= 0){
                    noRecord.show();
                    curTable.add(pagination).hide();
                } else {
                    noRecord.hide();
                    curTable.add(pagination).show();
                    pageFn(curItem,result,type);
                }
            }
            
            function pageFn(curItem,result,type) {
                var indexPage=null;
                var lastPage=null;
                var pageText = '';
                var curPaginate=curItem.find('.pagination');

                curPaginate.show();
                Firstp2p.paginate(curPaginate, {
                    pages: result.pagecount,
                    currentPage: result.page,
                    displayedPages:3,
                    onPageClick: function(page, $obj) {
                        reqData(curItem,type,page);
                    }
                });
                //分页结构二次加工
                if(result.page==1){
                    indexPage=$('<li><span class="index" title="首页">首页</span></li>');
                }else{
                    indexPage=$('<li><a href="#page=1" class="page-link index" title="首页">首页</a></li>');
                }
                if(result.page==result.pagecount){
                    lastPage=$('<li><span class="last" title="尾页">尾页</span></li>');
                }else{
                    lastPage=$('<li><a href="#page='+result.pagecount+'" class="page-link last" title="尾页">尾页</a></li>');
                }
                pageText = '<li><span class="total">共<i>'+result.pagecount+'</i>页</span></li>';
                curPaginate.find("ul").append(lastPage,pageText).prepend(indexPage);
                curPaginate.find('a.last,a.index').on('click',function (event) {
                    event.preventDefault();
                    var page=$(this).attr('href').match(/.*#page=(\d*)$/)[1];
                    reqData(curItem,type,page);
                });
            }

            tabItem.each(function (index) {
              var type=tabTitle.eq(index).data('tabname');
              reqData($(this),type,1);
            });
        })();

        //TODO 游戏入口,ztx 2015//11//15修改
        (function() {
            //分享插件参数配置
            window.jiathis_config = {}; //jiathis_config必须是全局对象
            var jiaThisBox = $('#jiaThisShareBox');
            var jiaThisBoxObj = jiaThisBox.data('shareData');
            jiathis_config.url = jiaThisBoxObj.url; //初始化分享的url
            jiathis_config.summary = jiaThisBoxObj.summary; //初始化分享的summary

            // 返利说明弹窗
            $("#js_flsm").on("click", function() {
                var flAdv = $('#js_flsm_tel').html();
                var popStr = '<div class="fl-list">';
                popStr += flAdv;
                popStr += '</div>';
                $.weeboxs.open(popStr, {
                    contentType: 'text',
                    boxclass: "fl-list-box",
                    showButton: false,
                    okBtnName: '',
                    showCancel: false,
                    showOk: false,
                    title: '奖励规则',
                    width: 740,
                    type: 'wee'
                });
            });

            //复制链接
            function copyText() {
              var text = document.getElementById("clipTar").innerText;
              var input = document.getElementById("inputHidden");
              input.value = text; // 修改文本框的内容
              input.select(); // 选中文本
              document.execCommand("copy"); // 执行浏览器复制命令
              $.showErr("邀请链接已复制到剪切板", "", "提示");
            }
            $('.copy-link').on('click',function(){
                copyText();
            })
        })();
    });
})(jQuery);

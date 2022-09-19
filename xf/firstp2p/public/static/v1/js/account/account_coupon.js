(function($) {
    $(function() {
        (function() {
            // 邀请记录
            var iptTxt = $("#ipt_txt"),
                userVerify = "",
                errorSpan = $(".ss_error"),
                ssBtn = $("#ss_btn"),
                tabsUl = $("#tabs"),
                loadurl = window.location.href,
                _set = function(msg) {
                    errorSpan.css("display", "block");
                    errorSpan.html(msg);
                },
                _reset = function() {
                    errorSpan.css("display", "none");
                },
                setpro = function() {
                    var userVal = $.trim(iptTxt.val());
                    userVerify = (userVal == "") || /^[\u0391-\uFFE5]{2,12}$|^1[3|4|5|7|8][0-9]\d{8}$/.test(userVal);
                    iptTxt.val(userVal);
                    if (!userVerify) {
                        _set("手机号/姓名格式不正确！");
                        return false;
                    } else {
                        _reset();
                         return true;
                    }
                };
                iptTxt.blur(function() {
                    setpro();
                });
            // tab事件绑定
            tabsUl.on("click", "li a", function() {
                var $that = $(this),
                    status = $that.attr('data-tab');
                    iptTxt.val('');
                    _reset();
                if ($that.parent('li').hasClass('select')) return;
                tabsUl.find("li").removeClass('select');
                $that.parent('li').addClass('select');
                var tabData = Firstdata.data;
                tabData["type"] = status;
                tabData["content"] = '';
                reqData(tabData);
            });
            // 点击查询按钮
            $("#ss_form").on("submit", function() {
                if(!!setpro()){
                    var status = tabsUl.find(".select a").attr('data-tab');
                    var tabData = Firstdata.data;
                    tabData["type"] = status;
                    tabData["content"] = $.trim(iptTxt.val());
                    reqData(tabData);
                }
            });
            // ajax请求
            function reqData(data) {
                var pageText = '';
                var _lock = ssBtn.data('lock');
                if(_lock == "1"){
                       return; 
                    }
                _lock = "1";
                $.ajax({
                    url: '/coupon/lists',
                    type: 'GET',
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        // console.log(JSON.stringify(result));
                        if (result.type == 'p2p' || result.type == 'duotou') {
                            investHtml(result);
                        } else if (result.type == 'reg') {
                            regHtml(result);
                        }
                        if (result.pagecount <= 0) {
                            $(data.pageSelector).hide();
                            return;
                        } else {
                            $(data.pageSelector).show();
                            Firstp2p.paginate($(data.pageSelector), {
                                pages: result.pagecount,
                                currentPage: result.page,
                                onPageClick: function(pageNumber, $obj) {
                                    reqData({
                                        type: data.type,
                                        page: pageNumber,
                                        content: data.content,
                                        pageSelector: data.pageSelector
                                    });
                                }
                            });
                            pageText = '<li style="line-height:25px;">' + result.count + ' 条记录 ' + result.page + '/' + result.pagecount + ' 页</li>';
                            $(data.pageSelector).find("ul").prepend(pageText);
                        }
                        _lock = "0";
                    },
                    error: function() {
                        _lock = "1";
                    }
                })
            }
            /*
            数字序列化
            exp: showDou(2888888);
            输出：2,888,888
            */
            var showDou = function(val) {
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

            //强制保留2位小数，如：2，会在2后面补上00.即2.00 
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

            // 数据拼接
            function investHtml(data) {
                var html = template('invest_data', data);
                var ssStr = template('ss_data', data);
                $("#ss_result").html(ssStr);
                $('#tabs_content01').html(html);
                if(data.pagecount <= 0){
                    $("#no_record01").show();
                    $("#user_cont01").hide();
                } else {
                   $("#no_record01").hide(); 
                   $("#user_cont01").show();
                }
                $("#yh_fl").html(showDou(parseFloat($("#yh_fl").html()).toFixed(2)));
                $("#dh_fl").html(showDou(parseFloat($("#dh_fl").html()).toFixed(2)));
            }
            function regHtml(data) {
                var html = template('reg_data', data);
                $('#tabs_content02').html(html);
                if(data.pagecount <= 0){
                    $("#no_record02").show();
                    $("#user_cont02").hide();
                } else {
                   $("#no_record02").hide(); 
                   $("#user_cont02").show();
                }
            }
            // 初始数据
            var Firstdata = {
                data: {
                    type: 'p2p',
                    page: 1,
                    content: '',
                    pageSelector: '#pagination_00'
                }
            };
            var Seconddata = {
                data: {
                    type: 'reg',
                    page: 1,
                    pageSelector: '#pagination_01'
                }
            };
            var investData = Firstdata.data;
            var regData = Seconddata.data;
            reqData(investData);
            reqData(regData);
        })();

        //TODO 游戏入口,ztx 2015//11//15修改
        (function() {
            //分享插件参数配置
            window.jiathis_config = {}; //jiathis_config必须是全局对象
            var curInvCode = function() {
                var codeArr = $('#clipTar01').text().match(/.*cn=\s*(\w+)\s*/);
                if (codeArr == null) {
                    return "";
                } else {
                    return codeArr[1];
                }
            }(); //当前邀请码
            var jiaThisBox01 = $('#jiaThisShareBox1');
            var jiaThisBox1Obj = jiaThisBox01.data('shareData');
            var initShareUrl = jiathis_config.url = jiaThisBox1Obj.url; //初始化分享的url
            var initShareSummary = jiathis_config.summary = jiaThisBox1Obj.summary; //初始化分享的summary

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
                    title: '返利说明',
                    width: 740,
                    type: 'wee'
                });
            });

            // 邀请方式一：复制链接弹窗
            var ZeroClip = new ZeroClipboard($('.copy-link,.game_share'), {
                moviePath: "../static/v1/js/vendor/ZeroClipboard.swf",
                trustedDomains: ['*'],
                allowScriptAccess: "always"
            });
            var gameErrTimer = null; //复制成功的提示浮层计时器
            ZeroClip.on("load", function(client) {
                client.on("complete", function(client, args) {
                    var gameErr = $(".game-err");
                    if ($(this).hasClass('copy-link')) {
                        $.showErr("邀请链接已复制到剪切板", "", "提示");
                    } else if ($(this).hasClass('game_share')) {
                        gameErr.show();
                        clearTimeout(gameErrTimer);
                        gameErrTimer = null;
                        gameErrTimer = setTimeout(function() {
                            gameErr.hide();
                        }, 2000);
                    }
                });
            });
            //TODO 游戏返利入口
            /**
             * 通过json对象生成游戏列表
             */
            var gameShareWrap = $('#gameShareWrap');
            var gameUl = $('#game-list'); //游戏列表外盒子UL
            var gamePopCon = $('#gamePopCon'); //游戏弹层内容html
            //如果没有配置游戏或者配置出现异常时，进行容错处理

            /**
             * 验证游戏数据是否完备
             */
            function verifyGameData(dataJson) {
                var arr = ['img', 'title', 'url', 'description', 'summary'];
                var flag = true;
                for (var i = 0, max = arr.length; i < max; i++) {
                    if (typeof dataJson[arr[i]] == 'undefined') {
                        flag = false;
                        break;
                    }
                }
                return flag;
            }

            /**
             * 判断数据源是否合法
             * @returns {boolean}
             */
            function verifyDataSource() {
                var verifyFlag = true;
                var gameArr = [];
                var newArr = [];
                if (gameAdJson == null) {
                    verifyFlag = false;
                } else {
                    gameArr = gameAdJson.gameArr;
                    for (var i = 0, max = gameArr.length; i < max; i++) {
                        if (verifyGameData(gameArr[i])) {
                            newArr.push(gameArr[i]);
                        }
                    }
                    gameAdJson.gameArr = newArr;
                }
                if (newArr.length == 0) {
                    verifyFlag = false;
                }
                return verifyFlag;
            }

            /**
             * 增加url参数
             * @param baselink
             * @param par
             * @param parVal
             * @returns {string}
             */
            function addUrlPar(baselink, par, parVal) {
                return baselink + (baselink.indexOf('?') == -1 ? "?" : "&") + par + "=" + encodeURIComponent(parVal);
            }
            /**
             * 生成游戏列表html结构
             */
            function gamePanelFn() {
                var invMore = $('.inv-more'); //展开&&收起游戏列表按钮
                var newLi = null;
                var newLiStr = "";
                var gameArr = gameAdJson.gameArr; //游戏信息数组
                var curGameDes = ""; //游戏图片下面描述和对应弹层标题
                var spanText = "";
                $('#gameShareTitle').text(gameAdJson.title);
                $('#gameShareDes').text(gameAdJson.des);
                for (var i = 0, max = gameArr.length; i < max; i++) {
                    curGameDes = gameArr[i].title;
                    spanText = function() {
                        var returnStr;
                        if (curGameDes.length <= 15) {
                            returnStr = curGameDes;
                        } else {
                            returnStr = curGameDes.substr(0, 15) + '...';
                        }
                        return returnStr;
                    }();
                    newLiStr = '<li><img src="' + gameArr[i].img + '" alt="" width="72" height="72"><div><span title="' + curGameDes + '">' + spanText + '</span></div></li>'
                    newLi = $(newLiStr);
                    gameUl.append(newLi);
                }
                //如果游戏数量>6,显示"展开更多"
                if (max > 6) {
                    invMore.on('click', function() {
                        if (!gameUl.hasClass('unfold')) {
                            gameUl.addClass('unfold');
                            $(this).text('收起更多').addClass('up');
                        } else {
                            gameUl.removeClass('unfold');
                            $(this).text('展开更多').removeClass('up');
                        }

                        $(".span_placeholder").remove();
                        //IE9以下浏览器不支持placeholder
                        $(".int_placeholder").each(function() {
                            var p_text = $(this).attr("data-placeholder");
                            new Firstp2p.placeholder(this, {
                                placeholder_text: p_text == null ? "请输入" : p_text
                            });
                        });
                    });
                    invMore.css('display', 'inline-block');
                }
            }
            //如果数据源合法，执行gamePanelFn渲染游戏列表
            if (verifyDataSource()) {
                gamePanelFn();
            } else {
                gameShareWrap.hide();
                //IE9以下浏览器不支持placeholder
                $(".int_placeholder").each(function() {
                    var p_text = $(this).attr("data-placeholder");
                    new Firstp2p.placeholder(this, {
                        placeholder_text: p_text == null ? "请输入" : p_text
                    });
                });
            }
            /**
             * 返回点击弹层需要的相关数据
             * @param jsonObj
             */
            function creatDataJson(jsonObj) {
                var newJson = $.extend({}, jsonObj);
                return newJson;
            }

            /**
             * 游戏列表点击事件
             */
            gameUl.on('click', 'li', function() {
                var dataJson = null;
                var _this = $(this);
                var gameArr = gameAdJson.gameArr;
                dataJson = function() {
                    var returnObj = null;
                    if (_this.data('shareData')) {
                        returnObj = _this.data('shareData');
                    } else {
                        returnObj = creatDataJson(gameArr[_this.index()]);
                        _this.data('shareData', returnObj);

                    }
                    return returnObj;
                }();
                //console.dir(dataJson);
                changGamePanel(dataJson);
                $.weeboxs.open('.gshare-list', {
                    contentType: 'selector',
                    boxclass: "gs-list-box",
                    showButton: false,
                    okBtnName: '',
                    showCancel: false,
                    showOk: false,
                    title: dataJson.title,
                    width: 654,
                    type: 'wee',
                    onclose: function() {
                        jiathis_config.url = initShareUrl;
                        jiathis_config.summary = initShareSummary;
                        delete jiathis_config.pic;
                    }
                });
                //var jiathis_shareUrl=dataJson.url+"?cn=" + curInvCode;//获取游戏分享地址
                var jiathis_shareUrl = addUrlPar(dataJson.url, "cn", curInvCode); //获取游戏分享地址
                jiathis_config.url = jiathis_shareUrl;
                jiathis_config.summary = dataJson.summary;
                jiathis_config.pic = dataJson.img + "||" + 'http://s.jiathis.com/qrcode.php?url=' + encodeURIComponent(jiathis_shareUrl);
                $('.dialog-content').append(gamePopCon.show());
                var gsListBox = $('.gs-list-box'); //弹层外盒子
                gsListBox.css({
                    'top': ($(window).height() - gsListBox.height()) / 2 + $(window).scrollTop(),
                    'left': ($(window).width() - gsListBox.width()) / 2 + $(window).scrollLeft()
                });
            });
            /**
             * 修改弹层面板数据
             * @param dataJson
             */
            function changGamePanel(dataJson) {
                var wxBox = gamePopCon.find('.gm-share'); //微信图片外盒子
                var jiaThisBox = gamePopCon.find('.jthis'); //分享插件外盒子
                //var popSharePar = dataJson.url+"?cn=" + curInvCode;
                var popSharePar = addUrlPar(dataJson.url, 'cn', curInvCode);
                var invUrlInput = gamePopCon.find('.inv-url .inv-url-input');
                gamePopCon.find('.des').html(dataJson.description);
                invUrlInput.val(popSharePar).data('initVal', popSharePar);
                invUrlInput.off().on({
                    'keyup keydown': function(event) {
                        var keyCode = event.keyCode;
                        //console.log(keyCode);
                        if (keyCode != "37" && keyCode != "39" && keyCode != "35" && keyCode != "36") {
                            return false;
                        }
                    },
                    'paste contextmenu': function(event) {
                        return false;
                    },
                    'blur': function() {
                        $(this).val($(this).data('initVal'));
                    }
                });
                wxBox.find('img').attr('src', 'http://s.jiathis.com/qrcode.php?url=' + encodeURIComponent(popSharePar));
            }
        })();
    });
})(jQuery);

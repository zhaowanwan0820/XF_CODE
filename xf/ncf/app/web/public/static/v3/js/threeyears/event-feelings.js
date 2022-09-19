$(function() {
    //(NOTE!!!!!!)如果线上图片资源跟这个地址不一样请主动修改
    var imageRoot = location.protocol + '//' + location.hostname + '/static/v3/images/threeyears/';
    //此处记录所有引入的图片的尺寸,除了大背景。大背景要预先加载
    var sourceFiles = [{
        "url": "earth.png",
        "size": 42
    }, {
        "url": "icon-logo.png",
        "size": 4.16
    }, {
        "url": "icon-star.png",
        "size": 1.98
    }, {
        "url": "icon-thanks.png",
        "size": 10.4
    }, {
        "url": "moon.png",
        "size": 7.16
    }, {
        "url": "people.png",
        "size": 4.69
    }, {
        "url": "rocket.png",
        "size": 8.73
    }, {
        "url": "rocket-bd.png",
        "size": 4.95
    }, {
        "url": "rocket-ft.png",
        "size": 5.24
    }, {
        "url": "sc-01.png",
        "size": 4.55
    }, {
        "url": "statistics-1.png",
        "size": 17.2
    }, {
        "url": "statistics-2.png",
        "size": 13.7
    }, {
        "url": "statistics-3.png",
        "size": 17.7
    }, {
        "url": "statistics-4.png",
        "size": 13.7
    }, {
        "url": "statistics-5.png",
        "size": 19.3
    }, {
        "url": "statistics-6.png",
        "size": 11.7
    }, {
        "url": "statistics-7.png",
        "size": 15.9
    }, {
        "url": "statistics-8.png",
        "size": 19.5
    }, {
        "url": "statistics-galaxy.png",
        "size": 33.7
    }];
    //预加载逻辑，就是把主要的大图size拿出来，计算单个加载的比例算出当前进度
    function preloadLogic() {
        var totalSize = 0;
        var loadedSize = 0;
        for (var i = 0; i < sourceFiles.length; i++) {
            totalSize += sourceFiles[i].size;
        }
        var numAnim = new CountUp($("#JS-loading font")[0], 0, 0);
        for (var i = 0; i < sourceFiles.length; i++) {
            var item = sourceFiles[i];
            var img = new Image();
            img.size = item.size;
            img.onload = function() {
                loadedSize = loadedSize + this.size;
                numAnim.update(parseInt(loadedSize * 100 / totalSize));
                // load ready
                if (parseInt(loadedSize * 100 / totalSize) > 98) {
                    setTimeout(function() {
                        $("#JS-loading").remove();
                        $("body").addClass('load-ready');
                    }, 1000);
                }
            }
            img.src = imageRoot + item.url;
        }
    }
    preloadLogic();

    //判断是否添加分享邀请按钮
    var inviteBtn = $('#inviteBtn');
    //alert(_isApp);
    if (typeof(_isApp) != 'undefined' && _isApp != 0) {
        inviteBtn.attr('href', 'bonus://api?title=' + encodeURIComponent(shareOpt.title) + '&content=' + encodeURIComponent(shareOpt.content) + '&face=' + encodeURIComponent(shareOpt.img) + '&url=' + encodeURIComponent(shareOpt.url));
    } else {
        inviteBtn.on('click', function() {
            //title, content, url, img
            P2PWAP.ui.showShareView(shareOpt.title, shareOpt.content, shareOpt.url, shareOpt.img, 1);
            return false;
        });
    }
    if (_isHideShare == 1) {
        inviteBtn.parent().hide();
    }

    $('html,body').on('touchmove', function(e) {
        e.preventDefault();
    });

    // The people walk animation
    var moveAnimate = {
        moveAnimateEl: document.getElementsByClassName('earth-area')[0],
        wait: function() {
            this.moveAnimateEl.id = "";
        },
        walk: function() {
            this.moveAnimateEl.id = "js-walk";
        },
        meet: function() {
            this.moveAnimateEl.id = "js-meet";
        }
    };


    // section play animate
    var SectionAnimate = function(els, opt) {
        this.opt = opt || {};
        this.delayArray = [8, 8, 8, 8, 8, 13, 3, 3, 3, 10, 10, 10];
        this.outAnimateAttay = [6, 6, 6, 6, 6, 8, 6, 6, 6, 7, 7, 10];
        if (this.opt.isNewUser) { // remove 7、8、9
            //alert(this.opt.isNewUser);
            document.body.removeChild($('.mod-sc')[6]);
            document.body.removeChild($('.mod-sc')[6]);
            document.body.removeChild($('.mod-sc')[6]);
            this.delayArray.splice(6, 3);
            this.outAnimateAttay.splice(6, 3);
        }
        this.items = els;
        this.length = this.items.length;
        this.index = 0;
        this.playTimmer = null;
        this.outTimmer = null;
        this.canPlay = true;
        this.inAnimat = false;
        this.init();
    };
    SectionAnimate.prototype = {
        constructor: constructor,
        init: function() {
            for (var i = 0; i < this.items.length; i++) {
                this.items[i].style.display = 'none';
            }
            this.show(this.index);
        },
        show: function(index) {
            var _this = this;
            for (var i = 0; i < this.items.length; i++) {
                $(this.items[i]).hide().removeClass("js-inAnimate").removeClass("js-outAnimate");
            }
            $(this.items[index]).show().addClass("js-inAnimate");
            this.outTimmer = setTimeout(function() {
                $(_this.items[index]).addClass("js-outAnimate");
            }, _this.outAnimateAttay[_this.index] * 1000);

            this.animateTimmer = setTimeout(function() {
                _this.inAnimat = false;
            }, 3000);

            if (this.index >= this.length - 3) {
                moveAnimate.meet(); // 小人开始走动
            }

            this.index++;
            if (!this.opt.isNewUser) { // 不显示个人信息
                if (this.index > 6 && this.index < 11) {
                    $('#btn-play').show();
                } else {
                    $('#btn-play').hide();
                }
                if (this.index == 8 || this.index == 9 || this.index == 10  || this.index == 7) {
                    moveAnimate.wait(); // 小人开始走动
                    this.stop();
                }
            }
            if (this.opt.tryPlaybtn) {
                this.opt.tryPlaybtn.onclick = function() {
                    if (_this.inAnimat) {
                        return;
                    };
                    _this.inAnimat = true;
                    moveAnimate.walk(); // 小人开始走动
                    $(_this.items[index]).addClass("js-outAnimate");
                    var _tryPlayAgreeTimmer = setTimeout(function() {
                        _this.canPlay = true;
                        _this.show(_this.index);
                    }, _this.delayArray[_this.index - 1] * 1000);
                };
            }
            if (this.canPlay) {
                this.play();
            }
        },
        play: function() {
            if (this.index >= this.length) {
                clearTimeout(this.playTimmer);
                clearTimeout(this.outTimmer);
                return;
            }
            var _this = this;
            this.playTimmer = setTimeout(function() {
                _this.show(_this.index);
            }, _this.delayArray[_this.index - 1] * 1000);
        },
        stop: function() {
            this.canPlay = false;
            clearTimeout(this.outTimmer);
        }
    };


    //loginBtn.value = '数据加载中...';
    moveAnimate.walk(); // 小人开始走动
    setTimeout(function() {
        //console.log('获取数据成功');
        // 载入用户数据
        setMemberData(userData);
        var sectionEl = document.getElementsByClassName('mod-sc');
        new SectionAnimate(sectionEl, {
            tryPlaybtn: document.getElementById('btn-play'),
            isNewUser: isNewUser
        });
    }, 2000);

    function setMemberData(data) {
		var curElementStr="";
        var curData="";
        var data = data || {};
        var dataElement = [
            '#data_memberRank',
            '#data_memberMins',
            '#data_rankScale',
            '#data_contributionScale',
            '#data_joinMins',
            '#data_bonus',
            '#data_mins',
            '#data_medalNum',
            '#data_medalRank'
        ];
        var memberData = [
            data.memberRank,
            data.memberMins,
            data.rankScale,
            data.contributionScale,
            data.joinMins,
            data.bonus,
            data.mins,
            data.medalNum,
            data.medalRank
        ];
        for (var i = 0; i < dataElement.length; i++) {
			curElementStr=dataElement[i];
            curData=memberData[i];
             if(curElementStr=="#data_rankScale" && /^\s*%/.test(curData)){
                 curData="0%";
             }
             if(curElementStr=="#data_contributionScale" && /^\s*$/.test(curData)){
                 curData="0";
             }
             $(curElementStr).text(curData);
        }
    };
});



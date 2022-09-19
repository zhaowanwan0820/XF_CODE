seajs.use(['jquery', 'validator', 'formsubmit', 'dialog', 'upload'], function($, undefined, formsubmit, dialog, upload) {
    //防止重复检测
    var storages = {};

    var lock = function(ele, cls) {
        ele.addClass(cls);
        ele.attr('disabled',true);
    }
    var unlock = function(ele, cls) {
        ele.removeClass(cls);
        ele.attr('disabled',false);
    }

    //lock($('#licaiuser'), 'gray');

    function form2obj(frm) {
        var obj ={}, _len = 0, cnames = {};
        for (var i = 0, len = frm.length; i < len; ++i) {
            var item = frm[i];
            var t, type = item.getAttribute("type");
            if (item.nodeName.toLowerCase() == "input"
                && type && (t = type.toLowerCase(), t == "buttom" || t == "image" || t == "file")
            ) {
                continue;
            }
            if (item.disabled || !item.getAttribute('name')) {
                continue;
            }
            _len++;
            var name = item.name;
            var type = "";
            if (item.getAttribute("type")) {
                type = item.getAttribute("type").toLowerCase();
            }
            var val = item.value;
            if (type == "radio") {
                if (item.checked) {
                    obj[name] = val;
                }
            } else if (type == "checkbox") {
                if (item.checked) {
                    obj[name] = obj[name] || [];
                    obj[name].push(val);
                    cnames[name] = name;
                }
            } else {
                obj[name] = val;
            }
        }
        for (var k in cnames) {
            obj[cnames[k]] = obj[cnames[k]].join(',');
        }
        if (_len === 0) {
            obj = null;
        }
        return obj;
    }

    $.validator.config({
        //stopOnError: false,
        //theme: 'yellow_right',
        defaultMsg: "{0}格式不正确",
        loadingMsg: "正在验证...",
        rules: {
            digits: [/^\d+$/, "请输入数字"]
            ,letters: [/^[a-z]+$/i, "{0}只能输入字母"]
            ,tel: [/^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:[48]00[\- ]?[1-9]\d{6}))$/, "电话格式不正确"]
            ,mobile: [/^1[3456789]\d{9}$/, "手机号格式不正确"]
            ,email: [/^[\w\+\-]+(\.[\w\+\-]+)*@[a-z\d\-]+(\.[a-z\d\-]+)*\.([a-z]{2,4})$/i, "邮箱格式不正确"]
            ,qq: [/^[1-9]\d{4,}$/, "QQ号格式不正确"]
            //,date: [/^\d{4}-\d{1,2}-\d{1,2}$/, "请输入正确的日期,例:yyyy-mm-dd"]
            ,time: [/^([01]\d|2[0-3])(:[0-5]\d){1,2}$/, "请输入正确的时间,例:14:30或14:30:00"]
            ,ID_card: [/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, "请输入正确的身份证号码"]
            ,url: [/^(https?|ftp):\/\/[^\s]+$/i, "网址格式不正确"]
            ,postcode: [/^[1-9]\d{5}$/, "邮政编码格式不正确"]
            ,chinese: [/^[\u0391-\uFFE5]+$/, "请输入中文"]
            ,chineseName : [/^[\u0391-\uFFE5]{2,6}$/,"请输入2-6个汉字中文"]
            ,username2: [/^\w{4,16}$/, "请输入4-16位数字、字母、下划线"]
            ,password: [/^[0-9a-zA-Z]{6,16}$/, "密码由6-16位数字、字母组成"]
            ,fileImage : [/\.jpg$|\.png$/,"图片格式仅限JPG,PNG"]
            ,address : [/^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/,"填写正确的地址"]
            ,name:  [/^.{2,20}$/,"请正确输入{0}"]
            ,judegRepeat: function(el) {
            	var val = $.trim($(el).val());
            	return $.ajax({
                    url: '/deal/dobidstepone',
                    type: 'post',
                    data: {"username": val},
                    dataType: 'json',
                    success: function(data){
						data.error = "该用户名已被使用";
                    }
                });
            }
            ,date: function(el) {
                var val = el.value;
                var reg = /^\d{4}-\d{1,2}-\d{1,2}$/;
                if (reg.test(val)) {
                    var yearNow = Math.floor(new Date().getUTCFullYear());
                    var year = Math.floor(val.substring(0,4));
                    var age = yearNow - year;
                    if (age < 18 || age > 70) {
                        return {
                            "error" : "仅支持18至70周岁的用户"
                        }
                    }
                } else {
                    return {
                        error: "日期格式:yyyy-mm-dd"
                    }
                }

            }
            ,ID_card_more: function(el) {
                var val = $.trim($(el).val());
                var regs = ['', /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[a-zA-Z])$/, /^.{1,50}$/, /^.{1,50}$/, /^.{1,50}$/];
                var id_type = document.getElementById('id_type');
                var _val = id_type.value;
                //正则验证通过后做唯一性验证
                var index;
                var hash = {"1":1, "4":2, "6":3, "2":4};
                if (hash[id_type.value] == undefined) {
                    return;
                }
                index = hash[id_type.value];

                if (regs[index] && regs[index].test(val)) {
                    //身份证效验
                    // if (index == '1') {
                    //     var year = Math.floor(val.substring(6,10));
                    //     var month = Math.floor(val.substring(10,12));
                    //     var day = Math.floor(val.substring(12,14));
                    //     var yearNow = Math.floor(new Date().getUTCFullYear());
                    //     var age = yearNow - year;
                    //     if (age < 18 || age > 70) {
                    //         return {
                    //             "error" : "仅支持18至70周岁的用户"
                    //         }
                    //     }
                    // }
                    if (storages[val]) {
                        return;
                    }

                    return $.ajax({
                        url: './IdcardExist',
                        type: 'post',
                        //idno idType id_type
                        data: {"idno": val, "idType": document.getElementById('id_type').value},
                        dataType: 'json',
                        success: function(data){
                            if (data.code == "0") {
                                storages[val] = true;
                            } else {
                                data.error = data.msg;
                            }
                        }
                    })

                } else {
                    return {
                        "error" : "证件格式不正确，请重新输入"
                    }
                }

            }
        }
    });
    // dialog

    function dialogSet(el, t, cont) {
        dialog({
            title: '提示',
            content: cont.info,
            skin: 'newdialog',
            okValue: '<span style="font-size:16px; padding-left: 30px;padding-right: 30px;">确定</span>',
            cancelValue: '取消',
            ok: function() {
                t.btnReset(el);
                cont.jump && window.location.replace(cont.jump);
            },
            cancelDisplay: false,
            cancel: function(){
                t.btnReset(el);
                cont.jump && window.location.replace(cont.jump);
            }
        }).show();
    }

    function dealRet(el, t, cont) {

/*        cont.jump && window.location.replace(cont.jump);
        $("._reg_tip").find('span').html(cont.info);
        $("._reg_tip").show(300);
        window.scrollTo(0,240)
        t.btnReset(el);
*/
        var id_type = document.getElementById('id_type');
        var success_msg = document.getElementById("success_msg");
        var good_item = document.getElementById("good_item");
        var step2 = document.getElementById("step2");
        var step3_msg = document.getElementById("step3_msg");

        if (cont.jump != '') {
            window.scrollTo(0,0)
            success_msg.style.display = 'block';
            step2.style.display = 'none';
            //内地
            if (id_type.value == '1') {
                step3_msg.innerHTML = '现在进入首页 ，开始投资理财或者查看 <a href="/guide" title="新手指南" class="color-blue1">新手指南</a>';
                good_item.style.display = 'block'
            } else {
                step3_msg.innerHTML = '实名认证将在1到3个工作日内审核完成，请耐心等候.';
            }
        } else {
            alert(cont.info);
            window.location.reload();
            // $("._reg_tip").find('span').html(cont.info);
            // $("._reg_tip").show(300);
            // window.scrollTo(0,240)
            // t.btnReset(el);
        }
    }

    var up_switch = false;

    var upload_cfg = {
        template : {

//                        +   '<input class="ui_file" type="file" name="file[]" />'
            "html5"   : '<div class="showimg"></div><div class="progress"></div><div class="upfile_root">'
                        +   '<div class="upfile_c">'
                        +   '<a class="upfile_word" href="javascript:void(0);">选择文件</a>'
                        +   '<input class="ui_file" type="file" name="file" />'
                        +   ''
                        +   '</div>' +
                        '</div>',
            "normal" : '<div class="showimg"></div><div class="upfile_root">'
                        +   '<div class="upfile_c">'
                        +   '<a class="upfile_word" href="javascript:void(0);">选择文件</a>'
                        +   '<input class="ui_file" type="file" name="file" />'
                        +   ''
                        +   '</div>' +
                        '</div>'
        },
        datatype : "json",//返回值类型
        static : [Firstp2p.staticUrl + "/v2/js/widget/seajs/pc/upload/css/html5.v1.css"],
        type : "'jpeg|jpg'", //允许上传类型
        onsuccess : function(ele, data){ //上传成功回调
            //console.log('data, ele', data, ele);
            up_switch = false;
            var prt = this.ele;
            var showimg = prt.find('.showimg');
            if (data.error) {
                alert(data.msg);
                return false;
            }
            if (showimg.length) {
                showimg.css({
                    "background-image":"url(" + data.imgUrl.replace(/^\./, '') + ")",
                    "background-size":"150px auto",
                    "background-repeat": "no-repeat",
                    "background-position": "50% 50%"
                });
                //去掉查看大图
                //showimg.html('<p><a href="'+ data.imgUrl +'" target="_blank">点击查看大图</a></p>');
                $('#' + prt.attr('data-target')).val(data.imgUrl);
                //$('#' + prt.attr('data-target')).blur();
                //$('#' + prt.attr('data-target')).blur();
                //console.log('prt', prt.attr('data-target'));
                this.ele.find('.upfile_c').css('opacity', 0);
                //修改 删除功能
                this._opts.modifyAndDel && this._opts.modifyAndDel(this);
            }
        },
        modifyAndDel: function(that){
            var hack = '<div class="upfile_root mobile-pup-ul-upload">'
            +   '<div class="upfile_c" style="opacity: 1; width:30px;">'
            +       '<a class="upfile_word" href="javascript:void(0);">修改</a>'
            +       '<input class="ui_file" type="file" name="file" style="display: block;">'
            +   '</div>'
            +'</div>'
            var cont = that.ele.parent().find('.mobile-pup-ul');
            cont.find('.imgdesc_m').html(hack);
            cont.find('.imgdesc_d').html('<a href="javascript:void(0);">删除</a>');
            //cont.html('&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" class="imgdesc_m">修改</a>&nbsp;&nbsp;<a href="javascript:void(0);" class="imgdesc_d">删除</a>');
            var _md = function (that, el) {
                //自动选择
                up_switch = true;
                if (that._opts.html5) {
                    //ie9对html5上传支持不足bug
                    if ( that.detectIE(9, 9) ) {
                        that._do_normal(el)
                    } else {
                    if (that._opts.begin) {
                        that._opts.begin(that.ele);
                    }
                        that._do_html5(el);
                    }
                } else {
                    that._do_normal(el);
                }
                //var prt = that.ele;
                //$('#' + prt.attr('data-target')).blur();
            }
            var _de = function (that) {
                that.ele.find('.upfile_c').css('opacity', 1);
                var prt = that.ele;
                var showimg = prt.find('.showimg');
                $('#' + prt.attr('data-target')).val('');
                if (showimg.length) {
                    showimg.css({
                        "background-image":"none"
                    });
                }
                //var prt = that.ele;
                //$('#' + prt.attr('data-target')).blur();
            }
            //修改
            cont.find('.imgdesc_m input[type=file]').unbind('change').change(function() {
                _de(that);
                _md(that, this);
            });
            //删除
            cont.find('.imgdesc_d a').unbind('click').click(function() {
                _de(that);
            });
        },
        onerror : function(e){ //产生错误回调
            alert("错误!");
            //console.log(e);
        },
        progress : function(per, ele){
            //上传进度条回调, 参数 per 代表百分比
            var pele = ele.parent().parent().parent().find(".progress");
            pele.css({"display": "block", "width": per + "%"});
            if (per>=100) {
                pele.hide(500);
            }
        },
        //begin处理样式
        begin: function(ele){

            if (!ele.find('input[type=file]').val() && !up_switch) {
                return false;
            }
            ele.find('.upfile_c').css('opacity', 1);
            var word = ele.find('.upfile_c');
            var link = word.find('.upfile_word')[0];
            if (!link.getAttribute('_init')) {
                link.setAttribute("_init", link.innerHTML);
                link.innerHTML = '上传中..';
            }
            word.addClass('upfiling');
            ele.find('input[type=file]').css('display', 'none');
            lock($('#licaiuser'), 'gray');
        },
        end: function(ele){

            var word = ele.find('.upfile_c');
            var link = word.find('.upfile_word')[0];
            link.getAttribute('_init') && (link.innerHTML = link.getAttribute('_init'));
            link.removeAttribute('_init');
            word.removeClass('upfiling');
            ele.find('input[type=file]').css('display', 'block');
            unlock($('#licaiuser'), 'gray')
        },
        //post_params: {"is_priv": "1"}, //其他传递参数
        upload_url : "../upload/Newuploadimg" //ww-upload.php ../upload/Newuploadimg
    }

    var exec = function() {
        $("#birthday").datepicker({
          showAnim:'fadeIn',
          changeMonth: true,
          changeYear: true,
          yearRange: "1942:2014",
          minDate: "-72Y",
          maxDate: -1,
          onSelect:function(date){
              this.focus();
              this.blur();
          }
        });
        //file upload
        upload($('#upcontent'), upload_cfg);
        upload($('#upcontent2'), upload_cfg);
        //[TODO]上传范例
        var list =["","", Firstp2p.staticUrl + "/default/images/hk.jpg", Firstp2p.staticUrl + "/default/images/tw.jpg", Firstp2p.staticUrl + "/default/images/hz.jpg"];
        var imgdesc = ["", "", "通行证正面,通行证反面", "通行证内页", "护照"];
        //上传范例
        var imgdemo = document.getElementById('imgdemo');
        var id_type = document.getElementById('id_type');
        var elimgdesc = $('.imgdesc');
        var clear = function(){
            var ids = ["name","idno","birthday","file1","file2"];
            for (var i = 0, len = ids.length; i < len; i++) {
                var el = document.getElementById(ids[i]);
                if (el) {
                    el.value = '';
                }
            }
            //清除错误提示
            $("._reg_tip").find('span').html('');
            $("._reg_tip").css({"display": "none"});
        }
        var showItem = function(tag, upn) {
            clear();
            elimgdesc.html('');
            //重置提示 和 图片
            $('#mobilepaseed').find('.msg-box').css('display', 'none');
            $('#mobilepaseed').find('.showimg').css({'background-image':''});

            var el = document.getElementById('showItem');
            var inputs = $(el).find('input');
            if (tag == "1") {
                el.style.display = "none";
                inputs.attr('disabled', 'disabled');
                return false;
            }
            inputs.removeAttr('disabled');
            el.style.display = "block";
            var ele = $(el).find('.mobile-pup');
            var ele2 = $(el).find('.mobile-pup-ul');
            ele.css('display', 'none');
            ele2.css('display', 'none');
            if (upn) {
                ele.eq(0).css('display', 'block');
                ele2.eq(0).css('display', 'block');
                $('#' + ele.eq(1).attr('data-target')).attr('disabled', 'disabled');
            } else {
                ele.css('display', 'block');
                ele2.css('display', 'block');
            }

            if (list[tag]) {
                imgdemo.setAttribute('href', list[tag]);
                var arr = imgdesc[tag].split(',');
                for (var i = 0, len = arr.length; i < len; i++) {
                    elimgdesc.eq(i).html(arr[i]);
                }
            }
        }
        var task = ['',
                    function(tag){
                        showItem(tag);
                    },
                    function(tag){
                        showItem(tag);
                    },
                    function(tag){
                        showItem(tag,'1');
                    },
                    function(tag){
                        showItem(tag, '1');
                    }

                    ];
        //自建索引 去掉 在ie8中发现bug, 自建属性 index 不能用getAttribute获得,可以用 .index 获得 哦 option 标记

        id_type.onchange = function(e) {
            var val = this.value, index;

            var hash = {"1":1, "4":2, "6":3, "2":4};
            if (hash[id_type.value] == undefined) {
                return;
            }
            index = hash[id_type.value];

            //console.log('index', index);
            var evt = task[index];
                if (typeof evt == 'function') {
                    evt(index);
                }
        }

    }

	$(function(){
        // 表单提交
        formsubmit('#mobilepaseed', {
            btnReset: function(el) {
                return this.submitBtn.removeAttr('disabled', 'disabled').css({
                    "background": "#ffb904",
                    "color": "#fff"
                }).val('提交');
            },
            btnDisable : function(el){
                return this.submitBtn.attr('disabled','disabled').css({"background":"gray","color":"#fff"}).val('正在提交中...');
            },
            ajaxFn: function(el) {
                var that = this;
                $.ajax({
                    type: 'POST',
                    url: el.attr('action'),
                    beforeSend: function() {
                        that.btnDisable(el);
                    },
                    data: form2obj(el.get(0)),
                    success: function(data) {
                        //默认已经变为object
                        data = typeof data == 'string' ? $.parseJSON(data) : data;
                        var cont = data.info || '';
                        dealRet(el, that, data);
                    },
                    error: function() {
                        dealRet(el, that, {info: "提交失败，请重新提交！"});
                    }
                });
            }
        })
        exec();
    });

//end
});

﻿(function(){
    //域名
    var _wapHost = P2PWAP.util.getWapHost('redirect_uri', 'http:\/\/(.*)\/account');
    // 验证码逻辑
    $('#JS-regpanel .JS-verifyimg').click(function() {
        $('#JS-regpanel .JS-verifyimg').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
    });
    $('#JS-regpanel .JS-verifyimg').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());

    // 点击注册时发送验证码逻辑
    var _tmpRegData = {};
    var _inMcodeRequest = false;

    var _mcoderegbtnenable = false;
    // 更新点击注册领红包按钮状态
    function _updateMcodeRegbtn() {
        var enable = !_inMcodeRequest &&
            $("#JS-regpanel .JS-input_mobile").val().trim() != "" &&
            $("#JS-regpanel .JS-input_pwd").val() != "" &&
            $("#JS-regpanel .JS-input_captcha").val().trim() != "";
        if (enable) {
            $("#JS-regpanel .JS-regbtn").removeClass("reg_finish_btn_dis");
        } else {
            $("#JS-regpanel .JS-regbtn").addClass("reg_finish_btn_dis");
        }
        _mcoderegbtnenable = enable;
    }
    $("#JS-regpanel .JS-input_mobile").bind("input", _updateMcodeRegbtn);
    $("#JS-regpanel .JS-input_pwd").bind("input", _updateMcodeRegbtn);
    $("#JS-regpanel .JS-input_captcha").bind("input", _updateMcodeRegbtn);

    $("#JS-regpanel .JS-regbtn").bind("click", function(){
        if (_inMcodeRequest == true || !_mcoderegbtnenable) return;
        var mobile = $("#JS-regpanel .JS-input_mobile").val().trim();
        var password = $("#JS-regpanel .JS-input_pwd").val();
        var captcha = $("#JS-regpanel .JS-input_captcha").val().trim();
        if (!P2PWAP.util.checkMobile(mobile)) {
            P2PWAP.ui.showErrorTip('手机号格式不正确');
            return;
        }
        if (!P2PWAP.util.checkPassword(password)) {
            P2PWAP.ui.showErrorTip('密码格式不正确，请输入6-20个字符');
            return;
        }
        if (!P2PWAP.util.checkCaptcha(captcha)) {
            P2PWAP.ui.showErrorTip('图形验证码不正确');
            return;
        }

        _tmpRegData['mobile'] = mobile;
        _tmpRegData['password'] = password;
        _tmpRegData['captcha'] = captcha;
        _tmpRegData['type'] = 1;
        var _invite = $(".JS-input_invite").val();
        if (_invite != undefined && _invite != "") {
            _tmpRegData['invite'] = _invite;
        }
        //_tmpRegData['invite'] = $(".JS-input_invite").val();
        if ($("#token_id").length > 0) {
            _tmpRegData['token_id'] = $("#token_id").val();
            _tmpRegData['token'] = $("#token").val();
        } else {
            _tmpRegData['active'] = 1;
        }
        _inMcodeRequest = true;
        $("#JS-regpanel input").attr("readonly", "true");
        _updateMcodeRegbtn();
        P2PWAP.util.ajax('/user/MCode', 'post', function(json) {
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $("#JS-regpanel input").removeAttr("readonly");
            if (json['code'] == 1) {
                _showVerifyMobileDialog();
            } else {
                _tmpRegData = {};
                P2PWAP.ui.showErrorTip(json['message']);
            }
        }, function(msg) {
            _tmpRegData = {};
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $("#JS-regpanel input").removeAttr("readonly");
            P2PWAP.ui.showErrorTip(msg);
        }, _tmpRegData);
    });

    // 验证手机框逻辑
    var _inRegRequest = false;
    var _reMcodeAjax = null;
    var _reMcodeTimer = null;
    var _finishRegbtnenable = false;
    function _updateFinishRegBtn() {
        var enable = !_inRegRequest && $("#JS-regverifypanel .JS-input_vcode").val().trim() != "";
        if (enable) {
            $("#JS-regverifypanel .JS-regbtn").removeClass("reg_finish_btn_dis");
        } else {
            $("#JS-regverifypanel .JS-regbtn").addClass("reg_finish_btn_dis");
        }
        _finishRegbtnenable = enable;
    }
    $("#JS-regverifypanel .JS-input_vcode").bind("input", _updateFinishRegBtn);

    // 重新获取验证码逻辑
    var _reMcodeBtn = $("#JS-regverifypanel .JS-mcodebtn");
    function _cleanMcodeBtn() {
        if (_reMcodeTimer == null) return;
        clearInterval(_reMcodeTimer);
        _reMcodeTimer = null;
        _reMcodeBtn.removeClass('dis_reset').text('重新发送');
    }
    function _updateMcodeBtn() {
        var timeRemained = 60;
        _reMcodeBtn.addClass('dis_reset').text(timeRemained + '秒后可重发');
        _reMcodeTimer = setInterval(function() {
            timeRemained--;
            if (timeRemained < 1) {
                _cleanMcodeBtn();
            } else {
                _reMcodeBtn.text(timeRemained + '秒后可重发');
            }
        }, 1000);
    }
    _reMcodeBtn.bind("click", function() {
        if (_reMcodeAjax != null || _reMcodeTimer != null) return;
        _reMcodeBtn.addClass('dis_reset').text('正在发送');
        _reMcodeAjax = P2PWAP.util.ajax('/user/MCode', 'post', function(obj) {
            _reMcodeAjax = null;
            if (obj.code == 1){
                _updateMcodeBtn();
            } else {
               P2PWAP.ui.showErrorTip(obj.message);
               _reMcodeBtn.removeClass('dis_reset').text('重新发送');
            }
        }, function(msg) {
            _reMcodeAjax = null;
            P2PWAP.ui.showErrorTip(msg);
            _reMcodeBtn.removeClass('dis_reset').text('重新发送');
        }, _tmpRegData);
    });
    function _showVerifyMobileDialog() {
        $("#JS-regverifypanel .JS-mobilelabel").text("已向" + _tmpRegData['mobile'] + "发送短信验证码");
        $("#JS-regverifypanel .JS-input_vcode").val("");
        _updateMcodeBtn();
        P2PWAP.ui.addModalView($("#JS-regverifypanel")[0]);
        $("#JS-regverifypanel").show();
    }
    $("#JS-regverifypanel .JS-closebtn").bind("click", function() {
        if (_inRegRequest) return;
        if (_reMcodeAjax) {
            _reMcodeAjax.abort();
            _reMcodeAjax = null;
        }
        _cleanMcodeBtn();
        P2PWAP.ui.removeModalView($("#JS-regverifypanel")[0]);
        $("#JS-regverifypanel").hide();
    });

    //注册逻辑
    $("#JS-regverifypanel .JS-regbtn").bind("click", function(){
        if (_inRegRequest || !_finishRegbtnenable) return;
        if (_reMcodeAjax != null) return;
        var vcode = $("#JS-regverifypanel .JS-input_vcode").val().trim();
        if (!P2PWAP.util.checkMcode(vcode)) {
            P2PWAP.ui.showErrorTip('请填写6位数字验证码');
            return;
        }
        _inRegRequest = true;
        _updateFinishRegBtn();

        var regdata = {};
        //添加活动附带参数
        if (window['_eventRegisterAddParams']) {
            regdata = window['_eventRegisterAddParams'];
        } else {
            regdata['cn'] = '';
        }
        regdata['mobile'] = _tmpRegData['mobile'];
        regdata['password'] = _tmpRegData['password'];
        regdata['captcha'] = _tmpRegData['captcha'];
        regdata['code'] = vcode;
        regdata['isAjax'] = 1;
        if ($(".JS-input_invite").length > 0) regdata['cn'] = $(".JS-input_invite").val().trim();
        if (typeof regdata['event_id'] == 'undefined') regdata['event_id'] = '';
        if (typeof regdata['event_data'] == 'undefined') regdata['event_data'] = '';
        var api = '';
        if (window['isMaster'] != undefined && (isMaster == false || isMaster == 0)) {
             // 分站登录逻辑，参数整理
            api = '/user/DoRegister';
            regdata['invite'] = regdata['cn'];
            regdata['agreement'] = 1;
            regdata['type'] = 'h5';
            // url参数传递
            var params = window.location.search.substr(1).split('&');
            $.each(params, function(k, v) {
                var kv = v.split('=');
                if (typeof regdata[kv[0]] == 'undefined') {
                    regdata[kv[0]] = kv[1];
                }
            })
        } else {
            api = '/user/DoH5RegisterAndLogin?client_id=db6c30dddd42e4343c82713e&response_type=code'
        }
        P2PWAP.util.ajax(api, 'post', function(json) {
            _inRegRequest = false;
            _updateFinishRegBtn();
            if (json['errorCode'] == 0) {
                //活动附带响应
                if (window['_eventRegisterCallback']) {
                   $("#JS-regverifypanel").hide();
                   window['_eventRegisterCallback'].call(null, json['data']);
                } else {
                    if (!!regdata['from_platform']) {
                        window.location.href = "http://" + _wapHost + "/oauth?code=" + json['data']['oauth_code'] + "&from_platform=" + encodeURIComponent(regdata['from_platform']);
                    } else {
                        window.location.href = "http://" + _wapHost + "/oauth?code=" + json['data']['oauth_code'];
                    }
                }
            } else {
                P2PWAP.ui.showErrorTip(json['errorMsg']);
            }
        }, function(msg) {
            _inRegRequest = false;
            _updateFinishRegBtn();
            P2PWAP.ui.showErrorTip(msg);
        }, regdata);
    });

    //注册协议
    $('#JS-regpanel .JS-regterm').click(function (event) {
        var _rootDomain = 'firstp2p.com';
        if (window['rootDomain'] && window['rootDomain'] != undefined && window['rootDomain'] != "") {
            _rootDomain = window['rootDomain'];
        }
        var _explain = '备案号为：京ICP证130046号，以下简称网信';
        if (window['explain'] && window['explain'] != undefined && window['explain'] != "") {
            _explain = window['explain'];
        }
        P2PWAP.ui.showNoticeDialog('用户协议', '<p>欢迎阅读网信（以下简称本公司）用户协议。本协议详述您在域名为' + rootDomain + '的互联网网站（' + explain + '）使用本公司所提供服务须遵守的条款和条件。 </p>\
            <p >&nbsp;</p>\
            <p >您成为网信用户前，必须阅读、同意并接受本协议中所含的所有条款和条件，包括以下明示载明的及因被提及而纳入的文件、条款和条件。本公司强烈建议：您阅读本协议时，也应阅读本协议所提及的其他网页中所包含的资料，因为其可能包含对作为网信用户的您适用的进一步条款和条件。请注意：点击划有底线的词句即可链接到相应网页。 </p>\
            <p >&nbsp;</p>\
            <p >您申请注册为网信用户，表明您已经充分阅读、理解并无任何附加条件的接受了本协议中含有的所有条款和条件，包括本协议中载明的及因被提及而纳入的所有文件、条款和条件。 </p>\
            <p >&nbsp;</p>\
            <p >一、用户资格 </p>\
            <p >&nbsp;</p>\
            <p >1.1&nbsp;本公司的服务仅向适用法律规定的能够签订有法律约束力的相关合同、协议的个人、企业及其他组织提供并由其使用。 </p>\
            <p >1.2&nbsp;尽管有前述的规定，但本公司的服务不向18周岁以下及70周岁以上的个人、没有非保本类金融产品投资的经历且不熟悉网络借贷的出借人、及被本公司临时或无限期中止的网信用户提供。 </p>\
            <p >1.3&nbsp;若您不符合本协议规定的用户资格，请您切勿使用本公司的服务。 </p>\
            <p >&nbsp;</p>\
            <p >二、用户账户和密码的使用、保管 </p>\
            <p >&nbsp;</p>\
            <p >2.1&nbsp;您的网信账户的用户名仅限于您个人使用，不得向任何第三方转让或出售。 </p>\
            <p >2.2&nbsp;您的网信用户名和密码由您自行保管，因用户名和密码泄露等导致的您的任何损失，由您自行承担。 </p>\
            <p >2.3&nbsp;凡以您的网信用户名登录实施的一切行为均视为您的行为，所产生的法律后果由您承担。 </p>\
            <p >&nbsp;</p>\
            <p >三、服务和费用 </p>\
            <p >&nbsp;</p>\
            <p >3.1&nbsp;服务 </p>\
            <p >3.1.1&nbsp;本公司所提供服务的宗旨：为所有有投、融资需求且合法的企业及个人提供信息发布与交易撮合等中介服务。 </p>\
            <p >3.1.2&nbsp;投融资双方自行确认发布的信息，本网站对于信息情况不承担任何责任；对于本公司未参与的合同双方发生的纠纷，与本公司无任何关联，本公司不承担任何法律责任。 </p>\
            <p >3.1.3&nbsp;就本公司提供的服务，须与客户签署服务协议。一切权利与义务以服务协议约定为准。 </p>\
            <p >3.1.4&nbsp;在不违反适用法律的强制性规定的前提下，本公司向您提供的服务以实际情况为准。 </p>\
            <p >3.2&nbsp;费用 </p>\
            <p >3.2.1&nbsp;如您欲通过网信投资，本公司通过网信为您注册为网信网站用户并使用本公司所提供的服务是免费的。 </p>\
            <p >3.2.2&nbsp;尽管有3.2.1条款的约定，但本公司在适用法律许可的前提下，有权自行决定是否对本公司所提供的服务收取费用及收费方式等，惟该等关于费用收取的变更将至少提前于该等变更生效之日前[ 7 ]个工作日在网信以发布公告的方式通知您，通知发布后，如您继续选择接受网信提供的服务，意味着您对该等通知内容予以认可并接受。 </p>\
            <p >3.3&nbsp;其他 </p>\
            <p >3.3.1&nbsp;您使用本公司所提供服务过程中的其他支出，包括但不限于网络使用费、通信费、资料快递费等，由您自行承担。 </p>\
            <p >3.3.2&nbsp;您因使用本公司所提供服务完成的交易而取得的收入，应当由您按照所适用法律规定承担相应的税费。 </p>\
            <p >&nbsp;</p>\
            <p >四、您的资料 </p>\
            <p >&nbsp;</p>\
            <p >4.1&nbsp;您的资料包括：您在注册、竞价或登录过程中、在任何公共信息区域（包括留言栏或反馈区）或通过任何电邮形式或手机短信向本公司或其他用户提供的任何资料。您对您的资料负全责，网信网站仅作为您在网上分发及公布您的资料的渠道。</p>\
            <p >4.2&nbsp;您的资料不得：具有欺诈性、虚假、不准确或具误导性；侵犯任何第三方著作权、专利权、商标权、商业秘密或其他专有权利或发表权或隐私权；违反任何适用的法律或法规；有侮辱或者诽谤他人，侵害他人合法权益的内容；有淫秽、色情、赌博、暴力、凶杀、恐怖或者教唆犯罪的内容；包含可能破坏、改变、删除、不利影响、秘密截取、未经授权而接触或征用任何系统、数据或个人资料的任何病毒、特洛依木马、蠕虫、定时炸弹、删除蝇、复活节彩蛋、间谍软件或其他电脑程序；直接或间接链接至您无权链接或列入的任何网址或者内容。 </p>\
            <p >4.3&nbsp;为使本公司能够使用您向本公司提交的资料，使本公司不违反您可能在该资料中拥有的任何权利之目的，您应同意向本公司授予非独占、全球性、永久、不可撤消、免使用费、可分许可（通过多层许可的方式）的权利，以行使您在您的资料中（在任何已知或目前为未知媒体中）享有的与您的资料有关的相关权利。本公司将根据《隐私权保护规则》使用您的资料。 </p>\
            <p >&nbsp;</p>\
            <p >五、信用评价系统&nbsp;您不得采取可能破坏信用评价系统真实性、完整性的任何行为。本公司有权按照《信用评价规则》中止或者终止您的用户资格，在这种情况下，您将无法登录或发布贷款或借款信息。 </p>\
            <p >&nbsp;</p>\
            <p >5.1&nbsp;您已充分理解您的信用包含其他用户留下的意见和以此为依据所编制的综合信用评价数字，每次的信用评价都应有用户意见作为作出此次评价的说明，不带用户意见的综合数字不反映您的此次完整信用评价。鉴于信用评价仅为促进用户之间的交易之目的而设计，您同意您不得将您的信用评价在网信以外的任何场所推销出售或以其他方式输出。 </p>\
            <p >5.2&nbsp;本公司不提供使您能够从其他网站向网信输入信用评价的技术能力。 </p>\
            <p >&nbsp;</p>\
            <p >六、进入和干扰 </p>\
            <p >&nbsp;</p>\
            <p >6.1&nbsp;未经本公司明示或者书面准许，您不能为了任何目的使用任何机器人、蜘蛛软件、刷屏软件或其他自动方式进入网站。 </p>\
            <p >6.2&nbsp;此外，您同意您将不会： </p>\
            <p >(i)&nbsp;进行任何对本公司内部结构造成或可能造成（按本公司自行酌情确定）不合理或不合比例的重大负荷的行为； </p>\
            <p >(ii)&nbsp;未经本公司和适当第三方（如适用）事先明示书面准许，对网站的任何内容（除您的资料以外）制作拷贝、进行复制、修改、制作衍生作品、分发或公开展示； </p>\
            <p >(iii)&nbsp;干扰或试图干扰网站的正常工作或网站上进行的任何活动； </p>\
            <p >(iv)&nbsp;越过本公司可能用于防止或限制进入网站的机器人排除探头或其他形式。 </p>\
            <p >&nbsp;</p>\
            <p >七、欺诈 </p>\
            <p >&nbsp;</p>\
            <p >在不限制所适用法律规定的或本协议约定的本公司可取得的任何其它救济的前提下，如本公司通过任何方式，包括但不限于本公司自行酌情决定的方式，怀疑您参与了与本公司及网信有关的欺诈活动，本公司可全权酌情决定中止或终止您的帐户。 </p>\
            <p >&nbsp;</p>\
            <p >八、违约 </p>\
            <p >&nbsp;</p>\
            <p >在不限制其它救济的前提下，如发生以下情形，本公司可能限制您的活动并立即删除您的贷款、借款或融资、投资需求信息，发出有关您的行为的警告、发出警告通知、暂时中止、无限期地中止或终止您的用户资格及拒绝向您提供服务： </p>\
            <p >8.1&nbsp;您违反本协议或纳入本协议的文件； </p>\
            <p >8.2&nbsp;本公司无法核证或验证您向本公司提供的任何资料； </p>\
            <p >8.3&nbsp;您的行为可能对您、本公司用户或本公司造成损失或法律责任。 </p>\
            <p >&nbsp;</p>\
            <p >九、隐私保护 </p>\
            <p >&nbsp;</p>\
            <p >网信设有适用于所有用户并纳入用户协议的《隐私权保护规则》。本公司现有的《隐私权保护规则》可从网址为' + rootDomain + '的网页获取。您在作为网信用户期间将受其规定（及本公司对隐私权保护规则作出的任何修订）的约束。 </p>\
            <p >&nbsp;</p>\
            <p >十、不保证 </p>\
            <p >&nbsp;</p>\
            <p >10.1&nbsp;本公司以&#8220;按现状&#8221;的方式提供本公司网站和服务，而不带有任何保证或条件，无论该等保证或条件是明示、默示或法定的。 </p>\
            <p >10.2&nbsp;本公司通过网信网站向用户提供的服务不提供任何形式的承诺或保证。 </p>\
            <p >10.3&nbsp;本公司不就持续地、不受影响地或安全地接触并接受本公司服务作出担保，且本公司网站的经营可能受本公司无法控制的多种外部因素影响。 </p>\
            <p >&nbsp;</p>\
            <p >十一、特别声明 </p>\
            <p >&nbsp;</p>\
            <p >11.1&nbsp;本公司并不实质性介入您与其他用户之间的交易，对此，您充分理解并认可。 </p>\
            <p >11.2&nbsp;您无任何附加条件的接受，本公司、本公司的关联公司和相关实体或本公司的服务商在任何情况下均不就因本公司的网站、本公司的服务或本协议而产生或与之有关的利润损失或任何特别、间接或直接性的损害（无论以何种方式产生，包括疏忽）承担任何责任。 </p>\
            <p >11.3&nbsp;您同意就您自身行为之合法性单独承担责任。您同意，本公司和本公司的所有关联公司和相关实体对本公司用户的行为的合法性及产生的任何结果不承担责任。 </p>\
            <p >11.4&nbsp;您同意并接受本服务条款，视同您同意并接受网信以短信、彩信和邮件形式的营销行为。 </p>\
            <p >&nbsp;</p>\
            <p >十二、补偿及责任免除 </p>\
            <p >&nbsp;</p>\
            <p >就任何第三方提出的，由于您违反本协议或纳入本协议的条款和规则或您违反任何法律或侵犯第三方的权利而产生或引起的任一种类和性质的任何索赔、要求、诉讼、损失和损害（包括直接或间接的），无论是已知或未知的，包括合理的律师费，您同意就此对本公司和（如适用）本公司的母公司、子公司、关联公司、合作伙伴、高级职员、董事、代理人和雇员进行补偿并使其免受损害。 </p>\
            <p >&nbsp;</p>\
            <p >十三、遵守法律 </p>\
            <p >&nbsp;</p>\
            <p >13.1&nbsp;您使用本公司的服务及因此所进行的交易，应当遵守所有适用的国内及国际法律、法令、条例和法规。 </p>\
            <p >13.2&nbsp;仅仅是您，而非本公司，应负责确认您使用本公司及网信所提供服务是合法的。您必须确保您遵守所有适用法律。您还必须确保您遵守本协议及纳入本协议的所有其它条款和规则的所有规定。 </p>\
            <p >&nbsp;</p>\
            <p >十四、无代理关系 </p>\
            <p >&nbsp;</p>\
            <p >用户和本公司是独立的合同方，本协议无意建立也没有创立任何代理、合伙、合营、雇员与雇主或特许经营关系。本公司也不对任何用户及其网上交易行为做出明示或默许的推荐、承诺或担保。上述性质不因任何事由而改变。 </p>\
            <p >&nbsp;</p>\
            <p >十五、通知 </p>\
            <p >&nbsp;</p>\
            <p >15.1&nbsp;除非另行明示载明，任何通知将发往您在注册过程中向本公司提供的电邮地址。或者，本公司认为适当的其他方式。 </p>\
            <p >15.2&nbsp;任何通知应视为于以下时间送达： </p>\
            <p >(i)&nbsp;如通过电邮发送，则电邮发送后即视为送达，但发送方被告知电邮地址无效的，则属例外； </p>\
            <p >(ii)&nbsp;如以预付邮资的信件发送，则投邮之日后三个营业日视为送达； </p>\
            <p >(iii)&nbsp;如寄往或寄自中国，则在投邮后第七个营业日视为送达； </p>\
            <p >(iv)&nbsp;如通过传真发送，则传真发出的该个营业日视为送达（只要发送人收到载明以上传真号码、发送页数和发送日期的确认报告）。 </p>\
            <p >&nbsp;</p>\
            <p >十六、协议的修改、补充和终止 </p>\
            <p >&nbsp;</p>\
            <p >16.1&nbsp;您充分理解，本公司无法与所有的用户就本协议及本协议所纳入或被提及的文件、条款和条件进行逐一的协商。 </p>\
            <p >16.2&nbsp;您无任何附加条件的认可，本协议及本协议所纳入或被提及的文件、条款和条件可以由本公司自行酌情做出，惟本公司应当至少提前于该等修改和补充生效日前7日，在网信公告栏内公布。 </p>\
            <p >16.3&nbsp;若您对于该等修改和补充不予接受的，您应当立即停止使用本公司的服务，而本公司亦有权终止您的用户资格。 </p>\
            <p >16.4&nbsp;本协议自您注销您的用户账户的申请被本公司审核通过之日，或者本公司依据本协议及本协议所纳入或被提及的文件、条款和条件终止您的用户资格之日终止。 </p>\
            <p >&nbsp;</p>\
            <p >十七、一般规定 </p>\
            <p >&nbsp;</p>\
            <p >17.1&nbsp;本协议在所有方面均受中华人民共和国法律管辖。 </p>\
            <p >17.2&nbsp;任何争议，如协商不能解决，均应提交本公司注册地有管辖权的法院诉讼解决。 </p>\
            <p >17.3&nbsp;本协议的规定是可分割的，如本协议任何规定被裁定为无效或不可执行，该规定可被删除而其余条款应予以执行。 </p>\
            <p >17.4&nbsp;您同意，在发生并购时，本公司在本协议和所有纳入协议的条款和规则项下的所有或者部分权利和义务，可由本公司自行酌情决定向第三方自动转让。 </p>\
            <p >17.5&nbsp;标题仅为参考之用，在任何方面均不界定、限制、解释或描述该条的范围或限度。 </p>\
            <p >17.6&nbsp;本公司未就您或其他方的违约采取行动并不等于本公司放弃就随后或类似的违约采取行动的权利。 </p>\
            <p >17.7&nbsp;您同意，本协议不得仅由于系本公司制订而以对本公司不利的方式予以解释。 </p>\
            <p >17.8&nbsp;本协议和本协议所含条款和条件载明我们双方之间就本协议标注的全部予以理解和协议。 </p>\
            <p >17.9&nbsp;本协议在您提交的注册为网信用户的申请获得本公司审核通过时生效。 </p>\
            <p >17.10&nbsp;第4.3条、第六条、第九条、第十一条和第十二条在本协议终止后继续有效。 </p>');
    });
})();

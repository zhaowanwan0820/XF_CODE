function account(user_id)
{
    $.weeboxs.open(ROOT+'?m=User&a=account&id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_ACCOUNT'],width:600,height:260,onopen: function(){forms_lock();}});
}
function account_detail(user_id)
{
    location.href = ROOT + '?m=User&a=account_detail&id='+user_id;
}
function account_detail_supervision(user_id)
{
    location.href = ROOT + '?m=User&a=account_detail_supervision&id='+user_id;
}
function user_work(user_id)
{
    $.weeboxs.open(ROOT+'?m=User&a=work&id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_WORK'],width:600,height:400,onopen: function(){forms_lock();}});
}
function user_passport_passed(user_id)
{
    $.ajax({
        type: "POST",
        url: ROOT+'?m=User&a=passport_info',
        data: "id="+user_id,
        dataType:"json",
        success: function(data){
            var html = '';
            html += '<a href="###"  class="CreditEdit" onclick="opcredit(\'idcardpassed\',\'' + data.user_info.id + '\')">操作</a><br><br>';
            html += '证件归属地:' + data.passport.region + '<br>';
            html += '姓名:' + data.passport.name + '<br>';
            html += '通行证号码:' + data.passport.passportid + '<br>';
            html += '通行证有效期至:' + data.passport.valid_date + '<br>';
            html += '通行证正面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
            html += '<a class="img" href="' + data.passport.file.pass1 + '" target="_blank" style="display:none;">';
            html += '<img src="' + data.passport.file.pass1 + '" border="0" width="370"></a>';
            html += '通行证反面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
            html += '<a class="img" href="' + data.passport.file.pass2 + '" target="_blank" style="display:none;">';
            html += '<img src="' + data.passport.file.pass2 + '" border="0" width="370"></a>';
            html += '<br>';
            if (data.passport.sex == 0) {
                html += '性别:女<br>';
            } else {
                html += '性别:男<br>';
            }
            html += '出生日期:' + data.passport.birthday + '<br>';
            html += '身份证号:' + data.passport.idno + '<br>';
            html += '身份证正面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
            html += '<a class="img" href="' + data.passport.file.idno1 + '" target="_blank" style="display:none;">';
            html += '<img src="' + data.passport.file.idno1 + '" border="0" width="370"></a>';
            html += '身份证反面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
            html += '<a class="img" href="' + data.passport.file.idno2 + '" target="_blank" style="display:none;">';
            html += '<img src="' + data.passport.file.idno2 + '" border="0" width="370"></a>';
            if (data.user_info.idcardpassed != 1 && data.credit_file != null && data.credit_file.credit_identificationscanning.file != '') {
                html += '<a href="###"  class="CreditEdit" onclick="opcredit(\'idcardpassed\',\'' + data.user_info.id + '\')">操作</a><div class="blank5"></div><br><br><hr>';
            }
            html += '姓名:' + data.user_info.real_name + '<br>';
            html += '身份证号码:';
            if (data.user_info.idno != null) {
                html += data.user_info.idno;
            }
            html += '&nbsp;<a href="javascript:void(0)" onclick="checkidcrad(\'' + data.user_info.idno + '\')">查证</a><br>';
            html += '籍贯:';
            if (data.user_info.n_province != null) {
                html += data.user_info.n_province;
            }
            html += '&nbsp;';
            if (data.user_info.n_city != null) {
                html += data.user_info.n_city;
            }
            html += '<br>';
            html += '户口所在地:';
            if (data.user_info.province != null) {
                html += data.user_info.province;
            }
            html += '&nbsp;';
            if (data.user_info.city != null) {
                html += data.user_info.city;
            }
            html += '<br>';
            html += '出生日期:';
            if (data.user_info.byear != null) {
                html += data.user_info.byear;
            }
            html += '-';
            if (data.user_info.bmonth != null) {
                html += data.user_info.bmonth;
            }
            html += '-';
            if (data.user_info.bday != null) {
                html += data.user_info.bday;
            }
            html += '<br>';
            if (data.user_info.sex == 0) {
                html += '性别:女<br>';
            } else {
                html += '性别:男<br>';
            }
            $.weeboxs.open(html, {contentType:'html',showButton:false,title:'身份认证',width:400,height:400});
        }
    });
}

function special_user_passed(user_id)
{
    $.ajax({
        type: "POST",
        url: ROOT+'?m=User&a=passport_info',
        data: "id="+user_id,
        dataType:"json",
        success: function(data){
            var html = '';
            html += '<a href="###"  class="CreditEdit" onclick="opcredit(\'idcardpassed\',\'' + data.user_info.id + '\')">操作</a><br><br>';
            html += '证件类型:' + data.passport.typedesc + '<br>';
            html += '姓名:' + data.passport.name + '<br>';
            if (data.passport.sex == 0) {
                html += '性别:女<br>';
            } else {
                html += '性别:男<br>';
            }
            if (data.passport.type == 2) {
                html += '护照号码:' + data.passport.passportid + '<br>';
                html += '护照个人信息页正面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.idno1 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.idno1 + '" border="0" width="370"></a>';
                html += '本人手持护照正面照片:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.idno2 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.idno2 + '" border="0" width="370"></a>';
                html += '签证照片:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.pass1 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.pass1 + '" border="0" width="370"></a>';
                html += '盖有入境章的护照页:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.pass2 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.pass2 + '" border="0" width="370"></a>';
            } else if(data.passport.type == 3){
                html += '身份证号码:' + data.passport.idno + '<br>';
                html += '身份证正面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.pass1 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.idno1 + '" border="0" width="370"></a>';
                html += '身份证反面:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.pass2 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.idno2 + '" border="0" width="370"></a>';
                html += '军官证内页:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.idno1 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.pass1 + '" border="0" width="370"></a>';
                html += '手持证件照片:&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>';
                html += '<a class="img" href="' + data.passport.file.idno2 + '" target="_blank" style="display:none;">';
                html += '<img src="' + data.passport.file.pass2 + '" border="0" width="370"></a>';
            }else {
            }
            $.weeboxs.open(html, {contentType:'html',showButton:false,title:'身份认证',width:400,height:400});
        }
    });
}

function  user_passed(user_id) {
    window.location.href = ROOT+'?m=User&a=passed&id='+user_id;
}

function eidt_lock_money(user_id){
    $.weeboxs.open(ROOT+'?m=User&a=lock_money&id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_LOCK_MONEY'],width:600,height:400,onopen: function(){forms_lock();}});
}
//联系人列表  add by wenyaneli  20130627
function contact(user_id)
{
    $.weeboxs.open(ROOT+'?m=User&a=contact&id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_CONTACT'],width:500,height:550,onopen: function(){forms_lock();}});
}
//	用户 机构 修改
function user_company(user_id){
    window.open(ROOT+'?m=UserCompany&a=companyShow&user_id='+user_id);
    //$.weeboxs.open(ROOT+'?m=UserCompany&a=companyShow&user_id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_COMPANY'],width:600,height:500});
}

function money_transfer(user_id){
    $.weeboxs.open(ROOT+'?m=User&a=money_transfer&id='+user_id, {contentType:'ajax',showButton:false,title:'转账',width:450,height:300,onopen: function(){forms_lock();}});
}
function money_transfer_detail(out_id, into_id, money, info){
    $.weeboxs.close();
    $.weeboxs.open(ROOT+'?m=User&a=money_transfer_detail&id='+out_id+'&user_id='+into_id+'&money='+money+'&info='+encodeURIComponent(info), {contentType:'ajax',showButton:false,title:'转账详情',width:450,height:450,onopen: function(){forms_lock();}});
}
function supervision_transfer(user_id){
    $.weeboxs.open(ROOT+'?m=Supervision&a=transfer&id='+user_id, {contentType:'ajax',showButton:false,title:'余额划转',width:450,height:300,onopen: function(){forms_lock();}});
}
// 重置密码
function edit_password(user_id){
    $.weeboxs.open(ROOT+'?m=User&a=edit_password&id='+user_id, {contentType:'ajax',showButton:false,title:'重置密码',width:450,height:180,onopen: function(){forms_lock();}});
}
// 授权展示
function view_account_auth(user_id){
    $.weeboxs.open(ROOT+'?m=User&a=account_auth&id='+user_id, {contentType:'ajax',showButton:false,title:'授权展示',width:450,height:180,onopen: function(){forms_lock();}});
}

function forms_lock() {
    var forms = $('form[name=edit]');
    forms.each(function(i, el){
        var btn = $(el).find('input[type=submit]');
        var ret = false;
        //console.log('btn', btn);
        //删除行内onclick事件
        btn.attr('onclick', '');
        btn.click(function(){
            var ele = $(this);
            ele.css({"color":"gray","background":"#cccccc"}).attr("disabled","disabled");
            ret = confirm("确定此操作吗？");
            ele.css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            return ret;
        });
    })
}
//客服查询明细
function custServInquir_detail(user_id){
    location.href = ROOT + '?m=User&a=custServInquir_detail&id='+user_id;
}

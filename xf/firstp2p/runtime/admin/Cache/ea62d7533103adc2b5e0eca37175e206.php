<?php if (!defined('THINK_PATH')) exit();?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/style.css" />
<script type="text/javascript">
 	var VAR_MODULE = "<?php echo conf("VAR_MODULE");?>";
	var VAR_ACTION = "<?php echo conf("VAR_ACTION");?>";
	var MODULE_NAME	=	'<?php echo MODULE_NAME; ?>';
	var ACTION_NAME	=	'<?php echo ACTION_NAME; ?>';
	var ROOT = '__APP__';
	var ROOT_PATH = '<?php echo APP_ROOT; ?>';
	var CURRENT_URL = '<?php echo trim($_SERVER['REQUEST_URI']);?>';
	var INPUT_KEY_PLEASE = "<?php echo L("INPUT_KEY_PLEASE");?>";
	var TMPL = '__TMPL__';
	var APP_ROOT = '<?php echo APP_ROOT; ?>';
    var IMAGE_SIZE_LIMIT = '1';
</script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.timer.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/script.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/lang.js"></script>
<script type='text/javascript'  src='__ROOT__/static/admin/kindeditor/kindeditor.js'></script>
</head>
<body>
<div id="info"></div>

<script type="text/javascript" src="__TMPL__Common/js/user_edit.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>

<script type="text/javascript" src="__TMPL__ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/lang/zh-cn/zh-cn.js"></script>


<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript" src="__TMPL__widget/mulselect/cityData.js"></script>
<script type="text/javascript" src="__TMPL__widget/mulselect/mulselect.v1.js"></script>

<script type="text/javascript" src="__TMPL__chosen/js/chosen.jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__chosen/css/chosen.min.css" />

<?php function getUserSite($siteid)
    {
        $sitename = array_search($siteid,$GLOBALS['sys_config']['TEMPLATE_LIST']);
        if($sitename)
        {
            return $sitename;
        }
        else
        {
            return '?????????';
        }
    } ?>
<script type="text/javascript" src="//static.firstp2p.com/attachment/region.js?v=<?php echo app_conf('APP_SUB_VER'); ?>"></script>
<div class="main">
<div class="main_title"><?php echo L("EDIT");?> <a href="<?php echo u("User/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit"  id="Jcarry_From_2" action="__APP__" method="post" enctype="multipart/form-data">

<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>???????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">?????????:</td>
        <td class="item_input">
            <input type="hidden" id="_js_bankinfo_id" value="<?php echo ($bankcard_info["id"]); ?>">
            <input type="hidden" id="_js_bankinfo_uid" value="<?php echo ($bankcard_info["user_id"]); ?>">
            <input type="hidden" id="_js_bankinfo_bankcard" value="<?php echo ($bankcard_info["bankcard"]); ?>">
            <input type="text" name="bank_card_name" class="textbox _js_bankinfo" value="<?php echo ($bankcard_info["card_name"]); ?>" />&nbsp;<button id="_js_reset_bankinfo">???????????????</button>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            ?????????
        </td>
    </tr>
    <tr>
        <td class="item_title">???????????????:</td>
        <td class="item_input">
        <select name="card_type" class="_js_bankinfo">
                <?php if(is_array($cardTypes)): foreach($cardTypes as $key=>$item): ?><option <?php if($item['id'] == $bankcard_info['card_type']): ?>selected="selected"<?php endif; ?> value="<?php echo ($item["id"]); ?>"><?php echo ($item["card_type_name"]); ?></option><?php endforeach; endif; ?>
            </select>

        </td>
    </tr>
    <tr>

        <td class="item_title">??????:</td>
        <td class="item_input">
            <select name="bank_id" class="_js_bankinfo">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($bank_list)): foreach($bank_list as $key=>$item): ?><option <?php if($item['id'] == $bankcard_info['bank_id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($item["id"]); ?>"><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????????????????:</td>
        <td class="item_input">
            <input type="hidden" value="<?php echo ($bankcard_info["region_lv1"]); ?>" id="deflv1">
                <input type="hidden" value="<?php echo ($bankcard_info["region_lv2"]); ?>" id="deflv2">
                <input type="hidden" value="<?php echo ($bankcard_info["region_lv3"]); ?>" id="deflv3">
                <input type="hidden" value="<?php echo ($bankcard_info["region_lv4"]); ?>" id="deflv4">
                <select name="c_region_lv1" class="_js_bankinfo">
                    <option value="0">=<?php echo L("REGION_LV1");?>=</option>
                    <?php if(is_array($n_region_lv1)): foreach($n_region_lv1 as $key=>$lv1): ?><option <?php if($bankcard_info['region_lv1'] == $lv1['id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($lv1["id"]); ?>"><?php echo ($lv1["name"]); ?></option><?php endforeach; endif; ?>
                </select>

                <select name="c_region_lv2" class="_js_bankinfo">
                    <option value="0">=<?php echo L("REGION_LV2");?>=</option>
                </select>

                <select name="c_region_lv3" class="_js_bankinfo">
                    <option value="0">=<?php echo L("REGION_LV3");?>=</option>
                </select>
                <select name="c_region_lv4" id="Jcarry_region_lv4" class="_js_bankinfo">
                    <option value="0">=<?php echo L("REGION_LV4");?>=</option>
                </select>
        </td>
    </tr>
    <script type="text/javascript">
            $(document).ready(function(){
                $("select[name='c_region_lv1']").bind("change",function(){
                    load_select("1");
                });
                $("select[name='c_region_lv2']").bind("change",function(){
                    load_select("2");
                    clear_bank_site();
                });
                $("select[name='c_region_lv3']").bind("change",function(){
                    load_select("3");
                    bank_site();
                });
                $("select[name='c_region_lv4']").bind("change",function(){
                    load_select("4");

                });

                // init region
                var devlv1Option = $("select[name='c_region_lv1'] option[value='" + $("#deflv1").val() + "']")[0];
                if (devlv1Option) {
                    devlv1Option.selected = true;
                    load_select("1");
                    var devlv2Option = $("select[name='c_region_lv2'] option[value='" + $("#deflv2").val() + "']")[0];
                    if (devlv2Option) {
                        devlv2Option.selected = true;
                        load_select("2");
                        var devlv3Option = $("select[name='c_region_lv3'] option[value='" + $("#deflv3").val() + "']")[0];
                        if (devlv3Option) {
                            devlv3Option.selected = true;
                            load_select("3");
                            var devlv4Option = $("select[name='c_region_lv4'] option[value='" + $("#deflv4").val() + "']")[0];
                            if (devlv4Option) {
                                devlv4Option.selected = true;
                            }
                        }
                    }
                }
            });
            function load_select(lv)
            {
                if (lv == '0')
                {
                    lv = '1';
                }
                var name = "c_region_lv"+lv;
                var next_name = "c_region_lv"+(parseInt(lv)+1);
                var id = $("select[name='"+name+"']").val();

                if(lv==1)
                var evalStr="regionConf.r"+id+".c";
                if(lv==2)
                var evalStr="regionConf.r"+$("select[name='c_region_lv1']").val()+".c.r"+id+".c";
                if(lv==3)
                var evalStr="regionConf.r"+$("select[name='c_region_lv1']").val()+".c.r"+$("select[name='c_region_lv2']").val()+".c.r"+id+".c";

                if(id==0)
                {
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                }
                else
                {
                    var regionConfs=eval(evalStr);
                    evalStr+=".";
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                    for(var key in regionConfs)
                    {
                        html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
                    }
                }
                $("select[name='"+next_name+"']").html(html);
            }
        </script>
    <tr>
        <td class="item_title">????????????:</td>
        <td id="_js_bank_site">
            <select id="bankIssueName" name="bank_bankzone" readonly="readonly" data-placeholder="?????????????????????">
              <option value="<?php echo ($bankcard_info["bankzone"]); ?>"><?php echo ($bankcard_info["bankzone"]); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">?????????:</td>
        <td><input type="text" id="branch_no" name="branch_no" class="textbox _js_bankinfo" style="width:200px;" value="<?php if(!empty($bankcard_info['branch_no'])){echo $bankcard_info['branch_no'];}?>" readonly="readonly" /><span id="bankIssue"></span></td>
    </tr>

    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <input type="text" id="bank_bankcard" name="bank_bankcard" class="textbox _js_bankinfo" style="width:200px;" value="<?php echo ($bankcard_info["bankcard"]); ?>" />
        </td>
    </tr>

    <tr>
        <td class="item_title">????????????</td>
        <td class="item_input">
            <span id='status_tips'><?php if(!empty($bankcard_info) && intval($bankcard_info['status']) === 1){echo '?????????';}else{echo '?????????';}?></span>
            <input type="hidden" id="status" class="textbox _js_bankinfo" value="<?php echo ($bankcard_info["status"]); ?>" />
            <button id='_js_reset_status'>??????????????????</button>
        </td>

    </tr>
    <tr>
        <td class="item_title">?????????????????????</td>
        <td class="item_input">
            <span id='verify_status_tips'><?php if(!empty($bankcard_info) && intval($bankcard_info['verify_status']) === 1){echo '?????????';}else{echo '?????????';}?></span>
            <input type="hidden" id="verify_status" class="textbox _js_bankinfo" value="<?php echo ($bankcard_info["verify_status"]); ?>" />
            <button id='_js_reset_verify_status'>???????????????????????????</button>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????</td>
        <td class="item_input">
            <span id='cert_status_tips'>
                <?php
                    if ($bankcard_info['cert_status'] == 1) {
                        echo 'IVR????????????';
                    } else if ($bankcard_info['cert_status'] == 2) {
                        echo '????????????(???????????????)';
                    } else if ($bankcard_info['cert_status'] == 3) {
                        echo '????????????';
                    } else if ($bankcard_info['cert_status'] == 4) {
                        echo '?????????';
                    } else if ($bankcard_info['cert_status'] == 5) {
                        echo '????????????';
                    } else if ($bankcard_info['cert_status'] == 6) {
                        echo '????????????';
                    } else if ($bankcard_info['cert_status'] == 7) {
                        echo '????????????';
                    } else if ($bankcard_info['cert_status'] == 8) {
                        echo '?????????';
                    } else if ($bankcard_info['cert_status'] == 9) {
                        echo '?????????????????????';
                    } else {
                        echo $bankcard_info['cert_status'];
                    }
                ?>
            </span>
            <a href="/m.php?m=User&a=balance&uid=<?php echo ($bankcard_info["user_id"]); ?>">????????????????????????</a>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????????????????</td>
        <td class="item_input">
            <span><?php if(!empty($bankcard_info) && intval($bankcard_info['unitebank_state']) === 1){echo '?????????';}else{echo '?????????';}?></span>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????</td>
        <td class="item_input">
            <span><?php if(!empty($bankcard_info['e_account'])){echo $bankcard_info['e_account'];}else{echo '?????????';}?></span>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????????????????</td>
        <td class="item_input">
            <span><?php if(!empty($bankcard_info['p_account'])){echo $bankcard_info['p_account'];}else{echo '?????????';}?></span>
        </td>
    </tr>

    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>

<div class="blank5"></div>
    <table class="form" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan=2 class="topTd"></td>
        </tr>
        <tr>
            <td class="item_title"></td>
            <td class="item_input">
            <!--????????????-->
            <input type="hidden" name="id" value="<?php echo ($bankcard_info["user_id"]); ?>" />
            <input type="hidden" name="bankcard_id" id='bankcard_id' value="<?php echo ($bankcard_info["id"]); ?>" />
            <input type="hidden" name="edit_type" id="edit_type" value="1" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="User" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="do_edit_bank" />
            <!--????????????-->
            <input type="button" id="updateButtonIdSupervision" class="button" value="?????????????????????" onclick='return checkInchargeForm(2);'/>
            <input type="button" id="updateButtonId" class="button" value="<?php echo L("EDIT");?>" onclick='return checkInchargeForm(1);'/>
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
            </td>
        </tr>
        <tr>
            <td colspan=2 class="bottomTd"></td>
        </tr>
    </table>
</form>
</div>
<script type="text/javascript">

    </script>

<script type="text/javascript">
jQuery(function(){
    setTimeout("bank_site('<?php echo addslashes($bankcard_info['bankzone']); ?>');",1000);
    //?????????????????????
    $("#_js_reset_bankinfo").click(function(){
        var id = $("#_js_bankinfo_id").val();
        var uid = $("#_js_bankinfo_uid").val();
        var bankcard = $('#_js_bankinfo_bankcard').val();
        $("._js_bankinfo").val("");
        if(id>0 && uid>0 && bankcard){
            $.ajax({
                  type: "POST",
                  url: ROOT+'?m=User&a=resetbank',
                  data: "id="+id+"&uid="+uid+"&bankcard="+bankcard,
                  dataType:"json",
                  success: function(msg){
                      if(msg.code != '0000') {
                        alert('?????????????????????');
                        location.reload();
                      }
                  }
               });
        }
        return false;
    });

    //??????????????????
    $("#_js_reset_status").click(function(){
        var id = $("#_js_bankinfo_id").val();
        var uid = $('#_js_bankinfo_uid').val();
        var status = $('#status').val();
        if(id == 0){
            alert("?????????????????????");
            return false;
        }
           if(id>0 ){
               $.ajax({
                      type: "POST",
                      url: ROOT+'?m=User&a=resetStatus',
                      data: "id="+id+"&status="+status+"&uid="+uid,
                      dataType:"json",
                      success: function(msg){
                        if (msg.code !== '0000')
                        {
                            return alert(msg.msg);
                        }
                        $('#status').val(msg.msg);
                        var status_tips = msg.msg == 0 ? '?????????': '?????????';
                        $('#status_tips').text(status_tips);
                      }
               });
           }
        return false;
    });

    //???????????????????????????
    $("#_js_reset_verify_status").click(function(){
        var id = $("#_js_bankinfo_id").val();
        var uid = $('#_js_bankinfo_uid').val();
        var status = $('#verify_status').val();
        if(id == 0){
            alert("?????????????????????");
            return false;
        }
           if(id>0 ){
               $.ajax({
                      type: "POST",
                      url: ROOT+'?m=User&a=resetVerifyStatus',
                      data: "id="+id+"&verify_status="+status+"&uid="+uid,
                      dataType:"json",
                      success: function(msg){
                        if (msg.code !== '0000')
                        {
                            return alert(msg.msg);
                        }
                        $('#verify_status').val(msg.msg);
                        var status_tips = msg.msg == 0 ? '?????????': '?????????';
                        $('#verify_status_tips').text(status_tips);
                      }
               });
           }
        return false;
    });


    //????????????
    $("select[name='bank_id']").bind("change",function(){
        bank_site();
    });
    //????????????
    $("#_js_shoudong").live("click",function(){
       //$("#_js_shoudong_input").show();
    });

    $("#_js_bankone").live("change",function(){
        $("#_js_shoudong_input").val('');
        $("#_js_shoudong_input").hide();
    });

});
 //??????????????????
UE.getEditor('editor');

//??????????????????
function clear_bank_site() {
    $("#_js_bank_site").html('<input name="bank_bankzone" value="" readonly="" />');
    qIssue();
}

//????????????
function bank_site(bankzone = ''){
    var c = $("select[name='c_region_lv3']").find("option:selected").text();
    var p = $("select[name='c_region_lv2']").find("option:selected").text();
    var b = $("select[name='bank_id']").find("option:selected").text();
    var data = {c:c,p:p};

    $.getJSON("<?php echo ($wxlc_domain); ?>/api/banklist?c="+c+"&p="+p+"&b="+b+"&jsonpCallback=?",function(rs){
        $("#_js_bank_site").html(rs);
        $("input[name='bank_bankzone']").attr("readonly","readonly");
        $('#_js_bankone').val(bankzone);
        qIssue($('#_js_bankone').val());
        //?????????????????????
        $("#_js_bankone").chosen();
    });
}

function checkParams() {

    var idno = $("input[name='idno']").val();
    var id_type = $("#id_type").val();

    if (idno == '' || idno == 'undefined') {
        alert("????????????????????????!");
    }

    if (id_type == 1) {
        if (!/(^\d{15}$)|(^\d{17}([0-9]|X)$)/.test(idno)) {
            alert("????????????????????????");
            return false;
        }
    }


    if (id_type == 4) {
        var patt=new RegExp(/^[A-Z][A-Z]?\d{6}(\((\d|[A-Z])\))?$/);
        if (!patt.test(idno)) {
            alert("??????????????????????????????");
            return false;
        }
    }

    if (id_type == 5) {
        var patt = new RegExp(/^\d{7}\(\d\)$/);
        if (!patt.test(idno)) {
            alert("??????????????????????????????");
            return false;
        }
    }

    if (id_type == 6) {
        if (!/^[A-Z]\d{9}$/.test(idno)) {
            alert("??????????????????????????????");
            return false;
        }
    }

    return true;
}

$('#_js_bankone').live('change', function(){
    qIssue($(this).val());
});


function qIssue(issueName)
{
    if (issueName == '') {
        $('#branch_no').val('');
        $('#bankIssue').html('<span style="color:red;">???????????????????????????</span>');
        return;
    }
    $.getJSON('/m.php?m=BankList&a=qIssue&bankIssueName='+encodeURI(issueName), function(data){
        if (data.code == 0){
            $('#branch_no').val(data.issue);
            $('#bankIssue').html('');
        }else{
            $('#branch_no').val('');
            $('#bankIssue').html('<span style="color:red;">???????????????????????????</span>');
        }
    });
}

qIssue('<?php echo ($bankcard_info["bankzone"]); ?>');
//?????????????????????
$("#bankIssueName").chosen();
//????????????
function checkInchargeForm(type)
{
    $("#edit_type").val(type);
    if($.trim($("#bank_bankcard").val()) == '')
    {
        alert("????????????????????????");
        $("#bank_bankcard").focus();
        return false;
    }
    $("form").submit();
    return true;
}
</script>
<!--logId:<?php echo \libs\utils\Logger::getLogId(); ?>-->

<script>
jQuery.browser={};
(function(){
    jQuery.browser.msie=false;
    jQuery.browser.version=0;
    if(navigator.userAgent.match(/MSIE ([0-9]+)./)){
        jQuery.browser.msie=true;
        jQuery.browser.version=RegExp.$1;}
})();
</script>

</body>
</html>
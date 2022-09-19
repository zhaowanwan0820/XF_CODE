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

<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script>
//补发合同
/* var bflock = false;

function re_contract(id, btn) {
    $(btn).css({ "color": "gray", "background": "#ccc" }).attr("disabled", "disabled");
    if (!id) {
        alert('操作有误');
        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
        return false;
    }
    if (!bflock) {
        bflock = true;
        if (confirm('确认操作？')) {
            $.ajax({
                url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=re_contract&id=" + id,
                data: "ajax=1",
                dataType: "json",
                success: function(obj) {
                    bflock = false;
                    $("#info").html(obj.info);
                    if (obj.status == 1) {
                        location.href = location.href;
                    }
                }
            });
        } else {
             bflock = false;
        }
    } else {
        alert("请不要重复操作");
    }
    $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
} */

function export_contract()
{
    idBox = $(".key:checked");

    var param = '';
    if(idBox.length == 0){
        idBox = $(".key");
    }

    idArray = new Array();
    $.each( idBox, function(i, n){
        idArray.push($(n).val());
    });

    if(idArray.length == 0){
        alert('无可导出的数据！');
        return false;
    }

    id = idArray.join(",");

    var inputs = $(".search_row").find("input");

    for(i=0; i<inputs.length; i++){
        if(inputs[i].name != 'm' && inputs[i].name != 'a')
        param += "&"+inputs[i].name+"="+$(inputs[i]).val();
    }

    var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=export_all&id="+id;
    window.location.href = url+param;
}
function agree_contract(id, uid) {
    if (!id) {
        alert('操作有误');
        return false;
    }
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=agree&id="+id+"&uid="+uid,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.status==1){
                location.href=location.href;
            } else {
                alert(obj.info);
                return false;
            }
        }
    });
}

function agree_all(id, type) {
    if (!confirm('确认操作？')) {
        return false;
    }
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=agree_all&id="+id+"&type="+type,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.status==1){
                location.href=location.href;
            } else {
                alert(obj.info);
                return false;
            }
        }
    });
}

function recreate(id) {
    if (!confirm('确认操作？')) {
        return false;
    }
    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=recreate&id="+id,
        data: "ajax=1",
        dataType: "json",
        success: function(obj){
            if(obj.status==1){
                location.href=location.href;
            } else {
                alert(obj.info);
                return false;
            }
        }
    });
}
</script>
<div class="main">
    <div class="main_title">合同管理

    </div>
    <div class="blank5"></div>
    <div class="button_row">
    <input type="button" class="button" value="<?php echo L("FOREVERDEL");?>" onclick="foreverdel();" />
    <?php if($deal_id > 0): ?><input type="button" class="button" value="一键签署" onclick="agree_all(<?php echo ($deal_id); ?>, 0);" />
         <input type="button" class="button" value="代借款人签署" onclick="agree_all(<?php echo ($deal_id); ?>, 1);" />
         <input type="button" class="button" value="代担保公司签署" onclick="agree_all(<?php echo ($deal_id); ?>, 2);" />
         <input type="button" class="button" value="一键重生(慎用)" onclick="recreate(<?php echo ($deal_id); ?>);" /><?php endif; ?>
    <input type="button" class="button" value="导出" onclick="export_contract();" />
    </div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
                            合同id：<input type="text" class="textbox" name="cid" value="<?php echo trim($_REQUEST['cid']);?>" size="4"/>
                            合同标题：<input type="text" class="textbox" name="cname" value="<?php echo trim($_REQUEST['cname']);?>" size="8"/>
                            合同编号：<input type="text" class="textbox" name="cnum" value="<?php echo trim($_REQUEST['cnum']);?>" size="15"/>
                            用户姓名：<input type="text" class="textbox" name="cuser_name" value="<?php echo trim($_REQUEST['cuser_name']);?>" size="8"/>
                            用户id：<input type="text" class="textbox" name="cuser_id" value="<?php echo trim($_REQUEST['cuser_id']);?>" size="4"/>
                            借款id：<input type="text" class="textbox" name="deal_id" value="<?php echo trim($_REQUEST['deal_id']);?>" size="4"/>

            <!-- <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" /> -->
            <input type="hidden" value="Contract" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        </form>
    </div>
    <div class="blank5"></div>
    <!-- Think 系统列表组件开始 -->
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="20" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <th width="8"><input type="checkbox" id="check"
                onclick="CheckAll('dataTable')"></th>
            <th width="20px"><a
                href="javascript:sortBy('id','1','Contract','index')" title="按照编号升序排列 ">id</a></th>
            <th>合同标题</th>
            <th><a href="javascript:sortBy('number','1','Contract','index')">合同编号 </a></th>
            <th>角色</th>
            <th>用户姓名</th>
            <th>预签状态</th>
            <th>签署状态</th>
            <th>签署时间</th>
            <th>二次签署</th>
            <th>二次签署状态</th>
            <th>二次签署时间</th>
            <th width='30px'><a href="javascript:sortBy('deal_id','1','Contract','index')">借款id</a></th>
            <th width='50px'><a href="javascript:sortBy('create_time','1','Contract','index')">创建时间 </a></th>
            <th><a href="javascript:sortBy('is_send','1','Contract','index')">发送状态 </a></th>
            <th width='35px'><a href="javascript:sortBy('status','1','Contract','index')">合同状态</a></th>
            <th>投资金额</th>
            <th>状态</th>
            <th width='148px'>操作</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
            <td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>" sign = "<?php echo ($item["is_have_sign"]); ?>"></td>
            <td><?php echo ($item["id"]); ?></td>
            <td><a href='javascript:void(0)' onclick='opencontract(<?php echo ($item["id"]); ?>);'><?php echo ($item["title"]); ?></a></td>
            <td><?php echo ($item["number"]); ?></td>
            <td><?php echo ($item["usertype"]["name"]); ?></td>
            <td>
            <?php if($item["agency_id"] > 0): ?><a href='/m.php?m=DealAgency&a=index&id=<?php echo ($item["agency_id"]); ?>' target='_blank'><?php echo ($item["user_name"]); ?></a>
            <?php else: ?>
                 <a href='/m.php?m=User&a=index&user_id=<?php echo ($item["user_id"]); ?>' target='_blank'><?php echo ($item["user_name"]); ?></a><?php endif; ?></td>

            <td><?php if($item["agency_id"] == 0): ?>已预签<?php else: ?>--<?php endif; ?></td>

            <td>
            <?php if($item["agency_id"] > 0): ?><?php foreach($item['agency'] as $aitem){ ?>
                     <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["user_name"]); ?></a> <?php echo ($aitem["sign_info"]); ?><br />
                 <?php } ?>
            <?php else: ?>
                 <?php echo ($item["sign_info"]); ?><?php endif; ?>
            </td>

            <td>
            <?php if($item["agency_id"] > 0): ?><?php foreach($item['agency'] as $aitem){ ?>
                     <?php if($aitem["contract_time"] > 0): ?><?php echo ($aitem["user_name"]); ?> [<?php echo date("Y-m-d H:i:s", $aitem['contract_time']); ?>]<br/><?php endif; ?>
                 <?php } ?>
            <?php else: ?>
                 <?php if($item["contract_time"] > 0): ?><?php echo date("Y-m-d H:i:s", $item['contract_time']); ?><?php endif; ?><?php endif; ?>
            </td>

            <!-- 设置二次签署 -->
            <td>
                <input type='checkbox' name='is_needsign' conid='<?php echo ($item["id"]); ?>' <?php if($item["is_needsign"] == 1): ?>checked="checked"<?php endif; ?> onchange='change_needsign($(this))'>设置
            </td>

            <!-- 单独二次签署 -->
            <td>
            <?php if($item["agency_id"] > 0): ?><?php foreach($item['agency_alone_sign'] as $aitem){ ?>
                     <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["user_name"]); ?></a> <?php echo ($aitem["sign_info"]); ?><br />
                 <?php } ?>
            <?php else: ?>
                 <?php echo ($item["alone_sign_info"]); ?><?php endif; ?>
            </td>

            <!-- 单独二次签署 -->
            <td>
            <?php if($item["agency_id"] > 0): ?><?php foreach($item['agency_alone_sign'] as $aitem){ ?>
                     <?php if($aitem["contract_time"] > 0): ?><?php echo ($aitem["user_name"]); ?> [<?php echo date("Y-m-d H:i:s", $aitem['contract_time']); ?>]<br/><?php endif; ?>
                 <?php } ?>
            <?php else: ?>
                 <?php if($item["alone_sign_time"] > 0): ?><?php echo date("Y-m-d H:i:s", $item['alone_sign_time']); ?><?php endif; ?><?php endif; ?>
            </td>

            <td><a href='/m.php?m=Deal&a=edit&id=<?php echo ($item["deal_id"]); ?>'
                target='_blank'><?php echo ($item["deal_id"]); ?></a></td>
            <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>
            <td><?php if($item["is_send"] == 0): ?><font color='#FF4040'>未发送</font>
            <?php else: ?><font color='green'>已发送</font><?php endif; ?></td>
            <td><?php if($item["status"] == 0): ?><font color='#FF4040'>无效</font>
            <?php else: ?><font color='green'>有效</font><?php endif; ?></td>

            <!-- 需求2481开始 edit by liuty-->
            <td><?php if($item["money"] == 0): ?>--<?php else: ?><?php echo ($item["money"]); ?><?php endif; ?></td>
            <td><?php echo ($item["deal_status_cn"]); ?></td>
            <!-- 需求2481结束 -->

            <td>
                <a href="/m.php?m=Contract&a=edit&id=<?php echo ($item["id"]); ?>">修改</a> &nbsp;
                <a href="/m.php?m=Contract&a=foreverdelete&id=<?php echo ($item["id"]); ?>&ajax=0" onclick='return confirm("确认彻底删除？");'>删除</a> &nbsp;
                <?php if($item["type"] != 3 and $item["type"] != 6): ?><a href="/m.php?m=Contract&a=update_contract&id=<?php echo ($item["id"]); ?>&role=<?php echo ($item["usertype"]["role"]); ?>" onclick='return confirm("确认补发合同？");'>补发</a> &nbsp;<?php endif; ?><br />
                <a href="/m.php?m=Contract&a=repdf&id=<?php echo ($item["id"]); ?>&cnum=<?php echo ($item["number"]); ?>" onclick='return confirm("确认操作？");'>重新生成pdf</a> &nbsp;
                <a href="/m.php?m=Contract&a=download&id=<?php echo ($item["id"]); ?>">下载pdf</a>
                <!--<a href="/m.php?m=Contract&a=downloadtsa&id=<?php echo ($item["id"]); ?>" target="_blank">下载Tsa</a>-->
            </td>
        </tr><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 -->
    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script>
function opencontract(id){
    $.weeboxs.open(ROOT+'?m=Contract&a=opencontract&id='+id, {contentType:'ajax',showButton:false,title:'合同内容',width:650,height:500});
}

function change_needsign(obj){
    var tag = 0;
    if(obj.attr('checked')){
        tag = 1;
    }

    $.ajax({
        url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=needsign&id="+obj.attr('conid')+"&tag="+tag,
        data: "ajax=1",
        dataType: "json",
        success: function(res){
            if(res.status==1){
                location.href=location.href;
            } else {
                alert(res.info);
                return false;
            }
        }
    });
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
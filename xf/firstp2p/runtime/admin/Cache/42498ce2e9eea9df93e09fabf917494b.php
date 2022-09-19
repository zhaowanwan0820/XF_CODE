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
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=agreeAll&id="+id+"&type="+type,
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
    <div class="main_title">项目合同管理

    </div>
    <div class="blank5"></div>
    <div class="button_row">
        <?php if($project_id > 0): ?><input type="button" class="button" value="一键签署" onclick="agree_all(<?php echo ($project_id); ?>, 4);" />
            <input type="button" class="button" value="代借款人签署" onclick="agree_all(<?php echo ($project_id); ?>, 1);" />
            <input type="button" class="button" value="代担保公司签署" onclick="agree_all(<?php echo ($project_id); ?>, 2);" />
            <input type="button" class="button" value="代咨询公司签署" onclick="agree_all(<?php echo ($project_id); ?>, 3);" />
            <input type="button" class="button" value="代委托机构签署" onclick="agree_all(<?php echo ($project_id); ?>, 5);" />
            <input type="button" class="button" value="代渠道机构签署" onclick="agree_all(<?php echo ($project_id); ?>, 6);" /><?php endif; ?>
        <input type="button" class="button" value="导出" onclick="export_contract();" />
    </div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            合同标题：<input type="text" class="textbox" name="cname" value="<?php echo trim($_REQUEST['cname']);?>" size="8"/>
            合同编号：<input type="text" class="textbox" name="cnum" value="<?php echo trim($_REQUEST['cnum']);?>" size="15"/>
            用户姓名：<input type="text" class="textbox" name="cuser_name" value="<?php echo trim($_REQUEST['cuser_name']);?>" size="8"/>
            用户id：<input type="text" class="textbox" name="cuser_id" value="<?php echo trim($_REQUEST['cuser_id']);?>" size="4"/>
            借款id：<input type="text" class="textbox" name="project_id" value="<?php echo trim($_REQUEST['project_id']);?>" size="4"/>

            <!-- <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" /> -->
            <input type="hidden" value="ProjectContract" name="m" />
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
            <th width="20px">id</th>
            <th>合同标题</th>
            <th><a href="javascript:sortBy('number','1','Contract','index')">合同编号 </a></th>
            <th>角色</th>
            <th>用户姓名</th>
            <th>预签状态</th>
            <th>签署状态</th>
            <th>签署时间</th>
            <th width='30px'>借款id</th>
            <th width='50px'>创建时间 </th>
            <th>投资金额</th>
            <th width='148px'>操作</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><?php if($item["borrow_user_id"] > 0): ?><tr class="row">
                    <td><input type="checkbox" name="key" class="key" value="<?php echo ($item["project_id"]); ?>_<?php echo ($item["id"]); ?>" sign = "<?php echo ($item["is_have_sign"]); ?>"></td>
                    <td><?php echo ($item["id"]); ?></td>
                    <td><a href='javascript:void(0)' onclick="opencontract('<?php echo ($item["number"]); ?>',<?php echo ($item["id"]); ?>,<?php echo ($item["project_id"]); ?>);"><?php echo ($item["title"]); ?></a></td>
                    <td><?php echo ($item["number"]); ?></td>
                    <td>借款人</td>
                    <td>
                        <a href='/m.php?m=User&a=index&user_id=<?php echo ($item["borrow_user_id"]); ?>' target='_blank'><?php echo ($item["borrow_user_name"]); ?></a>
                    </td>

                    <td>--</td>

                    <td>
                        <?php if($item["borrower_sign_time"] > 0): ?><font color="green">已签</font><br />
                            <?php else: ?>
                            <font color="#FF4040">未签</font> <br /><?php endif; ?>
                    </td>

                    <td><?php if($item["borrower_sign_time"] != 0): ?><?php echo date("Y-m-d H:i:s", $item['borrower_sign_time']); ?><?php endif; ?>
                    </td>

                    <td><a href='#'
                           target='_blank'><?php echo ($item["project_id"]); ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>

                    <!-- 需求2481开始 edit by liuty-->
                    <td><?php if($item["load_money"] == 0): ?>--<?php else: ?><?php echo ($item["load_money"]); ?><?php endif; ?></td>
                    <!-- 需求2481结束 -->

                    <td>
                        <?php if($item["status"] == 1): ?><a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a>
                            <a href="/m.php?m=ContractNew&a=downloadtsa&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载TSA</a>
                        <?php else: ?>
                            <a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a><?php endif; ?>
                    </td>
                </tr><?php endif; ?>

            <?php if($item["user_id"] > 0): ?><tr class="row">
                    <?php if($item["borrow_user_id"] == 0): ?><td><input type="checkbox" name="key" class="key" value="<?php echo ($item["project_id"]); ?>_<?php echo ($item["id"]); ?>" sign = "<?php echo ($item["is_have_sign"]); ?>"></td>
                        <td><?php echo ($item["project_id"]); ?>_<?php echo ($item["id"]); ?></td>
                        <td><a href='javascript:void(0)' onclick="opencontract('<?php echo ($item["number"]); ?>',<?php echo ($item["id"]); ?>,<?php echo ($item["project_id"]); ?>);"><?php echo ($item["title"]); ?></a></td>
                        <td><?php echo ($item["number"]); ?></td>
                        <?php else: ?>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td><?php endif; ?>
                    <td>出借人</td>
                    <td>
                        <a href='/m.php?m=User&a=index&user_id=<?php echo ($item["user_id"]); ?>' target='_blank'><?php echo ($item["user_name"]); ?></a>
                    </td>

                    <td>已预签</td>

                    <td>
                        /
                    </td>

                    <td>
                        /
                    </td>

                    <td><a href=''
                           target='_blank'><?php echo ($item["project_id"]); ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>

                    <!-- 需求2481开始 edit by liuty-->
                    <td><?php if($item["load_money"] == 0): ?>--<?php else: ?><?php echo ($item["load_money"]); ?><?php endif; ?></td>
                    <!-- 需求2481结束 -->

                    <td>
                        <?php if($item["status"] == 1): ?><a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a>
                            <a href="/m.php?m=ContractNew&a=downloadtsa&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载TSA</a>
                            <?php else: ?>
                            <a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a><?php endif; ?>
                    </td>
                </tr><?php endif; ?>

            <?php if($item["agency_id"] > 0): ?><tr class="row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>担保公司</td>
                    <td>
                        <a href='/m.php?m=DealAgency&a=index&id=<?php echo ($item["agency_id"]); ?>' target='_blank'><?php echo ($item["agency_name"]); ?></a>
                    </td>

                    <td>--</td>

                    <td>
                        <?php if($item["agency_sign_time"] > 0): ?><?php foreach($item['agency_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a> <font color="green">已签</font><br /><br />
                            <?php } ?>

                            <?php else: ?>
                            <?php foreach($item['agency_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a><font color="#FF4040">未签</font> <br />
                            <?php } ?><?php endif; ?>
                    </td>

                    <td>
                        <?php if($item["agency_sign_time"] != 0): ?><?php echo date("Y-m-d H:i:s", $item['agency_sign_time']); ?><?php endif; ?>
                    </td>

                    <td><a href='#'
                           target='_blank'><?php echo ($item["project_id"]); ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>

                    <!-- 需求2481开始 edit by liuty-->
                    <td><?php if($item["load_money"] == 0): ?>--<?php else: ?><?php echo ($item["load_money"]); ?><?php endif; ?></td>
                    <!-- 需求2481结束 -->

                    <td>
                        <?php if($item["status"] == 1): ?><a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a>
                            <a href="/m.php?m=ContractNew&a=downloadtsa&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载TSA</a>
                            <?php else: ?>
                            <a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a><?php endif; ?>
                    </td>
                </tr><?php endif; ?>

            <?php if($item["entrust_agency_id"] > 0): ?><tr class="row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>受托机构</td>
                    <td>
                        <a href='/m.php?m=DealAgency&a=index&id=<?php echo ($item["entrust_agency_id"]); ?>' target='_blank'><?php echo ($item["entrust_name"]); ?></a>
                    </td>

                    <td>--</td>

                    <td>
                        <?php if($item["entrust_agency_sign_time"] > 0): ?><?php foreach($item['entrust_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a> <font color="green">已签</font><br /><br />
                            <?php } ?>

                            <?php else: ?>
                            <?php foreach($item['entrust_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a><font color="#FF4040">未签</font> <br />
                            <?php } ?><?php endif; ?>
                    </td>

                    <td>
                        <?php if($item["entrust_agency_sign_time"] != 0): ?><?php echo date("Y-m-d H:i:s", $item['entrust_agency_sign_time']); ?><?php endif; ?>
                    </td>

                    <td><a href='#'
                           target='_blank'><?php echo ($item["project_id"]); ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>

                    <!-- 需求2481开始 edit by liuty-->
                    <td><?php if($item["load_money"] == 0): ?>--<?php else: ?><?php echo ($item["load_money"]); ?><?php endif; ?></td>
                    <!-- 需求2481结束 -->

                    <td>
                        <?php if($item["status"] == 1): ?><a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a>
                            <a href="/m.php?m=ContractNew&a=downloadtsa&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载TSA</a>
                            <?php else: ?>
                            <a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a><?php endif; ?>
                    </td>
                </tr><?php endif; ?>

            <?php if($item["advisory_id"] > 0): ?><tr class="row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>资产管理方</td>
                    <td>
                        <a href='/m.php?m=DealAgency&a=index&id=<?php echo ($item["advisory_id"]); ?>' target='_blank'><?php echo ($item["advisory_name"]); ?></a>
                    </td>

                    <td>--</td>

                    <td>
                        <?php if($item["advisory_sign_time"] > 0): ?><?php foreach($item['advisory_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a> <font color="green">已签</font><br /><br />
                            <?php } ?>

                            <?php else: ?>
                            <?php foreach($item['advisory_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a><font color="#FF4040">未签</font><br />
                            <?php } ?><?php endif; ?>
                    </td>

                    <td>
                        <?php if($item["advisory_sign_time"] != 0): ?><?php echo date("Y-m-d H:i:s", $item['advisory_sign_time']); ?><?php endif; ?>
                    </td>

                    <td><a href='#'
                           target='_blank'><?php echo ($item["project_id"]); ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>

                    <!-- 需求2481开始 edit by liuty-->
                    <td><?php if($item["load_money"] == 0): ?>--<?php else: ?><?php echo ($item["load_money"]); ?><?php endif; ?></td>
                    <!-- 需求2481结束 -->

                    <td>
                        <?php if($item["status"] == 1): ?><a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a>
                            <a href="/m.php?m=ContractNew&a=downloadtsa&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载TSA</a>
                            <?php else: ?>
                            <a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a><?php endif; ?>                        <!--<a href="/m.php?m=Contract&a=downloadtsa&id=<?php echo ($item["id"]); ?>" target="_blank">下载Tsa</a>-->
                    </td>
                </tr><?php endif; ?>

            <?php if($item["canal_agency_id"] > 0): ?><tr class="row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>渠道方</td>
                    <td>
                        <a href='/m.php?m=DealAgency&a=index&id=<?php echo ($item["canal_agency_id"]); ?>' target='_blank'><?php echo ($item["canal_name"]); ?></a>
                    </td>

                    <td>--</td>

                    <td>
                        <?php if($item["canal_agency_sign_time"] > 0): ?><?php foreach($item['canal_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a> <font color="green">已签</font><br /><br />
                            <?php } ?>

                            <?php else: ?>
                            <?php foreach($item['canal_user'] as $aitem){ ?>
                            <a href='/m.php?m=User&a=index&user_id=<?php echo ($aitem["user_id"]); ?>' target='_blank'><?php echo ($aitem["real_name"]); ?></a><font color="#FF4040">未签</font><br />
                            <?php } ?><?php endif; ?>
                    </td>

                    <td>
                        <?php if($item["canal_agency_sign_time"] != 0): ?><?php echo date("Y-m-d H:i:s", $item['canal_agency_sign_time']); ?><?php endif; ?>
                    </td>

                    <td><a href='#'
                           target='_blank'><?php echo ($item["project_id"]); ?></a></td>
                    <td><?php echo date("Y-m-d H:i:s", $item['create_time']); ?></td>

                    <!-- 需求2481开始 edit by liuty-->
                    <td><?php if($item["load_money"] == 0): ?>--<?php else: ?><?php echo ($item["load_money"]); ?><?php endif; ?></td>
                    <!-- 需求2481结束 -->

                    <td>
                        <?php if($item["status"] == 1): ?><a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a>
                            <a href="/m.php?m=ContractNew&a=downloadtsa&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载TSA</a>
                            <?php else: ?>
                            <a href="/m.php?m=ContractNew&a=download&id=<?php echo ($item["id"]); ?>&num=<?php echo ($item["number"]); ?>&projectId=<?php echo ($item["project_id"]); ?>&type=1">下载pdf</a><?php endif; ?>                        <!--<a href="/m.php?m=Contract&a=downloadtsa&id=<?php echo ($item["id"]); ?>" target="_blank">下载Tsa</a>-->
                    </td>
                </tr><?php endif; ?><?php endforeach; endif; ?>
        <tr>
            <td colspan="20" class="bottomTd">&nbsp;</td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 -->
    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script>
    function opencontract(number,id,projectId){
        $.weeboxs.open(ROOT+'?m=ContractNew&a=openContract&type=1&num='+number+'&id='+id+'&projectId='+projectId, {contentType:'ajax',showButton:false,title:'合同内容',width:650,height:500});
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
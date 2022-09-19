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

<div class="main">
    <div class="main_title"><?php echo ($main_title); ?></div>
    <div class="blank5"></div>
    <div class="button_row">
        <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();"/>
        <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();"/>
    </div>
    <?php //读取机构类别
        function get_type($type){
            if(intval($type) === 1) {
                return '担保机构';
            }elseif(intval($type) === 2){
                return '咨询机构';
            }elseif(intval($type) === 3){
                return '平台机构';
            }elseif(intval($type) === 4){
                return '支付机构';
            }elseif(intval($type) === 5){
                return '管理机构';
            }elseif(intval($type) === 6){
                return '代垫机构';
            }elseif(intval($type) === 7){
                return '受托机构';
            }elseif(intval($type) === 8){
                return '代充值机构';
            }elseif(intval($type) === 9){
                return '交易所';
            }elseif(intval($type) === 10){
                return '渠道机构';
            }
        } ?>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            机构类型：
            <select name="agency_type" id="agency_type">
                <option value="0" <?php if(intval($_REQUEST['agency_type']) == 0): ?>selected="selected"<?php endif; ?>>全部</option>
                <?php if(is_array($agency_type_map)): foreach($agency_type_map as $type_id=>$type_name): ?><option value="<?php echo ($type_id); ?>" <?php if(intval($_REQUEST['agency_type']) == $type_id): ?>selected="selected"<?php endif; ?>><?php echo ($type_name); ?></option><?php endforeach; endif; ?>
            </select>

            <select name="search_type" id="search_type">
                <option value="1" <?php if(intval($_REQUEST['search_type']) == 1): ?>selected="selected"<?php endif; ?> >机构名称搜索</option>
                <option value="2" <?php if(intval($_REQUEST['search_type']) == 2): ?>selected="selected"<?php endif; ?> >关联会员名搜索</option>
                <option value="3" <?php if(intval($_REQUEST['search_type']) == 3): ?>selected="selected"<?php endif; ?> >代理会员名搜索</option>
            </select>
            <input type="text" name="keywords" value="<?php echo trim($_REQUEST['keywords']);?>" />

            信贷是否可见
            <select name="credit_display" id="credit_display">
                <option value="-1" <?php if(intval($_REQUEST['credit_display']) == -1): ?>selected="selected"<?php endif; ?> >全部</option>
                <option value="1" <?php if(intval($_REQUEST['credit_display']) == 1): ?>selected="selected"<?php endif; ?> >可见</option>
                <option value="0" <?php if(intval($_REQUEST['credit_display']) == 0): ?>selected="selected"<?php endif; ?> >不可见</option>
            </select>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <input type="hidden" value="DealAgency" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        </form>
    </div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan="12" class="topTd">&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>
            <th width="50px   ">
                <a href="javascript:sortBy('id','<?php echo ($sort); ?>','DealAgency','index')" title="按照<?php echo L("
                   ID");?>
                <?php echo ($sortType); ?> ">
                <?php echo L("ID");?>
                <?php if(($order)  ==  "id"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0"
                     align="absmiddle">
                <?php endif; ?>
                </a>
            </th>
            <th>机构类型</th>
            <th>
                <a href="javascript:sortBy('name','<?php echo ($sort); ?>','DealAgency','index')"
                   title="按照名称   <?php echo ($sortType); ?> ">名称
                    <?php if(($order)  ==  "name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif"
                                                           width="12" height="17" border="0" align="absmiddle">
                    <?php endif; ?></a></th>

            <th>是否具有独立ICP</th>
            <th>关联分站</th>
            <th>关联会员</th>
            <th>机构代理人</th>
            <th>信贷是否可见</th>
            <th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','DealAgency','index')"
                   title="按照<?php echo L(" IS_EFFECT");?>
                <?php echo ($sortType); ?> "><?php echo L("IS_EFFECT");?>   <?php if(($order)  ==  "is_effect"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0"
                     align="absmiddle">
                <?php endif; ?></a></th>
            <th><a href="javascript:sortBy('sort','<?php echo ($sort); ?>','DealAgency','index')"
                   title="按照<?php echo L('SORT');?>
                <?php echo ($sortType); ?> ">
                <?php echo L("SORT");?><?php if(($order)  ==  "sort"): ?>
                <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0"
                     align="absmiddle">
                <?php endif; ?></a></th>

            <th style="width:100px">操作</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>
        $deal): ++$i;$mod = ($i % 2 )?>
        <tr class="row">
            <td><input type="checkbox" name="key" class="key" value="<?php echo ($deal[" id"]); ?>"></td>
            <td>&nbsp;<?php echo ($deal["id"]); ?></td>
            <td>&nbsp;<?php echo (isset($organizeType[$deal["type"]]) ? $organizeType[$deal["type"]] : '未知机构'); ?></td>
            <td>&nbsp;<?php echo ($deal["name"]); ?></td>
            <td>&nbsp;<?php if(($deal["is_icp"]) == 0){
                                echo "否";
                            }else{
                                echo "是";
                            }?></td>
            <td>&nbsp;<?php if(($deal["site_id"]) <> 0){
                                echo $site_list[$deal["site_id"]];
                            }?></td>
            <td>&nbsp;<?php echo (get_user_name($deal["user_id"])); ?></td>
            <td>&nbsp;<?php echo (get_user_name($deal["agency_user_id"])); ?></td>
            <td><?php echo ($deal["is_credit_display"] == 1) ? '可见' : '不可见' ; ?></td></td>
            <td>&nbsp;<?php echo (get_is_effect($deal["is_effect"],$deal['id'])); ?></td>

            <td>&nbsp;<?php echo (get_sort($deal["sort"],$deal['id'])); ?></td>
            <td><a href="javascript:edit('<?php echo ($deal["id"]); ?>')">
                <?php echo L("EDIT");?></a>&nbsp;<a href="javascript: foreverdel('<?php echo ($deal["id"]); ?>')">
                <?php echo L("DEL");?>
                </a>&nbsp;
            </td>
        </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        <tr>
            <td colspan="12" class="bottomTd"> &nbsp;</td>
        </tr>
    </table>

    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
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
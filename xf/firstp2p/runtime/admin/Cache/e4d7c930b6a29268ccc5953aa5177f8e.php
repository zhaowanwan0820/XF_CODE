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


<style>
#dataTable th { text-align: left; }
</style>

<div class="main">
    <div class="main_title">
        <label>投资客户明细</label>
        <a href="<?php echo u("OexchangeBatch/index?pro_id=". $projectInfo['id']);?>" class="back_list"><?php echo L("BACK_LIST");?></a>
    </div>
    <div class="blank5"></div>

    <table id="dataTable" class="dataTable">
        <tr>
            <th style="text-align:right">项目名称：</th>
            <td><?php echo ($projectInfo['name']); ?></td>
        </tr>
        <tr>
            <th style="width:140px; text-align:right">交易所备案产品编号：</th>
            <td><?php echo ($projectInfo['jys_number']); ?></td>
        </tr>
        <tr>
            <th style="text-align:right">批次id：</th>
            <td><?php echo ($batchInfo['id']); ?></td>
        </tr>
        <tr>
            <th style="text-align:right">期数：</th>
            <td><?php echo ($batchInfo['batch_number']); ?></td>
        </tr>
        <tr>
            <th style="text-align:right">投资用户明细：</th>
            <td>
                <form id="upload_form" method="post" action="<?php echo u('ExchangeLoad/upload');?>" enctype="multipart/form-data">
                    <input type="hidden" name="batch_id" value="<?php echo ($batchInfo['id']); ?>" />
                    <input type="file" name="load_list" id="load_list" />
                    <input type="submit" value="导入" class="button" id="import" />
                    <input type="button" value="下载模板" class="button" id="download" />
                </form>
            </td>
        </tr>
    </table>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <table id="dataTable" class="dataTable">
        <tr class="row">
            <th>序号</th>
            <th>投资订单号</th>
            <th>用户名称</th>
            <th>身份证件类型</th>
            <th>证件号</th>
            <th>手机号</th>
            <th>银行卡号</th>
            <th>开户行名称</th>
            <th>联行号</th>
            <th>开户行所在省</th>
            <th>开户行所在市</th>
            <th>服务人邀请码</th>
            <th>打款时间</th>
            <th>认购金额</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($list)): $index = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$index;$mod = ($index % 2 )?><tr class="row">
            <td><?php echo ($pageSize * ($nowPage - 1) + $index); ?></td>
            <td><?php echo sprintf("%08d", $item[id]);?></td>
            <td><?php echo ($item['real_name']); ?></td>
            <td><?php echo ($item['certificate_type']); ?></td>
            <td><?php echo ($item['certificate_no']); ?></td>
            <td><?php echo ($item['mobile']); ?></td>
            <td><?php echo ($item['bank_no']); ?></td>
            <td><?php echo ($item['bank_name']); ?></td>
            <td><?php echo ($item['cnaps_no']); ?></td>
            <td><?php echo ($item['bank_province']); ?></td>
            <td><?php echo ($item['bank_city']); ?></td>
            <td><?php echo ($item['invite_code']); ?></td>
            <td><?php echo date("Y-m-d H:i:s", $item['pay_time']);?></td>
            <td><?php echo sprintf("%.2f", $item['pay_money'] / 100);?></td>
            <td> <?php if($item['status'] == 1): ?>有效<?php else: ?>无效<?php endif; ?></td>
            <td>
                <a href="<?php echo u('ExchangeLoad/show?load_id=' . $item['id']);?>">编辑</a>
                <a href="<?php echo u('ExchangeLoad/del?load_id=' . $item['id']);?>" class="del-link">作废</a>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
    <div class="blank5"></div>

    <div class="page"><?php echo ($page); ?></div>
    <div class="blank5"></div>
</div>

<script>
    $(function() {
        //下载模板
        $('#download').click(function() {
            location.href='/static/exchange_load.csv'
        })

        //上传文件
        $('#import').click(function() {
           if ($('#load_list').val()) {
              return $('#upload_form').submit();
           } else {
              alert("上传文件不能为空!");
              return false;
           }
        })

        $('.del-link').click(function() {
            if (!confirm('确定要将此记录作废?')) {
                return false;
            }
        })
    })
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
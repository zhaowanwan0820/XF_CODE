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
<script type="text/javascript" src="__TMPL__Common/js/papaparse.min.js"></script>
<div class="main">
<div class="main_title">大批量修改客户系数 </div>
<a href="/m.php?m=CouponBind&a=downDiscountCSVTpl">下载模板</a>
<div class="blank5"></div>

<div style='width: 400px;height:300px;margin:10px 0px 0px 10px'> 
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
    <p class="autoMsg"></p>
 </div>

<div class="blank5"></div>

<div class="search_row">

        <div class="button_row">
            <input id="upfile" type="file" name="upfile">
        </div>
        <input id="importCsv" type="button" class="button" value="导入" onclick='importCsv(this)' />
        <input type="button" class="button" value="下载错误数据" onclick='exportErrorCsv()' />

<div>

<div class="blank5"></div>

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

<script type="text/javascript">

var errorData = new Array();

function exportErrorCsv(){
    if(errorData.length == 0){
        alert("没有错误数据");
        return false;
    }

    csvData = Papa.unparse({fields:new Array("序号","真实姓名","会员编码","客户系数","错误原因"),data:errorData});
    funDownload(csvData,"errorData_"+Date.parse(new Date())+'.csv');

}

function funDownload(content, filename){

        // 创建隐藏的可下载链接
    var eleLink = document.createElement('a');
    eleLink.download = filename;
    eleLink.style.display = 'none';
    // 字符内容转变成blob地址
    var BOM = "\uFEFF";//告诉excel用utf8打开
    var blob = new Blob([BOM + content]);
    eleLink.href = URL.createObjectURL(blob);
    // 触发点击
    document.body.appendChild(eleLink);
    eleLink.click();
    // 然后移除
    document.body.removeChild(eleLink);
}

function autoMsg(msg){
    msgA = msg;
    msgB = '';
    $(".autoMsg").each(
        function(i){
            msgB = $(this).html();
            $(this).html(msgA);
            msgA = msgB;
        });
}

function clearMsg(){
        $(".autoMsg").each(
        function(i){
            $(this).html('');
        });
}

function importCsv(){
    if (window.navigator.userAgent.indexOf("Chrome") == -1 &&  window.navigator.userAgent.indexOf("Firefox") == -1){
        alert("不支持此浏览器，请选择火狐或者谷歌浏览器");
    }
    
    errorData = new Array();
    fileInput = document.getElementById("upfile");
    file = fileInput.files[0];
    if(!file){
        alert("请选择文件");
        return false;
    }

    size = <?php echo ($size); ?>;
    if(confirm("请确认要操作吗？")){
        timestamp_start = Date.parse(new Date());
        $("#importCsv").attr("disabled",true);
        clearMsg();
        if(window.navigator.userAgent.indexOf("Chrome") > -1){
            autoMsg("导入文件时间较长，如果页面无响应，请点击继续等待,请勿关闭浏览器");
        }
        
        autoMsg("正在加载csv文件...");
        Papa.parse(file, {
            encoding:'gbk',
            complete: function(results) {
            autoMsg("加载csv文件完毕...");
            totalData = results.data;
            totalData.shift();
            totalData.pop();
            while(totalData.length){
              data = totalData.splice(0,size);
              changeDiscountRatioFromData(data);
            }
            timestamp_end = Date.parse(new Date());
            cost = (timestamp_end-timestamp_start)/1000;
            if(errorData.length > 0){
                autoMsg("<font color='red'>csv文件导入完毕,错误数据共计"+errorData.length+"条,共耗时"+cost+"秒...</font>");
            }else{
                autoMsg("<font color='blue'>恭喜，csv文件导入完毕,未发现错误数据,共耗时"+cost+"秒...</font>");
            }

            $("#importCsv").attr("disabled",false);
            }
        });
    }
}


function changeDiscountRatioFromData(data){
    autoMsg("正在导入第"+data[0][0]+"到"+data[data.length-1][0]+"条数据...");
    $.ajax({
            type:"POST",
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=changeDiscountRatioFromData",
            data: {'data':data},
            dataType: "json",
            async:false,
            success: function(obj){
                if(obj.status==0){
                    data = obj.data 
                    errorData = errorData.concat(data);
                    autoMsg("<font color='red'>出现错误数据"+data.length+"条...</font>");
                }else{
                    autoMsg("<font color='blue'>未发现错误数据...</font>");
                }
            }
    });
    
}

</script>
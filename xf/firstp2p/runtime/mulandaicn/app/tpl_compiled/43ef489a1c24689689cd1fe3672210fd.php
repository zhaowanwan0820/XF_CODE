</div>
<div class="blank"></div>
<!--底部开始-->
<?php 
$k = array (
  'name' => 'get_adv',
  'x' => '底部导航',
);
echo $k['name']($k['x']);
?>
<!--底部结束-->

<script type='text/javascript'>
var _ncf={"prd":"firstp2p","pstr":"","pfunc":null,"pcon":"","pck":{"channel":"channel","fpid":"fpid"},"trid":"","channel":['pubid','mediumid','adid','adsize'],"rfuniq":[],"rfmuti":[]};
(function(p,h,s){var o=document.createElement(h);o.src=s;p.appendChild(o)})(document.getElementsByTagName("HEAD")[0],"script","<?php echo $this->asset->makeUrl('default/js/ncfpb.1.5.min.js');?>");
</script>

<!--[if IE 6]>
<link href="<?php echo $this->asset->makeUrl('v1/css/ie6-fix.css');?>" rel="stylesheet" type="text/css">
<script type="text/javascript" src="<?php echo $this->asset->makeUrl('v1/js/common/DD_belatedPNG.js');?>"></script>
<script type="text/javascript" src="<?php echo $this->asset->makeUrl('v1/js/common/DD_belated_ie6_fix.js');?>"></script>
<![endif]-->
<!--logId:<?php echo $this->_var['logId']; ?>-->
<?php
    //灰度标签
    if(isset($_SERVER['server_number']) && $_SERVER['server_number'] == 27){
        echo "<!-RC-->";
    }
?>

</body>
</html>

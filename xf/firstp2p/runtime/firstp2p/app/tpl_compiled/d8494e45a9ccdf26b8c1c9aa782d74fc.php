<?php if (app_conf ( 'TPL_HEADER' )): ?>
<?php echo $this->fetch(app_conf('TPL_HEADER')); ?>
<?php else: ?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php if (isset ( $this->_var['page_title'] ) && $this->_var['page_title']): ?><?php echo $this->_var['page_title']; ?> - <?php endif; ?><?php if (isset ( $this->_var['show_site_titile'] ) && ( $this->_var['show_site_titile'] == 1 )): ?><?php 
$k = array (
  'name' => 'app_conf',
  'value' => 'SHOP_SEO_TITLE',
);
echo $k['name']($k['value']);
?> - <?php endif; ?><?php echo $this->_var['site_info']['SHOP_TITLE']; ?></title>
<link rel="apple-touch-icon-precomposed" href="<?php echo $this->_var['APP_SKIN_PATH']; ?>images/favicon.png" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo $this->_var['APP_SKIN_PATH']; ?>images/favicon.png" type="image/x-icon" />
<meta name="keywords" content="<?php if (isset ( $this->_var['page_keyword'] ) && $this->_var['page_keyword']): ?><?php echo $this->_var['page_keyword']; ?><?php endif; ?><?php echo $this->_var['site_info']['SHOP_KEYWORD']; ?>" />
<meta name="description" content="<?php if (isset ( $this->_var['page_description'] ) && $this->_var['page_description']): ?><?php echo $this->_var['page_description']; ?><?php endif; ?><?php echo $this->_var['site_info']['SHOP_DESCRIPTION']; ?>" />
<?php 
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);
?>
<!-- 压缩输出 -->
<?php echo $this->asset->renderCssV2("common_v2"); ?>
<?php echo $this->asset->renderJsV2("common_v2"); ?>
<!--[if lte IE 9]>
<script type="text/javascript" src="<?php echo $this->asset->makeUrl('v1/js/common/html5shiv.js');?>"></script>
<![endif]-->



<script>
   var  status_switch = <?php 
$k = array (
  'name' => 'app_conf',
  'v' => 'SWITCH_DEAL_INFO_DISPLAY',
);
echo $k['name']($k['v']);
?>;
   var VAR_FILESIZE = <?php echo $this->_var['max_image_size']; ?>;
</script>

<?php if (isset ( $this->_var['rss_title'] ) && $this->_var['rss_title']): ?>
<link title="<?php echo $this->_var['rss_title']; ?>" type="application/rss+xml" rel="alternate" href="<?php echo $this->_var['rss_url']; ?>" />
<?php endif; ?>

<script type="text/javascript">
var APP_ROOT = '<?php echo $this->_var['APP_ROOT']; ?>';
<?php if (app_conf ( "APP_MSG_SENDER_OPEN" ) == 1): ?>
var send_span = <?php 
$k = array (
  'name' => 'app_conf',
  'v' => 'SEND_SPAN',
);
echo $k['name']($k['v']);
?>000;
<?php endif; ?>
var USER_INFO = <?php if ($this->_var['user_info']): ?>1<?php else: ?>0<?php endif; ?>;
<?php /** 是否企业用户*/ ?>
var isEnterprise = <?php if ($this->_var['user_info']['user_type'] == 1): ?>1<?php else: ?>0<?php endif; ?>;
<?php /** 是否强制密码修改 */ ?>
var forceChangePwd = <?php if ($this->_var['user_info']['force_new_passwd'] == 1): ?>1<?php else: ?>0<?php endif; ?>;
</script>

</head>
<body>
    <header class="m_header clearfix">
        <div class="top">
            <div class="w1100 clearfix">
                <div class="fl color_red public"><?php 
$k = array (
  'name' => 'get_adv',
  'x' => '首页通告_2015',
);
echo $k['name']($k['x']);
?></div>
                <?php 
$k = array (
  'name' => 'load_user_tip_v2',
);
echo $this->_hash . $k['name'] . '|' . base64_encode(serialize($k)) . $this->_hash;
?>
            </div>
        </div>
        <div class="clearfix bg_whtie">
            <div class="w1100">
            <a class="logo" href="/"></a>

            <div class="my_account">
                <div class="drop_t"></div>
                <div class="drop_b">
                    <a href="/account"><i class="ac_icon ml15"></i>我的账户<i class="icon_arrow ml5"></i></a>
                     <?php $_from = $this->_var['nav_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'nav_item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['nav_item']):
?>
                        <?php if ($this->_var['nav_item']['name'] == "我的p2p"): ?>
                            <?php if ($this->_var['nav_item']['sub_nav']): ?>
                            <ul>
                                <?php $_from = $this->_var['nav_item']['sub_nav']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('sub_key', 'sub_item');if (count($_from)):
    foreach ($_from AS $this->_var['sub_key'] => $this->_var['sub_item']):
?>
                                    <li <?php if ($this->_var['sub_key'] == count ( $this->_var['nav_item']['sub_nav'] ) - 1): ?>class="nobor"<?php endif; ?>><a href="<?php echo $this->_var['sub_item']['url']; ?>" target="<?php if ($this->_var['sub_item']['blank'] == 1): ?>_blank<?php endif; ?>"><?php echo $this->_var['sub_item']['name']; ?></a></li>
                                <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                            </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                     <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </div>
            </div>
            <nav id="top_nav">
                <ul>
                     <?php $_from = $this->_var['nav_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'nav_item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['nav_item']):
?>
                        <?php if ($this->_var['nav_item']['name'] != "我的p2p"): ?>
                            <?php if ($this->_var['nav_item']['name'] != "基金理财" || ! $this->_var['isEnterprise']): ?>
                            <li class="<?php if ($this->_var['nav_item']['sub_nav']): ?>j_showMenu<?php endif; ?> <?php if (isset ( $this->_var['nav_item']['current'] ) && $this->_var['nav_item']['current'] == 1): ?>select<?php endif; ?>">
                                <a href="<?php echo $this->_var['nav_item']['url']; ?>" target="<?php if ($this->_var['nav_item']['blank'] == 1): ?>_blank<?php endif; ?>">
                                    <?php if ($this->_var['nav_item']['sub_nav']): ?><span><?php echo $this->_var['nav_item']['name']; ?></span><i class="g-pos-i"></i><?php else: ?><?php echo $this->_var['nav_item']['name']; ?><?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>
                     <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </ul>
            </nav>
        </div>
        </div>

    </header>

    <!--面包屑导航开始-->
    <?php if (isset ( $this->_var['nav'] ) && $this->_var['nav']): ?>
    <section  class="crumbs">
        <div class="w1100">
            <a href="/">首页</a>
            <?php $_from = $this->_var['nav']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'n');if (count($_from)):
    foreach ($_from AS $this->_var['n']):
?>
            <i></i><span><?php if (isset ( $this->_var['n']['url'] ) && $this->_var['n']['url']): ?><a href="<?php echo $this->_var['n']['url']; ?>"><?php echo $this->_var['n']['text']; ?></a><?php else: ?><?php echo $this->_var['n']['text']; ?><?php endif; ?></span>
            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
        </div>
    </section>
    <?php endif; ?>
    <!--面包屑导航结束-->


<?php endif; ?>

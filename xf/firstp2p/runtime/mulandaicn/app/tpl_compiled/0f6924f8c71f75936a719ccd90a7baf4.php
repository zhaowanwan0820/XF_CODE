<?php if (app_conf ( 'TPL_HEADER' )): ?>
<?php echo $this->fetch(app_conf('TPL_HEADER')); ?>
<?php else: ?>
<!doctype html>
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
<link rel="icon" href="<?php echo $this->_var['APP_SKIN_PATH']; ?>images/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo $this->_var['APP_SKIN_PATH']; ?>images/favicon.ico" type="image/x-icon" />
<meta name="keywords" content="<?php if (isset ( $this->_var['page_keyword'] ) && $this->_var['page_keyword']): ?><?php echo $this->_var['page_keyword']; ?><?php endif; ?><?php echo $this->_var['site_info']['SHOP_KEYWORD']; ?>" />
<meta name="description" content="<?php if (isset ( $this->_var['page_description'] ) && $this->_var['page_description']): ?><?php echo $this->_var['page_description']; ?><?php endif; ?><?php echo $this->_var['site_info']['SHOP_DESCRIPTION']; ?>" />

<script>
   var  status_switch = <?php 
$k = array (
  'name' => 'app_conf',
  'v' => 'SWITCH_DEAL_INFO_DISPLAY',
);
echo $k['name']($k['v']);
?>;
</script>

<?php if (isset ( $this->_var['rss_title'] ) && $this->_var['rss_title']): ?>
<link title="<?php echo $this->_var['rss_title']; ?>" type="application/rss+xml" rel="alternate" href="<?php echo $this->_var['rss_url']; ?>" />
<?php endif; ?>

    <?php if (isset ( $this->_var['is_index'] ) && $this->_var['is_index'] == 1): ?>
<?php echo $this->asset->renderAll(1); ?>
<?php else: ?>
<!--[if lte IE 9]>
<script type="text/javascript" src="<?php echo $this->asset->makeUrl('v1/js/common/html5shiv.js');?>"></script>
<![endif]-->
<?php echo $this->asset->renderAll(); ?>
<?php endif; ?>

<?php if (app_conf ( "TEMPLATE_ID" ) != 1): ?>
<link rel="stylesheet" type="text/css" href="<?php 
$k = array (
  'name' => 'parse_css_site',
  'v' => 'css/style.css',
);
echo $k['name']($k['v']);
?>" />
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
</script>


<!--public js&css end -->

</head>

<body>  
<script type="text/javascript" src="/api/fzjs"></script>
<!--头部开始-->
    <header class="m-head">
        <div class="fix_width">
            <span><a href="<?php echo $this->_var['APP_ROOT']; ?>/"></a></span>
            <menu>
                <ul>
                     <?php $_from = $this->_var['nav_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'nav_item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['nav_item']):
?>
                        <li class="<?php if ($this->_var['nav_item']['sub_nav']): ?>j_showMenu<?php endif; ?> <?php if (isset ( $this->_var['nav_item']['current'] ) && $this->_var['nav_item']['current'] == 1): ?>select<?php endif; ?>">
                            <a href="<?php echo $this->_var['nav_item']['url']; ?>" target="<?php if ($this->_var['nav_item']['blank'] == 1): ?>_blank<?php endif; ?>">
                                <?php if ($this->_var['nav_item']['sub_nav']): ?><span><?php echo $this->_var['nav_item']['name']; ?></span><i class="g-pos-i"></i><?php else: ?><?php echo $this->_var['nav_item']['name']; ?><?php endif; ?>
                            </a>
                            <?php if ($this->_var['nav_item']['sub_nav']): ?>
                            <ul>
                                <?php $_from = $this->_var['nav_item']['sub_nav']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('sub_key', 'sub_item');if (count($_from)):
    foreach ($_from AS $this->_var['sub_key'] => $this->_var['sub_item']):
?>
                                    <li <?php if ($this->_var['sub_key'] == count ( $this->_var['nav_item']['sub_nav'] ) - 1): ?>class="nobor"<?php endif; ?>><a href="<?php echo $this->_var['sub_item']['url']; ?>" target="<?php if ($this->_var['sub_item']['blank'] == 1): ?>_blank<?php endif; ?>"><?php echo $this->_var['sub_item']['name']; ?></a></li>
                                <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                            </ul>
                            <?php endif; ?>
                        </li>
                        <?php if ($this->_var['key'] != count ( $this->_var['nav_list'] ) - 1): ?><li class="bg_li"></li><?php endif; ?>
                     <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </ul>
            </menu>
            <div class="g-head-right">
            <?php if ($this->_var['user_info']): ?>
                <ul>
                    <li>您好，<a href="/account"><?php if (empty ( $this->_var['user_info']['real_name'] )): ?><?php echo $this->_var['user_info']['user_name']; ?><?php else: ?><?php echo $this->_var['user_info']['real_name']; ?><?php endif; ?></a></li>
                    <li class="li_bg"></li>
                    <li class="<?php if ($this->_var['msg_count'] > 0): ?>pr15 j_showMenu<?php endif; ?>"><a href="/message">消息</a>
                        <?php if ($this->_var['msg_count'] > 0): ?>
                        <div class="g-message-number"><?php echo $this->_var['msg_count']; ?></div>
                        <ul>
                            <?php $_from = $this->_var['msg_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'msg');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['msg']):
?>
                            <li <?php if ($this->_var['key'] == count ( $this->_var['msg_list'] ) - 1): ?>class="nobor"<?php endif; ?>><em><a href="/message/deal/<?php echo $this->_var['msg']['group_key']; ?>"><?php echo $this->_var['msg']['total']; ?></a></em> 条 <?php if (isset ( $this->_var['msg_title'][$this->_var['msg']['is_notice']] )): ?>
                                    <?php echo $this->_var['msg_title'][$this->_var['msg']['is_notice']]; ?>
                                    <?php else: ?>
                                    <?php echo $this->_var['LANG']['SYSTEM_PM']; ?>
                                    <?php endif; ?>，<a href="/message/deal/<?php echo $this->_var['msg']['group_key']; ?>">查看</a></li>
                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <li class="li_bg"></li>
                    <li><a href="<?php
echo parse_url_tag("u:index|helpcenter|"."".""); 
?>">帮助</a></li>
                    <li class="li_bg"></li>
                    <li><a href="<?php
echo parse_url_tag("u:shop|user/loginout|"."".""); 
?>">退出</a></li>
                </ul>
            <?php else: ?>
                <ul>

                    <li><a href="<?php
echo parse_url_tag("u:shop|user/register|"."".""); 
?>">注册</a></li>
                    <li class="li_bg"></li>
                    <li><a href="<?php
echo parse_url_tag("u:shop|user/login|"."".""); 
?>">登录</a></li>
                    <li class="li_bg"></li>
                    <li><a href="<?php
echo parse_url_tag("u:index|helpcenter|"."".""); 
?>">帮助</a></li>
                </ul>
            <?php endif; ?>
            </div>


        </div>
    </header>
    <!--头部结束-->

    <?php if ($this->_var['MODULE_NAME'] == "deal" && $this->_var['ACTION_NAME'] == "index" && ( app_conf ( 'TEMPLATE_ID' ) == 1 || app_conf ( 'TEMPLATE_ID' ) == 7 || app_conf ( 'TEMPLATE_ID' ) == 6 )): ?>
        <?php if ($this->_var['deal']['type_id'] == 11): ?>
        <adv adv_id="详情页车贷banner">
        <?php elseif ($this->_var['deal']['type_id'] == 12): ?>
        <adv adv_id="详情页房贷banner">
        <?php endif; ?>
    <?php endif; ?>

    <!--面包屑导航开始-->
    <?php if (isset ( $this->_var['nav'] ) && $this->_var['nav']): ?>
    <section>
        <div class="fix_width">
            <div class="m-nav">
                <a href="/">首页</a>
                    <?php $_from = $this->_var['nav']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'n');if (count($_from)):
    foreach ($_from AS $this->_var['n']):
?>
                    > <label><?php if (isset ( $this->_var['n']['url'] ) && $this->_var['n']['url']): ?><a href="<?php echo $this->_var['n']['url']; ?>"><?php echo $this->_var['n']['text']; ?></a><?php else: ?><?php echo $this->_var['n']['text']; ?><?php endif; ?></label>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <!--面包屑导航结束-->

    <div class="<?php if (isset ( $this->_var['in_preset_page'] ) && $this->_var['in_preset_page'] == 1): ?><?php else: ?>wrap<?php endif; ?>">
<?php endif; ?>

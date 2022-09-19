<?php
// database host(待移除)
// $db_host   = "39.97.185.44:3306";

// // database name
// $db_name   = "ecshop_dev";

// // database username
// $db_user   = "ecshop";

// // database password
// $db_pass   = "susTIhlnaw7wp8n";
$db_host = "39.97.234.171:3306";
$db_name = "wx_shop";
$db_user = "ecshop";
$db_pass = "susTIhlnaw7wp8n";


//$db_host   = "127.0.0.1:3306";
//$db_name   = "ecshop";
//$db_user   = "root";
//$db_pass   = "123456";

//$db_host   = "10.0.0.55";
//$db_name   = "ecshop_dev";
//$db_user   = "dev";
//$db_pass   = "YrgGz4K+l1mn*U8I4DUJ";
// table prefix
$prefix    = "wx_";

$timezone    = "PRC";


$cookie_path    = "/";

$cookie_domain    = "";

$session = "1440";

//数据库主服务器设置, 支持多组服务器设置, 当设置多组服务器时, 则会随机使用某个服务器
// $_config['master'][0]['dbhost'] = "13.209.147.86:3306";
// $_config['master'][0]['dbname'] = "ecshop";
// $_config['master'][0]['dbuser'] = "AmaHuanhuan";
// $_config['master'][0]['dbpw'] = "A&huanhuan";

/*
 *$_config['master'][2]['dbhost'] = "";
 *...
 */

//数据库从服务器设置( slave, 只读 ), 支持多组服务器设置, 当设置多组服务器时, 系统每次随机使用
// $_config['master'][0]['dbhost'] = "13.209.147.86:3306";
// $_config['master'][0]['dbname'] = "ecshop";
// $_config['master'][0]['dbuser'] = "AmaHuanhuan";
// $_config['master'][0]['dbpw'] = "A&huanhuan";

define('DEBUG_MODE', 0);// 0 :禁用调试调试 1 :显示所有错误 2 :禁用Smarty缓存 4 :使用includes/lib.debug.php 8 :记录查询的SQL到data目录下以mysql_query_开头的文件


define('EC_CHARSET','utf-8');

define('ADMIN_PATH','admin');

define('AUTH_KEY', 'this is a key');

define('OLD_AUTH_KEY', '');

define('API_TIME', '');

define('STORE_KEY','a705b085844a480ae75defd607c9e5c4');

// appserver 路径
define('APPSERVER_PATH','/Users/super/shop/youjie_appserver');
// define('APPSERVER_PATH','E:/dev/appserver_miaosha_zhangjian_20190910');

define('EXEC_PHP',' php ');//'/usr/bin/php56 '   '/usr/local/php56/bin/php'

define('PLATFORM_UID', 1);//平台用户id;

define('EXPRESS_INFO_URL', '47.104.177.224:8098');// 电子面单ip
//请求爱投资秘钥
define('WX_API_SECRET','1F1FF7805F93B034F8E1C364173DE6EC'); //secret
define('WX_API_KEY','huanhuan');

define('TEST_URL', 'http://womecshop.itouzi.com');       // 工单测试环境域名
define('PRODUCTION_URL', 'https://wo.huanhuanyiwu.com'); // 工单线上环境域名

define('API_HOST', 'https://api.huanhuanyiwu.com/');

//首页tags列表页地址
define('HOME_GOODS_PATH', 'https://shop.firstp2p.cn/h5/#/products?tags_id=');

?>

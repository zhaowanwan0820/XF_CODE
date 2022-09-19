<?php
require(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ERROR);
ini_set('display_errors', 1);
use libs\utils\PaymentApi;
use core\service\UserTagService;
$sql = "SELECT id, site_id FROM firstp2p_user";
$result = $GLOBALS['db']->get_slave()->query($sql);
$userTagService = new UserTagService();
while($result && $data = $GLOBALS['db']->get_slave()->fetchRow($result)) {
    // 添加注册来源
    try {
        $userTagService->autoAddUserTag($data['id'], 'FROM_SITE_' . $data['site_id'], '注册自'. \libs\utils\Site::getTitleById($data['site_id']));
    } catch (\Exception $e) {
        PaymentApi::log('用户注册来源站点TAG失败|' . implode('|', $data));
    }
    PaymentApi::log('用户注册来源站点TAG成功|'. implode('|', $data));
}

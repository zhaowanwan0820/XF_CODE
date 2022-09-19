<?php
/**
 * 存管垫资账户余额同步脚本
 * 每天晚上11点开始执行,修复用户已绑卡但是银行名称为空的问题
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('__DEBUG', false);

use libs\utils\PaymentApi;
use core\service\SupervisionAccountService;
use core\service\SupervisionBaseService;


if (0 != app_conf('SUPERVISION_ADVANCE_ACCOUNT')) {
    try {
        $userId = app_conf('SUPERVISION_ADVANCE_ACCOUNT');
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $db->startTrans();
        $svService = new SupervisionAccountService();
        if (!$svService->isSupervisionUser($userId)) {
            throw new \Exception('用户尚未开通存管账户');
        }

        $svUserBalance = $svService->balanceSearch($userId);
        $userActuralMoney = $userActuralFreezeMoney = 0.00;
        if ($svUserBalance['status'] == SupervisionBaseService::RESPONSE_SUCCESS) {
            $userActuralMoney = bcdiv($svUserBalance['data']['availableBalance'], 100, 2);
            $userActuralFreezeMoney = bcdiv($svUserBalance['data']['freezeBalance'], 100, 2);
        }
        $sql = " UPDATE firstp2p_user SET money = '{$userActuralMoney}',lock_money = '{$userActuralFreezeMoney}' WHERE id = '{$userId}'";
        $db->query($sql);
        if ($db->affected_rows() < 1) {
            throw new \Exception('更新网信理财用户余额冻结金额失败,或者已经更新成功');
        }

        $sql = " UPDATE firstp2p_user_third_balance SET supervision_balance = '{$userActuralMoney}', supervision_lock_money = '{$userActuralFreezeMoney}' WHERE user_id = '{$userId}'";
        $db->query($sql);
        if ($db->affected_rows() < 1) {
            throw new \Exception('更新资产中心用户余额冻结金额失败,或者已经更新成功');
        }
        $db->commit();
        echo '更新成功';
   } catch(\Exception $e) {
        $db->rollback();
        echo '更新失败,'.$e->getMessage();
   }
}

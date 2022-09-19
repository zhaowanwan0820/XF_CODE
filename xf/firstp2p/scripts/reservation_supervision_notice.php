<?php
/** 存管系统上线前，线上已经预约的用户，
 *  如果未开通网贷P2P账户和快速投资服务的话，
 *  统一通过短信告知
 *  短信模版标识：TPL_SMS_RESERVE_SUPERVISION_ACCOUNT
 * @author guofeng3 2017-03-09
 */

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\PaymentApi;
use core\dao\UserReservationModel;
use core\dao\UserModel;
use core\service\SupervisionAccountService;

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '2048M');

class reservation_supervision_notice {
    /**
     * 每次获取的数量
     * @var int
     */
    private $pageSize = 0;

    /**
     * 当前时间戳
     * @var int
     */
    private $currentTime = 0;

    private $redis, $msgcenter;

    public function __construct() {
        $this->pageSize = !empty($argv[1]) ? (int)$argv[1] : 500;
        $this->currentTime = time();
        $this->redis = \SiteApp::init()->dataCache;
        $this->msgcenter = new \Msgcenter();
    }

    function run() {
        try{
            $logMsg = implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'supervision openAccount sms start'));
            PaymentApi::log($logMsg);
            echo $logMsg . PHP_EOL;
            $offset = 0;
            $userReservationModel = UserReservationModel::instance();
            $userModel = UserModel::instance();
            $supervisionAccountObj = new SupervisionAccountService();

            while ($reserveList = $userReservationModel->getUserReserveListByBatch($this->currentTime, $offset, $this->pageSize)) {
                foreach ($reserveList as $reservationItem) {
                    // 检查预约状态
                    if ($reservationItem['reserve_status'] != UserReservationModel::RESERVE_STATUS_ING) { //预约结束
                        $logMsg = implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('userReservation has Expire, userId:%s, reserveId:%s', $reservationItem['user_id'], $reservationItem['id'])));
                        PaymentApi::log($logMsg);
                        continue;
                    }
                    // 检查用户是否已开通存管账户
                    $isOpenAccount = $supervisionAccountObj->isSupervisionUser($reservationItem['user_id']);
                    if (!$isOpenAccount) {
                        // 检查用户是否存在
                        $userInfo = $userModel->find($reservationItem['user_id'], 'id,mobile,user_type', true);
                        if (empty($userInfo)) {
                            continue;
                        }
                        // 发送邮件通知用户
                        $this->_sendSupervisionAccountSms($reservationItem, $userInfo);
                    }
                }
                $offset += $this->pageSize;
            }
            $logMsg = implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'supervision openAccount sms end'));
            PaymentApi::log($logMsg);
            echo $logMsg . PHP_EOL;
        } catch (\Exception $e) {
            $errorMsg = implode(' | ', array(__CLASS__, __FUNCTION__, APP, $e->getMessage() . PHP_EOL));
            PaymentApi::log($errorMsg);
        }
    }

    /**
     * 发送存管开户提醒短信
     */
    private function _sendSupervisionAccountSms($userReservation, $user)
    {
        $sendSmsKey = sprintf('RESERVE_SEND_SUPERVISION_ACCOUNT_SMS_%d', $userReservation['id']);
        $expire = max($userReservation['end_time'] - $this->currentTime, 1);
        $redisState = $this->redis->setNx($sendSmsKey, 1, $expire);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        if ($state === 'OK') {
            // 短信模版标识
            $tpl = 'TPL_SMS_RESERVE_SUPERVISION_ACCOUNT';
            $site = $GLOBALS['sys_config']['SITE_LIST_TITLE']['firstp2p'];
            $mobile = $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE ? 'enterprise' : $user['mobile'];
            // 发送短信
            $this->msgcenter->setMsg($mobile, $user['id'], ['time' => date('Y-m-d')], $tpl, '预约用户未开存管账户提醒', '', $site);
            $this->msgcenter->save();
            $logMsg = implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('supervision openAccount sms send success, userId:%s, reserveId:%s', $user['id'], $userReservation['id'])));
            PaymentApi::log($logMsg);
        }else{
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('supervision openAccount sms has send, userId:%s, reserveId:%s', $user['id'], $userReservation['id']))));
        }
    }
}

$obj = new reservation_supervision_notice();
$obj->run();

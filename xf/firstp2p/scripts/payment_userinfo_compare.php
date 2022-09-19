<?php
/**
 * 用户余额核对
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/../system/utils/es_mail.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';
FP::import("libs.common.dict");

use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\dao\UserBankcardModel;
use core\service\UserTagService;

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$args = $_SERVER['argv'];
if (!isset($args[1]) || !is_numeric($args[1])) {
    exit('Usage: /bin/to/php payment_userinfo_compare.php [0-8]'.PHP_EOL);
}
if ($args[1] > 8 || $args[1] < 0) {
    exit('Usage: /bin/to/php payment_userinfo_compare.php [0-8]'.PHP_EOL);
}

\libs\utils\Script::start();
var_dump(Logger::getLogId());
$startTime = microtime(true);
PaymentApi::log('UserinfoCompareFirstStart. group:'.$args[1]);

$db = \libs\db\Db::getInstance('firstp2p', 'slave');
$dbMaster = \libs\db\Db::getInstance('firstp2p', 'master');
//全量用户对账
$exceptionUserIds = array();

$sql = 'SELECT max(id) maxid FROM firstp2p_user';
$maxUserId = $db->getOne($sql);

$startUserId = $args[1]*1000000;
$endUserId = ($args[1] + 1) * 1000000;

// 计算最大边界值
$maxUserId = min($endUserId, $maxUserId);

function _checkFullInfo($userInfo, $ucfpayUserInfo) {
    $checkResult = [];
    $checkResult['syncUser'] = false;
    $checkResult['syncBank'] = false;
    $checkResult['syncRealName'] = false;
    $checkResult['syncBankcard'] = false;
    $checkResult['syncMobile'] = false;
    $checkResult['syncIdno'] = false;
    // 只同步银行信息到用户银行卡表
    $checkResult['syncBankCode'] = false;

    // 个人用户筛选
    if (!empty($userInfo['mobile']) && !empty($userInfo['mobile_code'])) {
        $mobile = $userInfo['mobile'];
        if ($userInfo['user_type'] == '1' || ($userInfo['mobile_code'] == '86' && $mobile{0} == '6')) {
            return false;
        }
    }

    if (empty($userInfo['mobile']) || empty($userInfo['real_name']) || empty($userInfo['bankcard']) || empty($userInfo['idno']) || empty($userInfo['short_name']) || empty($ucfpayUserInfo['phone']) || empty($ucfpayUserInfo['real_name']) || empty($ucfpayUserInfo['bankcardNo']) || empty($ucfpayUserInfo['idno']) || empty($ucfpayUserInfo['bankCode'])) {
        return false;
    }
    //用户基本信息比对
    if ($userInfo['real_name'] != $ucfpayUserInfo['real_name']) {
        $checkResult['syncUser'] = true;
        $checkResult['syncRealName'] = true;
    }
    if ($userInfo['mobile'] != $ucfpayUserInfo['phone']) {
        $checkResult['syncUser'] = true;
        $checkResult['syncMobile'] = true;
    }
    if ($userInfo['idno'] != $ucfpayUserInfo['idno']) {
        $checkResult['syncUser'] = true;
        $checkResult['syncIdno'] = true;
    }
    // 用户银行卡信息
    if ($userInfo['bankcard'] != $ucfpayUserInfo['bankcardNo']) {
        $checkResult['syncBank'] = true;
        $checkResult['syncBankcard'] = true;
    } else {
        if ($userInfo['short_name'] != $ucfpayUserInfo['bankCode']) {
            $checkResult['syncBankCode'] = true;
        }
    }

    if (!$checkResult['syncUser'] && !$checkResult['syncBank'] && !$checkResult['syncBankCode']) {
        return true;
    }
    return $checkResult;
}

function getUcfpayUserinfo($p2pUserInfo) {
    $ucfpayResult = [];
    $userBankCardObj = new \core\service\UserBankcardService();
    foreach ($p2pUserInfo as $info) {
        $params = ['userId' => $info['id']];
        $result = \libs\utils\PaymentApi::instance()->request('searchuserinfo', $params);
        if (empty($result)) {
            $exceptionUserIds[] = $info['id'];
        }
        $ucfpayInfo = [];
        if (!empty($result['respCode']) && $result['respCode'] == '00') {
            if ($result['status'] == '00') {
                // 用户存在
                $ucfpayInfo = [];
                $ucfpayInfo['idno'] = $result['cardNo'];
                $ucfpayInfo['real_name'] = $result['realName'];
                $ucfpayInfo['phone'] = $result['phone'];
                $ucfpayInfo['userType'] = $result['userType'];
                // 获取支付系统所有银行卡列表-安全卡数据
                $cardResult = $userBankCardObj->queryBankCardsList($params['userId'], true);
                if (!empty($cardResult['list'])) {
                    $ucfpayInfo['bankcardNo'] = $cardResult['list']['cardNo'];
                    $ucfpayInfo['bankCode'] = $cardResult['list']['bankCode'];
                    $ucfpayInfo['certStatus'] = $cardResult['list']['certStatus'];
            }
            }
        }
        $ucfpayResult[$info['id']] = $ucfpayInfo;
    }
    return $ucfpayResult;
}

for ($i = $startUserId; $i <= $maxUserId; $i += 1000)
{
    $s = microtime(true);
    $sql ="SELECT u.id,u.real_name,u.idno,u.mobile,u.payment_user_id,ub.bankcard,ub.cert_status,b.short_name FROM firstp2p_user u LEFT JOIN firstp2p_user_bankcard ub ON ub.user_id = u.id LEFT JOIN firstp2p_bank b ON b.id = ub.bank_id WHERE u.is_effect = 1 AND u.id BETWEEN {$i} AND {$i}+1000 ";
    $p2pUserInfo = $db->getAll($sql);
    if (empty($p2pUserInfo)) {
        continue;
    }
    $ucfpayUserInfo = getUcfpayUserinfo($p2pUserInfo);
    foreach ($p2pUserInfo as $userInfo)
    {
        $userId = $userInfo['id'];
        $userInfo['cert_status'] = array_search($userInfo['cert_status'], UserBankcardModel::$cert_status_map);

        // 完全不为空
        $checkResult = _checkFullInfo($userInfo, $ucfpayUserInfo[$userId]);
        if ($checkResult === true) {
            // 打用户资料完善的tag
            $userTagService  = new UserTagService();
            $userTagService->addUserTagsByConstName($userId, 'SUPERVISION_FULLINFO_INIT');
            \libs\utils\PaymentApi::log(' tagUser addTag:SUPERVISION_INIT '.$userId);
        }
        else if ($checkResult !== false)
        {
           try {
                if ($checkResult['syncBankCode']) {
                    $bankSql = "SELECT id FROM firstp2p_bank WHERE short_name = '{$ucfpayUserInfo[$userId]['bankCode']}'";
                    $bankId = $db->getOne($bankSql);
                    if (empty($bankId)) {
                        throw new \Exception($ucfpayUserInfo[$userId]['bankCode'].' not exists in firstp2p');
                    }
                    $data = [];
                    $data['bank_id'] = $bankId;
                    $dbMaster->autoExecute('firstp2p_user_bankcard', $data, 'UPDATE', ' user_id = '.$userId);
                    \libs\utils\PaymentApi::log('tagUser bankname '.$userId .' success');
               }
           } catch (\Exception $e) {
                \libs\utils\PaymentApi::log('tagUser bankname '.$userId.' failed, msg:'.$e->getMessage());
           }
        }
    }
    PaymentApi::log('timeeclapsed:'.(microtime(true) - $s));
}

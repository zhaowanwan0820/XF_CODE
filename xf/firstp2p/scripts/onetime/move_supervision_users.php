<?php
/**
 * 根据相关标的给相关用户开存管户
 */
ini_set('memory_limit', '1024M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;
use \libs\utils\PaymentApi;
use \core\service\UserTagService;
use \core\dao\UserModel;
use \core\service\UserService;
use \core\service\SupervisionAccountService;

$UPDATE_USERS = empty($argv[1]) ? 0 : true;
$MOVE_USER = empty($argv[2]) ? 0 : true;

define('MOVE_USER', $MOVE_USER);

Logger::info("move supervision user start. updateUser:{$UPDATE_USERS}");

//投资人
$sql = "SELECT DISTINCT(user_id) FROM firstp2p_deal_load WHERE deal_id IN (SELECT id FROM firstp2p_deal WHERE deal_type=0 AND report_status=0 AND deal_status=4 AND is_delete=0 AND type_id=29)";
$result = Db::getInstance('firstp2p')->getAll($sql);

Logger::info("move supervision user start. invest users. count:".count($result));

foreach ($result as $item) {
    $userId = intval($item['user_id']);
    $user = UserModel::instance()->find($userId, '*' , true);

    //是否已开户
    if ($user['supervision_user_id'] > 0) {
        Logger::info("move supervision user failed. 已开户. id:{$user['id']}, supervision_user_id:{$user['supervision_user_id']}");
        continue;
    }

    $hasDuotou = '';
    $localPurpose = 1;
    $paymentPurpose = '01';
    $grantList = array('INVEST', 'SHARE_PAYMENT');
    $userType = GetUserType($user);

    if ($UPDATE_USERS) {
        UpdateUser($userId, $localPurpose, $paymentPurpose, $userType, $grantList);
    }

    Logger::info("move users done. investUser. id:{$userId}, userType:{$userType}, supervion_user_id:{$user['supervion_user_id']}, originPurpose:{$user['user_purpose']}, hasDuotou:{$hasDuotou}, targetLocalPurpose:{$localPurpose}, targetPaymentPurpose:{$paymentPurpose}, grant:".implode(',', $grantList));
    Logger::info(sprintf("move user for stat. %s, %s, %s, %s, %s, %s, %s, %s", 'invest', $userId, $userType, $user['supervision_user_id'], $user['user_purpose'], $hasDuotou, $localPurpose, $paymentPurpose));
}

//借款人
$sql = "SELECT DISTINCT(user_id) FROM firstp2p_deal WHERE deal_type=0 AND report_status=0 AND deal_status=4 AND is_delete=0 AND type_id=29";
$result = Db::getInstance('firstp2p')->getAll($sql);

Logger::info("move supervision user start. loan users. count:".count($result));

foreach ($result as $item) {
    $userId = intval($item['user_id']);
    $user = UserModel::instance()->find($userId, '*' , true);

    //是否有在投智多鑫
    $hasDuotou = HasDuotou($user['id']);

    if ($hasDuotou) {
        //有智多鑫为混合户
        $localPurpose = 0;
        $paymentPurpose = '06';
        $grantList = array('INVEST', 'REPAY', 'SHARE_PAYMENT');
    } else {
        //没有为借款户
        $localPurpose = 2;
        $paymentPurpose = '02';
        $grantList = array('REPAY', 'SHARE_PAYMENT');
    }

    $userType = GetUserType($user);

    if ($UPDATE_USERS) {
        UpdateUser($userId, $localPurpose, $paymentPurpose, $userType, $grantList);
    }

    Logger::info("move users done. loanUser. id:{$userId}, type:{$userType}, supervion_user_id:{$user['supervion_user_id']}, originPurpose:{$user['user_purpose']}, hasDuotou:{$hasDuotou}, targetLocalPurpose:{$localPurpose}, targetPaymentPurpose:{$paymentPurpose}, grant:".implode(',', $grantList));
    Logger::info(sprintf("move user for stat. %s, %s, %s, %s, %s, %s, %s, %s", 'loan', $userId, $userType, $user['supervision_user_id'], $user['user_purpose'], $hasDuotou, $localPurpose, $paymentPurpose));
}

/**
 * 更新用户相关信息
 */
function UpdateUser($userId, $localPurpose, $paymentPurpose, $userType, $grantList)
{
    Logger::info("update user. id:{$userId}, localPurpose:{$localPurpose}, purpose:{$paymentPurpose}, type:{$userType}, grant:".implode(',', $grantList));

    if (MOVE_USER) {
        //支付端开户
        UpdatePaymentUser($userId, $paymentPurpose, $userType);
    } else {
        //刷用户用途与授权
        $accountService = new SupervisionAccountService();
        $result = $accountService->updateUserPurpose($userId, $localPurpose, $paymentPurpose, $grantList);

        Logger::info("request update user purpose. userId:{$userId}, result:{$result}");

        //打Tag
        AddUserTag($userId);

        //更新本地用户supervision_user_id
        $resultUpdate = Db::getInstance('firstp2p')->update('firstp2p_user', array('supervision_user_id' => $userId), "id='{$userId}'");
    }
}

/**
 * 是否有在投智多鑫
 */
function HasDuotou($userId)
{
    $sql = "SELECT dt_norepay_principal FROM firstp2p_user_loan_repay_statistics where user_id='{$userId}'";
    $result = Db::getInstance('firstp2p')->getOne($sql);

    return $result > 0 ? 1 : 0;
}

/**
 * 支付开户
 */
function UpdatePaymentUser($userId, $purpose, $userType)
{
    $params = array(
        'users' => json_encode(array(
            array('userId' => $userId, 'bizType' => $purpose, 'userType' => $userType),
        )),
    );

    $result = PaymentApi::instance()->request('moveUser', $params);

    Logger::info("request move users. userId:{$userId}, result:".var_export($result, true));
}

/**
 * 获取用户类型
 */
function GetUserType($user)
{
    $userService = new UserService($user);
    $userType = $userService->isEnterpriseUser() ? 2 : 1;

    return $userType;
}

/**
 * 给用户打相关的tag
 */
function AddUserTag($userId)
{
    $tagService = new UserTagService();
    $tagService->addUserTagsByConstName($userId, 'SV_UNACTIVATED_USER');
}

Logger::info("move supervision user end.");

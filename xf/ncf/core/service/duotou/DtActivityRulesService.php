<?php
/**
 * DtActivityRulesService.php
 * 智多鑫活动规则
 */
namespace core\service\duotou;

use core\service\dealload\DealLoadService;
use core\dao\deal\DealLoadModel;
use core\service\user\UserService;

class DtActivityRulesService extends DuotouService
{
    public $rules = array(
        'loadGte3' => 'loadGte3', // 投资次数大于等于3次
    );

    private static $instance = null;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isMatchRule($rule, $params=array())
    {
        if (!isset($this->rules[$rule]) || !is_array($params)) {
            return false;
        }

        $this->initParams($params);
        return $this->$rule();
    }

    public function initParams($params)
    {
        foreach ($params as $k=>$v) {
            $this->{$k} = $v;
        }
        return $this;
    }

    private function loadGte3() {
        //智多鑫新用户组白名单,白名单用户组的用户作为新用户处理
        if (!empty($GLOBALS['user_info'])) {
            $userInfo = $GLOBALS['user_info'];
        } else {
            $userInfo = UserService::getUserById($this->userId);
        }

        \FP::import("libs.common.dict");
        $duotouUserGroupWhiteList = \dict::get('DUOTOU_NEW_USER_GROUP_WHITELIST');
        $duotouUserGroupWhiteList = empty($duotouUserGroupWhiteList) ? [] : $duotouUserGroupWhiteList;
        if ((in_array($userInfo['group_id'], $duotouUserGroupWhiteList) === true)) {
            return 1;
        }

        $dealLoadService = new DealLoadService();
        $loadCnt = $dealLoadService->getCountByUserIdInSuccess($this->userId, array(DealLoadModel::$SOURCE_TYPE['dtb']), false);
        $vars = array(
            'userId' => $this->userId,
        );
        $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getInvestNumByUserId', $vars));
        if (isset($response)) {
            $loadCnt+=$response['data'];
        }
        return $loadCnt < 3 ? 1 : 0;
    }
}

<?php
/**
 *------------------------------------------------------------------
 * 红包客服自助查询平台
 *------------------------------------------------------------------
 * @auther luzhengshuai<luzhengshuai@ucfgroup.com>
 *------------------------------------------------------------------
 */
use core\service\UserService;
use core\dao\BonusModel;
use core\dao\DealLoadModel;
use core\dao\UserCarryModel;
use core\dao\UserModel;
use core\dao\CompoundRedemptionApplyModel;
class BonusQueryAction extends CommonAction {

    private $consume_types = array('1' => '仅限投资', '2' => '可提现');
    private $referTypeMap = array(
        BonusModel::BONUS_FIRST_DEAL_FOR_INVITE => BonusModel::BONUS_FIRST_DEAL_FOR_DEAL,
        BonusModel::BONUS_REGISTER_FOR_INVITE => BonusModel::BONUS_REGISTER_FOR_NEW,
        BonusModel::BONUS_BINDCARD_FOR_INVITE => BonusModel::BONUS_BINDCARD_FOR_NEW,
        BonusModel::BONUS_CASH_FOR_INVITE => array(BonusModel::BONUS_CASH_FOR_NEW, BonusModel::BONUS_CASH_NORMAL_FOR_NEW),
    );

    private $typeConfig = array();

    public function __construct() {
        parent::__construct();
        $this->model = MI('Bonus');
        $this->typeConfig = BonusModel::$nameConfig;
    }

    public static $userMobileList = array();

    public static $userList = array();

    /**
     * 新手双返红包列表页面
     */
    public function index() {

        $userService = new UserService();
        //定义条件
        $where = ' 1=1';

        $this->assign('taskList', MI('BonusTask')->field('id,name')->select());
        $this->assign('typeMap', $this->typeConfig);
        $type = $_GET['type'] ? intval($_GET['type']) : (($_GET['type'] !== '' && $_GET['type'] !== NULL) ? 0 : 10000);
        $_REQUEST['type'] = $type;
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $status = intval($_GET['status']);
        $mobile = intval($_GET['mobile']);
        $owner_uid = intval($_GET['owner_uid']);
        $task_id = intval($_GET['task_id']);
        if ($type !== 10000) {
            $where .= " AND type = $type";
        } else {
            $where .= " AND type IN (" .implode(',', array_keys($this->typeConfig)). ")";
        }

        if ($owner_uid) {
            $where .= " AND owner_uid = " . $owner_uid;
        }

        if ($task_id) {
            $where .= " AND task_id = " . $task_id;
        }

        if ($timeStart) {
            $where .= " AND created_at >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND created_at <= '". strtotime($timeEnd) ."'";
        }

        if ($status) {
            $where .= " AND status = $status";
        }

        $userInfo = '';
        if ($mobile) {
            $userInfo = UserModel::instance()->findByViaSlave("mobile = '$mobile'", 'id,user_name,real_name,mobile');
            if (!empty($userInfo)) {
                $where .= " AND (mobile = '$mobile' OR owner_uid = {$userInfo['id']})";
            } else {
                $where .= " AND mobile = '$mobile'";
            }
        }

        if (empty ($this->model) || (!$mobile && !$owner_uid)) {
            $this->display();
            return false;
        }

        $this->_list($this->model, $where);
        $result = $this->get('list');
        if (empty($result)) {
            $this->display();
            return false;
        }

        $dataList = array();
        $referMobile = array();
        $refetUid = array();
        foreach ($result as $key => $item) {
            if ($item['owner_uid'] && !$userInfo) {
                $userId = $item['owner_uid'];
                $userInfo = UserModel::instance()->find($userId, 'id,user_name,real_name,mobile', true);
            }

            $data['id'] = $item['id'];
            $data['real_name'] = userNameFormat($userInfo['real_name']);
            $data['user_name'] = $userInfo['user_name'];
            $data['mobile'] = adminMobileFormat($userInfo['mobile']);
            $data['bonusType'] = $this->typeConfig[$item['type']];
            $data['money'] = $item['money'];
            $data['create_time'] = date('Y-m-d H:i:s', $item['created_at']);
            $data['expire_time'] = date('Y-m-d H:i:s', $item['expired_at']);
            $referBonusInfo = array();
            if ($item['refer_mobile'] > 10000000000) {
                $referBonusInfo = $this->_getReferBonusByMobile($item['refer_mobile'], $this->referTypeMap[$item['type']]);
            } else if ($item['refer_mobile'] > 0) {
                $referBonusInfo = $this->_getReferBonusByUid($item['refer_mobile'], $this->referTypeMap[$item['type']]);
            }
            $data['referBonus'] = $referBonusInfo;
            $dataList[] = $data;
        }

        $this->assign('list', $dataList);
        $this->display();
    }

    private function _getReferBonusByMobile($mobile, $type) {
        if (!$type) {
            return false;
        }

        $userInfo = self::$userMobileList[$mobile];
        if (empty($userInfo)) {
            $userInfo = UserModel::instance()->findByViaSlave("mobile = '$mobile'", 'id,user_name,real_name,mobile');
            if (!empty($userInfo)) {
                self::$userMobileList[$mobile] = $userInfo;
                self::$userList[$userInfo['id']] = $userInfo;
            } else {
                return false;
            }
        }

        $data = $this->_getBonusInfo($mobile, $userInfo, $type);
        return $data;
    }

    private function _getBonusInfo($mobile, $userInfo, $type) {

        $data = array();
        if (!is_array($type)) {
            $condition = "(mobile = '$mobile' OR owner_uid = {$userInfo['id']}) AND type = $type";
        } else {
            $condition = "(mobile = '$mobile' OR owner_uid = {$userInfo['id']}) AND type IN (".implode(',', $type).")";
        }
        $bonus = BonusModel::instance()->findByViaSlave($condition);

        if (empty($bonus)) {
            return false;
        }
        $data['mobile'] = adminMobileFormat($bonus['mobile']);
        $data['real_name'] = userNameFormat($userInfo['real_name']);
        $data['user_name'] = $userInfo['user_name'];
        $data['create_time'] = date('Y-m-d H:i:s', $bonus['created_at']);
        $data['expire_time'] = date('Y-m-d H:i:s', $bonus['expired_at']);
        $data['money'] = $bonus['money'];

        if ($type == BonusModel::BONUS_FIRST_DEAL_FOR_DEAL) {
            $dealLoadInfo = DealLoadModel::instance()->getFirstDealByUser($userInfo['id']);
            // TODO 获取用户首投时间和信息
            if ($dealLoadInfo['deal_type'] == 0) {
                $data['desc'] = '首次投资';
                $data['trigger_time'] = to_date($dealLoadInfo['create_time']);
            } else {
                $condition = sprintf("`deal_load_id`='%d'", $dealLoadInfo['id']);
                $redeemApplyInfo = CompoundRedemptionApplyModel::instance()->findByViaSlave($condition);
                $data['desc'] = '通知贷赎回';
                $data['trigger_time'] = to_date($redeemApplyInfo['create_time']);
            }
        }

        if ($type == array(BonusModel::BONUS_CASH_FOR_NEW, BonusModel::BONUS_CASH_NORMAL_FOR_NEW)) {
            // TODO 获取用户首投还是提现
            $data['desc'] = '现金红包活动';
            $dealTime = 0;
            $carryTime = 0;
            $dealLoadInfo = DealLoadModel::instance()->getFirstDealByUser($userInfo['id']);
            $userCarryInfo = $GLOBALS['db']->getOne("SELECT * FROM firstp2p_user_carry WHERE user_id = {$userInfo['id']} AND withdraw_status = ".UserCarryModel::WITHDRAW_STATUS_SUCCESS ." ORDER BY id ASC LIMIT 1");
            if (!empty($dealLoadInfo)) {
                $dealTime = $dealLoadInfo['create_time'];
            }

            if (!empty($userCarryInfo)) {
                $carryTime = $userCarryInfo['create_time'];
            }
            $data['trigger_time'] = ($dealTime > $carryTime) ? to_date($dealTime) : to_date($carryTime);
        }

        return $data;
    }

    private function _getReferBonusByUid($uid, $type) {
        if (!$type) {
            return false;
        }

        $userInfo = self::$userList[$uid];
        if (empty($userInfo)) {
            $userInfo = UserModel::instance()->findByViaSlave("id = $uid", 'id,user_name,real_name,mobile');
            if (!empty($userInfo)) {
                self::$userMobileList[$userInfo['mobile']] = $userInfo;
                self::$userList[$userInfo['id']] = $userInfo;
            } else {
                return false;
            }
        }

        $data = $this->_getBonusInfo($userInfo['mobile'], $userInfo, $type);
        return $data;
    }
}

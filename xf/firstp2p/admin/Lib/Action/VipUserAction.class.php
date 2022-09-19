<?php

use libs\db\Db;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\dao\UserModel;
use core\service\vip\VipService;
use core\dao\vip\VipLogModel;
use core\dao\vip\VipPrivilegeModel;
use core\dao\vip\VipAccountModel;

/**
 * vip用户
 */
class VipUserAction extends CommonAction {
    private $vipLevels = array();

    const CONFIG_KEY = 'user_vip_rebate_config'; //api_conf配置名

    public function __construct() {
        parent::__construct();
        require_once APP_ROOT_PATH . "/system/libs/user.php";
        foreach (VipEnum::$vipGrade as $key=>$value) {
            if ($key == VipEnum::VIP_GRADE_PT) {
                continue;
            }
            $this->vipLevels[$key] = $value['name'];
        }
    }

    public function configLevel() {
        $vipService = new VipService();
        $confVal = $vipService->getLevelVipConf();
        $privilegeList = VipPrivilegeModel::instance()->getAllEffectPrivilegeList();
        foreach ($privilegeList as $item) {
            $list[$item['id']] = array(
                'privilegeId' => $item['id'],
                'name' => $item['privilege_name'],
            );
        }
        $this->assign('vipLevels', $confVal);
        $this->assign('vipLevelPrivileges', $list);
        $this->display();
    }

    // 保存会员配置
    public function saveLevelConfig() {
        B('FilterString');
        $vipService = new VipService();
        $vipConfValue = json_encode($_REQUEST['vipConfig'], JSON_UNESCAPED_UNICODE);
        $res = $vipService->updateVipLevelConf($vipConfValue);
        if (false !== $res) {
            save_log($vipConfValue.L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            save_log($vipConfValue.L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, L("UPDATE_FAILED"));
        }
    }

    // 会员等级奖励配置
    public function configRebate() {
        $allowanceTypes = array(
            CouponGroupEnum::ALLOWANCE_TYPE_COUPON => '礼券',
            CouponGroupEnum::ALLOWANCE_TYPE_DISCOUNT => '投资券'
        );

        $vipService = new VipService();
        $confVal = $vipService->getVipRebateConf();
        $this->assign('vipLevels', $confVal);
        $this->assign('allowanceTypes', $allowanceTypes);
        $this->display();
    }

    //保存会员配置
    public function saveRebateConfig() {
        B('FilterString');

        $vipService = new VipService();
        $vipConfValue = json_encode($_REQUEST['vipConfig'], JSON_UNESCAPED_UNICODE);
        $res = $vipService->updateVipRebateConf($vipConfValue);
        if (false !== $res) {
            save_log($vipConfValue.L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            save_log($vipConfValue.L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, L("UPDATE_FAILED"));
        }
    }

    // 会员等级记录查询
    public function levelLog() {
        // 定义条件
        $where = ' 1=1';
        $userId = intval($_GET['user_id']);
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $mobile =  intval($_GET['mobile']);
        $serviceLevel = intval($_GET['serviceLevel']);
        $vipLevel = intval($_GET['vipLevel']);
        $vipLogType = intval($_GET['vipLogType']);

        if ($mobile) {
            $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id');
            $userId = $userInfo['id'];
        }

        if ($userId) {
            $where .= " AND user_id = " . $userId;
        }

        if ($timeStart) {
            $where .= " AND create_time >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND create_time <= '". strtotime($timeEnd) ."'";
        }

        if ($serviceLevel) {
            $where .= " AND service_grade = ".$serviceLevel;
        }

        if ($vipLevel) {
            $where .= " AND actual_grade = ".$vipLevel;
        }

        if ($vipLogType) {
            $where .= " AND log_type = ".$vipLogType;
        }

        $this->assign('vipLogTypes', VipEnum::$vipLogTypes);
        $this->assign('vipLevels', $this->vipLevels);
        if ($where == ' 1=1') {
            $this->assign('list', array());
            $this->display();
            return;
        }

        $this->model = MI('VipLog', 'vip', 'slave');

        $_REQUEST ['listRows'] = 25;
        $result = $this->_list($this->model, $where);
        if (!$result) {
            $this->assign('list', array());
            $this->display();
            return;
        }

        $dataList = array();
        $userCache = array();
        $userTipCache = array();
        foreach ($result as $key=>$item) {
            $userId = $item['user_id'];
            if (!$userCache[$userId]) {
                $userInfo = UserModel::instance()->find($userId, 'id,user_name,real_name,mobile', true);
                $userCache[$userId] = $userInfo;
            } else {
                $userInfo = $userCache[$userId];
            }
            // 增加vip详情tips
            if (!isset($userTipCache[$userId])) {
                $userTipCache[$userId] = $this->getUserTips($userId);
            }
            $userTip = $userTipCache[$userId];
            $data = $item;
            $data['real_name'] = userNameFormat($userInfo['real_name']);
            $data['user_name'] = $userInfo['user_name'];
            $data['mobile'] = adminMobileFormat($userInfo['mobile']);
            $data['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

            $serviceGrade = intval($data['service_grade']);
            $data['service_grade_desc'] = isset($this->vipLevels[$serviceGrade]) ? $this->vipLevels[$serviceGrade] : '普通用户';

            $actual_grade = intval($data['actual_grade']);
            $data['actual_grade_desc'] = isset($this->vipLevels[$actual_grade]) ? $this->vipLevels[$actual_grade] : '普通用户';

            $logType = intval($data['log_type']);
            $data['log_type_desc'] = isset(VipEnum::$vipLogTypes[$logType]) ? VipEnum::$vipLogTypes[$logType] : '';
            $data['user_tip'] = $userTip;

            $dataList[] = $data;
        }

        $this->assign('list', $dataList);
        $this->display();
    }

    private function  getUserTips($userId) {
        $userTip = '';
        $sql = 'SELECT * FROM firstp2p_vip_account WHERE user_id='. intval($userId);
        $userVipInfo = VipAccountModel::instance()->findBySql($sql)->getRow();
        if ($userVipInfo) {
            $userTip .= '实时会员积分:'.$userVipInfo['point']."</br>";
            $userTip .= '服务会员等级:'.(isset($this->vipLevels[$userVipInfo['service_grade']]) ? $this->vipLevels[$userVipInfo['service_grade']] : '普通用户')."</br>";
            $userTip .= '实际会员等级:'.(isset($this->vipLevels[$userVipInfo['actual_grade']]) ? $this->vipLevels[$userVipInfo['actual_grade']] : '普通用户')."</br>";
            $userTip .= '更新时间:'. date('Y-m-d H:i:s',$userVipInfo['update_time'])."</br>";
            $userTip .= '是否保级:'. ($userVipInfo['is_relegated'] ? '保级' : '未保级'). "</br>";
            if ($userVipInfo['is_relegated']) {
                $userTip .= '保级时间:'.date('Y-m-d H:i:s',$userVipInfo['relegate_time']). "</br>";
            }
        }
        return $userTip;
    }

    // 会员等级返利查询
    public function rebateLog() {
        // 定义条件
        $where = ' 1=1';
        $userId = intval($_GET['user_id']);
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $mobile =  intval($_GET['mobile']);

        if ($mobile) {
            $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id');
            $userId = $userInfo['id'];
        }

        if ($userId) {
            $where .= " AND user_id = " . $userId;
        }

        if ($timeStart) {
            $where .= " AND create_time >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND create_time <= '". strtotime($timeEnd) ."'";
        }

        if ($where == ' 1=1') {
            $this->assign('list', array());
            $this->display();
            return;
        }

        $this->model = MI('VipRateLog', 'vip', 'slave');

        $_REQUEST ['listRows'] = 30;
        $result = $this->_list($this->model, $where);
        if (!$result) {
            $this->assign('list', array());
            $this->display();
            return;
        }

        $dataList = array();
        $userCache = array();
        foreach ($result as $key=>$item) {
            $userId = $item['user_id'];
            if (!$userCache[$userId]) {
                $userInfo = \core\dao\UserModel::instance()->find($userId, 'id,user_name,real_name,mobile', true);
                $userCache[$userId] = $userInfo;
            } else {
                $userInfo = $userCache[$userId];
            }

            $data = $item;
            $data['real_name'] = userNameFormat($userInfo['real_name']);
            $data['user_name'] = $userInfo['user_name'];
            $data['mobile'] = adminMobileFormat($userInfo['mobile']);
            $data['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $data['allowance_money'] = format_price($data['allowance_money'], false);
            $dataList[] = $data;
        }

        $this->assign('list', $dataList);
        $this->display();
    }

    public function add() {
        $vipService = new VipService();
        $tmpGradeList = $vipService->getVipGradeList();
        $gradeList = array();
        foreach ($tmpGradeList as $grade => $name) {
            $gradeList[VipEnum::$vipGradeNoToAlias[$grade]] = array('url' => '', 'gradename' => $name, 'gradeLevel' => $grade);
        }
        $gradeList['grey'] = array('gradename' => '置灰状态');
        $this->assign('gradeList', $gradeList);
        $this->display();
    }

    public function view() {
        $id = intval($_REQUEST['id']);
        $task = VipPrivilegeModel::instance()->getTask($id);
        $task['effect_time'] = date('Y-m-d H:i:s', $task['effect_time']);
        $task['extra_info'] = json_decode($task['extra_info'], true);
        $img_conf = empty($task['img_conf']) ? array() : json_decode($task['img_conf'],true);
        $vipService = new VipService();
        $tmpGradeList = $vipService->getVipGradeList();
        $gradeList = array();
        foreach($tmpGradeList as $gradeLevel => $gradeName) {
            $gradeAlias = VipEnum::$vipGradeNoToAlias[$gradeLevel];
            $gradeList[$gradeAlias] = array(
                'url' => isset($img_conf[$gradeAlias]) ? $img_conf[$gradeAlias] : '',
                'gradename' => $gradeName,
                'gradeLevel' => $gradeLevel
            );
        }
        $gradeList['grey'] = array('url' => isset($img_conf['grey']) ? $img_conf['grey'] : '', 'gradename' => '置灰状态', 'gradeLevel' => '');
        $this->assign('gradeList', $gradeList);
        $this->assign('item', $task);
        $this->display();
    }

    public function savePrivilege() {
        $result = array("status" => 1, "errorMsg" => '');
        $id = intval($_REQUEST['id']);
        $privilege_name = trim($_REQUEST['privilege_name']);
        $privilege_desc = trim($_REQUEST['privilege_desc']);
        $privilege_detail = trim($_REQUEST['privilege_detail']);
        $weight = intval($_REQUEST['weight']);
        $img_conf = $this->parseImgConf($_REQUEST);
        $status = intval($_REQUEST['status']);
        $effect_time = $_REQUEST['effect_time'] ? strtotime($_REQUEST['effect_time']) : time();
        $buttonDesc = trim($_REQUEST['extra_info']['buttonDesc']);
        $buttonUrl = trim($_REQUEST['extra_info']['buttonUrl']);
        $buttonInfo = array('buttonDesc' => $buttonDesc, 'buttonUrl' => $buttonUrl);
        $extra_info = json_encode($buttonInfo, JSON_UNESCAPED_UNICODE);
        $data = array('privilege_name' => $privilege_name, 'privilege_desc' => $privilege_desc, 'privilege_detail' => $privilege_detail, 'weight' => $weight, 'img_conf' => json_encode($img_conf), 'status' => $status, 'effect_time' => $effect_time, 'extra_info' => $extra_info);
        if ($id) {
            $data['id'] = $id;
            $res = VipPrivilegeModel::instance()->updateTask($data);
            $this->success("更新成功",0, u(MODULE_NAME."/privilegelist"));
        } else {
            $res = VipPrivilegeModel::instance()->addTask($data);
            $this->success("添加成功",0, u(MODULE_NAME."/privilegelist"));
        }
    }

    public function privilegelist() {
        $condition = ' 1=1';
        $_REQUEST ['listRows'] = 20;
        $this->model = MI('VipPrivilege', 'vip', 'slave');
        $this->_list($this->model, $condition);
        $list = $this->get('list');
        $list = $this->formatList($list);

        $this->assign('list', $list);
        $this->display();
    }

    private function formatList($list) {
        $vipService = new VipService();
        $gradeList = $vipService->getVipGradeList();
        foreach ($list as &$item) {
            $imgList = json_decode($item['img_conf'],true);
            $imgDesc = '';
            foreach ($imgList as $k=>$v) {
                $imgDesc .= (isset($gradeList[VipEnum::$vipGradeAliasToNo[$k]]) ? $gradeList[VipEnum::$vipGradeAliasToNo[$k]] : '置灰状态').":<a href='$v'>". $v."</a></br>";
            }
            $item['status_desc'] = VipPrivilegeModel::$statusDesc[$item['status']];
            $item['img_desc'] = $imgDesc;
            $item['extra_info'] = json_decode($item['extra_info'],true);
        }
        return $list;
    }

    private function parseImgConf($data) {
        $vipService = new VipService();
        $gradeList = $vipService->getVipGradeList();
        $result = array();
        foreach($gradeList as $k=> $v) {
            $imgKey = 'img_'.VipEnum::$vipGradeNoToAlias[$k].'_url';
            $result[VipEnum::$vipGradeNoToAlias[$k]] = $data[$imgKey];
        }
        $result['grey'] = $data['img_grey_url'];
        return $result;
    }

    //上传图片
    public function loadFile() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            $rel = array("code" => 0,"message" => "图片为空");
        }
    
        if (!empty($file)) {
            $uploadFileInfo = array(
                    'file' => $file,
                    'isImage' => 1,
                    'asAttachment' => 1,
                    'limitSizeInMB' => round(200 / 1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
        }
        if(!empty($result['aid']) && empty($result['errors'])){
            $imgUrl = get_attr($result['aid'],1,false);
            $rel = array("code" => 1,"imgUrl" => $imgUrl);
        }else if(!empty($result['errors'])){
            $rel = array("code" => 0,"message" => end($result['errors']));
        }else{
            $rel = array("code" => 0,"message" => "图片上传失败");
        }
        echo  json_encode($rel);
    }

    public function transfer() {
        return ;
        $all = VipEnum::$vipPrivilege;
        foreach ($all as $id => $item) {
            $data = array();
            $data = array(
                'id' => $item['privilegeId'],
                'privilege_name' => $item['name'],
                'privilege_desc' => $item['describe'],
                'privilege_detail' => $item['detail'].'</br>'.VipEnum::$privilegeDisclaimer,
                'weight' => 100 - $item['privilegeId'],
                'create_time' => time(),
                'effect_time' => strtotime('20170918'),
                'status' => 1,
            );
            $imgConf = VipEnum::$privilegeImgUrl[$id];
            $imgArray = array();
            foreach($imgConf as $grade => $gradeUrl) {
                $imgArray[VipEnum::$vipGradeNoToAlias[$grade]] = $gradeUrl;
            }
            $imgArray['grey'] = $item['imgUrl'];
            $data['img_conf'] = json_encode($imgArray);
            $resId =VipPrivilegeModel::instance()->addTask($data);
            var_dump($resId);
        }
    }
}

<?php

use libs\db\Db;
use libs\utils\ExportCsv;
use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\UserLoanRepayStatisticsModel;
use core\dao\vip\ActivityUserModel;
use core\dao\DealLoadModel;
use core\dao\vip\ActivityModel;
use core\dao\vip\VipAccountModel;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\AccountService;

class OnlineRegistrationAction  extends CommonAction {

    public function __construct() {
        parent::__construct();
    }
    public static $LEVEL = array(
        VipEnum::VIP_GRADE_PT => '无限制',
        VipEnum::VIP_GRADE_QT => '青铜及以上VIP',
        VipEnum::VIP_GRADE_BY => '白银及以上VIP',
        VipEnum::VIP_GRADE_HJ => '黄金及以上VIP',
        VipEnum::VIP_GRADE_BJ => '铂金及以上VIP',
        VipEnum::VIP_GRADE_ZS => '钻石及以上VIP',
        VipEnum::VIP_GRADE_HG => '皇冠VIP',
    );
    public static $RELATION = array(
        1 => '朋友',
        2 => '父母',
        3 => '配偶',
        4 => '孩子',
    );
    public static $SEX = array(
        0 => '男',
        1 => '女',
    );

    /**
     * 在线活动配置
     */
    public function addActivity() {
        $this->assign("level",self::$LEVEL);
        $this->display();
    }

    /**
     * 在线活动配置
     */
    public function edit() {
        $id = intval($_REQUEST['id']);
        $activityModel = new ActivityModel;
        $activityInfo = $activityModel->getActivityById($id);

        $activityInfo['start_time'] = date("Y-m-d H:i",$activityInfo['start_time']);
        $activityInfo['end_time'] = date("Y-m-d H:i",$activityInfo['end_time']);
        $activityInfo['level'] = intval($activityInfo['level']);

        $this->assign("level",self::$LEVEL);
        $this->assign("activityInfo",$activityInfo);
        $this->display();
    }

    /**
     *在线活动列表
     */
    public function activityList() {
        $this->assign("level",self::$LEVEL);
        $this->assign("main_title","在线活动列表");

        $this->model = MI('Activity', 'vip', 'slave');
        $_REQUEST['listRows'] = 10;
        $result = $this->_list($this->model);
        if (empty($result)) {
            $this->assign('list', array());
            $this->display();
            return;
        }

        $dataList = $result;
        foreach ($result as $key=>$item) {
            $dataList[$key]['level'] = key_exists(intval($item['level']),self::$LEVEL) ? self::$LEVEL[intval($item['level'])] : '';
            $dataList[$key]['start_time'] = empty($item['start_time']) ? '':date("Y-m-d H:i",$item['start_time']);
            $dataList[$key]['end_time'] = empty($item['end_time']) ? '':date("Y-m-d H:i",$item['end_time']);
            $dataList[$key]['shared_icon'] = "<img width=50px src=\"".$item['shared_icon'] ."\">";
            $dataList[$key]['create_time'] = empty($item['create_time']) ? '':date("Y-m-d H:i",$item['create_time']);
            $dataList[$key]['update_time'] = empty($item['update_time']) ? '':date("Y-m-d H:i",$item['update_time']);
        }
        $this->assign('list', $dataList);
        $this->display();
    }

    /*
     * 用户列表
     */
    public function userList() {
        $province = $_GET['province'];
        $city = $_GET['city'];
        $relationType = intval($_GET['relation_type']);
        $title = trim($_GET['title']);
        $isExport = intval($_GET['export']);

        $queryString_user = " 1=1 ";

        if (!empty($province)) {
            $queryString_user .= " AND `province` = '".$province."'";
        }

        if (!empty($city)) {
            if ($province && !in_array($province,['北京市','天津市','上海市','深圳市'])) {
                $queryString_user .= " AND `city` = '".$city."'";
            }
        }

        if ($relationType >= 1 ) {
            $queryString_user .= " AND `relation_type` = ".$relationType;
        }
        if (!empty($title)) {
            $model = new ActivityModel;
            $activityId = $model->getActivityIdByTitle($title);
            $queryString_user .= " AND `activity_id` = ".$activityId;
        }

        $this->assign("level",self::$LEVEL);
        $this->assign("relation",self::$RELATION);
        $this->assign("title",$title);
        $this->assign("province",$province);
        $this->assign("city",$city);

        $this->model = MI('ActivityUser', 'vip', 'slave');
        $_REQUEST['listRows'] = 10;
        $result = $this->_list($this->model, $queryString_user);
        if (empty($result)) {
            $this->assign('list', array());
            $this->display();
            return;
        }

        $dataList = array();
        foreach ($result as $key=>$item) {

            $userId = intval($item['user_id']);
            $userModel = new UserModel;
            $userInfo = $userModel->find($userId);

            $vipAccountModel = new VipAccountModel;
            $vipAccountInfo = $vipAccountModel->getVipAccountByUserId($userId);

            $activityId = intval($item['activity_id']);
            $activityModel = new ActivityModel;
            $activityInfo = $activityModel->getActivityById($activityId);

            $data['user_id'] = $userId;
            $data['name'] = trim($item['real_name']);

            //根据用户ID获取基本信息
            $data['sex'] = array_key_exists(intval($userInfo['sex']), self::$SEX) ? self::$SEX[intval($userInfo['sex'])] : '';
            $bday = $userInfo['idno'] ? substr($userInfo['idno'],6,8) : '';
            $datetime = date(Ymd);
            $data['age'] = empty($bday) ? '' : substr($datetime-$bday,0,2);
            $data['phone'] = $userInfo['mobile'];

            //获取用户待收本金
            $accountService = new AccountService;
            $user_statics = $accountService->getUserStaicsInfo($userId);
            $data['money'] = bcadd($user_statics['principal'], $user_statics['dt_norepay_principal'], 2);

            //getVipAccountByUserId
            $data['service_level'] = array_key_exists(intval($vipAccountInfo['service_grade']), VipEnum::$vipGrade) ? VipEnum::$vipGrade[intval($vipAccountInfo['service_grade'])]['name'] : '';
            $data['actual_level'] = array_key_exists(intval($vipAccountInfo['actual_grade']), VipEnum::$vipGrade) ? VipEnum::$vipGrade[intval($vipAccountInfo['actual_grade'])]['name'] : '';

            $data['province'] = $item['province'];
            $data['city'] = $item['city'];
            $data['relation_type'] = array_key_exists(intval($item['relation_type']), self::$RELATION) ? self::$RELATION[intval($item['relation_type'])] : '';
            $data['relation_name'] = $item['relation_name'];
            $data['relation_sex'] = array_key_exists(intval($item['relation_sex']), self::$SEX) ? self::$SEX[intval($item['relation_sex'])] : '';
            $data['relation_age'] = empty($item['relation_age'])?'':$item['relation_age'];
            $data['relation_phone'] = $item['relation_phone'];

            //根据手机号获取账户状态
            if (empty($item['relation_phone'])) {
                $statusDesc = '';
            }else {
                $statusDesc = "未注册";
                $relationUser = $userModel->getUserByMobile($item['relation_phone']);
                if ($relationUser) {
                    $statusDesc = "注册未投资";
                    $dealLoadModel = new DealLoadModel;
                    if ($dealLoadModel->getFirstDealByUser($relationUser['id'])) {
                        $statusDesc = "已投资";
                    }
                }
            }
            $data['relation_status'] = $statusDesc;
            $data['apply_time'] = empty($item['apply_time']) ? '': date("Y-m-d H:i",$item['apply_time']);
            $data['title'] = $activityInfo['title'];

            $dataList[] = $data;
        }

        //导出
        if ($isExport) {
            $this->export($queryString_user);
        }

        $this->assign('list', $dataList);
        $this->display();
    }

    /**
     *保存活动
     */
    public function saveActivity() {
        $data = array();
        $data['title'] = trim($_POST['title']);
        $data['level'] = intval($_POST['level']);
        $data['start_time'] = strtotime($_POST['start_time']);
        $data['end_time'] = strtotime($_POST['end_time']);
        $data['detail'] = trim($_POST['detail']);
        $data['shared_icon'] = trim($_POST['shared_icon']);
        $data['shared_text'] = trim($_POST['shared_text']);

        $model = new ActivityModel;
        $result = $model->addActivity($data);
        if ($result) {
            Logger::info(implode(' | ', array_merge($data, array('新增在线活动成功'))));
            return $this->ajaxReturn("", '保存活动成功!',0);
        } else {
            return $this->ajaxReturn("", '保存活动失败，请重新提交!',-1);
        }
    }

    /**
     *更新活动
     */
    public function updateActivity() {
        $data = array();
        $id = intval($_POST['id']);
        $data['title'] = trim($_POST['title']);
        $data['level'] = intval($_POST['level']);
        $data['start_time'] = strtotime($_POST['start_time']);
        $data['end_time'] = strtotime($_POST['end_time']);
        $data['detail'] = trim($_POST['detail']);
        $data['shared_icon'] = trim($_POST['shared_icon']);
        $data['shared_text'] = trim($_POST['shared_text']);

        $model = new ActivityModel;
        $result = $model->updateActivity($id,$data);
        if ($result) {
            Logger::info(implode(' | ', array_merge($data, array('编辑在线活动成功'))));
            return $this->ajaxReturn("", '编辑活动成功!',0);
        } else {
            return $this->ajaxReturn("", '编辑活动失败，请重新提交!',-1);
        }
    }

    /**
     * 删除活动
     */
    public function delete() {
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_GET['id']);
        $model = new ActivityModel;
        $result = $model->delActivity($id);

        if ($result) {
            Logger::info(implode(' | ', array_merge(array($id), array('删除在线活动成功'))));
            $this->success (l("DELETE_SUCCESS"),$ajax);
        }else {
            $this->error (l("DELETE_FAILED"),$ajax);
        }
    }

    /**
     * 导出列表
     */
    public function export($queryString) {
        $queryString = trim($queryString);
        $fileName = '在线活动用户表';
        $fileName = $fileName . "_" . date('Ymd') . ".csv";
        $title = array('ID','用户姓名','用户性别','用户年龄', '用户手机号','服务等级','实际等级', '在投本金', '所在省份','所在城市',
            '亲友类别','亲友姓名','亲友性别','亲友年龄', '亲友手机号','亲友网信账户状态','提交时间','活动标题');

        $csv = new ExportCsv();
        $csv->setExportName($fileName);
        $csv->setExportTitle($title);

        $this->model = MI('ActivityUser', 'vip', 'slave');
        $count = $this->model->where($queryString)->count();
        $_REQUEST ['listRows'] = 50;
        $pageCount = ceil($count/$_REQUEST['listRows']);

        for ($i=0;$i<$pageCount;$i++) {
            $dataList = array();
            $_GET['p'] = $i+1;
            $result = $this->_list($this->model, $queryString);
            foreach ($result as $key=>$item) {
                $data = array();
                $userId = intval($item['user_id']);
                $userModel = new UserModel;
                $userInfo = $userModel->find($userId);

                $vipAccountModel = new VipAccountModel;
                $vipAccountInfo = $vipAccountModel->getVipAccountByUserId($userId);

                $activityId = intval($item['activity_id']);
                $activityModel = new ActivityModel;
                $activityInfo = $activityModel->getActivityById($activityId);

                $data['user_id'] = $userId;
                $data['name'] = trim($item['real_name']);

                //根据用户ID获取基本信息
                $data['sex'] = array_key_exists(intval($userInfo['sex']), self::$SEX) ? self::$SEX[intval($userInfo['sex'])] : '';
                $bday = $userInfo['idno'] ? substr($userInfo['idno'],6,8) : '';
                $datetime = date(Ymd);
                $data['age'] = empty($bday) ? '' : substr($datetime-$bday,0,2);
                $data['phone'] = $userInfo['mobile'];

                //getVipAccountByUserId
                $data['service_level'] = array_key_exists(intval($vipAccountInfo['service_grade']), VipEnum::$vipGrade) ? VipEnum::$vipGrade[intval($vipAccountInfo['service_grade'])]['name'] : '';
                $data['actual_level'] = array_key_exists(intval($vipAccountInfo['actual_grade']), VipEnum::$vipGrade) ? VipEnum::$vipGrade[intval($vipAccountInfo['actual_grade'])]['name'] : '';

                //获取用户待收本金
                $accountService = new AccountService;
                $user_statics = $accountService->getUserStaicsInfo($userId);
                $data['money'] = bcadd($user_statics['principal'], $user_statics['dt_norepay_principal'], 2);

                $data['province'] = $item['province'];
                $data['city'] = $item['city'];
                $data['relation_type'] = array_key_exists(intval($item['relation_type']), self::$RELATION) ? self::$RELATION[intval($item['relation_type'])] : '';
                $data['relation_name'] = $item['relation_name'];
                $data['relation_sex'] = array_key_exists(intval($item['relation_sex']), self::$SEX) ? self::$SEX[intval($item['relation_sex'])] : '';
                $data['relation_age'] = empty($item['relation_age'])?'':$item['relation_age'];
                $data['relation_phone'] = $item['relation_phone'];

                //根据手机号获取账户状态
                if (empty($item['relation_phone'])) {
                    $statusDesc = '';
                }else {
                    $statusDesc = "未注册";
                    $relationUser = $userModel->getUserByMobile($item['relation_phone']);
                    if ($relationUser) {
                        $statusDesc = "注册未投资";
                        $dealLoadModel = new DealLoadModel;
                        if ($dealLoadModel->getFirstDealByUser($relationUser['id'])) {
                            $statusDesc = "已投资";
                        }
                    }
                }
                $data['relation_status'] = $statusDesc;
                $data['apply_time'] = empty($item['apply_time']) ? '': date("Y-m-d H:i",$item['apply_time']);
                $data['title'] = $activityInfo['title'];

                $dataList[] = $data;
            }
            $csv->setExportData($dataList);
        }

        $csv->export($fileName, $title, $data);
        return;
    }

}

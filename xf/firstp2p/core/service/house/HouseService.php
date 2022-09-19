<?php

/**
 * HouseService 房贷配置
 * @date 2017-9-28
 * @auther sunxuefeng@ncfgroup.com
 */

namespace core\service\house;

use core\dao\ApiConfModel;
use core\dao\house\HouseUserModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Curl;
use libs\utils\Alarm;
use core\dao\house\HouseInfoModel;
use core\dao\house\HouseDealApplyModel;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;
use libs\vfs\VfsHelper;
use NCFGroup\Task\Services\TaskService AS GTaskService;

class HouseService {
    const HOUSE_CONFIG_KEY = 'house_parameters_config';

    const REQUEST_PARTNER_SUCCESS = 0;      // 请求成功
    const REQUEST_PARTNER_FAILED = 1;       // 请求失败
    const REQUEST_CURL_ERROR = 2;           // 请求CURL错误
    const REQUEST_PARTNER_NO_RETRY = 3;     // 请求不需要retry

    private $error = false;
    private $errorMsg = '';
    private $errorCode = 0;

    // 映射:  一房字段 => 网信字段
    private $loanDetailMapper = array(
        'APLNO' => 'orderId',
        'APLSTATUS' => 'dealStatus',                    // 业务状态  0:未放款    1:已放款
        'LOANOSTAMT' => 'notPaybackAmount',
        'LOANINTAMT' => 'notPaybackInterest',
        'TPAYOSTAMT' => 'paybackAmount',
        'TPAYINTAMT' => 'paybackInterest',
        'STAGES' => 'paybackCount',                     // 借款期数
        'LOANTIMELIMIT' => 'actualPaybackTimeLimit',    // 实际借款期限
        'LOANDATE' => 'loanDate',                       // 放款日期
        'MARKRATE' => 'actualLoanAnnualized',
        'LOANSTAUS' => 'loanStatus',
        'CLAMT' => 'maxLoanAmount',
        'DBD_BUILDINGAREA' => 'houseArea',
        'EXPECTDATE' => 'planFinishPaybackDate',
        'REALDATE' => 'actualFinishPaybackDate',
    );

    /*
     * 网信员工会员组id
     */
    private $NCF_GROUP_ID = array(13,248,237,171,11,347,345,139,85,389,465,411,
        412,413,410,464,416,417,418,415,225,437,450,24,422,439,162,163,153,
        65,143,14,454,145,222,12,436,166,100,384,382,383,84,114,285,128,57,
        149,55,147,250,59,468,408,151,48,126,258,455,457,132,93,259,189,190,
        303,302,118,50);

    /**
     * 统一的异常处理，保持和以前的处理方式兼容
     */
    protected function _handleException($e, $functionName, $data = array()) {
        PaymentApi::log("O2OService.$functionName:".$e->getMessage().', data: '.json_encode($data, JSON_UNESCAPED_UNICODE), Logger::ERR);

        // 需要报的错误信息
        $this->setErrorMsg($e->getMessage());
        $this->errorCode = $e->getCode();
        return false;
    }

    public function hasError() {
        return $this->error;
    }

    public function setErrorMsg($msg) {
        $this->error = true;
        $this->errorMsg = $msg;
        return true;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

    public function getErrorMsg() {
        return $this->errorMsg ? $this->errorMsg : '';
    }

    /**
     * IsHouseOpen，房贷功能是否可用
     */
    public function isHouseOpen() {
        return $this->checkSwitch() || $this->checkWhiteList() ? 1 : 0;
    }

    /**
     * checkSwitch 房贷服务开关
     *
     * @return int
     */
    public function checkSwitch() {
        return app_conf('HOUSE_LOAN_SERVICE_SWITCH') ? 1 : 0;
    }

    /**
     * 用户白名单判断
     */
    public function checkWhiteList() {
        return \libs\utils\ABControl::getInstance()->hit("houseLoan");
    }

    /**
     * get all house by user Id
     * @param $userId int 用户id
     * @return array
     */
    public function getHouseList($userId)
    {
        if (empty($userId)) {
            return false;
        }
        // get houseList by userId
        $condition = 'user_id = '.intval($userId).' ORDER BY update_time DESC';
        $houseInfoModel = HouseInfoModel::instance();
        $houseList = $houseInfoModel->findAll($condition, true);
        if (empty($houseList)) {
            return false;
        }

        // 用户房产估值是使用json存起来的
        foreach ($houseList as $key => $item) {
            if(!empty($item['house_value']) && $item['house_value'] != 'null') {
                // 倒序，申请时间较新的在上
                $houseValue = array_reverse(json_decode($item['house_value'], true));
                // 如果有3个及以上的估值，只取3个
                $houseList[$key]['house_value'] = (count($houseValue)>=3) ? array_slice($houseValue, 0, 3) : $houseValue;
            } else {
                $houseList[$key]['status'] = $this->getHouseStatus($item['id']);
            }
        }
        return $houseList;
    }

    public function getHouseStatus($houseId)
    {
        if (empty($houseId)) {
            return false;
        }
        $condition = 'house_id = '.intval($houseId);
        // 查找使用此房产申请过的贷款记录
        $houseDealApplyLogs = HouseDealApplyModel::instance()->findAll($condition, true);
        foreach ($houseDealApplyLogs as $applyLog) {
            if ($applyLog['status'] == HouseEnum::STATUS_CHECKING ||
                $applyLog['status'] == HouseEnum::STATUS_FIRST_CHECK_PASSED ||
                $applyLog['status'] == HouseEnum::STATUS_FACE_CHECK_PASSED ||
                $applyLog['status'] == HouseEnum::STATUS_MAKING_LOAN) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取房产信息
     */
    private function getHouseInfo($houseId) {
        $house = false;
        if(!empty($houseId)) {
            $condition = 'id = '.intval($houseId);
            $houseInfoModel = HouseInfoModel::instance();
            $house = $houseInfoModel->findBy($condition);
        }

        return $house ? $house->getRow() : false;
    }

    /*
     * get house information by houseId
     */
    public function getHouse($houseId, $token)
    {
        $house = false;
        if(!empty($houseId)) {
            $condition = 'id = '.intval($houseId);
            $houseInfoModel = HouseInfoModel::instance();
            $house = $houseInfoModel->findBy($condition);
            // 通过房产证信息是否是数字判断是否通过vfs获取图片
            $house['house_deed_first_id'] = $house['house_deed_first'];
            if (is_numeric($house['house_deed_first'])) {
                $house['house_deed_first'] = $this->getImageUrl($token, $house['house_deed_first']);
            }

            $house['house_deed_second_id'] = $house['house_deed_second'];
            if (is_numeric($house['house_deed_second'])) {
                $house['house_deed_second'] = $this->getImageUrl($token, $house['house_deed_second']);
            }
        }

        return $house ? $house->getRow() : false;
    }

    /**
     * 获取房贷用户status，判断是否是网信房贷用户
     * @param $user_id
     * @return bool
     */
    public function getUserStatus($userId)
    {
        if (!empty($userId)) {
            // get user
            $user = $this->getUserInfo($userId, '');
            if (empty($user)) {     // if no user, add one
                $user = array(
                    'user_id' => $userId,
                    'deal_type' => HouseEnum::TYPE_NCF_HOUSE,
                    'status' => HouseEnum::STATUS_IS_OFF,
                    'create_time' => time()
                );
                $user['update_time'] = $user['create_time'];
                HouseUserModel::instance()->addUser($user);
            }
            return $user['status'];
        } else {
            return false;
        }
    }

    public function getUserInfo($userId, $token)
    {
        if (!empty($userId)) {
            $houseUserModel = HouseUserModel::instance();
            $user = $houseUserModel->getUserByUserId($userId);
            if (empty($user)) {
                return false;
            }
            $user['usercard_front_id'] = $user['usercard_front'];
            // 通过身份证图片是否是数字判断是否通过vfs获取图片
            if (is_numeric($user['usercard_front'])) {
                $user['usercard_front'] = $this->getImageUrl($token, $user['usercard_front']);
            }

            $user['usercard_back_id'] = $user['usercard_back'];
            if (is_numeric($user['usercard_back'])) {
                $user['usercard_back'] = $this->getImageUrl($token, $user['usercard_back']);
            }
            return $user;
        } else {
            return false;
        }
    }

    public function updateUserStatus($userId)
    {
        $data = array(
            'status' => HouseEnum::STATUS_IS_ON
        );
        $data['update_time'] = time();
        $condition = ' user_id = '.intval($userId);
        return HouseUserModel::instance()->updateBy($data, $condition);
    }


    /*
     * 后台获取房贷配置
     */
    public function getHouseConfAdmin() {
        $condition = ' name="'.self::HOUSE_CONFIG_KEY.'"';
        $apiConfModel = ApiConfModel::instance();
        $houseConf = $apiConfModel->findBy($condition);

        if (empty($houseConf)) {
            $data = array();
            $data['title'] = "网信房贷基本参数配置";
            $data['name'] = self::HOUSE_CONFIG_KEY;
            $data['tip'] = "网信房贷";
            $apiConfModel->addRecord($data);
        }
        $data['id'] = $houseConf['id'];
        $data['value'] = $houseConf['value'];
        return $data;
    }

    /**
     * 获取房贷配置
     */
    public function getHouseConf() {
        $condition = ' name="'.self::HOUSE_CONFIG_KEY.'"';
        $apiConfModel = ApiConfModel::instance();
        $houseConf = $apiConfModel->findBy($condition);

        if (empty($houseConf)) {
            $data = array();
            $data['title'] = "网信房贷基本参数配置";
            $data['name'] = self::HOUSE_CONFIG_KEY;
            $data['tip'] = "网信房贷";
            $apiConfModel->addRecord($data);
        }

        return json_decode($houseConf['value'],true);
    }

    /**
     * 网信房贷配置 获取城市与费率列表
     * @return bool
     */
    public function getHouseConfCityList()
    {
        try {
            $conf = $this->getHouseConf();
            return isset($conf['cityList']) ? $conf['cityList'] : false;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 网信房贷配置 获取城市列表
     * @return array
     */
    public function getHouseConfCities()
    {
        $cityList = $this->getHouseConfCityList();
        foreach ($cityList as $item) {
            $cities[] = $item['city'];
        }

        return $cities;
    }

    /**
     * 根据城市名称获取城市费率
     * @param $cityName string 城市名称
     * @return bool
     */
    public function getHouseConfByCity($cityName)
    {
        try {
            $cityList = $this->getHouseConfCityList();
            foreach ($cityList as $item) {
                if ($item['city'] == $cityName) {
                    return $item;
                }
            }
            throw new \Exception('未找到该城市配置信息');
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取城市借款费率范围
     * @return bool|string
     */
    public function getHouseConfAnnualizedLimit()
    {
        try {
            $cityList = $this->getHouseConfCityList();
            if (empty($cityList) || !is_array($cityList)) {
                return array(
                    'min' => 0,
                    'max' => 0
                );
            }
            $annualizedList = $this->_array_column($cityList, 'annualized', 'city');
            if (empty($annualizedList)) {
                throw new \Exception('未找到年化信息');
            }
            sort($annualizedList, SORT_NUMERIC);
            return array(
                'min' => current($annualizedList),
                'max' => end($annualizedList)
            );
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * php version 5.5以下不支持array_column方法
     * @param array $array
     * @param $column_key
     * @param null $index_key
     * @return array
     */
    private function _array_column(array $array, $column_key, $index_key=null){
        $result = [];
        foreach($array as $arr) {
            if(!is_array($arr)) continue;

            if(is_null($column_key)){
                $value = $arr;
            }else{
                $value = $arr[$column_key];
            }

            if(!is_null($index_key)){
                $key = $arr[$index_key];
                $result[$key] = $value;
            }else{
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * 更新房贷配置
     */
    public function updateHouseConf($confId,$confValue,$isEffect = 1) {
        $data = array();
        if(empty($confId)) {
            return false;
        }
        $confValue = empty($confValue) ? '' : $confValue;
        $data['is_effect'] = $isEffect;
        $data['update_time'] = time();
        $data['tip'] = "网信房贷";
        if (is_array($confValue)) {
            $data['value'] = json_encode($confValue,JSON_UNESCAPED_UNICODE);
        }

        $condition = " `id` = ";
        $condition .= $confId;
        return ApiConfModel::instance()->updateBy($data,$condition);
    }

    public function saveUserHouse($houseInfo, $userId) {
        Logger::debug('saveUserHouse: useId '.$userId.'  houseInfo '.json_encode($houseInfo));
        try {
            if (empty($userId)) {
                throw new \Exception('用户id不能为空');
            }

            if (empty($houseInfo)) {
                throw new \Exception('房产信息不能为空');
            }

            // update house information
            if(!empty($houseInfo['id'])) {
                $actualHouse = $this->getHouseInfo($houseInfo['id']);
                if (empty($actualHouse)) {
                    throw new \Exception('房产信息不存在');
                }

                if ($actualHouse['user_id'] != $userId) {
                    throw new \Exception('无法变更该信息');
                }

                $houseInfo['update_time'] = time();
                $condition = "`id` = ".$houseInfo['id'];
                $house = array(
                    "house_city" => $houseInfo['house_city'],
                    "house_district" => $houseInfo['house_district'],
                    "house_address" => $houseInfo['house_address'],
                    "house_deed_first" => $houseInfo['house_deed_first'],
                    "house_deed_second" => $houseInfo['house_deed_second']
                );
                $result = HouseInfoModel::instance()->updateBy($house, $condition);
            } else {    // add house information
                $houseInfo['user_id'] = $userId;
                $result = HouseInfoModel::instance()->addUserHouse($houseInfo);
            }

            return $result;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取借款列表
     * 不传userId时，查询全部借款记录
     * 支持分页，每页10条
     * @param $userId int 用户id
     * @param $page int 页数 从1开始
     * @param $pageSize int 取多少条
     * @return array
     */
    public function getLoanList($userId = -1, $page = 0, $pageSize = 0) {
        /* select sql value */
        $conditionInit = ' status > 0 ';
        $whereParam = '';
        if ($userId != -1) {
            $whereParam = ' AND user_id = '.intval($userId);
        }
        $orderBy = ' ORDER BY update_time DESC';    // 按照记录的更新时间倒序查询
        $limit = ($page > 0 && $pageSize > 0) ? sprintf(' LIMIT %d,%d ', (($page - 1) * $pageSize), $pageSize) : '';
        /* END select sql value */

        $houseDealApplyModel = HouseDealApplyModel::instance();
        $loanList = $houseDealApplyModel->findAll($conditionInit.$whereParam.$orderBy.$limit, true);
        foreach ($loanList as $key => $value) {
            $loanList[$key]['status_info'] = HouseEnum::$STATUS[$value['status']];
            $house = $this->getHouseInfo($value['house_id']);
            $loanList[$key]['address'] = $house['house_city'].$house['house_district'].$house['house_address'];
            $loanList[$key]['payback_mode'] = HouseEnum::$REPAYMENT_MODES[$loanList[$key]['payback_mode']];
            $loanList[$key]['create_time'] = date("Y-m-d", $value['create_time']);
            $loanList[$key]['borrow_money'] = $value['borrow_money']/10000;
            try {
                // 对于放款中之后的状态，会有准确的贷款金额
                if ($value['status'] > HouseEnum::STATUS_MAKING_LOAN) {
                    $loanFromParnter = $this->getLoanDetailFromPartner($value['order_id']);
                    $loanList[$key]['borrow_money'] = isset($loanFromParnter['loanAmount'])
                        ? $loanFromParnter['loanAmount'] / 10000
                        : $loanList[$key]['borrow_money'];
                }
            } catch (\Exception $ex) {
                continue;
            }
        }

        return $loanList;
    }

    /*
     * 获取借款详情
     */
    public function getLoanDetail($orderId)
    {
        $loan = HouseDealApplyModel::instance()->findByOrderId($orderId);

        if (empty($loan['house_id'])) {
            throw new \Exception('house_id为空');
        }
        $house = HouseInfoModel::instance()->getHouseByOne(array('id' => $loan['house_id']));
        /*  从一房获取数据  */

        $loan['actual_money'] = $loan['borrow_money'];                // 实际放款金额（同实际借款金额）
        $loan['extra_money'] = '0';                 // 总利息
        $loan['success_date'] = '';                 // 实际放款时间
        $loan['plan_repay_finish_date'] = '';       // 预计结清时间
        $loan['actual_repay_finish_date'] = '';     // 实际结清时间
        $loan['payback_mode'] = HouseEnum::$REPAYMENT_MODES[$loan['payback_mode']];      // 还款方式

        /* 当状态为审核中、初审未通过、面审未通过的时候，从本地获取借款信息 */
        if ($loan['status'] != HouseEnum::STATUS_CHECKING
            && $loan['status'] != HouseEnum::STATUS_FIRST_CHECK_FAILED
            && $loan['status'] != HouseEnum::STATUS_FACE_CHECK_FAILED) {

            try {
                $loanInfoFromPartner = $this->getLoanDetailFromPartner($orderId);       // 处理到房产评估值
                /* 处理更新房产估值信息 */
                $houseValue = json_decode($house['house_value'], true);
                if (isset($loanInfoFromPartner['maxLoanAmount'])) {
                    // 是否已经添加了新的估值
                    $isExist = false;
                    foreach ($houseValue as $item) {
                        if ($item['time'] == $loan['create_time'] && $item['value'] == $loanInfoFromPartner['maxLoanAmount']) {
                            $isExist = true;
                            break;
                        }
                    }
                    if (!$isExist) {
                        $newValue['time'] = $loan['create_time'];
                        $newValue['value'] = $loanInfoFromPartner['maxLoanAmount'];
                        $houseValue[] = $newValue;
                        $condition = 'id = '.intval($loan['house_id']);
                        HouseInfoModel::instance()->updateBy(array('house_value' => json_encode($houseValue)), $condition);
                    }
                }
                /* END 处理更新房产估值信息 */
                /* 处理房产面积估值信息 */
                if (intval($house['house_area']) == 0 && !empty($loanInfoFromPartner['houseArea'])) {
                    $condition = 'id = '.intval($loan['house_id']);
                    HouseInfoModel::instance()->updateBy(array('house_area' => $loanInfoFromPartner['houseArea']), $condition);
                }
                /* END 处理房产面积估值信息 */

                $loan['borrow_annualized'] = $loanInfoFromPartner['actualLoanAnnualized']          // 融资成本
                    ? $loanInfoFromPartner['actualLoanAnnualized'].'%'
                    : $loan['expect_annualized'].'%';

                // 实际放款金额与实际借款金额是一个值
                $loan['actual_money'] = isset($loanInfoFromPartner['loanAmount']) ? $loanInfoFromPartner['loanAmount'] : $loan['borrow_money'];
                $loan['extra_money'] = isset($loanInfoFromPartner['loanInterest']) ? $loanInfoFromPartner['loanInterest'] : 0;    // 总利息
                if ($loan['status'] > HouseEnum::STATUS_MAKING_LOAN) {      // 放款中之后的状态才有实际放款时间
                    $loan['success_date'] = strtotime($loanInfoFromPartner['loanDate']);           // 实际放款时间
                }
                $loan['plan_repay_finish_date'] = strtotime($loanInfoFromPartner['planFinishPaybackDate']);     // 预计结清时间
                $loan['actual_repay_finish_date'] = strtotime($loanInfoFromPartner['actualFinishPaybackDate']);   // 实际结清时间
                $loan['borrow_deadline_type'] = $loanInfoFromPartner['actualPaybackTimeLimit']
                    ? $loanInfoFromPartner['actualPaybackTimeLimit']
                    : $loan['borrow_deadline_type'];
            } catch (\Exception $ex) {
                Logger::warn('getLoanDetail: '.$ex->getMessage());
            }
        }

        /* 格式化数据 */
        $loan['status_info'] = HouseEnum::$STATUS[$loan['status']];         // 状态码转为文字
        $loan['status_text'] = HouseEnum::$STATUS_TEXT[$loan['status']];    // 状态码转为详细文案
        $loan['create_time'] = date('Y-m-d',$loan['create_time']);
        $loan['address'] = $house['house_city'].$house['house_district'].$house['house_address'];
        $loan['expect_annualized'] = $loan['expect_annualized'].'%';            // 融资成本
        /* END 格式化数据 */

        return $loan;
    }

    /**
     * 获取还款计划
     * @param $userId int 用户id
     * @param $orderId int 房贷订单id
     * @return array
     */
    public function getRepayList($userId, $orderId) {
        $result = array();
        $result['payback_plan'] = array();
        $result['orderId'] = $orderId;
        $result['init_money'] = '';
        $result['paybacked_money'] = '';
        $plan['list'] = array();
        if (empty($orderId)) {
            return $result;
        }

        try {
            $loanInfoFromPartner = $this->getLoanDetailFromPartner($orderId);
            // 通过接口获取还款计划
            $result['main_money'] = number_format($loanInfoFromPartner['loanAmount'], 2, ".", ",");
            $result['remainder_money'] = number_format($loanInfoFromPartner['notLoanPayback'], 2, ".", ",");
            $result['init_money'] = number_format($loanInfoFromPartner['loanAllMoney'], 2, ".", ",");
            $result['paybacked_money'] = number_format($loanInfoFromPartner['loanPayback'], 2, ".", ",");

            // 具体的还款计划
            $plan = array();
            $plan['payback_period'] = $loanInfoFromPartner['paybackCount'];
            $plan['status'] = intval($loanInfoFromPartner['loanStatus']);

            // 获取还款计划详细信息
            $plan['list'] = $this->getLoanRepayListFromPartner($orderId);
            $result['payback_plan'] = $plan;
            return $result;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    public function getDistrictListByCity($city) {
        try {
            $cityCodeList = $this->getAreaInfoFromPartner();
            if (empty($cityCodeList)) {
                throw new \Exception('城市编码列表为空');
            }

            $cityCode = false;
            foreach ($cityCodeList as $item) {
                if ($item['AREADESC'] == $city || $item['AREADESC'] == $city.'市') {
                    $cityCode = $item['AREACODE'];
                    break;
                }
            }

            if (empty($cityCode)) {
                throw new \Exception('找不到对应的城市编码');
            }

            // get district code
            return $this->getDistrictCodesFromPartner($cityCode);
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 申请贷款
     * @param $applyInfo array 申请信息
     * @return bool
     */
    public function commitApply(array $commitInfo) {
        Logger::info('commitApply: '.date('Y-m-d H:i:s').' '.json_encode($commitInfo));
        // 增加事务
        $GLOBALS['db']->startTrans();
        try {
            if (empty($commitInfo['apply_info']['order_id'])) {
                $commitInfo['apply_info']['order_id'] = Idworker::instance()->getId();
            }

            if (empty($commitInfo['apply_info']['order_id'])) {
                throw new \Exception('订单编号生成失败');
            }

            if (empty($commitInfo['user_info'])) {
                throw new \Exception('用户信息为空');
            }

            if (empty($commitInfo['user_info']['usercard_front'])) {
                throw new \Exception('身份证正面信息为空');
            }

            if (empty($commitInfo['user_info']['usercard_back'])) {
                throw new \Exception('身份证反面信息为空');
            }

            // update user_info's usercard by user id
            if (!empty($commitInfo['apply_info']['user_id'])) {
                $condition = '`user_id` = ' . intval($commitInfo['apply_info']['user_id']);
                HouseUserModel::instance()->updateBy($commitInfo['user_info'], $condition);
            }

            // update house_info's update_time and house_value by house id
            if (!empty($commitInfo['apply_info']['house_id'])) {
                $condition = 'id = '.intval($commitInfo['apply_info']['house_id']);
                HouseInfoModel::instance()->updateBy(
                    array('update_time' => time()),
                    $condition
                );
            }

            // add log of apply
            if (!empty($commitInfo['apply_info'])) {
                $this->addApplyLog($commitInfo['apply_info']);
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return $this->_handleException($e, __FUNCTION__, $commitInfo);
        }

        // 申请成功，添加异步消费任务
        $event = new \core\event\HouseApplyEvent($commitInfo);
        $taskObj = new GTaskService();
        $taskId = $taskObj->doBackground($event, 10);
        return $taskId;
    }

    /**
     * 通过图片的远程服务器地址获取图片的本地地址
     * @param $image
     * @param $name
     * @return string
     * @throws \Exception
     */
    private function getImage($image) {
        if (empty($image)) {
            Logger::error('image为空');
            return '';
        }

        $imageName = md5($image);
        if (!is_numeric($image)) {
            parse_str($image, $querys);
            if (!empty($querys['image_id'])) {
                $image = $querys['image_id'];
            }
        }

        if (is_numeric($image)) {
            try {
                // 根据附件表id，查询某条附件数据
                $attachmentDao = new \core\dao\AttachmentModel();
                $attachmentData = $attachmentDao->getAttachmentById($image);

                // 获取VFS上传的图片
                $file = $attachmentData['attachment'];
                $path = pathinfo($file);
                $streamContent = VfsHelper::image($file, true);
            } catch (\Exception $ex) {
                Logger::error('getImage: '.$ex->getMessage());
                throw new \Exception($ex->getMessage());
            }
        } else {
            $streamContent = Curl::get($image);
            Logger::error('curl, image: '.$image.', errno'.Curl::$errno.', error: '.Curl::$error.', httpCode: '.Curl::$httpCode);
        }

        $suffix = 'jpg';
        $fileName = '/tmp/'.$imageName.'.'.$suffix;
        $result = file_put_contents($fileName, $streamContent);
        return '@'.$fileName;
    }

    /**
     * 获取图片 无需token
     * @param $imageId int 图片id
     */
    public function getImageForAdmin($imageId)
    {
        try {
            if (empty($imageId)) {
                throw new \Exception('需要imageId');
            }
            // 根据附件表id，查询某条附件数据
            $attachmentDao = new \core\dao\AttachmentModel();
            $attachmentData = $attachmentDao->getAttachmentById($imageId);
            // 获取VFS上传的图片
            $file = $attachmentData['attachment'];
            $path = pathinfo($file);
            $streamContent = VfsHelper::image($file, true);
            if ($path['extension'] == 'jpg' || $path['extension'] == 'jpeg') {
                header('content-type:image/jpeg');
            } else  {
                header('content-type:application/octet-stream');
                header("Content-Disposition:attachment;filename=". $path['basename']);
            }
            return $streamContent;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取图片后缀
     * @param $url
     * @return mixed
     */
    private function getImageSuffix($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * 添加借款记录
     * @param $applyInfo array 申请信息
     * @return bool
     */
    public function addApplyLog($applyInfo)
    {
        if (!empty($applyInfo)) {

            $houseDealApplyModel = HouseDealApplyModel::instance();
            $result = $houseDealApplyModel->addApplyLog($applyInfo);
        }
        return $result;
    }

    /**
     * 贷款状态变更接口
     * @param $orderId int 订单id
     * @param $status int 借款状态
     * 状态，1：审核中，2：初审通过，3：初审未通过 4：面审通过，5：面审未通过，6：放款中
     *  7：使用中 8：正常结清，9：逾期结清，10：提前结清，11：逾期中
     * @return int
     */
    public function notify($orderId, $status) {
        try {
            if (empty($orderId)) {
                throw new \Exception('订单号不能为空');
            }

            $status = intval($status);
            if (!array_key_exists($status, HouseEnum::$STATUS)) {
                throw new \Exception('贷款状态不正确');
            }

            // 需要更新的数据
            $data = array('status'=>$status);

            try {
                /* 更新借款期限 */
                $serviceId = 'QD0070000000020';
                $params = array('APLNO'=>$orderId);
                $res = $this->requestFromPartner($serviceId, $params);
                if (isset($res['STAGES'])) {
                    $data['borrow_deadline_type'] = $res['STAGES'];
                }
                /* END 更新借款期限 */
            }  catch (\Exception $ex) {
                // 合理这是记录一下问题
                $this->_handleException($ex, __FUNCTION__, func_get_args());
            }

            $condition = '`order_id` = ' . $orderId;
            return HouseDealApplyModel::instance()->updateBy($data, $condition);
        }  catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 去除日志记录数据的敏感信息
     * @param $data
     * @return array
     */
    private function dataFormat($data)
    {
        if (isset($data['CUSTNAME'])) {
            $data['CUSTNAME'] = user_name_format($data['CUSTNAME']);
        }

        if (isset($data['MOBILEPHONE'])) {
            $data['MOBILEPHONE'] = moblieFormat($data['MOBILEPHONE']);
        }

        if (isset($data['CREDCODE'])) {
            $data['CREDCODE'] = idnoFormat($data['CREDCODE']);
        }

        return $data;
    }

    /**
     * 从渠道获取信息底层请求接口
     *
     * @param $serviceId string 第三方定义的服务id
     * @param $params array 请求参数
     * @param $needToken bool 是否需要传递token，默认为true，表示传递token
     * @param $timeout int 超时时间设置，默认是3秒
     * @return array
     */
    private function requestFromPartner($serviceId, $params, $needToken = true, $timeout = 3) {
        $formData = array();
        $formData['SERVICEID'] = $serviceId;
        // 是否需要传递token
        if ($needToken) {
            $formData['TOKEN'] = $this->getAuthTokenFromPartner(false);
        }

        if ($params) {
            $formData = array_merge($formData, $params);
        }

        $res = $this->postDataToPartner($serviceId, $formData, $timeout);

        // 可能需要重新更新一下token
        if ($needToken && ($res['code'] == '-9' || $res['message'] == 'token失效，请重新获取')) {
            // 重新更新token，再试一次
            $formData['TOKEN'] = $this->getAuthTokenFromPartner(true);
            $res = $this->postDataToPartner($serviceId, $formData, $timeout);
        }

        if ($res['code'] != '0') {
            $code = self::REQUEST_PARTNER_FAILED;
            if ($res['code'] == 'QD00722') {
                $code = self::REQUEST_PARTNER_NO_RETRY;
            }
            throw new \Exception($res['message'], $code);
        }

        // 返回接口数据
        return $res['single'] ? $res['singleData'] : $res['messageDataVos'][0]['datas'];
    }

    /**
     * 处理CURL请求错误
     * @param $serviceId string 第三方定义的服务id
     * @param $formData array 请求参数
     * @param $timeout int 超时时间设置
     * @return array
     */
    private function postDataToPartner($serviceId, $formData, $timeout) {
        $conf = $GLOBALS['sys_config']['HOUSE_YIFANG_SERVICE'];
        // header数据的conten-type设置
        $headers = array('Content-Type:multipart/form-data');

        // 请求接口
        $res = Curl::post($conf['url'], $formData, $headers, $timeout);

        // 记录日志
        $formatLogData = json_encode($this->dataFormat($formData), JSON_UNESCAPED_UNICODE);
        $level = (Curl::$errno != 0) ? Logger::ERR : Logger::INFO;
        PaymentApi::log('requestFromPartner, request: '.$formatLogData
            .', response: '.$res.', error: '.Curl::$error.', errno: '.Curl::$errno
            .', httpCode: '.Curl::$httpCode.', cost: '.Curl::$cost, $level);

        // 对于超过3s的请求，监控记录一下
        if (Curl::$cost >= 1) {
            Alarm::push('o2o_slow_rpc', $serviceId, 'request: '.$formatLogData.', cost: '.Curl::$cost);
        }

        // 对于curl错误的处理
        if (Curl::$errno != 0) {
            // 增加异常监控
            Alarm::push('o2o_exception', $serviceId, 'request: '.$formatLogData
                .', msg: '.Curl::$error.', code: '.Curl::$errno);

            throw new \Exception(Curl::$error, self::REQUEST_CURL_ERROR);
        }

        $res = json_decode($res, true);
        if ($res['code'] != '0') {
            // 增加异常监控
            Alarm::push('o2o_exception', $serviceId, 'request: '.$formatLogData
                .', msg: '.$res['message'].', code: '.$res['code']);
        }

        return $res;
    }

    /**
     * 获取渠道人业务开办区域
     */
    public function getAreaInfoFromPartner() {
        try {
            // cache设置
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            // 缓存7天
            $cacheExpireTime = 3600 * 24 * 7;
            $redisKey = md5('HOUSE_QD0060000000003_AREA');
            $areas = $redis->get($redisKey);
            if ($areas) {
                return unserialize($areas);
            }

            $areas = $this->requestFromPartner('QD0060000000003');
            if ($areas) {
                $redis->setex($redisKey, $cacheExpireTime, serialize($areas));
            }

            return $areas;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取渠道人业务开办 区级别代码list 例如：北京市所有区的代码list
     * @param $cityCode string 城市编码
     * @return string
     */
    public function getDistrictCodesFromPartner($cityCode) {
        try {
            if (empty($cityCode)) {
                throw new \Exception('城市编码为空');
            }

            $data = array('CITYID' => $cityCode);
            // cache设置
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            // 缓存7天
            $cacheExpireTime = 3600 * 24 * 7;
            $redisKey = md5('HOUSE_QD0060000000004_DISTRICT'.$cityCode);
            $district = $redis->get($redisKey);

            if ($district) {
                return unserialize($district);
            }

            $district = $this->requestFromPartner('QD0060000000004', $data);
            if ($district) {
                $redis->setex($redisKey, $cacheExpireTime, serialize($district));
            }

            return $district;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 渠道提交申请
     * @param $commitInfo array 提交信息
     * @param $houseInfo array 房产信息
     * @return bool
     */
    public function pushLoanApplyToPartner(array $commitInfo) {
        if (empty($commitInfo['apply_info']['order_id'])) {
            throw new \Exception('订单号不能为空');
        }

        // get house information by house id
        $houseInfo = $this->getHouseInfo($commitInfo['apply_info']['house_id']);
        if (empty($houseInfo)) {
            throw new \Exception('房产信息为空');
        }

        // get city code
        $cityCodeList = $this->getAreaInfoFromPartner();
        if (empty($cityCodeList)) {
            throw new \Exception('城市编码列表为空');
        }

        foreach ($cityCodeList as $item) {
            if ($item['AREADESC'] == $houseInfo['house_city'] || $item['AREADESC'] == $houseInfo['house_city'] . '市') {
                $houseInfo['cityCode'] = $item['AREACODE'];
                break;
            }
        }

        if (empty($houseInfo['cityCode'])) {
            throw new \Exception('城市编码列表不存在');
        }

        // get district code
        $districtCodeList = $this->getDistrictCodesFromPartner($houseInfo['cityCode']);
        if (empty($districtCodeList)) {
            throw new \Exception('城市区域编码列表为空');
        }

        if (empty($commitInfo['user_info']['usercard_front'])) {
            throw new \Exception('身份证图片正面为空');
        }

        if (empty($commitInfo['user_info']['usercard_back'])) {
            throw new \Exception('身份证图片反面为空');
        }

        if (empty($houseInfo['house_deed_first']) || empty($houseInfo['house_deed_second'])) {
            throw new \Exception('房产材料图片为空');
        }

        foreach ($districtCodeList as $item) {
            if ($item['AREADESC'] == $houseInfo['house_district']) {
                $houseInfo['districtCode'] = $item['AREACODE'];
                break;
            }
        }

        try {
            // 调用远程接口 传递申请数据给一房
            $data = array(
                'APLNO' => $commitInfo['apply_info']['order_id'],
                'ISEMPLOYEE' => $commitInfo['apply_info']['is_ncf_staff'] ? 1 : 0,          // 1: ncf   0: not ncf
                'CUSTNAME' => $commitInfo['other_info']['real_name'],
                'MOBILEPHONE' => $commitInfo['other_info']['phone'],
                'CREDCODE' => $commitInfo['other_info']['usercard_id'],
                'APPAMT' => $commitInfo['apply_info']['borrow_money'],
                'LOANTERM' => $commitInfo['apply_info']['borrow_deadline_type'],
                'HOSTCITY' => $houseInfo['house_city'],
                'HOSTCITYCODE' => $houseInfo['cityCode'],
                'HOUSELOCAREGION' => $houseInfo['house_district'],
                'HOSTAREACODE' => $houseInfo['districtCode'],
                'CREDFILE1' => $this->getImage($commitInfo['user_info']['usercard_front']),
                'CREDFILE2' => $this->getImage($commitInfo['user_info']['usercard_back']),
                'HOUSEFILES[0]' => $this->getImage($houseInfo['house_deed_first']),
                'HOUSEFILES[1]' => $this->getImage($houseInfo['house_deed_second'])
            );

            $serviceId = 'QD0070000000011';

            // 请求超时设置成20s
            return $this->requestFromPartner($serviceId, $data, true, 20);
        } catch (\Exception $ex) {
            // 对于错误的请求，将状态变更为初审失败
            if ($ex->getCode() == self::REQUEST_PARTNER_FAILED) {
                $condition = '`order_id` = ' . $commitInfo['apply_info']['order_id'] . ' AND `status` = 0';
                $updateData = array('status' => HouseEnum::STATUS_FIRST_CHECK_FAILED);
                HouseDealApplyModel::instance()->updateBy($updateData, $condition);
            } else if ($ex->getCode() == self::REQUEST_PARTNER_NO_RETRY) {
                return true;
            }

            throw $ex;
        }
    }

    /**
     * 根据渠道提交的申请编号查询贷款信息
     * @param $orderId bigint 订单id
     * @return array
     */
    public function getLoanDetailFromPartner($orderId) {
        if (empty($orderId)) {
            throw new \Exception('订单号不能为空');
        }

        $serviceId = 'QD0070000000020';
        $params = array('APLNO'=>$orderId);
        $dataFromPartner = $this->requestFromPartner($serviceId, $params);

        // middle layer, for uniting access of data from partner
        $loanDetail = array();
        foreach ($this->loanDetailMapper as $parnter => $ncf) {
            if (isset($dataFromPartner[$parnter])) {
                $loanDetail[$ncf] = $dataFromPartner[$parnter];
            }
        }
        // END midile layer

        if (isset($loanDetail['loanStatus'])) {
            $loanStatus = intval($loanDetail['loanStatus']);
            $status = HouseEnum::STATUS_MAKING_LOAN;
            if ($loanStatus == 0) {
                // 正常还款中
                $status = HouseEnum::STATUS_USING;
            } else if ($loanStatus == 1) {
                // 预期中
                $status = HouseEnum::STATUS_OVERDUE;
            } else if ($loanStatus == 2) {
                // 提前结清
                $status = HouseEnum::STATUS_FINISH_AHEAD;
            } else if ($loanStatus == 3) {
                // 正常结清
                $status = HouseEnum::STATUS_FINISHED;
            }

            // 实时更新贷款的状态
            $condition = '`order_id` = ' . $orderId . ' AND `status` > ' . HouseEnum::STATUS_MAKING_LOAN
                . ' AND `status` != '.$status;
            HouseDealApplyModel::instance()->updateBy(array('status' => $status), $condition);
        }

        $params = array();
        // 实际借款期限有检索的需求，必须回填
        if (isset($loanDetail['actualPaybackTimeLimit'])) {
            $timeLimit = strtotime($loanDetail['actualPaybackTimeLimit']);
            if ($timeLimit) {
                $params['actual_loan_timelimit'] = $timeLimit;
            }
        }

        // 实际放款时间有检索的需求，必须回填
        if (isset($loanDetail['loanDate'])) {
            $loanDate = strtotime($loanDetail['loanDate']);
            if ($loanDate) {
                $params['actual_success_date'] = $loanDate;
            }
        }

        if ($params) {
            $condition = 'order_id = ' . $orderId;
            HouseDealApplyModel::instance()->updateBy($params, $condition);
        }

        // Calculate formula for money together
        if (isset($loanDetail['notPaybackAmount']) && isset($loanDetail['paybackAmount'])
            && isset($loanDetail['notPaybackInterest']) && isset($loanDetail['paybackInterest'])) {

            $loanDetail['loanPayback'] = $loanDetail['paybackAmount'] + $loanDetail['paybackInterest'];             // 已还金额
            $loanDetail['notLoanPayback'] = $loanDetail['notPaybackAmount'] + $loanDetail['notPaybackInterest'];    // 未还金额

            $loanDetail['loanAmount'] = $loanDetail['notPaybackAmount'] + $loanDetail['paybackAmount'];             // 本金
            $loanDetail['loanInterest'] = $loanDetail['notPaybackInterest'] + $loanDetail['paybackInterest'];       // 利息
            $loanDetail['loanAllMoney'] = $loanDetail['loanAmount'] + $loanDetail['loanInterest'];                  // 本金 + 利息
        }

        // END Calculate
        return $loanDetail;
    }

    /**
     * 根据渠道提交的申请编号查询贷款还款计划
     * @param $orderId bigint 订单id
     * @return array
     */
    public function getLoanRepayListFromPartner($orderId) {
        $serviceId = 'QD0070000000021';
        $params = array('APLNO'=>$orderId);
        $items = $this->requestFromPartner($serviceId, $params, true, 5);

        $res = array();
        foreach ($items as $item) {
            $res[] = array(
                'status' => intval($item['PAYSTAUS']),                                              // 状态
                'status_info' => HouseEnum::$REPAY_PLAN_STATUS[intval($item['PAYSTAUS'])],          // 状态信息
                'init_money' => number_format($item['BASEOSTAMT'], 2, ".", ","),                    // 应还本金
                'extra_money' => number_format($item['BASEINTAMT'], 2, ".", ","),                   // 应还利息
                'all_money' => number_format(($item['BASEOSTAMT'] + $item['BASEINTAMT'] + $item['PASTDUEAMT']), 2, ".", ","),              // 本期应还总金额
                'over_days' => isset($item['PASTDUEDAYS']) ? $item['PASTDUEDAYS'].'天' : '-',                   // 逾期天数
                'over_money' => isset($item['PASTDUEAMT']) ? number_format($item['PASTDUEAMT'], 2, ".", ",").'元' : '-',                // 罚息
                'over_date' => isset($item['PASTDUEDATE']) ? date('Y-m-d', strtotime($item['PASTDUEDATE'])) : '-' ,          // 逾期开始日期
                'payback_date' => strtotime($item['PAYDATE']),        // 还款日期
                'payback_date_format' => date('Y-m-d', strtotime($item['PAYDATE'])),
                'actual_payback_date' => strtotime($item['TPAYDATE']),        //  实际还款日期,
                'actual_payback_date_format' => date('Y-m-d', strtotime($item['TPAYDATE']))
            );
        }

        return $res;
    }

    /**
     * 渠道人获取数据字典
     * @param $paramType string 字典类型
     * @return array
     */
    public function getDataDictFromPartner($paramType) {
        $serviceId = 'QD0060000000001';
        $params = array('PARMTYPE'=>$paramType);
        return $this->requestFromPartner($serviceId, $params);
    }

    /**
     * 查询渠道首页统计信息
     */
     public function getAllLoanAmountFromPartner() {
        return $this->requestFromPartner('QD0030000000019');
    }

    /**
     * 登录获取第三方的token
     *
     * @param $fresh bool 是否实时获取，true为实时，false为缓存获取
     * @return string
     */
    public function getAuthTokenFromPartner($fresh = false) {
        $conf = $GLOBALS['sys_config']['HOUSE_YIFANG_SERVICE'];
        // cache设置
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        // 缓存2个小时
        $cacheExpireTime = 7200;
        $redisKey = md5('HOUSE_QD0030000000006_'.$conf['userId'].'_'.$conf['url']);
        if (!$fresh) {
            $token = $redis->get($redisKey);
            if ($token) {
                return $token;
            }
        }

        $formData = array();
        $formData['LOGINTYPE'] = 1;
        $formData['USERID'] = $conf['userId'];
        //$formData['PASSWORD'] = $this->encode(strrev($conf['password']), $conf['aesKey']);
        $formData['PASSWORD'] = $conf['encryptPassword'];

        $res = $this->requestFromPartner('QD0030000000006', $formData, false);
        if (!empty($res['TOKEN'])) {
            $redis->setex($redisKey, $cacheExpireTime, $res['TOKEN']);
        }

        return $res ? $res['TOKEN'] : '';
    }

    /**
     * 加密 (Aes + base64)
     */
    private function encode($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5Padding($input, $size);

        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);

        return $data;
    }

    /**
     * PKCS5方式填充
     */
    private function pkcs5Padding($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    /**
     * 判断是否是网信员工
     * @param $userId int 用户id
     * @return bool true:是网信员工 false:不是网信员工
     */
    public function isNcfStaff($userGroupId)
    {
        return $userGroupId ? in_array($userGroupId, $this->NCF_GROUP_ID) : false;
    }

    public function isAgainLoan($userId)
    {
        $condition = ' user_id = '.$userId.' AND status > 7';
        return HouseDealApplyModel::instance()->findBy($condition);
    }

    private function getImageUrl($token, $imageId)
    {
        return sprintf($this->getHost().'/common/image?token=%s&image_id=%s', $token, $imageId);
    }

    /**
     * 获取Api域名
     */
    private function getHost()
    {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }
}

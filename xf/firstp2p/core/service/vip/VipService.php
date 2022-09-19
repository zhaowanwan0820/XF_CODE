<?php
/**
 * VipService.php vip会员等级服务
 *
 * @date 2017-06-22
 * @author liguizhi <liguizhi@ucfgroup.com>
 */

namespace core\service\vip;

use core\dao\UserModel;
use core\dao\vip\VipAccountModel;
use core\dao\vip\VipLogModel;
use core\dao\vip\VipGiftLogModel;
use core\dao\vip\VipRateLogModel;
use core\dao\vip\VipPointLogModel;
use core\dao\ApiConfModel;
use libs\utils\PaymentApi;
use core\service\candy\CandyService;
use core\service\UserService;
use libs\utils\Finance;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\O2OService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\oto\O2OUtils;
use core\dao\vip\VipSourceWeightConfModel;
use core\service\vip\VipPointLogService;
use NCFGroup\Common\Library\Date\XDateTime;
use core\service\GoldBidService;
use core\service\UserLogService;
use core\service\UserThirdBalanceService;
use core\dao\DealLoanRepayModel;
use core\dao\vip\VipBidConfModel;
use libs\utils\Logger;
use core\event\VipPointResendEvent;
use core\dao\vip\VipPrivilegeModel;
use core\service\CouponService;
use core\service\CouponBindService;
use core\service\marketing\DiscountCenterService;
use core\service\candy\CandyActivityService;
use core\service\candy\CandySnatchService;
use NCFGroup\Common\Library\Msgbus;

/**
 * Class VipService
 */
class VipService {

    const VIP_PRIVILEGE_RAISE_INTEREST_ID = 1;//投资加息红包特权ID
    const VIP_PRIVILEGE_GIFT_VALUE_ID = 2;//投资加息红包特权ID
    const VIP_GRADE_PT = 0;//普通会员ID
    const VIP_GRADE_QT = 1;//青铜会员ID
    const VIP_GRADE_HG = 6;//皇冠会员ID

    const VIP_REBATE_CONFIG_KEY = 'user_vip_rebate_config'; // api_conf配置名
    const VIP_LEVEL_CONFIG_KEY = 'user_vip_level_config';   // api_conf配置名

    /**
     * 获取奖励配置
     */
    public function getVipRebateConf() {
        $condition = 'name="'.self::VIP_REBATE_CONFIG_KEY.'"';
        $apiConfModel = ApiConfModel::instance();
        $rebateConf = $apiConfModel->findByViaSlave($condition);

        $confVal = array();
        if (empty($rebateConf)) {
            $data = array(
                'title' => 'VIP等级奖励配置',
                'name' => self::VIP_REBATE_CONFIG_KEY,
                'conf_type' => 3,
                'value' => '',
                'is_effect' => 0
            );
            $apiConfModel->addRecord($data);
        } else {
            $confVal = json_decode($rebateConf['value'], true);
        }

        if (empty($confVal)) {
            foreach (VipEnum::$vipGrade as $key=>$value) {
                if (!isset(VipEnum::$vipGradeNoToAlias[$key]) || $key == VipEnum::VIP_GRADE_PT) {
                    continue;
                }

                $aliasKey = VipEnum::$vipGradeNoToAlias[$key];
                $confVal[$aliasKey] = array(
                    'name'=>$value['name'],
                    'vipGrade'=>$value['vipGrade']
                );
            }
        }

        return $confVal;
    }

    /**
     * 获取等级配置
     */
    public function getLevelVipConf() {
        $condition = 'name="'.self::VIP_LEVEL_CONFIG_KEY.'"';
        $apiConfModel = ApiConfModel::instance();
        $rebateConf = $apiConfModel->findByViaSlave($condition);

        $confVal = array();
        if (empty($rebateConf)) {
            $data = array(
                'title' => 'VIP等级参数配置',
                'name' => self::VIP_LEVEL_CONFIG_KEY,
                'conf_type' => 3,
                'value' => '',
                'is_effect' => 0
            );
            $apiConfModel->addRecord($data);
        } else {
            $confVal = json_decode($rebateConf['value'], true);
        }

        if (empty($confVal)) {
            foreach (VipEnum::$vipGrade as $key=>$value) {
                if (!isset(VipEnum::$vipGradeNoToAlias[$key]) || $key == VipEnum::VIP_GRADE_PT) {
                    continue;
                }

                $aliasKey = VipEnum::$vipGradeNoToAlias[$key];
                $confVal[$aliasKey] = array(
                    'name'          => $value['name'],
                    'vipGrade'      => $value['vipGrade'],
                    'minInvest'     => $value['minInvest'],
                    'raiseInterest' => $value['raiseInterest'],
                    'giftValue'     => $value['giftValue'],
                    'imgUrl'        => $value['imgUrl'],
                    'privilege'     => $value['privilege']
                );
            }
        }

        // 后台数据不存在普通用户配置的字段，增加默认值避免外层notice或者取不到普通用户等级信息
        if (!isset($confVal[VipEnum::VIP_GRADE_ALIAS_PT])) {
            $confVal[VipEnum::VIP_GRADE_ALIAS_PT] = array(
                'name'          => '普通用户',
                'vipGrade'      => 0,
                'minInvest'     => 0,
                'raiseInterest' => 0,
                'giftValue'     => 0,
                'imgUrl'        => '',
                'privilege'     => ''
            );
        }

        return $confVal;
    }

    /**
     * 获取某个vip等级的加息利率
     * @param $vipLevel int 用户的vip等级
     * @return float|bool
     */
    public function getVipInterest($vipLevel) {
        if ($vipLevel == VipEnum::VIP_GRADE_PT) {
            return 0;
        }

        if (!isset(VipEnum::$vipGradeNoToAlias[$vipLevel])) {
            throw new \Exception('不是有效的vip等级');
        }

        $aliasKey = VipEnum::$vipGradeNoToAlias[$vipLevel];

        $condition = 'name="'.self::VIP_LEVEL_CONFIG_KEY.'"';
        $rebateConf = ApiConfModel::instance()->findByViaSlave($condition);
        if (empty($rebateConf)) {
            throw new \Exception('vip等级配置参数不存在');
        }

        $confVal = json_decode($rebateConf['value'], true);
        if (!isset($confVal[$aliasKey])) {
            throw new \Exception('vip等级配置参数不正确');
        }

        return $confVal[$aliasKey]['raiseInterest'];
    }

    /**
     * 获取某个vip等级的生日奖励
     * @param $vipLevel int 用户的vip等级
     * @return array {groupType:, groupId: , pushMsg: }
     */
    public function getVipBirthdayRebate($vipLevel) {
        if (!isset(VipEnum::$vipGradeNoToAlias[$vipLevel])) {
            throw new \Exception('不是有效的vip等级');
        }

        $aliasKey = VipEnum::$vipGradeNoToAlias[$vipLevel];

        $condition = 'name="'.self::VIP_REBATE_CONFIG_KEY.'"';
        $rebateConf = ApiConfModel::instance()->findByViaSlave($condition);
        if (empty($rebateConf)) {
            throw new \Exception('vip奖励配置不存在');
        }

        $confVal = json_decode($rebateConf['value'], true);
        if (!isset($confVal[$aliasKey])) {
            throw new \Exception('vip奖励配置不正确');
        }

        $res = array();
        $res['groupType'] = $confVal[$aliasKey]['birthdayGroupType'];
        $res['groupId'] = $confVal[$aliasKey]['birthdayGroupId'];
        $res['pushMsg'] = $confVal[$aliasKey]['birthdayPushMsg'];
        return $res;
    }

    /**
     * 获取某个vip等级的周年奖励
     * @param $vipLevel int 用户的vip等级
     * @return array {groupType:, groupId: , pushMsg: }
     */
    public function getVipAnniverRebate($vipLevel) {
        if (!isset(VipEnum::$vipGradeNoToAlias[$vipLevel])) {
            throw new \Exception('不是有效的vip等级');
        }

        $aliasKey = VipEnum::$vipGradeNoToAlias[$vipLevel];

        $condition = 'name="'.self::VIP_REBATE_CONFIG_KEY.'"';
        $rebateConf = ApiConfModel::instance()->findByViaSlave($condition);
        if (empty($rebateConf)) {
            throw new \Exception('vip奖励配置不存在');
        }

        $confVal = json_decode($rebateConf['value'], true);
        if (!isset($confVal[$aliasKey])) {
            throw new \Exception('vip奖励配置不正确');
        }

        $res = array();
        $res['groupType'] = $confVal[$aliasKey]['anniverGroupType'];
        $res['groupId'] = $confVal[$aliasKey]['anniverGroupId'];
        $res['pushMsg'] = $confVal[$aliasKey]['anniverPushMsg'];
        return $res;
    }

    /**
     * 获取某个vip等级的升级礼包
     * @param $vipLevel int 用户的vip等级
     * @return array {groupType:, groupId: , smsId: , pushMsg: }
     */
    public function getVipGiftRebate($vipLevel) {
        if (!isset(VipEnum::$vipGradeNoToAlias[$vipLevel])) {
            throw new \Exception('不是有效的vip等级');
        }

        $aliasKey = VipEnum::$vipGradeNoToAlias[$vipLevel];

        $condition = 'name="'.self::VIP_REBATE_CONFIG_KEY.'"';
        $rebateConf = ApiConfModel::instance()->findByViaSlave($condition);
        if (empty($rebateConf)) {
            throw new \Exception('vip奖励配置不存在');
        }

        $confVal = json_decode($rebateConf['value'], true);
        if (!isset($confVal[$aliasKey])) {
            throw new \Exception('vip奖励配置不正确');
        }

        $res = array();
        $res['groupType'] = $confVal[$aliasKey]['giftGroupType'];
        $res['groupId'] = $confVal[$aliasKey]['giftGroupId'];
        $res['smsId'] = $confVal[$aliasKey]['giftSmsId'];
        $res['pushMsg'] = $confVal[$aliasKey]['giftPushMsg'];
        return $res;
    }

    /**
     * 更新奖励配置
     */
    public function updateVipRebateConf($confValue, $isEffect = 1) {
        $data = array();
        $data['is_effect'] = $isEffect;
        $data['update_time'] = time();
        if (is_array($confValue)) {
            $confValue = json_encode($confValue, JSON_UNESCAPED_UNICODE);
        }
        $data['value'] = $confValue;

        $condition = 'name="'.self::VIP_REBATE_CONFIG_KEY.'"';
        return ApiConfModel::instance()->updateBy($data, $condition);
    }

    /**
     * 更新等级参数配置
     */
    public function updateVipLevelConf($confValue, $isEffect = 1) {
        $data = array();
        $data['is_effect'] = $isEffect;
        $data['update_time'] = time();
        if (is_array($confValue)) {
            $confValue = json_encode($confValue, JSON_UNESCAPED_UNICODE);
        }
        $data['value'] = $confValue;

        $condition = 'name="'.self::VIP_LEVEL_CONFIG_KEY.'"';
        return ApiConfModel::instance()->updateBy($data, $condition);
    }

    /**
     * getSourceConfig 获取来源方vip业务配置参数[sourceWeight, expireMonth]
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-08-28
     * @param mixed $sourceType
     * @access public
     * @return void
     */
    public function getSourceConfig($sourceType) {
        $source = VipEnum::$vipSourceMap[$sourceType];
        $result = array('sourceWeight' => 1, 'expireMonth' => 12);
        $sourceConf = VipSourceWeightConfModel::instance()->find($source);
        if ($sourceConf) {
            $result = array(
                'sourceWeight' => $sourceConf['weight'],
                'expireMonth' => $sourceConf['expire_month']
            );
        }
        return $result;
    }

    /**
     * checkMainSite检查用户是否是主站用户,分站已撤,所有用户都当做主站用户
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-26
     * @access public
     * @return void
     */
    public function checkMainSite($userId) {
        return true;
    }

    /**
     * checkSwitch VIP服务开关
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-06-22
     * @access private
     * @return bool
     */
    public function checkSwitch() {
        return app_conf('VIP_SERVICE_SWITCH') ? true : false;
    }

    /**
     * checkWhiteList白名单方法
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-19
     * @access private
     * @return void
     */
    public function checkWhiteList($userId) {
        $whiteListConf = app_conf('VIP_SERVICE_WHITELIST');
        $whiteList = array();
        if ($whiteListConf) {
            $whiteList = explode(',', $whiteListConf);
        }
        return $whiteList ? in_array($userId, $whiteList) : false;
    }

    /**
     * isShowVip是否显示vip的逻辑判断
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-19
     * @access public
     * @param $userId int 用户id
     * @return void
     */
    public function isShowVip($userId) {
        // 企业用户不显示Vip
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log('vip不支持企业用户'.$userId);
            return false;
        }

        return $this->checkSwitch() || $this->checkWhiteList($userId);
    }
    /**
     * getVipGrade获取用户会员等级信息，扩充了前端显示需要的字段
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-06-22
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getVipGrade($userId) {
        if (empty($userId)) {
            return false;
        }
        $isUpgrade = VipAccountModel::instance()->checkUpgradeFlag($userId);
        $vipConf = $this->getLevelVipConf();
        $vipInfo = VipAccountModel::instance()->getVipGradeDetail($userId, $vipConf);
        $vipInfo = !empty($vipInfo) ? $vipInfo : [];
        if (empty($vipInfo)) {
            //未初始化会员账户信息的会员，经验值应该为0
            $vipInfo['point'] = 0;
            $vipInfo['remain_invest_money'] = $vipConf[VipEnum::VIP_GRADE_ALIAS_QT]['minInvest'];
            $vipInfo['service_grade'] = 0;
            $vipInfo['upgrade_percent'] = 0;
        }
        $vipInfo['is_upgrade'] = $isUpgrade;
        return $vipInfo;
    }

    /**
     * getVipInfoForSummary
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-09-11
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getVipInfoForSummary($userId) {
        $vipConf = $this->getLevelVipConf();
        $vipInfo = $this->getVipInfo($userId);
        if (isset($vipInfo['service_grade']) && $vipInfo['service_grade']) {
            $gradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipInfo['service_grade']]];
            $gradeName = $gradeInfo['name'];
        } else {
            $gradeName = '普通用户';
        }
        $result['vipGrade'] = isset($vipInfo['service_grade']) ? $vipInfo['service_grade'] : 0;
        $result['vipGradeName'] = $gradeName;
        $result['isUpgrade'] =  VipAccountModel::instance()->checkUpgradeFlag($userId);
        $result['upgradeCondition'] = '当前经验值'.($vipInfo['point'] ?: 0);
        return $result;
    }

    /**
     * getVipInfo获取用户会员信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-17
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getVipInfo($userId) {
        if (empty($userId)) {
            return false;
        }
        return VipAccountModel::instance()->getVipAccountByUserId($userId);
    }

    public function getVipInfoByMobile($mobile) {
        $ret = array();
        $userModel = new UserModel();
        if (is_mobile($mobile)) {
            $condition = "`mobile`=':mobile'";
        }else{
            return $ret;
        }
        $param = array(
            ':mobile' => $mobile,
        );
        $userInfo = $userModel->findByViaSlave($condition, 'id,group_id',$param);
        if (empty($userInfo)){
            return $ret;
        }
        $userId = $userInfo->id;
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return $ret;
        }
        return $vipInfo;
    }

    /**
     * getVipAccountInfo会员中心首页接口
     * 1.获取vip信息
     * 2.如有升级，提示升级礼包
     * 3.重置用户升级标识
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-06-22
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getVipAccountInfo($userId) {
        if (empty($userId)) {
            return false;
        }
        // 企业用户不显示会员中心
        $userService  =  new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            return false;
        }
        $isUpgrade = VipAccountModel::instance()->checkUpgradeFlag($userId);
        PaymentApi::log('checkupgrade:'.$isUpgrade.'|userId|'.$userId);
        $vipConf = $this->getLevelVipConf();
        $vipInfo = VipAccountModel::instance()->getVipGradeDetail($userId, $vipConf);
        if ($vipInfo) {
            if ($isUpgrade) {
                //如果升级，获取获得的升级礼包个数
                $vipInfo['gift_count'] = VipGiftLogModel::instance()->getUpgradeGiftCount($userId);
            } else {
                $vipInfo['gift_count'] = 0;
            }
            $vipInfo['is_upgrade'] = $isUpgrade;
            //查看会员中心页面后，重置用户升级标识
            VipAccountModel::instance()->clearUpgradeFlag($userId);
            return $vipInfo;
        } else {
            return false;
        }
    }

    /**
     * getExpectVipRebate根据交易ID获取vip预期返利[专享&p2p]
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-19
     * @param mixed $userId
     * @param mixed $dealLoadId
     * @access public
     * @return void
     */
    public function getExpectVipRebate($userId ,$dealLoadId) {
        $result = array(
            'expectVipRebate' => 0,
            'rebateDesc' => ''
        );
        if (empty($userId)) {
            return $result;
        }
        //企业用户不显示加息相关
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            return $result;
        }
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return $result;
        }
        $vipConf = $this->getLevelVipConf();
        $gradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipInfo['service_grade']]];
        $raiseInterest = $this->getVipInterest($vipInfo['service_grade']);
        if ($gradeInfo['raiseInterest'] > 0) {
            $annualizedAmount = O2OUtils::getAnnualizedAmountByDealLoadId($dealLoadId);
            $result['expectVipRebate'] = bcdiv(bcmul($annualizedAmount, $raiseInterest), 100, 2);
            $result['rebateDesc'] = "该笔可获{$result['expectVipRebate']}元{$gradeInfo['name']}加息红包\n将于放款后发放";
        }
        return $result;
    }

    public function getExpectRebateAndPoint($userId, $annualizedAmount, $sourceType) {
        $result = array(
            'expectVipRebate' => 0,
            'rebateDesc' => '',
            'vipPoint' => 0,
        );
        if (empty($userId)) {
            return $result;
        }
        //企业用户不显示加息相关
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            return $result;
        }
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return $result;
        }
        $vipConf = $this->getLevelVipConf();
        $gradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipInfo['service_grade']]];
        $raiseInterest = $this->getVipInterest($vipInfo['service_grade']);
        if ($gradeInfo['raiseInterest'] > 0) {
            $result['expectVipRebate'] = number_format($annualizedAmount * $raiseInterest / 100, 2);
            $result['rebateDesc'] = "该笔可获{$result['expectVipRebate']}元{$gradeInfo['name']}加息红包\n将于放款后发放";
        }
        $result['vipPoint'] = $this->computeVipPoint($sourceType, $annualizedAmount);
        return $result;

    }

    /**
     * getExpectVipRebateForGold
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-08-28
     * @param mixed $userId
     * @param mixed $annualizedAmount
     * @access public
     * @return void
     */
    public function getExpectVipRebateForGold($userId, $annualizedAmount) {
        $result = array(
            'expectVipRebate' => 0,
            'rebateDesc' => ''
        );
        if (empty($userId)) {
            return $result;
        }
        //企业用户不显示加息相关
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            return $result;
        }
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return $result;
        }
        $vipConf = $this->getLevelVipConf();
        $gradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipInfo['service_grade']]];
        $raiseInterest = $this->getVipInterest($vipInfo['service_grade']);
        if ($gradeInfo['raiseInterest'] > 0) {
            $result['expectVipRebate'] = bcdiv(bcmul($annualizedAmount , $raiseInterest), 100, 2);
            $result['rebateDesc'] = "该笔可获{$result['expectVipRebate']}元{$gradeInfo['name']}额外奖励\n将于认购后发放。";
        }
        return $result;
    }


    /**
     * computeVipGrade根据经验值计算用户会员等级
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-06-22
     * @param mixed $point
     * @access private
     * @return void
     */
    public function computeVipGrade($point) {
        $vipConf = $this->getLevelVipConf();
        //默认最高等级，从低等级开始匹配，匹配命中则更新等级。
        //vipConf中增加了普通用户的配置，会导致count总数不等于最高等级数，需要-1
        $grade = count($vipConf) - 1;

        foreach($vipConf as $v) {
            if ($v['minInvest']  > $point) {
                $grade = $v['vipGrade'] - 1;
                break;
            }
        }
        return $grade;
    }

    public function computeVipPoint($sourceType, $sourceAmount) {
        $sourceConfig = $this->getSourceConfig($sourceType);
        return $point = round($sourceAmount * $sourceConfig['sourceWeight']);
    }

    public function isUpdatePoint() {
        //大于开始更新时间才允许计算经验值
        $time = time();
        $startTime = app_conf('VIP_UPDATE_POINT_START');
        return ($startTime) ? (strtotime($startTime) <= $time) : false;
    }

    public function updatePoint($point, $userId) {
        return VipAccountModel::instance()->updatePoint($point, $userId);
    }

    /**
     * updateVipPoint 更新vip经验,暴露给业务方的通用接口
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-08-28
     * @param mixed $userId 用户id
     * @param mixed $sourceAmount 业务方数值(投资金额，黄金金额)
     * @param mixed $sourceType 来源业务
     * @param mixed $token 唯一凭证
     * @param mixed $info 业务描述
     * @param mixed $sourceId 来源id
     * @param mixed $pointLogId 经验值记录id[初始化数据会用]
     * @param mixed $firstloanAnnual 首投时的年化投资额
     * @access public
     * @return void
     */
    public function updateVipPoint($userId, $sourceAmount, $sourceType, $token, $info, $sourceId = 0, $pointLogId = 0, $firstloanAnnual = 0, $bidAmount = 0) {
        // 企业用户不支持Vip
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log('UPDATE vipPoint vip不支持企业用户'.$userId);
            return true;
        }
        /**
         * 1.获取已有经验和增加的经验，计算总经验
         * 2.根据总经验值计算会员等级computeGrade
         * 3.查询vip表会员等级vipGrade
         * 4.对比2,3的数据，如果等级发生变更，更新vip表等级并增加变更记录（如果触发礼包需发送升级礼包）
         * 5.写pointlog数据
         */
        //先根据token查询pointlog，如果有记录直接返回
        if (VipPointLogModel::instance()->getPointByTokens($token)) {
            PaymentApi::log('UPDATE vipPoint token exist,userId|'.$userId.'|token|'.$token);
            return true;
        }

        $point = $this->computeVipPoint($sourceType, $sourceAmount);
        if (empty($point)) {
            //变动的经验值为0时，直接返回
            PaymentApi::log('UPDATE vipPoint point 0,userId|'.$userId.'|token|'.$token.'|sourceAmount|'.$sourceAmount.'|sourceType|'.$sourceType.'|info|'.$info);
            return true;
        }
        $db = \libs\db\Db::getInstance('vip');
        $db->startTrans();
        $userGradeInfo = $this->getVipInfo($userId);
        if ($userGradeInfo) {
            $vipPointInfo = $this->updatePoint($point, $userId);
            $totalPoint = $vipPointInfo['point'];
        } else {
            $totalPoint = $point;
        }
        $computeGrade = $this->computeVipGrade($totalPoint);
        $userInfo = UserModel::instance()->find($userId);
        if (empty($userInfo)) {
            PaymentApi::log('UPDATE vipPoint user not exist ,userId|'.$userId.'|token|'.$token.'|sourceAmount|'.$sourceAmount.'|sourceType|'.$sourceType.'|info|'.$info);
            return true;
        }
        PaymentApi::log('UPDATE vipPoint userId|'.$userId. '|computeGrade|'. $computeGrade.'|token|'.$token.'|oldPoint|'.($totalPoint - $point).'|totalPoint|'.$totalPoint);
        $syncMarketVipGrade = false;
        try {
            if (empty($userGradeInfo)) {
                //未查到vip信息,初始化vip,initVip
                $this->initVip($userInfo, $computeGrade, $totalPoint);
                if ($computeGrade > 0) {
                    $syncMarketVipGrade = true;
                }
            } else {
                //已有vip账户，更新相关信息
                if ($userGradeInfo['is_relegated']) {
                    //当前处于保级状态
                    if ($computeGrade > $userGradeInfo['service_grade']) {
                        //升级&解除保级状态
                        $this->upgrade($userId, $computeGrade, $totalPoint);
                        $syncMarketVipGrade = true;
                    } else if ($computeGrade == $userGradeInfo['service_grade']) {
                        //解除保级状态
                        $this->removeSafeguard($userId, $userGradeInfo, $computeGrade, $totalPoint);
                    } else {
                        //维持保级状态，如果会员实际等级发生降级，更新实际等级&增加记录
                        $this->safeguardGrade($userId, $userGradeInfo, $computeGrade, false, $totalPoint);
                    }
                } else {
                    //当前非保级状态
                    if ($computeGrade > $userGradeInfo['service_grade']) {
                        //升级
                        $this->upgrade($userId, $computeGrade, $totalPoint);
                        $syncMarketVipGrade = true;
                    } else if ($computeGrade < $userGradeInfo['service_grade']) {
                        //保级
                        $this->safeguardGrade($userId, $userGradeInfo, $computeGrade, true, $totalPoint);
                    } else {
                        //维持等级,更新会员账户经验值
                        $this->updateVipAccountPoint($userId, $totalPoint, $computeGrade);
                    }
                }
                if (empty($userGradeInfo['byear']) && $userInfo['byear']) {
                    //解决创建vip账户时,用户尚未实名认证导致未初始化生日字段的问题
                    $this->updateVipBirthday($userId, $userInfo['byear'], $userInfo['bmonth'], $userInfo['bday']);
                }
            }
            $sourceConfig  = $this->getSourceConfig($sourceType);
            $sourceWeight = $sourceConfig['sourceWeight'];
            $expiredMonth = $sourceConfig['expireMonth'];
            //根据point值的正负判断是加经验值还是过期经验值
            $vipPointLogService = new VipPointLogService();
            $sourceTypeVal = VipEnum::$vipSourceMap[$sourceType];
            if ($point > 0) {
                //vipPointLogModel入库的sourceType是数值，需要转换
                $vipPointLogService->acquirePoint($userId, $point, $sourceTypeVal, $sourceId, $info, $token, $sourceAmount, $sourceWeight, $expiredMonth, $pointLogId);
            } else {
                $vipPointLogService->expirePoint($userId, $point, $token, $sourceTypeVal, $sourceAmount, $info);
            }
            $db->commit();
            // 增加vip经验值变动的通知
            $param = array(
                'userId' => $userId,
                'point' => $point,
                'createTime' => time()
            );
            Msgbus::instance()->produce('vip_point_update', $param);
            //如果升级，需要给marketing同步升级信息
            if ($syncMarketVipGrade) {
                PaymentApi::log('UPDATE vipPoint  notify marketing userId|'.$userId. '|computeGrade|'. $computeGrade);
                $discountCenterService = new DiscountCenterService();
                $discountCenterService->updateUserVipGrade($userId, $computeGrade);
            }
            //增加信力推送
            if ($sourceType == VipEnum::VIP_SOURCE_INVITE) {
                CandyService::changeAmountByType($userId, $token, CandyService::SOURCE_TYPE_INVITE, $firstloanAnnual, $sourceTypeVal);
            }
            if ($sourceType == VipEnum::VIP_SOURCE_CHECKIN) {
                CandyService::changeAmountByType($userId, $token, CandyService::SOURCE_TYPE_CHECKIN, '0', $sourceTypeVal);
            }
            PaymentApi::log('VipService.candyAccount notify info:'."token:$token, userId:$userId, point:$point, sourceType:$sourceType, firstloanAnnual:$firstloanAnnual");
            // 增加夺宝机会
            if ($sourceType == VipEnum::VIP_SOURCE_INVITE) {
                $inviteeId = explode('_', $token)[1];
                PaymentApi::log('vipService.candySnatch notify info:'."token:$token, userId:$userId, inviteeId:$inviteeId, bidAmount:$bidAmount");
                $candySnatchService = new CandySnatchService();
                $candySnatchService->addSnatchChance($token, $userId, $inviteeId, $bidAmount);
            }
        } catch (\Exception $ex) {
            PaymentApi::log('VipService.updateVipPoint ERR:'.$ex->getMessage());
            $db->rollback();
            if (strpos($ex->getMessage(), 'Duplicate entry') !== false) {
                sleep(1);
                $this->updateVipPoint($userId, $sourceAmount, $sourceType, $token, $info, $sourceId, $pointLogId);
            } else {
                throw $ex;
            }
        }
        return true;
    }

    /**
     * updateVipBirthday更新vip账户生日信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-12-06
     * @param mixed $userId
     * @param mixed $byear
     * @param mixed $bmonth
     * @param mixed $bday
     * @access private
     * @return void
     */
    private function updateVipBirthday($userId, $byear, $bmonth, $bday) {
        PaymentApi::log('UPDATE vipPoint |updateVipBirthday| userId:'.$userId. "|byear|$byear|bmonth|$bmonth|bday|$bday");
        $actionTime = time();
        $data = array(
            'byear' => $byear,
            'bmonth' => $bmonth,
            'bday' => $bday,
        );
        $res = VipAccountModel::instance()->updateByUserId($data, $userId);
        if ($res < 1) {
            PaymentApi::log('UPDATE VIP_ACCOUNT ERR:'.$userId);
            throw new \Exception('更新会员生日失败');
        }
        return true;
    }

    /**
     * updateVipAccountPoint更新vip账户表在投资金，在等级未发生变更的情况下调用
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-25
     * @param mixed $userId
     * @param mixed $computeInfo
     * @access public
     * @return void
     */
    public function updateVipAccountPoint($userId, $totalPoint, $computeGrade) {
        PaymentApi::log('UPDATE vipPoint |updateVipAccountPoint| userId:'.$userId. '|point|'. $totalPoint ."|grade|".$computeGrade);
        $actionTime = time();
        $data = array(
            'point' => $totalPoint,
            'update_time' => $actionTime,
        );
        $res = VipAccountModel::instance()->updateByUserId($data, $userId);
        return true;
    }

    private function initVip($userInfo, $computeGrade, $totalPoint) {
        PaymentApi::log('UPDATE vipPoint |initVip| userId:'.$userInfo['id']. '|totalPoint|'. $totalPoint ."|grade|".$computeGrade);
        $vipInfo = $this->getVipInfo($userInfo['id']);
        if ($vipInfo) {
            PaymentApi::log('UPDATE vipPoint |initVip| exist userId:'.$userInfo['id']. '|service_grade|'. $vipInfo['service_grade']);
            return true;
        }
        //创建vip账户
        //增加viplog
        //礼包log
        $actionTime = time();
        $data = array(
            'user_id' => $userInfo['id'],
            'register_time' => $userInfo['create_time'] + 8 *3600,//user表的create_time慢8小时
            'service_grade' => $computeGrade,
            'actual_grade' => $computeGrade,
            'is_relegated' => 0,
            'relegate_time' => 0,
            'point' => $totalPoint,
            'create_time' => $actionTime,
            'update_time' => $actionTime,
            'byear' => $userInfo['byear'],
            'bmonth' => $userInfo['bmonth'],
            'bday' => $userInfo['bday']
        );
        VipAccountModel::instance()->addAccount($data);
        $logData = array(
            'user_id' => $userInfo['id'],
            'log_type' => VipEnum::VIP_ACTION_INIT,
            'service_grade' => $computeGrade,
            'actual_grade' => $computeGrade,
            'point' => $totalPoint,
            'create_time' => $actionTime,
            'note' => '创建会员信息'
        );
        $vipLogId = VipLogModel::instance()->addLog($logData);
        if ($computeGrade > 0) {
            //创建vip账户时，判断如果等级大于0则增加升级标志和发礼包，否则只是创建账户信息
            VipAccountModel::instance()->addUpgradeFlag($userInfo['id']);
            $this->addUpgradeGiftLogAndSendGift($userInfo['id'], $computeGrade, $vipLogId);
        }
        return true;
    }

    /**
     * upgrade升级
     * 1.vip账户更新
     * 2.viplog记录
     * 3.会员礼包记录
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-10
     * @param mixed $userId
     * @param mixed $grade
     * @access public
     * @return void
     */
    private function upgrade($userId, $computeGrade, $totalPoint) {
        PaymentApi::log('UPDATE vipPoint |upgrade| userId:'.$userId. '|point|'. $totalPoint."|grade|".$computeGrade);
        $actionTime = time();
        $data = array(
            'service_grade' => $computeGrade,
            'actual_grade' => $computeGrade,
            'is_relegated' => 0,
            'relegate_time' => 0,
            'point' => $totalPoint,
            'update_time' => $actionTime,
        );
        VipAccountModel::instance()->updateByUserId($data, $userId);
        $logData = array(
            'user_id' => $userId,
            'log_type' => VipEnum::VIP_ACTION_UPGRADE,
            'service_grade' => $computeGrade,
            'actual_grade' => $computeGrade,
            'point' => $totalPoint,
            'create_time' => $actionTime,
            'note' => ''
        );
        $vipLogId = VipLogModel::instance()->addLog($logData);
        VipAccountModel::instance()->addUpgradeFlag($userId);
        $this->addUpgradeGiftLogAndSendGift($userId, $computeGrade, $vipLogId);

        return true;
    }

    /**
     * addUpgradeGiftLogAndSendGift升级礼包逻辑
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-20
     * @param int $userId 用户id
     * @param int $grade 会员等级
     * @param int $vipLogId 升级日志id
     * @param bool $withSms 是否发送短信，默认发送
     * @param bool $withPush 是否推送，默认推送
     * @access public
     * @return void
     */
    public function addUpgradeGiftLogAndSendGift($userId, $grade, $vipLogId, $withSms = true, $withPush = true) {
        PaymentApi::log('addUpgradeGiftLogAndSendGift | userId:'.$userId."|grade|".$grade."|vipLogId:$vipLogId");
        // 只有主站用户有礼包权益
        if (!$this->checkMainSite($userId)) {
            PaymentApi::log('addUpgradeGiftLogAndSendGift not mainsite user | userId:'.$userId."|grade|".$grade."|vipLogId:$vipLogId");
            return true;
        }

        $createTime = time();
        for($i = 1; $i <= $grade; $i++) {
            $logData = array();
            $gradeAlias = VipEnum::$vipGrade[$i]['alias'];
            $token = VipGiftLogModel::VIP_GIFT_TOKEN_PRE_UPGRADE .$userId.'_'.$gradeAlias;
            $giftLog = VipGiftLogModel::instance()->getVipGiftLogByToken($token);
            if ($giftLog) {
                // 同一等级只发放一次
                continue;
            } else {
                // 获取该等级的礼包数据
                $giftInfo = $this->getVipGiftRebate($i);
                // 如果升级礼包没有配置奖品，则忽略该等级礼包
                if (empty($giftInfo['groupId'])) {
                    continue;
                }

                $logData = array(
                    'user_id' => $userId,
                    'log_id' => $vipLogId,
                    'gift_info' => json_encode($giftInfo, JSON_UNESCAPED_UNICODE),
                    'award_type' => VipGiftLogModel::VIP_AWARD_TYPE_UPGRADE,
                    'service_grade' => $grade,
                    'allowance' => '',
                    'create_time' => $createTime,
                    'status' => VipGiftLogModel::VIP_GIFT_STATUS_INIT,
                    'token' => $token,
                    'gift_type' => $giftInfo['groupType']
                );

                try{
                    $giftLogId = VipGiftLogModel::instance()->addLog($logData);
                    // 根据viplog执行返利event
                    if ($i == $grade) {
                        // 只有最后一次等级升级才发短信和推送
                        $event = new \core\event\VipGiftEvent($userId, $giftLogId, $withSms, $withPush);
                    } else {
                        $event = new \core\event\VipGiftEvent($userId, $giftLogId, false, false);
                    }
                    $event->execute();
                } catch (\Exception $ex) {
                    if (strpos($ex->getMessage(), 'Duplicate entry') !== false) {
                        continue;
                    } else {
                        //如果同步执行异常，加入异步任务
                        $executeTime = XDateTime::now();
                        $executeTime = $executeTime->addMinute(2);
                        $taskObj = new GTaskService();
                        $taskId = $taskObj->doBackground($event, 3, 'NORMAL', $executeTime);
                        if (!$taskId) {
                            PaymentApi::log("vip礼包任务异常, 插入VipGiftEvent任务失败giftLogId:".$giftLogId);
                        }
                        throw $ex;
                    }
                }
            }
        }
        return true;
    }

    /**
     * addPeriodGiftLogAndSendGift发送周年或生日礼包逻辑
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-20
     * @param int $userId 用户id
     * @param int $serviceGrade 服务等级
     * @param int $vipLogId 升级日志id
     * @param string $token 幂等token
     * @param int $awardType 奖励类型（生日或周年）
     * @param bool $withSms 是否发送短信，默认发送
     * @param bool $withPush 是否推送，默认推送
     * @access public
     * @return bool
     */
    public function addPeriodGiftLogAndSendGift($userId, $serviceGrade, $vipLogId, $token, $awardType,
                                                $withSms = true, $withPush = true) {
        // 开关打开后，只有主站用户有礼包权益
        if (!$this->checkMainSite($userId)) {
            PaymentApi::log('addPeriodGiftLogAndSendGift not mainsite user | userId:'.$userId."|grade|".$serviceGrade."|vipLogId:$vipLogId");
            return true;
        }

        $giftLog = VipGiftLogModel::instance()->getVipGiftLogByToken($token);
        if ($giftLog) {
            // 幂等判断
            return true;
        }

        if ($awardType == VipGiftLogModel::VIP_AWARD_TYPE_BIRTHDAY) {
            $giftInfo = $this->getVipBirthdayRebate($serviceGrade);
        } else if ($awardType == VipGiftLogModel::VIP_AWARD_TYPE_ANNIVERSARY) {
            $giftInfo = $this->getVipAnniverRebate($serviceGrade);
        } else {
            return true;
        }

        if (empty($giftInfo['groupId'])) {
            // 如果对应礼包没有配置奖品，则忽略该礼包
            return true;
        }

        $logData = array(
            'user_id' => $userId,
            'log_id' => $vipLogId,
            'gift_info' => json_encode($giftInfo, JSON_UNESCAPED_UNICODE),
            'award_type' => $awardType,
            'service_grade' => $serviceGrade,
            'allowance' => '',
            'create_time' => time(),
            'status' => VipGiftLogModel::VIP_GIFT_STATUS_INIT,
            'token' => $token,
            'gift_type' => $giftInfo['groupType'],
        );

        try{
            $giftLogId = VipGiftLogModel::instance()->addLog($logData);
            $taskObj = new GTaskService();
            // 根据viplog执行返利event
            $event = new \core\event\VipGiftEvent($userId, $giftLogId, $withSms, $withPush);
            $taskId = $taskObj->doBackground($event, 3);
            if (!$taskId) {
                throw new \Exception('vip礼包任务异常, 插入VipGiftEvent任务失败');
            }
        } catch (\Exception $ex) {
            if (strpos($ex->getMessage(), 'Duplicate entry') !== false) {
                return true;
            }
            throw $ex;
        }

        return true;
    }

    /**
     * removeSafeguard解除保级
     * 1.移除保级状态,恢复正常等级
     * 2.增加viplog
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-10
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    private function removeSafeguard($userId, $userGradeInfo, $computeGrade, $totalPoint) {
        PaymentApi::log('UPDATE vipPoint |removeSafeguard| userId:'.$userId. '|point|'. $totalPoint."|grade|".$computeGrade);
        $actionTime = time();
        $actionTime = time();
        $data = array(
            'is_relegated' => 0,
            'relegate_time' => 0,
            'actual_grade' => $computeGrade,
            'point' => $totalPoint,
        );
        VipAccountModel::instance()->updateByUserId($data, $userId);
        $logData = array(
            'user_id' => $userId,
            'log_type' => VipEnum::VIP_ACTION_REMOVE_SAFEGUARDGRADE,
            'service_grade' => $userGradeInfo['service_grade'],
            'actual_grade' => $computeGrade,
            'point' => $totalPoint,
            'create_time' => $actionTime,
            'note' => ''
        );
        VipLogModel::instance()->addLog($logData);
        return true;
    }

    /**
     * safeguardGrade保级
     * 1.修改状态为保级
     * 2.增加viplog
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-10
     * @param mixed $userId
     * @access public
     * @return void
     */
    private function safeguardGrade($userId, $vipInfo, $computeGrade, $updateRelegate = true, $totalPoint) {
        PaymentApi::log('UPDATE vipPoint |safeguardGrade| userId:'.$userId. '|point|'. $totalPoint."|service_grade|".$vipInfo['service_grade']."|grade|".$computeGrade."|updateRelegate|".$updateRelegate);
        $actionTime = time();
        $actionTime = time();
        if ($updateRelegate) {
            $data = array(
                'is_relegated' => 1,
                'relegate_time' => $actionTime,
                'actual_grade' => $computeGrade,
                'point' => $totalPoint,
            );
        } else {
            $data = array(
                'actual_grade' => $computeGrade,
                'point' => $totalPoint,
            );
        }
        VipAccountModel::instance()->updateByUserId($data, $userId);
        $logData = array(
            'user_id' => $userId,
            'log_type' => VipEnum::VIP_ACTION_SAFEGUARDGRADE,
            'service_grade' => $vipInfo['service_grade'],
            'actual_grade' => $computeGrade,
            'point' => $totalPoint,
            'create_time' => $actionTime,
            'note' => ''
        );
        VipLogModel::instance()->addLog($logData);
        return true;
    }

    /**
     * degrade降级逻辑
     * 1.更新会员等级
     * 2.增加viplog
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-20
     * @param int $userId
     * @param int $toGrade
     * @access public
     * @return void
     */
    public function degrade($userId) {
        $vipInfo = $this->getVipInfo($userId);
        $totalPoint = $vipInfo['point'];
        $computeGrade = $this->computeVipGrade($totalPoint);
        PaymentApi::log('UPDATE vipPoint |degrade| userId:'.$userId. '|point|'. $totalPoint."|grade|".$computeGrade);
        $discountCenterService = new DiscountCenterService();
        $discountCenterService->updateUserVipGrade($userId, $computeGrade);
        PaymentApi::log('UPDATE vipPoint  notify marketing userId|'.$userId. '|computeGrade|'. $computeGrade);

        if (empty($vipInfo)) {
            return true;
        }
        $actionTime = time();
        $data = array(
            'is_relegated' => 0,
            'relegate_time' => 0,
            'actual_grade' => $computeGrade,
            'service_grade' => $computeGrade,
            'update_time' => $actionTime,
        );
        VipAccountModel::instance()->updateByUserId($data, $userId);
        $logData = array(
            'user_id' => $userId,
            'log_type' => VipEnum::VIP_ACTION_DEGRADE,
            'service_grade' => $computeGrade,
            'actual_grade' => $computeGrade,
            'point' => $totalPoint,
            'create_time' => $actionTime,
            'note' => ''
        );
        VipLogModel::instance()->addLog($logData);
        return true;
    }

    /**
     * vipRaiseInterest加息通用方法
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-11-20
     * @param mixed $userId
     * @param mixed $money
     * @param mixed $annualizedAmount
     * @param mixed $token
     * @param mixed $sourceType
     * @param mixed $couponGroupId
     * @access public
     * @return void
     */
    public function vipRaiseInterest($userId, $money, $annualizedAmount, $token, $sourceType, $couponGroupId) {
        PaymentApi::log('vip raiseInterest 返利记录userId|' . $userId. '|token|'.$token."|money|$money|annualizedAmount|$annualizedAmount|sourceType|$sourceType|couponGroupId|$couponGroupId");
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return true;
        }
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log('vip raiseInterest返利记录userId|' . $userId. '|企业会员不加息');
            return true;
        }
        $rateGrade = $vipInfo['service_grade'];
        $raiseInterest = $this->getVipInterest($rateGrade);
        $rebateMoney = bcdiv($annualizedAmount * $raiseInterest, 100, 2);
        $dealType = VipEnum::$vipSourceMap[$sourceType];
        if (bccomp($rebateMoney, '0.01' ,2) >= 0) {
            $rateLog = VipRateLogModel::instance()->getVipRateLogByToken($token);
            if (empty($rateLog)) {
                $rateLog = array(
                    'user_id' => $userId,
                    'deal_type' => $dealType,
                    'deal_load_id' => 0,
                    'service_grade' => $vipInfo['service_grade'],
                    'actual_grade' => $vipInfo['actual_grade'],
                    'rebate_rate' => $raiseInterest,
                    'bid_amount' => $money,
                    'bid_annual_amount' => $annualizedAmount,
                    'allowance_type' => 2,//1现金，2红包
                    'allowance_money' => $rebateMoney,
                    'token' => $token,
                    'coupon_group_id' => $couponGroupId,
                    'create_time' => time(),
                );
                $rateLogId = VipRateLogModel::instance()->addLog($rateLog);
                PaymentApi::log('vip raiseInterest返利记录token|'.$token.'|data|' . json_encode($rateLog).'|rateLogId|'.$rateLogId);
            }
            // 根据viplog执行返利event
            $taskObj = new GTaskService();
            $event = new \core\event\VipRebateEvent($userId, 0, $token);
            $taskId = $taskObj->doBackground($event, 3);
            // 必须保证所有任务的插入成功，否则抛出异常，保证数据的一致性
            if (!$taskId) {
                throw new \Exception('vip返利失败, 插入VipRebateEvent任务失败');
            }
            return $rateLog;
        } else {
            PaymentApi::log('vip raiseInterest返利记录token|'.$token.'|money less than 0.01|money:'.$rebateMoney);
        }
        return true;
    }

    /**
     * vipRebateRate根据交易信息处理vip返利
     * 1.计算返利金额[比较加息日期的会员等级和投资时的会员等级，取高等级返利]
     * 2.生成返利记录
     * 3.发送返利礼券
     * @param mixed $dealLoadInfo
     * @param mixed $rebateTime
     * @param string $sourceType
     * @access public
     * @return void
     */
    public function vipRebateRate($dealLoadInfo, $rebateTime, $sourceType = 'p2p') {
        $userId = $dealLoadInfo['user_id'];
        PaymentApi::log('vip返利记录userId|' . $userId. '|dealLoadId:'.$dealLoadInfo['id']);
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return true;
        }
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log('vip返利记录userId|' . $userId. '|企业会员不加息');
            return true;
        }
        $annualizedAmount = O2OUtils::getAnnualizedAmountByDealLoadId($dealLoadInfo['id']);
        //获取较高的返利等级
        $rateGrade = VipAccountModel::instance()->getVipRateSnap($userId, $dealLoadInfo['create_time'], $rebateTime);
        $raiseInterest = $this->getVipInterest($rateGrade);
        $rebateMoney = bcdiv(bcmul($annualizedAmount, $raiseInterest), 100, 2);
        //生成唯一token
        $token = $sourceType."_".$userId."_".$dealLoadInfo['id'];//p2p_userId_dealLoadId
        $dealType = VipEnum::$vipSourceMap[$sourceType];
        if (bccomp($rebateMoney, '0.01' ,2) >= 0) {
            $rateLog = VipRateLogModel::instance()->getVipRateLogByToken($token);
            if (empty($rateLog)) {
                $rateLog = array(
                    'user_id' => $userId,
                    'deal_type' => $dealType,
                    'deal_load_id' => $dealLoadInfo['id'],
                    'service_grade' => $vipInfo['service_grade'],
                    'actual_grade' => $vipInfo['actual_grade'],
                    'rebate_rate' => $raiseInterest,
                    'bid_amount' => $dealLoadInfo['money'],
                    'bid_annual_amount' => $annualizedAmount,
                    'allowance_type' => 2,//1现金，2红包
                    'allowance_money' => $rebateMoney,
                    'token' => $token,
                    'coupon_group_id' => app_conf('COUPON_GROUP_ID_VIP_REBATE_P2P'),
                    'create_time' => time(),
                );
                $rateLogId = VipRateLogModel::instance()->addLog($rateLog);
                PaymentApi::log('vip返利记录token|'.$token.'|data|' . json_encode($rateLog).'|rateLogId|'.$rateLogId);
            }
            // 根据viplog执行返利event
            $taskObj = new GTaskService();
            $event = new \core\event\VipRebateEvent($userId, $dealLoadInfo['id'], $token);
            $taskId = $taskObj->doBackground($event, 3);
            // 必须保证所有任务的插入成功，否则抛出异常，保证数据的一致性
            if (!$taskId) {
                throw new \Exception('vip返利失败, 插入VipRebateEvent任务失败');
            }
            return $rateLog;
        } else {
            PaymentApi::log('vip返利记录token|'.$token.'|money less than 0.01|money:'.$rebateMoney);
        }
        return true;
    }

    /**
     * vipRaiseRate 不依赖业务的加息逻辑
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-10-06
     * @param mixed $userId
     * @param mixed $bidTime
     * @param mixed $bidAmount
     * @param mixed $annualizedAmount
     * @param mixed $dealLoadId
     * @param mixed $token
     * @param mixed $sourceType
     * @access public
     * @return void
     */
    public function vipRaiseRate($userId, $bidTime, $bidAmount, $annualizedAmount, $dealLoadId, $token, $sourceType) {
        PaymentApi::log('vip返利记录userId|' . $userId. '|dealLoadId:'.$dealLoadId.'|sourceType|'.$sourceType);
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return true;
        }
        PaymentApi::log('vip返利记录userId|'.$userId.'|service_grade:'.$vipInfo['service_grade']);
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log('vip返利记录userId|' . $userId. '|企业会员不加息');
            return true;
        }
        //获取较高的返利等级
        $rebateTime = time();
        $rateGrade = VipAccountModel::instance()->getVipRateSnap($userId, $bidTime, $rebateTime);
        $raiseInterest = $this->getVipInterest($rateGrade);
        $rebateMoney = bcdiv(bcmul($annualizedAmount, $raiseInterest), 100, 2);
        $dealType = VipEnum::$vipSourceMap[$sourceType];
        if (bccomp($rebateMoney, '0.01' ,2) >= 0) {
            $rateLog = VipRateLogModel::instance()->getVipRateLogByToken($token);
            if (empty($rateLog)) {
                $rateLog = array(
                    'user_id' => $userId,
                    'deal_type' => $dealType,
                    'deal_load_id' => $dealLoadId,
                    'service_grade' => $vipInfo['service_grade'],
                    'actual_grade' => $vipInfo['actual_grade'],
                    'rebate_rate' => $raiseInterest,
                    'bid_amount' => $bidAmount,
                    'bid_annual_amount' => $annualizedAmount,
                    'allowance_type' => 2,//1现金，2红包
                    'allowance_money' => $rebateMoney,
                    'token' => $token,
                    'coupon_group_id' => app_conf('COUPON_GROUP_ID_VIP_REBATE_P2P'),
                    'create_time' => time(),
                );
                $rateLogId = VipRateLogModel::instance()->addLog($rateLog);
                PaymentApi::log('vip返利记录token|'.$token.'|data|' . json_encode($rateLog).'|rateLogId|'.$rateLogId);
            }
            // 根据viplog执行返利event
            $event = new \core\event\VipRebateEvent($userId, $dealLoadId, $token);
            $event->execute();
            return $rateLog;
        } else {
            PaymentApi::log('vip返利记录token|'.$token.'|money less than 0.01|money:'.$rebateMoney);
        }
        return true;
    }

    /**
     * vipGoldRebateRate根据交易信息处理vip返利
     * 1.计算返利金额[比较加息日期的会员等级和投资时的会员等级，取高等级返利]
     * 2.生成返利记录
     * 3.发送返利礼券
     * @access public
     * @return void
     */
    public function vipGoldRebateRate($userId, $dealLoadId, $money, $loanType, $dealCreateTime, $repayTime, $rebateTime, $sourceType = 'gold') {
        PaymentApi::log('vip_gold返利记录userId|' . $userId. '|dealLoadId:'.$dealLoadId);
        $vipInfo = $this->getVipInfo($userId);
        if (empty($vipInfo)) {
            return true;
        }
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log('vip_gold返利记录userId|' . $userId. '|企业会员不加息');
            return true;
        }

        $goldBidService = new GoldBidService();
        $annualizedAmount = $goldBidService->getAnnualizedAmount($loanType, $repayTime, $money);
        //获取较高的返利等级
        $rateGrade = VipAccountModel::instance()->getVipRateSnap($userId, $dealCreateTime, $rebateTime);

        $raiseInterest = $this->getVipInterest($rateGrade);
        $rebateMoney = bcdiv(bcmul($annualizedAmount, $raiseInterest), 100, 2);
        //生成唯一token
        $token = $sourceType."_".$userId."_".$dealLoadId;//p2p_userId_dealLoadId
        $dealType = VipEnum::$vipSourceMap[$sourceType];
        if (bccomp($rebateMoney, '0.01' ,2) >= 0) {
            $rateLog = VipRateLogModel::instance()->getVipRateLogByToken($token);
            if (empty($rateLog)) {
                $rateLog = array(
                    'user_id' => $userId,
                    'deal_type' => $dealType,
                    'deal_load_id' => $dealLoadId,
                    'service_grade' => $vipInfo['service_grade'],
                    'actual_grade' => $vipInfo['actual_grade'],
                    'rebate_rate' => $raiseInterest,
                    'bid_amount' => $money,
                    'bid_annual_amount' => $annualizedAmount,
                    'allowance_type' => 2,//1现金，2红包
                    'allowance_money' => $rebateMoney,
                    'token' => $token,
                    'coupon_group_id' => app_conf('COUPON_GROUP_ID_VIP_REBATE_GOLD'),
                    'create_time' => time(),
                );
                $rateLogId = VipRateLogModel::instance()->addLog($rateLog);
                PaymentApi::log('vip_gold返利记录token|'.$token.'|data|' . json_encode($rateLog).'|rateLogId|'.$rateLogId);
            }
            // 根据viplog执行返利event
            $taskObj = new GTaskService();
            $event = new \core\event\VipRebateEvent($userId, $dealLoadId, $token);
            $taskId = $taskObj->doBackground($event, 3);
            // 必须保证所有任务的插入成功，否则抛出异常，保证数据的一致性
            if (!$taskId) {
                throw new \Exception('vip_gold返利失败, 插入VipRebateEvent任务失败');
            }
            return $rateLog;
        } else {
            PaymentApi::log('vip_gold返利记录token|'.$token.'|money less than 0.01|money'.$rebateMoney);
        }
        return true;
    }

    /**
     * getVipGradePrivilege获取所有特权和等级
     *
     * @author yanjun <yanjun5@ucfgroup.com>
     * @date 2017-06-23
     * @param $isPrivilegeDetail 是否查看特权信息
     * @param $isLightImg 是否取点亮的图片
     * @access public
     * @return void
     */
    public function getVipGradePrivilege($userId,$vipGrade = null, $isPrivilegeDetail = false, $isLightImg = true) {
        $isMainSite = $this->checkMainSite($userId);
        $vipGradeInfo = array();
        $vipGradePrivilege = array();
        $gradeInfo = array();//等级
        $vipConf = $this->getLevelVipConf();

        if ($vipGrade) {
            $vipGradeInfo = array($vipGrade => $vipConf[VipEnum::$vipGradeNoToAlias[$vipGrade]]);
        } else {
            foreach ($vipConf as $gradeAlias => $levelInfo) {
                $vipGradeInfo[VipEnum::$vipGradeAliasToNo[$gradeAlias]] = $levelInfo;
            }
        }
        if(empty($vipGradeInfo)){
            return false;
        }
        foreach ($vipGradeInfo as $grade => $info ){
            if($grade == self::VIP_GRADE_PT){
                continue;
            }
            $gradeInfo['condition'] = "经验值达到".$info['minInvest'];//等级条件
            $gradeInfo['vipGrade'] = $info['vipGrade'];//会员等级
            $gradeInfo['vipGradeName'] = $info['name'];
            $gradeInfo['vipGradeImgUrl'] = $info['imgUrl'];

            if($isPrivilegeDetail){
                $privilegeDetail = array();//特权详情
                $arrayPrivilege = array();
                foreach ($info['privilege'] as $key => $value){
                    if(!$isMainSite && in_array($value,array(VipEnum::PRIVILEGE_GIFT,VipEnum::PRIVILEGE_BIRTHDAY_DISCOUNT,VipEnum::PRIVILEGE_ANNIVER_DISCOUNT))){
                        continue;
                    }
                    $privilegeDetails = $this->getPrivilegeDetail($info['vipGrade'], $value, false, $isLightImg);
                    if(empty($privilegeDetails)){
                        return false;
                    }
                    $privilegeDetail['privilegeId'] = $privilegeDetails['privilegeId'];
                    $privilegeDetail['name'] = $privilegeDetails['name'];
                    $privilegeDetail['describe'] = $privilegeDetails['describe'];
                    $privilegeDetail['imgUrl'] = $privilegeDetails['imgUrl'];
                    $privilegeDetail['weight'] = $privilegeDetails['weight'];
                    $arrayPrivilege[] = $privilegeDetail;
                }
                $gradeInfo['privilegeList'] = $this->getSortedPrivilege($arrayPrivilege);
            }
            $vipGradePrivilege[]  = $gradeInfo;
        }
        return $vipGradePrivilege;
    }

    public function getSortedPrivilege($privilegeList) {
        $list = array();
        foreach($privilegeList as $privilege) {
            $list[$privilege['weight']] = $privilege;
        }
        krsort($list);
        return array_values($list);
    }
    /**
     * 获取特权详情
     *
     * @author yanjun <yanjun5@ucfgroup.com>
     * @date 2017-06-29
     * @param $isDetail 是否查看特权介绍
     * @param $isLightImg 是否取点亮的图片
     * @access public
     * @return void
     */
    public function getPrivilegeDetail($vipGrade, $privilegeId,$isDetail = false, $isLightImg = true) {
        $privilegeDetail = VipPrivilegeModel::instance()->getFormatPrivilegeDetail($privilegeId);

        if(empty($privilegeDetail)){
            return false;
        }

        $imgConf = $privilegeDetail['imgConf'];
        //点亮图片取配置的等级图片，否则取默认的置灰配置
        $privilegeDetail['imgUrl'] = $isLightImg ? $imgConf[VipEnum::$vipGradeNoToAlias[$vipGrade]] : $imgConf['grey'];
        if(empty($privilegeDetail['imgUrl'])){
            return false;
        }

        $raiseInterest = $this->getVipInterest($vipGrade);
        if($privilegeId == self::VIP_PRIVILEGE_RAISE_INTEREST_ID) {//投资加息
            $privilegeDetail['describe'] = str_replace('{$n}', $raiseInterest,  $privilegeDetail['describe']);
        } elseif($privilegeId == self::VIP_PRIVILEGE_GIFT_VALUE_ID) {//升级礼包面值
            $privilegeDetail['describe'] = str_replace('{$m}', VipEnum::$vipGrade[$vipGrade]['giftValue'],  $privilegeDetail['describe']);
        }

        if($isDetail && in_array($privilegeId, array(self::VIP_PRIVILEGE_RAISE_INTEREST_ID, self::VIP_PRIVILEGE_GIFT_VALUE_ID))){
            foreach (VipEnum::$vipGrade as $grade => $giftValue){
                if($grade == self::VIP_GRADE_PT){
                    continue;
                }
                $itemRaiseInterest = $this->getVipInterest($grade);
                $conf['vipGrade'] = $giftValue['name'];
                $conf['raiseInterest'] = $itemRaiseInterest > 0 ? $itemRaiseInterest.'%' : $itemRaiseInterest;//投资加息配置列表
                $conf['giftValue'] = $giftValue['giftValue'];;//升级礼包配置列表
                $privilegeDetail['giftConfTable'][] = $conf;
            }
        }

        $privilegeDetail['disclaimer'] = VipEnum::$privilegeDisclaimer;
        return $privilegeDetail;
    }

    /**
     * 获取会员中心的等级信息
     *
     * @author yanjun <yanjun5@ucfgroup.com>
     * @date 2017-06-29
     * @param
     * @access public
     * @return void
     */
    public function getVipAccoutForGrade($userId,$vipGrade = 0) {
        $isMainSite = $this->checkMainSite($userId);
        //会员前后等级
        $preGrade = $vipGrade == self::VIP_GRADE_PT ? null : ($vipGrade - 1);
        $nextGrade = $vipGrade == self::VIP_GRADE_HG ? null : ($vipGrade + 1);
        $vipConf = $this->getLevelVipConf();
        foreach ($vipConf as $gradeAlias => $levelInfo) {
            $arrayVipGrade[VipEnum::$vipGradeAliasToNo[$gradeAlias]] = $levelInfo;
        }

        $vipInfo['vipGrade'] = $vipGrade;
        $vipInfo['vipGradeName'] = $vipGrade == self::VIP_GRADE_PT ? '普通用户' : $arrayVipGrade[$vipGrade]['name'];
        $vipInfo['vipGradeImgUrl'] = $vipGrade == self::VIP_GRADE_PT ? null : $arrayVipGrade[$vipGrade]['imgUrl'];
        $vipInfo['preGrade'] = $preGrade;
        $vipInfo['preGradeName'] = !empty($preGrade) ? $arrayVipGrade[$preGrade]['name'] : null;
        $vipInfo['preGradeImgUrl'] = !empty($preGrade) ? $arrayVipGrade[$preGrade]['imgUrl'] : null;
        $vipInfo['nextGrade'] = $nextGrade;
        $vipInfo['nextGradeName'] = !empty($nextGrade) ? $arrayVipGrade[$nextGrade]['name']: null;
        $vipInfo['nextGradeImgUrl'] = !empty($nextGrade) ? $arrayVipGrade[$nextGrade]['imgUrl'] : null;

        //展示的特权列表
        $vipInfo['vipGradeList'] = array();
        if($vipGrade != self::VIP_GRADE_PT){
            $vipGradeList = $this->getVipGradePrivilege($userId,$vipGrade, true, true);
            if(empty($vipGradeList)){
                return false;
            }
            $vipInfo['vipGradeList'] = $vipGradeList['0']['privilegeList'];
        }

        //下一个等级享有的特权array
        $diffPrivilegeList = array();
        $nextGradePrivilege = !empty($nextGrade) ? $arrayVipGrade[$nextGrade]['privilege'] : array();
        $vipGradePrivilege = !empty($vipGrade) ? $arrayVipGrade[$vipGrade]['privilege'] : array();

        $diffPrivilege = array_diff($nextGradePrivilege, $vipGradePrivilege);
        foreach ($diffPrivilege as $key => $value){
            if(!$isMainSite && in_array($value,array(VipEnum::PRIVILEGE_GIFT,VipEnum::PRIVILEGE_BIRTHDAY_DISCOUNT,VipEnum::PRIVILEGE_ANNIVER_DISCOUNT))){
                continue;
            }
            $diffPrivilegeValue = $this->getPrivilegeDetail($nextGrade, $value, false, false);
            if(empty($diffPrivilegeValue)){
                return false;
            }
            $diffPrivilegeList[] = $diffPrivilegeValue;
        }
        $vipInfo['nextGradePrivilege'] = $diffPrivilegeList;

        return $vipInfo;
    }

    public function getExpireInfoAndIncome($userId) {
        $date = date('Y-m-d');
        $vipPointLogService = new VipPointLogService();
        $soonExpirePoint = $vipPointLogService->getSoonExpirePoint($userId);
        $income = $vipPointLogService->getThisMonthAcquirePoint($userId);
        $endTime = strtotime(sprintf('%s +%s month', date("Y-m-01", strtotime($date)), 1)) - 1;
        $expireDate = date('Y-m-d', $endTime);
        return array('income' => $income, 'expireInfo' => ($soonExpirePoint > 0) ? ($expireDate.'即将过期'.$soonExpirePoint) : '', 'expirePoint' => $soonExpirePoint);
    }

    /**
     * updateVipPointCallback投资成功后回调更新经验值
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-08-28
     * @param mixed $param
     * @access public
     * @return void
     */
    public function updateVipPointCallback($param) {
        return $this->updateVipPoint($param['userId'], $param['sourceAmount'], $param['sourceType'], $param['token'], $param['info'], $param['sourceId']);
    }

    /**
     * getVipInfoForCC客服中心获取vip信息接口
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-09-27
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getVipInfoForCC($userId) {
        $vipInfo = VipAccountModel::instance()->getVipAccountByUserId($userId);
        $result['actualGrade'] = $vipInfo['actual_grade'] ?: 0;
        $result['serviceGrade'] = $vipInfo['service_grade'] ?: 0;
        $result['relegateEndTime'] = ($vipInfo['is_relegated']) ? date('Y-m-d',strtotime(sprintf('%s +1 year',date("Y-m-d",(strtotime(sprintf('%s +1 month', date("Y-m-01",$vipInfo['relegate_time']))) - 1))))) : 0;
        //3个月内最高等级
        $startTime = strtotime('-90 days');
        $topGrade = VipLogModel::instance()->getTopGradeByStartTime($userId, $startTime);
        $result['topGrade'] = $topGrade ?: 0;
        //获取末笔投资时间，一周内回款总额，最后一次充值，提现，回款时间
        $userLogService = new UserLogService();
        $bidLog = $userLogService->get_user_log(array(0,1),$userId, '', false, '投标冻结');
        $chargeLog = $userLogService->get_user_log(array(0,1),$userId, '', false, '充值');
        $withdrawLog = $userLogService->get_user_log(array(0,1),$userId, '', false, '提现成功');
        $repayLogList = $userLogService->get_user_log(array(0,1),$userId, '', false, '还本');
        $repayLogList1 = $userLogService->get_user_log(array(0,1),$userId, '', false, '提前还款本金');
        $repayTime = ($repayLogList1['list'][0]['log_time'] > $repayLogList['list'][0]['log_time']) ? $repayLogList1['list'][0]['log_time'] : $repayLogList['list'][0]['log_time'];

        $result['lastBidTime'] = $bidLog['list'] ? date('Y-m-d H:i:s',$bidLog['list'][0]['log_time'] + 8*3600) : 0;
        $result['lastChargeTime'] = $chargeLog['list'] ? date('Y-m-d H:i:s', $chargeLog['list'][0]['log_time'] + 8*3600) : 0;
        $result['lastWithdrawTime'] = $withdrawLog['list'] ? date('Y-m-d H:i:s',$withdrawLog['list'][0]['log_time'] + 8*3600) : 0;
        $result['lastRepayTime'] = $repayTime ? date('Y-m-d H:i:s', $repayTime + 8*3600) : 0;
        //获取当前账号余额
        $userService = new UserService();
        $user_info = $userService->getUser($userId);
        $userThirdBalanceService  = new UserThirdBalanceService();
        $balanceResult = $userThirdBalanceService->getUserSupervisionMoney($userId);
        $user_info['svCashMoney'] = $balanceResult['supervisionBalance'];
        $user_info['svFreezeMoney'] = $balanceResult['supervisionLockMoney'];
        $user_info['svTotalMoney'] = $balanceResult['supervisionMoney'];
        $result['totalCashMoney'] = Finance::addition(array($user_info['money'], $user_info['svCashMoney']), 2);//现金金额
        //获取一周内待回款金额
        $timestamp = strtotime("+7 days");
        $sql = sprintf("SELECT SUM(`money`) AS `m` FROM firstp2p_deal_loan_repay WHERE `loan_user_id`='%d' AND status=0 AND time>".time()." AND time<=$timestamp", intval($userId));
        $repayMoney = DealLoanRepayModel::instance()->findBySqlViaSlave($sql);
        $result['repayMoney'] = $repayMoney['m'] ? : 0;
        return $result;
    }

    /**
     * getVipBidErrMsg获取不符合vip投资的文案提示
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-10-09
     * @param mixed $grade
     * @access public
     * @return void
     */
    public function getVipBidErrMsg($grade) {
        $msg = '指定VIP用户可投';
        try{
            $activeConf = VipBidConfModel::instance()->findBy('status=1 AND is_delete=0 LIMIT 1');
            if ($activeConf) {
                $conf = json_decode($activeConf['conf'], true);
                $vipMsg = $conf[VipEnum::$vipGradeNoToAlias[$grade]];
                if($vipMsg) {
                    return $vipMsg;
                }
            }
        } catch (\Exception $e) {
            PaymentApi::log('获取不符合VIP用户配置文案异常'.$e->getMessage(), Logger::ERR);
        }
        return $msg;
    }

    /**
     * 获取vip等级和名称列表
     */
    public function getVipGradeList() {
        $result = array();
        $gradesList = array_keys(VipEnum::$vipGradeNoToAlias);
        foreach ($gradesList as $grade) {
            if ($grade == VipEnum::VIP_GRADE_PT){
                continue;
            }
            $result[$grade] = VipEnum::$vipGrade[$grade]['name'];
        }
        return $result;
    }

    public function resendPointTask($resendTaskId) {
        $event = new \core\event\VipPointResendEvent($resendTaskId);
        $taskObj = new GTaskService();
        $taskId = $taskObj->doBackground($event, 10);
        PaymentApi::log("VipService.VipPointResendEvent, resendTaskId:$resendTaskId | taskId:$taskId");
        return $taskId;
    }

    /**
     * getReferUserId获取邀请人id
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-11-28
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getReferUserId($userId) {
        $inviteUserId = 0;
        // 服务码升级后，邀请人和服务人都通过统一方法获取
        $couponBindService = new CouponBindService();
        $bindInfo = $couponBindService->getByUserId($userId);
        if ($bindInfo) {
            $inviteUserId = $bindInfo['invite_user_id'];
        }
        return $inviteUserId;
    }

    /**
     * getFormatVipInfo获取格式化的vip信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-02
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function isVip($userId) {
        $vipAccountInfo = VipAccountModel::instance()->getVipAccountByUserId($userId);
        return ($vipAccountInfo && ($vipAccountInfo['service_grade']) > 0) ? true :false;
    }

    /**
     * isVip判断用户是否是vip
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-14
     * @return void
     */
    public function getFormatVipInfo($userId) {
        $result = [
            'gradeName' => '普通用户',
            'grade' => 0,
            'point' => 0,
            'imgUrl' => '',
            'expirePoint' => 0,
            'upgradePoint' => 0,
            'nextGradeName' => '',
            'isRelegated' => 0,
            'remainRelegatedTime' => 0,
            'actualGradeName' => '',
            'remainRelegatedPoint' => 0,
        ];
        $vipAccount = VipAccountModel::instance()->getVipAccountByUserId($userId);
        $vipConf = $this->getLevelVipConf();
        $minGradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[1]];
        if (empty($vipAccount)) {
            $result['upgradePoint'] = $minGradeInfo['minInvest'];
            $nextGrade = $vipConf[VipEnum::$vipGradeNoToAlias[1]];
            $result['nextGradeName'] = $nextGrade['name'];
            return $result;
        }

        $actualGradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipAccount['actual_grade']]];
        $serviceGradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipAccount['service_grade']]];

        $result['gradeName'] = $serviceGradeInfo['name'];
        $result['grade'] = $vipAccount['service_grade'];
        $result['point'] = $vipAccount['point'];
        $result['imgUrl'] = $actualGradeInfo['imgUrl'];
        //下一等级名称
        if (isset($vipConf[VipEnum::$vipGradeNoToAlias[$vipAccount['service_grade']+1]])) {
            $nextGradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$vipAccount['service_grade']+1]];
            $result['nextGradeName'] = $nextGradeInfo['name'];
            $result['upgradePoint'] = $nextGradeInfo['minInvest'] - $vipAccount['point'];
        }

        //即将过期经验值
        $vipPointLogService = new VipPointLogService();
        $soonExpirePoint = $vipPointLogService->getSoonExpirePoint($userId);
        $result['expirePoint'] = $soonExpirePoint ?: 0;

        //如果保级，获取保级信息
        if ($vipAccount['is_relegated']) {
            $result['isRelegated'] = 1;
            $result['remainRelegatedTime'] = $vipAccount['relegate_time'] + 30 * 86400 - time();
            $result['actualGradeName'] = $actualGradeInfo['name'];
            $result['remainRelegatedPoint'] = $serviceGradeInfo['minInvest'] - $vipAccount['point'];
        }
        return $result;
    }

    /**
     * getVipUserList获取用户对应vip等级信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-08
     * @param mixed $userIds
     * @access public
     * @return void
     */
    public function getVipUserList($userIds) {
        if (empty($userIds)) {
            return array();
        }
        $vipConf = $this->getLevelVipConf();
        $vipUsers = VipAccountModel::instance()->getVipUserList($userIds);
        $ptVip = array('grade' => 0, 'gradeName' => '普通用户', 'imgUrl' => '');
        foreach ($vipUsers as $user) {
            $gradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$user['service_grade']]];
            $result[$user['user_id']] = array('grade' => $user['service_grade'],'gradeName' => $gradeInfo['name'], 'imgUrl' => $gradeInfo['imgUrl']);
        }
        foreach ($userIds as $userId) {
            if (!isset($result[$userId])) {
                $result[$userId] = $ptVip;
            }
        }
        return $result;
    }
}

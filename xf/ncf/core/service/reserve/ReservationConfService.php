<?php
/**
 * 短期标预约配置服务
 *
 * @date 2016-11-15
 * @author guofeng@ucfgroup.com
 */

namespace core\service\reserve;

use core\service\BaseService;
use core\dao\reserve\ReservationConfModel;
use core\enum\ReserveConfEnum;
use core\enum\ReserveEnum;
use core\enum\DealEnum;
use core\dao\reserve\UserReservationModel;
use core\service\deal\DealTypeGradeService;
use core\service\reserve\UserReservationService;
use core\service\reserve\ReservationEntraService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\user\UserService;

class ReservationConfService extends BaseService
{

    //借款类型和期限的映射表
    public static $dealTypeMap = [];

    //预约限制金额和期限的映射表
    public static $reserveLimitAmount = [];

    /**
     * 根据预约类型，获取预约公告或配置信息
     * @param int $type 预约类型
     * @return \libs\db\model
     */
    public function getReserveInfoByType($type, $dealTypeList = [])
    {
        return ReservationConfModel::instance()->getReserveInfoByType($type, $dealTypeList);
    }

    /**
     * 检查投资期限、预约期限是否在配置范围内
     * @param int $investDeadline 投资期限
     * @param int $investDeadlineUnit 投资期限单位(1:天2:月)
     * @param int $expire 预约有效期
     * @param int $expireUnit 预约有效期单位(1:小时2:天)
     */
    public function checkValueInConfig($investDeadline, $investDeadlineUnit, $expire, $expireUnit, $reserveInfo = array(), $userId = 0, $dealType = 0)
    {
        empty($reserveInfo) && $reserveInfo = $this->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
        if (empty($reserveInfo)) {
            return array('ret'=>false, 'errorMsg' => '尚未配置预约信息');
        }
        if (empty($reserveInfo['invest_conf'])) {
            return array('ret'=>false, 'errorMsg' => '尚未配置投资期限');
        }
        if (empty($reserveInfo['reserve_conf'])) {
            return array('ret'=>false, 'errorMsg' => '尚未配置预约有效期');
        }

        $userGroupId = 0;
        if ($userId) {
            $userInfo = UserService::getUserById($userId);
            $userGroupId = isset($userInfo['group_id']) ? $userInfo['group_id'] : 0;
        }

        // 投资期限配置，检查[投资期限|投资期限单位]是否一致
        $deadline_rate = 0;
        $rateFactor = 1;
        if (!empty($reserveInfo['invest_conf'])) {
            $investRet = false;
            foreach ($reserveInfo['invest_conf'] as $item1) {
                if (intval($item1['deadline']) == intval($investDeadline) && intval($item1['deadline_unit']) == intval($investDeadlineUnit) && $item1['deal_type'] == $dealType) {
                    // 用户组不在白名单，异常
                    if (!empty($item1['visiableGroupIds']) && !in_array($userGroupId, explode(',', $item1['visiableGroupIds']))) {
                        break;
                    }
                    $investRet = true;
                    $deadline_rate = $item1['rate'];
                    $rateFactor = isset($item1['rate_factor']) ? $item1['rate_factor'] : 1;
                    break;
                }
            }
            if (!$investRet) {
                return array('ret'=>false, 'errorMsg' => '投资期限等参数不合法');
            }
        }
        // 预约期限配置，检查[预约有效期|预约有效期期限单位]是否一致
        if (!empty($reserveInfo['reserve_conf'])) {
            $reserveRet = false;
            foreach ($reserveInfo['reserve_conf'] as $item2) {
                if (intval($item2['expire']) == intval($expire) && intval($item2['expire_unit']) == intval($expireUnit)) {
                    $reserveRet = true;
                    break;
                }
            }
            if (!$reserveRet) {
                return array('ret'=>false, 'errorMsg' => '预约有效期等参数不合法');
            }
        }
        return array('ret'=>true, 'deadline_rate'=>$deadline_rate, 'rate_factor' => $rateFactor);
    }

    /**
     * 编辑预约公告信息
     * @param string $bannerUri 短期标预告图片链接
     * @param string $description 委托合同和协议或预告描述
     * @return boolean
     */
    public function editReserveNotice($bannerUri, $description, $reserveRule)
    {
        if(empty($bannerUri) || empty($description) || empty($reserveRule)) {
            return array('errorCode'=>'01', 'errorMsg'=>'bannerUri or description or reserveRule is empty');
        }
        // 根据预约类型，获取预约公告或配置信息
        $reserveInfo = $this->getReserveInfoByType(ReserveConfEnum::TYPE_NOTICE);
        if (empty($reserveInfo)) {
            $ret = ReservationConfModel::instance()->createReserveInfo(ReserveConfEnum::TYPE_NOTICE, $description, $bannerUri, 0, 0, array(), array(), $reserveRule);
        }else{
            $ret = ReservationConfModel::instance()->updateReserveInfo(ReserveConfEnum::TYPE_NOTICE, $description, $bannerUri, 0, 0, array(), array(), $reserveRule);
        }
        return array('errorCode'=>($ret ? '00' : '02'), 'errorMsg'=>($ret ? 'SUCCESS' : 'edit reservation_notice failed'));
    }

    /**
     * 编辑预约配置信息
     * @param int $minAmountCent 最低预约金额，单位为分
     * @param int $maxAmountCent 最高预约金额,单位分
     * @param int $expire 预约有效期
     * @param string $description 委托合同和协议或预告描述
     * @param array $investConf 投资期限配置
     * @param array $reserveConf 预约期限配置
     * @return boolean
     */
    public function editReserveConf($minAmountCent, $maxAmountCent, $expire, $description, $investConf = array(), $reserveConf = array())
    {
        if($minAmountCent <= 0 || empty($description)) {
            return array('errorCode'=>'01', 'errorMsg'=>'minAmount or description is empty');
        }
        // 根据预约类型，获取预约公告或配置信息
        $reserveInfo = $this->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
        if (empty($reserveInfo)) {
            $ret = ReservationConfModel::instance()->createReserveInfo(ReserveConfEnum::TYPE_CONF, $description, '', $minAmountCent, $maxAmountCent, $investConf, $reserveConf);
        }else{
            $ret = ReservationConfModel::instance()->updateReserveInfo(ReserveConfEnum::TYPE_CONF, $description, '', $minAmountCent, $maxAmountCent, $investConf, $reserveConf);
        }
        return array('errorCode'=>($ret ? '00' : '02'), 'errorMsg'=>($ret ? 'SUCCESS' : 'edit reservation_conf failed'));
    }

    /**
     * @获取系统配置中预约投资的信息
     * @param string $keyName
     * @return array
     */
    public function getReserveSysConf($keyName)
    {
        if(!$confData = app_conf('DEAL_CATEGORY_CONF')) return array();

        $tags = '|' . $keyName;
        $tagsLen = strlen($tags)+1;

        if(($offset = strpos($confData, $tags)) === false) return array();
        $offset += $tagsLen;
        $confItem = substr($confData, $offset);
        $offset   = strpos($confItem, '|');
        $resData = substr($confItem, 0, $offset);
        if(!strlen($resData)) return array();

        $infoNames = array('name','rate','desc','isIndexShow');
        $resData   = array_combine($infoNames, explode(',', $resData));

        $reserveConfInfo = ReservationConfModel::instance()->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
        $investConf = $reserveConfInfo['invest_conf'];
        $resData['num'] = sizeof($investConf) - $this->chkReserveConfGroupIds($investConf, 0);
        return $resData;
    }

    /**
     * @递归计算随心约配置中的可见组数量
     * @param array $dataList
     * @param int   $incrment
     * @return int
     */
    public function chkReserveConfGroupIds($dataList, $incrment)
    {
        if(empty($dataList[$incrment])) return 0;

        if($dataList[$incrment]['visiableGroupIds']){
            return $this->chkReserveConfGroupIds($dataList, $incrment+1)+1;
        }

        return $this->chkReserveConfGroupIds($dataList, $incrment+1);
    }

    /**
     * 获取投资期限下的所有三级产品
     */
    public function getThirdGradeByDeadLine($deadline, $deadlineUnit, $dealType, $investRate, $loantype) {
        $thirdGrade = [];
        $entraService = new ReservationEntraService();
        $entra = $entraService->getReserveEntra($deadline, $deadlineUnit, $dealType, $investRate, $loantype, -1);
        $productGradeConf = !empty($entra['product_grade_conf']) ? json_decode($entra['product_grade_conf'], true) : []; //获取三级分类
        if (empty($productGradeConf)) {
            return $thirdGrade;
        }
        if (!empty($productGradeConf['firstGradeName']) || !empty($productGradeConf['secondGradeName']) || !empty($productGradeConf['thirdGradeName'])) {
            $gradeName = array_merge($productGradeConf['firstGradeName'], $productGradeConf['secondGradeName'], $productGradeConf['thirdGradeName']);
            $thirdGrade = array_merge(
                $thirdGrade,
                DealTypeGradeService::getSubThirdGradeByNameArray($gradeName)
            );
        }
        return $thirdGrade;
    }

    /**
     * 产品风险等级
     * @param number $deadline 期限
     * @param string $thirdGradeName 产品三级名称
     * @param number $deadline_unit 期限单位
     * return bool | float
     */
    public function getScoreByDeadLine($deadline = 0, $deadline_unit = 1, $dealType = 0, $investRate = 0, $loantype = 0, $thirdGradeName = ''){

        $DealProjectRiskAssessmentService = new DealProjectRiskAssessmentService();
        if ($DealProjectRiskAssessmentService::$is_reserve_check_enable == $DealProjectRiskAssessmentService::CHECK_DISABLE){
            return true;
        }
        if($deadline == 0){
            return false;
        }

        $maxScore = false ;
        $thirdGrade = \SiteApp::init()->dataCache->call($this, 'getThirdGradeByDeadLine', [$deadline, $deadline_unit, $dealType, $investRate, $loantype], 60); //使用缓存
        foreach ($thirdGrade as $grade) {
            if(!empty($thirdGradeName) && $thirdGradeName != $grade['name']){
                continue;
            }
            if($maxScore === false){
                $maxScore = $grade['score'];
            }else{
                $maxScore = max($maxScore,$grade['score']);
            }
        }
        return $maxScore;
    }

    /**
     * 获取随心约-合同列表配置
     */
    public static function getReserveContractConfig() {
        $siteId = \libs\utils\Site::getId();
        $reserveContractStr = get_config_db('RESERVE_CONTRACT_CONF', $siteId);
        if (empty($reserveContractStr)) {
            return [];
        }

        // 解析合同列表配置
        $tmpConfig = $configData = [];
        parse_str($reserveContractStr, $tmpConfig);
        if (empty($tmpConfig)) {
            return [];
        }

        foreach ($tmpConfig as $key => $item) {
            if (empty($item)) continue;
            foreach ($item as $id => $title) {
                $configData[trim($key)][] = ['id' => (int)$id, 'title'=>trim(addslashes($title))];
            }
        }
        return $configData;
    }

    /**
     * 投资期限转换为天数
     */
    public function convertToDays($deadline, $deadlineUnit) {
        $days = 0;
        switch ($deadlineUnit) {
            case ReserveEnum::INVEST_DEADLINE_UNIT_MONTH:
                $days = $deadline * ReserveEnum::DAYS_OF_MONTH;
                break;
            case ReserveEnum::INVEST_DEADLINE_UNIT_DAY:
                $days = $deadline;
                break;
            default:
                $days = 0;
        }
        return $days;
    }

    /**
     * 获取预约限制金额 (包含预约最小值和最大值)
     * 根据贷款类型
     */
    public function getReserveLimitAmountByDealType($dealType) {
        //读取缓存
        if (isset(self::$reserveLimitAmount[$dealType])) {
            return self::$reserveLimitAmount[$dealType];
        }
        $limitAmount = [
            'min_amount' => ReserveConfEnum::RESERVE_MIN_AMOUNT_DEFAULT, //预约最低金额
            'max_amount' => ReserveConfEnum::RESERVE_MAX_AMOUNT_DEFAULT, //预约最大金额
        ];
        $reserveConf = ReservationConfModel::instance()->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);//预约配置
        if (!empty($reserveConf['amount_conf']) && is_array($reserveConf['amount_conf'])) {
            foreach ($reserveConf['amount_conf'] as $amountConf) {
                if ($dealType == $amountConf['deal_type']) {
                    $limitAmount['min_amount'] = bcdiv($amountConf['min_amount'], 100, 2);
                    $limitAmount['max_amount'] = bcdiv($amountConf['max_amount'], 100, 2);
                    break;
                }
            }
        }
        self::$reserveLimitAmount[$dealType] = $limitAmount;
        return $limitAmount;
    }

    /**
     * 获取最低预约金额
     * 根据贷款类型
     */
    public function getReserveMinAmountByDealType($dealType) {
        $limitAmount = $this->getReserveLimitAmountByDealType($dealType);
        return $limitAmount['min_amount'];
    }

    /**
     * 用于页面显示的授权金额
     */
    public function getAuthorizeAmountString($minAmount, $maxAmount) {
        $authorizeAmountString = sprintf('%s元起', number_format($minAmount));
        // 最高预约金额,单位元
        if (bccomp($maxAmount, 0, 2) === 1) {
            $authorizeAmountString .= sprintf('，最高%s元', number_format($maxAmount));
        }
        return $authorizeAmountString;
    }

    /**
     * 获取最低预约投资金额
     * 根据贷款类型
     */
    public function getReserveMinLoanMoney($dealType) {
        return $this->getReserveMinAmountByDealType($dealType);
    }


}

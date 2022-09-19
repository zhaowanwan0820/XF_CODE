<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/16
 * Time: 10:49
 */
namespace openapi\conf\adddealconf\angli;
use openapi\conf\adddealconf\AddDealBaseAction;
use openapi\conf\adddealconf\angli\AngliConf;
use libs\utils\Alarm;

class DealHandle extends AddDealBaseAction {
    /**
     * angli上标处理
     * @param $params
     * @return array|bool
     */
    function dealHandle($params) {
        $params['erroCode'] = '0';
        $params['erroMsg'] = '';
        //总开关
        if (app_conf('ANGLI_ENABLE') === '0') {
            $params['erroCode'] = '1';
            $params['erroMsg'] = "功能已停用";
            return $params;
        }
        $repayPeriod = intval($params['repayPeriod']);
        //不同借款期限的对应配置
        $angliProductConfig = AngliConf::$_ANGLI_PRODUCT_CONFIG;
        if (!array_key_exists($repayPeriod, $angliProductConfig)) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = "repayPeriod参数无效";
            return $params;
        }
        //相应借款信息对应的产品信息
        $productInfo = $angliProductConfig[$repayPeriod];
        if (empty($productInfo)) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = "repayPeriod无效";
            return $params;
        }
        $params = array_merge($params,$productInfo);
        //用款产品生效时间限制
        $angliCommonConfig = AngliConf::$_ANGLI_COMMON_CONFIG;//公共配置
        if (time() > strtotime($angliCommonConfig['invalidTime'])) {
            Alarm::push('ANGLI', 'ANGLI用款产品时间过期导致上标失败', $this->errorMsg);
            $params['erroCode'] = '1';
            $params['erroMsg'] = "该用款产品已经失效";
            return $params;
        }

        //产品限额判断
        $borrowAmount = doubleval($this->getMoney($params['borrowAmount']));
        if (!$borrowAmount) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = "borrowAmount is not double";
            return $params;
        }
        //if ($borrowAmount < $angliCommonConfig['singleUseMinMoney'] || $borrowAmount > $angliCommonConfig['singleUseMaxMoney']) {
        //上标金额为1000
        if ($borrowAmount != 1000) {
            Alarm::push('ANGLI', 'ANGLI该用款不符合产品金额限制', $borrowAmount);
            $params['erroCode'] = '1';
            $params['erroMsg'] = "该用款不符合产品金额限制";
            return $params;
        }
        $params['borrowAmount'] = $borrowAmount;
        $params['wxOpenId'] = $params['openID'];
        $params['name'] = $angliCommonConfig['productName'] . $this->getProjectIncrNo(AngliConf::REDIS_INCR_KEY);
        $params['realName'] = $params['realName'];
        //实际放款帐号开户网点、银行卡号
        $params['bankZone'] = $params['bankZone'] ? $params['bankZone'] : '';

        $params['bankId'] = 0;
        //放款方式为受托支付,则要校验开户行
        if ($angliCommonConfig['loanMoneyType'] == 3) {
            if (empty($params['bankShortName'])) {
                $params['erroCode'] = '1';
                $params['erroMsg'] = "bankShortName 不能为空";
                return $params;
            }
            //检查开户行信息(但开启行名称后面并没有用)，另开户行网点及联行号对方传的都是无此数据，所以不再校验
            $result = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT * FROM firstp2p_bank WHERE short_name='{$params['bankShortName']}' LIMIT 1");
            if (empty($result['id'])) {
                Alarm::push('ANGLI', 'ANGLI参数错误导致上标失败', $this->errorMsg . ". bankShortName:{$params['bankShortName']}");
                $params['erroCode'] = '1';
                $params['erroMsg'] = "开户行数据不符";
                return $params;
            }
            $params['bankId'] = intval($result['id']);
        }

        //信息披露
        $file = file_get_contents(APP_ROOT_PATH . 'openapi/conf/angLiDealTpl.html');//获取对应的模板
        $projectInfoUrl = $this->getDisclosureInfo(2,intval($params['repayPeriod']),1,$params,$file);
        if ($projectInfoUrl['errorCode'] != 0) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = $projectInfoUrl['errorMsg'];
            return $params;
        }
        $params['projectInfoUrl'] = $projectInfoUrl['data'];
        $params = array_merge($params,$angliCommonConfig);
        $params['rate'] = $this->getMoney($productInfo['zixunRate'] + $productInfo['danbaoRate'] + $productInfo['thirdPayRate'] + $productInfo['platformRate'] + $productInfo['profitRate']);
        $params['guaranteeFeeRate'] = $this->getMoney($productInfo['danbaoRate']);
        $params['packingRate'] = $this->getMoney($productInfo['thirdPayRate'] + $productInfo['platformRate'] + $productInfo['profitRate']);
        $params['typeId'] = $angliCommonConfig['productTypeId'] ? $angliCommonConfig['productTypeId'] : 1;
        $params['manageFeeRate'] = $this->getMoney($productInfo['platformRate']);
        $params['consultFeeRate'] = $this->getMoney($productInfo['zixunRate']);
        $params['prepayRate'] = $this->getMoney($params['prepayRate']);
        $params['annualPaymentRate'] = $this->getMoney($productInfo['thirdPayRate']);
        $params['loanMoneyType'] = $this->svStatus == 1 ? 1 : $angliCommonConfig['loanMoneyType'];
        $params['repayPeriodType'] = 1;

        return $params;
    }
}
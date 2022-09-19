<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/16
 * Time: 10:49
 */
namespace openapi\conf\adddealconf\gfd;
use openapi\conf\adddealconf\AddDealBaseAction;
use openapi\conf\adddealconf\gfd\GfdConf;
use libs\utils\Alarm;

class DealHandle extends AddDealBaseAction {
    /**
     * @param $params
     * @return array|bool
     * 功夫贷处理逻辑
     */
    function dealHandle($params) {
        $params['erroCode'] = '0';
        $params['erroMsg'] = '';
        $repayPeriod = intval($params['repayPeriod']);
        //不同借款期限的对应配置
        $ProductConfig = GFDConf::$_PRODUCT_CONFIG;
        if (!array_key_exists($repayPeriod, $ProductConfig)) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = 'repayPeriod参数无效';
            return $params;
        }
        //相应借款信息对应的产品信息
        $productInfo = $ProductConfig[$repayPeriod];
        if (empty($productInfo)) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = 'repayPeriod无效';
            return $params;
        }
        if (!is_numeric($params['consultFeeRate']) || floatval($params['consultFeeRate'] < 0)) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = 'consultFeeRate不能为空或负数';
            return $params;
        }
        $params = array_merge($params,$productInfo);
        //公共配置
        $CommonConfig = GfdConf::$_COMMON_CONFIG;
        //用款产品生效时间限制
        if (time() > strtotime($CommonConfig['invalidTime'])) {
            Alarm::push('ANGLI', 'GFD用款产品时间过期导致上标失败', $this->errorMsg);
            $params['erroCode'] = '1';
            $params['erroMsg'] = '该用款产品已经失效';
            return $params;
        }

        $borrowAmount = doubleval($this->getMoney($params['borrowAmount']));
        if (!$borrowAmount) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = 'borrowAmount is not double';
            return $params;
        }
        if ($borrowAmount < $CommonConfig['singleUseMinMoney'] || $borrowAmount > $CommonConfig['singleUseMaxMoney']) {
            Alarm::push('ANGLI', 'gfd该用款不符合产品金额限制', $borrowAmount);
            $params['erroCode'] = '1';
            $params['erroMsg'] = "该用款不符合产品金额限制";
            return $params;
        }
        $params['borrowAmount'] = $borrowAmount;
        $params['wxOpenId'] = $params['openID'];
        $params['name'] = $CommonConfig['productName'] . $this->getProjectIncrNo(GfdConf::REDIS_INCR_KEY);
        $params['realName'] = $params['realName'];
        //实际放款帐号开户网点、银行卡号
        $params['bankZone'] = $params['bankZone'] ? $params['bankZone'] : '';
        $params['bankId'] = 0;
        //信息披露
        $file = file_get_contents(APP_ROOT_PATH . 'openapi/conf/GfdDealTpl.html');//获取对应的模板
        $projectInfoUrl = $this->getDisclosureInfo(3,intval($params['repayPeriod']),2,$params,$file);
        if ($projectInfoUrl['errorCode'] != 0) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = $projectInfoUrl['errorMsg'];
            return $params;
        }
        $params['projectInfoUrl'] = $projectInfoUrl['data'];
        $params = array_merge($params,$CommonConfig);
        $params['rate'] = $this->getMoney($params['consultFeeRate'] + $productInfo['danbaoRate'] + $productInfo['thirdPayRate'] + $productInfo['platformRate'] + $productInfo['profitRate']);
        $params['guaranteeFeeRate'] = $this->getMoney($productInfo['danbaoRate']);
        $params['packingRate'] = $this->getMoney($productInfo['thirdPayRate'] + $productInfo['platformRate'] + $productInfo['profitRate']);
        $params['manageFeeRate'] = $this->getMoney($productInfo['platformRate']);
        $params['consultFeeRate'] = $this->getMoney($params['consultFeeRate']);
        $params['prepayRate'] = $this->getMoney($productInfo['prepayRate']);
        $params['annualPaymentRate'] = $this->getMoney($productInfo['thirdPayRate']);

        return $params;
    }

}
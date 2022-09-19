<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/16
 * Time: 10:49
 */
namespace openapi\conf\adddealconf\retail;
use openapi\conf\adddealconf\AddDealBaseAction;
use openapi\conf\adddealconf\retail\RetailConf;
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
        $params['extLoanType'] = isset($params['extLoanType']) ? intval($params['extLoanType']) : 0 ;
        //业务处理开始
        $borrow_amount = doubleval($params['borrowAmount']);
        if (!$borrow_amount) {
            $params['erroCode'] = '1';
            $params['erroMsg'] = "borrowAmount is not double";
            return $params;
        }
        $params['prepayDaysLimit'] = intval($params['prepayDaysLimit']);
        $params['repayPeriod'] = intval($params['repayPeriod']);
        $params['prepayPenaltyDays'] = intval($params['prepayPenaltyDays']);
        $params['overdueDay'] = intval($params['overdueDay']);
        $params['loanType']  = intval($params['loanType']);
        $params['profitRate'] = floatval($params['rateYields']);
        $params['typeId']   = ($params['typeId'] == 'null') ? 1 : $params['typeId'];
        $params['lineSiteId']   = (int)$params['lineSiteId'];
        $params['leasingContractTitle']   = (string)$params['leasingContractTitle'];
        $params['overdueBreakDays']   = (int)$params['overdueBreakDays'];
        $params['lineSiteName'] = ($params['lineSiteName'] == 'null' || $params['lineSiteName'] == null) ? '' : (string)$params['lineSiteName'];
        $params['guaranteeFeeRateType'] = ($params['guaranteeFeeRateType'] == 'null' || $params['guaranteeFeeRateType'] == null) ? 1 : (int)$params['guaranteeFeeRateType'];
        $params['loanFeeRateType'] = ($params['loanFeeRateType'] == 'null' || $params['loanFeeRateType'] == null) ? 1 : (int)$params['loanFeeRateType'];
        $params['loanApplicationType'] = ($params['loanApplicationType'] == 'null' || $params['loanApplicationType'] == null) ? '' : (string)$params['loanApplicationType'];
        $params['consultFeeRateType'] = ($params['consultFeeRateType'] == 'null' || $params['consultFeeRateType'] == null) ? 1 : (int)$params['consultFeeRateType'];
        $params['contractTransferType'] = ($params['contractTransferType'] == 'null' || $params['contractTransferType'] == null) ? 0 : (int)$params['contractTransferType'];
        $params['payFeeRateType'] = ($params['payFeeRateType'] == 'null' || $params['payFeeRateType'] == null) ? 1 : (int)$params['payFeeRateType'];
        $params['repayPeriodType'] = ($params['repayPeriodType'] == 'null' || $params['repayPeriodType'] == null) ? 1 : (int)$params['repayPeriodType'];
        $params['annualPaymentRate'] = ($params['annualPaymentRate'] == 'null' || $params['annualPaymentRate'] == null) ? 0.000000 : $params['annualPaymentRate'];
        $params['guaranteeFeeRate'] = isset($params['guaranteeFeeRate']) ? $params['guaranteeFeeRate'] : 0.000000;
        $params['packingRate']       = isset($params['packingRate']) ? $params['packingRate'] : 0.000000;
        $params['consultFeeRate']   = isset($params['consultFeeRate']) ? $params['consultFeeRate'] : 0.000000;
        $params['projectInfoUrl']   = isset($params['projectInfoUrl']) ? base64_decode(urldecode(str_replace('!_!', '%',$params['projectInfoUrl']))) : '';
        $params['baseContractRepayTime'] = !empty($_REQUEST['baseContractRepayTime']) ? strtotime($_REQUEST['baseContractRepayTime']): 0;
        $params['lesseeRealName']     = !empty($_REQUEST['lesseeRealName']) ? $_REQUEST['lesseeRealName'] : '';
        $params['leasingContractNum'] = isset($_REQUEST['leasingContractNum']) ? $_REQUEST['leasingContractNum'] : '';
        $params['leasingMoney']        = isset($_REQUEST['leasingMoney']) ? $_REQUEST['leasingMoney'] : 0.00;
        $params['entrustedLoanBorrowContractNum']    = (empty($_REQUEST['entrustedLoanBorrowContractNum']) || $_REQUEST['entrustedLoanBorrowContractNum'] == 'null') ? '' : $_REQUEST['entrustedLoanBorrowContractNum'];
        $params['entrustedLoanEntrustedContractNum'] = (empty($_REQUEST['entrustedLoanEntrustedContractNum']) || $_REQUEST['entrustedLoanEntrustedContractNum'] == 'null') ? '' :$_REQUEST['entrustedLoanEntrustedContractNum'];
        $params['contractTplType']    = !empty($_REQUEST['contractTplType']) ? $_REQUEST['contractTplType'] : '';
        $params['loanMoneyType']      = !empty($_REQUEST['loanMoneyType']) ? $_REQUEST['loanMoneyType'] : 0;
        $params['cardName']            = !empty($_REQUEST['cardName']) ? $_REQUEST['cardName'] : '';
        $params['bankZone']             = !empty($_REQUEST['bankZone']) ? $_REQUEST['bankZone'] : '';
        $params['bankId']               = !empty($_REQUEST['bankId']) ? $_REQUEST['bankId'] : 0;
        $params['bankCard']             = !empty($_REQUEST['bankCard']) ? $_REQUEST['bankCard'] : '';
        $params['entrustSign']         = !empty($_REQUEST['entrustSign']) ? $_REQUEST['entrustSign'] : 0;
        $params['fixedReplay']         = !empty($_REQUEST['fixedReplay']) ? to_timespan((date('Y-m-d',(int)$_REQUEST['fixedReplay']) . ' 00:00:00')) : 0;
        $params['advanceAgencyId']    = !empty($_REQUEST['advanceAgencyId']) ? (int)$_REQUEST['advanceAgencyId'] : 0;
        $params['entrustAgencySign']  = !empty($_REQUEST['entrustAgencySign']) ? (int)$_REQUEST['entrustAgencySign'] : 0;
        $params['entrustAdvisorySign'] = !empty($_REQUEST['entrustAdvisorySign']) ? (int)$_REQUEST['entrustAdvisorySign'] : 0;
        $params['warrant']               = isset($_REQUEST['warrant']) ? (int)$_REQUEST['warrant'] : 2;
        $params['productClass']         = !empty($_REQUEST['productClass']) ? $_REQUEST['productClass'] : '消费贷';
        $params['productName']          = !empty($params['productName']) ? $params['productName'] : '';
        $params['entrustAgencyId']     = !empty($_REQUEST['entrustAgencyId']) ? $_REQUEST['entrustAgencyId'] : 0;
        $params['agencyId']     = !empty($_REQUEST['agencyId']) ? $_REQUEST['agencyId'] : 0;
        $params['consultFeePeriodRate'] = isset($params['consultFeePeriodRate']) ? $params['consultFeePeriodRate'] : 0.00000000;
        $params['loanUserCustomerType']     = !empty($_REQUEST['loanUserCustomerType']) ? $_REQUEST['loanUserCustomerType'] : 1;

        //受托支付校验
        if ($params['loanMoneyType'] == 3) {
            if (empty($params['loanBankCard']) || empty($params['bankShortName'])) {
                $params['erroCode'] = '1';
                $params['erroMsg'] = "卡号和银行简码不能为空";
                return $params;
            }
            $bankDao = new \core\dao\BankModel();
            $bankInfo = $bankDao->getBankByCode($params['bankShortName']);
            if (empty($bankInfo['id'])) {
                $params['erroCode'] = '1';
                $params['erroMsg'] = "银行简码不正确";
                return $params;
            }
            $params['bankId'] = $bankInfo['id'];
            if ($params['cardType'] == 1 ) {
                if (empty($params['bankNum'])) {
                    $params['erroCode'] = '1';
                    $params['erroMsg'] = "企业账号联行号不能为空";
                    return $params;
                }
                $banklistDao = new \core\dao\BanklistModel();
                $banklistInfo = $banklistDao->getBankInfoByBankId($params['bankNum']);
                if (empty($banklistInfo)) {
                    $params['erroCode'] = '1';
                    $params['erroMsg'] = "联行号不存在";
                    return $params;
                }
                $params['bankZone'] = $banklistInfo['name'];
            }

        }
        return $params;
    }
}

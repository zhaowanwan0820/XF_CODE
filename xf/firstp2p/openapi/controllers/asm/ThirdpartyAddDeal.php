<?php

/**
 * ThirdpartyAddDeal.php
 *
 * Filename: ThirdpartyAddDeal.php
 * Descrition: 首山昂励上标接口
 * Author: yutao@ucfgroup.com
 * Date: 17-3-4 下午5:02
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;
use NCFGroup\Protos\Contract\RequestGetContractByApproveNumber;
use core\dao\DealLoanTypeModel;
use core\service\DealLoanTypeService;
use libs\utils\Logger;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\rpc\Rpc;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\payment\supervision\Supervision;
use openapi\conf\adddealconf\common\CommonConf;
use openapi\conf\adddealconf\GetConf;
use core\service\UserBankcardService;

class ThirdpartyAddDeal extends BaseAction {

    public function init() {
        // fpm重试请求会导致全局变量$_POST丢失，尝试从原始数据流中读取
        if (empty($_POST)) {
            parse_str(file_get_contents('php://input'), $post);
            if ($_POST = $post) {
                $_REQUEST = array_merge($post, $_REQUEST);
            }
        }
        parent::init();
        $this->form = new Form();
        $privateParams = $this->getPrivateParams();
        if ($privateParams === false) {
            $this->setErr("ERR_PARAMS_ERROR", 'clientID-非法');
            return false;
        }
        $this->form->rules = array_merge(CommonConf::$_RULES,$privateParams);
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        // 此接口已关闭
        $this->setErr("ERR_SYSTEM_ACTION_CLOSE");
        return false;

        if(Supervision::isServiceDown()){ //存管降级不能上标
            $this->setErr('ERR_SYSTEM_MAINTENANCE');
            return false;
        }
        $params = $this->form->data;
        //根据client_id获取相应的平台
        $params['from_platform'] = array_search($params['client_id'],CommonConf::getAllowCliectId());
        if (empty($params['from_platform'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'clientID非法');
            return false;
        }
        $conf = new GetConf();
        $dealHandle = $conf->getConf('DealHandle\dealHandle',array(args => $params),$params['from_platform']);
        if ($dealHandle['erroCode'] != 0) {
            $this->setErr("ERR_PARAMS_ERROR", $dealHandle['erroMsg']);
            return false;
        }
        $params = array_merge($params,$dealHandle);
        //校验用户
        $params['user_types'] = 2;
        $cavedBindcard = $params['cavedBindcard'];
        $params['real_name'] = $params['realName'];
        $params['deal_type'] = 0;
        $userId = $this->checkCreditUser($params, $cavedBindcard);
        if ($userId < 0) {
            switch ($userId) {
                case -3 :
                    $this->errorCode = 4;
                    $this->errorMsg  = '该用户存管未开户';
                    break;
                case -5 :
                    $this->errorCode = 4;
                    $this->errorMsg  = '该存管户非借款户';
                    break;
                case -1 :
                    $this->errorCode = 2;
                    $this->errorMsg  = '该用户不存在';
                    break;
                case -4 :
                    $this->errorCode = 5;
                    $this->errorMsg = ($params['user_types'] == 2) ? '个人用户在途借款本金不得大于20万元' : '企业用户在途借款本金不得大于100万元';;
                    break;
                case -6:
                    $this->errorCode = 5;
                    $this->errorMsg  = ($params['user_types'] == 2) ? '个人用户跨平台借款本金不得大于100万元' : '企业用户跨平台借款本金不得大于500万元';
                    break;
                default:
                    $this->errorCode = 3;
                    $this->errorMsg  = '该用户未绑定银行卡';
            }

            Alarm::push('ANGLI', $params['from_platform'].'参数错误导致上标失败', $this->errorMsg . ". userId:{$userId}");
            return false;
        }
        //借款户
        $userArr = ['userId' => $userId];
        //咨询户
        //$this->setAgencyUid('advisoryId', $params['advisoryId'], $userArr);
        //担保户
        $this->setAgencyUid('agencyId', $params['agencyId'], $userArr);
        //代垫/代偿
        $this->setAgencyUid('advanceAgencyId', $params['advanceAgencyId'], $userArr);
        //代充值
        $params['generationRechargeId'] = isset($params['generationRechargeId']) ? $params['generationRechargeId'] : 0;
        $this->setAgencyUid('generationRechargeId', $params['generationRechargeId'], $userArr);

        $checkAuth = $this->checkBorrowAuth($params['typeId'], $userArr);
        if ($checkAuth['code'] !== 0) {
            $this->errorCode = 4;
            $this->errorMsg  = $checkAuth['msg'];
            return false;
        }

        $userBankInfo = (new UserBankcardService())->getBankcard($userId);
        if (!empty($params['bankCard']) && ($params['bankCard'] != $userBankInfo['bankcard'])) {
            $this->errorCode = 4;
            $this->errorMsg = '银行卡与用户绑定银行卡不一致';
            return false;
        }
        //产品结构化校验产品名称是否存在有效
        if (empty($params['productName'])) {
            $this->setErr("ERR_PARAMS_ERROR", "product_name不能为空");
            return false;
        }

        $params['productName'] = addslashes(trim($params['productName']));
        $params['productClass'] = addslashes(trim($params['productClass']));
        $params['clearingType'] = isset($params['clearingType']) ? $params['clearingType'] : 0;
        $ProductNameCheck = $this->rpc->local('DealTypeGradeService\getAllLevelByName', array($params['productClass'], $params['productName']));

        if (empty($ProductNameCheck['level2']) || empty($ProductNameCheck['level3'])) {
            $this->setErr("ERR_PARAMS_ERROR", "二级分类({$params['productClass']})和三级分类({$params['productName']})无效");
            return false;
        }
        //二级分类id
        $productClassType = $ProductNameCheck['id2'];

        $riskBearing = $this->rpc->local('DealProjectRiskAssessmentService\getByScoreAssesment', array($ProductNameCheck['score']));
        if (!$riskBearing) {
            $riskBearing = array('id' => 0);
        }
        $params['fixedReplay'] = isset($params['fixedReplay']) ? $params['fixedReplay'] : 0;
        $params['consultFeePeriodRate']   = isset($params['consultFeePeriodRate']) ? $params['consultFeePeriodRate'] : 0.00000000;

        /*
         * 前置合同
         * 校验借款人合同委托签署状态
         * 借款人合同委托签署状态为“已委托”，则根据放款审批单号判断是否存在记录。
         */
        $typeModel = new \core\dao\DealLoanTypeModel();
        $typeTag = $typeModel->getLoanTagByTypeId($params['typeId']);
        $whiteList = explode(',',str_replace('，',',',app_conf('CONTRACT_SIGN_VERIFY_TYPE_TAG')));
        //配置中的类型验证
        if (in_array($typeTag, $whiteList)) {
            if ($params['entrustSign'] == 1) {
                $rpc = new \libs\utils\Rpc('contractRpc');
                if (!$rpc) {
                    return $this->setErr('ERR_SYSTEM');
                }
                $contractRequest = new RequestGetContractByApproveNumber();
                $contractRequest->setApproveNumber($params['relativeSerialno']);
                $contractResponse = $rpc->go('NCFGroup\Contract\Services\ContractBeforeBorrow','getContractByApproveNumber',$contractRequest);

                //不存在 返回错误提示
                if ($contractResponse['errCode'] != 0 || $contractResponse['data']['borrowerSignTime'] <= 0) {
                    $this->errorCode = -10;
                    $this->errorMsg = '该标的未签署前置协议';
                    return false;
                }
                //合同类型
                if ($contractResponse['data']['categoryId'] != $params['contractTplType']) {
                    $this->errorCode = -11;
                    $this->errorMsg = '合同类型不一致';
                    return false;
                }
            }
        }

        //上标业务处理
        $request = new \NCFGroup\Protos\Ptp\ProtoProjectDeal();
        try {
            $request->setApproveNumber($params['relativeSerialno']);
            $request->setUserId((int) $userId);
            $request->setBorrowAmount($params['borrowAmount']);
            $request->setProjectBorrowAmout($params['borrowAmount']);
            $request->setCredit($params['borrowAmount']);
            $request->setLoanType($params['loanType']);
            $request->setName($params['name']);
            $request->setProjectName($params['name']);
            $request->setRate($params['rate']);
            $request->setGuaranteeFeeRate($params['guaranteeFeeRate']);
            $request->setPackingRate($params['packingRate']);
            $request->setRepayReriod($params['repayPeriod']);
            $request->setRepayPeriodType($params['repayPeriodType']); //1代表P2P侧的天 2代表月
            $request->setProjectInfoUrl($params['projectInfoUrl']);
            $request->setDealType(0);
            $request->setLockPeriod(0);
            $request->setRedemptionPeriod(0);
            $request->setAdvisoryId($params['advisoryId']);
            $request->setAgencyId($params['agencyId']);
            $request->setTypeId($params['typeId']);
            $request->setManageFeeRate($params['manageFeeRate']);
            $request->setConsultFeeRate($params['consultFeeRate']);
            $request->setPrepayRate($params['prepayRate']);
            $request->setPrepayPenaltyDays($params['prepayPenaltyDays']);
            $request->setPrepayDaysLimit($params['prepayDaysLimit']);
            $request->setOverdueRate($params['overdueRate']);
            $request->setOverdueDay($params['overdueDay']);
            $request->setContractTplType($params['contractTplType']);
            $request->setLeasingContractNum($params['leasingContractNum']);
            $request->setLesseeRealName($params['lesseeRealName']);
            $request->setLeasingMoney($params['leasingMoney']);
            $request->setEntrustedLoanEntrustedContractNum($params['entrustedLoanEntrustedContractNum']);
            $request->setEntrustedLoanBorrowContractNum($params['entrustedLoanBorrowContractNum']);
            $request->setBaseContractRepayTime($params['baseContractRepayTime']);
            $request->setAnnualPaymentRate($params['annualPaymentRate']);
            $request->setLineSiteId($params['lineSiteId']);
            $request->setLineSiteName($params['lineSiteName']);
            $request->setOverdueBreakDays($params['overdueBreakDays']);
            $request->setLoanFeeRateType($params['loanFeeRateType']);
            $request->setConsultFeeRateType($params['consultFeeRateType']);
            $request->setGuaranteeFeeRateType($params['guaranteeFeeRateType']);
            $request->setPayFeeRateType($params['payFeeRateType']);
            $request->setLeasingContractTitle($params['leasingContractTitle']);
            $request->setContractTransferType($params['contractTransferType']);
            $request->setLoanApplicationType($params['loanApplicationType']);
            $request->setRateYields($params['profitRate']);
            $request->setLoanMoneyType($params['loanMoneyType']);
            if ($params['loanMoneyType'] == 3) {
                $params['bankCard'] = $params['loanBankCard'];
                $request->setCardType(intval($params['cardType']));
            }
            $request->setBankCard($params['bankCard']);
            $request->setCardName($params['cardName']);
            $request->setBankZone($params['bankZone']);
            $request->setBankId($params['bankId']);
            $request->setEntrustSign($params['entrustSign']);
            $request->setFixedReplay($params['fixedReplay']);
            $request->setAdvanceAgencyId($params['advanceAgencyId']);
            $request->setEntrustAgencySign($params['entrustAgencySign']);
            $request->setEntrustAdvisorySign($params['entrustAdvisorySign']);
            $request->setWarrant($params['warrant']);
            $request->setIsCredit(1);
            $request->setProductClass($params['productClass']);
            $request->setProductName($params['productName']);
            $request->setDealTagName('');
            $request->setDealTagDesc('');
            $request->setMinLoanMoney(100);
            $request->setMaxLoanMoney(0);
            $request->setBusinessLines('mulandaicn');
            $request->setIsEffect(1);
            $request->setEntrustAgencyId($params['entrustAgencyId']);
            $request->setGenerationRechargeId($params['generationRechargeId']);
            $request->setRiskBearing(intval($riskBearing['id']));
            $request->setProductMix1($ProductNameCheck['level1']);
            $request->setProductMix2($ProductNameCheck['level2']);
            $request->setProductMix3($ProductNameCheck['level3']);
            $request->setClearingType($params['clearingType']);
            if (!empty($params['chnAgencyId'])) {
                $request->setCanalAgencyId($params['chnAgencyId']);
                $request->setCanalFeeRate($params['chnFeeRate']);
                $request->setCanalFeeRateType($params['chnFeeRateType']);
            }
            $request->setLoanUserCustomerType($params['loanUserCustomerType']);
            $request->setProductClassType($productClassType);
            $request->setConsultFeePeriodRate($params['consultFeePeriodRate']);
        } catch (\Exception $e) {
            $this->errorCode = -100;
            $this->errorMsg = "上标失败";
            Alarm::push('ANGLI', $params['from_platform'].'PtpBackend参数错误导致上标失败', $this->errorMsg . '. msg:' . $e->getMessage());
            Logger::error('ANGLI', $params['from_platform'].'PtpBackend参数错误导致上标失败', $this->errorMsg .  '. msg:' . $e->getMessage());
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpProjectDeal',
            'method' => 'addProjectDeal',
            'args' => $request
        ));

        $codeMsgList  = array('-1'=>'Project already exists',
                              '-2'=>'Project name already exists',
                              '1'=>'insert dealProject failed',
                              '-3'=>'该咨询机构的上标金额已超出平台限额，不能上标',
                              '-4'=>'该产品的上标金额已超出平台限额，不能上标',
                              '-5'=>'不在咨询机构的有效期内，不能上标',
                              '-6'=>'不在产品限额有效期内，不能上标',
                              '-8'=>'业务正在处理中',
                              '-9'=>'用户在途借款本金不得大于20万元'
        );
        $code = $response->resCode;
        if (in_array($code, array_keys($codeMsgList))) {
            $this->errorCode = $code;
            $this->errorMsg = $codeMsgList[$code];
            Alarm::push('ANGLI', $params['from_platform'].'PtpBackend返回异常导致上标失败', $this->errorMsg . ', approve_number:' . $params['relativeSerialno']);
            Logger::error('ANGLI', $params['from_platform'].'PtpBackend返回异常导致上标失败', $this->errorMsg . ',  params:'  . json_encode($params));
            return false;
        }

        $this->errorCode = 0;
        $this->errorMsg = ($code == -7) ?  '该项目标的已经存在' : 'ok' ;
        $this->json_data = $response->dealId;

        Monitor::add('ANGLI_ADDDEALl_SUCCESS');
        Logger::info('ANGLI_ADDDEALl_SUCCESS. params:' . json_encode($params));
    }

    /**
     * 获取平台的私有传参
     * @return array|bool|mixed
     */
    public function getPrivateParams() {
        $clientId = $_REQUEST['client_id'];
        $fromPlatform = array_search($clientId,CommonConf::getAllowCliectId());
        if (empty($fromPlatform)) return false;
        $conf = new GetConf();
        $privateParams = $conf->getConf(ucfirst($fromPlatform).'Conf\getPrivateParams',array(),$fromPlatform);
        $res = isset($privateParams) ? $privateParams : array();
        return $res;
    }

    private function setAgencyUid($k, $agencyId, &$userArr) {
        if (!empty($agencyId)){
            $agencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyId);
            $userId = $agencyInfo['user_id'];
            if ($userId) {
                $userArr[$k] = $userId;
            }
        }
    }

}

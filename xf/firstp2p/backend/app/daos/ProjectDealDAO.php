<?php

namespace NCFGroup\Ptp\daos;

use core\service\CouponDealService;
use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Ptp\models\Firstp2pDealProject;

use NCFGroup\Ptp\models\Firstp2pDealProjectCompound;
use NCFGroup\Ptp\models\Firstp2pDealCompound;
use NCFGroup\Ptp\models\Firstp2pDealExt;
use NCFGroup\Ptp\models\Firstp2pDeal;
use NCFGroup\Ptp\models\Firstp2pCouponDeal;
use libs\utils\Logger;
use core\dao\DealAgencyModel;
use core\dao\DealLoanTypeModel;
use core\service\DealService;

use NCFGroup\Protos\Contract\RequestSetDealCId;
use NCFGroup\Protos\Contract\RequestUpdateDealCId;
use NCFGroup\Protos\Contract\RequestSaveContractAttachment;
use NCFGroup\Protos\Contract\RequestSetProjectDescription;
use libs\utils\Rpc;
use core\dao\DealModel;
use core\dao\JobsModel;
use core\dao\DealQueueModel;
use core\service\ProductManagementService;
use core\service\PlatformManagementService;
use NCFGroup\Ptp\daos\JobsDAO;


/**
 * ProjectDealDAO
 * @package default
 */
class ProjectDealDAO
{
    public static $paramsConf = array(
        'ext' => array('leasing_contract_num' => 'leasingContractNum',
                         'lessee_real_name' => 'lesseeRealName',
                         'leasing_money' => 'leasingMoney',
                         'entrusted_loan_entrusted_contract_num' => 'entrustedLoanEntrustedContractNum',
                         'entrusted_loan_borrow_contract_num' => 'entrustedLoanBorrowContractNum',
                         'base_contract_repay_time' => 'baseContractRepayTime',
                         'loan_fee_rate_type' => 'loanFeeRateType',
                         'consult_fee_rate_type' => 'consultFeeRateType',
                         'guarantee_fee_rate_type' => 'guaranteeFeeRateType',
                         'leasing_contract_title' => 'leasingContractTitle',
                         'contract_transfer_type' => 'contractTransferType',
                         'loan_application_type' => 'useInfo',
                          'ext_loan_type' => 'loanType'),
        'deal'=>array( 'borrow_amount' => 'borrowAmount',
                         'loan_type' => 'loantype',
                         'repay_period' => 'repayTime',
                         'min_loan_money' => 'minLoanMoney',
                         'prepay_days_limit' => 'prepayDaysLimit',
                         'contract_tpl_type' => 'contractTplType',
                         'jys_record_number' => 'jysRecordNumber',
                         'rate_yields' => 'incomeFeeRate',
                         'guarantee_fee_rate' => 'guaranteeFeeRate',
                         'consult_fee_rate' => 'consultFeeRate',
                         'manage_fee_rate' => 'loanFeeRate'),
        'project'=>array( 'card_name' => 'cardName',
                            'bankzone' => 'bankzone',
                            'bankid' => 'bankId',
                            'bankcard' => 'bankcard',
                            'assets_desc' => 'assetsDesc',
                            'clearingType' => 'clearingType',
                            'jys_record_number' => 'jysRecordNumber',
                            'loan_type' => 'loantype',
                            'loan_money_type' => 'loanMoneyType',
                            'repay_period' => 'repayTime',
                            'borrow_amount' => 'borrowAmount',
                            'rate' => 'rate',
                            'repay_period' => 'repayTime',
                            'project_info_url' => 'intro')
    );
    public static function addProjectDealInfo($request)
    {
        $res = false;
        $is_effect   = false;
        $deal_type = $request->getDealType();
        try {
            $dealObj = new Firstp2pDeal();
            $projectId = $request->getProjectId();
            if(empty($projectId)){
                $projectObj = new Firstp2pDealProject();
                $db = $projectObj->getDI()->get('firstp2p');
                $db->begin();
                $projectId = self::addProjectInfo($projectObj, $request, $deal_type);
            }else{
                $db = $dealObj->getDI()->get('firstp2p');
                $db->begin();
                $projectId = $request->getProjectId();
            }

            //消费分期、掌众、首山的标的默认为不显示
            $params_list = array(DealLoanTypeModel::TYPE_ZHANGZHONG, DealLoanTypeModel::TYPE_XFFQ, DealLoanTypeModel::TYPE_XFD, DealLoanTypeModel::TYPE_XSJK);
            for($i=1; $i<3; $i++){
                if($request->getTypeId() == DealLoanTypeModel::instance()->getIdByTag($params_list[$i])){
                    $is_effect = true;
                    break;
                }
            }

            if($request->getTypeId() == DealLoanTypeModel::instance()->getIdByTag($params_list[0])){
                $is_effect = true;
                $is_coupay_pay_type = false; //掌众的优惠码只与LoanFeeRateType有关，不和产品类别有关
            }

            //更新机构和产品预警信息
            if ($request->getAdvisoryWarningUseMoney() > 0) {
                self::updatePlatformInfo($request->getAdvisoryWarningUseMoney(), $request->getAdvisoryId(), $request->getAdvisoryWarningLevel());
            }
            if ($request->getProductWarningUseMoney() > 0) {
                self::updateProductInfo($request->getProductWarningUseMoney(), $request->getProductName(), $request->getProductWarningLevel());
            }
            //标的信息
            $deal_id = self::addDealInfo($dealObj, $deal_type, $projectId, $request, $is_effect);

            //标的附件信息
            self::addDealExt($deal_id, $request);

            //优惠码、通知贷
            self::addCreditCoupon($deal_id, $projectId, $deal_type, $request, $is_coupay_pay_type);

            //调用合同服务
            self::callContractService($deal_id, $request->getContractTplType(), $request->getAttachInfo(), $request->getIsCredit(), $deal_type, $projectId, $request->getEntrustInvestmentDesc());

            $site_id = !empty($GLOBALS['sys_config']['TEMPLATE_LIST'][$request->getBusinessLines()]) ? $GLOBALS['sys_config']['TEMPLATE_LIST'][$request->getBusinessLines()] : 1;
            \FP::import("app.deal");

            //上标队列、优惠码返利配置处理
            //$jobs_model = new JobsModel();
            $function = '\core\service\DealService::initDeal';
            $param = array($deal_id, $request->getIsCredit());
            $priority = JobsModel::PRIORITY_XD_BID_SUCCESS;
            //$r = $jobs_model->addJob($function, $param);
            //由于不是一个db连接，事物回滚时添加的job不能回滚，现在把添加job放到一个事物里
            $r = JobsDAO::addJobs($function, $param,$priority);
            if($r === false){
                throw new \Exception("insert init deal jobs fail");
            }

            if($deal_site_statu = update_deal_site($deal_id, array($site_id), true) == true){
                if(!$db->commit()){
                    throw new \Exception('提交事务失败');
                }
                $res = true;
            }
        } catch (\Exception $ex) {
            $msg_title = ($deal_type == 1) ? 'openapi addProjectInfoCompound && deal errors' : 'openapi addProjectInfo && addDeal errors';
            \libs\utils\Logger::error($msg_title . '|' . $ex->getMessage());
            $db->rollback();
        }

        if(!$res) return false;

        //(new DealService())->initDeal($deal_id, $request->getIsCredit());
        return array('deal_id' => $deal_id, 'project_id' => $projectId);
    }

    /**
     * @添加项目信息
     * @param object $projectObj
     * @param object $request
     * @return bool
     */
    private static function addProjectInfo($projectObj, $request, $deal_type)
    {
        $projectObj->name          = $request->getProjectName();
        $projectObj->userId        = $request->getUserId();
        $projectObj->approveNumber = $request->getApproveNumber();
        $projectObj->borrowAmount  = $request->getProjectBorrowAmout();
        $projectObj->credit        = $request->getCredit();
        $projectObj->loantype      = $request->getLoanType();
        $projectObj->rate          = $request->getRate();
        $projectObj->repayTime     = $request->getRepayReriod();
        $projectObj->intro         = $request->getProjectInfoUrl();
        $projectObj->createTime    = get_gmtime();
        $projectObj->cardName      = $request->getCardName();
        $projectObj->bankcard      = $request->getBankCard();
        $projectObj->bankzone      = $request->getBankZone();
        $projectObj->bankId        = $request->getBankId();
        $projectObj->loanMoneyType = $request->getLoanMoneyType();
        $projectObj->borrowFeeType = $request->getLoanFeeRateType();
        $projectObj->entrustSign   = $request->getEntrustSign();
        $projectObj->status        = 0;
        $projectObj->dealType      = $deal_type;
        $projectObj->entrustAgencySign = $request->getEntrustAgencySign();
        $projectObj->entrustAdvisorySign = $request->getEntrustAdvisorySign();
        $projectObj->productClass = $request->getProductClass();
        $projectObj->productName = $request->getProductName();
        $projectObj->fixedValueDate = $request->getFixedValueDate();
        $projectObj->cardType = $request->getcardType();
        $projectObj->riskBearing = $request->getRiskBearing();
        $projectObj->productMix1 = $request->getProductMix1();
        $projectObj->productMix2 = $request->getProductMix2();
        $projectObj->productMix3 = $request->getProductMix3();
        $projectObj->assetsDesc = $request->getAssetsDesc();
        $projectObj->clearingType = $request->getClearingType();
        $projectObj->save();

        return (int)$projectObj->id;
    }

    /**
     * @添加标的信息
     * @param object $dealObj
     * @param int $deal_type
     * @param int $project_id
     * @param object $request
     * @return bool
     */
    private static function addDealInfo($dealObj, $deal_type, $project_id, $request, $is_effect)
    {
        // 借款类别
        $typeId = $request->getTypeId();
        // 标的状态(0:等待材料1:进行中2:满标3:流标4:还款中5:已还清6:预约投标中)
        $dealStatus = 0;

        // 分站id
        $site_id = !empty($GLOBALS['sys_config']['TEMPLATE_LIST'][$request->getBusinessLines()]) ? $GLOBALS['sys_config']['TEMPLATE_LIST'][$request->getBusinessLines()] : 1;
        $dealObj->siteId = $site_id;

        $dealObj->agencyId      = $request->getAgencyId();
        $dealObj->payAgencyId   = (new DealAgencyModel())->getUcfPayAgencyId();
        $dealObj->dealType      = $deal_type;
        $dealObj->name          = $request->getName();
        $dealObj->consultFeeRate    = $request->getConsultFeeRate();
        $dealObj->packingRate       = $request->getPackingRate();
        $dealObj->guaranteeFeeRate  = $request->getGuaranteeFeeRate();
        $dealObj->advanceAgencyId   = $request->getAdvanceAgencyId();
        $dealObj->dealTagName       = $request->getDealTagName();
        $dealObj->dealTagDesc       = $request->getDealTagDesc();
        $dealObj->entrustAgencyId   = $request->getEntrustAgencyId();
        $dealObj->subName = '';
        $dealObj->cateId  = 3;
        $dealObj->manager = '';
        $dealObj->managerMobile = '';
        $dealObj->description  = '';
        $dealObj->isEffect = ($is_effect) ? 0 : $request->getIsEffect();
        $dealObj->isDelete = 0;
        $dealObj->sort = 1;
        $dealObj->iconType=1;
        $dealObj->icon = '';
        $dealObj->seoTitle = '';
        $dealObj->seoKeyword = '';
        $dealObj->seoDescription = '';
        $dealObj->nameMatch = '';
        $dealObj->nameMatchRow = '';
        $dealObj->dealCateMatch = '';
        $dealObj->dealCateMatchRow = '';
        $dealObj->tagMatch = '';
        $dealObj->tagMatchRow = '';
        $dealObj->typeMatch = '';
        $dealObj->typeMatchRow = '';
        $dealObj->isRecommend = 0;
        $dealObj->buyCount  = 0;
        $dealObj->loadMoney  = 0;
        $dealObj->repayMoney = 0;
        $dealObj->startTime = 0;
        $dealObj->successTime = 0;
        $dealObj->repayStartTime = 0;
        $dealObj->lastRepayTime = 0;
        $dealObj->nextRepayTime = 0;
        $dealObj->badTime = 0;
        $dealObj->dealStatus = $dealStatus;
        $dealObj->enddate = 7;
        $dealObj->voffice = 0;
        $dealObj->vposition = 0;
        $dealObj->servicesFee = 0;
        $dealObj->publishWait = 0;
        $dealObj->isSendBadMsg = 0;
        $dealObj->badMsg = '';
        $dealObj->sendHalfMsgTime = 0;
        $dealObj->sendThreeMsgTime = 0;
        $dealObj->isHasLoans = 0;
        $dealObj->loantype = $request->getLoanType();
        $dealObj->warrant = $request->getWarrant();
        $dealObj->minLoanMoney = $request->getMinLoanMoney();
        $dealObj->maxLoanMoney = $request->getMaxLoanMoney();
        $dealObj->updateJson = '';
        $dealObj->manageFeeText = '';
        $dealObj->note = '';
        $dealObj->couponType = 1;
        $dealObj->approveNumber = $request->getApproveNumber();
        $dealObj->payFeeRate = $request->getAnnualPaymentRate();
        $dealObj->isHot = 0;
        $dealObj->isNew = 0;
        $dealObj->borrowAmount = $request->getBorrowAmount();
        $dealObj->repayTime = $request->getRepayReriod();
        $dealObj->advisoryId = $request->getAdvisoryId();
        $dealObj->day = $request->getOverdueDay();
        $dealObj->userId = $request->getUserId();
        $dealObj->typeId = $request->getTypeId();
        $dealObj->loanFeeRate   = $request->getManageFeeRate();
        $dealObj->incomeFeeRate = $request->getRateYields();
        $dealObj->rate          = $request->getRateYields();
        $dealObj->prepayRate        = $request->getPrepayRate();
        $dealObj->prepayPenaltyDays = $request->getPrepayPenaltyDays();
        $dealObj->prepayDaysLimit   = $request->getPrepayDaysLimit();
        $dealObj->overdueRate       = $request->getOverdueRate();
        $dealObj->overdueDay        = $request->getOverdueDay();
        $dealObj->contractTplType   = $request->getContractTplType();
        $dealObj->createTime        = get_gmtime();
        $dealObj->annualPaymentRate = $request->getAnnualPaymentRate();
        $dealObj->updateTime        = 0;
        $dealObj->projectId         = $project_id;
        $dealObj->generationRechargeId = $request->getGenerationRechargeId();
        $dealObj->jysRecordNumber = $request->getJysRecordNumber();
        $dealObj->jysId = $request->getJysId();
        $dealObj->isFloatMinLoan = $request->getIsFloatMinLoan();
        $dealObj->canalAgencyId = $request->getCanalAgencyId();
        $dealObj->canalFeeRate = $request->getCanalFeeRate();
        $dealObj->consultFeePeriodRate = $request->getConsultFeePeriodRate();
        $dealObj->productClassType = $request->getProductClassType();
        $dealObj->loanUserCustomerType = $request->getLoanUserCustomerType();
        $dealObj->save();

        return (int)$dealObj->id;
    }

    /**
     * @添加标的附件信息
     * @param int $deal_id
     * @param object $request
     * @return bool
     */
    private static function addDealExt($deal_id, $request)
    {
        $dealExtObj = new Firstp2pDealExt();
        $dealExtObj->dealId                 = $deal_id;
        $dealExtObj->incomeBaseRate         = $request->getRateYields();
        $dealExtObj->leasingContractNum     = $request->getLeasingContractNum();
        $dealExtObj->lesseeRealName         = $request->getLesseeRealName();
        $dealExtObj->leasingMoney           = $request->getLeasingMoney();
        $dealExtObj->entrustedLoanEntrustedContractNum = $request->getEntrustedLoanEntrustedContractNum();
        $dealExtObj->entrustedLoanBorrowContractNum    = $request->getEntrustedLoanBorrowContractNum();
        $dealExtObj->baseContractRepayTime             = $request->getBaseContractRepayTime();
        $dealExtObj->leasingContractTitle   = $request->getLeasingContractTitle();
        $dealExtObj->lineSiteId             = $request->getLineSiteId();
        $dealExtObj->lineSiteName           = $request->getLineSiteName();
        $dealExtObj->guaranteeFeeRateType   = $request->getGuaranteeFeeRateType();
        $dealExtObj->loanApplicationType    = 0;
        $dealExtObj->loanFeeRateType        = $request->getLoanFeeRateType();
        $dealExtObj->payFeeRateType         = $request->getPayFeeRateType();
        $dealExtObj->consultFeeRateType     = $request->getConsultFeeRateType();
        $dealExtObj->contractTransferType   = $request->getContractTransferType();
        $dealExtObj->overdueBreakDays       = $request->getOverdueBreakDays();
        $dealExtObj->firstRepayInterestDay  = $request->getFixedReplay();
        $dealExtObj->dealNamePrefix   = '';
        $dealExtObj->payFeeExt        = '';
        $dealExtObj->guaranteeFeeExt  ='';
        $dealExtObj->consultFeeExt    = '';
        $dealExtObj->consultFeeRate   = '';
        $dealExtObj->loanFeeExt       = $request->getLoanFeeExt();//初始化值为''，如果不是代销分期则为''
        $dealExtObj->managementFeeExt = '';
        $dealExtObj->loanType   = $request->getExtLoanType();//默认值为0
        $dealExtObj->useInfo    = $request->getLoanApplicationType();
        $dealExtObj->discountRate     = $request->getDiscountRate();
        $dealExtObj->canalFeeRateType = $request->getCanalFeeRateType();
        $dealExtObj->save();
    }

    /**
     * 根据approve_number获得dealProject信息
     *
     * @param mixed $approve_number
     * @static
     * @access public
     * @return void
     */
    public static function getProject($approve_number) {
        try {
            $projectObj = Firstp2pDealProject::findFirst("approveNumber='{$approve_number}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProject" . "|" . $ex->getMessage());
            $projectObj = null;
        }
        return $projectObj;
    }

    /**
     * 根据approve_number查询标的信息
     * @param $approve_number
     * @param bool $is_slave
     * @return null
     */
    public static function getDealByApproveNum($approve_number,$is_slave = true) {
        try {
            if (!$is_slave) {
                $firstp2pDeal = new Firstp2pDeal();
                $firstp2pDeal->setReadConnectionService('firstp2p');
                $projectObj = $firstp2pDeal->findFirst("approveNumber='{$approve_number}'");
            } else {
                $projectObj = Firstp2pDeal::findFirst("approveNumber='{$approve_number}'");
            }
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getDeal" . "|" . $ex->getMessage());
            $projectObj = null;
        }
        return $projectObj;
    }
    /**
     * 根据项目名称获得dealProject信息
     *
     * @param mixed $name
     * @static
     * @access public
     * @return void
     */
    public static function getByNameProject($name) {
        try {
            $projectObj = Firstp2pDealProject::findFirst("name='{$name}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProject" . "|" . $ex->getMessage());
            $projectObj = null;
        }
        return $projectObj;
    }

    /**
     * 根据projectId获得dealProjectCompound信息
     *
     * @param mixed $projectId
     * @static
     * @access public
     * @return void
     */
    public static function getProjectCompound($projectId) {
        try {
            $projectCompoundObj = Firstp2pDealProjectCompound::findFirst("projectId='{$projectId}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProjectCompound" . "|" . $ex->getMessage());
            $projectCompoundObj = null;
        }
        return $projectCompoundObj;
    }

    /**
     * @调用合同服务
     * @param int $deal_id
     * @param int $contract_tpl_type
     * @param array $attach_info
     * @param int   $isCredit
     * @return bool
     */
    private static function callContractService($deal_id, $contract_tpl_type, $attach_info, $isCredit, $dealType, $projectId = 0, $projectDesc = '')
    {
        $rpc = new Rpc('contractRpc');

        /*项目委托投资说明*/
        if(!empty($projectDesc)){
            $projectDescRequest = new RequestSetProjectDescription();
            $projectDescRequest->setProjectId($projectId);
            $projectDescRequest->setType(1);
            $projectDescRequest->setSourceType(0);
            $projectDescRequest->setContent($projectDesc);
            $projectDescResponse = $rpc->go("\NCFGroup\Contract\Services\Category","setProjectDescription",$projectDescRequest);
            if($projectDescResponse->status != true){
                throw new \Exception("合同服务项目委托投资说明调用失败：".$projectDescResponse->errorCode.":".$projectDescResponse->errorMsg);
            }
        }

        //合同服务设置标的模板分类ID
        $contractRequest = new RequestSetDealCId();
        $contractRequest->setDealId($deal_id);
        $contractRequest->setCategoryId((int) $contract_tpl_type);
        $contractRequest->setType(0);
        $contractRequest->setSourceType($dealType);
        $contractResponse = $rpc->go("\NCFGroup\Contract\Services\Category","setDealCId",$contractRequest);
        if($contractResponse->status != true){
            throw new \Exception("合同服务调用失败：".$contractResponse->errorCode.":".$contractResponse->errorMsg);
        }

        if($isCredit == 1) return;

        //附件合同调用
        $request_save_attachment = new RequestSaveContractAttachment();
        $request_save_attachment->setDealId($deal_id);
        $request_save_attachment->setJsonData($attach_info);
        $request_save_attachment->setSourceType($dealType);
        $response_attachment = $rpc->go("\NCFGroup\Contract\Services\Tpl","saveContractAttachment",$request_save_attachment);
        if(!$response_attachment){
            throw new \Exception("附件合同服务可能没有启用");
        }

        if(!$response_attachment->getStatus()){
            throw new \Exception("附件合同服务调用失败：".$response_attachment->getErrorMsg());
        }
    }

    /**
     * @上标优惠码相关
     * @param int $deal_id
     * @param int $project_id
     * @param object $request
     * @param bool $is_coupay_pay_type
     * @return bool
     */
    private static function addCreditCoupon($deal_id, $project_id, $deal_type, $request, $is_coupay_pay_type)
    {
        $dealCouponObj = new \NCFGroup\Ptp\models\Firstp2pCouponDeal();
        $dealCouponObj->dealId      = $deal_id;
        $dealCouponObj->payAuto     = 1;
        $repayReriod = $request->getRepayReriod();
        $dealCouponObj->rebateDays  = ($request->getRepayPeriodType() == 1) ? $repayReriod : ($repayReriod*30);
        $dealCouponObj->payType = ($request->getLoanFeeRateType() == 2 || $is_coupay_pay_type == true) ? 1 : 0; //默认优惠码结算方式为前收  0.放款时结算；1.还清时结算
        /*信贷一键上标接口优惠码结算方式根据loan_fee_rate_type不同的值取不同的结算方式,如果昂利和掌众的方式要改的话，要注意不要相互影响
          al:昂立：loanFeeRateType = 1 (此值目前写死的)
          zh掌众：loanFeeRateType = 2 (此值目前写死的)
         */
        if ($request->getLoanFeeRateType() == 5 || $request->getLoanFeeRateType() == 7) {
            //信贷固定比例前收、固定比例分期收，系统自动关联优惠码选择结算时间为放款时结算
            $dealCouponObj->payType = 0;
        } elseif ($request->getLoanFeeRateType() == 6) {
            //固定比例后收，系统自动关联优惠码选择结算时间为还清时结算
            $dealCouponObj->payType = 1;
        }
        $dealCouponObj->save();

        /*通知贷*/
        if($deal_type == 1){
            $projectCompoundObj = new \NCFGroup\Ptp\models\Firstp2pDealProjectCompound();
            $projectCompoundObj->lockPeriod         = $request->getLockPeriod();
            $projectCompoundObj->redemptionPeriod   = $request->getRedemptionPeriod();
            $projectCompoundObj->projectId          = $project_id;
            $projectCompoundObj->save();

            $compoundObj = new \NCFGroup\Ptp\models\Firstp2pDealCompound();
            $compoundObj->lockPeriod        = $request->getLockPeriod();
            $compoundObj->redemptionPeriod  = $request->getRedemptionPeriod();
            $compoundObj->dealId            = $deal_id;
            $compoundObj->createTime = get_gmtime();
            $compoundObj->updateTime = get_gmtime();
            $compoundObj->endDate    = 0;
            $compoundObj->rateDay    = 0;
            $compoundObj->save();
        }
    }

    /**
     * @删除指定的标的和项目
     * @param int $dealId
     * @param int $projectId
     * @return void
     */
    public static function delDealProject($dealId, $projectId)
    {
        $delDeal = Firstp2pDeal::findFirst(array("conditions" => "id = ?1", "bind" => array(1 => $dealId)));
        $delDeal->delete();

        $delDealExt = Firstp2pDealExt::findFirst(array("conditions" => "dealId = ?1", "bind" => array(1 => $dealId)));
        $delDealExt->delete();

        $delCouponDeal = Firstp2pCouponDeal::findFirst(array("conditions" => "dealId = ?1", "bind" => array(1 => $dealId)));
        $delCouponDeal->delete();

        if(!empty($projectId)){
            $delProjectinfo = Firstp2pDealProject::findFirst(array("conditions" => "id = ?1", "bind" => array(1 => $projectId)));
            $delProjectinfo->delete();
        }
    }
    /**
     * @更新平台合作限额
     * @param unknown $usemoney
     * @param unknown $advisory_id
     * @param unknown $warning_level
     */
    public function updatePlatformInfo($usemoney, $advisory_id, $warning_level) {
        $platFormObj = new \NCFGroup\Ptp\models\Firstp2pPlatformManagement();
        $condition = "advisoryId=" . intval($advisory_id);
        $data = $platFormObj->findFirst($condition);
        $data = $data->toArray();
        if (empty($data)) {
            return ;
        }
        $platFormObj->id = $data['id'];
        $platFormObj->advisoryName = $data['advisoryName'];
        $platFormObj->moneyLimit = $data['moneyLimit'];
        $platFormObj->moneyEffectTermStart = $data['moneyEffectTermStart'];
        $platFormObj->moneyEffectTermEnd = $data['moneyEffectTermEnd'];
        $platFormObj->operatePerson = $data['operatePerson'];
        $platFormObj->isEffect = $data['isEffect'];
        $platFormObj->isDelete = $data['isDelete'];
        $platFormObj->createTime = $data['createTime'];
        $platFormObj->useMoney = floatval($usemoney);
        $platFormObj->advisoryId = intval($advisory_id);
        $platFormObj->isWarning = intval($warning_level);
        $platFormObj->updateTime = $data['updateTime'];
        $platFormObj->save();
    }
    /**
     * @更新产品限额
     * @param unknown $usemoney
     * @param unknown $product_name
     * @param unknown $warning_level
     */
    public function updateProductInfo($usemoney, $product_name, $warning_level) {
        $productObj = new \NCFGroup\Ptp\models\Firstp2pProductManagement();
        $condition = "productName='" . addslashes($product_name)."'";
        $data = $productObj->findFirst($condition);
        $data = $data->toArray();
        if (empty($data)) {
            return ;
        }
        $productObj->id = $data['id'];
        $productObj->productId = $data['productId'];
        $productObj->advisoryId = $data['advisoryId'];
        $productObj->advisoryName = $data['advisoryName'];
        $productObj->moneyLimit = $data['moneyLimit'];
        $productObj->moneyEffectTermStart = $data['moneyEffectTermStart'];
        $productObj->moneyEffectTermEnd = $data['moneyEffectTermEnd'];
        $productObj->operatePerson = $data['operatePerson'];
        $productObj->isEffect = $data['isEffect'];
        $productObj->isDelete = $data['isDelete'];
        $productObj->createTime = $data['createTime'];
        $productObj->useMoney = floatval($usemoney);
        $productObj->productName = addslashes($product_name);
        $productObj->isWarning = intval($warning_level);
        $productObj->updateTime = $data['updateTime'];
        $productObj->save();
    }


    public static function updateProjectDealInfo($list,$project_id,$deal_id,$dealType)
    {
        $res = false;
        try {
            $db = getDI()->get('firstp2p');
            $db->begin();
            $deal_ext =array();
            $deal =array();
            $project =array();

            $dealObj = Firstp2pDeal::findFirst("id='{$deal_id}'");

            foreach($list as $value=>$key){
                if(!empty(self::$paramsConf['ext'][$value]) )
                $deal_ext[$value] = self::$paramsConf['ext'][$value] ;
                if(!empty(self::$paramsConf['deal'][$value]) )
                $deal[$value] = self::$paramsConf['deal'][$value] ;
                if(!empty(self::$paramsConf['project'][$value]) )
                $project[$value] = self::$paramsConf['project'][$value] ;
            }

            //附加表
            $dealExtObj = Firstp2pDealExt::findFirst("dealId='{$deal_id}'");
            foreach($deal_ext as $v=>$key){
                $dealExtObj ->$key = $list[$v];
            }
          if(!empty($list['rate_yields'])){
                $dealExtObj->incomeBaseRate = $list['rate_yields'];;
            }
            $dealExtObj->save();

            //标的
            if(!empty($deal)){
                foreach($deal as $v=>$key){
                    $dealObj ->$key = $list[$v];
                }
                if(!empty($list['rate_yields'])){
                    $dealObj->rate = $list['rate_yields'];;
                }
                $dealObj->save();
            }
            //项目
            if(!empty($project)){
                $projectObj = Firstp2pDealProject::findFirst("id='{$project_id}'");
                foreach($project as $v=>$key) {
                    $projectObj->$key = $list[$v];
                }
                if(!empty($list['borrow_amount'])){
                    $projectObj->moneyBorrowed = $list['borrow_amount'];;
                }
                $projectObj->save();
            }
            if(isset($list['contract_tpl_type'])){
                $rpc = new Rpc('contractRpc');
                //合同服务设置标的模板分类ID
                $contractRequest = new RequestUpdateDealCId();
                $contractRequest->setDealId(intval($deal_id));
                $contractRequest->setCategoryId((int) $list['contract_tpl_type']);
                $contractRequest->setType(0);
                $contractRequest->setSourceType($dealType);
                $contractResponse = $rpc->go("\NCFGroup\Contract\Services\Category","updateDealCId",$contractRequest);
                if($contractResponse->status != true){
                    throw new \Exception("合同服务调用失败：".$contractResponse->errorCode.":".$contractResponse->errorMsg);
                }
            }

            if(isset($list['repay_period'])){
                $coupon_deal_service = new CouponDealService();
                $rebate_days = ($dealObj->loantype == 5) ? intval($list['repay_period']) : (intval($list['repay_period']) * 30) ;
                if(isset($list['loan_fee_rate_type'])){
                    $coupon_deal_service = new CouponDealService();
                    if($list['loan_fee_rate_type'] ==2 || $list['loan_fee_rate_type'] ==3  || $list['loan_fee_rate_type'] ==6  ){
                        $pay_type = 1;
                    }else{
                        $pay_type = 0;
                    }
                    $resCoupon = $coupon_deal_service->updateRebateDaysByDealId($deal_id, $rebate_days,$pay_type);
                    if(!$resCoupon){
                        throw new \Exception('更新标优惠码信息失败');
                    }
                }else{
                    $resCoupon = $coupon_deal_service->updateRebateDaysByDealId($deal_id, $rebate_days);
                }
                if(!$resCoupon){
                    throw new \Exception('更新标优惠码信息失败');
                }
            }
            if(!$db->commit()){
                    throw new \Exception('提交事务失败');
            }
            $res = true;

        } catch (\Exception $ex) {
            $msg_title =  'openapi updateProjectInfo&& deal errors';
            \libs\utils\Logger::error($msg_title . '|' . $ex->getMessage());
           $db->rollback();
        }
        if(!$res) return false;
        return array('deal_id' => $deal_id, 'project_id' => $project_id);
    }
}

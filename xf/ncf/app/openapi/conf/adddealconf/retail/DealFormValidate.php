<?php
/**
 * 一键上标接口的参数验证类
 * Created by PhpStorm.
 * User: duxuefeng
 * Date: 2018/6/14
 * Time: 10:49
 */
namespace openapi\conf\adddealconf\retail;

use core\enum\DealExtraEnum;
use openapi\conf\adddealconf\retail\RetailConf;
use openapi\lib\Tools;
use openapi\conf\ConstDefine;

use libs\utils\Alarm;

use core\dao\related\RelatedCompanyModel;
use core\dao\related\RelatedUserModel;
use core\service\user\BankService;
use core\service\user\UserService;
use core\service\deal\DealLoanTypeService;
use core\service\deal\DealService;
use core\service\deal\DealAgencyService;
use core\service\deal\DealTypeGradeService;
use core\service\deal\ProductManagementService;
use core\service\deal\PlatformManagementService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionAccountService;
use core\service\contract\CategoryService;
use core\service\contract\ContractBeforeBorrowService;

use libs\utils\Finance;
use core\enum\DealLoanTypeEnum;
use core\enum\UserAccountEnum;
use core\enum\AccountAuthEnum;
use core\enum\RelatedEnum;


class DealFormValidate {

    private $_errMsg  = '';
    private $_errKey  = '';

    // 授权校验flag
    protected $checkAuthFlag = false;

    public $form;
    public $validateData;
    public function __construct($form){
        $this->form = $form;
        $this->validateData = array();
    }

    public function getErrMsg(){
        return $this->_errMsg;
    }

    public function setErrMsg($errMsg){
        $this->_errMsg = $errMsg;
    }
    public function getErrKey(){
        return $this->_errKey;
    }

    public function setErrKey($errKey){
        $this->_errKey = $errKey;
        $this->_errMsg = RetailConf::$codeMsgList[$errKey];
    }

    public function setErr($errKey,$errMsg){
        $this->_errKey = $errKey;
        $this->_errMsg = $errMsg;
    }

    public function getFinalData(){
        $tempData = array_diff_key($this->validateData, $this->form->data);
        $tempData2 = array_diff_key($this->form->data, $this->validateData);
        return array_merge($tempData,$tempData2,$this->validateData);
    }

    //************************************其他业务公用方法*********************************************
    /**
     * 判断该用户是否开通授权
     * @param $name  参数名
     * @param $userId  用户id
     * @param $accountType  账户类型
     * @return array|bool
     */
    public function checktAuth($name,$userId,$accountType){
        if ($this->checkAuthFlag) {
            //检查授权
            //通过用户id获取账户id
            $accountId = AccountService::getUserAccountId($userId,$accountType);
            if(empty($accountId)){
                $this->setErr(RetailConf::ERR_USER_AUTH_FAIL,$name.' uesrId('.$userId.')对应的账户不存在; 账户类型为:'.UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_WANGXIN][$accountType]);
                return false;
            }
            $isSupervisionUser = (new SupervisionAccountService())->isSupervisionUser($accountId);
            //如果存管开关打开并且该用户未开户，则返回错误
            if ($isSupervisionUser === false) {
                $this->setErr(RetailConf::ERR_USER_NOT_EXIST,$name.' uesrId('.$userId.')该用户存管未开户');
                return false;
            }
            // 通过账户id检查免密缴费和免密还款授权
            $checkInfo = AccountAuthService::checkAccountAuth($accountId, AccountAuthEnum::BIZ_TYPE_BORROW);
            if ($checkInfo) {
                $this->setErr(RetailConf::ERR_USER_AUTH_FAIL,$name.' uesrId('.$userId.'):'.$checkInfo['grantMsg']);
                return false;
            }
        }
       return true;
    }

    // *************************************通用判断方法-BEGIN************************************
    /**
     * 必传
     * 不能为null,'',0,空数组
     * 如果判断方法的参数是必传，则需要把form->data和validateData增加进去
     * @param $params
     * @return array|bool
     */
    public function notEmpty($param){
        $result = !empty($_REQUEST[$param]) ? true :false;
        if(!$result){
            $this->setErrMsg($param .' is empty. It can not be empty.');
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return $result;
    }

    /**
     * 必传
     * 不包含数字以外的字符(int,double都返回true)
     * @param $params
     * @return array|bool
     */
    public function isNumeric($param){
        $result = is_numeric($_REQUEST[$param]) ? true :false;
        if(!$result){
            $this->setErrMsg($param .' must only contain numbers.');
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return $result;
    }

    /**
     * 必传
     * 不包含数字以外的字符
     * 且大于等于0
     * @param $params
     * @return array|bool
     */
    public function notLessThanZero($param){
        if(!is_numeric($_REQUEST[$param])){
            $this->setErrMsg($param .' must only contain numbers.');
            return false;
        }
        if($_REQUEST[$param] < 0){
            $this->setErrMsg($param .' must be bigger than zero.');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * 必传
     * 不包含数字以外的字符
     * 且大于0
     * @param $params
     * @return array|bool
     */
    public function greaterThanZero($param){
        if(!is_numeric($_REQUEST[$param])){
            $this->setErrMsg($param .' must only contain numbers.');
            return false;
        }
        if($_REQUEST[$param] <= 0){
            $this->setErrMsg($param .' must be bigger than zero.');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }


    /**
     * 可选
     * 不传这个参数，则不会使用该参数进行验参
     * 传这个参数，则使用该参数进行验参
     * @param $params
     * @return array|bool
     */
    public function optionalCheck($param){
        if(!isset($_REQUEST[$param])){
            return true;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * 可选
     * 不传这个参数，则不会使用该参数进行验参,但是会有默认值
     * 传这个参数，但值为空，就给此值给个默认值
     * @param $params
     * @return array|bool
     */
    public function optionalCheckDefault($param, $option){
        if(!isset($_REQUEST[$param])){
            $this->validateData[$param] = $option;
            return true;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        if(empty($_REQUEST[$param])){
            $this->validateData[$param] = $option;
        }
        return true;
    }

    /**
     * 机构id必须存在(必须落完库)
     * @param $params
     * @return array|bool
     */
    public function agencyMustExist($param){
        $greaterThanZero = $this->greaterThanZero($param);
        if($greaterThanZero === false){
            return false;
        }

        //使用新方法 获取dealAgency
        $agencyService = new DealAgencyService();
        $agencyInfo = $agencyService->getDealAgencyById($_REQUEST[$param]);
        if(empty($agencyInfo['user_id'])){
            $this->setErrMsg($param .' can not be found in database.');
            return false;
        }
        //检查授权
        $accountType = $this->getAccountTypeByName($param);
        if($accountType == false){
            $this->setErrMsg($param .' 在方法getAccountTypeByName中未定义');
            return false;
        }
        $check =  $this->checktAuth($param, $agencyInfo['user_id'],$accountType);
        if($check === false){
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * 根据参数名获取accountType(机构)
     */
    public function getAccountTypeByName($param){
        switch ($param) {
            case 'agencyId':
                return UserAccountEnum::ACCOUNT_GUARANTEE;
            case 'advisoryId':
                return UserAccountEnum::ACCOUNT_ADVISORY;
            case 'advanceAgencyId':
                return UserAccountEnum::ACCOUNT_REPLACEPAY;
            case 'generationRechargeId':
                return UserAccountEnum::ACCOUNT_RECHARGE;
            case 'chnAgencyId':
                return UserAccountEnum::ACCOUNT_CHANNEL;
        }
        return false;
    }

    /**
     * 机构id可选的校验
     * @param $params
     * @return array|bool
     */
    public function agencyOptionalCheck($param){
        //不传则默认为0
        if(!isset($_REQUEST[$param])){
            $this->validateData[$param] = 0;
            return true;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        if(empty($_REQUEST[$param])){
            $this->validateData[$param] = 0;
            return true;
        }
        //获取dealAgency
        $agencyService = new DealAgencyService();
        $agencyInfo = $agencyService->getDealAgencyById($_REQUEST[$param]);
         if(empty($agencyInfo['user_id'])){
            $this->setErrMsg($param .' can not be found in database.');
            return false;
        }
        return true;
    }

    /**
     * 机构id可选的校验 并且验证授权
     * @param $params
     * @return array|bool
     */
    public function agencyOptionalCheckAuth($param){
        //不传则默认为0
        if(!isset($_REQUEST[$param])){
            $this->validateData[$param] = 0;
            return true;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        if(empty($_REQUEST[$param])){
            $this->validateData[$param] = 0;
            return true;
        }
        // 获取dealAgency
        $agencyService = new DealAgencyService();
        $agencyInfo = $agencyService->getDealAgencyById($_REQUEST[$param]);
        if(empty($agencyInfo['user_id'])){
            $this->setErrMsg($param .' can not be found in database.');
            return false;
        }

        // 检查授权
        $accountType = $this->getAccountTypeByName($param);
        if($accountType == false){
            $this->setErrMsg($param .' 在方法getAccountTypeByName中未定义');
            return false;
        }
        $check =  $this->checktAuth($param, $agencyInfo['user_id'],$accountType);
        if($check === false){
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * typeId
     * @param string $param
     * @return array|bool 增加checkAuthFlag
     */
    public function checkTypeId($param) {
        if (empty($_REQUEST[$param])) {
            $this->setErrMsg($param .' is empty. It can not be empty.');
            return false;
        }
        //通过typeId获取typeTag
        $typeService = new DealLoanTypeService();
        $typeTag = $typeService->getLoanTagByTypeId($_REQUEST[$param]);
        if (empty($typeTag)){
            $this->setErrMsg($param .' 所对应的tag不存在.');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        //  增加checkAuthFlag 赋值为true,验证借款和还款免密授权
        // 所有产品类别都校验借款授权
        $this->checkAuthFlag = true;
/*
 * 暂时注释掉，等产品稳定下来再删除掉，免得产品又要回滚代码
         产品类别为消费贷或者功夫贷时才校验借款授权
         if (in_array($typeTag, ['XFD','XJDGFD'])) {
            $this->checkAuthFlag = true;
        }
*/

        /*
         * 前置合同
         * 校验借款人合同委托签署状态
         * 借款人合同委托签署状态为“已委托”，则根据放款审批单号判断是否存在记录。
         */
        $whiteList = explode(',',str_replace('，',',',app_conf('CONTRACT_SIGN_VERIFY_TYPE_TAG')));
        //配置中的类型验证
        if (in_array($typeTag, $whiteList)) {
            if ($_REQUEST['entrustSign'] == 1) {
                $contractResponse = ContractBeforeBorrowService::getContractByApproveNumber($_REQUEST['relativeSerialno']);
                //不存在 返回错误提示
                if (empty($contractResponse) || $contractResponse['borrowerSignTime'] <= 0) {
                    $this->setErr(RetailConf::ERR_CONTRACT_BEFORE_BORROW_UNSIGN,'该标的未签署前置协议');
                    return false;
                }
                //合同类型
                if ($contractResponse['categoryId'] != $_REQUEST['contractTplType']) {
                    $this->setErr(RetailConf::ERR_CONTRACT_BEFORE_BORROW_UNSIGN,'合同类型不一致');
                    return false;
                }
            }
        }
        return true;
    }


    // *************************************通用判断方法-END************************************



    // *************************************特殊判断方法-BEGIN************************************
    /**
     * discountRate 必须为0~100,默认值为100
     * @param $params
     * @return array|bool
     */
    public function discountRateCheck($param){
        if(!isset($_REQUEST[$param])){
            $this->validateData[$param] = 100;
            return true;
        }
        if($_REQUEST[$param] < 0 || $_REQUEST[$param] > 100){
            $this->setErrMsg($param .' 不在范围内');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * 校验为零售信贷还是对公信贷
     * @param $params
     * @return array|bool
     */
    public function isRetail($param){
        if(empty($_REQUEST['relativeSerialno'])){
            $this->setErrMsg('relativeSerialno不能为空');
            return false;
        }
        $this->form->data['relativeSerialno'] = $_REQUEST['relativeSerialno'];
        return true;
    }

    /**
     * 必传 并且解析后的值赋值到validateData
     * @param $params
     * @return array|bool
     */
    public function decodeProjectInfoUrl($param){
        if(empty($_REQUEST[$param])){
            $this->setErrMsg($param .' is empty. It can not be empty.');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        $this->validateData[$param] = base64_decode(urldecode(str_replace('!_!', '%',$_REQUEST[$param])));
        return true;
    }

    /**
     * 非必传 并且解析后的值赋值到validateData
     * @param $params
     * @return array|bool
     */
    public function optionalDecode($param){
        if(!isset($_REQUEST[$param])){
            return true;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        $this->validateData[$param] = !empty($_REQUEST[$param]) ? base64_decode(urldecode(str_replace('!_!', '%',$_REQUEST[$param]))) : '';
        return true;
    }

    /**
     * 代销分期
     * @param $params
     * @return array|bool
     */
    public function isProxySaleCheck($param){
        if(!isset($_REQUEST[$param])){
            return true;
        }
        if ($_REQUEST['isProxySale'] == 1) {
            //如果是代销分期，则零期年化收益率和尾期年化收益率之和应该等于年化借款手续费
            if (bccomp($_REQUEST['manageFeeRate'],bcadd($_REQUEST['startYieldRate'], $_REQUEST['endYieldRate'], 8),8) != 0) {
                $this->setErrMsg("start_yield_rate,end_yield_rate传参有误");
                return false;
            }
            if ($_REQUEST['loanFeeRateType'] != 4) {
                $this->setErrMsg("loan_fee_rate_type传参有误");
                return false;
            }
            //根据还款方式，借款期限，计算需要拆分为多少期进行还款
            $dealService = new DealService();
            $repayTimes = $dealService->getRepayTimesByLoantypeAndRepaytime($_REQUEST['loanType'], $_REQUEST['repayPeriod']);
            //根据还款方式将年化利率转换为期间利率
            $periodRate = Finance::convertToPeriodRate($_REQUEST['loanType'], $_REQUEST['manageFeeRate'], $_REQUEST['repayPeriod']);
            $totalMoney = ceilfix(doubleval($_REQUEST['borrowAmount']) * $periodRate / 100.0);
            $loanFirstFee = ceilfix($totalMoney * $_REQUEST['startYieldRate'] / $_REQUEST['manageFeeRate']);
            $loanLastFee = ceilfix($totalMoney - $loanFirstFee);
            $loanFeeExt = json_encode(array('0'=>$loanFirstFee,$repayTimes=>$loanLastFee));
        } else {
            if ($_REQUEST['loanFeeRateType'] == 4) {
                $this->setErrMsg("loan_fee_rate_type传参有误");
                return false;
            }
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * 固定还款日转换为deal表的时间戳，并且赋值给gmtFixedReplay
     * @param $params
     * @return array|bool (增加 fixedReplayToGmtTime )
     */
    public function toGmtTime($param){
        if(!isset($_REQUEST[$param])){
            return true;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        if(!empty($_REQUEST[$param])){
            $this->validateData['fixedReplay'] = to_timespan((date('Y-m-d',(int)$_REQUEST['fixedReplay']) . ' 00:00:00'));
        }
        return true;
    }

   /**
     * 调用checkCreditUser之后才能调用loanMoneyTypeIsEntrust
     * loanMoneyType loanBankCard  bankShortName  cardType  bankNum 5个参数关联判断
     * @param $params
     * @return array|bool (增加 bankCard,bankZone,bankId)
     */
    public function loanMoneyTypeIsEntrust($param){
        // 该参数可选  如果传了，则默认值为0
        if(empty($_REQUEST['loanMoneyType'])){
            return true;
        }
        //受托支付校验
        if ($_REQUEST['loanMoneyType'] == 3) {
            if (empty($_REQUEST['loanBankCard']) || empty($_REQUEST['bankShortName'])) {
                $this->setErrMsg('loanMoneyType为3-受托支付，卡号和银行简码不能为空');
                return false;
            }
            //根据银行简码获取银行信息
            $bankInfo = BankService::getBankInfoByCode($_REQUEST['bankShortName']);
            if (empty($bankInfo['id'])) {
                $this->setErrMsg('bankShortName对应的银行简码不正确');
                return false;
            }
            $this->validateData['bankId'] = $bankInfo['id'];
            //受托支付时会把原有的请求中bankCard重新赋值
            $this->validateData['bankCard'] = $_REQUEST['loanBankCard'];

            if ($_REQUEST['cardType'] == 1 ) {
                if (empty($_REQUEST['bankNum'])) {
                    $this->setErrMsg('cardType为1时，bankNum-企业账号联行号不能为空');
                    return false;
                }
                //根据联行号获取支行信息
                $banklistInfo = BankService::getBranchInfoByBranchNo($_REQUEST['bankNum']);
                if (empty($banklistInfo['name'])) {
                    $this->setErrMsg('bankNum-联行号不存在');
                    return false;
                }
                $this->validateData['bankZone'] = $banklistInfo['name'];
            }
        }
        $this->form->data[$param] = $_REQUEST[$param];
        $this->validateData['loanMoneyType'] = !empty($_REQUEST['loanMoneyType']) ? $_REQUEST['loanMoneyType'] : 0;
        return true;
    }


    public function getUserId(){
        static $userId = null;
        if(isset($userId)){
            return $userId;
        }

        $userId = 0;
        if(isset($_REQUEST['userType']) && $_REQUEST['userType'] == 1){
            $ids = UserService::getUserIdByCCU($_REQUEST['idno'],$_REQUEST['realName'],$_REQUEST['userName']);
            if(empty($ids)){
                $this->setErr(RetailConf::ERR_USER_NOT_EXIST,'该用户不存在');
                return false;
            }
            if(count($ids)>1){
                $this->setErr(RetailConf::ERR_USER_ERR,RetailConf::$codeMsgList[RetailConf::ERR_USER_ERR].' 企业用户不唯一');
                return false;
            }
            $userId = $ids[0];
        }else{
            //个人用户
            $userInfo = UserService::getUserByMobile($_REQUEST['mobile'], 'idno,real_name,id,is_effect');
            if(!empty($userInfo) && $userInfo['is_effect'] == 1){
                $res_id_no = strtoupper($userInfo['idno']);
                $pro_id_no = strtoupper($_REQUEST['idno']);
                $userId   = (strcmp($res_id_no,$pro_id_no) == 0 && strcmp($userInfo['real_name'],$_REQUEST['realName']) == 0) ? $userInfo['id'] : 0;
            }else{
                $userId = 0;
            }
        }
        return $userId;
    }

    /**
     * userType,idno,mobile,realName,userName,borrowAmount,otherBorrowing 9个参数关联校验
     * @param $params
     * @return array|bool
     * $data['userId']赋值
     */
    public function checkCreditUser($param){
        $userId = $this->getUserId();

        if (empty($userId)){
            $this->setErr(RetailConf::ERR_USER_NOT_EXIST,'该用户不存在');
            return false;

        }
        //p2p标的校验存管是否开户
        //通过用户id获取账户id
        $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_FINANCE);
        // 通过账户id检查授权
        $saService = new SupervisionAccountService();
        $isSupervisionUser = $saService->isSupervisionUser($accountId);

        //如果存管开关打开并且该用户未开户，则返回错误
        if ($isSupervisionUser === false) {
            $this->setErr(RetailConf::ERR_USER_NOT_EXIST,'该用户存管未开户');
            return false;
        }
        //如果用户开过存管户，则需要校验是否激活
        $isActive = SupervisionAccountService::isActivated($accountId);
        if($isActive === false){
            $this->setErr(RetailConf::ERR_USER_NOT_EXIST,'该存管户未激活');
            return false;
        }

        // 检查授权
        $result = $this->checktAuth($param,$userId,UserAccountEnum::ACCOUNT_FINANCE);
        if($result === false){
            return false;
        }

        //校验借款用户的在途借款金额
        $borrowAmount = 0;
        $borrowAmount = doubleval($_REQUEST['borrowAmount']);
        $otherBorrow = doubleval($_REQUEST['otherBorrowing']);

        //其他平台借款
        $otherTotalMoney = bcadd($borrowAmount, $otherBorrow, 5);
        $isEnterprise = isset($_REQUEST['userType']) && ($_REQUEST['userType'] == 1) ? true : false;
        $comRes = $isEnterprise ? bccomp($otherTotalMoney, ConstDefine::LOAN_LIMIT_ENT_TOTAL, 5) : bccomp($otherTotalMoney, ConstDefine::LOAN_LIMIT_PER_TOTAL, 5);
        if ($comRes > 0 ) {
            $this->setErr(RetailConf::ERR_USER_OVER_LIMIT_MONEY, $isEnterprise ? '企业用户跨平台借款本金不得大于500万元' :'个人用户跨平台借款本金不得大于100万元');
            return false;
        }

        //检验用户是否有关联
        $isRelated = false;
        if($_REQUEST['userType'] == 1) {
            $relatedCompanyModel = new RelatedCompanyModel();
            $isRelated = $relatedCompanyModel->isRelatedCompany($_REQUEST['idno'],RelatedEnum::CHANNEL_NCFPH);
        } else {
            $relatedUserModel = new RelatedUserModel();
            $isRelated = $relatedUserModel->isRelatedUser($_REQUEST['idno'],RelatedEnum::CHANNEL_NCFPH);
        }
        if($isRelated) {
            $this->setErrKey(RetailConf::ERR_RELATED_USER);
            return false;
        }

        //使用新方法  通过uid获取这些用户p2p未还款金额
        $dealService = new DealService();
        $userMoney = $dealService->getUnrepayP2pMoneyByUids(array($userId));

        //本次借款和本平台借款
        $localTotalMoney = bcadd($borrowAmount, $userMoney, 5);
        $comRes = $isEnterprise ? bccomp($localTotalMoney, ConstDefine::LOAN_LIMIT_ENT, 5) : bccomp($localTotalMoney, ConstDefine::LOAN_LIMIT_PER, 5);
        if ($comRes > 0 ) {
            $this->setErr(RetailConf::ERR_USER_OVER_LIMIT_MONEY, $isEnterprise ? '企业用户在途借款本金不得大于100万元' :'个人用户在途借款本金不得大于20万元');
            return false;
        }

        //本次借款和本平台借款和其他平台借款
        $totalMoney = bcadd($localTotalMoney, $otherBorrow, 5);
        $comRes = $isEnterprise ? bccomp($totalMoney, ConstDefine::LOAN_LIMIT_ENT_TOTAL, 5) : bccomp($totalMoney, ConstDefine::LOAN_LIMIT_PER_TOTAL, 5);
        if ($comRes > 0 ) {
            $this->setErr(RetailConf::ERR_USER_OVER_LIMIT_MONEY, $isEnterprise ? '企业用户跨平台借款本金不得大于500万元' : '个人用户跨平台借款本金不得大于100万元');
            return false;
        }
        if(isset($_REQUEST['userType'])){
            $this->form->data[$param] = $_REQUEST[$param];
        }
        $this->validateData['userId'] = $userId;
        return true ;
    }


    /**
     * 产品结构化验证
     * productName productClass borrowAmount
     * @param $params
     * @return array|bool (增加 productClassType, productMix1 productMix2 productMix3  riskBearing)
     */
    public function productStructuredValidation($param) {
        //产品结构化校验产品名称是否存在有效
        if (empty($_REQUEST[$param])) {
            $this->setErrMsg("productName不能为空");
            return false;
        }
        //产品结构化
        $productClass = empty($_REQUEST['productClass']) ? $this->validateData['productClass'] : $_REQUEST['productClass'];
        $productNameCheck = DealTypeGradeService::getAllLevelByName($productClass, $_REQUEST['productName']);
        if (empty($productNameCheck['level2']) || empty($productNameCheck['level3'])) {
            $this->setErrMsg("二级分类({$this->validateData['productClass']})和三级分类({$_REQUEST['productName']})无效");
            return false;
        }

        //风险评级
        $assessmentService = new DealProjectRiskAssessmentService();
        $riskBearing = $assessmentService->getByScoreAssesment($productNameCheck['score']);
        if (!$riskBearing) {
            $riskBearing = array('id' => 0);
        }
        //检查产品限额
        //使用新方法 产品限额
        $service = new ProductManagementService();
        $checkProduct = $service->getProductManagement($_REQUEST['productName'],$_REQUEST['borrowAmount']);
        if ($checkProduct !== false) {
            if ($checkProduct['errno'] == 1) {
                $this->setErr(RetailConf::ERR_PRODUCT_OVER_LIMIT, 'PlateFormWarning ' . '产品限额不足 ' . 'productName:'.$_REQUEST['productName'].$checkProduct['errmsg']);
                return false;
            }
            if ($checkProduct['errno'] == 2) {
                $this->setErr(RetailConf::ERR_PRODUCT_OVER_LIMIT, 'PlateFormWarning ' .'不在产品有效期内 '. 'productName:'.$_REQUEST['productName']);
                return false;
            }
            if ($checkProduct['errno'] === 0) {
                $this->validateData['productWarningLevel'] = $checkProduct['level'];
                $this->validateData['productWarningUseMoney'] = $checkProduct['use_money'];
            }
        }

        $this->form->data[$param] = $_REQUEST[$param];
        //二级分类id
        $this->validateData['productClassType'] = $productNameCheck['id2'];
        $this->validateData['productMix1'] = $productNameCheck['level1'];
        $this->validateData['productMix2'] = $productNameCheck['level2'];
        $this->validateData['productMix3'] = $productNameCheck['level3'];
        $this->validateData['riskBearing'] = intval($riskBearing['id']);
        return true;
    }


    /**
     * chnAgencyId和chnFeeRate chnFeeRateType
     * @param $params
     * @return array|bool (update: chnAgencyId,canalFeeRate,canalFeeRateType)
     */
    public function agencyAssociatedCheck($param) {
        //如果agencyId为空，则将chnAgencyId chnFeeRate chnFeeRateType 置为0,0,0
        if (!isset($_REQUEST[$param])) {
            $this->validateData['chnAgencyId'] = 0;
            $this->validateData['canalFeeRate'] = 0;
            $this->validateData['canalFeeRateType'] = 0;
            return true;
        }
        //如果agencyId不为空，则校验一下在数据库中是否存在.不存在则报错
        //获取dealAgency
        $agencyService = new DealAgencyService();
        $agencyInfo = $agencyService->getDealAgencyById($_REQUEST['chnAgencyId']);

        if(empty($agencyInfo['user_id'])){
            $this->setErrMsg('chnAgencyId can not be found in database.');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * contractTplType
     * @param $params
     * @return array|bool
     */
    public function contractTplTypeCheck($param) {
        //产品结构化校验产品名称是否存在有效
        if (empty($_REQUEST[$param])) {
            $this->setErrMsg($param."不能为空");
            return false;
        }
        //检查该合同分类是否存在，并且可以使用(不是删除状态，不是历史使用状态)
        $category = CategoryService::getCategoryById(intval($_REQUEST[$param]));
        if(empty($category)) {
            $this->setErrMsg($_REQUEST[$param].'合同分类不存在');
            return false;
        }
        if($category['isDelete'] == 1){
            //模板是删除状态
            $this->setErrMsg($_REQUEST[$param].'合同分类已被删除');
            return false;
        }
        if($category['useStatus'] == 0){
            //模板为历史使用
            $this->setErrMsg($_REQUEST[$param].'合同分类为历史使用');
            return false;
        }
        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    /**
     * 平台限额检查 advisoryId(必传 大于0)  borrowAmount
     * @param $params
     * @return array|bool(增加 AdvisoryName AdvisoryWarningLevel  AdvisoryWarningUseMoney)
     */
    public function advisoryLimitCheck($param) {
        //产品结构化校验产品名称是否存在有效
        $greaterThanZero = $this->greaterThanZero($param);
        if($greaterThanZero === false){
            return false;
        }
        //检查AdvisoryId是否落库
        //使用新方法 获取dealAgency
        $agencyService = new DealAgencyService();
        $agencyInfo = $agencyService->getDealAgencyById($_REQUEST['advisoryId']);
        if(empty($agencyInfo['user_id'])){
            $this->setErrMsg('advisoryId can not be found in database.');
            return false;
        }

        //使用新方法 平台限额
        $service = new PlatformManagementService();
        $checkAdvisory = $service->getPlatManagement($_REQUEST['advisoryId'],$_REQUEST['borrowAmount']);
        // 如果为false则没有设置平台限额
        if ($checkAdvisory !== false) {
            if ($checkAdvisory['errno'] == 1) {
                $this->setErr(RetailConf::ERR_ADVISORY_OVER_LIMIT, 'PlateFormWarning ' . '机构限额不足 ' . 'advisoryid:'.$_REQUEST['advisoryId'].$checkAdvisory['errmsg']);
                return false;
            }
            if ($checkAdvisory['errno'] == 2) {
                $this->setErr(RetailConf::ERR_ADVISORY_OVER_LIMIT, 'PlateFormWarning ' .'不在机构有效期内 '. 'advisoryid:'.$_REQUEST['advisoryId']);
                return false;
            }
            if ($checkAdvisory['errno'] === 0) {
                $this->validateData['advisoryName'] = $checkAdvisory['advisory_name'];
                $this->validateData['advisoryWarningLevel'] = $checkAdvisory['level'];
                $this->validateData['advisoryWarningUseMoney'] = $checkAdvisory['use_money'];
                return true;
            }
        }

        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }

    public function bindBankCradCheck($param){
        if(!isset($_REQUEST[$param])){
            return true;
        }
        $userBankInfo = BankService::getNewCardByUserId($this->getUserId());
        if ($_REQUEST[$param] != $userBankInfo['bankcard']) {
            $this->setErr(RetailConf::BANK_CARD_INCONFORMITY,'银行卡与用户绑定银行卡不一致');
            return false;
        }
        return true;
    }

    /**
     * 争议解决校验
     * @param $param
     * @return bool
     */
    public function recourseCheck($param){
        $recourseType = $_REQUEST[$param];
        if(!is_numeric($recourseType)){
            $this->setErrMsg($param .' must only contain numbers.');
            return false;
        }

        if(!in_array($recourseType,array(DealExtraEnum::RECOURSE_TYPE_LAWSUIT,DealExtraEnum::RECOURSE_TYPE_ARBITRATE))){
            $this->setErrMsg($param ." only numbers ".DealExtraEnum::RECOURSE_TYPE_LAWSUIT." and ".DealExtraEnum::RECOURSE_TYPE_ARBITRATE." are allowed .");
            return false;
        }

        if($recourseType == DealExtraEnum::RECOURSE_TYPE_LAWSUIT) {
            if(empty($_REQUEST['lawsuitAddress'])) {
                //当“争议解决方式”值为“诉讼”时，“诉讼解决地点”不能为空，若为空则接口报错，上标失败
                $this->setErrKey(RetailConf::ERR_RECOURSE_EMPTY);
                return false;
            }
        } elseif($recourseType == DealExtraEnum::RECOURSE_TYPE_ARBITRATE) {
            if(empty($_REQUEST['arbitrateAddress'])) {
                //当“争议解决方式”值为“诉仲”时，“诉仲裁解决地点”不能为空，若为空则接口报错，上标失败；
                $this->setErrKey(RetailConf::ERR_RECOURSE_EMPTY);
                return false;
            }
        }

        $this->form->data[$param] = $_REQUEST[$param];
        return true;
    }


    // *************************************特殊判断方法-END************************************

 }

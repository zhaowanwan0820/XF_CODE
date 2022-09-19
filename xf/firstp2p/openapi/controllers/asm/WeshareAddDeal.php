<?php

/**
 * AddApply.php
 *
 * Filename: AddApply.php
 * Descrition: 针对掌众的特殊上标逻辑,其他端不可调用!!
 * Author: yutao@ucfgroup.com
 * Date: 16-12-8 下午5:05
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;
use core\dao\DealLoanTypeModel;
use core\service\DealLoanTypeService;
use core\service\UserBankcardService;
use libs\utils\Logger;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\rpc\Rpc;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\payment\supervision\Supervision;

class WeshareAddDeal extends BaseAction {

    const ZZ_NULL_DATA = '掌众无此数据';
    const REDIS_INCR_KEY = 'WESHARE_PROJECT_INCR_KEY_20161209';

    private $user_class = array(1 => '企业', 2 => '个人');
    private static $_DEFAULT_INPUT_PARAMS = array(
        'advisoryId' => 360, //咨询方编号
        'advisoryName' => '', //咨询方名称
        'agencyId' => 0, //担保方编号
        'agencyName' => '', //担保方名称
        'paymentID' => '', //资金垫付方编号
        'paymentName' => '', //资金垫付方名称
        'onlineNo' => '', //上线网站编号
        'onlineName' => '', //上线网站名称
        'payorgId' => '', //支付机构编号
        'payorgName' => '', //支付机构名称
        'generationRechargeId' => 396, //代充值机构ID
    );
    //授信产品机构限额管理
    private static $_CREDIT_ORGAN_MANAGE = array(
        'productId' => '', //授信产品编号
        'productName' => '', //授信产品名称
        'belongAsset' => '', //所属资产端
        'limitMoney' => '', //限额
        'effectTime' => '', //生效时间
        'invalidTime' => '' //到期时间
    );
    //用款产品基本信息
    private static $_USEMONEY_PRODUCT_INFO = array(
        'productId' => '', //用款产品编号
        'productName' => '闪电借款', //用款产品名称
        'relatedAsset' => '北京掌众金融信息服务有限公司', //关联资产端
        'flowType' => '', //适用流程类型
        'productCat' => '', //产品大类
        'effectTime' => '2016/12/1', //生效时间
        'invalidTime' => '2017/12/1', //失效时间
    );
    //用款产品参数表
    private static $_USEMONEY_PARAMS = array(
        'orgId' => '11BZZ01',
        'productTypeId' => 34,
        'zcdnamenoId' => '2016120100000002', //参数配置
        'lendType' => 1, //放款类型
        'firstRepDate1' => '', //首期还款限制1
        'firstRepDate2' => '', //首期还款限制2
        'contractTypeId' => '', //合同类别ID
        'contractType' => '', //合同类型
        'producttype' => '', //产品类别
        'productdesc' => '', //产品介绍
        'RISKMEASURE' => '', //投资安全性
        'reverseagencymeasure' => '', //反担保措施
        'PURPOSE' => '', //资金用途
        'effectTime' => '2016/12/1', //生效时间
        'invalidTime' => '2017/12/1', //失效时间
        'singleUseMaxMoney' => 10000.0000, //单笔最大限额
        'singleUseMinMoney' => 500.0000, //单笔最小限额
        'cardType' => 0, //放款账号对私
    );
    //用款业务品种相关
    private static $_USEMONEY_BUSINESS_TYPE = array(
        'businessType' => 'A2016120100000001',
        'feerategroupno' => 1,
        'feerateversionno' => '1ZZ',
        'termGroupNo' => '2016120100000001',
        'pEffectTime' => '2016/12/1', //生效时间 product_fee_rate_version
        'pInvalidTime' => '2017/12/1', //失效时间
        'tEffectTime' => '2016/12/1', //生效时间 termlayer
        'tInvalidTime' => '2017/12/1', //失效时间
        'loanType' => '5', //还款方式
        'zixunRate' => 0.00, //年化咨询费
        'danbaoRate' => 0.00, //年化担保费
        'thirdPayRate' => 0.00, //年化第三方支付费
        //下面的platformRate，profitRate后面会读取后台的配置值，这个值将失效
//        'platformRate' => 8.2, //年化平台费
//        'profitRate' => 5.3, //投资人收益率
    );
    //html array
    private static $_HTML_CONTENT = array(
        'productDesc' => '“闪电借款”是北京掌众金融信息服务有限公司基于移动互联网针对个人用户推出的一款周期21天、额度在1万元以下的小额无抵押信用贷款产品。闪电借款推荐的借款用户主要是二三四线城市的新白领和蓝领群体。',
        'redRemark' => '“闪电借款”还款方式为到期一次性还本付息，出借人出借资金后，借款人在借款到期时一次性向出借人归还全部本息。',
        'RiskMeasure' => '如借款人未按时履行还款义务，则将由担保方履行担保责任对出借人本金和收益进行代偿，以保障出借人权益。',
        //'Financialintroduce' => '北京掌众金融信息服务有限公司是一家提供移动互联网和大数据建模技术服务的公司，通过大数据匹配的模式对资金充裕方与资金短缺方进行撮合，响应国家普惠金融的政策>号召，充分发挥社会共享经济机制，为急需资金的用户和有闲钱的用户之间搭建桥梁，提供1万元以下的小额短期借款信息撮合服务。',
        'project_extrainfo_url' => '',
        'project_info_url' => '',
    );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            //基本信息参数
            "OrgID" => array("filter" => "required", "message" => "OrgID is required"), //机构编号
            "BusinessType" => array("filter" => "required", "message" => "BusinessType is required"), //用款业务品种
            "userid" => array("filter" => "required", "message" => "userid is required"), //录入岗ID,不同于平台的userId
            "limitBusinessType" => array("filter" => "required", "message" => "limitBusinessType is required"), //授信业务品种编号
            "RelativeSerialno" => array("filter" => "required", "message" => "RelativeSerialno is required"), //客户端业务主键
            //客户信息参数
            "FULLNAME" => array("filter" => "required", "message" => "FULLNAME is required"), //客户名称
            "CERTTYPE" => array("filter" => "required", "message" => "CERTTYPE is required"), //证件类型
            "CERTID" => array("filter" => "required", "message" => "CERTID is required"), //身份证号码
            "PHONE" => array("filter" => "required", "message" => "PHONE is required"), //手机号码
            "WORKCORP" => array("filter" => "string", 'option' => array('optional' => true)), //单位名称
            "WORKADD" => array("filter" => "string", 'option' => array('optional' => true)), //单位地址
            "income" => array("filter" => "required", "message" => "income is required"), //月收入
            "WorkTel" => array("filter" => "string", 'option' => array('optional' => true)), //单位电话
            "HEADSHIP" => array("filter" => "string", 'option' => array('optional' => true)), //职务
            "OCCUPATION" => array("filter" => "string", 'option' => array('optional' => true)), //行业
            "COMPANYNATURE" => array("filter" => "string", 'option' => array('optional' => true)), //单位性质
            "FAMILYADD" => array("filter" => "required", "message" => "FAMILYADD is required"), //家庭住址
            "EMAILADD" => array("filter" => "string", 'option' => array('optional' => true)), //电子邮箱
            "financAdd" => array("filter" => "required", "message" => "financAdd is required"), //融资地址
            //用款信息
            "BusinessSum" => array("filter" => "required", "message" => "BusinessSum is required"), //提款金额
            "BusinessTerm" => array("filter" => "required", "message" => "BusinessTerm is required"), ///提款期限
            "BusinessTermUnit" => array("filter" => "required", "message" => "BusinessTermUnit is required"), //提款期限单位
            "PutOutBankID" => array("filter" => "required", "message" => "PutOutBankID is required"), //实际放款账号
            "PutOutBankNameID" => array("filter" => "required", "message" => "PutOutBankNameID is required"), //实际放款账号开户行编号
            "PutOutBankName" => array("filter" => "required", "message" => "PutOutBankName is required"), //实际放款账号开户行
            "PutOutBankLocation" => array("filter" => "required", "message" => "PutOutBankLocation is required"), //实际放款账号开户行所在地
            "PutOutBankWDName" => array("filter" => "required", "message" => "PutOutBankWDName is required"), //实际放款账户开户网点
            "PutOutBankUserName" => array("filter" => "required", "message" => "PutOutBankUserName is required"), //实际放款账号开户人名称
            "RelevanceBankNo" => array("filter" => "required", "message" => "RelevanceBankNo is required"), //联行号
            "FirstRepDate" => array("filter" => "string", 'option' => array('optional' => true)), //首期还款日
            "OpenID" => array("filter" => "required", "message" => "OpenID is required"), //网信openID
            "BusinessCurrency" => array("filter" => "required", "message" => "BusinessCurrency is required"), //币种
            "ChangeCondition" => array("filter" => "required", "message" => "ChangeCondition is required"), //提是否放款条件变更
            "ISEXCHANGE" => array("filter" => "required", "message" => "ISEXCHANGE is required"), //是否资产转让
            "OccurType" => array("filter" => "required", "message" => "OccurType is required"), //发生类型
            "IsIncome" => array("filter" => "required", "message" => "IsIncome is required"), //是否通知贷
            "DEPOSITFLAG" => array("filter" => "required", "message" => "DEPOSITFLAG is required"), //是否收取备付金
            "DEPOSIT" => array("filter" => "required", "message" => "DEPOSIT is required"), //收取备付金比例（是否收取备付金为否时，值为0）
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        //总开关
        if (app_conf('WESHARE_ENABLE') === '0') {
            $this->setErr('ERR_SYSTEM', '功能已停用');
            return false;
        }
        if (Supervision::isServiceDown()) { //存管降级不能上标
            $this->setErr('ERR_SYSTEM_MAINTENANCE');
            return false;
        }

        $params = $this->form->data;
        //TODO start luzhengshuai
        $params['wx_open_id'] = $params['OpenID'];
        // BusinessTermUnit 只能是天 010
        if ($params['BusinessTermUnit'] !== '010') {
            $this->setErr('ERR_PARAMS_ERROR', '错误的BusinessTermUnit');
            return false;
        }

        $params['repay_period_type'] = 1; // 此参数需过信贷核对，暂无法验证, 1代表P2P侧的天 2代表月
        $params['contract_tpl_type'] = app_conf('WESHARE_CONTRACT_TPL_TYPE');
        $staticParms = array(
            'prepay_rate' => 0.00, // 提前还款违约金系数 product_fee_rate_version.overdue_rate
            'prepay_penalty_days' => 0, // 提前还款罚息天数
            'prepay_days_limit' => 0, // 提前还款锁定期
            'overdue_rate' => 0.05, // 逾期还款违约金系数 product_fee_rate_version.overduerate
            'overdue_day' => 0, // 代偿时间 product_parameter.overdueday1
            //'contract_tpl_type' => 299, // 合同类型 product_parameter.contracttypeid
            'leasing_contract_num' => '', // 基础合同编号
            'lessee_real_name' => '', // 基础合同交易金额
            'leasing_money' => 0.0000, // 基础合同交易金额
            'entrusted_loan_borrow_contract_num' => null, // 委托贷款委托合同编号
            'entrusted_loan_entrusted_contract_num' => null, // 委托贷款借款合同编号
            'base_contract_repay_time' => 0, // 基础合同借款到期日
            'line_site_id' => 1, // 上线网站编号 product_parameter.ONLINENO
            'line_site_name' => '网信理财', // 上线网站名称 product_parameter.ONLINENAME
            'overdue_break_days' => 0, // 中途逾期强还天数
            'loan_fee_rate_type' => 2, // 平台服务费收费方式 product_fee_rate_version.Isservicerate
            'consult_fee_rate_type' => 2, // 咨询费收费方式 product_fee_rate_version.ISRate1
            'guarantee_fee_rate_type' => 2, // 担保费收费方式 product_fee_rate_version.ISRate2
            'pay_fee_rate_type' => 2, // 第三方支付收费方式 product_fee_rate_version.OTHERGETMONEYTYPE
        );

        $params = array_merge($params, $staticParms);
        unset($staticParms);
        //TODO end luzhengshuai
        //掌众数据处理and前置判断
        if ($params['PutOutBankLocation'] == self::ZZ_NULL_DATA) {
            $params['PutOutBankLocation'] = '';
        }
        if ($params['PutOutBankWDName'] == self::ZZ_NULL_DATA) {
            $params['PutOutBankWDName'] = '';
        }
        if ($params['RelevanceBankNo'] == self::ZZ_NULL_DATA) {
            $params['RelevanceBankNo'] = '';
        }

        //$params['BusinessSum']
        //用款产品生效时间限制
        if (time() > strtotime(self::$_USEMONEY_PARAMS['invalidTime'])) {
            $this->setErr('ERR_PARAMS_ERROR', '该用款产品已经失效');
            Alarm::push('WESHARE', '用款产品时间过期导致上标失败', $this->errorMsg);
            return false;
        }

        //产品限额判断
        if ($params['BusinessSum'] < self::$_USEMONEY_PARAMS['singleUseMinMoney'] || $params['BusinessSum'] > self::$_USEMONEY_PARAMS['singleUseMaxMoney']) {
            $this->setErr('ERR_PARAMS_ERROR', '该用款超出产品金额限制');
            Alarm::push('WESHARE', '该用款超出产品金额限制', $this->errorMsg);
            return false;
        }

        //todo by quanhengzhuang
        //基础合同名称 (线上全是空的)
        //BASENAME = rs.getString("bc.BASENAME");
        $params['leasing_contract_title'] = '';

        //转让资产类型 (线上全是空的)
        //contract_transfer_type = rs.getString("bc.EXCHANGETYPE");
        $params['contract_transfer_type'] = 0;

        //借款用途分类
        //rs120.getString("MONEYPURPOSETYPE").toString();
        //getItemName('Moneytype', bc.MONEYPURPOSETYPE) as zzz,
        //loan_application_type = rs.getString("zzz");
        $params['loan_application_type'] = '日常消费';

        //放款方式
        //product_parameter.putouttype
        //if("20".equals(firstp2p_putouttype)) firstp2p_putouttype="3";
        $params['loan_money_type'] = 3;

        //实际放款帐号开户人名称
        $params['card_name'] = $params['PutOutBankUserName'];

        //实际放款帐号开户网点、银行卡号
        $params['bankzone'] = $params['PutOutBankWDName'];
        $params['bankid'] = intval($params['PutOutBankNameID']);
        $params['bankcard'] = $params['PutOutBankID'];
        $bankname = addslashes($params['PutOutBankName']);

        //检查开户行信息(但开启行名称后面并没有用)，另开户行网点及联行号对方传的都是无此数据，所以不再校验
        $result = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT id FROM firstp2p_bank WHERE id='{$params['bankid']}' AND name='{$bankname}' LIMIT 1");
        if (empty($result)) {
            $this->setErr('ERR_PARAMS_ERROR', '开户行Id与开户行名称不符');
            Alarm::push('WESHARE', '参数错误导致上标失败', $this->errorMsg . ". bankid:{$params['bankid']}, bankname:{$bankname}.");
            return false;
        }

        //获取后台配置的费率相关信息
        $feeRateInfo = $this->getZzFee();
        $income_base_rate= $feeRateInfo['income_base_rate'];//投资人收益率
        $loan_fee_rate= $feeRateInfo['loan_fee_rate'];//年化平台费
        //投资人收益率
        //product_fee_rate_version.yieldofinvestors
        //profit = rs.getString("bc.profit");
        $params['rate_yields'] = $income_base_rate;

        //借款合同是否委托签署
        //product_parameter.contractsinged
        //contractsinged = rs.getString("bc.contractsinged");
        $params['entrust_sign'] = 1;

        //tmpCustomerType = rs.getString("ci.CUSTOMERTYPE");
        $params['user_types'] = 90;

        //首期还款日
        //FirstRepDate = rs.getString("FirstRepDate");
        $params['fixed_replay'] = $params['FirstRepDate'];

        //资金垫付方
        //product_parameter.paymentID
        //paymentid = rs.getString("bc.paymentid");
        $params['advance_agency_id'] = 361;

        //担保合同是否委托签署
        //product_parameter
        //GuaranteeContractIsSigned = rs.getString("bc.GuaranteeContractIsSigned");
        $params['entrust_agency_sign'] = 1;

        //资产管理方合同是否委托签署
        //product_parameter
        //ZCContractIsSigned = rs.getString("bc.ZCContractIsSigned");
        $params['entrust_advisory_sign'] = 1;

        //担保范围
        //product_parameter
        //warrant = rs.getString("bc.warrant");
        $params['warrant'] = 0;

        //是否绑卡校验
        //product_parameter.checkwhetherbinding
        //caved_bindcard = rs.getString("bc.caved_bindcard");
        $params['caved_bindcard'] = 2;

        //委托机构id (信贷系统中)受托方编号
        //commissionid =rs.getString("bc.commissionid");
        $params['entrust_agency_id'] = 0;

        //产品大类
        //product_parameter
        //producttypename2 = rs.getString("bc.producttypename2");
        $params['product_class'] = '消费贷';

        //getBusinessName(BusinessType)
        //ssBusinessTypeName = rs.getString("spBusinessTypeName");
        $params['product_name'] = '闪电借款';
        //todo end by quanhengzhuang

        $params['approve_number'] = $params['RelativeSerialno'];
        $params['name'] = self::$_USEMONEY_PRODUCT_INFO['productName'] . $this->getProjectIncrNo();
        $params['real_name'] = $params['FULLNAME'];
        $params['idno'] = $params['CERTID'];
        $params['mobile'] = $params['PHONE'];
        if ($params['BusinessType'] != self::$_USEMONEY_BUSINESS_TYPE['businessType']) {
            $this->setErr("ERR_PARAMS_ERROR", 'BusinessType无效');
            return false;
        }
        $params['loan_type'] = self::$_USEMONEY_BUSINESS_TYPE['loanType'];
        $params['credit'] = $params['BusinessSum'];
        $params['rate'] = $this->getMoney(self::$_USEMONEY_BUSINESS_TYPE['zixunRate'] + self::$_USEMONEY_BUSINESS_TYPE['danbaoRate'] + self::$_USEMONEY_BUSINESS_TYPE['thirdPayRate'] + $loan_fee_rate + $income_base_rate);

        // BusinessTerm 只能 是21天
        if (intval($params['BusinessTerm']) != 21) {
            $this->setErr("ERR_PARAMS_ERROR", 'BusinessTerm无效');
            return false;
        }

        $params['repay_period'] = $params['BusinessTerm'];
        $params['deal_type'] = $params['IsIncome'] == 1 ? 1 : 0;
        $params['lock_period'] = 0;
        $params['redemption_period'] = 0;
        $params['borrow_amount'] = $this->getMoney($params['BusinessSum']);
        $params['advisory_id'] = self::$_DEFAULT_INPUT_PARAMS['advisoryId'];
        $params['agency_id'] = self::$_DEFAULT_INPUT_PARAMS['agencyId'];
        $params['generation_recharge_id'] = self::$_DEFAULT_INPUT_PARAMS['generationRechargeId'];

        if ($params['OrgID'] != self::$_USEMONEY_PARAMS['orgId']) {
            $this->setErr("ERR_PARAMS_ERROR", 'OrgID无效');
            return false;
        }
        $params['type_id'] = self::$_USEMONEY_PARAMS['productTypeId'];
        $params['manage_fee_rate'] = $this->getMoney($loan_fee_rate);
        $params['annual_payment_rate'] = $this->getMoney(self::$_USEMONEY_BUSINESS_TYPE['thirdPayRate']);
        $params['guarantee_fee_rate'] = $this->getMoney(self::$_USEMONEY_BUSINESS_TYPE['danbaoRate']);
        $params['consult_fee_rate'] = $this->getMoney(self::$_USEMONEY_BUSINESS_TYPE['zixunRate']);
        $params['packing_rate'] = $this->getMoney(self::$_USEMONEY_BUSINESS_TYPE['thirdPayRate'] + $loan_fee_rate + $income_base_rate);

        //产品结构化校验产品名称是否存在有效
        if (empty($params['product_name'])) {
            $this->setErr("ERR_PARAMS_ERROR", "product_name不能为空");
            return false;
        }
        $params['product_name'] = addslashes(trim($params['product_name']));
        $ProductNameCheck = $this->rpc->local('DealTypeGradeService\getAllBySubName', array($params['product_name']));
        if (empty($ProductNameCheck) || empty($ProductNameCheck['level3'])) {
            $this->setErr("ERR_PARAMS_ERROR", "product_name无效");
            return false;
        }
        $riskBearing = $this->rpc->local('DealProjectRiskAssessmentService\getByScoreAssesment', array($ProductNameCheck['score']));
        if (!$riskBearing) {
            $riskBearing = array('id' => 0);
        }


        //获取后天配置的信息披露数据
        $request = new SimpleRequestBase();
        $paramsArray = array(
            'productType' => 1, //产品类型目前只有闪电消费这一种
            'investTerm' => intval($params['BusinessTerm']),
            'investUnit' => 1, //现在只支持天
        );
        $request->setParamArray($paramsArray);
        $result = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpWeshare',
            'method' => 'getWeshareInfoDisclosureInfo',
            'args' => $request
        ));
        if (empty($result) || $result->resCode != 0) {
            $this->setErr("ERR_PARAMS_ERROR", '后台没有相匹配的信息披露');
            return false;
        }
        if ($result->isEffect != 1) {
            $this->setErr("ERR_PARAMS_ERROR", '后台没有对应有效的信息披露');
            return false;
        }
        $resInfo = $result->toArray();
        unset($resInfo['resCode']);
        if (in_array('', $resInfo)) {
            $this->setErr("ERR_PARAMS_ERROR", '对应的后台信息披露信息有空值');
            return false;
        }
        if (empty($params['financAdd'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'financAdd不能为空');
            return false;
        }
        //项目简介拼接
        if ($resInfo['loanUsage'] == '<p>日常消费（融资方保证按照借款用途使用资金）</p>') {
            $loanUsageRep = '日常消费'; //跟产品确认，如果是默认值，则取日常消费，其他不变
        } else {
            $loanUsageRep = str_replace('<p>', '', $resInfo['loanUsage']);
        }
        $productDescInfo = '融资方于' . date("Y") . '年' . date("m") . '月' . date("d") . '日' . '在' . $params['financAdd'] . '申请融资用于' . $loanUsageRep;
        //读取模板内容
        $file = file_get_contents(APP_ROOT_PATH . 'openapi/conf/weshareDealTpl.html');
        if (empty($file)) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            Alarm::push('WESHARE', '模板问题导致上标失败', $this->errorMsg);
            return false;
        }
        $idnoFormat = substr($params['CERTID'], 0, 3) . '******' . substr($params['CERTID'], -3); //与产品确认规则去前三后三中间6个星号
        $file = str_replace("\n", "", $file);
        $file = str_replace('${productDesc}', $productDescInfo, $file);
        $file = str_replace('${repayGuaranteeMeasur}', $resInfo['repayGuaranteeMeasur'], $file);
        $file = str_replace('${loanUsage}', $resInfo['loanUsage'], $file);
        $file = str_replace('${expectIntrerstDate}', $resInfo['expectIntrerstDate'], $file);
        $file = str_replace('${limitManage}', $resInfo['limitManage'], $file);
        $file = str_replace('${name}', nameFormat($params['FULLNAME']), $file);
        $file = str_replace('${idno}', $idnoFormat, $file);
        $file = str_replace('${overCount}', '0次', $file);
        $file = str_replace('${overMoney}', '0元', $file);
        $file = str_replace('${projectRiskTip}', $resInfo['projectRiskTip'], $file);

        $params['project_extrainfo_url'] = self::$_HTML_CONTENT['project_extrainfo_url'];
        $params['project_info_url'] = $file;

        //之前的setProjectDealInfo逻辑
        $user_types = isset($params['user_types']) ? (($params['user_types'] > 2 || $params['user_types'] < 1) ? 2 : (int) $params['user_types']) : 2;
        $caved_bindcard = (!empty($params['caved_bindcard']) && $params['caved_bindcard'] == 2) ? true : false;

        $params['user_types'] = $user_types;
        $userId = $this->checkCreditUser($params, $caved_bindcard);
        if ($userId < 0) {
            switch ($userId) {
                case -3 :
                    $this->errorCode = 4;
                    $this->errorMsg = '该' . $this->user_class[$user_types] . '存管未开户';
                    break;
                case -1 :
                    $this->errorCode = 2;
                    $this->errorMsg = '该' . $this->user_class[$user_types] . '不存在';
                    break;
                case -4 :
                    $this->errorCode = 5;
                    $this->errorMsg = ($params['user_types'] == 2) ? '个人用户在途借款本金不得大于20万元' : '企业用户在途借款本金不得大于100 万元';;
                    break;
                default:
                    $this->errorCode = 3;
                    $this->errorMsg = '该' . $this->user_class[$user_types] . '未绑定银行卡';
            }
            Alarm::push('WESHARE', '参数错误导致上标失败', $this->errorMsg . ". userId:{$userId}, user_types:{$user_types}.");
            return false;
        }
        if ($this->svStatus == 1) {
            $params['loan_money_type'] = 1;
            $userBankInfo = (new UserBankcardService())->getBankcard($userId);
            if (!empty($params['bankcard']) && ($params['bankcard'] != $userBankInfo['bankcard'])) {
                $this->errorCode = 4;
                $this->errorMsg = '银行卡与用户绑定银行卡不一致';
                return false;
            }
        }

        //业务处理开始
        $borrow_amount = doubleval($params['borrow_amount']);
        if (!$borrow_amount) {
            $this->setErr("ERR_PARAMS_ERROR", "borrow_amount is not double");
            return false;
        }

        $credit = doubleval($params['credit']);
        if (!$credit) {
            $this->setErr("ERR_PARAMS_ERROR", "credit is not double");
            return false;
        }

        $params['type_id'] = ($params['type_id'] == 'null') ? 1 : $params['type_id'];
        $params['line_site_id'] = (int) $params['line_site_id'];
        $params['leasing_contract_title'] = (string) $params['leasing_contract_title'];
        $params['overdue_break_days'] = (int) $params['overdue_break_days'];
        $params['deal_type'] = empty($params['deal_type']) ? 0 : (($params['deal_type'] >= 0 && $params['deal_type'] <= 3) ? (int) $params['deal_type'] : 0);
        $params['line_site_name'] = ($params['line_site_name'] == 'null' || $params['line_site_name'] == null) ? '' : (string) $params['line_site_name'];
        $params['guarantee_fee_rate_type'] = ($params['guarantee_fee_rate_type'] == 'null' || $params['guarantee_fee_rate_type'] == null) ? 1 : (int) $params['guarantee_fee_rate_type'];
        $params['loan_fee_rate_type'] = ($params['loan_fee_rate_type'] == 'null' || $params['loan_fee_rate_type'] == null) ? 1 : (int) $params['loan_fee_rate_type'];
        $params['loan_application_type'] = ($params['loan_application_type'] == 'null' || $params['loan_application_type'] == null) ? '' : (string) $params['loan_application_type'];
        $params['consult_fee_rate_type'] = ($params['consult_fee_rate_type'] == 'null' || $params['consult_fee_rate_type'] == null) ? 1 : (int) $params['consult_fee_rate_type'];
        $params['contract_transfer_type'] = ($params['contract_transfer_type'] == 'null' || $params['contract_transfer_type'] == null) ? 0 : (int) $params['contract_transfer_type'];
        $params['pay_fee_rate_type'] = ($params['pay_fee_rate_type'] == 'null' || $params['pay_fee_rate_type'] == null) ? 1 : (int) $params['pay_fee_rate_type'];
        $params['repay_period_type'] = ($params['repay_period_type'] == 'null' || $params['repay_period_type'] == null) ? 1 : (int) $params['repay_period_type'];
        $params['annual_payment_rate'] = ($params['annual_payment_rate'] == 'null' || $params['annual_payment_rate'] == null) ? 0.000000 : $params['annual_payment_rate'];
        $params['guarantee_fee_rate'] = isset($params['guarantee_fee_rate']) ? $params['guarantee_fee_rate'] : 0.000000;
        $params['packing_rate'] = isset($params['packing_rate']) ? $params['packing_rate'] : 0.000000;
        $params['consult_fee_rate'] = isset($params['consult_fee_rate']) ? $params['consult_fee_rate'] : 0.000000;
        $params['project_info_url'] = isset($params['project_info_url']) ? $params['project_info_url'] : '';
        $params['base_contract_repay_time'] = !empty($params['base_contract_repay_time']) ? strtotime($params['base_contract_repay_time']) : 0;
        $params['lessee_real_name'] = !empty($params['lessee_real_name']) ? $params['lessee_real_name'] : '';
        $params['leasing_contract_num'] = isset($params['leasing_contract_num']) ? $params['leasing_contract_num'] : '';
        $params['leasing_money'] = isset($params['leasing_money']) ? $params['leasing_money'] : 0.00;
        $params['entrusted_loan_borrow_contract_num'] = ($params['entrusted_loan_borrow_contract_num'] == 'null' || empty($params['entrusted_loan_borrow_contract_num'])) ? '' : $params['entrusted_loan_borrow_contract_num'];
        $params['entrusted_loan_entrusted_contract_num'] = (empty($params['entrusted_loan_entrusted_contract_num']) || $params['entrusted_loan_entrusted_contract_num'] == 'null') ? '' : $params['entrusted_loan_entrusted_contract_num'];
        $params['contract_tpl_type'] = !empty($params['contract_tpl_type']) ? $params['contract_tpl_type'] : '';
        $params['loan_money_type'] = !empty($params['loan_money_type']) ? $params['loan_money_type'] : 0;
        $params['card_name'] = !empty($params['card_name']) ? $params['card_name'] : '';
        $params['bankzone'] = !empty($params['bankzone']) ? $params['bankzone'] : '';
        $params['bankid'] = !empty($params['bankid']) ? $params['bankid'] : 0;
        $params['bankcard'] = !empty($params['bankcard']) ? $params['bankcard'] : '';
        $params['lock_period'] = !empty($params['lock_period']) ? $params['lock_period'] : 0;
        $params['entrust_sign'] = !empty($params['entrust_sign']) ? $params['entrust_sign'] : 0;
        $params['fixed_replay'] = !empty($params['fixed_replay']) ? to_timespan((date('Y-m-d', (int) $params['fixed_replay']) . ' 00:00:00')) : 0;
        $params['advance_agency_id'] = !empty($params['advance_agency_id']) ? (int) $params['advance_agency_id'] : 0;
        $params['entrust_agency_sign'] = !empty($params['entrust_agency_sign']) ? (int) $params['entrust_agency_sign'] : 0;
        $params['entrust_advisory_sign'] = !empty($params['entrust_advisory_sign']) ? (int) $params['entrust_advisory_sign'] : 0;
        $params['warrant'] = isset($params['warrant']) ? (int) $params['warrant'] : 0;
        $params['product_class'] = !empty($params['product_class']) ? $params['product_class'] : '';
        $params['product_name'] = !empty($params['product_name']) ? $params['product_name'] : '';
        $params['entrust_agency_id'] = !empty($params['entrust_agency_id']) ? $params['entrust_agency_id'] : 0;
        $request = new \NCFGroup\Protos\Ptp\ProtoProjectDeal();
        try {
            $request->setApproveNumber($params['approve_number']);
            $request->setUserId((int) $userId);
            $request->setBorrowAmount($borrow_amount);
            $request->setProjectBorrowAmout($borrow_amount);
            $request->setCredit($credit);
            $request->setLoanType($params['loan_type']);
            $request->setName($params['name']);
            $request->setProjectName($params['name']);
            $request->setRate($params['rate']);
            $request->setGuaranteeFeeRate($params['guarantee_fee_rate']);
            $request->setPackingRate($params['packing_rate']);
            $request->setRepayReriod($params['repay_period']);
            $request->setRepayPeriodType($params['repay_period_type']);
            $request->setProjectInfoUrl($params['project_info_url']);
            $request->setDealType($params['deal_type']);
            $request->setLockPeriod($params['lock_period']);
            $request->setRedemptionPeriod($params['redemption_period']);
            $request->setAdvisoryId($params['advisory_id']);
            $request->setAgencyId($params['agency_id']);
            $request->setTypeId($params['type_id']);
            $request->setManageFeeRate($params['manage_fee_rate']);
            $request->setConsultFeeRate($params['consult_fee_rate']);
            $request->setPrepayRate($params['prepay_rate']);
            $request->setPrepayPenaltyDays($params['prepay_penalty_days']);
            $request->setPrepayDaysLimit($params['prepay_days_limit']);
            $request->setOverdueRate($params['overdue_rate']);
            $request->setOverdueDay($params['overdue_day']);
            $request->setContractTplType($params['contract_tpl_type']);
            $request->setLeasingContractNum($params['leasing_contract_num']);
            $request->setLesseeRealName($params['lessee_real_name']);
            $request->setLeasingMoney($params['leasing_money']);
            $request->setEntrustedLoanEntrustedContractNum($params['entrusted_loan_entrusted_contract_num']);
            $request->setEntrustedLoanBorrowContractNum($params['entrusted_loan_borrow_contract_num']);
            $request->setBaseContractRepayTime($params['base_contract_repay_time']);
            $request->setAnnualPaymentRate($params['annual_payment_rate']);
            $request->setLineSiteId($params['line_site_id']);
            $request->setLineSiteName($params['line_site_name']);
            $request->setOverdueBreakDays($params['overdue_break_days']);
            $request->setLoanFeeRateType($params['loan_fee_rate_type']);
            $request->setConsultFeeRateType($params['consult_fee_rate_type']);
            $request->setGuaranteeFeeRateType($params['guarantee_fee_rate_type']);
            $request->setPayFeeRateType($params['pay_fee_rate_type']);
            $request->setLeasingContractTitle($params['leasing_contract_title']);
            $request->setContractTransferType($params['contract_transfer_type']);
            $request->setLoanApplicationType($params['loan_application_type']);
            $request->setRateYields($params['rate_yields']);
            $request->setLoanMoneyType($params['loan_money_type']);
            $request->setCardName($params['card_name']);
            $request->setBankCard($params['bankcard']);
            $request->setBankZone($params['bankzone']);
            $request->setBankId($params['bankid']);
            $request->setEntrustSign($params['entrust_sign']);
            $request->setFixedReplay($params['fixed_replay']);
            $request->setAdvanceAgencyId($params['advance_agency_id']);
            $request->setEntrustAgencySign($params['entrust_agency_sign']);
            $request->setEntrustAdvisorySign($params['entrust_advisory_sign']);
            $request->setWarrant($params['warrant']);
            $request->setIsCredit(1);
            $request->setProductClass($params['product_class']);
            $request->setProductName($params['product_name']);
            $request->setDealTagName('');
            $request->setDealTagDesc('');
            $request->setMinLoanMoney(100);
            $request->setMaxLoanMoney(0);
            $request->setBusinessLines('mulandaicn');
            $request->setIsEffect(1);
            $request->setEntrustAgencyId($params['entrust_agency_id']);
            $request->setCardType(self::$_USEMONEY_PARAMS['cardType']);
            $request->setRiskBearing(intval($riskBearing['id']));
            $request->setProductMix1($ProductNameCheck['level1']);
            $request->setProductMix2($ProductNameCheck['level2']);
            $request->setProductMix3($ProductNameCheck['level3']);
            $request->setGenerationRechargeId($params['generation_recharge_id']);
        } catch (\Exception $e) {
            $this->errorCode = -100;
            $this->errorMsg = "上标失败";
            Alarm::push('WESHARE', 'PtpBackend参数错误导致上标失败', $this->errorMsg . '. msg:' . $e->getMessage());
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpProjectDeal',
            'method' => 'addProjectDeal',
            'args' => $request
        ));

        $codeMsgList = array('-1' => 'Project already exists',
            '-2' => 'Project name already exists',
            '1' => 'insert dealProject failed',
            '-3' => '该咨询机构的上标金额已超出平台限额，不能上标',
            '-4' => '该产品的上标金额已超出平台限额，不能上标',
            '-5' => '不在咨询机构的有效期内，不能上标',
            '-6' => '不在产品限额有效期内，不能上标',
            '-8'=>'业务正在处理中',
            '-9'=>'用户在途借款本金不得大于20万元'
        );
        $code = $response->resCode;
        if (in_array($code, array_keys($codeMsgList))) {
            $this->errorCode = $code;
            $this->errorMsg = $codeMsgList[$code];
            Alarm::push('WESHARE', 'PtpBackend返回异常导致上标失败', $this->errorMsg . ', approve_number:' . $params['approve_number']);
            return false;
        }

        $this->errorCode = 0;
        $this->errorMsg = ($code == -7) ?  '该项目标的已经存在' : 'ok';
        $this->json_data = $response->dealId;

        Monitor::add('WESHARE_ADDDEALl_SUCCESS');
        Logger::info('WESHARE_ADDDEALl_SUCCESS. params:' . json_encode($params));
    }

    private function getMoney($money) {
        return bcadd($money, 0, 2);
    }

    private function getProjectIncrNo() {

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        return $redis->incr(self::REDIS_INCR_KEY);
    }

    /**
     * 获取后台掌众的费率配置信息
     * @return array|bool|\mix|mixed|\stdClass|string
     */
    private function getZzFee() {
        $res = $this->rpc->local('DealLoanTypeService\getInfoByTag', array('ZZJR'));
        return json_decode($res,ture);
    }

}

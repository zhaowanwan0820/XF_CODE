<?php
/**
 * [合同相关：提供合同模板渲染的方法]
 * @author <fanjingwen@>
 */

namespace core\service\contract;

use core\dao\deal\DealExtraModel;
use core\dao\deal\DealSiteModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealExtModel;
use core\dao\project\DealProjectModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealAgencyModel;


use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\deal\DealAgencyService;
use core\service\deal\EarningService;

use libs\utils\XDateTime;
use libs\utils\Finance;

use core\enum\DealEnum;
use core\enum\DealExtraEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\contract\ContractServiceEnum;
require_once(APP_ROOT_PATH . "libs/common/app.php");

/**
* [合同模板渲染]
*/
class ContractRenderService
{
    // ------------------------ get - 合同签署各方的信息 ------------------------
    /**
     * [获取甲方-出借方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param array $user_info [key-value:对应user表中字段]
     * @param array $loan_bank_info [key-value:对应user_bank_card表中字段]
     * @return array $loanInfo [see returnInfo]
     */
    static public function getLoanInfo($user_info)
    {
        // init returnInfo
        $loanInfo = array(
            'loan_name_info'                => '',
            'loan_username_info'            => '',
            // 合同模板信息
            'loan_credentials_info'         => '',
            'loan_bank_user_info'           => '',
            'loan_bank_no_info'             => '',
            'loan_bank_name_info'           => '',
            // ---
            'loan_name_info_transfer'       => '',
            'loan_username_info_transfer'   => '',
            'loan_credentials_info_transfer'=> '',
            'loan_bank_user_info_transfer'  => '',
            'loan_bank_no_info_transfer'    => '',
            'loan_bank_name_info_transfer'  => '',
            'loan_major_name'               => '',
            'loan_major_condentials_no'     => '',
            'loan_user_number'              => '', // 会员编号
        );
        $loan_user_info = isset($user_info['user']) ? $user_info['user'] : array() ;
        // 如果投资人用户信息为空，则返回格式化的初始数据
        if(empty($loan_user_info)){
            return $loanInfo;
        }
        $loan_bank_info = $user_info['card'];
        if(!empty($user_info['enterprise'])){
            $enterprise_info = $user_info['enterprise'];
            $loanInfo['loan_name_info'] = $enterprise_info['company_name'];
            $loanInfo['loan_username_info'] = $loan_user_info['user_name'];
            $loanInfo['loan_credentials_info'] = $enterprise_info['credentials_no'];
            $loanInfo['loan_bank_user_info'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
            //转让合同
            $loanInfo['loan_name_info_transfer'] = $enterprise_info['company_name'];
            $loanInfo['loan_username_info_transfer'] = $loan_user_info['user_name'];
            $loanInfo['loan_credentials_info_transfer'] = $enterprise_info['credentials_no'];
            $loanInfo['loan_bank_user_info_transfer'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info_transfer'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info_transfer'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            // JIRA#3260 企业账户二期 <fanjingwen@>
            $loanInfo['loan_major_name'] = '代理人：' . $enterprise_info['contact']['major_name']; // 企业账户负责人-姓名
            $loanInfo['loan_major_condentials_no'] = '代理人身份证件号：' . $enterprise_info['contact']['major_condentials_no']; // 企业账户负责人-证件号码
            $loanInfo['loan_user_number'] = numTo32Enterprise($loan_user_info['id']);
        }else{
            //借款合同
            $loanInfo['loan_name_info'] = $loan_user_info['real_name'];
            $loanInfo['loan_username_info'] = $loan_user_info['user_name'];
            $loanInfo['loan_credentials_info'] = $loan_user_info['idno'];
            $loanInfo['loan_bank_user_info'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            //转让合同
            $loanInfo['loan_name_info_transfer'] = $loan_user_info['real_name'];
            $loanInfo['loan_username_info_transfer'] = $loan_user_info['user_name'];
            $loanInfo['loan_credentials_info_transfer'] = $loan_user_info['idno'];
            $loanInfo['loan_bank_user_info_transfer'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info_transfer'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info_transfer'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            // JIRA#3260 企业账户二期 <fanjingwen@>
            $loanInfo['loan_major_name'] = '';
            $loanInfo['loan_major_condentials_no'] = '';
            $loanInfo['loan_user_number'] =isset($loan_user_info['id']) ? numTo32($loan_user_info['id']) : '';
        }

        return $loanInfo;
    }

    /**
     * [获取乙方-借款方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $$userID
     * @return array $borrowInfo [see returnInfo]
     */
    static public function getBorrowInfo($userInfo)
    {
        // init returnInfo
        $borrowInfo = array(
            'borrow_name'                 => '',
            'borrow_user_number'          => '', // 会员编号
            'borrow_license'              => '', // 营业执照号
            'borrow_agency_realname'      => '', // 代理人姓名
            'borrow_agency_idno'          => '', // 代理人证件号
            'borrow_legalbody_name'      => '', // 法人姓名姓名
            'borrow_registration_address'          => '', // 企业注册地址
        );

        // 1、企业会员；2、以前的机构成员；

        $user_info = isset($userInfo['user']) ? $userInfo['user'] : array();
        // 如果投资人用户信息为空，则返回格式化的初始数据
        if(empty($user_info)){
            return $borrowInfo;
        }
        if (!empty($userInfo['enterprise'])) {
            $enterprise_info = $userInfo['enterprise'];

            // 合同模板信息
            $borrowInfo['borrow_name'] = $enterprise_info['company_name'];
            $borrowInfo['borrow_user_number'] = numTo32Enterprise($enterprise_info['user_id']);
            $borrowInfo['borrow_license'] = $enterprise_info['credentials_no']; // 企业证件号码
            $borrowInfo['borrow_agency_realname'] = $enterprise_info['contact']['major_name']; // 企业负责人
            $borrowInfo['borrow_agency_idno'] = $enterprise_info['contact']['major_condentials_no'];
        } else {
            $company_info = $userInfo['company'];
            // 合同模板信息
            $borrowInfo['borrow_name'] = isset($company_info['name']) ? $company_info['name'] : '';
            $borrowInfo['borrow_user_number'] = numTo32($user_info['id']);
            $borrowInfo['borrow_license'] = isset($company_info['license']) ? $company_info['license'] : ''; // 关联公司的营业执照
            $borrowInfo['borrow_agency_realname'] = isset($user_info['real_name']) ? $user_info['real_name'] : '';
            $borrowInfo['borrow_agency_idno'] = isset($user_info['idno']) ? $user_info['idno'] : '';
        }

        return $borrowInfo;
    }
    /**
     * [获取丙方-平台方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param array $agencyInfo 机构数据
     * @param array $userInfo agency['user_id'] 用户数据
     * @param array $agencyUserInfo agency['agency_user_id']  代理人数据
     * @return array $platformInfo [see returnInfo]
     */
    static public function getPlatformInfo($agencyInfo,$userInfo,$agencyUserInfo)
    {
        // init returnInfo
        $platformInfo = array(
            'platform_realname'             => '',
            'platform_address'              => '',
            // 合同模板信息
            'platform_name'                 => '',
            'platform_agency_user_number'   => '',
            'platform_license'              => '',
            'platform_agency_realname'      => '',
            'platform_agency_idno'          => '',
            'platform_agency_username'      => '', // 平台代理人
        );
        //获取平台方信息

        // 1、企业会员；2、以前的机构成员；

        if (!empty($userInfo['enterprise'])) {
            $enterprise_info = $userInfo['enterprise'];
            $userInfo = $userInfo['user']; // 获取企业法人信息

            // info
            $platformInfo['platform_realname'] = $userInfo['real_name'];
            $platformInfo['platform_address'] = $enterprise_info['contract_address'];
            // 合同模板信息
            $platformInfo['platform_name'] = $enterprise_info['company_name'];
            $platformInfo['platform_agency_user_number'] = numTo32Enterprise($enterprise_info['user_id']);
            $platformInfo['platform_license'] = $enterprise_info['credentials_no']; // 企业证件号码
            $platformInfo['platform_agency_realname'] = $enterprise_info['contact']['major_name']; // 企业法人
            $platformInfo['platform_agency_idno'] = $enterprise_info['contact']['major_condentials_no'];
            $platformInfo['platform_agency_username'] = '';
        } else {
            if (!empty($agencyUserInfo['user'])) {
                $platform_agency_user = $agencyUserInfo['user'];

            }
            // info
            $platformInfo['platform_realname'] = $agencyInfo['realname'];
            $platformInfo['platform_address'] = $agencyInfo['address'];
            // 合同模板信息
            $platformInfo['platform_name'] = $agencyInfo['name'];
            $platformInfo['platform_agency_user_number'] = isset($platform_agency_user) ? numTo32($platform_agency_user['id']) : '';
            $platformInfo['platform_license'] = $agencyInfo['license'];
            $platformInfo['platform_agency_realname'] = isset($platform_agency_user) ? $platform_agency_user['real_name'] : '';
            $platformInfo['platform_agency_idno'] = isset($platform_agency_user) ? $platform_agency_user['idno'] : '';
            $platformInfo['platform_agency_username'] = isset($platform_agency_user) ? $platform_agency_user['user_name'] : '';
        }

        return $platformInfo;
    }

    /**
     * [获取丁方-资产管理方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $advisoryID
     * @return array $advisoryInfo [see returnInfo]
     */
    static public function getAdvisoryInfo($advisory_info,$userInfo)
    {
        // init returnInfo
        $advisoryInfo = array(
            'advisory_name'                 => '',
            'advisory_agent_user_number'    => '',
            'advisory_license'              => '',
            'advisory_agent_real_name'      => '',
            'advisory_agent_user_idno'      => '',
            'advisory_address'              => '',
            'advisory_mobile'               => '',
            'advisory_card_name'            => '',
            'advisory_bankcard'             => '',
            'advisory_bankzone'             => '',
            'advisory_realname'             => '',
            'advisory_agent_user_name'      => '', // 代理人用户名
        );

        // 资产管理信息

        if (!empty($userInfo['enterprise'])) {
            $enterprise_info = $userInfo['enterprise'];
            $userInfo = $userInfo['user']; // 获取企业法人信息

            // 合同取值
            $advisoryInfo['advisory_name'] = $enterprise_info['company_name'];
            $advisoryInfo['advisory_agent_user_number'] = numTo32Enterprise($enterprise_info['user_id']);
            $advisoryInfo['advisory_license'] = $enterprise_info['credentials_no'];
            $advisoryInfo['advisory_agent_real_name'] = $enterprise_info['contact']['major_name'];
            $advisoryInfo['advisory_agent_user_idno'] = $enterprise_info['contact']['major_condentials_no'];
            // other
            $advisoryInfo['advisory_address'] = $enterprise_info['contract_address'];
            $advisoryInfo['advisory_realname'] = $userInfo['real_name'];
            $advisoryInfo['advisory_agent_user_name'] = $advisory_info['agency_agent_user_name'];
        } else {
            $advisoryInfo['advisory_name'] = $advisory_info['name'];
            $advisoryInfo['advisory_agent_user_number'] = numTo32($advisory_info['agency_user_id']);
            $advisoryInfo['advisory_license'] = $advisory_info['license'];
            $advisoryInfo['advisory_agent_real_name'] = $advisory_info['agency_agent_real_name'];
            $advisoryInfo['advisory_agent_user_idno'] = $advisory_info['agency_agent_user_idno'];
            // other
            $advisoryInfo['advisory_address'] = $advisory_info['address'];
            $advisoryInfo['mobile'] = $advisory_info['mobile'];
            $advisoryInfo['advisory_realname'] = $advisory_info['realname'];
            $advisoryInfo['advisory_agent_user_name'] = $advisory_info['agency_agent_user_name'];
            $advisoryInfo['advisory_card_name'] = $advisory_info['card_name'];
            $advisoryInfo['advisory_bankcard'] = $advisory_info['bankcard'];
            $advisoryInfo['advisory_bankzone'] = $advisory_info['bankzone'];
        }

        return $advisoryInfo;
    }

    /**
     * [获取戊方-保证方信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param array $agency_info
     * @param array $userInfo
     * @return array $advisoryInfo [see returnInfo]
     */
    static public function getAgencyInfo($agency_info, $userInfo)
    {
        // int returnInfo
        $agencyInfo = array(
            'agency_name'               => '',
            'agency_agent_user_number'  => '',
            'agency_license'            => '',
            'agency_agent_real_name'    => '',
            'agency_agent_user_idno'    => '',

            'agency_platform_realname'  => '',
            'agency_agent_user_name'    => '',
            'agency_user_realname'      => '',
            'agency_address'            => '',
            'agency_mobile'             => '',
            'agency_postcode'           => '',
            'agency_fax'                => '',
            'agency_card_name'          => '',
            'agency_bankcard'          => '',
            'agency_bankzone'          => '',
        );

        $agency_platform_user = isset($userInfo['user']) ? $userInfo['user'] : array();
        if (!empty($userInfo['enterprise'])) {
            $enterprise_info = $userInfo['enterprise'];
            // 合同取值
            $agencyInfo['agency_name'] = $enterprise_info['company_name'];
            $agencyInfo['agency_agent_user_number'] = numTo32Enterprise($enterprise_info['user_id']);
            $agencyInfo['agency_license'] = $enterprise_info['credentials_no'];
            $agencyInfo['agency_agent_real_name'] = $enterprise_info['contact']['major_name'];
            $agencyInfo['agency_agent_user_idno'] = $enterprise_info['contact']['major_condentials_no'];
        } else {
            $agencyInfo['agency_name'] = $agency_info['name'];
            $agencyInfo['agency_agent_user_number'] = numTo32($agency_info['agency_user_id']);
            $agencyInfo['agency_license'] = $agency_info['license'];
            $agencyInfo['agency_agent_real_name'] = $agency_info['agency_agent_real_name'];
            $agencyInfo['agency_agent_user_idno'] = $agency_info['agency_agent_user_idno'];
        }
        $agencyInfo['agency_platform_realname'] = $agency_platform_user['real_name'];
        $agencyInfo['agency_agent_user_name'] = $agency_info['agency_agent_user_name'];
        $agencyInfo['agency_user_realname'] = $agency_info['realname'];
        $agencyInfo['agency_address'] = $agency_info['address'];
        $agencyInfo['agency_mobile'] = $agency_info['mobile'];
        $agencyInfo['agency_postcode'] = $agency_info['postcode'];
        $agencyInfo['agency_fax'] = $agency_info['fax'];
        $agencyInfo['agency_card_name'] = $agency_info['card_name'];
        $agencyInfo['agency_bankcard'] = $agency_info['bankcard'];
        $agencyInfo['agency_bankzone'] = $agency_info['bankzone'];

        return $agencyInfo;
    }
    // ------------------------ over - 合同签署各方的信息 ------------------------

    /**
     * get loan_type_mark for contract_content
     * @author <fanjingwen@ucf>
     * @param int $loan_type
     * @return string $loan_type_mark
     */
    static public function getLoanTypeMark($loan_type)
    {
        $hash_loan_type = array(
            '1'     => 'D',  // '按季等额本息还款'
            '2'     => 'C',  // '按月等额本息还款'
            '3'     => 'E',  // '到期支付本金收益'
            '4'     => 'A',  // '按月支付收益到期还本'
            '5'     => 'E',  // '到期支付本金收益'
            '6'     => 'B',  // '按季支付收益到期还本'
            '8'     => 'F',  // '等额本息固定日还款'
            '9'     => 'G',  // '按月等额本金'
            '10'    => 'H',  // '按季等额本金'
        );

        return isset($hash_loan_type[$loan_type]) ? $hash_loan_type[$loan_type] : '';
    }

    /**
     * get repay_time_unit for contract_content
     * @author <fanjingwen@ucf>
     * @param int $type_id 借款类别
     * @param int $loan_type 还款方式
     * @param int $repay_time 借款期限
     * @param int $first_repay_interest_day 针对消费分期，第一期还款日
     * @return string $repay_time_unit
     */
    static public function getRepayTimeUnit($type_id, $loan_type, $repay_time, $first_repay_interest_day)
    {
        $repay_time_unit = '';
        // 看借款类型
        $deal_type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId($type_id);
        if (DealLoanTypeEnum::TYPE_XFFQ == $deal_type_tag) {
            $time_obj_old = XDateTime::valueOfTime($first_repay_interest_day);
            $time_obj_new = $time_obj_old->addMonth($repay_time);
            $repay_time_unit = to_date($time_obj_new->getTime(), "Y年m月d日");
        } else {
            $repay_time_unit = (5 == $loan_type) ? $repay_time . "天" : $repay_time . "个月";
        }

        return $repay_time_unit;
    }

    /**
     * 获取订单的用户企业信息
     *
     * @param $deal 订单信息
     * @param $userInfo 用户数据
     * @return array
     */
    public static function getDealUserCompanyInfo($deal,$userInfo) {
        $data = array();

        $data['is_company'] = 0; //合同模板中没有使用该变量

        //个人借款
        $user_info = isset($userInfo['user']) ? $userInfo['user'] : array();
        if (!empty($user_info)) {
            $data['show_name'] = isset($user_info['user_name']) ? $user_info['user_name'] : ''; //合同模板中没有使用该变量
            //合同变量
            $data['borrow_real_name'] = isset($user_info['real_name']) ? $user_info['real_name'] : ''; //真实姓名 合同模板有使用
            $data['borrow_user_name'] = isset($user_info['user_name']) ? $user_info['user_name'] : ''; //用户名 历史合同模板使用该变量
            $data['borrow_user_idno'] = isset($user_info['idno']) ? $user_info['idno'] : ''; //身份证 合同模板有使用
            $data['borrow_address'] = isset($user_info['address']) ? $user_info['address'] : ''; //地址 合同模板有使用
            $data['borrow_mobile'] = isset($user_info['mobile']) ? $user_info['mobile'] : ''; //手机 历史合同模板使用该变量
            $data['borrow_postcode'] = isset($user_info['email']) ? $user_info['email'] : ''; //邮箱 （历史错误）历史合同模板使用该变量
            $data['borrow_email'] = isset($user_info['email']) ? $user_info['email'] : ''; //邮箱 历史合同模板使用该变量
            $data['real_name'] = isset($user_info['real_name']) ? $user_info['real_name'] : ''; //邮 合同模板中没有使用该变量
        }

        /*
         *公司借款 合同服务相关
         */
        if(is_numeric($deal['contract_tpl_type'])){
            $tpl_info = CategoryService::getCategoryById($deal['contract_tpl_type']);
            $tpl_info['contract_type'] = $tpl_info['contractType'];
        }else{
            $tpl_info = $deal['contract_tpl_type'] ? CategoryService::getCategoryLikeTypeTag($deal['contract_tpl_type']) : array();
        }
        $data['company_description_html'] = '';
        if (!empty($tpl_info) && $tpl_info['contract_type'] == ContractServiceEnum::TYPE_COMPANY) {
            $company_info = $userInfo['company'];
            $data['is_company'] = 1;
            $data['show_name'] = isset($company_info['name']) ? $company_info['name'] : '';
            $data['company_name'] = isset($company_info['name']) ? $company_info['name'] : ''; //名称 合同模板有使用
            $data['company_address'] = isset($company_info['address']) ? $company_info['address'] : ''; //注册地址 历史网贷合同模板使用该变量
            $data['company_legal_person'] = isset($company_info['legal_person']) ? $company_info['legal_person'] : ''; //法定代表人 历史网贷合同模板使用该变量
            $data['company_tel'] = isset($company_info['tel']) ? $company_info['tel'] : ''; //联系电话  历史网贷合同模板使用该变量
            $data['company_license'] = isset($company_info['license']) ? $company_info['license'] : ''; //营业执照号 历史网贷合同模板和现在黄金合同模板使用该变量
            $data['company_description'] = isset($company_info['description']) ? $company_info['description'] : ''; //简介 合同模板没有使用
            $data['company_address_current'] = isset($company_info['domicile']) ? $company_info['domicile'] : ''; //借款公司住所地 历史网贷、交易所和专享合同模板使用该变量

            $tempDes = $data['company_description'];
            $company_info['is_html'] = isset($company_info['is_html']) ? $company_info['is_html'] : -1;
            if(intval($company_info['is_html']) === 0) { //数据处理
                $tempDes = str_replace("\n", "<br/>", $data['company_description']);
            }
            $data['company_description_html'] = $tempDes; //合同模板没有使用
        }
        return $data;
    }

    /**
     * 获取标的各方的用户信息
     * @param array $deal
     * @param int $userId
     * @param int $dealSiteId  site_id
     * @return array 和标的有关的所有用户数据
     */
    public static function getDealUserInfos($deal,$userId,$dealSiteId = null){
        if(empty($deal)){
            return false;
        }
        // 获取机构
        $dealAgencyModel = new DealAgencyModel();
        $entrust_agency_info = $dealAgencyModel->getDealAgencyById($deal['entrust_agency_id']);
        $agency_info = $dealAgencyModel->getDealAgencyById($deal['agency_id']);
        $canal_info = $dealAgencyModel->getDealAgencyById($deal['canal_agency_id']);
        $advisory_info = $dealAgencyModel->getDealAgencyById($deal['advisory_id']);
        // $dealSiteId没有传参数，则取查询标的对应的站点
        if(is_null($dealSiteId)){
            $deal_site_info = DealSiteModel::instance()->getSiteByDeal($deal['id']);
            $dealSiteId = !empty($deal_site_info['site_id']);
        }
        $dealagency_service = new DealAgencyService();
        $platform_info = $dealagency_service->getDealAgencyBySiteId($dealSiteId);

        $agencyInfos = array(
            'entrustInfo' => $entrust_agency_info,
            'platformInfo' => $platform_info,
            'advisoryInfo' => $advisory_info,
            'agencyInfo' => $agency_info,
            'canalInfo' => $canal_info,
        );

        // 获取需要查询的userIds
        $userIds = array($deal['user_id']);
        // $userId 是为借款人，可能为空，则不能校验userId必须不为空
        if(!empty($userId) && !in_array($userId, $userIds)){
            $userIds[] = $userId;
        }
        if(!empty($entrust_agency_info['user_id']) && !in_array($entrust_agency_info['user_id'], $userIds)){
            $userIds[] = $entrust_agency_info['user_id'];
        }
        if(!empty($agency_info['user_id']) && !in_array($agency_info['user_id'], $userIds)){
            $userIds[] = $agency_info['user_id'];
        }
        if(!empty($canal_info['user_id']) && !in_array($canal_info['user_id'], $userIds)){
            $userIds[] = $canal_info['user_id'];
        }
        if(!empty($platform_info['user_id']) && !in_array($platform_info['user_id'], $userIds)){
            $userIds[] = $platform_info['user_id'];
        }
        if(!empty($platform_info['agency_user_id']) && !in_array($platform_info['user_id'], $userIds)){
            $userIds[] = $platform_info['agency_user_id'];
        }
        if(!empty($advisory_info['user_id']) && !in_array($advisory_info['user_id'], $userIds)){
            $userIds[] = $advisory_info['user_id'];
        }

        $userInfos = UserService::getUserInfoForContractByUserId($userIds);
        // 把id重新放到user中
        foreach($userInfos as $k => $v){
            $userInfos[$k]['user']['id'] = $k;
        }

        // 构造合同需要的用户信息
        $userInfos[$userId] = isset($userInfos[$userId]) ? $userInfos[$userId] : array();
        $userInfos[$entrust_agency_info['user_id']] = isset($userInfos[$entrust_agency_info['user_id']]) ? $userInfos[$entrust_agency_info['user_id']] : array();
        $userInfos[$platform_info['user_id']] = isset($userInfos[$platform_info['user_id']]) ? $userInfos[$platform_info['user_id']] : array();
        $userInfos[$platform_info['agency_user_id']] = isset($userInfos[$platform_info['agency_user_id']]) ? $userInfos[$platform_info['agency_user_id']] : array();
        $userInfos[$advisory_info['user_id']] = isset($userInfos[$advisory_info['user_id']]) ? $userInfos[$advisory_info['user_id']] : array();
        $userInfos[$agency_info['user_id']] = isset($userInfos[$agency_info['user_id']]) ? $userInfos[$agency_info['user_id']] : array();
        $userInfos[$canal_info['user_id']] = isset($userInfos[$canal_info['user_id']]) ? $userInfos[$canal_info['user_id']] : array();
        $userInfos[$deal['user_id']] = isset($userInfos[$deal['user_id']]) ? $userInfos[$deal['user_id']] : array();
        $loanInfo = self::getLoanInfo($userInfos[$userId]); // 甲方 - 借出方
        $entrustInfo = self::getAgencyInfo($entrust_agency_info,$userInfos[$entrust_agency_info['user_id']]);  // 委托方
        $borrowInfo = self::getBorrowInfo($userInfos[$deal['user_id']]);
        $platformInfo = self::getPlatformInfo($platform_info, $userInfos[$platform_info['user_id']], $userInfos[$platform_info['agency_user_id']]); // 丙方 - 平台方
        $advisoryInfo = self::getAdvisoryInfo($advisory_info,$userInfos[$advisory_info['user_id']]); // 丁方 - 资产管理方
        $agencyInfo = self::getAgencyInfo($agency_info,$userInfos[$agency_info['user_id']]); // 戊方 - 保证方
        $canalInfo = self::getAgencyInfo($canal_info,$userInfos[$canal_info['user_id']]); // 渠道方
        $borrowUserInfoNotice = self::getDealUserCompanyInfo($deal,$userInfos[$deal['user_id']]);
        $dealUserInfos = array(
            'userInfos' => $userInfos,
            'agencyInfos' => $agencyInfos,
            'entrustInfo' => $entrustInfo,
            'platformInfo' => $platformInfo,
            'advisoryInfo' => $advisoryInfo,
            'agencyInfo' => $agencyInfo,
            'canalInfo' => $canalInfo,
            'loanInfo' => $loanInfo,
            'borrowInfo' => $borrowInfo,
            'borrowUserInfoNotice' => $borrowUserInfoNotice,
        );
        return $dealUserInfos;
    }

    /**
     * 获取渲染合同模板的变量
     * 此方法汇总了所有的模板变量，所以任何模板获取变量 只需调用此方法即可
     * @param int $deal_id
     * @param int $user_id
     * @param float $money 准备投或者已投的金额
     * @param array $contract_info 合同信息 对应合同库的 contract_* 表 为空，则代表投资之前的预览
     * @param int $service_type 服务类型 1:标的；2:项目
     * @return array 合同模板变量集合
     */
    static public function getNoticeInfo($deal_id, $user_id = 0, $money = 0, $contract_info = array(), $service_type = 1)
    {
        $deal = self::get_deal_info($deal_id);
        if(empty($deal)){
            return false;
        }

        $dealUserInfos = self::getDealUserInfos($deal,$user_id);

        $user_info = isset($dealUserInfos['userInfos'][$user_id]['user']) ? $dealUserInfos['userInfos'][$user_id]['user'] : array();
        $loanInfo = $dealUserInfos['loanInfo']; // 甲方 - 借出方
        $entrustInfo = $dealUserInfos['entrustInfo'];  // 委托方
        $borrowInfo = $dealUserInfos['borrowInfo']; // 乙方 - 借款方
        $platformInfo = $dealUserInfos['platformInfo']; // 丙方 - 平台方
        $advisoryInfo = $dealUserInfos['advisoryInfo']; // 丁方 - 资产管理方
        $agencyInfo =$dealUserInfos['agencyInfo']; // 戊方 - 保证方
        $canalInfo = $dealUserInfos['canalInfo']; // 渠道方
        $borrow_user_info = $dealUserInfos['borrowUserInfoNotice']; // 乙方 - 借款方
        $borrow_bank_info =  $dealUserInfos['userInfos'][$deal['user_id']]['card']; // 乙方 - 借款方  - 银行卡
        $loan_bank_info = isset($dealUserInfos['userInfos'][$user_id]['card']) ? $dealUserInfos['userInfos'][$user_id]['card'] : '';
        $loan_legal_person = isset($dealUserInfos['userInfos'][$user_id]['company']) ? $dealUserInfos['userInfos'][$user_id]['company'] : '';
        if (empty($contract_info)) {
            $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
            $notice['number'] = '[]';

            if($deal['deal_type'] == 0){
                // 借款方
                $notice['borrow_name'] = '***出借成功后才可查看';
                $notice['borrow_user_number'] = "***出借成功后才可查看";
                $notice['borrow_license'] = '***出借成功后才可查看';
                $notice['borrow_agency_realname'] = '***出借成功后才可查看';
                $notice['borrow_agency_idno'] = '***出借成功后才可查看';
                $notice['company_license'] = '***出借成功后才可查看';
                $notice['company_address_current'] = "***出借成功后才可查看";

                // 委托方
                $notice['entrust_name'] = '***出借成功后才可查看';
                $notice['entrust_license'] = '***出借成功后才可查看';
                $notice['entrust_agent_real_name'] = '***出借成功后才可查看';
                $notice['entrust_agent_user_number'] = '***出借成功后才可查看';
                $notice['entrust_agent_user_idno'] = '***出借成功后才可查看';
                $notice['canal_agent_user_idno'] = '***出借成功后才可查看';
            }else{
                // 借款方
                $notice['borrow_name'] = '***投资成功后才可查看';
                $notice['borrow_user_number'] = "***投资成功后才可查看";
                $notice['borrow_license'] = '***投资成功后才可查看';
                $notice['borrow_agency_realname'] = '***投资成功后才可查看';
                $notice['borrow_agency_idno'] = '***投资成功后才可查看';
                $notice['company_license'] = '***投资成功后才可查看';
                $notice['company_address_current'] = "***投资成功后才可查看";

                // 委托方
                $notice['entrust_name'] = '***投资成功后才可查看';
                $notice['entrust_license'] = '***投资成功后才可查看';
                $notice['entrust_agent_real_name'] = '***投资成功后才可查看';
                $notice['entrust_agent_user_number'] = '***投资成功后才可查看';
                $notice['entrust_agent_user_idno'] = '***投资成功后才可查看';
                $notice['canal_agent_user_idno'] = '***投资成功后才可查看';
            }

            $notice['start_time'] = '[]';
            $notice['end_time'] = '[]';
            $notice['borrow_sign_time'] = '合同签署之日';
            $notice['advisory_sign_time'] = '合同签署之日';
            $notice['agency_sign_time'] = '合同签署之日';
            $notice['entrust_sign_time'] = '合同签署之日';
            $notice['canal_sign_time'] = '合同签署之日';

            // 通知贷 合同编号
            $notice['loan_contract_num'] = '[]';
        } else {
            if (ContractServiceEnum::SERVICE_TYPE_PROJECT == $service_type) {
                $money = $deal['project_info']['borrow_amount'];
            } else {
                $loan_user_info = DealLoadModel::instance()->getLoadDetailInfo($deal_id, $contract_info['dealLoadId']);
                $money = $loan_user_info['loan_money'];
                $notice['sign_time'] = to_date($loan_user_info['jia_sign_time'], "Y年m月d日");
                // 通知贷 合同编号
                $notice['loan_contract_num'] = self::create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1);
            }

            $notice['number'] = $contract_info['number'];

            // 借款方
            $notice['borrow_name']                 = $borrowInfo['borrow_name'];
            $notice['borrow_user_number']          = $borrowInfo['borrow_user_number']; // 会员编号
            $notice['borrow_license']              = $borrowInfo['borrow_license']; // 营业执照号
            $notice['borrow_agency_realname']      = $borrowInfo['borrow_agency_realname']; // 代理人姓名
            $notice['borrow_agency_idno']          = $borrowInfo['borrow_agency_idno']; // 代理人证件号
            $notice['company_license']             = $borrowInfo['borrow_license'];

            // 委托方
            $notice['entrust_name'] = $entrustInfo['agency_name'];
            $notice['entrust_license'] = $entrustInfo['agency_license'];
            $notice['entrust_agent_real_name'] = $entrustInfo['agency_agent_real_name'];
            $notice['entrust_agent_user_number'] = $entrustInfo['agency_agent_user_number'];
            $notice['entrust_agent_user_idno'] = $entrustInfo['agency_agent_user_idno'];

            //合同起始时间
            $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : $contract_info['createTime'];
            $notice['start_time'] = date("Y-m-d",$contract_start_time);
            $notice['end_time'] = ($deal['loantype'] == 5) ? date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time)) : date("Y-m-d",strtotime("+".$deal['repay_time']." month",  $contract_start_time));

            // 获取各角色的签署时间
            list($notice['borrow_sign_time'], $notice['advisory_sign_time'], $notice['agency_sign_time'], $notice['entrust_sign_time']) = self::getRoleSignTime($contract_info);
            if(empty($notice['sign_time'])){
                $notice['sign_time'] = $notice['borrow_sign_time'];
            }
        }

        //********************************受托支付方*******************************************
        $dealProject = DealProjectModel::instance()->findViaSlave($deal['project_id']);
        $notice['entrust_loan_name'] = $dealProject['card_name'] ;
        $notice['entrust_loan_bank_card'] = \libs\utils\DBDes::decryptOneValue($dealProject['bankcard']);
        $notice['entrust_loan_bankzone'] = $dealProject['bankzone'];

        // 渠道方
        $notice['canal_name'] = $canalInfo['agency_name'];
        $notice['canal_license'] = $canalInfo['agency_license'];
        $notice['canal_agent_real_name'] = $canalInfo['agency_agent_real_name'];
        $notice['canal_agent_user_number'] = $canalInfo['agency_agent_user_number'];
        $notice['canal_agent_user_idno'] = $canalInfo['agency_agent_user_idno'];

        $notice['consult_fee_period_rate_year'] = format_rate_for_cont(bcmul($deal['consult_fee_period_rate'],12,8));
        $notice['loan_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time']));
        $notice['manage_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['manage_fee_rate'], $deal['repay_time']));
        $notice['manage_fee_rate_part_prepayment'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['prepay_manage_fee_rate'], $deal['repay_time']));
        $notice['overdue_compensation_time'] = $deal['overdue_day'];

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $money);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $money;

        $deal_ext_model = new DealExtModel;
        $dealExtra = DealExtraModel::instance()->findByViaSlave(" `deal_id` = ".intval($deal['id']));
        $deal_ext = $deal_ext_model->getDealExtByDealId($deal['id']);
        $base_deal_num = $deal_ext['leasing_contract_num']?$deal_ext['leasing_contract_num']:'';
        $lessee_real_name = $deal_ext['lessee_real_name']?$deal_ext['lessee_real_name']:'';
        $leasing_contract_title = $deal_ext['leasing_contract_title'];

        switch($deal_ext['loan_application_type']){
            case '1':
                $loan_application_type = '企业经营';
                break;
            case '2':
                $loan_application_type = '短期周转';
                break;
            case '3':
                $loan_application_type = '日常消费';
                break;
            default: $loan_application_type = '其他';
        }
        $use_info = empty($deal_ext['use_info'])?$loan_application_type:$deal_ext['use_info'];

        switch($deal_ext['contract_transfer_type']){
            case '1':
                $contract_transfer_type = '债权';
                break;
            case '2':
                $contract_transfer_type = '资产收益权';
                break;
            default: $contract_transfer_type = '';
        }

        switch($deal_ext['loan_fee_rate_type']){
            case '1':
            case '5':
                $loan_fee_rate_type = 'A';
                break;
            case '2':
            case '6':
                $loan_fee_rate_type = 'B';
                break;
            case '3':
            case '4':
            case '7':
                $loan_fee_rate_type = 'C';
                break;
            default: $loan_fee_rate_type = '';
        }
        // 为了兼容新老合同模板，需要新加个变量consult_fee_rate_type_new
        $consult_fee_rate_type =  $consult_fee_rate_type_new = '';
        switch($deal_ext['consult_fee_rate_type']){
            case '1':
                $consult_fee_rate_type_new = $consult_fee_rate_type = 'A';
                // 咨询费为1-前收，并且分期费率大于0，则合同中为D-混合收取咨询费率
                if($deal['consult_fee_period_rate'] > 0 ){
                    $consult_fee_rate_type_new = 'D';
                }
                break;
            case '2':
                $consult_fee_rate_type_new = $consult_fee_rate_type = 'B';
                break;
            case '3':
                $consult_fee_rate_type_new = $consult_fee_rate_type = 'C';
                break;
            default: $consult_fee_rate_type_new = $consult_fee_rate_type = '';
        }

        switch($deal_ext['guarantee_fee_rate_type']){
            case '1':
                $guarantee_fee_rate_type = 'A';
                break;
            case '2':
                $guarantee_fee_rate_type = 'B';
                break;
            case '3':
                $guarantee_fee_rate_type = 'C';
                break;
            default: $guarantee_fee_rate_type = '';
        }

        switch($deal_ext['pay_fee_rate_type']){
            case '1':
                $pay_fee_rate_type = 'A';
                break;
            case '2':
                $pay_fee_rate_type = 'B';
                break;
            case '3':
                $pay_fee_rate_type = 'C';
                break;
            default: $pay_fee_rate_type = '';
        }

        switch($deal_ext['canal_fee_rate_type']){
            case '1':
                $canal_fee_rate_type = 'A';
                break;
            case '2':
                $canal_fee_rate_type = 'B';
                break;
            case '3':
                $canal_fee_rate_type = 'C';
                break;
            default: $canal_fee_rate_type = '';
        }

        if ($deal['loantype'] == 5) {
            $deal_repay_time = $deal['repay_time'] . "天";
        } else {
            $deal_repay_time = $deal['repay_time'] . "个月";
        }
        // JIRA#WXPH-208 约定还款日，节假日相关
        $notice['holiday_repay_type'] = isset(DealEnum::$HOLIDAY_REPAY_TYPE_CONTRACT[$deal['holiday_repay_type']]) ? DealEnum::$HOLIDAY_REPAY_TYPE_CONTRACT[$deal['holiday_repay_type']] : '/';
        // 追债相关
        $notice['recourse_user'] = '/';
        $notice['recourse_time'] = '/';
        $notice['recourse_type'] = '/';
        $notice['lawsuit_address'] = '/';
        $notice['arbitrate_address'] = '/';
        if(!empty($dealExtra)){
            $notice['recourse_user'] = !empty($dealExtra['recourse_user']) ? $dealExtra['recourse_user'] : '/';
            $notice['recourse_time'] = !empty($dealExtra['recourse_time']) ? $dealExtra['recourse_time'] : '/';
            $notice['recourse_type'] = isset(DealExtraEnum::$RECOURSE_TYPE_CONTRACT[$dealExtra['recourse_type']]) ? DealExtraEnum::$RECOURSE_TYPE_CONTRACT[$dealExtra['recourse_type']] : '/';
            $notice['lawsuit_address'] = !empty($dealExtra['lawsuit_address']) ? $dealExtra['lawsuit_address']: '/';
            $notice['arbitrate_address'] = !empty($dealExtra['arbitrate_address']) ? $dealExtra['arbitrate_address']: '/';
        }
        // JIRA#3260-企业账户二期 <fanjingwen@>
        // 其他模板参数
        $loan_type_mark = self::getLoanTypeMark($deal['loantype']);
        $repay_time_unit = self::getRepayTimeUnit($deal['type_id'], $deal['loantype'], $deal['repay_time'], $deal_ext['first_repay_interest_day']);

        // ---------------- 甲方 - 借出方 ----------------
        $notice['loan_name_info'] = $loanInfo['loan_name_info'];
        $notice['loan_username_info'] = $loanInfo['loan_username_info'];
        $notice['loan_credentials_info'] = $loanInfo['loan_credentials_info'];
        $notice['loan_bank_user_info'] = $loanInfo['loan_bank_user_info'];
        $notice['loan_bank_no_info'] = $loanInfo['loan_bank_no_info'];
        $notice['loan_bank_name_info'] = $loanInfo['loan_bank_name_info'];
        $notice['loan_name_info_transfer'] = $loanInfo['loan_name_info_transfer'];
        $notice['loan_username_info_transfer'] = $loanInfo['loan_username_info_transfer'];
        $notice['loan_credentials_info_transfer'] = $loanInfo['loan_credentials_info_transfer'];
        $notice['loan_bank_user_info_transfer'] = $loanInfo['loan_bank_user_info_transfer'];
        $notice['loan_bank_no_info_transfer'] = $loanInfo['loan_bank_no_info_transfer'];
        $notice['loan_bank_name_info_transfer'] = $loanInfo['loan_bank_name_info_transfer'];
        $notice['loan_major_name'] = $loanInfo['loan_major_name'];
        $notice['loan_major_condentials_no'] = $loanInfo['loan_major_condentials_no'];
        $notice['loan_user_number'] = $loanInfo['loan_user_number'];

        // ---------------- 丙方 - 平台方 ----------------
        $notice['platform_show_name'] = '网信理财';
        $notice['platform_domain'] = 'www.firstp2p.com';
        $notice['platform_realname'] = $platformInfo['platform_realname'];
        $notice['platform_address'] = $platformInfo['platform_address'];
        // 合同
        $notice['platform_name'] = $platformInfo['platform_name'];
        $notice['platform_agency_user_number'] = $platformInfo['platform_agency_user_number'];
        $notice['platform_license'] = $platformInfo['platform_license'];
        $notice['platform_agency_realname'] = $platformInfo['platform_agency_realname'];
        $notice['platform_agency_idno'] = $platformInfo['platform_agency_idno'];
        $notice['platform_agency_username'] = $platformInfo['platform_agency_username'];

        // ---------------- 丁方 - 资产管理方 ----------------
        $notice['advisory_name'] = $advisoryInfo['advisory_name'];
        $notice['advisory_agent_user_number'] = $advisoryInfo['advisory_agent_user_number'];
        $notice['advisory_license'] = $advisoryInfo['advisory_license'];
        $notice['advisory_agent_real_name'] = $advisoryInfo['advisory_agent_real_name'];
        $notice['advisory_agent_user_idno'] = $advisoryInfo['advisory_agent_user_idno'];
        $notice['advisory_address'] = $advisoryInfo['advisory_address'];
        $notice['advisory_realname'] = $advisoryInfo['advisory_realname'];
        $notice['advisory_agent_user_name'] = $advisoryInfo['advisory_agent_user_name'];

        // ---------------- 戊方 - 保证方 ----------------
        $notice['agency_name'] = $agencyInfo['agency_name'];
        $notice['agency_agent_user_number'] = $agencyInfo['agency_agent_user_number'];
        $notice['agency_license'] = $agencyInfo['agency_license'];
        $notice['agency_agent_real_name'] = $agencyInfo['agency_agent_real_name'];
        $notice['agency_agent_user_idno'] = $agencyInfo['agency_agent_user_idno'];
        // other
        $notice['agency_agent_user_name'] = $agencyInfo['agency_agent_user_name'];
        $notice['agency_user_realname'] = $agencyInfo['agency_user_realname'];
        $notice['agency_address'] = $agencyInfo['agency_address'];
        $notice['agency_mobile'] = $agencyInfo['agency_mobile'];
        $notice['agency_postcode'] = $agencyInfo['agency_postcode'];
        $notice['agency_fax'] = $agencyInfo['agency_fax'];
        $notice['agency_platform_realname'] = $agencyInfo['agency_platform_realname'];
        // ---------------- over - 保证方 -------------------

        //----------------- 受托方-----------------------
        $notice['entrust_address'] = $entrustInfo['agency_address'];
        $notice['entrust_agent_user_name'] = $entrustInfo['agency_agent_user_name'];
        $notice['entrust_user_realname'] = $entrustInfo['agency_user_realname'];
        $notice['entrust_mobile'] = $entrustInfo['agency_mobile'];
        $notice['entrust_postcode'] = $entrustInfo['agency_postcode'];
        $notice['entrust_fax'] = $entrustInfo['agency_fax'];
        $notice['entrust_platform_realname'] = $entrustInfo['agency_platform_realname'];

        //----------------- 渠道方-----------------------
        $notice['canal_address'] = $canalInfo['agency_address'];
        $notice['canal_user_number'] = $canalInfo['agency_agent_user_number'];
        $notice['canal_agent_user_name'] = $canalInfo['agency_agent_user_name'];
        $notice['canal_user_realname'] = $canalInfo['agency_user_realname'];
        $notice['canal_agency_agent_user_idno'] = $canalInfo['agency_agent_user_idno'];
        $notice['canal_mobile'] = $canalInfo['agency_mobile'];
        $notice['canal_postcode'] = $canalInfo['agency_postcode'];
        $notice['canal_fax'] = $canalInfo['agency_fax'];
        $notice['canal_platform_realname'] = $canalInfo['agency_platform_realname'];

        $notice['contract_transfer_type'] = $contract_transfer_type;

        $notice['base_deal_num'] = $base_deal_num;
        $notice['lessee_real_name'] = $lessee_real_name;
        $notice['loan_application_type'] = $loan_application_type;
        $notice['loan_type_mark'] = $loan_type_mark;
        $notice['use_info'] = $use_info;

        $notice['loan_fee_rate_type'] = $loan_fee_rate_type;
        $notice['consult_fee_rate_type'] = $consult_fee_rate_type;
        $notice['consult_fee_rate_type_new'] = $consult_fee_rate_type_new;
        $notice['guarantee_fee_rate_type'] = $guarantee_fee_rate_type;
        $notice['pay_fee_rate_type'] = $pay_fee_rate_type;
        $notice['canal_fee_rate_type'] = $canal_fee_rate_type;
        $notice['leasing_contract_title'] = $leasing_contract_title;


        $notice['loan_user_name'] =  isset($user_info['user_name']) ? $user_info['user_name'] : '';
        $notice['loan_bank_user'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
        $notice['loan_bank_card'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
        $notice['loan_bank_name'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        $notice['loan_real_name'] = isset($user_info['real_name']) ? $user_info['real_name'] : '';
        $notice['loan_user_idno'] = isset($user_info['idno']) ? $user_info['idno'] : '';
        $notice['loan_address'] = isset($user_info['address']) ? $user_info['address'] : '';
        $notice['loan_legal_person'] = isset($loan_legal_person['legal_person']) ? $loan_legal_person['legal_person'] : ''; //合同模板中没有使用该变量
        $notice['loan_phone'] = isset($user_info['mobile']) ? $user_info['mobile'] : '';
        $notice['loan_email'] = isset($user_info['email']) ? $user_info['email'] : '';

        $notice['loan_money'] = $money;
        $notice['loan_money_uppercase'] = get_amount($money);
        $notice['loan_money_repay'] = $loan_money_repay;
        $notice['loan_money_repay_uppercase'] = get_amount($loan_money_repay);
        $notice['loan_money_earning'] = $loan_money_earning_format;
        $notice['loan_money_earning_uppercase'] = get_amount($loan_money_earning_format);
        $notice['loan_user_mobile'] = $user_info['mobile'];

        $notice['repay_time'] = $deal['repay_time'];
        $notice['deal_repay_time'] = $deal_repay_time;
        $notice['repay_time_unit'] = $repay_time_unit;


        $notice['rate'] = format_rate_for_cont($deal['int_rate']);
        $notice['loan_fee_rate'] = format_rate_for_cont($deal['loan_fee_rate']);
        $notice['loan_fee_rate_annual'] = DealService::isDealFeeRateTypeFixed($deal_ext['loan_fee_rate_type']) ? format_rate_for_cont($deal['loan_fee_rate']) : '年化'. format_rate_for_cont($deal['loan_fee_rate']); // 年化收益率
        $notice['guarantee_fee_rate'] = format_rate_for_cont($deal['guarantee_fee_rate']);
        $notice['pay_fee_rate'] = format_rate_for_cont($deal['pay_fee_rate']);
        $notice['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];

        $notice['leasing_money'] = format_rate_for_cont($deal['borrow_amount']);
        $notice['leasing_money_uppercase'] = get_amount($deal['borrow_amount']);

        $notice['borrow_bank_user'] = $borrow_bank_info['card_name'];
        $notice['borrow_bank_card'] = $borrow_bank_info['bankcard'];
        $notice['borrow_bank_name'] = $borrow_bank_info['bankname'];

        $notice['house_address'] = $deal['house_address'];
        $notice['house_sn'] = $deal['house_sn'];

        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];

        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));

        $notice['borrow_money'] = $deal['borrow_amount'];
        $notice['uppercase_borrow_money'] = get_amount($deal['borrow_amount']);
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);

        $notice['consult_fee_rate'] = format_rate_for_cont($deal['consult_fee_rate']);
        $notice['consult_fee_rate_part'] = format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time']));

        $notice['consulting_company_name'] = $advisoryInfo['advisory_name'];
        $notice['consulting_company_address'] = $advisoryInfo['advisory_address'];
        $notice['consulting_company_tel'] = $advisoryInfo['advisory_mobile'];
        $notice['consulting_company_bank_user'] = $advisoryInfo['advisory_card_name'];
        $notice['consulting_company_bank_card'] = $advisoryInfo['advisory_bankcard'];
        $notice['consulting_company_bank_name'] = $advisoryInfo['advisory_bankzone'];
        $notice['consulting_company_agent_real_name'] = $advisoryInfo['advisory_agent_real_name'];
        $notice['consulting_company_agent_user_name'] = $advisoryInfo['advisory_agent_user_name'];
        $notice['consulting_company_agent_user_idno'] = $advisoryInfo['advisory_agent_user_idno'];

        $notice['prepayment_day_restrict'] = $deal['prepay_days_limit'];
        $notice['prepayment_penalty_ratio'] = format_rate_for_cont($deal['prepay_rate']);
        $notice['overdue_break_days'] = $deal['overdue_break_days'];

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

        $notice['prepay_penalty_days'] = $deal['prepay_penalty_days'];//提前还款罚息天数
        $notice['redemption_period'] = isset($deal['redemption_period']) ? $deal['redemption_period'] : '';
        $notice['lock_period'] = isset($deal['lock_period']) ? $deal['lock_period'] : '';
        $notice['rate_day'] = isset($deal['rate_day']) ? format_rate_for_db($deal['rate_day']) : '';

        $notice['overdue_ratio'] = format_rate_for_cont($deal['overdue_rate']);

        $notice['first_repay_interest_day'] = to_date($deal_ext['first_repay_interest_day'], "Y年m月d日"); // 第一期还款日
        $notice['min_loan_money'] = $deal['min_loan_money'];
        $notice['min_loan_money_uppercase'] = get_amount($deal['min_loan_money']);
        $notice['project_borrow_amount'] = intval($deal['project_info']['borrow_amount']);
        $notice['project_borrow_amount_uppercase'] = get_amount($deal['project_info']['borrow_amount']);
        $notice['manage_fee_rate'] = format_rate_for_cont($deal['manage_fee_rate']);
        $notice['manage_fee_text'] = $deal['manage_fee_text'];

        $notice['canal_fee_rate'] = format_rate_for_cont($deal['canal_fee_rate']);


        $notice['assets_desc'] = $deal['project_info']['assets_desc'];
        $notice['jys_record_number'] = $deal['jys_record_number'];

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息
        $notice['borrow_user_idno'] = idnoFormat($notice['borrow_user_idno']); // 代理人证件号
        if(empty($contract_info)){
            $hideLenRealName = mb_strlen($notice['borrow_real_name']) - 1;
            $notice['borrow_real_name'] = mb_substr($notice['borrow_real_name'], 0, 1) . str_repeat("*", $hideLenRealName);
            $hideLenEnterpriseName = mb_strlen($borrowInfo['borrow_name']) - 4;
            // 如果不足4个字 不脱敏
            $hideLenEnterpriseName = $hideLenEnterpriseName > 0 ? $hideLenEnterpriseName : 0;
            $notice['borrow_name'] = str_repeat("*", $hideLenEnterpriseName).mb_substr($borrowInfo['borrow_name'], $hideLenEnterpriseName, 4);
        }else{
            // 对个人账户的身份证号进行脱敏，会在打戳合同中。产品说不会影响合同的法律效力
            if(empty($dealUserInfos['userInfos'][$user_id]['enterprise'])) {
                $notice['loan_user_idno'] = idnoFormat($notice['loan_user_idno']);
                $notice['loan_credentials_info'] = idnoFormat($notice['loan_credentials_info']);
            }
        }
        return $notice;
    }

    /**
     * 获取除甲方之外，其他角色的签署时间
     */
    static private function getRoleSignTime($contract_info)
    {
        if($contract_info['borrowerSignTime'] > 0){
            $borrow_sign_time = "<span id='borrow_sign_time'>".date('Y年m月d日',$contract_info['borrowerSignTime'])."</span>";
        }else{
            $borrow_sign_time = "<span id='borrow_sign_time'>合同签署之日</span>";
        }


        if($contract_info['advisorySignTime'] > 0){
            $advisory_sign_time = "<span id='borrow_sign_time'>".date('Y年m月d日',$contract_info['advisorySignTime'])."</span>";
        }else{
            $advisory_sign_time = "<span id='advisory_sign_time'>合同签署之日</span>";
        }


        if($contract_info['agencySignTime'] > 0){
            $agency_sign_time = "<span id='borrow_sign_time'>".date('Y年m月d日',$contract_info['agencySignTime'])."</span>";
        }else{
            $agency_sign_time = "<span id='agency_sign_time'>合同签署之日</span>";

        }



        if($contract_info['entrustAgencySignTime'] > 0){
            $entrust_sign_time = "<span id='entrust_sign_time'>".date('Y年m月d日',$contract_info['entrustAgencySignTime'])."</span>";
        }else{
            $entrust_sign_time = "<span id='entrust_sign_time'>合同签署之日</span>";
        }


        return array($borrow_sign_time, $advisory_sign_time, $agency_sign_time, $entrust_sign_time);
    }

    static private function get_user_data($user_id)
    {
        $user_id = intval($user_id);
        if($user_id <= 0){
            return array();
        }
        static $user_info = array();
        if(!isset($user_info[$user_id])){
            $user_info[$user_id] = UserService::getUserById($user_id,'id,user_name,real_name,idno');
        }
        return $user_info[$user_id];
    }

    static private function get_deal_info($deal_id)
    {
        $deal_id = intval($deal_id);
        if($deal_id <= 0){
            return array();
        }
        static $deal_info = array();
        if(!isset($deal_info[$deal_id])){
            $deal_service = new DealService();
            $deal_project_model = new DealProjectModel();
            $deal_info[$deal_id] = $deal_service->getDeal($deal_id, true);
            $deal_info[$deal_id]['project_info'] = $deal_project_model->find($deal_info[$deal_id]['project_id']);
        }
        return $deal_info[$deal_id];
    }

    /**
     * 旧版通知贷合同 生成合同编号 [兼容老逻辑，新的合同编号生成，不要用这个方法]
     */
    static private function create_deal_number($deal, $user_id, $load_id, $type=NULL)
    {
        $load_id = str_replace(",", "", $load_id);
        //判断子母单和普通单的情况
        if ($deal['parent_id'] == -1){
            return str_pad($deal['id'],6,"0",STR_PAD_LEFT).'01'.str_pad($type,2,"0",STR_PAD_LEFT).str_pad($user_id,8,"0",STR_PAD_LEFT).str_pad($load_id,10,"0",STR_PAD_LEFT);
        }elseif ($deal['parent_id'] > 0){
            return str_pad($deal['id'].'02'.$deal['parent_id'].$type.$user_id.$load_id,16,"0",STR_PAD_LEFT);
        }else {
            return str_pad($deal['id'].'03'.$type.$user_id.$load_id,16,"0",STR_PAD_LEFT);
        }
    }

    /**
     * 获取前置合同的合同变量(用于渲染前置合同)
     * @param array $contract  contract_before_borrow表的数据
     * @return array $notice
     */
    static public function getBeforeBorrowNotice($contract){
        $notice = array(
            'number'                          => '',
            'loan_name_info'                  => '',
            'loan_user_number'                => '',
            'loan_credentials_info'           => '',
            'borrow_real_name'                => '',
            'borrow_user_number'              => '',
            'borrow_user_idno'                => '',
            'advisory_name'                   => '',
            'advisory_agent_user_number'      => '',
            'advisory_license'                => '',
            'agency_name'                     => '',
            'agency_agent_user_number'        => '',
            'agency_license'                  => '',
            'loan_money'                      => '',
            'loan_money_uppercase'            => '',
            'use_info'                        => '',
            'rate'                            => '',
            'loan_type_mark'                  => '',
            'prepayment_day_restrict'         => '',
            'prepayment_penalty_ratio'        => '',
            'guarantee_fee_rate'              => '',
            'guarantee_fee_rate_type'         => '',
            'sign_time'                       => '',
            'advisory_sign_time'              => '',
            'agency_sign_time'                => '',
            'loan_fee_rate_annual'            => '',
            'loan_fee_rate_type'              => '',
            'consult_fee_rate'                => '',
            'consult_fee_period_rate_year'    => '',
            'borrow_agency_realname'    => '',  // 前置合同变量
            'borrow_sign_time'    => '', // 前置合同变量
            'borrow_agency_idno'    => '',  // 前置合同变量
            'uppercase_borrow_money'    => '', // 前置合同变量
            'entrust_loan_name'    => '', // 前置合同变量
            'entrust_loan_bank_card'    => '', // 前置合同变量
            'entrust_loan_bankzone'    => '', // 前置合同变量
            'repay_time_unit' => '', // 前置合同变量
        ); // 这些为空的默认值，为了使渲染后的合同模板中不出现变量名
        if(empty($contract)){
            return $notice;
        }
        $borrowUserId = $contract['borrowUserId'];
        $params = json_decode($contract['params'], true);
        $userInfos = UserService::getUserInfoForContractByUserId($borrowUserId);
        $userInfos[$borrowUserId]['user']['id'] = $borrowUserId;
        $borrowInfo = self::getBorrowInfo($userInfos[$borrowUserId]);
        // **********************************借款人***********************************
        $notice['borrow_name']                 = $borrowInfo['borrow_name'];
        $notice['borrow_user_number']          = $borrowInfo['borrow_user_number']; // 会员编号
        $notice['borrow_license']              = $borrowInfo['borrow_license']; // 营业执照号
        $notice['borrow_agency_realname']      = $borrowInfo['borrow_agency_realname']; // 代理人姓名
        $notice['borrow_agency_idno']          = $borrowInfo['borrow_agency_idno']; // 代理人证件号
        $notice['company_license']             = $borrowInfo['borrow_license'];
        $notice['borrow_real_name'] = isset($userInfos[$borrowUserId]['user']['real_name']) ? $userInfos[$borrowUserId]['user']['real_name'] : '';
        //borrow_real_name借款人真实姓名 合同模板有使用
        $notice['borrow_sign_time'] = !empty($contract['borrowerSignTime']) ?  date("Y年m月d日",$contract['borrowerSignTime']) : date("Y年m月d日",time());

        $borrow_bank_info = $userInfos[$borrowUserId]['card'];
        $notice['borrow_bank_user'] = $borrow_bank_info['card_name'];
        $notice['borrow_bank_card'] = $borrow_bank_info['bankcard'];
        $notice['borrow_bank_name'] = $borrow_bank_info['bankname'];

        // **********************************受托支付信息***********************************
        $notice['entrust_loan_name'] = $params['entrustName'] ;
        $notice['entrust_loan_bank_card'] = $params['loanBankCard'] ;
        $bank = BankService::getBranchInfoByBranchNo($params['bankZone']);
        $notice['entrust_loan_bankzone'] = $bank['name'];

        $notice['repay_time_unit'] = ($params['repayPeriodType'] == 2) ? $params['repayPeriod'] . '个月' : $params['repayPeriod'] . '天';
        $notice['borrow_money'] = $params['borrowAmount'];
        $notice['uppercase_borrow_money'] = get_amount($notice['borrow_money']);
        return $notice;
    }

}

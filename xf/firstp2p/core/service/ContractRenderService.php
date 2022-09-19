<?php
/**
 * [合同相关：提供合同模板渲染的方法]
 * @author <fanjingwen@>
 */

namespace core\service;

use core\dao\DealSiteModel;
use core\service\UserService;
use core\service\DealAgencyService;
use core\dao\UserCompanyModel;
use core\dao\DealLoanTypeModel;
use libs\utils\XDateTime;

/**
* [合同模板渲染]
*/
class ContractRenderService extends BaseService
{
    // ------------------------ get - 合同签署各方的信息 ------------------------
    /**
     * [获取甲方-出借方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param array $user_info [key-value:对应user表中字段]
     * @param array $loan_bank_info [key-value:对应user_bank_card表中字段]
     * @return array $loanInfo [see returnInfo]
     */
    public function getLoanInfo($user_info, $loan_bank_info)
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

        $loan_user_service = new UserService($user_info['id']);
        if($loan_user_service->isEnterprise()){
            $enterprise_info = $loan_user_service->getEnterpriseInfo(true);
            $loanInfo['loan_name_info'] = $enterprise_info['company_name'];
            $loanInfo['loan_username_info'] = $user_info['user_name'];
            $loanInfo['loan_credentials_info'] = $enterprise_info['credentials_no'];
            $loanInfo['loan_bank_user_info'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
            //转让合同
            $loanInfo['loan_name_info_transfer'] = $enterprise_info['company_name'];
            $loanInfo['loan_username_info_transfer'] = $user_info['user_name'];
            $loanInfo['loan_credentials_info_transfer'] = $enterprise_info['credentials_no'];
            $loanInfo['loan_bank_user_info_transfer'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info_transfer'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info_transfer'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            // JIRA#3260 企业账户二期 <fanjingwen@>
            $loanInfo['loan_major_name'] = '代理人：' . $enterprise_info['contact']['major_name']; // 企业账户负责人-姓名
            $loanInfo['loan_major_condentials_no'] = '代理人身份证件号：' . $enterprise_info['contact']['major_condentials_no']; // 企业账户负责人-证件号码
            $loanInfo['loan_user_number'] = numTo32Enterprise($user_info['id']);
        }else{
            //借款合同
            $loanInfo['loan_name_info'] = $user_info['real_name'];
            $loanInfo['loan_username_info'] = $user_info['user_name'];
            $loanInfo['loan_credentials_info'] = $user_info['idno'];
            $loanInfo['loan_bank_user_info'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            //转让合同
            $loanInfo['loan_name_info_transfer'] = $user_info['real_name'];
            $loanInfo['loan_username_info_transfer'] = $user_info['user_name'];
            $loanInfo['loan_credentials_info_transfer'] = $user_info['idno'];
            $loanInfo['loan_bank_user_info_transfer'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loanInfo['loan_bank_no_info_transfer'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loanInfo['loan_bank_name_info_transfer'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            // JIRA#3260 企业账户二期 <fanjingwen@>
            $loanInfo['loan_major_name'] = '';
            $loanInfo['loan_major_condentials_no'] = '';
            $loanInfo['loan_user_number'] = numTo32($user_info['id']);
        }

        return $loanInfo;
    }

    /**
     * [获取乙方-借款方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $$userID
     * @return array $borrowInfo [see returnInfo]
     */
    public function getBorrowInfo($userID)
    {
        // init returnInfo
        $borrowInfo = array(
            'borrow_name'                 => '',
            'borrow_user_number'          => '', // 会员编号
            'borrow_license'              => '', // 营业执照号
            'borrow_agency_realname'      => '', // 代理人姓名
            'borrow_agency_idno'          => '', // 代理人证件号
        );

        // 1、企业会员；2、以前的机构成员；
        $borrow_user_service = new UserService($userID);
        if ($borrow_user_service->isEnterprise()) {
            $enterprise_info = $borrow_user_service->getEnterpriseInfo(true);
            // 合同模板信息
            $borrowInfo['borrow_name'] = $enterprise_info['company_name'];
            $borrowInfo['borrow_user_number'] = numTo32Enterprise($enterprise_info['user_id']);
            $borrowInfo['borrow_license'] = $enterprise_info['credentials_no']; // 企业证件号码
            $borrowInfo['borrow_agency_realname'] = $enterprise_info['contact']['major_name']; // 企业负责人
            $borrowInfo['borrow_agency_idno'] = $enterprise_info['contact']['major_condentials_no'];
        } else {
            $user_info = $borrow_user_service->getUserViaSlave($userID);
            $company_info = UserCompanyModel::instance()->findByUserId($userID);
            // 合同模板信息
            $borrowInfo['borrow_name'] = $company_info['name'];
            $borrowInfo['borrow_user_number'] = numTo32($user_info['id']);
            $borrowInfo['borrow_license'] = $company_info['license']; // 关联公司的营业执照
            $borrowInfo['borrow_agency_realname'] = $user_info['real_name'];
            $borrowInfo['borrow_agency_idno'] = $user_info['idno'];
        }

        return $borrowInfo;
    }
    /**
     * [获取丙方-平台方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $$dealID
     * @return array $platformInfo [see returnInfo]
     */
    public function getPlatformInfo($dealID)
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
        $deal_site = new DealSiteModel();
        $deal_site_id = $deal_site->getSiteByDeal($dealID);
        $deal_site_id = 0 ? 1:$deal_site_id['site_id'];

        $dealagency_service = new DealAgencyService();
        $deal_agency = $dealagency_service->getDealAgencyBySiteId($deal_site_id);

        // 1、企业会员；2、以前的机构成员；
        $platform_user_service = new UserService($deal_agency['user_id']);
        if ($platform_user_service->isEnterprise()) {
            $enterprise_info = $platform_user_service->getEnterpriseInfo(true);
            $userInfo = $platform_user_service->getUserViaSlave($enterprise_info['user_id']); // 获取企业法人信息
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
            if ($deal_agency['agency_user_id'] > 0) {
                $platform_agency_user = $platform_user_service->getUserViaSlave($deal_agency['agency_user_id']);
            }
            // info
            $platformInfo['platform_realname'] = $deal_agency['realname'];
            $platformInfo['platform_address'] = $deal_agency['address'];
            // 合同模板信息
            $platformInfo['platform_name'] = $deal_agency['name'];
            $platformInfo['platform_agency_user_number'] = isset($platform_agency_user) ? numTo32($platform_agency_user['id']) : '';
            $platformInfo['platform_license'] = $deal_agency['license'];
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
    public function getAdvisoryInfo($advisoryID)
    {
        // init returnInfo
        $advisoryInfo = array(
            'advisory_name'                 => '',
            'advisory_agent_user_number'    => '',
            'advisory_license'              => '',
            'advisory_agent_real_name'      => '',
            'advisory_agent_user_idno'      => '',
            'advisory_address'              => '',
            'advisory_realname'             => '',
            'advisory_agent_user_name'      => '', // 代理人用户名
        );
        $dealagency_service = new DealAgencyService();
        $advisory_info = $dealagency_service->getDealAgency($advisoryID); // 资产管理信息
        $advisory_user_service = new UserService($advisory_info['user_id']);
        if ($advisory_user_service->isEnterprise()) {
            $enterprise_info = $advisory_user_service->getEnterpriseInfo(true);
            $userInfo = $advisory_user_service->getUserViaSlave($enterprise_info['user_id']); // 获取企业法人信息
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
            $advisoryInfo['advisory_realname'] = $advisory_info['realname'];
            $advisoryInfo['advisory_agent_user_name'] = $advisory_info['agency_agent_user_name'];
        }

        return $advisoryInfo;
    }

    /**
     * [获取戊方-保证方信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $agencyID
     * @return array $advisoryInfo [see returnInfo]
     */
    public function getAgencyInfo($agencyID)
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
        );
        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($agencyID);

        $user_service = new UserService();
        $agency_platform_user = $user_service->getUser($agency_info['user_id']);

        $agency_user_service = new UserService($agency_info['user_id']);
        if ($agency_user_service->isEnterprise()) {
            $enterprise_info = $agency_user_service->getEnterpriseInfo(true);
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

        return $agencyInfo;
    }
    // ------------------------ over - 合同签署各方的信息 ------------------------

    /**
     * get loan_type_mark for contract_content
     * @author <fanjingwen@ucf>
     * @param int $loan_type
     * @return string $loan_type_mark
     */
    public function getLoanTypeMark($loan_type)
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
    public function getRepayTimeUnit($type_id, $loan_type, $repay_time, $first_repay_interest_day)
    {
        $repay_time_unit = '';
        // 看借款类型
        $deal_type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId($type_id);
        if (DealLoanTypeModel::TYPE_XFFQ == $deal_type_tag) {
            $time_obj_old = XDateTime::valueOfTime($first_repay_interest_day);
            $time_obj_new = $time_obj_old->addMonth($repay_time);
            $repay_time_unit = to_date($time_obj_new->getTime(), "Y年m月d日");
        } else {
            $repay_time_unit = (5 == $loan_type) ? $repay_time . "天" : $repay_time . "个月";
        }

        return $repay_time_unit;
    }
}
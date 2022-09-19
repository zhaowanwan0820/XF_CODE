<?php
/**
 * [合同相关：提供合同模板渲染的方法]
 * @author <fanjingwen@>
 */

namespace core\service\contract;

use core\dao\DealSiteModel;
use core\dao\UserCompanyModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealExtModel;
use core\dao\DealContractModel;
use core\dao\DealProjectModel;
use core\dao\DealLoadModel;
use core\dao\BanklistModel;

use core\service\DealService;
use core\service\UserService;
use core\service\DealAgencyService;
use core\service\EarningService;
use core\service\UserCompanyService;

use libs\utils\XDateTime;
use libs\utils\Finance;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

\FP::import("libs.common.app");

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
    static public function getLoanInfo($user_info, $loan_bank_info)
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
    static public function getBorrowInfo($userID)
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
        $borrow_user_service = new UserService($userID);

        if ($borrow_user_service->isEnterprise()) {
            $enterprise_info = $borrow_user_service->getEnterpriseInfo(true);

            // 合同模板信息
            $borrowInfo['borrow_name'] = $enterprise_info['company_name'];
            $borrowInfo['borrow_user_number'] = numTo32Enterprise($enterprise_info['user_id']);
            $borrowInfo['borrow_license'] = $enterprise_info['credentials_no']; // 企业证件号码
            $borrowInfo['borrow_agency_realname'] = $enterprise_info['contact']['major_name']; // 企业负责人
            $borrowInfo['borrow_agency_idno'] = $enterprise_info['contact']['major_condentials_no'];
            $borrowInfo['borrow_legalbody_name'] = $enterprise_info['legalbody_name']; // 企业法人
            $borrowInfo['borrow_registration_address'] = self::get_regon_name($enterprise_info['registration_region']).$enterprise_info['registration_address'];//注册地址
        } else {
            $user_info = $borrow_user_service->getUserViaSlave($userID);
            $company_info = UserCompanyModel::instance()->findByUserId($userID);

            // 合同模板信息
            $borrowInfo['borrow_name'] = $company_info['name'];
            $borrowInfo['borrow_user_number'] = numTo32($user_info['id']);
            $borrowInfo['borrow_license'] = $company_info['license']; // 关联公司的营业执照
            $borrowInfo['borrow_agency_realname'] = $user_info['real_name'];
            $borrowInfo['borrow_agency_idno'] = $user_info['idno'];
            $borrowInfo['borrow_legalbody_name'] = $company_info['legal_person']; // 企业法人
            $borrowInfo['borrow_registration_address'] = $company_info['address'];//注册地址
        }

        return $borrowInfo;
    }
    /**
     * [获取丙方-平台方的信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $$dealID
     * @return array $platformInfo [see returnInfo]
     */
    static public function getPlatformInfo($dealID)
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
    static public function getAdvisoryInfo($advisoryID)
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
     * @param int $agencyID
     * @return array $advisoryInfo [see returnInfo]
     */
    static public function getAgencyInfo($agencyID)
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
        if (DealLoanTypeModel::TYPE_XFFQ == $deal_type_tag) {
            $time_obj_old = XDateTime::valueOfTime($first_repay_interest_day);
            $time_obj_new = $time_obj_old->addMonth($repay_time);
            $repay_time_unit = to_date($time_obj_new->getTime(), "Y年m月d日");
        } else {
            $repay_time_unit = (5 == $loan_type) ? $repay_time . "天" : $repay_time . "个月";
        }

        return $repay_time_unit;
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
        $deal_service = new DealService();
        $is_p2p = $deal_service->isP2pPath($deal);

        $user_info = $user_id ? self::get_user_data($user_id) : array();
        $entrustInfo = self::getAgencyInfo($deal['entrust_agency_id']);
        $canalInfo = self::getAgencyInfo($deal['canal_agency_id']);
        $borrowInfo = self::getBorrowInfo($deal['user_id']);

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
        $notice['entrust_loan_bank_card'] = $dealProject['bankcard'] ;
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

        $user_company_service = new UserCompanyService();
        $loan_legal_person = $user_company_service->getCompanyLegalInfo($user_id);

        $dealagency_service = new DealAgencyService();

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $money);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $money;

        $deal_ext_model = new DealExtModel;

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

        switch($deal_ext['consult_fee_rate_type']){
            case '1':
                $consult_fee_rate_type = 'A';
                break;
            case '2':
                $consult_fee_rate_type = 'B';
                break;
            case '3':
                $consult_fee_rate_type = 'C';
                break;
            default: $consult_fee_rate_type = '';
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

        //获取用户的银行卡信息
        $loan_bank_info = get_user_bank($user_id);
        $borrow_bank_info = get_user_bank($deal['user_id']);

        // JIRA#3260-企业账户二期 <fanjingwen@>
        $loanInfo = self::getLoanInfo($user_info, $loan_bank_info); // 甲方 - 借出方
        $platformInfo = self::getPlatformInfo($deal['id']); // 丙方 - 平台方
        $advisoryInfo = self::getAdvisoryInfo($deal['advisory_id']); // 丁方 - 资产管理方
        $agencyInfo = self::getAgencyInfo($deal['agency_id']); // 戊方 - 保证方
        $canalInfo = self::getAgencyInfo($deal['canal_agency_id']); // 渠道方

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
        $notice['entrust_realname'] = isset($entrustInfo['agency_realname']) ? $entrustInfo['agency_realname'] : '';//合同模板中没有使用这个变量
        $notice['entrust_agent_user_name'] = $entrustInfo['agency_agent_user_name'];
        $notice['entrust_user_realname'] = $entrustInfo['agency_user_realname'];
        $notice['entrust_mobile'] = $entrustInfo['agency_mobile'];
        $notice['entrust_postcode'] = $entrustInfo['agency_postcode'];
        $notice['entrust_fax'] = $entrustInfo['agency_fax'];
        $notice['entrust_platform_realname'] = $entrustInfo['agency_platform_realname'];

        //----------------- 渠道方-----------------------
        $notice['canal_address'] = $canalInfo['agency_address'];
        $notice['canal_realname'] =  isset($canalInfo['agency_realname']) ? $canalInfo['agency_realname'] : '';//合同模板中没有使用这个变量
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
        $notice['guarantee_fee_rate_type'] = $guarantee_fee_rate_type;
        $notice['pay_fee_rate_type'] = $pay_fee_rate_type;
        $notice['canal_fee_rate_type'] = $canal_fee_rate_type;
        $notice['leasing_contract_title'] = $leasing_contract_title;


        $notice['loan_user_name'] = $user_info['user_name'];
        $notice['loan_bank_user'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
        $notice['loan_bank_card'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
        $notice['loan_bank_name'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        $notice['loan_real_name'] = $user_info['real_name'];
        $notice['loan_user_idno'] = $user_info['idno'];
        $notice['loan_address'] = $user_info['address'];
        $notice['loan_legal_person'] = $loan_legal_person['legal_person'];
        $notice['loan_phone'] = $user_info['mobile'];
        $notice['loan_email'] = $user_info['email'];

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
        $notice['jys_id'] = $deal['jys_id'];
        $notice['jys_record_number'] = $deal['jys_record_number'];

        $deal_service = new DealService();
        $borrow_user_info = $deal_service->getDealUserCompanyInfo($deal);
        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息
        if($is_p2p){
            $notice['borrow_user_idno'] = idnoFormat($notice['borrow_user_idno']); // 代理人证件号

            if(empty($contract_info)){
                $hideLenRealName = mb_strlen($notice['borrow_real_name']) - 1;
                $notice['borrow_real_name'] = mb_substr($notice['borrow_real_name'], 0, 1) . str_repeat("*", $hideLenRealName);

                $hideLenEnterpriseName = mb_strlen($borrowInfo['borrow_name']) - 4;
                $notice['borrow_name'] = str_repeat("*", $hideLenEnterpriseName).mb_substr($borrowInfo['borrow_name'], $hideLenEnterpriseName, 4);
            }else{
                $loan_user_service = new UserService($user_info['id']);
                if(!$loan_user_service->isEnterprise()) {
                    $notice['loan_user_idno'] = idnoFormat($notice['loan_user_idno']);
                    $notice['loan_credentials_info'] = idnoFormat($notice['loan_credentials_info']);
                }
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
            $user_service = new UserService();
            $user_info[$user_id] = $user_service->getUserViaSlave($user_id);
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
        if(empty($contract)){
            return false;
        }
        $notice = array();
        $borrowUserId = $contract['borrowUserId'];
        $params = json_decode($contract['params'], true);
        $borrowInfo = self::getBorrowInfo($borrowUserId);
        // **********************************借款人***********************************
        $notice['borrow_name']                 = $borrowInfo['borrow_name'];
        $notice['borrow_user_number']          = $borrowInfo['borrow_user_number']; // 会员编号
        $notice['borrow_license']              = $borrowInfo['borrow_license']; // 营业执照号
        $notice['borrow_agency_realname']      = $borrowInfo['borrow_agency_realname']; // 代理人姓名
        $notice['borrow_agency_idno']          = $borrowInfo['borrow_agency_idno']; // 代理人证件号
        $notice['company_license']             = $borrowInfo['borrow_license'];

        $notice['borrow_sign_time'] = !empty($contract['borrowerSignTime']) ?  date("Y年m月d日",$contract['borrowerSignTime']) : date("Y年m月d日",time());

        $borrow_bank_info = get_user_bank($borrowUserId);
        $notice['borrow_bank_user'] = $borrow_bank_info['card_name'];
        $notice['borrow_bank_card'] = $borrow_bank_info['bankcard'];
        $notice['borrow_bank_name'] = $borrow_bank_info['bankname'];

        // **********************************受托支付信息***********************************
        $notice['entrust_loan_name'] = $params['entrustName'] ;
        $notice['entrust_loan_bank_card'] = $params['loanBankCard'] ;
        $bank = BanklistModel::instance()->getBankInfoByBankId($params['bankZone']) ;
        $notice['entrust_loan_bankzone'] = $bank['name'];

        $notice['repay_time_unit'] = ($params['repayPeriodType'] == 2) ? $params['repayPeriod'] . '个月' : $params['repayPeriod'] . '天';
        $notice['borrow_money'] = $params['borrowAmount'];
        $notice['uppercase_borrow_money'] = get_amount($notice['borrow_money']);
        return $notice;
    }
    static private function get_regon_name($regons){
        $address = '';
        if(empty($regons)){
            return $address;
        }
        $regons = explode(',',$regons);
        foreach($regons as $regon){
            if($regon){
                $n_region = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "delivery_region where id = $regon");
                $address = $address.$n_region[0]['name'];
            }
        }
        return $address;
    }

}

<?php
/**
 * 针对单个合同的更新
 */
namespace system\libs;

use core\dao\ContractRenewModel;
use libs\utils\Finance;
use core\service\EarningService;
use core\service\DealAgencyService;
use core\service\UserCompanyService;
use core\service\UserService;
use core\service\ContractService;
use core\data\DealData;
use libs\utils\Logger;
use core\dao\MsgTemplateModel;
use core\dao\DealExtModel;
use core\dao\ContractModel;
use core\dao\DealSiteModel;
use core\service\ContractRenderService;

class updateContract
{
    private $_db = null;
    private $_tplTable = null;//模板表
    private $_queueTable = null;//消息存储表
    private $_dealTable = null;
    private $_contractList = array();//存储消息内容
    private $_tplList = array();//存储消息模板
    private $_tpl = array(); //存储使用的合同模板

    public function __construct() {
        $this->_tplTable = DB_PREFIX.'msg_template';
        $this->_queueTable = DB_PREFIX.'contract';
        $this->_dealTable = DB_PREFIX.'deal';
        $this->_db = $GLOBALS['db'];
        //$this->_loadTpl();//一次性加载全部模板
    }

    /**
    * 用模板格式化消息内容
    * @param $contract_id 合同id
    * @param $type 合同类型   （1借款合同，2委托担保合同，3保证反担保合同，4保证合同,5出借人平台服务协议,6.付款委托书）
    * @param $deal_id 借款id
    * @param $tpl_name 模板标识
    * @param $content_data 合同内容变量
    * @return int 返回结果
    */
    public function setContract($contract_id, $type, $deal_id, $tpl_name, $content_data){

        $typeSuffix = $this->contract_tpl_suffix($deal_id);

        /*
         * 合同模板单条获取
         */
        $deal_data = new DealData();
        if(isset($this->_tpl[$tpl_name.$typeSuffix]) && ($this->_tpl[$tpl_name.$typeSuffix] <> '')){
            $tpl = $this->_tpl[$tpl_name.$typeSuffix];
        }else{
            $tpl = $deal_data->getMsgTemplatesByName($tpl_name.$typeSuffix);
            $this->_tpl[$tpl_name.$typeSuffix] = $tpl;
        }

        if($tpl === false){
            $msg_template_model = new MsgTemplateModel();
            $tpl = $msg_template_model->getTemplateByName($tpl_name.$typeSuffix);
            if($tpl){
                $tpl = $tpl->getRow();
                $this->_tpl[$tpl_name.$typeSuffix] = $tpl;
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'read from db', "succ")));
            $deal_data->setMsgTemplatesByName($tpl_name.$typeSuffix,$tpl);
        }

        if(empty($tpl)){
            return 0;
        }

        if($type == 6){
            $GLOBALS['tmpl']->assign("notice",$content_data);
            $content_fetch = $GLOBALS['tmpl']->fetch("str:".$tpl['content']);
        }else{
            $content_fetch = $this->_fetchContent($tpl['content'], $content_data);
        }

        $r = false;
        if($contract_id && $content_fetch){

            $data['update_time'] = time();
            $data['attach_id'] = 0;
            $data['id'] = $contract_id;

            \FP::import("libs.utils.logger");
            $contract_renew_model = new \core\dao\ContractRenewModel();
            $content_model = new \core\dao\ContractContentModel();

            try {
                $contract_service = new ContractService();
                $contract_model = new ContractModel();
                $contract_renew_model = new ContractRenewModel();

                $contract_renew_model->db->startTrans();

                $sign_info = $contract_model->find($contract_id,'status');
                if($sign_info['status'] == 3){
                    $renew_record = $contract_renew_model->findBy("`number` = '".$content_data['number']."'",'id',array());
                    if($renew_record->id){
                        $record['number'] = $content_data['number'];
                        $record['content'] = $content_fetch;
                        $record['create_time'] = time();
                        $contract_renew_model->setRow(array('id'=>$renew_record->id));
                        $res = $contract_renew_model->update($record);
                    }else{
                        $contract_renew_model->number = $content_data['number'];
                        $contract_renew_model->content = $content_fetch;
                        $contract_renew_model->create_time = time();
                        $res = $contract_renew_model->save();
                    }
                    if(!$res){
                        throw new \Exception(sprintf("合同id:%d,补发失败", $contract_id));
                    }
                }else{
                    $update_contract = $contract_service->updateContInfo($data);
                    if(!$update_contract){
                        throw new \Exception(sprintf("合同id:%d,补发失败", $contract_id));
                    }
                    $update_content = $content_model->update($contract_id, $content_fetch);
                    if(!$update_content){
                        throw new \Exception(sprintf("合同id:%d,补发失败", $contract_id));
                    }
                }
                $contract_renew_model->db->commit();
                \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $contract_id, "succ")));
                return true;
            } catch (\Exception $e) {
                $contract_renew_model->db->rollback();
                \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $contract_id, "fail", $e->getMessage(), "line:".__LINE__)));
                return false;
            }
        }

        return false;
    }

    /**
    * 变量值写入模板，生成完整的消息内容
    * @param string $tpl 类似 您好，{$notice.user_name}已经{$notice.verify}成为“{$notice.deal_name}”的借款保证人【{$notice.site_name}】
    * @param mix $contentData 模板中对应字段的数据
    * @return string 合并模板与数据的完整内容
    */
    private function _fetchContent($tpl,$contentData){
        $content = preg_replace('/\{\$.*?\./', '{', $tpl);
        foreach($contentData as $k=>$v){
        $content = str_replace('{'.$k.'}', $v, $content);
        }
        return $content;
    }

    /**
    * 一次性取出所有模板,并格式化
    */
    private function _loadTpl(){
        $deal_data = new \core\data\DealData();
        //在redis获取所有模版
        $tpls = $deal_data->getMsgTemplates();
        if($tpls == NULL){
            $tpls = $GLOBALS['db']->get_slave()->getAll("select * from ".$this->_tplTable);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'read from db', "succ")));
            $deal_data->setMsgTemplates($tpls);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'set to redis', "succ")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'read to redis', "succ")));
        }

        foreach($tpls as $k=>$v){
            $this->_tplList[$v['name']]=$v;//用模板名称做key
        }
        if (empty($this->_tplList)){
            return false;
        }
        return true;
    }

    /**
    * 取合同模板类型id，参考public/sys_dictionary.php CONTRACT_TPL_TYPE
    * 模板取名时会在默认模板名后加上该id,以此判断
    * @param int $deal_id
    * @return string 如果是DF则返回空
    */
    public function contract_tpl_suffix($deal_id){
        $type_id = $this->_db->get_slave()->getOne("select contract_tpl_type from ".$this->_dealTable." where id=".$deal_id);
        if($type_id == 'DF'){
            return '';
        }
        return '_'.$type_id;
    }

    /**
    * 借款合同  (出借人-借款人)
    *
    * @param $contract_info 合同信息
    * @param $deal 借款信息
    * @param $loan_user_info 投资信息
    * @param $borrow_user_info 借款人（公司）信息
    * @return 修改结果
    */
    public function push_loan_contract($contract_info, $deal, $loan_user_info, $borrow_user_info){
        if(empty($loan_user_info)){
           return 0;
        }

        $borrow_bank_info = get_user_bank($deal['user_id']);
        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息

        $loan_bank_info = get_user_bank($loan_user_info['id']);

//        $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1); //合同编号
        $number = $contract_info['number'];

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : $contract_info['create_time'];

        // 如果是按天一次性
        if($deal['loantype'] == 5){
            $repay_time = $deal['repay_time'].'天';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
        }else{
            $repay_time = $deal['repay_time'].'个月';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month",  $contract_start_time));
        }

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];

        $loan_user_service = new UserService($loan_user_info['id']);
        if($loan_user_service->isEnterprise()){
            $enterprise_info = $loan_user_service->getEnterpriseInfo(true);
            $loan_name_info = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info = '企业会员用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_bank_user_info = '平台绑定银行开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info = '平台绑定银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info = '绑定银行账号开户行名称 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            $loan_name_info_transfer = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info_transfer = '企业会员用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info_transfer = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_bank_user_info_transfer = '平台绑定银行开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info_transfer = '平台绑定银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info_transfer = '绑定银行账号开户行名称 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            // JIRA#3260 企业账户二期 <fanjingwen@>
            $loan_major_name = '代理人：' . (isset($enterprise_info['contact']['major_name']) ? $enterprise_info['contact']['major_name'] : '');
            $loan_major_condentials_no = '代理人身份证件号：' . (isset($enterprise_info['contact']['major_condentials_no']) ? $enterprise_info['contact']['major_condentials_no'] : '');
            $loan_user_number = numTo32Enterprise($loan_user_info['id']);
        }else{
            //借款合同
            $loan_name_info = '甲方（出借方） : '.$loan_user_info['real_name'];
            $loan_username_info = '网信理财网站用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info = '身份证号/营业执照号 : '.$loan_user_info['idno'];
            $loan_bank_user_info = '开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info = '银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info = '开户行 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            //转让合同
            $loan_name_info_transfer = '甲方（受让方） : '.$loan_user_info['real_name'];
            $loan_username_info_transfer = '平台用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info_transfer = '身份证号/营业执照号 : '.$loan_user_info['idno'];
            $loan_bank_user_info_transfer = '开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info_transfer = '银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info_transfer = '开户行 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            // JIRA#3260 企业账户二期 <fanjingwen@>
            $loan_major_name = '';
            $loan_major_condentials_no = '';
            $loan_user_number = numTo32($loan_user_info['id']);
        }
        $notice_contrace = array(
                'number' => $number,
                'loan_real_name' => $loan_user_info['real_name'],
                'loan_user_name' => $loan_user_info['user_name'],
                'loan_user_number' => $loan_user_number,
                'loan_user_idno' => $loan_user_info['idno'],
                'loan_money' => $loan_user_info['loan_money'],
                'loan_money_uppercase' => get_amount($loan_user_info['loan_money']),

                'loan_money_repay' => $loan_money_repay,
                'loan_money_repay_uppercase' => get_amount($loan_money_repay),
                'loan_money_earning' => $loan_money_earning_format,
                'loan_money_earning_uppercase' => get_amount($loan_money_earning_format),

                'loan_bank_user' => isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '',
                'loan_bank_card' => isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '',
                'loan_bank_name' => isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '',

                'borrow_user_number' => $this->numTo32($deal['user_id']),
                'borrow_bank_user' => $borrow_bank_info['card_name'],
                'borrow_bank_card' => $borrow_bank_info['bankcard'],
                'borrow_bank_name' => $borrow_bank_info['bankname'].$borrow_bank_info['bankzone'],
                'borrow_money' => $deal['borrow_amount'],
                'uppercase_borrow_money' => get_amount($deal['borrow_amount']),

                'start_time' => date("Y-m-d",$contract_start_time),
                'end_time' => $end_time,
                'loantype' => $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']],
                'repay_time' => $deal['repay_time'],
                'repay_time_unit' => $repay_time,

                //利率不从配置取，改取数据库  edit by wenyanlei 20130816
                'rate' => format_rate_for_cont($deal['int_rate']),

                //甲方的签署时间改为 投资时间
                'sign_time' => to_date($loan_user_info['jia_sign_time'], "Y年m月d日"),

                //借款合同（公司借款）中添加担保公司信息 edit by wenyanlei 20131112
                'agency_name' => $agency_info['name'],
                'agency_user_realname' => $agency_info['realname'],
                'agency_address' => $agency_info['address'],
                'agency_mobile' => $agency_info['mobile'],
                'agency_postcode' => $agency_info['postcode'],
                'agency_fax' => $agency_info['fax'],

                //借款扩展字段
                'use_info' => $deal['use_info'],
                'house_address' => $deal['house_address'],
                'house_sn' => $deal['house_sn'],

                //借款咨询费
                'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
                'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

                'prepayment_day_restrict' => $deal['prepay_days_limit'],
                'prepayment_penalty_ratio' => format_rate_for_cont($deal['prepay_rate']),
                'overdue_break_days' => $deal['overdue_break_days'],
                'overdue_ratio' => format_rate_for_cont($deal['overdue_rate']),

                'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
                'redemption_period' => isset($deal['redemption_period']) ? $deal['redemption_period'] : '',
                'lock_period' => isset($deal['lock_period']) ? $deal['lock_period'] : '',
                'rate_day' => isset($deal['rate_day']) ? format_rate_for_db($deal['rate_day']) : '',

                //企业会员相关

                //企业会员借款合同相关
                'loan_name_info' => $loan_name_info,
                'loan_username_info' => $loan_username_info,
                'loan_credentials_info' => $loan_credentials_info,
                'loan_bank_user_info' => $loan_bank_user_info,
                'loan_bank_no_info' => $loan_bank_no_info,
                'loan_bank_name_info' => $loan_bank_name_info,

                //企业会员转让合同相关
                'loan_name_info_transfer' => $loan_name_info_transfer,
                'loan_username_info_transfer' => $loan_username_info_transfer,
                'loan_credentials_info_transfer' => $loan_credentials_info_transfer,
                'loan_bank_user_info_transfer' => $loan_bank_user_info_transfer,
                'loan_bank_no_info_transfer' => $loan_bank_no_info_transfer,
                'loan_bank_name_info_transfer' => $loan_bank_name_info_transfer,

                // JIRA#3260 qiye2 <fanjingwen@>
                'loan_major_name' => $loan_major_name, // 企业负责人
                'loan_major_condentials_no' => $loan_major_condentials_no,
        );

        $contract_model = new ContractModel();

        //如果是借款合同，需要保留之前的签署时间  @todo
        $borrow_sign_time = "<span id='borrow_sign_time'>乙方签署之日</span>";
        if(preg_match("/(\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $borrow_sign_time = $match[1];

        //"/\<p\>乙方.*?\<\/p\>\s+\<p\>日期：(.*?)\<\/p\>/"
        }elseif(preg_match("/\<p\>.*?乙方.*?\<\/p\>\s+\<p\>.*?日期：(.*?)\<\/p\>/", $contract_info['content'], $match_new)){
            $borrow_sign_time = "<span id='borrow_sign_time'>".$match_new[1]."</span>";
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND user_id = '.$borrow_user_info['user_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $borrow_sign_time = "<span id='borrow_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        $notice_contrace['borrow_sign_time'] = $borrow_sign_time;
        $notice_contrace['repayment_table'] = "";
        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        return $this->setContract($contract_info['id'], 1, $deal['id'], 'TPL_LOAN_CONTRACT',$notice_contrace);
    }


    /**
     * 借款合同  (出借人-借款人)
     *
     * @param $contract_info 合同信息
     * @param $deal 借款信息
     * @param $loan_user_info 投资信息
     * @param $borrow_user_info 借款人（公司）信息
     * @return 修改结果
     */
    public function push_loan_contract_v2($contract_info, $deal, $loan_user_info, $borrow_user_info){
        if(empty($loan_user_info)){
            return 0;
        }

        $borrow_bank_info = get_user_bank($deal['user_id']);
        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
        $advisory_info =  $dealagency_service->getDealAgency($deal['advisory_id']);//担保公司信息

        $user_service = new UserService();
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
                $loan_fee_rate_type = 'A';
                break;
            case '2':
                $loan_fee_rate_type = 'B';
                break;
            case '3':
            case '4':
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
            default:$consult_fee_rate_type = '';
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

        //获取平台方信息
        $deal_site = new DealSiteModel();
        $deal_site_id = $deal_site->getSiteByDeal($deal['id']);
        $deal_site_id = 0 ? 1:$deal_site_id['site_id'];
        $deal_agency = $dealagency_service->getDealAgencyBySiteId($deal_site_id);

        if($deal_agency['agency_user_id'] > 0){
            $platform_agency_user = $user_service->getUser($deal_agency['agency_user_id']);
        }

        $agency_platform_user = $user_service->getUser($agency_info['user_id']);
        if ($deal['loantype'] == 5) {
            $deal_repay_time = $deal['repay_time'] . "天";
        } else {
            $deal_repay_time = $deal['repay_time'] . "个月";
        }

        $loan_bank_info = get_user_bank($loan_user_info['id']);
//        $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1); //合同编号

        $number = $contract_info['number'];

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : $contract_info['create_time'];

        // 如果是按天一次性
        if($deal['loantype'] == 5){
            $repay_time = $deal['repay_time'].'天';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
        }else{
            $repay_time = $deal['repay_time'].'个月';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month",  $contract_start_time));
        }

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];

        $user_company_service = new UserCompanyService();
        $loan_legal_person = $user_company_service->getCompanyLegalInfo($loan_user_info['id']);

        // JIRA#3260-企业账户二期 <fanjingwen@>
        $contractRenderService = new ContractRenderService();
        $loanInfo = $contractRenderService->getLoanInfo($loan_user_info, $loan_bank_info); // 甲方 - 借出方
        $borrowInfo = $contractRenderService->getBorrowInfo($deal['user_id']);  // 乙方 - 借款方
        $platformInfo = $contractRenderService->getPlatformInfo($deal['id']); // 丙方 - 平台方
        $advisoryInfo = $contractRenderService->getAdvisoryInfo($deal['advisory_id']); // 丁方 - 资产管理方
        $agencyInfo = $contractRenderService->getAgencyInfo($deal['agency_id']); // 戊方 - 保证方

        // 获取还款方式标记
        $loan_type_mark = $contractRenderService->getLoanTypeMark($deal['loantype']);

        $notice_contrace = array(
            'number' => $number,

            // 甲方 - 借出方
            //企业会员借款合同相关
            'loan_name_info' => $loanInfo['loan_name_info'],
            'loan_username_info' => $loanInfo['loan_username_info'],
            'loan_credentials_info' => $loanInfo['loan_credentials_info'],
            'loan_bank_user_info' => $loanInfo['loan_bank_user_info'],
            'loan_bank_no_info' => $loanInfo['loan_bank_no_info'],
            'loan_bank_name_info' => $loanInfo['loan_bank_name_info'],
            //企业会员转让合同相关
            'loan_name_info_transfer' => $loanInfo['loan_name_info_transfer'],
            'loan_username_info_transfer' => $loanInfo['loan_username_info_transfer'],
            'loan_credentials_info_transfer' => $loanInfo['loan_credentials_info_transfer'],
            'loan_bank_user_info_transfer' => $loanInfo['loan_bank_user_info_transfer'],
            'loan_bank_no_info_transfer' => $loanInfo['loan_bank_no_info_transfer'],
            'loan_bank_name_info_transfer' => $loanInfo['loan_bank_name_info_transfer'],
            'loan_major_name' => $loanInfo['loan_major_name'], // 企业负责人
            'loan_major_condentials_no' => $loanInfo['loan_major_condentials_no'],
            'loan_user_number' => $loanInfo['loan_user_number'],

            // 乙方 - 借款方
            'borrow_name'                 => $borrowInfo['borrow_name'],
            'borrow_user_number'          => $borrowInfo['borrow_user_number'], // 会员编号
            'borrow_license'              => $borrowInfo['borrow_license'], // 营业执照号
            'borrow_agency_realname'      => $borrowInfo['borrow_agency_realname'], // 代理人姓名
            'borrow_agency_idno'          => $borrowInfo['borrow_agency_idno'], // 代理人证件号

            // 丙方 - 平台方
            //暂时使用主站平台名称，后续使用独立icp
            'platform_show_name' => '网信理财',
            'platform_domain' => 'www.firstp2p.com',
            'platform_realname' => $platformInfo['platform_realname'],
            'platform_address' => $platformInfo['platform_address'],
            'platform_name' => $platformInfo['platform_name'],
            'platform_agency_user_number' => $platformInfo['platform_agency_user_number'],
            'platform_license' => $platformInfo['platform_license'],
            'platform_agency_realname' => $platformInfo['platform_agency_realname'],
            'platform_agency_idno' => $platformInfo['platform_agency_idno'],
            'platform_agency_username' => $platformInfo['platform_agency_username'],

            // 丁方 - 资产管理方
            'advisory_name' => $advisoryInfo['advisory_name'],
            'advisory_agent_user_number' => $advisoryInfo['advisory_agent_user_number'],
            'advisory_agent_real_name' => $advisoryInfo['advisory_agent_real_name'],
            'advisory_agent_user_idno' => $advisoryInfo['advisory_agent_user_idno'],
            'advisory_address' => $advisoryInfo['advisory_address'],
            'advisory_realname' => $advisoryInfo['advisory_realname'],
            'advisory_license' => $advisoryInfo['advisory_license'],
            'advisory_agent_user_name' => $advisoryInfo['advisory_agent_user_name'],

            // 戊方 - 保证方
            //借款合同（公司借款）中添加担保公司信息 edit by wenyanlei 20131112
            'agency_name' => $agencyInfo['agency_name'],
            'agency_user_realname' => $agencyInfo['agency_user_realname'],
            'agency_address' => $agencyInfo['agency_address'],
            'agency_mobile' => $agencyInfo['agency_mobile'],
            'agency_postcode' => $agencyInfo['agency_postcode'],
            'agency_fax' => $agencyInfo['agency_fax'],
            'agency_agent_user_name' => $agencyInfo['agency_agent_user_name'],
            'agency_agent_user_number' => $agencyInfo['agency_agent_user_number'],
            'agency_agent_user_idno' => $agencyInfo['agency_agent_user_idno'],
            'agency_agent_real_name' => $agencyInfo['agency_agent_real_name'],
            'agency_license' => $agencyInfo['agency_license'],
            'agency_platform_realname' => $agencyInfo['agency_platform_realname'],
            // --------------------- over -----------------------------

            'contract_transfer_type' => $contract_transfer_type,

            'base_deal_num' => $base_deal_num,
            'lessee_real_name' => $lessee_real_name,

            'loan_fee_rate_type' => $loan_fee_rate_type,
            'consult_fee_rate_type' => $consult_fee_rate_type,
            'pay_fee_rate_type' => $pay_fee_rate_type,
            'guarantee_fee_rate_type' => $guarantee_fee_rate_type,
            'leasing_contract_title' => $leasing_contract_title,
            'loan_application_type' => $loan_application_type,
            'loan_type_mark' => $loan_type_mark,

            'loan_fee_rate' => format_rate_for_cont($deal['loan_fee_rate']),
            'guarantee_fee_rate' => format_rate_for_cont($deal['guarantee_fee_rate']),
            'pay_fee_rate' => format_rate_for_cont($deal['pay_fee_rate']),
            'leasing_money' => format_rate_for_cont($deal['borrow_amount']),
            'leasing_money_uppercase' => get_amount($deal['borrow_amount']),

            'loan_real_name' => $loan_user_info['real_name'],
            'loan_user_name' => $loan_user_info['user_name'],
            'loan_user_idno' => $loan_user_info['idno'],
            'loan_money' => $loan_user_info['loan_money'],
            'loan_address' => $loan_user_info['address'],
            'loan_money_uppercase' => get_amount($loan_user_info['loan_money']),
            'loan_money_repay' => $loan_money_repay,
            'loan_money_repay_uppercase' => get_amount($loan_money_repay),
            'loan_money_earning' => $loan_money_earning_format,
            'loan_money_earning_uppercase' => get_amount($loan_money_earning_format),
            'loan_legal_person' => $loan_legal_person['legal_person'],
            'loan_user_mobile' => $loan_user_info['mobile'],

            'loan_bank_user' => isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '',
            'loan_bank_card' => isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '',
            'loan_bank_name' => isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '',

            'borrow_bank_user' => $borrow_bank_info['card_name'],
            'borrow_bank_card' => $borrow_bank_info['bankcard'],
            'borrow_bank_name' => $borrow_bank_info['bankname'].$borrow_bank_info['bankzone'],
            'borrow_money' => $deal['borrow_amount'],
            'uppercase_borrow_money' => get_amount($deal['borrow_amount']),
            'start_time' => date("Y-m-d",$contract_start_time),
            'end_time' => $end_time,
            'loantype' => $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']],
            'repay_time' => $deal['repay_time'],
            'deal_repay_time' => $deal_repay_time,
            'repay_time_unit' => $repay_time,

            //利率不从配置取，改取数据库  edit by wenyanlei 20130816
            'rate' => format_rate_for_cont($deal['int_rate']),

            //甲方的签署时间改为 投资时间
            'sign_time' => to_date($loan_user_info['jia_sign_time'], "Y年m月d日"),

            //借款扩展字段
            'use_info' => $deal['use_info'],
            'house_address' => $deal['house_address'],
            'house_sn' => $deal['house_sn'],

            //借款咨询费
            'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
            'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

            'prepayment_day_restrict' => $deal['prepay_days_limit'],
            'prepayment_penalty_ratio' => format_rate_for_cont($deal['prepay_rate']),
            'overdue_break_days' => $deal_ext['overdue_break_days'],
            'overdue_ratio' => format_rate_for_cont($deal['overdue_rate']),

            'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
            'redemption_period' => isset($deal['redemption_period']) ? $deal['redemption_period'] : '',
            'lock_period' => isset($deal['lock_period']) ? $deal['lock_period'] : '',
            'rate_day' => isset($deal['rate_day']) ? format_rate_for_db($deal['rate_day']) : '',
        );

        $contract_model = new ContractModel();

        //如果是借款合同，需要保留之前的签署时间  @todo
        $borrow_sign_time = "<span id='borrow_sign_time'>乙方签署之日</span>";
        if(preg_match("/(\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $borrow_sign_time = $match[1];

            //"/\<p\>乙方.*?\<\/p\>\s+\<p\>日期：(.*?)\<\/p\>/"
        }elseif(preg_match("/\<p\>.*?乙方.*?\<\/p\>\s+\<p\>.*?日期：(.*?)\<\/p\>/", $contract_info['content'], $match_new)){
            $borrow_sign_time = "<span id='borrow_sign_time'>".$match_new[1]."</span>";
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND user_id = '.$borrow_user_info['user_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $borrow_sign_time = "<span id='borrow_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        //如果是借款合同，需要保留之前的签署时间  @todo
        $advisory_sign_time = "<span id='advisory_sign_time'>丁方签署之日</span>";
        if(preg_match("/(\<span[\s]*id\=\'advisory_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $advisory_sign_time = $match[1];

            //"/\<p\>丁方.*?\<\/p\>\s+\<p\>日期：(.*?)\<\/p\>/"
        }elseif(preg_match("/\<p\>.*?丁方.*?\<\/p\>\s+\<p\>.*?日期：(.*?)\<\/p\>/", $contract_info['content'], $match_new)){
            $advisory_sign_time = "<span id='advisory_sign_time'>".$match_new[1]."</span>";
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND agency_id = '.$deal['advisory_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $advisory_sign_time = "<span id='advisory_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        //如果是借款合同，需要保留之前的签署时间  @todo
        $agency_sign_time = "<span id='agency_sign_time'>戊方签署之日</span>";
        if(preg_match("/(\<span[\s]*id\=\'agency_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $agency_sign_time = $match[1];

            //"/\<p\>戊方.*?\<\/p\>\s+\<p\>日期：(.*?)\<\/p\>/"
        }elseif(preg_match("/\<p\>.*?戊方.*?\<\/p\>\s+\<p\>.*?日期：(.*?)\<\/p\>/", $contract_info['content'], $match_new)){
            $agency_sign_time = "<span id='agency_sign_time'>".$match_new[1]."</span>";
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND agency_id = '.$deal['agency_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $agency_sign_time = "<span id='advisory_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        $notice_contrace['borrow_sign_time'] = $borrow_sign_time;
        $notice_contrace['advisory_sign_time'] = $advisory_sign_time;
        $notice_contrace['agency_sign_time'] = $agency_sign_time;
        $notice_contrace['repayment_table'] = "";

        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        return $this->setContract($contract_info['id'], 1, $deal['id'], 'TPL_LOAN_CONTRACT_V2',$notice_contrace);
    }

    /**
    * 委托担保合同 （借款人--担保公司）
    *
    * @param $contract_info 合同信息
    * @param $deal 借款信息
    * @param $guarantor_list 保证人列表
    * @param $loan_user_info 投资列表
    * @param $borrow_user_info 借款人信息
    * @param $agency_info 担保公司信息
    * @return 修改结果
    */
    public function push_entrust_warrant_contract($contract_info, $deal, $guarantor_list, $loan_user_info, $borrow_user_info, $agency_info){

        if(empty($loan_user_info)){
            return 0;
        }

        $guarantor_names = ' ';
        $guarantor_name = array();
        foreach($guarantor_list as $ginfo){
            $guarantor_name[] = $ginfo['name'];
        }
        if($guarantor_name){
            $guarantor_names = implode(',', $guarantor_name);
        }

        $borrow_bank_info = get_user_bank($deal['user_id']);

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : $contract_info['create_time'];

//        $number = $this->create_deal_number($deal, $borrow_user_info['user_id'],$loan_user_info['deal_load_id'],2); //合同编号

        $number = $contract_info['number'];

        // 如果是按天一次性
        if($deal['loantype'] == 5){
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
            if ($deal['id'] <= $GLOBALS['dict']['OLD_DEAL_DAY_ID']) {
                $base_time = '365';
            } else {
                $base_time = Finance::DAY_OF_YEAR;
            }
            $repay_time = $deal['repay_time'].'天';
        }else{
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month", $contract_start_time));
            $base_time = '12';
            $repay_time = $deal['repay_time'].'个月';
        }

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];

        $notice_contrace = array(
                'number' => $number,
                'agency_name' => $agency_info['name'],
                'agency_user_realname' => $agency_info['realname'],
                'agency_address' => $agency_info['address'],
                'agency_mobile' => $agency_info['mobile'],
                'agency_postcode' => $agency_info['postcode'],
                'uppercase_borrow_money' => get_amount($loan_user_info['loan_money']),
                'loan_real_name' => $loan_user_info['real_name'],
                'guarantor_name' => $guarantor_names,
                'start_time' => date("Y-m-d",$contract_start_time),
                'end_time' => $end_time,
                'sign_time' => date("Y年m月d日",$contract_info['create_time']),
                'review' => get_amount($agency_info['review']),
                'premium' => get_amount($agency_info['premium']),
                'caution_money' => get_amount($agency_info['caution_money']),
                //'guarantee_fee_rate' => format_rate_for_cont(floatval($deal['guarantee_fee_rate'])),
                //'guarantee_fee_rate_year' => $guarantee_fee_rate_year,
                'guarantee_fee_rate' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'])),
                'guarantee_fee_rate_year' => format_rate_for_cont($deal['guarantee_fee_rate']),
                'rate' => format_rate_for_cont($deal['int_rate']),
                'loan_fee_rate' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'])),
                'loan_fee_rate_year' => format_rate_for_cont($deal['loan_fee_rate']),
                'loan_contract_num' => $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1),
                'repay_time' => $deal['repay_time'],
                'repay_time_unit' => $repay_time,

                //借款扩展字段
                'use_info' => $deal['use_info'],
                'house_address' => $deal['house_address'],
                'house_sn' => $deal['house_sn'],

                //借款咨询费
                'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
                'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

                'loan_money_repay' => $loan_money_repay,
                'loan_money_repay_uppercase' => get_amount($loan_money_repay),
                'loan_money_earning' => $loan_money_earning_format,
                'loan_money_earning_uppercase' => get_amount($loan_money_earning_format),

                'borrow_user_number' => $this->numTo32($deal['user_id']),
                'borrow_bank_user' => $borrow_bank_info['card_name'],
                'borrow_bank_card' => $borrow_bank_info['bankcard'],
                'borrow_bank_name' => $borrow_bank_info['bankname'].$borrow_bank_info['bankzone'],

                'agency_license' => $agency_info['license'],
                'agency_agent_real_name' => $agency_info['agency_agent_real_name'],
                'agency_agent_user_name' => $agency_info['agency_agent_user_name'],
                'agency_agent_user_number' => $this->numTo32($agency_info['agency_user_id']),
                'agency_agent_user_idno' => $agency_info['agency_agent_user_idno'],
                'overdue_break_days' => $deal['overdue_break_days'],
                'overdue_ratio' => format_rate_for_cont($deal['overdue_rate']),
                'prepayment_penalty_ratio' => format_rate_for_cont($deal['prepay_rate']),

                'entrusted_loan_entrusted_contract_num' => $deal['entrusted_loan_entrusted_contract_num'],
                'entrusted_loan_borrow_contract_num' => $deal['entrusted_loan_borrow_contract_num'],
                'base_contract_repay_time' => $deal['base_contract_repay_time'],
                'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
        );

        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息
        return $this->setContract($contract_info['id'], 2, $deal['id'], 'TPL_ENTRUST_WARRANT_CONTRACT',$notice_contrace);
    }

    /**
    * 保证合同（担保公司、出借人）
    *
    * @param $deal 订单
    * @param $loan_user_list 出借人列表
    * @param $agency_info 担保公司信息
    */
    public function push_warrant_contract($contract_info, $deal, $loan_user_info, $borrow_user_info, $agency_info){

        if(empty($loan_user_info)){
            return 0;
        }

//        $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],4); //合同编号

        $number = $contract_info['number'];

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : $contract_info['create_time'];

        $loan_bank_info = get_user_bank($loan_user_info['id']);

        // 如果是按天一次性
        if($deal['loantype'] == 5){
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
        }else{
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month", $contract_start_time));
        }

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];

        $loan_user_service = new UserService($loan_user_info['id']);
        if($loan_user_service->isEnterprise()){
            $enterprise_info = $loan_user_service->getEnterpriseInfo(true);
            $loan_name_info = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info = '企业会员用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_bank_user_info = '平台绑定银行开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info = '平台绑定银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info = '绑定银行账号开户行名称 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            $loan_name_info_transfer = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info_transfer = '企业会员用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info_transfer = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_bank_user_info_transfer = '平台绑定银行开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info_transfer = '平台绑定银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info_transfer = '绑定银行账号开户行名称 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        }else{

            $loan_name_info = '乙方（出借人） : '.$loan_user_info['real_name'];
            $loan_username_info = '网信理财网站用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info = '身份证号/营业执照号 : '.$loan_user_info['idno'];
            $loan_bank_user_info = '开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info = '银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info = '开户行 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';

            $loan_name_info_transfer = '乙方（资产收益权受让方） : '.$loan_user_info['real_name'];
            $loan_username_info_transfer = '网信理财网站用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info_transfer = '身份证号/营业执照号 : '.$loan_user_info['idno'];
            $loan_bank_user_info_transfer = '开户名 : '.isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
            $loan_bank_no_info_transfer = '银行账号 : '.isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
            $loan_bank_name_info_transfer = '开户行 '.isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        }
        $notice_contrace = array(
                'number' => $number,
                'agency_name' => $agency_info['name'],
                'agency_user_realname' => $agency_info['realname'],
                'agency_address' => $agency_info['address'],
                'agency_mobile' => $agency_info['mobile'],
                'agency_postcode' => $agency_info['postcode'],
                'agency_fax' => $agency_info['fax'],
                'loan_real_name' => $loan_user_info['real_name'],
                'loan_user_idno' => !empty($loan_user_info['idno']) ? $loan_user_info['idno']:'',
                'loan_user_address' => !empty($loan_user_info['address']) ? $loan_user_info['address']:'',
                'loan_user_mobile' => $loan_user_info['mobile'],
                'loan_user_postcode' => !empty($loan_user_info['postcode']) ? $loan_user_info['postcode']:'',
                'loan_user_email' => $loan_user_info['email'],
                'loan_money' => $loan_user_info['loan_money'],
                'loan_money_up' => get_amount($loan_user_info['loan_money']),
                'uppercase_borrow_money' => get_amount($deal['borrow_amount']),
                'loan_money_repay' => $loan_money_repay,
                'loan_money_repay_uppercase' => get_amount($loan_money_repay),
                'loan_money_earning' => $loan_money_earning_format,
                'loan_money_earning_uppercase' => get_amount($loan_money_earning_format),
                'start_time' => date("Y-m-d",$contract_start_time),
                'end_time' => $end_time,
                'sign_time' => date("Y年m月d日",$contract_info['create_time']),
                'loan_contract_num' => $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1),
                //借款扩展字段
                'use_info' => $deal['use_info'],
                'house_address' => $deal['house_address'],
                'house_sn' => $deal['house_sn'],

                //借款咨询费
                'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
                'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

                'agency_license' => $agency_info['license'],
                'agency_agent_real_name' => $agency_info['agency_agent_real_name'],
                'agency_agent_user_name' => $agency_info['agency_agent_user_name'],
                'agency_agent_user_idno' => $agency_info['agency_agent_user_idno'],

                'loan_user_name' => $loan_user_info['user_name'],
                'loan_user_number' => $this->numTo32($loan_user_info['id']),
                'loan_bank_user' => isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '',
                'loan_bank_card' => isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '',
                'loan_bank_name' => isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '',

                'overdue_break_days' => $deal['overdue_break_days'],
                'overdue_compensation_time' => $deal['overdue_day'],
                'prepayment_penalty_ratio' => format_rate_for_cont($deal['prepay_rate']),

                'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
                'borrow_user_number' => $this->numTo32($deal['user_id']),

                //企业会员相关
                'loan_name_info' => $loan_name_info,
                'loan_username_info' => $loan_username_info,
                'loan_credentials_info' => $loan_credentials_info,
                'loan_bank_user_info' => $loan_bank_user_info,
                'loan_bank_no_info' => $loan_bank_no_info,
                'loan_bank_name_info' => $loan_bank_name_info,

                //企业会员转让合同相关
                'loan_name_info_transfer' => $loan_name_info_transfer,
                'loan_username_info_transfer' => $loan_username_info_transfer,
                'loan_credentials_info_transfer' => $loan_credentials_info_transfer,
                'loan_bank_user_info_transfer' => $loan_bank_user_info_transfer,
                'loan_bank_no_info_transfer' => $loan_bank_no_info_transfer,
                'loan_bank_name_info_transfer' => $loan_bank_name_info_transfer,
        );
        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息
        return $this->setContract($contract_info['id'], 4, $deal['id'], 'TPL_WARRANT_CONTRACT',$notice_contrace);
    }

    /**
    * 出借人平台服务协议
    *
    * @param $contract_info 合同信息
    * @param $deal 借款信息
    * @param $loan_user_info 投资记录
    * @param $borrow_user_info 借款人
    * @return int 发送合同数量
    */
    public function push_lender_protocal($contract_info, $deal, $loan_user_info, $borrow_user_info){

        if(empty($loan_user_info)){
            return 0;
        }

//        $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],5); //合同编号

        $number = $contract_info['number'];

        $loan_user_service = new UserService($loan_user_info['id']);
        if($loan_user_service->isEnterprise()){
            $enterprise_info = $loan_user_service->getEnterpriseInfo(true);
            $loan_name_info = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info = '企业会员用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_address_info = "企业注册地址 ：".$enterprise_info['registration_address'];
            $loan_tel_info = "";
            $loan_email_info = "";

            $loan_name_info_transfer = '企业会员公司全称 : '.$enterprise_info['company_name'];
            $loan_username_info_transfer = '企业会员用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info_transfer = '营业执照号或其他证件号 : '.$enterprise_info['credentials_no'];
            $loan_address_info_transfer = "企业注册地址 ：".$enterprise_info['registration_address'];
            $loan_tel_info_transfer = "";
        }else{
            $loan_name_info = '乙方 : '.$loan_user_info['real_name'];
            $loan_username_info = '网信理财网站用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info = '身份证号/营业执照号 : '.$loan_user_info['idno'];
            $loan_address_info = "企业注册地址 : ".$loan_user_info['address'];
            $loan_tel_info = "电话 : ".$loan_user_info['mobile'];
            $loan_email_info = "电子邮箱 : ".$loan_user_info['email'];;

            $loan_name_info_transfer = '乙方（资产收益权受让方） : '.$loan_user_info['real_name'];
            $loan_username_info_transfer = '网信理财网站用户名 : '.$loan_user_info['user_name'];
            $loan_credentials_info_transfer = '身份证号/营业执照号 : '.$loan_user_info['idno'];
            $loan_address_info_transfer = "企业注册地址 : ".$loan_user_info['address'];
            $loan_tel_info_transfer = "电话 : ".$loan_user_info['mobile'];

        }

        $notice_contrace = array(
                'number' => $number,
                'loan_real_name' => $loan_user_info['real_name'],
                'loan_user_idno' => $loan_user_info['idno'],
                'loan_address' => $loan_user_info['address'],
                'loan_phone' => $loan_user_info['mobile'],
                'loan_email' => $loan_user_info['email'],
                'manage_fee_rate' => format_rate_for_cont($deal['manage_fee_rate']),
                   'manage_fee_text' => $deal['manage_fee_text'],
                //借款扩展字段
                'use_info' => $deal['use_info'],
                'house_address' => $deal['house_address'],
                'house_sn' => $deal['house_sn'],

                //借款咨询费
                'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
                'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

                'sign_time' => to_date($loan_user_info['jia_sign_time'], "Y年m月d日"),
                'loan_user_name' => $loan_user_info['user_name'],
                'loan_user_number' => $this->numTo32($loan_user_info['id']),
                'manage_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['manage_fee_rate'], $deal['repay_time'])),
                'manage_fee_rate_part_prepayment' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['prepay_manage_fee_rate'], $deal['repay_time'])),

                'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
                'borrow_user_number' => $this->numTo32($deal['user_id']),

                //企业会员相关

                //企业会员借款合同相关
                'loan_name_info' => $loan_name_info,
                'loan_username_info' => $loan_username_info,
                'loan_credentials_info' => $loan_credentials_info,
                'loan_email_info' => $loan_address_info,
                'loan_tel_info' => $loan_tel_info,
                'loan_email_info' => $loan_email_info,


                //企业会员转让合同相关
                'loan_name_info_transfer' => $loan_name_info_transfer,
                'loan_username_info_transfer' => $loan_username_info_transfer,
                'loan_credentials_info_transfer' => $loan_credentials_info_transfer,
                'loan_address_info_transfer' => $loan_address_info_transfer,
                'loan_tel_info_transfer' => $loan_tel_info_transfer,
        );
        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息
        return $this->setContract($contract_info['id'], 5, $deal['id'], 'TPL_LENDER_PROTOCAL',$notice_contrace);
    }

    /**
    * 借款人平台服务协议
    * @param type $deal
    * @param type $loan_user_list 出借人
    * @param type $borrow_user_info 借款人
    * @return int 发送合同数量
    */
    public function push_borrower_protocal($contract_info, $deal, $borrow_user_info){
//        $number = $this->create_deal_number($deal, $borrow_user_info['user_id'],000,5); //合同编号

        $number = $contract_info['number'];
        $user_id = $borrow_user_info['user_id'];
        $borrow_bank_info = get_user_bank($deal['user_id']);

        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['advisory_id']);//担保公司信息

        $notice_contrace = array(
                'number' => $number,
                'loan_fee_rate' => format_rate_for_cont($deal['loan_fee_rate']),
                'loan_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'])),
                'loan_money' => $deal['borrow_amount'],
                'repay_time' => $deal['repay_time'],
                'repay_time_unit' => $deal['loantype'] == 5 ? $deal['repay_time'].'天' : $deal['repay_time'].'个月',
                //借款扩展字段
                'use_info' => $deal['use_info'],
                'house_address' => $deal['house_address'],
                'house_sn' => $deal['house_sn'],

                //借款咨询费
                'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
                'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

                'consulting_company_name' => $agency_info['name'],
                'consulting_company_address' => $agency_info['address'],
                'consulting_company_tel' => $agency_info['mobile'],
                'consulting_company_bank_user' => $agency_info['card_name'],
                'consulting_company_bank_card' => $agency_info['bankcard'],
                'consulting_company_bank_name' => $agency_info['bankzone'],
                'consulting_company_agent_real_name' => $agency_info['agency_agent_real_name'],
                'consulting_company_agent_user_name' => $agency_info['agency_agent_user_name'],
                'consulting_company_agent_user_idno' => $agency_info['agency_agent_user_idno'],
                'borrow_bank_user' => $borrow_bank_info['card_name'],
                'borrow_bank_card' => $borrow_bank_info['bankcard'],
                'borrow_bank_name' => $borrow_bank_info['bankname'].$borrow_bank_info['bankzone'],

                'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
                'borrow_user_number' => $this->numTo32($deal['user_id']),
        );

        //如果是借款合同，需要保留之前的签署时间  @todo
        $contract_model = new ContractModel();
        $notice_contrace['sign_time'] = "<span id='borrow_sign_time'></span>";
        if(preg_match("/(\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $notice_contrace['sign_time'] = $match[1];
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND user_id = '.$borrow_user_info['user_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $notice_contrace['sign_time'] = "<span id='borrow_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        return $this->setContract($contract_info['id'], 5, $deal['id'], 'TPL_BORROWER_PROTOCAL', $notice_contrace);
    }

    /**
     * 借款人平台服务协议-新版合同（借款人，资产管理方）
     * @param type $deal
     * @param type $loan_user_list 出借人
     * @param type $borrow_user_info 借款人
     * @return int 发送合同数量
     */
    public function push_borrower_protocal_v2($contract_info, $deal, $borrow_user_info){
//        $number = $this->create_deal_number($deal, $borrow_user_info['user_id'],000,5); //合同编号

        $number = $contract_info['number'];
        $user_id = $borrow_user_info['user_id'];
        $borrow_bank_info = get_user_bank($deal['user_id']);

        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
        $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//担保资产管理方信息

        if($deal_service->isDealDT($deal['id'])){
            $management_info = $dealagency_service->getDealAgency($deal['management_agency_id']);//管理机构信息
            $management_company_name = $management_info['name'];
        }

        $user_service = new UserService();
        $deal_ext_model = new DealExtModel;

        $deal_ext = $deal_ext_model->getDealExtByDealId($deal['id']);

        $base_deal_num = $deal_ext['leasing_contract_num']?$deal_ext['leasing_contract_num']:'';

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
                $loan_fee_rate_type = 'A';
                break;
            case '2':
                $loan_fee_rate_type = 'B';
                break;
            case '3':
            case '4':
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

        //获取平台方信息
        $deal_site = new DealSiteModel();

        $deal_site_id = $deal_site->getSiteByDeal($deal['id']);
        $deal_site_id = 0 ? 1:$deal_site_id['site_id'];
        $deal_agency = $dealagency_service->getDealAgencyBySiteId($deal_site_id);

        if($deal_agency['agency_user_id'] > 0){
            $platform_agency_user = $user_service->getUser($deal_agency['agency_user_id']);
        }


        // JIRA#3260-企业账户二期 <fanjingwen@>
        $contractRenderService = new ContractRenderService();
        $platformInfo = $contractRenderService->getPlatformInfo($deal['id']); // 平台方
        $borrowInfo = $contractRenderService->getBorrowInfo($deal['user_id']);  // 借款方
        $advisoryInfo = $contractRenderService->getAdvisoryInfo($deal['advisory_id']); // 资产管理方
        $agencyInfo = $contractRenderService->getAgencyInfo($deal['agency_id']); // 保证方

        $notice_contrace = array(
            'number' => $number,

            // ------------------------------ 签署各方信息  ----------------------------------
            // 甲方- 平台方信息
            'platform_name' => $platformInfo['platform_name'],
            'platform_license' => $platformInfo['platform_license'],
            'platform_address' => $platformInfo['platform_address'],
            'platform_realname' => $platformInfo['platform_realname'],
            'platform_agency_realname' => $platformInfo['platform_agency_realname'],
            'platform_agency_user_number' => $platformInfo['platform_agency_user_number'],
            'platform_agency_idno' => $platformInfo['platform_agency_idno'],
            'platform_agency_username' => $platformInfo['platform_agency_username'],

            // 乙方 - 资产管理方
            'advisory_name' => $advisoryInfo['advisory_name'],
            'advisory_agent_user_number' => $advisoryInfo['advisory_agent_user_number'],
            'advisory_agent_real_name' => $advisoryInfo['advisory_agent_real_name'],
            'advisory_agent_user_idno' => $advisoryInfo['advisory_agent_user_idno'],
            'advisory_address' => $advisoryInfo['advisory_address'],
            'advisory_realname' => $advisoryInfo['advisory_realname'],
            'advisory_license' => $advisoryInfo['advisory_license'],
            'advisory_agent_user_name' => $advisoryInfo['advisory_agent_user_name'],

            // 丙方 - 借款方
            'borrow_name'                 => $borrowInfo['borrow_name'],
            'borrow_user_number'          => $borrowInfo['borrow_user_number'], // 会员编号
            'borrow_license'              => $borrowInfo['borrow_license'], // 营业执照号
            'borrow_agency_realname'      => $borrowInfo['borrow_agency_realname'], // 代理人姓名
            'borrow_agency_idno'          => $borrowInfo['borrow_agency_idno'], // 代理人证件号

            // 戊方 - 保证方
            //借款合同（公司借款）中添加担保公司信息 edit by wenyanlei 20131112
            'agency_name' => $agencyInfo['agency_name'],
            'agency_user_realname' => $agencyInfo['agency_user_realname'],
            'agency_address' => $agencyInfo['agency_address'],
            'agency_mobile' => $agencyInfo['agency_mobile'],
            'agency_postcode' => $agencyInfo['agency_postcode'],
            'agency_fax' => $agencyInfo['agency_fax'],
            'agency_agent_user_idno' => $agencyInfo['agency_agent_user_idno'],
            'agency_agent_real_name' => $agencyInfo['agency_agent_real_name'],

            // ------------------------------ over --------------------------------

            'contract_transfer_type' => $contract_transfer_type,

            'base_deal_num' => $base_deal_num,

            'loan_fee_rate_type' => $loan_fee_rate_type,
            'consult_fee_rate_type' => $consult_fee_rate_type,
            'pay_fee_rate_type' => $pay_fee_rate_type,
            'guarantee_fee_rate_type' => $guarantee_fee_rate_type,

            //暂时使用主站平台名称，后续使用独立icp
            'platform_show_name' => '网信理财',
            'platform_domain' => 'www.firstp2p.com',

            'loan_fee_rate' => format_rate_for_cont($deal['loan_fee_rate']),
            'loan_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'])),
            'loan_money' => $deal['borrow_amount'],
            'repay_time' => $deal['repay_time'],
            'repay_time_unit' => $deal['loantype'] == 5 ? $deal['repay_time'].'天' : $deal['repay_time'].'个月',
            //借款扩展字段
            'use_info' => $deal['use_info'],
            'house_address' => $deal['house_address'],
            'house_sn' => $deal['house_sn'],

            //借款咨询费
            'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
            'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),

            'pay_fee_rate' => format_rate_for_cont($deal['pay_fee_rate']),

            //管理机构费率及签署涉及管理机构公司名
            'management_name' => empty($management_info['name'])?'':$management_info['name'],
            'management_agency_user_number' => empty($management_info['agency_user_id'])?'': $this->numTo32($management_info['agency_user_id']),
            'management_license' => empty($management_info['license'])?'':$management_info['license'],
            'management_fee_rate' => format_rate_for_cont($deal['management_fee_rate']),
            'management_company_name' => empty($management_company_name)?'':$management_company_name,

            'consulting_company_name' => $agency_info['name'],
            'consulting_company_address' => $agency_info['address'],
            'consulting_company_tel' => $agency_info['mobile'],
            'consulting_company_bank_user' => $agency_info['card_name'],
            'consulting_company_bank_card' => $agency_info['bankcard'],
            'consulting_company_bank_name' => $agency_info['bankzone'],
            'consulting_company_agent_real_name' => $agency_info['agency_agent_real_name'],
            'consulting_company_agent_user_name' => $agency_info['agency_agent_user_name'],
            'consulting_company_agent_user_idno' => $agency_info['agency_agent_user_idno'],

            'borrow_bank_user' => $borrow_bank_info['card_name'],
            'borrow_bank_card' => $borrow_bank_info['bankcard'],
            'borrow_bank_name' => $borrow_bank_info['bankname'].$borrow_bank_info['bankzone'],

            'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数

            'sign_time' => date("Y年m月d日",$contract_info['create_time']),
        );

        //如果是借款合同，需要保留之前的签署时间  @todo
        $contract_model = new ContractModel();
        $notice_contrace['borrow_sign_time'] = "<span id='borrow_sign_time'>合同签署之日</span>";
        if(preg_match("/(\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $notice_contrace['borrow_sign_time'] = $match[1];
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND user_id = '.$borrow_user_info['user_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $notice_contrace['borrow_sign_time'] = "<span id='borrow_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        $notice_contrace['advisory_sign_time'] = "<span id='advisory_sign_time'>合同签署之日</span>";
        if(preg_match("/(\<span[\s]*id\=\'advisory_sign_time\'\>.*?\<\/span\>)/", $contract_info['content'], $match)){
            $notice_contrace['advisory_sign_time'] = $match[1];
        }else{
            $sign_time = $contract_model->findByViaSlave('deal_id = '.$contract_info['deal_id'].' AND agency_id = '.$deal['advisory_id']." AND number = '".$contract_info['number']."'",'sign_time',array());
            if($sign_time['sign_time'] > 0) {
                $notice_contrace['advisory_sign_time'] = "<span id='advisory_sign_time'>" . date('Y年m月d日', $sign_time['sign_time']) . "</span>";
            }
        }

        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        return $this->setContract($contract_info['id'], 5, $deal['id'], 'TPL_BORROWER_PROTOCAL_V2', $notice_contrace);
    }

    /**
    * 资产收益权回购通知  (出借人-借款人)
    *
    * @param $deal 订单
    * @param $loan_user_list 出借人列表
    * @param $borrow_user_info 借款人列表
    * @param $user_type
    */

    public function push_buyback_notification($contract_info, $deal, $loan_user_info, $borrow_user_info){
        if(empty($loan_user_info)){
           return 0;
        }

//        $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1); //合同编号

        $number = $contract_info['number'];

        $earning = new EarningService();
        $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
        $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
        $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];


        $notice_contrace = array(
                    //合同编号：
                    'number' => $number,
                    //资产收益权转让协议合同编号：
                    'loan_contract_num' => $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1),
                    //出借人真实姓名：
                    'loan_real_name' => $loan_user_info['real_name'],
                    //出借人身份证号：
                    'loan_user_idno' => $loan_user_info['idno'],
                    //回购价款：
                    'loan_money_repay' => $loan_money_repay,
                    //回购价款（大写）：
                    'loan_money_repay_uppercase' => get_amount($loan_money_repay),
                    //签署日期：
                    'sign_time' => to_date($loan_user_info['jia_sign_time'], "Y年m月d日"),
                    //回购日期
                    'buyback_time' => "<span id='buyback_time'> 年 月 日</span>",
                    //提前还款罚息天数
                    'prepay_penalty_days' => $deal['prepay_penalty_days'],

                    'borrow_user_number' => $this->numTo32($deal['user_id']),
        );

        //计算回购日
        if(!empty($deal['repay_start_time'])){
            //还款方式不同，做不同处理
            if($deal['loantype'] == 5){
                $buyback_time = strtotime("+".$deal['repay_time']." day", strtotime(to_date($deal['repay_start_time'])));
            }else{
                $buyback_time = strtotime("+".$deal['repay_time']." month", strtotime(to_date($deal['repay_start_time'])));
            }
            $notice_contrace['buyback_time'] = "<span id='buyback_time'>".date("Y年m月d日",$buyback_time)."</span>";
        }

        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        return $this->setContract($contract_info['id'], 7, $deal['id'], 'TPL_BUYBACK_NOTIFICATION',$notice_contrace);
    }

    /**
    * 生成合同编号
    *
    * 借款合同编号  ：         deal_id_0_loan_user_id
    * 委托担保合同编号：           deal_id_1_agency_id
    * 保证反担保合同编号：       deal_id_2_guarantor_id
    * 保证合同：                         deal_id_3_loan_user_id
    * 出借人平台服务协议: type =5
    */
    public function create_deal_number($deal, $user_id, $load_id, $type=NULL){
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

//会员编号
//type 用户类型：0 个人会员 1:企业会员
    private function numTo32($no, $type=0){
        $no+=34000000;
        $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
                          "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M", 
                          "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        while($no >= 32) {
            $rtn = $char_array[fmod($no, 32)].$rtn;
            $no = floor($no/32);
            }

        $prefix = '00';
        if($type == 1){
            $prefix = '66';
        }
        return $prefix.$char_array[$no].$rtn;
    }
}
?>

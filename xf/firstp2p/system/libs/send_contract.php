<?php

/**
 * 前台满标以后下发合同处理，修改发送合同逻辑时，请同步更新update_contract.php 代码
 */

use libs\utils\Finance;
use core\service\DealService;
use core\service\EarningService;
use core\service\UserCompanyService;
use core\service\DealAgencyService;
use core\service\UserService;
use core\data\DealData;
use libs\utils\Logger;

use core\dao\OpLogModel;
use core\dao\DealSiteModel;
use core\dao\MsgTemplateModel;
use core\dao\DealExtModel;
use core\service\ContractRenderService;

class sendContract
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
     * @param mix $contentData
     * @param int $user_id 收取消息用户id
     * @param string $tplName 消息模板名称
     * @param string $title 邮件标题
     * @param string $attachment 附件文件路径
     * @param $type 合同类型   （1借款合同，2委托担保合同，3保证反担保合同，4保证合同,5出借人平台服务协议, 6、生产委托书）
     * @return int 暂存的消息数量
     */
    public function setContract($title,$type,$number,$user_id,$deal_id,$tplName,$contentData,$agency_id = 0, $deal_load_id = 0){
        $typeSuffix = $this->contract_tpl_suffix($deal_id);
        $tpl = $this->_tplList[$tplName.$typeSuffix];

        /*
         * 合同模板单条获取
         */
        $deal_data = new DealData();
        if(isset($this->_tpl[$tplName.$typeSuffix]) && ($this->_tpl[$tplName.$typeSuffix] <> '')){
            $tpl = $this->_tpl[$tplName.$typeSuffix];
        }else{
            $tpl = $deal_data->getMsgTemplatesByName($tplName.$typeSuffix);
            $this->_tpl[$tplName.$typeSuffix] = $tpl;
        }

        if($tpl === false){
            $msg_template_model = new MsgTemplateModel();
            $tpl = $msg_template_model->getTemplateByName($tplName.$typeSuffix);
            if($tpl){
                $tpl = $tpl->getRow();
                $this->_tpl[$tplName.$typeSuffix] = $tpl;
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'read from db', "succ")));
            $deal_data->setMsgTemplatesByName($tplName.$typeSuffix,$tpl);
        }


        if(empty($tpl)){
            return 0;
        }

        if($type == 6){
            $GLOBALS['tmpl']->assign("notice",$contentData);
            $contentData = $GLOBALS['tmpl']->fetch("str:".$tpl['content']);
        }else{
            $contentData = $this->_fetchContent($tpl['content'], $contentData);
        }

        $msgData=array(
                'title' => empty($tpl['contract_title']) ? $title : trim($tpl['contract_title']),
                'type' => $type,
                'number' => $number,
                'user_id' => $user_id,
                'deal_id' => $deal_id,
                'content' => $contentData,
                'create_time' => time(),
                'agency_id' => $agency_id,
                'deal_load_id' => $deal_load_id,
        );

        array_push($this->_contractList,$msgData);
        return sizeof($this->_contractList);
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
    * 坑带宽,准备干掉
    */
//    private function _loadTpl(){
//        $deal_data = new \core\data\DealData();
//        //在redis获取所有模版
//        $tpls = $deal_data->getMsgTemplates();
//        if($tpls == NULL){
//            $tpls = $GLOBALS['db']->get_slave()->getAll("select * from ".$this->_tplTable);
//            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'read from db', "succ")));
//            $deal_data->setMsgTemplates($tpls);
//            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'set to redis', "succ")));
//        }else{
//            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'read to redis', "succ")));
//        }
//
//        foreach($tpls as $k=>$v){
//            $this->_tplList[$v['name']]=$v;//用模板名称做key
//        }
//        if (empty($this->_tplList)){
//            return false;
//        }
//        return true;
//    }

    /**
    * 一次性写入所有消息内容
    * @return int 成功写入的消息数量
    */
    public function save($op_log_id = false, $update_time = false){
        $count = count($this->_contractList);
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'START SAVE CONTRACT op_log_id = '.$op_log_id, "succ")));

        if($count > 0){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'START SAVE CONTRACT count = '.$count, "succ")));
            \FP::import("libs.utils.logger");
            $contract_content_dao = new \core\dao\ContractContentModel();
            try {
                $contract_content_dao->db->startTrans();

                foreach($this->_contractList as $row){

                    $content = $row['content'];//content单独处理
                    unset($row['content']);//停止向firstp2p_contract插入contetn数据

                    $id = 0;
                    $add_contract = $this->_db->autoExecute($this->_queueTable, $row);
                    $id = $this->_db->insert_id();

                    if(!$add_contract || !$id){
                        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'START SAVE CONTRACT add_contract', "succ")));
                        throw new \Exception(sprintf("借款id:%d,生成合同失败！%s", $row['deal_id'], $this->_db->error()));
                    }

                    $add_content = $contract_content_dao->add($id, $content);
                    if(!$add_content){
                        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'START SAVE CONTRACT add_content', "succ")));
                        throw new \Exception(sprintf("借款id:%d,生成合同失败！%s", $row['deal_id'], $contract_content_dao->getError()));
                    }
                }
                if($op_log_id !== false){
                    $model = new OpLogModel();
                    $op_log = $model->find($op_log_id,'op_status,update_time');

                    //用update time做幂等，如果更新时间不同，则事物回滚
                    if((($op_log['update_time'] === $update_time) && $op_log['op_status'] <= 0)||($update_time == false)){
                        $ret = $model->update_status($op_log_id, 1, gmdate('Y-m-d H:i:s',time()+8*3600));
                        if($ret === false){
                            throw new \Exception('异步任务更新失败! ID:'.$op_log_id);
                        }

                    }else{
                        logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $row['deal_id'], "fail", "合同已由jobs生成", "line:".__LINE__)));
                        return true;
                    }
                }
                $contract_content_dao->db->commit();
                \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $row['deal_id'], "succ")));
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'START SAVE CONTRACT SUCCESS', "succ")));
                return true;
            } catch (\Exception $e) {
                $contract_content_dao->db->rollback();
                \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $row['deal_id'], "fail", $e->getMessage(), "line:".__LINE__)));
                throw new \Exception($e->getMessage());
            }
        }
        return 0;
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
    * @param $deal 订单
    * @param $loan_user_list 出借人列表
    * @param $borrow_user_info 借款人列表
    * @param $user_type
    */
    public function push_loan_contract($deal, $loan_user_list, $borrow_user_info, $user_type=NULL){
        FP::import("libs.common.app");
        $borrow_bank_info = get_user_bank($deal['user_id']);
        $agency_info = get_agency_info($deal['agency_id']);

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : time();

        //还款方式不同，做不同处理
        if($deal['loantype'] == 5){
            $repay_time = $deal['repay_time'].'天';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
        }else{
            $repay_time = $deal['repay_time'].'个月';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month", $contract_start_time));
        }

        //合同标题
        $title = '借款合同';
        $deal_service = new DealService();
        if ($deal_service->isDealLeaseByType($deal['type_id'])) {
            $title = '资产收益权转让协议';
        }
        foreach ($loan_user_list as $loan_user_info){

            $loan_bank_info = get_user_bank($loan_user_info['id']);
            $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1); //合同编号

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
                    'repay_time' => $deal['repay_time'],
                    'repay_time_unit' => $repay_time,
                    'loantype' => $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']],
                    'end_time' => $end_time,

                    'rate' => format_rate_for_cont($deal['int_rate']),

                    //甲方的签署时间改为 投资时间
                    'sign_time' => to_date($loan_user_info['jia_sign_time'], "Y年m月d日"),

                    //把乙方的签署时间改为文字提示
                    'borrow_sign_time' => "<span id='borrow_sign_time'>乙方签署之日</span>",

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

            $notice_contrace['repayment_table'] = "";
            $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

            if(empty($user_type)){
                $user_id = $loan_user_info['id'];
            }else{
                $user_id = $deal['user_id'];
            }
            $count[] = $this->setContract($title, 1,$number, $user_id, $deal['id'], 'TPL_LOAN_CONTRACT',$notice_contrace, 0, $loan_user_info['deal_load_id']);
            $notice_contrace = array(); //清空
            unset($number);
        }
        return sizeof($count);
    }

    /**
    * 委托担保合同 （借款人--担保公司）
    *
    * @param $deal 订单
    * @param $borrow_user_info 借款人信息
    * @param $agency_info 担保公司信息
    * @param $user_id to user_id
    */
    public function push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $user_id ,$user_type=NULL){
        //获取保证人
        $guarantor_names = ' ';
        $guarantor_name = array();
        foreach($guarantor_list as $ginfo){
            $guarantor_name[] = $ginfo['name'];
        }
        if($guarantor_name){
            $guarantor_names = implode(',', $guarantor_name);
        }

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : time();

        //还款方式不同，做不同处理
        if($deal['loantype'] == 5){
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
            $base_time = '360';
            $repay_time = $deal['repay_time'].'天';
        }else{
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month", $contract_start_time));
            $base_time = '12';
            $repay_time = $deal['repay_time'].'个月';
        }

        //合同标题
        $title = '委托担保合同';

        $borrow_bank_info = get_user_bank($deal['user_id']);

        foreach ($loan_user_list as $loan_user_info){
            $number = $this->create_deal_number($deal, $borrow_user_info['user_id'],$loan_user_info['deal_load_id'],2); //合同编号
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
                    'sign_time' => date("Y年m月d日",time()),
                    'review' => get_amount($agency_info['review']),
                    'premium' => get_amount($agency_info['premium']),
                    'caution_money' => get_amount($agency_info['caution_money']),
                    //'guarantee_fee_rate' => format_rate_for_cont(floatval($deal['guarantee_fee_rate'])),
                    'guarantee_fee_rate' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'])),
                    //'guarantee_fee_rate_year' => $guarantee_fee_rate_year,
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
                    'agency_agent_user_idno' => $agency_info['agency_agent_user_idno'],
                    'overdue_break_days' => $deal['overdue_break_days'],
                    'overdue_ratio' => format_rate_for_cont($deal['overdue_rate']),
                    'prepayment_penalty_ratio' => format_rate_for_cont($deal['prepay_rate']),

                    'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
            );
            $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

            if($user_type){
                $agency_id = $agency_info['id'];
                $user_id = 0;
            }
            $count[] = $this->setContract($title, 2, $number, $user_id, $deal['id'], 'TPL_ENTRUST_WARRANT_CONTRACT',$notice_contrace,$agency_id, $loan_user_info['deal_load_id']);
            $notice_contrace = array(); //清空
            unset($agency_id);
            unset($number);
        }
        return $count;
    }

    /**
    * 保证反担保合同  (保证人)  已废弃
    *
    * @param $deal 订单
    * @param $guarantor_list 担保人列表
    * @param $loan_user_list 出借人列表
    * @param $agency_info 担保公司列表
    * @param $borrow_user_info 借款人信息
    * $user_id to user_id
    *
    */
    public function push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, $user_type){

        //合同标题
        $title = '保证反担保合同';
        FP::import("libs.common.app");

        foreach ($guarantor_list as $guarantor){
            $guarantor_user_info = get_user_info($guarantor['to_user_id'],true);
            foreach ($loan_user_list as $loan_user_info){
                $number = $this->create_deal_number($deal, $guarantor['to_user_id'],$loan_user_info['deal_load_id'],3); //合同编号
                $notice_contrace = array(
                        'number' => $number,
                        'guarantor_name' => $guarantor['name'],
                        'guarantor_address' => !empty($guarantor_user_info['address']) ? $guarantor_user_info['address']:'',
                        'guarantor_mobile' => $guarantor['mobile'],
                        'guarantor_email' => $guarantor['email'],
                        'guarantor_idno' => !empty($guarantor_user_info['idno']) ? $guarantor_user_info['idno']:'',
                        'agency_name' => $agency_info['name'],
                        'agency_user_realname' => $agency_info['realname'],
                        'agency_address' => $agency_info['address'],
                        'agency_mobile' => $agency_info['mobile'],
                        'loan_real_name' => $loan_user_info['real_name'],
                        'loan_user_idno' => $loan_user_info['idno'],
                        'sign_time' => date("Y年m月d日",time()),
                        'loan_contract_num' => $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1),
                        'warrant_contract_num' => $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],4),
                        //借款扩展字段
                        'use_info' => $deal['use_info'],
                        'house_address' => $deal['house_address'],
                        'house_sn' => $deal['house_sn'],

                        //借款咨询费
                        'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
                        'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),
                        'borrow_user_number' => $this->numTo32($deal['user_id']),
                );

                $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

                if($user_type == 'guarantor'){
                    $user_id = $guarantor['to_user_id'];
                }elseif ($user_type == 'agency'){
                    $user_id = 0;
                    $agency_id = $agency_info['id'];
                }
                $count[] = $this->setContract($title, 3,$number, $user_id, $deal['id'], 'TPL_WARRANDICE_CONTRACT',$notice_contrace,$agency_id);
                $notice_contrace = array(); //清空
                unset($user_id);
                unset($agency_id);
                unset($number);
            }
        }
        return sizeof($count);
    }

    /**
    * 保证合同（担保公司、出借人）
    *
    * @param $deal 订单
    * @param $loan_user_list 出借人列表
    * @param $agency_info 担保公司信息
    * $user_id to user_id
    */
    public function push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, $user_id=NULL, $user_type=NULL){

        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : time();

        //还款方式不同，做不同处理
        if($deal['loantype'] == 5){
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
        }else{
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month", $contract_start_time));
        }

        //合同标题
        $title = '保证合同';

        foreach ($loan_user_list as $loan_user_info) {
            $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],4); //合同编号
            $earning = new EarningService();
            $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
            $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
            $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];
            $loan_bank_info = get_user_bank($loan_user_info['id']);

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
                    'sign_time' => date("Y年m月d日",$contract_start_time),
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
                    'agency_agent_user_number' => $this->numTo32($agency_info['agency_user_id']),
                    'agency_agent_user_idno' => $agency_info['agency_agent_user_idno'],

                    'loan_user_name' => $loan_user_info['user_name'],
                    'loan_user_number' => $this->numTo32($loan_user_info['id']),
                    'loan_bank_user' => isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '',
                    'loan_bank_card' => isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '',
                    'loan_bank_name' => isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '',

                    'overdue_break_days' => $deal['overdue_break_days'],
                    'overdue_compensation_time' => $deal['overdue_day'],
                    'prepayment_penalty_ratio' => format_rate_for_cont($deal['prepay_rate']),

                    'entrusted_loan_entrusted_contract_num' => $deal['entrusted_loan_entrusted_contract_num'],
                    'entrusted_loan_borrow_contract_num' => $deal['entrusted_loan_borrow_contract_num'],
                    'base_contract_repay_time' => $deal['base_contract_repay_time'],

                    'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数

                    'borrow_user_number' => $this->numTo32($deal['user_id']),

                    //企业会员相关
                    'loan_name_info' => $loan_name_info,
                    'loan_username_info' => $loan_username_info,
                    'loan_credentials_info' => $loan_credentials_info,
                    'loan_bank_user_info' => $loan_bank_user_info,
                    'loan_bank_no_info' => $loan_bank_no_info,
                    'loan_bank_name_info' => $loan_bank_name_info,

                    'loan_name_info_transfer' => $loan_name_info_transfer,
                    'loan_username_info_transfer' => $loan_username_info_transfer,
                    'loan_credentials_info_transfer' => $loan_credentials_info_transfer,
                    'loan_bank_user_info_transfer' => $loan_bank_user_info_transfer,
                    'loan_bank_no_info_transfer' => $loan_bank_no_info_transfer,
                    'loan_bank_name_info_transfer' => $loan_bank_name_info_transfer,


            );
            $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息
            if(empty($user_id)){
                $user_id = $loan_user_info['id'];
            }
            if ($user_type == 'agency'){
                $agency_id = $agency_info['id'];
                $user_id = 0;
            }
            $count[] = $this->setContract($title, 4,$number, $user_id, $deal['id'], 'TPL_WARRANT_CONTRACT',$notice_contrace,$agency_id, $loan_user_info['deal_load_id']);
            $notice_contrace = array(); //清空
            unset($agency_id);
            unset($number);
            unset($user_id);
        }
        return $count;
    }

    /**
    * 出借人平台服务协议
    * @param type $deal
    * @param type $loan_user_list 出借人
    * @param type $borrow_user_info 借款人
    * @return int 发送合同数量
    */
    public function push_lender_protocal($deal, $loan_user_list, $borrow_user_info){
        FP::import("libs.common.app");
        $title = '出借人平台服务协议';
        $deal_service = new DealService();
        if ($deal_service->isDealLeaseByType($deal['type_id'])) {
            $title = '资产受让方咨询服务协议';
        }
        foreach ($loan_user_list as $loan_user_info){
            $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],5); //合同编号
            $user_id = $loan_user_info['id'];
            $loan_bank_info = get_user_bank($loan_user_info['id']);

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

            $count[] = $this->setContract($title, 5,$number, $user_id, $deal['id'], 'TPL_LENDER_PROTOCAL',$notice_contrace, 0, $loan_user_info['deal_load_id']);
            $notice_contrace = array(); //清空
        }
        return sizeof($count);
    }

    /**
    * 借款人平台服务协议
    * @param type $deal
    * @param type $loan_user_list 出借人
    * @param type $borrow_user_info 借款人
    * @return int 发送合同数量
    */
    public function push_borrower_protocal($deal, $borrow_user_info){
        FP::import("libs.common.app");
        $title = '借款人平台服务协议';
        $deal_service = new DealService();
        if ($deal_service->isDealLeaseByType($deal['type_id'])) {
            $title = '资产转让方咨询服务协议';
        }

        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['advisory_id']);//担保公司信息

        $number = $this->create_deal_number($deal, $borrow_user_info['user_id'],000,5); //合同编号
        $user_id = $borrow_user_info['user_id'];
        $notice_contrace = array(
            'number' => $number,
            'loan_money' => $deal['borrow_amount'],
            'repay_time' => $deal['repay_time'],
            'repay_time_unit' => $deal['loantype'] == 5 ? $deal['repay_time'].'天' : $deal['repay_time'].'个月',
            'loan_fee_rate' => format_rate_for_cont($deal['loan_fee_rate']),
            'loan_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'])),

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
            'sign_time' => "<span id='borrow_sign_time'></span>",

            'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数

            'borrow_user_number' => $this->numTo32($deal['user_id']),
        );
        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        $c = $this->setContract($title, 5,$number, $user_id, $deal['id'], 'TPL_BORROWER_PROTOCAL',$notice_contrace);
        return $c;
    }

    /**
     * 汇赢的《付款委托书》
     *
     * @author wenyanlei 2013-8-27
     * @param $deal 借款申请信息
     * @param $loan_user_list 投资人（出借人）列表
     * @param $borrow_user_info 借款人
     * @return int 发送合同数量
     */
    public function push_payment_order($deal, $loan_user_list, $borrow_user_info) {

        $title = '付款委托书';

        $services_fee = $deal['borrow_amount'] * (floatval($deal['loan_fee_rate']) + floatval($deal['guarantee_fee_rate'])) / 100.0;

        $money = ceilfix($deal['borrow_amount'] - $services_fee);

        $money_up = get_amount($money);

        $number = $this->create_deal_number($deal, $borrow_user_info['user_id'], 000, 6); //合同编号

        $loan_arr = array();
        foreach ($loan_user_list as $loan_user_info){
            $loan_number = $this->create_deal_number($deal, $loan_user_info['id'], $loan_user_info['deal_load_id'], 1);
            $loan_arr[] = array(
                    'number' => $loan_number,
                    'loan_real_name' => $loan_user_info['real_name']
            );
        }

        $notice = array(
            'borrow_money_up' => get_amount($deal['borrow_amount']),
            'borrow_money' => $deal['borrow_amount'],
            'money_up' => $money_up,
            'money' => $money,
            'loan_list' => $loan_arr,
            //借款扩展字段
            'use_info' => $deal['use_info'],
            'house_address' => $deal['house_address'],
            'house_sn' => $deal['house_sn'],

            //借款咨询费
            'consult_fee_rate' => format_rate_for_cont($deal['consult_fee_rate']),
            'consult_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'])),
            //提前还款罚息天数
            'prepay_penalty_days' => $deal['prepay_penalty_days'],

            'borrow_user_number' => $this->numTo32($deal['user_id']),
        );

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $c = $this->setContract($title, 6, $number, $borrow_user_info['user_id'], $deal['id'], 'TPL_DEAL_PAYMENT_ORDER', $notice);
        return $c;
    }





    /**
    * 资产收益权回购通知  (出借人-借款人)
    *
    * @param $deal 订单
    * @param $loan_user_list 出借人列表
    * @param $borrow_user_info 借款人列表
    * @param $user_type
    */
    public function push_buyback_notification($deal, $loan_user_list, $borrow_user_info, $user_type=NULL){
        FP::import("libs.common.app");

        //合同标题
        $title = '资产收益权回购通知';

        foreach ($loan_user_list as $loan_user_info){

            $loan_bank_info = get_user_bank($loan_user_info['id']);
            $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],7); //合同编号

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

            if(empty($user_type)){
                $user_id = $loan_user_info['id'];
            }else{
                $user_id = $deal['user_id'];
            }

            $count[] = $this->setContract($title, 7,$number, $user_id, $deal['id'], 'TPL_BUYBACK_NOTIFICATION',$notice_contrace, 0, $loan_user_info['deal_load_id']);
            $notice_contrace = array(); //清空
            unset($number);
        }
        return sizeof($count);
    }

    /*
     * 新版本借款合同（借款人,出借人,资产管理方,担保公司）
     */

    public function push_loan_contract_v2($deal, $loan_user_list, $borrow_user_info,$agency_info, $advisory_info){
        FP::import("libs.common.app");
        $borrow_bank_info = get_user_bank($deal['user_id']);
        //合同起始时间
        $contract_start_time = $deal['repay_start_time'] > 0 ? strtotime(to_date($deal['repay_start_time'])) : time();

        //还款方式不同，做不同处理
        if($deal['loantype'] == 5){
            $repay_time = $deal['repay_time'].'天';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." day", $contract_start_time));
        }else{
            $repay_time = $deal['repay_time'].'个月';
            $end_time = date("Y-m-d",strtotime("+".$deal['repay_time']." month", $contract_start_time));
        }

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

        //还款类型标记 用户合同显示 A、按月支付收益到期还本。B、按季支付收益到期还本。C、按月等额本息。D、按季等额本息。E、到期支付本金收益。
        switch($deal['loantype']){
            case '1':
                $loan_type_mark = 'D';
                break;
            case '2':
                $loan_type_mark = 'C';
                break;
            case '3':
                $loan_type_mark = 'E';
                break;
            case '4':
                $loan_type_mark = 'A';
                break;
            case '5':
                $loan_type_mark = 'E';
                break;
            case '6':
                $loan_type_mark = 'B';
                break;
            default: $loan_type_mark = '';
        }

        //合同标题
        $title = '借款合同';

        if ($deal['loantype'] == 5) {
            $deal_repay_time = $deal['repay_time'] . "天";
        } else {
            $deal_repay_time = $deal['repay_time'] . "个月";
        }

        foreach ($loan_user_list as $loan_user_info){
            $loan_bank_info = get_user_bank($loan_user_info['id']);
            $number = $this->create_deal_number($deal, $loan_user_info['id'],$loan_user_info['deal_load_id'],1); //合同编号

            $user_company_service = new UserCompanyService();
            $loan_legal_person = $user_company_service->getCompanyLegalInfo($loan_user_info['id']);
            $earning = new EarningService();
            $loan_money_earning = $earning->getEarningMoney($deal['id'], $loan_user_info['loan_money']);
            $loan_money_earning_format = sprintf("%.2f", $loan_money_earning);
            $loan_money_repay= $loan_money_earning_format + $loan_user_info['loan_money'];

            // JIRA#3260-企业账户二期 <fanjingwen@>
            $contractRenderService = new ContractRenderService();
            $loanInfo = $contractRenderService->getLoanInfo($loan_user_info, $loan_bank_info); // 甲方 - 借出方
            $borrowInfo = $contractRenderService->getBorrowInfo($deal['user_id']);  // 乙方 - 借款方
            $platformInfo = $contractRenderService->getPlatformInfo($deal['id']); // 丙方 - 平台方
            $advisoryInfo = $contractRenderService->getAdvisoryInfo($deal['advisory_id']); // 丁方 - 资产管理方
            $agencyInfo = $contractRenderService->getAgencyInfo($deal['agency_id']); // 戊方 - 保证方

            $notice_contrace = array(
                //平台方信息
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
                'borrow_sign_time' => "<span id='borrow_sign_time'>合同签署之日</span>", //借款方

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
                'advisory_sign_time' => "<span id='advisory_sign_time'>合同签署之日</span>", //资产管理方

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
                'agency_sign_time' => "<span id='agency_sign_time'>合同签署之日</span>", //担保方
                // --------------------- over -----------------------------

                'contract_transfer_type' => $contract_transfer_type,
                'base_deal_num' => $base_deal_num,
                'lessee_real_name' => $lessee_real_name,
                'loan_application_type' => $loan_application_type,
                'loan_type_mark' => $loan_type_mark,

                'loan_fee_rate_type' => $loan_fee_rate_type,
                'consult_fee_rate_type' => $consult_fee_rate_type,
                'pay_fee_rate_type' => $pay_fee_rate_type,
                'guarantee_fee_rate_type' => $guarantee_fee_rate_type,

                'loan_fee_rate' => format_rate_for_cont($deal['loan_fee_rate']),
                'guarantee_fee_rate' => format_rate_for_cont($deal['guarantee_fee_rate']),
                'pay_fee_rate' => format_rate_for_cont($deal['pay_fee_rate']),
                'leasing_money' => format_rate_for_cont($deal['borrow_amount']),
                'leasing_money_uppercase' => get_amount($deal['borrow_amount']),
                'leasing_contract_title' => $leasing_contract_title,

                'loan_real_name' => $loan_user_info['real_name'],
                'loan_user_name' => $loan_user_info['user_name'],
                'loan_user_idno' => $loan_user_info['idno'],
                'loan_bank_user' => isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '',
                'loan_bank_card' => isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '',
                'loan_bank_name' => isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '',

                'loan_legal_person' => $loan_legal_person['legal_person'],
                'loan_address' => $loan_user_info['address'],

                'loan_money' => $loan_user_info['loan_money'],
                'loan_money_uppercase' => get_amount($loan_user_info['loan_money']),
                'loan_money_repay' => $loan_money_repay,
                'loan_money_repay_uppercase' => get_amount($loan_money_repay),
                'loan_money_earning' => $loan_money_earning_format,
                'loan_money_earning_uppercase' => get_amount($loan_money_earning_format),
                'loan_user_mobile' => $loan_user_info['mobile'],
                'borrow_bank_user' => $borrow_bank_info['card_name'],
                'borrow_bank_card' => $borrow_bank_info['bankcard'],
                'borrow_bank_name' => $borrow_bank_info['bankname'].$borrow_bank_info['bankzone'],
                'borrow_money' => $deal['borrow_amount'],
                'uppercase_borrow_money' => get_amount($deal['borrow_amount']),
                'start_time' => date("Y-m-d",$contract_start_time),
                'repay_time' => $deal['repay_time'],
                'deal_repay_time' => $deal_repay_time,
                'repay_time_unit' => $repay_time,
                'loantype' => $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']],
                'end_time' => $end_time,

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
            $notice_contrace['repayment_table'] = "";
            $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息
            $count[] = $this->setContract($title, 1,$number,  $loan_user_info['id'], $deal['id'], 'TPL_LOAN_CONTRACT_V2',$notice_contrace, 0, $loan_user_info['deal_load_id']);
            $count[] = $this->setContract($title, 1,$number,  $deal['user_id'], $deal['id'], 'TPL_LOAN_CONTRACT_V2',$notice_contrace, 0, $loan_user_info['deal_load_id']);
            $count[] = $this->setContract($title, 1,$number,  0, $deal['id'], 'TPL_LOAN_CONTRACT_V2',$notice_contrace, $agency_info['id'], $loan_user_info['deal_load_id']);
            $count[] = $this->setContract($title, 1,$number, 0, $deal['id'], 'TPL_LOAN_CONTRACT_V2',$notice_contrace, $advisory_info['id'], $loan_user_info['deal_load_id']);
            $notice_contrace = array(); //清空
            unset($number);
        }
        return sizeof($count);
    }

    /*
     * 新版本借款人咨询服务协议
     */
    public function push_borrower_protocal_v2($deal, $borrow_user_info, $advisory_info){
        FP::import("libs.common.app");
        $title = '借款人平台服务协议';
        $deal_service = new DealService();
        if ($deal_service->isDealLeaseByType($deal['type_id'])) {
            $title = '资产转让方咨询服务协议';
        }

        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['advisory_id']);//担保公司信息

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

        $borrow_bank_info = get_user_bank($deal['user_id']);

        $number = $this->create_deal_number($deal, $borrow_user_info['user_id'],000,5); //合同编号
        $user_id = $borrow_user_info['user_id'];

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

            'loan_money' => $deal['borrow_amount'],
            'repay_time' => $deal['repay_time'],
            'repay_time_unit' => $deal['loantype'] == 5 ? $deal['repay_time'].'天' : $deal['repay_time'].'个月',
            'loan_fee_rate' => format_rate_for_cont($deal['loan_fee_rate']),
            'loan_fee_rate_part' => format_rate_for_cont(Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'])),
            'contract_transfer_type' => $contract_transfer_type,

            'loan_fee_rate_type' => $loan_fee_rate_type,
            'consult_fee_rate_type' => $consult_fee_rate_type,
            'pay_fee_rate_type' => $pay_fee_rate_type,
            'guarantee_fee_rate_type' => $guarantee_fee_rate_type,

            'base_deal_num' => $base_deal_num,

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

            //暂时使用主站平台名称，后续使用独立icp
            'platform_show_name' => '网信理财',
            'platform_domain' => 'www.firstp2p.com',

            //把乙方的签署时间改为文字提示
            'sign_time' => "<span id='sign_time'>合同签署之日</span>",
            'borrow_sign_time' => "<span id='borrow_sign_time'>合同签署之日</span>", //借款方
            'advisory_sign_time' => "<span id='advisory_sign_time'>合同签署之日</span>", //资产管理方

            'prepay_penalty_days' => $deal['prepay_penalty_days'],//提前还款罚息天数
        );
        $notice_contrace = array_merge($notice_contrace, $borrow_user_info);//借款人信息和公司信息

        $c = $this->setContract($title, 5,$number, $user_id, $deal['id'], 'TPL_BORROWER_PROTOCAL_V2',$notice_contrace);
        $c = $this->setContract($title, 5,$number,0, $deal['id'], 'TPL_BORROWER_PROTOCAL_V2',$notice_contrace, $advisory_info['id']);
        return $c;
    }



    /**
    * 生成合同编号
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

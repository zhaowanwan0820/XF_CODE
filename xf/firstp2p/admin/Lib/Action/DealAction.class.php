<?php
// +----------------------------------------------------------------------
// | easethink 易想商城系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
FP::import("libs.libs.msgcenter");
FP::import("app.deal");

//use app\models\service\Finance;
use libs\utils\Finance;
use app\models\service\LoanType;
use app\models\dao\Deal;
use app\models\dao\User;
use app\models\dao\DealTag;
use app\models\dao\DealLoad;
use app\models\dao\DealLoanRepay;
use app\models\dao\DealSite;
use app\models\service\Earning;

use core\service\DealService;
use core\service\DealTagService;
use core\service\DealRepayService;
use core\service\DealProjectService;
use core\service\CouponLogService;
use core\service\ContractService;
use core\service\ContractNewService;
use core\service\DealContractService;
use core\service\UserThirdBalanceService;
use core\service\P2pDepositoryService;
use core\service\P2pIdempotentService;
use core\service\DealProjectRepayYjService;
use core\service\DealLoanPartRepayService;

use core\dao\DealContractModel;
use core\dao\CouponLogModel;
use libs\vfs\Vfs;
use libs\vfs\VfsException;
use libs\utils\PaymentApi;
use core\service\PaymentService;
use core\dao\FinanceQueueModel;
use core\dao\UserModel;
use core\dao\DealQueueModel;
use core\dao\DealQueueInfoModel;
use libs\lock\LockFactory;
use core\service\DealLoadService;
use core\dao\DealCompoundModel;
use core\service\DealCompoundService;
use core\dao\JobsModel;
use core\service\DealProjectCompoundService;
use core\service\CouponDealService;
use core\dao\DealModel;
use core\service\MsgBoxService;
use core\dao\DealLoanTypeModel;
use core\dao\DealRepayModel;
use core\dao\DealLoadModel;
use core\dao\DealTagModel;
use core\dao\UserCompanyModel;
use libs\web\Url;
use libs\utils\Aes;
use core\service\UserService;
use core\dao\EnterpriseModel;
use core\dao\DealProjectModel;
use core\dao\DealExtModel;
use core\dao\DealPrepayModel;
use core\dao\UserCarryModel;
use core\dao\SupervisionIdempotentModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealLoanPartRepayModel;
use core\service\UserCarryService;

use NCFGroup\Protos\Contract\RequestGetCategorys;
use NCFGroup\Protos\Contract\RequestSetDealCId;
use NCFGroup\Protos\Contract\RequestUpdateDealCId;
use NCFGroup\Common\Library\Idworker;
use core\service\P2pDealReportService;
use libs\utils\Logger;
use core\dao\UserBankcardModel;
use core\service\vip\VipService;
use core\service\DealCustomUserService;
use core\service\BwlistService;
use libs\db\Db;
use core\service\offlineRepay\OfflineRepayService;

class DealAction extends CommonAction{

    public $project;
    public $deal;
    public $deal_ext;
    public $deal_compound;
    public $deal_data;
    public $deal_ext_data;

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型



    public function index(){

        //jira:4308 贷款类型默认为专享
        $_REQUEST['deal_type'] = isset($_REQUEST['deal_type']) ? intval($_REQUEST['deal_type']):2;

        //分类
        /* $cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $cate_tree = D("DealCate")->toFormatTree($cate_tree,'name');
        $this->assign("cate_tree",$cate_tree); */
        if (!$this->is_cn) {
            $this->assign('sitelist', $GLOBALS['sys_config']['TEMPLATE_LIST']);
        } else {
            $this->assign('sitelist', $GLOBALS['sys_config']['TEMPLATE_LIST_CN']);
        }
        //开始加载搜索条件
        $map['is_delete'] = 0;
        $map['publish_wait'] = 0;
        //非利滚利项目
        $deal_type = $this->getDealType();
        $map['deal_type'] = $deal_type;
        $this->assign('deal_type', $deal_type);

        if(intval($_REQUEST['id'])>0){
            $map['id'] = intval($_REQUEST['id']);
        }

        if(trim($_REQUEST['name'])!=''){
            $name = addslashes(trim($_REQUEST['name']));
            $map['name'] = array('like','%'.$name.'%');
        }

        /* if(intval($_REQUEST['cate_id'])>0)
        {
            FP::import("libs.utils.child");
            $child = new Child("deal_cate");
            $cate_ids = $child->getChildIds(intval($_REQUEST['cate_id']));
            $cate_ids[] = intval($_REQUEST['cate_id']);
            $map['cate_id'] = array("in",$cate_ids);
        } */

        if(intval($_REQUEST['site_id']) > 0){
            $sql  ="select deal_id from ".DB_PREFIX."deal_site where site_id =".intval($_REQUEST['site_id']);

            $id_res = $GLOBALS['db']->get_slave()->getAll($sql);
            $id_arr = array();
            $ids = '';
            foreach($id_res as $dealid){
                $id_arr[] = $dealid['deal_id'];
            }
            if($id_arr){
                $ids = implode(',', $id_arr);
            }
            $map['id'] = array("in",$ids);
        }


        if(trim($_REQUEST['real_name'])!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%" . $real_name . "%'";

            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $map['user_id'] = array("in",$ids);
        }

        if(isset($_REQUEST['deal_status']) && trim($_REQUEST['deal_status']) != '' && trim($_REQUEST['deal_status']) != 'all'){
            $map['deal_status'] = array("eq",intval($_REQUEST['deal_status']));
        }

        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` like '%" . trim($_REQUEST['project_name']) . "%')";
        }

        // 存管报备状态
        if(trim($_REQUEST['jys_record_number']) != ''){
            $map['jys_record_number'] = array("eq",$_REQUEST['jys_record_number']);
        }

        // 放款审批单编号
        if (!empty($_REQUEST['approve_number'])) {
            $map['approve_number'] = array('eq', addslashes(trim($_REQUEST['approve_number'])));
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        $name=$this->getActionName();
        $model = DI ($name);
        $userIDArr = array();

        //机构管理后台
        if ($orgMap = $this->orgCondition()) {
            unset($map['deal_type']);
            $map = array_merge($map, $orgMap);
        }

        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
            $deal_pro_service = new DealProjectService();
            //jira#5361 增加“平台费折扣率”
            $extModel = new DealExtModel();
            foreach($list as $k=>$v){
                $list[$k]['ecid'] = Aes::encryptForDeal($v['id']);
                $list[$k]['is_entrust_zx'] = $deal_pro_service->isProjectEntrustZX($v['project_id']);
                $extRow = $extModel->findBy("deal_id = " . $v['id'], "discount_rate", array(), true);
                $list[$k]['discount_rate'] = $extRow['discount_rate'];
                $userIDArr[] = $v['user_id'];
            }
            $this->assign('list', $list);
        }

        // JIRA#3260 企业账户二期 <fanjingwen@>
        // 获取借款人相关的基本信息
        $userServ = new UserService();
        $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);

        foreach($listOfBorrower as $k=>$v){
            $borrowUserService = new UserService($k);
            if($borrowUserService->isEnterprise()){
                $enterprise_info = $borrowUserService->getEnterpriseInfo(true);
                $borrowName = $enterprise_info['company_name'];
            }else{
                $company_info = \core\dao\UserCompanyModel::instance()->findByUserId($v['id']);
                $borrowName = $company_info['name'];
            }
            $listOfBorrower[$k]['borrowName'] = $borrowName;
        }

        $this->assign('listOfBorrower', $listOfBorrower);
        // -------------- over -----------------
        $template = $this->is_cn ? 'index_cn' : 'index';
        $template = !empty($this->orgData) ? 'index_org' : $template;
        $this->display ($template);
        return;
    }

    public function monitor() {
        $map['deal_status'] = array("eq", 1);
        $map['is_effect'] = array("eq", 1);
        $map['is_delete'] = array("eq", 0);

        $name=$this->getActionName();
        $model = DI($name);
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
            foreach($list as $k=>$v){
                $list[$k]['url'] = get_deal_domain($v['id']) . Url::gene("d", "", Aes::encryptForDeal($v['id']), true);
            }
            $this->assign('list', $list);
        }
        $this->display ();
        return;
    }

    public function compound(){
        //搜索条件
        $map['deal_type'] = 1;
        $map['is_delete'] = 0;
        $map['publish_wait'] = 0;

        if(intval($_REQUEST['id'])>0){
            $map['id'] = intval($_REQUEST['id']);
        }

        if(trim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
        }

        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` = '" . trim($_REQUEST['project_name']) . "')";
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $model = DI (MODULE_NAME);
        $this->_list ( $model, $map );

        $list = $this->get('list');

        $userIDArr = array();
        if (is_array($list) && count($list) > 0 ) {
            $dealload_service = new DealLoadService();
            $compound_service = new DealCompoundService();
            $apply_model = new \core\dao\CompoundRedemptionApplyModel();

            $today_start = to_timespan('today');
            $yesterday_start = to_timespan('yesterday');
            foreach($list as &$item){
                $deal_ids[] = $item['id'];
                $item['ecid'] = Aes::encryptForDeal($item['id']);
                $item['rate_day'] =  $compound_service->getDayRateByDealId($item['id']) * 100;

                $item['redeemed_principal'] = $compound_service->getPayedCompoundPrincipal($item['id']);    // 已赎回本金
                $item['redeemed_interest'] = $compound_service->getPayedCompoundInterest($item['id']);
                $item['redeeming_principal'] = $compound_service->getUnpayedCompoundPrincipal($item['id']);   // 未赎回本金;
                $item['redeeming_interest'] = $compound_service->getUnPayedCompoundInterest($item['id']);
                /*
                $item['load_all'] = $dealload_service->getLoadStatByDeal($item['id']);
                $item['load_yesterday'] = $dealload_service->getLoadStatByDeal($item['id'], $yesterday_start, $today_start-1);
                $item['load_today'] = $dealload_service->getLoadStatByDeal($item['id'], $today_start);

                $item['apply_ok'] = $apply_model->getSumMoneyByDeal($item['id'], 1);
                $item['apply_no'] = $apply_model->getSumMoneyByDeal($item['id'], 0);

                $item['keyong'] = $item['load_all'] - $item['apply_ok'] - $item['apply_no'];
                 */
                $new_list[$item['id']] = $item;

                // JIRA#3260
                $userIDArr[] = $item['user_id'];
            }

            // JIRA#3260 企业账户二期 <fanjingwen@>
            // 获取借款人相关的基本信息
            $userServ = new UserService();
            $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
            // -------------- over -----------------

            $compounds = $compound_service->getInfoByDealIds($deal_ids);
            foreach ($compounds as $row) {
                $new_list[$row['deal_id']]['redemption_period'] = $row['redemption_period'];
                $new_list[$row['deal_id']]['lock_period'] = $row['lock_period'];

            }
        } else {
            $new_list = array();
            $listOfBorrower = array();
        }
        $this->assign('list', $new_list);
        $this->assign('listOfBorrower', $listOfBorrower);


        $this->display ();
    }


    /**
     *
     * @Title: twenty_four
     * @Description: 24小时内即将流标
     * @param
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    public function twenty_four(){
        //分类
        $cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $cate_tree = D("DealCate")->toFormatTree($cate_tree,'name');
        $this->assign("cate_tree",$cate_tree);

        //开始加载搜索条件
        if(intval($_REQUEST['id'])>0)
            $map['id'] = intval($_REQUEST['id']);
        $map['is_delete'] = 0;
        if(trim($_REQUEST['name'])!='')
        {
            $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
        }

        if(intval($_REQUEST['cate_id'])>0)
        {
            FP::import("libs.utils.child");
            $child = new Child("deal_cate");
            $cate_ids = $child->getChildIds(intval($_REQUEST['cate_id']));
            $cate_ids[] = intval($_REQUEST['cate_id']);
            $map['cate_id'] = array("in",$cate_ids);
        }


        if(trim($_REQUEST['user_name'])!='')
        {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name like '%".trim($_REQUEST['user_name'])."%'";

            $ids = $GLOBALS['db']->getOne($sql);
            $map['user_id'] = array("in",$ids);
        }

        if(isset($_REQUEST['deal_status']) && trim($_REQUEST['deal_status']) != '' && trim($_REQUEST['deal_status']) != 'all'){
            $map['deal_status'] = array("eq",intval($_REQUEST['deal_status']));
        }

        $map['publish_wait'] = 0;

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        unset($map);
        $map = " publish_wait = 0 AND is_delete = 0 AND deal_type = 0 AND deal_status = 1 AND (start_time + enddate*24*3600 - ".get_gmtime()." ) < 24 * 3600";
        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ('index');
        return;
    }

    /**
     *
     * @Title: wait_confirm
     * @Description: 等待用户确认的单子
     * @param
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    public function wait_confirm(){

        $voList = $this->wait_confirm_factory_data();
        $this->assign ( 'list', $voList );

        $this->display ('index');
        return;
    }

    /**
     *
     * @Title: confirm
     * @Description: 已经确认的单子
     * @param
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    public function confirm(){

        $voList = $this->confirm_factory_data();
        $this->assign ( 'list', $voList );

        $this->display ('index');
        return;
    }

    public function three()
    {
        $this->assign("main_title",L("DEAL_THREE"));
        //分类
        $cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $cate_tree = D("DealCate")->toFormatTree($cate_tree,'name');
        $this->assign("cate_tree",$cate_tree);

        //开始加载搜索条件
        if(intval($_REQUEST['id'])>0)
            $map['id'] = intval($_REQUEST['id']);
        $map['is_delete'] = 0;
        if(trim($_REQUEST['name'])!='')
        {
            $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
        }

        if(intval($_REQUEST['cate_id'])>0)
        {
            FP::import("libs.utils.child");
            $child = new Child("deal_cate");
            $cate_ids = $child->getChildIds(intval($_REQUEST['cate_id']));
            $cate_ids[] = intval($_REQUEST['cate_id']);
            $map['cate_id'] = array("in",$cate_ids);
        }


        if(trim($_REQUEST['user_name'])!='')
        {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name like '%".trim($_REQUEST['user_name'])."%'";

            $ids = $GLOBALS['db']->getOne($sql);
            $map['user_id'] = array("in",$ids);
        }

        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` = '" . trim($_REQUEST['project_name']) . "')";
        }

        $map['publish_wait'] = 0;
        $temp_ids = M("Deal")->where("publish_wait = 0 AND deal_type = 0 AND deal_status = 4 AND (next_repay_time - ".get_gmtime().")/24/3600 between 0 AND 3 ")->Field('id')->findAll();
        $deal_ids[] = 0;
        foreach($temp_ids as $k=>$v){
            $deal_ids[] = $v['id'];
        }
        $map['id'] = array("in",implode(",",$deal_ids));

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ();
        return;
    }

    public function three_msg(){
        $map['is_delete'] = 0;
        if(trim($_REQUEST['name'])!='')
        {
            $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
        }

        if(intval($_REQUEST['cate_id'])>0)
        {
            FP::import("libs.utils.child");
            $child = new Child("deal_cate");
            $cate_ids = $child->getChildIds(intval($_REQUEST['cate_id']));
            $cate_ids[] = intval($_REQUEST['cate_id']);
            $map['cate_id'] = array("in",$cate_ids);
        }


        if(trim($_REQUEST['user_name'])!='')
        {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name like '%".trim($_REQUEST['user_name'])."%'";

            $ids = $GLOBALS['db']->getOne($sql);
            $map['user_id'] = array("in",$ids);
        }

        $map['publish_wait'] = 0;
        $temp_ids = M("Deal")->where("publish_wait = 0 AND deal_status = 4 AND (next_repay_time - ".get_gmtime().")/24/3600 <= 3 AND  (send_three_msg_time < next_repay_time OR send_three_msg_time = 0) ")->Field('id')->findAll();
        $deal_ids[] = 0;
        foreach($temp_ids as $k=>$v){
            $deal_ids[] = $v['id'];
        }
        $map['id'] = array("in",implode(",",$deal_ids));

        $list = D ("Deal")->where($map)->findAll();
        //发送信息
        foreach($list as $k=>$v){
            $next_repay_time = 0;
            if($v['next_repay_time'] > 0)
                $next_repay_time = $v['next_repay_time'];
            else
                $next_repay_time = next_replay_month($v['repay_start_time']);

            //计算最后一个月该还多少
            $repay_money = pl_it_formula($v['borrow_amount'],$v['rate']/12/100,$v['repay_time']);

            $idx = ((int)to_date(get_gmtime(),"Y") - (int)to_date($v['repay_start_time'],"Y"))*12 + ((int)to_date(get_gmtime(),"m") - (int)to_date($v['repay_start_time'],"m"));
            if($v['repay_time']==$idx){
                $repay_money = $repay_money*12 - ($idx-1)*round($repay_money,2);
            }

            $v['name'] = get_deal_title($v['name'], '', $v['id']);

            //站内信
            $content = "您在".app_conf("SHOP_TITLE")."的借款 “<a href=\"".url("index","deal",array("id"=>$v['id']))."\">".$v['name']."</a>”，" .
                "最近一期还款将于".to_date($next_repay_time,"d")."日到期，需还金额".round($repay_money,2)."元。";

            $group_arr = array(0,$v['user_id']);
            sort($group_arr);
            $group_arr[] =  4;

            $msg_data['content'] = $content;
            $msg_data['to_user_id'] = $v['user_id'];
            $msg_data['create_time'] = get_gmtime();
            $msg_data['type'] = 0;
            $msg_data['group_key'] = implode("_",$group_arr);
            $msg_data['is_notice'] = 12;
            $msg_data['fav_id'] = $v['id'];

            /*
            $GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg_data);
            $id = $GLOBALS['db']->insert_id();
            $GLOBALS['db']->query("update ".DB_PREFIX."msg_box set group_key = '".$msg_data['group_key']."_".$id."' where id = ".$id);
            */
            $msgBoxService = new MsgBoxService();
            $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'], "", $msg_data['content']);
            $user_info  = D("User")->where("id=".$v['user_id'])->find();

            // email and sms start
            $msgcenter = new Msgcenter();
            //邮件
            if(app_conf("MAIL_ON")==1)
            {
                $tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_DEAL_THREE_EMAIL'");

                $notice['user_name'] = $user_info['user_name'];
                $notice['deal_name'] = $v['name'];
                $notice['deal_url'] = get_domain().url("index","deal",array("id"=>$v['id']));
                $notice['repay_url'] = get_domain().url("index","uc_deal#refund");
                $notice['repay_time_y'] = to_date($next_repay_time,"Y");
                $notice['repay_time_m'] = to_date($next_repay_time,"m");
                $notice['repay_time_d'] = to_date($next_repay_time,"d");
                $notice['site_name'] = app_conf("SHOP_TITLE");
                $notice['repay_money'] = round($repay_money,2);
                $notice['help_url'] = get_domain().url("index","helpcenter");
                $notice['msg_cof_setting_url'] = get_domain().url("index","uc_msg#setting");

                $GLOBALS['tmpl']->assign("notice",$notice);

                $msgcenter->setMsg($user_info['email'], $user_info['id'], $notice, 'TPL_DEAL_THREE_EMAIL', "三日内还款通知");
            }

            //短信
            if(app_conf("SMS_ON")==1)
            {
                $tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_DEAL_THREE_SMS'");
                // 区分企业用户与个人用户
                if ($user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $userName = get_company_shortname($user_info['id']); // by fanjingwen
                } else {
                    $_mobile = $user_info['mobile'];
                    $userName = $user_info['user_name'];
                }
                $notice['user_name'] = $userName;
                $notice['deal_name'] = $v['name'];
                $notice['repay_time_y'] = to_date($next_repay_time,"Y");
                $notice['repay_time_m'] = to_date($next_repay_time,"m");
                $notice['repay_time_d'] = to_date($next_repay_time,"d");
                $notice['site_name'] = app_conf("SHOP_TITLE");
                $notice['repay_money'] = round($repay_money,2);

                $GLOBALS['tmpl']->assign("notice",$notice);
                // SMSSend 三日内还款通知 暂不支持企业用户
                \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_DEAL_THREE_SMS', $notice, $user_info['id']);
            }
            $msgcenter->save();
            // email and sms end

            $GLOBALS['db']->autoExecute(DB_PREFIX."deal",array("send_three_msg_time"=>$next_repay_time),"UPDATE","id=".$v['id']);
        }

        //成功提示
        if($deal_ids){
            save_log(implode(",",$deal_ids)."发送三日内还款提示",1);
        }
        $this->success("发送成功");

    }

    public function yuqi()
    {
        unset($_REQUEST['m'], $_REQUEST['a']);
        $_REQUEST['report_status'] = 0;
        $this->assign("main_title",L("DEAL_YUQI"));

        if (!empty($_REQUEST['ref'])) {
            $_REQUEST = \es_session::get('seKeyDealYuqi');
            // 记录分页参数
            if (isset($_GET['p'])) {
                $_REQUEST['p'] = (int)$_GET['p'];
                \es_session::set('seKeyDealYuqi', $_REQUEST);
            }else{
                $_GET['p'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
            }
        } else if (isset($_REQUEST['report_status'])) {
            \es_session::set('seKeyDealYuqi', $_REQUEST);
        }else{
            \es_session::delete('seKeyDealYuqi');
        }

        $aliasRepayTable = "t1";
        $aliasDealTable = "t2";

        $dealService = new \core\service\DealService();
        $userService = new \core\service\UserService();

        $requestRepayTimeBeginCondition = $requestRepayTimeEndCondition = '';
        if($_REQUEST['repay_time_begin']) {
            $requestRepayTimeBegin = to_timespan($_REQUEST['repay_time_begin'] . " 0:0:0");
            $requestRepayTimeBeginCondition = " AND {$aliasRepayTable}.`repay_time` >= {$requestRepayTimeBegin} ";
        }

        if($_REQUEST['repay_time_end']) {
            $requestRepayTimeEnd = to_timespan($_REQUEST['repay_time_end'] . " 23:59:59");
            $requestRepayTimeEndCondition = " AND {$aliasRepayTable}.`repay_time` <= {$requestRepayTimeEnd} ";
        }

        $whereDealId = "";
        $id = trim($_REQUEST['deal_id']);
        if(is_numeric($id)) {
            $whereDealId = "and {$aliasRepayTable}.`deal_id` = {$id}";
        }else if(trim($_REQUEST['name'])!='') {
            $sql = "select group_concat(id) from " . DB_PREFIX . "deal where name like '%" . addslashes(trim($_REQUEST['name'])) . "%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if($ids) {
                $whereDealId = "and {$aliasRepayTable}.`deal_id` in " . "(" . $ids . ") ";
            } else {//当没有，设置永远错误的条件，让搜索的结果为空
                $whereDealId = "and 1 < 0";
            }
        }

        // 全部查出来方便 列表筛选
        $deal_repay_mode_contract_white = array();
        $where_contract_ids = '';
        $whereContractTplType = '';
        $contract_ids = BwlistService::getValueList(DealRepayModel::DEAL_REPAY_MODE_WHITE_TYPE_KEY);
        if (!empty($contract_ids)){
            foreach($contract_ids as $con_id){
                $deal_repay_mode_contract_white[$con_id['value']] = $con_id['value'];
            }

            if (!empty($deal_repay_mode_contract_white)){
                $where_contract_ids = implode(',',$deal_repay_mode_contract_white);
            }
        }
        if (!empty($_REQUEST['repay_mode_holiday']) && !empty($where_contract_ids)){

            // 节前
            if ($_REQUEST['repay_mode_holiday'] == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE){

                $whereContractTplType .= " and {$aliasDealTable}.`contract_tpl_type` not in ({$where_contract_ids}) ";
            }
            if ($_REQUEST['repay_mode_holiday'] == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER){

                $whereContractTplType .= " and {$aliasDealTable}.`contract_tpl_type` in ({$where_contract_ids}) ";
            }

        }

        // 没有白名单全是节前
        if (!empty($_REQUEST['repay_mode_holiday']) && empty($where_contract_ids) && $_REQUEST['repay_mode_holiday'] == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER){
            $whereContractTplType = ' and 1 < 0 ';
        }

        $whereIgnoreProjectId = "";
        //去掉盈嘉线下还款的项目
        $repayYjService = new DealProjectRepayYjService();
        $yjProjectIds = $repayYjService->getYjProjectIds();
        if (!empty($yjProjectIds)) {
            $whereIgnoreProjectId = " AND {$aliasDealTable}.`project_id` NOT IN " . "(" . implode(",",$yjProjectIds) . ") ";
        }
        // jira 6427 去掉报备状态
        //$reportStatus = (isset($_REQUEST['report_status']) && $_REQUEST['report_status']== '0') ? 0 : 1;
        //$whereReport = " and {$aliasDealTable}.`report_status`=".$reportStatus." ";
        $whereReport = '';

        //机构管理后台,借道$whereReport
        $whereReport .= $this->orgCondition(false, $aliasDealTable);

        // 获取还款列表中审核信息的 deal_id 过滤条件，以及渲染页面 repay prepay 信息
        list($whereDealId, $repays, $prepays) = $this->getYuQiAuditDealCondition($aliasRepayTable, $id, $ids, $whereDealId);
        $this->assign('repays', $repays);
        $this->assign('prepays', $prepays);
        $this->assign('role', $this->getRole());
        $role = $this->getRole();



        $this->assign('dealAgency', MI('DealAgency')->where('is_effect = 1 and type=2')->getField('id,name'));
        $querystring = array();
        foreach ($_GET as $k => $v) {
            if (!empty($v)) {
                if ($k == 'deal_id') {
                    continue;
                }
                $querystring[$k] = $v;
            }
        }
        $this->assign('querystring', http_build_query($querystring));

        $whereUserId = "";
        if(trim($_REQUEST['user_name'])!='')
        {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name like '%". addslashes(trim($_REQUEST['user_name']))."%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if($ids) {
                $whereUserId = " and {$aliasRepayTable}.`user_id` in " . "(" . $ids . ") ";
            } else {//当没有，设置永远错误的条件，让搜索的结果为空
                $whereUserId = " and 1 < 0 ";
            }
        }

        // 增加贷款类型
        $deal_type = $this->getDealType(DealModel::DEAL_TYPE_EXCHANGE);
        $deal_type_where = " `deal_type` IN ({$deal_type})";
        $this->assign('deal_type', $deal_type);
        $isP2P = (!isset($_REQUEST['report_status']) || $_REQUEST['report_status'] == '1') ? true :false;

        if (isset ($_REQUEST ['_sort'])) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        } else {
            $sort =  'desc';
        }
        $orderBy = " ORDER by {$aliasDealTable}.`id` {$sort} ";

        // 搜索条件里加上repay_type、type_id和is_during_repay
        $typeIdSql = !empty($_REQUEST['type_id']) ? " AND {$aliasDealTable}.`type_id` = ".$_REQUEST['type_id']." "  : "";
        $repayTypeSql = $_REQUEST['repay_type'] != "" ? " AND {$aliasRepayTable}.`repay_type` = ".$_REQUEST['repay_type']." "  : "";
        $isDuringRepaySql = $_REQUEST['is_during_repay'] != "" ? " and {$aliasDealTable}.`is_during_repay` = " . intval($_REQUEST['is_during_repay']) : "";

        // 项目名搜索
        $where_project = !empty($_REQUEST['project_name']) ? " AND {$aliasDealTable}.`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE name LIKE '%" . trim($_REQUEST['project_name']) . "%')" : '';
        /*
         *  SELECT count(1) FROM firstp2p_deal_repay t1
         *  LEFT JOIN firstp2p_deal t2
         *  ON t1.deal_id = t2.id and t1.repay_time < {$requestRepayTime} and t1.status = 0
         *  WHERE t2.is_delete = 0 AND t2.publish_wait = 0 AND t2.deal_status = 4 AND t2.is_during_repay = 0
         * */
        $countSql = "SELECT count(1)
            FROM " . DB_PREFIX . "deal_repay {$aliasRepayTable} " . " LEFT JOIN " . DB_PREFIX . "deal {$aliasDealTable} " .
            " ON {$aliasRepayTable}.`deal_id` = {$aliasDealTable}.`id` {$requestRepayTimeBeginCondition} {$requestRepayTimeEndCondition} AND
        {$aliasRepayTable}.`status` = 0 " . $whereDealId . $whereUserId . $whereReport . $whereIgnoreProjectId . $whereContractTplType .
            " WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 {$where_project} AND {$aliasDealTable}.{$deal_type_where}". $typeIdSql . $repayTypeSql . $isDuringRepaySql ;
            //" WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 AND {$aliasDealTable}.`is_during_repay` = 0 ";

        /*
         *  SELECT t1.repay_time, t1.repay_money, t1.user_id, t2.id, t2.name, t2.borrow_amount, t2.rate,
         *  t2.loantype, t2.repay_time as repay_period, t2.deal_status, t2.parent_id
         *  FROM firstp2p_deal_repay t1
         *  LEFT JOIN firstp2p_deal t2
         *  ON t1.deal_id = t2.id and t1.repay_time < {$requestRepayTime} and t1.status = 0
         *  WHERE t2.is_delete = 0 AND t2.publish_wait = 0 AND t2.deal_status = 4 AND t2.is_during_repay = 0
         * */
        //不显示正在还款的表
        $sql = "SELECT {$aliasRepayTable}.`id` as deal_repay_id, {$aliasRepayTable}.`repay_type`, {$aliasRepayTable}.`repay_time`, {$aliasRepayTable}.`repay_money`, {$aliasRepayTable}.`user_id`,
            {$aliasDealTable}.`id`, {$aliasDealTable}.`name`, {$aliasDealTable}.`borrow_amount`, {$aliasDealTable}.`rate`, {$aliasDealTable}.`advisory_id`,{$aliasDealTable}.`type_id`,
            {$aliasDealTable}.`loantype`, {$aliasDealTable}.`repay_time` as repay_period, {$aliasDealTable}.`deal_status`,
            {$aliasDealTable}.`parent_id`, {$aliasDealTable}.`is_during_repay`, {$aliasDealTable}.`project_id` FROM " . DB_PREFIX . "deal_repay {$aliasRepayTable} " . " LEFT JOIN " . DB_PREFIX . "deal {$aliasDealTable} " .
        " ON {$aliasRepayTable}.`deal_id` = {$aliasDealTable}.`id` {$requestRepayTimeBeginCondition} {$requestRepayTimeEndCondition} AND
        {$aliasRepayTable}.`status` = 0 " . $whereDealId . $whereUserId . $whereReport . $whereIgnoreProjectId . $whereContractTplType .
            " WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 {$where_project} AND {$aliasDealTable}.{$deal_type_where}". $typeIdSql . $repayTypeSql . $isDuringRepaySql . $orderBy;
            //" WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 AND {$aliasDealTable}.`is_during_repay` = 0 " . $orderBy;
        $count = $GLOBALS['db']->get_slave()->getOne($countSql);

        $p = new Page ($count, "");
        $limit = " limit {$p->firstRow}, {$p->listRows}";
        $sql .= $limit;
        $voList = $GLOBALS['db']->get_slave()->getAll($sql, array(), true);

        //处理时间，前端只做展示，以后台的时间为准。
        $deal_pro_service = new DealProjectService();
        $dealLoanPartRepayModel = new DealLoanPartRepayModel();
        foreach($voList as $key => &$repay) {
            $repay['deal_info'] = DealModel::instance()->findViaSlave($repay['id']);
            $repay['deal_ext'] = \core\dao\DealExtModel::instance()->getInfoByDeal($repay['id']);
            $repay['is_part_user_repay'] = 0;
            if($dealLoanPartRepayModel->isPartRepay($repay['deal_repay_id'])) {
                $repay['is_part_user_repay'] = 1;
                $deal_repay_model = new DealRepayModel();
                $repayPart = $deal_repay_model->find($repay['deal_repay_id']);
                $repayPart = $dealLoanPartRepayModel->formatPartRepay($repayPart,$repay['deal_repay_id'],DealLoanPartRepayModel::STATUS_SAVED);
                //$repay['repay_money'] = $repayPart['repay_money'];
                $repay['repay_money'] = $repayPart['repay_money'] == 0 ? $dealLoanPartRepayModel->getNotRepayMoney($repay['deal_repay_id']) : $repayPart['repay_money'];
            }
            $repay['repay_alarm'] = 0; // repay_alarm是判断该笔还款是否超过1小时，方便运营人员查看
            if($repay['is_during_repay'] == DealModel::DURING_REPAY){
                // 处于正在还款中，则查询幂等表(type-3,result-0 还款未回调)
                $condition =sprintf("`repay_id`= '%d' AND `type`= '%d' AND `result` = '%d' ORDER BY `id` DESC limit 1",
                   $repay['deal_repay_id'], P2pDepositoryService::IDEMPOTENT_TYPE_REPAY, P2pIdempotentService::RESULT_WAIT );
                $repayResult = SupervisionIdempotentModel::instance()->findByViaSlave($condition);
                if(!empty($repayResult)){
                    // 存在,并且创建时间与当前时间相差1小时，则报警，将相应页面上的的标红
                    // 3600秒=1小时
                    $repay['repay_alarm'] = ((time()-$repayResult['create_time']) >= 3600) ? 1 : 0;
                }else{
                    // type-11,result-0 代扣未回调
                    $condition =sprintf("`repay_id`= '%d' AND `type`= '%d' AND `result` = '%d' ORDER BY `id` DESC limit 1",
                       $repay['deal_repay_id'], P2pDepositoryService::IDEMPOTENT_TYPE_DK, P2pIdempotentService::RESULT_WAIT );
                    $dkResult = SupervisionIdempotentModel::instance()->findByViaSlave($condition);
                    $repay['repay_alarm'] = (!empty($dkResult) && ((time()-$dkResult['create_time']) >= 3600)) ? 1 : 0;
                }
            }

            $repay['borrow_amount'] = sprintf("%.2f", $repay['borrow_amount']);
            if($repay['loantype'] == 5) {
                $repay['repay_period'] = $repay['repay_period'] . "天";
            } else {
                $repay['repay_period'] = $repay['repay_period'] . "月";
            }
            $repay['loantype'] = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'][$repay['loantype']] : $GLOBALS['dict']['LOAN_TYPE'][$repay['loantype']];
            $repay['rate'] = sprintf("%.5f", $repay['rate']);
            //用户信息
            $userInfo = M("User")->where("`id` = {$repay['user_id']}")->find();
            $user = \core\dao\UserModel::instance()->find($userInfo['id']);
            $moneyInfo = $userService->getMoneyInfo($user);

            $repay['user_name'] = $userInfo['user_name'];
            $repay['real_name'] = $userInfo['real_name'];
            //$repay['money'] = sprintf("%.2f", $userInfo['money']);
            $repay['money'] = $isP2P ? $moneyInfo['bank'] : $userInfo['money'];

            if(to_date($repay['repay_time'], "Ymd") < to_date($requestRepayTime, "Ymd")) {
                $repay['is_repay_delayed'] = 1;
            } else {
                $repay['is_repay_delayed'] = 0;
            }
            $repay['repay_time'] = to_date($repay['repay_time'], "Y-m-d");
            $repay['repay_money'] = sprintf("%.2f",$repay['repay_money']);
            if(bccomp($repay['money'], $repay['repay_money'], 2) == -1) {//余额不足
                $repay['insufficient'] = 1;
            }
//            $repay['deal_status_info'] = l("DEAL_STATUS_". $repay['deal_status']);

            $repay['is_entrust_zx'] = $deal_pro_service->isProjectEntrustZX($repay['project_id']); // 是否为专享1.75+
            // 还款模式 处理 节前节后
            if (in_array($repay['deal_info']['contract_tpl_type'],$deal_repay_mode_contract_white)){

                $repay['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER];
            }else{
                $repay['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE];
            }


        }
        //分页显示
        $page = $p->show();
        //列表排序显示

        $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT"); //排序提示
        $sort = $sort == 'desc' ? 1 : 0; //排序方式
        $sortImg = $sort; //排序图标

        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
        foreach($dealLoanType as $typeValue){
            $loanTypes[$typeValue['id']] = $typeValue['name'];
        }
        $dealLoanType = $this->is_cn ? $GLOBALS['dict']['DEAL_TYPE_ID_CN'] : $dealLoanType;

        foreach($voList as &$vo){
            $vo['type_name'] = $loanTypes[$vo['type_id']];
            $project_info = DealProjectModel::instance()->findViaSlave($vo['project_id'],'clearing_type');
            // 结算方式
            switch($project_info['clearing_type']){
                case 1:
                    $vo['clearing_type_name'] = '场内';
                    break;
                case 2:
                    $vo['clearing_type_name'] = '场外';
                    break;
                default:
                    $vo['clearing_type_name'] = '--';
                    break;
            }

        }
        //模板赋值显示
        $this->assign('deal_loan_type',$dealLoanType);
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayModel::$repayTypeMsg[DealRepayModel::DEAL_REPAY_TYPE_SELF]);
        $this->assign('deal_repay_type',DealRepayModel::$repayTypeMsg);
        $this->assign('type_id', $_REQUEST['type_id']);
        $this->assign('repay_type', $_REQUEST['repay_type']);
        $this->assign('list', $voList);
        $this->assign('sort', $sort);
        $this->assign('sortImg', $sortImg);
        $this->assign('sortType', $sortAlt);
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $template = $this->is_cn ? 'yuqi_cn' : 'yuqi';
        $this->display ($template);
    }

    /**
     * 导出当期投资银行相关信息
     */
    public function export_repay_user_bank_list(){

        $deal_repay_id = intval($_REQUEST['deal_repay_id']);
        if($deal_repay_id == 0){
            $this->error("参数错误");
        }
        set_time_limit(0);
        $dealLoanPartRepayModel = new DealLoanPartRepayModel();
        $dealLoanRepayInfos = $dealLoanPartRepayModel->getOriginLoanRepayInfos($deal_repay_id);
        if (empty($dealLoanRepayInfos)){
            $this->error("没有数据");
        }
        $content = iconv("utf-8", "gbk", "\t\t\t\t用户id,收款方账号,收款方户名,收款方银行,收款方开户行所在省,收款方开户行所在市,银行联行号,收款方开户行名称,金额");
        $content .= "\n";
        foreach ($dealLoanRepayInfos as $vrepay){
            $new_data = array();
            //用户银行卡信息
            $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
            $bankcard_info = UserBankcardModel::instance()->getNewCardByUserId($vrepay['loan_user_id']);
            if ($bankcard_info) {
                foreach ($bank_list as $k => $v) {
                    if ($v['id'] == $bankcard_info['bank_id']) {
                        $bankcard_info['is_rec'] = $v['is_rec'];
                        $bankcard_info['bank_name'] = $v['name'];
                        break;
                    }
                }
                $bankcard_info['region_lv2'] = empty($bankcard_info['region_lv2']) ? 0 : $bankcard_info['region_lv2'];
                $bankcard_info['region_lv3'] = empty($bankcard_info['region_lv3']) ? 0 : $bankcard_info['region_lv3'];
                $bankInfo['region_lv2_name'] = M("DeliveryRegion")->where("id=" . $bankcard_info['region_lv2'])->getField("name");
                $bankInfo['region_lv3_name'] = M("DeliveryRegion")->where("id=" . $bankcard_info['region_lv3'])->getField("name");
                $new_data['user_id'] = $vrepay['loan_user_id']."\t";
                $new_data['bankcard'] = $bankcard_info['bankcard']."\t";
                $new_data['card_name'] = iconv("utf-8", "gbk", $bankcard_info['card_name']);
                $new_data['bank_name'] = iconv("utf-8", "gbk", $bankcard_info['bank_name']);
                $new_data['province'] = iconv("utf-8", "gbk", $bankInfo['region_lv2_name']);
                $new_data['city'] = iconv("utf-8", "gbk", $bankInfo['region_lv3_name']);
                // 数据库会存有带双引号数据
                $bankcard_info['bankzone'] = str_replace('"','',$bankcard_info['bankzone']);

                $new_data['bank_no'] = '';
                if (!empty($bankcard_info['bankzone'])){
                    $bank_no = MI("Banklist")->where("name='" .$bankcard_info['bankzone']."'")->getField("bank_id");
                    $new_data['bank_no'] = $bank_no."\t";
                }
                $new_data['bankzone'] = iconv("utf-8", "gbk", $bankcard_info['bankzone']);
                $new_data['money'] = $vrepay['repay_money'];

                $content .= implode(",", $new_data) . "\n";
            }else{
                $this->error($vrepay['loan_user_id']." 用户银行信息不存在");
            }

        }
        $datatime = date("YmdHis",time());
        header("Content-Disposition: attachment; filename={$datatime}_deal_repay_list.csv");
        echo $content;

    }
    /**
     * 导出待还款列表
     * @param int $page
     */
    public function export_repay_list($page = 1) {
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayModel::$repayTypeMsg[DealRepayModel::DEAL_REPAY_TYPE_SELF]);

        //设置执行没有超时时间
        set_time_limit(0);
        $_REQUEST['report_status'] = 0;
        $isP2P = (!isset($_REQUEST['report_status']) || $_REQUEST['report_status'] == '1') ? true :false;
        $userThirdBalanceService = new UserThirdBalanceService();

        $limit = " LIMIT " .(($page - 1)*intval(app_conf("BATCH_PAGE_SIZE"))).",".(intval(app_conf("BATCH_PAGE_SIZE")));
        $aliasRepayTable = "t1";
        $aliasDealTable = "t2";

        $requestRepayTimeBeginCondition = $requestRepayTimeEndCondition = '';
        if($_REQUEST['repay_time_begin']) {
            $requestRepayTimeBegin = to_timespan($_REQUEST['repay_time_begin'] . " 0:0:0");
            $requestRepayTimeBeginCondition = " AND {$aliasRepayTable}.`repay_time` >= {$requestRepayTimeBegin} ";
        }
        if($_REQUEST['repay_time_end']) {
            $requestRepayTimeEnd = to_timespan($_REQUEST['repay_time_end'] . " 23:59:59");
            $requestRepayTimeEndCondition = " AND {$aliasRepayTable}.`repay_time` <= {$requestRepayTimeEnd} ";
        }

        $id = trim($_REQUEST['deal_id']);
        $whereDealId = "";
        if($_REQUEST['id']) {
            $ids = $_REQUEST['id'];
            $whereDealId = " and {$aliasRepayTable}.`deal_id` in " . "(" . $ids . ") ";
        } else {
            if(is_numeric($_REQUEST['deal_id'])) {
                $whereDealId = " and {$aliasRepayTable}.`deal_id` = {$_REQUEST['deal_id']} ";
            } else if (trim($_REQUEST['name'])) {
                $sql = "select group_concat(id) from " . DB_PREFIX . "deal where name like '%" . addslashes(trim($_REQUEST['name'])) . "%'";
                $ids = $GLOBALS['db']->get_slave()->getOne($sql);
                if ($ids) {
                    $whereDealId = " and {$aliasRepayTable}.`deal_id` in " . "(" . $ids . ") ";
                } else {
                    $whereDealId = " and 1 < 0 ";
                }
            }
        }

        // 关于A/B 审核
        list($where_audit_deal_id) = $this->getYuQiAuditDealCondition($aliasRepayTable, $id, $ids, $whereDealId);

        if(trim($_REQUEST['user_name']))
        {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name like '%" . addslashes(trim($_REQUEST['user_name'])) . "%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if($ids) {
                $whereUserId = "and {$aliasRepayTable}.`user_id` in " . "(" . $ids . ")";
            } else {
                $whereUserId = " and 1 < 0 ";
            }
        } else {
            $whereUserId = "";
        }
        //caution: 注意查询的时间是否正确。
        if(trim($_REQUEST['repay_time'])) {
            $requestRepayTime = to_timespan($_REQUEST['repay_time'] . " 23:59:59");
        } else {
            $requestRepayTime = to_timespan(date("Y-m-d") . " 23:59:59");
            $_REQUEST['repay_time'] = date("Y-m-d");
        }

        // 项目名搜索
        $where_project = !empty($_REQUEST['project_name']) ? " AND {$aliasDealTable}.`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE name LIKE '%" . trim($_REQUEST['project_name']) . "%')" : '';
        // 增加贷款类型
        $deal_type = !empty($_REQUEST['deal_type']) ? (string)$_REQUEST['deal_type'] : (DealModel::DEAL_TYPE_GENERAL . ',' . DealModel::DEAL_TYPE_COMPOUND);
        $deal_type_where = " `deal_type` IN ({$deal_type}) ";

        // 搜索条件里加上repay_type、type_id和is_during_repay
        $typeIdSql = !empty($_REQUEST['type_id']) ? " AND {$aliasDealTable}.`type_id` = ".$_REQUEST['type_id']." "  : "";
        $repayTypeSql = $_REQUEST['repay_type'] != "" ? " AND {$aliasRepayTable}.`repay_type` = ".$_REQUEST['repay_type']." "  : "";
        $isDuringRepaySql = $_REQUEST['is_during_repay'] != "" ? " and {$aliasDealTable}.`is_during_repay` = " . intval($_REQUEST['is_during_repay']) : "";

        // 取消报备状态 jira 6247
        $reportStatus = (isset($_REQUEST['report_status']) && $_REQUEST['report_status']== '0') ? 0 : 1;
        //$whereReport = " and {$aliasDealTable}.`report_status`=".$reportStatus." ";
        $whereReport = '';
        $whereIgnoreProjectId = "";
        //去掉盈嘉线下还款的项目
        $repayYjService = new DealProjectRepayYjService();
        $yjProjectIds = $repayYjService->getYjProjectIds();
        if (!empty($yjProjectIds)) {
            $whereIgnoreProjectId = " AND {$aliasDealTable}.`project_id` NOT IN " . "(" . implode(",",$yjProjectIds) . ") ";
        }

        $deal_repay_mode_contract_white = array();
        $where_contract_ids = '';
        $whereContractTplType = '';
        $contract_ids = BwlistService::getValueList(DealRepayModel::DEAL_REPAY_MODE_WHITE_TYPE_KEY);
        if (!empty($contract_ids)){
            foreach($contract_ids as $con_id){
                $deal_repay_mode_contract_white[$con_id['value']] = $con_id['value'];
            }

            if (!empty($deal_repay_mode_contract_white)){
                $where_contract_ids = implode(',',$deal_repay_mode_contract_white);
            }
        }
        if (!empty($_REQUEST['repay_mode_holiday']) && !empty($where_contract_ids)){

            // 节前
            if ($_REQUEST['repay_mode_holiday'] == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE){

                $whereContractTplType .= " and {$aliasDealTable}.`contract_tpl_type` not in ({$where_contract_ids}) ";
            }
            if ($_REQUEST['repay_mode_holiday'] == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER){

                $whereContractTplType .= " and {$aliasDealTable}.`contract_tpl_type` in ({$where_contract_ids}) ";
            }

        }

        // 没有白名单全是节前
        if (!empty($_REQUEST['repay_mode_holiday']) && empty($where_contract_ids) && $_REQUEST['repay_mode_holiday'] == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER){
            $whereContractTplType = ' and 1 < 0 ';
        }

        $sql = "SELECT {$aliasRepayTable}.`id` as deal_repay_id , {$aliasRepayTable}.`repay_type`, {$aliasRepayTable}.`repay_time`, {$aliasRepayTable}.`repay_money`, {$aliasRepayTable}.`user_id`,
            {$aliasDealTable}.`id`, {$aliasDealTable}.`name`, {$aliasDealTable}.`borrow_amount`, {$aliasDealTable}.`rate`, {$aliasDealTable}.`type_id`,
            {$aliasDealTable}.`loantype`, {$aliasDealTable}.`repay_time` as repay_period, {$aliasDealTable}.`deal_status`, {$aliasDealTable}.`is_during_repay`,
            {$aliasDealTable}.`parent_id`,{$aliasDealTable}.`prepay_days_limit`,{$aliasDealTable}.`project_id`,{$aliasDealTable}.`loan_fee_rate`,{$aliasDealTable}.`consult_fee_rate`,{$aliasDealTable}.`guarantee_fee_rate`,{$aliasDealTable}.`pay_fee_rate`,{$aliasDealTable}.`canal_fee_rate` FROM " . DB_PREFIX . "deal_repay {$aliasRepayTable} " . " LEFT JOIN " . DB_PREFIX . "deal {$aliasDealTable} " .
            " ON {$aliasRepayTable}.`deal_id` = {$aliasDealTable}.`id` {$requestRepayTimeBeginCondition} {$requestRepayTimeEndCondition} AND
        {$aliasRepayTable}.`status` = 0 " . $whereDealId . $whereUserId . $whereReport . $where_audit_deal_id . $whereIgnoreProjectId .$whereContractTplType.
        " WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 AND {$aliasDealTable}.{$deal_type_where} {$where_project}" . $typeIdSql . $repayTypeSql . $isDuringRepaySql . $limit;

        $repayList = $GLOBALS['db']->get_slave()->getAll($sql, array(), true);

        //根据type_id获取type_name
        //根据repay_type获取相应的name
        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
        foreach($dealLoanType as $typeValue){
            $loanTypes[$typeValue['id']] = $typeValue['name'];
        }
        foreach($repayList as &$vo){
            $vo['deal_type_name'] = $loanTypes[$vo['type_id']];
            $vo['repay_type_name'] = DealRepayModel::$repayTypeMsg[$vo['repay_type']];
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportDealRepayList',
                'analyze' => $sql,
            )
        );
        $dealLoanPartRepayModel = new DealLoanPartRepayModel();

        if($repayList) {
            register_shutdown_function(array(&$this, 'export_repay_list'), $page + 1);
            if($page == 1) {
                if ($this->is_cn) {
                    $content = iconv("utf-8", "gbk", "\t\t\t\t{$_REQUEST['repay_time']}待还款列表\n编号,项目名称,借款标题,借款金额,年化借款利率,借款期限,放款日期,费用收取方式,还款方式,资产管理方,借款人用户名,借款人姓名,借款人id,借款人账号余额,最近一期还款日,还款金额,出借状态,提前还款/回购限制,借款手续费,借款手续费收取方式,代销分期前收金额,代销分期后收金额,借款咨询费,借款咨询费收取方式,借款担保费,借款担保费收取方式,支付服务费,支付服务费收取方式,渠道服务费,渠道服务费收取方式,产品类别,本期还款类型");
                } else {

                    $content = iconv("utf-8", "gbk", "\t\t\t\t{$_REQUEST['repay_time']}待还款列表\n编号,项目名称,借款标题,借款金额,年化借款利率,借款期限,放款日期,费用收取方式,还款方式,还款模式,资产管理方,结算方式,借款人用户名,借款人姓名,借款人id,借款人账号余额,最近一期还款日,还款金额,投资状态,提前还款/回购限制,借款手续费,借款手续费收取方式,代销分期前收金额,代销分期后收金额,借款咨询费,借款咨询费收取方式,借款担保费,借款担保费收取方式,支付服务费,支付服务费收取方式,渠道服务费,渠道服务费收取方式,产品类别,本期还款类型,是否部分用户还款");
                }
                $content = $content . "\n";
            }

            $dealAgency = MI('DealAgency')->where('is_effect = 1 and type=2')->getField('id,name');
            foreach($repayList as $repay) {
                $deal_info = DealModel::instance()->find($repay['id']);
                $deal_ext = \core\dao\DealExtModel::instance()->getInfoByDeal($repay['id']);
                $deal_project = M("Deal_project")->field("name,clearing_type")->where("id = '" .$repay['project_id']. "'")->find();

                $formatRepay['id'] = $repay['id'];
                $formatRepay['project_name'] = iconv("utf-8", "gbk", $deal_project['name']);
                $formatRepay['name'] = iconv("utf-8", "gbk", $repay['name']);
                $formatRepay['borrow_amount'] = sprintf("%.2f", $repay['borrow_amount']);
                $formatRepay['rate'] = sprintf("%.2f", $repay['rate']) . "%";
                if($repay['loantype'] == 5) {
                    $formatRepay['repay_period'] = iconv("utf-8", "gbk", $repay['repay_period'] . "天");
                } else {
                    $formatRepay['repay_period'] = iconv("utf-8", "gbk",$repay['repay_period'] . "月");
                }
                $formatRepay['repay_start_time'] = to_date($deal_info['repay_start_time'],'Y-m-d');
                $formatRepay['fee_type'] = strip_tags(iconv("utf-8", "gbk", get_deal_ext_fee_type($deal_info['id'])));
                $formatRepay['loantype'] = $this->is_cn ? iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE_CN'][$repay['loantype']]) :iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE'][$repay['loantype']]);
                // 还款模式 处理 节前节后
                if (in_array($deal_info['contract_tpl_type'],$deal_repay_mode_contract_white)){

                    $formatRepay['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER];
                }else{
                    $formatRepay['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE];
                }

                $formatRepay['repay_mode_name'] = iconv("utf-8", "gbk", $formatRepay['repay_mode_name']);
                $formatRepay['dealagency'] = iconv("utf-8", "gbk", $dealAgency[$deal_info['advisory_id']]);
                // 结算方式
                switch($deal_project['clearing_type']){
                    case 1:
                        $formatRepay['clearing_type_name'] = '场内';
                        break;
                    case 2:
                        $formatRepay['clearing_type_name'] = '场外';
                        break;
                    default:
                        $formatRepay['clearing_type_name'] = '--';
                        break;
                }
                $formatRepay['clearing_type_name'] = iconv("utf-8", "gbk", $formatRepay['clearing_type_name']);
                //用户信息
                $userInfo = UserModel::instance()->find($repay['user_id']);
                $moneyInfo = $userThirdBalanceService->getUserSupervisionMoney($userInfo['id']);
                $formatRepay['user_name'] = iconv("utf-8", "gbk", $userInfo['user_name']);
                $formatRepay['real_name'] = iconv("utf-8", "gbk", $userInfo['real_name']);
                $formatRepay['user_id'] = $repay['user_id'];
                $formatRepay['money'] =  $isP2P ? $moneyInfo['supervisionBalance'] : $userInfo['money'];
                $formatRepay['repay_time'] = iconv("utf-8", "gbk", to_date($repay['repay_time'], "Y年m月d日"));
                if($dealLoanPartRepayModel->isPartRepay($repay['deal_repay_id'])) {
                    $deal_repay_model = new DealRepayModel();
                    $repayPart = $deal_repay_model->find($repay['deal_repay_id']);
                    $repayPart = $dealLoanPartRepayModel->formatPartRepay($repayPart,$repay['deal_repay_id'],DealLoanPartRepayModel::STATUS_SAVED);
                    $repay['repay_money'] = $repayPart['repay_money'];
                }
                $formatRepay['repay_money'] = sprintf("%.2f",$repay['repay_money']);
                $formatRepay['deal_status'] = iconv("utf-8", "gbk", l("DEAL_STATUS_". $repay['deal_status'])  . ( $repay['is_during_repay'] == 1 ? '正在还款' : ''));
                $formatRepay['prepay_days_limit'] = $repay['prepay_days_limit'];
                $formatRepay['loan_fee_rate'] = sprintf("%.2f", $repay['loan_fee_rate']) . "%";
                $formatRepay['loan_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtModel::$fee_rate_type_name_map[$deal_ext['loan_fee_rate_type']]);
                $loan_fee_ext_arr = json_decode($deal_ext['loan_fee_ext'], true);
                $formatRepay['loan_fee_proxy_head'] = (DealExtModel::FEE_RATE_TYPE_PROXY == $deal_ext['loan_fee_rate_type']) ? $loan_fee_ext_arr[0] : '';
                $formatRepay['loan_fee_proxy_end'] = (DealExtModel::FEE_RATE_TYPE_PROXY == $deal_ext['loan_fee_rate_type']) ? array_pop($loan_fee_ext_arr) : '';
                $formatRepay['consult_fee_rate'] = sprintf("%.2f", $repay['consult_fee_rate']) . "%";
                $formatRepay['consult_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtModel::$fee_rate_type_name_map[$deal_ext['consult_fee_rate_type']]);
                $formatRepay['guarantee_fee_rate'] = sprintf("%.2f", $repay['guarantee_fee_rate']) . "%";
                $formatRepay['guarantee_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtModel::$fee_rate_type_name_map[$deal_ext['guarantee_fee_rate_type']]);
                $formatRepay['pay_fee_rate'] = sprintf("%.2f", $repay['pay_fee_rate']) . "%";
                $formatRepay['pay_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtModel::$fee_rate_type_name_map[$deal_ext['pay_fee_rate_type']]);
                $formatRepay['canal_fee_rate'] = sprintf("%.2f", $repay['canal_fee_rate']) . "%";
                $formatRepay['canal_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtModel::$fee_rate_type_name_map[$deal_ext['canal_fee_rate_type']]);
                $formatRepay['deal_type_name'] = iconv("utf-8", "gbk", $repay['deal_type_name']);
                $formatRepay['repay_type_name'] = iconv("utf-8", "gbk", $repay['repay_type_name']);

                if ($dealLoanPartRepayModel->isPartRepay($repay['deal_repay_id'])){
                    $formatRepay['is_part_user_repay'] = iconv("utf-8", "gbk", '是');
                }else{
                    $formatRepay['is_part_user_repay'] = iconv("utf-8", "gbk", '否');
                }

                $content .= implode(",", $formatRepay) . "\n";
            }

            $datatime = date("YmdHis",time());
            header("Content-Disposition: attachment; filename={$datatime}_deal_repay_list.csv");
            echo $content;
        } else {
            if($page==1)
                $this->error(L("NO_RESULT"));
        }
    }

        public function  download_repay_account(){
            $cache = \SiteApp::init()->dataCache->getRedisInstance();
            $redisKey = "repay_trial_email_finish_".date('Ymd');
            $isFinish = $cache->get($redisKey);
            if(!$isFinish){
                echo '当前列表还未生成、请在后台跑批完成后在进行下载!';
                exit;
            }

            $file_path  =  $savepath = 'uploads/' . date('Ymd') . '/'; // 本次文件所存的文件夹

            $remote_filename = $savepath . "repay_trial.csv";

            header ( "Content-type: application/pdf");
            header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'.csv"');
            header ( "Content-Length: " . filesize($file_path));
            echo Vfs::read($remote_filename,false);
        }

    /**
     * 获取还款列表中审核信息的 deal_id 过滤条件，以及渲染页面 repay prepay 信息
     * @param string $aliasRepayTable 还款表别名
     * @param int $id 标的id
     * @param string $ids 逗号分隔的标的id
     * @param string $whereDealId 已有的过滤条件
     */
    private function getYuQiAuditDealCondition($aliasRepayTable, $id, $ids, $whereDealId)
    {
        $role = $this->getRole();
        $limitDays = intval(app_conf('AB_REPAY_TIME_LIMIT'));
        if ($limitDays <= 0) {
            $limitDays = 3;
        }
        if ($role == 'b') {// B角审核筛选申请人
            $conds = array(
                'service_type' => array('in', implode(',', array(ServiceAuditModel::SERVICE_TYPE_REPAY, ServiceAuditModel::SERVICE_TYPE_PREPAY))),
                'status' =>  array('in', implode(',', array(ServiceAuditModel::NOT_AUDIT))));
            $conds['update_time'] = array('gt', time() - 86400 * $limitDays);
            if ($_REQUEST['service_type']) {
                $conds['service_type'] = $_REQUEST['service_type'];
            }
            if ($_REQUEST['submit_uid']) {
                $adminId = M('Admin')->where('adm_name="'.addslashes($_REQUEST['submit_uid']).'"')->getField('id');
                $conds['submit_uid'] = $adminId;
            }
            $auditList = D('ServiceAudit')->where($conds)->field('service_id, status, submit_uid, service_type')->select();
            $audits = $repays = array();
            foreach ($auditList as $row) {
                $row['submit_user_name'] = $row['submit_uid'] ? get_admin_name($row['submit_uid']) : '';
                if ($row['service_type'] == ServiceAuditModel::SERVICE_TYPE_REPAY) {
                    $audits[$row['service_id']] = $row;
                } else {
                    $prepayAudits[$row['service_id']] = $row;
                }
            }
            $idsRepay = $idsPrepay = $prepays = array();
            if($audits || $prepayAudits) {
                if ($prepayAudits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_prepay where id in (" . implode(',', array_keys($prepayAudits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $prepays[$row['deal_id']] = $prepayAudits[$row['id']];
                        $idsPrepay[] = $row['deal_id'];
                    }
                }
                if ($audits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_repay where id in (" . implode(',', array_keys($audits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $idsRepay[] = $row['deal_id'];
                        $repays[$row['deal_id']] = $audits[$row['id']];
                    }
                }
                $idsRepay = array_merge($idsRepay, $idsPrepay);
                if ($id) {
                    $idsRepay = array_intersect(array($id), $idsRepay);
                }
                if ($ids) {
                    $idsRepay = array_intersect(explode(',', $ids), $idsRepay);
                }

                if ($idsRepay) {
                    $whereDealId = "and {$aliasRepayTable}.`deal_id` in " . "(" . implode(',', $idsRepay) . ") ";
                } else {
                    $whereDealId = " and 1 < 0 ";
                }

            } else {//当没有，设置永远错误的条件，让搜索的结果为空
                $whereDealId = " and 1 < 0 ";
            }
        } else {
            $conds = array('service_type' => array('in', implode(',', array(ServiceAuditModel::SERVICE_TYPE_REPAY, ServiceAuditModel::SERVICE_TYPE_PREPAY))));
            $conds['create_time'] = array('gt', time() - 86400 * $limitDays);
            $auditList = D('ServiceAudit')->where($conds)->field('service_id, status, submit_uid, service_type')->select();
            $audits = $repays = $prepays = $prepayAudits = array();
            foreach ($auditList as $row) {
                $row['submit_user_name'] = $row['submit_uid'] ? get_admin_name($row['submit_uid']) : '';
                if ($row['service_type'] == ServiceAuditModel::SERVICE_TYPE_REPAY) {
                    $audits[$row['service_id']] = $row;
                } else {
                    $prepayAudits[$row['service_id']] = $row;
                }
            }
            if($audits) {
                if ($audits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_repay where id in (" . implode(',', array_keys($audits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $repays[$row['deal_id']] = $audits[$row['id']];
                    }
                }
            }
            if($prepayAudits) {
                if ($prepayAudits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_prepay where id in (" . implode(',', array_keys($prepayAudits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $prepays[$row['deal_id']] = $prepayAudits[$row['id']];
                    }
                }
            }
            if ($_REQUEST['audit_status'] && $whereDealId != 'and 1 < 0') {
                $tmpArray = $filterList = array();
                foreach ($repays as $k => $v) {
                    $filterList[$k] = $v;
                }
                foreach ($prepays as $k => $v) {
                    $filterList[$k] = $v;
                }
                if ($id) {
                    $ids = $id;
                }
                if (!empty($ids)) {
                    $ids = explode(',', $ids);
                }
                if (in_array($_REQUEST['audit_status'], array(ServiceAuditModel::NOT_AUDIT, ServiceAuditModel::AUDIT_SUCC, ServiceAuditModel::AUDIT_FAIL))) {
                    foreach ($filterList as $dealId => $row) {
                        if ($_REQUEST['audit_status'] == $row['status']) {
                            $tmpArray[] = $dealId;
                        }
                    }
                    if (!empty($ids)) {
                        $tmpArray = array_intersect($ids, $tmpArray);
                    }
                    if (!empty($tmpArray)) {
                        $whereDealId = "and {$aliasRepayTable}.`deal_id` in " . "(" . implode(',', $tmpArray) . ") ";
                    } else {
                        $whereDealId = "and 1 < 0 ";
                    }
                } else {
                    foreach ($filterList as $dealId => $row) {
                        if (($id && $id != $dealId) || ($ids && in_array($dealId, $ids))) {
                            continue;
                        }
                        $tmpArray[] = $dealId;
                    }
                    if (!empty($ids)) {
                        $tmpArray = array_diff($ids, array_keys($filterList));
                        $whereDealId = "and {$aliasRepayTable}.`deal_id` in " . "(" . implode(',', $tmpArray) . ") ";
                    } elseif (!empty($tmpArray)) {
                        $whereDealId = "and {$aliasRepayTable}.`deal_id` not in " . "(" . implode(',', $tmpArray) . ") ";
                    } else {
                        $whereDealId = "and 1 < 0 ";
                    }
                }
            }
        }

        return array($whereDealId, $repays, $prepays);
    }

    public function trash()
    {
        $condition['is_delete'] = 1;
        $this->assign("default_map",$condition);
        parent::index();
    }

    public function add()
    {
        //项目
        $pro_id = intval($_REQUEST ['proid']);
        if($pro_id <= 0){
            $this->error('所属项目不能为空');
        }

        $mp = new DealProjectService();
        $this->project = $project = $mp->getProInfo($pro_id);

        //$project = M("DealProject")->where(array('id' => $pro_id))->find();
        if(empty($project)){
            $this->error('所属项目不能为空');
        }
        $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);

        if(bccomp($project['borrow_amount'], $project['money_borrowed']) <= 0){
            $this->error('项目借款金额已满');
        }

        if ($project['deal_type'] == DealProjectService::DEAL_TYPE_LGL) {

            $project_compound_service = new DealProjectCompoundService();
            //已赎回本金 + 利息 这儿可能会查询比较多
            $redeemed_principal = $project_compound_service->getPayedProjectCompoundPrincipal($project['id']);
            $redeemed_principal = $redeemed_principal + $project_compound_service->getPayedCompoundInterest($project['id']);
            // 赎回中本金 + 利息
            $redeeming_principal = $project_compound_service->getUnpayedCompoundPrincipal($project['id']);
            $redeeming_principal = $redeeming_principal + $project_compound_service->getUnpayedCompoundInterest($project['id']);

            $this->assign ( 'redeeming_principal', $redeeming_principal );
            $this->assign ( 'redeemed_principal', $redeemed_principal);
        }

        // 计算日化利率
        $project['rate_day'] = \core\service\DealCompoundService::convertRateYearToDay($project['rate'], $project['redemption_period'], true);
        $this->assign ( 'vo', $project );

        $user = M("User")->where(array('id' => $project['user_id']))->find() ;
        // ----------------- over ----------------
        // JIRA#3260 企业账户二期 - 获取用户类型名称 <fanjingwen@ucfgroup.com>
        if (!empty($project['user_id']) && !empty($user)) {
            $user['user_type_name'] = getUserTypeName($user['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserModel::USER_TYPE_ENTERPRISE == $user['user_type']) {
                $user['company_name'] = getUserFieldUrl($user, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
            } else {
                $user['real_name'] = getUserFieldUrl($user, UserModel::TABLE_FIELD_REAL_NAME);
            }
        }
        // ----------------- over ----------------

        $this->assign ( 'user', $user);

        //用户分组
        $this->assign ( 'usergroupList', M("UserGroup")->select() );

        //排序
        $this->assign("new_sort", M("Deal")->where("is_delete=0")->max("sort")+1);

        //借款分类
        $deal_cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $deal_cate_tree = D("DealCate")->toFormatTree($deal_cate_tree,'name');
        $this->assign("deal_cate_tree",$deal_cate_tree);

        //担保机构
        $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1 ')->order('sort DESC')->findAll();
        $this->assign("deal_agency",$deal_agency);

        //支付机构
        $pay_agency = M("DealAgency")->where('is_effect = 1 and type=4 ')->order('id ASC')->findAll();
        $this->assign("pay_agency",$pay_agency);

        //管理机构
        $management_agency = M("DealAgency")->where('is_effect = 1 and type=5 ')->order('id ASC')->findAll();
        $this->assign("management_agency",$management_agency);
        //代垫机构
        $advance_agency = M("DealAgency")->where('is_effect = 1 and type=6 ')->order('id ASC')->findAll();
        $this->assign("advance_agency",$advance_agency);

        //受托机构
        $advance_agency = M("DealAgency")->where('is_effect = 1 and type=7 ')->order('id ASC')->findAll();
        $this->assign("entrust_agency",$advance_agency);

        //代充值机构
        $generation_recharge = M("DealAgency")->where('is_effect = 1 and type=8 ')->order('id ASC')->findAll();
        $this->assign("generation_recharge",$generation_recharge);

        //借款用途
        $deal_type_tree = $this->getDealLoanTypeList();
        $this->assign("deal_type_tree",$deal_type_tree);

        //合同类型
        FP::import("libs.common.app");
//        $contract_service = new \core\service\ContractService();
//        $contract_tpl_type = $contract_service->getContractType();

        $tplRequest = new RequestGetCategorys();
        $tplRequest->setIsDelete(0);
        if($this->is_cn){
            $tplRequest->setSourceType(0);
        }
        $tplResponse = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategorys",
            'args' => $tplRequest,
        ));

        if(!is_array($tplResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }

        $this->assign('contract_tpl_type', $tplResponse->list);    //合同类型

        //从配置文件取公用信息
        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);        //还款方式
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory",$deal_advisory);

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);

        //限制vip等级
        $vipService = new VipService();
        $vipGrades = $vipService->getVipGradeList();
        $this->assign('vipGrades', $vipGrades);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);

        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = get_sites_template_list();
        $site_list = changeDealSite($site_list);
        $this->assign('site_list', $site_list);

        //利滚利分类标识
        $this->assign('lgl_tag', \core\dao\DealLoanTypeModel::TYPE_LGL);
        $this->assign('bxt_tag', \core\dao\DealLoanTypeModel::TYPE_BXT);
        $this->assign('dtb_tag', \core\dao\DealLoanTypeModel::TYPE_DTB);
        $this->assign('zcgl_tag', \core\dao\DealLoanTypeModel::TYPE_GLJH);

        $this->display();
    }

    private function _insertCheckDeal($data) {
        //开始验证数据有效性
        if($data['project_id']==0){
            $this->error('所属项目不能为空');
        }

        if(empty($_REQUEST['deal_site'])){
            $this->error('所属网站不能为空！');
        }

        if($data['cate_id']==0){
            $this->error(L("DEAL_CATE_EMPTY_TIP"));
        }

        if($data['type_id']==0){
            $this->error(L("DEAL_TYPE_EMPTY_TIP"));
        }

        if(!check_empty($data['repay_time'])){
            $this->error('借款期限不能为空');
        }

        // 检查交易所后台配置最低投资金额
        if ($data['is_float_min_loan'] == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES){

            $jys_min_money = (new DealModel())->getJYSMinLoanMony($data['jys_id']);
            if (!empty($jys_min_money) && $data ['min_loan_money'] < $jys_min_money){
                $this->error ( "不能低于该交易所最低投资限额，最低投资限额为：".$jys_min_money );
            }
        }
        /*if(get_wordnum ( $data ['description'] ) < 5 || get_wordnum ( $data ['description'] ) > 1000) {
            $this->error ( "‘借款描述’应在5-1000个字之间" );
        }*/

//        大金所上标可能为空
//        if(empty($data['advisory_id'])) {
//            $this->error("咨询机构不能为空");
//        }

        if(($data ['max_loan_money']) > 0 && ($data ['max_loan_money']) < ($data ['min_loan_money'])) {
            $this->error ( "最大金额不能小于最小金额" );
        }

        $project = M("DealProject")->where(array('id' => $data['project_id']))->find();
        $project_left_money = $project['borrow_amount'] - $project['money_borrowed'];

        $data['user_id'] = $project['user_id'];

        if(bccomp($data ['borrow_amount'], $project_left_money) == 1){
            $this->error ( "项目的待借款金额剩余：".$project_left_money );
        }elseif($data ['borrow_amount'] < app_conf ( 'MIN_BORROW_QUOTA' ) || $data ['borrow_amount'] > app_conf ( 'MAX_BORROW_QUOTA' )) {
            $this->error ( "‘借款金额’应为" . app_conf ( 'MIN_BORROW_QUOTA' ) . "至" . app_conf ( 'MAX_BORROW_QUOTA' ) . "的整数！" );
        }

        $this->deal_data;
        return $data;

    }

    private function _insertCheckDealExt($data, $deal_ext_data) {
        //“年化收益基本利率”和“年化收益浮动利率” 的处理
        $income_rate_sum = sprintf("%.5f", $deal_ext_data['income_base_rate'] + $deal_ext_data['income_float_rate']);
        if($income_rate_sum - sprintf("%.5f", $data['income_fee_rate']) != 0){
            $this->error('年化出借人收益率 = 年化收益基本利率 + 年化收益浮动利率');
        }

        $deal_ext_data['start_loan_time'] = trim($deal_ext_data['start_loan_time']) == '' ? 0 : to_timespan($deal_ext_data['start_loan_time']);

        if($deal_ext_data['start_loan_time'] && $deal_ext_data['start_loan_time'] <= get_gmtime()){
            $this->error('“开标时间”应当大于当前时间');
        }

        $deal_ext_data['base_contract_repay_time'] = trim($deal_ext_data['base_contract_repay_time']) == '' ? 0 : to_timespan($deal_ext_data['base_contract_repay_time']);

        $bankcard_info = UserBankcardModel::instance()->getNewCardByUserId($data['user_id']);
        if(!$bankcard_info || $bankcard_info['status'] != 1){
            $this->error ( "借款人用户银行卡未验证" );
        }

        return $deal_ext_data;
    }

    private function _insertCheckDealCompound() {
       if (!is_numeric($_REQUEST['redemption_period']) || intval(trim($_REQUEST['redemption_period'])) <= 0)
       {
            $this->error ( "赎回期必须为数字且大于0" );
       }

       if (!is_numeric($_REQUEST['lock_period']))
       {
            $this->error ( "锁定期必须为数字" );
       }
    }

    private function _insertCheck($deal_data, $deal_ext_data) {
        $this->deal_data = $this->_insertCheckDeal($deal_data);
        $this->deal_ext_data = $this->_insertCheckDealExt($this->deal_data, $deal_ext_data);
        // 如果是通知贷
        if ($_REQUEST['deal_type'] == 1) {
            $this->_insertCheckDealCompound();
        }
    }

    /**
     * 借款保存
     * @actionLock
     * lockauthor qicheng
     */
    public function insert() {

        B('FilterString');
        $data = M(MODULE_NAME)->create();
        $model_deal_ext = M('DealExt');
        $deal_ext_data = $model_deal_ext->create();
        $rs_check = $this->_insertCheck($data, $deal_ext_data);

        $data = $this->deal_data;
        $deal_ext_data = $this->deal_ext_data;

        $_income_fee_rate = trim($_POST['income_fee_rate']); // 年化出借人收益率
        $_annualized_rate = trim($_POST['rate']);    // 借款年利率
        $_income_float_rate = trim($_POST['income_float_rate']);; //年化收益浮动利率
        $_income_base_rate = trim($_POST['income_base_rate']);; // 年化收益基本利率

        if(bccomp($_income_fee_rate,$_annualized_rate,5) !=0 || bccomp($_annualized_rate,bcadd($_income_float_rate,$_income_base_rate,5),5) !=0) {
            $this->error('请注意 ： 借款年利率＝年化出借人收益率 = (年化收益基本利率 + 年化收益浮动利率');
        }
        //更新数据
        $data['create_time'] = get_gmtime();
        $data['update_time'] = get_gmtime();
        $data['start_time'] = trim($data['start_time'])==''?0:to_timespan($data['start_time']);
        $data['bad_time'] = trim($data['bad_time'])==''?0:to_timespan($data['bad_time']);
        $data['parent_id'] = -1; //所有标均为普通标
        $data['income_total_rate'] = $data['income_fee_rate'] + $deal_ext_data['income_subsidy_rate'];

        $loan_type_info = M("Deal_loan_type")->where("id = ".intval($data['type_id']))->find();
        if($loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_LGL){
            $data['deal_type'] = 1;
        }
        if ($data['deal_type'] == 0) {
            if (!is_numeric($_POST['rebate_days']) ||  $_POST['rebate_days'] < 0){
                $this->error('返利天数不能为负数');
            }
        }
        if($data['deal_crowd'] == 16 && !trim($_POST['specify_uid'])) {
            $this->error('指定用户ID不能为空');
        }

        $m = M(MODULE_NAME);
        $result = $m->add ($data);

        // JIRA#3271 平台产品名称定义 2016-03-29 <fanjingwen@ucfgroup.com>
        // 更新deal表中对应的标的名称
        $deal_ext_data['deal_name_prefix'] = isset($_REQUEST['prefix_title']) ? mysql_real_escape_string($_REQUEST['prefix_title']) : '';
        $dealServiceForName = new DealService();
        $dealName = $dealServiceForName->updateDealName($result, $data['project_id']);
        // -------------- over ------------------

        //更新项目信息
        $deal_pro_service = new DealProjectService();
        $deal_pro_service->updateProBorrowed($data['project_id']);
        $deal_pro_service->updateProLoaned($data['project_id']);

        if(!$result){
            $dbErr = M()->getDbError();
            save_log($dealName . L("INSERT_FAILED").$dbErr,0);
            $this->error(L("INSERT_FAILED").$dbErr);
        }
        if($data['deal_crowd'] == 2){ // 特定用户组
            $dealGroupModel = M('DealGroup');
            $grouplist = $_POST['user_group'];
            if($grouplist)
            {
                foreach ($grouplist as $key => $value) {
                    $dealGroupModel->add(array('deal_id'=>$result,'user_group_id'=>$value));
                }
            }
        }elseif($data['deal_crowd'] == 16) {// 指定用户可投
            $deal_ext_data['deal_specify_uid'] = trim($_POST['specify_uid']);
        }

        FP::import("libs.deal.deal");
        $deal_id = $result;

        //合同服务设置标的模板分类ID
        $contractRequest = new RequestSetDealCId();
        $contractRequest->setDealId(intval($deal_id));
        $contractRequest->setCategoryId(intval($data['contract_tpl_type']));
        $contractRequest->setType(0);
        $contractRequest->setSourceType($data['deal_type']);

        $contractResponse = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "setDealCId",
            'args' => $contractRequest,
        ));

        // 加入tags部分  add by zhanglei5 20140827
        $deal_tags = trim($_REQUEST['deal_tags']);
        if(strlen($deal_tags) > 0) {
            $deal_tag_service = new DealTagService();
            $rs = $deal_tag_service->insert($deal_id,$_REQUEST['deal_tags']);
            if(!$rs) {
                save_log($dealName.L("INSERT_FAILED").'插入deal_tag_insert faild', C('FAILED'), '', $_REQUEST['deal_tags'], C('SAVE_LOG_FILE'));
                $this->error(L("INSERT_FAILED"));
            }
        }

        $deal_site = $_REQUEST['deal_site'];
        update_deal_site($deal_id, $deal_site);
        // 是否需要短信通知3日还款提醒  --add by zhanglei5 20141013
        $deal_ext_data['need_repay_notice']= isset($_REQUEST['need_repay_notice']) ? $_REQUEST['need_repay_notice'] : 0;

        //合同展示相关 --add by wangjiantong 20150923

        //转让资产类别
        if (isset($_REQUEST['contract_transfer_type'])) {
            $deal_ext_data['contract_transfer_type']= isset($_REQUEST['contract_transfer_type']) ? intval($_REQUEST['contract_transfer_type']) : 0;
        }

        //年化借款平台手续费类型
        $deal_ext_data['loan_fee_rate_type']= isset($_REQUEST['loan_fee_rate_type']) ? intval($_REQUEST['loan_fee_rate_type']) : 0;

        //年化借款咨询费类型
        $deal_ext_data['consult_fee_rate_type']= isset($_REQUEST['consult_fee_rate_type']) ? intval($_REQUEST['consult_fee_rate_type']) : 0;

        //年化担保费类型
        $deal_ext_data['guarantee_fee_rate_type']= isset($_REQUEST['guarantee_fee_rate_type']) ? intval($_REQUEST['guarantee_fee_rate_type']) : 0;

        //年化支付服务费类型
        $deal_ext_data['pay_fee_rate_type']= isset($_REQUEST['pay_fee_rate_type']) ? intval($_REQUEST['pay_fee_rate_type']) : 0;

        //年化管理服务费类型
        $deal_ext_data['management_fee_rate_type']= isset($_REQUEST['management_fee_rate_type']) ? intval($_REQUEST['management_fee_rate_type']) : 0;

        //基础合同编号
        $deal_ext_data['leasing_contract_title'] = isset($_REQUEST['leasing_contract_title']) ? $_REQUEST['leasing_contract_title'] : '';

        //借款用途分类
        $deal_ext_data['loan_application_type']= isset($_REQUEST['loan_application_type']) ? intval($_REQUEST['loan_application_type']) : 0;

        //JIRA#2925  合同变更需求，新增“资产转让类别”
        //转让资产类别数据校验,若资产转让类别选择为“无”，则此五项为非必填项。若资产转让类别选择为“债权”或“资产收益权”，则“基础合同的编号”、“原始债务人”、“基础合同交易金额”三项为必填项
        if ($deal_ext_data['contract_transfer_type'] > 0) {
            if("" == strval($deal_ext_data['leasing_contract_num'])) {
                $this->error('基础合同的编号 不能为空！');
            }
            if("" == strval($deal_ext_data['lessee_real_name'])) {
                $this->error('原始债务人 不能为空！');
            }
            if("" == strval($deal_ext_data['leasing_money'])) {
                $this->error('基础合同交易金额 不能为空！');
            }
        }

        //保存订单扩展信息 --add by liangqiang 20140108


        $deal_ext_data['deal_id'] = $deal_id;

        // 增加变现通特殊逻辑
        if( $loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_BXT ){
            $deal_ext_data['max_rate'] = sprintf("%.5f",$_REQUEST['max_rate']);
        }

        // 增加平台手续费 分期收方式
        $repay_times = DealModel::getRepayTimesByLoantypeAndRepaytime($data['loantype'], $data['repay_time']);
        if($deal_ext_data['loan_fee_rate_type'] == 4) {
            $loan_fee_arr[0] = $_REQUEST['loan_fee_arr'][0];
            $loan_fee_arr[$repay_times] = $_REQUEST['loan_fee_arr'][1];
            $deal_ext_data['loan_fee_ext'] = json_encode($loan_fee_arr);
        }

        // JIRA#3271 平台产品名称定义 2016-03-29 <fanjingwen@ucfgroup.com>
        $rs = $model_deal_ext->add($deal_ext_data);
        if (empty($rs)) {
            save_log($deal_ext_data['deal_name_prefix'] . $_REQUEST['main_title'] . '：插入deal_ext_add fail',0);
            $this->error('标的扩展信息插入失败');
        }
        // ------------------ over ------------------


        // 标的优惠码设置
        $deal_coupon_res = $this->addCouponDeal($data['deal_type'], $deal_id);
        if (!$deal_coupon_res){
            $this->error('保存标优惠码失败');
        }

        //生成贷款发送相关消息。
        $loan_type_name = get_deal_title($dealName, $loan_type_info['name']);

        //保存利滚利标信息
        if($loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_LGL){
            $deal_compound['redemption_period'] = intval($_REQUEST['redemption_period']);
            $deal_compound['lock_period'] = intval($_REQUEST['lock_period']);
            $deal_compound['create_time'] = get_gmtime();
            $deal_compound['deal_id'] = $deal_id;
            $deal_compound['end_date'] = $_REQUEST['end_date'] ? to_timespan($_REQUEST['end_date']) : 0;
            M('DealCompound')->add($deal_compound);
        }

        FP::import("libs.common.app");
        $user_info = get_user_info($data['user_id'],true);

        //获取用户信息
        $user_new = M('User')->where('id='.intval($data['user_id']))->find();

        $notice = array();
        $notice['loan_type_name'] = $loan_type_name;
        $notice['user_id'] = $user_info['id'];
        $notice['real_name'] = $user_info['real_name'];
        $notice['email'] = $user_info['email'];
        $notice['user_name'] = $user_new['user_name'];
        $notice['deal_id'] = $result;
        $notice['mobile'] = $user_info['mobile'];

        $this->_msgfor_upload_deal($notice);
        if($data['deal_crowd'] =='34'){
            $importCsvResult=$this->importCsvUserIds($result);
            $dealCustomUserService = new \core\service\DealCustomUserService ();
            $updateCache=$dealCustomUserService->getCacheDealUserIds(1);
            save_log($deal_id.'投资限定条件1批量导入用户'.L("UPDATE_SUCCESS"),C('SUCCESS'),'',$_FILES['upfile'] ['name']);
        } elseif ($data['deal_crowd'] == '35') {
            $adm_session = es_session::get(md5(conf("AUTH_KEY" )));
            $dealCustomUserService = new \core\service\DealCustomUserService();
            $saveGroupIds = $dealCustomUserService->saveGroupIds($result, $_POST['group_ids'], $adm_session['adm_id'],$_POST['group_type']);
            save_log($deal_id.'投资限定条件1指定用户组可投'.L("UPDATE_SUCCESS"),C('SUCCESS'),'',json_encode($_POST['group_ids']));
        }
        FP::import("libs.common.app");
        //成功提示
        syn_deal_status($result);
        syn_deal_match($result);
        save_log('上标'.$dealName.L("INSERT_SUCCESS"), C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));
        $this->success(L("INSERT_SUCCESS"));
    }

    /**
     * 变更受托专享项目的业务状态
     * 规则：若要将项目业务状态变更为 待上线，则需要项目下边之前只有一个进行中 或 满标的标；若变更为 募集中，则需要项目下之前无进行中 或 满标的标
     * @param int $project_id
     * @param int $business_status
     * @return boolean
     */
    private function changeEntrustZXProjectStatus($project_id, $business_status)
    {
        $pro_service = new DealProjectService();
        if (!$pro_service->isProjectEntrustZX($project_id)) { // 不是专享项目 不用变更
            return true;
        }

        $deal_service = new DealService();
        $deal_count = count($deal_service->getDealByProId($project_id, array(DealModel::$DEAL_STATUS['progressing'], DealModel::$DEAL_STATUS['full'])));
        $can_update = (DealProjectModel::$PROJECT_BUSINESS_STATUS['waiting'] == $business_status && 1 == $deal_count) || (DealProjectModel::$PROJECT_BUSINESS_STATUS['process'] == $business_status && 0 == $deal_count);
        if ($can_update) {
            return DealProjectModel::instance()->changeProjectStatus($project_id, $business_status);
        } else {
            return true;
        }
    }

    /**
     * 操作放款表单
     */
    public function lent()
    {
        $id = intval($_REQUEST['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;

        $this->assign('role', $this->getRole());
        $this->assign('return_type_list', self::$returnTypes);
        $this->assign('readonly', $_REQUEST['readonly']);// 审核中或者审核通过的不可以被编辑

        //标的信息
        $vo = M(MODULE_NAME)->where($condition)->find();
//        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        $vo['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($vo['id'])){
            $vo['isDtb'] = 1;
        }

        //用户信息处理
        $userInfo = M('User')->where('id='.intval($vo['user_id']))->find();
        $this->assign("userInfo",$userInfo);

        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();

        // 结算方式
        if ($vo['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE){
            switch($project['clearing_type']){
                case 1:
                    $project['clearing_type_name'] = '场内';
                    break;
                case 2:
                    $project['clearing_type_name'] = '场外';
                    break;
                default:
                    $project['clearing_type_name'] = '--';
                    break;
            }
        }

        $this->assign ( 'pro', $project );

        $this->assign('loan_money_type', $GLOBALS['dict']['LOAN_MONEY_TYPE']); //放款方式
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);

        // JIRA#1108 计算还款期数
        $deal_model = \core\dao\DealModel::instance()->find($vo['id']);
        $repay_times = $deal_model->getRepayTimes();
        $this->assign('repay_times', $repay_times);

        if($vo['loantype'] == 5) {
            $repayTime =   $vo['repay_time'] . "天";
        } else {
            $repayTime = $vo['repay_time'] . "月";
        }
        $this->assign('repay_time', $repayTime); // 借款期限

        //订单扩展信息
        $deal_ext = M("DealExt")->where(array('deal_id' => $id))->find();
        // 计算服务费
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $vo['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        }
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);

        if ($vo['isDtb'] == 1) {
            $management_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['management_fee_rate'], $vo['repay_time'], false);
            $management_fee = $deal_model->floorfix($vo['borrow_amount'] * $management_fee_rate / 100.0);
            $this->assign("management_fee", $management_fee);
        }

        $this->assign ('vo', $vo );
        $this->assign('deal_ext', $deal_ext);
        $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);
        $this->assign("repay_start_time", empty($vo['repay_start_time']) ? "" :to_date($vo['repay_start_time'], "Y-m-d"));
        $this->assign('redirectUrl', empty($_SESSION['lastDealLoanUrl']) ? '?m=DealLoan' : $_SESSION['lastDealLoanUrl']);

        // 分期 平台手续费
        if (!empty($deal_ext['loan_fee_ext'])) {
            $loan_fee_arr =  json_decode($deal_ext['loan_fee_ext'], true);
            $this->assign("loan_fee_arr",$loan_fee_arr);
            $proxy_sale['loan_fee_sum'] = array_sum($loan_fee_arr);
            $proxy_sale['loan_first_rate'] = ceilfix($loan_fee_arr[0] / $proxy_sale['loan_fee_sum'] * $vo['loan_fee_rate'], 5);
            $proxy_sale['loan_last_rate'] = ceilfix($vo['loan_fee_rate'] - $proxy_sale['loan_first_rate'], 5);
            $proxy_sale['loan_rate_sum'] = $vo['loan_fee_rate'];
            $this->assign("proxy_sale", $proxy_sale);
        }
        $template = $this->is_cn ? 'lent_cn' : 'lent';
        $this->display($template);
    }

    /**
     * 放款操作
     */
    public function enqueue()
    {
        $role = $this->getRole();
        $id = $_REQUEST['id'];
        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $id))->find();
//        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        $vo['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($id)){
            $vo['isDtb'] = 1;
        }
        try {
            $dealService->isOKForMakingLoans($vo);
            // 如果是 a 角提交，或者 b 角同意
            if ('a' == $role || 1 == $_REQUEST['agree']) {
                $deal_ext_info = DealExtModel::instance()->getDealExtByDealId($id);
                if (in_array($deal_ext_info->loan_type, array(UserCarryModel::LOAN_AFTER_CHARGE, UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN))){
                    if($deal_ext_info->loan_type == UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN) {
                        //检查是否满足收费后先计息后放款条件
                        $this->_checkLoanAfterChargeLaterLoan($id);
                    }

                    if (!DealModel::instance()->canUserAffordDealFee($id)) { // 负担不起
                        throw new \Exception('放款类型为收费后放款或收费后先计息后放款，客户账户余额不足');
                    }
                }
            }
        } catch (\Exception $e) {
            $ret['status'] = 0;
            $ret['error_msg'] = $e->getMessage();
            ajax_return($ret);
            return;
        }

        $audit = D('serviceAudit')->where(array('service_type' => $this->getServiceType(), 'service_id' => $id))->find();
        if ($role != 'b' || $_REQUEST['agree'] != 1) {
            $auditRes = $this->audit($vo, $role, $audit);
            if ($auditRes == 0) {
                $result['status'] = 0;
                $result['error_msg'] = "审核异常，请重试";
                ajax_return($result);
                return;
            }
            $result['status'] = $auditRes;
            $result['error_msg'] = "审核成功";
            ajax_return($result);
            return;
        }

        //放款添加到jobs
        if(!$dealService->isP2pPath(intval($id))) {
            $function = '\core\service\DealService::makeDealLoansJob';
            $param = array('deal_id' => $id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']);
        }else{
            $orderId = Idworker::instance()->getId();
            $function = '\core\service\P2pDealGrantService::dealGrantRequest';
            $param = array(
                'orderId' => $orderId,
                'dealId'=>$id,
                'param'=>array('deal_id' => $id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']),
            );
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$id);
        }

        $GLOBALS['db']->startTrans();
        try {
            $auditRes = $this->audit($vo, $role, $audit);
            if (!$auditRes) {
                throw new \Exception("AB角审核失败");
            }
            //如果没有设置放款时间，则添加默认的放款时间
            $vo['deal_status'] = 4; //设置状态为放款中
            if(!$vo['repay_start_time']) {
                $vo['repay_start_time'] = to_timespan(date("Y-m-d"));
            }

            if(intval($vo['next_repay_time']) == 0){
                $delta_month_time = get_delta_month_time($vo['loantype'], $vo['repay_time']);

                // 按天一次到期
                if($vo['loantype'] == 5){
                    $vo['next_repay_time'] = next_replay_day_with_delta($vo['repay_start_time'], $delta_month_time);
                }else{
                    $vo['next_repay_time'] = next_replay_month_with_delta($vo['repay_start_time'], $delta_month_time);
                }
            }

            $isSaved = M(MODULE_NAME)->save($vo);
            if(!$isSaved) {
                throw new \Exception("修改标的状态或者放款时间错误");
            }

            //成功提示
            syn_deal_status($vo['id']);
            syn_deal_match($vo['id']);

            //更新项目信息
            $deal_pro_service = new DealProjectService();
            $deal_pro_service->updateProBorrowed($vo['project_id']);
            $deal_pro_service->updateProLoaned($vo['project_id']);

            $job_model = new \core\dao\JobsModel();
            $job_model->priority = 99;
            //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
            $add_job = $job_model->addJob($function, $param, get_gmtime() + 180,9999);
            if (!$add_job) {
                throw new \Exception("放款任务添加失败");
            }
            //更新标放款状态
            $deal_model = new DealModel();
            $save_status = $deal_model->changeLoansStatus($id, 2);
            if (!$save_status) {
                throw new \Exception("更新标放款状态 is_has_loans 失败");
            }
            $GLOBALS['db']->commit();
            $result['status'] = 1;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $result['status'] = 0;
            $result['error_msg'] = $e->getMessage();
        }
        //log
        if($add_job) {
            $data = array(
                "job_id"    =>  $add_job,
                "function"  =>  $function,
                "param" =>  $param,
            );
            save_log('放款'.$vo['name'].L("INSERT_SUCCESS"), C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));
        }
        ajax_return($result);
    }

    /**
     * 检查是否满足收费后先计息后放款条件
     * @param $deal_id 标的Id
     * @param bool $throw_exception 是否抛出异常
     * @return bool
     * @throws Exception
     */
    private function _checkLoanAfterChargeLaterLoan($deal_id,$throw_exception = true) {
        $deal_ext_info = DealExtModel::instance()->getDealExtByDealId($deal_id);
        $deal = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $deal_id))->find();
        $dealProject = M("DealProject")->where(array('id' => $deal['project_id']))->find();

        //是否全部费用前收
        $is_all_fee_before = false;
        if( DealExtModel::FEE_RATE_TYPE_BEFORE == $deal_ext_info['loan_fee_rate_type']
            && DealExtModel::FEE_RATE_TYPE_BEFORE == $deal_ext_info['consult_fee_rate_type']
            && DealExtModel::FEE_RATE_TYPE_BEFORE == $deal_ext_info['guarantee_fee_rate_type']
            && DealExtModel::FEE_RATE_TYPE_BEFORE == $deal_ext_info['pay_fee_rate_type'] )
        {
            $is_all_fee_before = true;
        }

        // “收费后先计息后放款“放款类型仅支持“实际放款”和“受托支付”两种放款方式且仅支持 “前收”一种费用收取方式；其余放款方式和费用收取方式不支持
        $loanMoneyType = $dealProject['loan_money_type'];
        try {
            if($loanMoneyType == 0 || $loanMoneyType == 1) { //实际放款
                if(!$is_all_fee_before) {
                    throw new \Exception('此费用收取方式不支持收费后先计息后放款');
                }
            } else if($loanMoneyType == 2) { //非实际放款
                throw new \Exception('此放款方式不支持收费后先计息后放款');
            } else if($loanMoneyType == 3) { //受托支付
                if(!$is_all_fee_before) {
                    throw new \Exception('此费用收取方式不支持收费后先计息后放款');
                }
            }
        } catch (\Exception $e) {
            if($throw_exception) {
                throw $e;
            } else {
                return false;
            }
        }
        return true;
    }

    public function update_lent() {
        B('FilterString');

        $data = M(MODULE_NAME)->create();
        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $data['id']))->find();
        if(empty($vo)) {
            $errMsg = "无法找到id为{$data['id']}的标";
            $this->error(L("UPDATE_FAILED").$errMsg,0);
        }

        if (isset($_REQUEST['loan_type']) && $_REQUEST['loan_type'] == UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN) {
            //检查是否满足收费后先计息后放款条件
            try{
                $this->_checkLoanAfterChargeLaterLoan($data['id']);
            } catch (\Exception $e) {
                $this->error($e->getMessage(),0);
            }
        }

//        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        $vo['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($vo['id'])){
            $vo['isDtb'] = 1;
        }

        if(empty($data['repay_start_time'])) {
            $data['repay_start_time'] = to_timespan(date("Y-m-d"));
        } else {
            $data['repay_start_time'] = to_timespan($data['repay_start_time']);
        }
        M(MODULE_NAME)->save($data);

        $model_deal_ext = M("DealExt");
        $old_deal_ext = M("DealExt")->where(array('deal_id'    =>  $_REQUEST['deal_ext_id']))->find();

        $deal_ext_data = array();
        // JIRA#1108 计算还款期数
        $deal_model = \core\dao\DealModel::instance()->find($vo['id']);
        $repay_times = $deal_model->getRepayTimes();

        //手续费
        $period_fee_arr = array(
            'loan_fee_arr' => $_REQUEST['loan_fee_arr'],
            'consult_fee_arr' => $_REQUEST['consult_fee_arr'],
            'guarantee_fee_arr' => $_REQUEST['guarantee_fee_arr'],
            'pay_fee_arr' => $_REQUEST['pay_fee_arr'],
            'management_fee_arr' => $_REQUEST['management_fee_arr'],
        );
        if (false === DealService::updateHandlingCharge($vo['id'], $period_fee_arr)) {
            $this->error('标的手续费相关信息更新失败！');
            exit;
        }

        // 放款类型
        if (isset($_REQUEST['loan_type'])) {
            $deal_ext_data['loan_type'] = intval($_REQUEST['loan_type']);
        }

        //防止deal_ext没有，不过一般情况这个都会有
        if(empty($old_deal_ext)) {
            $deal_ext_data['deal_id'] = $data['id'];
            $result = $model_deal_ext->add($deal_ext_data);
        } else {
            $options['where'] = "`deal_id` = {$_REQUEST['deal_ext_id']}";
            $result = $model_deal_ext->save($deal_ext_data, $options);
        }

        if (false === $result) {
            //错误提示
            $dbErr = M()->getDbError();
            save_log($vo['name'].L("UPDATE_FAILED").$dbErr,C('FAILED'), $old_deal_ext, $deal_ext_data, C('SAVE_LOG_FILE'));
            $this->error(L("UPDATE_FAILED").$dbErr,0);
        } else {
            //成功提示
            save_log($vo['name'].L("UPDATE_SUCCESS"),C('SUCCESS'), $old_deal_ext, empty($deal_ext_data) ? $old_deal_ext : $deal_ext_data, C('SAVE_LOG_FILE'));
            $this->success(L("UPDATE_SUCCESS"));
        }
    }

    public function edit() {
        $userInfo = array();
        $id = intval($_REQUEST['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $this->deal = $vo = M(MODULE_NAME)->where($condition)->find();
        if (!$vo) {
            $this->error('获取标的信息失败');
        }

        //获得当前标的tag信息   add by  zhanglei5 2014/08/27
        $deal_tag_service = new DealTagService();
        $tags =  $deal_tag_service->getTagByDealId($id);
        $vo['tags'] = implode(',',$tags);
        $vo['start_time'] = $vo['start_time']!=0?to_date($vo['start_time']):'';
        $vo['bad_time'] = $vo['bad_time']!=0?to_date($vo['bad_time']):'';
        $vo['repay_start_time'] = $vo['repay_start_time']!=0?to_date($vo['repay_start_time'],"Y-m-d"):'';
        $usergroupList = M("UserGroup")->select();

        $this->assign ( 'usergroupList', $usergroupList );

        if($vo['deal_status'] ==0){
            $level_list = load_auto_cache("level");
            $u_level = M("User")->where("id=".$vo['user_id'])->getField("level_id");
            $vo['services_fee'] = $level_list['services_fee'][$u_level];
        }

        if($vo['manage_fee_text'] === ''){
            $vo['manage_fee_text'] = '年化，收益率计算中已包含此项，不再收取。';
        }

        $group = M("DealGroup")->where(array('deal_id'=>$id))->select();

        if($group){
            $t_group = array();
            foreach ($group as $row){
                $t_group[] = $row['user_group_id'];
            }
            $vo['user_group'] = $t_group;
        }

        if ($vo['deal_type'] == DealProjectService::DEAL_TYPE_LGL) {
            //利滚利标
            $compound_service = new DealCompoundService();
            $deal_compound = $compound_service->getDealCompound($id);
            $vo['lock_period'] = $deal_compound['lock_period'];
            $vo['redemption_period'] = $deal_compound['redemption_period'];
            $vo['rate_day'] = $compound_service->convertRateYearToDay($vo['rate'], $vo['redemption_period'], true);
            $vo['compound_id'] = $deal_compound['id'];
            $vo['end_date'] = $deal_compound['end_date'] ? to_date($deal_compound['end_date'], "Y-m-d") : "";
        }

        // JIRA#1108 计算还款期数
        $deal_model = \core\dao\DealModel::instance()->find($vo['id']);
        $this->assign('repay_times', $deal_model->getRepayTimes());

        //订单扩展信息
        $deal_ext = M("DealExt")->where(array('deal_id' => $id))->find();

        // 计算服务费
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $vo['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        }
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        //功夫贷分期咨询费计算
        if($vo['consult_fee_period_rate'] > 0){
            $consult_fee_period = $deal_model->floorfix($vo['borrow_amount'] * $vo['consult_fee_period_rate'] / 100.0);
            $this->assign("consult_fee_period", $consult_fee_period);
        }

        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);
        $management_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['management_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);
        $management_fee = $deal_model->floorfix($vo['borrow_amount'] * $management_fee_rate / 100.0);

        $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);
        $this->assign("management_fee", $management_fee);

        //用户信息处理
        if(!empty($vo['user_id'])) {
            $userInfo = M('User')->where('id='.intval($vo['user_id']))->find();
            if(!empty($userInfo)) {
                $userInfo['audit'] = M('UserBankcard')->where('user_id='.$userInfo['id'])->find();
            }
        }

        if(trim($_REQUEST['type'])=="deal_status"){
            $this->display ("Deal:deal_status");
            exit();
        }

        //处理旧标的 借款保证人信息
        if(!empty($vo)){
            $where = ' where deal_id = '.$id;
            $guarantor = M('deal_guarantor')->query("select id,name,to_user_id,status from ".DB_PREFIX."deal_guarantor $where");
            $status_name = $GLOBALS['dict']['DEAL_GUARANTOR_STATUS'];
            foreach($guarantor as $k=>$v){
                $guarantor[$k]['status_name'] = $status_name[$v['status']];
            }
            $this->assign("guarantor",$guarantor);
        }

        //借款分类
        $deal_cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $deal_cate_tree = D("DealCate")->toFormatTree($deal_cate_tree,'name');
        $this->assign("deal_cate_tree",$deal_cate_tree);

        //担保机构
        $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1')->order('sort DESC')->findAll();
        $this->assign("deal_agency",$deal_agency);

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory",$deal_advisory);

        //支付机构
        $pay_agency = M("DealAgency")->where('is_effect = 1 and type=4')->order('sort DESC')->findAll();
        $this->assign("pay_agency",$pay_agency);

        //管理机构
        $management_agency = M("DealAgency")->where('is_effect = 1 and type=5 ')->order('id ASC')->findAll();
        $this->assign("management_agency",$management_agency);
        //代垫机构
        $advance_agency = M("DealAgency")->where('is_effect = 1 and type=6 ')->order('sort ASC')->findAll();
        $this->assign("advance_agency",$advance_agency);

        //受托机构
        $advance_agency = M("DealAgency")->where('is_effect = 1 and type=7 ')->order('id ASC')->findAll();
        $this->assign("entrust_agency",$advance_agency);

        //代充值机构
        $generation_recharge = M("DealAgency")->where('is_effect = 1 and type=8 ')->order('id ASC')->findAll();
        $this->assign("generation_recharge",$generation_recharge);

        // 交易所
        $jys = M("DealAgency")->where('is_effect = 1 and type=9 ')->order('id ASC')->findAll();
        $this->assign("jys",$jys);

        //渠道机构
        $canal_agency = M("DealAgency")->where('is_effect = 1 and type=10 ')->order('id ASC')->findAll();
        $this->assign("canal_agency",$canal_agency);

        //借款用途
        $deal_type_tree = $this->getDealLoanTypeList();
        $this->assign("deal_type_tree",$deal_type_tree);
        $loan_type_info = M("Deal_loan_type")->where("id = ".$vo['type_id'])->find();
        $this->assign("loan_type_info",$loan_type_info);
        //从配置文件取公用信息
        if ($this->is_cn) {
            $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式
        } else {
            $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);        //还款方式
        }
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        //合同类型
        FP::import("libs.common.app");

        //$contract_tpl_type = get_contract_type();
        $contract_service = new \core\service\ContractService();
	    $contract_tpl_type = $contract_service->getContractType();
        if(!isset($contract_tpl_type[$vo['contract_tpl_type']])){
            $contract_tpl_type[$vo['contract_tpl_type']] = M('MsgCategory')->where(array('type_tag' => $vo['contract_tpl_type']))->getField('type_name');
        }

        $tplRequest = new RequestGetCategorys();
        $tplRequest->setIsDelete(0);
        if($this->is_cn){
            $tplRequest->setSourceType(0);  // 网贷
        }else{
            $tplRequest->setSourceType($vo['deal_type']); // 获取对应deal_type的合同分类列表
        }
        $tplRequest->setType(0); //0-p2p项目(包括网贷，交易所，专享，小贷)

    	$tplResponse = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategorys",
            'args' => $tplRequest,
        ));
        if(!is_array($tplResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }
        $this->assign('contract_tpl_type', $tplResponse->list);    //合同类型

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);
        //限制vip等级
        $vipService = new VipService();
        $vipGrades = $vipService->getVipGradeList();
        $this->assign('vipGrades', $vipGrades);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);

        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = $this->is_cn ? $GLOBALS['sys_config']['TEMPLATE_LIST_CN'] : get_sites_template_list();
        $site_list = changeDealSite($site_list);
        $deal_site_list = get_deal_site($id);

        $this->assign('site_list', $site_list);
        $this->assign('deal_site_list', $deal_site_list);

        $deal_ext['start_loan_time'] = $deal_ext['start_loan_time'] == 0 ? '' : to_date($deal_ext['start_loan_time']);
        $deal_ext['first_repay_interest_day'] = $deal_ext['first_repay_interest_day'] == 0 ? '' : to_date($deal_ext['first_repay_interest_day'], "Y-m-d");
        $deal_ext['base_contract_repay_time'] = $deal_ext['base_contract_repay_time'] == 0 ? '' : to_date($deal_ext['base_contract_repay_time'], "Y-m-d");

        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);
        // 增加变现通特殊逻辑
        if( $vo['type_id'] == $bxtTypeId ){
            $deal_ext['max_rate'] = sprintf("%.5f",$deal_ext['max_rate']);
        }
        if($vo['deal_crowd'] == DealModel::DEAL_CROWD_SPECIFY_USER) {
            $specify_uid_info = M('User')->where('id='.intval($deal_ext['deal_specify_uid']))->find();
            $this->assign('specify_uid_info',$specify_uid_info);
        }

        $this->assign('deal_ext', $deal_ext);
        // 标的优惠码设置信息
        $deal_coupon = M("CouponDeal")->where(array('deal_id' => $id))->find();
        $this->assign("deal_coupon",$deal_coupon);
        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        $disabled_deal_crowd_34 = 0;
        if($project){
            $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);
            $project['business_status'] = intval($project['business_status']);
            $this->assign ( 'pro', $project );
        }else{
            if ($vo['deal_status'] != DealModel::$DEAL_STATUS['waiting'] &&  $vo['deal_status']!=DealModel::$DEAL_STATUS['progressing'] && $vo['deal_crowd'] == 34){
                $disabled_deal_crowd_34 = 1;
            }
        }

        if ($vo['deal_crowd'] == '35') {
            $groupInfos = M("DealCustomUser")->field('group_id,user_name')->where(array("deal_id"=>$id,'type'=>2))->select();
            foreach ($groupInfos as &$item) {
                $item['group_name'] = M("UserGroup")->where(array('id' => $item['group_id']))->getField('name');
                $this->assign('groupType', $item['user_name']);//不想加字段，借用下user_name
            }
            $this->assign('groupInfos', $groupInfos);
        }

        $this->assign("disabled_deal_crowd_34",$disabled_deal_crowd_34);
        // JIRA#3271 平台产品名称定义 2016-03-29 <fanjingwen@ucfgroup.com>
        $vo['prefix_title'] = $deal_ext['deal_name_prefix'];
        $idStr = str_pad(strval($id), 9, strval(0), STR_PAD_LEFT);
        $vo['main_title'] = $project['name'] . 'A' . $idStr;
        // ----------------- over ----------------

        // JIRA#3260 企业账户二期 - 获取用户类型名称 <fanjingwen@ucfgroup.com>
        if (!empty($vo['user_id']) && !empty($userInfo)) {
            $userInfo['user_type_name'] = getUserTypeName($userInfo['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserModel::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userInfo['id']);
                $userInfo['company_name'] = getUserFieldUrl($userInfo, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
            } else {
                $userInfo['real_name'] = getUserFieldUrl($userInfo, UserModel::TABLE_FIELD_REAL_NAME);
            }
        }

        //借款客群
        if(($vo['loan_user_customer_type'] > 0) && array_key_exists($vo['loan_user_customer_type'],$GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'])){
            $vo['loan_user_customer'] = $GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'][$vo['loan_user_customer_type']];
        }
        // ----------------- over ----------------

        //利滚利分类标识
        $this->assign('lgl_tag', \core\dao\DealLoanTypeModel::TYPE_LGL);
        $this->assign('bxt_tag', \core\dao\DealLoanTypeModel::TYPE_BXT);
        $this->assign('dtb_tag', \core\dao\DealLoanTypeModel::TYPE_DTB);
        $this->assign('xffq_tag', \core\dao\DealLoanTypeModel::TYPE_XFFQ);
        $this->assign('zcgl_tag', \core\dao\DealLoanTypeModel::TYPE_GLJH);
        $this->assign('zzjr_tag', \core\dao\DealLoanTypeModel::TYPE_ZHANGZHONG);
        $this->assign('xsjk_tag', \core\dao\DealLoanTypeModel::TYPE_XSJK);
        $this->assign('xjdcdt_tag', \core\dao\DealLoanTypeModel::TYPE_XJDCDT);
        $this->assign ('vo', $vo);
        $this->assign("userInfo",$userInfo);
        $this->assign("project_business_status",DealProjectModel::$PROJECT_BUSINESS_STATUS);

        // 分期 平台手续费
        if (!empty($deal_ext['loan_fee_ext'])) {
            $loan_fee_arr =  json_decode($deal_ext['loan_fee_ext'], true);
            $this->assign("loan_fee_arr",$loan_fee_arr);
            $proxy_sale['loan_fee_sum'] = array_sum($loan_fee_arr);
            $proxy_sale['loan_first_rate'] = ceilfix($loan_fee_arr[0] / $proxy_sale['loan_fee_sum'] * $vo['loan_fee_rate'], 5);
            $proxy_sale['loan_last_rate'] = ceilfix($vo['loan_fee_rate'] - $proxy_sale['loan_first_rate'], 5);
            $proxy_sale['loan_rate_sum'] = $vo['loan_fee_rate'];
            $this->assign("proxy_sale", $proxy_sale);
        }
        $template = $this->is_cn ? 'edit_cn' : 'edit';
        $this->display ($template);
    }

    /**
     * 根据提前还款违约金系数和提前还款罚息天数计算利息补偿天数
     * @param $prepay_rate 提前还款违约金系数
     * @param $prepay_penalty_days 提前还款罚息天数
     *
     * @return int 利息补偿天数
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    private function getCompensationDays($prepay_rate, $prepay_penalty_days, $rate){
        return $prepay_rate * \libs\utils\Finance::DAY_OF_YEAR / $rate + $prepay_penalty_days;
    }

    /**
     * 修改
     * @actionLock
     * lockauthor qicheng
     */
    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $data['id']))->find();
        //专享项目如果更改项目中唯一进行中标的状态为等待确认,则同时更新项目状态为待上线
        if(isset($_REQUEST['deal_status'])){
            if(intval($_REQUEST['deal_status']) === DealModel::$DEAL_STATUS['waiting']){
                $dealProService = new DealProjectService();
                if($dealProService->isProjectEntrustZX($vo['project_id'])){
                    $dealModel = new DealModel();
                    $dealIds = $dealModel->findAllBySql("SELECT id FROM firstp2p_deal WHERE project_id = ".$vo['project_id']." AND deal_status > 0",true);
                    if(count($dealIds) == 1){
                        foreach($dealIds as $dealId){
                            if($dealId['id'] == $vo['id']){
                                $changeProStatus = DealProjectModel::instance()->changeProjectStatus($vo['project_id'],DealProjectModel::$PROJECT_BUSINESS_STATUS['waitting']);
                                if(!$changeProStatus){
                                    $this->error('受托专享项目状态变更失败！');
                                }
                            }
                        }
                    }
                }
            }
        }
        if(isset($_REQUEST['deal_status'])&&((intval($_REQUEST['deal_status']) ===intval(DealModel::$DEAL_STATUS['waiting']))||(intval($_REQUEST['deal_status']) ===intval(DealModel::$DEAL_STATUS['progressing'])))){
            if($data['deal_crowd']=='34'){
                $dealCustomUserService = new \core\service\DealCustomUserService ();
                if(!empty($_FILES ['upfile'] ['name']) ){
                    $result=$dealCustomUserService->getDealUserList($data['id']);
                    $is_delete=1;
                    if(!empty($result)){
                        $is_delete=0;
                    }
                    $importResult=$this->importCsvUserIds($data['id'],$is_delete);
                    $updateCache=$dealCustomUserService->getCacheDealUserIds(1);
                    save_log($vo['id'].'投资限定条件1批量导入用户'.L("UPDATE_SUCCESS"),C('SUCCESS'),'',$_FILES ['upfile'] ['name']);
                }
            } else if ($data['deal_crowd'] == '35') {
                $adm_session = es_session::get(md5(conf("AUTH_KEY" )));
                $dealCustomUserService = new \core\service\DealCustomUserService();
                $saveGroupIds = $dealCustomUserService->saveGroupIds($data['id'], $_POST['group_ids'], $adm_session['adm_id'],$_POST['group_type']);
                $updateCache=$dealCustomUserService->getCacheDealUserGroupIds(1);
                save_log($vo['id'].'投资限定条件1指定用户组可投'.L("UPDATE_SUCCESS"),C('SUCCESS'),'',json_encode($_POST['group_ids']));
            }
            if( isset($data['deal_crowd']) && ($vo['deal_crowd']=='34'&& $data['deal_crowd']!='34')){
                $dealCustomUserService = new \core\service\DealCustomUserService ();
                $deleteResult=$dealCustomUserService->deleteInfo($data['id']);
                if(!$deleteResult){
                    $this->error ( "删除csv数据失败" );
                }
                $updateCache=$dealCustomUserService->getCacheDealUserIds(1);
            } else if (isset($data['deal_crowd']) && ($vo['deal_crowd']=='35'&& $data['deal_crowd']!='35')) {
                $adm_session = es_session::get(md5(conf("AUTH_KEY" )));
                $dealCustomUserService = new \core\service\DealCustomUserService ();
                $updateCache=$dealCustomUserService->getCacheDealUserGroupIds(1);
                $deleteGroupRes = $dealCustomUserService->saveGroupIds($data['id'], [], $adm_session['adm_id']);
            }
        }
        // 标的状态变更前，先更新项目业务状态
        $to_change_business_status_map = array(
            DealModel::$DEAL_STATUS['progressing'] => DealProjectModel::$PROJECT_BUSINESS_STATUS['process'],
            DealModel::$DEAL_STATUS['failed'] => DealProjectModel::$PROJECT_BUSINESS_STATUS['waitting'],
        );
        if (isset($to_change_business_status_map[$data['deal_status']]) && false === $this->changeEntrustZXProjectStatus($vo['project_id'], $to_change_business_status_map[$data['deal_status']])) {
            $this->error('受托专享项目状态变更失败！');
        }

        // 检查交易所配置最低限额
        if ($data['is_float_min_loan'] == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES){

            $jys_min_money = (new DealModel())->getJYSMinLoanMony($data['jys_id']);
            if (!empty($jys_min_money) && $data ['min_loan_money'] < $jys_min_money){
                $this->error ( "不能低于该交易所最低投资限额，最低投资限额为：".$jys_min_money );
            }
        }

        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));

        if(!empty($vo['user_id'])) {
            $userInfo = M('User')->where('id='.intval($vo['user_id']))->find();
            if(intval($userInfo['idcardpassed']) !== 1) {
                $this->error('借款人用户身份未认证');
            }
            if(!empty($userInfo['id'])) {
                $userAuditInfo = M('UserBankcard')->where('user_id='.$userInfo['id'])->find();
                if(intval($userAuditInfo['status']) !== 1) {
                    $this->error('借款人用户银行卡未验证');
                }
            }
        }

        $_income_fee_rate = trim($_POST['income_fee_rate']); // 年化出借人收益率
        $_annualized_rate = trim($_POST['rate']);    // 借款年利率
        $_income_float_rate = trim($_POST['income_float_rate']);; //年化收益浮动利率
        $_income_base_rate = trim($_POST['income_base_rate']);; // 年化收益基本利率

        if(bccomp($_income_fee_rate,$_annualized_rate,5) !=0 || bccomp($_annualized_rate,bcadd($_income_float_rate,$_income_base_rate,5),5) !=0) {
            $this->error('请注意 ： 借款年利率＝年化出借人收益率 = (年化收益基本利率 + 年化收益浮动利率');
        }

        $borrow_min = app_conf ( 'MIN_BORROW_QUOTA' );
        $borrow_max = app_conf ( 'MAX_BORROW_QUOTA' );

        $project = array();
        if($vo['project_id']){
            $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        }

        if($project && $data['deal_status'] != 3){
            if($vo['publish_wait'] == 1){
                if(bccomp($project['money_borrowed'] + $data['borrow_amount'], $project['borrow_amount']) == 1){
                    $this->error ( "项目上标金额 + ".$data['borrow_amount']." 已超出项目总额，请修改" );
                }
            }elseif(bccomp($project['money_borrowed'] - $vo['borrow_amount'] + $data['borrow_amount'], $project['borrow_amount']) == 1){
                $this->error ( "项目上标金额 + ".$data['borrow_amount']." 已超出项目总额，请修改" );
            }
        }
       if($data ['borrow_amount'] < $borrow_min || $data ['borrow_amount'] > $borrow_max) {
            $this->error ( "‘借款金额’应为" . $borrow_min . "至" . $borrow_max . "的整数！" );
        }

        if(isset($data['prepay_days_limit']) && $data['prepay_days_limit'] <0){
            $this->error ( "‘提前还款限制期’应大于等于0" );
        }

        /*$desc_wordnum = get_wordnum ( $data ['description'] );
        if (isset($data ['description']) && ($desc_wordnum < 5 || $desc_wordnum > 1000)) {
            $this->error ( "‘借款描述’应在5-1000个字之间" );
        }*/

        if(isset($data['repay_time']) && empty($data['repay_time'])){
            $this->error('借款期限不能为空');
        }
        if(($data ['max_loan_money']) > 0 && ($data ['max_loan_money']) < ($data ['min_loan_money'])) {
            $this->error ( "最大金额不能小于最小金额" );
        }

        // 如果是通知贷
        if ($_REQUEST['deal_type'] == 1) {
            $this->_insertCheckDealCompound();
        }
        if (!$this->is_cn){
            if ( $_REQUEST['deal_type'] == 0 && (!is_numeric($_POST['rebate_days']) || $_POST['rebate_days'] < 0)) {
                $this->error('优惠码返利天数不能为负数');
            }
        }
        //“年化收益基本利率”和“年化收益浮动利率” 的处理
        $model_deal_ext = M('DealExt');
        $deal_ext_data = $model_deal_ext->create();
        // 是否需要短信通知3日还款提醒  --add by zhanglei5 20141013
        if (isset($_REQUEST['need_repay_notice'])) {
            $deal_ext_data['need_repay_notice']= isset($_REQUEST['need_repay_notice']) ? $_REQUEST['need_repay_notice'] : 0;
        }
        $income_rate_sum = sprintf("%.5f", $deal_ext_data['income_base_rate'] + $deal_ext_data['income_float_rate']);
        /*if($income_rate_sum - sprintf("%.5f", $data['income_fee_rate']) != 0){
            $this->error('年化出借人收益率 = 年化收益基本利率 + 年化收益浮动利率');
        }*/

        $deal_ext_data['start_loan_time'] = trim($deal_ext_data['start_loan_time']) != '' && $data['deal_status'] == 0 ? to_timespan($deal_ext_data['start_loan_time']) : '';
        if($deal_ext_data['start_loan_time'] && $deal_ext_data['start_loan_time'] <= get_gmtime()){
            $this->error('“开标时间”应当大于当前时间');
        }

        $deal_ext_data['base_contract_repay_time'] = trim($deal_ext_data['base_contract_repay_time']) == '' ? 0 : to_timespan($deal_ext_data['base_contract_repay_time']);

        //转让资产类别
        if (isset($_REQUEST['contract_transfer_type'])) {
            $deal_ext_data['contract_transfer_type']= isset($_REQUEST['contract_transfer_type']) ? intval($_REQUEST['contract_transfer_type']) : 0;
        }
        //年化借款平台手续费类型
        if (isset($_REQUEST['loan_fee_rate_type'])) {
            $deal_ext_data['loan_fee_rate_type']= !empty($_REQUEST['loan_fee_rate_type']) ? intval($_REQUEST['loan_fee_rate_type']) : 1;
        }

        //年化借款咨询费类型
        if (isset($_REQUEST['consult_fee_rate_type'])) {
            $deal_ext_data['consult_fee_rate_type']= !empty($_REQUEST['consult_fee_rate_type']) ? intval($_REQUEST['consult_fee_rate_type']) : 1;
        }

        //年化担保费类型
        if (isset($_REQUEST['guarantee_fee_rate_type'])) {
            $deal_ext_data['guarantee_fee_rate_type']= !empty($_REQUEST['guarantee_fee_rate_type']) ? intval($_REQUEST['guarantee_fee_rate_type']) : 1;
        }

        //年化渠道费类型
        if($vo['canal_agency_id'] != 0){
            if (isset($_REQUEST['canal_fee_rate_type'])) {
                $deal_ext_data['canal_fee_rate_type']= !empty($_REQUEST['canal_fee_rate_type']) ? intval($_REQUEST['canal_fee_rate_type']) : 1;
            }
        }else{
            $deal_ext_data['canal_fee_rate_type'] = 1;
            $deal_ext_data['canal_fee_rate_ext'] = '';
            $data['canal_fee_rate'] = 0;
        }

        //年化借款咨询费类型
        if (isset($_REQUEST['pay_fee_rate_type'])) {
            $deal_ext_data['pay_fee_rate_type']= !empty($_REQUEST['pay_fee_rate_type']) ? intval($_REQUEST['pay_fee_rate_type']) : 1;
        }

        //年化管理服务费类型
        if (isset($_REQUEST['management_fee_rate_type'])) {
            $deal_ext_data['management_fee_rate_type']= !empty($_REQUEST['management_fee_rate_type']) ? intval($_REQUEST['management_fee_rate_type']) : 1;
        }


        //基础合同编号
        $deal_ext_data['leasing_contract_title'] = isset($_REQUEST['leasing_contract_title']) ? $_REQUEST['leasing_contract_title'] : '';

        //借款用途分类
        $deal_ext_data['loan_application_type']= isset($_REQUEST['loan_application_type']) ? intval($_REQUEST['loan_application_type']) : 0;
        $deal_ext_data['line_site_id'] = $_POST['line_site_id'];
        $deal_ext_data['line_site_name'] = $_POST['line_site_name'];

        //平台费折扣率 JIRA#5361
        $deal_ext_data['discount_rate'] = $_POST['discount_rate'];

        //JIRA#2925  合同变更需求，新增“资产转让类别”
        //转让资产类别数据校验,若资产转让类别选择为“无”，则此五项为非必填项。若资产转让类别选择为“债权”或“资产收益权”，则“基础合同的编号”、“原始债务人”、“基础合同交易金额”三项为必填项
        if ($deal_ext_data['contract_transfer_type'] > 0) {
            if("" == strval($deal_ext_data['leasing_contract_num'])) {
                $this->error('基础合同的编号 不能为空！');
            }
            if("" == strval($deal_ext_data['lessee_real_name'])) {
                $this->error('原始债务人 不能为空！');
            }
            if("" == strval($deal_ext_data['leasing_money'])) {
                $this->error('基础合同交易金额 不能为空！');
            }
        }

        //所属网站：进行中之前必填
        $deal_site = $_REQUEST['deal_site'];
        if($vo['deal_status'] == 0 && empty($deal_site)){
            $this->error('所属网站 不能为空！');
        }
        if(is_array($deal_site)){
            $res_deal_site = update_deal_site($data['id'], $deal_site);
        }

        //进行中 可以改为 等待确认 edit by wenyanlei 20140324
        if($vo['deal_status'] > 1 && $data['deal_status'] == 0){
            if ($res_deal_site === true) {
                $this->error("所属站点修改成功，其他信息不允许修改！");
            } else {
                $this->error('不可以修改！');//从"进行中" 或之后的状态 改为 "等待材料"
            }
        }

        if($vo['publish_wait'] == 1 && $data['deal_status'] == 4 && $vo['deal_status'] == 0){
            $this->error('不可直接改为“还款中”');
        }

        //整理数据
        $data['deal_status'] = intval($data['deal_status']) > 0 ? intval($data['deal_status']) : 0;
        $data['update_time'] = get_gmtime();
        $data['publish_wait'] = '0';
        $data['manager'] = htmlspecialchars($data['manager']);
        $data['manager_mobile'] = htmlspecialchars($data['manager_mobile']);
        $data['start_time'] = trim($data['start_time']) == '' ? 0 : to_timespan($data['start_time']);
        $data['bad_time'] = trim($data['bad_time']) == '' ? 0 : to_timespan($data['bad_time']);

        $data['repay_start_time'] = trim($data['repay_start_time']) == '' ? 0 : to_timespan($data['repay_start_time']);

        $compensation_days = $this->getCompensationDays($data['prepay_rate'], $data['prepay_penalty_days'], $data['rate']);

        $data['compensation_days'] = $compensation_days;
        $data['loan_compensation_days'] = $this->getCompensationDays($data['prepay_rate'], $data['prepay_penalty_days'], $data['income_fee_rate']);
        $data['update_time'] = get_gmtime();

        // 加入tags部分  add by zhanglei5 20140827
        $deal_tags = trim($_REQUEST['deal_tags']);
        if(strlen($deal_tags) > 0) {
            $deal_tag_service = new DealTagService();
            $rs = $deal_tag_service->updateTag($data['id'], $deal_tags);
        } else {
            DealTagModel::instance()->deleteByDealId($data['id']);
        }

        if($data['repay_start_time'] > 0 && intval($data['next_repay_time']) == 0){
            $delta_month_time = get_delta_month_time($vo['loantype'], $vo['repay_time']);

            // 按天一次到期
            if($vo['loantype'] == 5){
                $data['next_repay_time'] = next_replay_day_with_delta($data['repay_start_time'], $delta_month_time);
            }else{
                $data['next_repay_time'] = next_replay_month_with_delta($data['repay_start_time'], $delta_month_time);
            }
        }
        $loan_type_info = M("Deal_loan_type")->where("id = ".intval($data['type_id']))->find();

        // 判断第一期还款日是否可用
        if ($vo['deal_status'] == 4 || $vo['deal_status'] == 3 || $vo['deal_status'] == 5 ||  $loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_XFFQ) {
            unset($deal_ext_data['first_repay_interest_day']);
        }

        if(  $loan_type_info['type_tag'] != \core\dao\DealLoanTypeModel::TYPE_XFFQ) {
            $deal_ext_data['first_repay_interest_day'] = to_timespan($deal_ext_data['first_repay_interest_day']);
            if ($deal_ext_data['first_repay_interest_day']) {
                if ( $deal_ext_data['first_repay_interest_day'] <= $data['repay_start_time'] || $deal_ext_data['first_repay_interest_day'] >= $data['next_repay_time']) {
                    $this->error("下一期还款时间应为大于放款时间并小于下期还款时间");
                }
                if (to_date($deal_ext_data['first_repay_interest_day'], "d") > 28) {
                    $this->error("下一期还款日不要设置为29,30,31号");
                }
            }
        }

        $loan_type_info = M("Deal_loan_type")->where("id = ".intval($vo['type_id']))->find();
        // 增加变现通特殊逻辑
        if( $loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_BXT ){
            $deal_ext_data['max_rate'] = sprintf("%.5f",$_REQUEST['max_rate']);
        }

        // JIRA#3271 平台产品名称定义 2016-03-29 <fanjingwen@ucfgroup.com>
        // 更新deal中的标名
        if (0 == $vo['deal_status']) {
            $prefixStr = mysql_real_escape_string($_REQUEST['prefix_title']);
            // deal ext
            $deal_ext_data['deal_name_prefix'] = $prefixStr;
        }
        // ------------- over ------------

        if($vo['publish_wait'] == 1){
            $deal_ext_data['publish_time'] = get_gmtime();
        }

        if (!$this->is_cn && $vo['deal_status'] == 0) {
            //更新优惠码返利天数
            $rebate_days = intval(trim($_POST['rebate_days']));
            $repay_time = intval($data['repay_time']) != 0? intval($data['repay_time']):$vo['repay_time'];
            $pay_type = intval(trim($_POST['pay_type']));
            $pay_auto = intval(trim($_POST['pay_auto']));
            $is_rebate = intval(trim($_POST['is_rebate']));
            $coupon_deal_service = new CouponDealService();
            if ($_REQUEST['deal_type'] == 1 ) {
                 // 通知贷的页面form 没有rebate_days这个字段 $vo 为旧数据，data为thinkphp 创建的from 提交对象
                 $loantype = isset($data['loantype'])?$data['loantype']:$vo['loantype'];
                 $rebate_days = ($loantype == 5) ? $repay_time : $repay_time*30;
            }

            $res = $coupon_deal_service->updateRebateDaysByDealId($data['id'], $rebate_days ,$pay_type ,$pay_auto,$is_rebate);
            if(!$res){
                $this->error("更新标优惠码信息失败");
            }
            save_log('更新标优惠码信息成功',1,array(),array('deal_id'=>$data['id'],'rebate_days'=>$rebate_days));
        }
        /*
        $deal_coupon = M("CouponDeal");
        $deal_coupon_data = $deal_coupon->create();
        $deal_coupon_data['deal_id'] = $data['id'];
        $deal_coupon_data['update_time'] = get_gmtime();
        if ($_REQUEST['deal_type'] == 1) {
            // 通知贷的页面form 没有rebate_days这个字段 $vo 为旧数据，data为thinkphp 创建的from 提交对象
            $deal_coupon_data['rebate_days'] = ($data['loantype'] == 5) ? $data['repay_time'] : $data['repay_time']*30;
        }
        //开始事务
        $GLOBALS['db']->startTrans();
        // 更新返利天数和返利金额
        $is_new_coupon_deal = false;
        try {
            $model_deal_coupon_info = $deal_coupon->where(array('deal_id' => $data['id']))->find();
            if (empty($model_deal_coupon_info)) {
                $model_deal_coupon_res = $this->addCouponDeal($_REQUEST['deal_type'], $data['id']);
                if (!$model_deal_coupon_res) {
                    throw new Exception('获取标优惠码信息失败');
                }
                $is_new_coupon_deal = true;
            }

            if ($is_new_coupon_deal==false){
                $deal_coupon_res = $deal_coupon->save($deal_coupon_data);
                if (!$deal_coupon_res){
                    throw new Exception('更新标优惠码设置信息失败');
                }
            }
            if ($_REQUEST['deal_type'] == 0) {
                if ($model_deal_coupon_info['rebate_days'] != $deal_coupon_data['rebate_days']) {
                    // 更新返利天数更新返点比例金额
                    $coupon_service_obj = new CouponLogService();
                    $coupon_service_rebate_days_res = $coupon_service_obj->updateRebateDaysAndAmount($data['id'], $deal_coupon_data['rebate_days']);
                    if ($coupon_service_rebate_days_res === false) {
                        throw new Exception('根据返利天数更新返点比例金额失败');
                    }
                }
            }

            $GLOBALS['db']->commit();
            save_log("更新返利天数成功",1,$model_deal_coupon_info,$deal_coupon_data);
        } catch (Exception $e) {
                $GLOBALS['db']->rollback();
                save_log($e->getMessage(),1,$model_deal_coupon_info,$deal_coupon_data);
                $this->error($e->getMessage());
        }
        */
        // 由于的运营的人比较懒，这个一个给标自动打tag的点
        if ($vo['deal_status'] == 0 && $data['deal_status'] == 1) {
            $state_manager = new \core\service\deal\StateManager();
            $state_manager->setDeal($data);
            $state_manager->work();
        }



        $loan_type_info = M("Deal_loan_type")->where("id = ".intval($data['type_id']))->find();

        $data['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($data['id'])){
            $data['isDtb'] = 1;
        }

        // 需要报备
        $dealService = new DealService();

        //if($data['deal_status'] == 1 && $data['report_type'] == 1 && $dealService->isNeedReportToBank($data['id'],$deal_tags)){
        if($data['deal_status'] == 1 && $data['report_type'] == 1){
            $data['project_id'] = $vo['project_id'];
            $data['user_id'] = $vo['user_id'];
            $data['use_info'] = $deal_ext_data['use_info'];
            try{
                $isReportUpdate = ($vo['report_status'] == 1) ? true :false;
                $reportService = new P2pDealReportService();
                $reportRes = $reportService->dealReportRequest($data,$isReportUpdate);
            }catch (\Exception $ex){
                $this->error($ex->getMessage());
            }
        }else{
            $data['report_type'] = 0;
        }


        //保存利滚利数据
        if($loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_LGL){
            $deal_compound['redemption_period'] = intval($_REQUEST['redemption_period']);
            $deal_compound['lock_period'] = intval($_REQUEST['lock_period']);
            $deal_compound['deal_id'] = $data['id'];
            $deal_compound['end_date'] = $_REQUEST['end_date'] ? to_timespan($_REQUEST['end_date']) : 0;

            if (empty($_REQUEST['deal_compound_id'])) {
                $deal_compound['create_time'] = get_gmtime();
                $res_compound = M('DealCompound')->add($deal_compound);
            } else {
                $deal_compound['id'] = intval($_REQUEST['deal_compound_id']);
                $deal_compound['update_time'] = get_gmtime();
                $res_compound = M('DealCompound')->save($deal_compound);
            }
            if ($res_compound === false) {
                $this->error("更新通知贷信息失败");
            }
            $data['deal_type'] = 1;
        }

        if($data['deal_crowd'] == 2){ // 特定用户组
            $dealGroupModel = M('DealGroup');
            $dealGroupModel->where(array('deal_id'=>$data['id']))->delete();
            $grouplist = $_POST['user_group'];
            if($grouplist){
                foreach ($grouplist as $key => $value) {
                    $dealGroupModel->add(array('deal_id'=>$data['id'],'user_group_id'=>$value));
                }
            }
        }elseif($data['deal_crowd'] == 16) {// 指定用户可投
            $deal_ext_data['deal_specify_uid'] = trim($_POST['specify_uid']);
        }elseif($data['deal_crowd'] == 33) {
            //指定VIP用户可投
            $deal_ext_data['deal_specify_uid'] = trim($_POST['specify_vip']);
        }

        // 计算pmt各种值
        $dealInfo['rate'] = trim($_POST['rate']);
        $dealInfo['repay_time'] = $_POST['repay_time'] ? trim($_POST['repay_time']) : $vo['repay_time'];
        $dealInfo['loantype'] = $_POST['loantype'] ? trim($_POST['loantype']) : $vo['loantype'];
        $dealInfo['borrow_amount'] = trim($_POST['borrow_amount']);
        $dealInfo['borrow_sum'] = trim($_POST['borrow_amount']);
        $dealInfo['manage_fee_rate'] = trim($_POST['manage_fee_rate']);
        $dealInfo['is_publish'] = trim($_POST['is_publish']);
        $dealInfo['3_3'] = trim($_POST['3_3']);
        $dealInfo['3_6'] = trim($_POST['3_6']);
        $dealInfo['3_9'] = trim($_POST['3_9']);
        $dealInfo['3_12'] = trim($_POST['3_12']);
        $pmtinfo = $this->getPmt($dealInfo);

        $data['rate'] = $pmtinfo[0]['rate'] * 100;
        $data['period_rate'] = $pmtinfo[0]['period_income_rate'] * 100; // 理财期间收益率
        //$data['income_fee_rate'] = $pmtinfo[0]['simple_interest'] * 100;
        $data['income_total_rate'] = $data['income_fee_rate'] + $deal_ext_data['income_subsidy_rate'];

        if($data['deal_status'] == 4){
            unset($data['rate']);
            if(!in_array($vo['deal_status'], array(2,4))){
                $this->error("未满标，无法设置为“还款中”状态!");
            }

            //放款
            if($vo['is_has_loans'] == 0 && $data['deal_status'] == 4){

                //除公益标之外，验证合同状态
                if($vo['contract_tpl_type']){
                    //验证合同是否已生成,添加合同服务化标的逻辑
                    if(!is_numeric($vo['contract_tpl_type'])){
                        $contract_service = new ContractService();
                        $contract_count = $contract_service->getCountByDealid($vo['id']);
                        if($contract_count == 0) {
                            $this->error("合同尚未生成!");
                        }
                        //验证合同是否已经签署
                        $notsign_count = $contract_service->getNotSginCountByDealid($vo['id']);
                        if($notsign_count) {
                            $this->error("借款人或担保公司的合同未通过!");
                        }
                    }else{
                        $dealContractModel = new DealContractModel();
                       if($dealContractModel->getDealContractUnSignInfo($vo['id']) == 0){
                            $dealService = new DealService();
                            if(!$dealService->isDealCrowdfunding($vo['id'])){
                                $this->error("借款人或担保,咨询公司,受托方的合同未通过");
                            }
                        }
                    }
                }

                $vo['agency_id'] = $data['agency_id'];
                $vo['advisory_id'] = $data['advisory_id'];
                $vo['entrust_agency_id'] = $data['entrust_agency_id'];
                $vo['canal_agency_id'] = $data['canal_agency_id'];

                if($data['isDtb'] == 1) {
                    $vo['management_agency_id'] = $data['management_agency_id'];
                }
                $vo['isDtb'] = $data['isDtb'];

                foreach($data as $key=>$value){
                    $vo[$key]=$value;
                }
                //放款
                $add_loans_job = $this->_make_loans_money($vo);
                if(!$add_loans_job){
                    $this->error("添加放款job失败！");
                }

                /* if($this->_make_loans_money($vo)){
                    $data['is_has_loans'] = 2;
                } */
            }
        } elseif ($data['deal_status'] == DealModel::$DEAL_STATUS['failed']) {     // 流标
            $data['is_doing'] = 1;
        }

        $data['is_float_min_loan'] = !isset($_REQUEST['is_float_min_loan'])  ? 0 : 1;

        //确认修改
        $list = M(MODULE_NAME)->save($data);

        // 保存订单扩展信息
        $deal_ext_data['deal_id'] = $data['id'];
        if (empty($_REQUEST['deal_ext_id'])) {
            $model_deal_ext->add($deal_ext_data);
        }else{
            $model_deal_ext->save($deal_ext_data);
        }

        // 更新 标的扩展表中的手续费相关参数
        $period_fee_arr = array(
            'loan_fee_arr' => $_REQUEST['loan_fee_arr'],
            'consult_fee_arr' => $_REQUEST['consult_fee_arr'],
            'guarantee_fee_arr' => $_REQUEST['guarantee_fee_arr'],
            'pay_fee_arr' => $_REQUEST['pay_fee_arr'],
            'management_fee_arr' => $_REQUEST['management_fee_arr'],
            'canal_fee_arr' => $_REQUEST['canal_fee_arr'],
        );
        if (false === DealService::updateHandlingCharge($data['id'], $period_fee_arr)) {
            $this->error('标的手续费相关信息更新失败！');
        }
        //如果是发布申请，给担保人发信息
        if(intval($vo['publish_wait']) === 1){
            $this->_publishMsg($data['id']);
        }

        if($data['deal_status'] == 3){
            FP::import("libs.common.app");
            FP::import("app.deal");
            $deal = get_deal($data['id']);
        }

        if (false === $list) {
            //错误提示
            $dbErr = M()->getDbError();
            save_log($vo['name'].L("UPDATE_FAILED").$dbErr,C('FAILED'), $vo, $data, C('SAVE_LOG_FILE'));
            $this->error(L("UPDATE_FAILED").$dbErr,0);
        } else {
            FP::import("libs.common.app");

            //成功提示
            syn_deal_status($data['id']);
            syn_deal_match($data['id']);

            //合同服务更新标的模板分类ID
            $contractRequest = new RequestUpdateDealCId();
            $contractRequest->setDealId(intval($data['id']));
            $contractRequest->setCategoryId(intval($data['contract_tpl_type']));
            $contractRequest->setType(0);
            $contractRequest->setSourceType($data['deal_type']);

            $contractResponse = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "updateDealCId",
                'args' => $contractRequest,
            ));

            //更新项目信息
            $deal_pro_service = new DealProjectService();
            $deal_pro_service->updateProBorrowed($vo['project_id']);
            $deal_pro_service->updateProLoaned($vo['project_id']);

            //成功提示
            save_log($vo['name'].L("UPDATE_SUCCESS"),C('SUCCESS'), $vo, $data, C('SAVE_LOG_FILE'));
            $this->success(L("UPDATE_SUCCESS"));
        }
    }

    //修改借款金额为当前总投资额
    public function updatemoney(){
        $id = intval($_REQUEST ['id']);

        $deal_data = new \core\data\DealData();
        if ($deal_data->lockDealBid($id) === false) {
            $this->error("系统繁忙，请稍后再试");
        }

        $dealInfo = M("Deal")->where(array('is_delete' => 0, 'id' => $id))->find();
        $dealInfo['url'] = url("index","deal",array("id"=>$id));
        //echo $dealInfo['deal_status'];exit;
        //判断前置条件
        if($dealInfo['deal_status'] !=1) $this->error('只有状态为“进行中”的标才能修改为满标');
        if($dealInfo['load_money'] <= 0) $this->error('投资额为0的标禁止修改为满标');
        FP::import("app.deal");
        FP::import("libs.common.app");

        //修改金额及状态
        $before_amount = $dealInfo['borrow_amount'];

        $GLOBALS['db']->startTrans();

        try {
            $deal_model = new DealModel();
            $r = $deal_model->updateMoney($id);
            if ($r === false) {
                throw new \Exception('更新标的金额失败');
            }

            //更新项目信息
            $deal_pro_service = new DealProjectService();
            $r = $deal_pro_service->updateProBorrowed($dealInfo['project_id']);
            if ($r === false) {
                throw new \Exception('更新项目金额失败');
            }

            $r = $deal_pro_service->updateProLoaned($dealInfo['project_id']);
            if ($r === false) {
                throw new \Exception('更新项目已投金额失败');
            }

            $deal_info = $deal_model->find($dealInfo['id']);
            $dinfo = $deal_info->getRow();

            $dinfo['borrow_sum'] = $dinfo['borrow_amount'];
            //发送消息,此处只会给单一标发送
            $function  = '\send_full_failed_deal_message';
            $params = array('deal' => $dinfo, 'type' => 'full');
            $r = \core\dao\JobsModel::instance()->addJob($function, $params);
            if ($r === false) {
                throw new \Exception('添加满标任务失败');
            }
            //send_full_failed_deal_message($dealInfo,'full');

            //$this->send_contract($dealInfo);
            // 改为队列发送合同
            $jobsModel = new JobsModel();

            $contract_function = '\core\service\DealLoadService::sendContract';
            $contract_param = array(
                'deal_id' => $id,
                'load_id' => 0,
                'is_full' => true,
                'create_time' => time(),
            );

            $jobsModel->priority = 121;
            $contract_ret = $jobsModel->addJob($contract_function, array('param' => $contract_param)); //不重试
            if ($contract_ret === false) {
                throw new \Exception('满标合同任务插入注册失败');
            }

            $full_ckeck_function = '\core\service\DealLoadService::fullCheck';
            $full_ckeck_param = array(
                'deal_id' => $id,
            );
            $jobsModel->priority = 122;
            $full_check_ret = $jobsModel->addJob($full_ckeck_function, array('param' => $full_ckeck_param), get_gmtime() + 1800); //不重试
            if ($full_check_ret === false) {
                throw new \Exception('检测标的合同任务注册失败');
            }

            //$function  = '\core\service\DealService::sendDealContract';
            //$params = array('deal_id' => $id);
            //\core\dao\JobsModel::instance()->addJob($function, $params);

            //$deal_service = new DealService();
            //$deal_service->sendDealContract($id);

            //向9888cn p2p发送相关数据
            $this->put_p2p_data($id);

            //满标触发首尾标附加返利
            $coupon_log_service = new CouponLogService();
            $r = $coupon_log_service->handleCouponExtraForDeal($id);
            if ($r === false) {
                throw new \Exception('优惠码结算失败');
            }

            // 更新手续费相关
            if (false === DealService::updateHandlingCharge($id, array(), true)) {
                throw new \Exception('标的手续费相关信息更新失败！');
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            save_log('id为'.$dealInfo['id'].'的标，截标失败: ' . $e->getMessage(), 1);
            $this->error("操作失败: " . $e->getMessage());
        }

        $deal_data->unlockDealBid($id);

        // 截标操作通知工单满标
        \core\service\partner\PartnerService::projectStatusChangedNotify($id, 2);

        save_log('id为'.$dealInfo['id'].'的标，借款金额由'.$before_amount.'改为'.$dealInfo['load_money'],1);
        $this->success(L("借款金额已改为".$dealInfo['load_money']));
    }

    /**
     * 改为借款中状态后(deal_status=4)，放款
     * @param mix $deal_data deal信息
     */
    private function _make_loans_money($deal_data){

        // 检查机构账户
        $advisory_info = $this->get_deal_agency($deal_data['advisory_id']); // 咨询机构
        $agency_info = $this->get_deal_agency($deal_data['agency_id']); // 担保机构
        $pay_info = $this->get_deal_agency($deal_data['pay_agency_id']); // 担保机构

        if (empty($advisory_info) || empty($agency_info)|| empty($pay_info)) {
            return $this->error("咨询担保,支付机构信息有误");
        }

        if ($deal_data['isDtb'] == 1) {
            $management_info = $this->get_deal_agency($deal_data['management_agency_id']); // 管理机构
            if (empty($management_info) || empty($management_info['user_id']) ) {
                return $this->error("管理机构信息有误");
            }
        }

        $consult_fee_user_id = $advisory_info['user_id']; // 咨询机构账户
        $guarantee_fee_user_id = $agency_info['user_id'];
        $pay_fee_user_id = $pay_info['user_id'];

        if (empty($consult_fee_user_id) || empty($guarantee_fee_user_id) || empty($pay_fee_user_id)) {
            return $this->error("咨询担保,支付机构信息有误");
        }

        //查询打款状态
        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($deal_data['id']);
        $is_has_loans = $dealInfo['is_has_loans'];
        if($is_has_loans != 0) {
            return $this->error("打款状态有误");
        }


        //放款添加到jobs
        if(!$dealService->isP2pPath(intval($deal_data['id']))) {
            $function = '\core\service\DealService::makeDealLoansJob';
            $param = array('deal_id' => $deal_data['id'], 'admin' => \es_session::get(md5(conf("AUTH_KEY"))));
        }else{
            $orderId = Idworker::instance()->getId();
            $function = '\core\service\P2pDealGrantService::dealGrantRequest';
            $param = array(
                'orderId' => $orderId,
                'dealId'=>$deal_data['id'],
                'param' => array('deal_id' => $deal_data['id'], 'admin' => \es_session::get(md5(conf("AUTH_KEY")))),
            );
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$deal_data['id']);
        }


        $GLOBALS['db']->startTrans();
        try {
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = 99;
            //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
            $add_job = $job_model->addJob($function, $param, get_gmtime() + 180);
            if (!$add_job) {
                throw new \Exception("放款任务添加失败");
            }
            //更新标放款状态
            $deal_model = new DealModel();
            $save_status = $deal_model->changeLoansStatus($deal_data['id'], 2);
            if (!$save_status) {
                throw new \Exception("更新标放款状态 is_has_loans 失败");
            }
            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
    }

    /**
     * 发送邮件短信及站内信
     * @todo update方法中的所有通知逻辑都在这里处理
     * @param int $deal_id 借款申请id
     * @return int 成功发送的邮件短信数量
     */
    private function _publishMsg($deal_id){

        //借款信息
        $deal_info = M("Deal")->where(array("id"=>$deal_id))->find();
        $deal_url = get_deal_domain($deal_id).'/d/'.Aes::encryptForDeal($deal_id);
        //url("index","deal",array("id"=>$deal_id));
        $site_url = get_deal_domain($deal_id).APP_ROOT;
        $help_url = get_deal_domain($deal_id).url("index","helpcenter");

        $deal_user = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$deal_info['user_id']);


        //借款类型
        $deal_name = get_deal_title($deal_info['name'], '', $deal_id);

        //已同意的借款保证人
        $guarantor_list = M("DealGuarantor")->where(array("deal_id"=>$deal_id,'status'=>2))->findAll();

        //给借款人站内信
        $content = "<p>您的借款申请“<a href=\"".$deal_url."\">".$deal_name."</a>”，已经发布";
        $this->_send_user_msg("",$content,0,$deal_info['user_id'],get_gmtime(),0,true,1);

        $Msgcenter = new Msgcenter();
        //借款人邮件
        $notice_mail = array(
            'deal_user_name' => '您',
            'deal_name' => $deal_name,
            'deal_url' => $deal_url,
            'site_url' => $site_url,
            'help_url' => $help_url,
            'site_name'=> app_conf("SHOP_TITLE"),
        );
        $mail_title = '您的借款申请“'.$deal_name.'“已经发布';
        $Msgcenter->setMsg($deal_user['email'], $deal_info['user_id'], $notice_mail, 'TPL_DEAL_PUBLISH_EMAIL',$mail_title,'',get_deal_domain_title($deal_id));

        //借款人短信
        $notice_sms = array(
            'deal_user_name' => $deal_user['user_name'],
            'deal_name' => $deal_name,
        );
        \libs\sms\SmsServer::instance()->send($deal_user['mobile'], 'TPL_DEAL_PUBLISH_SMS_NEW', $notice_sms, $deal_info['user_id'], get_deal_siteid($deal_id));

        //已同意的借款保证人
        $guarantor_list = M("DealGuarantor")->where(array("deal_id"=>$deal_id,'status'=>2))->findAll();
        $deal_user_name = M("User")->where(array('id'=>$deal_info['user_id']))->getField("real_name");
        foreach($guarantor_list as $k=>$v){
            $guarantor_info = M("User")->where(array('id'=>$deal_info['user_id']))->find();

            //邮件
            $notice_mail = array(
                'deal_user_name' => $deal_user_name,
                'deal_name' => $deal_name,
                'deal_url' => $deal_url,
                'site_url' => $site_url,
                'help_url' => $help_url,
                'site_name'=> app_conf("SHOP_TITLE"),
            );
            $mail_title = $deal_user_name.'的借款申请“'.$deal_name.'“已经发布';
            $Msgcenter->setMsg($v['email'], $v['to_user_id'], $notice_mail, 'TPL_DEAL_PUBLISH_EMAIL',$mail_title,'',get_deal_domain_title($deal_id));

            //短信
            $notice_sms = array(
                'deal_user_name' => $deal_user['user_name'],
                'deal_name' => $deal_name,
            );
            \libs\sms\SmsServer::instance()->send($v['mobile'], 'TPL_DEAL_PUBLISH_SMS_NEW', $notice_sms, $v['to_user_id'], get_deal_siteid($deal_id));

            //站内信
            $content = "<p>您担保的借款申请“<a href=\"".$deal_url."\">".$deal_name."</a>”，已经发布";
            $this->_send_user_msg("",$content,0,$v['to_user_id'],get_gmtime(),0,true,1);//给自己

        }
        $r = $Msgcenter->save();
        return $r;
    }

    //会员信息发送
    /**
     *
     * @param $title 标题
     * @param $content 内容
     * @param $from_user_id 发件人
     * @param $to_user_id 收件人
     * @param $create_time 时间
     * @param $sys_msg_id 系统消息ID
     * @param $only_send true为只发送，生成发件数据，不生成收件数据
     * @param $fav_id 相关ID
     */
    private function _send_user_msg($title,$content,$from_user_id,$to_user_id,$create_time,$sys_msg_id=0,$only_send=false,$is_notice = false,$fav_id = 0)
    {
        $group_arr = array($from_user_id,$to_user_id);
        sort($group_arr);
        if($sys_msg_id>0){
            $group_arr[] = $sys_msg_id;
        }
        if($is_notice > 0){
            $group_arr[] = $is_notice;
        }
        $msg = array();
        $msg['title'] = $title;
        $msg['content'] = addslashes($content);
        $msg['from_user_id'] = $from_user_id;
        $msg['to_user_id'] = $to_user_id;
        $msg['create_time'] = $create_time;
        $msg['system_msg_id'] = $sys_msg_id;
        $msg['type'] = !$only_send ? 1 : 0;  //这需要仔细检查，看我是否有修改错误。
        $msg['group_key'] = implode("_",$group_arr);
        $msg['is_notice'] = intval($is_notice);
        $msg['fav_id'] = intval($fav_id);

        $msgBoxService = new MsgBoxService();
        $msgBoxService->create($msg['to_user_id'], $msg['is_notice'], $msg['title'], $msg['content']);
        /*
        $GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg);
        $id = $GLOBALS['db']->insert_id();
        if($is_notice)
            $GLOBALS['db']->query("update ".DB_PREFIX."msg_box set group_key = '".$msg['group_key']."_".$id."' where id = ".$id);
        if(!$only_send) //这段逻辑直接合到上面了。
        {
            $msg['type'] = 1; //记录发件
            $GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg);
        }
        */

    }
    /**
     * 删除借款
     * @actionLock
     * lockauthor qicheng
     */
    public function delete() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $deny = '';
        if (isset ( $id )) {
            $deal_service = new DealService();
            $ids = explode(',', $id);
            $rs_arr = $deal_service->compareDeleteByIds($ids);
            if (count($rs_arr['allow'])) {    // 有允许删除的id 才进行删除操作
                $condition = array ('id' => array ('in',  $rs_arr['allow'] ) );
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();

                $condition = "";

                $condition = $this->format_id($rel_data);
                $rel_data = array();

                $rel_data = M(MODULE_NAME)->where($condition)->findAll();

                $dealProService = new DealProjectService();
                $dealModel = new DealModel();

                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];
                    rm_auto_cache("cache_deal_cart",array("id"=>$data['id']));

                    //专享项目如果删除项目中唯一标的,则同时更新项目状态为待上线
                    if($dealProService->isProjectEntrustZX($data['project_id'])){
                        $dealIds = $dealModel->findAllBySql("SELECT id FROM firstp2p_deal WHERE project_id = ".$data['project_id']." AND deal_status > 0",true);
                        if(count($dealIds) == 1){
                            foreach($dealIds as $dealId){
                                if($dealId['id'] == $data['id']){
                                    $changeProStatus = DealProjectModel::instance()->changeProjectStatus($data['project_id'],DealProjectModel::$PROJECT_BUSINESS_STATUS['waitting']);
                                    if(!$changeProStatus){
                                        $this->error('受托专享项目状态变更失败！');
                                    }
                                }
                            }
                        }
                    }
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 1 );
                if ($list!==false) {
                    foreach($rel_data as $one){
                        if($one['project_id'] > 0){
                            $dealProService->updateProBorrowed($one['project_id']);
                            $dealProService->updateProLoaned($one['project_id']);
                        }


                    }

                    if (count($rs_arr['deny']) > 0) {
                        $deny = implode(',', $rs_arr['deny']).' 不能删除';
                        $this->error (l("DELETE_FAILED").$deny,0);
                    }else {
                    save_log($info.l("DELETE_SUCCESS"),1);
                    $this->success (l("DELETE_SUCCESS"),$ajax);
                    }
                } else {
                    save_log($info.l("DELETE_FAILED"),0);
                    $this->error (l("DELETE_FAILED"),$ajax);
                }
            }else {
                if (count($rs_arr['deny']) > 0) {
                    $deny = implode(',', $rs_arr['deny']).' 不能删除';
                }
                save_log($id.'_'.l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED").$deny,$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 导出订单 csv
     *
     * @Title: export
     * @Description: todo(这里用一句话描述这个方法的作用)
     * @param
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    public function export_csv($page = 1)
    {
        set_time_limit(0);
        $limit = (($page - 1)*intval(app_conf("BATCH_PAGE_SIZE"))).",".(intval(app_conf("BATCH_PAGE_SIZE")));
        $where = " 1=1 ";

        if($_REQUEST['id']){
            $where .= " AND id in(". $_REQUEST['id'] . ")";
        }

        if(intval($_REQUEST['site_id']) > 0){
            $sql  ="select deal_id from ".DB_PREFIX."deal_site where site_id =".intval($_REQUEST['site_id']);

            $id_res = $GLOBALS['db']->get_slave()->getAll($sql);
            $id_arr = array();
            $ids = '';
            foreach($id_res as $dealid){
                $id_arr[] = $dealid['deal_id'];
            }
            if($id_arr){
                $ids = implode(',', $id_arr);
            }
            $where .= " AND id in(". $ids . ")";
        }

        if ($_REQUEST['name']) {
            $name = addslashes($_REQUEST['name']);
            $where .= " AND name LIKE '%{$name}%'";
        }

        if(trim($_REQUEST['real_name'])!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%" . $real_name ."%'";

            $ids = $GLOBALS['db']->getOne($sql);
            $where .= " AND user_id in(". $ids . ")";
        }

        if(isset($_REQUEST['deal_status']) && trim($_REQUEST['deal_status']) != '' && trim($_REQUEST['deal_status']) != 'all'){
            $deal_status = intval($_REQUEST['deal_status']);
            $where .= " AND deal_status = $deal_status";
        }

        $deal_type = 0;
        if ($_REQUEST['deal_type']) {
            $deal_type = intval($_REQUEST['deal_type']);
        }

        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $where .= " AND `project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` LIKE '%" . trim($_REQUEST['project_name']) . "%')";
        }


        if(trim($_REQUEST['report_status']) != ''){
            $where .= sprintf(' AND `report_status` = %d ', $_REQUEST['report_status']);
        }

        $where .= ' AND is_delete =0 AND `deal_type` = '.$deal_type.' AND publish_wait = 0';
        $list = MI("Deal")
            ->where($where)
            //->field(DB_PREFIX.'deal_order.*')
            ->order('id desc')
            ->limit($limit)->findAll ( );

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportDeal',
                'analyze' => M("Deal")->getLastSql()
                )
        );

        if($list)
        {
            register_shutdown_function(array(&$this, 'export_csv'), $page+1);

            $order_value = array(
                'id'=>'""',
                'deal_name'=>'""',
                'project_name'=>'""',
                'jys_record_number'=>'""',
                'deal_type_name'=>'""',
                'borrow_real_name' =>'""',
                'borrow_compony_name' =>'""',
                'borrow_user_name'=>'""',
                'borrow_user_id'=>'""',
                'borrow_guarantor_id'=>'""',
                'agency_id'=>'""',
                'advisory_id'=>'""',
                'warrant'=>'""',
                'loantype'=>'""',
                'borrow_amount'=>'""',
                'min_loan_money'=>'""',
                'repay_time'=>'""',
                'rate'=>'""',
                'enddate'=>'""',
                'loan_fee_rate'=>'""',
                'consult_fee_rate'=>'""',
                'guarantee_fee_rate'=>'""',
                'pay_fee_rate'=>'""',
                'manage_fee_rate'=>'""',
                'fee_total'=>'""',
                'clearing_type'=>'""',
                'description'=>'""',
                'deal_status'=>'""',
                'start_time'=>'""',
                'create_time'=>'""',
                'is_effect'=>'""',
                'user_type_name' => '""',
            );
            if($page == 1)
            {
                if ($this->is_cn) {
                    $content = iconv("utf-8","gbk","编号,借款标题,借款用途,借款人姓名,借款人,借款人id,借款保证人,担保机构,担保范围,还款方式,借款金额,最低金额,借款期限,年利率,筹标期限,借款手续费,借款咨询费,借款担保费,支付服务费,出借人平台管理费,借款描述,借款状态,开始时间,创建时间,状态,用户类型");
                    $content = $content . "\n";
                } else {
                    $content = iconv("utf-8","gbk","编号,借款标题,项目名称,交易所备案产品编号,借款用途,借款人姓名,借款企业,借款人,借款人id,借款保证人,担保机构,咨询方,担保范围,还款方式,借款金额,最低投资金额,借款期限,年利率,筹标期限,借款手续费,借款咨询费,借款担保费,支付服务费,出借人平台管理费,应收各项费用合计,结算方式,借款描述,借款状态,开始时间,创建时间,状态,用户类型");
                    $content = $content . "\n";

                }
            }

            foreach($list as $k=>$v)
            {
                $project_info = DealProjectModel::instance()->findViaSlave($v['project_id']);
                $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
                $order_value['deal_name'] = '"' . iconv('utf-8','gbk',$v['name']) . '"';
                $order_value['project_name'] = '"' . iconv('utf-8','gbk',$project_info['name']) . '"';
                $order_value['jys_record_number'] = '"' . iconv('utf-8','gbk',$v['jys_record_number']) . '"';
                $deal_type_name = M("Deal_loan_type")->field("name")->where("id = '" .$v['type_id']. "'")->find();
                $order_value['deal_type_name'] = '"' . iconv('utf-8','gbk',$deal_type_name['name']) . '"';
                $borrow_user_info = M("User")->where("id = '". $v['user_id'] ."'")->find();
                $order_value['borrow_real_name'] = '"' . iconv('utf-8','gbk',$borrow_user_info['real_name']) . '"';

                $borrowUserService = new UserService($v['user_id']);
                if($borrowUserService->isEnterprise()){
                    $enterprise_info = $borrowUserService->getEnterpriseInfo(true);
                    $borrowName = $enterprise_info['company_name'];
                }else{
                    $company_info = UserCompanyModel::instance()->findByUserId($v['user_id']);
                    $borrowName = $company_info['name'];
                }

                $order_value['borrow_compony_name'] = '"' . iconv('utf-8','gbk',$borrowName) . '"';
                $order_value['borrow_user_name'] = '"' . iconv('utf-8','gbk',$borrow_user_info['user_name']) . '"';
                $order_value['borrow_user_id'] = '"' . iconv('utf-8','gbk',$v['user_id']) . '"';
                $deal_guarantor = M("Deal_guarantor")->where("deal_id = '" .$v['id']. "'")->select();
                $deal_guarantor_id = "";
                if($deal_guarantor){
                    foreach ($deal_guarantor as $val){
                        if($val['to_user_id']){
                            $guarantor_name = M("User")->where("id = '". $val['to_user_id'] ."'")->find();
                            $deal_guarantor_id .= $guarantor_name['real_name'].',';
                        }
                    }
                }
                $order_value['borrow_guarantor_id'] = '"' . iconv('utf-8','gbk',trim($deal_guarantor_id,',')) . '"';
                $agency_id = M("Deal_agency")->where("id = '" .$v['agency_id']. "'")->find();
                $order_value['agency_id'] = '"' . iconv('utf-8','gbk',$agency_id['name']) . '"';

                $advisory_info = M("Deal_agency")->where("id = '" .$v['advisory_id']. "'")->find();
                $order_value['advisory_id'] = '"' . iconv('utf-8','gbk',$advisory_info['name']) . '"';

                switch ($v['warrant']){
                case 0:
                    $deal_warrant = '无';
                    break;
                case 1:
                    $deal_warrant = '本金';
                    break;
                case 2:
                    $deal_warrant = '本金及利息';
                    break;
                case 3:
                    $deal_warrant = '有';
                    break;
                case 4:
                    $deal_warrant = '第三方资产收购';
                    break;
                default:
                    $deal_warrant = '无';
                }
                $order_value['warrant'] = '"' . iconv('utf-8','gbk',$deal_warrant) . '"';
                $deal_loantype = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'][$v['loantype']] : $GLOBALS['dict']['LOAN_TYPE'][$v['loantype']];
                $order_value['loantype'] = '"' . iconv('utf-8','gbk',$deal_loantype) . '"';
                $order_value['borrow_amount'] = '"' . iconv('utf-8','gbk',number_format($v['borrow_amount'],2)) . '"';
                $order_value['min_loan_money'] = '"' . iconv('utf-8','gbk',number_format($v['min_loan_money'],2)) . '"';
                if ($v['loantype'] == 5) {
                    $deal_repay_time = $v['repay_time'] . "天";
                } else {
                    $deal_repay_time = $v['repay_time'] . "个月";
                }
                $order_value['repay_time'] = '"' . iconv('utf-8','gbk',$deal_repay_time) . '"';
                $order_value['rate'] = '"' . iconv('utf-8','gbk',$v['rate'].'%') . '"';
                $order_value['enddate'] = '"' . iconv('utf-8','gbk',$v['enddate'].'天') . '"';
                $order_value['loan_fee_rate'] = '"' . iconv('utf-8','gbk',floatval($v['loan_fee_rate']).'%') . '"';
                $order_value['consult_fee_rate'] = '"' . iconv('utf-8','gbk',floatval($v['consult_fee_rate']).'%') . '"';
                $order_value['guarantee_fee_rate'] = '"' . iconv('utf-8','gbk',floatval($v['guarantee_fee_rate']).'%') . '"';
                $order_value['pay_fee_rate'] = '"' . iconv('utf-8','gbk',floatval($v['pay_fee_rate']).'%') . '"';
                $order_value['manage_fee_rate'] = '"' . iconv('utf-8','gbk',floatval($v['manage_fee_rate']).'%') . '"';

                $fee_total = DealModel::instance()->getAllFee($v['id']);
                $order_value['fee_total'] = '"' . iconv('utf-8','gbk',number_format(array_sum($fee_total),2)) . '"';

                $clearing_type = '';
                switch ($project_info['clearing_type']) {
                    case 1:
                        $clearing_type = '场内';
                        break;
                    case 2:
                        $clearing_type = '场外';
                        break;
                }

                $order_value['clearing_type'] = '"' . iconv('utf-8','gbk',$clearing_type) . '"';
                $order_value['description'] = '"' . iconv('utf-8','gbk',$v['description']) . '"';
                switch ($v['deal_status']){
                case 0:
                    $deal_status = '等待确认';
                    break;
                case 1:
                    $deal_status = '进行中';
                    break;
                case 2:
                    $deal_status = '满标';
                    break;
                case 3:
                    $deal_status = '流标';
                    break;
                case 4:
                    $deal_status = '还款中';
                    break;
                case 5:
                    $deal_status = '已还清';
                    break;
                default:
                    $deal_status = '等待确认';
                }
                $order_value['deal_status'] = '"' . iconv('utf-8','gbk',$deal_status) . '"';
                $deal_start_time = empty($v['start_time']) ? '' : date("Y-m-d H:i:s",$v['start_time']);
                $order_value['start_time'] = '"' . iconv('utf-8','gbk',$deal_start_time) . '"';
                $order_value['create_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['create_time'])) . '"';
                $is_effect = empty($v['is_effect']) ? '无效' : '有效';
                $order_value['is_effect'] = '"' . iconv('utf-8','gbk',$is_effect) . '"';
                // JIRA#FIRSTPTOP-3260 企业账户二期功能 fanjingwen@
                $order_value['user_type_name'] = '"' . iconv('utf-8','gbk', getUserTypeName($v['user_id'])) . '"';
                $content .= implode(",", $order_value) . "\n";
            }

            $datatime = date("YmdHis",get_gmtime());
            header("Content-Disposition: attachment; filename={$datatime}_deal_list.csv");
            echo $content;
        }
        else
        {
            if($page==1)
                $this->error(L("NO_RESULT"));
        }

    }
    /**
     * ID处理
     *
     * @Title: format_id
     * @Description: 删除、恢复、彻底删除时对ID的处理。
     * @param @param unknown_type $id_arr
     * @return return_type
     * @author Liwei
     * @throws
     *
     */

    private function format_id($id_arr, $is_str = FALSE){

        if(empty($id_arr)) return false;

        foreach ($id_arr as $id){
            $id_str .= $id['id'].',';
        }

        $strs = array_unique(explode(',', trim($id_str,',')));
        $strs = implode($strs, ',');
        $strs = trim($strs,',');

        if($is_str){
            return $strs;
        }

        $condition = array ('id' => array ('in', $strs ) );

        return $condition;
    }
    //恢复
    public function restore() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();

            $condition = "";

            $condition = $this->format_id($rel_data);

            $rel_data = array();

            $rel_data = M(MODULE_NAME)->where($condition)->findAll();

            foreach($rel_data as $data)
            {
                $info[] = $data['name'];
                rm_auto_cache("cache_deal_cart",array("id"=>$data['id']));
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 0 );
            if ($list!==false) {
                $deal_pro_service = new DealProjectService();
                foreach($rel_data as $one){
                    if($one['project_id'] > 0){
                        $deal_pro_service->updateProBorrowed($one['project_id']);
                        $deal_pro_service->updateProLoaned($one['project_id']);
                    }
                }
                save_log($info.l("RESTORE_SUCCESS"),1);
                $this->success (l("RESTORE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("RESTORE_FAILED"),0);
                $this->error (l("RESTORE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $deal_service = new DealService();
            $ids = explode(',', $id);
            $rs_arr = $deal_service->compareDeleteByIds($ids);
            if (count($rs_arr['allow'])) {    // 有允许删除的id 才进行删除操作
                $condition = array ('id' => array ('in',  $rs_arr['allow'] ) );

            //$condition = array ('id' => array ('in', explode ( ',', $id ) ) );

                $rel_data = M(MODULE_NAME)->where($condition)->findAll();

                $id_str = $this->format_id($rel_data, TRUE);

                if(empty($id_str)){
                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }

                //删除的验证
                if(M("DealOrder")->where(array ('deal_id' => array ('in', $id_str ) ))->count()>0)
                {
                    $this->error(l("DEAL_ORDER_NOT_EMPTY"),$ajax);
                }
                M("DealPayment")->where(array ('deal_id' => array ('in', $id_str ) ))->delete();
                M("DealLoad")->where(array ('deal_id' => array ('in', $id_str ) ))->delete();
                M("DealLoanRepay")->where(array ('deal_id' => array ('in', $id_str ) ))->delete();
                M("DealRepay")->where(array ('deal_id' => array ('in', $id_str ) ))->delete();
                M("DealCollect")->where(array ('deal_id' => array ('in', $id_str ) ))->delete();
                M("DealInrepayRepay")->where(array ('deal_id' => array ('in', $id_str ) ))->delete();

                $rel_data = array();
                $condition = array();

                $condition = array ('id' => array ('in', $id_str ) );

                $rel_data = M(MODULE_NAME)->where($condition)->findAll();

                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];
                    rm_auto_cache("cache_deal_cart",array("id"=>$data['id']));
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->delete();

                if ($list!==false) {
                    $deny = '';
                    if (count($rs_arr['deny']) > 0) {
                        $deny = implode(',', $rs_arr['deny']).' 不能删除';
                        $this->success (l("FOREVER_DELETE_SUCCESS").$deny,$ajax);
                    }else {
                        save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                        $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                    }
                } else {
                    save_log($info.l("FOREVER_DELETE_FAILED"),0);
                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }
            } else{
                    if (count($rs_arr['deny']) > 0) {
                        $deny = implode(',', $rs_arr['deny']).' 不能删除';
                        $this->success (l("FOREVER_DELETE_SUCCESS").$deny,$ajax);
                    }else {
                        save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                        $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                    }
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function set_sort()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        $log_info = M(MODULE_NAME)->where("id=".$id)->getField('name');
        if(!check_sort($sort))
        {
            $this->error(l("SORT_FAILED"),1);
        }
        M(MODULE_NAME)->where("id=".$id)->setField("sort",$sort);
        rm_auto_cache("cache_deal_cart",array("id"=>$id));
        save_log($log_info.l("SORT_SUCCESS"),1);
        $this->success(l("SORT_SUCCESS"),1);
    }
    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M(MODULE_NAME)->where("id=".$id)->getField("name");
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        M(MODULE_NAME)->where("id=".$id)->setField("update_time",get_gmtime());
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);

        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)    ;
    }
    public function show_detail()
    {
        $id = intval($_REQUEST['id']);
        $deal_info = M("Deal")->getById($id);
        $this->assign("deal_info",$deal_info);

        //联查渠道推广信息
        //        $sql = "SELECT d.*, c.id AS channel_id, c.channel_value, c.name AS channel_name, l.is_delete AS log_is_delete";
        //        $sql .= " FROM " . DB_PREFIX . "deal_load d LEFT JOIN " . DB_PREFIX . "deal_channel_log l ON d.id=l.deal_load_id";
        //        $sql .= " LEFT JOIN " . DB_PREFIX . "deal_channel c ON l.channel_id=c.id";
        //        $sql .= " WHERE d.deal_id=$id ORDER BY d.id";
        //        $loan_list = D("DealLoad")->query($sql);

        $loan_list = D("DealLoad")->where('deal_id=' . $id)->order("id ASC")->findall();

        if ($deal_info['deal_type'] == 1) {
            $apply_model = new \core\dao\CompoundRedemptionApplyModel;
            $apply_list = $apply_model->getApplyByDeal($id);
        }

        foreach ($loan_list as $k => $load_item) {
            $sql = "SELECT c.id AS channel_id, c.channel_value, c.name AS channel_name, l.is_delete AS log_is_delete";
            $sql .= " FROM " . DB_PREFIX . "deal_channel_log l LEFT JOIN " . DB_PREFIX . "deal_channel c ON l.channel_id=c.id";
            $sql .= " WHERE l.deal_load_id=" . $load_item['id'] . " AND l.is_delete=0 limit 1";
            $channel_list = D("DealLoad")->query($sql);
            if (!empty($channel_list)) {
                $channel = $channel_list[0];
                $loan_list[$k]['opt_add_channel'] = '<a href="/m.php?m=DealChannel&a=index&id=' . $channel['channel_id'] . '" target="_blank">' . $channel['channel_name'] . "</a>";
            } else {
                // 已经使用优惠券，则不能使用邀请码
                $coupon_log_dao = new core\dao\CouponLogModel();
                $coupons = $coupon_log_dao->findByDealLoadId($load_item['id']);
                if (empty($coupons)) {
                    $loan_list[$k]['opt_add_channel'] = '<a href="javascript:weebox_add_channel(' . $load_item['id'] . ')">' . "添加推广记录</a>";
                } else {
                    $loan_list[$k]['opt_add_channel'] = '';
                }
            }

            if ($deal_info['deal_type'] == 1) {
                if (isset($apply_list[$load_item['id']]) && $apply_list[$load_item['id']]) {
                    $loan_list[$k]['redemption_time'] = to_date($apply_list[$load_item['id']]['create_time'], "Y-m-d H:i");
                } else {
                    $loan_list[$k]['redemption_time'] = "未赎回";
                }
            }
        }

        $this->assign("loan_list",$loan_list);
        $template = $this->is_cn ?  'show_detail_cn' : 'show_detail';
        $this->display($template);
    }
    public function filter_html()
    {
        $shop_cate_id = intval($_REQUEST['shop_cate_id']);
        $deal_id = intval($_REQUEST['deal_id']);
        $ids = $this->get_parent_ids($shop_cate_id);
        $filter_group = M("FilterGroup")->where(array("cate_id"=>array("in",$ids)))->findAll();
        foreach($filter_group as $k=>$v)
        {
            $filter_group[$k]['value'] = M("DealFilter")->where("filter_group_id = ".$v['id']." and deal_id = ".$deal_id)->getField("filter");
        }
        $this->assign("filter_group",$filter_group);
        $this->display();
    }
    //获取当前分类的所有父分类包含本分类的ID
    private $cate_ids = array();
    private function get_parent_ids($shop_cate_id)
    {
        $pid = $shop_cate_id;
        do{
            $pid = M("ShopCate")->where("id=".$pid)->getField("pid");
            if($pid>0)
                $this->cate_ids[] = $pid;
        }while($pid!=0);
        $this->cate_ids[] = $shop_cate_id;
        return $this->cate_ids;
    }
    public function publish($deal_type = 0)
    {
        $map['publish_wait'] = 1;
        $map['is_delete'] = 0;
        if($deal_type == 1) {
            $map['deal_type'] = $deal_type;
        }

        $template = $this->is_cn ? 'publish_cn' :'publish';
        if ($deal_type == 1) {
            $template = 'publish_lgl';
        }
        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        if (!empty($_REQUEST['project_name'])) {
            $map['_string'] = " `project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` = '" . trim($_REQUEST['project_name']) . "')";
        }
        if ($this->is_cn) {
            $map['deal_type'] = 0;//只显示网贷标
        }
        $name=$this->getActionName();
        $model = DI ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        if ($map['deal_type'] == 1) {
            $list = $this->get('list');
            $compound_service = new DealCompoundService();
            $userIDArr = array();
            foreach($list as $key => $row) {
                $deal_ids[] = $deal_id = $row['id'];

                $list_name = get_user_name($row['user_id']);
                $row['list_name'] = $row['user_id']." | $list_name | ".get_user_name($row['user_id'], 'real_name');
                $row['rate_day'] = $compound_service->getDayRateByDealId($row['id']);
                $deal_list[$deal_id] = $row;
                $userIDArr[] = $row['user_id'];
            }
            $m_dc = DI('DealCompound');
            $dc_map['deal_id'] = array('IN',implode(',' , $deal_ids));
            $compound_rs = $m_dc->where($dc_map)->select();
            foreach($compound_rs as $row) {
                $deal_id = $row['deal_id'];
                $deal_list[$deal_id]['lock_period'] = $row['lock_period'];
                $deal_list[$deal_id]['redemption_period'] = $row['redemption_period'];
            }
            $this->assign('list', $deal_list);

            // JIRA#3260 企业账户二期 <fanjingwen@>
            // 获取借款人相关的基本信息
            $userServ = new UserService();
            $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
            $this->assign('listOfBorrower', $listOfBorrower);
            // -------------- over -----------------
        }
        $this->display($template);
        return;
    }
    /**
     * 通知贷未审核列表
     */
    public function compound_publish(){
        $this->publish(1);
    }
    /**
     * 批量上传 借款申请.csv
     *
     * @author wenyanlei 2013-7-30
     * @param $_FILES['upfile']
     * @return NULL
     */
    public function douploadcsv() {
        if ($_FILES ['upfile'] ['error'] == 4) {
            $this->error ( "请选择文件！" );
            exit;
        }
        // 判断文件格式
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            $this->error ( "请上传csv格式的文件！" );
            exit;
        }

        FP::import("app.deal");
        FP::import("libs.id5.SynPlat");

        $row = 1;
        if (($handle = fopen ( $_FILES ['upfile'] ['tmp_name'], "r" )) !== FALSE) {
            $deal_array = array ();
            while ( ($deal = fgetcsv ( $handle )) !== FALSE ) {
                // 去掉第一行标题
                if ($row > 1) {
                    $deal_array [] = $this->_make_upload_deal ( $deal, $row );
                }
                $row ++;
            }
            fclose ( $handle );
            @unlink ( $_FILES ['upfile'] ['tmp_name'] );

            if ($deal_array) {
                $is_commit = true;
                $GLOBALS ['db']->startTrans ();
                foreach ( $deal_array as $dealinfo ) {
                    $ret = $this->_insert_upload_deal ( $dealinfo );
                    if ($ret === false) {
                        $is_commit = false;
                        $GLOBALS ['db']->rollback ();
                        break;
                    }
                }
                if ($is_commit) {
                    $GLOBALS ['db']->commit ();
                    $this->success ( "导入成功！" );
                    exit;
                } else {
                    $GLOBALS ['db']->rollback ();
                    $this->error ( "导入失败！" );
                    exit;
                }
            }
        }
        $this->error ( "出现错误，请检查文件和内容格式！" );
    }

    /**
     * 导入单条处理好的 借款申请信息
     *
     * @author wenyanlei 2013-7-31
     * @param $deal 所有的借款申请信息
     * @return bool
     */
    private function _insert_upload_deal($data) {
        $deal = $data ['deal'];
        $bankcard = $data ['bankcard'];
        //$msg_data = $data ['msg_data'];
        $regist_info = isset ( $data ['regist_info'] ) ? $data ['regist_info'] : array ();

        if ($deal ['user_id'] == 0 && ! empty ( $regist_info )) {
            // 把oauth的数据同步到firstp2p用户表
            $user_id = register_user_in_oauth ( $regist_info );
            if ($user_id === false)
                return false;

            $deal ['user_id'] = $user_id;
            $bankcard ['user_id'] = $user_id;
            $bankcard ['card_name'] = empty ( $regist_info ['user_nickname'] ) ? $bankcard ['card_name'] : $regist_info ['user_nickname'];
            //$msg_data ['real_name'] = empty ( $regist_info ['user_nickname'] ) ? $msg_data ['real_name'] : $regist_info ['user_nickname'];
        }

        // 插入借款申请
        $deal ['create_time'] = get_gmtime ();
        //$deal ['advisor_fee_rate'] = app_conf("DEFAULT_ADVISOR_FEE_RATE");
        $GLOBALS ['db']->autoExecute ( DB_PREFIX . "deal", $deal, "INSERT" );
        $deal_id = $GLOBALS ['db']->insert_id ();

        // 插入借款保证人
        /* if ($data ['guarantor']) {
            $_REQUEST ['user_id'] = $deal ['user_id'];
            upload_set_guarantor ( $deal_id, $deal ['user_id'], $data ['guarantor'], true );
        } */

        //add by zhangruoshi, about deal muti-site
        // 插入站点信息
        if(false !== $deal_id){
            $deal_site = array(app_conf('TEMPLATE_ID'));
            update_deal_site($deal_id, $deal_site);
        }
        //end add


        // 插入用户银行卡信息
        $bank_mode = 'INSERT';
        $bank_where = '';
        $bankcard['create_time'] = get_gmtime();
        $bank_id = $GLOBALS ['db']->getOne ( "SELECT id FROM " . DB_PREFIX . "user_bankcard WHERE user_id='" . $deal ['user_id'] . "'" );
        if ($bank_id) {
            $bank_mode = 'UPDATE';
            $bank_where = 'id=' . $bank_id;
            unset($bankcard['create_time']);
            $bankcard['update_time'] = get_gmtime();
        }
        $GLOBALS ['db']->autoExecute ( DB_PREFIX . "user_bankcard", $bankcard, $bank_mode, $bank_where );

        // 给借款人发送各种消息
        //$msg_data ['deal_id'] = $deal_id;
        //$msg_data ['user_id'] = $deal ['user_id'];
        // $this->_msgfor_upload_deal($msg_data);

        return true;
    }

    /**
     * 处理批量导入的 借款申请数据
     *
     * @author wenyanlei 2013-7-31
     * @param $deal 单行excel数据
     * @param $row 第几行
     * @return array
     */
    private function _make_upload_deal($deal, $row) {
        $return = array ();
        foreach ( $deal as &$val ) {
            $val = trim ( htmlspecialchars ( iconv ( 'GB2312', 'UTF-8', $val ) ) );
        }

        if (count ( $deal ) != 27) {
            $this->error ( "第 {$row} 行，数据应该是27列，该行是" . count ( $deal ) . '列！' );
        }

        // ------------------ 0 ---------------------
        if (empty ( $deal [0] )) {
            $this->error ( "第 {$row} 行，‘借款人用户名’不能为空！" );
        }

        $userinfo = $GLOBALS ['db']->getRow ( "select id,real_name,email,mobile from " . DB_PREFIX . "user where user_name = '" . $deal [0] . "'" );

        if (empty ( $userinfo )) {
            $oauth_userinfo = get_userinfo_in_oauth ( $deal [0] ); // 获取oauth上 $deal[0]对应的用户信息
            if (is_array ( $oauth_userinfo ) && !empty ( $oauth_userinfo ['user_login_name'] ) && !empty ( $oauth_userinfo ['user_email'] ) && !empty ( $oauth_userinfo ['user_name'] )) {
                $return ['regist_info'] = $oauth_userinfo;
            } else {
                $this->error ( "第 {$row} 行，‘借款人用户名’信息错误，没有该用户，或用户手机号邮箱信息不全！" );
            }
        }

        // ------------------ 1 ---------------------
        if (empty ( $deal [1] )) {
            $this->error ( "第 {$row} 行，‘借款用途’信息有误！" );
        }
        $loan_type_id = $GLOBALS ['db']->getOne ( "select id from " . DB_PREFIX . "deal_loan_type where name = '" . $deal [1] . "'" );
        if ($loan_type_id == '') {
            $this->error ( "第 {$row} 行，‘借款用途’信息有误！" );
        }

        // ------------------ 2 ---------------------
        /* if (get_wordnum ( $deal [2] ) < 2 || get_wordnum ( $deal [2] ) > 30) {
            $this->error ( "第 {$row} 行，‘借款标题’信息有误！" );
        } */

        // ------------------ 3 ---------------------
        if (get_wordnum ( $deal [3] ) < 5 || get_wordnum ( $deal [3] ) > 1000) {
            $this->error ( "第 {$row} 行，‘借款描述’信息有误！" );
        }

        // ------------------ 4 ---------------------
        if (strpos ( $deal [4], '.' ) || $deal [4] < app_conf ( 'MIN_BORROW_QUOTA' ) || $deal [4] > app_conf ( 'MAX_BORROW_QUOTA' )) {
            $this->error ( "第 {$row} 行，‘借款金额’应为" . app_conf ( 'MIN_BORROW_QUOTA' ) . "至" . app_conf ( 'MAX_BORROW_QUOTA' ) . "的整数！" );
        }

        // ------------------ 5 ---------------------
        if ($deal[6] != '按天到期支付本金收益' && ! isset ( $GLOBALS ['dict'] ['REPAY_TIME'] [$deal [5]] ) || empty($deal [5])) {
            $this->error ( "第 {$row} 行，‘借款期限’信息有误！" );
        }

        // ------------------ 6 ---------------------
        $loan_type = array_flip ( $GLOBALS ['dict'] ['LOAN_TYPE'] );
        //if (! isset ( $GLOBALS ['dict'] ['REPAY_MODE'] [$loan_type [$deal [6]]] )) {
        if (! isset ( $loan_type [$deal [6]])) {
            $this->error ( "第 {$row} 行，‘还款方式’信息有误！" );
        }

        //--------------------- 7 ----------------------
        if(empty( $deal[7] ) || floatval($deal[7]) < 0 || floatval($deal[7]) > 100){
            $this->error ( "第 {$row} 行，‘借款年利率’信息有误！" );
        }

        // ------------------ 8 ---------------------
        if ($deal [8] < 1 || $deal [8] > 30) {
            $this->error ( "第 {$row} 行，‘筹标期限’信息有误！" );
        }

        // ------------------ 9,10,11,12 ---------------------
        if ($deal [9] < 0 || $deal [9] > 100) {
            $this->error ( "第 {$row} 行，‘借款服务费率’信息有误！" );
        }
        if ($deal [10] < 0 || $deal [10] > 100) {
            $this->error ( "第 {$row} 行，‘借款咨询费率’信息有误！" );
        }
        if ($deal [11] < 0 || $deal [11] > 100) {
            $this->error ( "第 {$row} 行，‘借款担保费率’信息有误！" );
        }
        if ($deal [12] < 0 || $deal [12] > 100) {
            $this->error ( "第 {$row} 行，‘出借人平台服务费率’信息有误！" );
        }

        //-------------------- 13 ------------------------
        if(!empty($deal[13])){
            $deal[13] = floatval($deal[13]);
            if($deal[13] < 0 || $deal[13] > 100){
                $this->error ( "第 {$row} 行，‘出借人收益率’信息有误！" );
            }
        }

        // ------------------ 14 ---------------------
        $tpl_exist = "SELECT * FROM " . DB_PREFIX . "msg_category WHERE is_delete = 0 and is_contract = 1 and type_tag ='" . $deal [14] . "'";
        $contract_type = $GLOBALS ['db']->getAll ( $tpl_exist );
        if (empty($contract_type)) {
            $this->error ( "第 {$row} 行，‘合同类型’信息有误！" );
        }

        // ------------------ 15 ---------------------
        if ($deal [15] < 0 || $deal [15] > 100) {
            $this->error ( "第 {$row} 行，‘期间顾问费率’信息有误！" );
        }

        // ------------------ 17 ---------------------
        if (empty ( $deal [17] )) {
            $this->error ( "第 {$row} 行，‘担保机构’信息有误！" );
        }
        $agency_id = $GLOBALS ['db']->getOne ( "SELECT id FROM " . DB_PREFIX . "deal_agency WHERE name='" . $deal [17] . "'" );
        if (! $agency_id) {
            $this->error ( "第 {$row} 行，‘担保机构’信息有误！" );
        }

        // ------------------ 18,19,20,21,22,23,24 ---------------------
        if ($deal [18] == '' || $deal [19] == '' || $deal [20] == '' || $deal [21] == '' || $deal [22] == '' || $deal [23] == '' || $deal [24] == '') {
            $this->error ( "第 {$row} 行，银行卡信息有误！" );
        }

        $bank_id = $GLOBALS ['db']->getOne ( "SELECT id FROM " . DB_PREFIX . "bank WHERE name='" . $deal [19] . "'" );
        if (empty ( $bank_id )) {
            $this->error ( "第 {$row} 行，‘借记卡银行’信息有误！" );
        }

        $region2 = $GLOBALS ['db']->getOne ( "select id from " . DB_PREFIX . "delivery_region where region_level = 2 and name = '" . $deal [20] . "'" ); // 省
        if ($region2 == '')
            $this->error ( "第 {$row} 行，‘开户行所在省’信息有误！" );

        $region3 = $GLOBALS ['db']->getOne ( "select id from " . DB_PREFIX . "delivery_region where region_level = 3 and name = '" . $deal [21] . "' and pid = $region2" ); // 市
        if ($region3 == '')
            $this->error ( "第 {$row} 行，‘开户行所在市’信息有误！" );

        $region4 = $GLOBALS ['db']->getOne ( "select id from " . DB_PREFIX . "delivery_region where region_level = 4 and name = '" . $deal [22] . "' and pid = $region3" ); // 县
        if ($region4 == '')
            $this->error ( "第 {$row} 行，‘开户行所在区’信息有误！" );

        // ------------------ 25,26 ---------------------
        /* if (! empty ( $deal [25] )) {

            $guarantor ['name'] = explode ( ',', $deal [25] );
            $guarantor_total = count ( $guarantor ['name'] );

            if ($guarantor_total == 0 || $guarantor_total > 10) {
                $this->error ( "第 {$row} 行，‘保证人’信息有误！最多只能有10个保证人！" );
            }

            if ($deal [26] == '') {
                $this->error ( "第 {$row} 行，‘借款保证人关系’信息有误！" );
            }

            $guarantor ['rel'] = explode ( ',', $deal [26] );

            if (count ( $guarantor ['rel'] ) != $guarantor_total) {
                $this->error ( "第 {$row} 行，‘保证人’信息有误！" );
            }

            $relation = array_flip ( $GLOBALS ['dict'] ['DICT_RELATIONSHIPS'] );

            for($i = 0; $i < $guarantor_total; $i ++) {
                // || trim ( $guarantor ['name'] [$i] ) == $deal [0]  edit by wenyanlei 20131112 保证人可以是自己
                if (trim ( $guarantor ['name'] [$i] ) == '') {
                    $this->error ( "第 {$row} 行，‘保证人用户名’信息有误！" );
                }

                if (trim ( $guarantor ['rel'] [$i] ) == '') {
                    $this->error ( "第 {$row} 行，‘保证人关系’信息有误！" );
                }

                if (! isset ( $relation [$guarantor ['rel'] [$i]] )) {
                    $this->error ( "第 {$row} 行，‘保证人关系’信息有误！" );
                }
                $guarantor ['relation'] [$i] = $relation [$guarantor ['rel'] [$i]];

                $guserinfo = $GLOBALS ['db']->getRow ( "select id,real_name,email,mobile from " . DB_PREFIX . "user where user_name = '" . $guarantor ['name'] [$i] . "'" );

                if (empty ( $guserinfo )) {
                    $goauth_userinfo = get_userinfo_in_oauth ( $guarantor ['name'] [$i] );
                    if (is_array ( $goauth_userinfo ) && !empty ( $goauth_userinfo ['user_login_name'] ) && !empty ( $goauth_userinfo ['user_email'] ) && !empty ( $goauth_userinfo ['user_name'] )) {
                        $guarantor_userid = register_user_in_oauth ( $goauth_userinfo );
                        if ($guarantor_userid === false) {
                            $this->error ( "第 {$row} 行，保证人“" . $guarantor ['name'] [$i] . "”在firstp2p注册失败！" );
                        }
                    } else {
                        $this->error ( "第 {$row} 行，‘保证人用户名’信息错误，没有用户“" . $guarantor ['name'] [$i] . "”,或者手机号邮箱信息不全！" );
                    }
                }

                $guarantor ['name'] [$i] = empty ( $guserinfo ['real_name'] ) ? $goauth_userinfo ['user_nickname'] : $guserinfo ['real_name'];
                $guarantor ['email'] [$i] = empty ( $guserinfo ['email'] ) ? $goauth_userinfo ['user_email'] : $guserinfo ['email'];
                $guarantor ['mobile'] [$i] = empty ( $guserinfo ['mobile'] ) ? $goauth_userinfo ['user_name'] : $guserinfo ['mobile'];
                $guarantor ['to_user_id'] [$i] = empty ( $guserinfo ['id'] ) ? $guarantor_userid : $guserinfo ['id'];
            }
            unset ( $guarantor ['rel'] );
            $return ['guarantor'] = $guarantor;
        } */

        // ------------------ 25,26 ---------------------
        if (! empty ( $deal [25] ) && ! preg_match('/^1[3458]\d{9}$/', $deal [26]) ) {
            $this->error ( "第 {$row} 行，‘推荐人手机号’信息错误！" );
        }

        /* if(!empty($deal[7])){
            $res = $deal[7];
        }else{
            $repay_mode = $GLOBALS ['dict'] ['REPAY_MODE'] [$loan_type [$deal [6]]];
            $repay_period = $GLOBALS ['dict'] ['REPAY_PERIOD'] [$deal [5]];
            $res = $GLOBALS ['db']->getOne ( "SELECT " . $repay_period . " FROM " . DB_PREFIX . "deploy WHERE process='" . $repay_mode . "'" );
        } */

        $data ['is_delete'] = 0;
        $data ['publish_wait'] = 1;
        $data ['is_effect'] = 1; // 是否有效
        $data ['name'] = $deal [2]; // 借款标题
        $data ['type_id'] = $loan_type_id; // 借款用途
        $data ['borrow_amount'] = floatval ( $deal [4] ); // 借款金额
        $data ['repay_time'] = intval ( $deal [5] ); // 借款期限
        $data ['deal_status'] = 0; // 状态:等待确认
        $data ['cate_id'] = 3; // 机构担保标
        $data ['warrant'] = 2; // 担保范围 本金和利息
        $data ['agency_id'] = $agency_id; // 担保机构
        $data ['rate'] = floatval ( $deal[7] ); // 年化贷款利率
        $data ['enddate'] = intval ( $deal [8] ); // 筹标期限
        $data ['description'] = $deal [3]; // 借款描述
        $data ['voffice'] = 0; // 默认不向出借人出示“公司名称”信息
        $data ['manager'] = $deal [26]; // 推荐人姓名
        $data ['manager_mobile'] = empty ( $deal [26] ) ? '' : $deal [27]; // 推荐人手机号
        $data ['loantype'] = $loan_type [$deal [6]]; // 还款方式
        $data ['loan_fee_rate'] = floatval ( $deal [9] ); // 借款服务费率
        $data ['consult_fee_rate'] = floatval ( $deal [10] ); // 借款咨询费
        $data ['guarantee_fee_rate'] = floatval ( $deal [11] ); // 借款担保费率
        $data ['pay_fee_rate'] = floatval ( $deal [12] ); // 借款担保费率
        $data ['manage_fee_rate'] = floatval ( $deal [13] ); // 出借人平台服务费率
        $data ['income_fee_rate'] = empty($deal[14]) ? get_income_fee_rate($data ['rate'], $data ['manage_fee_rate'], $data ['repay_time']) : $deal[14];//出借人收益率
        $data ['contract_tpl_type'] = strtoupper( $deal [15] );//合同类型
        $data ['advisor_fee_rate'] = floatval ( $deal [16] );//顾问费率
        $data ['manage_fee_text'] = $deal [17];//年化出借人平台费率备注
        $data ['user_id'] = isset ( $userinfo ['id'] ) ? $userinfo ['id'] : 0; // 借款人用户id
        $data ['vposition'] = 1; // 职位
        $return ['deal'] = $data;

        // 银行卡信息
        $bankcard ['bankzone'] = ''; // 开户网点
        $bankcard ['region_lv1'] = 1; // 中国
        $bankcard ['region_lv2'] = $region2; // 省
        $bankcard ['region_lv3'] = $region3; // 市
        $bankcard ['region_lv4'] = $region4; // 县、区
        $bankcard ['bank_id'] = $bank_id; // 开户银行
        $bankcard ['bankzone'] = $deal [24]; //开户行网点
        $bankcard ['bankcard'] = $deal [25]; // number_format($deal[21],0,'','');//卡号
        $bankcard ['user_id'] = isset ( $userinfo ['id'] ) ? $userinfo ['id'] : 0; // 用户id
        $bankcard ['card_name'] = $deal[19]; // 开户姓名
        $bankcard ['status'] = 1;
        $return ['bankcard'] = $bankcard;

        // 发送消息需要用到的
        /* $msg_data ['user_name'] = $deal [0];
        $msg_data ['real_name'] = $bankcard ['card_name'];
        $msg_data ['loan_type_name'] = $deal [1];
        $msg_data ['mobile'] = empty ( $userinfo ['mobile'] ) ? $oauth_userinfo ['user_name'] : $userinfo ['mobile'];
        $msg_data ['email'] = empty ( $userinfo ['email'] ) ? $oauth_userinfo ['user_email'] : $userinfo ['email'];
        $return ['msg_data'] = $msg_data; */

        return $return;
    }

    /**
     * 批量导入借款，每导入一条，给用户发送消息
     *
     * @author wenyanlei 2013-8-5
     * @param $msg_arr 发送消息用到的数组配置
     * @return int
     */
    private function _msgfor_upload_deal($msg_arr) {
        $deal_url = get_deal_domain ($msg_arr['deal_id']) .'/d/'.Aes::encryptForDeal($msg_arr ['deal_id']);
        $deal_type = get_deal_title($msg_arr['loan_type_name'], '', $msg_arr['deal_id']);

        // 站内消息
        $content = "<p>您已经提交借款申请“<a href=\"" . $deal_url . "\">" . $deal_type . "</a>”，请等待审核";
        $this->_send_user_msg ( "", $content, 0, $msg_arr ['user_id'], get_gmtime (), 0, true, 1 );

        $Msgcenter = new Msgcenter ();

        // 邮件
        if ($msg_arr ['email']) {
            $msg_mail = array (
                'user_name' => $msg_arr ['user_name'],
                'deal_name' => $deal_type,
                'deal_url' => get_deal_domain ($msg_arr['deal_id']) . url ( "index", "deal", array (
                    "id" => $msg_arr ['deal_id']
                ) ),
                'guarantor_url' => get_deal_domain ($msg_arr['deal_id']) . url ( "index", "deal", array (
                    "id" => $msg_arr ['deal_id']
                ) ),
                'site_name' => app_conf ( "SHOP_TITLE" ),
                'site_url' => get_deal_domain ($msg_arr['deal_id']) . APP_ROOT,
                'help_url' => get_deal_domain ($msg_arr['deal_id']) . url ( "index", "helpcenter" )
            );

            $Msgcenter->setMsg ( $msg_arr ['email'], 0, $msg_mail, 'TPL_DEAL_SUBMIT_MAIL', "您的借款申请已经提交" ,'',get_deal_domain_title( $msg_arr ['deal_id']));
        }

        // 短信
        if ($msg_arr ['mobile']) {
            $msg_sms = array (
                'user_name' => $msg_arr ['user_name'],
                'deal_name' => $deal_type
            );
            \libs\sms\SmsServer::instance()->send($msg_arr['mobile'], 'TPL_DEAL_SUBMIT_SMS', $msg_sms, get_deal_siteid($msg_arr['deal_id']));
        }

        $res = $Msgcenter->save ();
        return $res;
    }

    public function load_user(){
        $return= array("status"=>0,"message"=>"");
        $id = intval($_REQUEST['id']);
        if($id==0){
            return ajax_return($return);
        }
        $user = $GLOBALS['db']->getRow("SELECT u.*,l.name,l.point as l_point,l.services_fee,enddate FROM ".DB_PREFIX."user u LEFT JOIN ".DB_PREFIX."user_level l ON u.level_id = l.id WHERE u.id=".$id);
        if(!$user){
            return ajax_return($return);
        }
        $return['status']=1;
        $return['user']=$user;
        return ajax_return($return);
    }

    //获取基础数据配置
    function get_deploy() {
        $m = M("deploy")->select();
        $r = array();
        foreach ($m as $key => $value) {
            $v = $value['process'];
            unset($value['process']);
            $r[$v] = $value;
        }
        return $r;
    }

    /**
     * 根据借款用途获取对应图片
     * 2013-07-02 Liwei Add
     */
    private function get_icon_by_type($type_id){
        if(empty($type_id)) return false;
        $iconModule = M("Deal_loan_type");
        $icon = $iconModule->field("icon")->where("id = ".intval($type_id))->find();
        return $icon['icon'];
    }

    /**
     * 给贷款担保人发邮件及短信,写入业务邮件队列表deal_msg_list
     * @param int $deal_id 贷款申请id
     * @author zhang ruoshi
     * @return int 添加到任务队列中的记录数量
     */
    private function _guarantorMsg($deal_id, $deal_name, $user_id, $user_name){
        if(!$deal_id) return 0;
        FP::import("libs.common.app");
        //贷款申请信息
        /* $deal = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."deal WHERE id=".$deal_id." and user_id=".$user_id);
        if(empty($deal)) return 0; */

        //贷款担保人信息
        //and status != 2   => edit by wenyanlei 导入的借款保证人都是已经同意的，不需要发送邀请信息
        $guarantor = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_guarantor where user_id =". $user_id." and deal_id =".$deal_id." and status != 2 order by id asc");
        if(empty($guarantor)) return 0;

        //循环处理担保人邮件及短信数据
        $count = 0;
        $Msgcenter = new Msgcenter();
        foreach($guarantor as $k=>$v){
            //邮件
            $icode = base62encode($v['id']);
            $msg_mail = array(
                'user_name'=>$v['name'],
                'msg_user_name'=>$user_name,
                'deal_name'=>$deal_name,
                'guarantor_url'=>get_deal_domain($deal_id).url("index","user-register",array("icode"=>$icode)),
                'deal_url'=>get_deal_domain($deal_id).url("index","deal",array("id"=>$deal_id)),
                'site_name'=>app_conf("SHOP_TITLE"),
                'site_url'=>get_deal_domain($deal_id).APP_ROOT,
                'help_url'=>get_deal_domain($deal_id).url("index","helpcenter"),
            );
            $Msgcenter->setMsg($v['email'], 0, $msg_mail, 'TPL_DEAL_GUARANTOR_MAIL',$user_name."邀请您作为借款保证人",'',get_deal_domain_title($deal_id));

            // JIRA#3260 企业账户二期 <fanjingwen@ucf>
            $userServ = new \core\service\UserService($deal['user_id']);
            if ($userServ->isEnterprise()) {
                $user_name = get_company_shortname($deal['user_id']);
            }

            //短信
            $msg_sms = array(
                'user_name'=>$v['name'],
                'msg_user_name'=>$user_name,
                'deal_name'=>$deal_name,
                'email'=>$v['email'],
            );
            \libs\sms\SmsServer::instance()->send($v['mobile'], 'TPL_DEAL_GUARANTOR_SMS', $msg_sms, get_deal_siteid($deal_id));
            $count++;
        }
        $r = $Msgcenter->save();
        return $count;
    }

    /* 显示担保人列表 */
    public function show_adviser_list()
    {
        $id = intval($_REQUEST['id']);

        $list = D("DealLoadAdviser")->where('deal_id='.$id)->order("id ASC")->findall();

        // 查询顾问和用户信息
        foreach($list as &$val)
        {
            $uinfo = M("User")->where("id=".$val['user_id']." and is_delete = 0")->find();

            if(!$uinfo)
                $val['real_name'] = l("NO_USER");
            else
            {
                #$val['real_name'] = $uinfo['real_name'];
                $val['real_name'] = "<a href='".u("User/index",array("user_name"=>$uinfo['user_name']))."' target='_blank'>".$uinfo['real_name']."</a>";
            }

            $ainfo = M("Adviser")->where("id=".$val['recommend_id'])->find();
            if($ainfo)
            {
                #$val['adviser_name'] = $ainfo['name'];
                $val['adviser_name'] = "<a href='".u("Adviser/index",array("id"=>$ainfo['id']))."' target='_blank'>".$ainfo['name']."</a>";
            }
        }

        $this->assign("list",$list);
        $template = $this->is_cn ? 'show_adviser_list_cn' : 'show_adviser_list';
        $this->display($template);
    }

    /**
     * 计算pmt各值
     * $rate 年利率
     * $repay_time 借款期限
     * $loantype 还款方式,1 按季等额还款,2 按月等额还款,3到期支付本金收益
     * $borrow_amount 借款金额
     * $manage_fee_rate 平台管理费
     * */
    public function getPmt($dealInfo) {
        $finance = new Finance();
        $pmt = $finance->getPmtByDeal($dealInfo);
        return array(0=>$pmt);
    }

    /**
     * 文件管理
     */
    public function file_operate()
    {

        if (stripos($_SERVER['HTTP_REFERER'], 'm=Deal&a=index') !== FALSE){
            $_SESSION['refer_url'] = $_SERVER['HTTP_REFERER'];
        }

        $id = intval($_REQUEST['id']);
        $order = "id asc";
        if (isset($_GET['_order']))
        {
            $order = "`".$_GET['_order']."` asc";
        }

        //$sql = "select f.*,a.adm_name,d.name as deal_name from firstp2p_deal_attachment as f,firstp2p_deal as d,firstp2p_admin as a where f.deal_id=".$id." and f.deal_id=d.id and f.admin_user_id=a.id and status=0 order by ".$order;
        $sql = "select f.*,a.adm_name,d.name as deal_name from ".DB_PREFIX."deal_attachment as f,".DB_PREFIX."deal as d,".DB_PREFIX."admin as a where f.deal_id=".$id." and f.deal_id=d.id and f.admin_user_id=a.id and status=0 order by ".$order;
        $list = $GLOBALS['db']->getAll($sql);
        $this->assign("refer", $_SESSION['refer_url']);
        $this->assign('id',$id);
        $this->assign("list",$list);
        $this->display();
    }

    /**
     * 添加文件
     */
    public function add_deal_file()
    {

        $id = intval($_REQUEST['id']);

        $types = $GLOBALS['dict']['DEAL_FILE_TYPE'];
        $stype = '';
        foreach ($types as $type){
            $stype .= '*.'.$type.';';
        }
        $this->assign("max_upload", $GLOBALS['dict']['MAX_UPLOAD']);
        $this->assign("stype", $stype);
        $this->assign("ssid",session_id());
        $this->assign('id',$id);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit_deal_file()
    {

        $id = intval($_REQUEST ['id']);
        if (!isset($_POST['save']))
        {
            $lid = intval($_REQUEST ['list_id']);
            $deal = M("DealAttachment")->where("id=".$id)->find();
            $deal['create_time'] = to_date($deal['create_time']);
            $admin = M("Admin")->where("id=".$deal['admin_user_id'])->find();
            $deal['admin_name'] = $admin['adm_name'];
            unset($admin);
            $this->assign("max_upload", $GLOBALS['dict']['MAX_UPLOAD']);
            $this->assign("id", $id);
            $this->assign("lid", $lid);
            $this->assign('deal', $deal);
            $this->display();
        }else{

            if ($id<0) $this->error("操作错误");
            if ($_POST['name'] == '') $this->error('标题不能为空');
            if ($_POST['description'] == '') $this->error('描述不能为空');

            $data = array();
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $data['title'] = trim($_POST['name']);
            $data['description'] = htmlspecialchars(trim($_POST['description']));
            $data["order"] = $_REQUEST['order'];
            $data['create_time'] = get_gmtime();
            $data['admin_user_id'] = $adm_session['adm_id'];

            if (!empty($_SESSION['file_save'])){
                $data['filename'] = $_SESSION['file_save']['path'];
                $data['type'] = $_SESSION['file_save']['ext'];
            }

            $rs = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_attachment",$data,"UPDATE","id=".$id);
            if ($rs)
            {
                unset($_SESSION['file_save']);
                save_log($adm_session['adm_name']."编辑订单附件文件 ".$id." 成功",1);
                $this->success(L("修改成功"));
            } else {
                save_log($adm_session['adm_name']."编辑订单附件文件 ".$id." 失败",1);
                $this->error(L("修改失败"));
            }

        }

    }

    /**
     * 保存文件
     * Enter description here ...
     */
    public function save_deal_file()
    {
        if ($_POST['name']=='') $this->error('标题不能为空');
        if ($_POST['description'] == '') $this->error('描述不能为空');

        if (empty($_SESSION['file_save'])){
            $this->error(L("上传文件不能为空"));
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['title'] = trim($_POST['name']);
        $data['deal_id'] = $_REQUEST['id'];
        $data['description'] = htmlspecialchars(trim($_POST['description']));
        $data['order'] = $_REQUEST['order'];
        $data['create_time'] = get_gmtime();
        $data['admin_user_id'] = $adm_session['adm_id'];
        $data['status'] = 0;

        $data['filename'] = $_SESSION['file_save']['path'];
        $data['type'] = $_SESSION['file_save']['ext'];

        $inster_id = M("DealAttachment")->add($data);

        if ($inster_id>0)
        {
            unset($_SESSION['file_save']);
            save_log($adm_session['adm_name']."添加订单id ".$data['deal_id']."附件成功,编号为".$inster_id,1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            save_log($adm_session['adm_name']."添加订单id ".$data['deal_id']."附件失败",0);
            $this->error(L("INSERT_FAILED"));
        }

    }



    /**
     * 删除文件
     */
    public function del_deal_file()
    {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $list = M("DealAttachment")->where ( $condition )->setField ( 'status', 1 );
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($list!==false)
            {
                save_log($adm_session['adm_name']."删除文件".$id.l("DELETE_SUCCESS"),1);
                $this->success (l("DELETE_SUCCESS"),$ajax);
            } else {
                save_log($adm_session['adm_name']."删除文件".$id.l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);

        }
    }

    /**
     * 上传文件
     *
     */
    public function upload(){
        set_time_limit(0);
        if (isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0)
        {

            $upload_file = $_FILES['Filedata'];
            $file_info   = pathinfo($upload_file['name']);
            $filename = md5(time().rand());

            $time = get_gmtime();
            $year = to_date($time,"Y");
            $month = to_date($time, "m");
            $day = to_date($time, "d");
            $dir = $GLOBALS['dict']['DEAL_FILE_PATH'].$year."/".$month."/".$day."/";
            $this->mkdirs($dir); //创建层级目录

            $file_save = $year."/".$month."/".$day."/".$filename.'.'.$file_info['extension'];
            // if(move_uploaded_file($upload_file['tmp_name'], APP_ROOT_PATH.$GLOBALS['dict']['DEAL_FILE_PATH'].$file_save))
            // TODO vfs done 2014-05-27 17:55:22
        try {
            Vfs::write( APP_ROOT_PATH.$GLOBALS['dict']['DEAL_FILE_PATH'].$file_save, $upload_file['tmp_name']);
                    $_SESSION['file_save']["path"] = $file_save;
                    $_SESSION['file_save']["ext"] = $file_info['extension'];
            exit();
        } catch (Exception $e) {
            exit('1');
        }
        }
    }

    /**
     * 创建层级目录
     * @param 目录 $dir
     */
    private function mkdirs($dir)
    {
        $dir_arr = explode("/", $dir);
        $dirs = APP_ROOT_PATH;
        $i = 1;
        foreach ($dir_arr as $path){
            if ($i > 1)
            {
                $dirs .= "/".$path;
            }else{
                $dirs .= $path;
            }
            if (!is_dir($dirs))
            {
                mk_dir($dirs, 0777);
            }
            $i++;
        }
        return true;
    }
    /**
     * 向 p2p传输 deal 和  deal_load数据
     *
     * @Title: put_p2p_data
     * @Description: todo(这里用一句话描述这个方法的作用)
     * @param @param unknown_type $deal
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    private function put_p2p_data($id){

        $deal = $GLOBALS['db']->getRow("SELECT id,loantype,repay_money,repay_time,type_id,parent_id FROM ".DB_PREFIX."deal WHERE id=".$id);

        if(!$GLOBALS['sys_config']['P2P_API_URL_iS_OK']) {
            return false;
        }

        if(empty($deal)) {
            return false;
        }
        $post_data = array();
            $load_list = $GLOBALS['db']->getAll("SELECT id,deal_id,user_id,user_name,user_deal_name,money,create_time,from_deal_id FROM ".DB_PREFIX."deal_load WHERE deal_id = ".$deal['id']);

            foreach ($load_list as &$item)
            {
                $item['adviser'] = $this->getAdviserInfo($item['id']);
                $item['create_time'] = strtotime(to_date($item['create_time']));
            }

            $post_data = array(
                'deal_info' => array(
                    0 => $deal
                ),
                'deal_load' => $load_list
            );

        $url = $GLOBALS['sys_config']['P2P_API_URL'].'?act=first_p2p&type=order';

        // 数据插入到队列表
        $data = array();
        $data['dest'] = $url;
        $data['send_type'] = 2;
        $data['content'] = serialize($post_data);
        $data['create_time'] = get_gmtime();

        $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$data,"INSERT"); //这里已经被废弃

        return $ret;
    }

    /**
     * 根据id获取机构信息
     *
     * @param $id 机构id
     * @return 机构信息
     */
    private function get_deal_agency($id) {
        $agency = M("Deal_agency")->where("id = '" . $id . "' and is_effect = 1")->find();
        return (empty($agency) || empty($agency['user_id'])) ? false : $agency;
    }

    /* 获取顾问信息 */
    private function getAdviserInfo($deal_load_id)
    {
        $sql = "select adviser_id,name,mobile,status from ".DB_PREFIX."deal_load_adviser as a , ".DB_PREFIX."adviser as b where a.deal_load_id = '{$deal_load_id}' and a.recommend_id = b.id ";
        $info = $GLOBALS['db']->getRow($sql);
        return $info;
    }

   /**
     * 发送放款通知
     *
     * @param $deal_data 订单数据
     * @param $services_fee 服务费（平台+资讯+担保）
     * @param $actual_amount
     */
    /* private function _carry_borrow_notice($deal_data, $services_fee,$actual_amount){

        //发送消息
        $shop_title = get_deal_domain_title($deal_data['id']);
        $deal_data['url'] = '/deal/'.$deal_data['id'];//url("index","deal",array("id"=>$deal_data['id']));
        $content = "您好，您在" . $shop_title . "的借款 “<a href=\"" . $deal_data['url'] . "\">" . $deal_data['name'] . "</a>”已招标成功。";
        $content .= "借款金额:" . format_price($deal_data['borrow_amount'], 0) . "元，扣除服务费和担保费" . format_price($services_fee, 0) . "元，实得" . format_price($actual_amount,0) . "元。";
        $content .= "系统已进行提现处理，如您填写的账户信息正确无误，您的资金将会于3个工作日内到达您的银行账户。";
        $this->_send_user_msg("招标成功自动提现", $content, 0, $deal_data['user_id'], get_gmtime(), 0, true, 5);
        send_tender_deal_message($deal_data,'loan',number_format($deal_data['borrow_amount'],2));
    } */

    /**
     * 强制还款
     * @actionlock
     */
    public function do_force_repay(){
        set_time_limit(0);
        $deal_id = $id = intval($_REQUEST['deal_id']);
        $repayType = $_REQUEST['repay_user_type'] <> 0 ? intval($_REQUEST['repay_user_type']):0;
        if('b' == $_REQUEST['role']){
            $repayType = $_REQUEST['repay_user_type_by_a'] <> 0 ? intval($_REQUEST['repay_user_type_by_a']):0;
        }
        $ignore_impose_money = $_REQUEST['ignore_impose_money'] == 1 ? true : false;
        if($id == 0){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            set_time_limit(30);
            $this->error("参数错误");
        }
        $deal = new core\dao\DealModel();
        $deal = $deal->find($id);
        if(!$deal || $deal['deal_status']!=4){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('强制还款失败 deal_id:'.$deal_id.' 借款状态错误', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,借款状态错误");
        }

        if($deal['is_during_repay'] == core\dao\DealModel::DURING_REPAY){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('强制还款失败 deal_id:'.$deal_id.' 借款正在还款中', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,借款正在还款中");
        }

        $this->assign("jumpUrl",u(MODULE_NAME."/force_repay", array("deal_id"=>$id)));
        $ids = $_REQUEST['repay_to'];


        //代垫
        if($repayType == 1){
            $dealAgency = new \core\service\DealAgencyService();
            $advanceAgencyInfo = $dealAgency->getDealAgency($deal['advance_agency_id']);
        }

        $dealService = new DealService();


        //逐一执行还款
        foreach ($ids as $id) {
            $id = intval($id);
            if($id == 0){
                save_log('强制还款失败 deal_id:'.$deal_id.' 还款id缺失', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                //set_time_limit(30);
                $this->error("操作失败,还款id缺失");
                exit();
            }
            /*
            $deal_repay = new core\dao\DealRepayModel();
            $deal_repay = $deal_repay->find($id);
            if($deal_repay){
                if ($deal_repay->repay($ignore_impose_money) == false) {
                    save_log('强制还款失败 deal_id:'.$deal_id.' repay_id:'.$id, C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                    set_time_limit(30);
                    $this->error("操作失败");
                    exit;
                }
            }
            */

            $authKey =conf ("AUTH_KEY");
            $admInfo = \es_session::get(md5($authKey));
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_REPAY, 'service_id' => $id))->find();

            $param = array('deal_repay_id' => $id, 'ignore_impose_money' => $ignore_impose_money, 'admin' => $admInfo, 'negative'=> 1,'repayType' => $repayType, 'submitUid' => $audit['submit_uid']);

            try {
                $GLOBALS['db']->startTrans();

                $job_model = new JobsModel();
                if(!$dealService->isP2pPath($deal)) {
                    // 异步处理还款
                    $function = '\core\service\DealRepayService::repay';

                    $dealLoanPartRepayModel = new DealLoanPartRepayModel();
                    if($dealLoanPartRepayModel->isPartRepay($id)) {
                        $param['negative'] = 0;//不允许扣负
                        $function = '\core\service\DealLoanPartRepayService::repay';
                        $list = $dealLoanPartRepayModel->getPartRepayListByRepayId(intval($id), DealLoanPartRepayModel::STATUS_SAVED);
                        if(empty($list)) {
                            throw new \Exception("获取部分还款数据失败");
                        }
                        foreach ($list as $item) {
                            $res = $dealLoanPartRepayModel->updateLoanRepayStatus($item['deal_repay_id'], $item['deal_loan_id'], DealLoanPartRepayModel::STATUS_ADOPTED);
                            if (!$res) {
                                throw new \Exception("更新部分还款数据状态失败");
                            }
                        }
                    }
                    $job_model->priority = JobsModel::PRIORITY_DEAL_REPAY;
                }else{
                    // p2p 还款逻辑
                    $orderId = Idworker::instance()->getId();
                    $function = '\core\service\P2pDealRepayService::dealRepayRequest';
                    $param = array('orderId'=>$orderId,'dealRepayId'=>$id,$repayType,'params'=>$param);
                    $job_model->priority = JobsModel::PRIORITY_P2P_REPAY_REQUEST;
                }

                $auditRes= M("ServiceAudit")->where("id=" . $audit['id'])->save(array('status' => ServiceAuditModel::AUDIT_SUCC, 'audit_uid' => $admInfo['adm_id']));
                if (!$auditRes) {
                    throw new \Exception("操作失败");
                }

                $res = $job_model->addJob($function, $param);
                if ($res === false) {
                    save_log('强制还款失败 deal_id:' . $deal_id . ' repay_id:' . $id, C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                    throw new \Exception("操作失败");
                    //set_time_limit(30);
                }else{
                    $deal->changeRepayStatus(core\dao\DealModel::DURING_REPAY);
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e){
                $GLOBALS['db']->rollback();
                $this->error($e->getMessage());
                exit;
            }
        }
        set_time_limit(30);
        save_log('do_force_repay id:'.$deal_id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
        $this->assign("jumpUrl", "/m.php?m=Deal&a=yuqi&ref=1&{$_REQUEST['querystring']}");
        $this->success("操作成功");
    }

    public function force_repay(){
        $role = $this->getRole();
        $this->assign('role', $role);
        $this->assign('return_type_list', self::$returnTypes);//回退选项
        FP::import("libs.common.app");
        $id = intval($_REQUEST['deal_id']);
        if($id == 0){
            $this->error("参数错误");
        }
        $deal = \core\dao\DealModel::instance()->find($id);

        $dealLoanPartRepayModel = new DealLoanPartRepayModel();
        if(!$deal){
            $this->error("参数错误");
        }
        $isP2pPath = $deal['report_status'] == 1 ? true :false;

        $deal['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($deal['id'])){
            $deal['isDtb'] = 1;
        }

        $this->assign("deal",$deal);

        // 还款方 select 默认选中项
        $selected_repay_user = getRepayUserSelectStatus($deal['id']);

        $borrowUser = UserModel::instance()->find($deal['user_id']);
        $repayUser[] = array('userName' => $borrowUser['real_name'],'type'=> 0, 'is_selected' => $selected_repay_user['borrower']);//借款人信息 type = 0
        //代垫机构
        if($deal['advance_agency_id'] > 0){
            $advance_agency = \core\dao\DealAgencyModel::instance()->find($deal['advance_agency_id']);
            $repayUser[] = array('userName' => $advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name'],'type'=> 1, 'is_selected' => $selected_repay_user['advance_agency']);//代垫机构 type = 1
            $deal_type = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);

        }
        //代偿机构
        if($deal['agency_id'] > 0){//担保机构代偿
            $advance_agency = \core\dao\DealAgencyModel::instance()->find($deal['agency_id']);
            //代偿机构
            $repayUser[] = array('userName' => $advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name'],'type'=> 2, 'is_selected' => $selected_repay_user['agency']);
            //间接代偿机构
            $repayUser[] = array('userName' => '间接代偿' . ($advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name']),'type'=> DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG, 'is_selected' => $selected_repay_user['indirect_agency']);
        }

        //代充值机构
        if($deal['generation_recharge_id'] > 0){
            $generation_recharge = \core\dao\DealAgencyModel::instance()->find($deal['generation_recharge_id']);
            $repayUser[] = array('userName' => $generation_recharge['short_name'] == '' ? $generation_recharge['name']:$generation_recharge['short_name'],'type'=> 3, 'is_selected' => $selected_repay_user['generation_recharge']);
        }
        $deal_ext = core\dao\DealExtModel::instance()->getDealExtByDealId($id);
        $loan_arr = $deal_ext['loan_fee_ext'] ? json_decode($deal_ext['loan_fee_ext'], true) : array();
        $consult_arr = $deal_ext['consult_fee_ext'] ? json_decode($deal_ext['consult_fee_ext'], true) : array();
        $guarantee_arr = $deal_ext['guarantee_fee_ext'] ? json_decode($deal_ext['guarantee_fee_ext'], true) : array();
        $pay_arr = $deal_ext['pay_fee_ext'] ? json_decode($deal_ext['pay_fee_ext'], true) : array();
        $canal_arr = $deal_ext['canal_fee_ext'] ? json_decode($deal_ext['canal_fee_ext'], true) : array();

        //还款列表
        $loan_list = core\dao\DealRepayModel::instance()->findAll("deal_id =".$id." order by id asc");

        $user_info = UserModel::instance()->find($loan_list[0]['user_id']);

        for ($i = 0; $i < count($loan_list); $i++) {

            $loan_list[$i]['allow_repay'] = $loan_list[$i]->canRepay();
            $loan_list[$i]['repay_day'] = to_date($loan_list[$i]['repay_time'], 'Y-m-d');
            $loan_list[$i]['month_has_repay_money_all'] = $loan_list[$i]['status'] != 0 ? number_format($loan_list[$i]['repay_money'], 2) : 0;
            $loan_list[$i]['month_need_all_repay_money'] = $loan_list[$i]['status'] == 0 ? number_format($loan_list[$i]['repay_money'], 2) : 0;
            $loan_list[$i]['month_repay_money'] = $loan_list[$i]['status'] == 0 ? number_format($loan_list[$i]['principal'] + $loan_list[$i]['interest'], 2) : 0;
            $loan_list[$i]['status_text'] = $this->getLoanStatus($loan_list[$i]['status']);
            if($dealLoanPartRepayModel->isPartRepay($loan_list[$i]['id'])) {
                $loan_list[$i]['status_text'] = $this->getLoanStatus(5);
                $repaySumRepayed = $dealLoanPartRepayModel->getPartRepaySumByStatus($loan_list[$i]['id'],DealLoanPartRepayModel::STATUS_ISPAYED);
                $totalFee = 0;
                if(bccomp($repaySumRepayed['repay_money'],'0.00',2) == 1) { //如果有过部分还款，则金额加上费用
                    $totalFee = bcadd($totalFee,$loan_list[$i]['loan_fee'],2);
                    $totalFee = bcadd($totalFee,$loan_list[$i]['consult_fee'],2);
                    $totalFee = bcadd($totalFee,$loan_list[$i]['guarantee_fee'],2);
                    $totalFee = bcadd($totalFee,$loan_list[$i]['pay_fee'],2);
                    $totalFee = bcadd($totalFee,$loan_list[$i]['canal_fee'],2);
                    $repaySumRepayed['repay_money'] = bcadd($repaySumRepayed['repay_money'],$totalFee,2); //还款总额
                }

                $loan_list[$i]['month_has_repay_money_all'] = $repaySumRepayed['repay_money'];
                $loan_list[$i]['month_need_all_repay_money'] = bcsub($loan_list[$i]['repay_money'],$repaySumRepayed['repay_money'],2);
                $loan_list[$i]['month_repay_money'] = number_format($loan_list[$i]['principal'] + $loan_list[$i]['interest'] - ($repaySumRepayed['repay_money'] -$totalFee), 2);
            }
            $loan_list[$i]['impose_money'] = $loan_list[$i]->feeOfOverdue();
        }

        if ($loan_arr === array()) {
            // 年化收 还是 固定比例收
            if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
                $loan_fee_rate = $deal['loan_fee_rate'];
            } else {
                $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
            }
            $loan_fee = $deal->floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);
        } else {
            $loan_fee = $loan_arr[0];
        }

        if ($consult_arr === array()) {
            $consult_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
            $consult_fee = $deal->floorfix($deal['borrow_amount'] * $consult_fee_rate / 100.0);
        } else {
            $consult_fee = $consult_arr[0];
        }

        if ($guarantee_arr === array()) {
            $guarantee_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
            $guarantee_fee = $deal->floorfix($deal['borrow_amount'] * $guarantee_fee_rate / 100.0);
        } else {
            $guarantee_fee = $guarantee_arr[0];
        }

        if ($pay_arr === array()) {
            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $pay_fee = $deal->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);
        } else {
            $pay_fee = $pay_arr[0];
        }

        if ($canal_arr === array()) {
            $canal_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['canal_fee_rate'], $deal['repay_time'], false);
            $canal_fee = $deal->floorfix($deal['borrow_amount'] * $canal_fee_rate / 100.0);
        } else {
            $canal_fee = $canal_arr[0];
        }

        if ($role == 'b') {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis) {
                $this->assign('chk_ids', explode(',', $redis->get('admin_cache_service_audit_force_repay_chk_value_'.$id)));
                $this->assign('ignore_impose_money', intval($redis->get('admin_cache_service_audit_force_repay_ignore_ignore_impose_money_'.$id)));
                $this->assign('repay_user_type', $redis->get('admin_cache_service_audit_force_repay_user_type_'.$id));
            }

        }

        $temp_list = array(
            "allow_repay" => false,
            "repay_day" => to_date($deal['repay_start_time'], 'Y-m-d'),
            "month_has_repay_money_all" => number_format($loan_fee + $consult_fee + $guarantee_fee + $pay_fee, 2),
            "month_need_all_repay_money" => 0,
            "month_repay_money" => 0,
            "loan_fee" => $loan_fee,
            "consult_fee" => $consult_fee,
            "guarantee_fee" => $guarantee_fee,
            "pay_fee" => $pay_fee,
            "canal_fee" => $canal_fee,
            "status_text" => $this->getLoanStatus(1),
            "status" => 1,
            "impose_money" => 0,
        );

        if($deal['isDtb'] == 1) {
            $management_arr = $deal_ext['management_fee_ext'] ? json_decode($deal_ext['management_fee_ext']) : array();
            if ($management_arr === array()) {
                $management_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['management_fee_rate'], $deal['repay_time'], false);
                $management_fee = $deal->floorfix($deal['borrow_amount'] * $management_fee_rate / 100.0);
            } else {
                $management_fee = $management_arr[0];
            }
            $temp_list['management_fee'] = $management_fee;
        }

        $loan_list[-1] = $temp_list;
        sort($loan_list);
        $this->assign("repay_user",$repayUser);
        $this->assign("loan_list",$loan_list);
        $this->assign('today',to_date(time(), 'Y-m-d'));
        $repayUserType = 0;
        if ($role == 'b') {
            $repayUserType = $redis->get('admin_cache_service_audit_force_repay_user_type_'.$id);
        }

        $dealService = new DealService();
        $advanceAgencyUserId = $dealService->getRepayUserAccount($id,1);
        $agencyUserId = $dealService->getRepayUserAccount($id,2);
        $generationRechargeUserId = $dealService->getRepayUserAccount($id,3);
        $indirectAencyUserId = $dealService->getRepayUserAccount($id, DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG);
        $advanceAgencyUserInfo = UserModel::instance()->find(intval($advanceAgencyUserId));
        $agencyUserInfo = UserModel::instance()->find(intval($agencyUserId));
        $generationRechargeUserInfo = UserModel::instance()->find(intval($generationRechargeUserId));
        $indirectAencyUserInfo = UserModel::instance()->find(intval($indirectAencyUserId));

        if ($repayUserType == '1') {
            $payer = $advanceAgencyUserInfo;
        } elseif ($repayUserType == '2') {
            $payer = $agencyUserInfo;
        }elseif ($repayUserType == '3') {
            $payer = $generationRechargeUserInfo;
        }elseif ($repayUserType == DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) {
            $payer = $indirectAencyUserInfo;
        }else {
            $payer = $user_info;
        }
        if($isP2pPath){
            $balanceService = new \core\service\UserThirdBalanceService;
            $balanceResult = $balanceService->getUserSupervisionMoney($payer['id']);
            $advanceResult = $balanceService->getUserSupervisionMoney($advanceAgencyUserId);
            $agencyResult = $balanceService->getUserSupervisionMoney($agencyUserId);
            $generationRechargeResult = $balanceService->getUserSupervisionMoney($generationRechargeUserId);
            $indirectAencyResult = $balanceService->getUserSupervisionMoney($indirectAencyUserId);
            $userMoney = $balanceResult['supervisionBalance'];
            $advanceAgencyUserInfo['money'] = $advanceResult['supervisionBalance'];
            $agencyUserInfo['money'] = $agencyResult['supervisionBalance'];
            $generationRechargeUserInfo['money'] = $generationRechargeResult['supervisionBalance'];
            $indirectAencyUserInfo['money'] = $indirectAencyResult['supervisionBalance'];
        }else{
            $userMoney = $payer['money'];
        }

        $this->assign('advance_money',$advanceAgencyUserInfo['money']);
        $this->assign('agency_money',$agencyUserInfo['money']);
        $this->assign('generation_recharge_money',$generationRechargeUserInfo['money']);
        $this->assign('indirect_agency_money',$indirectAencyUserInfo['money']);
        $this->assign('user_money',$userMoney);

        $querystring = array();
        foreach ($_GET as $k => $v) {
            if (!empty($v)) {
                if ($k == 'deal_id') {
                    continue;
                }
                $querystring[$k] = $v;
            }
        }
        $this->assign('querystring', http_build_query($querystring));
        $template = $this->is_cn ? 'force_repay_cn' : 'force_repay';
        $this->display ($template);
    }

    /**
     * 线下还款
     */
    public function offline_repay(){
        $deal_repay_id = intval($_REQUEST['deal_repay_id']);
        if($deal_repay_id == 0){
            $this->error("参数错误");
        }

        $deal_loan_repay_model = new DealLoanRepayModel();

        $list = $deal_loan_repay_model->getOriginLoanRepayInfos($deal_repay_id);
        
        $this->assign("repayInfos",$list);
        $this->assign("is_select_checked_all",1);
        $this->assign("deal_repay_id",$deal_repay_id);
        $this->display ();

    }

    /**
     * 线上还款
     */
    public function save_offline_repay(){
        $deal_repay_id = intval($_REQUEST['repay_id']);

        $status_arr = json_decode($_POST['json_str'],true);

        if (empty($status_arr)){
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = '请选择';
            ajax_return($result);
        }

        $dealRepay = DealRepayModel::instance()->find($deal_repay_id);
        if (empty($dealRepay)){
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = '还款信息不存在';
            ajax_return($result);
        }
        $deal = DealModel::instance()->find($dealRepay->deal_id);
        if ($deal->is_during_repay ==1){
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = '正在还款中';
            ajax_return($result);
        }

        $offline_repay_service = new OfflineRepayService();
        try {
            $authKey =conf ("AUTH_KEY");
            $audit = array('submit_uid' => 0);
            $ret = $offline_repay_service->repay($deal_repay_id,array_keys($status_arr),$authKey,$audit);
            if ($ret){
                $result = array();
                $result['errCode'] =  0 ;
                $result['errMsg'] =  '成功';
                ajax_return($result);
            }
        }catch (\Exception $e){
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = $e->getMessage();
            ajax_return($result);
        }

        $result = array();
        $result['errCode'] =  -1 ;
        $result['errMsg'] =  '未知错误';
        ajax_return($result);
    }
    /**
     * 部分用户还款
     */
    public function part_user_repay(){
        $role = $this->getRole();
        $this->assign('role', $role);
        $this->assign('return_type_list', self::$returnTypes);//回退选项
        FP::import("libs.common.app");
        $deal_repay_id = intval($_REQUEST['deal_repay_id']);
        if($deal_repay_id == 0){
            $this->error("参数错误");
        }

        $dealLoanPartRepayService = new DealLoanPartRepayService();
        //如果该还款Id没有过部分用户还款记录，则拉取原始数据
        $is_select_checked_all = 0;
        $dealLoanRepayInfos = $dealLoanPartRepayService->getPartRepayListByRepayId($deal_repay_id);
        if(empty($dealLoanRepayInfos)) {//未取得数据
            $dealLoanPartRepayModel = new DealLoanPartRepayModel();
            $dealLoanRepayInfos = $dealLoanPartRepayModel->getOriginLoanRepayInfos($deal_repay_id);
            if (!empty($dealLoanRepayInfos)){
                $is_select_checked_all = 1;
            }

        }
        $this->assign("repayInfos",$dealLoanRepayInfos);
        $this->assign("is_select_checked_all",$is_select_checked_all);
        $this->assign("deal_repay_id",$deal_repay_id);
        $this->display ();
    }

    public function save_part_user_repay() {
        $role = $this->getRole();
        $this->assign('role', $role);
        $deal_repay_id = intval($_REQUEST['repay_id']);

        $status_arr = json_decode($_POST['json_str'],true);

        if (empty($status_arr)){
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = '请选择';
            ajax_return($result);
        }
        //如果该还款Id没有过部分用户还款记录，则拉取原始数据
        $is_new = 0;
        $dealLoanPartRepayService = new DealLoanPartRepayService();
        $dealLoanRepayInfos = $dealLoanPartRepayService->getPartRepayListByRepayId($deal_repay_id);
        $dealLoanPartRepayModel = new DealLoanPartRepayModel();
        if(empty($dealLoanRepayInfos)) {//未取得数据
            $dealLoanRepayInfos = $dealLoanPartRepayModel->getOriginLoanRepayInfos($deal_repay_id);
            $is_new = 1;
        }

        try {
            $GLOBALS['db']->startTrans();
            $batchId = $dealLoanPartRepayModel->getLatestBatchId($deal_repay_id);
            if ($is_new == 1) {
                foreach ($dealLoanRepayInfos as $v) {
                    $dealLoanPartRepayModel = new DealLoanPartRepayModel();
                    $dealLoanPartRepayModel->deal_id = $v['deal_id'];
                    $dealLoanPartRepayModel->deal_repay_id = $deal_repay_id;
                    $dealLoanPartRepayModel->deal_loan_id = $v['deal_loan_id'];
                    $dealLoanPartRepayModel->loan_user_id = $v['loan_user_id'];
                    $dealLoanPartRepayModel->borrow_user_id = $v['borrow_user_id'];
                    $dealLoanPartRepayModel->repay_money = $v['repay_money'];
                    $dealLoanPartRepayModel->principal = $v['principal'];
                    $dealLoanPartRepayModel->interest = $v['interest'];
                    $dealLoanPartRepayModel->apply_time = time();
                    $dealLoanPartRepayModel->time = $v['time'] + 28800;
                    $dealLoanPartRepayModel->real_time = 0;
                    $dealLoanPartRepayModel->batch_id = $batchId;
                    $dealLoanPartRepayModel->deal_type = $v['deal_type'];
                    $dealLoanPartRepayModel->status = isset($status_arr[$v['deal_loan_id']]) ? $status_arr[$v['deal_loan_id']] : 0;
                    $dealLoanPartRepayModel->update_time = time();
                    $ret = $dealLoanPartRepayModel->save();
                    if (empty($ret)){
                        throw new \Exception("写入失败 ".$v['deal_id'].' '.$v['deal_loan_id'].' '.$deal_repay_id);
                    }

                }
            }else{
                // 更新操作
                $dealLoanPartRepayModel = new DealLoanPartRepayModel();
                if (!empty($status_arr)){
                    foreach($status_arr as $key => $v){
                        $data['status'] = $v;
                        if($v == DealLoanPartRepayModel::STATUS_NOTPAYED) {
                            $data['batch_id'] = 0;
                        } else {
                            $data['batch_id'] = $batchId;
                        }
                        $where = 'deal_loan_id='.intval($key).' AND status in (0,2) AND deal_repay_id='.intval($deal_repay_id);
                        $ret = $dealLoanPartRepayModel->updateBy($data,$where);
                        if (empty($ret)){
                            throw new \Exception("更新失败 ".$key.' '.$deal_repay_id);
                        }
                    }
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $errCode = -1;
            $errMsg = '操作失败';
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.$e->getMessage());
        }
        $result = array();
        $result['errCode'] = empty($errCode) ? 0 : $errCode;
        $result['errMsg'] = empty($errMsg) ? '成功' : $errMsg;
        ajax_return($result);


    }
    public function save_service_fee() {
        $deal_id = intval($_REQUEST['deal_id']);
        $loan_fee = $_REQUEST['loan_fee'];
        $consult_fee = $_REQUEST['consult_fee'];
        $guarantee_fee = $_REQUEST['guarantee_fee'];
        $pay_fee = $_REQUEST['pay_fee'];
        $canal_fee = $_REQUEST['canal_fee'];

        if($deal_id == 0){
            $this->error("参数错误");
        }
        $deal = \core\dao\DealModel::instance()->find($deal_id);
        if(!$deal){
            $this->error("参数错误");
        }

        $management_fee= null;
        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        if(($deal['type_id'] == $dtbTypeId) && isset($_REQUEST['management_fee'])){
            $management_fee = $_REQUEST['management_fee'];
        }

        $result = core\dao\DealRepayModel::instance()->updateDealRepayServicefee($deal_id, $loan_fee, $consult_fee, $guarantee_fee,$pay_fee,$canal_fee,$management_fee);
        if ($result === true) {
            $this->success("操作成功", 1);
        } else {
            $this->error("操作失败", 1);
        }
    }

    /**
     * 计算是否能够进行还款
     * 能够还款的条件是：
     *  1、本期尚未还款
     *  2、当前日期正是本期还款时间
     * @return bool
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    private function allowRepay($repay_day){
        if($repay_day <= get_gmtime()){
            return true;
        }
        return false;
    }

    private function getLoanStatus($status_id){
        $status = array(
            0 => '待还',
            1 => '准时还款',
            2 => '逾期还款',
            3 => '严重逾期',
            4 => '提前还款',
            5 => '部分还款',
            100 => '线下还款',
        );
        return $status[$status_id];
    }

    /**
     * 提前还款申请
     * @actionlock
     */
    public function apply_prepay() {
        $deal_id = intval($_GET['deal_id']);
        if($deal_id == 0){
            $this->error("操作失败！");
        }
        $deal = get_deal($deal_id);
        if(!$deal || $deal['deal_status'] != 4){
            save_log('提前还款申请失败 deal_id:'.$deal_id.' 标状态错误', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败！");
        }

        $deal_model = core\dao\DealModel::instance()->find($deal_id);
        if($deal_model['is_during_repay'] == core\dao\DealModel::DURING_REPAY){
            save_log('提前还款申请失败 deal_id:'.$deal_id.' 标状态错误，正在还款中 during repay', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败， 正在还款中！");
        }

        $deal_loan_repay_model = new DealLoanRepay();

        $prepay_time = get_gmtime();
        $remain_days = get_remain_day($deal, $prepay_time);
        $remain_principal = get_remain_principal($deal);
        //$prepay_money = prepay_money($remain_principal, $remain_days, $deal['compensation_days'], $deal['int_rate']);
        $prepay_result = $deal_loan_repay_model->getPrepayMoney($deal_id, $remain_principal, $remain_days);

        $prepay_money = $prepay_result['prepay_money'];
        $remain_principal = $prepay_result['principal'];
        $prepay_interest = $prepay_result['prepay_interest'];

        $deal_dao = new Deal();
        $remain_principal = $deal_dao->floorfix($remain_principal);
        $prepay_interest = $deal_dao->floorfix($prepay_interest);
        $prepay_compensation = $deal_dao->floorfix($prepay_money - $prepay_interest - $remain_principal);
        $prepay_money = $deal_dao->floorfix($prepay_money + $prepay_result['loan_fee'] + $prepay_result['consult_fee'] + $prepay_result['guarantee_fee'] + $prepay_result['pay_fee']);

        $data = array(
            'deal_id'             => $deal_id,
            'user_id'             => $deal['user_id'],
            'prepay_time'         => get_gmtime(),
            'remain_days'         => $remain_days,
            'prepay_money'        => $prepay_money,
            'remain_principal'    => $remain_principal,
            'prepay_interest'     => $prepay_interest,
            'prepay_compensation' => $prepay_compensation,
            'loan_fee'            => $prepay_result['loan_fee'],
            'consult_fee'         => $prepay_result['consult_fee'],
            'guarantee_fee'       => $prepay_result['guarantee_fee'],
            'pay_fee'       => $prepay_result['pay_fee'],
        );
        $sql = "select count(*) from ".DB_PREFIX."deal_prepay where deal_id= $deal_id and (status =0 or status = 1)";
        $count = intval($GLOBALS['db']->getOne($sql));
        if($count > 0) {
            save_log('提前还款申请失败 deal_id:'.$deal_id.' 重复申请', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("请勿重复申请！");
        }

        $authKey =conf ("AUTH_KEY");
        $admInfo = \es_session::get(md5($authKey));

        $GLOBALS['db']->startTrans();
        try {
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"INSERT");
            if ($res == false) {
                throw new \Exception("insert deal_prepay error");
            }

            $res = $deal_model->changeRepayStatus(core\dao\DealModel::DURING_REPAY);
            if ($res == false) {
                throw new \Exception("chage repay status error");
            }

            $user = UserModel::instance()->find($deal['user_id']);

            $bizToken = [
                'dealId' => $deal['id'],
            ];
            $user->changeMoney($prepay_money, "提前还款申请", '编号'.$deal['id'].' '.$deal['name'], $admInfo['adm_id'], 0, UserModel::TYPE_LOCK_MONEY,0,$bizToken);

            $GLOBALS['db']->commit();

            save_log('提前还款申请 deal_id:'.$deal_id,C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));
            $this->success("操作成功!");
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            save_log('提前还款申请失败 deal_id:'.$deal_id.' 申请失败', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("申请失败！");
        }
    }

    /**
     * 提前还款审核
     * @actionlock
     */
    public function do_prepay(){  // 此方法已弃用
        $id = intval($_GET['id']);
        $op = $_REQUEST['op'];
        $retry = 0;

        $sucess =  C('SUCCESS') ;
        $saveLogFile = C('SAVE_LOG_FILE');
        $authKey =conf ("AUTH_KEY");
        $admInfo = \es_session::get(md5($authKey));

        if($id == 0 || empty($op)) {
            $this->error('参数错误');
        }
        $prepay = new \core\dao\DealPrepayModel();
        $prepay = $prepay->find($id);

        if($prepay->status > 0){
            $this->error('请不要重复操作');
        }

        //20141227 optimized
        $prepay->remark = $_POST['description'];
        if($op == "拒绝申请"){
            $prepay->status = 2;
        } else if ($op == '通过审核'){
            $prepay->status = 1;
        }

        save_log('提前还款'.$op.' id:' . $id, $success, '', '', $saveLogFile);
        $function  = '\core\service\DealPrepayService::prepay';
        $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_PREPAY, 'service_id' => intval($id)))->find();
        $param = array('id' => $id, 'status' => $prepay->status, 'success' => $success, 'saveLogFile' => $saveLogFile, 'admInfo' => $admInfo, 'submitUid' => $audit['submit_uid']);
        try{
            $GLOBALS['db']->startTrans();
            $result = M("ServiceAudit")->where("id=" . $audit['id'])->save(array('status' => ServiceAuditModel::AUDIT_SUCC));
            if (!$result) {
                throw new \Exception("更新审核状态失败");
            }
            $job_model = new JobsModel();
            $job_model->priority = 80;
            $job_model->addJob($function, array('param' => $param), false, $retry);
            $prepay->save();
            $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
        }
        $this->redirect('m.php?m=Deal&a=yuqi&role=b');
    }

    public function prepay_edit(){
        if(!isset($_GET['id'])){
            $this->error("参数错误");
        }
        $prepay = M("DealPrepay")->find($_GET['id']);
        $deal = get_deal($prepay['deal_id']);

        $deal['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($deal['id'])){
            $deal['isDtb'] = 1;
        }
        $this->assign("prepay", $prepay);
        $this->assign("deal", $deal);
        $this->display();
    }

    public function prepay(){
        $status = array(
            0 => '审核中',
            1 => '已通过',
            2 => '已拒绝',
        );
        $map = array();

        //机构管理后台
        $orgSql = $this->orgCondition(false);

        if (!empty($_REQUEST['project_name'])) {
            $map['_string'] = " `deal_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal` WHERE `project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE name = '" . trim($_REQUEST['project_name']) . "')".$orgSql.")";
        } elseif ($orgSql) {
            $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE 1 '.$orgSql.')';
        }

        $this->_list (M("DealPrepay"), $map, 'status', true);
        $prepays = $this->get('list');
        foreach ($prepays as &$prepay) {
            $prepay['deal']= get_deal($prepay['deal_id']);
            $prepay['status']= $status[$prepay['status']];
        }
        $this->assign("list", $prepays);
        $this->display();
    }

    /**
     * 编辑备注
     */
    public function edit_note() {
        $id = $_REQUEST['id'];
        $deal = get_deal($id);
        if (empty($deal)) {
            $this->error("参数错误");
        }
        $this->assign("vo", $deal);
        if (!isset($_REQUEST['note'])) {
            return $this->display();
        }

        return parent::set_field_by_id(array('note' => trim($_REQUEST['note'])));
    }

    /**
     * 此方法已弃用
     * 提前还款试算，暂不支持按月等额还款或者按季等额还款
     */
//     public function prepay_compute() {
//         $id = $_REQUEST['deal_id'];
//         $deal = M("Deal")->find($id);
//         if(empty($deal)) {
//             $this->error("标{$id}不存在");
//         }
//         if($deal['loantype'] == 1 || $deal['loantype'] == 2) {
//             $this->error("按月等额还款或者按季等额还款暂不支持提前还款试算");

//         }
//         $remainPrincipal = get_remain_principal($deal);
//         //开始计息日期取放款日期
//         $repayStartTime = $deal['repay_start_time'];
//         if($deal['loantype'] == 5) {//按天计算
//             $interestDays = $deal['repay_time'];
//         } else {//按月，一个月按30天计算
//             $interestDays = $deal['repay_time'] * 30;
//         }

//         $repayModel = new DealRepayModel();
//         //获取最近一期未还的记录
//         $unpaidRepayList = $repayModel->getDealUnpaiedRepayListByDealId($deal['id']);
//         $count = count($unpaidRepayList);
//         if($count < 1) {
//             $this->error("无法找到还款计划");
//         }
//         //最后一期的还款计划
//         $dealLastUnpaidRepay = $unpaidRepayList[$count - 1];
//         //最后一期应还的日期
//         $repayEndTime = $dealLastUnpaidRepay['repay_time'];

//         $totalRepayMoney = 0.00;
//         $interest = 0.00;
//         $loanFee = 0.00;
//         $consultFee = 0.00;
//         $guaranteeFee = 0.00;
//         $payFee = 0.00;
//         $managementFee = 0.00;

//         foreach($unpaidRepayList as $repay) {
//             $totalRepayMoney += $repay['repay_money'];
//             $interest += $repay['interest'];
//             $loanFee += $repay['loan_fee'];
//             $consultFee += $repay['consult_fee'];
//             $guaranteeFee += $repay['guarantee_fee'];
//             $payFee += $repay['pay_fee'];
//             $managementFee += $repay['managementFee'];
//         }
//         $prepayPenalty = bcmul($deal['borrow_amount'], $deal['prepay_rate'] / 100, 2);

//         $this->assign("deal", $deal);
//         $this->assign('remain_principal', number_format($remainPrincipal, 2));
//         $this->assign('repay_start_time', to_date($repayStartTime, "Y-m-d"));
//         $this->assign('repay_end_time', to_date($repayEndTime, "Y-m-d"));
//         $this->assign('interest_days', $interestDays);
//         $this->assign("total_repay_money", number_format($totalRepayMoney,2));
//         $this->assign("prepay_penalty", $prepayPenalty);
//         $this->assign("interest", number_format($interest,2));
//         $this->assign("loan_fee", number_format($loanFee, 2));
//         $this->assign("consult_fee", number_format($consultFee, 2));
//         $this->assign("guarantee_fee", number_format($guaranteeFee, 2));
//         $this->assign("pay_fee", number_format($payFee, 2));
//         $this->assign("managementFee", number_format($managementFee, 2));
//         $this->assign("redirectUrl", $_SERVER['HTTP_REFERER']);
//         $this->display();
//     }
    /**
     * 此方法已弃用
     */
//     public function do_prepay_compute() {
//         $dealId = $_REQUEST['deal_id'];
//         $deal = M("Deal")->find($dealId);
//         //验证标是否存在
//         if(empty($deal)) {
//             $result['status'] = 0;
//             $result['error_msg'] = "无法找到id为{$dealId}的标";
//             ajax_return($result);
//             return;
//         }

//         //验证是否超过提前还款的限制期
//         $prepayDate = trim($_REQUEST['prepay_end_date']);
//         $repayModel = new DealRepayModel();
//         //获取最近一期未还的记录
//         $unpaidRepayList = $repayModel->getDealUnpaiedRepayListByDealId($deal['id']);
//         $count = count($unpaidRepayList);
//         if($count < 1) {
//             $result['status'] = 0;
//             $result['error_msg'] = "无法找到还款计划";
//             ajax_return($result);
//             return;
//         }

//         //最后一期的还款计划
//         $dealLastUnpaidRepay = $unpaidRepayList[$count - 1];
//         //最后一期应还的日期
//         $repayEndTime = $dealLastUnpaidRepay['repay_time'];
//         $repayEndTime = to_date($repayEndTime, "Y-m-d");
//         //开始计息日
//         $repayStartTime = get_last_repay_time($deal);
//         $repayStartTime = to_date($repayStartTime, "Y-m-d");
//         //当放款的时间大于提前还款的日期，或者当最后一期还款的日期小于或者等于提前还款日期
//         if($repayStartTime > $prepayDate || $repayEndTime <= $prepayDate) {
//             $result['status'] = 0;
//             $result['error_msg'] = "您输入的日期{$prepayDate}不合法，请输入{$repayStartTime}和{$repayEndTime}之间的日期";
//             ajax_return($result);
//             return;
//         }

//         $deal_loan_repay_model = new DealLoanRepay();
//         $prepayTime = to_timespan($prepayDate);
//         $repayStartTime = to_timespan($repayStartTime);
//         $interestDays = ($prepayTime - $repayStartTime) / 86400;
//         //利息天数
//         $interestDays = intval($interestDays);
//         //使用天数
//         $useDays = ($prepayTime - $deal['repay_start_time']) / 86400;
//         $useDays = intval($useDays);
//         //利息天数
// //        $remain_days = get_remain_day($deal, $prepay_time);

//         $result = array(
//             'status' => 1,
//             'data'  => array(),
//         );
//         $result['data']['interest_days'] = $interestDays;

//         $remainPrincipal = get_remain_principal($deal);

//         $prepayResult = $deal_loan_repay_model->computePrepayMoney($dealId, $remainPrincipal, $interestDays, $useDays);

//         $deal_dao = new Deal();
//         $result['data']['principal'] = number_format($prepayResult['principal'], 2);
//         $result['data']['prepay_interest'] = number_format($prepayResult['prepay_interest'], 2);
//         $result['data']['prepay_penalty'] = number_format($prepayResult['prepay_penalty'], 2);
//         $result['data']['loan_fee'] = number_format($prepayResult['loan_fee'], 2);
//         $result['data']['consult_fee'] = number_format($prepayResult['consult_fee'], 2);
//         $result['data']['guarantee_fee'] = number_format($prepayResult['guarantee_fee'], 2);
//         $result['data']['pay_fee'] = number_format($prepayResult['pay_fee'], 2);
//         $prepayMoney = $deal_dao->floorfix($prepayResult['principal'] + $prepayResult['prepay_interest'] + $prepayResult['loan_fee'] + $prepayResult['consult_fee'] + $prepayResult['guarantee_fee'] + $prepayResult['pay_fee']);
//         $result['data']['prepay_money'] = number_format($prepayMoney, 2);

//         ajax_return($result);
//     }

    /**
     * 预约投标
     **/
    public function make_appointment()
    {
        $deal_id = intval($_GET['deal_id']);
        if($deal_id == 0){
            $this->error("参数错误");
        }
        $deal = Deal::instance()->find($deal_id);
        if(empty($deal)){
            $this->error("参数错误");
        }
        $this->assign("deal_id", $deal_id);
        $this->assign("deal_name", $deal->name);
        $this->display();
    }

    /**
     * 后台用户预约投标
     * @actionLock
     * lockauthor qicheng
     **/
    public function do_make_appointment() {

        $id = intval($_POST['deal_id']);
        $user_name = trim($_POST['user_name']);
        $user_info = UserModel::instance()->getInfoByName($user_name);
        if (empty($user_info['id'])){
            $this->error($user_name."用户不存在");
        }
        $user_id = $user_info['id'];
        $money = floatval($_POST['money']);
        $coupon = trim($_POST['coupon']);
        if(empty($coupon)){
            $coupon = 0;
        }

        if($id == 0 || $user_id == 0 || $money == 0){
            $this->error("提交的数据存在异常");
        }

        if(app_conf('PAYMENT_ENABLE')=='1'){
            try {
                $service = new PaymentService;
                $rs = $service->register($user_id);
                if ($rs === PaymentService::REGISTER_FAILURE) {
                    $this->error($user_id . '开户检测没有通过，开户失败' . var_export($rs, true));
                }
            }
            catch (Exception $e) {
                $this->error($user_id . '开户检测没有通过，开户失败' . $e->getMessage());
            }
        }


        $deal_site = DealSite::instance()->findBy('deal_id = '.$id);
        $site_id = $deal_site ? $deal_site->site_id : 1;

        try{
            $GLOBALS['db']->startTrans();
            $deal_service = new DealService();
            $res = $deal_service->bid($user_id, $id, $money, $coupon, DealLoad::$SOURCE_TYPE['appointment'], $site_id);
            if($res['error'] == true) {
                throw new \Exception("投标失败");
            }

            /*******智多鑫不再通过后台预约上标*****/
//            if($deal_service->isDealDT($id)) {
//                $jobsModel = new JobsModel();
//                $jobsModel->priority = JobsModel::PRIORITY_DT_BID_SUCCESS;
//                $function = '\core\service\DealLoadService::dtBidSuccess';
//                $param = array(
//                    'deal_id' => $id,
//                    'money' => $money,
//                );
//                $ret = $jobsModel->addJob($function, array('param' => $param), false, 1);
//                if(!$ret) {
//                    throw new \Exception("预约投标jobs 插入失败");
//                }
//            }

            $GLOBALS['db']->commit();
        }catch(\Exception $e) {
            $GLOBALS['db']->rollback();
            $res['error'] = true;
            $res['msg'] = $e->getMessage();
        }

        if ($res['error'] == true) {
            $this->error($res['msg']);
        } else {
            $this->success("预约成功");
        }
    }

    /**
     * 检查是否已经满标
     *
     * @Title: is_deal_full
     * @Description: todo(这里用一句话描述这个方法的作用)
     * @param
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    private function check_deal_full($deal){
        $deal = get_deal($deal['id']);
        if($deal['deal_status'] == 2){
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 复制借款
     * @actionLock
     * lockauthor qicheng
     */
    public function copy_deal(){

        $copy_res = false;
        $deal_id = intval($_GET['id']);
        $ajax = intval($_GET['ajax']);

        if($deal_id > 0){
            $deal_service = new DealService();
            $copy_res = $deal_service->copyDeal($deal_id);
            if($copy_res){
                save_log('复制借款 id:'.$deal_id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
                $this->success('已成功复制到未审核列表中',$ajax);
                exit;
            }
        }

        save_log('复制借款 id:'.$deal_id,C('FAILED'), '', '', C('SAVE_LOG_FILE'));
        $this->error('操作失败',$ajax);
    }

    /**
     * 修改借款的 借款人 Ajax
    */
    public function edit_borrower(){
        $deal_id = intval($_GET['deal_id']);

        $deal_info = M('Deal')->where('id='.$deal_id)->find();

        if(empty($deal_info) || !in_array($deal_info['deal_status'], array(0,1))){
            exit('非法操作！');
        }
        $user_info = M('User')->where('id='.$deal_info['user_id'])->find();
        $bank_info = M('UserBankcard')->where('user_id='.$deal_info['user_id'])->find();

        $this->assign("deal_id",$deal_id);
        $this->assign("user_id",$deal_info['user_id']);
        $this->assign("bank_status",$bank_info['status']);
        $this->assign("idcard_status",$user_info['idcardpassed']);

        $this->display();
    }

    public function update_borrower(){
        $deal_id = intval($_GET['deal_id']);
        $user_id = intval($_GET['user_id']);
        $old_uid = intval($_GET['old_uid']);

        $user_info = M('User')->where('id='.$user_id)->find();

        if(empty($user_info)){
            $this->ajaxReturn('','用户不存在',0);
        }

        $data['id'] = $deal_id;
        $data['user_id'] = $user_id;
        $res = M('Deal')->save($data);

        //记录操作日志
        $log_msg = 'id为'.$deal_id.'的借款user_id由'.$old_uid.'改为'.$user_id;

        if($res){
            save_log($log_msg.L("UPDATE_SUCCESS"), 1);

            $bank_info = M('UserBankcard')->where('user_id='.$user_id)->find();
            $return['user_id'] = $user_id;
            $return['user_html'] = get_user_name($user_id);
            $return['bank_status'] = $bank_info['status'];
            $return['idcard_status'] = $user_info['idcardpassed'];
            $this->ajaxReturn($return,'操作成功',1);
        }else{
            save_log($log_msg.L("UPDATE_FAILED"), 0);
            $this->ajaxReturn('','操作失败',0);
        }
    }

    /**
     * 利滚利标七日还款计划
     */
    public function compound_repay_schedule() {
        $deal_id = intval($_REQUEST['id']);
        if (empty($deal_id)) {
            $this->error('参数错误！');
        }
        $deal_compound_service = new DealCompoundService();
        $result = $deal_compound_service->getRepaySchedule($deal_id);
        $this->assign("list", $result);
        $this->display();
    }

    /**
     * getDealLoanTypeList
     * 获取借款用途列表
     *
     * @access public
     * @return void
     */
    public function getDealLoanTypeList() {
        //借款用途

        if ($this->project['deal_type'] == DealProjectService::DEAL_TYPE_LGL || DealProjectService::DEAL_TYPE_LGL == $this->deal['deal_type']) {
            $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` = 'LGL'")->order('sort desc')->findAll();
        } else {
            $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL'")->order('sort desc')->findAll();
        }
        $deal_type_tree = D("DealLoanType")->toFormatTree($deal_type_tree,'name');
        if ($this->is_cn)  {
            $deal_type_tree = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];
        }
        return $deal_type_tree;
    }
    /**
     * 增加标的优惠码配置
     * @param int $deal_type
     * @param int $deal_id
     */
    public function addCouponDeal($deal_type, $deal_id){
        // 标的优惠码设置
        $model_deal_coupon = M("CouponDeal");

        $deal_coupon_data = array(
            'deal_id' => $deal_id,
            'pay_type' => trim($_POST['pay_type']),
            'pay_auto' => trim($_POST['pay_auto']),
            'rebate_days' => trim($_POST['rebate_days']),
            'deal_type' => $deal_type,
            'loantype' => $_POST['loantype'],
            'repay_time' => trim($_POST['repay_time']),
            'is_rebate' => intval($_POST['is_rebate']),
        );
        $coupon_deal_service = new CouponDealService();
        return $coupon_deal_service->add($deal_coupon_data);
    }

    /**
     * 审核放款
     *
     * @access public
     * @return int //0 失败 1 通过审核 2 拒绝
     */
    public function audit($data, $role, $audit, $auditType = '', $serviceId = 0, $agree = false)
    {
        if($auditType === ServiceAuditModel::SERVICE_TYPE_PROJECT_LOAN){
            $project = $data;
        }else{
            $deal = $data;
        }

        $agree = (false === $agree) ? intval($_REQUEST['agree']) : $agree;

        $operation = ServiceAuditModel::OPERATION_SAVE;
        $param = array();
        $param['service_type'] = $auditType ? $auditType :  $this->getServiceType();
        if ($serviceId > 0) {
            $param['service_id']   = $serviceId;
        } else {
            $param['service_id']   = $data['id'];
        }
        $param['status']       = ServiceAuditModel::NOT_AUDIT;
        $admin                 = \es_session::get(md5(conf("AUTH_KEY")));
        if (empty($audit)) {
            $param['standby_1']    = $data['name'];
            $param['standby_2']    = $data['create_time'];
            $operation = ServiceAuditModel::OPERATION_ADD;
        }

        $opType = 1; //提交审核
        if ($role == 'b') { //B角审核状态
            $submitUid = $audit['submit_uid'];
            $param['audit_uid']   = $admin['adm_id'];//审核用户

            if ($agree == '1') {
                $opType = 0; //审核成功
                $param['status'] = ServiceAuditModel::AUDIT_SUCC;
            } else {
                $opType = 2; //审核失败
                $param['status'] = ServiceAuditModel::AUDIT_FAIL;
            }
        } else {
            $submitUid = $param['submit_uid'] = $admin['adm_id']; //提交审核的用户
        }
        $param['mark'] = $_REQUEST['return_reason'];

        $GLOBALS['db']->startTrans();

        if($auditType === ServiceAuditModel::SERVICE_TYPE_PROJECT_LOAN){
            $dealService = new DealService();
            try {
                $deals = $dealService->getDealByProId($project['id'],array(DealModel::$DEAL_STATUS['full']));
                $result = D('ServiceAudit')->opServiceAudit($param, $operation);
                if (!$result) {
                    throw new \Exception("更新审核状态失败");
                }
                if(count($deals)>0){
                    foreach($deals as $deal){
                        if ($opType != 0) {
                            if (in_array($auditType, array(ServiceAuditModel::SERVICE_TYPE_REPAY, ServiceAuditModel::SERVICE_TYPE_PREPAY))) {
                                $result = $this->saveRepayOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType, $auditType, $serviceId);
                            } else {
                                $result = $this->saveOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType);
                            }
                            if (!$result) {
                                throw new \Exception("插入操作记录失败");
                            }
                        }
                    }
                }


                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                return 0; //审核错误
            }
        }else{
            try {
                $result = D('ServiceAudit')->opServiceAudit($param, $operation);
                if (!$result) {
                    throw new \Exception("更新审核状态失败");
                }
                if ($opType != 0) {
                    if (in_array($auditType, array(ServiceAuditModel::SERVICE_TYPE_REPAY, ServiceAuditModel::SERVICE_TYPE_PREPAY))) {
                        $result = $this->saveRepayOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType, $auditType, $serviceId);
                    } else {
                        $result = $this->saveOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType);
                    }
                    if (!$result) {
                        throw new \Exception("插入操作记录失败");
                    }
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                return 0; //审核错误
            }
        }

        if (!$result) {
            return 0; //审核错误
        }

        if ($role == 'b') {
            if ($agree == '1') {
                return 1; //审核通过
            }
            return 2; // B角回退
        }
        return 3; //提交审核

    }

    /**
     * 获取服务类型
     * @param $project 是否为项目放款
     * @access private
     * @return void
     */
    private function getServiceType($project = false) {
        if($project == false){
            return ServiceAuditModel::SERVICE_TYPE_LOAN;
        }else{
            return ServiceAuditModel::SERVICE_TYPE_PROJECT_LOAN;
        }
    }

    /**
     * 生成操作日志
     *
     * @param mixed $deal
     * @param int $returnType
     * @param string $returnReason
     * @param int $opType
     * @access private
     * @return void
     */
    private function saveOplog($deal, $admin, $submitUid, $returnType = 0, $returnReason = '', $opType = 1) {

        $projectInfo      = \core\dao\DealProjectModel::instance()->find($deal['project_id']);
        $loan_oplog_model = new \core\dao\LoanOplogModel();

        $loan_oplog_model->op_type         = $opType;
        $loan_oplog_model->loan_batch_no   = '';
        $loan_oplog_model->deal_id         = $deal['id'];
        $loan_oplog_model->deal_name       = $deal['name'];
        $loan_oplog_model->borrow_amount   = $deal['borrow_amount'];
        $loan_oplog_model->repay_time      = $deal['repay_time'];
        $loan_oplog_model->loan_type       = $deal['loantype'];
        $loan_oplog_model->borrow_user_id  = $deal['user_id'];
        $loan_oplog_model->op_user_id      = $admin['adm_id'];
        $loan_oplog_model->loan_money_type = $projectInfo['loan_money_type'];
        $loan_oplog_model->op_time         = get_gmtime();
        $loan_oplog_model->loan_money      = $deal['borrow_amount'];
        $loan_oplog_model->return_type     = $returnType;
        $loan_oplog_model->return_reason   = $returnReason;
        $loan_oplog_model->submit_uid      = $submitUid;
        if(!$loan_oplog_model->save()){
            throw new \Exception("保存放款操作记录失败");
        };

        return true;
    }

    private function saveRepayOplog($deal, $adminInfo, $submitUid, $returnType, $returnReason, $opType = 1, $auditType = '', $serviceId = '')
    {
        if ($auditType == ServiceAuditModel::SERVICE_TYPE_PREPAY) {
            $repay = D('DealPrepay')->where(array('id' => intval($serviceId)))->find();
            $opStuts = 2;
        } else {
            $opStuts = 1;
            $repay = D('DealRepay')->where(array('id' => intval($serviceId)))->find();
            $dealLoanPartRepayModel = new DealLoanPartRepayModel();
            if($dealLoanPartRepayModel->isPartRepayDeal($deal['id'],intval($serviceId))) {
                $deal_repay_model = new DealRepayModel();
                $repayPart = $deal_repay_model->find(intval($serviceId));
                $repayPart = $dealLoanPartRepayModel->formatPartRepay($repayPart,intval($serviceId),DealLoanPartRepayModel::STATUS_SAVED);
                $repay['repay_money'] = $repayPart['repay_money'];
                $opStuts = \core\dao\DealRepayOplogModel::REPAY_TYPE_PART;
            }
        }
        //增加提前还款的操作记录
        $repayOpLog                   = new \core\dao\DealRepayOplogModel();
        $repayOpLog->operation_type   = $opStuts;
        $repayOpLog->operation_time   = get_gmtime();
        $repayOpLog->operation_status = 0;
        $repayOpLog->operator         = $adminInfo['adm_name'];
        $repayOpLog->operator_id      = $adminInfo['adm_id'];
        $repayOpLog->deal_id          = $deal['id'];
        $repayOpLog->deal_name        = $deal['name'];
        $repayOpLog->borrow_amount    = $deal['borrow_amount'];
        $repayOpLog->rate             = $deal['rate'];
        $repayOpLog->loantype         = $deal['loantype'];
        $repayOpLog->repay_period     = $deal['repay_time'];
        $repayOpLog->user_id          = $deal['user_id'];
        $repayOpLog->report_status      = $deal['report_status'];
        $repayOpLog->deal_repay_id    = $repay['id'];
        $repayOpLog->repay_money      = $opStuts == 2 ? $repay['prepay_money'] : $repay['repay_money'];
        $repayOpLog->real_repay_time  = get_gmtime();
        $repayOpLog->return_type      = $returnType;
        $repayOpLog->return_reason    = $returnReason;
        $repayOpLog->submit_uid       = $submitUid;
        $repayOpLog->audit_type       = $opType;

        $result = $repayOpLog->save();
        if(!$result) {
            throw new \Exception("保存还款操作记录失败");
        };

        return true;
    }

    public function submitAudit()
    {
        $dealId = intval($_REQUEST['deal_id']);
        $role = $this->getRole();

        if ($_REQUEST['audit_type'] == ServiceAuditModel::SERVICE_TYPE_PREPAY && $_REQUEST['deal_repay_id'] == '') {
            $sql = "select * from ".DB_PREFIX."deal_prepay where deal_id= $dealId and status =0";
            $res = $GLOBALS['db']->getRow($sql);
            $_REQUEST['deal_repay_id'] = $res['id'];
        }

        $ids = explode(',', $_REQUEST['deal_repay_id']);
        if (!is_array($ids)) {
            $result = array();
            $result['errCode'] = 1;
            $result['errMsg'] = "请选择还款";
            ajax_return($result);
            return;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            if ($_REQUEST['ignore_impose_money'] == 'true') {
                $_REQUEST['ignore_impose_money'] = 1;
            } else {
                $_REQUEST['ignore_impose_money'] = 0;
            }
            $redis->setex('admin_cache_service_audit_force_repay_chk_value_'.$dealId, 2592000, $_REQUEST['deal_repay_id']); //有效期30天
            $redis->setex('admin_cache_service_audit_force_repay_ignore_ignore_impose_money_'.$dealId, 2592000, $_REQUEST['ignore_impose_money']); //有效期30天
            $redis->setex('admin_cache_service_audit_force_repay_user_type_'.$dealId, 2592000, $_REQUEST['repay_user_type']); //有效期30天
        }

        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $dealId))->find();
        if (empty($vo)) {
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = "标的错误";
            ajax_return($result);
            return;
        }
        foreach ($ids as $index => $id) {
            if ($id <= 0) {
                $result['errCode'] = 1;
                $result['errMsg'] = "错误的还款ID";
                ajax_return($result);
                return;
            }
            $auditType = intval($_REQUEST['audit_type']);
            if (!in_array($auditType, array(ServiceAuditModel::SERVICE_TYPE_REPAY, ServiceAuditModel::SERVICE_TYPE_PREPAY))) {
                $result['errCode'] = 1;
                $result['errMsg'] = "提交审核类型错误";
                ajax_return($result);
                return;
            }

            $dealPartRepayModel = new DealLoanPartRepayModel();
            if($dealPartRepayModel->isPartRepay($id)) {
                //没有状态为2的数据不执行还款
                $savedCount = $dealPartRepayModel->getRepayCountByDealRepayId($id,DealLoanPartRepayModel::STATUS_SAVED);
                if($savedCount ==0) {
                    $result['errCode'] = 1;
                    $result['errMsg'] = "请勾选部分还款用户后再提交";
                    ajax_return($result);
                    return;
                }
            }

            $audit = D('ServiceAudit')->where(array('service_type' => $auditType, 'service_id' => $id))->find();

            if ($index == 0 && $audit['service_id'] > 0 && $role != 'b') {
                if ($audit['service_type'] == ServiceAuditModel::SERVICE_TYPE_REPAY && $audit['service_type'] == ServiceAuditModel::NOT_AUDIT) {
                    $repay = D('DealRepay')->where(array('id' => intval($audit['service_id']), 'deal_id' => $dealId))->find();
                    if ($repay) {
                        $result['errCode'] = 1;
                        $result['errMsg'] = "该标的已经在审核中，请审核后再提交";
                        ajax_return($result);
                        return;
                    }
                }
                if ($audit['service_type'] == ServiceAuditModel::SERVICE_TYPE_PREPAY && $audit['service_type'] == ServiceAuditModel::NOT_AUDIT) {
                    $prepay = D('DealPrepay')->where(array('id' => intval($audit['service_id']), 'deal_id' => $dealId))->find();
                    if ($prepay) {
                        $result['errCode'] = 1;
                        $result['errMsg'] = "该标的已经在审核中，请审核后再提交!";
                        ajax_return($result);
                        return;
                    }
                }
            }
            $this->audit($vo, $role, $audit, $auditType, $id);
        }

        $result = array();
        $result['errCode'] = 0;
        $result['errMsg'] = "提交审核成功";
        ajax_return($result);
        return;
    }

    // 待放款列表批量提交
    public function batch_submit() {
        $deal_ids = trim($_POST['deal_ids']);
        $return = array(
            'status'=> 1,
            'msg' =>'success',
            'data'=>array(),
        );

        if(empty($deal_ids)) {
            $return['status'] = 0;
            $return['msg'] = 'error params';
        }else {
            $deal_ids = explode(",", $deal_ids);
            $total_deal_num = count($deal_ids);
            $failDealIds = array();

            $userBatchLoanInfos = array();
            foreach ($deal_ids as $k=> $deal_id) {
                $deal_info = DealModel::instance()->findViaSlave(intval($deal_id));
                if(!isset($userBatchLoanInfos[$deal_info->user_id])) {
                    $user_info = UserModel::instance()->findViaSlave($deal_info->user_id, 'id,money,real_name');
                    $userBatchLoanInfos[$deal_info->user_id]['total_money'] = $user_info->money;
                    $userBatchLoanInfos[$deal_info->user_id]['total_fee'] = 0;
                    $userBatchLoanInfos[$deal_info->user_id]['enough'] = true;
                    $userBatchLoanInfos[$deal_info->user_id]['user_real_name'] = $user_info->real_name;
                    $userBatchLoanInfos[$deal_info->user_id]['fail_deal_names'] = array();
                }
                $deal_ext_info = DealExtModel::instance()->getDealExtByDealId(intval($deal_id));
                if (!in_array($deal_ext_info->loan_type, array(UserCarryModel::LOAN_AFTER_CHARGE, UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN))){
                    continue;
                }
                $userBatchLoanInfos[$deal_info->user_id]['fail_deal_names'][] = $deal_info->name;
                $fee = $deal_info->getAllFee($deal_id);
                $fee_sum = Finance::addition(array($fee['loan_fee'], $fee['consult_fee'], $fee['guarantee_fee'], $fee['pay_fee'], $fee['manage_fee']));
                $userBatchLoanInfos[$deal_info->user_id]['total_fee'] = bcadd($userBatchLoanInfos[$deal_info->user_id]['total_fee'],$fee_sum,2);
                if(bccomp($userBatchLoanInfos[$deal_info->user_id]['total_fee'],$userBatchLoanInfos[$deal_info->user_id]['total_money'],2) == 1) {
                    $userBatchLoanInfos[$deal_info->user_id]['enough'] = false;
                    unset($deal_ids[$k]);
                    $failDealIds[] = $deal_id;
                }
            }

            foreach ($deal_ids as $deal_id) {
                $vo = M('Deal')->where(array('is_delete' => 0, 'id' => $deal_id))->find();
                $deal_ext_info = DealExtModel::instance()->getDealExtByDealId(intval($deal_id));
                if($userBatchLoanInfos[$vo['user_id']]['enough'] == false) {
                    if (in_array($deal_ext_info->loan_type, array(UserCarryModel::LOAN_AFTER_CHARGE, UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN))){
                        $failDealIds[] = $deal_id;
                        continue;
                    }
                }

                if($deal_ext_info->loan_type == UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN) {
                    //检查是否满足收费后先计息后放款条件
                    if(!$this->_checkLoanAfterChargeLaterLoan($deal_id,false)) {
                        $failDealIds[] = $deal_id;
                        continue;
                    }
                }

                $vo['isDtb'] = 0;
                $dealService = new DealService();
                if($dealService->isDealDT($deal_id)){
                    $vo['isDtb'] = 1;
                }

                try {
                    $dealService = new \core\service\DealService();
                    $dealService->isOKForMakingLoans($vo);
                    $role = $this->getRole();
                    $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_LOAN, 'service_id' => $deal_id))->find();
                    if ($role != 'b' || $_REQUEST['agree'] != 1) {
                        $auditRes = $this->audit($vo, $role, $audit);
                        if ($auditRes == 0) {
                            throw new \Exception("审核异常");
                        }
                    }
                } catch (\Exception $e) {
                    $failDealIds[] = $deal_id;
                    save_log('提交放款失败 deal_id:'.$deal_id." ".$e->getMessage() , C('SUCCESS'),'', '', C('SAVE_LOG_FILE'));
                }
            }

            $return['succ_num'] = $total_deal_num - count($failDealIds);
            $return['fail_num'] = count($failDealIds);
            $return['deal_ids'] = implode(",",$failDealIds);

            $fail_batch_info_arr = array();
            foreach ($userBatchLoanInfos as $userBatchLoanInfo) {
                if($userBatchLoanInfo['enough'] == false) {
                    $fail_batch_info_arr[] = sprintf("%s，放款类型为收费后放款或收费后先计息后放款，%s网信账户余额不足",implode('; ',$userBatchLoanInfo['fail_deal_names']),$userBatchLoanInfo['user_real_name']);
                }
            }
            $return['fail_batch_info'] = implode(',',$fail_batch_info_arr);

            ajax_return($return);
        }
    }

    public function batch_qnqueue() {
        $deal_ids = trim($_POST['deal_ids']);
        $return = array(
            'status'=> 1,
            'msg' =>'success',
            'data'=>array(),
        );

        if(empty($deal_ids)) {
            $return['status'] = 0;
            $return['msg'] = 'error params';
            ajax_return($return);
        }
        $deal_ids = explode(",", $deal_ids);
        $total_deal_num = count($deal_ids);
        $failDealIds = array();
        $userBatchLoanInfos = array();
        foreach ($deal_ids as $k=> $deal_id) {
            $deal_info = DealModel::instance()->findViaSlave(intval($deal_id));
            if(!isset($userBatchLoanInfos[$deal_info->user_id])) {
                $user_info = UserModel::instance()->findViaSlave($deal_info->user_id, 'id,money,real_name');
                $userBatchLoanInfos[$deal_info->user_id]['total_money'] = $user_info->money;
                $userBatchLoanInfos[$deal_info->user_id]['total_fee'] = 0;
                $userBatchLoanInfos[$deal_info->user_id]['enough'] = true;
                $userBatchLoanInfos[$deal_info->user_id]['user_real_name'] = $user_info->real_name;
                $userBatchLoanInfos[$deal_info->user_id]['fail_deal_names'] = array();
            }
            $deal_ext_info = DealExtModel::instance()->getDealExtByDealId(intval($deal_id));
            if (!in_array($deal_ext_info->loan_type, array(UserCarryModel::LOAN_AFTER_CHARGE, UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN))){
                continue;
            }
            $userBatchLoanInfos[$deal_info->user_id]['fail_deal_names'][] = $deal_info->name;
            $fee = $deal_info->getAllFee($deal_id);
            $fee_sum = Finance::addition(array($fee['loan_fee'], $fee['consult_fee'], $fee['guarantee_fee'], $fee['pay_fee'], $fee['manage_fee']));
            $userBatchLoanInfos[$deal_info->user_id]['total_fee'] = bcadd($userBatchLoanInfos[$deal_info->user_id]['total_fee'],$fee_sum,2);
            if(bccomp($userBatchLoanInfos[$deal_info->user_id]['total_fee'],$userBatchLoanInfos[$deal_info->user_id]['total_money'],2) == 1) {
                $userBatchLoanInfos[$deal_info->user_id]['enough'] = false;
                unset($deal_ids[$k]);
                $failDealIds[]=$deal_id;
            }
        }

        $_REQUEST['agree'] = 1;
        $dealService = new \core\service\DealService();
        foreach ($deal_ids as $deal_id) {

            $vo = M('Deal')->where(array('is_delete' => 0, 'id' => $deal_id))->find();
            $deal_ext_info = DealExtModel::instance()->getDealExtByDealId(intval($deal_id));
            if($userBatchLoanInfos[$vo['user_id']]['enough'] == false) {
                if (in_array($deal_ext_info->loan_type, array(UserCarryModel::LOAN_AFTER_CHARGE, UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN))){
                    $failDealIds[] = $deal_id;
                    continue;
                }
            }

            if($deal_ext_info->loan_type == UserCarryModel::LOAN_AFTER_CHARGE_LATER_LOAN) {
                //检查是否满足收费后先计息后放款条件
                if(!$this->_checkLoanAfterChargeLaterLoan($deal_id,false)) {
                    $failDealIds[] = $deal_id;
                    continue;
                }
            }

            $vo['isDtb'] = 0;
            $dealService = new DealService();
            if($dealService->isDealDT($vo['id'])){
                $vo['isDtb'] = 1;
            }

            $role = 'b';
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_LOAN, 'service_id' => $deal_id))->find();
            if ($role != 'b' || $_REQUEST['agree'] != 1) {
                $auditRes = $this->audit($vo, $role, $audit);
                if ($auditRes == 0) {
                    $failDealIds[]=$deal_id;
                    continue;
                }
            }



            //放款添加到jobs
            if(!$dealService->isP2pPath(intval($deal_id))) {
                $function = '\core\service\DealService::makeDealLoansJob';
                $param = array('deal_id' => $deal_id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']);
            }else{
                $orderId = Idworker::instance()->getId();
                $function = '\core\service\P2pDealGrantService::dealGrantRequest';
                $param = array(
                    'orderId' => $orderId,
                    'dealId'=>$deal_id,
                    'param' => array('deal_id' => $deal_id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']),
                );
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$deal_id);
            }

            $GLOBALS['db']->startTrans();
            try {
                $auditRes = $this->audit($vo, $role, $audit);
                if (!$auditRes) {
                    throw new \Exception("AB角审核失败");
                }
                //如果没有设置放款时间，则添加默认的放款时间
                $vo['deal_status'] = 4; //设置状态为放款中

                $vo['repay_start_time'] = to_timespan(date("Y-m-d")); // 批量提交都以当前时间作为放款时间


                if(intval($vo['next_repay_time']) == 0){
                    $delta_month_time = get_delta_month_time($vo['loantype'], $vo['repay_time']);

                    // 按天一次到期
                    if($vo['loantype'] == 5){
                        $vo['next_repay_time'] = next_replay_day_with_delta($vo['repay_start_time'], $delta_month_time);
                    }else{
                        $vo['next_repay_time'] = next_replay_month_with_delta($vo['repay_start_time'], $delta_month_time);
                    }
                }

                $isSaved = M(MODULE_NAME)->save($vo);
                if(!$isSaved) {
                    throw new \Exception("修改标的状态或者放款时间错误");
                }

                //成功提示
                syn_deal_status($vo['id']);
                syn_deal_match($vo['id']);

                //更新项目信息
                $deal_pro_service = new DealProjectService();
                $deal_pro_service->updateProBorrowed($vo['project_id']);
                $deal_pro_service->updateProLoaned($vo['project_id']);

                $job_model = new \core\dao\JobsModel();
                $job_model->priority = 99;
                //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
                $add_job = $job_model->addJob($function, $param, get_gmtime() + 180);
                if (!$add_job) {
                    throw new \Exception("放款任务添加失败");
                }
                //更新标放款状态
                $deal_model = new DealModel();
                $save_status = $deal_model->changeLoansStatus($deal_id, 2);
                if (!$save_status) {
                    throw new \Exception("更新标放款状态 is_has_loans 失败");
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $failDealIds[]=$deal_id;
                save_log('放款失败'.$deal_id, $e->getMessage(), '', '', C('SAVE_LOG_FILE'));
                continue;
            }
            //log
            if($add_job) {
                $data = array(
                    "job_id"    =>  $add_job,
                    "function"  =>  $function,
                    "param" =>  $param,
                );
                save_log('放款'.$vo['name'].L("INSERT_SUCCESS"), C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));
            }
        }
        $return['succ_num'] = $total_deal_num - count($failDealIds);
        $return['fail_num'] = count($failDealIds);
        $return['deal_ids'] = implode(",",$failDealIds);

        $fail_batch_info_arr = array();
        foreach ($userBatchLoanInfos as $userBatchLoanInfo) {
            if($userBatchLoanInfo['enough'] == false) {
                $fail_batch_info_arr[] = sprintf("%s，放款类型为收费后放款或收费后先计息后放款，%s网信账户余额不足",implode('; ',$userBatchLoanInfo['fail_deal_names']),$userBatchLoanInfo['user_real_name']);
            }
        }
        $return['fail_batch_info'] = implode(',',$fail_batch_info_arr);

        ajax_return($return);
    }


    /**
     * 专享项目正常还款
     * @param $id 项目ID
     * @return json 返回结果
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/
    public function repayProject()
    {
        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $negativeIds = app_conf('PROJECT_REPAY_NEGATIVE');
        if($negativeIds){
            $negativeIds = explode(',',$negativeIds);
        }
        $id = intval($_REQUEST['project_id']);
        $canNegative = in_array($id,$negativeIds) ?  1 : 0;
        $repayType = intval($_REQUEST['repay_user_type']) <> 0 ? intval($_REQUEST['repay_user_type']):0;

        //验证标的状态
        $status = DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying'];
        $dealType = DealModel::DEAL_TYPE_EXCLUSIVE;
        $condition = "id = $id  AND deal_type = $dealType AND fixed_value_date > 0 AND business_status = $status";
        $project = D('DealProject')->where($condition)->find();

        if(empty($project)){
            $this->error("项目不存在或项目状态异常!");
        }

        $deals = DealModel::instance()->getDealByProId($project['id'],array(4));

        //获取该项目所有还款中的标的

        $dealLoanType = new DealLoanTypeModel();
        $dealRepayModel = new DealRepayModel();

        $rows = $dealRepayModel->getProjectDealRepay($project['id']);
        if(empty($rows)){
            $this->error("没有需要还款的记录!");
        }

        $dealAgency = new DealAgencyModel();
        $user = new UserModel();
        $repayMoney = 0;
        foreach($rows as $row){
            if(empty($user_info)){
                $dealInfo = DealModel::instance()->find($row['deal_id']);
                $dealTag = $dealLoanType->getLoanTagByTypeId($dealInfo['type_id']);
                if(($dealTag == DealLoanTypeModel::TYPE_XFFQ)||($dealTag == DealLoanTypeModel::TYPE_XFD) || ($dealTag == DealLoanTypeModel::TYPE_ZHANGZHONG)){
                    if($dealInfo['advance_agency_id'] > 0){
                        $dealAgencyInfo = $dealAgency->find($dealInfo['advance_agency_id']);
                        $userInfo = $user->find($dealAgencyInfo['user_id']);
                        $repayType = 1;
                    }else{
                        $this->error("消费分期/消费贷标的未设置代垫机构!");
                    }
                }else{
                    if($repayType <> 0){
                        $repayUserId = DealService::getRepayUserAccount($row['deal_id'],$repayType);
                        if($repayType == 2){
                            $userType = "代偿";
                        }else if($repayType == 1){
                            $userType = "代垫";
                        }
                        $userInfo = $user->find($repayUserId);
                    }else{
                        $userType = "借款人";
                        $userInfo = $user->find($row['user_id']);
                    }
                }
            }

            $repayMoney += $row['repay_money'];
            $repayIds[] = array('repay_id'=>$row['id'],'deal_id'=>$row['deal_id']);
        }


        if(bccomp($userInfo['money'], $row['repay_money'], 2) == -1 && $canNegative == 0) { //余额不足 不进行强制还款
            $this->error($userType."账户余额不足!");
        }

        $admInfo = \es_session::get(md5(conf("AUTH_KEY")));
        try{
            $GLOBALS['db']->startTrans();
            $function = '\core\service\DealRepayService::projectRepay';
            $param = array('project_id' => $id, 'ignore_impose_money' => intval($_REQUEST['ignore_impose_money']), 'admin' => $admInfo,'negative'=>$canNegative,'repayType'=>$repayType, 'submitUid' => $audit['submit_uid'], 'auditType' => 3);

            $job_model = new JobsModel();
            $job_model->priority = 110;
            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }

            if(!$res) {
                throw new \Exception("改变标的还款状态失败");
            }

            if(!DealProjectModel::instance()->changeProjectStatus($project["id"],DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay'])){
                throw new \Exception("变更项目正在还款状态失败");
            }

            $audit = M('serviceAudit');
            $audit->status = ServiceAuditModel::AUDIT_SUCC;
            $audit->audit_uid = $admInfo['adm_id'];
            if (!$audit->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_PROJECT_REPAY, 'service_id' => $project_repay_id))->save()) {
                throw new \Exception("变更审核状态失败");
            }

            $GLOBALS['db']->commit();
            $this->success("操作成功", 0, '/m.php?m=DealProjectRepay&a=index&ref=1&role='. $_REQUEST['role']);
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            $this->error($ex->getMessage());
        }

    }

    /**
     * 资金用途标列表展示-合规临时方案
     */
    public function cash_used_info(){
        //jira:4308 贷款类型默认为专享
        $_REQUEST['deal_type'] = 0;

        //分类
        /* $cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $cate_tree = D("DealCate")->toFormatTree($cate_tree,'name');
        $this->assign("cate_tree",$cate_tree); */
        $this->assign('sitelist', $GLOBALS['sys_config']['TEMPLATE_LIST']);

        //开始加载搜索条件
        $map['is_delete'] = 0;
        $map['publish_wait'] = 0;
        //非利滚利项目
        $deal_type = $this->getDealType();
        $map['deal_type'] = $deal_type;
        $this->assign('deal_type', $deal_type);

        if(intval($_REQUEST['id'])>0){
            $map['id'] = intval($_REQUEST['id']);
        }

        if(trim($_REQUEST['name'])!=''){
            $name = addslashes(trim($_REQUEST['name']));
            $map['name'] = array('like','%'.$name.'%');
        }

        /* if(intval($_REQUEST['cate_id'])>0)
        {
            FP::import("libs.utils.child");
            $child = new Child("deal_cate");
            $cate_ids = $child->getChildIds(intval($_REQUEST['cate_id']));
            $cate_ids[] = intval($_REQUEST['cate_id']);
            $map['cate_id'] = array("in",$cate_ids);
        } */

        if(intval($_REQUEST['site_id']) > 0){
            $sql  ="select deal_id from ".DB_PREFIX."deal_site where site_id =".intval($_REQUEST['site_id']);

            $id_res = $GLOBALS['db']->get_slave()->getAll($sql);
            $id_arr = array();
            $ids = '';
            foreach($id_res as $dealid){
                $id_arr[] = $dealid['deal_id'];
            }
            if($id_arr){
                $ids = implode(',', $id_arr);
            }
            $map['id'] = array("in",$ids);
        }


        if(trim($_REQUEST['real_name'])!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%" . $real_name . "%'";

            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $map['user_id'] = array("in",$ids);
        }

        $map['deal_status'] = array("eq",4);

        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` like '%" . trim($_REQUEST['project_name']) . "%')";
        }

        // 放款审批单编号
        if (!empty($_REQUEST['approve_number'])) {
            $map['approve_number'] = array('eq', addslashes(trim($_REQUEST['approve_number'])));
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        $name=$this->getActionName();
        $model = DI ($name);

        $userIDArr = array();
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
            foreach($list as $k=>$v){
                $list[$k]['ecid'] = Aes::encryptForDeal($v['id']);
                $userIDArr[] = $v['user_id'];
            }
            $this->assign('list', $list);
        }

        // JIRA#3260 企业账户二期 <fanjingwen@>
        // 获取借款人相关的基本信息
        $userServ = new UserService();
        $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
        $this->assign('listOfBorrower', $listOfBorrower);
        // -------------- over -----------------

        $redis = SiteApp::init()->dataCache->getRedisInstance();
        if($cashUsedMapString = $redis->get('bid_cash_used_info_map')){
            $this->assign ('cashUsedMap', json_decode($cashUsedMapString,true));
        }else{
            $this->assign ('cashUsedMap', array());
        }


       // var_dump( json_decode($cashUsedMapString,true));exit;
        $this->assign("cashUsedInfoValues",array(1=>'借款人已按既定的资金用途使用资金',2=>'借款人未按照既定资金用途使用资金，但未发现不利于还款的因素',3=>'该项目金额低于1万元（含），不对资金用途进行复核',4=>'其他'));
        $this->display ();
        return;
    }
    /**
     * 资金用途编辑-合规临时方案
     */
    public function edit_cash_used_info() {
        $userInfo = array();
        $id = intval($_REQUEST['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $this->deal = $vo = M(MODULE_NAME)->where($condition)->find();

        if (!$vo) {
            $this->error('获取标的信息失败');
        }

        //获得当前标的tag信息   add by  zhanglei5 2014/08/27
        $deal_tag_service = new DealTagService();
        $tags =  $deal_tag_service->getTagByDealId($id);
        $vo['tags'] = implode(',',$tags);
        $vo['start_time'] = $vo['start_time']!=0?to_date($vo['start_time']):'';
        $vo['bad_time'] = $vo['bad_time']!=0?to_date($vo['bad_time']):'';
        $vo['repay_start_time'] = $vo['repay_start_time']!=0?to_date($vo['repay_start_time'],"Y-m-d"):'';
        $usergroupList = M("UserGroup")->select();

        $this->assign ( 'usergroupList', $usergroupList );

        if($vo['deal_status'] ==0){
            $level_list = load_auto_cache("level");
            $u_level = M("User")->where("id=".$vo['user_id'])->getField("level_id");
            $vo['services_fee'] = $level_list['services_fee'][$u_level];
        }

        if($vo['manage_fee_text'] === ''){
            $vo['manage_fee_text'] = '年化，收益率计算中已包含此项，不再收取。';
        }

        $group = M("DealGroup")->where(array('deal_id'=>$id))->select();

        if($group){
            $t_group = array();
            foreach ($group as $row){
                $t_group[] = $row['user_group_id'];
            }
            $vo['user_group'] = $t_group;
        }

        if ($vo['deal_type'] == DealProjectService::DEAL_TYPE_LGL) {
            //利滚利标
            $compound_service = new DealCompoundService();
            $deal_compound = $compound_service->getDealCompound($id);
            $vo['lock_period'] = $deal_compound['lock_period'];
            $vo['redemption_period'] = $deal_compound['redemption_period'];
            $vo['rate_day'] = $compound_service->convertRateYearToDay($vo['rate'], $vo['redemption_period'], true);
            $vo['compound_id'] = $deal_compound['id'];
            $vo['end_date'] = $deal_compound['end_date'] ? to_date($deal_compound['end_date'], "Y-m-d") : "";
        }

        // JIRA#1108 计算还款期数
        $deal_model = \core\dao\DealModel::instance()->find($vo['id']);
        $this->assign('repay_times', $deal_model->getRepayTimes());


        //订单扩展信息
        $deal_ext = M("DealExt")->where(array('deal_id' => $id))->find();
        // 计算服务费
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $vo['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        }
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);
        $management_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['management_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);
        $management_fee = $deal_model->floorfix($vo['borrow_amount'] * $management_fee_rate / 100.0);

        $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);
        $this->assign("management_fee", $management_fee);

        //用户信息处理
        if(!empty($vo['user_id'])) {
            $userInfo = M('User')->where('id='.intval($vo['user_id']))->find();
            if(!empty($userInfo)) {
                $userInfo['audit'] = M('UserBankcard')->where('user_id='.$userInfo['id'])->find();
            }
        }

        if(trim($_REQUEST['type'])=="deal_status"){
            $this->display ("Deal:deal_status");
            exit();
        }

        //处理旧标的 借款保证人信息
        if(!empty($vo)){
            $where = ' where deal_id = '.$id;
            $guarantor = M('deal_guarantor')->query("select id,name,to_user_id,status from ".DB_PREFIX."deal_guarantor $where");
            $status_name = $GLOBALS['dict']['DEAL_GUARANTOR_STATUS'];
            foreach($guarantor as $k=>$v){
                $guarantor[$k]['status_name'] = $status_name[$v['status']];
            }
            $this->assign("guarantor",$guarantor);
        }




        //借款用途
        $deal_type_tree = $this->getDealLoanTypeList();
        $this->assign("deal_type_tree",$deal_type_tree);
        $loan_type_info = M("Deal_loan_type")->where("id = ".$vo['type_id'])->find();
        $this->assign("loan_type_info",$loan_type_info);

        //从配置文件取公用信息
        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);        //还款方式
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        //合同类型
        FP::import("libs.common.app");

        //$contract_tpl_type = get_contract_type();
        $contract_service = new \core\service\ContractService();
        $contract_tpl_type = $contract_service->getContractType();
        if(!isset($contract_tpl_type[$vo['contract_tpl_type']])){
            $contract_tpl_type[$vo['contract_tpl_type']] = M('MsgCategory')->where(array('type_tag' => $vo['contract_tpl_type']))->getField('type_name');
        }

        $tplRequest = new RequestGetCategorys();

        $tplRequest->setIsDelete(0);
        $tplResponse = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategorys",
            'args' => $tplRequest,
        ));

        if(!is_array($tplResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }

        $this->assign('contract_tpl_type', $tplResponse->list);    //合同类型

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);

        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = get_sites_template_list();
        $deal_site_list = get_deal_site($id);
        $this->assign('site_list', $site_list);
        $this->assign('deal_site_list', $deal_site_list);

        $deal_ext['start_loan_time'] = $deal_ext['start_loan_time'] == 0 ? '' : to_date($deal_ext['start_loan_time']);
        $deal_ext['first_repay_interest_day'] = $deal_ext['first_repay_interest_day'] == 0 ? '' : to_date($deal_ext['first_repay_interest_day'], "Y-m-d");
        $deal_ext['base_contract_repay_time'] = $deal_ext['base_contract_repay_time'] == 0 ? '' : to_date($deal_ext['base_contract_repay_time'], "Y-m-d");

        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);
        // 增加变现通特殊逻辑
        if( $vo['type_id'] == $bxtTypeId ){
            $deal_ext['max_rate'] = sprintf("%.5f",$deal_ext['max_rate']);
        }
        if($vo['deal_crowd'] == DealModel::DEAL_CROWD_SPECIFY_USER) {
            $specify_uid_info = M('User')->where('id='.intval($deal_ext['deal_specify_uid']))->find();
            $this->assign('specify_uid_info',$specify_uid_info);
        }

        $this->assign('deal_ext', $deal_ext);
        // 标的优惠码设置信息
        $deal_coupon = M("CouponDeal")->where(array('deal_id' => $id))->find();
        $this->assign("deal_coupon",$deal_coupon);
        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();

        if($project){
            $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);
            $this->assign ( 'pro', $project );
        }

        // JIRA#3271 平台产品名称定义 2016-03-29 <fanjingwen@ucfgroup.com>
        $vo['prefix_title'] = $deal_ext['deal_name_prefix'];
        $idStr = str_pad(strval($id), 9, strval(0), STR_PAD_LEFT);
        $vo['main_title'] = $project['name'] . 'A' . $idStr;
        // ----------------- over ----------------
        $redis = SiteApp::init()->dataCache->getRedisInstance();
        if($cashUsedMapString = $redis->get('bid_cash_used_info_map')){
            $this->assign ('cashUsedMap', json_decode($cashUsedMapString,true));
        }else{
            $this->assign ('cashUsedMap', array());
        }


        $this->assign ('vo', $vo);
        $this->assign("userInfo",$userInfo);
        $this->assign("cashUsedInfoValues",array(1=>'借款人已按既定的资金用途使用资金',2=>'借款人未按照既定资金用途使用资金，但未发现不利于还款的因素',3=>'该项目金额低于1万元（含），不对资金用途进行复核',4=>'其他'));

        $this->display ();
    }
    /**
     * 资金用途保存-合规临时方案
     */
    public function save_cash_used_info(){
        if(empty($_GET['id'])||empty($_GET['cashUsedValue'])){
            $data = array('status' => 1, 'data'=> '保存失败，参数不正确!');
            ajax_return($data);
        }

        $redis = SiteApp::init()->dataCache->getRedisInstance();
        if (empty($redis)) {
            $data = array('status' => 1, 'data'=> '服务异常，请稍后再试!');
        }else{
            $cashUsedMapString = $redis->get('bid_cash_used_info_map');
            if(empty($cashUsedMapString)){
                $redis->set('bid_cash_used_info_map',json_encode(array($_GET['id']=>$_GET['cashUsedValue'])));
            }else{
                $cashUsedMap=json_decode($cashUsedMapString,true);
                $cashUsedMap[$_GET['id']]=$_GET['cashUsedValue'];
                $redis->set('bid_cash_used_info_map',json_encode($cashUsedMap));
            }
            $data = array('status' => 1, 'data'=> '保存成功!');
        }
        ajax_return($data);
    }

    /**
     * 操作批量变更页面
     */
    public function batch_update()
    {
        $this->assign('main_title', '批量更新标的信息');
        $templet = $this->is_cn ? 'batch_update_cn' : 'batch_update';
        $this->display($templet);
    }

    /**
     * 导出批量修改 csv 模板
     */
    function get_batch_update_csv_tpl()
    {
        header('Content-Type: text/csv;charset=utf8');
        header("Content-Disposition: attachment; filename=batch_update_deal_template.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');
        if ($this->is_cn) {
            $title = array('标id', '所属队列', '管理机构', 'tag', '借款金额', '最低金额', '年化基本利率', '借款年利率', '年化出借人利率', '年化借款平台手续费',
            '年化借款咨询费', '年化借款担保费', '年化支付服务费', '年化管理服务费', '年化管理服务费收取方式', '合同类型', '借款状态', '状态', '所属网站');
        } else {
            $title = array('标id', '所属队列', '管理机构', 'tag', '借款金额', '最低起投金额', '年化收益基本利率', '借款年利率', '年化
出借人收益率', '年化借款平台手续费',
            '年化借款咨询费', '年化借款担保费', '年化支付服务费', '年化管理服务费', '年化管理服务费收取方式', '合同类型', '借款状态', '状态', '所属网站');

        }
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }

        fputcsv($fp, $title);
        exit;
    }

    /**
     * 执行批量变更
     */
    public function do_batch_update()
    {
        try {
            // 判断是否为 csv
            $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
            if(!in_array($_FILES['batch_update_file']['type'], $mimes)) {
                throw new \Exception('上传文件需为 csv 格式！');
            }

            if (false === ($handle = fopen($_FILES['batch_update_file']['tmp_name'], 'r'))) {
                throw new \Exception('csv 文件打开失败！');
            }

            $success_collection = array();
            $fail_collection = array();
            $is_header = true;
            $dealQueueModel = new DealQueueModel();
            $dealQueueInfoModel = new DealQueueInfoModel();
            while(false !== ($data = fgetcsv($handle))) {
                // 固定标的修改信息只能是19列
                if (19 != count($data)) {
                    throw new \Exception('数据格式错误：此 csv 只能是19列！');
                }

                foreach($data as $k => $v){
                    $data[$k] = iconv('gbk', 'utf-8', trim($v));
                }

                // 约定第一行为表头，跳过
                if ($is_header) {
                    $is_header = false;
                    continue;
                }

                list($new_data['id'],
                    $new_data['queue_id'], // 自动上标队列
                    $new_data['management_agency_id'], // 管理机构
                    $new_data['deal_tags'],
                    $new_data['borrow_amount'],
                    $new_data['min_loan_money'],
                    $new_data['income_base_rate'], // 年化收益基本利率
                    $new_data['rate'],
                    $new_data['income_fee_rate'], // 年化出借人收益率
                    $new_data['loan_fee_rate'], // 平台费
                    $new_data['consult_fee_rate'], // 咨询费
                    $new_data['guarantee_fee_rate'], // 担保费
                    $new_data['pay_fee_rate'], // 支付费
                    $new_data['management_fee_rate'], // 管理费
                    $new_data['management_fee_rate_type'], // 管理费收费方式
                    $new_data['contract_tpl_type'], // 合同类型
                    $new_data['deal_status'], // 借款状态
                    $new_data['is_effect'], // 状态
                    $new_data['deal_site'], // 所属网站
                ) = $data; // 赋值给对应项
                if (empty($new_data['id'])) {
                    Logger::error(sprintf('标的id 为空，，参数：%s，file：%s, line:%s', json_encode($new_data), __FILE__, __LINE__));
                    continue;
                }

                $deal_obj = DealModel::instance()->find($new_data['id']);
                if (empty($deal_obj)) {
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'deal_id'=>$new_data['id'],
                        'fail_msg'=>'标的不存在',
                    );
                    continue;
                }
                // 标的不是等待确认时，无法修改合同类型
                if(!empty($new_data['contract_tpl_type']) && ($deal_obj->deal_status != DealModel::$DEAL_STATUS['waiting'])){
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'deal_id'=>$new_data['id'],
                        'fail_msg'=>'标的不是等待确认，无法修改合同类型',
                    );
                    continue;
                }
                //判断标的“投资记录”是否为“空”
                $totalBid = DealLoadModel::instance()->getLoadCount($new_data['id']);
                if($totalBid['buy_count'] > 0) {
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'deal_id'=>$new_data['id'],
                        'fail_msg'=>'投资记录不为空',
                    );
                    continue;
                }

                //判断标的“投资限定条件 1” 是否为“全部用户”
                if($deal_obj->deal_crowd != DealModel::DEAL_CROWD_ALL) {
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'deal_id'=>$new_data['id'],
                        'fail_msg'=>'投资限定条件1不满足变更条件',
                    );
                    continue;
                }

                //当需要变更“最低起投金额”时，需进行以下判断
                if(bccomp($new_data['min_loan_money'] , $deal_obj->borrow_amount,2) == 1) {
                    //判断标的“最低起投金额”是否小于等于“借款金额”
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'deal_id'=>$new_data['id'],
                        'fail_msg'=>'标的的最低起投金额>借款金额',
                    );
                    continue;
                }

                $deal_ext_obj = DealExtModel::instance()->getDealExtByDealId($new_data['id']);

                // 如果标的 不是进行中 或 等待确认，只能修改所属网站；
                if (!in_array($deal_obj->deal_status, array(DealModel::$DEAL_STATUS['waiting'], DealModel::$DEAL_STATUS['progressing']))) {
                    $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$new_data['deal_site']];
                    if (empty($site_id)) {
                        Logger::error(sprintf('标的信息所属网站更新失败，标id：%d，失败原因：所属网站不存在 （%s），file：%s, line:%s', $new_data['id'], $new_data['deal_site'], __FILE__, __LINE__));
                        $fail_collection[] = array(
                            'id'=>count($fail_collection) + 1,
                            'deal_id'=>$new_data['id'],
                            'fail_msg'=>'所属网站不存在',
                        );
                    } else {
                        update_deal_site($new_data['id'], array($site_id));
                        Logger::info(sprintf('标的信息所属网站更新成功，标id：%d，所属网站：%d，file：%s, line:%s', $new_data['id'], $site_id, __FILE__, __LINE__));
                        $success_collection[] = $new_data['id'];
                    }
                    continue;
                } else {
                    // 标的信息需要多表更新 - 事务处理
                    try {
                        $GLOBALS['db']->startTrans();

                        $is_deal_modify = false;
                        $is_deal_ext_modify = false;
                        foreach ($new_data as $key => $value) {

                            // 先处理可以为空的值
                            if ('deal_tags' == $key) {
                                if(strlen($value) > 0) {
                                    $deal_tag_service = new DealTagService();
                                    if (false === $deal_tag_service->updateTag($new_data['id'], $value)) {
                                        throw new \Exception('标的 tag 更新失败！');
                                    }
                                } else {
                                    if (false === DealTagModel::instance()->deleteByDealId($new_data['id'])) {
                                        throw new \Exception('标的 tag 删除失败！');
                                    }
                                }
                                continue;
                            }

                            // 再处理不可以为空的值
                            if ('' === $value || is_null($value) || 'id' == $key) {
                                continue;
                            }

                            // 如果标的状态要从等待确认变成进行中，start_time 赋为当前时间
                            if (isset($new_data['deal_status']) && DealModel::$DEAL_STATUS['waiting'] == $deal_obj->deal_status && DealModel::$DEAL_STATUS['progressing'] == $new_data['deal_status']) {
                                $deal_obj->start_time = get_gmtime();
                            }

                            if ($deal_obj->offsetExists($key)) {
                                $is_deal_modify = true;
                                $deal_obj->$key = addslashes($value);
                            } elseif ($deal_ext_obj->offsetExists($key)) {
                                $is_deal_ext_modify = true;
                                $deal_ext_obj->$key = addslashes($value);
                            } elseif ('deal_site' == $key) { // 更新上标站点
                                $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$value];
                                if (empty($site_id)) {
                                    throw new \Exception('不存在对应的所属网站！');
                                } else {
                                    update_deal_site($new_data['id'], array($site_id));
                                }
                            }
                        }

                        // 报备
                        $deal_service = new DealService();
                        if($is_deal_modify && $deal_obj->deal_status == DealModel::$DEAL_STATUS['progressing'] && $deal_service->isNeedReportToBank($deal_obj->id)) { // 如果标的信息有变化，且状态为进行中，且是需要报备的标
                            Logger::info(sprintf('deal_report_request_start,deal_id:%d,function:%s,file:%s,line:%s', $deal_obj->id, __FUNCTION__, __FILE__, __LINE__));
                            $report_service = new P2pDealReportService();
                            $is_report_update = (1 == $deal_obj->report_status) ? true :false;
                            $report_service->dealReportRequest($deal_obj->getRow(), $is_report_update); // true or throw
                            Logger::info(sprintf('deal_report_request_end,deal_id:%d,function:%s,file:%s,line:%s', $deal_obj->id, __FUNCTION__, __FILE__, __LINE__));
                        }

                        // 此处保存一定要放到报备之后，否则在此隔离级别下，会读到刚才保存的新状态
                        if ($is_deal_modify && false === $deal_obj->save()) {
                            throw new \Exception('标的信息更新失败！');
                        }

                        if ($is_deal_ext_modify && false === $deal_ext_obj->updateByDealId()) {
                            throw new \Exception('标的扩展信息更新失败！');
                        }

                        //增删更改队列
                        if(strlen($new_data['queue_id']) > 0){
                            //查询是否在队列中
                            $queueInfo = $dealQueueInfoModel->getDealQueueByDealId(intval($new_data['id']));
                            //删除所属队列
                            if($new_data['queue_id'] === '0'){
                                if(!empty($queueInfo)){
                                    $result = $dealQueueModel->deleteDealQueue(intval($queueInfo['id']), intval($new_data['id']));
                                    if(!$result){
                                        throw new \Exception('变更队列：删除队列失败，标id:' . $new_data['id'] . '，队列id:' . $queueInfo['id']);
                                    }
                                }
                            }else{
                                //变更所属队列
                                //查询队列是否存在
                                $queue = $dealQueueModel->find($new_data['queue_id']);
                                if(empty($queue)){
                                    throw new \Exception('变更队列：加入队列失败，标id:' . $new_data['id'] . '，队列id:' . $new_data['queue_id'] . "队列不存在");
                                }
                                if(empty($queueInfo)){
                                    $result = $dealQueueModel->insertDealQueue($new_data['queue_id'],  $new_data['id']);
                                    if(!$result){
                                        throw new \Exception('变更队列：加入队列失败，标id:' . $new_data['id'] . '，队列id:' . $new_data['queue_id']);
                                    }
                                }else{
                                    if($new_data['queue_id'] != $queueInfo['id']){
                                        $result = $dealQueueModel->deleteDealQueue(intval($queueInfo['id']), intval($new_data['id']));
                                        if(!$result){
                                           throw new \Exception('变更队列：加入新队列。删除旧队列失败。标id:' . $new_data['id'] . '，旧队列id:' . $queueInfo['id']. '，新队列id:' . $new_data['queue_id']);
                                        }
                                        $result1 = $dealQueueModel->insertDealQueue($new_data['queue_id'],  $new_data['id']);
                                        if(!$result1){
                                            throw new \Exception('变更队列：加入新队列。加入新队列失败。标id:' . $new_data['id']. '，旧队列id:' . $queueInfo['id'] . '，新队列id:' . $new_data['queue_id']);
                                        }
                                    }
                                }
                            }
                        }

                        // 修改合同类型(为等待状态，并且不为空，才能修改合同类型。并且如果事务提交失败，则将合同类型再改为原来的。)
                        // 放在事务提交前，db操作失败则会立即回滚；
                        if($is_deal_modify && $deal_obj->deal_status == DealModel::$DEAL_STATUS['waiting'] && !empty($new_data['contract_tpl_type'])){
                            //合同服务更新标的模板分类ID
                            $contractRequest = new RequestUpdateDealCId();
                            $contractRequest->setDealId(intval($new_data['id']));
                            $contractRequest->setCategoryId(intval($new_data['contract_tpl_type']));
                            $contractRequest->setType(0);
                            $contractRequest->setSourceType($deal_obj->deal_type);

                            $contractResponse = $this->getRpc('contractRpc')->callByObject(array(
                                'service' => "\NCFGroup\Contract\Services\Category",
                                'method' => "updateDealCId",
                                'args' => $contractRequest,
                            ));

                            if($contractResponse->errorCode != 0){
                                throw new \Exception('合同类型更新失败！原因:'.$contractResponse->errorMsg);
                            }
                        }

                        $GLOBALS['db']->commit();
                        $success_collection[] = $new_data['id'];
                        Logger::info(sprintf('标的信息更新成功，更新内容：%s，file：%s, line:%s', json_encode($new_data), __FILE__, __LINE__));
                    } catch (\Exception $e) {
                        $GLOBALS['db']->rollback();

                        $fail_collection[] = array(
                            'id'=>count($fail_collection) + 1,
                            'deal_id'=>$new_data['id'],
                            'fail_msg'=>'标的信息更新失败',
                        );
                        Logger::error(sprintf('标的信息更新失败，标id：%d，失败原因：%s，file：%s, line:%s', $new_data['id'], $e->getMessage(), __FILE__, __LINE__));
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('%s，上传文件名：%s，file：%s, line:%s', $e->getMessage(), $_FILES['batch_update_file']['name'], __FILE__, __LINE__));
            $this->error($e->getMessage());
            return;
        }

        $this->assign('main_title', '标的信息更新结果');
        $this->assign('file_name', $_FILES['batch_update_file']['name']);
        $this->assign('success_count', count($success_collection));
        $this->assign('fail_count', count($fail_collection));
        $this->assign('fail_collection', $fail_collection);
        $this->display();
    }

    /**
     * 专享项目根据项目放款
     * @param $id 项目ID
     * @return json 返回结果
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/
    public function enqueueProject()
    {
        $result = $this->loanOneProjectFromAdmin(intval($_REQUEST['project_id']), $this->getRole(), intval($_REQUEST['agree']));
        ajax_return($result);
    }

    /**
     * 专享项目放款
     * @param int $id project_id
     * @param string $role 操作的角色 AB
     * @param 是否通过审核 $role 操作的角色
     * @return array
     **/
    private function loanOneProjectFromAdmin($id, $role, $agree = 0)
    {
        $status = DealProjectModel::$PROJECT_BUSINESS_STATUS['transfer_loans_audit'];
        $dealType = DealModel::DEAL_TYPE_EXCLUSIVE;
        $condition = "id = $id  AND deal_type = $dealType AND fixed_value_date > 0 AND business_status = $status";
        $project = D('DealProject')->where($condition)->find();

        // 合同
        $deal_contract_service = new DealContractService();
        if (!$deal_contract_service->isAllDealContracOfProjectSigned($id)) {
            $result['status'] = 0;
            $result['error_msg'] = '项目下存在未签署的标的合同';
            return $result;
        }

        $contractService = new ContractNewService();
        $signInfo = $contractService->getProjectContSignNum(intval($id),0,0);
        if(!$signInfo['is_sign_all']){
            $result['status'] = 0;
            $result['error_msg'] = "项目合同未签署完毕!";
            return $result;
        }

        if(to_date($project['fixed_value_date'],"Y-m-d") > to_date(get_gmtime(),"Y-m-d")){
            $result['status'] = 0;
            $result['error_msg'] = "还未到固定起息日,放款失败!";
            return $result;
        }

        if(empty($project)){
            $result['status'] = 0;
            $result['error_msg'] = "项目状态或项目起息日,项目合同模板配置异常!";
            return $result;
        }

        try {
            $dealService = new DealService();
            $dealService->isOKForZxMakingLoans($project);
        } catch (\Exception $e) {
            $ret['status'] = 0;
            $ret['error_msg'] = $e->getMessage();
            return $ret;
        }

        //a,b角审核
        $audit = D('serviceAudit')->where(array('service_type' => $this->getServiceType(true), 'service_id' => $id))->find();
        if ($role != 'b' || $agree != 1) { // a 角 审核  或者 b 角 退回
            $auditRes = $this->audit($project, $role, $audit, ServiceAuditModel::SERVICE_TYPE_PROJECT_LOAN, $id, $agree);
            if ($auditRes == 0) {
                $result['status'] = 0;
                $result['error_msg'] = "审核异常，请重试";
                return $result;
            }
            $result['status'] = $auditRes;
            $result['error_msg'] = "审核成功";
            return $result;
        }

        //按照项目进行放款操作
        $GLOBALS['db']->startTrans();
        try {
            $auditRes = $this->audit($project, $role, $audit, ServiceAuditModel::SERVICE_TYPE_PROJECT_LOAN, $id, $agree);
            if (1 == $auditRes) {
                $function = '\core\service\DealService::makeProjectLoansJob';
                $param = array('project_id' => $id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']);
                $jobModel = new \core\dao\JobsModel();
                $jobModel->priority = 109;
                $addJob = $jobModel->addJob($function, $param, get_gmtime() + 60);
                if (!$addJob) {
                    throw new \Exception("项目:$id,放款任务添加失败");
                }
            }
            $GLOBALS['db']->commit();
            $result['status'] = 1;

        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $result['status'] = 0;
            $result['error_msg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * 批处理项目放款 提交审核、放款
     * @param $_POST project_ids 逗号分隔的项目 id；role 本次处理类型：1：批量提交审核；2：批量放款
     */
    public function batchEnqueueProject()
    {
        $project_ids = trim(addslashes($_POST['project_ids']));
        $role = addslashes($_POST['role']); // 本次处理的类型
        $agree = intval('b' == $role);

        if(empty($project_ids)) {
            $return['status'] = 0;
            $return['msg'] = 'error params';
        } else {
            $project_id_arr = explode(",", $project_ids);

            $failed_id_arr = array();
            foreach ($project_id_arr as $project_id) {
                $res = $this->loanOneProjectFromAdmin($project_id, $role, $agree);
                if (0 == $res['status']) {
                    $failed_id_arr[] = $project_id;
                }
            }
            $return['status'] = 1;
            $return['fail_num'] = count($failed_id_arr);
            $return['succ_num'] = count($project_id_arr) - $return['fail_num'];
            $return['project_ids'] = implode(",",$failed_id_arr);
            if ($result['fail_num'] > 0) {
                save_log('提交放款失败 project_id:'.$return['project_ids']." ".$e->getMessage() , C('SUCCESS'),'', '', C('SAVE_LOG_FILE'));
            }
        }
        ajax_return($return);
    }

    /**
     * 用于显示指定项目下的标的列表
     */
    public function deals()
    {
        $map['project_id'] = intval($_REQUEST['project_id']);
        $user_id_arr = array();
        $list = $this->_list(DI('Deal'), $map);

        // 获取借款人相关的基本信息
        foreach ($list as $k => $v) {
            $user_id_arr[] = $v['user_id'];
        }
        $user_service = new UserService();
        $borrower_list = $user_service->getUserInfoListByID($user_id_arr);
        $this->assign('listOfBorrower', $borrower_list);

        $this->assign('list', $list);
        $this->assign('main_title', '项目标的列表');
        $this->display();
    }

    /**
     * mock 存管回调
     * m.php?m=Deal&a=spcallback&status=F&orderId=61653286624699381,61653326281842792&service=P2pDealRepayService&method=dealRepayCallBack
     */
    public function spcallback(){
        $service = trim($_REQUEST['service']);
        $method = trim($_REQUEST['method']);
        $orderIds = trim($_REQUEST['orderId']);
        $status = trim($_REQUEST['status']);

        if(!in_array($status,array('S','F'))){
            die('Error status!');
        }

        $orderIdArr = explode(",",$orderIds);
        foreach($orderIdArr as $orderId){
            $orderInfo = \core\service\P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo){
                die ('orderId:'.$orderId.' no exists');
            }
        }

        $class = "\core\service\\" . $service;
        if(!class_exists($class)){
            die('class no exists');
        }
        if(!method_exists($class,$method)){
            die('method no exists');
        }

        try{
            foreach($orderIdArr as $orderId){
                $res = (new $class())->$method($orderId,$status);
                var_dump($res);
            }
        }catch (\Exception $ex){
            var_dump($ex->getMessage());
            exit;
        }
    }

    public function manualDealCancel(){
        $orderId = trim($_REQUEST['orderId']);
        $dealId = trim($_REQUEST['dealId']);
        if(empty($orderId) || empty($dealId)){
            die("参数错误");
        }
        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'repay_id' => 0,
            'params' => '',
            'type' => \core\service\P2pDepositoryService::IDEMPOTENT_TYPE_CANCEL,
            'status' => \core\service\P2pIdempotentService::STATUS_SEND,
            'result' => \core\service\P2pIdempotentService::RESULT_WAIT,
        );
        $params = array(
            'bidId' =>  $dealId,
            'rpDirect' => '02', // 流标红包到投资人账户
        );
        $res =  \core\service\P2pIdempotentService::addOrderInfo($orderId,$data);
        if(!$res){
            var_dump("订单信息保存失败");
            exit;
        }
        echo "SUCCESS!";
        exit;
    }

    /**
     *批量修改定制用户
     */
    public function importCsvUserIds($deal_id,$is_delete=1) {
        if (empty ( $_FILES ['upfile'] ['name'] )) {
            return false;
        }
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            $this->error ( "请上传csv格式的文件！" );
        }
        $max_error_num = 30;
        $error_total_num = 0;
        $max_import_line = 5000;
        $i = 1;
        $error_str = '';
        $new_data = array ();
        $error_data = array ();
        // 获取登录人session
        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
        $csv_content = file_get_contents ( $_FILES ['upfile'] ['tmp_name'] );
        $csv_content = trim ($csv_content);
        if (empty ( $csv_content )) {
            $this->error ( '文件内容不能为空' );
        }
        $total_line = explode ( "\n", iconv ( 'GBK', 'UTF-8', $csv_content ) );
        // 统计去掉第一个行Title
        $count_total_line = count ( $total_line ) - 1;
        // 最后一行如果空行，不做计数
        if (empty ( $total_line [$count_total_line] )) {
            $count_total_line -= 1;
        }
        if ($count_total_line > $max_import_line) {
            $this->error ( '最大导入' . $max_import_line . '条数据' );
        }
        $correct = array ();
        $csv_row=array();
        $csv_data=array();
        if (($handle = fopen ( $_FILES ['upfile'] ['tmp_name'], "r" )) !== false) {
            if (fgetcsv ( $handle ) !== false) { // 第一行是标题不放到数据列表里
                while ( ($row_data = fgetcsv ( $handle )) !== false ) {
                    $error_msg = $this->check_csv_datas ( $row_data, $i );
                    if (! empty ( $error_msg ['error_msg'] )) {
                        $error_total_num ++;
                        $error_str .= $error_msg ['error_msg'];
                        unset ( $error_msg );
                        $i ++;
                        continue;
                    }
                    if (! empty ( $error_str )) {
                        $error_data = explode ( ',', $error_str );
                    }else{
                        $csv_row[$i]=$row_data[0];
                    }
                    $i ++;
                }
            }
            fclose ( $handle );
            @unlink ( $_FILES ['upfile'] ['tmp_name'] );
            if (!empty ( $error_str )) {
                $this->error($error_str);
            }
            // 更新数据
            if(empty($csv_row)){
                $this->error("可处理的数据为空");
            }else{
                $correct_user_id=array_unique($csv_row);
                foreach($correct_user_id as $key =>$value){
                    $csv_data['user_id']=$value;
                    $userModel = new \core\dao\UserModel ();
                    $userInfo=$userModel->find( intval($value));
                    $csv_data['user_name']=$userInfo['user_name'];
                    $csv_data['admin_id']=$adm_session ["adm_id"];
                    $csv_data['deal_id']=$deal_id;
                    $correct[$key]=$csv_data;
                }
                $dealCustomUserService = new \core\service\DealCustomUserService ();
                $result = $dealCustomUserService->insertInfo ( $correct,$deal_id,$is_delete );
                if (! $result) {
                    $this->error ( "操作失败" );
                }
                Logger::info(__CLASS__ . " | ". __FUNCTION__ ." | ".__LINE__." | "."import_csv_file_name : ".$_FILES ['upfile'] ['name']." | "."user_ids : ".json_encode($correct_user_id));
            }
        } else {
            $this->error ( "上传的文件不可读" );
        }
    }

    /**
     * 检查csv 数据
     * @param
     *
     */
    private function check_csv_datas($data, $line) {
        $ret = array (
                'user_id' => 0,
                'error_msg' => ''
                );
        $error_str='';
        $error_list = '';
        $error_array= array();
        // 判断会员ID是否为空
        if (empty ( $data [0] )) {
            $error_str .= ' 会员ID不能为空';
            foreach ( $data as $k => $v ) {
                $v = iconv ( 'gbk', 'utf-8', $v );
                $error_list .= $v . ',';
            }
        } else {
            $userModel = new \core\dao\UserModel ();
            $userInfo=$userModel->find( intval($data [0]));
            // 判断用户是否存在
            if (empty ( $userInfo )) {
                $error_str="用户不存在";
                foreach ( $data as $k => $v ) {
                    $v = iconv ( 'gbk', 'utf-8', $v );
                    $error_list .= $v . ',';
                }
            }
        }
        if (! empty ( $error_list )) {
            $error_list = $error_list .$error_str."\n";
        }
        $ret ['user_id'] = empty ( $userInfo ['id'] ) ? 0 : $userInfo ['id'];
        $ret ['error_msg'] = $error_list;
        return $ret;
    }

    public function view(){
        $dealId = intval($_REQUEST['id']);
        $dealCustomUserService = new \core\service\DealCustomUserService ();
        $result=$dealCustomUserService->getDealUserList($dealId);
        $userModel = new \core\dao\UserModel ();
        foreach($result as $key=>$value){
            $userInfo=$userModel->find($value['user_id']);
            $result[$key]['mobile']=$userInfo['mobile'];
            $user_num = numTo32($value['user_id']);
            if($userModel->isEnterpriseUser($value['user_id'])) {
                $user_num = numTo32Enterprise($value['user_id']);
            }

            $result[$key]['user_num']=$user_num;
        }
        $this->assign('user_info', $result);
        $this->assign('id',$dealId);
        $this->display();
    }

    /**
     * 清空单个标的下面批量导入用户
     */
    public function customUserDelAll(){

        $dealId = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $dealId))->find();

        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );

        if (empty($vo)){
            $this->error('获取标的信息失败',$ajax);
        }
        // 进行中和等待确认 可以操作
        if ($vo['deal_status'] != 1 && $vo['deal_status'] != 0){
            $this->error('标不是进行中或等待确认',$ajax);
        }
        // 如果不是导入用户选项不让删除
        if ($vo['deal_crowd'] != 34 ){
            $this->error('投资条件不是 批量导入可投用户',$ajax);
        }
        $dealcuUserService = new DealCustomUserService();
        $ret = $dealcuUserService->delAll($dealId);

        Logger::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$dealId.' '.$adm_session['adm_id'].' '.$ret);

        if (empty($ret)){
            $this->error('操作失败',$ajax);
        }


        $this->success('操作成功',$ajax);
    }
    public function deal_view() {
        $userInfo = array();
        $id = intval($_REQUEST['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $this->deal = $vo = M(MODULE_NAME)->where($condition)->find();

        if (!$vo) {
            $this->error('获取标的信息失败');
        }

        //获得当前标的tag信息   add by  zhanglei5 2014/08/27
        $deal_tag_service = new DealTagService();
        $tags =  $deal_tag_service->getTagByDealId($id);
        $vo['tags'] = implode(',',$tags);
        $vo['start_time'] = $vo['start_time']!=0?to_date($vo['start_time']):'';
        $vo['bad_time'] = $vo['bad_time']!=0?to_date($vo['bad_time']):'';
        $vo['repay_start_time'] = $vo['repay_start_time']!=0?to_date($vo['repay_start_time'],"Y-m-d"):'';
        $usergroupList = M("UserGroup")->select();

        $this->assign ( 'usergroupList', $usergroupList );

        if($vo['deal_status'] ==0){
            $level_list = load_auto_cache("level");
            $u_level = M("User")->where("id=".$vo['user_id'])->getField("level_id");
            $vo['services_fee'] = $level_list['services_fee'][$u_level];
        }

        if($vo['manage_fee_text'] === ''){
            $vo['manage_fee_text'] = '年化，收益率计算中已包含此项，不再收取。';
        }

        $group = M("DealGroup")->where(array('deal_id'=>$id))->select();

        if($group){
            $t_group = array();
            foreach ($group as $row){
                $t_group[] = $row['user_group_id'];
            }
            $vo['user_group'] = $t_group;
        }

        if ($vo['deal_type'] == DealProjectService::DEAL_TYPE_LGL) {
            //利滚利标
            $compound_service = new DealCompoundService();
            $deal_compound = $compound_service->getDealCompound($id);
            $vo['lock_period'] = $deal_compound['lock_period'];
            $vo['redemption_period'] = $deal_compound['redemption_period'];
            $vo['rate_day'] = $compound_service->convertRateYearToDay($vo['rate'], $vo['redemption_period'], true);
            $vo['compound_id'] = $deal_compound['id'];
            $vo['end_date'] = $deal_compound['end_date'] ? to_date($deal_compound['end_date'], "Y-m-d") : "";
        }

        // JIRA#1108 计算还款期数
        $deal_model = \core\dao\DealModel::instance()->find($vo['id']);
        $this->assign('repay_times', $deal_model->getRepayTimes());

        //订单扩展信息
        $deal_ext = M("DealExt")->where(array('deal_id' => $id))->find();

        // 计算服务费
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $vo['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        }
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        //功夫贷分期咨询费计算
        if($vo['consult_fee_period_rate'] > 0){
            $consult_fee_period = $deal_model->floorfix($vo['borrow_amount'] * $vo['consult_fee_period_rate'] / 100.0);
            $this->assign("consult_fee_period", $consult_fee_period);
        }

        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);
        $management_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['management_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);
        $management_fee = $deal_model->floorfix($vo['borrow_amount'] * $management_fee_rate / 100.0);

        $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);
        $this->assign("management_fee", $management_fee);

        //用户信息处理
        if(!empty($vo['user_id'])) {
            $userInfo = M('User')->where('id='.intval($vo['user_id']))->find();
            if(!empty($userInfo)) {
                $userInfo['audit'] = M('UserBankcard')->where('user_id='.$userInfo['id'])->find();
            }
        }

        if(trim($_REQUEST['type'])=="deal_status"){
            $this->display ("Deal:deal_status");
            exit();
        }

        //处理旧标的 借款保证人信息
        if(!empty($vo)){
            $where = ' where deal_id = '.$id;
            $guarantor = M('deal_guarantor')->query("select id,name,to_user_id,status from ".DB_PREFIX."deal_guarantor $where");
            $status_name = $GLOBALS['dict']['DEAL_GUARANTOR_STATUS'];
            foreach($guarantor as $k=>$v){
                $guarantor[$k]['status_name'] = $status_name[$v['status']];
            }
            $this->assign("guarantor",$guarantor);
        }

        //借款分类
        $deal_cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $deal_cate_tree = D("DealCate")->toFormatTree($deal_cate_tree,'name');
        $this->assign("deal_cate_tree",$deal_cate_tree);

        //担保机构
        $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1')->order('sort DESC')->findAll();
        $this->assign("deal_agency",$deal_agency);

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory",$deal_advisory);

        //支付机构
        $pay_agency = M("DealAgency")->where('is_effect = 1 and type=4')->order('sort DESC')->findAll();
        $this->assign("pay_agency",$pay_agency);

        //管理机构
        $management_agency = M("DealAgency")->where('is_effect = 1 and type=5 ')->order('id ASC')->findAll();
        $this->assign("management_agency",$management_agency);
        //代垫机构
        $advance_agency = M("DealAgency")->where('is_effect = 1 and type=6 ')->order('sort ASC')->findAll();
        $this->assign("advance_agency",$advance_agency);

        //受托机构
        $advance_agency = M("DealAgency")->where('is_effect = 1 and type=7 ')->order('id ASC')->findAll();
        $this->assign("entrust_agency",$advance_agency);

        //代充值机构
        $generation_recharge = M("DealAgency")->where('is_effect = 1 and type=8 ')->order('id ASC')->findAll();
        $this->assign("generation_recharge",$generation_recharge);

        // 交易所
        $jys = M("DealAgency")->where('is_effect = 1 and type=9 ')->order('id ASC')->findAll();
        $this->assign("jys",$jys);

        //渠道机构
        $canal_agency = M("DealAgency")->where('is_effect = 1 and type=10 ')->order('id ASC')->findAll();
        $this->assign("canal_agency",$canal_agency);

        //借款用途
        $deal_type_tree = $this->getDealLoanTypeList();
        $this->assign("deal_type_tree",$deal_type_tree);
        $loan_type_info = M("Deal_loan_type")->where("id = ".$vo['type_id'])->find();
        $this->assign("loan_type_info",$loan_type_info);

        //从配置文件取公用信息
        if ($this->is_cn) {
            $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式
        } else {
            $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);        //还款方式
        }
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        //合同类型
        FP::import("libs.common.app");

        //$contract_tpl_type = get_contract_type();
        $contract_service = new \core\service\ContractService();
        $contract_tpl_type = $contract_service->getContractType();
        if(!isset($contract_tpl_type[$vo['contract_tpl_type']])){
            $contract_tpl_type[$vo['contract_tpl_type']] = M('MsgCategory')->where(array('type_tag' => $vo['contract_tpl_type']))->getField('type_name');
        }

        $tplRequest = new RequestGetCategorys();
        $tplRequest->setIsDelete(0);
        if($this->is_cn){
            $tplRequest->setSourceType(0);  // 网贷
        }else{
            $tplRequest->setSourceType($vo['deal_type']); // 获取对应deal_type的合同分类列表
        }
        $tplRequest->setType(0); //0-p2p项目(包括网贷，交易所，专享，小贷)

        $tplResponse = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategorys",
            'args' => $tplRequest,
        ));
        if(!is_array($tplResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }
        $this->assign('contract_tpl_type', $tplResponse->list);    //合同类型

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);
        //限制vip等级
        $vipService = new VipService();
        $vipGrades = $vipService->getVipGradeList();
        $this->assign('vipGrades', $vipGrades);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);

        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = $this->is_cn ? $GLOBALS['sys_config']['TEMPLATE_LIST_CN'] : get_sites_template_list();
        $site_list = changeDealSite($site_list);
        $deal_site_list = get_deal_site($id);

        $this->assign('site_list', $site_list);
        $this->assign('deal_site_list', $deal_site_list);

        $deal_ext['start_loan_time'] = $deal_ext['start_loan_time'] == 0 ? '' : to_date($deal_ext['start_loan_time']);
        $deal_ext['first_repay_interest_day'] = $deal_ext['first_repay_interest_day'] == 0 ? '' : to_date($deal_ext['first_repay_interest_day'], "Y-m-d");
        $deal_ext['base_contract_repay_time'] = $deal_ext['base_contract_repay_time'] == 0 ? '' : to_date($deal_ext['base_contract_repay_time'], "Y-m-d");

        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);
        // 增加变现通特殊逻辑
        if( $vo['type_id'] == $bxtTypeId ){
            $deal_ext['max_rate'] = sprintf("%.5f",$deal_ext['max_rate']);
        }
        if($vo['deal_crowd'] == DealModel::DEAL_CROWD_SPECIFY_USER) {
            $specify_uid_info = M('User')->where('id='.intval($deal_ext['deal_specify_uid']))->find();
            $this->assign('specify_uid_info',$specify_uid_info);
        }

        $this->assign('deal_ext', $deal_ext);
        // 标的优惠码设置信息
        $deal_coupon = M("CouponDeal")->where(array('deal_id' => $id))->find();
        $this->assign("deal_coupon",$deal_coupon);
        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        $disabled_deal_crowd_34 = 0;
        if($project){
            $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);
            $project['business_status'] = intval($project['business_status']);
            $this->assign ( 'pro', $project );
        }else{
            if ($vo['deal_status'] != DealModel::$DEAL_STATUS['waiting'] &&  $vo['deal_status']!=DealModel::$DEAL_STATUS['progressing'] && $vo['deal_crowd'] == 34){
                $disabled_deal_crowd_34 = 1;
            }
        }

        $this->assign("disabled_deal_crowd_34",$disabled_deal_crowd_34);
        // JIRA#3271 平台产品名称定义 2016-03-29 <fanjingwen@ucfgroup.com>
        $vo['prefix_title'] = $deal_ext['deal_name_prefix'];
        $idStr = str_pad(strval($id), 9, strval(0), STR_PAD_LEFT);
        $vo['main_title'] = $project['name'] . 'A' . $idStr;
        // ----------------- over ----------------

        // JIRA#3260 企业账户二期 - 获取用户类型名称 <fanjingwen@ucfgroup.com>
        if (!empty($vo['user_id']) && !empty($userInfo)) {
            $userInfo['user_type_name'] = getUserTypeName($userInfo['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserModel::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userInfo['id']);
                $userInfo['company_name'] = getUserFieldUrl($userInfo, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
            } else {
                $userInfo['real_name'] = getUserFieldUrl($userInfo, UserModel::TABLE_FIELD_REAL_NAME);
            }
        }

        //借款客群
        if(($vo['loan_user_customer_type'] > 0) && array_key_exists($vo['loan_user_customer_type'],$GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'])){
            $vo['loan_user_customer'] = $GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'][$vo['loan_user_customer_type']];
        }
        // ----------------- over ----------------

        //利滚利分类标识
        $this->assign('lgl_tag', \core\dao\DealLoanTypeModel::TYPE_LGL);
        $this->assign('bxt_tag', \core\dao\DealLoanTypeModel::TYPE_BXT);
        $this->assign('dtb_tag', \core\dao\DealLoanTypeModel::TYPE_DTB);
        $this->assign('xffq_tag', \core\dao\DealLoanTypeModel::TYPE_XFFQ);
        $this->assign('zcgl_tag', \core\dao\DealLoanTypeModel::TYPE_GLJH);
        $this->assign('zzjr_tag', \core\dao\DealLoanTypeModel::TYPE_ZHANGZHONG);
        $this->assign('xsjk_tag', \core\dao\DealLoanTypeModel::TYPE_XSJK);
        $this->assign('xjdcdt_tag', \core\dao\DealLoanTypeModel::TYPE_XJDCDT);
        $this->assign ('vo', $vo);
        $this->assign("userInfo",$userInfo);
        $this->assign("project_business_status",DealProjectModel::$PROJECT_BUSINESS_STATUS);

        // 分期 平台手续费
        if (!empty($deal_ext['loan_fee_ext'])) {
            $loan_fee_arr =  json_decode($deal_ext['loan_fee_ext'], true);
            $this->assign("loan_fee_arr",$loan_fee_arr);
            $proxy_sale['loan_fee_sum'] = array_sum($loan_fee_arr);
            $proxy_sale['loan_first_rate'] = ceilfix($loan_fee_arr[0] / $proxy_sale['loan_fee_sum'] * $vo['loan_fee_rate'], 5);
            $proxy_sale['loan_last_rate'] = ceilfix($vo['loan_fee_rate'] - $proxy_sale['loan_first_rate'], 5);
            $proxy_sale['loan_rate_sum'] = $vo['loan_fee_rate'];
            $this->assign("proxy_sale", $proxy_sale);
        }
        $template = !empty($this->orgData) ? 'deal_view_org' : 'deal_view';
        $this->display ($template);
    }

    public function p2p_zone()
    {
        if (!empty($_POST)) {
            $result = \core\service\ncfph\DealService::setZoneTags($_POST['tag_names']);
            if ($result) {
                $this->success(L("UPDATE_SUCCESS"));
            }
        }
        $tagNames = \core\service\ncfph\DealService::getZoneTags();

        $this->assign('tag_names', $tagNames);
        $this->display();
    }

}

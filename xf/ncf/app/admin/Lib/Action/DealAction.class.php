<?php
/**
 *
 * 标的相关
 */

use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealTagModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealAgencyModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\ServiceAuditModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\dao\dealqueue\DealQueueModel;
use core\dao\repay\DealRepayModel;
use core\dao\deal\DealExtraModel;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\vfs\Vfs;
use libs\db\Db;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use core\enum\DealRepayEnum;
use core\enum\DealAgencyEnum;
use core\enum\ServiceAuditEnum;
use core\enum\UserEnum;
use core\enum\UserAccountEnum;
use core\enum\DealProjectEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\EnterpriseEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\P2pDepositoryEnum;
use core\service\user\UserService;
use core\service\deal\DealTagService;
use libs\utils\Finance;
use core\service\deal\DealService;
use core\service\project\ProjectService;
use core\service\makeloans\MakeLoansService;
use core\service\user\VipService;
use core\service\user\BankService;
use core\service\deal\DealSiteService;
use core\service\coupon\CouponService;
use core\service\deal\state\StateManager;
use core\service\account\AccountService;
use core\dao\jobs\JobsModel;
use core\service\repay\DealRepayService;
use core\dao\deal\DealGroupModel;
use core\dao\supervision\SupervisionIdempotentModel;
use core\dao\project\DealProjectModel;
use core\service\contract\ContractService;
use core\service\contract\CategoryService;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\service\msgbus\MsgbusService;
use NCFGroup\Common\Library\Idworker;
use libs\utils\DBDes;
use core\service\repay\DealPartRepayService;
use core\dao\repay\DealLoanRepayModel;

// 加载标的相关函数
FP::import("app.Lib.deal");

class DealAction extends CommonAction{

    public $deal_data;
    public $deal_ext_data;

    protected $pageEnable = false;

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型

    /**
     * 列表
     */
    public function index()
    {
        //开始加载搜索条件
        $map['is_delete'] = 0;
        $map['publish_wait'] = 0;
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
        // 编号
        if(intval($_REQUEST['id'])>0){
            $map['id'] = intval($_REQUEST['id']);
        }
        // 标题
        if(trim($_REQUEST['name'])!=''){
            $name = addslashes(trim($_REQUEST['name']));
            $map['name'] = array('like','%'.$name.'%');
        }
        // 借款人姓名
        if(trim($_REQUEST['real_name'])!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $ids = UserService::getUserIdByRealName($real_name);

            if (!empty($ids)) {
                $map['user_id'] = array("in", $ids);
            }else{
                // 远程调用失败
                $map['user_id'] = array("in", '-1');
            }
        }

        // 项目名称
        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
//            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` like '%" . trim($_REQUEST['project_name']) . "%')";
            //改为精确查询
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` = '" . trim($_REQUEST['project_name']) . "')";
        }
        // 放款审批单编号
        if (!empty($_REQUEST['approve_number'])) {
            $map['approve_number'] = array('eq', addslashes(trim($_REQUEST['approve_number'])));
        }

        // 存管报备状态
        if(trim($_REQUEST['report_status']) != ''){
            $map['report_status'] = array("eq",intval($_REQUEST['report_status']));
        }

        $deal_status_repaid = '';
        // 标状态
        if(isset($_REQUEST['deal_status']) && trim($_REQUEST['deal_status']) != '' && trim($_REQUEST['deal_status']) != 'all'){
            $map['deal_status'] = array("eq",intval($_REQUEST['deal_status']));
            $deal_status_repaid = intval($_REQUEST['deal_status']);
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();

        $model = DI($name);

       /*

          暂时不迁移已还清从备份库中读
        if ($deal_status_repaid === DealEnum::DEAL_STATUS_REPAID){
            $model = $this->getMovedModel($name);
        }else {
            $model = DI($name);
        }*/

        $userIDArr = array();
        $listOfBorrower = array();
        // 存储合同签署状态
        $deal_contract_list = array();
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
            //jira#5361 增加“平台费折扣率”
            $extModel = new DealExtModel();
            foreach($list as $k=>$v){
                $list[$k]['ecid'] = Aes::encryptForDeal($v['id']);
                $extRow = $extModel->findBy("deal_id = " . $v['id'], "discount_rate", array(), true);
                $list[$k]['discount_rate'] = $extRow['discount_rate'];
                // 去重
                if (!isset($userIDArr[$v['user_id']])){
                    $userIDArr[$v['user_id']] = $v['user_id'];
                }

               $contract_info = ContractService::getContractSignStatus($v['id'],$v['user_id'],$v['agency_id'],$v['advisory_id'],$v['entrust_agency_id'],$v['canal_agency_id']);
                $deal_contract_list[$v['id']] = $contract_info;


            }
            $listOfBorrower = UserService::getUserInfoByIds($userIDArr,true);

            $this->assign('list', $list);
        }
        $this->assign('listOfBorrower', $listOfBorrower);
        $this->assign('deal_contract_list', $deal_contract_list);
        $this->assign('sitelist', $GLOBALS['sys_config']['TEMPLATE_LIST']);
        $this->display ();
    }

    public function show_detail()
    {
        $id = intval($_REQUEST['id']);
        $deal_info = DealModel::instance()->getDealInfo($id);
        $this->assign("deal_info",$deal_info);

        $loan_list = D("DealLoad")->where('deal_id=' . $id)->order("id ASC")->findall();

        $this->assign("loan_list",$loan_list);

        $this->display();
    }
    /**
     * 从备份库读取
     * @param string $name
     *
     * @return object $model
     */
    public function getMovedModel($name){

        if (empty($name)) return false;
        return M($name,'Model',false,DealEnum::DEAL_MOVED_DB_NAME,'slave');
    }

    public function edit() {

        C('TOKEN_ON',true);
        $userInfo = array();
        $id = intval($_REQUEST['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        // 从备份库
        if (empty($vo)){
            $this->deal = $vo = $this->getMovedModel(MODULE_NAME)->where($condition)->find();
        }
        if (!$vo) {
            $this->error('获取标的信息失败');
        }

        //获得当前标的tag信息
        $deal_tag_service = new DealTagService();
        $tags =  $deal_tag_service->getTagByDealId($id);
        $vo['tags'] = implode(',',$tags);
        $vo['start_time'] = $vo['start_time']!=0?to_date($vo['start_time']):'';
        $vo['bad_time'] = $vo['bad_time']!=0?to_date($vo['bad_time']):'';
        $vo['repay_start_time'] = $vo['repay_start_time']!=0?to_date($vo['repay_start_time'],"Y-m-d"):'';

        // 读取用户组列表
        $usergroupList = UserService::getUserGroupList();
        if (empty($usergroupList)){
            $this->error('获取用户组列表失败');
        }
        $this->assign ( 'usergroupList', $usergroupList );

        if($vo['deal_status'] == DealEnum::DEAL_STATS_WAITING){
            $vo['services_fee'] = UserService::getUserServicesFee($vo['user_id']);
        }

        if($vo['manage_fee_text'] === ''){
            $vo['manage_fee_text'] = '年化，收益率计算中已包含此项，不再收取。';
        }

        // 标和用户组对应关系
        $group = M("DealGroup")->where(array('deal_id'=>$id))->select();

        if($group){
            $t_group = array();
            $relation = '';
            foreach ($group as $row){
                $t_group[] = $row['user_group_id'];
                $relation = $row['relation'];
            }
            $vo['user_group'] = $t_group;
            $vo['relation'] = $relation;
        }

        $foreground_deal_model = new DealModel();
       //计算还款期数
        $deal_model = $foreground_deal_model->getDealInfo($vo['id']);
        $this->assign('repay_times', $foreground_deal_model->getRepayTimes());

        //订单扩展信息
        $deal_ext = M("DealExt")->where(array('deal_id' => $id))->find();

        // 计算服务费
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $vo['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        }
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        //功夫贷分期咨询费计算
        if($vo['consult_fee_period_rate'] > 0){
            $consult_fee_period = $foreground_deal_model->floorfix($vo['borrow_amount'] * $vo['consult_fee_period_rate'] / 100.0);
            $this->assign("consult_fee_period", $consult_fee_period);
        }

        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);
        $management_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['management_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);
        $management_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $management_fee_rate / 100.0);

        $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);
        $this->assign("management_fee", $management_fee);

        //用户信息处理
        if(!empty($vo['user_id'])) {
            $userInfo = UserService::getUserByCondition('id='.intval($vo['user_id']));
            if (empty($userInfo)){
                $this->error('获取借款人信息失败');
            }
            $userInfo['audit'] = BankService::getNewCardByUserId($userInfo['id'],'*');
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
        $deal_cate_tree = D('Common')->toFormatTree($deal_cate_tree,'name');
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

        //功夫贷原始担保机构
        if($loan_type_info['type_tag'] == DealLoanTypeEnum::TYPE_XJDGFD) {
            $deal_extra_info = DealExtraModel::instance()->getDealExtraByDealId($vo['id']);
            if(!empty($deal_extra_info)) {
                //原始担保机构
                $deal_original_agency = M("DealAgency")->where(array("id"=>$deal_extra_info['original_agency_id']))->find();
                $this->assign("deal_original_agency",$deal_original_agency);
            }
        }

        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        $tplResponse = CategoryService::getCategorys();
        if(!is_array($tplResponse)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }
        $this->assign('contract_tpl_type', $tplResponse);    //合同类型

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);

        $vipGrades = VipService::getVipGradeList();
        if (empty($vipGrades)){
            $this->error('获取vip等级列表失败');
        }
        $this->assign('vipGrades', $vipGrades);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);


        //取平台信息
        $site_list = $GLOBALS['sys_config']['TEMPLATE_LIST'];
        $deal_site_list = get_deal_site($id);

        $this->assign('site_list', $site_list);
        $this->assign('deal_site_list', $deal_site_list);

        $deal_ext['start_loan_time'] = $deal_ext['start_loan_time'] == 0 ? '' : to_date($deal_ext['start_loan_time']);
        $deal_ext['first_repay_interest_day'] = $deal_ext['first_repay_interest_day'] == 0 ? '' : to_date($deal_ext['first_repay_interest_day'], "Y-m-d");
        $deal_ext['base_contract_repay_time'] = $deal_ext['base_contract_repay_time'] == 0 ? '' : to_date($deal_ext['base_contract_repay_time'], "Y-m-d");

        if($vo['deal_crowd'] == DealEnum::DEAL_CROWD_SPECIFY_USER) {
            $specify_uid_info = UserService::getUserByCondition("id=".intval($deal_ext['deal_specify_uid']),'*');
            if (empty($specify_uid_info)){
                $this->error('获取指定用户信息失败');
            }
            $this->assign('specify_uid_info',$specify_uid_info);
        }

        $this->assign('deal_ext', $deal_ext);

        $deal_coupon = CouponService::getCouponDealByDealId($id);
        if (empty($deal_coupon)){
            $this->error('优惠码标的设置信息不存在');
        }
        $this->assign("deal_coupon",$deal_coupon);
        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        if($project){
            $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);
            $project['business_status'] = intval($project['business_status']);
            $this->assign ( 'pro', $project );
        }

        // JIRA#3271 平台产品名称定义 2016-03-29
        $vo['prefix_title'] = $deal_ext['deal_name_prefix'];
        $idStr = str_pad(strval($id), 9, strval(0), STR_PAD_LEFT);
        $vo['main_title'] = $project['name'] . 'A' . $idStr;
        // ----------------- over ----------------

        // JIRA#3260 企业账户二期 - 获取用户类型名称 <fanjingwen@ucfgroup.com>
        if (!empty($vo['user_id']) && !empty($userInfo)) {
            $userInfo['user_type_name'] = getUserTypeName($userInfo['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserEnum::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {

                $enterpriseInfo = UserService::getEnterpriseInfo($userInfo['id']);
                // 有会员列表后需要加链接
                $userInfo['company_name'] = $enterpriseInfo['company_name'];
            } else {
                // 有会员列表后需要加链接
                $userInfo['real_name'] = $userInfo['real_name'] ;
            }
        }

        //借款客群
        if(($vo['loan_user_customer_type'] > 0) && array_key_exists($vo['loan_user_customer_type'],$GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'])){
            $vo['loan_user_customer'] = $GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'][$vo['loan_user_customer_type']];
        }
        // ----------------- over ----------------

        //利滚利分类标识
        $this->assign('lgl_tag', DealLoanTypeEnum::TYPE_LGL);
        $this->assign('bxt_tag', DealLoanTypeEnum::TYPE_BXT);
        $this->assign('dtb_tag', DealLoanTypeEnum::TYPE_DTB);
        $this->assign('xffq_tag', DealLoanTypeEnum::TYPE_XFFQ);
        $this->assign('zcgl_tag', DealLoanTypeEnum::TYPE_GLJH);
        $this->assign('zzjr_tag', DealLoanTypeEnum::TYPE_ZHANGZHONG);
        $this->assign('xsjk_tag', DealLoanTypeEnum::TYPE_XSJK);
        $this->assign('xjdcdt_tag', DealLoanTypeEnum::TYPE_XJDCDT);


        $this->assign ('vo', $vo);
        $this->assign("userInfo",$userInfo);
        $this->assign("project_business_status",DealProjectEnum::$PROJECT_BUSINESS_STATUS);

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

        $this->display ();
    }
    public function add()
    {

        C('TOKEN_ON',true);
        //项目
        $pro_id = intval($_REQUEST ['proid']);
        if($pro_id <= 0){
            $this->error('所属项目不能为空');
        }

        $mp = new ProjectService();
        $this->project = $project = $mp->getProInfo($pro_id);

        if(empty($project)){
            $this->error('所属项目不能为空');
        }
        $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);

        if(bccomp($project['borrow_amount'], $project['money_borrowed']) <= 0){
            $this->error('项目借款金额已满');
        }

        $day_rate = DealModel::instance()->convertRateYearToDay($project['rate'], $project['redemption_period']);
        // 计算日化利率
        $project['rate_day'] = bcmul($day_rate, '100', 5);
        $this->assign ( 'vo', $project );

        $user = UserService::getUserByCondition('id='.intval($project['user_id']));
        if (empty($user)){
            $this->error('获取用户信息失败');
        }
        // ----------------- over ----------------
        // JIRA#3260 企业账户二期 - 获取用户类型名称
        if (!empty($project['user_id']) && !empty($user)) {
            $user['user_type_name'] = getUserTypeName($user['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserEnum::USER_TYPE_ENTERPRISE == $user['user_type']) {
                $enterpriseInfo = UserService::getEnterpriseInfo($user['id']);
                if (empty($enterpriseInfo)){
                    $this->error('企业信息获取失败');
                }
                $user['company_name'] = $enterpriseInfo['company_name'];
            }
        }
        // ----------------- over ----------------

        $this->assign ( 'user', $user);

        //用户分组
        // 读取用户组列表
        $usergroupList = UserService::getUserGroupList();
        if (empty($usergroupList)){
            $this->error('获取用户组列表失败');
        }
        $this->assign ( 'usergroupList', $usergroupList );

        //排序
        $this->assign("new_sort", M("Deal")->where("is_delete=0")->max("sort")+1);

        //借款分类
        $deal_cate_tree = M("DealCate")->where('is_delete = 0')->findAll();
        $deal_cate_tree = D("Common")->toFormatTree($deal_cate_tree,'name');
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

        $tplResponse = CategoryService::getCategorys();
        if(!is_array($tplResponse)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }

        $this->assign('contract_tpl_type', $tplResponse);    //合同类型

        //从配置文件取公用信息
        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory",$deal_advisory);

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);

        //限制vip等级
        $vipGrades =  VipService::getVipGradeList();
        $this->assign('vipGrades', $vipGrades);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);

        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = get_sites_template_list();
        $this->assign('site_list', $site_list);

        //利滚利分类标识
        $this->assign('lgl_tag', DealLoanTypeEnum::TYPE_LGL);
        $this->assign('bxt_tag', DealLoanTypeEnum::TYPE_BXT);
        $this->assign('dtb_tag', DealLoanTypeEnum::TYPE_DTB);
        $this->assign('zcgl_tag', DealLoanTypeEnum::TYPE_GLJH);

        $this->display();
    }
    /**
     * 借款保存
     * @actionLock
     * lockauthor qicheng
     */
    public function insert() {

        C('TOKEN_ON',true);
        B('FilterString');
        $data = M(MODULE_NAME)->create();
        $model_deal_ext = M('DealExt');
        $deal_ext_data = $model_deal_ext->create();

        // 检查表单数据
        $rs_check = $this->_insertCheck($data, $deal_ext_data);

        $data = $this->deal_data;
        $deal_ext_data = $this->deal_ext_data;

        $_income_fee_rate = trim($_POST['income_fee_rate']); // 年化出借人收益率
        $_annualized_rate = trim($_POST['rate']);    // 借款年利率
        $_income_float_rate = trim($_POST['income_float_rate']);; //年化收益浮动利率
        $_income_base_rate = trim($_POST['income_base_rate']);; // 年化收益基本利率

        //更新数据
        $data['create_time'] = get_gmtime();
        $data['update_time'] = get_gmtime();
        $data['start_time'] = trim($data['start_time'])==''?0:to_timespan($data['start_time']);
        $data['bad_time'] = trim($data['bad_time'])==''?0:to_timespan($data['bad_time']);
        $data['parent_id'] = -1; //所有标均为普通标
        $data['income_total_rate'] = $data['income_fee_rate'] + $deal_ext_data['income_subsidy_rate'];

        $loan_type_info = M("Deal_loan_type")->where("id = ".intval($data['type_id']))->find();


        $m = M(MODULE_NAME);
        $deal_id = $m->add ($data);
        // 新增默认是等待确认状态
        if(empty($deal_id)){
            $dbErr = M()->getDbError();
            save_log( L("INSERT_FAILED").$dbErr,0);
            $this->error(L("INSERT_FAILED").$dbErr);
        }
        // JIRA#3271 平台产品名称定义
        // 更新deal表中对应的标的名称
        $deal_ext_data['deal_name_prefix'] = isset($_REQUEST['prefix_title']) ? mysql_real_escape_string($_REQUEST['prefix_title']) : '';


        // -------------- over ------------------


        if($data['deal_crowd'] == 16) {// 指定用户可投
            $deal_ext_data['deal_specify_uid'] = trim($_POST['specify_uid']);
        }

        FP::import("libs.deal.deal");



        // 加入tags部分  add by zhanglei5 20140827
        // 是否需要短信通知3日还款提醒  --add by zhanglei5 20141013
        $deal_ext_data['need_repay_notice']= isset($_REQUEST['need_repay_notice']) ? $_REQUEST['need_repay_notice'] : 0;

        //合同展示相关 --add by wangjiantong 20150923

        //转让资产类别
        $deal_ext_data['contract_transfer_type']= isset($_REQUEST['contract_transfer_type']) ? intval($_REQUEST['contract_transfer_type']) : 0;

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

        $deal_ext_data['deal_id'] = $deal_id;

        // 增加平台手续费 分期收方式
        $repay_times = DealModel::getRepayTimesByLoantypeAndRepaytime($data['loantype'], $data['repay_time']);
        if($deal_ext_data['loan_fee_rate_type'] == 4) {
            $loan_fee_arr[0] = $_REQUEST['loan_fee_arr'][0];
            $loan_fee_arr[$repay_times] = $_REQUEST['loan_fee_arr'][1];
            $deal_ext_data['loan_fee_ext'] = json_encode($loan_fee_arr);
        }

        $GLOBALS['db']->startTrans();
        try {
            $dealService = new DealService();
            $dealName = $dealService->updateDealName($deal_id, $data['project_id']);

            /*
             * 审核的时候更新
             * $projectSerice = new ProjectService();
            $ret = $projectSerice->updateProBorrowedLoanedById($data['project_id'], $data['borrow_amount'], 0);
            if (empty($ret)){
                throw new \Exception('更新项目上标金额失败');
            }*/
            $this->insertOtherData($data,$deal_id,$deal_ext_data);

            $admin = \es_session::get(md5(conf("AUTH_KEY")));
            $deal_coupon_data = array(
                    'dealId' => intval($deal_id),
                    'rebateDays' => intval($_POST['rebate_days']),
                    'payType' =>  intval($_POST['pay_type']),
                    'payAuto' => intval($_POST['pay_auto'])
                );
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_CREATE,$deal_coupon_data);

            $dealService->insertContract($deal_id,$admin,$data['contract_tpl_type']);

            $GLOBALS['db']->commit();

        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            $r = M(MODULE_NAME)->delete($deal_id);
            if (empty($r)){
                $this->error('标的回滚失败，请从未审核列表里删除id为 '.$deal_id. L("UPDATE_FAILED").$e->getMessage(),0);
            }
            save_log($dealName .L("UPDATE_FAILED").$e->getMessage(),C('FAILED'),'', $data, C('SAVE_LOG_FILE'));
            $this->error(L("UPDATE_FAILED").$e->getMessage(),0);
        }
        //生成贷款发送相关消息。
        $loan_type_name = get_deal_title($dealName, $loan_type_info['name']);


        FP::import("libs.common.app");


        //获取用户信息
        $user_info = UserService::getUserByCondition('id='.intval($data['user_id']));

        $notice = array();
        $notice['loan_type_name'] = $loan_type_name;
        $notice['user_id'] = $user_info['id'];
        $notice['real_name'] = $user_info['real_name'];
        $notice['email'] = $user_info['email'];
        $notice['user_name'] = $user_info['user_name'];
        $notice['deal_id'] = $deal_id;
        $notice['mobile'] = $user_info['mobile'];

       // $this->_msgfor_upload_deal($notice);


        save_log('上标'.$dealName.L("INSERT_SUCCESS"), C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));
        $this->success(L("INSERT_SUCCESS"));
    }
    private function _insertCheck($deal_data, $deal_ext_data) {

        if($deal_data['deal_crowd'] == 16 && !trim($_POST['specify_uid'])) {
            $this->error('指定用户ID不能为空');
        }

        if (!is_numeric($_POST['rebate_days']) ||  $_POST['rebate_days'] < 0){
            $this->error('返利天数不能为负数');
        }

        $_income_fee_rate = trim($_POST['income_fee_rate']); // 年化出借人收益率
        $_annualized_rate = trim($_POST['rate']);    // 借款年利率
        $_income_float_rate = trim($_POST['income_float_rate']);; //年化收益浮动利率
        $_income_base_rate = trim($_POST['income_base_rate']);; // 年化收益基本利率

        if(bccomp($_income_fee_rate,$_annualized_rate,5) !=0 || bccomp($_annualized_rate,bcadd($_income_float_rate,$_income_base_rate,5),5) !=0) {
            $this->error('请注意 ： 借款年利率＝年化出借人收益率 = (年化收益基本利率 + 年化收益浮动利率');
        }

        $this->deal_data = $this->_insertCheckDeal($deal_data);
        $this->deal_ext_data = $this->_insertCheckDealExt($this->deal_data, $deal_ext_data);
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

        $bankcard_info = BankService::getNewCardByUserId($data['user_id'],'status');
        if(!$bankcard_info || $bankcard_info['status'] != 1){
            $this->error ( "借款人用户银行卡未验证" );
        }

        return $deal_ext_data;
    }
    /**
     * 更新的时候必要检查
     * @param $chckParam
     */
    private function updateCheck($chckParam){

        // 表单数据
        $data = $chckParam['data'];
        // 数据库查询标的信息
        $vo = $chckParam['dealInfo'];

        $project = $chckParam['project'];

        $_income_fee_rate = trim($_POST['income_fee_rate']); // 年化出借人收益率
        $_annualized_rate = trim($_POST['rate']);    // 借款年利率
        $_income_float_rate = trim($_POST['income_float_rate']);; //年化收益浮动利率
        $_income_base_rate = trim($_POST['income_base_rate']);; // 年化收益基本利率

        $borrow_min = app_conf ( 'MIN_BORROW_QUOTA' );
        $borrow_max = app_conf ( 'MAX_BORROW_QUOTA' );

        if (empty($data['id'])){
            $this->error('参数错误');
        }


        if($data ['borrow_amount'] < $borrow_min || $data ['borrow_amount'] > $borrow_max) {
            $this->error ( "‘借款金额’应为" . $borrow_min . "至" . $borrow_max . "的整数！" );
        }
        if ( !is_numeric($_POST['rebate_days']) || $_POST['rebate_days'] < 0) {
            $this->error('优惠码返利天数不能为负数');
        }
        if(isset($data['prepay_days_limit']) && $data['prepay_days_limit'] <0){
            $this->error ( "‘提前还款限制期’应大于等于0" );
        }


        if(isset($data['repay_time']) && empty($data['repay_time'])){
            $this->error('借款期限不能为空');
        }
        if(($data ['max_loan_money']) > 0 && ($data ['max_loan_money']) < ($data ['min_loan_money'])) {
            $this->error ( "最大金额不能小于最小金额" );
        }
        if(bccomp($_income_fee_rate,$_annualized_rate,5) !=0 || bccomp($_annualized_rate,bcadd($_income_float_rate,$_income_base_rate,5),5) !=0) {
            $this->error('请注意 ： 借款年利率＝年化出借人收益率 = (年化收益基本利率 + 年化收益浮动利率');
        }
        if (empty($vo) || $vo['is_delete'] !=0 ){
            $this->error('信息不存在');
        }

        if (empty($vo['user_id'])){
            $this->error('借款人信息为空');
        }
        $userInfo = UserService::getUserByCondition('id='.intval($vo['user_id']),'idcardpassed,id');

        if(intval($userInfo['idcardpassed']) !== 1) {
            $this->error('借款人用户身份未认证');
        }

        $userAuditInfo = BankService::getNewCardByUserId($userInfo['id'],'status');
        if(intval($userAuditInfo['status']) !== 1) {
            $this->error('借款人用户银行卡未验证');
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

        //所属网站：进行中之前必填
        $deal_site = $_REQUEST['deal_site'];
        if($vo['deal_status'] == 0 && empty($deal_site)){
            $this->error('所属网站 不能为空！');
        }

        if($vo['publish_wait'] == 1 && $data['deal_status'] == 4 && $vo['deal_status'] == 0){
            $this->error('不可直接改为“还款中”');
        }



        return true;
    }
    /**
     * 修改
     */
    public function update() {
        B('FilterString');

        C('TOKEN_ON',true);
        // 表单数据
        $data = M(MODULE_NAME)->create ();
        $data['id'] = intval($data['id']);

        // 标的信息
        $deal_model = new DealModel();
        $vo = $deal_model->getDealInfo($data['id']);

        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));

        $_income_fee_rate = trim($_POST['income_fee_rate']); // 年化出借人收益率
        $_annualized_rate = trim($_POST['rate']);    // 借款年利率
        $_income_float_rate = trim($_POST['income_float_rate']);; //年化收益浮动利率
        $_income_base_rate = trim($_POST['income_base_rate']);; // 年化收益基本利率

        $borrow_min = app_conf ( 'MIN_BORROW_QUOTA' );
        $borrow_max = app_conf ( 'MAX_BORROW_QUOTA' );

        $project = array();
        if($vo['project_id']){
            $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        }

        $checkParam = array(
            'data' => $data,
            'dealInfo' => $vo,
            'project' => empty($project) ? 0 : $project
        );
        // 检查各种条件
        $this->updateCheck($checkParam);


        //“年化收益基本利率”和“年化收益浮动利率” 的处理
        $model_deal_ext = M('DealExt');
        $deal_ext_data = $model_deal_ext->create();
        // 是否需要短信通知3日还款提醒
        if (isset($_REQUEST['need_repay_notice'])) {
            $deal_ext_data['need_repay_notice']= isset($_REQUEST['need_repay_notice']) ? $_REQUEST['need_repay_notice'] : 0;
        }
        $income_rate_sum = sprintf("%.5f", $deal_ext_data['income_base_rate'] + $deal_ext_data['income_float_rate']);


        $deal_ext_data['start_loan_time'] = trim($deal_ext_data['start_loan_time']) != '' && $data['deal_status'] == 0 ? to_timespan($deal_ext_data['start_loan_time']) : '';
        if($deal_ext_data['start_loan_time'] && $deal_ext_data['start_loan_time'] <= get_gmtime()){
            $this->error('“开标时间”应当大于当前时间');
        }

        $deal_ext_data['base_contract_repay_time'] = trim($deal_ext_data['base_contract_repay_time']) == '' ? 0 : to_timespan($deal_ext_data['base_contract_repay_time']);

        //转让资产类别
        //年化借款平台手续费类型
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
        if (isset($_REQUEST['loan_application_type'])) {
            $deal_ext_data['loan_application_type']= !empty($_REQUEST['loan_application_type']) ? intval($_REQUEST['loan_application_type']) : 0;
        }
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



        //进行中 可以改为 等待确认
        if($vo['deal_status'] > 1 && $data['deal_status'] == 0){
            if(is_array($deal_site)){
                $deal_site_service = new DealSiteService();
                $res_deal_site = $deal_site_service->updateDealSite($data['id'], $deal_site);
            }
            if ($res_deal_site === true) {
                $this->error("所属站点修改成功，其他信息不允许修改！");
            } else {
                $this->error('不可以修改！');//从"进行中" 或之后的状态 改为 "等待材料"
            }
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
        if ($vo['deal_status'] == 4 || $vo['deal_status'] == 3 || $vo['deal_status'] == 5 ||  $loan_type_info['type_tag'] == DealLoanTypeEnum::TYPE_XFFQ) {
            unset($deal_ext_data['first_repay_interest_day']);
        }

        if(  $loan_type_info['type_tag'] != DealLoanTypeEnum::TYPE_XFFQ) {
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



        // JIRA#3271 平台产品名称定义
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


        $data['isDtb'] = 0;
        // 默认都是报备
        $data['report_type'] = 1;

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

        $data['is_float_min_loan'] = !isset($_REQUEST['is_float_min_loan'])  ? 0 : 1;


        if($data['deal_crowd'] == 16) {// 指定用户可投
            $deal_ext_data['deal_specify_uid'] = trim($_POST['specify_uid']);
        }elseif($data['deal_crowd'] == 33) {
            //指定VIP用户可投
            $deal_ext_data['deal_specify_uid'] = trim($_POST['specify_vip']);
        }
        // 如果是审核通过情况
        if ($vo['publish_wait'] == 1) {
            $update_project_broow_amount = $data['borrow_amount'];
        }else{
            $update_project_broow_amount = 0;
            if (bccomp($data['borrow_amount'], $vo['borrow_amount'], 2) == 1) {
                $update_project_broow_amount = $data['borrow_amount'] - $vo['borrow_amount'];

            }
            if (bccomp($data['borrow_amount'], $vo['borrow_amount'], 2) == -1) {
                $update_project_broow_amount = $vo['borrow_amount'] - $data['borrow_amount'];
                $update_project_broow_amount = '-' . $update_project_broow_amount;
            }
        }


        if($data['deal_status'] == 4){
            unset($data['rate']);
            if(!in_array($vo['deal_status'], array(2,4))){
                $this->error("未满标，无法设置为“还款中”状态!");
            }
            //放款
            if($vo['is_has_loans'] == 0 && $data['deal_status'] == 4) {

                $vo['agency_id'] = $data['agency_id'];
                $vo['advisory_id'] = $data['advisory_id'];
                $vo['entrust_agency_id'] = $data['entrust_agency_id'];
                $vo['canal_agency_id'] = $data['canal_agency_id'];

                if ($data['isDtb'] == 1) {
                    $vo['management_agency_id'] = $data['management_agency_id'];
                }
                $vo['isDtb'] = $data['isDtb'];

                foreach ($data as $key => $value) {
                    $vo[$key] = $value;
                }

                $makeLoansService = new MakeLoansService();
                try {
                    $GLOBALS['db']->startTrans();
                    if($makeLoansService->isOKForMakingLoans($vo)) {
                        $orderId = Idworker::instance()->getId();
                        $function = '\core\service\deal\P2pDealGrantService::dealGrantRequest';
                        $param = array(
                                'orderId' => $orderId,
                                'dealId' => $data['id'],
                                'param' => array('deal_id' => $data['id'], 'admin' => \es_session::get(md5(conf("AUTH_KEY")))),
                                );
                        $job_model = new JobsModel();
                        $job_model->priority = JobsEnum::PRIORITY_DEAL_GRANT;
                        //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
                        $add_job = $job_model->addJob($function, $param, get_gmtime() + 180);
                        if (!$add_job) {
                            throw new \Exception("放款任务添加失败");
                        }
                        //更新标放款状态
                        $deal_model = new DealModel();
                        $save_status = $deal_model->changeLoansStatus($data['id'], 2);
                        if (!$save_status) {
                            throw new \Exception("更新标放款状态 is_has_loans 失败");
                        }
                    }
                    $GLOBALS['db']->commit();
                } catch (\Exception $e) {
                    $GLOBALS['db']->rollback();
                    $this->error("操作失败: " . $e->getMessage());
                }
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$data['id']);

            }
        } elseif ($data['deal_status'] == DealEnum::DEAL_STATUS_FAIL) {     // 流标
            $data['is_doing'] = 1;
        }

        // 更新开始
        $GLOBALS['db']->startTrans();
        try {

            if($data['deal_crowd'] == 2){ // 特定用户组
                $relation = 0;
                foreach ($_POST['relation'] as $v) {
                    $relation = $relation | $v;
                }
                $relation = base_convert($relation, 2, 10);
                $dealGroupModel = M('DealGroup');
                $dealGroupModel->where(array('deal_id'=>$data['id']))->delete();
                $grouplist = $_POST['user_group'];
                if($grouplist){
                    foreach ($grouplist as $key => $value) {
                        $dealGroupModel->add(array('deal_id'=>$data['id'],'user_group_id'=>$value, 'relation' => $relation));
                    }
                }
            }

            $this->updateDealOtherData($data['id'],$data,$deal_ext_data);
            if (!empty($update_project_broow_amount)) {
                if ($vo['project_id']){
                    //更新项目信息
                    $deal_pro_service = new ProjectService();
                    $ret = $deal_pro_service->updateProBorrowedLoanedById($vo['project_id'], $update_project_broow_amount, 0);
                    if (empty($ret)) {
                        throw new \Exception("更新项目已上标金额失败");
                    }
                }

            }
            // 目前只处理进行中的
            if ($vo['deal_status'] == 0 && $data['deal_status'] == 1)  {

                $data['project_id'] = $vo['project_id'];
                $data['user_id'] = $vo['user_id'];
                $data['use_info'] = $deal_ext_data['use_info'];
                $deal_model = new DealModel();
                $state_manager = new StateManager($deal_model->find($data['id']));
                //$state_manager->setDeal($data);
                $report_staus_ret = $state_manager->work();
                if (empty($report_staus_ret)) {
                    throw new \Exception("标的报备失败:".$state_manager->getErrMsg());
                }
            }
                // 更新合同和优惠码加jobs
                /*  $model_jobs = new JobsModel();
                  $function = '\core\service\DealService::updateContractCoupon';
                  $param = array(
                      'deal_id' => $data['id'],
                      'admin' => \es_session::get(md5(conf("AUTH_KEY"))),
                      'contract_tpl_type' => $data['contract_tpl_type'],
                      'coupon_info' => array(
                              'rebateDays' => $rebate_days,
                              'payType' => $pay_type,
                             'payAuto' => $pay_auto,
                                  ),
                      );
                  $ret = $model_jobs->addJob($function, $param);*/
                $param = array(
                    'deal_id' => $data['id'],
                    'admin' => \es_session::get(md5(conf("AUTH_KEY"))),
                    'contract_tpl_type' => $data['contract_tpl_type'],
                );
             if($vo['deal_status'] == 0) {
                 $rebate_days = intval(trim($_POST['rebate_days']));
                 $pay_type = intval(trim($_POST['pay_type']));
                 $pay_auto = intval(trim($_POST['pay_auto']));
                 $param['coupon_info'] = array(
                     'rebateDays' => $rebate_days,
                     'payType' => $pay_type,
                     'payAuto' => $pay_auto,
                 );
                 $deal_service = new DealService();
                 $ret = $deal_service->updateContract($param['deal_id'],$param['admin'],$param['contract_tpl_type'],$param['coupon_info']);
                 if (empty($ret)){
                     throw new \Exception("更新合同失败");
                 }
             }

            // 处理满标 还款中 流标
            syn_deal_status($data['id']);
            $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            save_log($vo['name'].L("UPDATE_FAILED").$e->getMessage(),C('FAILED'), $vo, $data, C('SAVE_LOG_FILE'));
            $this->error(L("UPDATE_FAILED").$e->getMessage(),0);
        }
        // 优惠码读普惠借口
        if($vo['deal_status'] == 0) {
            $ret = CouponService::saveCouponDeal($param['deal_id'], $param['coupon_info']['rebateDays'], $param['coupon_info']['payType'], $param['coupon_info']['payAuto']);
            if (empty($ret)) {
                $this->error(L("UPDATE_FAILED") . '优惠码标的设置失败', 0);
            }
        }
        //如果是发布申请，给担保人发信息
        if(intval($vo['publish_wait']) === 1){
            // TODO 临时注掉
            //$this->_publishMsg($data['id']);
        }

        //成功提示
        save_log($vo['name'].L("UPDATE_SUCCESS"),C('SUCCESS'), $vo, $data, C('SAVE_LOG_FILE'));
        $this->success(L("UPDATE_SUCCESS"));
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
        //判断前置条件
        if($dealInfo['deal_status'] !=1) $this->error('只有状态为“进行中”的标才能修改为满标');
        if($dealInfo['load_money'] <= 0) $this->error('投资额为0的标禁止修改为满标');

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
            $pro_service = new ProjectService();
            $r = $pro_service->updateProBorrowed($dealInfo['project_id']);
            if ($r === false) {
                throw new \Exception('更新项目金额失败');
            }

            $r = $pro_service->updateProLoaned($dealInfo['project_id']);
            if ($r === false) {
                throw new \Exception('更新项目已投金额失败');
            }

            $deal_info = $deal_model->find($dealInfo['id']);
            $dinfo = $deal_info->getRow();

            $dinfo['borrow_sum'] = $dinfo['borrow_amount'];

            // 改为队列发送合同
            $jobsModel = new JobsModel();


            $contract_function = '\core\service\dealload\DealLoadService::sendContract';
            $contract_param = array(
                'deal_id' => $id,
                'load_id' => 0,
                'is_full' => true,
                'create_time' => time(),
            );

            $jobsModel->priority = JobsEnum::BID_SEND_CONTRACT;
            $contract_ret = $jobsModel->addJob($contract_function, array('param' => $contract_param)); //不重试
            if ($contract_ret === false) {
                throw new \Exception('满标合同任务插入注册失败');
            }

            $full_ckeck_function = '\core\service\dealload\DealLoadService::fullCheck';
            $full_ckeck_param = array(
                'deal_id' => $id,
            );
            $jobsModel->priority = JobsEnum::BID_CHECK_FULL_CONTRACT;
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
            //$this->put_p2p_data($id);

            /*
             * //满标触发首尾标附加返利
             *      $coupon_log_service = new CouponLogService();
             *       $r = $coupon_log_service->handleCouponExtraForDeal($id);
             *        if ($r === false) {
             *             throw new \Exception('优惠码结算失败');
             *          }
             */

            // 更新手续费相关
            if (false === DealService::updateHandlingCharge($id, array(), true)) {
                throw new \Exception('标的手续费相关信息更新失败！');
            }

            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_FULL,array('dealId'=>$id));
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            save_log('id为'.$dealInfo['id'].'的标，截标失败: ' . $e->getMessage(), 1);
            $this->error("操作失败: " . $e->getMessage());
        }

        $deal_data->unlockDealBid($id);

        save_log('id为'.$dealInfo['id'].'的标，借款金额由'.$before_amount.'改为'.$dealInfo['load_money'],1);
        $this->success(L("借款金额已改为".$dealInfo['load_money']));
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
        //TODO 先注掉还没接口提供
       // $this->_send_user_msg("",$content,0,$deal_info['user_id'],get_gmtime(),0,true,1);

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
        $Msgcenter->setMsg($deal_user['mobile'], $deal_info['user_id'], $notice_sms, 'TPL_DEAL_PUBLISH_SMS_NEW','','',get_deal_domain_title($deal_id));

        //已同意的借款保证人
        $guarantor_list = M("DealGuarantor")->where(array("deal_id"=>$deal_id,'status'=>2))->findAll();
        $deal_user_name = UserService::getUserByCondition("'id'=>{$deal_info['user_id']}","real_name");
        foreach($guarantor_list as $k=>$v){

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
            $Msgcenter->setMsg($v['mobile'], $v['to_user_id'], $notice_sms, 'TPL_DEAL_PUBLISH_SMS_NEW','','',get_deal_domain_title($deal_id));

            //站内信
            $content = "<p>您担保的借款申请“<a href=\"".$deal_url."\">".$deal_name."</a>”，已经发布";
            //TODO 先注掉还没接口提供
           // $this->_send_user_msg("",$content,0,$v['to_user_id'],get_gmtime(),0,true,1);//给自己

        }
        $r = $Msgcenter->save();
        return $r;
    }

    /**
     * 批量导入借款，每导入一条，给用户发送消息
     *
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
            $Msgcenter->setMsg ( $msg_arr ['mobile'], 0, $msg_sms, 'TPL_DEAL_SUBMIT_SMS','','',get_deal_domain_title( $msg_arr ['deal_id']) );
        }

        $res = $Msgcenter->save ();
        return $res;
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

    }

    /**
     * 更新标的相关数据
     */
    private function updateDealOtherData($id,$data = array(),$new_deal_ext_data = array()){
        // 加入tags部分
        $deal_tags = trim($_REQUEST['deal_tags']);
        if(strlen($deal_tags) > 0) {
            $deal_tag_service = new DealTagService();
            $rs = $deal_tag_service->updateTag($id, $deal_tags);
        } else {
            $rs = DealTagModel::instance()->deleteByDealId($id);
        }

        if (empty($rs)){
            throw new \Exception("更新tag失败");
        }

        //所属网站：进行中之前必填
        $deal_site = $_REQUEST['deal_site'];

        if(is_array($deal_site)){
            $deal_site_service = new DealSiteService();
            $res_deal_site = $deal_site_service->updateDealSite($id, $deal_site);
        }

        if (empty($res_deal_site)){
            throw new \Exception("更新站点失败");
        }

        $deal_model = new DealModel();
        $deal_model->_isNew = false;
        $deal_model->setRow($data);

        //确认修改
        $ret = $deal_model->save();
        if (empty($ret)){
            throw new \Exception("更新标信息失败");
        }
        $model_deal_ext = new DealExtModel();
        // 保存订单扩展信息
        if (empty($_REQUEST['deal_ext_id'])) {
            $deal_ext_data['deal_id'] = $data['id'];
            $model_deal_ext->setRow($new_deal_ext_data);
            $res = $model_deal_ext->insert();
        }else{
            $res = $model_deal_ext->updateBy($new_deal_ext_data,"deal_id='{$data['id']}'");
        }
        if (false === $res){
            throw new \Exception("更新标扩展消息失败");
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

        if (false === DealService::updateHandlingCharge($id, $period_fee_arr)) {
            throw new \Exception("标的手续费相关信息更新失败");
        }

        return true;

    }

    /**
     * 增加标的数据
     * @param $data
     * @param $new_deal_ext_data
     */
    private function insertOtherData($data,$deal_id,$new_deal_ext_data){

        if (empty($deal_id) || empty($new_deal_ext_data)){
            throw new \Exception('增加标的其他数据参数错误');
        }

        $deal_new_id = $deal_id;

        if($data['deal_crowd'] == 2){ // 特定用户组

            $grouplist = $_POST['user_group'];

            if($grouplist)
            {
                $relation = 0;
                foreach ($_POST['relation'] as $v) {
                    $relation = $relation | $v;
                }
                $relation = base_convert($relation, 2, 10);
                foreach ($grouplist as $key => $value) {
                    $dealGroupModel = new DealGroupModel();
                    $dealGroupModel->setRow(array('deal_id'=>$deal_new_id,'user_group_id'=>$value, 'relation' => $relation));

                   $ret =  $dealGroupModel->save();
                    if (empty($ret)){
                        throw new \Exception('新增特定用户组失败');
                    }
                }
            }
        }

        $deal_site = $_REQUEST['deal_site'];
        $dealSiteService = new DealSiteService();
        $ret = $dealSiteService->updateDealSite($deal_id,$deal_site);
        if (empty($ret)){
            throw new \Exception('增加标的站点失败');
        }
        $deal_tags = trim($_REQUEST['deal_tags']);
        if(strlen($deal_tags) > 0) {
            $deal_tag_service = new DealTagService();
            $rs = $deal_tag_service->insert($deal_id,$deal_tags);
            if(!$rs) {
               throw new \Exception('增加标的tags 失败');
            }
        }
        $model_deal_ext = new DealExtModel();
        $model_deal_ext->setRow($new_deal_ext_data);
        // JIRA#3271 平台产品名称定义 2016-03-29
        $rs = $model_deal_ext->save();
        if (empty($rs)) {
           throw new \Exception('标的扩展信息插入失败');
        }

        return true;

    }
    /**
     * 删除借款
     */
    public function delete() {

        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = addslashes($_REQUEST ['id']);

        $deny = '';
        if (empty($id)){
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
        $deal_service = new DealService();
        $ids = explode(',', $id);
        $rs_arr = $deal_service->compareDeleteByIds($ids);
        if (empty($rs_arr['allow'])){
            if (count($rs_arr['deny']) > 0) {
                $deny = implode(',', $rs_arr['deny']).' 不能删除';
            }
            save_log($id.'_'.l("DELETE_FAILED"),0);
            $this->error (l("DELETE_FAILED").$deny,$ajax);
        }
        $condition = array ('id' => array ('in',  $rs_arr['allow'] ) );
        $rel_data = M(MODULE_NAME)->where($condition)->findAll();

        $condition = "";
        $condition = $this->format_id($rel_data);

        $rel_data = array();
        $rel_data = M(MODULE_NAME)->where($condition)->findAll();

        $dealProService = new ProjectService();

        foreach($rel_data as $data)
        {
            $info[] = $data['name'];
            rm_auto_cache("cache_deal_cart",array("id"=>$data['id']));
        }
        if($info) $info = implode(",",$info);

        $GLOBALS['db']->startTrans();
        $ids_str = $this->format_id($rel_data,true);
        try{
            $ret = $deal_service->batchDeleteByIds($ids_str);

            if (empty($ret)){
                throw new \Exception("del deal fail ".$ids_str);
            }
            foreach($rel_data as $one){
                if($one['project_id'] > 0){
                    $ret = $dealProService->updateProBorrowedLoanedById($one['project_id'],'-'.$one['borrow_amount'],'-'.$one['load_money']);
                    if (empty($ret)){
                        throw new \Exception("del deal fail ".$ids_str);
                    }
                }
            }
            $GLOBALS['db']->commit();
        }catch(\Exception $e){
            $GLOBALS['db']->rollback();
            save_log($info.l("DELETE_FAILED"),0);
            $this->error (l("DELETE_FAILED"),$ajax);
        }

        if (count($rs_arr['deny']) > 0) {
            $deny = implode(',', $rs_arr['deny']).' 不能删除';
            $this->error (l("DELETE_FAILED").$deny,0);
        }

        save_log($info.l("DELETE_SUCCESS"),1);
        $this->success (l("DELETE_SUCCESS"),$ajax);

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
    public function publish($deal_type = 0)
    {
        $map['publish_wait'] = 1;
        $map['is_delete'] = 0;
        $map['deal_type'] = 0;

        $template = 'publish';

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        if (!empty($_REQUEST['project_name'])) {
            $map['_string'] = " `project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` = '" . trim($_REQUEST['project_name']) . "')";
        }

        $map['deal_type'] = 0;//只显示网贷标

        $name=$this->getActionName();
        $model = DI ($name);
        $userIDArr = array();
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
            foreach($list as $k=>$v){
                // 去重
                if (!isset($userIDArr[$v['user_id']])){
                    $userIDArr[$v['user_id']] = $v['user_id'];
                }
            }
            $listOfBorrower = UserService::getUserInfoByIds($userIDArr,true);
            foreach($list as $k=> $v){
                $list[$k]['userTypeName'] = $listOfBorrower[$v['user_id']]['user_type_name'];
            }

            $this->assign('list', $list);
        }
        $this->display($template);
        return;
    }

    /**
     *
     */
    public function trash()
    {
        $condition['is_delete'] = 1;
        $this->assign("default_map",$condition);
        parent::index();
    }

    //恢复
    public function restore() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = addslashes($_REQUEST ['id']);

        if (empty( $id )) {
            save_log(l("RESTORE_FAILED"),0);
            $this->error (l("RESTORE_FAILED"),$ajax);
        }
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


            $ids_str = $this->format_id($rel_data,true);

            $deal_pro_service = new ProjectService();
            $deal_service = new DealService();
            $GLOBALS['db']->startTrans();
            try {

                $ret = $deal_service->batchDeleteByIds($ids_str,0);
                if (empty($ret)){
                    throw new \Exception("del deal fail ".$ids_str);
                }
                foreach ($rel_data as $one) {
                    if ($one['project_id'] > 0) {
                        $ret = $deal_pro_service->updateProBorrowedLoanedById($one['project_id'],$one['borrow_amount'],$one['load_money']);
                        if (empty($ret)){
                            throw new \Exception("del deal fail ".$ids_str);
                        }
                    }
                }
                $GLOBALS['db']->commit();
            }catch (\Exception $e){
                $GLOBALS['db']->rollback();
                save_log($info.l("RESTORE_FAILED"),0);
                $this->error (l("RESTORE_FAILED"),$ajax);
            }

            save_log($info . l("RESTORE_SUCCESS"), 1);
            $this->success(l("RESTORE_SUCCESS"), $ajax);

    }
    /**
     * 获取借款用途列表
     *
     * @access public
     * @return void
     */
    public function getDealLoanTypeList() {
       $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->findAll();
        $deal_type_tree = D("Common")->toFormatTree($deal_type_tree,'name');
       // $deal_type_tree = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];

        return $deal_type_tree;
    }
    /**
     * ID处理
     *
     * @Title: format_id
     * @Description: 删除、恢复、彻底删除时对ID的处理。
     * @param  $id_arr
     * @return array
     *
     */

    private function format_id($id_arr, $is_str = FALSE){

        if(empty($id_arr)) return false;

        $id_str = '';
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
    /**
     * 导出订单 csv
     *
     * @param int $page
     * @return return_type
     * @throws
     *
     */
    public function export_csv($page = 1)
    {


        set_time_limit(0);
       // $limit = (($page - 1)*intval(app_conf("BATCH_PAGE_SIZE"))).",".(intval(app_conf("BATCH_PAGE_SIZE")));
        ini_set('memory_limit', '256M');
        $limit = '0,10000';
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

        if(trim($_REQUEST['real_name'])!='') {
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $condition = " real_name LIKE '%" . $real_name . "%'";
            $ids_list = UserService::getUserByCondition($condition, 'id');
            if (!empty($ids_list)) {
                $ids = '';
                foreach ($ids_list as $key => $v){
                    $ids .= $v['id'].',';
                }
                $ids = trim($ids,',');
                $where .= " AND user_id in(" . $ids . ")";
            }
        }
        $model = MI("Deal");
        $down_file_name = 'deal_list';
        if(isset($_REQUEST['deal_status']) && trim($_REQUEST['deal_status']) != '' && trim($_REQUEST['deal_status']) != 'all'){
            $deal_status = intval($_REQUEST['deal_status']);
            $where .= " AND deal_status = $deal_status";
            if ($deal_status == DealEnum::DEAL_STATUS_REPAID){
                $model = $this->getMovedModel('Deal');
                $down_file_name .= '_repaid';
            }
        }

        $deal_type = 0;
        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $where .= " AND `project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` LIKE '%" . trim($_REQUEST['project_name']) . "%')";
        }


        if(trim($_REQUEST['report_status']) != ''){
            $where .= sprintf(' AND `report_status` = %d ', $_REQUEST['report_status']);
        }

        $where .= ' AND is_delete =0 AND publish_wait = 0';

        $list = $model
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
            //register_shutdown_function(array(&$this, 'export_csv'), $page+1);

            $order_value = array(
                'id'=>'""',
                'deal_name'=>'""',
                'deal_type_name'=>'""',
                'borrow_real_name' =>'""',
                'borrow_user_name'=>'""',
                'borrow_user_id'=>'""',
                'borrow_guarantor_id'=>'""',
                'agency_id'=>'""',
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
                'description'=>'""',
                'deal_status'=>'""',
                'start_time'=>'""',
                'create_time'=>'""',
                'is_effect'=>'""',
                'user_type_name' => '""',
            );
            if($page == 1)
            {
                $content = iconv("utf-8","gbk","编号,借款标题,借款用途,借款人姓名,借款人,借款人id,借款保证人,担保机构,担保范围,还款方式,借款金额,最低金额,借款期限,年利率,筹标期限,借款手续费,借款咨询费,借款担保费,支付服务费,出借人平台管理费,借款描述,借款状态,开始时间,创建时间,状态,用户类型");
                $content = $content . "\n";

            }
            $userIDArr = array();
            foreach($list as $k=>$v){
                // 去重
                if (!isset($userIDArr[$v['user_id']])){
                    $userIDArr[$v['user_id']] = $v['user_id'];
                }
            }
            $listOfBorrower = UserService::getUserInfoByIds($userIDArr,true);
            foreach($list as $k=>$v)
            {

                $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
                $order_value['deal_name'] = '"' . iconv('utf-8','gbk',$v['name']) . '"';
                $deal_type_name = M("Deal_loan_type")->field("name")->where("id = '" .$v['type_id']. "'")->find();
                $order_value['deal_type_name'] = '"' . iconv('utf-8','gbk',$deal_type_name['name']) . '"';
                $borrow_user_info = $listOfBorrower[$v['user_id']];
                $order_value['borrow_real_name'] = '"' . iconv('utf-8','gbk',$borrow_user_info['real_name']) . '"';
                $order_value['borrow_user_name'] = '"' . iconv('utf-8','gbk',$borrow_user_info['user_name']) . '"';
                $order_value['borrow_user_id'] = '"' . iconv('utf-8','gbk',$v['user_id']) . '"';
                $deal_guarantor = M("Deal_guarantor")->where("deal_id = '" .$v['id']. "'")->select();
                $deal_guarantor_id = "";
                if($deal_guarantor){
                    foreach ($deal_guarantor as $val){
                        if($val['to_user_id']){
                            $guarantor_name = UserService::getUserById("{$val['to_user_id']}",'real_name');
                            $deal_guarantor_id .= $guarantor_name['real_name'].',';
                        }
                    }
                }
                $order_value['borrow_guarantor_id'] = '"' . iconv('utf-8','gbk',trim($deal_guarantor_id,',')) . '"';
                $agency_id = M("Deal_agency")->where("id = '" .$v['agency_id']. "'")->find();
                $order_value['agency_id'] = '"' . iconv('utf-8','gbk',$agency_id['name']) . '"';
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
                $deal_loantype = $GLOBALS['dict']['LOAN_TYPE_CN'][$v['loantype']];
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
                // JIRA#FIRSTPTOP-3260 企业账户二期功能
                $order_value['user_type_name'] = '"' . iconv('utf-8','gbk', $borrow_user_info['user_type_name']) . '"';
                $content .= implode(",", $order_value) . "\n";
            }

            $datatime = date("YmdHis",get_gmtime());
            header("Content-Disposition: attachment; filename={$datatime}_{$down_file_name}.csv");
            echo $content;
        }
        else
        {
            if($page==1)
                $this->error(L("NO_RESULT"));
        }

    }
    /**
     * 操作批量变更页面
     */
    public function batch_update()
    {
        $this->assign('main_title', '批量更新标的信息');
        $this->display('batch_update_cn');
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

        $title = array('标id', '所属队列', '管理机构', 'tag', '借款金额', '最低金额', '年化基本利率', '借款年利率', '年化出借人利率', '年化借款平台手续费',
            '年化借款咨询费', '年化借款担保费', '年化支付服务费', '年化管理服务费', '年化管理服务费收取方式', '合同类型', '借款状态', '状态', '所属网站');

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
                if(!empty($new_data['contract_tpl_type']) && ($deal_obj->deal_status != DealEnum::$DEAL_STATUS['waiting'])){
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
                        'fail_msg'=>'出借记录不为空',
                    );
                    continue;
                }

                //判断标的“投资限定条件 1” 是否为“全部用户”
                if($deal_obj->deal_crowd != DealEnum::DEAL_CROWD_ALL) {
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'deal_id'=>$new_data['id'],
                        'fail_msg'=>'出借限定条件1不满足变更条件',
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
                if (!in_array($deal_obj->deal_status, array(DealEnum::$DEAL_STATUS['waiting'], DealEnum::$DEAL_STATUS['progressing']))) {
                    $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$new_data['deal_site']];
                    if (empty($site_id)) {
                        Logger::error(sprintf('标的信息所属网站更新失败，标id：%d，失败原因：所属网站不存在 （%s），file：%s, line:%s', $new_data['id'], $new_data['deal_site'], __FILE__, __LINE__));
                        $fail_collection[] = array(
                            'id'=>count($fail_collection) + 1,
                            'deal_id'=>$new_data['id'],
                            'fail_msg'=>'所属网站不存在',
                        );
                    } else {
                        $ds = new DealSiteService();
                        $upRes = $ds->updateDealSite($new_data['id'], array($site_id));
                        if(!$upRes){
                            $fail_collection[] = array(
                                'id'=>count($fail_collection) + 1,
                                'deal_id'=>$new_data['id'],
                                'fail_msg'=>'标的信息所属网站更新失败',
                            );
                            Logger::info(sprintf('标的信息所属网站更新失败，标id：%d，所属网站：%d，file：%s, line:%s', $new_data['id'], $site_id, __FILE__, __LINE__));
                        }else{
                            Logger::info(sprintf('标的信息所属网站更新成功，标id：%d，所属网站：%d，file：%s, line:%s', $new_data['id'], $site_id, __FILE__, __LINE__));
                            $success_collection[] = $new_data['id'];
                        }
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
                            if (isset($new_data['deal_status']) && DealEnum::$DEAL_STATUS['waiting'] == $deal_obj->deal_status && DealEnum::$DEAL_STATUS['progressing'] == $new_data['deal_status']) {
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
                                    $ds = new DealSiteService();
                                    $upRes = $ds->updateDealSite($new_data['id'], array($site_id));
                                    if(!$upRes){
                                        throw new \Exception('更改站点信息失败！');
                                    }
                                }
                            }
                        }

                        // 报备
                        $is_report_update = (1 == $deal_obj->report_status) ? true :false;

                        // 此处保存一定要放到报备之后，否则在此隔离级别下，会读到刚才保存的新状态
                        if ($is_deal_modify && false === $deal_obj->save()) {
                            throw new \Exception('标的信息更新失败！');
                        }

                        if ($is_deal_ext_modify && false === $deal_ext_obj->updateByDealId()) {
                            throw new \Exception('标的扩展信息更新失败！');
                        }


                        $deal_service = new DealService();
                        if($is_deal_modify && $deal_obj->deal_status == DealEnum::$DEAL_STATUS['progressing']) { // 如果标的信息有变化，且状态为进行中，且是需要报备的标
                            Logger::info(sprintf('deal_report_request_start,deal_id:%d,function:%s,file:%s,line:%s', $deal_obj->id, __FUNCTION__, __FILE__, __LINE__));
                            $report_service = new \core\service\deal\depository\ReportDepositoryService();
                            $report_service->dealReportRequest($deal_obj->getRow(), $is_report_update); // true or throw
                            Logger::info(sprintf('deal_report_request_end,deal_id:%d,function:%s,file:%s,line:%s', $deal_obj->id, __FUNCTION__, __FILE__, __LINE__));
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
                        // 修改合同类型(标的为等待状态，并且变更的合同类型不为空，才能修改合同类型)
                        // 放在事务提交前，db操作失败则会立即回滚
                        if($is_deal_modify && ($deal_obj->deal_status == DealEnum::$DEAL_STATUS['waiting']) && !empty($new_data['contract_tpl_type'])){
                            $contractResult =  $deal_service->updateContract($new_data['id'],array(),$new_data['contract_tpl_type'],'');
                            if(!$contractResult){
                                throw new \Exception('合同类型更新失败！');
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
        $vo['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($vo['id'])){
            $vo['isDtb'] = 1;
        }

        //用户信息处理
        $userInfo = UserService::getUserByCondition('id='.intval($vo['user_id']));
        $this->assign("userInfo",$userInfo);

        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        $project['bankcard'] = DBDes::decryptOneValue($project['bankcard']);
        $this->assign ( 'pro', $project );

        $this->assign('loan_money_type', $GLOBALS['dict']['LOAN_MONEY_TYPE']); //放款方式
        $bank_list = BankService::getAllByStatusOrderByRecSortId(0);
        $this->assign("bank_list", $bank_list);

        // JIRA#1108 计算还款期数
        $deal_model = DealModel::instance()->find($vo['id']);
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
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD))) {
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
        $this->display('lent');
    }

    public function update_lent() {
        B('FilterString');

        $data = M(MODULE_NAME)->create();
        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $data['id']))->find();
        if(empty($vo)) {
            $errMsg = "无法找到id为{$data['id']}的标";
            $this->error(L("UPDATE_FAILED").$errMsg,0);
        }

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
        $deal_model = DealModel::instance()->find($vo['id']);
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


    /**
     * 放款操作
     */
    public function enqueue()
    {
        $role = $this->getRole();
        $id = $_REQUEST['id'];
        $vo = M(MODULE_NAME)->where(array('is_delete' => 0, 'id' => $id))->find();
        $vo['isDtb'] = 0;
        $dealService = new DealService();
        $makeLoansService = new MakeLoansService();
        if($dealService->isDealDT($id)){
            $vo['isDtb'] = 1;
        }
        try {
            $makeLoansService->isOKForMakingLoans($vo);
            // 如果是 a 角提交，或者 b 角同意
            if ('a' == $role || 1 == $_REQUEST['agree']) {
                $deal_ext_info = DealExtModel::instance()->getDealExtByDealId($id);
                if ($deal_ext_info->loan_type == DealExtEnum::LOAN_AFTER_CHARGE) {
                    if (!DealModel::instance()->canUserAffordDealFee($id)) { // 负担不起
                        throw new \Exception('放款类型为收费后放款，客户账户余额不足');
                    }
                }
            }
        } catch (\Exception $e) {
            $ret['status'] = 0;
            $ret['error_msg'] = $e->getMessage();
            ajax_return($ret);
            return;
        }

        $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditEnum::SERVICE_TYPE_LOAN, 'service_id' => $id))->find();
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

        // 更新相关费率
        if ($makeLoansService->saveServiceFeeExt($vo) === false) {
            throw new \Exception("Save deal ext fail. Error:deal id:" . $vo['id']);
        }

        $GLOBALS['db']->startTrans();
        try {



            //放款添加到jobs
            $orderId = Idworker::instance()->getId();
            $function = '\core\service\deal\P2pDealGrantService::dealGrantRequest';
            $param = array(
                'orderId' => $orderId,
                'dealId'=>$id,
                'param'=>array('deal_id' => $id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']),
            );
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$id);

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
            $deal_pro_service = new ProjectService();
            $deal_pro_service->updateProBorrowed($vo['project_id']);
            $deal_pro_service->updateProLoaned($vo['project_id']);

            $job_model = new JobsModel();
            $job_model->priority = JobsEnum::PRIORITY_DEAL_GRANT;
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
     * 审核放款
     * @access public
     * @return int //0 失败 1 通过审核 2 拒绝
     */
    public function audit($data, $role, $audit, $auditType = '', $serviceId = 0, $agree = false)
    {
        $deal = $data;
        $agree = (false === $agree) ? intval($_REQUEST['agree']) : $agree;
        $operation = ServiceAuditEnum::OPERATION_SAVE;
        $param = array();
        $param['service_type'] = $auditType ? $auditType :  ServiceAuditEnum::SERVICE_TYPE_LOAN;
        if ($serviceId > 0) {
            $param['service_id']   = $serviceId;
        } else {
            $param['service_id']   = $data['id'];
        }
        $param['status']       = ServiceAuditEnum::NOT_AUDIT;
        $admin                 = \es_session::get(md5(conf("AUTH_KEY")));
        if (empty($audit)) {
            $param['standby_1']    = $data['name'];
            $param['standby_2']    = $data['create_time'];
            $operation = ServiceAuditEnum::OPERATION_ADD;
        }

        $opType = 1; //提交审核
        if ($role == 'b') { //B角审核状态
            $submitUid = $audit['submit_uid'];
            $param['audit_uid']   = $admin['adm_id'];//审核用户
            if ($agree == '1') {
                $opType = 0; //审核成功
                $param['status'] = ServiceAuditEnum::AUDIT_SUCC;
            } else {
                $opType = 2; //审核失败
                $param['status'] = ServiceAuditEnum::AUDIT_FAIL;
            }
        } else {
            $submitUid = $param['submit_uid'] = $admin['adm_id']; //提交审核的用户
        }
        $param['mark'] = $_REQUEST['return_reason'];

        $GLOBALS['db']->startTrans();
        D("ServiceAudit")->startTrans();
        try {
            $result = D('ServiceAudit')->opServiceAudit($param, $operation);
            if (!$result) {
                throw new \Exception("更新审核状态失败");
            }
            if ($opType != 0) {
                if (in_array($auditType, array(ServiceAuditEnum::SERVICE_TYPE_REPAY, ServiceAuditEnum::SERVICE_TYPE_PREPAY))) {
                    $result = $this->saveRepayOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType, $auditType, $serviceId);
                } else {
                    $result = $this->saveOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType);
                }
                if (!$result) {
                    throw new \Exception("插入操作记录失败");
                }
            }
            $GLOBALS['db']->commit();
            D("ServiceAudit")->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            D("ServiceAudit")->rollback();
            return 0; //审核错误
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
        $projectInfo      = DealProjectModel::instance()->find($deal['project_id']);
        $loan_oplog_model = new \core\dao\dealloan\LoanOplogModel();

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
        if ($auditType == ServiceAuditEnum::SERVICE_TYPE_PREPAY) {
            $repay = D('DealPrepay')->where(array('id' => intval($serviceId)))->find();
            $opStuts = 2;
        } else {
            $repay = D('DealRepay')->where(array('id' => intval($serviceId)))->find();
            $opStuts = 1;
        }
        //增加提前还款的操作记录
        $repayOpLog                   = new \core\dao\repay\DealRepayOplogModel();
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
        $repayOpLog->report_status    = $deal['report_status'];
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

    /* 待还款列表
     * 待审核列表
     */
    public function yuqi(){
        unset($_REQUEST['m'], $_REQUEST['a']);
        $this->assign("main_title",L("DEAL_YUQI"));

        // 记录查询参数并复原
        if (!empty($_REQUEST['ref'])) {
            $_REQUEST = \es_session::get('seKeyDealYuqi');
            // 记录分页参数
            if (isset($_GET['p'])) {
                $_REQUEST['p'] = (int)$_GET['p'];
                \es_session::set('seKeyDealYuqi', $_REQUEST);
            }else{
                $_GET['p'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
            }
        } elseif (isset($_REQUEST['report_status'])) {
            \es_session::set('seKeyDealYuqi', $_REQUEST);
        } else {
            \es_session::delete('seKeyDealYuqi');
        }

        $aliasRepayTable = "t1";
        $aliasDealTable = "t2";

        $userService = new UserService();

        //还款时间
        $requestRepayTimeBeginCondition = $requestRepayTimeEndCondition = '';
        if ($_REQUEST['repay_time_begin']) {
            $requestRepayTimeBegin = to_timespan($_REQUEST['repay_time_begin'] . " 0:0:0");
            $requestRepayTimeBeginCondition = " AND {$aliasRepayTable}.`repay_time` >= {$requestRepayTimeBegin} ";
        }

        if ($_REQUEST['repay_time_end']) {
            $requestRepayTimeEnd = to_timespan($_REQUEST['repay_time_end'] . " 23:59:59");
            $requestRepayTimeEndCondition = " AND {$aliasRepayTable}.`repay_time` <= {$requestRepayTimeEnd} ";
        }

        $whereDealId = "";
        // 标的编号，标的名称(模糊搜索)
        $id = trim($_REQUEST['deal_id']);
        if (is_numeric($id)) {
            $whereDealId = "and {$aliasRepayTable}.`deal_id` = {$id}";
        } elseif (trim($_REQUEST['name'])!='') {
            $sql = "select group_concat(id) from " . DealModel::instance()->tableName() . " where name like '%" . addslashes(trim($_REQUEST['name'])) . "%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if($ids) {
                $whereDealId = "and {$aliasRepayTable}.`deal_id` in " . "(" . $ids . ") ";
            } else {
                //当没有，设置永远错误的条件，让搜索的结果为空
                $whereDealId = "and 1 < 0";
            }
        }

        $reportStatus = (isset($_REQUEST['report_status']) && $_REQUEST['report_status']== '0') ? 0 : 1;
        $whereReport = " and {$aliasDealTable}.`report_status`=".$reportStatus." ";

        // 获取还款列表中审核信息的 deal_id 过滤条件，以及渲染页面 repay prepay 信息
        list($whereDealId, $repays, $prepays) = $this->getYuQiAuditDealCondition($aliasRepayTable, $id, $ids, $whereDealId);
        $this->assign('repays', $repays);
        $this->assign('prepays', $prepays);
        $this->assign('role', $this->getRole());

        // 获取所有机构
        $this->assign('dealAgency', MI('DealAgency')->where('is_effect = 1 and type='.DealAgencyEnum::TYPE_CONSULT)->getField('id,name'));

        $querystring = array();
        foreach ($_GET as $k => $v) {
            if (!empty($v)) {
                if ($k == 'deal_id') {
                    continue;
                }
                $querystring[$k] = $v;
            }
        }
        // 用于加到强制还款和审核的链接中
        $this->assign('querystring', http_build_query($querystring));

        $whereUserId = "";
        if (trim($_REQUEST['user_name'])!='') {
            $user_name = addslashes(trim($_REQUEST['user_name']));
            $userIds = UserService::getUserIdByRealName($user_name);
            if ($userIds) {
                $whereUserId = " and {$aliasRepayTable}.`user_id` in " . "(" . implode(',', $userIds) . ") ";
            } else {
                //当没有，设置永远错误的条件，让搜索的结果为空
                $whereUserId = " and 1 < 0 ";
            }
        }

        $deal_type_where = " `deal_type` = ". DealEnum::DEAL_TYPE_GENERAL;
        $isP2P = (!isset($_REQUEST['report_status']) || $_REQUEST['report_status'] == DealEnum::DEAL_REPORT_STATUS_YES) ? true :false;

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
        $holidayRepayTypeSql = $_REQUEST['holiday_repay_type'] != "" ? " and {$aliasDealTable}.`holiday_repay_type` = " . intval($_REQUEST['holiday_repay_type']) : "";

        // 项目名搜索
        $where_project = !empty($_REQUEST['project_name']) ? " AND {$aliasDealTable}.`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE name LIKE '%" . trim($_REQUEST['project_name']) . "%')" : '';
        /**
         *  SELECT count(1)  FROM firstp2p_deal_repay t1  LEFT JOIN firstp2p_deal t2
         *  ON t1.`deal_id` = t2.`id` AND  t1.`status` = 0 and 1 < 0  and t2.`report_status`=1
         *  WHERE t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4  AND t2. `deal_type` = 0
         *  AND t2.`type_id` = 43  AND t1.`repay_type` = 2  and t2.`is_during_repay` = 0
         */
        $countSql = "SELECT count(1)
            FROM " . DB_PREFIX . "deal_repay {$aliasRepayTable} " . " LEFT JOIN " . DB_PREFIX . "deal {$aliasDealTable} " .
            " ON {$aliasRepayTable}.`deal_id` = {$aliasDealTable}.`id` {$requestRepayTimeBeginCondition} {$requestRepayTimeEndCondition} AND
            {$aliasRepayTable}.`status` = 0 " . $whereDealId . $whereUserId . $whereReport .
            " WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 {$where_project} AND {$aliasDealTable}.{$deal_type_where}". $typeIdSql . $repayTypeSql . $isDuringRepaySql . $holidayRepayTypeSql ;

        /*
         *  SELECT t1.`id` as deal_repay_id, t1.`repay_type`, t1.`repay_time`, t1.`repay_money`, t1.`user_id`,
         *      t2.`id`, t2.`name`, t2.`borrow_amount`, t2.`rate`, t2.`advisory_id`,t2.`type_id`,
         *      t2.`loantype`, t2.`repay_time` as repay_period, t2.`deal_status`, t2.`parent_id`, t2.`is_during_repay`,
         *      t2.`project_id`
         *  FROM firstp2p_deal_repay t1  LEFT JOIN firstp2p_deal t2
         *  ON t1.`deal_id` = t2.`id` AND t1.`status` = 0 and 1 < 0  and t2.`report_status`=1  WHERE t2.`is_delete` = 0
         *  AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4  AND t2. `deal_type` = 0 AND t2.`type_id` = 43
         *  AND t1.`repay_type` = 2  and t2.`is_during_repay` = 0 ORDER by t2.`id` desc  limit 0, 30
         * */
        $sql = "SELECT {$aliasRepayTable}.`id` as deal_repay_id, {$aliasRepayTable}.`repay_type`, {$aliasRepayTable}.`repay_time`, {$aliasRepayTable}.`repay_money`, `status`, `loan_fee`, `guarantee_fee`, `consult_fee`, `pay_fee`, `canal_fee`, `part_repay_money`, `interest`, `principal`, {$aliasRepayTable}.`user_id`,
            {$aliasDealTable}.`id`, {$aliasDealTable}.`name`, {$aliasDealTable}.`borrow_amount`, {$aliasDealTable}.`rate`, {$aliasDealTable}.`advisory_id`,{$aliasDealTable}.`type_id`,
            {$aliasDealTable}.`loantype`, {$aliasDealTable}.`repay_time` as repay_period, {$aliasDealTable}.`deal_status`,
            {$aliasDealTable}.`parent_id`, {$aliasDealTable}.`is_during_repay`, {$aliasDealTable}.`project_id` FROM " . DB_PREFIX . "deal_repay {$aliasRepayTable} " . " LEFT JOIN " . DB_PREFIX . "deal {$aliasDealTable} " .
            " ON {$aliasRepayTable}.`deal_id` = {$aliasDealTable}.`id` {$requestRepayTimeBeginCondition} {$requestRepayTimeEndCondition} AND
            {$aliasRepayTable}.`status` = 0 " . $whereDealId . $whereUserId . $whereReport .
            " WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 {$where_project} AND {$aliasDealTable}.{$deal_type_where}". $typeIdSql . $repayTypeSql . $isDuringRepaySql . $holidayRepayTypeSql . $orderBy;

        $count = $GLOBALS['db']->get_slave()->getOne($countSql);

        $p = new Page($count, "");
        $limit = " limit {$p->firstRow}, {$p->listRows}";
        $sql .= $limit;
        $voList = $GLOBALS['db']->get_slave()->getAll($sql, array(), true);

        // 批量获取用户信息
        $userIDArr = array();
        foreach ($voList as $key => &$repay) {
            $repay['deal_info'] = DealModel::instance()->findViaSlave($repay['id']);
            $repay['deal_ext'] = DealExtModel::instance()->getInfoByDeal($repay['id']);
            $repay['repay_alarm'] = 0; // repay_alarm是判断该笔还款是否超过1小时，方便运营人员查看
            if($repay['is_during_repay'] == DealEnum::DEAL_DURING_REPAY){
                $now = time();
                // 处于正在还款中，则查询幂等表(type-3,result-0 还款未回调)
                $condition =sprintf("`repay_id`= '%d' AND `type`= '%d' AND `result` = '%d' ORDER BY `id` DESC limit 1",
                   $repay['deal_repay_id'], P2pDepositoryEnum::IDEMPOTENT_TYPE_REPAY, P2pIdempotentEnum::RESULT_WAIT );
                $repayResult = SupervisionIdempotentModel::instance()->findByViaSlave($condition);
                if(!empty($repayResult)){
                    // 存在,并且创建时间与当前时间相差1小时，则报警，将相应页面上的的标红
                    // 3600秒=1小时
                    $repay['repay_alarm'] = (($now - $repayResult['create_time']) >= 3600) ? 1 : 0;
                }else{
                    // type-11,result-0 代扣未回调
                    $condition =sprintf("`repay_id`= '%d' AND `type`= '%d' AND `result` = '%d' ORDER BY `id` DESC limit 1",
                       $repay['deal_repay_id'], P2pDepositoryEnum::IDEMPOTENT_TYPE_DK, P2pIdempotentEnum::RESULT_WAIT );
                    $dkResult = SupervisionIdempotentModel::instance()->findByViaSlave($condition);
                    $repay['repay_alarm'] = (!empty($dkResult) && (($now - $dkResult['create_time']) >= 3600)) ? 1 : 0;
                }
            }

            $repay['borrow_amount'] = sprintf("%.2f", $repay['borrow_amount']);
            // BY_DAY: 到期支付本金利息
            if($repay['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
                $repay['repay_period'] = $repay['repay_period'] . "天";
            } else {
                $repay['repay_period'] = $repay['repay_period'] . "月";
            }
            $repay['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$repay['loantype']];
            $repay['rate'] = sprintf("%.5f", $repay['rate']);

            // userId去重
            if (!isset($userIDArr[$repay['user_id']])){
                $userIDArr[$repay['user_id']] = $repay['user_id'];
            }

            if(to_date($repay['repay_time'], "Ymd") < to_date(get_gmtime(), "Ymd")) {
                $repay['is_repay_delayed'] = 1;
            } else {
                $repay['is_repay_delayed'] = 0;
            }
            $repay['repay_time'] = to_date($repay['repay_time'], "Y-m-d");
            $repay['repay_money'] = sprintf("%.2f",$repay['repay_money']);
       }

        //用户信息
        $listOfBorrower = UserService::getUserInfoByIds($userIDArr,true);
        foreach($userIDArr as $oneUserId){
            $money = AccountService::getAccountMoney($oneUserId,UserAccountEnum::ACCOUNT_FINANCE);
            $listOfBorrower[$oneUserId]['money'] = $money['money'];
            $listOfBorrower[$oneUserId]['user_name_url'] = get_user_url($listOfBorrower[$oneUserId],'user_name');
            $listOfBorrower[$oneUserId]['real_name_url'] = get_user_url($listOfBorrower[$oneUserId],'real_name');
        }

        //余额不足
        //前端页面标黄
        foreach ($voList as &$repay){
           if (bccomp($listOfBorrower[$repay['user_id']]['money'], $repay['repay_money'], 2) == -1) {
                $repay['insufficient'] = 1;
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

        foreach($voList as &$vo){
            $vo['type_name'] = $loanTypes[$vo['type_id']];

            $partRepayInfo = DealPartRepayService::getPartRepayMoney($vo, 0);
            $vo['month_repay_money_principal'] = $partRepayInfo['needToRepayPrincipal'] ?: 0;
            $vo['month_repay_money_interest'] = $partRepayInfo['needToRepayInterest'] ?: 0;

            $vo['month_has_repay_money_all'] = $vo['status'] != 0 ? number_format($vo['repay_money'], 2) : ($vo['part_repay_money'] > 0 ? number_format($vo['part_repay_money'], 2) : 0);
            $vo['month_need_all_repay_money'] = $vo['status'] == 0 ? number_format($partRepayInfo['needToRepayTotal'], 2) : 0;
        }
        $this->assign('deal_loan_type',$dealLoanType);
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayEnum::$repayTypeMsg[DealRepayEnum::DEAL_REPAY_TYPE_SELF]);
        $this->assign('deal_repay_type',DealRepayEnum::$repayTypeMsg);
        $this->assign('holiday_repay_types',DealEnum::$HOLIDAY_REPAY_TYPES);
        $this->assign('borrower_list',$listOfBorrower);
        $this->assign('type_id', $_REQUEST['type_id']);
        $this->assign('repay_type', $_REQUEST['repay_type']);
        $this->assign('list', $voList);
        $this->assign('sort', $sort);
        $this->assign('sortImg', $sortImg);
        $this->assign('sortType', $sortAlt);
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->display ('yuqi');
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
        // 获取用户角色
        $role = $this->getRole();
        $limitDays = intval(app_conf('AB_REPAY_TIME_LIMIT'));
        if ($limitDays <= 0) {
            $limitDays = 3;
        }
        // B角审核筛选申请人
        if ($role == 'b') {
            // 获取强制还款和提前还款未处理的请求
            $conds = array(
                'service_type' => array('in', implode(',', array(ServiceAuditEnum::SERVICE_TYPE_REPAY, ServiceAuditEnum::SERVICE_TYPE_PREPAY))),
                'status' =>  array('in', implode(',', array(ServiceAuditEnum::NOT_AUDIT))));
            // 只获取最近几天的提前还款和强制还款请求
            $conds['create_time'] = array('gt', time() - 86400 * $limitDays);
            if ($_REQUEST['service_type']) {
                $conds['service_type'] = $_REQUEST['service_type'];
            }
            // 根据提交账户名称获取账户id
            if ($_REQUEST['submit_uid']) {
                $adminId = M('Admin')->where('adm_name="'.addslashes($_REQUEST['submit_uid']).'"')->getField('id');
                $conds['submit_uid'] = $adminId;
            }
            $auditList = D('ServiceAudit')->where($conds)->field('service_id, status, submit_uid, service_type')->select();
            $audits = $repays = array();
            foreach ($auditList as $row) {
                $row['submit_user_name'] = $row['submit_uid'] ? get_admin_name($row['submit_uid']) : '';
                if ($row['service_type'] == ServiceAuditEnum::SERVICE_TYPE_REPAY) {
                    $audits[$row['service_id']] = $row;
                } else {
                    $prepayAudits[$row['service_id']] = $row;
                }
            }
            $idsRepay = $idsPrepay = $prepays = array();
            if($audits || $prepayAudits) {
                // 根据prepay_id获取deal_id
                if ($prepayAudits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_prepay where id in (" . implode(',', array_keys($prepayAudits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $prepays[$row['deal_id']] = $prepayAudits[$row['id']];
                        $idsPrepay[] = $row['deal_id'];
                    }
                }
                // 根据repay_id获取deal_id
                if ($audits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_repay where id in (" . implode(',', array_keys($audits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $idsRepay[] = $row['deal_id'];
                        $repays[$row['deal_id']] = $audits[$row['id']];
                    }
                }
                // 获取$idsPrepay和$idsRepay的交集
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

            } else {
                // 当没有，设置永远错误的条件，让搜索的结果为空
                $whereDealId = " and 1 < 0 ";
            }
        } else {
            // 获取还款和提前还款的,并且创建时间大于指定时间的
            $conds = array('service_type' => array('in', implode(',', array(ServiceAuditEnum::SERVICE_TYPE_REPAY, ServiceAuditEnum::SERVICE_TYPE_PREPAY))));
            // 只获取最近几天的提前还款和强制还款请求
            $conds['create_time'] = array('gt', time() - 86400 * $limitDays);
            $auditList = D('ServiceAudit')->where($conds)->field('service_id, status, submit_uid, service_type')->select();
            $audits = $repays = $prepays = $prepayAudits = array();
            foreach ($auditList as $row) {
                $row['submit_user_name'] = $row['submit_uid'] ? get_admin_name($row['submit_uid']) : '';
                if ($row['service_type'] == ServiceAuditEnum::SERVICE_TYPE_REPAY) {
                    $audits[$row['service_id']] = $row;
                } else {
                    $prepayAudits[$row['service_id']] = $row;
                }
            }
            // 根据repay_id获取deal_id
            if($audits) {
                if ($audits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_repay where id in (" . implode(',', array_keys($audits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $repays[$row['deal_id']] = $audits[$row['id']];
                    }
                }
            }
            // 根据prepay_id获取deal_id
            if($prepayAudits) {
                if ($prepayAudits) {
                    $sql = "select id, deal_id from " . DB_PREFIX . "deal_prepay  where id in (" . implode(',', array_keys($prepayAudits)) . ")";
                    $result = $GLOBALS['db']->getAll($sql);
                    foreach ($result as $row) {
                        $prepays[$row['deal_id']] = $prepayAudits[$row['id']];
                    }
                }
            }
            //根据审核状态来筛dealId
            //只有审核状态不为0(全部)，才进入以下分支
            if ($_REQUEST['audit_status'] && $whereDealId != 'and 1 < 0') {
                $tmpArray = $filterList = array();
                // filterList包含强制还款和提前还款的
                foreach ($repays as $k => $v) {
                    $filterList[$k] = $v;
                }
                foreach ($prepays as $k => $v) {
                    $filterList[$k] = $v;
                }
                // $id不为空，则将$id赋值给$ids
                if ($id) {
                    $ids = $id;
                }
                // 将$id变为数组
                if (!empty($ids)) {
                    $ids = explode(',', $ids);
                }
                if (in_array($_REQUEST['audit_status'], array(ServiceAuditEnum::NOT_AUDIT, ServiceAuditEnum::AUDIT_SUCC, ServiceAuditEnum::AUDIT_FAIL))) {
                    // 审核状态为:未审核,审核成功,审核失败
                    foreach ($filterList as $dealId => $row) {
                        if ($_REQUEST['audit_status'] == $row['status']) {
                            $tmpArray[] = $dealId;
                        }
                    }
                    // 获取$ids和$idsRepay的交集
                    if (!empty($ids)) {
                        $tmpArray = array_intersect($ids, $tmpArray);
                    }
                    if (!empty($tmpArray)) {
                        $whereDealId = "and {$aliasRepayTable}.`deal_id` in " . "(" . implode(',', $tmpArray) . ") ";
                    } else {
                        // 如果筛选不出指定审核状态的标的，则返回的结果为空
                        $whereDealId = "and 1 < 0 ";
                    }
                } else {
                    // 审核状态为:还款待处理
                    // 如果$id和$ids都为空，则会把提交审核的标的剔除出去
                    foreach ($filterList as $dealId => $row) {
                        if (($id && $id != $dealId) || ($ids && in_array($dealId, $ids))) {
                            continue;
                        }
                        $tmpArray[] = $dealId;
                    }
                    if (!empty($ids)) {
                        // $ids 和 array_keys($filterList)的差集
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

    /**
     * 导出待还款列表
     * @param int $page
     */
    public function export_repay_list($page = 1)
    {
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayEnum::$repayTypeMsg[DealRepayEnum::DEAL_REPAY_TYPE_SELF]);

        //设置执行没有超时时间
        set_time_limit(0);
        $isP2P = (!isset($_REQUEST['report_status']) || $_REQUEST['report_status'] == '1') ? true :false;
        $aliasRepayTable = "t1";
        $aliasDealTable = "t2";
        $limit = " ORDER BY {$aliasRepayTable}.`id` ASC LIMIT " .(($page - 1)*intval(app_conf("BATCH_PAGE_SIZE"))).",".(intval(app_conf("BATCH_PAGE_SIZE")));

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
            $user_name = addslashes(trim($_REQUEST['user_name']));
            $userIds = UserService::getUserIdByRealName($user_name);
            if ($userIds) {
                $whereUserId = " and {$aliasRepayTable}.`user_id` in " . "(" . implode(',', $userIds) . ") ";
            } else {
                //当没有，设置永远错误的条件，让搜索的结果为空
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
        $deal_type_where = " `deal_type` = ". DealEnum::DEAL_TYPE_GENERAL;

        // 搜索条件里加上repay_type、type_id和is_during_repay
        $typeIdSql = !empty($_REQUEST['type_id']) ? " AND {$aliasDealTable}.`type_id` = ".$_REQUEST['type_id']." "  : "";
        $repayTypeSql = $_REQUEST['repay_type'] != "" ? " AND {$aliasRepayTable}.`repay_type` = ".$_REQUEST['repay_type']." "  : "";
        $isDuringRepaySql = $_REQUEST['is_during_repay'] != "" ? " and {$aliasDealTable}.`is_during_repay` = " . intval($_REQUEST['is_during_repay']) : "";
        $holidayRepayTypeSql = $_REQUEST['holiday_repay_type'] != "" ? " and {$aliasDealTable}.`holiday_repay_type` = " . intval($_REQUEST['holiday_repay_type']) : "";

        // 报备状态
        $reportStatus = (isset($_REQUEST['report_status']) && $_REQUEST['report_status']== '0') ? 0 : 1;
        $whereReport = " and {$aliasDealTable}.`report_status`=".$reportStatus." ";


        $sql = "SELECT {$aliasRepayTable}.`repay_type`, {$aliasRepayTable}.`repay_time`, {$aliasRepayTable}.`repay_money`, `status`, `loan_fee`, `guarantee_fee`, `consult_fee`, `pay_fee`, `canal_fee`, `part_repay_money`, `interest`, `principal`, {$aliasRepayTable}.`user_id`,
            {$aliasDealTable}.`id`, {$aliasDealTable}.`prepay_rate`,{$aliasDealTable}.`name`, {$aliasDealTable}.`borrow_amount`, {$aliasDealTable}.`rate`, {$aliasDealTable}.`type_id`,
            {$aliasDealTable}.`loantype`, {$aliasDealTable}.`repay_time` as repay_period, {$aliasDealTable}.`deal_status`, {$aliasDealTable}.`is_during_repay`,
            {$aliasDealTable}.`parent_id`,{$aliasDealTable}.`prepay_days_limit`,{$aliasDealTable}.`project_id`,{$aliasDealTable}.`loan_fee_rate`,{$aliasDealTable}.`consult_fee_rate`,{$aliasDealTable}.`guarantee_fee_rate`,{$aliasDealTable}.`pay_fee_rate`,{$aliasDealTable}.`canal_fee_rate` FROM " . DB_PREFIX . "deal_repay {$aliasRepayTable} " . " LEFT JOIN " . DB_PREFIX . "deal {$aliasDealTable} " .
            " ON {$aliasRepayTable}.`deal_id` = {$aliasDealTable}.`id` {$requestRepayTimeBeginCondition} {$requestRepayTimeEndCondition} AND
        {$aliasRepayTable}.`status` = 0 " . $whereDealId . $whereUserId . $whereReport . $where_audit_deal_id .
        " WHERE {$aliasDealTable}.`is_delete` = 0 AND {$aliasDealTable}.`publish_wait` = 0 AND {$aliasDealTable}.`deal_status` = 4 AND {$aliasDealTable}.{$deal_type_where} {$where_project}" . $typeIdSql . $repayTypeSql . $isDuringRepaySql . $holidayRepayTypeSql . $limit;

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
            $vo['repay_type_name'] = DealRepayEnum::$repayTypeMsg[$vo['repay_type']];
            // userId去重
            if (!isset($userIDArr[$vo['user_id']])){
                $userIDArr[$vo['user_id']] = $vo['user_id'];
            }
        }
        //用户信息
        $listOfBorrower = UserService::getUserInfoByIds($userIDArr,true);
        foreach($userIDArr as $oneUserId){
            $money = AccountService::getAccountMoney($oneUserId,UserAccountEnum::ACCOUNT_FINANCE);
            $listOfBorrower[$oneUserId]['money'] = $money['money'];
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportDealRepayList',
                'analyze' => $sql,
            )
        );

        if($repayList) {
            register_shutdown_function(array(&$this, 'export_repay_list'), $page + 1);
            if($page == 1) {
                $content = iconv("utf-8", "gbk", "\t\t\t\t{$_REQUEST['repay_time']}待还款列表\n编号,项目名称,借款标题,借款金额,年化借款利率,借款期限,放款日期,费用收取方式,还款方式,资产管理方,借款人用户名,借款人姓名,借款人id,借款人账号余额,最近一期还款日,还款金额,出借状态,提前还款/回购限制,借款手续费,借款手续费收取方式,代销分期前收金额,代销分期后收金额,借款咨询费,借款咨询费收取方式,借款担保费,借款担保费收取方式,支付服务费,支付服务费收取方式,提前还款违约金系数,渠道服务费,渠道服务费收取方式,产品类别,本期还款类型,约定还款日");
                $content = $content . "\n";
            }

            $dealAgency = MI('DealAgency')->where('is_effect = 1 and type=2')->getField('id,name');
            foreach($repayList as $repay) {
                $deal_info = DealModel::instance()->find($repay['id']);
                $deal_ext = DealExtModel::instance()->getInfoByDeal($repay['id']);
                $deal_project = M("Deal_project")->field("name")->where("id = '" .$repay['project_id']. "'")->find();

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
                $formatRepay['dealagency'] = iconv("utf-8", "gbk", $dealAgency[$deal_info['advisory_id']]);
                $formatRepay['user_name'] = iconv("utf-8", "gbk", $listOfBorrower[$repay['user_id']]['user_name']);
                $formatRepay['real_name'] = iconv("utf-8", "gbk", $listOfBorrower[$repay['user_id']]['real_name']);
                $formatRepay['user_id'] = $repay['user_id'];
                $formatRepay['money'] = $listOfBorrower[$repay['user_id']]['money'];
                $formatRepay['repay_time'] = iconv("utf-8", "gbk", to_date($repay['repay_time'], "Y年m月d日"));


                $partRepayInfo = DealPartRepayService::getPartRepayMoney($repay, 0);
                $formatRepay['repay_money'] = sprintf("%.2f", $partRepayInfo['needToRepayTotal']);


                $formatRepay['deal_status'] = iconv("utf-8", "gbk", l("DEAL_STATUS_". $repay['deal_status'])  . ( $repay['is_during_repay'] == 1 ? '正在还款' : ''));
                $formatRepay['prepay_days_limit'] = $repay['prepay_days_limit'];
                $formatRepay['loan_fee_rate'] = sprintf("%.2f", $repay['loan_fee_rate']) . "%";
                $formatRepay['loan_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtEnum::$fee_rate_type_name_map[$deal_ext['loan_fee_rate_type']]);
                $loan_fee_ext_arr = json_decode($deal_ext['loan_fee_ext'], true);
                $formatRepay['loan_fee_proxy_head'] = (DealExtEnum::FEE_RATE_TYPE_PROXY == $deal_ext['loan_fee_rate_type']) ? $loan_fee_ext_arr[0] : '';
                $formatRepay['loan_fee_proxy_end'] = (DealExtEnum::FEE_RATE_TYPE_PROXY == $deal_ext['loan_fee_rate_type']) ? array_pop($loan_fee_ext_arr) : '';
                $formatRepay['consult_fee_rate'] = sprintf("%.2f", $repay['consult_fee_rate']) . "%";
                $formatRepay['consult_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtEnum::$fee_rate_type_name_map[$deal_ext['consult_fee_rate_type']]);
                $formatRepay['guarantee_fee_rate'] = sprintf("%.2f", $repay['guarantee_fee_rate']) . "%";
                $formatRepay['guarantee_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtEnum::$fee_rate_type_name_map[$deal_ext['guarantee_fee_rate_type']]);
                $formatRepay['pay_fee_rate'] = sprintf("%.2f", $repay['pay_fee_rate']) . "%";
                $formatRepay['pay_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtEnum::$fee_rate_type_name_map[$deal_ext['pay_fee_rate_type']]);
                $formatRepay['prepay_rate'] = sprintf("%.2f", $repay['prepay_rate']) . "%";
                $formatRepay['canal_fee_rate'] = sprintf("%.2f", $repay['canal_fee_rate']) . "%";
                $formatRepay['canal_fee_rate_type_name'] = iconv("utf-8", "gbk", DealExtEnum::$fee_rate_type_name_map[$deal_ext['canal_fee_rate_type']]);
                $formatRepay['deal_type_name'] = iconv("utf-8", "gbk", $repay['deal_type_name']);
                $formatRepay['repay_type_name'] = iconv("utf-8", "gbk", $repay['repay_type_name']);
                $formatRepay['holiday_repay_type'] = iconv("utf-8", "gbk", DealEnum::$HOLIDAY_REPAY_TYPES[$deal_info['holiday_repay_type']]);
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

    /**
     * 下载还款账户详情
     */
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
     * 部分还款
     * @return [type] [description]
     */
    public function show_part_repay()
    {
        $role = $this->getRole();
        $this->assign('role', $role);

        $drs = new DealRepayService();
        $id = intval($_REQUEST['deal_id']);
        $deal = DealModel::instance()->find($id);
        $repayUser = $drs->getAllRepayAccountInfo($deal);
        // 2 所有还款角色(还款方)
        $this->assign("repay_user",$repayUser);
        $this->display();
    }

    public function do_part_repay()
    {
        set_time_limit(0);
        // 1 检查form参数
        $deal_id = intval($_REQUEST['deal_id']);
        $deal_repay_id = intval($_REQUEST['deal_repay_id']);
        $money = $_REQUEST['money'];
        $repayType = $_REQUEST['repay_user_type_part'] <> 0 ? intval($_REQUEST['repay_user_type_part']):0;

        if($deal_id == 0){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            set_time_limit(30);
            $this->error("参数错误");
        }

        // 2 检查标的及相关参数
        $deal = DealModel::instance()->getDealInfo($deal_id);
        if(!$deal || $deal['deal_status']!=DealEnum::DEAL_STATUS_REPAY){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('部分还款失败 deal_id:'.$deal_id.' 借款状态错误', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,借款状态错误");
        }
        if($deal['report_status'] != DealEnum::DEAL_REPORT_STATUS_YES){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('部分还款失败 deal_id:'.$deal_id.' 借款正在还款中', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error('没有报备，不允许部分还款');
        }
        if($deal['is_during_repay'] == DealEnum::DEAL_DURING_REPAY){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('部分还款失败 deal_id:'.$deal_id.' 借款正在还款中', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,借款正在还款中");
        }

        //逐一执行还款
        $id = intval($deal_repay_id);
        if($id == 0){
            save_log('部分还款失败 deal_id:'.$deal_id.' 还款id缺失', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,还款id缺失");
        }

        $authKey =conf ("AUTH_KEY");
        $admInfo = \es_session::get(md5($authKey));

        $orderId = Idworker::instance()->getId();

        // 部分还款校验 START
        if ($money <= 0) $this->error("金额必须为正");
        $dealRepay = DealRepayModel::instance()->find($deal_repay_id);
        if (empty($dealRepay)) {
            $this->error("还款计划不存在");
        }

        $partRepayMoneyReal = 0;
        $partRepayMoneyInfo = DealPartRepayService::getPartRepayMoney($dealRepay, $money);
        // 可以足额还
        if ($money >= $partRepayMoneyInfo['needToRepayTotal']) {
            $this->error("最后一笔请走正常还款逻辑");
        }
        if (!$partRepayMoneyInfo['isFeeRepayed'] && $money <= $partRepayMoneyInfo['totalFee']) {
            $this->error("金额不够还费用");
        }
        $dealLoadModel = new DealLoadModel();
        $dealLoadList  = $dealLoadModel->getDealLoanList($deal_id);

        $userRepayCnt = 0;
        foreach ($dealLoadList as $dealLoan) {

            $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND `status` = 0";
            $condition = sprintf($condition, $deal_repay_id, $dealLoan->id, $dealLoan->user_id);

            //根据还款记录ID，投标记录ID，投资人ID
            $loanRepayList = DealLoanRepayModel::instance()->findAll($condition);

            // 部分还款拆分用户金额
            $partRepayMoneyList = DealPartRepayService::getPartRepayInfo(
                $partRepayMoneyInfo['repayMoneyWithoutFee'],
                $partRepayMoneyInfo['needToRepayInterest'],
                $partRepayMoneyInfo['needToRepayPrincipal'],
                $loanRepayList, "{$orderId}_{$dealLoan['id']}");

            foreach ($loanRepayList as $loanRepay) {
                if($loanRepay['money'] !=0) {
                    // 部分还款，替换金额
                    if (!isset($partRepayMoneyList[$loanRepay['id']])) {
                        continue;
                    } else {
                        $userRepayCnt++;
                    }
                }
            }
        }
        if ($userRepayCnt <= 0) $this->error('金额不够分，至少需要还一人');
        // END

        $db = Db::getInstance('firstp2p');
        try{
            $db->startTrans();

            // 还款方法加入到jobs中
            $job_model = new JobsModel();
            $param = array('deal_repay_id' => $id,'repayAccountType' => $repayType,'admin' => $admInfo, 'submitUid' => 0);

            $function = '\core\service\repay\P2pDealRepayService::dealRepayRequest';
            $param = array('orderId'=>$orderId,'dealRepayId'=>$id,'repayAccountType' => $repayType,'params'=>$param, 'partRepayMoney' => $money);

            $job_model->priority = JobsEnum::PRIORITY_P2P_REPAY_REQUEST;

            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                save_log('部分还款失败 deal_id:' . $deal_id . ' repay_id:' . $id, C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                throw new \Exception("JobsModel操作失败");
            }

            // 标的状态置为正在还款
            $updateRes = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
            if($updateRes == false){
                throw new \Exception("标的操作失败");
            }
            $db->commit();
        }catch(\Exception $e){
            $db->rollback();
            $this->error('更新数据库失败！ 失败原因:' . $e->getMessage());
        }
        set_time_limit(30);
        save_log('do_part_repay id:'.$deal_id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
        $this->success("操作成功");
    }

    /**
     * 强制还款页面
     */
    public function force_repay()
    {
        $role = $this->getRole();
        $this->assign('role', $role);
        $this->assign('return_type_list', self::$returnTypes);//回退选项

        $id = intval($_REQUEST['deal_id']);
        if($id == 0){
            $this->error("参数错误");
        }
        $deal = DealModel::instance()->find($id);
        if(!$deal){
            $this->error("标的不存在");
        }
        if($deal['report_status'] != DealEnum::DEAL_REPORT_STATUS_YES){
            $this->error('没有报备，不允许强制还款');
        }

        $deal['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($deal['id'])){
            $deal['isDtb'] = 1;
        }

        // 1 标的数据
        $this->assign("deal",$deal);


        $borrowUser = UserService::getUserById($deal['user_id']);

        $drs = new DealRepayService();
        $repayUser = $drs->getAllRepayAccountInfo($deal);
        // 2 所有还款角色(还款方)
        $this->assign("repay_user",$repayUser);

        $deal_ext = DealExtModel::instance()->getDealExtByDealId($id);
        $loan_arr = $deal_ext['loan_fee_ext'] ? json_decode($deal_ext['loan_fee_ext'], true) : array();
        $consult_arr = $deal_ext['consult_fee_ext'] ? json_decode($deal_ext['consult_fee_ext'], true) : array();
        $guarantee_arr = $deal_ext['guarantee_fee_ext'] ? json_decode($deal_ext['guarantee_fee_ext'], true) : array();
        $pay_arr = $deal_ext['pay_fee_ext'] ? json_decode($deal_ext['pay_fee_ext'], true) : array();
        $canal_arr = $deal_ext['canal_fee_ext'] ? json_decode($deal_ext['canal_fee_ext'], true) : array();

        // 3 还款列表
        $loan_list = DealRepayModel::instance()->findAll("deal_id =".$id." order by id asc");
        for ($i = 0; $i < count($loan_list); $i++) {
            $loan_list[$i]['allow_repay'] = $loan_list[$i]->canRepay();
            $loan_list[$i]['repay_day'] = to_date($loan_list[$i]['repay_time'], 'Y-m-d');
            // $loan_list[$i]['month_has_repay_money_all'] = $loan_list[$i]['status'] != 0 ? number_format($loan_list[$i]['repay_money'], 2) : 0;
            // $loan_list[$i]['month_need_all_repay_money'] = $loan_list[$i]['status'] == 0 ? number_format($loan_list[$i]['repay_money'], 2) : 0;
            $loan_list[$i]['month_repay_money'] = number_format($loan_list[$i]['principal'] + $loan_list[$i]['interest'], 2);
            $loan_list[$i]['status_text'] = DealRepayEnum::$statusMsg[$loan_list[$i]['status']];
            $loan_list[$i]['impose_money'] = $loan_list[$i]->feeOfOverdue();

            $partRepayInfo = DealPartRepayService::getPartRepayMoney($loan_list[$i], 0);
            $loan_list[$i]['month_repay_money_principal'] = $partRepayInfo['needToRepayPrincipal'] ?: 0;
            $loan_list[$i]['month_repay_money_interest'] = $partRepayInfo['needToRepayInterest'] ?: 0;

            $loan_list[$i]['month_has_repay_money_all'] = $loan_list[$i]['status'] != 0 ? number_format($loan_list[$i]['repay_money'], 2) : ($loan_list[$i]['part_repay_money'] > 0 ? number_format($loan_list[$i]['part_repay_money'], 2) : 0);
            $loan_list[$i]['month_need_all_repay_money'] = $loan_list[$i]['status'] == 0 ? number_format($partRepayInfo['needToRepayTotal'], 2) : 0;

        }
        // 手续费
        if ($loan_arr === array()) {
            // 年化收 还是 固定比例收
            if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD))) {
                $loan_fee_rate = $deal['loan_fee_rate'];
            } else {
                $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
            }
            $loan_fee = $deal->floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);
        } else {
            $loan_fee = $loan_arr[0];
        }
        // 咨询费
        if ($consult_arr === array()) {
            $consult_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
            $consult_fee = $deal->floorfix($deal['borrow_amount'] * $consult_fee_rate / 100.0);
        } else {
            $consult_fee = $consult_arr[0];
        }
        // 担保费
        if ($guarantee_arr === array()) {
            $guarantee_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
            $guarantee_fee = $deal->floorfix($deal['borrow_amount'] * $guarantee_fee_rate / 100.0);
        } else {
            $guarantee_fee = $guarantee_arr[0];
        }
        // 支付服务费
        if ($pay_arr === array()) {
            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $pay_fee = $deal->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);
        } else {
            $pay_fee = $pay_arr[0];
        }
        // 渠道服务费
        if ($canal_arr === array()) {
            $canal_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['canal_fee_rate'], $deal['repay_time'], false);
            $canal_fee = $deal->floorfix($deal['borrow_amount'] * $canal_fee_rate / 100.0);
        } else {
            $canal_fee = $canal_arr[0];
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
            "status_text" => DealRepayEnum::$statusMsg[1],
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
        $this->assign("loan_list",$loan_list);
        $this->assign('today',to_date(time(), 'Y-m-d'));
        $repayUserType = 0;

        if ($role == 'b') {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis) {
                $this->assign('chk_ids', explode(',', $redis->get('admin_cache_service_audit_force_repay_chk_value_'.$id)));
                $this->assign('ignore_impose_money', intval($redis->get('admin_cache_service_audit_force_repay_ignore_ignore_impose_money_'.$id)));
                $this->assign('repay_user_type', $redis->get('admin_cache_service_audit_force_repay_user_type_'.$id));
                $repayUserType = $redis->get('admin_cache_service_audit_force_repay_user_type_'.$id);
            }
        }

        // 4 还款方各角色的网贷账户余额
        // 借款方
        $borrowerMoney = AccountService::getAccountMoney($borrowUser['id'],UserAccountEnum::ACCOUNT_FINANCE);
        $borrowUser['money'] = $borrowerMoney['money'];
        $dealService = new DealService();
        //代垫户
        $advanceAgencyUserId = $dealService->getRepayUserAccount($id,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN);
        $advanceAgencyUserInfo = AccountService::getAccountMoney(intval($advanceAgencyUserId),UserAccountEnum::ACCOUNT_REPLACEPAY);
        $advanceAgencyUserInfo = array_merge(array('id'=>intval($advanceAgencyUserId)),$advanceAgencyUserInfo);
        // 担保户(直接代偿)
        $agencyUserId = $dealService->getRepayUserAccount($id,DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG);
        $agencyUserInfo = AccountService::getAccountMoney(intval($agencyUserId),UserAccountEnum::ACCOUNT_GUARANTEE);
        $agencyUserInfo= array_merge(array('id'=>intval($agencyUserId)),$agencyUserInfo);
        // 代充值户
        $generationRechargeUserId = $dealService->getRepayUserAccount($id,DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI);
        $generationRechargeUserInfo = AccountService::getAccountMoney(intval($generationRechargeUserId),UserAccountEnum::ACCOUNT_RECHARGE);
        $generationRechargeUserInfo = array_merge(array('id'=>intval($generationRechargeUserId)),$generationRechargeUserInfo);

        // 担保户(间接代偿) 去掉间接代偿
        //$indirectAencyUserId = $dealService->getRepayUserAccount($id, DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG);
        //$indirectAencyUserInfo = AccountService::getAccountMoney(intval($indirectAencyUserId),UserAccountEnum::ACCOUNT_GUARANTEE);
        //$indirectAencyUserInfo = array_merge(array('id'=>intval($indirectAencyUserId)),$indirectAencyUserInfo);

        if ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN) {
            $payer = $advanceAgencyUserInfo;
        } elseif ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG) {
            $payer = $agencyUserInfo;
        }elseif ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI) {
            $payer = $generationRechargeUserInfo;
        }elseif ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) {
            $payer = $agencyUserInfo;
        }else {
            $payer = $borrowUser;
        }
        $userMoney = $payer['money'];

        $this->assign('agency_money',$agencyUserInfo['money']);
        $this->assign('generation_recharge_money',$generationRechargeUserInfo['money']);
        $this->assign('user_money',$userMoney);
        $this->assign('advance_money',$advanceAgencyUserInfo['money']);
        //$this->assign('indirect_agency_money',$indirectAencyUserInfo['money']);

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
        $this->display ('force_repay');
    }

    /**
     * 提交请求(强制还款，提前还款)
     */
    public function submitAudit()
    {
        $dealId = intval($_REQUEST['deal_id']);
        $role = $this->getRole();

        // 检查标的
        $vo = DealModel::instance()->getDealInfo($dealId);
        if (empty($vo)) {
            $result = array();
            $result['errCode'] = -1;
            $result['errMsg'] = "标的错误";
            ajax_return($result);
            return;
        }
        // 查出未审核的提前还款申请记录
        if ($_REQUEST['audit_type'] == ServiceAuditEnum::SERVICE_TYPE_PREPAY && $_REQUEST['deal_repay_id'] == '') {
            $sql = "select * from ".DB_PREFIX."deal_prepay where deal_id= $dealId and status =0";
            $res = $GLOBALS['db']->getRow($sql);
            $_REQUEST['deal_repay_id'] = $res['id'];
        }
        // 检查是否选择了某期还款
        $ids = explode(',', $_REQUEST['deal_repay_id']);
        if (!is_array($ids)) {
            $result = array();
            $result['errCode'] = 1;
            $result['errMsg'] = "请选择还款";
            ajax_return($result);
            return;
        }
        // 提交还款请求(可以选择多期还款)
        foreach ($ids as $index => $id) {
            if ($id <= 0) {
                $result['errCode'] = 1;
                $result['errMsg'] = "错误的还款ID";
                ajax_return($result);
                return;
            }
            $auditType = intval($_REQUEST['audit_type']);
            if (!in_array($auditType, array(ServiceAuditEnum::SERVICE_TYPE_REPAY, ServiceAuditEnum::SERVICE_TYPE_PREPAY))) {
                $result['errCode'] = 1;
                $result['errMsg'] = "提交审核类型错误";
                ajax_return($result);
                return;
            }
            $audit = D('ServiceAudit')->where(array('service_type' => $auditType, 'service_id' => $id))->find();
            if ($index == 0 && $audit['service_id'] > 0 && $role != 'b') {
                if ($audit['service_type'] == ServiceAuditEnum::SERVICE_TYPE_REPAY && $audit['service_type'] == ServiceAuditEnum::NOT_AUDIT) {
                    $repay = D('DealRepay')->where(array('id' => intval($audit['service_id']), 'deal_id' => $dealId))->find();
                    if ($repay) {
                        $result['errCode'] = 1;
                        $result['errMsg'] = "该标的已经在审核中，请审核后再提交";
                        ajax_return($result);
                        return;
                    }
                }
                if ($audit['service_type'] == ServiceAuditEnum::SERVICE_TYPE_PREPAY && $audit['service_type'] == ServiceAuditEnum::NOT_AUDIT) {
                    $prepay = D('DealPrepay')->where(array('id' => intval($audit['service_id']), 'deal_id' => $dealId))->find();
                    if ($prepay) {
                        $result['errCode'] = 1;
                        $result['errMsg'] = "该标的已经在审核中，请审核后再提交!";
                        ajax_return($result);
                        return;
                    }
                }
            }
            // 如果结果为0，则是数据库保存失败
            $auditResult = $this->audit($vo, $role, $audit, $auditType, $id);
            if($auditResult == 0){
                $result['errCode'] = 1;
                $result['errMsg'] = "提交审核失败";
                ajax_return($result);
                return;
            }
        }
        // 成功后设置redis的key值
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
        $result = array();
        $result['errCode'] = 0;
        $result['errMsg'] = "提交审核成功";
        ajax_return($result);
        return;
    }

    /**
     * 强制还款
     * @actionlock
     */
    public function do_force_repay()
    {
        set_time_limit(0);
        // 1 检查form参数
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
        // 2 检查标的及相关参数
        $deal = DealModel::instance()->getDealInfo($id);
        if(!$deal || $deal['deal_status']!=DealEnum::DEAL_STATUS_REPAY){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('强制还款失败 deal_id:'.$deal_id.' 借款状态错误', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,借款状态错误");
        }
        if($deal['report_status'] != DealEnum::DEAL_REPORT_STATUS_YES){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('强制还款失败 deal_id:'.$deal_id.' 借款正在还款中', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error('没有报备，不允许强制还款');
        }
        if($deal['is_during_repay'] == DealEnum::DEAL_DURING_REPAY){
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log('强制还款失败 deal_id:'.$deal_id.' 借款正在还款中', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error("操作失败,借款正在还款中");
        }

        $this->assign("jumpUrl",u(MODULE_NAME."/force_repay", array("deal_id"=>$id)));
        $ids = $_REQUEST['repay_to'];

        //逐一执行还款
        foreach ($ids as $id) {
            $id = intval($id);
            if($id == 0){
                save_log('强制还款失败 deal_id:'.$deal_id.' 还款id缺失', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                $this->error("操作失败,还款id缺失");
            }

            $authKey =conf ("AUTH_KEY");
            $admInfo = \es_session::get(md5($authKey));
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditEnum::SERVICE_TYPE_REPAY, 'service_id' => $id))->find();
            if(!$audit){
                save_log('强制还款失败 serviceAudit记录不存在', C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                $this->error("强制还款失败 serviceAudit记录不存在");
            }
            $db = Db::getInstance('firstp2p');
            try{
                $db->startTrans();
                // 保存审核结果
                $data = array('status' => ServiceAuditEnum::AUDIT_SUCC, 'audit_uid' => $admInfo['adm_id']);
                $condition = 'id = '.intval($audit['id']);
                $auditRes = ServiceAuditModel::instance()->updateBy($data,$condition);
                if (!$auditRes) {
                    throw new \Exception("ServiceAudit操作失败");
                }
                // 还款方法加入到jobs中
                $job_model = new JobsModel();
                $param = array('deal_repay_id' => $id,'repayAccountType' => $repayType,'admin' => $admInfo, 'submitUid' => $audit['submit_uid']);
                $orderId = Idworker::instance()->getId();
                $function = '\core\service\repay\P2pDealRepayService::dealRepayRequest';
                $param = array('orderId'=>$orderId,'dealRepayId'=>$id,$repayType,'params'=>$param);
                $job_model->priority = JobsEnum::PRIORITY_P2P_REPAY_REQUEST;

                $res = $job_model->addJob($function, $param);
                if ($res === false) {
                    save_log('强制还款失败 deal_id:' . $deal_id . ' repay_id:' . $id, C('FAILED'), '', '', C('SAVE_LOG_FILE'));
                    throw new \Exception("JobsModel操作失败");
                }
                // 标的状态置为正在还款
                $updateRes = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
                if($updateRes == false){
                    throw new \Exception("标的操作失败");
                }
                $db->commit();
            }catch(\Exception $e){
                $db->rollback();
                $this->error('更新数据库失败！ 失败原因:' . $e->getMessage());
            }
        }
        set_time_limit(30);
        save_log('do_force_repay id:'.$deal_id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
        $this->assign("jumpUrl", "/m.php?m=Deal&a=yuqi&ref=1&{$_REQUEST['querystring']}");
        $this->success("操作成功");
    }

    /**
     * 批量放款
     */
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

        $failDealIds = array();
        $_REQUEST['agree'] = 1;
        $dealService = new DealService();
        foreach ($deal_ids as $deal_id) {
            $vo = M('Deal')->where(array('is_delete' => 0, 'id' => $deal_id))->find();
            $vo['isDtb'] = 0;
            $dealService = new DealService();
            if($dealService->isDealDT($vo['id'])){
                $vo['isDtb'] = 1;
            }

            $role = 'b';
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditEnum::SERVICE_TYPE_LOAN, 'service_id' => $deal_id))->find();
            if ($role != 'b' || $_REQUEST['agree'] != 1) {
                $auditRes = $this->audit($vo, $role, $audit);
                if ($auditRes == 0) {
                    $failDealIds[]=$deal_id;
                    continue;
                }
            }

            $dealModelClass = M(MODULE_NAME);
            $dealModelClass->startTrans();
            $GLOBALS['db']->startTrans();
            try {
                //放款添加到jobs
                $orderId = Idworker::instance()->getId();
                $function = '\core\service\deal\P2pDealGrantService::dealGrantRequest';
                $param = array(
                    'orderId' => $orderId,
                    'dealId'=>$deal_id,
                    'param' => array('deal_id' => $deal_id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid']),
                );
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$orderId." dealId:".$deal_id);

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

                $isSaved = $dealModelClass->save($vo);
                if(!$isSaved) {
                    throw new \Exception("修改标的状态或者放款时间错误");
                }

                //成功提示
                syn_deal_status($vo['id']);
                syn_deal_match($vo['id']);

                //更新项目信息
                $deal_pro_service = new ProjectService();
                $deal_pro_service->updateProBorrowed($vo['project_id']);
                $deal_pro_service->updateProLoaned($vo['project_id']);

                $job_model = new JobsModel();
                $job_model->priority = JobsEnum::PRIORITY_DEAL_GRANT;
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
                $dealModelClass->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $dealModelClass->rollback();
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
        $return['succ_num'] = count($deal_ids) - count($failDealIds);
        $return['fail_num'] = count($failDealIds);
        $return['deal_ids'] = implode(",",$failDealIds);

        ajax_return($return);
    }


    /**
     * 待放款列表批量提交
     */
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
            $failDealIds = array();
            $dealService = new DealService();
            $makeLoansService = new MakeLoansService();
            foreach ($deal_ids as $deal_id) {
                $vo = M('Deal')->where(array('is_delete' => 0, 'id' => $deal_id))->find();
                $vo['isDtb'] = 0;
                if($dealService->isDealDT($deal_id)){
                    $vo['isDtb'] = 1;
                }
                try {
                    $makeLoansService->isOKForMakingLoans($vo);
                    if ($makeLoansService->saveServiceFeeExt($vo) === false) {
                        throw new \Exception("Save deal ext fail. Error:deal id:" . $vo['id']);
                    }
                    $role = $this->getRole();
                    $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditEnum::SERVICE_TYPE_LOAN, 'service_id' => $deal_id))->find();
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
                $return['succ_num'] = count($deal_ids) - count($failDealIds);
                $return['fail_num'] = count($failDealIds);
                $return['deal_ids'] = implode(",",$failDealIds);
            }
            ajax_return($return);
        }
    }

    public function load_user(){
        $return= array("status"=>0,"message"=>"");
        $id = intval($_REQUEST['id']);
        if($id==0){
            return ajax_return($return);
        }
        $user = UserService ::getUserById($id);
        if(!$user){
            return ajax_return($return);
        }
        $return['status']=1;
        $return['user']=$user;
        return ajax_return($return);
    }

    public function deal_view() {

        C('TOKEN_ON',true);
        $userInfo = array();
        $id = intval($_REQUEST['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        // 从备份库
        if (empty($vo)){
            $this->deal = $vo = $this->getMovedModel(MODULE_NAME)->where($condition)->find();
        }
        if (!$vo) {
            $this->error('获取标的信息失败');
        }

        //获得当前标的tag信息
        $deal_tag_service = new DealTagService();
        $tags =  $deal_tag_service->getTagByDealId($id);
        $vo['tags'] = implode(',',$tags);
        $vo['start_time'] = $vo['start_time']!=0?to_date($vo['start_time']):'';
        $vo['bad_time'] = $vo['bad_time']!=0?to_date($vo['bad_time']):'';
        $vo['repay_start_time'] = $vo['repay_start_time']!=0?to_date($vo['repay_start_time'],"Y-m-d"):'';

        // 读取用户组列表
        $usergroupList = UserService::getUserGroupList();
        if (empty($usergroupList)){
            $this->error('获取用户组列表失败');
        }
        $this->assign ( 'usergroupList', $usergroupList );

        if($vo['deal_status'] == DealEnum::DEAL_STATS_WAITING){
            $vo['services_fee'] = UserService::getUserServicesFee($vo['user_id']);
        }

        if($vo['manage_fee_text'] === ''){
            $vo['manage_fee_text'] = '年化，收益率计算中已包含此项，不再收取。';
        }

        // 标和用户组对应关系
        $group = M("DealGroup")->where(array('deal_id'=>$id))->select();

        if($group){
            $relation = '';
            $t_group = array();
            foreach ($group as $row){
                $relation = $row['relation'];
                $t_group[] = $row['user_group_id'];
            }
            $vo['user_group'] = $t_group;
            $vo['relation'] = $relation;
        }

        $foreground_deal_model = new DealModel();
        //计算还款期数
        $deal_model = $foreground_deal_model->getDealInfo($vo['id']);
        $this->assign('repay_times', $foreground_deal_model->getRepayTimes());

        //订单扩展信息
        $deal_ext = M("DealExt")->where(array('deal_id' => $id))->find();

        // 计算服务费
        if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $vo['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        }
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        //功夫贷分期咨询费计算
        if($vo['consult_fee_period_rate'] > 0){
            $consult_fee_period = $foreground_deal_model->floorfix($vo['borrow_amount'] * $vo['consult_fee_period_rate'] / 100.0);
            $this->assign("consult_fee_period", $consult_fee_period);
        }

        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);
        $management_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['management_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);
        $management_fee = $foreground_deal_model->floorfix($vo['borrow_amount'] * $management_fee_rate / 100.0);

        $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);
        $this->assign("management_fee", $management_fee);

        //用户信息处理
        if(!empty($vo['user_id'])) {
            $userInfo = UserService::getUserByCondition('id='.intval($vo['user_id']));
            if (empty($userInfo)){
                $this->error('获取借款人信息失败');
            }
            $userInfo['audit'] = BankService::getNewCardByUserId($userInfo['id'],'*');
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
        $deal_cate_tree = D('Common')->toFormatTree($deal_cate_tree,'name');
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


        //功夫贷原始担保机构
        if($loan_type_info['type_tag'] == DealLoanTypeEnum::TYPE_XJDGFD) {
            $deal_extra_info = DealExtraModel::instance()->getDealExtraByDealId($vo['id']);
            if(!empty($deal_extra_info)) {
                //原始担保机构
                $deal_original_agency = M("DealAgency")->where(array("id"=>$deal_extra_info['original_agency_id']))->find();
                $this->assign("deal_original_agency",$deal_original_agency);
            }
        }

        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());

        $tplResponse = CategoryService::getCategorys();
        if(!is_array($tplResponse)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }
        $this->assign('contract_tpl_type', $tplResponse);    //合同类型

        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);

        $vipGrades = VipService::getVipGradeList();
        if (empty($vipGrades)){
            $this->error('获取vip等级列表失败');
        }
        $this->assign('vipGrades', $vipGrades);

        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);


        //取平台信息
        $site_list = $GLOBALS['sys_config']['TEMPLATE_LIST'];
        $deal_site_list = get_deal_site($id);

        $this->assign('site_list', $site_list);
        $this->assign('deal_site_list', $deal_site_list);

        $deal_ext['start_loan_time'] = $deal_ext['start_loan_time'] == 0 ? '' : to_date($deal_ext['start_loan_time']);
        $deal_ext['first_repay_interest_day'] = $deal_ext['first_repay_interest_day'] == 0 ? '' : to_date($deal_ext['first_repay_interest_day'], "Y-m-d");
        $deal_ext['base_contract_repay_time'] = $deal_ext['base_contract_repay_time'] == 0 ? '' : to_date($deal_ext['base_contract_repay_time'], "Y-m-d");

        if($vo['deal_crowd'] == DealEnum::DEAL_CROWD_SPECIFY_USER) {
            $specify_uid_info = UserService::getUserByCondition("id=".intval($deal_ext['deal_specify_uid']),'*');
            if (empty($specify_uid_info)){
                $this->error('获取指定用户信息失败');
            }
            $this->assign('specify_uid_info',$specify_uid_info);
        }

        $this->assign('deal_ext', $deal_ext);

        $deal_coupon = CouponService::getCouponDealByDealId($id);
        if (empty($deal_coupon)){
            $this->error('优惠码标的设置信息不存在');
        }
        $this->assign("deal_coupon",$deal_coupon);
        //项目信息
        $project = M("DealProject")->where(array('id' => $vo['project_id']))->find();
        if($project){
            $project['left_money'] = sprintf("%.2f",$project['borrow_amount'] - $project['money_borrowed']);
            $project['business_status'] = intval($project['business_status']);
            $this->assign ( 'pro', $project );
        }

        // JIRA#3271 平台产品名称定义 2016-03-29
        $vo['prefix_title'] = $deal_ext['deal_name_prefix'];
        $idStr = str_pad(strval($id), 9, strval(0), STR_PAD_LEFT);
        $vo['main_title'] = $project['name'] . 'A' . $idStr;
        // ----------------- over ----------------

        // JIRA#3260 企业账户二期 - 获取用户类型名称 <fanjingwen@ucfgroup.com>
        if (!empty($vo['user_id']) && !empty($userInfo)) {
            $userInfo['user_type_name'] = getUserTypeName($userInfo['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserEnum::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {

                $enterpriseInfo = UserService::getEnterpriseInfo($userInfo['id']);
                // 有会员列表后需要加链接
                $userInfo['company_name'] = $enterpriseInfo['company_name'];
            } else {
                // 有会员列表后需要加链接
                $userInfo['real_name'] = $userInfo['real_name'] ;
            }
        }

        //借款客群
        if(($vo['loan_user_customer_type'] > 0) && array_key_exists($vo['loan_user_customer_type'],$GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'])){
            $vo['loan_user_customer'] = $GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'][$vo['loan_user_customer_type']];
        }
        // ----------------- over ----------------

        //利滚利分类标识
        $this->assign('lgl_tag', DealLoanTypeEnum::TYPE_LGL);
        $this->assign('bxt_tag', DealLoanTypeEnum::TYPE_BXT);
        $this->assign('dtb_tag', DealLoanTypeEnum::TYPE_DTB);
        $this->assign('xffq_tag', DealLoanTypeEnum::TYPE_XFFQ);
        $this->assign('zcgl_tag', DealLoanTypeEnum::TYPE_GLJH);
        $this->assign('zzjr_tag', DealLoanTypeEnum::TYPE_ZHANGZHONG);
        $this->assign('xsjk_tag', DealLoanTypeEnum::TYPE_XSJK);
        $this->assign('xjdcdt_tag', DealLoanTypeEnum::TYPE_XJDCDT);


        $this->assign ('vo', $vo);
        $this->assign("userInfo",$userInfo);
        $this->assign("project_business_status",DealProjectEnum::$PROJECT_BUSINESS_STATUS);

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

        $this->display ();
    }
}
?>

<?php
/**
 * 黄金标相关操作
 */

FP::import("libs.libs.msgcenter");
FP::import("app.deal");

use core\service\UserService;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\service\DtTransferService;
use core\service\IdempotentService;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use core\service\DtBidService;
use core\dao\IdempotentModel;
use libs\utils\Rpc;
use libs\utils\deal;
use core\service\DealTypeGradeService;
use core\service\GoldService;
use NCFGroup\Protos\Contract\RequestGetCategorys;
use core\dao\DealAgencyModel;
use core\dao\JobsModel;
use core\data\GoldDealData;
use core\service\GoldDealService;
use core\service\DealQueueService;

class GoldDealAction extends CommonAction {

    const EXPORT_CSV_MAX_COUNT = 10000;

    protected static $loanType = array('1'=> '网信优金');

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型

    protected static $reypayType = array('5'=> '已购黄金及收益克重到期一次性交付','6'=> '已购黄金到期交付，收益克重按季度交付');


    public function add() {
        $tree = array();
        $product_mix_1  = '稀贵商品';//产品一级
        $product_mix_2  = '优长金';//产品二级 先写死
        $dealTypeGradeService = new DealTypeGradeService();
        $type_1 = $dealTypeGradeService-> findByName($product_mix_1);
        $type_2 = $dealTypeGradeService-> findByName($product_mix_2);
        $typelist2 = $dealTypeGradeService -> getbyParentId($type_1 ->id);
        $typelist3 = $dealTypeGradeService -> getbyParentId($typelist2[0]["id"]);
        $this->assign('typelist2', $typelist2);
        $this->assign('typelist3', $typelist3);
        $contractRequest = new RequestGetCategorys();
        $contractRequest->setType(2);
        $contractRequest->setIsDelete(0);
        $contractResponse = $this->getRpc('contractRpc')->callByObject(
            array(
                'service' => 'NCFGroup\Contract\Services\Category',
                'method' => 'getCategorys',
                'args' => $contractRequest,
            )
        );
        if(!is_array($contractResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }
        $this->assign('contract_tpl_type', $contractResponse->list);    //合同类型
        //支付机构
        $pay_agency = M("DealAgency")->where('is_effect = 1 and type=4 ')->order('id ASC')->findAll();
        $this->assign("pay_agency",$pay_agency);
        //产品类别
        $this->assign("deal_type_tree",GoldDealAction::$loanType);
        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);
        //从配置文件取公用信息
        $this->assign('loan_type',GoldDealAction::$reypayType);        //还款方式
        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);
        $usergroupList = M("UserGroup")->select();
        $this->assign ( 'usergroupList', $usergroupList );
        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = get_sites_template_list();
        $site_list = changeDealSite($site_list,true);
        //$deal_site_list = get_deal_site($id);
        $this->assign('site_list', $site_list);
        // $this->assign('deal_site_list', $deal_site_list);
        $this->assign('repay_time_month', get_repay_time_month());
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->display('add');
    }

    public function edit() {
        $product_mix_1  = '稀贵商品';//产品一级
        $product_mix_2  = '优长金';//产品二级 先写死
        $dealTypeGradeService = new DealTypeGradeService();
        $type_1 = $dealTypeGradeService-> findByName($product_mix_1);
        $type_2 = $dealTypeGradeService-> findByName($product_mix_2);
        $typelist2 = $dealTypeGradeService -> getbyParentId($type_1 ->id);
        $typelist3 = $dealTypeGradeService -> getbyParentId($typelist2[0]["id"]);
        $this->assign('typelist2', $typelist2);
        $this->assign('typelist3', $typelist3);

        $dealId = $_REQUEST['id'];
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$dealId));

        $rpcResponse = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        //支付机构
        $pay_agency = M("DealAgency")->where('is_effect = 1 and type=4 ')->order('id ASC')->findAll();
        $this->assign("pay_agency",$pay_agency);
        //产品类别
        $this->assign("deal_type_tree",GoldDealAction::$loanType);
        //投资人群
        $this->assign('deal_crowd', $GLOBALS['dict']['DEAL_CROWD']);
        //从配置文件取公用信息
        $this->assign('loan_type',GoldDealAction::$reypayType);        //还款方式
        //投资限定条件2
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);
        $usergroupList = M("UserGroup")->select();

        $this->assign ( 'usergroupList', $usergroupList );
        //取平台信息
        FP::import("libs.deal.deal");
        $site_list = get_sites_template_list();
        $site_list = changeDealSite($site_list,true);
        //$deal_site_list = get_deal_site($id);
        $this->assign('site_list', $site_list);
        // $this->assign('deal_site_list', $deal_site_list);
        $this->assign('repay_time_month', get_repay_time_month());
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        //用户信息处理
        if(!empty($rpcResponse['data']['userId'])) {
            $userInfo = M('User')->where('id='.intval($rpcResponse['data']['userId']))->find();
            if(!empty($userInfo)) {
                $userInfo['audit'] = M('UserBankcard')->where('user_id='.$userInfo['id'])->find();
            }
        }
        // JIRA#3260 企业账户二期 - 获取用户类型名称 <fanjingwen@ucfgroup.com>
        if (!empty($rpcResponse['data']['userId']) && !empty($userInfo)) {
            $userInfo['user_type_name'] = getUserTypeName($userInfo['id']);
            // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
            if (UserModel::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userInfo['id']);
                $userInfo['company_name'] = getUserFieldUrl($userInfo, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
            } else {
                $userInfo['real_name'] = getUserFieldUrl($userInfo, UserModel::TABLE_FIELD_REAL_NAME);
            }
        }
        $contractRequest = new RequestGetCategorys();
        $contractRequest->setType(2);
        $contractRequest->setIsDelete(0);
        $contractResponse = $this->getRpc('contractRpc')->callByObject(
            array(
                'service' => 'NCFGroup\Contract\Services\Category',
                'method' => 'getCategorys',
                'args' => $contractRequest,
            )
        );
        if(!is_array($contractResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }
        $this->assign('contract_tpl_type', $contractResponse->list);    //合同类型
        $rpcResponse['data']['startLoanTime'] = $rpcResponse['data']['startLoanTime'] == 0 ? '' : date('Y-m-d H:i', $rpcResponse['data']['startLoanTime']);
        $rpcResponse['data']['startTime'] = $rpcResponse['data']['startTime'] == 0 ? '' : date('Y-m-d H:i', $rpcResponse['data']['startTime']);
        $rpcResponse['data']['repayStartTime'] = $rpcResponse['data']['repayStartTime'] == 0 ? '' : date('Y-m-d H:i', $rpcResponse['data']['repayStartTime']);
        $rpcResponse['data']['badTime'] = $rpcResponse['data']['badTime'] == 0 ? '' : date('Y-m-d H:i', $rpcResponse['data']['badTime']);
        $rpcResponse['data']['loanCreateTime'] = $rpcResponse['data']['loanCreateTime'] == 0 ? '' : date('Y-m-d H:i', $rpcResponse['data']['loanCreateTime']);
        $this->assign("userInfo",$userInfo);
        $this->assign ('vo', $rpcResponse['data']);
        $this->display('edit');
    }

    public function index() {
        $request = new RequestCommon();
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $id = trim($_REQUEST['id']);
        $name = trim($_REQUEST['name']);
        $real_name = trim($_REQUEST['real_name']);
        $deal_status = trim($_REQUEST['deal_status']);
        if($deal_status != 'all' &&isset($_REQUEST['deal_status']) ){
            $request->setVars(array('deal_status'=> $deal_status ));
        }

        if(trim($real_name)!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%" . $real_name . "%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $request->setVars(array('ids'=> array($ids) ));
        }
        $this->assign('sitelist', $GLOBALS['sys_config']['TEMPLATE_LIST']);
        // 显示审核过
        $request->setVars(array("pageNum"=>$pageNum,"pageSize"=>$pageSize,"id"=>$id,"name"=>$name,"real_name"=>$real_name,'publish_wait' => 0));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'listDeal',
            'args' => $request,
        ));
        $dealQueueServ = new DealQueueService();
        if(!$response) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );

        $userIDArr = array();
        if (! empty ( $response['data']['data'] )) {
            foreach($response['data']['data']  as $k=>$v){
                $queueInfo = $dealQueueServ->getQueueInfoByDealId($v['id'],'GOLD');
                $response['data']['data'][$k]['queueName']=empty($queueInfo['name'])?'':$queueInfo['name'];
                $userIDArr[] = $v['userId'];
            }
            $this->assign('list', $response['data']['data'] );
        }
        $userServ = new UserService();
        $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
        $this->assign('listOfBorrower', $listOfBorrower);
        $this->display ('index');
    }
    public function publish() {
        $request = new RequestCommon();
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $request->setVars(array('publish_wait'=> 1,'pageNum' => $pageNum,'pageSize' => $pageSize ));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'listDeal',
            'args' => $request,
        ));
        if(!$response) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $userIDArr = array();
        if (! empty ( $response['data']['data'] )) {
            foreach($response['data']['data']  as $k=>$v){
                $userIDArr[] = $v['userId'];
            }
            $this->assign('list', $response['data']['data'] );
        }
        $userServ = new UserService();
        $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
        $this->assign('listOfBorrower', $listOfBorrower);
        $this->display ('publish');
    }
    public function trash() {
        $request = new RequestCommon();
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $request->setVars(array('is_delete'=> 1,"pageNum"=>$pageNum,"pageSize"=>$pageSize));//删除
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'listDeal',
            'args' => $request,
        ));
        if(!$response) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }
        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $userIDArr = array();
        if (! empty ( $response['data']['data'] )) {
            foreach($response['data']['data']  as $k=>$v){
                $userIDArr[] = $v['userId'];
            }
            $this->assign('list', $response['data']['data'] );
        }
        $userServ = new UserService();
        $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
        $this->assign('listOfBorrower', $listOfBorrower);
        $this->display ('trash');
    }
    public function insert() {
        $request = new RequestCommon();

        if(!empty($_POST['user_id'])) {
            $userInfo = M('User')->where('id='.intval($_POST['user_id']))->find();
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
        if(isset($_POST['repay_time']) && empty($_POST['repay_time'])){
            $this->error('期限不能为空');
        }
        if(($_POST ['max_loan_money']) > 0 && ($_POST ['max_loan_money']) < ($_POST ['min_loan_money'])) {
            $this->error ( "最大金额不能小于最小金额" );
        }
        $_POST['start_loan_time'] = strtotime($_POST['start_loan_time']);
        // 新添加的走待审核列表
        $_POST['publish_wait'] = 1;
        $request->setVars($_POST);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'saveDeal',
            'args' => $request,
        ));
       if(!$response || $response['data'] == false) {
            $this->error("操作失败 errCode:".$response['errCode']." errMsg:".$response['errMsg']);
        }
        $this->success("操作成功");

    }


    /**
     * 显示投资认购界面
     */
    public function showInvest() {
        $id = intval(trim($_REQUEST['id']));

        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$id));

        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealInfoById',
            'args' => $request,
        ));

        $dealInfo = $response['data'];

        if(!$dealInfo) {
            $this->error("操作失败 errCode:".$dealInfo['errCode']." errMsg:".$dealInfo['errMsg']);
        }

        $canUseMoney = $dealInfo['moneyLimitDay'] - $dealInfo['hasLoanMoney'] ;

        $dtUser = UserModel::instance()->find(app_conf('AGENCY_ID_DT_PRINCIPAL'))->getRow();

        $dtUsers = array(array('user_name' => $dtUser['user_name'],'id'=>$dtUser['id']));
        $this->assign("dtUsers",$dtUsers);
        $this->assign("canUseMoney",$canUseMoney);
        $this->display ();
    }

    /**
     * 获取用户余额
     */
    public function getUserMoney() {
        $userName = trim($_REQUEST['user_name']);
        $user_info = UserModel::instance()->getUserinfoByUsername($userName);
        if (empty($user_info['id'])){
            $this->ajaxReturn(0,'用户不存在',0);
        }
        $user_id = $user_info['id'];
        $money = $user_info['money'];

        if($user_id == 0 || $money == 0){
            $this->ajaxReturn(0,'提交的数据存在异常',0);
        }
        $this->ajaxReturn($money);
    }

    /**
     * 投资认购
     */
    public function invest() {
        $userName = trim($_REQUEST['user_name']);
        $user_info = UserModel::instance()->getUserinfoByUsername($userName);
        $userId = $user_info['id'];

        $user_dt_interest = UserModel::instance()->find(app_conf('AGENCY_ID_DT_INTEREST'));
        if($userId == $user_dt_interest['id']) {
            $this->error("智多新利息账户不允许投资！");
        }
        $dealId = intval(trim($_REQUEST['id']));
        $investMoney = floatval(trim($_REQUEST['investMoney']));

        if($userId == 0 || $investMoney <= 0){
            $this->error("提交的数据存在异常");
        }

        $dtbidService = new DtBidService();
        $res = $dtbidService->bidAdmin($userId, $dealId, $investMoney);

        if($res['errCode']){
            $this->error($res['errMsg'], "");
        } else {
            $this->success("操作成功！");
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
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$id));

        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        if (empty($response['data'])){
            $this->error("标信息不存在");
        }
        $vo = $response['data'];

        //用户信息处理
        $userInfo = M('User')->where('id='.intval($vo['userId']))->find();
        $this->assign("userInfo",$userInfo);


        $this->assign('loan_money_type', $GLOBALS['dict']['LOAN_MONEY_TYPE']); //放款方式
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);


        if($vo['loantype'] == 5) {
            $repayTime =   $vo['repayTime'] . "天";
        } else {
            $repayTime = $vo['repayTime'] . "月";
        }
        $this->assign('repay_time', $repayTime); // 借款期限
        // 计算服务费
       /* $loan_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['loan_fee_rate'], $vo['repay_time'], false);
        $consult_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['consult_fee_rate'], $vo['repay_time'], false);
        $guarantee_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['guarantee_fee_rate'], $vo['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($vo['loantype'], $vo['pay_fee_rate'], $vo['repay_time'], false);

        $loan_fee = $deal_model->floorfix($vo['borrow_amount'] * $loan_fee_rate / 100.0);
        $consult_fee = $deal_model->floorfix($vo['borrow_amount'] * $consult_fee_rate / 100.0);
        $guarantee_fee = $deal_model->floorfix($vo['borrow_amount'] * $guarantee_fee_rate / 100.0);
        $pay_fee = $deal_model->floorfix($vo['borrow_amount'] * $pay_fee_rate / 100.0);*/



        $this->assign ('vo', $vo );
       /* $this->assign("loan_fee", $loan_fee);
        $this->assign("consult_fee", $consult_fee);
        $this->assign("guarantee_fee", $guarantee_fee);
        $this->assign("pay_fee", $pay_fee);*/

        $this->assign("loan_create_time", empty($vo['loanCreateTime']) ? "" :date( "Y-m-d", $vo['loanCreateTime']));
        $this->assign('redirectUrl', empty($_SESSION['lastDealLoanUrl']) ? '?m=GoldDealLoan' : $_SESSION['lastDealLoanUrl']);


        $this->display();
    }

    /**
     * 更新放款表单
     */
    public function update_lent() {
        B('FilterString');

        $data = M(MODULE_NAME)->create();
        //标的信息
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$_POST['id']));
        $data = $_POST;

        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        unset($request);
        $vo = $response['data'];
        if(empty($vo)) {
            $errMsg = "无法找到id为{$data['id']}的标";
            $this->error(L("UPDATE_FAILED").$errMsg,0);
        }

//        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        if(empty($data['loan_create_time'])) {
            // 放款时间
            $data['loan_create_time'] = strtotime(date("Y-m-d"));

        } else {
            $data['loan_create_time'] = strtotime($data['loan_create_time']);
        }
        if (!empty($data['loan_create_time'])){
            //$golddealService = new GoldDealService();
            //$data['repay_start_time'] = $golddealService->getInterestDate($data['loan_create_time']);
            // jira 5247 即日起息
            $data['repay_start_time'] = strtotime(date("Y-m-d",$data['loan_create_time']));

        }

        $deal_ext_data = array();
      //  $repay_times = $this->getRepayTimes($vo['loantype'],$vo['repayTime']);


        // JIRA#3221 先计息后放款 <fanjingwen@ucfgroup.com>
        /*if (0 == $_REQUEST['loan_type']) {//直接放款
            $deal_ext_data['loan_type'] = 0;
        } else if (1 == $_REQUEST['loan_type']) {//先计息后放款
            $deal_ext_data['loan_type'] = 1;
        }*/
        unset($data['loan_type']);
        //$data = array_merge($data,$deal_ext_data);
        $request = new RequestCommon();
        $request->setVars($data);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'updateDeal',
            'args' => $request,
        ));
        if (false === $response['data']) {
            //错误提示

            save_log($vo['name'].L("UPDATE_FAILED"),C('FAILED'), $vo, $data, C('SAVE_LOG_FILE'));
            $this->error(L("UPDATE_FAILED"),0);
        } else {
            //成功提示
            save_log($vo['name'].L("UPDATE_SUCCESS"),C('SUCCESS'), $vo, $data, C('SAVE_LOG_FILE'));
            $this->success(L("UPDATE_SUCCESS"));
        }
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
            $failDealIds = array();
            foreach ($deal_ids as $deal_id) {
                try {
                    $request = new RequestCommon();
                    $request->setVars(array("deal_id"=>$deal_id));
                    //$data = $_POST;
                    //标的信息
                    $response = $this->getRpc('goldRpc')->callByObject(array(
                        'service' => 'NCFGroup\Gold\Services\Deal',
                        'method' => 'getUnderlineDealById',
                        'args' => $request,
                    ));
                    unset($request);
                    $dealInfo = $response['data'];

                    if(empty($dealInfo)) {
                       throw new \Exception("无法找到id为{$deal_id}的标");
                    }

                    $role = $this->getRole();
                    $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_GOLD_PROJECT_LOAN, 'service_id' => $deal_id))->find();
                    if ($role != 'b' || $_REQUEST['agree'] != 1) {
                        $auditRes = $this->audit($dealInfo, $role, $audit);
                        if ($auditRes == 0) {
                            throw new \Exception("审核异常");
                        }
                    }
                    unset($dealInfo);
                } catch (\Exception $e) {
                    $failDealIds[] = $deal_id;
                    save_log('提交gold放款失败 deal_id:'.$deal_id." ".$e->getMessage() , C('FAILED'),'', '', C('SAVE_LOG_FILE'));
                }

                save_log('提交gold放款成功 deal_id:'.$deal_id , C('SUCCESS'),'', '', C('SAVE_LOG_FILE'));

            }
            $return['succ_num'] = count($deal_ids) - count($failDealIds);
            $return['fail_num'] = count($failDealIds);
            $return['deal_ids'] = implode(",",$failDealIds);
            ajax_return($return);
            exit;
        }

        ajax_return($return);
        exit;
    }

    /**
     * 放款操作包括提交审核
     */
    public function enqueue()
    {
        $id = $_REQUEST['id'];

        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$id));
        //$data = $_POST;
        //标的信息
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getUnderlineDealById',
            'args' => $request,
        ));
        unset($request);
        $dealInfo = $response['data'];

        if(empty($dealInfo)) {
            $errMsg = "无法找到id为{$id}的标";
            $this->error(L("UPDATE_FAILED").$errMsg,0);
        }
        try {
            // 标没有满标
            if(!in_array($dealInfo['deal_status'], array(2,4))) {
                throw new \Exception("标还未满标");
            }
            //TODO //除公益标，附件合同除外，验证合同状态 检查合同
            //已经放过款
            if($dealInfo['is_has_loans'] != 0) {
                throw new \Exception("已经放过款");
            }
        } catch (\Exception $e) {
            $ret['status'] = 0;
            $ret['error_msg'] = $e->getMessage();
            ajax_return($ret);
            return;
        }

        $role = $this->getRole();
        $audit = D('serviceAudit')->where(array('service_type' =>ServiceAuditModel::SERVICE_TYPE_GOLD_PROJECT_LOAN , 'service_id' => $id))->find();

        if ($role != 'b' || $_REQUEST['agree'] != 1) {
            $auditRes = $this->audit($dealInfo, $role, $audit);
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
        $agency_model = new DealAgencyModel();
        // 取默认的支付id
        if (!$dealInfo['pay_agency_id']) {
            $dealInfo['pay_agency_id'] = $agency_model->getUcfPayAgencyId();
        }

       // $loan_fee_user_id = app_conf('GOLD_LOAN_FEE_USER_ID');

        $loan_fee_user_id = $agency_model->getLoanAgencyUserIdBySiteId($dealInfo['site_id']);

        // 支付机构用户id
        $pay_agency_user = $agency_model->find($dealInfo['pay_agency_id']);
        // 操作放款jobs
        $function = '\core\service\GoldDealService::makeDealLoansJob';
        $param = array('deal_id' => $id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid'],'pay_user_id' => $pay_agency_user['user_id'], 'loan_user_id' => $loan_fee_user_id);

        try {
            $GLOBALS['db']->startTrans();
            $auditRes = $this->audit($dealInfo, $role, $audit);
            if (!$auditRes) {
                throw new \Exception("AB角审核失败");
            }
            //如果没有设置放款时间，则添加默认的放款时间
            $update_dealInfo['deal_status'] = 4; //设置状态为放款中
            if(!$dealInfo['loan_create_time']) {
                $update_dealInfo['loan_create_time'] = strtotime(date("Y-m-d"));
                //$golddealService = new GoldDealService();
                //$update_dealInfo['repay_start_time'] = $golddealService->getInterestDate($update_dealInfo['loan_create_time']);

                $update_dealInfo['repay_start_time'] = strtotime(date("Y-m-d"));
                // 放款时间
            }

           /* if(intval($dealInfo['next_repay_time']) == 0){
                $delta_month_time = get_delta_month_time($dealInfo['loantype'], $dealInfo['repay_time']);

                // 按天一次到期
                if($dealInfo['loantype'] == 5){
                    $update_dealInfo['next_repay_time'] = next_replay_day_with_delta($dealInfo['repay_start_time'], $delta_month_time);
                }else{
                    $update_dealInfo['next_repay_time'] = next_replay_month_with_delta($dealInfo['repay_start_time'], $delta_month_time);
                }
            }*/
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = \core\dao\JobsModel::PRIORITY_GOLD_MAKE_LOAN;
            //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
            $add_job = $job_model->addJob($function, $param, get_gmtime() + 180,1);
            if (!$add_job) {
                throw new \Exception("放款任务添加失败");
            }

            $update_dealInfo['dealId'] = $id;
            $update_dealInfo['has_loan_status'] = 2;
            $request = new RequestCommon();
            $request->setVars($update_dealInfo);
            $response = $this->getRpc('goldRpc')->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\Deal',
                'method' => 'updateLoansStatus',
                'args' => $request,
            ));

            if (false == $response['data']){
                throw new \Exception("更新标信息失败");

            }
            $GLOBALS['db']->commit();
            $result['status'] = 1;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $result['status'] = 0;
            $result['error_msg'] = $e->getMessage();
        }
        ajax_return($result);



    }

    /**
     * 批量操作审核通过放款
     */
    public function batch_enqueue(){
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
            exit;
        }
        $deal_ids = explode(",", $deal_ids);

        $failDealIds = array();
        $_REQUEST['agree'] = 1;
        $i = 0;
        foreach($deal_ids as $deal_id){

            $request = new RequestCommon();
            $request->setVars(array("deal_id"=>$deal_id));

            try {
                //$data = $_POST;
                //标的信息
                $response = $this->getRpc('goldRpc')->callByObject(array(
                    'service' => 'NCFGroup\Gold\Services\Deal',
                    'method' => 'getUnderlineDealById',
                    'args' => $request,
                ));
                unset($request);
                $dealInfo = $response['data'];

                if (empty($dealInfo)) {
                    throw new \Exception("无法找到id为{$deal_id}的标");
                }
                // 标没有满标
                if(!in_array($dealInfo['deal_status'], array(2,4))) {
                    throw new \Exception("标还未满标");
                }

                if($dealInfo['is_has_loans'] != 0) {
                    throw new \Exception("放款中状态不对");
                }
                $role = 'b';
                $audit = D('serviceAudit')->where(array('service_type' =>ServiceAuditModel::SERVICE_TYPE_GOLD_PROJECT_LOAN , 'service_id' => $deal_id))->find();
                $agency_model = new DealAgencyModel();
                // 取默认的支付id
                if (!$dealInfo['pay_agency_id']) {
                    $dealInfo['pay_agency_id'] = $agency_model->getUcfPayAgencyId();
                }

                // $loan_fee_user_id = app_conf('GOLD_LOAN_FEE_USER_ID');

                $loan_fee_user_id = $agency_model->getLoanAgencyUserIdBySiteId($dealInfo['site_id']);

                // 支付机构用户id
                $pay_agency_user = $agency_model->find($dealInfo['pay_agency_id']);
                // 操作放款jobs
                $function = '\core\service\GoldDealService::makeDealLoansJob';
                $param = array('deal_id' => $deal_id, 'admin' => \es_session::get(md5(conf("AUTH_KEY"))), 'submit_uid' => $audit['submit_uid'],'pay_user_id' => $pay_agency_user['user_id'], 'loan_user_id' => $loan_fee_user_id);
                $GLOBALS['db']->startTrans();
                $auditRes = $this->audit($dealInfo, $role, $audit);
                if (!$auditRes) {
                    throw new \Exception("AB角审核失败");
                }

                $update_dealInfo['deal_status'] = 4; //设置状态为放款中
                // 批量提交以当前时间为放款时间
                $update_dealInfo['loan_create_time'] = strtotime(date("Y-m-d"));
                $update_dealInfo['repay_start_time'] = strtotime(date("Y-m-d"));

                $job_model = new \core\dao\JobsModel();
                $job_model->priority = \core\dao\JobsModel::PRIORITY_GOLD_MAKE_LOAN;
                //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
                $add_job = $job_model->addJob($function, $param, get_gmtime() + (180+$i),1);
                if (!$add_job) {
                    throw new \Exception("放款任务添加失败");
                }

                $update_dealInfo['dealId'] = $deal_id;
                $update_dealInfo['has_loan_status'] = 2;
                $request = new RequestCommon();
                $request->setVars($update_dealInfo);
                $response = $this->getRpc('goldRpc')->callByObject(array(
                    'service' => 'NCFGroup\Gold\Services\Deal',
                    'method' => 'updateLoansStatus',
                    'args' => $request,
                ));

                if (empty($response) || false == $response['data']){
                    throw new \Exception("更新标信息失败");

                }
                $GLOBALS['db']->commit();
                $result['status'] = 1;
                $i = $i + 60;
            }catch (\Exception $e){
                $GLOBALS['db']->rollback();
                $failDealIds[] = $deal_id;
                save_log('GOLD放款审核失败 '.$deal_id.' '.$e->getMessage().' '.$e->getFile().' '.$e->getFile(),C('FAILED'),'','',C('SAVE_LOG_FILE'));
            }

            save_log('批量GOLD放款审核成功 '.$deal_id,C('SUCESS'),'','',C('SAVE_LOG_FILE'));

        }

        $return['succ_num'] = count($deal_ids) - count($failDealIds);
        $return['fail_num'] = count($failDealIds);
        $return['deal_ids'] = implode(",",$failDealIds);

        ajax_return($return);
        exit;
    }

    /**
     * 审核放款
     *
     * @access public
     * @return int //0 失败 1 通过审核 2 拒绝
     */
    public function audit($data, $role, $audit, $auditType = '', $serviceId = 0, $agree = false)
    {

        $deal = $data;
        $agree = (false === $agree) ? intval($_REQUEST['agree']) : $agree;

        $operation = ServiceAuditModel::OPERATION_SAVE;
        $param = array();
        $param['service_type'] = $auditType ? $auditType :  ServiceAuditModel::SERVICE_TYPE_GOLD_PROJECT_LOAN ;
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
        try {
            $result = D('ServiceAudit')->opServiceAudit($param, $operation);
            if (!$result) {
                throw new \Exception("更新审核状态失败");
            }
            /* 临时去掉
             * if ($opType != 0) {
                $result = $this->saveOplog($deal, $admin, $submitUid, $_REQUEST['return_type'], $_REQUEST['return_reason'], $opType);
                if (!$result) {

                    throw new \Exception("插入操作记录失败");
                }
            }*/
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
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
        $loan_oplog_model->loan_money_type = 1;
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

    public function update() {
        $dealId = intval($_REQUEST['id']);
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$dealId));

        $rpcResponse = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        unset($request);
        if (empty($rpcResponse['data'])){
            $this->error('获取标信息失败');
        }

        // 满标或者还款中 标不可编辑
        //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5176,业务提出需求，满标任然可以编辑标的内容，出了问题，后果业务负责，这个项目管理找业务确认过过
        // if ($rpcResponse['data']['dealStatus'] == 2 || $rpcResponse['data']['dealStatus'] == 4){
        if ($rpcResponse['data']['dealStatus'] == 4 || $rpcResponse['data']['dealStatus'] == 5 || $rpcResponse['data']['isHasLoans'] == 1 || $rpcResponse['data'][' isDuringRepay'] == 1){
            //$this->error('已满标或者还款中不可编辑');
            $this->error('还款中不可编辑');
        }

        if($rpcResponse['data']['dealStatus'] == 0 && empty($_REQUEST['site_id'])){
            $this->error('所属网站 不能为空！');
        }
        $request = new RequestCommon();
        if(!empty($_POST['user_id'])) {
            $userInfo = M('User')->where('id='.intval($_POST['user_id']))->find();
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
        if(isset($_POST['repay_time']) && empty($_POST['repay_time'])){
            $this->error('期限不能为空');
        }
        if(($_POST ['max_loan_money']) > 0 && ($_POST ['max_loan_money']) < ($_POST ['min_loan_money'])) {
            $this->error ( "最大金额不能小于最小金额" );
        }
        $_POST['start_time'] = strtotime($_POST['start_time']);
        $_POST['start_loan_time'] = strtotime($_POST['start_loan_time']);
        // 为审核通过状态
        $_POST['publish_wait'] = 0;
        if ($_POST['bad_time']) {
            $_POST['bad_time'] = strtotime($_POST['bad_time']);
        }
        if ($_POST['deal_status'] == 4){
            if(empty($_POST['loan_create_time'])) {
                // 放款时间
                $_POST['loan_create_time'] = strtotime(date("Y-m-d"));

            } else {
                $_POST['loan_create_time'] = strtotime($_POST['loan_create_time']);
            }
            if (!empty($_POST['loan_create_time'])){
               // $golddealService = new GoldDealService();
                //$_POST['repay_start_time'] = $golddealService->getInterestDate($_POST['loan_create_time']);

                $_POST['repay_start_time'] = strtotime(date("Y-m-d",$_POST['loan_create_time']));
            }
        }
        $request->setVars($_POST);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'saveDeal',
            'args' => $request,
        ));
        if(!$response || $response['data'] == false) {
            $this->error("操作失败 errCode:".$response['errCode']." errMsg:".$response['errMsg']);
        }

        if($_POST['deal_status'] == 3){
            $jobsModel = new JobsModel();
            $jobsModel->priority = JobsModel::PRIORITY_GOLD_FAILDEAL;
            $function = '\core\service\GoldDealService::failDeal';
            $ret = $jobsModel->addJob($function,array($_POST['id']));
            if ($ret === false) {
                throw new \Exception('流标失败Jobs任务注册失败');
            }
            //截标触发上标队列上标
            $dealQueueService = new DealQueueService(0,DealQueueService::GOLD);
            $dealQueueService->process($dealId);
        }

        $this->success("操作成功");
    }
    /**
     * 编辑备注
     */
    public function edit_note() {
        //  var_dump($_REQUEST);exit;
        $id = $_REQUEST['id'];
        $deal = get_deal($id);
        if (empty($deal)) {
            $this->error("参数错误");
        }
        $this->assign("vo", $deal);
        if (!isset($_REQUEST['note'])) {
            return $this->display();
        }
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'updateDeal',
            'args' => $request,
        ));
        if(!$response || $response['data'] == false) {
            $this->error("操作失败 errCode:".$response['errCode']." errMsg:".$response['errMsg']);
        }
        $this->success("操作成功");

    }
    /**
     * 复制借款
     * @actionLock
     * lockauthor qicheng
     */
    public function copy_deal()
    {

        $copy_res = false;
        $deal_id = intval($_GET['id']);
        //$deal_id = 6487;
        $ajax = intval($_GET['ajax']);;
        if ($deal_id > 0) {
            $request = new RequestCommon();
            $request->setVars(array("id" => $deal_id));
            $response = $this->getRpc('goldRpc')->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\Deal',
                'method' => 'copyDeal',
                'args' => $request,
            ));

             if(!$response || $response['data'] == false) {
                  $this->error("操作失败 errCode:".$response['errCode']." errMsg:".$response['errMsg']);
              }else{
                 $this->success('已成功复制到未审核列表中',$ajax);
                 save_log('复制黄金标的 id:'.$deal_id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
                 $this->success('已成功复制到未审核列表中',$ajax);
                 exit;
             }
          }
          save_log('复制黄金标的 id:'.$deal_id,C('FAILED'), '', '', C('SAVE_LOG_FILE'));
          $this->error('操作失败',$ajax);
        }
    public function show_detail()
    {
        $id = intval($_REQUEST['id']);
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$id));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        if($response && ($response->errorCode != 0)) {
            throw new \Exception('RPC gold is fail!');
        }
        $goldService = new GoldService();
        $requestloan = $goldService ->getDealLog($id,true);
        $deal_info = $response["data"];
        $this->assign("deal_info",$deal_info);
        $loan_list  = $requestloan["data"];
     /*   foreach ($loan_list as $k => $load_item) {
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
        }*/

        $this->assign("loan_list",$loan_list);
        $this->assign("loan_list_count",(empty($loan_list)? 0: count($loan_list)));

        $this->display();
    }

    public function findType()
    {
        $product_mix_2 = trim($_REQUEST['name']);
       // $product_mix_2 = '黄金';
        $dealTypeGradeService = new DealTypeGradeService();
        $type_2 = $dealTypeGradeService-> findByName($product_mix_2);
        $typelist3 = $dealTypeGradeService -> getbyParentId($type_2 ->id);
        //var_dump($typelist3);
        echo json_encode($typelist3);
    }

    /**
     * 截标
     */
    public function updatemoney(){

        $id = intval($_REQUEST ['id']);
        $goldDealData = new GoldDealData();

        // 需要加锁
        $lock = $goldDealData->enterPool($id);
        if ($lock === false) {
            $goldDealData->leavePool($id);
            $this->error("系统繁忙，请稍后再试");
        }
        //标的信息
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$id));

        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        if (empty($response['data'])){
            $goldDealData->leavePool($id);
            $this->error("标信息不存在");
        }
        $dealInfo = $response['data'];
        //判断前置条件
        if($dealInfo['dealStatus'] !=1) $this->error('只有状态为“进行中”的标才能修改为满标');
        if($dealInfo['loadMoney'] <= 0) $this->error('投资额为0的标禁止修改为满标');

        $update_dealInfo['id'] = $id;
        $update_dealInfo['borrow_amount'] = $dealInfo['loadMoney'];
        $update_dealInfo['point_percent'] = 1;
        $update_dealInfo['success_time'] = time();
        $update_dealInfo['deal_status'] = 2;
        $request = new RequestCommon();
        $request->setVars($update_dealInfo);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'updateDeal',
            'args' => $request,
        ));

        if (empty($response['data'])){
            $goldDealData->leavePool($id);
            $this->error("标更新失败");
        }
        // 发送相关消息和邮件
        //生成借款人合同
        //合同jobs
        $param = array();
        $param['borrowId'] = $dealInfo['userId'];
        $param['dealId'] = $id;
        $param['userId'] = 0;
        $param['loadId'] = 0;
        $param['isFull'] = true;
        $function = '\core\service\SendContractService::sendGoldConstract';
        $job_model = new \core\dao\JobsModel();
        $job_model->priority = JobsModel::PRIORITY_GOLD_CONTRACT;
        $ret = $job_model->addJob($function,$param); //不重试
        if ($ret === false) {
            $goldDealData->leavePool($id);
            $this->error("添加借款人生成合同任务失败");
        }
        // 解锁
        $goldDealData->leavePool($id);

        //截标触发上标队列上标
        $dealQueueService = new DealQueueService(0,DealQueueService::GOLD);
        $dealQueueService->process($id);

        $this->success("操作成功");

    }

    /**
     * 导出 index csv
     */
    public function export_csv()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $memory_start = memory_get_usage();
        $id = trim($_REQUEST['id']);
        $name = trim($_REQUEST['name']);
        $real_name = trim($_REQUEST['real_name']);
        $deal_status = trim($_REQUEST['deal_status']);
        $request = new RequestCommon();
        if($deal_status != 'all' &&isset($_REQUEST['deal_status']) ){
            $request->setVars(array('deal_status'=> $deal_status ));
        }
        if(trim($real_name)!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%" . $real_name . "%'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $request->setVars(array('ids'=> array($ids) ));
        }
        $filename = "Goldloadcurrent-Log-" . date('YmdHis') . ".csv";
        $content = iconv("utf-8","gbk","标的ID,标的名称,增值支付方式,产品期限,标的克重,成交克重,标签,最低购买克重,最高购买克重,购买人次,标的售卖状态,状态,上标平台,挂网时间,满标时间,合同类型,用户买入手续费,年化补偿基本率,年化平台服务费率");
        $content = $content . "\n";
        $i = 0;
        $total = 0;
        $pageSize = 1000;
        $hasTotalCount = 1;
        $pageNo = 1;
        do {
            try {
                $request->setVars(array("pageNum"=>$pageNo,"pageSize"=>$pageSize,"id"=>$id,"name"=>$name,"real_name"=>$real_name,'publish_wait' => 0));
                $response = $this->getRpc('goldRpc')->callByObject(array(
                    'service' => '\NCFGroup\Gold\Services\Deal',
                    'method' => 'listDeal',
                    'args' => $request,
                ));
                if ($total == 0) {
                    // 获取总的数据条数
                    $total = $response['data']['totalSize'];
                    $hasTotalCount = 0;
                }
                $deal_list = $response['data']['data'];
                $order_value = array(
                    'id'=>'""',
                    'name'=>'""',
                    'loantype'=>'""',
                    'repayTime'=>'""',
                    'borrowAmount'=>'""',
                    'loadMoney' =>'""',
                    'dealTagName'=>'""',
                    'minLoanMoney'=>'""',
                    'maxLoanMoney'=>'""',
                    'buyCount'=>'""',
                    'dealStatus'=>'""',
                    'isEffect' => '""',
                    'siteId' => '""',
                    'startTime'=>'""',
                    'successTime' => '""',
                    'contractTplType'=>'""',
                    'buyerFee'=>'""',
                    'rate'=>'""',
                    'loanFeeRate'=>'""',
                );

                foreach($deal_list as $k=>$v)
                {

                    $v['repayTime'] = ($v['loantype'] == 5) ? $v['repayTime'].'天' :$v['repayTime']. '个月';
                    $v['isEffect'] = ($v['isEffect'] == 1) ? '有效' :'无效';
                    $v['loantype'] = ($v['loantype'] == 5) ? '已购黄金及补偿克重到期一次性交付' :'已购黄金到期交付，补偿克重按季度交付';
                    $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
                    $order_value['name'] = '"' . iconv('utf-8','gbk',$v['name']) . '"';
                    $order_value['loantype'] = '"' . iconv('utf-8','gbk',$v['loantype']) . '"';
                    $order_value['repayTime'] = '"' . iconv('utf-8','gbk',$v['repayTime']) . '"';
                    $order_value['borrowAmount'] = '"' . iconv('utf-8','gbk',format_price($v['borrowAmount'], false)) . '"';
                    $order_value['loadMoney'] = '"' . iconv('utf-8','gbk',format_price($v['loadMoney'], false)) . '"';
                    $order_value['dealTagName'] = '"' . iconv('utf-8','gbk',$v['dealTagName']) . '"';
                    $order_value['minLoanMoney'] = '"' . iconv('utf-8','gbk',format_price($v['minLoanMoney'], false)) . '"';
                    $order_value['maxLoanMoney'] = '"' . iconv('utf-8','gbk',format_price($v['maxLoanMoney'], false)) . '"';
                    $order_value['buyCount'] = '"' . iconv('utf-8','gbk',$v['buyCount']) . '"';
                    $order_value['dealStatus'] = '"' . iconv("utf-8", "gbk", l("DEAL_STATUS_". $v['dealStatus'])) . '"';
                    $order_value['isEffect'] = '"' . iconv('utf-8','gbk',$v['isEffect']) . '"';
                    $order_value['siteId'] = '"' . iconv('utf-8','gbk',get_gold_deal_domain($v['siteId'],true)) . '"';
                    $order_value['startTime'] = '"' . iconv('utf-8','gbk',to_date($v['startTime'])) . '"';
                    $order_value['successTime'] = '"' . iconv('utf-8','gbk',to_date($v['successTime'])) . '"';
                    $order_value['contractTplType'] = '"' . iconv('utf-8','gbk',$v['contractTplType']) . '"';
                    $order_value['buyerFee'] = '"' . iconv('utf-8','gbk',$v['buyerFee']) . '"';
                    $order_value['rate'] = '"' . iconv('utf-8','gbk',$v['rate']) . '"';
                    $order_value['loanFeeRate'] = '"' . iconv('utf-8','gbk',$v['loanFeeRate']) . '"';

                    if(is_array($ids) && count($ids) > 0){
                        if(array_search($v['id'],$ids) !== false){
                            $content .= implode(",", $order_value) . "\n";
                        }
                    }else{
                        $content .= implode(",", $order_value) . "\n";
                    }
                }
            } catch (\Exception $ex) {
                Logger::error('exportList: '.$ex->getMessage());
            }
            // 处理下一页数据
            $pageNo++;
            $i += $pageSize;
        } while ($i <= $total);
        $datatime = date("YmdHis",get_gmtime());
        header("Content-Disposition: attachment; filename={$datatime}_deal_loan_list.csv");
        echo $content;
        return;
    }
    /**
     * 删除标
     */
    function delete(){
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $deny = '';
        if (empty($id)){
            $this->error (l("INVALID_OPERATION"),$ajax);
        }

        //重新转换成数组
        $ids = explode(',', $id);

        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$ids));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'compareDeleteByIds',
            'args' => $request,
        ));
        if (empty($response['data'])){
            $errMsg = empty($response['errMsg']) ? '获取可删除标失败': $response['errMsg'];
            $this->error($errMsg,$ajax);
        }

        $rs_arr = $response['data'];
        if (count($rs_arr['allow']) == 0){
            if (count($rs_arr['deny']) > 0) {
                $deny = implode(',', $rs_arr['deny']).' 不能删除';
            }
            save_log($id.'_'.l("DELETE_FAILED"),0);
            $this->error (l("DELETE_FAILED").$deny,$ajax);
        }

        // 更新允许的
        unset($request);
        $request = new RequestCommon();
        $request->setVars(array("ids"=> implode(',',$rs_arr['allow'])));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'batchDelDealByIds',
            'args' => $request,
        ));

        if (empty($response['data'])){
            save_log($id.'_'.l("DELETE_FAILED"),0);
            $this->error (l("DELETE_FAILED"),$ajax);
        }else {

            if (count($rs_arr['deny']) > 0) {
                $deny = implode(',', $rs_arr['deny']) . ' 不能删除';
                $this->error(l("DELETE_FAILED") . $deny, 0);
            } else {
                save_log($id . l("DELETE_SUCCESS"), 1);
                $this->success(l("DELETE_SUCCESS"), $ajax);
            }
        }
    }
    public function show() {

        $dealId = $_REQUEST['id'];
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$dealId));
        $rpcResponse = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        echo $rpcResponse['data']['intro'];
    }

    /**
     * 彻底删除
     */
    public function foreverdelete(){
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (empty($id)){
            $this->error (l("INVALID_OPERATION"),$ajax);
        }

        //重新转换成数组
        $ids = explode(',', $id);

        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$ids));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'compareDeleteByIds',
            'args' => $request,
        ));
        if (empty($response['data'])){
            $errMsg = empty($response['errMsg']) ? '获取可删除标失败': $response['errMsg'];
            $this->error($errMsg,$ajax);
        }

        $rs_arr = $response['data'];
        if (count($rs_arr['allow']) == 0){
            if (count($rs_arr['deny']) > 0) {
                $deny = implode(',', $rs_arr['deny']).' 不能删除';
            }
            save_log($id.'_'.l("DELETE_FAILED"),0);
            $this->error (l("DELETE_FAILED").$deny,$ajax);
        }

        $ids = implode(',',$rs_arr['allow']);
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$ids));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'batchForeverDeleteByIds',
            'args' => $request,
        ));
        if (empty($response['data'])){
            $errMsg = empty($response['errMsg']) ? '彻底删除标失败': $response['errMsg'];
            $this->error($errMsg,$ajax);
        }
        $msg = $ids.' 彻底删除 success';
        save_log($msg,1,$ids,$ids,2);


        $this->success(l("DELETE_SUCCESS"), $ajax);


    }

    /**
     * 恢复
     */
    public function restore(){

        $ajax = intval($_REQUEST['ajax']);
        $ids = $_REQUEST ['id'];
        if (empty($ids)){
            $this->error (l("INVALID_OPERATION"),$ajax);
        }

        $request = new RequestCommon();
        $request->setVars(array("ids"=>$ids));
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'batchRestoreDealByIds',
            'args' => $request,
        ));
        if (empty($response['data'])){
            $errMsg = empty($response['errMsg']) ? '恢复失败': $response['errMsg'];
            $this->error($errMsg,$ajax);
        }

        $msg = $ids.' 恢复 success';
        save_log($msg,1,$ids,$ids,2);

        $this->success(l("DELETE_SUCCESS"), $ajax);

    }

}

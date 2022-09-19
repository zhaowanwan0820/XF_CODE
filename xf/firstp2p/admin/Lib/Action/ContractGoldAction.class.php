<?php
// +----------------------------------------------------------------------
// | 合同管理
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------
use core\service\ContractService;
use core\service\ContractNewService;
use core\service\ContractSignService;
use core\service\DealService;
use core\dao\DealContractModel;
use core\dao\ContractContentModel;
use core\service\UserService;
use core\dao\DealModel;
use core\dao\ContractModel;
use core\dao\ContractFilesWithNumModel;
use core\dao\OpLogModel;
use core\dao\OpStatusModel;
use core\dao\UserModel;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use libs\utils\Rpc;
use libs\utils\Logger;

class ContractGoldAction extends CommonAction {
    // 首页
    public function index() {
        $request = new RequestCommon();
        if(intval($_REQUEST ['deal_id']) > 0){
            $request->setVars(array("deal_id"=>intval($_REQUEST ['deal_id'])));
            $response = $this->getRpc('goldRpc')->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\Deal',
                'method' => 'getDealById',
                'args' => $request,
            ));
            if($response && ($response->errorCode != 0)) {
                throw new \Exception('RPC gold is fail!');
            }
        }

        $where = "1=1";
        if(!empty($_REQUEST ['cnum'])){
            $where .= " AND number='{$_REQUEST ['cnum']}'";
        }
        if(!empty($_REQUEST ['cname'])){
            $where .= " AND title='{$_REQUEST ['cname']}'";
        }
        if(!empty($_REQUEST ['cuser_id'])){
            $where .= " AND user_id='{$_REQUEST ['cuser_id']}'";
        }
        if(!empty($_REQUEST ['cborrow_user_id'])){
            $where .= " AND borrow_user_id='{$_REQUEST ['cborrow_user_id']}'";
        }
               $p = empty($_REQUEST['p'])?1:$_REQUEST['p'];
                $contractRequest = new RequestGetContractByDealId();
                $contractRequest->setDealId(intval($_REQUEST['deal_id']));
                $contractRequest->setSourceType(100);
                $contractRequest->setPageNo(intval($p));
                $contractRequest->setWhere(trim($where));
             //   $contractRequest->setSourceType($deal_info['deal_type']);
                $response = $this->getRpc('contractRpc')->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Contract",
                    'method' => "getContractByDealId",
                    'args' => $contractRequest,
                ));

                $listRows = $response->list;
                $isNewCont = 1;
            foreach($listRows as &$listRow){
                    if(!in_array($listRow['type'],array(1,5))){
                        $isNewCont = 0;
                    }
                    $listRow['user_name'] = $this->get_real_name($listRow['user_id']);
                    $listRow['borrow_user_name'] = $this->get_real_name($listRow['borrow_user_id']);

                    $agency_user =  $GLOBALS ['db']->getAll ( "select * from " . DB_PREFIX . "agency_user where agency_id = " . $listRow ['agency_id'] );
                    foreach($agency_user as $k=> &$v){
                        $user_info = $user_service->getUser($v['user_id']);
                        $agency_user[$k]['real_name'] = $user_info['real_name'];
                    }
                    $listRow['agency_user'] = $agency_user;
                    $listRow['agency_name'] = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "deal_agency where id = " . $listRow ['agency_id'] );
                    $advisory_user = $GLOBALS ['db']->getAll ( "select * from " . DB_PREFIX . "agency_user where agency_id = " . $listRow ['advisory_id'] );

                    foreach($advisory_user as $k=> &$v){
                        $user_info = $user_service->getUser($v['user_id']);
                        $advisory_user[$k]['real_name'] = $user_info['real_name'];

                    }
                    $listRow['advisory_user'] = $advisory_user;

                    $listRow['advisory_name'] = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "deal_agency where id = " . $listRow ['advisory_id'] );
                    $entrust_user = $GLOBALS ['db']->getAll ( "select * from " . DB_PREFIX . "agency_user where agency_id = " . $listRow ['entrust_agency_id'] );
                    foreach($entrust_user as $k=> &$v){
                        $user_info = $user_service->getUser($v['user_id']);
                        $entrust_user[$k]['real_name'] = $user_info['real_name'];

                    }
                    $listRow['entrust_user'] = $entrust_user;

                    $listRow['entrust_name'] = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "deal_agency where id = " . $listRow ['entrust_agency_id'] );
                    //购买克重
                    $request->setVars(array('deal_load_id' => $listRow ['deal_load_id']));
                    $response = $this->getRpc('goldRpc')->callByObject(array(
                                'service' => 'NCFGroup\Gold\Services\DealLoad',
                                'method' => 'getDealLoadInfoById',
                                'args' => $request,
                    ));
                    $listRow['load_money'] = ($response['errCode'] == 0) ? $response['data']['buyAmount'] : '--';
                }

                $sign_num_limit = $GLOBALS['dict']['CONT_SIGN_NUM'];
                $p = new Page ( $response->count['num'], 10 );
                $this->assign ( 'is_new_cont', $isNewCont);
                $this->assign ( 'page', $p->show() );
                $this->assign ( 'list', $listRows );
                $this->assign ( 'sign_num_limit', $sign_num_limit ? $sign_num_limit : 20);
                $this->assign ( 'deal_id', intval($_REQUEST ['deal_id']));
                $this->display();
    }

    /**
     * 重生合同
     */
    public function recreate() {
        $deal_id = intval ( $_REQUEST ['id'] );
        if (!$deal_id){
            $this->error("操作失败", 1);
        }

        $cont_service = new ContractService();
        $del = $cont_service->delContByDeal($deal_id);

        $deal_service = new DealService();
        $res = $deal_service->sendDealContract($deal_id);

        if($res){
            $op_status = new OpStatusModel();
            $params = array(':op_name' => OpLogModel::instance()->get_opname_by_content($deal_id, OpLogModel::OPNAME_DEAL_CONTRACT));
            $op_row = $op_status->findBy("`op_name` = ':op_name'", 'id', $params);
            if($op_row){
                $op_status->update_status($op_row->id, 1);
            }
        }

        $dc_model = new DealContractModel();
        $deal = DealModel::instance()->find($deal_id);
        if(($deal['contract_tpl_type'] == 'NGRZR') or ($deal['contract_tpl_type'] == 'NQYZR')) {
            $deal['contract_version'] = 2;
        }
        $dc_model->create($deal);

        if ($res) {
            $this->success("操作成功", 1);
        } else {
            $this->error("操作失败", 1);
        }
    }

   // 首页
    public function opencontract()
    {
        if(empty($_REQUEST['num'])||empty($_REQUEST['id'])){
            $this->error("非法操作！！！");
        }
        $contractService = new ContractNewService();
        //项目合同展示
        $contract = $contractService->showContract(intval($_REQUEST['id']),intval($_REQUEST['dealId']),0,intval($_REQUEST['type']));
        echo hide_message($contract['content']);
    }

    // 下载
    public function download()
    {
        if(empty($_REQUEST['num'])||empty($_REQUEST['id'])){
            $this->error("非法操作！！！");
        }
        $contractService = new ContractNewService();
        $contract = $contractService->contractDownload(intval($_REQUEST['id']), intval($_REQUEST['dealId']), $_REQUEST['num'],0,100);

    }

    /**
    * 获取用户姓名
    * @param id 用户id
    * @return string
    */
    private function get_real_name($id){
        if(!$id){
            return false;
        }
        $userinfo = $GLOBALS ['db']->get_slave()->getRow ( "select user_name,real_name from " . DB_PREFIX . "user where id = " . intval($id));
        $user_name = !empty($userinfo['real_name']) ? $userinfo['real_name'] : $userinfo['user_name'];
        return $user_name;
    }

    /**
     * 修改合同
     */
    public function edit(){
        $id = empty($_GET['id']) ? '' : intval($_GET['id']);
        if(empty($id)){
            $this->error("非法操作！！！");
        }
        $contract_service = new ContractService();
        $cont_info = $contract_service->showContract($id);
        $this->assign ( 'contract_info', $cont_info );
        $this->display ();
    }

    /**
     * 保存合同
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function update(){
        $id = empty($_POST['id']) ? '' : intval($_POST['id']);
        $number = empty($_POST['number']) ? '' : $_POST['number'];
        $content = empty($_POST['content']) ? '' : $_POST['content'];
        $new_number = empty($_POST['new_number']) ? '' : $_POST['new_number'];
        $type = empty($_POST['type']) ? '' : $_POST['type'];

        if(empty($id)){
            $this->error("非法操作！");
        }
        if(empty($content)){
            $this->error("非法操作！");
        }
        if(empty($type)){
            $this->error("非法操作！");
        }

        // 更新数据
        $log_info = "合同id:".$id;

        $data = array();
        $data['number'] = $new_number;

        $ids = ($type == 2) ? M(MODULE_NAME)->where("number = '".$number ."'")->getField('group_concat(`id`)') : $id;
        $where = sprintf("id in (%s)", $ids);
        $res = M(MODULE_NAME)->where($where)->save ($data);

        if (false !== $res) {
            $id_arr = explode(',', $ids);
            foreach($id_arr as $id){
                //更新content,因已分表，需要循环去不同的表update
                $update_content = ContractContentModel::instance()->update($id, $content);
                //重新生成PDF文件
                $this->dorepdf($id);
            }
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"));
        }
    }

    /**
     * 管理员代签合同
     * @author wangyiming 20131126
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function agree() {
        $ajax = intval($_REQUEST['ajax']);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false ;
        $uid = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : false ;
        if (!$id || !$uid) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }

        //获取合同信息
        $contract_service = new ContractService();
        $contract = $contract_service->getContract($id);

        $user_service = new UserService();
        $user = $user_service->getUserViaSlave($uid);

        if (empty($contract) || empty($user) || $contract['type'] == 7) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }

        $re = $contract_service->signOneContNew($contract, $user);

        if (!$re) {
            $this->error (L("UPDATE_FAILED"), $ajax);
        }
        $this->success(L("UPDATE_SUCCESS"), $ajax);
    }

    /**
     * 一键签署
     * @actionlock
     */
    public function agree_all() {
        $ajax = intval($_REQUEST['ajax']);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false ;
        if (!$id) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $type = intval($_REQUEST['type']);

        $deal_info = DealModel::instance()->find($id);
        if (empty($deal_info)) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }

        $adm = es_session::get(md5(conf("AUTH_KEY")));
        if (!$adm) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }

        $contract_service = new ContractService();
        if ($type == 0) {
            $re = $contract_service->signDealAllCont($id);
            $act = "一键签署";
        } elseif ($type == 1) {
            $re = $contract_service->signDealContNew($id, $deal_info['user_id']);
            $act = "代借款人签署";
        } elseif ($type == 2) {
            $re = $contract_service->signDealContNew($id, 0, 1, 0);
            $act = "代担保公司签署/资产管理方";
        }

        if (!$re) {
            save_log($id . " " . $deal_info['name']. " " . $act . " " . $adm['adm_id'] . "|" . $adm['adm_name'] . " " . L("UPDATE_FAILED").$re,C('FAILED'), array(), array(), C('SAVE_LOG_FILE'));
            $this->error (L("UPDATE_FAILED"), $ajax);
        }

        save_log($id . " " . $deal_info['name']. " " . $act . " " . $adm['adm_id'] . "|" . $adm['adm_name'] . " " . L("UPDATE_SUCCESS"),C('SUCCESS'), array(), array(), C('SAVE_LOG_FILE'));
        $this->success(L("UPDATE_SUCCESS"), $ajax);
    }

    /**
    * 批量彻底删除合同数据
    * @actionlock
    * @lockAuthor daiyuxin
    */
    public function foreverdelete(){

        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];

        if (isset ( $id )) {

            $id_arr = explode ( ',', $id );
            $condition = array ('id' => array ('in',  $id_arr) );

            $GLOBALS['db']->query("delete FROM ".DB_PREFIX."agency_contract where contract_id in (".$id.")");
            $res = M(MODULE_NAME)->where ( $condition )->delete();

            if ($res) {
                $content_model = new ContractContentModel();
                $content_model->mdel($id_arr);

                save_log("合同id$id".l("FOREVER_DELETE_SUCCESS"),1);
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log("合同id$id".l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
    * 重新生成pdf文件
    */
    public function repdf(){
        $res = $this->dorepdf(intval($_REQUEST['id']));
        if($res){
            $this->success('操作成功');
        }
        $this->error('操作失败');
    }

    /**
     * 重新生成pdf文件
     */
    private function dorepdf($id){
        $id = intval($id);
        if($id <= 0){
            return false;
        }
        $contract = M("Contract")->where("id = '".$id ."'")->find();
        if(empty($contract)){
            return false;
        }
        $contract_service = new ContractService();
        return $contract_service->unlinkContFile($id);
    }

    /**
     * 下载pdf文件
     */
    public function downloadreNew(){
        $id = intval($_REQUEST['id']);
        if($id >= 0){
            $contract_service = new ContractService();
            $contract_service->contractDownloadRenew($id);
        }
    }

    public function agreeAllGold()
    {
        // $ajax = intval($_REQUEST['ajax']);
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false ;
        if (!$id) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $type = intval($_REQUEST['type']);
        $adm = es_session::get(md5(conf("AUTH_KEY")));
        if (!$adm) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $contractService = new ContractNewService();
        $res = $contractService->signGoldDealContNew($id,$type,0);
        if (!$res) {
            $this->error (L("UPDATE_FAILED"), $ajax);
        }else{
            $this->success(L("UPDATE_SUCCESS"), $ajax);
        }
    }

    /**
     * 下载时间戳的pdf文件
     */
    public function downloadtsa(){
        $id = intval($_REQUEST['id']);
        $dealId = intval($_REQUEST['dealId']);
        if($id >= 0){
            $contractSignService = new ContractSignService();
            $ret = $contractSignService->readSignedPdf($id,true,0,$dealId,2);
            return $ret;
        }
    }

    /**
     * 同步打戳文件
     */
    public function signTsaSync(){
        $id = intval($_REQUEST['id']);
        $number = $_REQUEST['num'];
        $dealId = isset($_REQUEST['dealId'])?intval($_REQUEST['dealId']):null;
        $contractSign = new ContractSignService();
        if($id >= 0){
            // 存在的且状态是已经打过的就不打了。米等用
            $exist = ContractFilesWithNumModel::instance()->getAllByContractNum($number);
            if(!empty($exist) && $exist[0]['status'] == ContractFilesWithNumModel::TSA_STATUS_DONE){
                $contractSign->afterHook(null,$dealId,$number);
                Logger::wLog(implode(" | ", array(__CLASS__, __FUNCTION__, $id, $number, "already exist !")), Logger::INFO, Logger::FILE);
                return true;
            }else{
                if(empty($exist)){
                    // 现插入一发,状态为0。
                    $fileRet = ContractFilesWithNumModel::instance()->addNewRecord($id,
                            $number,ContractFilesWithNumModel::FDFS_DEFAULT,ContractFilesWithNumModel::FDFS_DEFAULT,-1);
                }
                $success = $contractSign->signOneContract($id,false,$dealId);
                if($success>0){
                    echo json_encode(array('errno'=>0));
                }else{
                    echo json_encode(array('errno'=>$success));
                }
            }
        }
    }

    /**
    * 异步打戳
    */
    public function signTsaWithId(){
        $dealId = intval($_REQUEST['id']);
        $contractService = new ContractNewService();
        $ret = $contractService ->startSignAllContract($dealId);
        if(!empty($ret)){
            echo json_encode(array('errno'=>0));
        }else{
            echo json_encode(array('errno'=>1));
        }
    }

    public function checkTsaWithDealId($dealId,$page){
        $list = ContractModel::instance()->getContractIdNumbersByDealId($dealId);
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);
        $ret = array('dealId'=>$dealId);
        $ret['contractNum'] = count($list);
        $ret['failInfo'] = array();
        $fails = array();
        $count = 0;
        if(!$list){
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestGetContractByDealId();
            $contractRequest->setDealId(intval($dealId));
            $contractRequest->setSourceType(intval($deal['deal_type']));
            // 暂时不处理分页信息
            // $contractRequest->setPageNo($page);
            $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractIdNumByDealId",$contractRequest);
            if($response->errCode == 0){
                $list = $response->list;
            }
            $ret['contractNum'] = count($list);
        }
        foreach($list as $one){
            $tsaInfo = ContractFilesWithNumModel::instance()->getAllByContractNum($one['number']);
            if(!empty($tsaInfo[0]['group_id']) && !empty($tsaInfo[0]['path'])){
                $count ++;
            }else{
                $ret['failInfo'][] = array('id'=>$one['id'],'number'=>$one['number'],'group'=>$tsaInfo[0]['group_id'],'path'=>$tsaInfo[0]['path']);
            }
        }
        $ret['tsaNum'] = $count;
        if(empty($ret['failInfo'])){
            $ret['hasfail'] = 0;
        }else{
            $ret['hasfail'] = 1;
        }
        return $ret;
    }
    public function checkTsa(){
        $dealIds = $_REQUEST['deal_id'];
        $page = isset($_REQUEST['p'])?intval($_REQUEST['p']):1;
        $ids = explode(",",$dealIds);
        $list = array();
        foreach($ids as $one){
            $ret = $this->checkTsaWithDealId(intval($one),$page);
            $list[] = $ret;
        }
        $this->assign ( 'list', $list );
        $this->display();
    }


    /**
     * 导出合同
     */
    public function export_contract(){

        $ids = $_REQUEST['id'];
        if(empty($ids)){
            $this->error('无可导出的数据！');
        }
        $where_array = array();
        $need_search = false;

        if(trim($_REQUEST['cid']) != ''){
            $where_array[] = "id = ".intval(trim($_REQUEST['cid']));
            $need_search = true;
        }
        if(trim($_REQUEST['cname']) != ''){
            $where_array[] = "title = '".trim($_REQUEST['cname'])."'";
            $need_search = true;
        }
        if(trim($_REQUEST['cnum']) != ''){
            $where_array[] = "number = ".intval(trim($_REQUEST['cnum']));
            $need_search = true;
        }
        if(trim($_REQUEST['cuser_name']) != ''){
            $user_id_all = $GLOBALS['db']->getAll("SELECT id FROM ".DB_PREFIX."user where real_name like '%".trim($_REQUEST['cuser_name'])."%'");

            if($user_id_all){
                foreach($user_id_all as $user_id){
                    $id_arr[] = $user_id["id"];
                }
                $where_array[] = "user_id in (".implode(',', $id_arr).") ";
            }else{
                $where_array[] = "user_id = 0";
                $where_array[] = "agency_id = 0";
            }
            $need_search = true;
        }
        if(trim($_REQUEST['cuser_id']) != ''){
            $where_array[] = "user_id = ".intval(trim($_REQUEST['cuser_id']));
            $need_search = true;
        }
        if(trim($_REQUEST['cdeal_id']) != ''){
            $where_array[] = "deal_id = ".intval(trim($_REQUEST['cdeal_id']));
            $need_search = true;
        }

        if($need_search == false){
            $where_array[] = "id in ($ids)";
        }
        $where = $where_array ? implode(' and ', $where_array) : '';

        $name = $this->getActionName ();
        $model = D ( $name );
        $voList = $model->where ( $where )->order ( "id desc" )->findAll ();

        if(empty($voList)){
            $this->error('无可导出的数据！');
        }

        $content = iconv("utf-8","gbk","合同id,合同标题,合同编号,角色,用户姓名,预签状态,签署状态,签署时间,借款标题,合同创建时间,发送状态,合同状态,投资金额,状态")."\n";

        foreach ( $voList as &$val ){

            //获取合同的用户id 对应的用户名
            $val['user_name'] = '担保公司';

            if($val ['user_id']){
                $val['user_name'] = $this->get_real_name($val['user_id']);

            }elseif($val['agency_id']){
                $val['user_name'] = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "deal_agency where id = " . $val ['agency_id'] );
            }

            $val['contract_time'] = '';
            $val['sign_before'] = '已预签';

            if($val['agency_id']){
                $val['sign_before'] = '--';
            }

            $deal_info = $GLOBALS ['db']->getRow ( "select `name`,`user_id`,`contract_tpl_type`,`deal_status` from " . DB_PREFIX . "deal where id = " . $val ['deal_id'] );

            $val['deal_name'] = $deal_info['name'];
            $val['status'] = $val['status'] == 0 ? '无效' : '有效';
            $val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
            $val['send_status'] = $val['is_send'] == 0 ? '未发送' : '发送成功';
            $val['deal_status_cn'] = get_deal_status($deal_info['deal_status']);

            //获取合同金额
            $money = $GLOBALS['db']->getRow ("select `id`,`money` from " . DB_PREFIX . "deal_load where id = {$val['deal_load_id']}");
            $val['money'] = $money == 0 ? '--' : $money['money'];

            //借款人和出借人
            if ($val ['user_id'] > 0 && $val['type'] != 3) {

                $val ['sign_info'] = '--';
                $val ['usertype'] = '出借人';

                //获取该合同对应的借款人id
                if ($val ['user_id'] == $deal_info['user_id']) {//借款人
                    $val ['usertype'] = '借款人';
                    $val ['sign_info'] = ($val['type'] == 7) ? '--' : '未签';
                    if($val['type'] == 7){
                        $is_sign = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "agency_contract where agency_id = 0 and pass = 1 and deal_id = " . $val ['deal_id'] . " and contract_id = " . $val ['id'] );
                        if ($is_sign){
                            $val ['sign_info'] = '已签';
                            $val ['contract_time'] = date("Y-m-d H:i:s", $is_sign['create_time']);
                        }
                    }
                }

                //保证人和担保公司
            } else {
                $val ['sign_info'] = '--';
                $val ['usertype'] = '保证人';

                //担保公司
                if($val['user_id'] == 0 && $val['agency_id'] > 0){

                    $agency_user = array();
                    $contract_tpl_type = $GLOBALS ['db']->getOne ( "select contract_tpl_type from " . DB_PREFIX . "deal where id = " . $val ['deal_id'] );
                    if($contract_tpl_type == 'HY' && $val['agency_id'] == $GLOBALS['dict']['HY_DBGS']){
                        FP::import("libs.common.dict");
                        foreach(dict::get('HY_DB') as $agency_user_hy){
                            $user_info = $GLOBALS ['db']->getRow ( "select id as user_id from " . DB_PREFIX . "user where user_name = '" . $agency_user_hy . "'" );
                            if($user_info){
                                $agency_user[] = $user_info;
                            }
                        }
                    }else{
                        $agency_user = $GLOBALS ['db']->getAll ( "select user_id from " . DB_PREFIX . "agency_user where agency_id = " . $val ['agency_id'] );
                    }
                    $contract_time = $sign_info = array ();
                    foreach ( $agency_user as $uval ) {
                        $is_sign = $GLOBALS ['db']->getRow ( "select * from " . DB_PREFIX . "agency_contract where agency_id = " . $val ['agency_id'] . " and pass = 1 and deal_id = " . $val ['deal_id'] . " and user_id = " . $uval ['user_id'] . " and contract_id = " . $val ['id'] );
                        $sign_msg = '未签';
                        $agency_real_name = $this->get_real_name($uval ['user_id']);

                        if ($is_sign){
                            $sign_msg = '已签';
                            $contract_time[] = $agency_real_name.'['.date("Y-m-d H:i:s", $is_sign['create_time']).']';
                        }
                        $sign_info[] = $agency_real_name.'['.$sign_msg.']';
                    }
                    $val ['usertype'] = '担保公司';
                    $val ['sign_info'] = implode("\t\t", $sign_info);
                    $val ['contract_time'] = implode("\t\t", $contract_time);
                }
            }

            $row = sprintf("%s,%s,\t%s,%s,%s,%s,%s,\t%s,%s,\t%s,%s,%s,%s,%s",
                    $val['id'], $val['title'], $val['number'], $val['usertype'], $val['user_name'], $val['sign_before'], $val['sign_info'], $val['contract_time'] ? $val['contract_time'] : '', $val['deal_name'].' [id:'.$val['deal_id'].']', $val['create_time'], $val['send_status'], $val['status'],$val['money'],$val['deal_status_cn']
            );
            $content .= iconv("utf-8","gbk",$row) . "\n";
        }

        $datatime = date("YmdHis",time());
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=contract_{$datatime}.csv");
        header('Cache-Control: max-age=0');
        echo $content;


        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportContract',
                'analyze' => $where
                )
        );



    }

    /**
     * 设置单个合同需要单独签署
     */
    public function needsign(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $tag = isset($_GET['tag']) ? intval($_GET['tag']) : 0;
        $ajax = intval($_GET['ajax']);
        if($id <= 0){
            $this->error("非法操作！！！");
        }

        $data['resign_status'] = 0;
        $data['resign_time'] = '';
        $data['is_needsign'] = $tag;
        M('Contract')->where('id = ' . $id)->save($data);

        $this->success(L("UPDATE_SUCCESS"),$ajax);
    }

    /**
     * 补发单条合同
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function update_contract(){

        $id = intval($_GET['id']);
        $role = intval($_GET['role']);

        if($id <= 0 || $role <= 0){
            $this->error("非法操作！");
        }

        $info = M('Contract')->where('id = ' . $id)->find();
        $contract_service = new ContractService();
        $res = $contract_service->contractRenew($info['deal_id'], '', array($id));

        if($res['num'] > 0){
            save_log("合同id$id补发成功",1);
            $this->success("已补发！");
        }
        save_log("合同id$id补发失败",0);
        $this->error("操作失败！");
    }

    /**
     * 合同所属用户角色显示
     */
    private function contract_character($role){
        $character = array(
            1 => array('role' => 1,'name' => '借款人'),
            2 => array('role' => 2,'name' => '出借人'),
            3 => array('role' => 3,'name' => '保证人'),
            4 => array('role' => 4,'name' => '担保公司'),
            5 => array('role' => 5,'name' => '资产管理方'),
        );
        return $character[$role];
    }

    /**
     * export_all
     * 导出一个标下的所有合同
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @access public
     * @return void
     */
    function export_all() {
        $deal_id = $_REQUEST['deal_id'];
        if (empty($deal_id)) {
            if(isset($_REQUEST['id'])) {    //如果没有deal_id 代表是从合同管理页面的导出来的
                $this->export_contract();
                return;
            } else {
                $this->error('deal_id 数据有误');
            }
        }

        $deal_info = $GLOBALS ['db']->getRow ( "SELECT `id`,`name`,`user_id`, `contract_tpl_type`,`agency_id`,`advisory_id`,`deal_type` FROM " . DB_PREFIX . "deal WHERE id = " . $deal_id );
        if(is_numeric($deal_info['contract_tpl_type'])){
            $contractRequest = new RequestGetContractByDealId();
            $contractRequest->setDealId(intval($deal_id));
            $contractRequest->setSourceType($deal_info['deal_type']);
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Contract",
                'method' => "getContractByDealId",
                'args' => $contractRequest,
            ));

            $list = $response->list;
            $content = iconv("utf-8","gbk","合同id,合同标题,合同编号,借款人姓名,借款人签署状态,借款人签署时间,投资人姓名,投资人签署状态,投资人签署时间,担保公司名称,担保公司签署状态,担保公司签署时间,资产管理方名称,资产管理方签署状态,资产管理方签署时间,借款标题,合同创建时间,发送状态,投资金额,状态")."\n";
            $user_service = new UserService();
            $deal_agency_service = new \core\service\DealAgencyService();
            $agency_info = $deal_agency_service->getDealAgency($deal_info['agency_id']);//担保公司信息
            $advisory_info =  $deal_agency_service->getDealAgency($deal_info['advisory_id']);//担保公司信息
            foreach($list as $val){
                $borrower_info = $user_service->getUser($val['borrow_user_id']);
                $loan_user_info = $user_service->getUser($val['user_id']);

                $borrower_name = $borrower_info['real_name'];
                if($val['borrower_sign_time'] > 0) {
                    $borrower_sign_status = '已签署';
                    $borrower_sign_time = date('Y-m-d h:i:s', $val['borrower_sign_time']);
                }else{
                    $borrower_sign_status = '未签署';
                    $borrower_sign_time = '';
                }

                $loan_name = $loan_user_info['real_name'];
                if($val['user_sign_time'] > 0) {
                    $loan_sign_status = '已签署';
                    $loan_sign_time = date('Y-m-d h:i:s', $val['user_sign_time']);
                }else{
                    $loan_sign_status = '未签署';
                    $loan_sign_time = '';
                }

                $agency_name = $agency_info['name'];
                if($val['agency_sign_time'] > 0){
                    $agency_sign_status = '已签署';
                    $agency_sign_time = date('Y-m-d h:i:s',$val['agency_sign_time']);
                }else{
                    $agency_sign_status = '未签署';
                    $agency_sign_time = '';
                }

                $advisory_name = $advisory_info['name'];
                if($val['advisory_sign_time'] > 0){
                    $advisory_sign_status = '已签署';
                    $advisory_sign_time = date('Y-m-d h:i:s',$val['advisory_sign_time']);
                }else{
                    $advisory_sign_status = '未签署';
                    $advisory_sign_time = '';
                }


                if($val['borrow_user_id'] == 0){
                    $borrower_name = '--';
                    $borrower_sign_status = '--';
                    $borrower_sign_time = '--';
                }

                if($val['user_id'] == 0){
                    $loan_name = '--';
                    $loan_sign_status = '--';
                    $loan_sign_time = '--';
                }

                if($val['agency_id'] == 0){
                    $agency_name = '--';
                    $agency_sign_status = '--';
                    $agency_sign_time = '--';
                }

                if($val['advisory_id'] == 0){
                    $advisory_name = '--';
                    $advisory_sign_status = '--';
                    $advisory_sign_time = '--';
                }

                $status = $val['status'] == 0?'未盖戳':'已盖戳';
                if($val['deal_load_id'] > 0){
                    $deal_load_info = \core\dao\DealLoadModel::instance()->getDealInfoByLoadId($val['deal_load_id']);
                    $money = $deal_load_info['money'];
                }else{
                    $money = 0;
                }

                $row = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                    $val['deal_id'].'_'.$val['id'],$val['title'],$val['number'],$borrower_name,$borrower_sign_status,$borrower_sign_time,$loan_name,$loan_sign_status,$loan_sign_time,$agency_name,$agency_sign_status,$agency_sign_time,$advisory_name,$advisory_sign_status,$advisory_sign_time,$deal_info['name'],date('Y-m-d h:i:s',$val['create_time']),'已发送',$money,$status);
                $content .= iconv("utf-8","gbk",$row) . "\n";
            }
        }else{
            $name = $this->getActionName ();
            $model = D ( $name );
            $voList = $model->where ( array('deal_id' => $deal_id) )->order ( "id desc" )->findAll();
            $list = $this->processContractList($voList, $deal_info, true);
            $content = iconv("utf-8","gbk","合同id,合同标题,合同编号,角色,用户姓名,预签状态,签署状态,签署时间,借款标题,合同创建时间,发送状态,合同状态,投资金额,状态")."\n";
            foreach ($list as $val) {
                $sign_before = $val['agency_id'] > 0 ? '--' : '已预签';
                $val['send_status'] = $val['is_send'] == 0 ? '未发送' : '发送成功';
                $val['status'] = $val['status'] == 0 ? '无效' : '有效';
                $val['money'] = $val['money'] == 0 ? '--' : $val['money'];
                $contract_time = $val['contract_time'] ? date('Y-m-d H:i:s', $val['contract_time']) : '';

                $row = sprintf("%s,%s,\t%s,%s,%s,%s,%s,\t%s,\"%s\",\t%s,%s,%s,%s,%s",
                    $val['id'], $val['title'], $val['number'], $val['usertype']['name'], $val['user_name'], $sign_before, $val['sign_info'],
                    $contract_time, $deal_info['name'].' [id:'.$val['deal_id'].']',  date('Y-m-d H:i:s', $val['create_time']),
                    $val['send_status'], $val['status'],$val['money'],$val['deal_status_cn']);
                $content .= iconv("utf-8","gbk",$row) . "\n";
            }
        }

        $datatime = date("YmdHis",time());
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=contract_{$datatime}.csv");
        header('Cache-Control: max-age=0');
        echo $content;
    }

    /**
     * processContractList 处理合同列表
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @param array $list   合同列表
     * @param array  $deal_info    标id
     * @param bool $is_export  是否导出
     * @access public
     * @return void
     */
    function processContractList(&$list, $deal_info, $is_export = false) {
        $uids = $aids = $deal_ids = array();

        //************获取每个合同投资金额及投资状态开始
        $tmpDealLoadIdArr = array();
        $tmpDealIdArr = array();
        foreach ($list as $row){
            if(isset($row['deal_load_id']) && !empty($row['deal_load_id'])){//0也会被empty排除掉
                $tmpDealLoadIdArr[] = $row['deal_load_id'];
            }
            if(isset($row['deal_id']) && !empty($row['deal_id'])){
                $tmpDealIdArr[] = $row['deal_id'];
            }
        }

        //组装好后只用查询一次数据库即可取出所有数据(投资金额)
        if(empty($tmpDealLoadIdArr)){
            $dealLoadInfo = array();
        }else{
            $dealLoadInfo = $GLOBALS['db']->getAll("select `id`,`money` from " . DB_PREFIX . "deal_load where id in (".implode(",",$tmpDealLoadIdArr).")");
        }
        //查询好以后组装回原数组(投资金额)
        foreach ($dealLoadInfo as $info){
            foreach ($list as $key=>$val){
                if($info['id'] == $list[$key]['deal_load_id']){
                    $list[$key]['money'] = $info['money'];
                }
            }
        }

        //组装好后只用查询一次数据库即可取出所有数据(标状态)
        if(empty($tmpDealIdArr)){
            $dealInfo = array();
        }else{
            $dealInfo = $GLOBALS['db']->getAll("select `id`,`deal_status` from " . DB_PREFIX . "deal where id in (".implode(",",$tmpDealIdArr).")");
        }
        //查询好以后组装回原数组(标状态)
        foreach ($dealInfo as $oneDeal){
            foreach ($list as $key=>$val){
                if($oneDeal['id'] == $list[$key]['deal_id']){
                    $list[$key]['deal_status_code'] = $oneDeal['deal_status'];
                    $list[$key]['deal_status_cn'] = get_deal_status($oneDeal['deal_status']);
                }
            }
        }
        //************获取每个合同投资金额及投资状态结束

        foreach ($list as $val) {
            //得到用户名 或者 担保公司名称 的id
            if($val ['user_id'] > 0){
                $uids[] = $val['user_id'];
            }elseif($val['agency_id'] > 0){
                $aids[] = $val['agency_id'];
            }
            if ($val ['user_id'] > 0 && $val['type'] != 3) {
                if($val['user_id'] == $deal_info['user_id']) {  // 得到借款合同id
                    $borrow_cids[] = $val['id'];
                }
            } elseif($val['user_id'] == 0 && $val['agency_id'] > 0){ // 担保公司id
                $agency_ids[] = $val['agency_id'];
            }
            $deal_ids[] = $val['deal_id'];
        }
        $deal_agency_list = $agency_list = $agency_contract_list = $user_list =  array();
        if (count($agency_ids)) {
            $agency_ids = array_unique($agency_ids);
            // 获得担保公司的用户
            $agency_list = $GLOBALS['db']->getAll("select `user_id`,`user_name`,`agency_id` from " . DB_PREFIX . "agency_user where agency_id in (".implode(",",$agency_ids).")");
            foreach ($agency_list as $row) {
                $uids[] = $row['user_id'];
            }
        }
        $aids = array_unique($aids);
        $uids = array_unique($uids);
        // 担保机构信息

        if (count($aids)) {
            $deal_agency_list = $GLOBALS['db']->getAll("select id,name from " . DB_PREFIX . "deal_agency where id in (".implode(',',$aids).")");
        }

        if (count($uids)) {
            $user_list = $GLOBALS['db']->getAll("select id,real_name from " . DB_PREFIX . "user where id in (".implode(',',$uids).")");
        }

        // 得到当前标下 /或当前页面的标 所有的签署合同
        $agency_contract_list = $GLOBALS['db']->getAll("select `agency_id`, `contract_id`, `user_id`, `pass`, `create_time`, `sign_pass`, `deal_id` from " . DB_PREFIX . "agency_contract where deal_id in (".implode(',', $deal_ids).")");
        if (empty($deal_info)) {
            $deal_list = $GLOBALS['db']->getAll("select `id`,`name`,`user_id`, `contract_tpl_type` from " . DB_PREFIX . "deal where id in (".implode(',', $deal_ids).")");
            $data['deal_info'] = $this->formatArray($deal_list, 'id');
        } else {
            $data['deal_info'] = array($deal_info['id'] => $deal_info);
        }
        // 格式化结果集
        $data['deal_agency'] = $this->formatArray($deal_agency_list, 'id');
        $data['user'] = $this->formatArray($user_list, 'id');
        $data['agency_contract'] = $this->formatArray($agency_contract_list, array('agency_id', 'contract_id', 'user_id', 'deal_id')); //  签署合同
        $data['agency'] = $this->formatArray($agency_list, array('agency_id','user_id'));
        $data['is_export'] = $is_export;
        $this->_makeData($list, $data);
        return $list;
    }

    /**
     * formatArray
     * 按要求格式化 数组
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @param array $list 要格式化的数组
     * @param mixed $keys   按哪些key 来格式化
     * @access public
     * @return void
     */
    function formatArray($list, $keys) {
        $new_list = array();
        foreach ($list as $row) {
            if (is_array($keys)) {  //拼接key
                $k_arr = array();
                foreach ($keys as $k) {
                    $k_arr[] = $row[$k];
                }
                $key = implode('_', $k_arr);
            } else {
                $key = $row[$keys];
            }
            $new_list[$key] = $row;
        }
        return $new_list;
    }

    /**
     * _makeData 真正处理合同列表的函数
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @param array $list  合同数组
     * @param array $data  所需要的其他数组的集合
     * $data['agency_contract'] 担保合同数组
     * $data['deal_agency'] 担保机构的数组
     * $data['deal_info'] 标的信息
     * @access private
     * @return void
     */
    private function _makeData(&$list, $data = array()) {
        // 得到 汇赢担保帐号
        FP::import("libs.common.dict");
        $user_names = dict::get('HY_DB');
        $user_names = implode("','",$user_names);
        $user_names = "'{$user_names}'";
        $hy_agency_user = $GLOBALS ['db']->getAll( "select `id` as user_id,`real_name` from " . DB_PREFIX . "user where user_name in ({$user_names})");

        $user_list = $data['user'];
        if (count($hy_agency_user)) {
            foreach ($hy_agency_user as $hy_user) {
                $user_list[$hy_user['user_id']] = $hy_user;
            }
        }

        if(count($data['deal_info']) === 1){
            foreach($data['deal_info'] as $dk => $dv){
                $deal_id = $dk;
            }
        }
        $deal_id = intval($deal_id);
        $advisory = $GLOBALS ['db']->getRow( "select `advisory_id` from " . DB_PREFIX . "deal where id = '$deal_id'");

        $deal_agency_list = $data['deal_agency'];
        $agency_contract_list = $data['agency_contract'];
        $agency_list = $data['agency'];
        $deals = $data['deal_info'];
        $is_export = $data['is_export'];

        //拼装页面所需数据
        foreach ($list as &$val) {
            $deal_info = $deals[$val['deal_id']];
            $contract_tpl_type = $deal_info['contract_tpl_type'];

            //获取合同的用户id 对应的用户名
            $val['user_name'] = $this->contract_character(4);
            if($val ['user_id']){
                $val['user_name'] = $user_list[$val['user_id']]['real_name'];
            }elseif($val['agency_id']){
                $val['user_name'] = $deal_agency_list[$val['agency_id']]['name'];
            }
            $val['is_have_sign'] = 1;
            $val['contract_time'] = 0;
            //借款人和出借人
            if ($val ['user_id'] > 0 && $val['type'] != 3) {
                $val ['sign_info'] = '--';
                $val ['usertype'] = $this->contract_character(2);

                if ($val ['user_id'] == $deal_info['user_id']) {//借款人合同
                    $is_sign = false;
                    if($val['sign_time']){
                        $is_sign = true;
                        $val ['contract_time'] = $val['sign_time'];
                    }else{
                        $cur_agency_contract =  $agency_contract_list[$val['agency_id'].'_'.$val['id'].'_'.$val['user_id'].'_'.$val['deal_id']];  // 当前签署合同
                        if ($cur_agency_contract['agency_id'] == 0 && $cur_agency_contract['pass'] == 1) {
                            $val ['contract_time'] = $cur_agency_contract['create_time'];
                            $is_sign = true;
                        }
                    }

                    $val ['is_have_sign'] = 0;
                    $val ['sign_info'] = '--';
                    if($val['type'] == 7){
                        $val ['is_have_sign'] = 1;
                    } else{
                        if (!$is_export) {
                            $val ['sign_info'] = '<font color="#FF4040">未签</font> <a href="javascript:void(0);" onclick="agree_contract('.$val['id'].', '.$val['user_id'].')">代签</a>';
                        } else {
                            $val ['sign_info'] = '未签';
                        }
                    }
                    if ($is_sign){
                        $val ['is_have_sign'] = 1;
                        if (!$is_export) {
                            $val ['sign_info'] = '<font color="green">已签</font>';
                        } else {
                            $val ['sign_info'] = '已签';
                        }
                    }
                    $val ['usertype'] = $this->contract_character(1);
                }
            } else { //保证人和担保公司
                $val ['sign_info'] = '--';
                $val ['usertype'] = $this->contract_character(3);
                //担保公司
                if ($val['user_id'] == 0 && $val['agency_id'] > 0) {
                    $agency_user = array();
                    //  如果是汇赢的 找出配置里的
                    if ($contract_tpl_type == 'HY' && ($val['agency_id'] == $GLOBALS['dict']['HY_DBGS'])) {
                        $agency_user = $hy_agency_user;
                    } else {
                        foreach ($agency_list as $agency) {
                            if ($agency['agency_id'] == $val['agency_id']) {
                                $agency_user[] = $agency;
                            }
                        }
                    }
                    $is_have_sign = 1;
                    $agency = $agency_alone_sign = array();
                    foreach ( $agency_user as $uval ) { //遍历担保用户
                        $is_sign = array();
                        if($val['sign_time']){
                            $is_sign['create_time'] = $val['sign_time'];
                        }else{
                            $is_sign = $agency_contract_list[$val['agency_id'].'_'.$val['id'].'_'.$uval['user_id'].'_'.$val['deal_id']];
                        }
                        $is_have_sign = 0;
                        if (!$data['is_export']) {
                            $sign_info = '[<font color="#FF4040">未签</font>] <a href="javascript:void(0);" onclick="agree_contract('.$val['id'].', '.$uval['user_id'].')">代签</a>';
                        } else {
                            $sign_info = '未签';
                        }
                        if ($is_sign){
                            $is_have_sign = 1;
                            if ($data['is_export']) {
                                $sign_info = '已签';
                            } else {
                                $sign_info = '[<font color="green">已签</font>]';
                            }
                        }
                        $agency_user_name = $user_list[$uval['user_id']]['real_name'];
                        $agency [] = array ('user_name' => $agency_user_name,'user_id' => $uval ['user_id'],'sign_info' => $sign_info,'contract_time' => $is_sign['create_time']);
                         //查询二次签署状态和签署时间
                        if ($val['is_needsign'] == 1) {
                            $alone_sign = array();
                            if($val['resign_time']){
                                $alone_sign['sign_pass'] = $val['resign_status'];
                                $alone_sign['sign_time'] = $val['resign_time'];
                            }else{
                                $alone_sign = $agency_contract_list[$val['agency_id'].'_'.$val['id'].'_'.$uval['user_id'].'_'.$val['deal_id']];
                            }

                            if ($alone_sign['sign_pass'] == 0) {
                                $alone_sign_info = '<font color="#FF4040">未签</font>';
                            } elseif($alone_sign['sign_pass'] == 1) {
                                $alone_sign_info = '<font color="green">已签</font>';
                            } elseif($alone_sign['sign_pass'] == 2) {
                                $alone_sign_info = '<font color="blue">拒签</font>';
                            }

                            $agency_alone_sign [] = array ('user_name' => $agency_user_name,'user_id' => $uval ['user_id'],'sign_info' => $alone_sign_info,'contract_time' => $alone_sign['sign_time']);
                        }
                    }
                    $val ['is_have_sign'] = $is_have_sign;
                    $val ['agency'] = $agency;
                    $val ['agency_alone_sign'] = $agency_alone_sign;
                    if($advisory['advisory_id'] === $val['agency_id']){
                        $val ['usertype'] = $this->contract_character(5);
                    }else{
                        $val ['usertype'] = $this->contract_character(4);
                    }
                }
            }
            //查询二次签署状态和签署时间 (借款人、出借人、保证人)
            $val['alone_sign_info'] = '';
            $val['alone_sign_time'] = 0;

            if($val['is_needsign'] == 1){
                if($val['resign_time']){
                    $alone_sign['sign_pass'] = $val['resign_status'];
                    $alone_sign['sign_time'] = $val['resign_time'];
                }else{
                    $alone_sign = $agency_contract_list[$val['agency_id'].'_'.$val['id'].'_'.$val['user_id'].'_'.$val['deal_id']];
                }
                if($alone_sign['sign_pass'] == 0){
                    $val['alone_sign_info'] = '<font color="#FF4040">未签</font>';
                }elseif($alone_sign['sign_pass'] == 1){
                    $val ['alone_sign_info'] = '<font color="green">已签</font>';
                }elseif($alone_sign['sign_pass'] == 2){
                    $val ['alone_sign_info'] = '<font color="blue">拒签</font>';
                }
                $val['alone_sign_time'] = $alone_sign['sign_time'];
            }
        }
    }
}
?>

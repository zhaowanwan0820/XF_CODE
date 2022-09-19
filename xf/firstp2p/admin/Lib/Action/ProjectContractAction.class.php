<?php
// +----------------------------------------------------------------------
// | 项目合同管理
// +----------------------------------------------------------------------
// | Author: wangjiantong@ucfgroup.com
// +----------------------------------------------------------------------
use core\service\ContractNewService;
use core\service\UserService;
use core\dao\DealProjectModel;

use NCFGroup\Protos\Contract\RequestGetContractByProjectId;

class ProjectContractAction extends CommonAction {
    // 首页
    public function index() {
        $where_array = array();
        if(intval($_REQUEST ['project_id']) > 0){
            $project_id = intval($_REQUEST ['project_id']);
            $where_array[] = "project_id = '".trim($project_id)."'";
            $project = $GLOBALS ['db']->get_slave()->getRow ( "SELECT * FROM " . DB_PREFIX . "deal_project WHERE id = " . $project_id );

        }else{
            $this->error("未指定项目");
        }

        $user_service = new UserService();
        if(trim($_REQUEST['cname'])){
            $where_array[] = "title = '".trim($_REQUEST['cname'])."'";
        }

        if(trim($_REQUEST['cnum'])){
            $where_array[] = "number = '".trim($_REQUEST['cnum'])."'";
        }

        if(trim($_REQUEST['cuser_name']) ){
            $user_id_all = $GLOBALS['db']->get_slave()->getAll("SELECT id FROM ".DB_PREFIX."user where real_name like '%".trim($_REQUEST['cuser_name'])."%'");
            if($user_id_all){
                foreach($user_id_all as $user_id){
                    $ids[] = $user_id['id'];
                }
                $where_array[] = "(user_id in (".implode(',', $ids).") OR borrow_user_id in (".implode(',', $ids)."))";
            }
        }

        if(trim($_REQUEST['cuser_id']) != ''){
            $where_array[] = "(user_id = ".intval(trim($_REQUEST['cuser_id'])).' OR borrow_user_id = '.intval(trim($_REQUEST['cuser_id'])).')';
        }

        $where = $where_array ? implode(' and ', $where_array) : '';
        $p = empty($_REQUEST['p'])?1:$_REQUEST['p'];
        $contractRequest = new RequestGetContractByProjectId();
        $contractRequest->setProjectId(intval($project_id));
        $contractRequest->setPageNo(intval($p));
        $contractRequest->setWhere(trim($where));
        $contractRequest->setSourceType($project['deal_type']);
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Contract",
            'method' => "getContractByProjectId",
            'args' => $contractRequest,
        ));

        $listRows = $response->list;


        foreach($listRows as &$listRow){
            $listRow['user_name'] = get_user_realname($listRow['user_id']);
            $listRow['borrow_user_name'] = get_user_realname($listRow['borrow_user_id']);
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

            $canal_user = $GLOBALS ['db']->getAll ( "select * from " . DB_PREFIX . "agency_user where agency_id = " . $listRow ['canal_agency_id'] );
            foreach($canal_user as $k=> &$v){
                $user_info = $user_service->getUser($v['user_id']);
                $entrust_user[$k]['real_name'] = $user_info['real_name'];

            }
            $listRow['canal_user'] = $canal_user;

            $listRow['canal_name'] = $GLOBALS ['db']->getOne ( "select name from " . DB_PREFIX . "deal_agency where id = " . $listRow ['canal_agency_id'] );
        }

        $sign_num_limit = $GLOBALS['dict']['CONT_SIGN_NUM'];
        $p = new Page ( $response->count['num'], 10 );
        $this->assign ( 'page', $p->show() );
        $this->assign ( 'list', $listRows );
        $this->assign ( 'sign_num_limit', $sign_num_limit ? $sign_num_limit : 20);
        $this->assign ( 'project_id', intval($_REQUEST ['project_id']));
        $this->display('projectContract');
    }


    public function agreeAll()
    {
        $ajax = intval($_REQUEST['ajax']);

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : false ;
        if (!$id) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }

        $type = intval($_REQUEST['type']);
        $projectInfo = DealProjectModel::instance()->find($id);

        if (empty($projectInfo)) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        $adm = es_session::get(md5(conf("AUTH_KEY")));
        if (!$adm) {
            $this->error (L("INVALID_OPERATION"), $ajax);
        }
        
        $contractService = new ContractNewService();
        $res = $contractService->signProjectCont($id,$type,0,$adm['adm_id']);
        if (!$res) {
            $this->error (L("UPDATE_FAILED"), $ajax);
        }else{
            $this->success(L("UPDATE_SUCCESS"), $ajax);
        }
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
        $projectId = $_REQUEST['project_id'];
        $dealProjectModel = new DealProjectModel();
        $deal = $dealProjectModel->getFirstDealByProjectId($projectId);

        if(is_numeric($deal['contract_tpl_type'])){
            $contractRequest = new RequestGetContractByProjectId();
            $contractRequest->setProjectId(intval($projectId));
            $contractRequest->setSourceType($deal['deal_type']);
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Contract",
                'method' => "getContractByProjectId",
                'args' => $contractRequest,
            ));

            $list = $response->list;
            $content = iconv("utf-8","gbk","合同id,合同标题,合同编号,借款人姓名,借款人签署状态,借款人签署时间,投资人姓名,投资人签署状态,投资人签署时间,担保公司名称,担保公司签署状态,担保公司签署时间,资产管理方名称,资产管理方签署状态,资产管理方签署时间,借款标题,合同创建时间,发送状态,状态")."\n";
            $user_service = new UserService();
            $deal_agency_service = new \core\service\DealAgencyService();
            $agency_info = $deal_agency_service->getDealAgency($deal['agency_id']);//担保公司信息
            $advisory_info =  $deal_agency_service->getDealAgency($deal['advisory_id']);//担保公司信息
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

                $row = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                    $val['deal_id'].'_'.$val['id'],$val['title'],$val['number'],$borrower_name,$borrower_sign_status,$borrower_sign_time,$loan_name,$loan_sign_status,$loan_sign_time,$agency_name,$agency_sign_status,$agency_sign_time,$advisory_name,$advisory_sign_status,$advisory_sign_time,$deal['name'],date('Y-m-d h:i:s',$val['create_time']),'已发送',$status);

                $content .= iconv("utf-8","gbk",$row) . "\n";
            }
        }
        $datatime = date("YmdHis",time());
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=contract_{$datatime}.csv");
        header('Cache-Control: max-age=0');
        echo $content;
    }
}
?>

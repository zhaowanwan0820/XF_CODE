<?php

FP::import("libs.libs.msgcenter");
FP::import("app.deal");

use core\dao\DealAgencyModel;
use NCFGroup\Protos\Gold\RequestCommon;
use core\service\UserService;

class GoldDealLoanAction extends CommonAction
{

    /**
     * 业务审核状态
     *
     * @var array
     */
    public static $auditStatus = array(
        '0'    => '放款待处理',
        '1'    => '放款待审核',
        '3'    => '放款已退回',
    );

    /**
     * 待放款列表
     */
    public function index()
    {
        $map = $this->_getDealMap($_REQUEST);
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $map['pageNum'] = $pageNum;
        $map['pageSize'] = $pageSize;
        $role = $this->getRole();
        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => ServiceAuditModel::SERVICE_TYPE_GOLD_PROJECT_LOAN, 'status' =>
            array('in', implode(',', array(ServiceAuditModel::NOT_AUDIT, ServiceAuditModel::AUDIT_FAIL))));
        if ($role == 'b') {// B角审核筛选申请人
            $conds['status'] = ServiceAuditModel::NOT_AUDIT;
            if ($_REQUEST['admin_name'] != '') {
                $adminId = M('Admin')->where('adm_name="'.addslashes($_REQUEST['admin_name']).'"')->getField('id');
                $conds['submit_uid'] = $adminId;
            }
        } else {
            if ($_REQUEST['audit_status'] > 0 && $_REQUEST['audit_status'] != '9999') {
                $conds['status'] = intval($_REQUEST['audit_status']);
            }
        }
        $audit_deal_list = D('ServiceAudit')->where($conds)->field('service_id,status,submit_uid')->select();
        $service_ids = array();
        foreach ($audit_deal_list as $item) {
            $admin_name = '';
            if ($item['submit_uid'] > 0) {
                $admin_name = get_admin_name($item['submit_uid']);
            }
            $service_ids[$item['service_id']] = array('submit_user_name' => $admin_name, 'status' => self::$auditStatus[$item['status']]);
        }
        if (empty($service_ids)) {
            $service_ids[] = 0;
        }
        if ($role == 'b' && !isset($_GET['audit_status'])) { //审核角色展现待审核列表
            $map['id'] = array('in', implode(',', array_keys($service_ids)));
        } else {
            if (isset($_REQUEST['audit_status'])) {
                    if ($_REQUEST['audit_status'] == 0) {
                        $map['id'] = array('not in', implode(',', array_keys($service_ids)));
                    }
                    if ($_REQUEST['audit_status'] == 1 || $_REQUEST['audit_status'] == 3) {
                        $map['id'] = array('in', implode(',', array_keys($service_ids)));
                    }
            }
        }
        $this->assign('audit_deal_list', $service_ids);
        if (!isset($_REQUEST['audit_status'])) {
            $_REQUEST['audit_status'] = '9999';
        }
        $this->assign('audit_status_list', self::$auditStatus);
        $request = new RequestCommon();
        $request->setVars($map);

        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Deal',
            'method' => 'listDealLoan',
            'args' => $request,
        ));


        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
       // $this->assign ( "nowPage", $p->nowPage );

        if (!empty($response['data']['data'])){
            $userIDArr = array();

            foreach($response['data']['data']  as $k=>$v){
                $userIDArr[] = $v['userId'];
            }
            $userServ = new UserService();
            $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
            $this->assign('listOfBorrower', $listOfBorrower);
            $list = $response['data']['data'];
            $this->assign('list', $list);
        }

        //最后访问的列表地址存起来，以便放款操作完返回
        $_SESSION['lastDealLoanUrl'] = $_SERVER['REQUEST_URI'];

        $this->assign('role', $this->getRole());

        $this->display('loans');
        return;
    }


    private function _getDealMap($request){

        $map['dealStatus'] = 2;

        if(intval($request['deal_id'])>0){
            $map['id'] = intval($request['deal_id']);
        }
        if(trim($request['name'])!=''){
            $map['name'] = addslashes(trim($request['name']));
           // $map['name'] = array('like','%'.$name.'%');
        }
        if(trim($request['real_name'])!=''){
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%".trim($request['real_name'])."%'";
            $ids1 = $GLOBALS['db']->getOne($sql);
        }

        if(trim($request['user_name']) != '') {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name = '".trim($request['user_name']) . "'";
            $ids2 = $GLOBALS['db']->getOne($sql);
        }

        if($ids1 && $ids2) {
            $map['userId'] = $ids1 . "," . $ids2;
        } else if($ids1) {
            $map['userId'] = $ids1;
        } else if($ids2) {
            $map['userId'] = $ids2;
        }

        if(intval($request['agency_id'])>0){
            $map['agency_id'] = intval($request['agency_id']);
        }

        if ($request['success_time_start']>0 && $request['success_time_end']>0) {
            $map['success_time'] = array("between", to_timespan($request['success_time_start']) . "," . to_timespan($request['success_time_end']));
        } elseif ($request['success_time_start']>0){
            $map['success_time'] = array("gt", to_timespan($request['success_time_start']));
        } elseif ($request['success_time_end']>0){
            $map['success_time'] = array("lt", to_timespan($request['success_time_end']));
        }

        // 万恶的合同签署状态现在开始
        //$GLOBALS['db']->query("SET GLOBAL group_concat_max_len = 4294967295;");
        if (intval($request['sign_borrow']) == 1) {
            $deal_ids_1 = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_contract as dc on d.id = dc.deal_id where d.deal_status=2 and dc.agency_id=0 and dc.status=1");
        } elseif (intval($request['sign_borrow']) == 2) {
            $deal_ids_1 = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_contract as dc on d.id = dc.deal_id where d.deal_status=2 and dc.agency_id=0 and dc.status!=1");
        }

        if (intval($request['sign_agency']) == 1) {
            $deal_ids_2 = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_contract as dc on d.id = dc.deal_id where d.agency_id = dc.agency_id and d.deal_status=2 and dc.agency_id!=0 and dc.status=1");
        } elseif (intval($request['sign_agency']) == 2) {
            $deal_ids_2 = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_contract as dc on d.id = dc.deal_id where d.agency_id = dc.agency_id and d.deal_status=2 and dc.agency_id!=0 and dc.status!=1");
        }

        if (intval($request['sign_advisory']) == 1) {
            $deal_ids_3 = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_contract as dc on d.id = dc.deal_id where d.advisory_id = dc.agency_id and d.deal_status=2 and dc.agency_id!=0 and dc.status=1");
        } elseif (intval($request['sign_advisory']) == 2) {
            $deal_ids_3 = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_contract as dc on d.id = dc.deal_id where d.advisory_id = dc.agency_id and d.deal_status=2 and dc.agency_id!=0 and dc.status!=1");
        }

        $union_arr = array();
        if ($deal_ids_1) {
            $arr1 = array();
            foreach ($deal_ids_1 as $v) {
                $arr1[] = $v['id'];
                $union_arr[] = $v['id'];
            }
        }
        if ($deal_ids_2) {
            $arr2 = array();
            foreach ($deal_ids_2 as $v) {
                $arr2[] = $v['id'];
                $union_arr[] = $v['id'];
            }
        }
        if ($deal_ids_3) {
            $arr3 = array();
            foreach ($deal_ids_3 as $v) {
                $arr3[] = $v['id'];
                $union_arr[] = $v['id'];
            }
        }

        $union_arr = array_unique($union_arr);
        $deal_ids = array_intersect($arr1?$arr1:$union_arr, $arr2?$arr2:$union_arr,$arr3?$arr3:$union_arr);

        if($deal_ids_1 || $deal_ids_2 || $deal_ids_3) { //精确过滤
            if ($deal_ids) {
                $map['id'] = array('in', implode(',', $deal_ids));
            } else {
                $map['id'] = '-1';//查询不到
            }
        }

        // 增加贷款类型
      //  $deal_type = !empty($_REQUEST['deal_type']) ? (string)$_REQUEST['deal_type'] : (DealModel::DEAL_TYPE_GENERAL . ',' . DealModel::DEAL_TYPE_COMPOUND);
       // $map['deal_type'] = array('in', $deal_type);
        return $map;
    }
    /**
     * 导出 index csv
     */
    public function export_csv()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $memory_start = memory_get_usage();
        $map = $this->_getDealMap($_REQUEST);
        $role = $this->getRole();
        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => ServiceAuditModel::SERVICE_TYPE_GOLD_PROJECT_LOAN, 'status' =>
            array('in', implode(',', array(ServiceAuditModel::NOT_AUDIT, ServiceAuditModel::AUDIT_FAIL))));
        if ($role == 'b') {// B角审核筛选申请人
            $conds['status'] = ServiceAuditModel::NOT_AUDIT;
            if ($_REQUEST['admin_name'] != '') {
                $adminId = M('Admin')->where('adm_name="'.addslashes($_REQUEST['admin_name']).'"')->getField('id');
                $conds['submit_uid'] = $adminId;
            }
        } else {
            if ($_REQUEST['audit_status'] > 0 && $_REQUEST['audit_status'] != '9999') {
                $conds['status'] = intval($_REQUEST['audit_status']);
            }
        }
        $audit_deal_list = D('ServiceAudit')->where($conds)->field('service_id,status,submit_uid')->select();
        $service_ids = array();
        foreach ($audit_deal_list as $item) {
            $admin_name = '';
            if ($item['submit_uid'] > 0) {
                $admin_name = get_admin_name($item['submit_uid']);
            }
            $service_ids[$item['service_id']] = array('submit_user_name' => $admin_name, 'status' => self::$auditStatus[$item['status']]);
        }
        if (empty($service_ids)) {
            $service_ids[] = 0;
        }
        if ($role == 'b' && !isset($_GET['audit_status'])) { //审核角色展现待审核列表
            $map['id'] = array('in', implode(',', array_keys($service_ids)));
        } else {
            if (isset($_REQUEST['audit_status'])) {
                if ($_REQUEST['audit_status'] == 0) {
                    $map['id'] = array('not in', implode(',', array_keys($service_ids)));
                }
                if ($_REQUEST['audit_status'] == 1 || $_REQUEST['audit_status'] == 3) {
                    $map['id'] = array('in', implode(',', array_keys($service_ids)));
                }
            }
        }
        if (!isset($_REQUEST['audit_status'])) {
            $_REQUEST['audit_status'] = '9999';
        }
        $request = new RequestCommon();
        $request->setVars($map);
        $i = 0;
        $total = 0;
        $pageSize = 1000;
        $hasTotalCount = 1;
        $pageNo = 1;
        $content = iconv("utf-8","gbk","标的ID,标的名称,单次上线克重,延期提货补偿率,期限,黄金及支付补偿方式,用户类型,运营方ID/姓名/手机号,标的售卖状态,状态,满标时间");
        $content = $content . "\n";
        do {
            try {
                $request->setVars(array("pageNum"=>$pageNo,"pageSize"=>$pageSize));
                $response = $this->getRpc('goldRpc')->callByObject(array(
                    'service' => '\NCFGroup\Gold\Services\Deal',
                    'method' => 'listDealLoan',
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
                    'borrowAmount'=>'""',
                    'rate'=>'""',
                    'repayTime'=>'""',
                    'loantype'=>'""',
                    'userId' => '""',
                    'userInfo' => '""',
                    'dealStatus'=>'""',
                    'isEffect' => '""',
                    'successTime' => '""',
                );
                $userServ = new UserService();
                foreach($deal_list  as $k=>$v){
                    $userIDArr[] = $v['userId'];
                }
                $listOfBorrower = $userServ->getUserInfoListByID($userIDArr);
                foreach($deal_list as $k=>$v)
                {
                    $v['repayTime'] = ($v['loantype'] == 5) ? $v['repayTime'].'天' :$v['repayTime']. '个月';
                    $v['isEffect'] = ($v['isEffect'] == 1) ? '有效' :'无效';
                    $v['loantype'] = ($v['loantype'] == 5) ? '已购黄金及补偿克重到期一次性交付' :'已购黄金到期交付，补偿克重按季度交付';
                    $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
                    $order_value['name'] = '"' . iconv('utf-8','gbk',$v['name']) . '"';
                    $order_value['borrowAmount'] = '"' . iconv('utf-8','gbk',format_price($v['borrowAmount'], false)) . '"';
                    $order_value['rate'] = '"' . iconv('utf-8','gbk',$v['rate']) .'%'. '"';
                    $order_value['repayTime'] = '"' . iconv('utf-8','gbk',$v['repayTime']) . '"';
                    $order_value['loantype'] = '"' . iconv('utf-8','gbk',$v['loantype']) . '"';
                    $order_value['userId'] = '"' . iconv('utf-8','gbk',getUserTypeName($v['userId'])) . '"';
                    $order_value['dealStatus'] = '"' . iconv('utf-8','gbk','满标') . '"';
                    $order_value['isEffect'] = '"' . iconv('utf-8','gbk',$v['isEffect']) . '"';
                    $order_value['userInfo'] = '"' . iconv('utf-8','gbk',$v['userId']).'/'.  iconv('utf-8','gbk',$listOfBorrower[$v['userId']]['real_name']). '/'.iconv('utf-8','gbk',$listOfBorrower[$v['userId']]['mobile']).'"';
                    $order_value['successTime'] = '"' . iconv('utf-8','gbk',to_date($v['successTime'])) . '"';
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
}
?>

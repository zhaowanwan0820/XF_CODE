<?php

FP::import("libs.libs.msgcenter");
FP::import("app.deal");

use core\dao\DealAgencyModel;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\UserCarryModel;

use core\service\DealProjectService;
use core\dao\DealProjectModel;

class DealLoanAction extends CommonAction
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
        if(!isset($_REQUEST['loan_type']) ||  $_REQUEST['loan_type']=='') {
            $_REQUEST['loan_type'] = -1;
        }
        $map = $this->_getDealMap($_REQUEST);

        $role = $this->getRole();
        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => ServiceAuditModel::SERVICE_TYPE_LOAN, 'status' =>
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
        $service_id_str = implode(',', array_keys($service_ids));
        if ($role == 'b' && !isset($_GET['audit_status'])) { //审核角色展现待审核列表
            $map['id'][]  = array('exp'," IN ({$service_id_str}) ");
        } else {
            if (isset($_REQUEST['audit_status'])) {
                    if ($_REQUEST['audit_status'] == 0) {
                        $map['id'][]  = array('exp'," not in ({$service_id_str}) ");
                    }
                    if ($_REQUEST['audit_status'] == 1 || $_REQUEST['audit_status'] == 3) {
                        $map['id'][]  = array('exp'," in ({$service_id_str}) ");
                    }
            }
        }
        $this->assign('audit_deal_list', $service_ids);
        if (!isset($_REQUEST['audit_status'])) {
            $_REQUEST['audit_status'] = '9999';
        }
        $this->assign('audit_status_list', self::$auditStatus);

        // 增加贷款类型
        $deal_type = $this->getDealType(DealModel::DEAL_TYPE_EXCHANGE);
        $map['deal_type'] = array('in', $deal_type);
        $this->assign('deal_type', $deal_type);
        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` LIKE '%" . trim($_REQUEST['project_name']) . "%')";
        }

        $model = D ('Deal');

        //机构管理后台
        $orgMap = $this->orgCondition();
        $map = array_merge($map, $orgMap);

        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
            $deal_pro_service = new DealProjectService();

            foreach ($list as $key => $deal) {
                $deal['is_entrust_zx'] = $deal_pro_service->isProjectEntrustZX($deal['project_id']);
                $project_info = DealProjectModel::instance()->findViaSlave($deal['project_id'],'clearing_type');
                // 结算方式
                switch($project_info['clearing_type']){
                    case 1:
                        $deal['clearing_type_name'] = '场内';
                        break;
                    case 2:
                        $deal['clearing_type_name'] = '场外';
                        break;
                    default:
                        $deal['clearing_type_name'] = '--';
                        break;
                }
                $deal['loan_type'] = DealExtModel::instance()->getDealExtLoanType($deal['id']);
                $list[$key] = $deal;
            }
            $this->assign('list', $list);
        }

        //最后访问的列表地址存起来，以便放款操作完返回
        $_SESSION['lastDealLoanUrl'] = $_SERVER['REQUEST_URI'];

        $da_model = new DealAgencyModel();
        $deal_agency_list = $da_model->getDealAgencyList(DealAgencyModel::TYPE_GUARANTEE);

        $loan_types = UserCarryModel::$loantypeDesc;
        $this->assign('loan_types', $loan_types);
        $this->assign('deal_agency_list', $deal_agency_list);
        $this->assign('role', $this->getRole());
        if (!$this->is_cn) {
            $this->display('loans');
        } else {
            $this->display('loans_cn');
        }
        return;
    }

    /**
     * 导出订单 csv
     */
    public function export_csv()
    {
        if($_REQUEST['id'] <> ''){
            $ids = explode(',',$_REQUEST['id']);
        }
        if(!isset($_REQUEST['loan_type']) ||  $_REQUEST['loan_type']=='') {
            $_REQUEST['loan_type'] = -1;
        }
        $map = $this->_getDealMap($_REQUEST);

        if (isset($_REQUEST['project_name']) && '' != trim($_REQUEST['project_name'])) {
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` WHERE `name` LIKE '%" . trim($_REQUEST['project_name']) . "%')";
        }

        $model = D ('Deal');
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
        }

        $da_model = new DealAgencyModel();
        $deal_agency_list = $da_model->getDealAgencyList(DealAgencyModel::TYPE_GUARANTEE);
        if ($_REQUEST['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE){
            $content = iconv("utf-8","gbk","编号,借款标题,项目名称,借款金额,借款期限,还款方式,标的状态,满标时间,费用收取方式,放款方式,结算方式,借款人用户名,借款人姓名,担保/代偿I机构名称,借款人是否签署合同,担保/代偿I机构是否签署合同,资产管理方是否签署");
        }else{
            $content = iconv("utf-8","gbk","编号,借款标题,项目名称,借款金额,借款期限,还款方式,标的状态,满标时间,费用收取方式,放款方式,借款人用户名,借款人姓名,担保/代偿I机构名称,借款人是否签署合同,担保/代偿I机构是否签署合同,资产管理方是否签署");
        }

        $content = $content . "\n";

        if ($_REQUEST['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE) {
            $order_value = array(
                'id' => '""',
                'deal_name' => '""',
                'project_name' => '""',
                'borrow_amount' => '""',
                'repay_time' => '""',
                'loan_type' => '""',
                'deal_status' => '""',
                'success_time' => '""',
                'fee_rate_type' => '""',
                'loan_money_type' => '""',
                'clearing_type_name' => '""',
                'borrow_user_name' => '""',
                'borrow_real_name' => '""',
                'angency_name' => '""',
                'sign_borrower' => '""',
                'sign_agency' => '""',
                'sign_advisory' => '""',
            );

        }else{
            $order_value = array(
                'id' => '""',
                'deal_name' => '""',
                'project_name' => '""',
                'borrow_amount' => '""',
                'repay_time' => '""',
                'loan_type' => '""',
                'deal_status' => '""',
                'success_time' => '""',
                'fee_rate_type' => '""',
                'loan_money_type' => '""',
                'borrow_user_name' => '""',
                'borrow_real_name' => '""',
                'angency_name' => '""',
                'sign_borrower' => '""',
                'sign_agency' => '""',
                'sign_advisory' => '""',
            );
        }
        foreach($list as $k=>$v)
        {
            $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
            $order_value['deal_name'] = '"' . iconv('utf-8','gbk',$v['name']) . '"';
            $project_name = M("Deal_project")->field("name,clearing_type")->where("id = '" .$v['project_id']. "'")->find();
            $order_value['project_name'] = '"' . iconv('utf-8','gbk',$project_name['name']) . '"';
            $order_value['borrow_amount'] = '"' . iconv('utf-8','gbk',$v['borrow_amount']) . '"';
            if ($v['loantype'] == 5) {
                $deal_repay_time = $v['repay_time'] . "天";
            } else {
                $deal_repay_time = $v['repay_time'] . "个月";
            }
            $order_value['repay_time'] = '"' . iconv('utf-8','gbk',$deal_repay_time) . '"';
            $deal_loantype = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'][$v['loantype']] : $GLOBALS['dict']['LOAN_TYPE'][$v['loantype']];
            $order_value['loan_type'] = '"' . iconv('utf-8','gbk',$deal_loantype) . '"';
            $order_value['deal_status'] = '"' . iconv('utf-8','gbk','满标') . '"';
            $order_value['success_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['success_time']+8*3600)) . '"';
            $order_value['fee_rate_type'] = '"' . iconv('utf-8','gbk', get_deal_ext_fee_type($v['id'])) . '"';

            $loanMoneyType = $GLOBALS['db']->getOne("SELECT `loan_money_type` FROM firstp2p_deal_project WHERE `id`='" . $v['project_id'] . "'");
            if($loanMoneyType == 0 || $loanMoneyType == 1) {
                $order_value['loan_money_type']  = "实际放款";
            } else if($loanMoneyType == 2) {
                $order_value['loan_money_type'] = "非实际放款";
            } else if($loanMoneyType == 3) {
                $order_value['loan_money_type'] = "受托支付";
            }
            $order_value['loan_money_type'] = '"' . iconv('utf-8','gbk',$order_value['loan_money_type']) . '"';
             if ($_REQUEST['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE) {
                 switch ($project_name['clearing_type']) {
                     case 1:
                         $order_value['clearing_type_name'] = '场内';
                         break;
                     case 2:
                         $order_value['clearing_type_name'] = '场外';
                         break;
                     default:
                         $order_value['clearing_type_name'] = '--';
                         break;
                 }
                 $order_value['clearing_type_name'] = '"' . iconv('utf-8', 'gbk', $order_value['clearing_type_name']) . '"';
             }
            $borrow_user_info = M("User")->where("id = '". $v['user_id'] ."'")->find();
            $order_value['borrow_real_name'] = '"' . iconv('utf-8','gbk',$borrow_user_info['real_name']) . '"';
            $order_value['borrow_user_name'] = '"' . iconv('utf-8','gbk',$borrow_user_info['user_name']) . '"';
            $order_value['angency_name'] = '"' . iconv('utf-8','gbk',$deal_agency_list[$v['agency_id']]['short_name']) . '"';
            $order_value['sign_borrower'] = '"' . iconv('utf-8','gbk',get_deal_contract_status($v['id'],0)) . '"';
            $order_value['sign_agency'] = '"' . iconv('utf-8','gbk',get_deal_contract_sign_status($v['id'],$v['agency_id'])) . '"';
            $order_value['sign_advisory'] = '"' . iconv('utf-8','gbk',get_deal_contract_sign_status($v['id'],$v['advisory_id'])) . '"';
            $order_value['sign_entrust'] = '"' . iconv('utf-8','gbk',get_deal_contract_sign_status($v['id'],$v['entrust_agency_id'])) . '"';

            if(is_array($ids) && count($ids) > 0){
                if(array_search($v['id'],$ids) !== false){
                    $content .= implode(",", $order_value) . "\n";
                }
            }else{
                $content .= implode(",", $order_value) . "\n";
            }
        }

        $datatime = date("YmdHis",get_gmtime());
        header("Content-Disposition: attachment; filename={$datatime}_deal_loan_list.csv");
        echo $content;
        return;
    }

    private function _getDealMap($request){

        $map['deal_status'] = 2;

        if(intval($request['deal_id'])>0){
            $deal_id = intval($request['deal_id']);
            $map['id'][]  = array('exp'," IN ({$deal_id}) ");
        }
        if(trim($request['name'])!=''){
            $name = addslashes(trim($request['name']));
            $map['name'] = array('like','%'.$name.'%');
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
            $map['user_id'] = array("in", $ids1 . "," . $ids2);
        } else if($ids1) {
            $map['user_id'] = array("in", $ids1);
        } else if($ids2) {
            $map['user_id'] = array("in", $ids2);
        }

        if(intval($request['agency_id'])>0){
            $map['agency_id'] = intval($request['agency_id']);
        }

        if ($this->is_cn) {
            $request['loan_money_type'] = empty($request['loan_money_type']) ?  1 : intval($request['loan_money_type']);
            $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` where `loan_money_type` = " . intval($request['loan_money_type']) . ")";
        } else {
             if(intval($request['loan_money_type']) > 0) {
                 $map['_string'] = "`project_id` IN (SELECT `id` FROM `" . DB_PREFIX . "deal_project` where `loan_money_type` = " . intval($request['loan_money_type']) . ")";
             }
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

        if ($request['loan_type'] != -1) {
            $loan_type_deal_ids = $GLOBALS['db']->getAll("select d.id from firstp2p_deal as d left join firstp2p_deal_ext as de on d.id = de.deal_id where d.deal_status=2 AND de.loan_type=".intval($request['loan_type']));
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

        if ($loan_type_deal_ids) {
            $loan_type_deal_id_arr = array();
            foreach ($loan_type_deal_ids as $v) {
                $loan_type_deal_id_arr[] = $v['id'];
                $union_arr[] = $v['id'];
            }
        }


        $union_arr = array_unique($union_arr);
        $deal_ids = array_intersect($arr1?$arr1:$union_arr, $arr2?$arr2:$union_arr,$arr3?$arr3:$union_arr);
        if ($request['loan_type'] != -1) {
            $deal_ids = array_intersect($deal_ids,$loan_type_deal_id_arr);
        }

        if($deal_ids_1 || $deal_ids_2 || $deal_ids_3||($request['loan_type'] != -1)) { //精确过滤
            if ($deal_ids) {
                $in_str = implode(',', $deal_ids);
                $map['id'][]  = array('exp'," IN ({$in_str}) ");
            } else {
                $map['id'][]  = array('exp'," IN (-1) ");//查询不到
            }
        }

        // 增加贷款类型
        $deal_type = !empty($_REQUEST['deal_type']) ? (string)$_REQUEST['deal_type'] : (DealModel::DEAL_TYPE_GENERAL . ',' . DealModel::DEAL_TYPE_COMPOUND);
        $map['deal_type'] = array('in', $deal_type);
        return $map;
    }

    /**
     * 标的放款批量查询
     */
    public function batchQuery() {
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $_where = '';

            $dealIds = isset($_REQUEST['deal_ids']) ? trim($_REQUEST['deal_ids']) : '';
            if (!empty($dealIds)) {
                $dealIdsArr = explode("\n", $dealIds);
                $dealIdsArr = array_filter(array_map('intval', $dealIdsArr));
                if (count($dealIdsArr) > 1000) {
                    $this->error("最多输入1000个标的编号");
                }
                $_where .= ' AND id IN (' . implode(',', $dealIdsArr) . ') ';
            }

            $approveNumbers = isset($_REQUEST['approve_numbers']) ? trim($_REQUEST['approve_numbers']) : '';
            if (!empty($approveNumbers)) {
                $approveNumbersArr = explode("\n", $approveNumbers);
                $approveNumbersArr = array_filter(array_map('trim', $approveNumbersArr));
                if (count($approveNumbersArr) > 1000) {
                    $this->error("最多输入1000个放款审批单号");
                }
                $_where .= " AND approve_number IN ('" . implode("','", $approveNumbersArr) . "') ";
            }

            $list = [];
            if (!empty($_where)) {
                $db = \libs\db\Db::getInstance('firstp2p', 'slave');

                //查询标的
                $sql = 'SELECT * FROM firstp2p_deal WHERE 1 = 1 ' . $_where;
                $dealList = $db->getAll($sql);
                $searchIds = [];
                foreach ($dealList as $deal) {
                    $searchIds[] = $deal['id'];
                }

                //查询网贷提现状态
                if ($searchIds) {
                    $sql = 'SELECT * FROM firstp2p_supervision_withdraw WHERE bid in (' . implode(',', $searchIds) . ') ORDER BY id ASC';
                    $withdrawList = $db->getAll($sql);
                    $withdrawListMap = [];
                    foreach ($withdrawList as $withdraw) {
                        $withdrawListMap[$withdraw['bid']] = $withdraw;
                    }

                    //查询其他提现状态
                    $sql = 'SELECT * FROM firstp2p_user_carry WHERE deal_id in (' . implode(',', $searchIds) . ') ORDER BY id ASC';
                    $carryList = $db->getAll($sql);
                    $carryListMap = [];
                    foreach ($carryList as $carry) {
                        $carryListMap[$carry['deal_id']] = $carry;
                    }
                }

                //拼接提现数据
                foreach($dealList as $deal) {
                    $temp = $deal;
                    $temp['withdraw_out_order_id'] = '';
                    $temp['withdraw_amount'] = 0;
                    $temp['withdraw_status'] = '';
                    $temp['withdraw_create_time'] = '';
                    $temp['withdraw_update_time'] = '';
                    $temp['withdraw_remark'] = '';

                    if (isset($withdrawListMap[$deal['id']])) {
                        $withdraw = $withdrawListMap[$deal['id']];
                        $temp['withdraw_out_order_id'] = $withdraw['out_order_id'];
                        $temp['withdraw_amount'] = bcdiv($withdraw['amount'], 100, 2);
                        $temp['withdraw_status'] = $withdraw['withdraw_status'];
                        $temp['withdraw_create_time'] = $withdraw['create_time'];
                        $temp['withdraw_update_time'] = $withdraw['update_time'];
                        $temp['withdraw_remark'] = $withdraw['remark'];
                    }
                    if (isset($carryListMap[$deal['id']])) {
                        $carry = $carryListMap[$deal['id']];
                        $temp['withdraw_out_order_id'] = $carry['id'];
                        $temp['withdraw_amount'] = $carry['money'];
                        $temp['withdraw_status'] = $carry['withdraw_status'];
                        $temp['withdraw_create_time'] = $carry['create_time'];
                        $temp['withdraw_update_time'] = $carry['update_time'];
                        $temp['withdraw_remark'] = $carry['withdraw_msg'];
                    }

                    $list[] = $temp;
                }
            }

            //导出
            if (!empty($_REQUEST['export'])) {
                $title = '借款编号,贷款类型,放款审批单号,借款标题,标的状态,标的创建时间,提现外部订单号,提现金额,提现状态,提现创建时间,提现更新时间,提现备注';
                $content = iconv('utf-8', 'gbk', $title) . "\n";
                $withdrawStatusMap = array(
                    0 => '未处理',
                    1 => '提现成功',
                    2 => '提现失败',
                    3 => '提现处理中',
                );
                $dealTypeMap = [
                    0 => '网贷',
                    3 => '专享',
                ];
                foreach ($list as $k => $v) {
                    $row = '';
                    $row .= $v['id'];
                    $row .= ','.$dealTypeMap[$v['deal_type']];
                    $row .= ','.$v['approve_number'];
                    $row .= ','.$v['name'];
                    $row .= ','.l("DEAL_STATUS_". $v['deal_status']);;
                    $row .= ",\"" . to_date($v['create_time']) . "\"";
                    $row .= ','.$v['withdraw_out_order_id'];
                    $row .= ','. ($v['withdraw_amount'] > 0 ? $v['withdraw_amount'] . '元' : '');
                    $row .= ','.$withdrawStatusMap[$v['withdraw_status']];
                    $row .= ",\"" . date('Y-m-d H:i:s', $v['withdraw_create_time']) . "\"";
                    $row .= ",\"" . date('Y-m-d H:i:s', $v['withdraw_update_time']) . "\"";
                    $row .= ",\"" . $v['withdraw_remark'] . "\"";
                    $row = strip_tags($row);
                    $content .= iconv('utf-8', 'gbk', $row) . "\n";
                }
                header("Content-Disposition: attachment; filename=batch_query.csv");
                echo $content;

                exit;
            }

            $this->assign('list', $list);

        }
        $this->display('batchQuery');
    }
}
?>

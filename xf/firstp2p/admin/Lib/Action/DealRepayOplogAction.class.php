<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/11/16
 * Time: 16:53
 */

use \core\dao\DealRepayOplogModel;
use \core\service\DealService;
use \core\dao\DealRepayModel;
use \core\dao\DealPrepayModel;
use \core\dao\UserModel;
use \core\dao\DealAgencyModel;
use \core\dao\DealLoanTypeModel;
use \core\dao\DealModel;
use \core\service\BwlistService;


class DealRepayOplogAction extends CommonAction {

    public static $auditTypes = array(0 => '还款', 1 => '提交', 2 => '退回', 3 => '自动还款');
    public static $returnTypes = array('1' => '差错', '2' => '其他');
    private function _getRepayLogMap($request) {
        $map = array();
        $dealId = intval($request['deal_id']);
        if(is_numeric($dealId) && $dealId > 0 ) {
            $map['deal_id'] = array('eq', intval($request['deal_id']));
        }

        if(trim($request['deal_name']) != '') {
            $dealName = addslashes(trim($request['deal_name']));
            $map['deal_name'] = array('like', '%' . $dealName . '%');
        }

        $idsByUserName = '';
        if(trim($request['user_name']) != '') {
            $userName = addslashes(trim($request['user_name']));
            $userNameSql = "select group_concat(id) from ".DB_PREFIX."user where user_name like '%". $userName."%'";
            $idsByUserName = $GLOBALS['db']->getOne($userNameSql);
        }

        $idsByRealName = '';
        if(trim($request['real_name']) != '') {
            $realName = addslashes(trim($request['real_name']));
            $realNameSql = "select group_concat(id) from ".DB_PREFIX."user where real_name like '%". $realName ."%'";
            $idsByRealName = $GLOBALS['db']->getOne($realNameSql);
        }

        if ($_REQUEST['return_type']) {
            $map['return_type'] = array('eq', intval($_REQUEST['return_type']));
        }

        if (isset($_REQUEST['report_status']) && $_REQUEST['report_status'] != "") {
            $map['report_status'] = array('eq', intval($_REQUEST['report_status']));
        }

        if (isset($_REQUEST['repay_type']) && $_REQUEST['repay_type'] != "") {
            $map['repay_type'] = array('eq', intval($_REQUEST['repay_type']));
        }

        if (isset($_REQUEST['audit_type']) && $_REQUEST['audit_type'] !== '9999') {
            $map['audit_type'] = array('eq', intval($_REQUEST['audit_type']));
        } else {
            $_REQUEST['audit_type'] = '9999';
        }

        if ($_REQUEST['submit_user_name'] != '') {
            $adminId = M('Admin')->where('adm_name="'.addslashes($_REQUEST['submit_user_name']).'"')->getField('id');
            $map['submit_uid'] = $adminId;
        }

        if($idsByUserName && $idsByRealName) {
            $userIds = $idsByUserName . "," . $idsByRealName;
        } else if($idsByUserName) {
            $userIds = $idsByUserName;
        } else if($idsByRealName) {
            $userIds = $idsByRealName;
        } else if(is_null($idsByRealName) || is_null($idsByUserName)) { //没有查到结果，设置userIds为一个非法的id
            $userIds = -1;
        } else {
            $userIds = "";
        }

        if($userIds) {
            $map['user_id'] = array('in', $userIds);
        }

        $repayTime = trim($_REQUEST['real_repay_time']);
        if($repayTime) {
            $map['real_repay_time'] = array('between', to_timespan($repayTime ." 00:00:00") . "," . to_timespan($repayTime ." 23:59:59"));
        }

        $operationType = intval($_REQUEST['operation_type']);
        if(is_numeric($operationType) && $operationType > 0) {
            $map['operation_type'] = array('eq', $operationType);
        }

        $operationTime = trim($_REQUEST['operation_time']);
        $operationTimeEnd = trim($_REQUEST['operation_time_end']);
        if($operationTime) {
            $map['operation_time'] = array('between', to_timespan($operationTime . " 00:00:00") . "," . to_timespan($operationTimeEnd . " 23:59:59"));
        }

        $operator = $_REQUEST['operator'];
        if($operator) {
            $map['operator'] = array('like', '%' . addslashes($operator) . '%');
        }
        //$map['operation_type'] = array('neq',DealRepayOplogModel::REPAY_TYPE_PRE_SELF); // 暂时不显示用户自助提前还款的记录

        /*if (!empty($request['project_name'])) {
            $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0 AND `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` LIKE \'%' . trim($request['project_name']) .'%\'))';
        }else{
            $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0) ';
        }*/

        //机构管理后台
        $orgSql = $this->orgCondition(false);

        if (!empty($request['project_name'])) {
           if (!$this->is_cn) {
               $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` LIKE \'%' . trim($request['project_name']) .'%\')'.$orgSql.')';
           } else {
              $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0 AND `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` LIKE \'%' . trim($request['project_name']) .'%\')'.$orgSql.')';
           }
       } else {
           if ($this->is_cn) {
               $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0) ';
           } elseif($orgSql) {
               $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE 1 '.$orgSql.')';
           }

       }

        if (!empty($request['project_id'])) {
            $deal_service = new DealService();
            $deal_arr = $deal_service->getDealByProId(intval($request['project_id']));
            $deal_id_arr = array(0);
            foreach ($deal_arr as $deal) {
                $deal_id_arr[] = $deal['id'];
            }
            $map['deal_id'] = array('IN', implode(',', $deal_id_arr));
        }

        return $map;
    }

    public function index() {
        $map = $this->_getRepayLogMap($_REQUEST);
        $name = $this->getActionName();
        $model = D($name);
        if(!empty($model)) {
            $voList = $this->_list($model, $map);
        }

        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealModel = new DealModel();


        // 全部查出来方便 列表筛选
        $deal_repay_mode_contract_white = array();
        $contract_ids = BwlistService::getValueList(DealRepayModel::DEAL_REPAY_MODE_WHITE_TYPE_KEY);
        if (!empty($contract_ids)){
            foreach($contract_ids as $con_id){
                $deal_repay_mode_contract_white[$con_id['value']] = $con_id['value'];
            }

        }

        //为了后台展示，处理数据。
        foreach($voList as &$opLog) {


            $deal = $dealModel->find($opLog['deal_id']);
            $opLog['loanTypeName'] = $dealLoanTypeModel->getLoanNameByTypeId($deal['type_id']);

            if($opLog['loantype'] == 5) {
                $opLog['repay_period'] = $opLog['repay_period'] . "天";
            } else {
                $opLog['repay_period'] = $opLog['repay_period'] . "月";
            }
            $opLog['loantype'] = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'][$opLog['loantype']] : $GLOBALS['dict']['LOAN_TYPE'][$opLog['loantype']];
            $userInfo = M("User")->where("`id` = {$opLog['user_id']}")->find();
            $opLog['user_name'] = $userInfo['user_name'];
            $opLog['real_name'] = $userInfo['real_name'];
            $opLog['operation_time'] = to_date($opLog['operation_time']);
            $opLog['real_repay_time'] = to_date($opLog['real_repay_time'], "Y-m-d");
            if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_NORMAL) {
                $opLog['operation_type'] = "正常还款";
            } else if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_PRE){
                $opLog['operation_type'] = "提前还款";
            } else if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_PRE_SELF){
                $opLog['operation_type'] = "自助还款";
            } else if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_DAIFA){
                $opLog['operation_type'] = "代发还款";
            }else if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_PART){
                $opLog['operation_type'] = "部分还款";
            }
            $opLog['audit_type'] = self::$auditTypes[$opLog['audit_type']];
            $opLog['submit_uid'] = $opLog['submit_uid'] ? get_admin_name($opLog['submit_uid']) : '';
            $opLog['return_type'] = $opLog['return_type'] ? self::$returnTypes[$opLog['return_type']] : '';
            // 还款模式 处理 节前节后
            if (in_array($deal['contract_tpl_type'],$deal_repay_mode_contract_white)){

                $opLog['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER];
            }else{
                $opLog['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE];
            }
        }

        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayModel::$repayTypeMsg[DealRepayModel::DEAL_REPAY_TYPE_SELF]);
        $this->assign('deal_repay_type',DealRepayModel::$repayTypeMsg);
        $this->assign('list', $voList);
        $this->display();
    }

    public function export_csv() {
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayModel::$repayTypeMsg[DealRepayModel::DEAL_REPAY_TYPE_SELF]);
        if($_REQUEST['id'] <> '') {
            $ids = explode(',', $_REQUEST['id']);
        }
        $map = $this->_getRepayLogMap($_REQUEST);
        $name = $this->getActionName();
        $model = D($name);
        if(!empty($model)) {
            $repayOplogList = $this->_list($model, $map);
        }

        // 全部查出来方便 列表筛选
        $deal_repay_mode_contract_white = array();
        $contract_ids = BwlistService::getValueList(DealRepayModel::DEAL_REPAY_MODE_WHITE_TYPE_KEY);
        if (!empty($contract_ids)){
            foreach($contract_ids as $con_id){
                $deal_repay_mode_contract_white[$con_id['value']] = $con_id['value'];
            }

        }

        if($repayOplogList) {
                //title
            $content = iconv("utf-8", "gbk", "\t\t\t\t还款操作列表\n编号,项目名称,借款标题,借款金额,年化借款利率,借款期限,还款方式,还款模式,借款人姓名,借款人用户名,借款人id,实际还款日期,本期已还款金额,操作类型,操作日期,操作人员,产品类型,本期还款形式");
            $content = $content . "\n";

            //产品类型
            $dealLoanTypeModel = new DealLoanTypeModel();
            $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
            foreach($dealLoanType as $typeValue){
                $loanTypes[$typeValue['id']] = $typeValue['name'];
            }
            $dealModel = new DealModel();

            foreach($repayOplogList as $key => $opLog) {

                $deal = $dealModel->find($opLog['deal_id']);

                $formatOpLog['deal_id'] = $opLog['deal_id'];
                $project_info = DealService::getProjectInfoByDealId($opLog['deal_id']);
                $formatOpLog['project_name'] = iconv("utf-8", "gbk", $project_info['name']);
                $formatOpLog['deal_name'] = iconv("utf-8", "gbk", $opLog['deal_name']);
                $formatOpLog['borrow_amount'] = sprintf("%.2f", $opLog['borrow_amount']);
                $formatOpLog['rate'] = sprintf("%.2f", $opLog['rate']) . "%";
                if($opLog['loantype'] == 5) {
                    $opLog['repay_period'] = $opLog['repay_period'] . "天";
                } else {
                    $opLog['repay_period'] = $opLog['repay_period'] . "月";
                }
                $formatOpLog['repay_period'] = iconv("utf-8", "gbk", $opLog['repay_period']);
                $formatOpLog['loantype'] = $this->is_cn ? iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE_CN'][$opLog['loantype']]) : iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE'][$opLog['loantype']]);
                // 还款模式 处理 节前节后
                if (in_array($deal['contract_tpl_type'],$deal_repay_mode_contract_white)){

                    $formatOpLog['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER];
                }else{
                    $formatOpLog['repay_mode_name'] =     DealRepayModel::$dealRepayModeText[DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE];
                }

                $formatOpLog['repay_mode_name'] = iconv("utf-8", "gbk", $formatOpLog['repay_mode_name']);
                $userInfo = M("User")->where("`id` = {$opLog['user_id']}")->find();
                $formatOpLog['user_name'] = iconv("utf-8", "gbk", $userInfo['user_name']);
                $formatOpLog['real_name'] = iconv("utf-8", "gbk", $userInfo['real_name']);
                $formatOpLog['user_id'] = $opLog['user_id'];
                $formatOpLog['real_repay_time'] = to_date($opLog['real_repay_time'], "Y-m-d");
                $formatOpLog['repay_money'] = $opLog['repay_money'];
                if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_NORMAL) {
                    $opLog['operation_type'] = "正常还款";
                } else if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_PRE){
                    $opLog['operation_type'] = "提前还款";
                } else if($opLog['operation_type'] == DealRepayOplogModel::REPAY_TYPE_DAIFA){
                    $opLog['operation_type'] = "代发还款";
                }
                $formatOpLog['operation_type'] = iconv("utf-8", "gbk", $opLog['operation_type']);
                $formatOpLog['operation_time'] = to_date($opLog['operation_time']);
                $formatOpLog['operator'] = iconv("utf-8", "gbk", $opLog['operator']);


                $formatOpLog['loanTypeName'] = iconv("utf-8", "gbk", $loanTypes[$deal['type_id']]);
                $formatOpLog['repay_type_name'] = iconv("utf-8", "gbk", DealRepayModel::$repayTypeMsg[$opLog['repay_type']]);


                //因为数据中逗号（半角）会影响生成csv表格，所以此处去掉数据中的逗号。
                foreach($formatOpLog as $key => $value){
                    $formatOpLog[$key] = (strstr($value, ',') === false ? $value : str_replace(',', ' ', $value));
                }

                if(is_array($ids) && count($ids) > 0) {
                    if(array_search($opLog['$id'], $ids) !== false) {
                        $content .= implode(",", $formatOpLog) . "\n";
                    }
                } else {
                    $content .= implode(",", $formatOpLog) . "\n";
                }
            }
            $dateTime = date("YmdHis", time());
            header("Content-Disposition: attachment; filename={$dateTime}_deal_repay_oplog.csv");
            echo $content;
        } else {
            $this->error(L("NO_RESULT"));
        }
    }

}

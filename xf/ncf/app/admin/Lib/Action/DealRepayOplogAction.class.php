<?php
/**
 * 还款操作记录
 * User: Yihui
 * Date: 2015/11/16
 * Time: 16:53
 */

use \core\dao\repay\DealRepayOplogModel;
use \core\service\deal\DealService;
use \core\dao\repay\DealRepayModel;
use \core\dao\repay\DealPrepayModel;
use \core\service\user\UserService;
use \core\dao\deal\DealAgencyModel;
use \core\dao\deal\DealLoanTypeModel;
use \core\dao\deal\DealModel;
use \core\enum\DealRepayOpLogEnum;
use \core\enum\DealRepayEnum;


class DealRepayOplogAction extends CommonAction {

    public static $auditTypes = array(0 => '还款', 1 => '提交', 2 => '退回', 3 => '自动还款');
    public static $returnTypes = array('1' => '差错', '2' => '其他');
    private function _getRepayLogMap($request) {
        // 默认为1年内操作记录
        $_REQUEST['is_history'] = isset($_REQUEST['is_history']) ? intval($_REQUEST['is_history']) : 0;

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
            $idsByUserName = UserService::getUserByName($userName);
        }

        $idsByRealName = '';
        if(trim($request['real_name']) != '') {
            $realName = addslashes(trim($request['real_name']));
            $idsByRealName = UserService::getUserIdByRealName($realName);
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

        if (!empty($request['project_name'])) {
            $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` LIKE \'%' . trim($request['project_name']) .'%\'))';
       } else {
            //$map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0) ';
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
        if($_REQUEST['is_history'] == 1){
            $model = D($name,'',false,'firstp2p_history','slave');
        }else{
            $model = D($name,'',false,'firstp2p','adminslave');
        }

        if(!empty($model)) {
            $voList = $this->_list($model, $map);
        }
        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealModel = new DealModel();
        $userids = array();
        //为了后台展示，处理数据。
        foreach($voList as &$opLog) {
            $userids[$opLog['user_id']] = $opLog['user_id'];
            $deal = $dealModel->find($opLog['deal_id']);
            $opLog['loanTypeName'] = $dealLoanTypeModel->getLoanNameByTypeId($deal['type_id']);

            if($opLog['loantype'] == 5) {
                $opLog['repay_period'] = $opLog['repay_period'] . "天";
            } else {
                $opLog['repay_period'] = $opLog['repay_period'] . "月";
            }
            $opLog['loantype'] = $GLOBALS['dict']['LOAN_TYPE_CN'][$opLog['loantype']];
            $opLog['operation_time'] = to_date($opLog['operation_time']);
            $opLog['real_repay_time'] = to_date($opLog['real_repay_time'], "Y-m-d");
            if($opLog['operation_type'] == DealRepayOpLogEnum::REPAY_TYPE_NORMAL) {
                $opLog['operation_type'] = "正常还款";
            } else if($opLog['operation_type'] == DealRepayOpLogEnum::REPAY_TYPE_PRE){
                $opLog['operation_type'] = "提前还款";
            } else if($opLog['operation_type'] == DealRepayOpLogEnum::REPAY_TYPE_PRE_SELF){
                $opLog['operation_type'] = "自助还款";
            } else if($opLog['operation_type'] == DealRepayOpLogEnum::REPAY_TYPE_DAIFA){
                $opLog['operation_type'] = "代发还款";
            }
            $opLog['audit_type'] = self::$auditTypes[$opLog['audit_type']];
            $opLog['submit_uid'] = $opLog['submit_uid'] ? get_admin_name($opLog['submit_uid']) : '';
            $opLog['return_type'] = $opLog['return_type'] ? self::$returnTypes[$opLog['return_type']] : '';
        }
       $userinfos  = UserService ::getUserInfoByIds($userids);
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayEnum::$repayTypeMsg[DealRepayEnum::DEAL_REPAY_TYPE_SELF]);
        $this->assign('deal_repay_type',DealRepayEnum::$repayTypeMsg);
        $this->assign('list', $voList);
        $this->assign('userinfos', $userinfos);
        $this->display();
    }

    public function export_csv() {
        //按照需求文档去除本期还款形式是“借款人还款”的选项
        unset(DealRepayEnum
                ::$repayTypeMsg[DealRepayEnum::DEAL_REPAY_TYPE_SELF]);
        if($_REQUEST['id'] <> '') {
            $ids = explode(',', $_REQUEST['id']);
        }
        $map = $this->_getRepayLogMap($_REQUEST);
        $name = $this->getActionName();
        if($_REQUEST['is_history'] == 1){
            $model = D($name,'',false,'firstp2p_history','slave');
        }else{
            $model = D($name,'',false,'firstp2p','adminslave');
        }

        if(!empty($model)) {
            $repayOplogList = $this->_list($model, $map);
        }


        if($repayOplogList) {
                //title
            $content = iconv("utf-8", "gbk", "\t\t\t\t还款操作列表\n编号,项目名称,借款标题,借款金额,年化借款利率,借款期限,还款方式,借款人姓名,借款人用户名,借款人id,实际还款日期,本期已还款金额,还款类型,操作类型,操作日期,操作人员,产品类型,本期还款形式");
            $content = $content . "\n";

            //产品类型
            $dealLoanTypeModel = new DealLoanTypeModel();
            $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
            foreach($dealLoanType as $typeValue){
                $loanTypes[$typeValue['id']] = $typeValue['name'];
            }
            $dealModel = new DealModel();
            foreach($repayOplogList as $opLog) {
                $userids[$opLog['user_id']] = $opLog['user_id'];
            }
            $userinfos  = UserService ::getUserInfoByIds($userids);
            foreach($repayOplogList as $key => $opLog) {
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
                $formatOpLog['loantype'] =  iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE_CN'][$opLog['loantype']]);

                $formatOpLog['user_name'] = iconv("utf-8", "gbk", $userinfos[$opLog['user_id']]['user_name']);
                $formatOpLog['real_name'] = iconv("utf-8", "gbk", $userinfos[$opLog['user_id']]['real_name']);
                $formatOpLog['user_id'] = $opLog['user_id'];
                $formatOpLog['real_repay_time'] = to_date($opLog['real_repay_time'], "Y-m-d");
                $formatOpLog['repay_money'] = $opLog['repay_money'];
                if($opLog['operation_type'] == DealRepayOplogEnum::REPAY_TYPE_NORMAL) {
                    $opLog['operation_type'] = "正常还款";
                } else if($opLog['operation_type'] == DealRepayOplogEnum::REPAY_TYPE_PRE){
                    $opLog['operation_type'] = "提前还款";
                } else if($opLog['operation_type'] == DealRepayOplogEnum::REPAY_TYPE_DAIFA){
                    $opLog['operation_type'] = "代发还款";
                }
                $formatOpLog['operation_type'] = iconv("utf-8", "gbk", $opLog['operation_type']);
                $formatOpLog['audit_type'] = iconv("utf-8", "gbk", self::$auditTypes[$opLog['audit_type']]);
                $formatOpLog['operation_time'] = to_date($opLog['operation_time']);
                $formatOpLog['operator'] = iconv("utf-8", "gbk", $opLog['operator']);

                $deal = $dealModel->find($opLog['deal_id']);
                $formatOpLog['loanTypeName'] = iconv("utf-8", "gbk", $loanTypes[$deal['type_id']]);
                $formatOpLog['repay_type_name'] = iconv("utf-8", "gbk", DealRepayEnum::$repayTypeMsg[$opLog['repay_type']]);

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

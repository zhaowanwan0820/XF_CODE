<?php
/**
 * Created by PhpStorm.
 * User: wangchuanlu
 * Date: 2018/05/23
 */

use \core\dao\DealRepayOplogModel;
use \core\service\DealService;
use \core\dao\DealRepayModel;
use \core\dao\DealPrepayModel;
use \core\dao\UserModel;
use \core\dao\DealAgencyModel;
use \core\dao\DealLoanTypeModel;
use \core\dao\DealModel;


class ProjectYjRepayOplogAction extends CommonAction {

    private function _getRepayLogMap($request) {
        $map = array();
        if(intval($request['project_id']) > 0 ) {
            $map['project_id'] = array('eq', intval($request['project_id']));
        }
        if(intval($request['operation_type']) > 0 ) {
            $map['operation_type'] = array('eq', intval($_REQUEST['operation_type']));
        }

        $operationTime = trim($_REQUEST['operation_time']);
        $operationTimeEnd = trim($_REQUEST['operation_time_end']);
        if($operationTime) {
            $map['operation_time'] = array('between', to_timespan($operationTime . " 00:00:00") . "," . to_timespan($operationTimeEnd . " 23:59:59"));
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
            $opLog['return_type'] = $opLog['return_type'] ? self::$returnTypes[$opLog['return_type']] : '';
        }

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


        if($repayOplogList) {
                //title
            $content = iconv("utf-8", "gbk", "\t\t\t\t还款操作列表\n编号,项目名称,借款标题,借款金额,年化借款利率,借款期限,还款方式,借款人姓名,借款人用户名,借款人id,实际还款日期,本期已还款金额,操作类型,操作日期,操作人员,产品类型,本期还款形式");
            $content = $content . "\n";

            //产品类型
            $dealLoanTypeModel = new DealLoanTypeModel();
            $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
            foreach($dealLoanType as $typeValue){
                $loanTypes[$typeValue['id']] = $typeValue['name'];
            }
            $dealModel = new DealModel();

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
                $formatOpLog['loantype'] = $this->is_cn ? iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE_CN'][$opLog['loantype']]) : iconv("utf-8", "gbk", $GLOBALS['dict']['LOAN_TYPE'][$opLog['loantype']]);
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
                }
                $formatOpLog['operation_type'] = iconv("utf-8", "gbk", $opLog['operation_type']);
                $formatOpLog['operation_time'] = to_date($opLog['operation_time']);
                $formatOpLog['operator'] = iconv("utf-8", "gbk", $opLog['operator']);

                $deal = $dealModel->find($opLog['deal_id']);
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

<?php
use core\service\UserService;
use core\service\CreditLoanService;
use core\service\CreditLoanConfigService;
use core\dao\CreditLoanModel;
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
class CreditLoanAction extends CommonAction{

    public function index(){
        $map = $this->_get_map();
        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = DI ($name);
        $list = array();
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
        }
        $this->assign('list', $list);
        $this->assign('main_title','信用贷申请列表');
        $this->display ();
        return;
    }

    /**
     * 撤销信用贷申请列表
     */
    public function revoke()
    {
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);

        $creditLoanService = new CreditLoanService();
        $res = $creditLoanService->revokeCreditLoanByLoanId($id);
        if ($res) {
            save_log('撤销信用贷申请成功,申请编号:'.$id,0);
            $this->success ('撤销信用贷申请成功',$ajax);
        } else {
            save_log('撤销信用贷申请失败,申请编号:'.$id, 0);
            $this->error('撤销信用贷申请失败',$ajax);
        }
    }

    public function manual_repay() {
        $id = intval($_REQUEST ['loan_id']);
        $cs = new CreditLoanService();
        $creditLoanInfo = $cs->getCreditLoanRecordByCreditLoanId($id);
        if(!$creditLoanInfo){
            $this->error("借款信息不存在");
        }
        $this->assign("loan_id",$id);
        $this->display ();
    }

    public function save_manual_repay() {
        $loan_id = intval($_POST['loan_id']);
        $interest = $_POST['interest'];
        $service_fee = $_POST['service_fee'];
        $finish_time = $_POST['finish_time'];

        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $interest)) {
            $this->error("利息必须为两位小数");
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $service_fee)) {
            $this->error("业务信息服务费必须为两位小数");
        }
        if(!$finish_time) {
            $this->error("请填写正确的时间格式");
        }

        try{
            $cs = new CreditLoanService();
            $creditLoanInfo = $cs->offlinePrepay($loan_id,$interest,$service_fee,strtotime($finish_time));
        }catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
        $this->success("还款成功",0,u("CreditLoan/index"));
    }

    private function _get_map(){
        $map = array();
        if(intval($_REQUEST['id'])>0){
            $map['id'] = intval($_REQUEST['id']);
        }
        if(intval($_REQUEST['deal_id'])>0){
            $map['deal_id'] = intval($_REQUEST['deal_id']);
        }
        if(intval($_REQUEST['user_id'])>0){
            $map['user_id'] = intval($_REQUEST['user_id']);
        }
        if(isset($_REQUEST['status']) && trim($_REQUEST['status']) != '' && trim($_REQUEST['status']) != 'all'){
            if(intval($_REQUEST['status']) == CreditLoanModel::STATUS_REPAY) {//还款中
                $map['status'] = array("in",array(
                    CreditLoanModel::STATUS_REPAY,
                    CreditLoanModel::STATUS_REPAY_HANDLE,
                    CreditLoanModel::STATUS_PAYMENT,
                ));
            } else {
                $map['status'] = array("eq",intval($_REQUEST['status']));
            }
        }

        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = strtotime($_REQUEST['apply_start']." 00:00:00");
            $map['create_time'] = array('egt', $apply_start);
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = strtotime($_REQUEST['apply_end']." 23:59:59");
            $map['create_time'] = array('between', sprintf('%s,%s', $apply_start, $apply_end));
        }
        return $map;
    }

    /**
     * 导出信用贷申请列表
     */
    public function export_csv($page = 1)
    {
        set_time_limit(0);
        $limit = (($page - 1)*intval(app_conf("BATCH_PAGE_SIZE"))).",".(intval(app_conf("BATCH_PAGE_SIZE")));
        $where = " 1=1 ";

        if($_REQUEST['id']){
            $where .= " AND id in(". $_REQUEST['id'] . ")";
        }
        if(intval($_REQUEST['user_id'])>0){
            $where .= " AND user_id = ". $_REQUEST['user_id'];
        }

        if(intval($_REQUEST['deal_id'])>0){
            $where .= " AND deal_id = ". $_REQUEST['deal_id'];
        }

        if(isset($_REQUEST['status']) && trim($_REQUEST['status']) != '' && trim($_REQUEST['status']) != 'all'){

            if(intval($_REQUEST['status']) == CreditLoanModel::STATUS_REPAY) {//还款中
                $where .= " AND status in (". explode(',',array(CreditLoanModel::STATUS_REPAY,CreditLoanModel::STATUS_REPAY_HANDLE,CreditLoanModel::STATUS_PAYMENT)) .")";
            } else {
                $where .= " AND status = ". $_REQUEST['status'];
            }
        }

        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = strtotime($_REQUEST['apply_start']." 00:00:00");
            $where .= " AND create_time >= ". $apply_start;
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = strtotime($_REQUEST['apply_end']." 23:59:59");
            $where .= " AND create_time <= ". $apply_end;
        }

        $list = M("CreditLoan")
            ->where($where)
            ->order('id desc')
            ->limit($limit)->findAll ( );

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportCreditLoan',
                'analyze' => M("CreditLoan")->getLastSql()
            )
        );

        if($list)
        {
            register_shutdown_function(array(&$this, 'export_csv'), $page+1);
            $order_value = array(
                'id'=>'""',
                'user_id'=>'""',
                'deal_id'=>'""',
                'create_time' =>'""',
                'money'=>'""',
                'period_apply'=>'""',
                'rate'=>'""',
                'loan_time' => '""',
                'plan_time'=>'""',
                'repay_time'=>'""',
                'finish_time'=>'""',
                'period_repay'=>'""',
                'interest'=>'""',
                'service_fee'=>'""',
                'status'=>'""',
            );

            if($page == 1)
            {
                $content = iconv("utf-8","gbk","编号,会员ID,质押标的,申请时间,申请金额,申请期限,利率,实际放款时间,预计还款时间,申请还款时间,实际还款时间,借款期限,利息,业务服务费,状态");
                $content = $content . "\n";
            }

            foreach($list as $k=>$v)
            {
                $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
                $order_value['user_id'] = '"' . iconv('utf-8','gbk',$v['user_id']) . '"';
                $order_value['deal_id'] = '"' . iconv('utf-8','gbk',$v['deal_id']) . '"';
                $order_value['create_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['create_time'])) . '"';
                $order_value['money'] = '"' . iconv('utf-8','gbk',$v['money']) . '"';
                $order_value['period_apply'] = '"' . iconv('utf-8','gbk',$v['period_apply']."天") . '"';
                $order_value['rate'] = '"' . iconv('utf-8','gbk',$v['rate'].'%') . '"';
                $order_value['loan_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['loan_time'])) . '"';
                $order_value['plan_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['plan_time'])) . '"';
                $order_value['repay_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['repay_time'])) . '"';
                $order_value['finish_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['finish_time'])) . '"';
                if($v['loan_time'] == 0) {
                    $order_value['loan_time'] = '"--"';
                }
                if($v['plan_time'] == 0) {
                    $order_value['plan_time'] = '"--"';
                }
                if($v['repay_time'] == 0) {
                    $order_value['repay_time'] = '"--"';
                }
                if($v['finish_time'] == 0) {
                    $order_value['finish_time'] = '"--"';
                }
                $order_value['period_repay'] = '"' . iconv('utf-8','gbk',$v['period_repay'].'天') . '"';
                $order_value['interest'] = '"' . iconv('utf-8','gbk',floatval($v['interest'])) . '"';
                $order_value['service_fee'] = '"' . iconv('utf-8','gbk',$v['service_fee']) . '"';
                switch ($v['status']){
                    case CreditLoanModel::STATUS_APPLY:
                        $status = '申请中';
                        break;
                    case CreditLoanModel::STATUS_FAIL:
                        $status = '已取消';
                        break;
                    case CreditLoanModel::STATUS_USING:
                        $status = '使用中';
                        break;
                    case CreditLoanModel::STATUS_REPAY:
                    case CreditLoanModel::STATUS_REPAY_HANDLE:
                    case CreditLoanModel::STATUS_PAYMENT:
                        $status = '还款中';
                        break;
                    case CreditLoanModel::STATUS_FINISH:
                        $status = '已还清';
                        break;
                    default:
                        $status = '申请中';
                }
                $order_value['status'] = '"' . iconv('utf-8','gbk',$status) . '"';
                $content .= implode(",", $order_value) . "\n";
            }
            $datatime = date("YmdHis",get_gmtime());
            header("Content-Disposition: attachment; filename={$datatime}_creditloan_list.csv");
            echo $content;
        } else {
            if($page==1) {
                $this->error(L("NO_RESULT"));
            }
        }
    }

    /*
     * 银信通配置页面
     */
    public function settings() {
        $creditLoanConfigService = new CreditLoanConfigService();
        $data = $creditLoanConfigService->getConfigColums('config');
        $data['CREDIT_LOAN_DEAL_REPAY_TYPE_ARRY'] = explode(CreditLoanConfigService::$configDelimiter, $data['CREDIT_LOAN_DEAL_REPAY_TYPE']);
        $data['CREDIT_LOAN_DEAL_TYPE_ARRY'] = explode(CreditLoanConfigService::$configDelimiter, $data['CREDIT_LOAN_DEAL_TYPE']);
        $data['CREDIT_LOAN_DEAL_TYPE_DICTIONARY'] = CreditLoanConfigService::$creditLoanDealType;
        $data['CREDIT_LOAN_BORROW_RATE_ARRY'] = explode(CreditLoanConfigService::$configDelimiter, $data['CREDIT_LOAN_BORROW_RATE']);
        $data['CREDIT_LOAN_BORROW_RATE_DICTIONARY'] = CreditLoanConfigService::$creditLoanRate;
        // ban掉还款方式和标的类型
        $data['banDealRepayTypes'] = CreditLoanConfigService::$banDealRepayTypes;
        $data['banDealTypes'] = CreditLoanConfigService::$banDealTypes;
        $this->assign('settings', $data);
        $this->display();
    }

    /**
     * 黑名单配置页面
     */
    public function blacklist() {
        $creditLoanConfigService = new CreditLoanConfigService();
        $data = $creditLoanConfigService->getConfigColums('blacklist');
        $this->assign('settings', $data);
        $this->display();
    }

    /**
     * 更新配置项
     */
    public function updateSetttings() {
        $toUpdate = $_POST;
        foreach ($toUpdate as $configName => $data) {
            $_type = gettype($data);
            switch ($_type){
                case 'string':
                    $toUpdate[$configName] = addslashes(trim($data));
                    break;
                case 'array':
                    foreach ($data as $idx => $value) {
                        $data[$idx] = addslashes(trim($value));
                    }
                    $toUpdate[$configName] = implode(CreditLoanConfigService::$configDelimiter, $data);
                    break;
            }
        }
        $creditLoanConfigService = new CreditLoanConfigService();
        $result = $creditLoanConfigService->updateSetttings($toUpdate);
        if ($result) {
            return $this->success('更新成功！');
        } else {
            return $this->error('更新失败！');
        }
    }
}

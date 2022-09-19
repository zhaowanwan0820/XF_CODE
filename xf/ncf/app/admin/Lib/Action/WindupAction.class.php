<?php
/**
 * JobsAction class file.
 * */
use core\dao\deal\DealModel;
use core\dao\repay\DealLoanRepayModel;

use core\service\account\AccountService;

use core\enum\DealEnum;
use core\enum\AccountEnum;

use libs\db\Db;
use NCFGroup\Common\Library\Idworker;

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__DEBUG', true);

class WindupAction extends CommonAction{
    const STATUS_INIT = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;
    const STATUS_PROCESSING = 3;

    static $statusCn = [
        self::STATUS_INIT => '初始化',
        self::STATUS_PROCESSING => '处理中',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAILURE => '失败',
        ];

    const STATE_WAIT_B_AUDIT = 1;
    const STATE_B_PASSED =  2;
    const STATE_B_REFUSED = 3;
    const STATE_COLLECT_DATA_FINISHED = 4;
    const STATE_PAYING = 5;
    const STATE_PAY_OK = 6;
    const STATE_PAY_FAIL = 7;

    static $stateCn = [
        self::STATE_WAIT_B_AUDIT => 'B角色审核',
        self::STATE_B_PASSED => 'B角色通过',
        self::STATE_B_REFUSED => 'B角色拒绝',
        self::STATE_COLLECT_DATA_FINISHED => '待请求支付',
        self::STATE_PAYING => '支付处理中',
        self::STATE_PAY_OK => '支付处理成功',
        self::STATE_PAY_FAIL => '支付处理失败',
        ];

    public $checkCritical = false;
    public $checkResult = [];
    public $currentLine;
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->_list(MI('Windup'));
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $this->assign('p', $p);
        $this->assign('pay_status', self::$statusCn);
        $this->assign('status', $status);
        $GLOBALS['statusListCn'] = self::$stateCn;
        $this->display('index');

    }

    public function import() {
        if (empty($_FILES['batchFile']) || $_FILES['batchFile']['error'] != 0) {
            $this->error("文件不存在或者上传有误");
        }
        $file = $_FILES['batchFile'];
        $fileContents = file($file['tmp_name']);
        $colNames = explode(',', trim(array_shift($fileContents)));
        $data = $fileContents;
        $newRows = [];
        foreach ($data as $k => $row) {
            $this->currentLine = $k + 1;
            $row = array_combine($colNames, explode(',', trim($row)));
            $this->check($row);
            if ($this->checkCritical) {
                break;
            }
            $newRows[] = $row;
        }


        // 失败
        if (!empty($this->checkResult)) {
            $this->assign('list', $this->checkResult);
            return $this->display('error');
        }
        // 数据整理
        $payUserDealMap = [];
        foreach ($newRows as $k => $row) {
            $userIds[] = $row['user_id'];
            $dealIds[] = $row['deal_id'];
            $payUserDealMap[$row['deal_id']] = $row['pay_user_id'];
        }
        $newUserIds = array_unique($userIds);
        $newDealIds = array_unique($dealIds);
        $userIdString = implode(',', $newUserIds);
        $dealIdString = implode(',', $newDealIds);
        $recordData = [];
        $recordData['out_order_id'] = Idworker::instance()->getId();
        $recordData['user_ids'] = $userIdString;
        $recordData['deal_ids'] = $dealIdString;
        $recordData['pay_user_ids'] = json_encode($payUserDealMap);
        $recordData['sign'] = md5($userIdString.'*****'.$dealIdString.'@@@@'.$recordData['pay_user_ids']);
        $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
        $recordData['total_users'] = count($newUserIds);
        $recordData['total_deals'] = count($newDealIds);
        $recordData['apply_user'] = $adminInfo['adm_name'];
        $recordData['apply_time'] = date('Y-m-d H:i:s');

        try {
            $db = Db::getInstance('firstp2p','master');
            $cnt = $db->getOne("SELECT count(*) AS cnt FROM firstp2p_windup WHERE sign = '{$recordData['sign']}'");
            if ($cnt == 1) {
                throw new \Exception('批次数据已经存在');
            }
            $res = $db->autoExecute('firstp2p_windup', $recordData, 'INSERT');
            if ($db->affected_rows() < 1) {
                throw new \Exception('操作失败,数据提交失败');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        //成功
        $this->success('上传成功，请等待系统计算数据');
    }


    public function details() {
        if (isset($_GET['export'])) {
            return $this->export();
        }
        $map = $this->getMap();
        $GLOBALS['statusCn'] = self::$statusCn;
        $this->_list(MI('WindupDetail'), $map);
        $this->assign('pay_status', self::$statusCn);
        $this->assign('status', $status);
        $this->assign('p', $p);
        $this->display('detail');
    }

    public function audit() {
        $action = addslashes(trim($_GET['action']));
        $id = intval($_GET['id']);
        $db = Db::getInstance('firstp2p', 'master');
        $data =[];
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['audit_time'] = $data['update_time'];
        $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
        $data['audit_user'] = $adminInfo['adm_name'];
        switch($action) {
            case 'refuse':
                $data['state'] = self::STATE_B_REFUSED;
                $data['sign'] = uniqid();
                $db->autoExecute('firstp2p_windup', $data, 'UPDATE', " id = '{$id}' ");
                break;
            case 'pass':
                $data['state'] = self::STATE_B_PASSED;
                $db->autoExecute('firstp2p_windup', $data, 'UPDATE', " id = '{$id}' ");
                break;
            case 'confirm':
                $data['state'] = self::STATE_COLLECT_DATA_FINISHED;
                $db->autoExecute('firstp2p_windup', $data, 'UPDATE', " id = '{$id}' ");
                break;

        }
        $this->success('审核成功');
    }

    public function batchAuditConfirm() {
        if (empty($_REQUEST['checkIds'])) {
            return ajax_return(['status'=> 1, 'message' => '请选择要确认的数据']);
        }

        $result = [
            'status' => 0,
            'message' => '成功',
            ];
        $db = Db::getInstance('firstp2p', 'master');
        try {
            $ids = array();
            foreach ($_REQUEST['checkIds'] as $id) {
                $id = intval($id);
                if ($id === 0) {
                    throw new  \Exception('数据格式错误');
                }
                array_push($ids, $id);
            }

            $sql = "UPDATE firstp2p_windup SET state = ".self::STATE_COLLECT_DATA_FINISHED." WHERE  state = ".self::STATE_B_PASSED." AND id IN (".implode(',', $ids).")";
            $db->query($sql);

        } catch (\Exception $e) {
            $result['status'] = 1;
            $result['message'] = $e->getMessage();

        }

        return  ajax_return($result);
    }


    private function getMap() {
        $map = array();
        if(!empty($_GET['batch_id'])) {
            $map['batch_id'] =  intval($_GET['batch_id']);
        }
        if(!empty($_GET['deal_id'])) {
            $map['deal_id'] =  intval($_GET['deal_id']);
        }
        if(!empty($_GET['user_id'])){
            $map['user_id'] = intval($_GET['user_id']);
        }
        if(isset($_GET['pay_status']) AND $_GET['pay_status'] != '') {
            $map['pay_status'] = intval($_GET['pay_status']);
        }
        return $map;
    }

    private function createQueryCondition($map) {
        if (empty($map)) {
            return '';
        }
        $queryConditionStr = ' WHERE ';
        $querySlice = [];
        foreach ($map as $k => $v) {
            $v = addslashes($v);
            $querySlice[] = "{$k} = '{$v}' ";
        }

        $queryConditionStr .= implode(' AND ' , $querySlice);
        return $queryConditionStr;
    }

    private function export()
    {
        $conditionStr =  $this->createQueryCondition($this->getMap());
        return $this->export_query_order($conditionStr);
    }

    private function export_query_order($conditionStr = '') {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $rows = $this->getData($conditionStr);
        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename={$datatime}.csv");

        $title = array(
                '编号','外部订单号','还款账户编号','标的编号', '用户编号', '提现金额',
                '申请时间','支付状态', '支付时间',
                );

        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gbk//IGNORE", $v);
        }

        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);
        foreach ($rows as $v) {
            $arr = [];
            $arr[] = $v['id'];
            $arr[] = $v['batch_id']."\t";
            // 获取用户信息
            $arr[] = $v['pay_user_id'];
            $arr[] = $v['deal_id'];
            $arr[] = $v['user_id'];
            $arr[] = bcdiv($v['amount'], 100,2);
            $arr[] = $v['create_time'];
            $arr[] = self::$statusCn[$v['pay_status']];
            foreach ($arr as $k => $v){
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", strip_tags($v));
            }
            fputcsv($fp, $arr);
        }
    }

    public function getData($queryConditionStr = '') {
        // 拼装sql
        $sql = "SELECT * FROM firstp2p_windup_detail {$queryConditionStr}";

        $db = Db::getInstance('firstp2p', 'master');
        $rows = $db->getAll($sql);
        return $rows;
    }

    private function check($data) {
        // 检查标的是否是智多鑫标的
        $this->checkDeal($data['deal_id']);
        $this->checkUserExists($data['user_id']);
        $this->checkUserInvestmentExists($data['user_id'], $data['deal_id']);
    }

    private function checkDeal($dealId) {
        $isZdx = DealModel::instance()->db->getOne("SELECT COUNT(*) FROM firstp2p_deal_tag WHERE deal_id = '{$dealId}' AND tag_id = '42'");
        if (!empty($isZdx) && $isZdx == 1) {
            $this->checkResult[] = '第 '.$this->currentLine.' 行，标的编号（'.$dealId . '）属于智多鑫标的，不符合处理规则，预检失败';
            $this->checkCritical = true;
            return ;
        }

        $dealInfo = DealModel::instance()->find($dealId);
        if (!$dealInfo) {
            $this->checkResult[] = '第 '.$this->currentLine.' 行，标的编号（'.$dealId . '）不存在，不符合处理规则，预检失败';
            $this->checkCritical = true;
            return ;
        }

        if ($dealInfo->deal_status != DealEnum::DEAL_STATUS_REPAY) {
            $this->checkResult[] = '第 '.$this->currentLine.' 行，标的编号（'.$dealId . '）状态不是还款中，不符合处理规则，预检失败';
            $this->checkCritical = true;
            return ;
        }



        return true;
    }
    private function checkUserExists($userId) {
        $userInfo = AccountService::getAccountInfoById($userId);
        if (empty($userInfo) || $userInfo['status'] == AccountEnum::STATUS_UNACTIVATED) {
            $this->checkResult[] = '第 '.$this->currentLine.' 行，用户ID（'.$userId .'）不存在或者存管未开户或者存管状态异常。';
            return false;
        }
    }

    private function checkUserInvestmentExists($userId, $dealId) {
        $list = DealLoanRepayModel::instance()->getDealUnpaiedPrincipalByDealIdAndUserId($dealId, $userId);
        if (!$list) {
            $this->checkResult[] = '第 '.$this->currentLine. ' 行，用户编号（'.$userId .'）不存在对标的（'.$dealId.'）的投资记录。';
            return false;
        }
    }
}


<?php

use core\service\candy\CandyShopService;
use core\service\candy\CandyAccountService;
use libs\db\Db;
use core\service\O2OService;

/**
 * 信宝账户
 * @author: wangxiangshuo@ucfgroup.com
 */
class CandyAccountAction extends CommonAction
{

    public static $statusMap = array(
        'applyList' => array(
            '0' => '未审核',
            '1' => '已批准',
            '2' => '已拒绝',
        ),
        'creConvert' => array(
            '1' => '未操作',
            '2' => '成功',
            '3' => '失败',
        ),
    );

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 信宝-BUC总账
     */
    public function index()
    {
        $userId = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';
        $mobile = !empty($_REQUEST['mobile']) ? intval($_REQUEST['mobile']) : '';

        $model = M('candy_account', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('candy_account');
        $condition = "1";

        if (!empty($mobile)) {
            $userModel = M('user');
            $userInfo = $userModel->where("mobile='{$mobile}'")->find();
            $condition .= " AND user_id='{$userInfo['id']}'";
        }

        if (!empty($userId)) {
            $condition .= " AND user_id = {$userId}";
        }

        $this->_list($model, $condition);

        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
            $tempUser = MI('User')->where("id='{$value['user_id']}'")->find();
            $list[$key]['real_name'] = $tempUser['real_name'];
        }

        $this->assign('list', $list);
        $this->assign('userId', $userId);
        $this->assign('mobile', $mobile);
        $this->display();
    }

    /**
     * 信宝账户日志
     */
    public function candyAccountLog()
    {
        $userId = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $type = isset($_REQUEST['type']) ? addslashes(trim($_REQUEST['type'])) : '';

        $model = M('candy_account_log', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('candy_account_log');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = {$userId}";
        }
        if (!empty($type)) {
            $condition .= " AND type = '{$type}'";
        }

        $this->_list($model, $condition);
        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }

        $this->assign('list', $list);
        $this->assign('user_id', $userId);
        $this->assign('type', $type);

        $this->display();
    }
    /**
     * BUC账户日志
     */
    public function bucAccountLog()
    {
        $userId = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';
        $type = isset($_REQUEST['type']) ? addslashes(trim($_REQUEST['type'])) : '';

        $model = M('buc_account_log', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('buc_account_log');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = {$userId}";
        }
        if (!empty($type)) {
            $condition .= " AND type = '{$type}'";
        }

        $this->_list($model, $condition);

        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }
        $this->assign('list', $list);
        $this->assign('user_id', $userId);
        $this->assign('type', $type);

        $this->display();
    }
    /**
     * BUC提币日志
     */
    public function bucWithdraw()
    {
        $userId = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';
        $bucTradeNo = isset($_REQUEST['buc_trade_no']) ? intval($_REQUEST['buc_trade_no']) : '';
        $address = isset($_REQUEST['address']) ? addslashes(trim($_REQUEST['address'])) : '';

        $model = M('buc_withdraw_order', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('buc_withdraw_order');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = {$userId}";
        }
        if (!empty($bucTradeNo)) {
            $condition .= " AND buc_trade_no = {$bucTradeNo}";
        }
        if (!empty($address)) {
            $condition .= " AND address = {$address}";
        }

        $this->_list($model, $condition);
        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['finish_time'] = date('Y-m-d H:i:s', $value['finish_time']);
        }

        $this->assign('list', $list);
        $this->assign('user_id', $userId);
        $this->assign('buc_trade_no', $bucTradeNo);
        $this->assign('address', $address);

        $this->display();

    }
    /**
     * 信宝生产日志
     */
    public function candyProduce()
    {
        $batchNo = isset($_REQUEST['batch_no']) ? addslashes(trim($_REQUEST['batch_no'])) : '';

        $model = M('candy_produce_log', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('candy_produce_log');

        $condition = "1";
        if (!empty($batchNo)) {
            $condition .= " AND batch_no = '{$batchNo}'";
        }
        if (!empty($model)) {
            $this->_list($model, $condition);
        }

        $list = $this->get('list');
        foreach ($list as $key => $value) {
            $list[$key]['starttime'] = date('Y-m-d H:i:s', $value['starttime']);
            $list[$key]['endtime'] = date('Y-m-d H:i:s', $value['endtime']);
            $list[$key]['activity_total'] = number_format($value['activity_total']);
            $list[$key]['amount_total'] = number_format($value['amount_total'], 3);

        }

        $this->assign('list', $list);
        $this->display();
    }
    /**
     * 信力日志
     */
    public function candyActivity()
    {
        $userId = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';
        $token = isset($_REQUEST['token']) ? addslashes(trim($_REQUEST['token'])) : '';
        $sourceType = isset($_REQUEST['source_type']) ? addslashes(trim($_REQUEST['source_type'])) : '';

        $tempList = core\service\candy\CandyActivityService::$sourceTypeConf;
        foreach ($tempList as $key => $value) {
            $tempList[$key] = $value['key'];
        }

        $type = array_search($sourceType, $tempList);

        $model = M('activity_log', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('activity_log');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = {$userId}";
        }
        if (!empty($type)) {
            $condition .= " AND source_type = {$type}";
        }
        if (!empty($token)) {
            $condition .= " AND token = '{$token}'";
        }

        $this->_setPageEnable(false);
        $this->_list($model, $condition);

        $list = $this->get('list');
        foreach ($list as $key => $value) {
            $list[$key]['source_type'] = core\service\candy\CandyActivityService::$sourceTypeConf[$value['source_type']]['key'];
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }

        $this->assign('list', $list);
        $this->assign('user_id', $userId);
        $this->assign('type', $sourceType);
        $this->assign('token', $token);

        $this->display();

    }

    /**
     * 信宝申请添加表单
     */
    public function applyAdd()
    {
        $this->display();
    }

    /**
     * 信宝申请保存
     */
    public function applySave()
    {
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount = isset($_POST['amount']) ? addslashes(trim($_POST['amount'])) : 0;
        $bucAmount = isset($_POST['buc_amount']) ? addslashes(trim($_POST['buc_amount'])) : 0;
        if (empty($userId) || (empty($amount) && empty($bucAmount))) {
            return $this->error('用户名填写错误，或数额填写错误');
        }
        $type = isset($_POST['type']) ? addslashes(trim($_POST['type'])) : '';
        $note = isset($_POST['note']) ? addslashes(trim($_POST['note'])) : '';
        $creator = es_session::get(md5(conf("AUTH_KEY")))['adm_name'];

        $this->applyChangeCandy($userId, $amount, $bucAmount, $type, $note, $creator);

        $this->success('操作成功', 0, '?m=CandyAccount&a=applyList');
    }

    /**
     * 审核申请列表
     */
    public function applyList()
    {
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if (!isset(self::$statusMap['applyList'][$status])) {
            $status = 0;
        }

        $where = "status='{$status}'";
        $list = Db::getInstance('candy')->getAll("SELECT * From candy_change_apply WHERE {$where} ORDER BY id DESC LIMIT 500");

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['audit_time'] = $value['audit_time'] ? date('Y-m-d H:i:s', $value['audit_time']) : '-';
            $list[$key]['status'] = self::$statusMap['applyList'][$value['status']];
            $list[$key]['amount'] = number_format($value['amount'], 3);
            $list[$key]['buc_amount'] = number_format($value['buc_amount'], 6);
        }

        $this->assign('list', $list);
        $this->assign('status', $status);
        $this->display();
    }

    /**
     * 审核
     */
    public function applyAudit()
    {
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (empty($id) || empty($status)) {
            return $this->error('参数错误');
        }

        $result = Db::getInstance('candy')->getRow("SELECT * FROM candy_change_apply WHERE id='{$id}' AND status=0");
        if (empty($result)) {
            return $this->error('记录不存在，或者已审核');
        }

        $userId = $result['user_id'];
        $amount = $result['amount'];
        $type = $result['type'];
        $note = $result['note'];
        $bucAmount = $result['buc_amount'];

        Db::getInstance('candy')->startTrans();
        try {
            //更新信宝的申请状态
            $this->updateApply($id, $status);
            //更新信宝账户及记录
            if ($status == 1) {
                if (!empty($amount)) {
                    $changeCandy = new core\service\candy\CandyAccountService();
                    $changeCandy->changeAmount($userId, $amount, $type, $note);
                }
                if (!empty($bucAmount)) {
                    $changeActivity = new core\service\candy\CandyBucService();
                    $changeActivity->changeBucAmount($userId, $bucAmount, $type, $note);
                }
            }
            Db::getInstance('candy')->commit();

            $this->success('操作成功', 0, '?m=CandyAccount&a=applyList');
        } catch (\Exception $e) {
            Db::getInstance('candy')->rollback();
            $this->error($e->getMessage());
        }

    }

    /**
     * 插入申请变更信宝/BUC额度
     */
    private function applyChangeCandy($userId, $amount, $bucAmount, $type, $note, $creator)
    {
        $db = Db::getInstance('candy');
        $insertId = $db->insert('candy_change_apply', array(
            'user_id' => $userId,
            'amount' => $amount,
            'buc_amount' => $bucAmount,
            'type' => $type,
            'status' => 0,
            'note' => $note,
            'creator' => $creator,
            'create_time' => time(),
        ));

        if (empty($insertId)) {
            throw new \Exception('添加信宝申请失败');
        }
    }

    /**
     * 变更信宝审核状态
     */
    private function updateApply($id, $status)
    {
        $where = "`id`='{$id}'";
        $data = array(
            'status' => $status,
            'audit_time' => time(),
            'update_time' => time(),
            'auditor' => es_session::get(md5(conf("AUTH_KEY")))['adm_name'],
        );

        $db = Db::getInstance('candy');
        $db->update('candy_change_apply', $data, $where);
        if ($db->affected_rows() < 1) {
            throw new \Exception('审核状态更新失败');
        }
    }


    /**
     * 批量插入申请变更信宝
     */
    public function applyMultiSave()
    {
        $fileName = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
        if (empty($fileName)) {
            $this->error('请选择要导入的CSV文件');
        }
        $handle = fopen($fileName, 'r');
        $result = $this->analyzCsv($handle);
        $lengthResult = count($result);

        //将CSV文件中的数据逐条插入到申请表里
        $creator = es_session::get(md5(conf("AUTH_KEY")))['adm_name'];
        Db::getInstance('candy')->startTrans();
        try {
            for ($i = 0; $i < $lengthResult; $i++) {
                $userId = $result[$i]['user_id'];
                $amount = $result[$i]['amount'];
                $bucAmount = $result[$i]['buc_amount'];
                $type = $result[$i]['type'];
                $note = $result[$i]['note'];
                $this->applyChangeCandy($userId, $amount, $bucAmount, $type, $note, $creator);
            }
            Db::getInstance('candy')->commit();
            $this->success('操作成功', 0, '?m=CandyAccount&a=applyList');
        } catch (\Exception $e) {
            Db::getInstance('candy')->rollback();
            $this->error($e->getMessage());
        }

    }

    /**
     * 处理上传的CSV文件
     */
    private function analyzCsv($handle)
    {
        $dataList = array();
        fgetcsv($handle);
        while ($tableData = fgetcsv($handle)) {
            if (count($tableData) !=  5) {
                $this->error('数据列不匹配，请重新填写');
            }

            list($userId, $amount, $bucAmount, $type, $note) = $tableData;
            $userId = isset($userId) ? intval($userId) : 0;

            if (empty($userId)) {
                $this->error('用户ID不能为空');
            }

            $amount = isset($amount) ? number_format($amount, 3) : '';
            $bucAmount = isset($bucAmount) ? number_format($bucAmount, 6) : '';

            if (empty($amount) && empty($bucAmount)) {
                $this->error('金额输入不正确，请重新输入');
            }

            if (empty($type)) {
                $this->error('类型不能为空，请重新填写');
            }

            $type = iconv('gb2312', 'utf-8', addslashes(trim($type)));
            $note = isset($note) ? iconv('gb2312', 'utf-8', addslashes(trim($note))) : '';

            $dataList[] = array('user_id' => $userId, 'amount' => $amount, 'buc_amount' => $bucAmount, 'type' => $type, 'note' => $note);
        }

        $lengthResult = count($dataList);
        if ($lengthResult <= 0) {
            $this->error('没有任何数据');
        }

        if ($lengthResult > 1000) {
            $this->error('数据多于1000条，请分表填写');
        }

        return $dataList;
    }

    /**
     * CRE兑换信宝订单记录
     */
    public function creConvert()
    {
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';

        $condition = '1';
        if (!empty($userId)) {
            $condition .= " AND user_id='{$userId}'";
        }
        $model = M('cre_convert_order', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('cre_convert_order');

        $this->_list($model, $condition);
        $list = $this->get('list');

        foreach ($list as $key => $val) {
            $list[$key]['create_time'] = date("Y-m-d", $val['create_time']);
            $list[$key]['status'] = self::$statusMap['creConvert'][$val['status']];
            if (!empty($val['update_time'])) {
                $list[$key]['update_time'] = date("Y-m-d", $val['update_time']);
            } else {
                $list[$key]['update_time'] = "-";
            }
        }

        $this->assign('list', $list);
        $this->assign('user_id', $userId);

        $this->display();
    }

    /**
     * CRE每日库存记录
     */
    public function creDailyStock()
    {
        $model = M('cre_daily_stock', 'Model', false, 'candy', 'master');
        $model->setTrueTableName("cre_daily_stock");

        $this->_list($model);
        $list = $this->get('list');

        foreach ($list as $key => $val) {
            $list[$key]['create_time'] = date("Y-m-d", $val['create_time']);
            if (!empty($val['update_time'])) {
                $list[$key]['update_time'] = date("Y-m-d", $val['update_time']);
            } else {
                $list[$key]['update_time'] = "-";
            }
        }

        $this->assign('list', $list);
        $this->display();
    }

}

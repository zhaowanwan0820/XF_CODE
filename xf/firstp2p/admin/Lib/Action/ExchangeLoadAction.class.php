<?php

/**
 *  线下交易所投资明细相关业务
 */
use libs\utils\Logger;
use core\service\CouponService;

class ExchangeLoadAction extends CommonAction {

    /**
     * 投资明细
     */
    public function index() {
        $batchId = intval($_REQUEST['batch_id']);

        $batchInfo = M('ExchangeBatch')->find($batchId);
        if (!$batchInfo) {
            return $this->error("找不到批次信息!");
        }

        $projectInfo = M('ExchangeProject')->find($batchInfo['pro_id']);
        if (!$projectInfo) {
            return $this->error("找不到项目信息!");
        }

        $this->_list(M('ExchangeLoad'), ['batch_id' => $batchId], 'id', true);

        $this->assign("batchInfo", $batchInfo);
        $this->assign("projectInfo", $projectInfo);

        return $this->display();
    }

    private function _checkLine(&$array) {
        $validLine = $blankLine = true;

        foreach ($array as $index => &$value) {
            $value = iconv('GBK', 'UTF-8', $value);
            $value = mb_ereg_replace('^(　| )+', '', mb_ereg_replace('(　| )+$', '', $value));
            strlen($value) > 0 ? $blankLine = false : (($index != 3 && $index != 6) && $validLine = false);
        }

        return ['blankLine' => $blankLine, 'validLine' => $validLine];
    }

    private function _checkMomeyAndInviteCode($params) {
        $projectInfo = M('ExchangeProject')->find($params['project_id']);
        if (!$projectInfo) {
            $this->error("找不到项目信息!");
            return false;
        }

        $condition['pro_id'] = ['eq',  $params['project_id']];
        $condition['is_ok'] = ['eq',  1];
        $condition['id'] = ['neq', $params['batch_id']];
        $batchMoney = M('ExchangeBatch')->where($condition)->sum('amount'); //分
        if (false === $batchMoney) {
            $this->error("查询项目批次金额失败!");
            return false;
        }

        if (($params['total_money'] + $batchMoney) > $projectInfo['amount']) {
            $this->error("已超出项目借款金额，请检查!");
            return false;
        }

        $service = new CouponService();
        $inviteCodes = array_unique($params['invite_codes']);
        foreach ($inviteCodes as $code) {
            $aCheckRet = $service->checkCoupon($code);
            if (!$aCheckRet || strtoupper($aCheckRet['short_alias']) != strtoupper($code)) {
                $this->error(sprintf("邀请码 %s 错误，请检查!", $code));
                return false;
            }
        }

        return true;
    }

    private function _checkUpload($projectId, $batchId) {
        if (!isset($_FILES['load_list']) || $_FILES['load_list']['error']) {
            $this->error("上传文件失败!");
            return false;
        }

        $file = $_FILES['load_list'];
        if (!($file['type'] == 'application/vnd.ms-excel' && substr(strtolower($file['name']), -4) == '.csv')) {
            $this->error("必须上传csv格式文件！");
            return false;
        }

        $totalMoney  = $count = 0;
        $inviteCodes = $rows  = [];
        $handle = fopen($file['tmp_name'], 'r');
        $title  = fgetcsv($handle);
        while (!feof($handle)) {
            $row = fgetcsv($handle);
            $res = $this->_checkLine($row);
            if ($res['blankLine']) {
                continue;
            }
            if (!$res['validLine']) {
                $this->error("上传失败, 上传文件存在空白字段！");
                return false;
            }

            $payMoney = intval(bcmul(100, $row[11])); //分
            if ($payMoney <= 0) {
                $this->error("投资应该大于0！");
                return false;
            }

            $count = $count + 1;
            $inviteCodes[] = $row[9];
            $totalMoney = $totalMoney + $payMoney;

            $row[11] = $payMoney;
            $rows[] = $row;
        }

        if ($count > 1000) {
            $this->error("上传失败, 上传文件超过1000行");
            return false;
        }

        $params = [
            'project_id'   => $projectId,
            'batch_id'     => $batchId,
            'total_money'  => $totalMoney, //分
            'invite_codes' => $inviteCodes
        ];
        if (!$this->_checkMomeyAndInviteCode($params)) {
            Logger::warn("上传投资明细金额或者邀请码存在错误!");
            return false;
        }

        return $rows;
    }

    /**
     * 上次投资明细
     */
    public function upload() {
        $batchId = intval($_REQUEST['batch_id']);
        $batchInfo = M('ExchangeBatch')->find($batchId);
        if (!$batchInfo) {
            return $this->error("找不到批次信息!");
        }
        if ($batchInfo['deal_status'] != 1) {
            return $this->error("非进行中批次，不能进行上传操作!");
        }

        $rows = $this->_checkUpload($batchInfo['pro_id'], $batchInfo['id']);
        if (!is_array($rows)) {
            Logger::warn("此次上次文件存在问题");
            return false;
        }

        $model = M('ExchangeLoad');
        $model->startTrans();
        $res = $model->execute("DELETE FROM firstp2p_exchange_load WHERE batch_id = " . $batchId);
        if (false === $res) {
            $model->rollback();
            return $this->error("删除上次上传数据失败!");
        }

        $fields = [
            'real_name', 'certificate_type', 'certificate_no', 'mobile', 'bank_no', 'bank_name',
            'cnaps_no', 'bank_province', 'bank_city', 'invite_code', 'pay_time', 'pay_money',
        ];
        $fixInfo  = [
            'status' => 1,
            'update_time' => time(),
            'create_time' => time(),
            'project_id'  => $batchInfo['pro_id'],
            'batch_id'    => $batchInfo['id']
        ];

        $totalMoney = 0;
        foreach ($rows as $row) {
            $data = array_combine($fields, $row);
            $data['pay_time'] = strtotime($data['pay_time']);
            $data['pay_money'] = intval($data['pay_money']);
            $data = array_merge($data, $fixInfo);

            $res = $model->add($data);
            if (!$res) {
                $model->rollback();
                return $this->error("数据库写入失败!");
            }
            $totalMoney = $totalMoney + $data['pay_money'];
        }

        $res = M('ExchangeBatch')->execute(sprintf("UPDATE firstp2p_exchange_batch SET amount = %d WHERE id = %d", $totalMoney, $batchInfo['id']));
        if (false === $res) {
            $model->rollback();
            return $this->error("数据库写入失败!");
        }
        $bRet = D('OffexchangeBatch')->updateBatchFee($batchInfo['id']);
        if(!$bRet){
            $model->rollback();
            return $this->error("更新批次费用失败!");
        }

        $model->commit();
        save_log("上传批次信息成功", 1, [], $fixInfo);

        return $this->success("上传成功!");
    }

    /**
     * 单条投资明细作废
     */
    public function del() {
        $model = M('ExchangeLoad');

        $loadId = intval($_REQUEST['load_id']);
        $loadInfo = $model->find($loadId);
        if (empty($loadInfo)) {
            return $this->error("找不到操作的数据!");
        }
        if ($loadInfo['status'] == 2) {
            return $this->success("操作成功");
        }

        $batchInfo = M('ExchangeBatch')->find($loadInfo['batch_id']);
        if (!$batchInfo) {
            return $this->error("找不到批次信息!");
        }
        if (1 != $batchInfo['deal_status']) {
            return $this->error("批次在进行中状态下才能作废!");
        }

        $nloadInfo = $loadInfo;
        $nloadInfo['status'] = 2;
        $nloadInfo['update_time'] = time();

        $model->startTrans();
        $saveRes = $model->save($nloadInfo);
        if (!$saveRes) {
            $model->rollback();
            return $this->error("操作失败, 请重试");
        }

        $res = M('ExchangeBatch')->execute(sprintf("UPDATE firstp2p_exchange_batch SET amount = amount - %d WHERE id = %d", $loadInfo['pay_money'], $loadInfo['batch_id']));
        if (false === $res) {
            $model->rollback();
            return $this->error("操作失败, 请重试");
        }
        $bRet = D('OffexchangeBatch')->updateBatchFee($batchInfo['id']);
        if(!$bRet){
            $model->rollback();
            return $this->error("更新批次费用失败!");
        }

        save_log("投资明细作废成功", 1, $loadInfo, $nloadInfo);
        $model->commit();

        return $this->success("操作成功");
    }

    public function show() {
        $loadId = intval($_REQUEST['load_id']);
        $loadInfo = M('ExchangeLoad')->find($loadId);
        if (!$loadInfo) {
            return $this->error("找不到投资明细, 请重试");
        }

        $this->assign('loadInfo', $loadInfo);
        return $this->display();
    }

    public function save() {
        $loadId = intval($_REQUEST['id']);
        $loadInfo = M('ExchangeLoad')->find($loadId);
        if (!$loadInfo) {
            return $this->error("找不到投资明细, 请重试");
        }

        unset($_REQUEST['pay_money']); // 不能修改
        $nloadInfo = array_merge($loadInfo, $_REQUEST);
        $nloadInfo['pay_time']  = strtotime($nloadInfo['pay_time']);
        $nloadInfo['update_time'] = time();

        $fields = ['m', 'a', 'status', 'project_id', 'batch_id', 'create_time'];
        foreach ($fields as $field) {
            unset($nloadInfo[$field]);
        }

        if ($loadInfo['invite_code'] != $nloadInfo['invite_code']) {
            $service = new CouponService();
            if (!$service->checkCoupon($nloadInfo['invite_code'])) {
                return $this->error("邀请码输入错误, 请重试");
            }
        }

        $res = M('ExchangeLoad')->save($nloadInfo);
        if (!$res) {
            return $this->error("编辑投资明细失败, 请重试");
        }

        save_log("编辑投资明细成功", 1, $loadInfo, $nloadInfo);
        return $this->success("操作成功", 0, u("ExchangeLoad/index", ["batch_id" => $loadInfo['batch_id']]));
    }

}

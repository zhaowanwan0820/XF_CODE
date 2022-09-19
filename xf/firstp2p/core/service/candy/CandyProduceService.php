<?php

namespace core\service\candy;

use libs\db\Db;
use core\service\candy\CandyAccountService;

/**
 * 积分生产
 */
class CandyProduceService
{

    // 第一年积分发行总量 (1亿)
    const FIRST_YEAR_CANDY_TOTAL = 100000000;

    // 进行中
    const PRODUCE_STATUS_INHAND = 0;

    // 成功
    const PRODUCE_STATUS_SUCCESS = 1;

    // 用户记录已生成
    const PRODUCE_USER_LOG_SUCCESS = -1;

    /**
     * 是否已完成
     */
    public function isBatchProduceDone($batchNo)
    {
        $data = Db::getInstance('candy')->getRow("SELECT * FROM candy_produce_log WHERE batch_no='{$batchNo}'");

        if (isset($data['status']) && $data['status'] == self::PRODUCE_STATUS_SUCCESS) {
            return true;
        }

        return false;
    }

    /**
     * 生产开始
     */
    public function batchProduceStart($batchNo, $userCount, $activityTotal, $amountTotal)
    {
        $data = Db::getInstance('candy')->getRow("SELECT * FROM candy_produce_log WHERE batch_no='{$batchNo}'");

        // 已完成
        if (isset($data['status']) && $data['status'] == self::PRODUCE_STATUS_SUCCESS) {
            throw new \Exception('已完成');
        }

        // 已有进行中记录，继续进行
        if (isset($data['status']) && $data['status'] == self::PRODUCE_STATUS_INHAND) {
            return true;
        }

        // 插入记录
        $data = array(
            'batch_no' => $batchNo,
            'user_count' => $userCount,
            'activity_total' => $activityTotal,
            'amount_total' => $amountTotal,
            'starttime' => time(),
        );
        Db::getInstance('candy')->insert('candy_produce_log', $data);

        return true;
    }

    /**
     * 生产完成
     */
    public function batchProduceFinish($batchNo)
    {
        // 插入记录
        $data = array(
            'status' => self::PRODUCE_STATUS_SUCCESS,
            'endtime' => time(),
        );
        $where = "batch_no='{$batchNo}'";
        Db::getInstance('candy')->update('candy_produce_log', $data, $where);

        return true;
    }

    /**
     * 是否已存在用户发放记录
     */
    public function existsProduceUserLog($userId, $batchNo)
    {
        $id = Db::getInstance('candy')->getOne("SELECT id FROM candy_produce_user_log WHERE user_id='{$userId}' AND batch_no='{$batchNo}'");
        if (empty($id)) {
            return false;
        }

        return true;
    }

    /**
     * 创建用户发放记录
     */
    public function createProduceUserLog($userId, $batchNo, $activity, $amount)
    {
        $data = array(
            'batch_no' => $batchNo,
            'user_id' => $userId,
            'activity' => $activity,
            'amount' => $amount,
            'create_time' => time(),
        );
        Db::getInstance('candy')->insert('candy_produce_user_log', $data);

        return true;
    }

    /**
     * 根据时间计算积分总数
     */
    public function calcCandyTotalByTime($time)
    {
        // 查询起始时间
        $result = Db::getInstance('candy')->getRow("SELECT * FROM candy_produce_log ORDER BY id ASC LIMIT 1");
        $firstday = isset($result['create_time']) ? $result['create_time'] : time();

        // 计算已过年数
        $seconds = strtotime(date('Ymd', $time)) - strtotime(date('Ymd', $firstday));
        $years = intval($seconds / 86400 / 365);
        if ($years < 0) {
            $years = 0;
        }

        return bcadd(pow(0.5, $years) * self::FIRST_YEAR_CANDY_TOTAL / 365, 0, CandyAccountService::AMOUNT_DECIMALS);
    }

    /**
     * 估算用户当天的积分
     */
    public function calcUserCandyToday($userActivity, $totalActivity)
    {
        $candyTotal = $this->calcCandyTotalByTime(time());
        return $this->calcUserCandy($userActivity, $totalActivity, $candyTotal);
    }

    /**
     * 计算用户积分
     */
    public function calcUserCandy($userActivity, $totalActivity, $candyTotal)
    {
        return bcadd(($userActivity / $totalActivity) * $candyTotal, 0, CandyAccountService::AMOUNT_DECIMALS);
    }

}

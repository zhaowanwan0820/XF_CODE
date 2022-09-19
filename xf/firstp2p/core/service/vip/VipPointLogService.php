<?php

namespace core\service\vip;

use core\dao\vip\VipPointLogModel;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

/**
 * Class VipService
 */
class VipPointLogService
{
    /**
     * [expirePoint description]
     * @param  [type]  $userId [description]
     * @param  [type]  $date   [description]
     * @param  integer $point  [description]
     * @return [type]          [description]
     */
    public function expirePoint($userId, $point, $token, $sourceType, $sourceAmount, $info = '经验值过期')
    {
        $data = [];
        $data['user_id']       = intval($userId);
        $data['token']         = $token;
        $data['point']         = abs($point);
        $data['source_type']   = $sourceType;
        $data['source_amount'] = $sourceAmount;
        $data['info']          = $info;
        $data['status']        = VipEnum::VIP_POINT_EXPIRE;
        $data['source_weight'] = 1;
        if ($data['point'] <= 0) {
            return 0;
        }

        return VipPointLogModel::instance()->expirePoint($data);
    }

    /**
     * [acquirePoint description]
     * @param  [type]  $userId       [description]
     * @param  [type]  $point        [description]
     * @param  [type]  $sourceType   [description]
     * @param  [type]  $sourceId     [description]
     * @param  string  $info         [description]
     * @param  string  $token        [description]
     * @param  integer $sourceAmount [description]
     * @param  integer $sourceWeight [description]
     * @param  integer $expiredDay   [description]
     * @return [type]                [description]
     */
    public function acquirePoint($userId, $point, $sourceType, $sourceId, $info = '', $token = '', $sourceAmount = 0, $sourceWeight = 0, $countMonth = 12, $logId = 0, $createTime=0)
    {
        $data['user_id']       = $userId;
        $data['point']         = $point;
        $data['token']         = $token ?: sprintf('%s_%s_%s', $userId, $sourceType, $sourceId);
        $data['source_type']   = $sourceType;
        $data['source_id']     = $sourceId;
        $data['info']          = $info;
        $data['source_amount'] = $sourceAmount;
        $data['source_weight'] = $sourceWeight;
        $data['create_time']   = $createTime ? : time();
        $data['expire_time']   = strtotime(sprintf('%s +%s month', date("Y-m-01"), $countMonth + 1)) - 1;
        if ($logId > 0) {
            $data['id'] = $logId;
        }

        return VipPointLogModel::instance()->acquirePoint($data);
    }

    /**
     * [getPointByPage description]
     * @param  [type]  $userId [description]
     * @param  integer $page   [description]
     * @param  integer $count  [description]
     * @return [type]          [description]
     */
    public function getPointByPage($userId, $page = 1, $count = 10)
    {
        if (empty($userId)) {
            return [];
        }
        $page = $page > 1 ? $page : 1;
        return VipPointLogModel::instance()->getPointByPage($userId, $page, $count);
    }

    /**
     * [getFormatPointByPage description]
     * @param  [type]  $userId [description]
     * @param  integer $page   [description]
     * @param  integer $count  [description]
     * @return [type]          [description]
     */
    public function getFormatPointByPage($userId, $page = 1, $count = 10)
    {
        $list = $this->getPointByPage($userId, $page, $count);
        $result = array();
        if ($list) {
            foreach($list as $item) {
                $tmp = array();
                $tmp['point'] = ($item['status'] == VipEnum::VIP_POINT_ADD) ? '+'.$item['point'] : '-'.$item['point'];
                if ($item['source_type'] == VipEnum::VIP_SOURCE_VALUE_INIT) {
                    $tmp['info'] = $item['info'];
                } else {
                    $tmp['info'] = VipEnum::$vipSourceDesc[VipEnum::$vipSourceMapToAlias[$item['source_type']]] ? : 'P2P出借';
                }
                $tmp['date'] = date('Y-m-d', $item['create_time']);
                $result[] = $tmp;
            }
        }
        return $result;
    }

    /**
     * [getValidPoint description]
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getValidPoint($userId)
    {
        return VipPointLogModel::instance()->getValidPoint($userId);
    }

    /**
     * [getSoonExpirePoint description]
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getSoonExpirePoint($userId)
    {
        list($startTime, $endTime) = $this->getMonthStartAndEndTimestamp();

        return VipPointLogModel::instance()->getPointByExpireTime($userId, $startTime, $endTime);
    }

    /**
     * [getThisMonthAcquirePoint description]
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getThisMonthAcquirePoint($userId)
    {
        list($startTime, $endTime) = $this->getMonthStartAndEndTimestamp();

        return VipPointLogModel::instance()->getPointByCreateTime($userId, $startTime, $endTime);

    }

    /**
     * [getLastMonthExpiredPoint description]
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getLastMonthExpiredPoint($userId)
    {
        list($startTime, $endTime) = $this->getMonthStartAndEndTimestamp(date("Y-m-d", strtotime('-1 month')));

        return VipPointLogModel::instance()->getPointByExpireTime($userId, $startTime, $endTime);
    }

    /**
     * [getMonthStartAndEndTimestamp description]
     * @param  string $date [description]
     * @return [type]       [description]
     */
    private function getMonthStartAndEndTimestamp($date = '')
    {
        if (empty($date)) {
            $date = date('Y-m');
        }
        $startTime = strtotime(date("Y-m-01", strtotime($date)));
        $endTime = strtotime(sprintf('%s +%s month', date("Y-m-01", strtotime($date)), 1)) - 1;

        return [$startTime, $endTime];
    }

    /**
     * [getExpireToken description]
     * @param  [type] $userId [description]
     * @param  [type] $date   [description]
     * @return [type]         [description]
     */
    public function getExpireToken($userId, $date)
    {
        return sprintf('expire:%s:%s', $userId, date('Ym', strtotime($date)));
    }

    /**
     * [getExpiredListByPage description]
     * @param  [type] $date  [description]
     * @param  [type] $page  [description]
     * @param  [type] $count [description]
     * @return [type]        [description]
     */
    public function getExpiredListByPage($date, $page, $count)
    {
        list($startTime, $endTime) = $this->getMonthStartAndEndTimestamp($date);
        return VipPointLogModel::instance()->getExpiredList($startTime, $endTime, ($page - 1) * $count, $count);
    }

    /**
     * [getExpiredTotal description]
     * @param  [type] $date [description]
     * @return [type]       [description]
     */
    public function getExpiredTotal($date)
    {
        list($startTime, $endTime) = $this->getMonthStartAndEndTimestamp($date);
        return VipPointLogModel::instance()->getExpiredTotal($startTime, $endTime);
    }

}

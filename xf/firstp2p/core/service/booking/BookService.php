<?php

namespace core\service\booking;

use libs\db\Db;
use libs\utils\Logger;

class BookService {
    const STATUS_SUCCESS = 1;   // 预约成功
    const STATUS_CANCEL = 0;    // 预约取消

    /**
     * 场次状态
     */
    const SESSION_STATUS_INVALID = 0;   // 无效
    const SESSION_STATUS_NORMAL = 1;    // 可预约
    const SESSION_STATUS_FULL = 2;      // 已约满
    const SESSION_STATUS_EXPIRED = 3;   // 已过期

    const SESSION_BEIJING = 1;  // 北京
    const SESSION_SHANGHAI = 2; // 上海
    const SESSION_SHENZHEN = 3; // 深圳

    public static $CITYS = [
        self::SESSION_BEIJING => '北京',
        self::SESSION_SHANGHAI => '上海',
        self::SESSION_SHENZHEN => '深圳',
    ];

    /**
     * 获取全部有效的场次信息
     */
    public static function getSessions() {
        $db = Db::getInstance('firstp2p', 'slave');

        $sessions = array();
        $startTime = strtotime(date('Y-m-d', time()));
        $items = $db->getAll("SELECT * FROM `firstp2p_booking_session` WHERE `start_time`>={$startTime} AND `status`>0");
        foreach ($items as $item) {
            $city = $item['city'];
            $sessionId = $item['id'];
            $sessions[$sessionId] = [
                'startTime'=>date('Y/m/d H:i', $item['start_time']),
                'endTime'=>date('Y/m/d H:i', $item['end_time']),
                'status'=>$item['status'],
                'city'=>$city,
                'screen'=>$sessionId,
                'limitCount'=>$item['limit_count'],
                'sessionDesc'=>date('Y/m/d H:i', $item['start_time']) . ' ~ ' . date('H:i', $item['end_time'])
            ];
        }

        return $sessions;
    }

    /**
     * 获取单场场次信息 格式化场次信息
     *
     * @param $screen int 场次
     * @return array
     */
    public static function getSession($screen) {
        $db = Db::getInstance('firstp2p', 'slave');
        $screen = intval($screen);
        if (!$screen) {
            throw new \Exception('场次不存在');
        }

        $session = $db->getRow("SELECT * FROM `firstp2p_booking_session` WHERE `id`={$screen}");
        if (!$session) {
            throw new \Exception('场次不存在');
        }

        $result = array();
        $result['id'] = $session['id'];
        $result['startTime'] = date('Y/m/d H:i', $session['start_time']);
        $result['endTime'] = date('Y/m/d H:i', $session['end_time']);
        $result['status'] = $session['status'];

        $city = $session['city'];
        $result['city'] = $city;
        $result['cityName'] = isset(self::$CITYS[$city]) ? self::$CITYS[$city] : '未知';
        $result['screen'] = $screen;
        $result['limitCount'] = $session['limit_count'];
        $result['sessionDesc'] = date('Y/m/d H:i', $session['start_time']) . ' ~ ' . date('H:i', $session['end_time']);
        return $result;
    }

    /**
     * 预约场次
     * @param $userId int 用户id
     * @param $session int 预约场次
     * @param $remark string 备注
     * @return mixed，失败返回false
     */
    public static function book($userId, $session, $remark = '') {
        // 验证参数
        if (empty($userId) || empty($session)) {
            throw new \Exception('场次不存在');
        }

        // 查询是否已经预约
        $booking = self::myBooking($userId, 0, 1);
        if ($booking) {
            throw new \Exception('不能重复预约');
        }

        // 时间判断
        $sessionInfo = self::getSession($session);
        if (self::isExpiredSession($session)) {
            throw new \Exception('本场次已过期, 请预约其他场次');
        }

        // 单场人数限制
        $currentCount = self::getBookingCount($session);
        if ($sessionInfo['limitCount'] > 0 && $currentCount >= $sessionInfo['limitCount']) {
            throw new \Exception('本场次已满, 请预约其他场次');
        }

        $time = time();
        $session = intval($session);
        return Db::getInstance('firstp2p')->insert('firstp2p_booking', array(
            'user_id' => $userId,
            'reserved_session' => $session,
            'created_at' => $time,
            'modify_at' => $time,
            'reserved_at' => $time,
            'remark'=>$remark
        ));
    }

    /**
     * 取消预约
     * @param $userId int 用户id
     * @param $session int 预约场次
     * @return mixed，失败返回false
     */
    public static function cancel($userId, $session) {
        // 验证参数
        if (empty($userId) || empty($session)) {
            return false;
        }

        if (self::isExpiredSession($session)) {
            throw new \Exception('该场次已过期, 不能取消');
        }

        $session = intval($session);
        $data = array('status'=>self::STATUS_CANCEL);
        $where = "`user_id`='{$userId}' AND `reserved_session`={$session}";
        return Db::getInstance('firstp2p')->update('firstp2p_booking', $data, $where);
    }

    /**
     * 查询我的预约
     * @param $userId int 用户id
     * @return array
     */
    public static function myBooking($userId) {
        $successStatus = self::STATUS_SUCCESS;
        $sql = "SELECT * FROM `firstp2p_booking` WHERE `user_id`='{$userId}' AND `status`='{$successStatus}' ORDER BY `id`";
        $bookInfo = Db::getInstance('firstp2p')->getRow($sql);
        if (!$bookInfo) {
            return false;
        }

        $session = self::getSession($bookInfo['reserved_session']);
        $bookInfo = array_merge($bookInfo, $session);
        return $bookInfo;
    }

    /**
     * 查询预约数据
     * @param $userId int 用户id
     * @param $screen int 预约的场次
     * @return array
     */
    public static function bookingList($userId, $screen) {
        if (!$screen) {
            throw new \Exception('预约场次不能为空');
        }

        $successStatus = self::STATUS_SUCCESS;
        $sql = "SELECT * FROM `firstp2p_booking` WHERE `reserved_session`='{$screen}' AND `status`='{$successStatus}'";
        if ($userId) {
            $sql .= " AND `user_id`=''{$userId}' ";
        }

        return Db::getInstance('firstp2p')->getAll($sql);
    }

    /**
     * 获取场次当前人数
     *
     * @param $screen int 场次
     * @return int
     */
    public static function getBookingCount($screen) {
        if (!$screen) {
            throw new \Exception('预约场次不能为空');
        }

        $successStatus = self::STATUS_SUCCESS;
        $sql = "SELECT COUNT(id) as cnt FROM `firstp2p_booking` WHERE `reserved_session`='{$screen}' AND `status`='{$successStatus}'";
        $res = Db::getInstance('firstp2p')->getRow($sql);
        return empty($res['cnt']) ? 0 : intval($res['cnt']);
    }

    /**
     * 获取预约列表
     */
    public static function getBookingList() {
        $sessions = self::getSessions();

        $result = array();
        foreach (self::$CITYS as $city=>$name) {
            $result[$city] = array();
        }

        foreach ($sessions as $screen => $session) {
            $currentCount = self::getBookingCount($screen);
            if (self::isExpiredSession($screen) && !self::isTodaySession($screen)) {
                // 已过期的不展示
                continue;
            }

            if ($currentCount >= $session['limitCount']) {
                $session['status'] = self::SESSION_STATUS_FULL;
                $session['sessionDesc'] .= ' 【已约满】';
            } else if (self::isExpiredSession($screen) && self::isTodaySession($screen)) {
                $session['status'] = self::SESSION_STATUS_EXPIRED;
                $session['sessionDesc'] .= ' 【已过期】';
            }

            $city = $session['city'];
            $result[$city][$screen] = $session;
        }

        return $result;
    }

    /**
     * 判断是否是今天的场次
     *
     * @param $screen int 场次
     */
    public static function isTodaySession($screen) {
        $session = self::getSession($screen);
        $startTimestamp = strtotime($session['startTime']);
        if (!$startTimestamp) {
            throw new \Exception('时间配置错误, params: '.$session['startTime']);
        }

        return (date('Ymd', $startTimestamp) == date('Ymd')) ? true : false;
    }

    /**
     * 判断场次是否过期
     *
     * @param $screen int 场次
     */
    public static function isExpiredSession($screen) {
        $session = self::getSession($screen);
        $startTimestamp = strtotime($session['startTime']);
        if (!$startTimestamp) {
            throw new \Exception('时间配置错误, params: '.$session['startTime']);
        }

        return ($startTimestamp - time() < 30 * 60) ? true : false;
    }
}

<?php
namespace core\service\notice;

/**
 * 站内公告Service
 * @author longbo
 */
use core\dao\notice\NoticeModel;
use core\service\BaseService;

class NoticeService extends BaseService {
    const NOTICE_USER_KEY = 'notice_user_tips_cnt';

    /**
     * 公告列表
     * @params $user_id
     * @params $offset
     * @params $count
     * @return array
     */
    public function getList($user_id, $offset = 0, $count = 10, $limitShowTime = 0)
    {
        $this->updateUserNoticeTips($user_id, time());
        return NoticeModel::instance()->getList($offset, $count, intval($limitShowTime));
    }

    /**
     * 查出更新的公告,redis存了用户updateTime
     * @param int $user_id
     * @return int
     */
    public function getUserNoticeTips($user_id)
    {
        $count = 0;
        if ($user_id) {
            $rediscache = \SiteApp::init()->dataCache->getRedisInstance();
            $user_tip_time = $rediscache->hget(self::NOTICE_USER_KEY, strval($user_id));
            $uptime = empty($user_tip_time) ? time() - 30*24*3600 : intval($user_tip_time);
            $count = NoticeModel::instance()->getCount($uptime);
        }
        return $count;
    }

    /**
     * 更新用户公告updateTime
     * @param int $user_id
     * @param int $updateTime
     * @return int
     */
    public function updateUserNoticeTips($user_id, $updateTime = 0)
    {
        if ($user_id) {
            $rediscache = \SiteApp::init()->dataCache->getRedisInstance();
            $user_tips = $rediscache->hset(self::NOTICE_USER_KEY, strval($user_id), strval($updateTime));
        }
    }

}



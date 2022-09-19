<?php
/**
 * 缓存用户组和服务等级之间关系.
 *
 * @date 2018-06-26
 *
 * @author <wangzhen@ucfgroup.com>
 */
require_once dirname(__FILE__).'/../../app/init.php';
use libs\utils\Logger;
use core\service\UserCouponLevelService;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class CouponLevelGroupCache
{
    public function run()
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(' | ', array_merge($log_info, array('script start'))));
        $result = $this->getGroupLevel();
        //更新用户组和等级缓存
        $userCouponLevelService = new UserCouponLevelService();
        $userCouponLevelService->refreshGroupLevel($result);
        Logger::info(implode(' | ', array('CouponLevelGroupCache', 'run', ' script end')));
    }

    private function getGroupLevel()
    {
        $sql = "select DISTINCT new_coupon_level_id as level_id,group_id from firstp2p_user where new_coupon_level_id != '0' and new_coupon_level_id is not null and group_id != 0 ";
        $result = $this->multipleQuery($sql,500000);
        return $result;
    }

    private function multipleQuery($sql,$size){
        $data = array();
        $startId = 0;
        $endId = $this->getMaxUserId()+5000;
        while ($startId < $endId) {
            $tmpSql = $sql ." and id>= ".$startId .  " and id <" . ($startId+$size);
            $result = $GLOBALS['db']->get_slave()->getAll($tmpSql);
            $data = array_unique(array_merge($data,$result),SORT_REGULAR);
            $startId += $size;
        }
        return $data;
    }

    private function getMaxUserId(){
        $sql = 'select max(id) from firstp2p_user';
        return $GLOBALS['db']->get_slave()->getOne($sql);
    } 

}

$couponLevelGroupCache = new CouponLevelGroupCache();
$couponLevelGroupCache->run();
echo "CouponLevelGroupCache.run.end";

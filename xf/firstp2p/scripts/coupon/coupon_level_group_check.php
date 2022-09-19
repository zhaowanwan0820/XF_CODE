<?php

require_once dirname(__FILE__).'/../../app/init.php';

use libs\utils\Logger;

class CouponlevelGroupCheck
{
    public function run()
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(' | ', array_merge($log_info, array('script start'))));
        $groupLevels = $this->getGroupLevel();
        if (!empty($groupLevels)) {
            foreach ($groupLevels as $value) {
                $levelInfo = $this->getLevelById(intval($value['level_id']));
                $groupInfo = $this->getGroupById(intval($value['group_id']));
                if (empty($groupInfo)) {
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '用户组'.$value['group_id'].'不存在或者无效', json_encode($value));
                    continue;
                }

                if (empty($levelInfo)) {
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '服务等级'.$value['level_id'].'不存在或者无效', json_encode($value));
                    continue;
                }

                if (0 == $groupInfo['service_status']) {
                    continue;
                }

                $rebateRatio = $levelInfo['rebate_ratio'];
                $maxRackRatio = $groupInfo['max_pack_ratio'];
                if (1 == $groupInfo['is_related']) {
                    $agencyRebateRatio = bcsub($groupInfo['pack_ratio'], $levelInfo['rebate_ratio'], 5);
                } else {
                    $agencyRebateRatio = $groupInfo['pack_ratio'];
                }

                if (bccomp($agencyRebateRatio, 0, 5) == -1) {
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '机构系数不能小于0', json_encode($value));
                    continue;
                }

                if (1 == bccomp(bcadd($agencyRebateRatio, $rebateRatio, 5), $maxRackRatio, 5)) {
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '个人系数加机构系数不能大于打包比例系数上限', json_encode($value));
                    continue;
                }
            }
        }
        Logger::info(implode(' | ', array('CouponLevelGroupCache', 'run', ' script end')));
    }

    private function getGroupLevel()
    {
        $sql = "select DISTINCT new_coupon_level_id as level_id,group_id from firstp2p_user ";
        //$result = $GLOBALS['db']->get_slave()->getAll($sql);
        $result = $this->multipleQuery($sql,500000);
        return $result;
    }

    private function multipleQuery($sql,$size){
        $data = array();
        $startId = 0;
        $endId = $this->getMaxUserId()+5000;
        while ($startId < $endId) {
            $tmpSql = $sql ." where  id>= ".$startId .  " and id <" . ($startId+$size);
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

    private function getLevelById($id)
    {
        static $levelInfo = array();
        if (!isset($levelInfo[$id])) {
            $sql = 'select * from firstp2p_user_coupon_level where id ='.$id.' and is_effect = 1';
            $levelInfo[$id] = $GLOBALS['db']->get_slave()->getRow($sql);
        }
        return $levelInfo[$id];
    }

    private function getGroupById($id)
    {
        static $groupInfo = array();
        if (!isset($groupInfo[$id])) {
            $sql = 'select * from firstp2p_user_group where id ='.$id.' and is_effect = 1';
            $groupInfo[$id] = $GLOBALS['db']->get_slave()->getRow($sql);
        }
        return $groupInfo[$id];
    }
}

$couponlevelGroupCheck = new CouponlevelGroupCheck();
$couponlevelGroupCheck->run();
echo 'CouponlevelGroupCheck.run.end';
exit;

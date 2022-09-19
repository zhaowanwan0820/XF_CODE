<?php
namespace core\service\duotou;

use core\dao\duotou\DuotouEntranceModel;
use libs\utils\Site;

/**
 * 智多鑫入口的相关服务
 */
class DtEntranceService
{

    /**
     * 获取入口相关信息
     * @return array
     */
    public function getEntranceInfo($id, $site_id = 0)
    {
        $info = DuotouEntranceModel::instance()->find($id);
        if (empty($info)) {
            return [];
        }

        $info = $info->getRow();
        if ($site_id > 0) {
            //站点白名单，不在白名单返回空
            if ($info['site_ids_type'] == 1) {
                $site_ids = explode(',', $info['site_ids']);
                if (!in_array($site_id, $site_ids)) {
                    $info = [];
                }
            }

            //站点黑名单，在黑名单返回空
            if ($info['site_ids_type'] == 2) {
                $site_ids = explode(',', $info['site_ids']);
                if (in_array($site_id, $site_ids)) {
                    $info = [];
                }
            }
        }
        return $info;
    }

    /**
     * 获取站点对应入口列表
     */
    public function getEntranceList($site_id = 0, $lock_day=0, $min_invest_money=0)
    {
        if (empty($site_id)) {
            $site_id = Site::getId();
        }

        $cond = "`status`='1' AND `lock_day`>= {$lock_day} AND `min_invest_money` >= {$min_invest_money}  ORDER BY lock_day ASC";
        //全部入口
        $entrances = DuotouEntranceModel::instance()->findAll($cond);

        if (empty($entrances)) {
            return [];
        }

        $ret = [];
        foreach ($entrances as $entrance) {
            //全部站点
            if ($entrance['site_ids_type'] == 0) {
                $ret[] = $entrance->getRow();
            }

            //站点白名单
            if ($entrance['site_ids_type'] == 1) {
                $site_ids = explode(',', $entrance['site_ids']);
                if (in_array($site_id, $site_ids)) {
                    $ret[] = $entrance->getRow();
                }
            }

            //站点黑名单
            if ($entrance['site_ids_type'] == 2) {
                $site_ids = explode(',', $entrance['site_ids']);
                if (!in_array($site_id, $site_ids)) {
                    $ret[] = $entrance->getRow();
                }
            }
        }

        return $ret;
    }

    /**
     *获取锁定期限列表.
     */
    public static function getLockDayList()
    {
        return DuotouEntranceModel::instance()->getLockDayList();
    }
}

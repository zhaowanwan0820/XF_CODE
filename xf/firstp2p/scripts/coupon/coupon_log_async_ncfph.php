<?php
/**
 * 补刀脚本，补普惠邀请码当前30分钟之前到60分钟之间的数据.
 */
require_once dirname(__FILE__).'/../../app/init.php';
use libs\utils\Logger;
use core\service\CouponService;

set_time_limit(0);
ini_set('memory_limit', '1024M');
//error_reporting(E_ALL &~E_DEPRECATED);

class coupon_log_async
{
    public $db;
    private $dbname = 'ncfph';
    private $host = 'r-ncfph.mysql.ncfrds.com';
    private $username = 'ncfph_pro_r';
    private $password = '6734B7FE98e13613';
    private $port = '3306';
    private $dealLoadId = 0;
    private $startId = 0;
    private $endId = 0;
    private $size = 100;
    private $endTime = 0;
    private $maxId = 0;
    private $do = 0;
    const MAX_ID_OFFSET = 5000000;

    public function __construct()
    {
        $opts = getopt('s:e:d:c:');
        $this->db = new Pdo("mysql:dbname=$this->dbname;host=$this->host;port=$this->port", $this->username, $this->password, array(PDO::ATTR_PERSISTENT => true));
        $this->couponService = new CouponService('ncfph');
        $this->endTime = get_gmtime() - 1800; //当前时间往前推30分钟
        $this->maxId = $this->getMaxId();
        $this->startId = isset($opts['s']) && intval($opts['s']) ? intval($opts['s']) : $this->getStartId();
        $this->endId = isset($opts['e']) && intval($opts['e']) ? intval($opts['e']) : $this->getEndId();
        $this->do = isset($opts['c']) && intval($opts['c']) ? intval($opts['c']) : $this->do;
        $this->dealLoadId = isset($opts['d']) && intval($opts['d']) ? intval($opts['d']) : 0;
    }

    public function run()
    {
        if (0 != $this->dealLoadId) {
            $this->consume($this->dealLoadId);
        } else {
            while ($this->startId <= $this->endId) {
                $phLoadIds = $this->getDealLoadIds($this->startId, $this->size);
                $this->startId += $this->size;
                if (empty($phLoadIds)) {
                    break;
                }

                $loadIds = $this->getNotInCouponLogLoadIds($phLoadIds);
                if (!empty($loadIds)) {
                    foreach ($loadIds as $loadId) {
                        $this->consume($loadId);
                    }
                }
            }
        }
    }

    private function consume($loadId)
    {
        $dealLoad = $this->getDealLoadById($loadId);
        $coupon_fields = array();
        $coupon_fields['deal_id'] = $dealLoad['deal_id'];
        $coupon_fields['money'] = $dealLoad['money'];
        $coupon_fields['site_id'] = $dealLoad['site_id'];
        if (!$this->isDT($dealLoad['deal_id'])) {
            $log_info = array(__CLASS__, __FUNCTION__);
            $res = true;
            if (0 != $this->do) {
                $res = $this->couponService->consume($loadId, $dealLoad['short_alias'], $dealLoad['user_id'], $coupon_fields, CouponService::COUPON_SYNCHRONOUS);
            }
            $delay_time = get_gmtime() - $dealLoad['create_time'];
            if ($delay_time >= 1800) {//超过设置的时间没有进coupon_log发警告日志
                \libs\utils\Alarm::push('coupon_log_async', 'coupon_log_ncfph数据延迟超过'.ceil($delay_time / 60).'分钟,处理'.($res ? '成功' : '失败'), json_encode($dealLoad));
            }

            Logger::info(implode(' | ', array_merge($log_info, array('data:'.json_encode($dealLoad), 'deal_load 数据进入coupon_log_ncfph 监控脚本处理'.($res ? '成功' : '失败')))));
        }
    }

    private function getDealLoadIds($startId, $size)
    {
        $dealLoadIds = array();
        $sql = 'select id from firstp2p_deal_load where id >= '.$startId.' limit '.$size;
        $result = $this->getAll($sql);
        if (!empty($result)) {
            foreach ($result as $value) {
                $dealLoadIds[] = $value['id'];
            }
        }
        return $dealLoadIds;
    }

    private function getNotInCouponLogLoadIds($phLoadIds = array())
    {
        $dealLoadIds = array();
        $sql = 'select DISTINCT deal_load_id from firstp2p_coupon_log_ncfph where deal_load_id in ('.implode(',', $phLoadIds).')';
        $result = $GLOBALS['db']->get_slave()->getAll($sql, true);
        if (!empty($result)) {
            foreach ($result as $value) {
                $dealLoadIds[] = $value['deal_load_id'];
            }
        }

        return array_diff($phLoadIds, $dealLoadIds);
    }

    private function getDealLoadById($id)
    {
        $sql = ' select id,deal_id,money,user_id,short_alias,site_id,create_time from firstp2p_deal_load where id= '.$id;
        $dealLoad = $this->getRow($sql);
        return $dealLoad;
    }

    private function getMaxId()
    {
        $sql = ' select max(id) from firstp2p_deal_load ';
        $maxId = $this->getOne($sql);
        return $maxId;
    }

    private function getStartId()
    {
        $startTime = $this->endTime - 1800;
        $sql = 'select id from firstp2p_deal_load where id >= '.($this->maxId - self::MAX_ID_OFFSET).' and create_time <= '.$startTime.'  order by id desc limit 1';
        return  $this->getOne($sql);
    }

    private function getEndId()
    {
        $sql = 'select id from firstp2p_deal_load where id >= '.($this->maxId - self::MAX_ID_OFFSET).' and create_time <= '.$this->endTime.'  order by id desc limit 1';
        return  $this->getOne($sql);
    }

    private function isDT($dealId)
    {
        $sql = 'select dt.deal_id from firstp2p_deal_tag dt left join firstp2p_tag t on dt.tag_id = t.id where deal_id = '.$dealId." and t.tag_name = 'DEAL_DUOTOU' ";
        $result = $this->getOne($sql);
        return !empty($result);
    }

    private function getOne($sql)
    {
        $result = false;
        $stmt = $this->db->query($sql);
        if ($stmt) {
            $result = $stmt->fetchColumn();
        }
        return $result;
    }

    private function getRow($sql)
    {
        $result = false;
        $stmt = $this->db->query($sql);
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    private function getAll($sql)
    {
        $result = false;
        $stmt = $this->db->query($sql);
        if ($stmt) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }
}

$time = time();
$coupon_log_async = new coupon_log_async();
$coupon_log_async->run();
exit('普惠邀请码异步任务处理完毕,总共消耗'.(time() - $time).'秒');

<?php
/**
 * 告警服务类
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace libs\utils;

class Alarm
{

    const KEY_LIST = 'ALARM_QUEUE_';

    const KEY_LASTTIME = 'ALARM_LASTTIME_';

    /**
     * 添加告警
     */
    public static function push($type, $title, $data = '')
    {
        $backtrace = debug_backtrace();
        $data = array(
            'title' => $title,
            'content' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE),
            'logId' => \libs\utils\Logger::getLogId(),
            'serverIp' => gethostname(),
            'createtime' => time(),
            'file' => basename($backtrace[0]['file']).':'.$backtrace[0]['line'],
        );

        \libs\utils\Monitor::add('ALARM_'.$type);
        \libs\utils\Logger::error("AlarmPush. type:{$type}, title:{$title}, content:{$data['content']}, file:{$data['file']}");

        try {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if (!$redis) {
                throw new \Exception('getRedisInstance failed');
            }
            $redis->lPush(self::KEY_LIST.$type, json_encode($data));
        } catch (\Exception $e) {
            \libs\utils\Logger::error('AlarmPushFailed. '.$e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 获取告警
     */
    public static function pop($type, $count = 1)
    {
        $data = array();
        for ($i = 0; $i < $count; $i++)
        {
            $ret = \SiteApp::init()->dataCache->getRedisInstance()->rPop(self::KEY_LIST.$type);
            $data[] = json_decode($ret, true);
        }
        return $data;
    }

    /**
     * 告警队列中的告警个数
     */
    public static function count($type)
    {
        return \SiteApp::init()->dataCache->getRedisInstance()->lLen(self::KEY_LIST.$type);
    }

    /**
     * 最后发送告警时间
     */
    public static function getLastTime($type)
    {
        return \SiteApp::init()->dataCache->getRedisInstance()->get(self::KEY_LASTTIME.$type);
    }

    /**
     * 更新最后发送告警时间
     */
    public static function updateLastTime($type)
    {
        return \SiteApp::init()->dataCache->getRedisInstance()->set(self::KEY_LASTTIME.$type, time());
    }

}

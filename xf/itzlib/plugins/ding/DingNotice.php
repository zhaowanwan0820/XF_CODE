<?php

use DingNotice\DingTalk;

require __DIR__.'/vendor/autoload.php';

class DingNotice
{
    private static $sleep_cache_key = 'ding_sleep';

    public function __construct()
    {
        $config = include __DIR__.'/config/ding.php';
        $ding = new DingTalk($config);
        $this->ding = $ding;
    }

    public static function warning($title, $markdown)
    {
        $cache_key = Yii::app()->rcache->get(self::$sleep_cache_key);
        if ($cache_key && $cache_key >= 19) {
            return;
        }
        $dingNotice = new static();
        $result = $dingNotice->ding->markdown($title, $markdown);
        Yii::app()->rcache->set(self::$sleep_cache_key, $cache_key + 1, 60);

        return json_decode($result, true);
    }
}

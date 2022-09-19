<?php
namespace core\service\directPush;

abstract class DptStrategy
{
    public $fields= array();

    public $requestParams = array();

    public abstract function getParams($data);

    public abstract function buildConditonString($data);

    public static $repeatMap = array('一', '二', '三', '四' , '五', '六', '七', '八', '九', '十');

    public function generateTimes($data)
    {
        $count = $data['params_count'];
        $startTime = $data['start_time'];
        $continuous_interval = $data['params_interval'];
        if (!$data['is_continuous']) {
            $count = 1;
        }
        $times = array();
        for ($i = 0; $i < $count; $i++) {
            $times[] = array(
                'start_time' => $i * $continuous_interval * 86400 + $startTime,
                'name' => $data['name'] . (($count == 1) ? '' : '（'.self::$repeatMap[$i].'）'),
            );
        }

        return $times;
    }

}

<?php

namespace NCFGroup\Common\Library\Risk;

use NCFGroup\Common\Library\Risk\Huoyan;
use NCFGroup\Common\Library\Risk\RiskUtils;
use NCFGroup\Common\Library\Idworker;

class Risk
{
    private static $instance = null;
    private $param = array();

    public function __construct()
    {
        $this->param = [
            'device' => RiskUtils::getDevice(),
            'request_time' => RiskUtils::getMillisecond(),
        ];
    }

    /**
     * 获取一个单例
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 核查用户是否是正常用户
     */
    public function check($data)
    {
        return Huoyan::check($this->formatParams($data));
    }

    /**
     * 上报用户操作信息
     */
    public function report($data)
    {
        return Huoyan::report($this->formatParams($data));
    }

    /**
     * 格式化参数
     */
    private function formatParams($params)
    {
        foreach ($params['biz_info'] as $key => $value) {
            $params['biz_info'][$key] = strval($value);
        }
        $params['token'] = Idworker::instance()->getId();
        $this->param['ip'] = !empty($params['biz_info']['ip']) ? $params['biz_info']['ip'] : get_real_ip();
        $this->param['fingerprint'] = !empty($params['biz_info']['fingerprint']) ? $params['biz_info']['fingerprint']
: RiskUtils::getFinger();
        unset($params['biz_info']['ip']);
        unset($params['biz_info']['fingerprint']);
        return array_merge($this->param, $params);
    }

}

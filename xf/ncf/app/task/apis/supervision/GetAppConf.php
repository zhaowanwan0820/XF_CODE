<?php
/**
 * 获取普惠配置信息
 */
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\common\WXException;

class GetAppConf extends ApiAction
{
    public function invoke()
    {
        try {
            $param  = $this->getParam();
            $name = !empty($param['name']) ? addslashes($param['name']) : '';
            if (empty($name)) {
                throw new WXException('ERR_PARAM');
            }

            $value = app_conf($name);
            $this->json_data = !empty($value) ? $value : '';
        } catch(\Exception $e) {
            $this->show_exception($e);
        }
    }
}
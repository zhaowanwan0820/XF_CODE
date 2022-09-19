<?php

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\TraceSdk;

/**
 * 对于backend里面提供http+json的api的基类
 * 主要处理验签，获取参数，格式化结果等
 */
class ApiBackend extends \Phalcon\DI\Injectable {
    protected $params = [];

    public function __construct() {
        $di = getDI();
        $this->params = $di->get('requestBody');
        // 验证请求
        if (!Api::instance('rpc')->verify($this->params)) {
            TraceSdk::record(TraceSdk::LOG_TYPE_ERROR, __FILE__, __LINE__, 'rpc', '参数校验错误');
            throw new \NCFGroup\Common\Extensions\Exceptions\RpcApiException(
                '参数校验错误',
                400,
                array(
                    'dev' => 'That sign is not valid.',
                    'internalCode' => 'NF1001',
                    'more' => 'Check sign param.'
                )
            );
        }
    }

    /**
     * 获取参数
     * @param $key string 键值
     * @param $default string 默认值
     * @return mixed
     */
    public function getParam($key = '', $default = '') {
        if (empty($key)) {
            return $this->params;
        }

        return isset($this->params[$key]) ? $this->params[$key] : $default;
    }

    /**
     * 格式化输出结果
     * @param $data array 数据
     * @param $code int 错误码
     * @param $msg string 错误信息
     * @return array
     */
    protected function formatResult($data, $code = 0, $msg = 'success') {
        return array(
            'data'=>$data,
            'errorCode'=>$code,
            'errorMsg'=>$msg
        );
    }
}
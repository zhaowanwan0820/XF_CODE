<?php
namespace NCFGroup\Ptp\Apis;
/**
 * 获取user接口
 */

use \core\service\UserTokenService;

class TokenApi
{
    private $params = [];
    private $token = null;
    private $output = ['errorCode' => 0, 'errorMsg' => '', 'data' => []];

    private function _init()
    {
        $this->params = json_decode(file_get_contents('php://input'), true);
        if (empty($this->params['token'])) {
            throw new \Exception('token is required', 1);
        }
        $this->token = trim($this->params['token']);
    }

    public function getUserId()
    {
        try {
            $this->_init();
            $tokenService = new UserTokenService();
            $res = $tokenService->getUidByToken($this->token);
            if (empty($res)) {
                throw new \Exception('user is not exist', 2);
            }
            $this->output['data'] = $res;
        } catch (\Exception $e) {
            $this->output['errorCode'] = $e->getCode();
            $this->output['errorMsg'] = $e->getMessage();
        }
        return $this->output;
    }


}

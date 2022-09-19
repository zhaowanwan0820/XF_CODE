<?php
namespace web\controllers\address;

/**
 * User address List
 * @author longbo
 */
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;

class Index extends BaseAction
{

    public function init()
    {
        if (!$this->check_login()) parent::init();
    }

    public function invoke()
    {
        $user = $GLOBALS['user_info'];
        try {
            $result = $this->rpc->local(
                'AddressService\getList',
                array($user['id'])
            );
        } catch (\Exception $e) {
            Logger::error('Get Address Error:'.$e->getMessage());
            return $this->show_error('获取地址失败', '', 0, 0, url("account/setup"));
        }

        $tokenId = md5(microtime(true).rand(1, 9999));
        $token = mktoken($tokenId);
        $this->tpl->assign('tokenId', $tokenId);
        $this->tpl->assign('token', $token);

        $this->tpl->assign('addressList', $result);
    }
}

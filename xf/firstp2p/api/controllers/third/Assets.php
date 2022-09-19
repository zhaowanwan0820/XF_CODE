<?php
namespace api\controllers\third;
/**
 * 第三方资产
 * @author longbo
 */

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use libs\utils\Logger;
use core\service\partner\RequestService;

class Assets extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'platform' => array('filter' => 'required', 'message' => 'platform is null')
            );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        $platform = $data['platform'];

        $isOauthUser = $this->rpc->local(
                'ThirdpartyPtpService\isOauthUser',
                array($platform, $user['id'])
            );

        if (!$isOauthUser) {
            $this->setErr('ERR_USER_THIRD_ASSET');
        }

        $result = [];
        try {
            $result = RequestService::init($platform)
                ->setApi('user.asset')
                ->setPost(['open_id' => $user['id']])
                ->request();
            $this->json_data = $result;
        } catch (\Exception $e) {
            \libs\utils\Alarm::push('third_assets', "第三方资产获取失败", "{$platform}:".$e->getMessage());
            $this->setErr('ERR_USER_THIRD_ASSET');
        }
    }
}


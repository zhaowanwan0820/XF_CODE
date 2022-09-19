<?php
/**
 * 网信收银台-绑卡同步回调接口(从卡中心绑卡，返回到卡中心)
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use libs\utils\Logger;
use core\service\life\PaymentUserService;

class CardCenterBindReturn extends LifeBaseAction {

    const IS_H5 = true;

    public function init() {
        // 该页面是支付绑卡回调页面，不是APP请求的，所以header里没有Version字段，模版是在_v473下面的
        !isset($_SERVER['HTTP_VERSION']) && $_SERVER['HTTP_VERSION'] = 473;
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'data' => array('filter' => 'required', 'message' => 'data is required'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $data = $this->form->data;
        if (empty($data['data'])) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }

        $obj = new PaymentUserService();
        $response = $obj->bindCardReturn($data['data']);

        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, 'CardCenterBindReturn', APP, 'params：' . json_encode($data) . '，response：' . json_encode($response))));
        $this->tpl->assign('status', $response['status']);
        $this->tpl->assign('content', $response['content']);
        $this->tpl->assign('jumpUrl', $this->getAppScheme('closeall'));
        return true;
    }
}
<?php
/**
 * 获取分站配置信息
 *
 */
namespace api\controllers\open;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\open\OpenService;

class SiteConf extends AppBaseAction
{
    protected $needAuth = false;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'domain'  => array('filter' => 'string', 'option' => array('optional' => false)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $domain = $this->form->data['domain'];
        $site_id = intval($this->form->data['domain']);
        $preview = $this->form->data['preview'];

        $result = OpenService::openSiteConf($domain, $site_id, $preview);
        if ($result === false) {
            $this->setErr(OpenService::getErrorData(), OpenService::getErrorMsg());
        }

        $this->json_data = $result;
        return true;
    }

}

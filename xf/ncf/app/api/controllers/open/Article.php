<?php
/**
 * 获取分站文章内容
 *
 */
namespace api\controllers\open;

use libs\web\Url;
use libs\web\Open;
use libs\web\Form;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\OpenService;

class Article extends AppBaseAction
{
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'siteId' => array('filter' => 'int', 'option' => array('optional' => false)),
            'atc_id' => array('filter' => 'int', 'option' => array('optional' => false)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $siteId = $this->form->data['siteId'];
        $id = $this->form->data['atc_id'];

        $srv = new OpenService();
        $response = $srv->getArticle($siteId, $id);
        if($response == false){
            //错误处理
            $this->errorCode = -1;
            $this->errorMsg  = 'get Article failed!';
            return false;
        }
        $this->json_data = $response->data;
        return true;
    }

}

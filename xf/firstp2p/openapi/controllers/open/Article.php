<?php
/**
 * @abstract openapi 获取文章详情页
 * @date 2016年 10月 12日 星期二 11:47:18 CST
 *
 */

namespace openapi\controllers\open;

use libs\web\Url;
use libs\web\Open;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\OpenService;

class Article extends BaseAction
{
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'siteId'  => array('filter' => 'int', 'option' => array('optional' => true)),
            'atc_id' => array('filter' => 'int',    'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
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

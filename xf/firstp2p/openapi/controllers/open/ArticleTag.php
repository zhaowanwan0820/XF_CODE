<?php
/**
 * @abstract openapi 获取文章列表
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

class ArticleTag extends BaseAction
{
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'siteId'  => array('filter' => 'int', 'option' => array('optional' => true)),
            'type' => array('filter' => 'int',    'option' => array('optional' => true)),
            'cnt' => array('filter' => 'int',    'option' => array('optional' => false)),
            'title' => array('filter' => 'int',    'option' => array('optional' => false)),
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
        $type = $this->form->data['type'];
        $cnt = $this->form->data['cnt'];
        $title = $this->form->data['title'];

        $cnt <= 0 ? $cnt = 10 : $cnt;
        $title<=0 ? $title = 50 : $title;

        $srv = new OpenService();
        $response = $srv->getArticletag($siteId, $type, $cnt, $title);
        if($response == false){
            //错误处理
            $this->errorCode = -1;
            $this->errorMsg  = 'get ArticleList failed!';
            return false;
        }
        $response = $response->data;
        $this->json_data = $response;
        return true;
    }

}

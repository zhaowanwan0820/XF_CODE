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

class ArticleLists extends BaseAction
{
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'siteId'  => array('filter' => 'int', 'option' => array('optional' => true)),
            'type' => array('filter' => 'int',    'option' => array('optional' => true)),
            'pageNo' => array('filter' => 'int',    'option' => array('optional' => false)),
            'pageSize' => array('filter' => 'int',    'option' => array('optional' => false)),
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
        $pageNo = $this->form->data['pageNo'];
        $pageSize = $this->form->data['pageSize'];

        $pageNo <= 0 ? $pageNo = 1 : $pageNo;
        $pageSize<=0 ? $pageSize = 10 : $pageSize;

        $srv = new OpenService();
        $response = $srv->getArticleLists($siteId, $type, $pageNo, $pageSize);
        if($response == false){
            //错误处理
            $this->errorCode = -1;
            $this->errorMsg  = 'get ArticleList failed!';
            return false;
        }
        $this->json_data = $response;
        return true;
    }

}

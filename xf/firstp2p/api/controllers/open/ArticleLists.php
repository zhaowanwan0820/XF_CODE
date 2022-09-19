<?php
/**
 * 获取分站文章列表
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

class ArticleLists extends AppBaseAction
{
    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'siteId'  => array('filter' => 'int', 'option' => array('optional' => false)),
            'type' => array('filter' => 'int',    'option' => array('optional' => true)),
            'pageNo' => array('filter' => 'int',    'option' => array('optional' => false)),
            'pageSize' => array('filter' => 'int',    'option' => array('optional' => false)),
        );

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

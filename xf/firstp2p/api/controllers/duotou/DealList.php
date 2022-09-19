<?php
/**
 * DealList controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-03-02
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Duotou\RequestCommon;

/**
 * 多投-项目标的列表
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class DealList extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'page_num' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'page_size' => array(
                    'filter' => 'int',
                    'message' =>'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $request = new RequestCommon();
        if ($this->form->data['page_num']) {
            $vars['pageNum'] = $this->form->data['page_num'];//默认为1，如果大于实际值，则返回结果为空数据
        }
        if ($this->form->data['page_size']) {
            $vars['pageSize'] = $this->form->data['page_size'];//定义每个页面的记录数，默认为20
        }
        if ($vars) {
            $request->setVars($vars);
        }

        $response = $GLOBALS['duotouRpc']->callByObject(array(
                'service' => 'NCFGroup\Duotou\Services\Deal',
                'method' => 'listDealWithProject',
                'args' => $request,
        ));

        if ($response['errCode']) {
            $this->setErr('ERR_SYSTEM',$response['errMsg'].'['.$response['errCode'].']');
            return false;
        } else {
            $res['totalPage'] = $response['data']['totalPage'];
            $res['totalNum'] = $response['data']['totalNum'];
            $res['list'] = $response['data']['data'];
            $this->json_data = $res;
        }
    }

}

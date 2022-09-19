<?php
/**
 * 多投宝信息披露
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DtPublishService;

class Publish extends DuotouBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'page' => array( //第几页
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'page_size' => array( //每页条数
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $pageNum = intval($data['page'])==0 ? 1 : intval($data['page']);
        $pageSize = intval($data['page_size'])==0 ? 10 : intval($data['page_size']);
        $user = $this->user;

        $userId = $user['id'];
        $oDtPublishService = new DtPublishService();
        $res = \SiteApp::init()->dataCache->call($oDtPublishService, 'getPublishP2pDeals', array($userId,$pageNum,$pageSize), 30);
        $canCancel = $oDtPublishService->getCanCancelToday($userId);
        $return = array();
        $return['list'] = $res['list'];
        $return['total'] = $res['totalNum'];
        $return['totalPage'] = $res['totalPage'];
        $return['canCancel'] = intval($canCancel);
        $return['token'] = $data['token'];
        $this->json_data = $return;
    }

}

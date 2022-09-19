<?php
/**
 * 多投宝信息披露
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;

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
            'is_ajax' => array( //是否是ajax
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
        $isAjax  = intval($data['is_ajax']);
        if($isAjax == 1) {
            $user = $this->getUserByToken();
            if (empty($user)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            $userId = $user['id'];
            $res = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtPublishService\getPublishP2pDeals', array($userId,$pageNum,$pageSize),'duotou'), 30);
            $canCancel = $this->rpc->local('DtPublishService\getCanCancelToday', array($userId),'duotou');
            $return = array();
            $return['list'] = $res['list'];
            $return['total'] = $res['totalNum'];
            $return['totalPage'] = $res['totalPage'];
            $return['canCancel'] = intval($canCancel);
            $return['token'] = $data['token'];
            $this->json_data = $return;
        } else {
            $this->tpl->assign('is_h5', true);
            $this->tpl->assign('token',$data['token']);
        }
    }

}

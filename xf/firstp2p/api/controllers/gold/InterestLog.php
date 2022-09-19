<?php

/**
 * InterestLog.php
 *
 * @date 2017-06-27
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 * 获取用户收益明细接口
 *
 * Class InterestLog
 * @package api\controllers\deals
 */
class InterestLog extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'token is required',
            ),
            'pageNum' => array(
                'filter' => 'int',
                'message' => 'pageNum must int',
                'option' => array('optional' => true)
            ),
            'pageSize' => array(
                'filter' => 'int',
                'message' => 'pageSize must int',
                'option' => array('optional' => true)
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $pageNum = !empty($data['pageNum']) ? intval($data['pageNum']) : '';
        $pageSize = intval($data['pageSize']) ? intval($data['pageSize']) : '';
        $res = $this->rpc->local('GoldService\getCurrentInterestLog', array(intval($user['id']), $pageNum, $pageSize));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['errMsg']);
            return false;
        }
        $this->json_data = $res['list'];

    }

}

<?php
/**
 * 设置常用的私募基金
 **/

namespace api\controllers\fund;

use libs\web\Form;
use api\controllers\AppBaseAction;

class SetUsual extends AppBaseAction {

    private $_arr_fund_type = array(
        1 => '盈信基金',
        2 => '宜投基金',
    );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token不能为空'),
            "fund_type" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $info = $this->getUserByToken();
        if (empty($info)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }
        $user_id = $info['id'];

        $fund_type = $data['fund_type'];
        if (!$this->_arr_fund_type[$fund_type]) {
            $this->setErr('ERR_PARAMS_ERROR'); // 只允许1或2
            return false;
        }

        $key = "pefund_usual_" . $user_id;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->setEx($key, 7776000, $fund_type); // 90天

        $this->json_data = array(
            'code' => 0,
            'msg' => '设定成功',
        );
    }
}

<?php
/**
 * 检查金融工场传的idno是否是网信用户
 * @author liguizhi<liguizhi@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use libs\utils\Aes;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;

class GetIdno extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter'=>'required'),
            'id' => array('filter'=>'required')
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $key = $GLOBALS['config']['jrgcConfig']['aesKey'];
        $salt = $GLOBALS['config']['jrgcConfig']['salt'];
        $result = array('errorCode' => 0);
        $data = $this->form->data;

        $orrToken = $data['token'];
        $data['id'] = base64_decode($data['id']);
        $id = Aes::decode($data['id'], $key);
        $token = md5($salt.$id);
        PaymentApi::log('jrgc_query_id id:'.$id.' params:'.var_export($data, true));
        if($token != $orrToken) {
            $result['errorCode'] = 1;
            $result['errorMsg'] = '签名不正确';
            echo json_encode($result,JSON_UNESCAPED_UNICODE);
            exit;
        }
        $bankcard = $this->rpc->local("UserService\getUserByIdno", array("idno"=>$id));
        if (!empty($bankcard)) {
            $result['data'] = 1;
        } else {
            $result['data'] = 0;
        }
        PaymentApi::log('jrgc_query_id id:'.$id.' result:'.var_export($result, true));

        echo json_encode($result);
        exit;
    }
}

<?php
namespace api\controllers\user;

use api\conf\Error;
use api\controllers\FundBaseAction;
use libs\web\Form;
use api\conf\ConstDefine;

/**
 * SecurNet
 * @abstract 公安网调用接口
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 * @date 2015-06-18
 */

class SecurNet extends FundBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules=array(
                'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
                'name' => array('filter' =>"required", 'message'=> '姓名不能为空！'),
                'idno'=>array('filter'=>"required", 'message'=> '身份证号不能为空！'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
       
    }
    
    public function invoke()
    {
        $data=$this->form->data;

        if (strpos($data['name'], ' ') !== false || empty($data['name'])) {
            $msg = '用户姓名输入有误！';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        
        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($data['idno']))) {
            $msg = '平台仅支持二代身份证！';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
 
        $result=$this->rpc->local('UserService\psCheckUserNoid', array($data['name'], $data['idno']));
        
        if ($result['code']=='0') {
            $res['success'] = ConstDefine::RESULT_SUCCESS;
            $res['msg'] = '身份证和姓名匹配，查询成功!';
        } else {
            $msg = $result['msg'];
            $this->setErr($result['code'], $msg);
            return false;
        }
        $this->json_data = $res;
        return true;
    }
}
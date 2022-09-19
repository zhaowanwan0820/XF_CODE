<?php
/**
 *  * 验证手机手是否存在
 *  * @author yangqing<yangqing@ucfgroup.com>
 *  *
 **/

namespace web\controllers\user;
use web\controllers\BaseAction;
use libs\web\Form;

class MobileExist extends BaseAction {

	private $_error;

	public function init() {
		$this->form = new Form('get');
		$this->form->rules = array(
			'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字",
                "option" => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/")),
		);
		if (!$this->form->validate()) {
			$this->_error = $this->form->getErrorMsg();
			return $this->printResult();
		}
	}

	public function invoke() {

		$msg = array('code'=>-1,'msg'=>'访问频次过高，请稍后再试');
		return $this->show_success('访问过于频繁，请稍后再试', '', 1);
		/*
		$mobile = trim($this->form->data['mobile']);
		$ret = $this->rpc->local('UserService\isExistsMobile', array($mobile));
		if($ret === TRUE)
		{
		    $this->_error = '该手机号已经存在';
		}
		return $this->printResult();
		*/
	}

	private function printResult($code=null) {
		if(empty($this->_error))
		{
            $code = (empty($code))?0:$code;
			$json = array('code'=>$code,'msg'=>'');
		}
		else
		{
            $code = (empty($code))?-1:$code;
			$json = array('code'=>$code,'msg'=>$this->_error);
		}
		echo json_encode($json);
		return false;
	}

}




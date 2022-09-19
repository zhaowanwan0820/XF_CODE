<?php

//开放的公共类，不需RABC验证
class PublicAction extends BaseAction{
	public function login()
	{
		//验证是否已登录
		//管理员的SESSION
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$adm_name = $adm_session['adm_name'];
		$adm_id = intval($adm_session['adm_id']);

		if($adm_id != 0)
		{
			//已登录
			$this->redirect(u("Index/index"));
		}
		else
		{
            $this->assign("isAdminVerify", $this->isAdminVerify());
			$this->display();
		}
	}

	public function verify()
	{
        Image::buildImageVerify(4,1);
    }

    //登录函数
    public function do_login()
    {
    	$adm_name = trim($_REQUEST['adm_name']);
    	$adm_password = trim($_REQUEST['adm_password']);
    	$ajax = intval($_REQUEST['ajax']);  //是否ajax提交

    	if($adm_name == '')
    	{
    		$this->error(L('ADM_NAME_EMPTY',$ajax));
    	}
    	if($adm_password == '')
    	{
    		$this->error(L('ADM_PASSWORD_EMPTY',$ajax));
    	}

        //开发测试环境关闭验证码校验
        if($this->isAdminVerify() && es_session::get("verify") != md5($_REQUEST['adm_verify'])){
            $this->error(L('ADM_VERIFY_ERROR'),$ajax);
        }

		$condition['adm_name'] = $adm_name;
		$condition['is_effect'] = 1;
		$condition['is_delete'] = 0;
		$adm_data = M("Admin")->where($condition)->find();
		if($adm_data) //有用户名的用户
		{
			if($adm_data['adm_password']!=md5($adm_password))
			{
				save_log($adm_name.L("ADM_PASSWORD_ERROR"),0); //记录密码登录错误的LOG
				#$this->error(L("ADM_PASSWORD_ERROR"),$ajax);
				$this->error("用户名或密码错误",$ajax);
			}
			else
			{
				//登录成功
				$adm_session['adm_name'] = $adm_data['adm_name'];
				$adm_session['adm_id'] = $adm_data['id'];
                $adm_session['adm_role_id'] = $adm_data['role_id'];
                $adm_session['force_change_pwd'] = $adm_data['force_change_pwd'];
                $adm_session['password_update_time'] = $adm_data['password_update_time'];
                $adm_session['mobile'] = $adm_data['mobile'];

				$role_data = M("Role")->where('id='.$adm_data['role_id'])->find();
				$adm_session['adm_role'] = $role_data['name'];

				es_session::set(md5(conf("AUTH_KEY")),$adm_session);

				//重新保存记录
				$adm_data['login_ip'] = get_client_ip();
				$adm_data['login_time'] = get_gmtime();
				M("Admin")->save($adm_data);
				save_log($adm_data['adm_name'].L("LOGIN_SUCCESS"),1);
				$this->success(L("LOGIN_SUCCESS"),$ajax);
			}
		}
		else
		{
			save_log($adm_name.L("ADM_NAME_ERROR"),0); //记录用户名登录错误的LOG
			#$this->error(L("ADM_NAME_ERROR"),$ajax);
			$this->error("用户名或密码错误",$ajax);
		}
    }

    //登出函数
	public function do_loginout()
	{
	//验证是否已登录
		//管理员的SESSION
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$adm_id = intval($adm_session['adm_id']);

		if($adm_id == 0)
		{
			//已登录
			$this->redirect(u("Public/login"));
		}
		else
		{
			es_session::delete(md5(conf("AUTH_KEY")));
			$this->assign("jumpUrl",U("Public/login"));
			$this->assign("waitSecond",3);
			$this->success(L("LOGINOUT_SUCCESS"));
		}
	}

	/**
	 * 获取半小时内未确认的订单!
	 */
	public function checkSupplierBooking(){
		$now_time = get_gmtime();
		$e_time = $now_time - 60*30;
		$b_time = $e_time - 3600;

		$list = M("SupplierLocationOrder")->where("status=0 AND create_time between $b_time AND $e_time ")->findAll();

		$this->assign("list",$list);
		$this->display();
	}

	public function checkIdCard(){
		$return['status'] = 0;
		$card = trim($_REQUEST['card']);
		$url = "http://www.youdao.com/smartresult-xml/search.s?jsFlag=true&type=id&q=".$card;
		$result = $this->getUrlContent($url);

		$result = iconv("gbk","UTF-8",$result);
		preg_match("/{(.*?)}/",$result,$res);
		if($res[0]){
			$return = $res[0];
			echo str_replace("'","\"",$return);
		}
		else{
			$return['status'] = 0;
			$return['info'] = '没找到';
			echo json_encode($return);
		}

	}

	public function getUrlContent($url)
	{
		$content = '';
		if(!$this->parseUrl($url))
		{
			$content = @file_get_contents($url);
		}
		else
		{
			if(function_exists('curl_init'))
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_TIMEOUT,100);
				curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
				curl_setopt($ch, CURLOPT_REFERER,_REFERER_);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$content = curl_exec($ch);
				curl_close($ch);
			}
			else
			{
				$content = @file_get_contents($url);
			}
		}

		return $content;
	}

	/**
	 * 获取链接格式是否正确
	 * @param string $url 链接
	 * @return bool
 	*/
	function parseUrl($url)
	{
		$parse_url = parse_url($url);
		return (!empty($parse_url['scheme']) && !empty($parse_url['host']));
	}

    /**
     * 后台登录是否需要验证码
     * ip白名单不校验
     */
    private function isAdminVerify(){
        $env = app_conf('ENV_FLAG');
        if ($env == 'test' || $env == 'dev') {
            return false;
        }

        $server_ip = $_SERVER['SERVER_ADDR'];
        if(isset($GLOBALS['sys_config']['IS_ADMIN_VERIFY_WHITE_LIST']) && in_array($server_ip, $GLOBALS['sys_config']['IS_ADMIN_VERIFY_WHITE_LIST'])){
            return false;
        }
        return true;
    }

    //登录函数
    public function cc_login()
    {
        $ref_m = (string)$_REQUEST['ref_m'];
        $ref_a = (string)$_REQUEST['ref_a'];
        $sign  = (string)$_REQUEST['sign'];
        $practice = (string)$_REQUEST['practice'];
        save_log(var_export($_REQUEST,true),1);

        if(!$ref_m || !$ref_a || !$sign || !$practice){
            $msg = "缺少参数";
            echo $msg;
            save_log($msg. "--" . $ref_m . '|' . $ref_a,1);
            return;
        }

        $secret = "call_center~@)>";
        $sortedReq = $secret;
        $params_list = array("ref_m" => $ref_m, "ref_a" => $ref_a, "practice" => $practice);
        ksort($params_list);
        reset($params_list);
        while (list ($key, $val) = each($params_list)) {
            if (!is_null($val)) {
                $sortedReq .= $key . $val;
            }
        }

        $sortedReq .= $secret;
        $sign_md5 = strtoupper(md5($sortedReq));
        if($sign !== $sign_md5){
            $msg = "签名不正确";
            save_log($msg. "--" . $sign . '|' . $sign_md5 . ':' . $sortedReq,1);
            echo $msg;
            return;
        }

        $auth_list['User'] = array('custServInquir', 'custServInquir_detail');
        $auth_list['BonusGroupQuery'] = array('index', 'detail');
        $auth_list['BonusQuery'] = array('index');
        if(!in_array($ref_m, array_keys($auth_list))){
            $msg = '未被授权访问_1';
            echo $msg;
            save_log($msg. "--" . $ref_m . '|' . $ref_a,1);
            return;
        }

        if(!in_array($ref_a, $auth_list[$ref_m])){
            $msg = '未被授权访问_2';
            echo $msg;
            save_log($msg. "--" . $ref_m . '|' . $ref_a,1);
            return;
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $adm_id = intval($adm_session['adm_id']);
        if($adm_id !=0){
            header("Location:?m={$ref_m}&a={$ref_a}");
        }
        $adm_name = "call_center_user";
        $adm_password = "cc_Auto_login";

        $condition['adm_name'] = $adm_name;
        $condition['is_effect'] = 1;
        $condition['is_delete'] = 0;
        $adm_data = M("Admin")->where($condition)->find();
        if($adm_data) //有用户名的用户
        {
            if($adm_data['adm_password']!=md5($adm_password))
            {
                save_log($adm_name.L("ADM_PASSWORD_ERROR"),0); //记录密码登录错误的LOG
                echo "用户名不存在";
                return;
            }
            else
            {
                //登录成功
                $adm_session['adm_name'] = $adm_data['adm_name'];
                $adm_session['adm_id'] = $adm_data['id'];
                $adm_session['adm_role_id'] = $adm_data['role_id'];
                $adm_session['force_change_pwd'] = $adm_data['force_change_pwd'];
                $adm_session['password_update_time'] = $adm_data['password_update_time'];

                $role_data = M("Role")->where('id='.$adm_data['role_id'])->find();
                $adm_session['adm_role'] = $role_data['name'];

                es_session::set(md5(conf("AUTH_KEY")),$adm_session);

                //重新保存记录
                $adm_data['login_ip'] = get_client_ip();
                $adm_data['login_time'] = get_gmtime();
                M("Admin")->save($adm_data);
                save_log($adm_data['adm_name'].L("LOGIN_SUCCESS"),1);
                header("Location:?m={$ref_m}&a={$ref_a}");
            }
        }
        else
        {
            save_log($adm_name.L("ADM_NAME_ERROR"),0); //记录用户名登录错误的LOG
            echo "用户名或密码错误";
            return;
        }
    }
}
?>

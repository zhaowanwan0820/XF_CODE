<?php
FP::import("app.deal");
class baijinModule extends SiteBaseModule
{
	/**
	 * 预约信息
	 * @author guomumin aaron8573@gmail.com
	 * @date 2013-8-14
	 * @see SiteBaseModule::index()
	 */
	public function index(){
		
		//return app_redirect(url('huodong-baijin'));
		//exit();
		
		$code = $_POST['code'];
		$recode = $this->creatRandNum(8);
		$preset = array();
		

		//接收post提交数据
		if (isset($code)){
			
			// 验证表单令牌
			if(!check_token())
			{
				return showErr($GLOBALS['lang']['TOKEN_ERR']);
			}
			
			$_SESSION['recode'] = $recode;
			
			$real_name = isset($_POST['name']) ? trim($_POST['name']) : '';
			$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
			$email = isset($_POST['email']) ? trim($_POST['email']) : '';
			$money = isset($_POST['money']) ? trim($_POST['money']) : '';
			$user_name = isset($_POST['uname']) ? trim($_POST['uname']) : '';
			$user_name = ($user_name=="注册用户名")?'':trim($user_name);
			$program_id = $_POST['pro'];
			
			$_SESSION['preset'] = array(
				'name' => htmlspecialchars($real_name),
				'mobile' => htmlspecialchars($mobile),
				'email' => htmlspecialchars($email),
				'money' => htmlspecialchars($money),
				'uname' => htmlspecialchars($user_name)
			);
			
			//验证真实姓名
			if (strlen($real_name)<6 || strlen($real_name)>18){
				return showErr('请填写正确的真实姓名');
			}
			
			//验证email
			if (!is_email($email)){
				return showErr('请填正确的email');
			}
			
			//验证手机号
			if (!is_mobile($mobile)){
				return showErr('请填写正确的手机号');
			}
			
			//验证金额
			$str = "/^[0-9]+[\.][0-9]{0,2}$/";
			$str2 = "/^[0-9]+$/";
			if (!preg_match($str, $money) && !preg_match($str2, $money)){
				return showErr('请填写正确的金额');
			}
			
			// 判断是不是内部员工
			if($GLOBALS['db']->getOne("SELECT * FROM ".DB_PREFIX."staff_list WHERE mobile=".$mobile))
				$is_staff = 1;
			else 
				$is_staff = 0;
			
			//写入数据库
			$predata = array(
				'real_name' => $real_name,
				'mobile' => $mobile,
				'email' => $email,
				'money' => $money,
				'user_name' => $user_name,
				'create_time' => get_gmtime(),
				'is_staff' => $is_staff,
				'program_id' => $program_id
			);
			$GLOBALS['db']->autoExecute(DB_PREFIX."preset", $predata, "INSERT");
			unset($_SESSION['preset']);
			return showSuccess("预约成功,去看看进行中的项目",0,url("deals"));
			
		}else{
			$_SESSION['recode'] = $recode;
			if ($GLOBALS['user_info']['id']>0){
				$preset['name'] = $GLOBALS['user_info']['real_name'];
				$preset['email'] = $GLOBALS['user_info']['email'];
				$preset['mobile'] = $GLOBALS['user_info']['mobile'];
				$preset['money'] = '';
				$preset['uname'] = $GLOBALS['user_info']['user_name'];
			}
			
			if (!empty($_SESSION['preset'])){
				$preset = $_SESSION['preset'];
			}
		}
		
		$GLOBALS['tmpl']->assign("code", $recode);
		$GLOBALS['tmpl']->assign("preset",$preset);
		$GLOBALS['tmpl']->assign("in_preset_page",1);
		$GLOBALS['tmpl']->assign("page_title", "白金1号");
		$GLOBALS['tmpl']->display("baijin.html");
		
	}
	
	/**
	 * 预约已满通告
	 * @author Weakow Wang <wang@weakow.com>
	 * @date 2013-08-22
	 */
  public function full() {
  	$GLOBALS['tmpl']->assign("in_preset_page",1);
  	$GLOBALS['tmpl']->assign("page_title", "白金1号");
  	$GLOBALS['tmpl']->display("baijin-full.html");
  }

	/**
	 * 生成随机码
	 * 
	 * @param 位数 $len
	 * @return 32位md5加密随机码
	 */
	private function creatRandNum($len){
		$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$str = '';
        for ($i=0; $i < $len; $i++)
        {
       		$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
        }
        return md5($str);
	}
	
}
?>

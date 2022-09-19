<?php
/**
 * 预约项目类
 * @author guomumin <aaron8573@gmail.com>
 * @date 2013-09-06
 */
class huodongModule extends SiteBaseModule
{

	private $act;	//项目url

	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		if (!isset($_REQUEST['act'])){
			return app_redirect(url('/'));
		}else{
			$this->act = $_REQUEST['act'];
		}
	}

	/**
	 * 预约信息
	 */
	public function index(){
		$preset = array();
		//$pro = $GLOBALS['db']->getRow("SELECT * FROM ". DB_PREFIX ."preset_program WHERE program_url='". $this->act."'");
        $pro = \core\dao\PresetProgramModel::instance()->getPresetProgram($this->act);

		if (empty($pro)){
			return app_redirect(url('/'));
		}
		if ($pro['program_status'] == 3){
			return app_redirect(url('/'));
		}

		//接收post提交数据
		if (isset($_POST['name'])){

			if ($pro['program_status'] == 0){
				exit('预约没有开始');
			}
			if($pro['program_status'] == 2){
				exit('预约已经结束');
			}

			if($pro['program_is_login'] == 1 && empty($GLOBALS['user_info'])){
				exit('请先登录');
			}

			$real_name = isset($_POST['name']) ? trim($_POST['name']) : '';
			$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
			$email = isset($_POST['email']) ? trim($_POST['email']) : '';
			$money = isset($_POST['money']) ? trim($_POST['money']) : '';
			$area = isset($_POST['area']) ? trim($_POST['area']) : '';
			$user_name = isset($_POST['uname']) ? trim($_POST['uname']) : '';
			$user_name = ($user_name=="注册用户名") ? '' : trim($user_name);

			//存储默认信息
			$_SESSION['preset'] = array(
				'name' => htmlspecialchars($real_name),
				'mobile' => htmlspecialchars($mobile),
				'email' => htmlspecialchars($email),
				'money' => htmlspecialchars($money),
				'uname' => htmlspecialchars($user_name),
				'area' => htmlspecialchars($area),
			);

			//验证真实姓名
			if (strlen($real_name) < 6 || strlen($real_name) > 18){
				exit('请填写正确的真实姓名');
			}

			//验证email
			if (!is_email($email)){
				exit('请填写正确的email');
			}

			//验证手机号
			if (!is_mobile($mobile)){
				exit('请填写正确的手机号');
			}

			//验证金额
			if (!preg_match("/^[0-9]+[\.][0-9]{0,2}$/", $money) && !preg_match("/^[0-9]+$/", $money)){
				exit('请填写正确的金额');
			}

			if ($pro['program_area'] && (empty($area) || !in_array($area, explode('||', $pro['program_area'])))){
				exit('请选择正确的地址');
			}

			// 验证表单令牌
			if(!check_token()){
				exit('提交失败，请刷新页面重试');
			}

			// 判断是不是内部员工
			$is_staff = 0;
			if($GLOBALS['db']->getOne("SELECT * FROM ".DB_PREFIX."staff_list WHERE mobile=".$mobile)){
				$is_staff = 1;
			}

			//写入数据库
			$predata = array(
				'real_name' => $real_name,
				'mobile' => $mobile,
				'email' => $email,
				'money' => $money,
				'user_name' => $user_name,
				'user_area' => $area,
				'create_time' => get_gmtime(),
				'is_staff' => $is_staff,
				'program_id' => $pro['id']
			);

			$GLOBALS['db']->autoExecute(DB_PREFIX."preset", $predata, "INSERT");

			unset($_SESSION['preset']);
			exit('1');

		}else{
			//$_REQUEST['preview'] == 1 时可以预览
			if(!isset($_REQUEST['preview']) || $_REQUEST['preview'] != 1){
				if ($pro['program_status'] == 0){
					return app_redirect(url('/'));
				}

				if($pro['program_is_login'] == 1 && empty($GLOBALS['user_info'])){
					es_session::set('before_login',$_SERVER['REQUEST_URI']);
					return app_redirect(url("index","user#login"));
				}
			}
			//预约页面
			if ($GLOBALS['user_info']['id']>0){
				$preset['name'] = $GLOBALS['user_info']['real_name'];
				$preset['email'] = $GLOBALS['user_info']['email'];
				$preset['mobile'] = $GLOBALS['user_info']['mobile'];
				$preset['money'] = '';
				$preset['uname'] = $GLOBALS['user_info']['user_name'];
			}

			//加载上次填写信息
			if (!empty($_SESSION['preset'])){
				$preset = $_SESSION['preset'];
			}

			if($pro['program_deals']){
				FP::import("app.deal");
				$deal_list = get_deal_list(100,0,"deal.publish_wait = 0 AND deal.deal_status in(0,1,2,4) AND deal.id in ({$pro['program_deals']})","deal.update_time DESC , deal.sort DESC , deal.id DESC");
				$GLOBALS['tmpl']->assign("deal_count",$deal_list['count']);
				$GLOBALS['tmpl']->assign("deal_list",$deal_list['list']);
			}

			$preset_area = $pro['program_area'] ? explode('||', $pro['program_area']) : array();

			$GLOBALS['tmpl']->assign("pro", $pro);
			$GLOBALS['tmpl']->assign("preset",$preset);
			$GLOBALS['tmpl']->assign("preset_area",$preset_area);
			$GLOBALS['tmpl']->assign("in_preset_page",1);
			$GLOBALS['tmpl']->assign("page_title", $pro['program_name']);
			$GLOBALS['tmpl']->display("preset.html");
		}
	}
}
?>

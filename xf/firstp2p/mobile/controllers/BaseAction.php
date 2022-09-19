<?php
/**
 * BaseAction class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
namespace mobile\controllers;

use libs\rpc\Rpc;
use libs\web\Action;

/**
 * BaseAction class
 *
 * @packaged default
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class BaseAction extends Action
{
    public $rpc;
    public function __construct()
    {
        parent::__construct();
        $this->rpc = new Rpc();
        $this->template = $this->getTemplate(); //覆盖phoenix框架的模板路径
        $this->setTpl();
	    $this->initCommon();
    }

    /**
     * 设置模板类
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    private function setTpl()
    {
        $this->tpl = new \AppTemplate();
        $this->tpl->asset = \SiteApp::init()->asset;
        $this->tpl->cache_dir    = APP_RUNTIME_PATH.'app/tpl_caches';
        $this->tpl->compile_dir  = APP_RUNTIME_PATH.'app/tpl_compiled';
	    $this->tpl->template_dir = APP_ROOT_PATH;

        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
	    $arr_path = explode("/", $class_path);
	    $this->tpl->assign("MODULE_NAME", $arr_path[2]);
	    $this->tpl->assign("ACTION_NAME", $arr_path[3]);
	    $this->tpl->assign("LANG", $GLOBALS['lang']);

        $this->tpl->assign("APP_SKIN_PATH",APP_SKIN_PATH);
        $tmpl_new_path = APP_ROOT."/static/".app_conf("STATIC_FILE_VERSION");
        $this->tpl->assign("TMPL_NEW",$tmpl_new_path);
    }

	/**
	 * 初始化action的公用页面变量
	 * @return void
	 */
	private function initCommon() {
		//输出导航菜单
		$nav_list= $this->_initNavList(get_nav_list(), true);
		foreach($nav_list as $k=>$v){
			$nav_list[$k]['sub_nav'] = $this->_initNavList($v['sub_nav'], false);
		}
		$this->tpl->assign("nav_list",$nav_list);

		// 页面关键字、标题、描述
		$this->tpl->assign("site_info", get_site_info());

        // 设置用户信息
        $this->tpl->assign("user_info", $GLOBALS['user_info']);
	}

	/**
     * 获取模板文件路径, 默认存放在与controllers平级的views目录下
     *
     * @return string 模板文件路径
     **/
    public function getTemplate()
    {
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        return str_replace('/controllers/', '/views/', $class_path).'.html';
    }

    /**
     * invoke的前置工作, 初始化form数据验证，session配置，登录状态等
     *
     * @return void
     **/
    public function init(){
    }

	/**
	 * 设置面包屑
	 * @param string|array $text
	 */
	public function set_nav($text) {
		if (!is_array($text)) {
			$arr = array(
				"text" => $text,
			);
			$nav = array($arr);
		} else {
			foreach ($text as $k => $v) {
				if (is_numeric($k)) {
					$nav[] = array("text" => $v);
				} else {
					$nav[] = array("url" => $v, "text" => $k);
				}
			}
		}
		$this->tpl->assign("nav", $nav);
	}

	/**
	 * 初始化首部导航信息
	 * @param $nav_list
	 * @param $isroot
	 * @return mixed
	 */
	private function _initNavList($nav_list, $isroot) {
		$class_path = strtolower(str_replace('\\', '/', get_class($this)));
	    $arr_path = explode("/", $class_path);

		$u_param = "";
		foreach($_GET as $k=>$v)
		{
			if(strtolower($k)!="ctl"&&strtolower($k)!="act"&&strtolower($k)!="city")
			{
				$u_param.=$k."=".$v."&";
			}
		}
		if(substr($u_param,-1,1)=='&')
			$u_param = substr($u_param,0,-1);
		foreach($nav_list as $k=>$v)
		{
			if($v['url']=='')
			{
				$route = $v['u_module'];
				if($v['u_action']!='')
					$route.="#".$v['u_action'];

				$app_index = $v['app_index'];

				if($v['u_module']=='index')
				{
					$route="index";
					$v['u_module'] = "index";
				}

				if($v['u_action']=='')
					$v["u_action"] = "index";

				$str = "u:".$app_index."|".$route."|".$v['u_param'];
				$nav_list[$k]['url'] =  parse_url_tag($str);

				if ($isroot) {
					if($v['u_module']=='deals' && $arr_path[2]=='tool') {
						$nav_list[$k]['current'] = 1;
					} elseif ($arr_path[2]==$v['u_module']) {
						$nav_list[$k]['current'] = 1;
					} elseif ($v['u_module'] == 'uc_center' && (strpos($arr_path[2], "uc_") === 0 || $arr_path[2] == "account")) {
						// HACK(jiankangzhang): 现在UCCENTER下面有很多tab不在nav的config里面，现在将uc_开头的module归属到uc的tab下面.
						$nav_list[$k]['current'] = 1;
					}
				} else {
					if ($v['u_module'] == 'help') {
						if ($arr_path[2] == 'help') {
							$menu_id = substr($v['u_param'], strpos($v['u_param'], '=') + 1);
							if (in_array($menu_id, array('9', '12', '27'))) {
								if ($menu_id == $_REQUEST['id']) {
									$nav_list[$k]['current'] = 1;
								}
							} else {
								if (!in_array($_REQUEST['id'], array('9', '12', '27'))) {
									$nav_list[$k]['current'] = 1;
								}
							}
						}
					} else {
						if ($arr_path[3]==$v['u_action']&&$arr_path[2]==$v['u_module']) {
							$nav_list[$k]['current'] = 1;
						}
					}
				}
			}
		}
		return $nav_list;
	}
} // END class BaseAction

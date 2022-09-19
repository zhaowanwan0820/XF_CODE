<?php
/**
 * BaseAction class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
namespace mobileapp\controllers;

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
        $this->setChannel();
        $this->initCommon();
        $this->_isPuhui();
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
    //设置全局推广标记
    private function setChannel(){
        $channel = isset($_GET['cn']) ? $_GET['cn'] : "";
        if ($channel) {
            setcookie('link_coupon', $channel, 0, '/', '.firstp2p.com');
        }
    }

    /**
     * 是否是普惠站 firstp.cn 为普惠站域名，firstp2p.com 为网信理财站域名
     */
    private function _isPuhui(){
        $env = app_conf("ENV_FLAG");
        if ($env=='test' || $env=='dev'){
            $rootDomain = 'firstp2plocal.com';
        }else{
            $rootDomain = 'firstp2p.com';
        }
        $this->isPuhui =  get_root_domain() != $rootDomain;
    }

	/**
	 * xss过滤
	 */
	public function removeXss($val) {
		// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
		// this prevents some character re-spacing such as <java\0script>
		// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
		$val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

		// straight replacements, the user should never need these since they're normal characters
		// this prevents like <IMG SRC=@avascript:alert('XSS')>
		$search = 'abcdefghijklmnopqrstuvwxyz';
		$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$search .= '1234567890!@#$%^&*()';
		$search .= '~`";:?+/={}[]-_|\'\\';
		for ($i = 0; $i < strlen($search); $i++) {
		   // ;? matches the ;, which is optional
		   // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		   // @ @ search for the hex values
		   $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
		   // @ @ 0{0,7} matches '0' zero to seven times
		   $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
		}

		// now the only remaining whitespace attacks are \t, \n, and \r
		$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object',
			'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base', 'href'
		);

		$ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut',
			'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint',
			'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 
			'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend',
			'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish',
			'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture',
			'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove',
			'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart',
			'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart',
			'onstop', 'onsubmit', 'onunload'
		);
		$ra = array_merge($ra1, $ra2);

		$found = true; // keep replacing as long as the previous round replaced something
		while ($found == true) {
		   $val_before = $val;
		   for ($i = 0; $i < sizeof($ra); $i++) {
			  $pattern = '/';
			  for ($j = 0; $j < strlen($ra[$i]); $j++) {
				 if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				 }
				 $pattern .= $ra[$i][$j];
			  }
			  $pattern .= '/i';
			  $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
			  $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			  if ($val_before == $val) {
				 // no replacements were made, so exit the loop
				 $found = false;
			  }
		   }
		}
		return $val;
	 }
} // END class BaseAction

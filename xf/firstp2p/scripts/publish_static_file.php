<?php

/**
 * 分析模板中通过parse_css|parse_script引用的静态文件，并做好这些文件；
 * @author laijinhai@ucfgroup.com  2013-11-25
 *
 */

/**
 * 系统根目录路径
 */
defined('APP_ROOT_PATH') or define('APP_ROOT_PATH', preg_replace('/scripts$/', '', rtrim(str_replace('\\', '/', dirname(__FILE__)), '/')));



/**
 * 从app/Tpl中的.html文件中分析出形如{function name="parse_(css|script)" v="xxx" c="xxx"}这种获取js或css地址的语句；
 * 返回分析出的原始.js和.css；
 * 
 * @param $dir, 分析的目录，为app/Tpl
 * @param $lv, 分析层级，暂未使用
 *
 * @return $res, 结果数组，大致格式为：
$res = array(
	array(
		'type'=>'',			//类型，css或script
		'name'=>'',			//function的name
		'file'=>'',			//.html的路径
		'files'=>array(),	//需要合并的文件
		'cfiles'=>array(),	//需要压缩的文件
	),
);
 *
 */
function get_parse_file($dir, $lv){
	$res = array();
	if (!is_dir($dir)){
		return $res;
	}
	$files = scandir($dir);
	//print_r($files);
	foreach ($files as $k=>$file){
		if ($file == "." || $file == ".."){
			continue;
		}
		if (preg_match('@\.html$@', $file)){
			$cont = file_get_contents($dir.$file);
			preg_match_all('@\{function name=[\'"]parse_(css|script)[\'"] v=[\'"]\$([^\'"]+)[\'"](?: c=[\'"]\$([^\'"]+)[\'"])?\}@', $cont, $rg);
			if (!isset($rg[1])){
				continue;
			}
			foreach ($rg[0] as $rk=>$rv){
				if (isset($rg[3][$rk])){
					$preg_js = '('.$rg[2][$rk].'|'.$rg[3][$rk].')';
				} else {
					$preg_js = '('.$rg[2][$rk].')';
				}
				preg_match_all('@var\[\\\''.$preg_js.'\\\'\](?:.*?)var\[\\\'APP_STATIC_PATH\\\'\]\."([^\'"]+)"@', $cont, $srg);
				#print_r($srg);
				$rsub = array('type'=>$rg[1][$rk], 'name'=>$rg[2][$rk], 'file'=>$dir.$file, 'files'=>array(), 'cfiles'=>array());
				foreach ($srg[0] as $srk=>$srv){
					if ($srg[1][$srk] == $rg[2][$rk]){
						$rsub['files'][$srk] = $srg[2][$srk];
					} else {
						$rsub['cfiles'][$srk] = $srg[2][$srk];
					}
				}
				$res[] = $rsub;
			}
		} elseif (is_dir($dir.$file)){
			$r_rg = get_parse_file($dir.$file."/", $lv+1);
			//$r_rg = array();
			if (count($r_rg)==0){
				continue;
			}
			foreach ($r_rg as $rrk=>$rrv){
				$res[] = $rrv;
			}
		}
	}
	return $res;
}

/**
 * 将分析出的文件压缩成相应的js和css
 * 
 */
function publish_file($conf){
	//从配置文件中取用$ver
	//$conf_common = require(APP_ROOT_PATH."conf/common.conf.php");
	//$ver = $conf_common['APP_SUB_VER'];
        $ver = time();

	//获取文件数组
	$fns = get_parse_file(APP_ROOT_PATH."app/Tpl/", 0);
	//print_r($fns);

	//对文件数组做组合去重分析
	$pubs = array();
	foreach ($fns as $k=>$fn){
		#$pubs[$fn['type']][$fn['name']]['file'][] = $fn['file'];
		#$pubs[$fn['type']][$fn['name']]['files'][] = $fn['files'];

		//分析出模板的名字
		preg_match('@app/Tpl/([^/]+)/@', $fn['file'], $frg);
		$tpl = $frg[1];

		//文件有可能是css/xxx.css，这里只取xxx.css
		$fn_key_arr = array();
		foreach ($fn['files'] as $fk=>$fv){
			$fv_arr = explode("/", $fv);
			$fn_key_arr[] = $fv_arr[count($fv_arr)-1];
		}
		$fn_key = $tpl."__".implode(",", $fn_key_arr);

		$pubs[$fn_key]['type'] = $fn['type'];
		$pubs[$fn_key]['name'][$fn['type']."__".$fn['name']] = 1;
		$pubs[$fn_key]['nums'] = count($fn_key_arr);
		$pubs[$fn_key]['file'][] = $fn['file'];
		$pubs[$fn_key]['files'][] = $fn['files'];
		$pubs[$fn_key]['cfiles'][] = $fn['cfiles'];
		foreach ($fn['files'] as $fk=>$fv){
			$pubs[$fn_key]['uniq_file'][$fv] = 1;
		}
		foreach ($fn['cfiles'] as $fk=>$fv){
			$pubs[$fn_key]['uniq_cfile'][$fv] = 1;
		}
	}

	//print_r($pubs);
	//echo "Total = ". count($pubs)."\n\n";

	//分析输出路径，引用压缩类库
	if ($conf['path'] == "" || !is_dir(APP_ROOT_PATH.$conf['path']) && !mkdir(APP_ROOT_PATH.$conf['path'])){
		$conf['path'] = "runtime/static/";
	}
	require(APP_ROOT_PATH.'system/libs/javascriptpacker.php');
	$static_path = APP_ROOT_PATH.$conf['path'];
	if (!is_dir($static_path)){
		mkdir($static_path);
	}
	foreach ($pubs as $pk=>$pv){
		list($tpl, $p_name) = explode("__", $pk);
		$f_name = md5($p_name);//生成的文件名，是拿组合文件的文件名联合成的内容做md5
		$f_path = $static_path.$tpl."/";
		if (!is_dir($f_path)){
			mkdir($f_path);
		}
		$f_path = $f_path."pub/";
		if (!is_dir($f_path)){
			mkdir($f_path);
		}
		$f_file = $f_path.$f_name.".".($pv['type'] == "script" ? "js" : "css");
		$f_cont = '';
		foreach ($pv['uniq_file'] as $pck=>$pcv){
			$ori_file = APP_ROOT_PATH.'public/static/'.$tpl.$pck;
			if (!file_exists($ori_file)){
				//这个文件从项目开始就不存在，所以不报警
				if (strpos($ori_file, "exchange.js") === false){
					echo $ori_file. " no found!\n";
				}
				continue;
			}
			$f_append = file_get_contents($ori_file);
			//如果在压缩文件列表中，则需要用类库进行压缩
			if (isset($pv['uniq_cfile'][$pck])){
				$packer = new JavaScriptPacker($f_append);
				$f_append = $packer->pack();
				$packer = null;
			}
			if ($pv['type'] == "css"){
				//对于css，需要替换图片；注意，此处仅替换形如background:url()中 ../(img|images)/.*?\.(jpg|gif|png) 的图片
				$f_append = preg_replace('@url(\([\'"]?)(\.\./(?:img|images)/[^\'"\)\?]+\.(?:jpg|gif|png))([\'"]?\))@i', 'url\1\2?v='.$ver.'\3', $f_append);
				$f_append = preg_replace("/[\r\n]/",'',$f_append);
			}
			$f_cont .= $f_append;
		}
		if ($f_cont != ""){
			file_put_contents($f_file, $f_cont);
		}
		//输出每个模板的语言文件
		publish_lang($static_path.$tpl."/");
	}

	//特殊逻辑，将admin中需要用到的calendar语言包生成js，改变原来引用php的途径
	publish_admin_calendar_lang($static_path);
}

function publish_lang($app_static_path){
	$lang = require APP_ROOT_PATH.'/app/Lang/zh-cn/lang.php';
	if(!file_exists($app_static_path.'lang.js')){			
		$str = "var LANG = {";
		foreach($lang as $k=>$lang_row){
			$str .= "\"".$k."\":\"".str_replace("nbr","\\n",addslashes($lang_row))."\",";
		}
		$str = substr($str,0,-1);
		$str .="};";
		file_put_contents($app_static_path.'lang.js',$str);
	}
}

function publish_admin_calendar_lang($static_path){
	return ;
	require(APP_ROOT_PATH.'/admin/Lang/zh-cn/calendar.php');
	$opln = '';
	foreach ($_LANG['calendar_lang'] AS $cal_key => $cal_data){
		$opln .= 'var ' . $cal_key . " = \"" . $cal_data . "\";\r\n";
	}
	$static_path = APP_ROOT_PATH.'admin/public/static/';
	if (!is_dir($static_path)){
		mkdir($static_path);
	}
	file_put_contents($static_path."admin/Common/js/calendar/calendar_lang.js", $opln);
}

function css_add_ver($cont, $ver){
	preg_match_all('@url(\([\'"]?)(\.\./(?:img|images)/[^\'"\)\?]+\.(?:jpg|gif|png))([\'"]?\))@i', $cont, $rg);
	//print_r($rg);
	$cont = preg_replace('@url(\([\'"]?)(\.\./(?:img|images)/[^\'"\)\?]+\.(?:jpg|gif|png))([\'"]?\))@i', 'url\1\2?V='.$ver.'\3', $cont);
	echo $cont;
}

if (isset($argv[1]) && $argv[1] == "calendar"){
	publish_admin_calendar_lang("../runtime/static/");
} elseif (isset($argv[1]) && $argv[1] == "css"){
	css_add_ver(file_get_contents(APP_ROOT_PATH.'runtime/static/default/css/style.css'), "20131126");
} else {
	$conf = array();
	$conf['path'] = isset($argv[1]) ? $argv[1] : '';
	publish_file($conf);
}


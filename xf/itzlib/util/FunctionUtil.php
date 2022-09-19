<?php

/**
 * FunctionUtil
 *
 * 从原来的代码中整体移出 原function.inc.php
 *
 * 函数没有测试 不建议使用
 *
 * FunctionUtil.php这个文件有两处地方有,itouzi/protected/lib/util/FunctionUtil.php,itzlib/util/FunctionUtil.php
 * 只能使用itzlib/util/FunctionUtil.php,itouzi下的这个弃用
 */
class FunctionUtil
{
    static public $html_safe = false;

	static  $ispArray = array(
		/*"YD" => array("134","135","136","137","138","139","150","151","152","157","158","159","1705","178","182","183","184","187","188","147"),
		"DX" => array("133","153","1700","177","180","181","189"),
		"LT" => array("130","131","132","155","156","1709","176","185","186","145"),*/
		"YD" => array("134","135","136","137","138","139","150","151","152","157","158","159","170","178","182","183","184","187","188","147"),
		"DX" => array("133","153","170","177","180","181","189"),
		"LT" => array("130","131","132","155","156","170","176","185","186","145")
	);

    /**
     * smarty 编译专用
     */
    static function escapeHtmlForSmartyCompile($string, $encoding)
    {
        if (self::$html_safe) return $string;
        if (!json_decode($string)) {
            $string = htmlspecialchars($string, ENT_QUOTES, $encoding);
        }
        return $string;
    }

    /**
     * 获取IP地址
     */
    static function ip_address()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip_address = $_SERVER["HTTP_CLIENT_IP"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip_address = array_shift($ips);
        } else if (!empty($_SERVER["REMOTE_ADDR"])) {
            $ip_address = $_SERVER["REMOTE_ADDR"];
        } else {
            $ip_address = '';
        }
        return trim($ip_address);
    }

    function get_module_info($module, $module_dir = "")
    {
        $var = array("code", "name", "version", "description", "author", "date", "type");
        if ($module_dir == "") $module_dir = ROOT_PATH . "modules/$module/";
        include($module_dir . "" . $module . ".info");
        foreach ($var as $val) {
            $result[$val] = empty($$val) ? "" : $$val;
        }
        return $result;
    }

    function editor($fname = "content", $value = "", $width = 630, $height = 460)
    {
        require_once(ROOT_PATH . "/plugins/sinaeditor/Editor.class.php");
        $editor = new sinaEditor($fname);
        $editor->Value = $value;
        $editor->BasePath = '../libs';
        $editor->Height = $height;
        $editor->Width = $width;
        $editor->AutoSave = false;
        return $editor->Create();
    }


    function mk_dir($dir, $dir_perms = 0775)
    {
        /* 循环创建目录 */
        if (DIRECTORY_SEPARATOR != '/') {
            $dir = str_replace('\\', '/', $dir);
        }


        if (is_dir($dir)) {
            return true;
        }

        if (@ mkdir($dir, $dir_perms)) {
            return true;
        }

        if (!self::mk_dir(dirname($dir))) {
            return false;
        }

        return mkdir($dir, $dir_perms);

    }

    function mkdirs($path, $mode = 0777)
    {
        $dirs = explode('/', $path);
        $pos = strrpos($path, ".");
        if ($pos === false) {
            $subamount = 0;
        } else {
            $subamount = 1;
        }

        for ($c = 0; $c < count($dirs) - $subamount; $c++) {
            $path = "";
            for ($cc = 0; $cc <= $c; $cc++) {
                $path .= $dirs[$cc] . '/';
            }
            if (!file_exists($path)) {
                mkdir($path, $mode);
            }
        }
    }


    function mk_file($dir, $contents)
    {
        $dirs = explode('/', $dir);
        if ($dirs[0] == "") {
            $dir = substr($dir, 1);
        }
        self::mk_dir(dirname($dir));
        @chmod($dir, 0777);
        if (!($fd = @fopen($dir, 'wb'))) {
            $_tmp_file = $dir . DIRECTORY_SEPARATOR . uniqid('wrt');
            if (!($fd = @fopen($_tmp_file, 'wb'))) {
                trigger_error("系统无法写入文件'$_tmp_file'");
                return false;
            }
        }
        fwrite($fd, $contents);
        fclose($fd);
        @chmod($dir, 0777);
        return true;
    }


    function get_file($dir, $type = 'dir')
    {
        $result = "";
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $_file = $dir . "/" . $file;
                    if ($file != "." && $file != ".." && filetype($_file) == $type) {
                        $result[] = $file;
                    }
                }
                closedir($dh);
            }
        }
        return $result;
    }

    //删除指定目录（文件夹）中的所有文件函数
    function del_file($dir)
    {
        if (is_dir($dir)) {
            $dh = opendir($dir);//打开目录 //列出目录中的所有文件并去掉 . 和 ..
            while (false !== ($file = readdir($dh))) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        unlink($fullpath);
                    } else {
                        del_file($fullpath);
                    }
                }
            }
            closedir($dh);
        }
    }

    function read_file($filename)
    {
        if (file_exists($filename) && is_readable($filename) && ($fd = @fopen($filename, 'rb'))) {
            $contents = '';
            while (!feof($fd)) {
                $contents .= fread($fd, 8192);
            }
            fclose($fd);
            return $contents;
        } else {
            return false;
        }
    }


    function fields_input($input = "")
    {
        $_input = array("text"      => "单行文本",
                        "multitext" => "多行文本",
                        "password"  => "密码类型",
                        "htmltext"  => "HTML文本",
                        "datetime"  => "时间类型",
                        "image"     => "图片类型",
                        "color"     => "颜色类型",
                        "annex"     => "附件类型",
                        "site"      => "站点栏目",
                        "year"      => "年代选择",
                        "select"    => "select下拉框",
                        "checkbox"  => "checkbox多选框",
                        "radio"     => "radio单选框");
        if ($input == "") {
            return $_input;
        } else {
            return $_input['input'];
        }
    }

    function fields_type($type = "")
    {
        $_type = array("varchar"    => "字符串[varchar]",
                       "int"        => "数值型[int]",
                       "text"       => "一般文本[text]",
                       "mediumtext" => "中型文本[mediumtext]",
                       "longtext"   => "大型文本[longtext]");
        if ($type == "") {
            return $_type;
        } else {
            return $_type['type'];
        }
    }

    function UpfileImage($data = array())
    {
        $error = "";

        //文件名
        $file = isset($data['file']) ? $data['file'] : "";
        if ($file == "") $error = -1;;

        $upfileDir = isset($data['upfile_dir']) ? $data['upfile_dir'] : "/data/upfiles/images/";//允许上传的文件类型
        $fileType = isset($data['upfile_type']) ? $data['upfile_type'] : array('jpg', 'gif', 'bmp', 'png');//上传图片
        $maxSize = isset($data['upfile_size']) ? $data['upfile_size'] : "300";//单位：KB
        $newDir = ROOT_PATH . "/" . $upfileDir;

        $cutWidth = isset($data['upfile_width']) ? $data['upfile_width'] : "300";//截图的宽
        $cutHeight = isset($data['upfile_height']) ? $data['upfile_height'] : "300";//截图的高
        $cutType = isset($data['cuttype']) ? $data['cuttype'] : "";//截图的类型
        $min_width = 10;//截图最小的宽度
        $min_height = 10;//截图最小的高度

        //判断是不是数组
        if (is_array($_FILES[$file]['name'])) {
            $_result = array();
            foreach ($_FILES[$file]['name'] as $i => $value) {

                if ($value != "") {
                    if ($_FILES[$file]['size'][$i] == 0) $error = -1;//文件不存在
                    if (!in_array(strtolower(substr($_FILES[$file]['name'][$i], -3, 3)), $fileType)) $error = -1;
                    if (strpos($_FILES[$file]['type'][$i], 'image') === false) $error = -1;
                    if ($_FILES[$file]['size'][$i] > $maxSize * 1024) $error = -2;
                    if ($_FILES[$file]['error'][$i] != 0) $error = -3;

                    //mkdirs($upfileDir,777);//创建文件夹

                    $newFile = md5(time() . rand(1, 9)) . $i . substr($_FILES[$file]['name'][$i], -4, 4);//新文件名
                    $oldFile = $_FILES[$file]['name'][$i];//旧文件名
                    $allFile = $newDir . $newFile; //

                    if (function_exists('move_uploaded_file')) {
                        $result = move_uploaded_file($_FILES[$file]['tmp_name'][$i], $allFile);

                    } else {
                        @copy($_FILES[$file]['tmp_name'][$i], $allFile);
                    }

                    /*是否截图 开始*/
                    if ($cutType == 1) {
                        /*获取图片的信息 开始*/
                        $pic_info = @getimagesize($allFile);
                        if ($pic_info[0] < $min_width || $pic_info[1] < $min_heigth) {
                            $error = -4;
                        } else {
                            //获取图片要压缩的比例
                            $re_scal = 1;
                            if ($pic_info[0] > $cutWidth) {
                                $re_scal = ($cutWidth / $pic_info[0]);
                            } elseif ($pic_info[1] > $cutHeight) {
                                $re_scal = ($cutHeight / $pic_info[1]);
                            }

                            if ($re_scal > 0) {
                                $re_width = round($pic_info[0] * $re_scal);
                                $re_height = round($pic_info[1] * $re_scal);
                            } else {
                                $re_width = $cutWidth;
                                $re_height = $cutHeight;
                            }

                            /*创建空图象 开始*/
                            $new_pic = @imagecreatetruecolor($re_width, $re_height);
                            if (!$new_pic) {
                                $error = -4;
                            } else {
                                //复制图象
                                if (function_exists("file_get_contents")) {
                                    $src = file_get_contents($allFile);
                                } else {
                                    $handle = fopen($allFile, "r");
                                    while (!feof($handle)) {
                                        $src .= fgets($fd, 4096);
                                    }
                                    fclose($handle);
                                }

                                /*输入文件 开始*/
                                if (!empty($src)) {
                                    $pic_creat = @ImageCreateFromString($src);
                                    if (!@imagecopyresampled($new_pic, $pic_creat, 0, 0, 0, 0, $re_width, $re_height, $pic_info[0], $pic_info[1])) {
                                        $error = -5;
                                    } else {
                                        //输出文件
                                        $out_file = '';
                                        switch ($pic_info['mime']) {
                                            case 'image/jpeg':
                                                $out_file = @imagejpeg($new_pic, $allFile);
                                                break;
                                            case 'image/gif':
                                                $out_file = @imagegif($new_pic, $allFile);
                                                break;
                                            case 'image/png':
                                                $out_file = @imagepng($new_pic, $allFile);
                                                break;
                                            case 'image/wbmp':
                                                $out_file = @imagewbmp($new_pic, $allFile);
                                                break;
                                            default:
                                                $error = 6;
                                                break;
                                        }
                                    }
                                }
                                /*输入文件 结束*/
                            }
                            /*创建空图象 结束*/
                        }
                        /*获取图片的信息 开始*/
                    }
                    /*是否截图 结束*/
                    if ($error == "") {
                        $_result[] = $upfileDir . $newFile;
                    }
                }
            }

            return $_result;
        }
    }


    /**
     * 上传图片
     *
     * @return Boolean
     */
    function upload($file, $type = "", $fileType = "", $upfileDir = "", $maxSize = "")
    {

        if ($_FILES[$file]['size'] == 0) return 0;
        if ($fileType == "") $fileType = array('jpg', 'gif', 'bmp', 'png');//允许上传的文件类型
        if ($upfileDir == "") $upfileDir = '/data/upfiles/litpics/'; //上传图片
        if ($maxSize == "") $maxSize = 300; //单位：KB

        if (!in_array(strtolower(substr($_FILES[$file]['name'], -3, 3)), $fileType)) return -1;
        if (strpos($_FILES[$file]['type'], 'image') === false) return -1;
        if ($_FILES[$file]['size'] > $maxSize * 1024) return -2;
        if ($_FILES[$file]['error'] != 0) return -3;

        $newDir = dirname(__FILE__) . "/.." . $upfileDir;

        $_upfileDir = explode("/", $upfileDir);
        foreach ($_upfileDir as $key => $value) {
            if ($value != "")
                $fileDir[] = $value;
        }
        /*
    	   foreach ($fileDir as $key => $value){
    	   $_fileDir = "";
    	   for($i=0;$i<=$key;$i++){
    	   $_fileDir .= $fileDir[$i]."/";
    	   }
    	   if (!is_dir("../".$_fileDir)){
    	   mkdir("../".$_fileDir,0777);
    	   }
    	   }
    	 */
        $newFile = date('Ymd') . time() . substr($_FILES[$file]['name'], -4, 4);
        $oldFile = $_FILES[$file]['name'];
        $allFile = $newDir . $newFile;

        if (function_exists('move_uploaded_file')) {
            $result = move_uploaded_file($_FILES[$file]['tmp_name'], $allFile);
            return array($upfileDir . $newFile, $type, $newFile, $oldFile, $_FILES[$file]['size']);
        } else {
            @copy($_FILES[$file]['tmp_name'], $allFile);
            return array($upfileDir . $newFile, $type, $newFile, $oldFile, $_FILES[$file]['size']);
        }
    }


    /**
     * 检查权限
     *
     * @param Varchar $purview (表示此文件的所需权限，比如other_all)
     * @param Varchar $admin_purview (管理员的权限值)
     * @return Bollen
     */
    function check_rank($purview)
    {
        global $_G;
        $_admin_purview = empty($_SESSION['purview']) ? "other_all" : $_SESSION['purview'];

        $admin_purview = explode(",", $_admin_purview);
        $_purview = explode("_", $purview);

        if (in_array("other_all", $admin_purview) || $_G['user_result']['type_id'] == 1) {
            return true;
        } else if (!in_array($purview, $admin_purview)) {
            /*
    		   var_dump($purview);
    		   var_dump($admin_purview);
    		   die();
    		 */
            echo "<script>alert('你没有权限');history.go(-1);</script>";
            exit;
        }
    }

    function post_maketime($name)
    {

        $var = array("year", "month", "date", "hour", "min");
        foreach ($var as $val) {
            $$val = !isset($_POST[$name . "_" . $val]) ? "0" : $_POST[$name . "_" . $val];
        }
        return mktime($hour, $min, 0, $month, $date, $year);

    }

    function post_area($nid = "")
    {
        $pname = $nid . "procvince";
        $cname = $nid . "city";
        $aname = $nid . "area";

        if (isset($_POST[$aname]) && $_POST[$aname] != "") {
            if ($_POST[$cname] == "") {
                $area = $_POST[$pname];
            } else {
                $area = $_POST[$aname];
            }
        } else {
            if (isset($_POST[$cname]) && $_POST[$cname] != "") {
                $area = $_POST[$cname];
            } else {
                $area = isset($_POST[$pname]) ? $_POST[$pname] : "";
            }
        }
        return $area;
    }

    function post_fields($fields)
    {
        $_fields = "";
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                $_fields[$value['nid']] = empty($_POST[$value['nid']]) ? "" : $_POST[$value['nid']];
            }
        }
        return $_fields;
    }

    function post_var($var, $type = "")
    {
        if ($type == "module") {
            $var = array("name", "status", "code", "order", "default_field", "description", "index_tpl", "list_tpl", "content_tpl", "article_status", "onlyone", "visit_type", "title_name", "issent", "version", "author", "type");

        }
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $_val = (isset($_POST[$val]) && $_POST[$val] != "") ? $_POST[$val] : "";
                if ($_val == "") {
                    $_val = NULL;
                } elseif (!is_array($_val)) {
                    /*if ($val!="content"){
    					$_val = nl2br($_val);
    				}*/
                } else {
                    $_val = join(",", $_val);
                }
                $result[$val] = $_val;

                if ($val == "area") {//地区
                    $result[$val] = post_area();
                } elseif ($val == "flag") {//地区
                    //$result[$val] = !isset($_POST["flag"])?NULL:join(",",$_POST["flag"]);
                    if (isset($_POST['flag']) && is_array($_POST['flag'])) {
                        $result[$val] = join(",", $_POST["flag"]);
                    } else {
                        $result[$val] = isset($_POST['flag']) ? $_POST['flag'] : NULL;
                    }
                } elseif ($val == "clearlitpic") {
                    if ($result["clearlitpic"] != "" && $result["clearlitpic"] == 1) {
                        $result['litpic'] = NULL;
                    }
                    unset($result["clearlitpic"]);
                } elseif ($val == "updatetime") {//地区
                    $result[$val] = time();
                } elseif ($val == "updateip") {//地区
                    $result[$val] = ip_address();
                } elseif ($_val == "content") {
                    $result[$val] = htmlspecialchars($result[$val]);
                }
            }

            return $result;
        } else {
            return (!isset($_POST[$var]) || $_POST[$var] == "") ? NULL : $_POST[$var];
        }
    }

    function gdversion()
    {
        static $gd_version_number = null;
        if ($gd_version_number === null) {
            ob_start();
            phpinfo(8);
            $module_info = ob_get_contents();
            ob_end_clean();
            if (preg_match("/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i", $module_info, $matches)) {
                $gdversion_h = $matches [1];
            } else {
                $gdversion_h = 0;
            }
        }
        return $gdversion_h;
    }

    function arc_page($total, $nowindex, $url)
    {
        $result = "<div class='hycms_pages'><ul><li><a>共" . $total . "页: </a></li>";
        for ($i = 1; $i <= $total; $i++) {
            if ($i == $nowindex) {
                $result .= "</li><li class='thisclass'><a href='#'>$i</a></li>";
            } else {
                $result .= "<li><a href='" . format_url("$url/$i") . "'>$i</a></li>";
            }
        }

        $result .= "</ul></div>";
        return $result;

    }

    static function get_mktime($mktime)
    {
        if ($mktime == "") return "";
        $dtime = trim(preg_replace("/[ ]{1,}/", " ", $mktime));
        $ds = explode(" ", $dtime);
        $ymd = explode("-", $ds[0]);
        if (isset($ds[1]) && $ds[1] != "") {
            $hms = explode(":", $ds[1]);
            $mt = mktime(empty($hms[0]) ? 0 : $hms[0], !isset($hms[1]) ? 0 : $hms[1], !isset($hms[2]) ? 0 : $hms[2], !isset($ymd[1]) ? 0 : $ymd[1], !isset($ymd[2]) ? 0 : $ymd[2], !isset($ymd[0]) ? 0 : $ymd[0]);
        } else {
            $mt = mktime(0, 0, 0, !isset($ymd[1]) ? 0 : $ymd[1], !isset($ymd[2]) ? 0 : $ymd[2], !isset($ymd[0]) ? 0 : $ymd[0]);
        }

        return $mt;
    }

    /**
     * 格式化路径
     */
    function format_url($url, $type = "", $isurl = "", $tplname = "")
    {
        global $system;
        if (is_array($isurl) && $isurl[0] == 1) {
            return "http://" . str_replace("http://", "", $isurl[1]);
        }
        if ($system['con_rewrite'] == 1) {
            $url = str_replace("?", "", $url);
            $_url = explode("/", $url);
            if (!isset($_url[1])) {
                $reurl = "list_" . $_url[0];
                if ($type != "sitelist" && isset($_REQUEST['page'])) {
                    $reurl .= "_" . $_REQUEST['page'];
                }
            } else {
                $reurl = "content_" . $_url[0] . "_" . $_url[1];
                if (isset($_url[2])) {
                    $reurl .= "_" . $_url[2];
                }
            }
            $reurl .= ".html";
            return $reurl;
        } elseif ($system['con_rewrite'] == 2) {
            return '';

        } else {
            return $url;
        }


    }

    /**
     * 格式化路径
     */
    function format_tpl($tpl, $var)
    {
        if ($tpl == "") return "";
        if (isset($var['code'])) {
            $tpl = str_replace("[code]", $var['code'], $tpl);
        }
        if (isset($var['site_id'])) {
            $tpl = str_replace("[site_id]", $var['site_id'], $tpl);
        }
        if (isset($var['id'])) {
            $tpl = str_replace("[id]", $var['id'], $tpl);
        }
        if (isset($var['nid'])) {
            $tpl = str_replace("[nid]", $var['nid'], $tpl);
        }
        $page = !isset($_REQUEST['page']) ? 1 : $_REQUEST['page'];
        $tpl = str_replace("[page]", $page, $tpl);

        return trim($tpl);

    }

    function get_ip_place()
    {
        $ip = file_get_contents("http://fw.qq.com/ipaddress");
        $ip = str_replace('"', ' ', $ip);
        $ip2 = explode("(", $ip);
        $a = substr($ip2[1], 0, -2);
        $b = explode(",", $a);
        return $b;
    }

    function gbk2utf8($str)
    {
        return $str;
        return iconv("GBK", "UTF-8", $str);
    }


    function maketime($name)
    {

        $var = array("year", "month", "date", "hour", "min");
        foreach ($var as $val) {
            $$val = !isset($_POST[$name . "_" . $val]) ? "0" : $_POST[$name . "_" . $val];
        }
        return mktime($hour, $min, 0, $month, $date, $year);

    }

    /**
     * 获取中文首个拼音字母
     * @param $input 中文字符 eg:中国
     */
    function getCnFirstChar($input)
    {

        $arr_input = array();
        $input = trim($input);
        $len = strlen($input);
        $str = '';
        for ($i = 0; $i < $len; $i++) {

            $str .= substr($input, $i, 1);
            if ($i % 2) {
                if ($str) {
                    array_push($arr_input, $str);
                }
                $str = '';
            }
        }
        if (empty ($arr_input)) {
            return '';
        }

        $word = '';
        foreach ($arr_input as $input) {

            $code = '';
            $asc = ord(substr($input, 0, 1)) * 256 + ord(substr($input, 1, 1)) - 65536;
            if ($asc >= -20319 and $asc <= -20284) $code = "A";
            if ($asc >= -20283 and $asc <= -19776) $code = "B";
            if ($asc >= -19775 and $asc <= -19219) $code = "C";
            if ($asc >= -19218 and $asc <= -18711) $code = "D";
            if ($asc >= -18710 and $asc <= -18527) $code = "E";
            if ($asc >= -18526 and $asc <= -18240) $code = "F";
            if ($asc >= -18239 and $asc <= -17923) $code = "G";
            if ($asc >= -17922 and $asc <= -17418) $code = "H";
            if ($asc >= -17417 and $asc <= -16475) $code = "J";
            if ($asc >= -16474 and $asc <= -16213) $code = "K";
            if ($asc >= -16212 and $asc <= -15641) $code = "L";
            if ($asc >= -15640 and $asc <= -15166) $code = "M";
            if ($asc >= -15165 and $asc <= -14923) $code = "N";
            if ($asc >= -14922 and $asc <= -14915) $code = "O";
            if ($asc >= -14914 and $asc <= -14631) $code = "P";
            if ($asc >= -14630 and $asc <= -14150) $code = "Q";
            if ($asc >= -14149 and $asc <= -14091) $code = "R";
            if ($asc >= -14090 and $asc <= -13319) $code = "S";
            if ($asc >= -13318 and $asc <= -12839) $code = "T";
            if ($asc >= -12838 and $asc <= -12557) $code = "W";
            if ($asc >= -12556 and $asc <= -11848) $code = "X";
            if ($asc >= -11847 and $asc <= -11056) $code = "Y";
            if ($asc >= -11055 and $asc <= -10247) $code = "Z";

            $word .= $code;
        }

        return strtoupper($word);
    }

    //导出excel格式表
    function exportData($filename, $title, $data, $colwidths = array())
    {
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=" . $filename . ".xls");
        echo "<table cellspacing=\"0\">";
        if (is_array($title)) {
            echo "<tr>";
            foreach ($title as $key => $value) {
                if (isset($colwidths[$key]) && $colwidths[$key] != '') {
                    $wstr = 'width:' . $colwidths[$key] . 'pt;';
                } else {
                    $wstr = '';
                }
                echo "<td style=\"" . $wstr . "text-align:center; font-weight:bold; border:solid 1px #666666;\">" . $value . "</td>";
            }
            echo "</tr>";
        }
        echo "\n";
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                echo "<tr>";
                foreach ($value as $_key => $_value) {
                    echo "<td style='vnd.ms-excel.numberformat:@; border:solid 1px #666666;'>" . $_value . "</td>";
                }
                echo "</tr>";
                echo "\n";
            }
        }
        echo "</table>";
    }


    /**
     * 获取属性的列表
     *
     * @param Array $fields_id
     * @param Array $order
     * @return Integer
     */
    function getFlagName($data = array())
    {
        $result = $data['result'];
        $flag = $data['flag'];
        $_flag = "";
        if (is_array($result)) {
            foreach ($result as $key => $value) {
                $flagres[$value['nid']] = $value['name'];
            }
            $flags = explode(",", $flag);
            foreach ($flags as $_key => $_value) {
                if ($_value != "") {
                    $_flag .= $flagres[$_value] . " ";
                }
            }
        }
        return $_flag;
    }


    /**
     * 将ID转化为URL格式
     *
     * @param Integer $goods_id
     * @param String(eg:goods_vps/goods_hire) $goods_type
     * @return String
     */
    static function Key2Url($key, $type)
    {
        return base64_encode($type . $key);
    }

    /**
     * 将URL格式的字符串转化为ID
     *
     * @param String $str
     * @return Array(goods_type, goods_id)
     */
    static function Url2Key($key, $type)
    {
        $key = base64_decode(urldecode($key));
        return explode($type, $key);
    }

    function _key2url($key, $type = 'bid', $separator = ',')
    {
        global $DES_key, $DES_iv;
        include_once(ROOT_PATH . "plugins/id5/SynPlat/DES.php");
        $DES = new DES($DES_key, $DES_iv);
        return bin2hex($DES->encrypt(implode($separator, array($key, $type))));
    }

    function _url2key($key, $separator = ',')
    {
        global $DES_key, $DES_iv;
        include_once(ROOT_PATH . "plugins/id5/SynPlat/DES.php");
        $DES = new DES($DES_key, $DES_iv);
        return explode($separator, $DES->decrypt(hex2bin($key)));
    }

    /**
     * 加密激活链接
     * @param $id
     * @return String
     */
    function EnActionCode($id, $key)
    {

        return base64_encode((string)($id * 3 + $key));
    }

    /**
     * 解密激活链接
     * @param $id
     * @return String
     */
    function DeActionCode($id, $key)
    {
        return (base64_decode($id) - $key) / 3;
    }

    static function RegEmailMsg($data = array())
    {
        $user_id = $data['user_id'];
        $username = $data['username'];
        $webname = $data['webname'];
        $email = $data['email'];
        $query_url = isset($data['query_url']) ? $data['query_url'] : "action/active";
        if ($data['changeMail']) {
            $active_id = urlencode(self::authcode($user_id . "," . time() . "," . $data['code'], "ENCODE"));
        } else {
            $active_id = urlencode(self::authcode($user_id . "," . time(), "ENCODE"));
        }
        $_url = Yii::app()->createUrl("/newuser/main/mailActive?id={$active_id}");
        $send_email_msg = '
    		亲爱的<span style="font-weight:bold;">用户</span>，您好<br/>
    		感谢您注册' . $webname . '，您登录的邮箱账号为<span style="font-weight:bold;">' . $email . '</span><br/>            
    		请点击下面的链接完成邮箱激活，激活邮箱后，您可享受ITZ的更多服务。<br/>            
    		<a href="' . $_url . '" style=" _display:inline; display:inline-block; margin:10px 0; border-radius:5px; padding:5px 18px 3px; height:25px; line-height:25px; color:#fff;background:url(https://www.xxx.com/themes/ruizhict/static/css/img/btns_bg_r-m.png) repeat-x left top #B40B0B; text-align:center; text-decoration:none;">点击完成激活</a><br/> 
    		或点击下面的链接:<a href="' . $_url . '" target="_blank">' . $_url . '</a><br/>            
    		如果链接无法点击，请将它拷贝到浏览器地址栏中直接访问。
    		';
        return $send_email_msg;
    }

    /**
     * 用户中心新版认证邮箱
     * @param array $data
     * @return string
     */
    static function EmailMsg($data = array())
    {
        $user_id = $data['user_id'];
        $username = $data['username'];
        $webname = $data['webname'];
        $email = $data['email'];
        $query_url = isset($data['query_url']) ? $data['query_url'] : "action/active";
        if ($data['changeMail']) {
            $active_id = urlencode(self::authcode($user_id . "," . time() . "," . $data['code'], "ENCODE"));
        } else {
            $active_id = urlencode(self::authcode($user_id . "," . time(), "ENCODE"));
        }
        $_url = Yii::app()->createUrl("/user/safeAjax/mailActive?id={$active_id}");
        $send_email_msg = '
    		亲爱的<span style="font-weight:bold;">用户</span>，您好<br/>
    		感谢您注册' . $webname . '，您登录的邮箱账号为<span style="font-weight:bold;">' . $email . '</span><br/>            
    		请点击下面的链接完成邮箱激活，激活邮箱后，您可享受ITZ的更多服务。<br/>            
    		<a href="' . $_url . '" style=" _display:inline; display:inline-block; margin:10px 0; border-radius:5px; padding:5px 18px 3px; height:25px; line-height:25px; color:#fff;background:url(https://www.xxx.com/themes/ruizhict/static/css/img/btns_bg_r-m.png) repeat-x left top #B40B0B; text-align:center; text-decoration:none;">点击完成激活</a><br/> 
    		或点击下面的链接:<a href="' . $_url . '" target="_blank">' . $_url . '</a><br/>            
    		如果链接无法点击，请将它拷贝到浏览器地址栏中直接访问。
    		';
        return $send_email_msg;
    }

    static function GetpwdMsgNew($data = array())
    {
        $user_id = $data['user_id'];
        $username = $data['username'];
        $webname = $data['webname'];
        $email = $data['email'];
        if ($data['changePwd']) {
            $active_id = urlencode(self::authcode($user_id . "," . time() . "," . $data['code'], "ENCODE"));
        } else {
            $active_id = urlencode(self::authcode($user_id . "," . time(), "ENCODE"));
        }
        $_url = Yii::app()->createUrl("/user/account/ForgetPwdStep2Email?id={$active_id}");
        $send_email_msg = '
    			亲爱的<span style="font-weight:bold;">用户</span>，您好<br/>
    			请点击下面的连接修改密码。<br/>
    			确认你的账号将让你使用 ITZ的全部服务,往后的提醒也将发送至该电子邮件地址。<br/>
    			<a href="' . $_url . '" style=" _display:inline; display:inline-block; margin:10px 0; border-radius:5px; padding:5px 18px 3px; height:25px; line-height:25px; color:#fff;background:url(https://www.xxx.com/themes/ruizhict/static/css/img/btns_bg_r-m.png) repeat-x left top #B40B0B; text-align:center; text-decoration:none;">点击重置密码</a><br/>
    			或点击下面的链接:<a href="' . $_url . '" target="_blank">' . $_url . '</a><br/>
    			如果链接无法点击，请将它拷贝到浏览器地址栏中直接访问。
    		';
        return $send_email_msg;
    }

    static function GetpwdMsg($data = array())
    {
        $user_id = $data['user_id'];
        $username = $data['username'];
        $webname = $data['webname'];
        $email = $data['email'];
        if ($data['changePwd']) {
            $active_id = urlencode(self::authcode($user_id . "," . time() . "," . $data['code'], "ENCODE"));
        } else {
            $active_id = urlencode(self::authcode($user_id . "," . time(), "ENCODE"));
        }
        $_url = Yii::app()->createUrl("/newuser/index/ForgetPwdStep2Email?id={$active_id}");
        $send_email_msg = '
    			亲爱的<span style="font-weight:bold;">用户</span>，您好<br/>
    			请点击下面的连接修改密码。<br/>            
    			确认你的账号将让你使用 ITZ的全部服务,往后的提醒也将发送至该电子邮件地址。<br/>            
    			<a href="' . $_url . '" style=" _display:inline; display:inline-block; margin:10px 0; border-radius:5px; padding:5px 18px 3px; height:25px; line-height:25px; color:#fff;background:url(https://www.xxx.com/themes/ruizhict/static/css/img/btns_bg_r-m.png) repeat-x left top #B40B0B; text-align:center; text-decoration:none;">点击重置密码</a><br/> 
    			或点击下面的链接:<a href="' . $_url . '" target="_blank">' . $_url . '</a><br/>            
    			如果链接无法点击，请将它拷贝到浏览器地址栏中直接访问。
    		';
        return $send_email_msg;
    }

    //或得用户的头像
    function get_avatar($data = array())
    {
        $uid = isset($data['user_id']) ? $data['user_id'] : "";
        $size = isset($data['size']) ? $data['size'] : "big";
        $type = isset($data['type']) ? $data['type'] : "";

        $istrue = isset($data['istrue']) ? $data['istrue'] : false;
        $size = in_array($size, array('big', 'middle', 'small')) ? $size : 'big';
        $uid = abs(intval($uid));

        $typeadd = $type == 'real' ? '_real' : '';
        if (is_file('data/avatar/' . $uid . $typeadd . "_avatar_$size.jpg")) {
            if ($istrue) return true;
            return '/data/avatar/' . $uid . $typeadd . "_avatar_$size.jpg";
        } else {
            if ($istrue) return false;
            return "/data/images/avatar/noavatar_{$size}.gif";
        }
    }
    
    static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    	// 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    	$ckey_length = 4;
    	// 密匙
    	$key = md5($key ? $key : "I10Tou20Zi09Com");
    	// 密匙a会参与加解密
    	$keya = md5(substr($key, 0, 16));
    	// 密匙b会用来做数据完整性验证
    	$keyb = md5(substr($key, 16, 16));
    	// 密匙c用于变化生成的密文
    	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    	// 参与运算的密匙
    	$cryptkey = $keya.md5($keya.$keyc);
    	$key_length = strlen($cryptkey);
    	// 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    	// 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    	$string_length = strlen($string);
    	$result = '';
    	$box = range(0, 255);
    	$rndkey = array();
    
    	// 产生密匙簿
    	for($i = 0; $i <= 255; $i++) {
    		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
    	}
    
    	// 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    	for($j = $i = 0; $i < 256; $i++) {
    		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
    		$tmp = $box[$i];
    		$box[$i] = $box[$j];
    		$box[$j] = $tmp;
    	}
    
    	// 核心加解密部分
    	for($a = $j = $i = 0; $i < $string_length; $i++) {
    		$a = ($a + 1) % 256;
    		$j = ($j + $box[$a]) % 256;
    		$tmp = $box[$a];
    		$box[$a] = $box[$j];
    		$box[$j] = $tmp;
    		// 从密匙簿得出密匙进行异或，再转成字符
    		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    	}
    
    
    	if($operation == 'DECODE') {
    		// substr($result, 0, 10) == 0 验证数据有效性
    		// substr($result, 0, 10) - time() > 0 验证数据有效性
    		// substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
    		// 验证数据有效性，请看未加密明文的格式
    		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
    			return substr($result, 26);
    		} else {
    			return '';
    		}
    	} else {
    		// 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
    		// 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
    		return $keyc.str_replace('=', '', base64_encode($result));
    	}
    }
    
    function show_pages($data = array())
    {
        $total = (int)$data['total'];
        $page = (int)$data['page'];
        $epage = (int)$data['epage'];
        if ($total == 0) {
            return "没有信息";
        }
        if ($total % $epage) {
            $page_num = (int)($total / $epage) + 1;
        } else {
            $page_num = $total / $epage;
        }
        //判断有多少页
        if ($page == "") {
            $page = 1;
        }
        $action = htmlspecialchars(strstr($_SERVER['REQUEST_URI'], "?"), ENT_QUOTES, 'UTF-8', true);

        $first_url = "index.html" . $action;
        $up_url = "index" . ($page - 1) . ".html" . $action;
        $last_url = "index" . $page_num . ".html" . $action;
        $down_url = "index" . ($page + 1) . ".html" . $action;

        if ($page != 1 && $page > $page_num) {
            header("location:index{$page_num}.html");
        }

        $display = "<b>共" . $total . "条</b>";

        $display .= " {$epage}条/页<span class='page_line'>|</span>第{$page}/{$page_num}页";

        //第一页
        if ($page == 1) {
            $display .= ' <span class="no_page page-ctrl page-ctrl_first">第一页</span>';
        } else {
            $display .= " <a href='{$first_url}' class='page-ctrl page-ctrl_first'>第一页</a>";
        }

        //上一页
        if ($page == 1) {
            $display .= ' <span class="no_page page-ctrl page-ctrl_prev">上一页</span>';
        } else {
            $display .= " <a href='{$up_url}' class='page-ctrl page-ctrl_prev'>上一页</a>";
        }

        if ($page_num > 5) {
            if ($page < 3) {
                $j = 1;
                $n = 5;
            } else {
                if ($page + 2 > $page_num) {
                    $j = $page_num - 4;
                    if ($j <= 0) $j = 1;
                    $n = $page_num;
                } else {
                    $j = $page - 2;
                    if ($j <= 0) $j = 1;
                    $n = $page + 2;
                }
            }
        } else {
            $j = $page - 2;
            if ($j <= 0) $j = 1;
            $n = $page + 2;
            if ($n > $page_num) $n = $page_num;
        }

        for ($i = $j; $i <= $n; $i++) {
            if ($i == $page) {
                $display .= " <span class='this_page'>{$i}</span>";
            } else {
                $display .= " <a href='index{$i}.html{$action}'>$i</a>";
            }
        }


        //下一页
        if ($page == $page_num) {
            $display .= ' <span class="no_page page-ctrl page-ctrl_next">下一页</span>';
        } else {
            $display .= " <a href='{$down_url}' class='page-ctrl page-ctrl_next'>下一页</a>";
        }

        //最后一页
        if ($page == $page_num) {
            $display .= ' <span class="no_page page-ctrl page-ctrl_last">最后一页</span>';
        } else {
            $display .= " <a href='{$last_url}' class='page-ctrl page-ctrl_last'>最后一页</a>";
        }
        //$display .=' <span class="page_go">转到<input type="text" id="page_text" size="4" onkeydown="if (event.keyCode==13){location.href =\'index\'+this.value+\'.html\'}"  value="'.$page.'"  onfocus="this.select()" />页</span>';
        return $display;
    }


    function nltobr($string = "")
    {
        if ($string == "") return "";
        $string = str_replace(" ", "&nbsp;", $string);
        $string = nl2br($string);
        return $string;
    }


    //去掉相应的参数
    function url_format($url, $format = '')
    {
        if ($url == "") return "?";
        $_url = explode("?", $url);
        $_url_for = "";
        if (isset($_url[1]) && $_url[1] != "") {
            $request = $_url[1];
            if ($request != "") {
                $_request = explode("&", $request);
                foreach ($_request as $key => $value) {
                    $_value = explode("=", $value);
                    if (trim($_value[0]) != $format) {
                        $_url_for = $_url_for . "&" . $value;
                    }
                }
            }
            $_url_for = substr($_url_for, 1, strlen($_url_for));
        }
        return "?" . $_url_for;
    }

    //获得时间天数
    function get_times($data = array())
    {
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];//时间
        } elseif (isset($data['date']) && $data['date'] != "") {
            $time = strtotime($data['date']);//日期
        } else {
            $time = time();//现在时间
        }
        if (isset($data['type']) && $data['type'] != "") {
            $type = $data['type'];//时间转换类型，有day week month year
        } else {
            $type = "month";
        }
        if (isset($data['num']) && $data['num'] != "") {
            $num = $data['num'];
        } else {
            $num = 1;
        }

        if ($type == "month") {
            $month = date("m", $time);
            $year = date("Y", $time);
            $_result = strtotime("$num month", $time);
            $_month = (int)date("m", $_result);
            if ($month + $num > 12) {
                $_num = $month + $num - 12;
                $year = $year + 1;
            } else {
                $_num = $month + $num;
            }

            if ($_num != $_month) {

                $_result = strtotime("-1 day", strtotime("{$year}-{$_month}-01"));
            }
        } else {
            $_result = strtotime("$num $type", $time);
        }
        if (isset($data['format']) && $data['format'] != "") {
            return date($data['format'], $_result);
        } else {
            return $_result;
        }

    }

    //编码格式转换
    function diconv($str, $in_charset, $out_charset = CHARSET, $ForceTable = FALSE)
    {
        global $_G;

        $in_charset = strtoupper($in_charset);
        $out_charset = strtoupper($out_charset);
        if ($in_charset != $out_charset) {
            require_once ROOT_PATH . 'core/chinese.class.php';
            $chinese = new Chinese($in_charset, $out_charset, $ForceTable);
            $strnew = $chinese->Convert($str);
            if (!$ForceTable && !$strnew && $str) {
                $chinese = new Chinese($in_charset, $out_charset, 1);
                $strnew = $chinese->Convert($str);
            }
            return $strnew;
        } else {
            return $str;
        }
    }

    /*
     * CopyRight: zxing
     * Document: 检查符合 GB11643-1999 标准的身份证号码的正确性
     * File:gb11643_1999.func.php Fri Mar 28 09:42:41 CST 2008 zxing
     * Updated:Fri Mar 28 09:42:41 CST 2008
     * Note: 调用函数 check_id();
     */

    /*
     * 函数功能：计算身份证号码中的检校码
     * 函数名称：idcard_verify_number
     * 参数表 ：string $idcard_base 身份证号码的前十七位
     * 返回值 ：string 检校码
     * 更新时间：Fri Mar 28 09:50:19 CST 2008
     */
    static function idcard_verify_number($idcard_base)
    {
        if (strlen($idcard_base) != 17) {
            return false;
        }
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); //debug 加权因子
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); //debug 校验码对应值
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    /*
     * 函数功能：将15位身份证升级到18位
     * 函数名称：idcard_15to18
     * 参数表 ：string $idcard 十五位身份证号码
     * 返回值 ：string
     * 更新时间：Fri Mar 28 09:49:13 CST 2008
     */
    static function idcard_15to18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        } else {// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . self::idcard_verify_number($idcard);
        return $idcard;
    }

    /*
     * 函数功能：18位身份证校验码有效性检查
     * 函数名称：idcard_checksum18
     * 参数表 ：string $idcard 十八位身份证号码
     * 返回值 ：bool
     * 更新时间：Fri Mar 28 09:48:36 CST 2008
     */
    static function idcard_checksum18($idcard)
    {
        if (APP_DEBUG === true) {
            return true;
        }
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);
        if (self::idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }

    /*
     * 函数功能：身份证号码检查接口函数
     * 函数名称：check_id
     * 参数表 ：string $idcard 身份证号码
     * 返回值 ：bool 是否正确
     * 更新时间：Fri Mar 28 09:47:43 CST 2008
     */
    static function isIdCard($idcard)
    {
        if (APP_DEBUG === true) {
            return true;
        }
        if (strlen($idcard) == 15 || strlen($idcard) == 18) {
            if (strlen($idcard) == 15) {
                $idcard = self::idcard_15to18($idcard);
            }
            if (self::idcard_checksum18($idcard)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /*
     * 函数功能：身份证号码返回性别
     * 函数名称：check_id
     * 参数表 ：string $idcard 身份证号码
     * 返回值 ：1 : 男，2 : 女，3 : 身份证号码不对
     */
    static function getSexFromIdCard($idcard)
    {
        $sex = 1;
        if (self::isIdCard($idcard)) {
            if (strlen($idcard) == 15) {
                $idcard = self::idcard_15to18($idcard);
            }
            $sexInt = (int)substr($idcard, 16, 1);
            $sex = $sexInt % 2 === 0 ? 2 : 1;
        } else {
            $sex = 3;
        }
        return $sex;
    }

    /*
     * 函数功能：身份证号码判断年龄
     * 函数名称：check_id
     * 参数表 ：string $idcard 身份证号码
     * 返回值 ：bool 是否正确
     * 更新时间：Fri Mar 28 09:47:43 CST 2008
     */
    static function isIdCard18($idcard)
    {
        if (APP_DEBUG === true) {
            return 20;
        }

        $age = 0;

        if (strlen($idcard) == 15 || strlen($idcard) == 18) {
            if (strlen($idcard) == 15) {
                $idcard = self::idcard_15to18($idcard);
            }

            $by = substr($idcard, 6, 4);
            $bm = substr($idcard, 10, 2);
            $bd = substr($idcard, 12, 2);
            $cm = date('n');
            $cd = date('j');
            $age = date('Y') - $by - 1;
            if ($cm > $bm || $cm == $bm && $cd >= $bd) $age++;
        }
        return $age;
    }

    /*
     *判断是否为手机号
     */
    static function IsMobile($no)
    {
        return preg_match('/^1[\d]{10}$/', $no);
    }

    /*
     *手机验证码
     */
    static function VerifyCode($code = 0)
    {
        $verifycode = $code ? $code : rand(100000, 999999);
        $verifycode = str_replace('1989', '9819', $verifycode);
        $verifycode = str_replace('1259', '9521', $verifycode);
        $verifycode = str_replace('12590', '95210', $verifycode);
        $verifycode = str_replace('10086', '68001', $verifycode);
        return $verifycode;
    }

    static function MaskCardID($card_id, $num = 1)
    {
        $len = strlen($card_id);
        if (strlen($card_id) <= 2 * $num) {
            return '******************';
        } else {
            $pre = substr($card_id, 0, $num);
            $sub = substr($card_id, $len - $num, $num);
            $ret = $pre;
            for ($i = $num; $i < $len - $num; $i++) {
                $ret .= '*';
            }
            return $ret . $sub;
        }
    }

    static function MaskTel($tel = 0)
    {

        $tel = strval($tel);

        if (strlen($tel) == 11) {
            $tel[3] = '*';
            $tel[4] = '*';
            $tel[5] = '*';
            $tel[6] = '*';
        }
        return $tel;
    }

    /**
     * 邮箱保留前两位脱敏
     * @param string $email
     * @return string
     */
    static function MaskEmail($email = '')
    {
        if (empty($email)) {
            return '';
        }
        $email = explode('@', $email);
        $return_email = substr($email[0], 0, 2);
        $len = strlen(substr($email[0], 2));
        for ($i = 0; $i < $len; $i++) {
            $return_email .= '*';
        }
        $return_email .= '@' . $email[1];
        return $return_email;
    }

    //实名脱敏
    static function MaskName($name = '')
    {
        if (empty($name)) {
            return '';
        }

        return '**' . mb_substr($name, -1, 1, 'utf-8');
    }

    //银行卡脱敏
    static function MaskBankCard($number = '')
    {
        if (empty($number)) {
            return '';
        }

        return '****' . mb_substr($number, -4, 4, 'utf-8');
    }

    /**
     * 获取手机号所属运营商
     * @param: string $phone
     * @return string $operator (CT：China Telecom  CM：China Mobile  CU：China Unicom)
     **/
    function GetMobileOperator($phone)
    {
        $CM = array('134', '135', '136', '137', '138', '139', '150', '151', '152', '158', '159', '157', '187', '188', '147', '183');
        $CU = array('130', '131', '132', '155', '156', '185', '186', '176');
        $CT = array('133', '153', '180', '189');

        $operator = '';

        if (in_array(substr($phone, 0, 3), $CT))
            $operator = 'CT';
        if (in_array(substr($phone, 0, 3), $CM))
            $operator = 'CM';
        if (in_array(substr($phone, 0, 3), $CU))
            $operator = 'CU';

        return $operator;
    }

    /**
     *
     * 计算问卷的分数
     * @param: array $answer_a
     * @return ineger score
     **/
    static function get_qn_score($answer_a)
    {
        $a = array(
            '1'  => array(
                '1' => 0,
                '2' => 10,
                '3' => 10,
                '4' => 10,
            ),
            '2'  => array(
                '1' => 10,
                '2' => 10,
                '3' => 10,
                '4' => 10,
            ),
            '3'  => array(
                '1' => 10,
                '2' => 10,
                '3' => 10,
                '4' => 10,
            ),
            '4'  => array(
                '1' => 5,
                '2' => 10,
                '3' => 10,
                '4' => 10,
            ),
            '5'  => array(
                '1' => 5,
                '2' => 5,
                '3' => 10,
                '4' => 10,
            ),
            '6'  => array(
                '1' => 10,
                '2' => 10,
                '3' => 10,
                '4' => 0,
            ),
            '7'  => array(
                '1' => 0,
                '2' => 10,
                '3' => 10,
                '4' => 10,
            ),
            '8'  => array(
                '1' => 0,
                '2' => 5,
                '3' => 10,
                '4' => 10,
            ),
            '9'  => array(
                '1' => 10,
                '2' => 0,
            ),
            '10' => array(
                '1' => 10,
                '2' => 0,
            )
        );
        $score = 0;
        foreach ($answer_a as $k => $v) {
            if (isset($a[$k][$v])) {
                $score += $a[$k][$v];
            }
        }
        return $score;
    }

    /**
     * BC match 库：是否相等函数
     * @param string $f1
     * @param string $f2
     * @param number $scale 精度，默认10
     * @return boolean f1=f2返回true
     */
    static function float_equal_bc($f1, $f2, $scale = 10)
    {
        $res = bccomp($f1, $f2, $scale);
        return $res == 0 ? true : false;
    }

    /**
     * BC match 库：比较大小函数
     * @param string $f1
     * @param string $f2
     * @param number $scale 精度，默认10
     * @return boolean $f1>$f2返回true
     */
    static function float_bigger_bc($f1, $f2, $scale = 10)
    {
        $res = bccomp($f1, $f2, $scale);
        return $res == 1 ? true : false;
    }

    /*
     * 浮点数比较函数
     */
    static function float_equal($f1, $f2, $precision = 10)
    {// are 2 floats equal
        $e = pow(0.1, ($precision < 3 ? 3 : $precision));
        return (abs($f1 - $f2) < $e);
        /*
        $e = pow(10,$precision);
        $i1 = intval($f1 * $e);
        $i2 = intval($f2 * $e);
        return ($i1 == $i2);
        */
    }

    static function float_bigger($big, $small, $precision = 10)
    {// is one float bigger than another
        $e = pow(10, $precision);
        $ibig = intval($big * $e);
        $ismall = intval($small * $e);
        return ($ibig > $ismall);
    }

    static function float_bigger_equal($big, $small, $precision = 10)
    {// is on float bigger or equal to another
        $e = pow(10, $precision);
        $ibig = intval($big * $e);
        $ismall = intval($small * $e);
        return ($ibig >= $ismall);
    }

    static function is_int_money($money)
    {
        $m1 = sprintf("%.2f", $money);
        $m2 = sprintf("%.2f", sprintf("%d", $money));
        return self::float_equal($m1, $m2, 5);
    }

    /**
     * general_apr_calcu
     *
     */
    static function general_apr_calcu($input_money, $output_money, $days)
    {
        if ($input_money == 0 || $days == 0) {
            return 0.00;
        }
        $profit = $output_money - $input_money;
        $profit = floatval($profit);
        $capital = floatval($input_money);
        $apr = ($profit * 365 * 100) / ($days * $capital);
        $apr = round($apr, 2);
        return $apr;
    }

    /**
     * general_apr_calcu buyer
     * @param float $y_interest 未支付收益(总)
     * @param float $curScale 转让钱数/总钱数的结果
     * @param float $curNum 折让金 例: -1.26
     * @param int $money 转让钱数（原始）
     * @param int $days 天数
     */
    static function general_apr_calcu_buy($y_interest, $curScale, $curNum, $money, $days)
    {
        if ($y_interest == 0 || $days == 0) {
            return 0.00;
        }
        $rs = (($y_interest * $curScale + $curNum) / ($money - $curNum)) * (365 / $days);
        $apr = round($rs * 100, 2);
        return $apr;
    }

    //临时增加的函数 需要迁移到cdn上 才能支持分布式
    static function avatar($user_id, $size = 'middle')
    {
        if (is_file('/home/work/xxx.com/xxx.com/data/avatar/' . $user_id . "_avatar_$size.jpg")) {
            return '/data/avatar/' . $user_id . "_avatar_$size.jpg";
        } else {
            return "/data/images/avatar/noavatar_{$size}.gif";
        }
    }

    //php生成guid方法
    function get_guid()
    {
        mt_srand((double)microtime() * 10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));

        $guid = substr($charid, 0, 8) . '-' .
            substr($charid, 8, 4) . '-' .
            substr($charid, 12, 4) . '-' .
            substr($charid, 16, 4) . '-' .
            substr($charid, 20, 12);
        return $guid;
    }

    /*
       函数名称：verify_id()
       函数作用：校验提交的ID类值是否合法
       参　　数：$id: 提交的ID值
       返 回 值：返回处理后的ID
     */
    static function verify_id($id = null)
    {
        if (empty($id)) {
            return $id;
        } else if (self::inject_check($id) || !is_numeric($id)) {
            exit('提交的参数非法！');
        }
        $id = intval($id); // 整型化
        return $id;
    }

    static function verify_id_list($id_list)
    {
        $id_arr = explode(",", strval($id_list));
        foreach ($id_arr as $_key => $_value) {
            $id_arr[$_key] = self::verify_id(trim($_value));
        }
        return join(",", $id_arr);
    }

    /*
        函数名称：inject_check()
        函数作用：检测提交的值是不是含有SQL注射的字符，防止注射，保护服务器安全
        参　　数：$sql_str: 提交的变量
        返 回 值：返回检测结果，ture or false
    */
    function inject_check($sql_str)
    {
        return preg_match('/select|insert|and|or|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile/i', $sql_str); // 进行过滤
    }

    /*
        函数名称：generate_menu()
        函数作用：生成二级导航
        参   数：$items：menu array
        返 回 值：返回html
        edited by dlj 2014/12/16
    */
    function generate_menu($items)
    {
        $req_url = $_SERVER['REQUEST_URI'];

        if (count($items) >= 1) {
            $result = '';
            foreach ($items as $index => $li) {
                $exist = false;
                if (!isset($li['visible']) || $li['visible'] == 1) {
                    if (isset($li['items'])) {  //有 第二级
                        $lis = '';
                        /*
                            遍历到二级菜单
                        */
                        foreach ($li['items'] as $key => $value) {
                            if (!isset($value['visible']) || $value['visible'] == 1) {
                                if (strcasecmp($req_url, $value['url']) == 0 ||
                                    //手机认证短信日志特殊处理
                                    (strcasecmp($value['url'], substr($req_url, 0, (stripos($req_url, "?") == false ? strlen($req_url) : stripos($req_url, "?")))) == 0 && $req_url != '/user/userSmsLog/admin?UserSmsLog[stype]=mob_auth')
                                ) { //二级菜单的url必须跟req_url(或者req_url去掉查询参数)一样

                                    $exist = true;
                                    //面包屑
                                    $GLOBALS['$GOLBAL_breadcrumb'] = array(
                                        $li['label']    => $li['url'] ? $li['url'] : "",
                                        $value['label'] => $value['url'],
                                    );

                                    $this->pageTitle = $value['label'];
                                    // 本级选中
                                    $lis = $lis . '<li class="active"><a href="' . $value['url'] . '">' . $value['label'] . '</a></li>';
                                } else {
                                    /*
                                        遍历到三级菜单
                                    */
                                    $level2 = "";
                                    if (isset($value['items'])) { //第三级
                                        $findFlag = false;
                                        foreach ($value['items'] as $key => $value1) {
                                            //判断$value1['url']是否有参数
                                            $req_url1 = $req_url;
                                            if (stristr($value1['url'], "?") == false) {
                                                $req_url1 = substr($req_url, 0, (stripos($req_url, "?") == false ? strlen($req_url) : stripos($req_url, "?")));
                                                // var_dump($req_url1);
                                            }
                                            // var_dump($req_url1."---".$value1['url']);
                                            if (strcasecmp($req_url1, $value1['url']) == 0) {
                                                // var_dump($value1['url']);
                                                $findFlag = true;
                                                $exist = true;
                                                //面包屑
                                                $GLOBALS['$GOLBAL_breadcrumb'] = array(
                                                    $li['label']     => $li['url'] ? $li['url'] : "",
                                                    $value['label']  => $value['url'],
                                                    $value1['label'] => "",
                                                );

                                                $this->pageTitle = $value['label'];
                                            }
                                        }
                                        if ($findFlag) {
                                            // 上一级选中
                                            $level2 = '<li class="active"><a href="' . $value['url'] . '">' . $value['label'] . '</a></li>';
                                        } else {
                                            // 上一级未选中
                                            $level2 = '<li><a href="' . $value['url'] . '">' . $value['label'] . '</a></li>';
                                        }
                                    } else {
                                        // 本级未选中
                                        $level2 = '<li><a href="' . $value['url'] . '">' . $value['label'] . '</a></li>';
                                    }
                                    $lis = $lis . $level2;
                                }
                            }
                        };
                        if ($exist == true) {
                            if ($li['url']) {
                                $result = $result . '<div class="back_eaeaea"><p><b><a href="' . $li['url'] . '">' . $li['label'] . '</a></b></p><ul class="cn disBlock" >' . $lis;
                            } else {
                                $result = $result . '<div class="back_eaeaea"><p><s class="slideDown"></s><b>' . $li['label'] . '</b></p><ul class="cn disBlock" >' . $lis;
                            }
                        } else {
                            if ($li['url']) {
                                $result = $result . '<div><p><b><a href="' . $li['url'] . '">' . $li['label'] . '</a></b></p><ul>' . $lis;
                            } else {
                                $result = $result . '<div><p><s></s><b>' . $li['label'] . '</b></p><ul>' . $lis;
                            }
                        }
                    } else {
                        if ($li['url']) {
                            if (stristr($req_url, $li['url'])) {
                                $result = $result . '<div><p><b><a href="' . $li['url'] . '">' . $li['label'] . '</a></b></p><ul class="cn disBlock" >' . $lis;
                            } else {
                                $result = $result . '<div><p><b><a href="' . $li['url'] . '">' . $li['label'] . '</a></b></p><ul>' . $lis;
                            }
                        } else {
                            if (stristr($req_url, $li['url'])) {
                                $result = $result . '<div><p><s></s><b>' . $li['label'] . '</b></p><ul class="cn disBlock" >' . $lis;
                            } else {
                                $result = $result . '<div><p><s></s><b>' . $li['label'] . '</b></p><ul>' . $lis;
                            }
                        }
                    }
                    $result = $result . '</ul></div>';
                }
            }
            // var_dump($result);die;
            return $result;
        }
    }

    /*
        函数名称：buildBreadmenu()
        函数作用：生成面包屑
        参   数：$breadmenu：menu array
        返 回 值：返回html
        created by dlj
    */

    function buildBreadmenu($breadmenu)
    {
        if (isset($breadmenu) && !empty($breadmenu)) {
            $return = "<div class='breadCrumb'>";
            foreach ($breadmenu as $key => $value) {
                if ($value == "") {
                    $return .= "<span>" . $key . "</span>";
                } else {
                    $return .= "<a href=" . $value . ">" . $key . "</a>";
                }
                $return .= "&nbsp;>&nbsp;";
            }

            return substr($return, 0, (strlen($return) - strlen("&nbsp;>&nbsp;"))) . "</div>";
        } else {
            return "";
        }
    }

    /**
     * 获得随机数字字符串
     */
    static function get_random_nstr($len = 4)
    {
        if (!is_numeric($len) || $len < 1) {
            return '0000';
        } else {
            $rand_str = "";
            $str_imgcode = "123456789ABCDEFGH";
            for ($i = 0; $i < 4; $i++) {
                $rand_str .= $str_imgcode[mt_rand(0, 16)];
            }
            return $rand_str;
        }
    }

    static function make_last_day_of_month($str)
    {
        $a = preg_split("#/#", $str);
        $y = $a[0];
        $m = $a[1];
        $tt = strtotime("$y-$m-01 00:00:00");
        $dd = strtotime(" +1 month", $tt);
        $dd = date('n月', strtotime(" -1 day", $dd));
        return $dd;
    }

    function getLuhn($luhn)
    {
        //此乃验证银行卡算法。是从js验证转过来的。Luhn算法
        $len = strlen($luhn);
        $mul = 0;
        $sum = 0;
        $prodArr = array(
            array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9),
            array(0, 2, 4, 6, 8, 1, 3, 5, 7, 9),
        );
        if ($len) {
            for ($len; $len--;) {
                $sum += $prodArr[$mul][(int)$luhn[$len]];
                $mul ^= 1;
            }
            return 0 === $sum % 10 && $sum > 0;
        }
    }


    /**
     * 取得生日（由身份证号）
     * @param int $id 身份证号
     * @return string
     */
    static public function getBirthDay($id)
    {
        $year= $month = $day = '';
        switch (strlen($id)) {
            case 15 :
                $year = "19" . substr($id, 6, 2);
                $month = substr($id, 8, 2);
                $day = substr($id, 10, 2);
                break;
            case 18 :
                $year = substr($id, 6, 4);
                $month = substr($id, 10, 2);
                $day = substr($id, 12, 2);
                break;
        }
        $birthday = array('year' => $year, 'month' => $month, 'day' => $day);
        return $birthday;
    }

    /**
     * 是否是网贷之家服务器的请求
     * @return bool
     */
    static function isWdzjIp()
    {
        // 网贷之家的ip
        $ips = array(
            '140.206.51.154',
            '140.206.51.155',
            '140.206.51.156',
            '140.206.51.157',
            '140.206.51.158',
            '140.206.49.178',
            '180.168.140.206'
        );
        return in_array(self::ip_address(), $ips);
    }

    /**
     * 判断私募开关关闭
     * @param $user_id
     * @return bool
     */
    static function openPeinvest($user_id = '')
    {
        $switch = Yii::app()->c->peinvestconfig['switch'];
        if ($switch == 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 是否是ITZ办公内网的请求
     * @return Boolean
     */
    static function isItzOfficeIp()
    {
        //中科资源：124.202.218.238，124.193.127.130
        //银网：119.2.6.62
        return in_array(self::ip_address(), array('36.110.113.78', '36.110.113.74'));
    }

    //添加失败log方法。$fail_array失败数组，$txt失败提示问题 FunctionUtil::setAuditLog()  武晓青
    static public function setAuditLog($fail_array, $txt)
    {
        if ($txt) {
            $su_parameters = substr($fail_array['parameters'], 0, strlen($fail_array['parameters']) - 1);
            $fail_array['parameters'] = $su_parameters . ',info:' . $txt . '}';
        }
        AuditLog::getInstance()->method('add', $fail_array);
    }

    /**
     *  description ：概率算法
     *  param       : array("a" => 10, "b" => 20, "c" => 30, "d" => 40);
     *  return      : 10%'a' or 20%'b' or 30%'c' or 40%'d'
     **/
    static function get_rand($arr)
    {
        $pro_sum = array_sum($arr);
        $rand_num = mt_rand(1, $pro_sum);
        $tmp_num = 0;
        foreach ($arr as $k => $val) {
            if ($rand_num <= $val + $tmp_num) {
                $n = $k;
                break;
            } else {
                $tmp_num += $val;
            }
        }
        return $n;
    }

    #解析xml 文档
    static function parse_xml($str)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $str, $vals, $index);
        xml_parser_free($p);
        $result = array();
        if ($vals) {
            foreach ($vals as $v) {
                if (isset($v['value'])) {
                    $result[$v['tag']] = $v['value'];
                }
            }
        }
        return $result;
    }

    static function IsSensitiveWord($string)
    {
        $sensitiveWord = array("bitch", "shit", "falun", "tianwang", "cdjp", "bignews", "boxun", "chinaliberal", "chinamz", "chinesenewsnet", "cnd", "creaders", "dafa", "dajiyuan", "dfdz", "dpp", "falu", "falundafa", "flg", "freechina", "freenet", "fuck", "GCD", "hongzhi", "hrichina", "huanet", "hypermart", "incest", "jiangdongriji", "lihongzhi", "making", "minghui", "minghuinews", "nacb", "naive", "nmis", "paper", "peacehall", "renminbao", "renmingbao", "rfa", "safeweb", "sex", "svdc", "taip", "tibetalk", "triangleboy", "UltraSurf", "unixbox", "ustibet", "voa", "wangce", "wstaiji", "xinsheng", "yuming", "zhengjian", "zhengjianwang", "zhenshanren", "zhuanfalun", "xxx", "anime", "censor", "hentai", "[hz]", "(hz)", "[av]", "(av)", "[sm]", "(sm)", "porn", "multimedia", "toolbar", "downloader", "女優", "小泽玛莉亚", "强歼", "乱交", "色友", "婊子", "蒲团", "美女", "女女", "喷尿", "绝版", "三級", "武腾兰", "凌辱", "暴干", "诱惑", "阴唇", "小泽圆", "插插", "坐交", "長瀨愛", "川島和津實", "草莓牛奶", "小澤園", "飯島愛", "星崎未來", "及川奈央", "朝河蘭", "夕樹舞子", "大澤惠", "金澤文子", "三浦愛佳", "伊東", "慰安妇", "女教師", "武藤蘭", "学生妹", "无毛", "猛插", "护士", "自拍", "A片", "A级", "喷精", "偷窥", "小穴", "大片", "被虐", "黄色", "被迫", "被逼", "强暴", "口技", "破处", "精液", "幼交", "狂干", "兽交", "群交", "叶子楣", "舒淇", "翁虹", "大陆", "露点", "露毛", "武藤兰", "饭岛爱", "波霸", "少儿不宜", "成人", "偷情", "叫床", "上床", "制服", "亚热", "援交", "走光", "情色", "肉欲", "美腿", "自摸", "18禁", "捆绑", "丝袜", "潮吹", "肛交", "黄片", "群射", "内射", "少妇", "卡通", "臭作", "薄格", "調教", "近親", "連發", "限制", "乱伦", "母子", "偷拍", "更衣", "無修正", "一本道", "1Pondo", "櫻井", "風花", "夜勤病栋", "菱恝", "虐待", "激情", "麻衣", "三级", "吐血", "三个代表", "一党", "多党", "民主", "专政", "行房", "自慰", "吹萧", "色狼", "胸罩", "内裤", "底裤", "私处", "爽死", "变态", "妹疼", "妹痛", "弟疼", "弟痛", "姐疼", "姐痛", "哥疼", "哥痛", "同房", "打炮", "造爱", "作爱", "做爱", "鸡巴", "阴茎", "阳具", "开苞", "肛门", "阴道", "阴蒂", "肉棍", "肉棒", "肉洞", "荡妇", "阴囊", "睾丸", "捅你", "捅我", "插我", "插你", "插她", "插他", "干你", "干她", "干他", "射精", "口交", "屁眼", "阴户", "阴门", "下体", "龟头", "阴毛", "避孕套", "你妈逼", "大鸡巴", "高潮", "政治", "大法", "弟子", "大纪元", "真善忍", "明慧", "洪志", "红志", "洪智", "红智", "法轮", "法论", "法沦", "法伦", "发轮", "发论", "发沦", "发伦", "轮功", "轮公", "轮攻", "沦功", "沦公", "沦攻", "论攻", "论功", "论公", "伦攻", "伦功", "伦公", "打倒", "民运", "六四", "台独", "王丹", "柴玲", "李鹏", "天安门", "江泽民", "朱容基", "朱镕基", "李长春", "李瑞环", "胡锦涛", "魏京生", "台湾独立", "藏独", "西藏独立", "疆独", "新疆独立", "警察", "民警", "公安", "邓小平", "大盖帽", "革命", "武警", "黑社会", "交警", "消防队", "刑警", "夜总会", "妈个", "公款", "首长", "书记", "坐台", "腐败", "城管", "暴动", "暴乱", "李远哲", "司法警官", "高干", "人大", "尉健行", "李岚清", "黄丽满", "于幼军", "文字狱", "宋祖英", "自焚", "骗局", "猫肉", "吸储", "张五常", "张丕林", "空难", "温家宝", "吴邦国", "曾庆红", "黄菊", "罗干", "吴官正", "贾庆林", "专制", "三個代表", "一黨", "多黨", "專政", "大紀元", "紅志", "紅智", "法輪", "法論", "法淪", "法倫", "發輪", "發論", "發淪", "發倫", "輪功", "輪公", "輪攻", "淪功", "淪公", "淪攻", "論攻", "論功", "論公", "倫攻", "倫功", "倫公", "民運", "台獨", "李鵬", "天安門", "江澤民", "朱鎔基", "李長春", "李瑞環", "胡錦濤", "臺灣獨立", "藏獨", "西藏獨立", "疆獨", "新疆獨立", "鄧小平", "大蓋帽", "黑社會", "消防隊", "夜總會", "媽個", "首長", "書記", "腐敗", "暴動", "暴亂", "李遠哲", "高幹", "李嵐清", "黃麗滿", "於幼軍", "文字獄", "騙局", "貓肉", "吸儲", "張五常", "張丕林", "空難", "溫家寶", "吳邦國", "曾慶紅", "黃菊", "羅幹", "賈慶林", "專制", "八九", "八老", "巴赫", "白立朴", "白梦", "白皮书", "保钓", "鲍戈", "鲍彤", "暴政", "北大三角地论坛", "北韩", "北京当局", "北京之春", "北美自由论坛", "博讯", "蔡崇国", "曹长青", "曹刚川", "常劲", "陈炳基", "陈军", "陈蒙", "陈破空", "陈希同", "陈小同", "陈宣良", "陈一谘", "陈总统", "程凯", "程铁军", "程真", "迟浩田", "持不同政见", "赤匪", "赤化", "春夏自由论坛", "达赖", "大参考", "大纪元新闻网", "大纪园", "大家论坛", "大史", "大史记", "大史纪", "大中国论坛", "大中华论坛", "大众真人真事", "戴相龙", "弹劾", "登辉", "邓笑贫", "迪里夏提", "地下教会", "地下刊物", "第四代", "电视流氓", "钓鱼岛", "丁关根", "丁元", "丁子霖", "东北独立", "东方红时空", "东方时空", "东南西北论谈", "东社", "东土耳其斯坦", "东西南北论坛", "动乱", "独裁", "独夫", "独立台湾会", "杜智富", "多维", "屙民", "俄国", "发愣", "发正念", "反封锁技术", "反腐败论坛", "反攻", "反共", "反人类", "反社会", "方励之", "方舟子", "飞扬论坛", "斐得勒", "费良勇", "分家在", "分裂", "粉饰太平", "风雨神州", "风雨神州论坛", "封从德", "封杀", "冯东海", "冯素英", "佛展千手法", "付申奇", "傅申奇", "傅志寰", "高官", "高文谦", "高薪养廉", "高瞻", "高自联", "戈扬", "鸽派", "歌功颂德", "蛤蟆", "个人崇拜", "工自联", "功法", "共产", "共党", "共匪", "共狗", "共军", "关卓中", "贯通两极法", "广闻", "郭伯雄", "郭罗基", "郭平", "郭岩华", "国家安全", "国家机密", "国军", "国贼", "韩东方", "韩联潮", "何德普", "何勇", "河殇", "红灯区", "红色恐怖", "宏法", "洪传", "洪吟", "洪哲胜", "胡紧掏", "胡锦滔", "胡锦淘", "胡景涛", "胡平", "胡总书记", "护法", "华建敏", "华通时事论坛", "华夏文摘", "华语世界论坛", "华岳时事论坛", "黄慈萍", "黄祸", "黄菊", "黄翔", "回民暴动", "悔过书", "鸡毛信文汇", "姬胜德", "积克馆", "基督", "贾廷安", "贾育台", "建国党", "江core", "江八点", "江流氓", "江罗", "江绵恒", "江青", "江戏子", "江则民", "江泽慧", "江贼", "江贼民", "江折民", "江猪", "江猪媳", "江主席", "姜春云", "将则民", "僵贼", "僵贼民", "讲法", "酱猪媳", "交班", "教养院", "接班", "揭批书", "金尧如", "锦涛", "禁看", "经文", "开放杂志", "看中国", "抗议", "邝锦文", "劳动教养所", "劳改", "劳教", "老江", "老毛", "黎安友", "李大师", "李登辉", "李红痔", "李宏志", "李洪宽", "李继耐", "李兰菊", "李老师", "李录", "李禄", "李少民", "李淑娴", "李旺阳", "李文斌", "李小朋", "李小鹏", "李月月鸟", "李志绥", "李总理", "李总统", "连胜德", "联总", "廉政大论坛", "炼功", "梁光烈", "梁擎墩", "两岸关系", "两岸三地论坛", "两个中国", "两会", "两会报道", "两会新闻", "廖锡龙", "林保华", "林长盛", "林樵清", "林慎立", "凌锋", "刘宾深", "刘宾雁", "刘刚", "刘国凯", "刘华清", "刘俊国", "刘凯中", "刘千石", "刘青", "刘山青", "刘士贤", "刘文胜", "刘晓波", "刘晓竹", "刘永川", "流亡", "龙虎豹", "陆委会", "吕京花", "吕秀莲", "抡功", "轮大", "罗礼诗", "马大维", "马良骏", "马三家", "马时敏", "卖国", "毛厕洞", "毛贼东", "美国参考", "美国之音", "蒙独", "蒙古独立", "密穴", "绵恒", "民国", "民进党", "民联", "民意", "民意论坛", "民阵", "民猪", "民主墙", "民族矛盾", "莫伟强", "木犀地", "木子论坛", "南大自由论坛", "闹事", "倪育贤", "你说我说论坛", "潘国平", "泡沫经济", "迫害", "祁建", "齐墨", "钱达", "钱国梁", "钱其琛", "抢粮记", "乔石", "亲美", "钦本立", "秦晋", "轻舟快讯", "情妇", "庆红", "全国两会", "热比娅", "热站政论网", "人民报", "人民内情真相", "人民真实", "人民之声论坛", "人权", "瑞士金融大学", "善恶有报", "上海帮", "上海孤儿院", "邵家健", "神通加持法", "沈彤", "升天", "盛华仁", "盛雪", "师父", "石戈", "时代论坛", "时事论坛", "世界经济导报", "事实独立", "双十节", "水扁", "税力", "司马晋", "司马璐", "司徒华", "斯诺", "四川独立", "宋平", "宋书元", "苏绍智", "苏晓康", "台盟", "台湾狗", "台湾建国运动组织", "台湾青年独立联盟", "台湾政论区", "台湾自由联盟", "太子党", "汤光中", "唐柏桥", "唐捷", "滕文生", "天怒", "天葬", "童屹", "统独", "统独论坛", "统战", "屠杀", "外交论坛", "外交与方略", "万润南", "万维读者论坛", "万晓东", "汪岷", "王宝森", "王炳章", "王策", "王超华", "王辅臣", "王刚", "王涵万", "王沪宁", "王军涛", "王力雄", "王瑞林", "王润生", "王若望", "王希哲", "王秀丽", "王冶坪", "网特", "魏新生", "温元凯", "文革", "无界浏览器", "吴百益", "吴方城", "吴弘达", "吴宏达", "吴仁华", "吴学灿", "吴学璨", "吾尔开希", "五不", "伍凡", "西藏", "洗脑", "项怀诚", "项小吉", "小参考", "肖强", "邪恶", "谢长廷", "谢选骏", "谢中之", "辛灏年", "新观察论坛", "新华举报", "新华内情", "新华通论坛", "新生网", "新闻封锁", "新语丝", "信用危机", "邢铮", "熊炎", "熊焱", "修炼", "徐邦秦", "徐才厚", "徐匡迪", "徐水良", "许家屯", "薛伟", "学潮", "学联", "学习班", "学运", "学自联", "雪山狮子", "严家其", "严家祺", "阎明复", "颜射", "央视内部晚会", "杨怀安", "杨建利", "杨巍", "杨月清", "杨周", "姚月谦", "夜话紫禁城", "一中一台", "义解", "亦凡", "异见人士", "异议人士", "易丹轩", "易志熹", "尹庆民", "由喜贵", "游行", "幼齿", "于浩成", "余英时", "舆论", "舆论反制", "宇明网", "圆满", "远志明", "岳武", "在十月", "则民", "择民", "泽民", "贼民", "曾培炎", "张伯笠", "张钢", "张宏堡", "张林", "张万年", "张伟国", "张昭富", "张志清", "赵海青", "赵南", "赵品潞", "赵晓微", "赵紫阳", "哲民", "真相", "真象", "镇压", "争鸣论坛", "正见网", "正义党论坛", "郑义", "包夜", "冰火", "插B", "操B", "处女", "打飞机", "风骚", "黄色电影", "激情视频", "叫春", "狂插", "狂操", "狂搞", "露乳", "裸聊", "裸体", "屁股", "强奸", "三级片", "色情", "脱光", "脱衣", "性爱", "性感", "性高潮", "性交", "胸部", "艳舞", "一夜情", "欲望", "操你", "你他妈", "傻逼", "傻B", "TMD", "TNND", "TND", "法轮功", "江氏", "李洪志", "新唐人", "淫靡", "淫水", "六四事件", "迷药", "迷昏药", "窃听器", "六合彩", "买卖枪支", "退党", "三唑仑", "麻醉药", "麻醉乙醚", "短信群发器", "帝国之梦", "毛一鲜", "色情服务", "对日强硬", "出售枪支", "摇头丸", "西藏天葬", "鬼村", "军长发威", "PK黑社会", "枪决女犯", "投毒杀人", "强硬发言", "出售假币", "监听王", "昏药", "侦探设备", "麻醉钢枪", "反华", "官商勾结", "自杀指南", "自杀手册", "佳静安定片", "蒙汗药粉", "古方迷香", "强效失意药", "迷奸药", "透视眼镜", "远程偷拍", "自制手枪", "子女任职名单", "激情小电影", "黄色小电影", "色情小电影", "天鹅之旅", "盘古乐队", "高校暴乱", "高校群体事件", "大学骚乱", "高校骚乱", "催情药", "拍肩神药", "春药", "窃听器材", "身份证生成器", "枪决现场", "出售手枪", "麻醉枪", "办理证件", "办理文凭", "疆独藏独", "高干子弟", "高干子女", "枪支弹药", "血腥图片", "反政府", "禁书", "特码", "成人片", "成人电影", "换妻", "发票", "增值税", "迷幻药", "隐形", "宏志", "杀死", "发抡", "拉登", "拉丹", "法抡", "法囵", "法仑", "法纶", "发仑", "发囵", "国研新闻邮件", "自由运动", "法轮大法", "淫秽", "E周刊", "龙卷风", "正法", "三陪", "嫖娼", "静坐", "政变", "造反", "独立", "发轮功", "功友", "人民大众时事参考", "示威", "罢工", "大法弟子", "印尼伊斯兰祈祷团", "中俄边界新约", "政治运动", "压迫", "非典", "共产党", "反革命", "十六大", "江独裁", "台湾", "东突厥斯坦伊斯兰运动", "一边一国", "回民", "中华民国", "政治风波", "突厥斯坦", "简鸿章", "联总之声传单", "人民报讯", "东突", "人民真实报道", "教徒", "推翻", "小灵通", "操你奶奶", "操你妈", "falun", "IP17908", "falong", "陈水扁", "主席", "改革", "他妈的", "人民真实报导", "开放", "中俄", "边界新约", "（诽闻）", "印尼依斯兰祈祷团", "东突厥斯坦依斯兰运动", "本拉登", "维吾尔自由运动", "国际地质科学联合会", "中国民主正义党", "www.cdjp.org", "民主中国", "www.chinamz.org", "中国民主同盟", "支联会", "天安门母亲", "张戎", "西藏流亡政府", "邓力群", "龙新民", "www.bignews.org", "www.boxun.com", "也就是博讯", "www.cnd.org", "www.chinesenewsnet.com", "纪元", "www.dajiyuan.com", "大纪元时报", "自由亚洲", "www.rfa.org", "www.renminbao.com", "维基百科", "www.xxx.com", "根敦.确吉尼玛", "根敦.确吉", "确吉尼玛", "西藏论坛", "www.tibetalk.com", "破网软件", "无界", "自由门", "花园网", "我的奋斗", "itouzi", "ITZ", "ITZ", "毛泽东", "王博", "谷云", "赵春霞", "王晓文", "孟繁春", "习近平", "李克强", "张德江", "俞正声", "刘云山", "王岐山", "张高丽", "彭丽媛");
        return in_array(strtolower($string), $sensitiveWord);
    }

    static function IsUsername($name)
    {
        return preg_match('/^[A-Za-z0-9_\w\p{Han}]{2,15}$/u', $name);
    }

    static function IsPwd($password)
    {
        return !preg_match("/[^0-9a-zA-Z`~!@#\$%\^&\*()\-=_\+\[\]\\\{\}\|\;':\",\.\/<>?]/", $password);
    }

    static function SpecialDate($time, $fmt = "m-d H:i")
    {
        //获取今天凌晨的时间戳
        $day = strtotime(date('Y-m-d', time()));
        //获取昨天凌晨的时间戳
        $pday = strtotime(date('Y-m-d', strtotime('-1 day')));
        //获取现在的时间戳
        $nowtime = time();

        $tc = $nowtime - $time;
        if ($time < $pday) {
            $str = date($fmt, $time);
        } elseif ($time < $day && $time > $pday) {
            $str = "昨天 " . date("H:i", $time);
        } else {
            $str = "今天 " . date("H:i", $time);
        }
        return $str;
    }

    /**
     * [getMillisecond 获取当前毫秒时间戳]
     * @return float
     */
    static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * [getCurrentUrl 获取当前请求的相对根目录地址]
     * @param  $controller
     * @return string
     */
    static function getCurrentUrl($controller = null)
    {
        if (!$controller) $controller = Yii::app()->controller;
        $_module = $controller->module->getId();
        $_controller = $controller->getId();
        $_action = $controller->getAction()->getId();
        $currentUrl = '/' . $_module
            . '/' . $_controller
            . '/' . $_action;
        return $currentUrl;
    }

    /*
    * function：计算两个日期相隔多少年，多少月，多少天
    * param string $date1[格式如：2011-11-5]
    * param string $date2[格式如：2012-12-01]
    * return array array('年','月','日');
    */
    static function diffDate($date1, $date2)
    {
        if (strtotime($date1) > strtotime($date2)) {
            $tmp = $date2;
            $date2 = $date1;
            $date1 = $tmp;
        }

        $datetime1 = date_create($date1);
        $datetime2 = date_create($date2);
        $interval = date_diff($datetime1, $datetime2);

        return array(
            'year'  => $interval->format('%y'),
            'month' => $interval->format('%m'),
            'day'   => $interval->format('%d'),
        );
    }

    /**
     * 实名认证去除中文姓名中间空格
     * @param string $str
     * @return return <string>
     */
    static function disBlank($str = '')
    {
        $str = trim($str);
        if (preg_match("/[\x{4e00}-\x{9fa5}]+[\s]+[\x{4e00}-\x{9fa5}]+/u", $str)) {
            $str = preg_replace("/[\s]+/", '', $str);
        }
        return $str;
    }

    /**
     * 资金报警
     * email_phones: 通知人
     * content: 报警内容
     */
    static function alertToAccountTeam($content, $email_phones = array(), $sendPhone = false)
    {
        if (empty($content)) return false;

        //报警给相关人
        $msg_phones = array(
            2 => array('phone' => '13716970622', 'email' => 'liuchunhua@xxx.com'),
			3 => array('phone' => '15810571697', 'email' => 'zhaowanwan@xxx.com'),
            4 => array('phone' => '18211130584', 'email' => 'wangyanan@xxx.com'),
        );
        if (!empty($email_phones)) {
            $msg_phones = array_merge($msg_phones, $email_phones);
        }

        $remind['nid'] = "zi_bj";
        $remind['sent_user'] = 0;
        $remind['receive_user'] = -1;
        foreach ($msg_phones as $key => $value) {
            $remind['phone'] = isset($value['phone']) ? $value['phone'] : "";
            $remind['email'] = isset($value['email']) ? $value['email'] : "";
            $remind['data']['content1'] = $content;
            $remind['type'] = "zi_bj";
            $remind['mtype'] = "zi_bj";
            $remind['status'] = 0;
            $result = NewRemindService::getInstance()->SendToUser($remind, false, true, $sendPhone);
            Yii::log("NewRemindService return result=" . $result, "info", __METHOD__);
        }
    }

    /**
     * 资金报警
     * email_phones: 通知人
     * content: 报警内容
     */
    static function alertToAccountTeamWeb($content, $email_phones = array(), $sendPhone = false)
    {
        if (empty($content)) return false;

        //报警给相关人
        $msg_phones = array(
            1 => array('phone' => '15810571697', 'email' => 'zhaowanwan@xxx.com'),
        );
        if (!empty($email_phones)) {
            $msg_phones = array_merge($msg_phones, $email_phones);
        }

        $remind['nid'] = "zi_bj";
        $remind['sent_user'] = 0;
        $remind['receive_user'] = -1;
        foreach ($msg_phones as $key => $value) {
            $remind['phone'] = isset($value['phone']) ? $value['phone'] : "";
            $remind['email'] = isset($value['email']) ? $value['email'] : "";
            $remind['data']['content1'] = $content;
            $remind['type'] = "zi_bj";
            $remind['mtype'] = "zi_bj";
            $remind['status'] = 0;
            $result = NewRemindService::getInstance()->SendToUser($remind, false, true, $sendPhone);
            Yii::log("NewRemindService return result=" . $result, "info", __METHOD__);
        }
    }


    /**
     * 资金报警
     * email_phones: 通知人
     * content: 报警内容
     */
    static function alertToAccountWx($content, $title='先锋报警')
    {
        $email = 'zhaowanwan@nebula-sc.com';
        $mailSender = new MailClass();
        $send = $mailSender->yjSend($email, $title, $content);
        Yii::log("alertToAccountWx return code={$send['code']}; title:$title; content:$content", "info");
    }

    /**
     * * 生成电话随机号码 eq:155****1234 10
     * @param number $num 生成个数
     * @param int $starnum 4 5 6
     * @return array
     */
    static function randPhoneList($num = 0, $starnum = 4)
    {
        $array = array();
        for ($i = 0; $i < $num; $i++) {
            //从运营商列表中获取前缀
            $ispArray = self::$ispArray;
            $ispKey = array_rand($ispArray, 1);
            $prefixArray = $ispArray[$ispKey];
            $prefix = $prefixArray[array_rand($prefixArray, 1)];

            //随机生成后缀
            $star = substr('********', 0, $starnum);
            if (strlen($star) == 4) {
                $suffix = rand(1000, 9999);
            } elseif (strlen($star) == 5) {
                $suffix = rand(100, 999);
            } elseif (strlen($star) == 6) {
                $suffix = rand(10, 99);
            } else {
                return false;
            }
            $array[$i] = array("phone" => $prefix . $star . $suffix);
        }
        return $array;
    }
	
	/**
     *  邮箱格式校验
     */
    static function isEmail($email){
        if(!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $email)){
            return false;
        }
        return true;
    }

    /**
     * 请求流水号 唯一值
     * 固定位数32
     * @param unknown $prefix 2-8位英文
     */
    static function getRequestNo($prefix = '')
    {
        $str = $prefix . time() . uniqid() . substr(rand(100000000, 999999999), 1, 9 - strlen($prefix));
        $_str = strtoupper($str);
        return $_str;
    }

    /**
     * 资产花园流水号 唯一值
     * @param unknown $prefix 4位英文
     */
    static function getAgRequestNo($prefix = ''){
        $str = $prefix . date('ymd') . uniqid();
        $_str = strtoupper($str);
        return $_str;
    }

    /**
     *  根据ua判断是否为ITZAPP
     */
    static function isITZAPP($prefix=''){
        $useragent = strtolower($_SERVER["HTTP_USER_AGENT"]);
        $posistion_ios = strripos($useragent,'ITZ_IOS');
        $posistion_and = strripos($useragent,'ITZ_ANDROID');

        $isApp = false;
        if( $posistion_ios !== FALSE || $posistion_and !== FALSE ) {
            $isApp = true;
        }
        return $isApp;
    }

    /**
     * xianfeng
     * 根据身份证判断性别
     * @param $card_id
     * @return mixed
     */
    static function getSexByCardID($card_id){
        $sex = array(
            -1,//异常
            1,//男
            0,//女
        );
        $count = strlen($card_id);
        if($count == 18){
            $sexStatus = substr($card_id,-2,1);
        }elseif($count == 15){
            $sexStatus = substr($card_id,-1,1);
        }else{
            return $sex[0];
        }
        if($sexStatus%2 == 0){
            return $sex[2];
        }else{
            return $sex[1];
        }
    }

    /**
     * 根据身份证判断性别
     * @param $card_id
     * @return mixed
     */
    static function getSexFromCardID($card_id){
        $sex = array(
            0,//异常
            1,//男
            2,//女
        );
        $count = strlen($card_id);
        if($count == 18){
            $sexStatus = substr($card_id,-2,1);
        }elseif($count == 15){
            $sexStatus = substr($card_id,-1,1);
        }else{
            return $sex[0];
        }
        if($sexStatus%2 == 0){
            return $sex[2];
        }else{
            return $sex[1];
        }
    }

    /**
     * 判断用户激活前需不需要更换银行卡
     * @param $user_id
     * @return int
     */
    static function xwTieCard($user_id)
    {
        // 可用的快捷卡
        $count1 = ItzSafeCard::model()->countByAttributes(['user_id' => $user_id, 'status' => 2]);
        // 用过的快捷卡
        $count2 = ItzSafeCard::model()->countByAttributes(['user_id' => $user_id]);
        // 提现卡
        $count3 = DwAccountBank::model()->countByAttributes(['user_id' => $user_id]);
        if ($count1 < 1 && ($count2 > 0 || $count3 > 0)) {
            return 2;
        }
        return 1;
    }

    /**
     * 用户是否已经完成开户
     * @param $user_id
     * @param $flag
     * @return bool false:已经完成, true:尚未完成
     */
    static function getXwOpen($user_id, $flag)
    {
        Yii::app()->db->switchToMaster();
        $modelm = User::model()->findByPk($user_id);
        Yii::app()->db->switchToSlave();
        $xw_open_m = $modelm->xw_open;
        Yii::log($flag . '用户的未切库开户状态:' . $xw_open_m, 'info', 'getWxOpen');
        $flag = true;
        if ($xw_open_m == '2') {
            $flag = false;
        }
        return $flag;
    }

    /**
     * 返回传入全名中的姓氏
     * @param string $fullname 全名
     * @return array 
     */
    static  function splitName($fullname){
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => ''
        );
		if(empty($fullname) || !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $fullname)){
            $return_result['code'] = 100;
            $return_result['info'] = '参数有误，请确认参数为中文姓名全称';
            return $return_result;
        }
        $return_result['data'] = $fullname;
        $hyphenated = array('欧阳','太史','端木','上官','司马','东方','独孤','南宫','万俟','闻人','夏侯','诸葛','尉迟','公羊','赫连','澹台','皇甫',
            '宗政','濮阳','公冶','太叔','申屠','公孙','慕容','仲孙','钟离','长孙','宇文','城池','司徒','鲜于','司空','汝嫣','闾丘','子车','亓官',
            '司寇','巫马','公西','颛孙','壤驷','公良','漆雕','乐正','宰父','谷梁','拓跋','夹谷','轩辕','令狐','段干','百里','呼延','东郭','南门',
            '羊舌','微生','公户','公玉','公仪','梁丘','公仲','公上','公门','公山','公坚','左丘','公伯','西门','公祖','第五','公乘','贯丘','公皙',
            '南荣','东里','东宫','仲长','子书','子桑','即墨','达奚','褚师');
        $vLength = mb_strlen($fullname, 'utf-8');
        //全名只有两个字时,以前一个为姓,后一下为名
        if($vLength == 2){
            $return_result['data'] = mb_substr($fullname ,0, 1, 'utf-8');
            return $return_result;
        }
        //当全名大于2个字时
        if($vLength > 2){
            $preTwoWords = mb_substr($fullname, 0, 2, 'utf-8');//取命名的前两个字,看是否在复姓库中
            if(in_array($preTwoWords, $hyphenated)){
                $return_result['data'] = $preTwoWords;
            }else{
                $return_result['data'] = mb_substr($fullname, 0, 1, 'utf-8');
            }
        }
        return $return_result;
    }
    public static function showPEMoney($money=0){
        $money = (float)((float)$money/10000);
        if($money<0){
            $money = 0;
        }
        return round($money,2);
    }
    public static function cutTime($time=''){
        if(empty($time)){
            return 0;
        }
        return substr($time, 0,10);
    }
    public static function getUrlPath($url){
        if(empty($url)){
            return '';
        }
        if(is_array($url)){
            foreach($url as $key=>$v){
                $url[$key] = AppCommon::getUrlPath($v);
            }
            return $url;
        }
        $r = parse_url($url);
        if($r['path']){
            return trim($r['path'],'/');
        }
        return '';
    }
    public static function setUrlDomain($url){
        if(empty($url)){
            return '';
        }
        if(is_array($url)){
            foreach($url as $key=>$v){
                $url[$key] = AppCommon::setUrlDomain($v);
            }
            return $url;
        }
        $r = parse_url($url);
        if($r['path']){
            return Yii::app()->oss->bucket_attachment_domain.'/'.$r['path'];
        }
        return '';
    }

    static function handleTimelimit($start_time, $end_time, $type = 0)
    {
        $startDate = date('Y-m-d-t', $start_time);
        list($startYear, $startMonth, $startDay, $startMax) = explode('-', $startDate);
        $endDate = date('Y-m-d-t', $end_time);
        list($endYear, $endMonth, $endDay, $endMax) = explode('-', $endDate);

        $limitMonth = 0;
        while ($endYear > $startYear || ($endYear == $startYear && $endMonth > $startMonth)) {
            $limitMonth += 1;
            if (++$startMonth > 12) {
                ++$startYear;
                $startMonth = 1;
            }
        }

        if ($startDay == $startMax) {
            if ($endDay == $endMax) {
                $limitDay = 0;
            } else {
                $limitMonth -= 1;
                $limitDay = $endDay;
            }
        } else {
            if ($endDay >= $startDay) {
                $limitDay = $endDay - $startDay;
            } else {
                $limitMonth -= 1;
                $prevMax = date('t', strtotime("-1 month", $end_time));
                $prevlimitDay = $prevMax - $startDay;
                $prevlimitDay = $prevlimitDay > 0 ? $prevlimitDay : 0;
                $limitDay = $prevlimitDay + $endDay;
            }
        }
        if ($limitDay >= 15) {
            $limitMonth += $limitDay <= 25 ? 0.5 : 1;
        }
        if ($type >= 2000) {
            return $limitDay;
        }
        return $limitMonth;
    }

    /**
     * 计算两个日期之间的天数
     * @param $start_time
     * @param $end_time
     * @param string $differenceFormat
     * @return string
     */
    static function handleTimelimitDays($start_time, $end_time, $differenceFormat = '%a')
    {
        $datetime1 = date_create(date('Y-m-d', $start_time));
        $datetime2 = date_create(date('Y-m-d', $end_time));
        $interval = date_diff($datetime1, $datetime2);
        return $interval->format($differenceFormat);
    }
    /**
     * 主站姓名脱敏
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    static function formatName($name){
        if(empty($name)){
            return "";
        }
        $first_letter = mb_substr($name,0,1,'UTF-8');
        return $first_letter."**";

    }
    /** 
     * 智选小还款记录表名称 （支持月、固定一个月几张表、天分表）
     * @param unknown $formal_time    项目开放时间
     * @param number $next 0 当表名， 1 当前表名的下一时间点的名称
     * @return string  表名
     * */
    static function getWiseCollectionName($formal_time,$next=0){
    	$formal_time = intval($formal_time);
    	$next = intval($next);
    	if(empty($formal_time)){
    		return '';
    	}
    	$table_name = '';
    	if($formal_time >= 1893427200){ //2030-01-01 以后按天分表
    		$table_name = 'itz_wise_collection_'.date('Ymd',$formal_time);
    		if($next==1){
    			$table_name = 'itz_wise_collection_'.date('Ymd',$formal_time+86400);
    		}
    	}elseif ($formal_time >= 1577808000 && $formal_time < 1893427200){//2020-01-01 -2030-01-01 按每月三张表的情况
    		$conf = array( //配置
    			1=>array(1,2,3,4,5,6,7,8,9,10),
    			2=>array(11,12,13,14,15,16,17,18,19,20),
    			3=>array(21,22,23,24,25,26,27,28,29,30,31)
    		);
    		$conf_num = count($conf);
    		$rank = '';
    		foreach ($conf as $key=>$value){
    			$day = date('d',$formal_time);
    			if(in_array($day, $value)){
    				$rank = $key;
    				break;
    			}
    		}
    		if($next==1){
    			$rank=$rank+1;
    			if($rank > $conf_num){
    				$rank = 1;
    			}
    		}
    		$table_name = 'itz_wise_collection_'.date('Ym',$formal_time).'00'.$rank;
    	}else { //按月一张表
    		$time_format = date('Ym',$formal_time);
    		if($next==1){
    			$time_format = date("Ym",strtotime("$time_format +1 month"));
    		}
    		$table_name =  'itz_wise_collection_'.$time_format;
    	}
    	return $table_name;
    }
    /**
     * 四舍六入五成双
     * @param unknown $num
     * @param number $precision
     * @return number
     */
    function roundToEven($num,$precision=0){
    	return round($num,$precision,PHP_ROUND_HALF_EVEN);
    }


	/**
	 * 格式化时间
	 * @param $time
	 * @return array
	 */
    static function dealDateStyle($time){
    	if(empty($time)){
    		$time = time();
		}
		$year 	= date('Y');
		$month 	= date('m');
		$day 	= date('d');

		$dealYear 	= date('Y',$time);
		$dealMonth 	= date('m',$time);
		$dealDay 	= date('d',$time);
		if($year==$dealYear && $month==$dealMonth && $day==$dealDay){
			$pre = '今天';
		}elseif($year==$dealYear && $month==$dealMonth ){
			$pre = date('m-d',$time);
		}else{
			$pre = date('Y-m-d',$time);
		}
		$end = date('H:i',$time);
		return ['day'=>$pre,'dian'=>$end];
	}


	/**
	 * 生成指定个符号
	 * @param string $s
	 * @param int $num
	 * @return string
	 */
	static function getSymbolic($s='*',$num=1){
		$str = '';
		for($i=0;$i<$num;$i++){
			$str .= $s;
		}
		return $str;
	}


	/**
	 * 字符串脱敏
	 * @param $name
	 * @param int $pre_retain 前面保留字符数
	 * @param int $end_retain 后面保留字符数
	 * @return string
	 */
	static function dealStr($name,$pre_retain=1,$end_retain=0){
		$len = mb_strlen($name,'utf-8');
		$pre = $end = '';
		if($pre_retain){
			$pre = mb_substr($name,0,$pre_retain,'utf-8');
		}
		if($end_retain){
			$end = mb_substr($name,$len-$end_retain,$end_retain,'utf-8');
		}
		$symbolic = self::getSymbolic('*',$len-$pre_retain-$end_retain);
		return $pre.$symbolic.$end;
	}

    //会员编号
    // no :user_id;
    //type 用户类型：0 个人会员 1:企业会员
    static function numTo32($no, $type=0){
        $no+=34000000;
        $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
            "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M",
            "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $rtn = "";
        while($no >= 32) {
            $rtn = $char_array[fmod($no, 32)].$rtn;
            $no = floor($no/32);
        }

        $prefix = '00';
        if($type == 1){
            $prefix = '66';
        }
        return $prefix.$char_array[$no].$rtn;
    }

    //人民币小写转大写
    static function get_amount($number = 0)
    {
        $int_unit = '元';
        $is_round = true;
        $is_extra_zero = false;

        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2) {
            $dec = $is_round ? substr(
                strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1) : substr(
                $parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if ((empty($int) && empty($dec)) || $number == 0) {
            return '零元整';
        }

        // 定义
        $chs = array(
            '0',
            '壹',
            '贰',
            '叁',
            '肆',
            '伍',
            '陆',
            '柒',
            '捌',
            '玖'
        );
        $uni = array(
            '',
            '拾',
            '佰',
            '仟'
        );
        $dec_uni = array(
            '角',
            '分'
        );
        $exp = array(
            '',
            '万'
        );
        $res = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k ++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j ++, $i --) {
                $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int{$i}] . $u . $str;
            }
            $str = rtrim($str, '0'); // 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if (! isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        $res .= empty($int) ? '' : $int_unit;

        // 小数部分从左向右找
        if (! empty($dec)) {

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, - 1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i ++) {
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
            $tag = $number < 0.1 ? '' : '零'; // 兼容0.03的情况
            $res = rtrim($res, '0'); // 去掉末尾的0
            $res = preg_replace("/0+/", $tag, $res); // 替换多个连续的0
        } else {
            $res .= '整';
        }
        return $res;
    }

    /**
     * 获取手机型号
     * @return string
     */
    public function get_user_agent(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $brand = $browser = '';
        if (preg_match('/MSIE/i',$user_agent)){
            $browser = 'MSIE';
        }elseif (preg_match('/Firefox/i',$user_agent)){
            $browser = 'Firefox';
        }elseif (preg_match('/Chrome/i',$user_agent)){
            $browser = 'Chrome';
        }elseif (preg_match('/Safari/i',$user_agent)){
            $browser = 'Safari';
        }elseif (preg_match('/Opera/i',$user_agent)){
            $browser = 'Opera';
        }elseif (stripos($user_agent, "iPhone")!==false) {
            $brand = 'iPhone';
        } else if (stripos($user_agent, "SAMSUNG")!==false || stripos($user_agent, "Galaxy")!==false || strpos($user_agent, "GT-")!==false || strpos($user_agent, "SCH-")!==false || strpos($user_agent, "SM-")!==false) {
            $brand = '三星';
        } else if (stripos($user_agent, "Huawei")!==false || stripos($user_agent, "Honor")!==false || stripos($user_agent, "H60-")!==false || stripos($user_agent, "H30-")!==false) {
            $brand = '华为';
        } else if (stripos($user_agent, "Lenovo")!==false) {
            $brand = '联想';
        } else if (strpos($user_agent, "MI-ONE")!==false || strpos($user_agent, "MI 1S")!==false || strpos($user_agent, "MI 2")!==false || strpos($user_agent, "MI 3")!==false || strpos($user_agent, "MI 4")!==false || strpos($user_agent, "MI-4")!==false) {
            $brand = '小米';
        } else if (strpos($user_agent, "HM NOTE")!==false || strpos($user_agent, "HM201")!==false) {
            $brand = '红米';
        } else if (stripos($user_agent, "Coolpad")!==false || strpos($user_agent, "8190Q")!==false || strpos($user_agent, "5910")!==false) {
            $brand = '酷派';
        } else if (stripos($user_agent, "ZTE")!==false || stripos($user_agent, "X9180")!==false || stripos($user_agent, "N9180")!==false || stripos($user_agent, "U9180")!==false) {
            $brand = '中兴';
        } else if (stripos($user_agent, "OPPO")!==false || strpos($user_agent, "X9007")!==false || strpos($user_agent, "X907")!==false || strpos($user_agent, "X909")!==false || strpos($user_agent, "R831S")!==false || strpos($user_agent, "R827T")!==false || strpos($user_agent, "R821T")!==false || strpos($user_agent, "R811")!==false || strpos($user_agent, "R2017")!==false) {
            $brand = 'OPPO';
        } else if (strpos($user_agent, "HTC")!==false || stripos($user_agent, "Desire")!==false) {
            $brand = 'HTC';
        } else if (stripos($user_agent, "vivo")!==false) {
            $brand = 'vivo';
        } else if (stripos($user_agent, "K-Touch")!==false) {
            $brand = '天语';
        } else if (stripos($user_agent, "Nubia")!==false || stripos($user_agent, "NX50")!==false || stripos($user_agent, "NX40")!==false) {
            $brand = '努比亚';
        } else if (strpos($user_agent, "M045")!==false || strpos($user_agent, "M032")!==false || strpos($user_agent, "M355")!==false) {
            $brand = '魅族';
        } else if (stripos($user_agent, "DOOV")!==false) {
            $brand = '朵唯';
        } else if (stripos($user_agent, "GFIVE")!==false) {
            $brand = '基伍';
        } else if (stripos($user_agent, "Gionee")!==false || strpos($user_agent, "GN")!==false) {
            $brand = '金立';
        } else if (stripos($user_agent, "HS-U")!==false || stripos($user_agent, "HS-E")!==false) {
            $brand = '海信';
        } else if (stripos($user_agent, "Nokia")!==false) {
            $brand = '诺基亚';
        } else {
            $brand = '其他';
        }
        $_data = [
            'brand' =>$brand,
            'browser' =>$browser,
        ];
         return $_data;
    }

}

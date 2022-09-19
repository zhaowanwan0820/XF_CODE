<?php
/**
 * StringUtil
 *
 */
class StringUtil {
    static $halfChs = null;
    /**
     * 常用的html实体
     *
     */
    static $escapeChs = array (
        '&nbsp;' => " ",
        '&lt;' => "<",
        '&gt;' => ">",
        '&amp;' => "&",
        '&quot;' => "\"",
        '&apos;' => "'"
    );

	/**
     * getHalfChs
     * 占位半个字位的字符
     *
     * @static
     * @access public
     * @return void
     */
    public static function getHalfChs() {
        if (! self::$halfChs) {
            self::$halfChs = array (
                ',' => true,
                ',' => true,
                '.' => true,
                '-' => true,
                '!' => true,
                '#' => true,
                '$' => true,
                '%' => true,
                '^' => true,
                '&' => true,
                '*' => true,
                '(' => true,
                ')' => true,
                '_' => true,
                '+' => true,
                '=' => true,
                '[' => true,
                ' ' => true,
                ']' => true,
                '{' => true,
                '}' => true,
                "\\" => true,
                '|' => true,
                '?' => true,
                '/' => true,
                '<' => true,
                '>' => true,
                ';' => true,
                ':' => true,
                ' ' => true,
                '`' => true,
                '•' => true,
                '~' => true,
                '«' => true,
                '»' => true,
                '¯' => true,
                '‹' => true,
                '›' => true,
                ' ' => true
            );
            for($i = ord ( 'a' ); $i <= ord ( 'z' ); $i ++) {
                self::$halfChs [chr ( $i )] = true;
            }
            for($i = ord ( 'A' ); $i <= ord ( 'Z' ); $i ++) {
                self::$halfChs [chr ( $i )] = true;
            }
            for($i = ord ( '0' ); $i <= ord ( '9' ); $i ++) {
                self::$halfChs [chr ( $i )] = true;
            }
        }
        return self::$halfChs;
    }

	/**
     * m2s
     *
     * @param mixed $mix
     * @static
     * @access public
     * @return void
     */
    public static function m2s($mix) {
        if (is_string ( $mix )) {
            return $mix;
        }

        if (is_bool ( $mix )) {
            return $mix ? "TRUE" : "FALSE";
        }

        if (is_numeric ( $mix )) {
            return "NULL";
        }

        if (is_array ( $mix ) || is_object ( $mix )) {
            $str = "";
            $str = print_r ( $mix, true );
            $str = preg_replace ( "/(\r|\n|\s|\t)+/", "", $str );
            return $str;
        }
        return "";
    }

	/**
     * a2s
     *
     * @param mixed $mix
     * @static
     * @access public
     * @return void
     */
    public static function a2s($mix) {
        if (is_array ( $mix )) {
            $str = "";
            $str = print_r ( $mix, true );
            if (! defined ( 'YII_DEBUG' ) || ! YII_DEBUG) {
                $str = preg_replace ( "/(\r|\n|\s|\t)+/", "", $str );
            }
            return $str;
        }
        return "";
    }

	/**
     * o2s
     *
     * @param mixed $mix
     * @static
     * @access public
     * @return void
     */
    public static function o2s($mix) {
        if (is_object ( $mix )) {
            $str = "";
            $str = print_r ( $mix, true );
            $str = preg_replace ( "/(\r|\n|\s|\t)+/", "", $str );
            return $str;
        }
        return "NULL";
    }

    /**
     * b2s
     *
     * @param mixed $mix
     * @static
     * @access public
     * @return void
     */
    public static function b2s($mix) {
        if (is_bool ( $mix )) {
            return $mix ? "TRUE" : "FALSE";
        }
        return "";
    }

    /**
     * trimString
     * delete space in the string
     *
     * @param mixed $query
     * @static
     * @access public
     * @return void
     */
    public static function trimString($query) {
        $str = trim ( $query );
        // 接着去掉两个空格以上的
        $str = preg_replace ( '/\s(?=\s)/', '', $str );
        return $str;
    }

    /**
     * utf8字符截断函数
     * html标签不占字符数,$halfChs中的字符算0.5个字符，其余的算1个字符
     *
     * @param string $str
     * @param int $len
     * @param string $suffix
     * @param bool $type true-省略符写标签内 false-写标签内（如截取的有标签）
     */
    public static function truncate($str, $len, $suffix = "…", $type = true) {
        $counter = 0;
        $i = 0;
        $strLen = mb_strlen ( $str, "UTF-8" );
        //$str = html_entity_decode ( $str, ENT_QUOTES, "UTF-8" );
        if ($strLen <= $len) {
            return $str;
        }
        //$newStr = htmlentities ( $newStr, ENT_QUOTES, "UTF-8" );
        //如果长度恰好等于$len+1，且有后缀，则直接返回
        if ($suffix && mb_strlen ( strip_tags ( $str ), "UTF-8" ) == $len + 1) {
            return $str;
        }

        $halfChs = self::getHalfChs ();
        $i = 0;
        while ( $counter < $len && $i < $strLen ) {
            $ch = mb_substr ( $str, $i, 1, "UTF-8" );
            $i ++;

            //处理html标签
            if ($ch == "<") {
                $i ++;
                while ( $i < $strLen ) {
                    $ch = mb_substr ( $str, $i, 1, "UTF-8" );
                    $i ++;
                    if ($ch == ">") {
                        break;
                    }
                }
                continue;
            }

            //处理html转义字符
            if ($ch == "&") {
                $escape = "&";
                for($j = $i; $j <= $i + 4; $j ++) {
                    $chTmp = mb_substr ( $str, $j, 1, "UTF-8" );
                    $escape .= $chTmp;
                    if ($chTmp == ";") {
                        break;
                    }
                }

                if (isset ( self::$escapeChs [$escape] )) {
                    $ch = self::$escapeChs [$escape];
                    $i = $j + 1;
                }
            }

            if (isset ( $halfChs [$ch] )) {
                $counter += 0.5;
            } else {
                $counter ++;
            }
        }

        //多了半个字，截掉最后一个字
        if ($counter == $len + 0.5) {
            $i --;
        }
        $newStr = mb_substr ( $str, 0, $i, "UTF-8" );
        //将剩余的tag追加到尾部
        while ( $i < $strLen ) {
            $ch = mb_substr ( $str, $i, 1, "UTF-8" );
            $i ++;
            if ($ch == "<") {
                $newStr .= $ch;
                while ( $i < $strLen ) {
                    $ch = mb_substr ( $str, $i, 1, "UTF-8" );
                    $newStr .= $ch;
                    $i ++;
                    if ($ch == ">") {
                        break;
                    }
                }
            }
        }
        if (mb_strlen ( $newStr, "UTF-8" ) < mb_strlen ( $str, "UTF-8" )) {
            return $newStr . $suffix;
        }
        return $newStr;
    }


    public static function truncateNew($str, $len, $suffix = "…", $type = true) {
        $counter = 0;
        $i = 0;
        $strLen = mb_strlen ( $str, "UTF-8" );
        //$str = html_entity_decode ( $str, ENT_QUOTES, "UTF-8" );
        if ($strLen <= $len) {
            return $str;
        }
        //$newStr = htmlentities ( $newStr, ENT_QUOTES, "UTF-8" );
        //如果长度恰好等于$len+1，且有后缀，则直接返回
        if ($suffix && mb_strlen ( strip_tags ( $str ), "UTF-8" ) == $len + 1) {
            return $str;
        }

        $halfChs = self::getHalfChs ();
        $i = 0;
        while ( $counter < $len && $i < $strLen ) {
            $ch = mb_substr ( $str, $i, 1, "UTF-8" );
            $i ++;

            //处理html标签
            if ($ch == "<") {
                $i ++;
                while ( $i < $strLen ) {
                    $ch = mb_substr ( $str, $i, 1, "UTF-8" );
                    $i ++;
                    if ($ch == ">") {
                        break;
                    }
                }
                continue;
            }

            //处理html转义字符
            if ($ch == "&") {
                $escape = "&";
                for($j = $i; $j <= $i + 4; $j ++) {
                    $chTmp = mb_substr ( $str, $j, 1, "UTF-8" );
                    $escape .= $chTmp;
                    if ($chTmp == ";") {
                        break;
                    }
                }

                if (isset ( self::$escapeChs [$escape] )) {
                    $ch = self::$escapeChs [$escape];
                    $i = $j + 1;
                }
            }

            if (isset ( $halfChs [$ch] )) {
                $counter += 0.5;
            } else {
                $counter ++;
            }
        }

        //多了半个字，截掉最后一个字
        if ($counter == $len + 0.5) {
            $i --;
        }
        $newStr = mb_substr ( $str, 0, $i, "UTF-8" );
        //将剩余的tag追加到尾部
        while ( $i < $strLen ) {
            $ch = mb_substr ( $str, $i, 1, "UTF-8" );
            $i ++;
            if ($ch == "<") {
                $newStr .= $ch;
                while ( $i < $strLen ) {
                    $ch = mb_substr ( $str, $i, 1, "UTF-8" );
                    $newStr .= $ch;
                    $i ++;
                    if ($ch == ">") {
                        break;
                    }
                }
            }
        }
        if (mb_strlen ( $newStr, "UTF-8" ) < mb_strlen ( $str, "UTF-8" )) {
            if($type && $last_strip_len = strripos($newStr, '</')) {
//                $rs = '/>[^\n]+<\//i';
                $rs = '/>[^>]+<\//is';
                preg_match_all($rs,$newStr,$match);
                $match_count = count($match[0]);
                $content_old = $match[0][($match_count-1)];
                $content_new = substr(substr($content_old,1),0,-2);
                $content_new = '>' . $content_new . $suffix . '</';
                return str_replace($content_old,$content_new,$newStr);
            }
            return $newStr . $suffix;
        }
        return $newStr;
    }

    /**
     * ITZ截断函数，用于用户名截断
     * */
    public static function truncate_cn_itz($string, $length = 20){
       if ($length == 0) { return ''; }

       $str_length = mb_strlen($string, 'UTF-8');
       if ($str_length <= $length) {
           return mb_substr($string, 0, 1, 'UTF-8').'***';
       } else {
           return mb_substr($string, 0, $length, 'UTF-8').'***';
       }
    }

    /**
     * ITZ截断函数，用于新版PC改版网站用户截断展示
     * */
    public static function truncate_cn_new_itz($string, $length = 20){
       if ($length == 0) { return ''; }

       $str_length = mb_strlen($string, 'UTF-8');
       if ($str_length <= $length) {
           return mb_substr($string, 0, 1, 'UTF-8').'**';
       } else {
           return mb_substr($string, 0, $length, 'UTF-8').'**';
       }
    }

    /**
     * s2i
     * 将str转换为int,便于后续负载均衡计算
     *
     * @param mixed $str
     * @static
     * @access public
     * @return void
     */
    public static function s2i($str) {
        $md5str = md5 ( $str );
        $md5Arr = unpack ( 'l*', pack ( "h*", $md5str ) );
        $ret = 0;
        foreach ( $md5Arr as $k => $v ) {
            $ret ^= $v;
        }
        return $ret;
    }

	/**
	 * cutEm
	 * 裁掉em标签
	 *
	 * @param string $str
	 * @param string $k
	 * @static
	 * @access public
	 * @return void
	 */
	public static function cutEm($str="",$k=""){
		if(is_string($str)){
			$str = str_replace("<em>","",$str);
			$str = str_replace("</em>","",$str);
		}
		return $str;
	}

	public static function cutEmWrite(&$str="",$k=""){
		if(is_string($str)){
			$str = str_replace("<em>","",$str);
			$str = str_replace("</em>","",$str);
		}
		return $str;
	}

    /**
     * UTF8字符串清理尾部乱码
     *
     * @param string $str
     * @return string
     */
    public static function utf8RTrim($str) {
        if ($str == null) {
            return "";
        }
        $new = "";
        $pos = 0;
        $len = strlen ( $str );
        while ( $pos < $len ) {
            $ch = ord ( $str [$pos] );
            $mask = $ch & 0xf0;
            if ($mask == 0xf0) {
                $pos += 4;
            } elseif ($mask == 0xe0) {
                $pos += 3;
            } elseif ($mask == 0xc0) {
                $pos += 2;
            } else {
                $pos ++;
            }
            if ($pos - 1 < $len) {
                $last = $pos - 1;
            }
        }
        if ($last + 1 >= 0) {
            $new = substr ( $str, 0, $last + 1 );
        }
        return $new;
    }

    /**
     * 数字千分位分割 ","，不处理小数点后的部分 svn 钩子block了。。
     *
     * @param string $str
     * @return string
     */
    public static function numberFormatS1($str) {

        $strArr = explode(".", "".$str);
        return number_format($strArr[0]) . ($strArr[1] ? ".".$strArr[1] : "");
    }

}

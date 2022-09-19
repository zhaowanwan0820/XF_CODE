<?php
    header("Content-Type: text/html; charset=utf-8");
    error_reporting(0);
    $php_path = dirname(__FILE__) . '/';
    $dir_arr = explode("admin",$php_path);
    $app_path = $dir_arr[0].'/public/attachment/ueditor';
    include_once($dir_arr[0]."/conf/env.conf.php");//STATIC_UPLOAD_HOST
    $upload_url = $env_conf['STATIC_UPLOAD_HOST'];
    //需要遍历的目录列表，最好使用缩略图地址，否则当网速慢时可能会造成严重的延时
//    $paths = array('upload/','upload1/');
    $paths = array($app_path);
    $action = htmlspecialchars( $_POST[ "action" ] );
    if ( $action == "get" ) {
        $files = array();
        foreach ( $paths as $path){
            $tmp = getfiles( $path );
            if($tmp){
                $files = array_merge($files,$tmp);
            }
        }
        if ( !count($files) ) return;
        rsort($files,SORT_STRING);
        $str = "";
        foreach ( $files as $file ) {
            $arr = explode("attachment",$file);
            $str .= $upload_url.$arr[1]. "ue_separate_ue";
        }
        echo $str;
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param array $files
     * @return array
     */
    function getfiles( $path , &$files = array() )
    {
        if ( !is_dir( $path ) ) return null;
        $handle = opendir( $path );
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( $file != '.' && $file != '..' ) {
                $path2 = $path . '/' . $file;
                if ( is_dir( $path2 ) ) {
                    getfiles( $path2 , $files );
                } else {
                    if ( preg_match( "/\.(gif|jpeg|jpg|png|bmp)$/i" , $file ) ) {
                        $files[] = $path2;
                    }
                }
            }
        }
        return $files;
    }

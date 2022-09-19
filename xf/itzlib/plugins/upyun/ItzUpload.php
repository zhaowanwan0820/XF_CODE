<?php
require_once('upyun.class.php');

class ItzUpload extends  UpYun{

    public  $upyun_url;
    /**
    * 新构造函数，配置文件位于 itzlib/config/upyunconfig.php
    */
    public function __construct($itz_bucket_name="itzstatic") {
        require_once dirname(dirname(dirname (__FILE__)))."/config/upyunconfig.php" ;
        extract($upyun_config[$itz_bucket_name]);
        $this->upyun_url = "https://".$bucketname."upyun.itzcdn.com";
        parent::__construct($bucketname, $username, $password, $endpoint, $timeout);
    }

    /**
     * @param $source_file_path  Php上传的临时文件，也可以是一个路径文件
     * @param $source_file_name 上传文件的名称
     * @param $save_file_name 上传文件的名称
     * @param $save_path
     */
    public function ItzWriteFile($uploadArray,$thumb_flag=false,$water_flag=false,$md5_flag=false,$fix_width=150){
        extract($uploadArray);
        if($source_file_path==""||$save_file_name=="") {
            Yii::log("上传图片原始路径 或 保存文件名为空","error");
            return false;
        }
        $returnResult = array(
            "file_domain"    => $this->upyun_url,
            "file_src"       => "",
            "file_src_md5"   => "",
            "file_src_thumb" => "", //只对图片有效，其他文件没有缩略图
        );
        try {
            if($water_flag){
                Yii::log(print_r("对图片打水印",true),"debug");
                require_once dirname (__FILE__)."/WaterMask.php" ;
                $obj = new WaterMask($source_file_path);
                $obj->waterType = 1;
                //水印透明度，值 越小透明度越高
                $obj->transparent = 50;
                //输出水印图片文件覆盖到输入的图片文件
                $obj->output();
            }

            Yii::log(print_r("Upyun 直接上传文件开始:".$source_file_path.$source_file_name,true),"debug");
            $fh = fopen($source_file_path, 'rb');
            $rsp = $this->writeFile($save_file_path.$save_file_name, $fh, True);   // 上传图片，自动创建目录
            fclose($fh);
            Yii::log(print_r($rsp,true),"debug");
            Yii::log(print_r("Upyun 直接上传文件完成:".$save_file_path.$save_file_name,true),"debug");
            $returnResult["file_src"] = $save_file_path.$save_file_name;

            if($thumb_flag){
                Yii::log(print_r("Upyun 直接生成缩略图，不保存原图片，仅对图片文件有效：".$source_file_path.$source_file_name,true),"debug");
                /*$opts = array(
                    UpYun::X_GMKERL_TYPE    => 'square', // 缩略图类型
                    UpYun::X_GMKERL_VALUE   => 150, // 缩略图大小
                    UpYun::X_GMKERL_QUALITY => 95, // 缩略图压缩质量
                    UpYun::X_GMKERL_UNSHARP => True // 是否进行锐化处理
                );*/
                $opts = array(
                    UpYun::X_GMKERL_TYPE    => 'fix_width', // 缩略图类型
                    UpYun::X_GMKERL_VALUE   =>  $fix_width,
                );
                /*
                Yii::log(print_r("Upyun 按照预先设置的缩略图类型生成缩略图类型生成缩略图，不保存原图，仅对图片空间有效：".$file.$file_name,true),"debug");
                $opts = array(
                    UpYun::X_GMKERL_THUMBNAIL => 'thumbtype'
                );*/
                $fh = fopen($source_file_path, 'rb');
                $rsp = $this->writeFile($save_file_path."thumb_".$save_file_name, $fh, True, $opts);   // 上传图片，自动创建目录
                fclose($fh);
                Yii::log(print_r($rsp,true),"debug");
                Yii::log(print_r("Upyun 直接上传文件完成:".$save_file_path."thumb_".$save_file_name,true),"debug");
                $returnResult["file_src_thumb"] = $save_file_path."thumb_".$save_file_name;
            }

            if($md5_flag){
                Yii::log(print_r("Upyun 设置MD5校验文件完整性上传:".$source_file_path.$source_file_name,true),"debug");
                $opts = array(
                    UpYun::CONTENT_MD5 => md5(file_get_contents($file))
                );
                $fh = fopen($source_file_path, 'rb');
                $rsp = $this->writeFile($save_file_path."md5_".$save_file_name, $fh, True, $opts);   // 上传图片，自动创建目录
                fclose($fh);
                Yii::log(print_r($rsp,true),"debug");
                Yii::log(print_r("Upyun 直接上传文件完成:".$save_file_path."md5_".$save_file_name,true),"debug");
                $returnResult["file_src_md5"] = $save_file_path."md5_".$save_file_name;
            }

            return $returnResult;
        }
        catch(Exception $e) {
            Yii::log(print_r($e->getCode(),true),"error");
            Yii::log(print_r($e->getMessage(),true),"error");
            return false;
        }
    }

    /**
     * 直接指定上传地址的方式，
     * @param $source_file_path  Php上传的临时文件，也可以是一个路径文件
     * @param $save_file_path_and_name  上传文件的路径+名称
     */
    public function ItzWriteFileEasy($source_file_path,$save_file_path_and_name){
        $returnResult = array(
            "file_src"       => "",
        );

        try {
            Yii::log(print_r("Upyun 直接上传文件开始",true),"debug");
            $fh = fopen($source_file_path, 'rb');
            $rsp = $this->writeFile($save_file_path_and_name, $fh, True);   // 上传图片，自动创建目录
            fclose($fh);
            Yii::log(print_r($rsp,true),"debug");
            Yii::log(print_r("Upyun 直接上传文件完成:".$save_file_path_and_name,true),"debug");
            $returnResult["file_src"] = $this->upyun_url.$save_file_path_and_name;

            return $returnResult;
        }
        catch(Exception $e) {
            Yii::log(print_r($e->getCode(),true),"error");
            Yii::log(print_r($e->getMessage(),true),"error");
            return false;
        }
    }


    /**
     * [ItzWriteFileEasyOnLine 上传网络图片]
     * @param [type] $source_file_path        [图片url]
     * @param [type] $save_file_path_and_name [description]
     */
    public function ItzWriteFileEasyOnLine($source_file_path,$save_file_path_and_name){
        $returnResult = array(
            "file_src"       => "",
        );

        try {
            Yii::log(print_r("Upyun 直接上传网络图片开始",true),"debug");
            $fh = @file_get_contents($source_file_path);
            if($fh){
                $rsp = $this->writeFile($save_file_path_and_name, $fh, True);   // 上传图片，自动创建目录
                // fclose($fh);      //跑脚本的时候 不用fcolse()
                Yii::log(print_r($rsp,true),"debug");
                Yii::log(print_r("Upyun 直接上传网络图片完成:".$save_file_path_and_name,true),"debug");
                $returnResult["file_src"] = $this->upyun_url.$save_file_path_and_name;
                return $returnResult;
            }else{
                return false;
            }
        }
        catch(Exception $e) {
            // echo $e->getCode();
            // echo $e->getMessage();
            Yii::log(print_r($e->getCode(),true),"error");
            Yii::log(print_r($e->getMessage(),true),"error");
            return false;
        }
    }

    /**
     * [ItzDownloadFile 下载文件]
     * @param [type] $source_file_path        [upyun地址]
     * @param [type] $save_file_path_and_name [本地保存地址]
     */
    public function ItzDownloadFile($source_file_path,$save_file_path_and_name){

        try {
            Yii::log(print_r("Upyun 下载文件开始",true),"debug");

            $fh = fopen($save_file_path_and_name,"w");
            $res = $this->readFile($source_file_path,$fh);
            fclose($fh);
            Yii::log(print_r($res,true),"debug");
            Yii::log(print_r("Upyun 文件下载完成:".$save_file_path_and_name,true),"debug");
            return true;
        }
        catch(Exception $e) {
            // echo $e->getCode();
            // echo $e->getMessage();
            Yii::log(print_r($e->getCode(),true),"error");
            Yii::log(print_r($e->getMessage(),true),"error");
            return false;
        }
    }






}

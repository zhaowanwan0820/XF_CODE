<?php
require_once('oss/sdk.class.php');

class ItzOss{
    private $oss_sdk_service; 
    public $bucket;
    public $debug = false;
    public $aliyun_url = 'http://oss.itzcdn.com';

    public function __construct($itz_bucket_name='itzprivate') {
        $this->oss_sdk_service = new ALIOSS();
        $this->oss_sdk_service->set_debug_mode($this->debug);
        $this->bucket = $itz_bucket_name;
    }
    
    /**
     * @param $source_file_path  Php上传的临时文件，也可以是一个路径文件
     * @param $source_file_name 上传文件的名称 
     * @param $save_file_name 上传文件的名称 
     * @param $save_path
     */
    public function ItzWriteFile($uploadArray){
        $returnResult = array(
            'file_domain'    => $this->aliyun_url,
            'file_src'       => '',
            'file_src_md5'   => '',
            'file_src_thumb' => '', //只对图片有效，其他文件没有缩略图
        );
        try{
            $object = $uploadArray['save_file_path'];
            $object = preg_replace ('/(^\/)|(\/$)/', '', $object);//兼容接口路径能在oss使用
            $response = $this->object_exist($object);
            if($response->status == '404'){
                $this->create_object($object);
            }
            $object = $object.'/'.$uploadArray['save_file_name'];
            $file_path = $uploadArray['source_file_path'];
            $response = $this->upload_by_file($object,$file_path,$options);
            if(false == $response->isOk()){
                Yii::log('aliyunOss Error:'.print_r($uploadArray,true).print_r($response,true), 'error');
                return false;
            }
            $returnResult['file_src'] = '/'.$object;
            return $returnResult;
        }catch (Exception $ex){
            Yii::log('aliyunOss Error:'.$object.'@'.print_r($ex->getMessage(),true), 'error');
            return false;
        }
    }
    
    public function ItzWriteContent($object,$content){
        try{
            $object = preg_replace ('/(^\/)/', '', $object);//兼容接口路径能在oss使用
            $dir = dirname($object);
            $response = $this->object_exist($dir);
            if($response->status == '404'){
                $this->create_object($dir);
            }
            $response = $this->upload_by_content($object,$content);
            if(false == $response->isOk()){
                Yii::log('aliyunOss Error:'.$object.'@'.print_r($response,true), 'error');
                return false;
            }
            return $object;
        }catch (Exception $ex){
            Yii::log('aliyunOss Error:'.$object.'@'.print_r($ex->getMessage(),true), 'error');
            return false;
        }
    }
    
    public function mkdirs($dir){  
        if(!is_dir($dir)){  
            if(!mkdirs(dirname($dir))){  
                return false;
            }  
            if(!mkdir($dir,0777)){  
                return false;  
            }  
        }  
        return true;  
    }
    
    public function ItzReadFile($object,$download_path=null){
        try{
            $dir = dirname($download_path);
            if(!file_exists($dir)){
                $this->mkdirs($dir);
            }
            
            $response = $this->get_object($object,$download_path);
            if($response->status == '200'){
                if(empty($download_path)){
                    header("content-type:{$response->header['content-type']} \r\n");
                    echo($response->body);
                }else{
                    return $download_path;
                }
            }else{
                Yii::log('aliyunOss Error:'.print_r(func_get_args(),true).print_r($response,true), 'error');
                return false;
            }
            return $returnResult;
        }catch (Exception $ex){
            Yii::log('aliyunOss Error:'.print_r(func_get_args(),true).print_r($ex->getMessage(),true), 'error');
            return false;
        }
    }

    //检测object是否存在
    function object_exist($object){
        $response = $this->oss_sdk_service->is_object_exist($this->bucket,$object);
        return $response;
    }

    //创建目录
    function create_object($object){
        $response  = $this->oss_sdk_service->create_object_dir($this->bucket,$object);
        return $response;
    }
    
    //通过路径上传文件
    function upload_by_file($object,$file_path,$options=null){
        $response = $this->oss_sdk_service->upload_file_by_file($this->bucket,$object,$file_path,$options);
        return $response;
    }
    
    //通过内容上传文件
    function upload_by_content($object,$content){
        $upload_file_options = array(
            'content' => $content,
            'length' => strlen($content),
        );
        $response = $this->oss_sdk_service->upload_file_by_content($this->bucket,$object,$upload_file_options);	
        return $response;
    }
    
    //获取object
    function get_object($object,$download_path=null){
        if(!empty($download_path)){
            $options = array(ALIOSS::OSS_FILE_DOWNLOAD =>$download_path);
        }
        $response = $this->oss_sdk_service->get_object($this->bucket,$object,$options);
        return $response;
    }
}

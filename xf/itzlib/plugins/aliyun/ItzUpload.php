<?php
require_once('oss/sdk.class.php');

class ItzUpload{
    private $oss_sdk_service; 
    public $bucket;
    public $debug = false;
    public $aliyun_url = 'http://oss.itzcdn.com';

    public function __construct($itz_bucket_name="itzstatic") {
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
    public function ItzWriteFile($uploadArray,$thumb_flag=false,$water_flag=false,$md5_flag=false){
        $returnResult = array(
            'file_domain'    => $this->aliyun_url,
            'file_src'       => '',
            'file_src_md5'   => '',
            'file_src_thumb' => '', //只对图片有效，其他文件没有缩略图
        );
        try{
            $object = $uploadArray['save_file_path'];
            $object = substr($object, 1, -1);//兼容接口路径能在oss使用
            $response = $this->object_exist($object);
            if($response->status == '404'){
                $this->create_object($object);
            }
            $object = $object.'/'.$uploadArray['save_file_name'];
            $file_path = $uploadArray['source_file_path'];
            $options = array(ALIOSS::OSS_CONTENT_TYPE => 'image/jpeg');
            $response = $this->upload_by_file($object,$file_path,$options);
            if($response->status != '200'){
                Yii::log('aliyunOss Error:'.print_r(func_get_args(),true).print_r($response,true), 'error');
                return false;
            }
            $returnResult['file_src'] = '/'.$object;
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
}

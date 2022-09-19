<?php
class CommonController extends DController {
    //public $layout = "main";

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/main';
    
    /**
     * 单文件上传类
     * [files] = array([name] => $`F_$4E{9@0L9G(TRUB0%`E.jpg
     *                 [type] => image/jpeg
     *                 [tmp_name] => /tmp/phpf8qRBf
     *                 [error] => 0
     *                 [size] => 27590
     */
    public function actionUploadFile(){
        Yii::log(print_r($_FILES,true),"debug");
        
        $input_name = key($_FILES);
        if(!$input_name) {
            return $this->echoJson(array(),100,"params error : name",true);
        }
        $CUploadedFile = CUploadedFile::getInstanceByName($input_name);

        //上传图片的width*height
        $sizes = getimagesize($CUploadedFile->tempName);
        $width = $sizes[0];
        $height = $sizes[1];

        //上传图片后台校验
        switch ($_GET["type"]) {
            case 'goodspic': //商品管理图片
                if (!in_array($CUploadedFile->type, array("image/png"))) {
                    return $this->echoJson(array(),100,"图片格式必须是PNG",true);
                }
                switch ($_GET['pictype']){
                    case 'pc_list_img':
                        if ($CUploadedFile->size > 20480) {
                            return $this->echoJson(array(),100,"图片大小须控制在20K以内",true);
                        }
                        if ($width != 170 || $height != 90) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是170*90px",true);
                        }
                        break;
//                    case 'pc_detail_img':
//                        if ($CUploadedFile->size > 102400) {
//                            return $this->echoJson(array(),100,"图片大小须控制在100K以内",true);
//                        }
//                        if ($width != 750 || $height != 300) {
//                            return $this->echoJson(array(),100,"图片的尺寸必须是750*300px",true);
//                        }
//                        break;
                    case 'app_list_img':
                        if ($CUploadedFile->size > 20480) {
                            return $this->echoJson(array(),100,"图片大小须控制在20K以内",true);
                        }
                        if ($width != 230 || $height != 230) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是230*230px",true);
                        }
                        break;
                    case 'app_detail_img':
                        if ($CUploadedFile->size > 51200) {
                            return $this->echoJson(array(),100,"图片大小须控制在50K以内",true);
                        }
                        if ($width != 750 || $height != 300) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是750*300px",true);
                        }
                        break;
                }
                break;
            case 'advertpic': //移动端开屏图
                if (!in_array($CUploadedFile->type, array("image/png"))) {
                    return $this->echoJson(array(),100,"图片格式必须是PNG",true);
                }
                if ($CUploadedFile->size > 102400) {
                    return $this->echoJson(array(),100,"图片大小须控制在100K以内",true);
                }
                if ($_GET['num'] == '1' && ($width != 640 || $height != 1136)) {
                    return $this->echoJson(array(),100,"图片1的尺寸必须是640* 1136",true);
                }
                if ($_GET['num'] == '2' && ($width != 640 || $height != 960)) {
                    return $this->echoJson(array(),100,"图片2的尺寸必须是640* 960",true);
                }
                break;
            case 'draw'://后台抽奖管理
                if (!in_array($CUploadedFile->type, array("image/gif","image/png","image/jpeg"))) {
                    return $this->echoJson(array(),100,"图片格式必须是PNG,JPG,GIF",true);
                }
                switch ($_GET['picType']) {
                    case 'banner_pic':
                        if ($CUploadedFile->size > 102400) {
                            return $this->echoJson(array(),100,"图片大小须控制在100K以内",true);
                        }
                        if ($width != 750 || $height != 300) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是750*300px",true);
                        }
                        break;
                    case 'background_pic':
                        if ($CUploadedFile->size > 204800) {
                            return $this->echoJson(array(),100,"图片大小须控制在200K以内",true);
                        }
                        if ($width != 750 ) {
                            return $this->echoJson(array(),100,"图片的宽必须是750px",true);
                        }
                        break;
                    case 'card_pic':
                        if ($CUploadedFile->size > 51200) {
                            return $this->echoJson(array(),100,"图片大小须控制在50K以内",true);
                        }
                        if ($width != 223 || $height != 271) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是223*271px",true);
                        }
                        break;
                    case 'my_pic':
                        if ($CUploadedFile->size > 102400) {
                            return $this->echoJson(array(),100,"图片大小须控制在100K以内",true);
                        }
                        if ($width != 750 || $height != 240) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是750*240px",true);
                        }
                        break;
                    case 'activity_pic':
                        if ($CUploadedFile->size > 204800) {
                            return $this->echoJson(array(),101,"图片大小须控制在200K以内",true);
                        }
                        if ($width != 750) {
                            return $this->echoJson(array(),101,"图片的宽必须是750px",true);
                        }
                        break;
                    case 'advert_pic':
                        if ($CUploadedFile->size > 102400) {
                            return $this->echoJson(array(),100,"图片大小须控制在100K以内",true);
                        }
                        if ($width != 750 ) {
                            return $this->echoJson(array(),100,"图片的宽必须是750px",true);
                        }
                        break;
                    case 'share_pic':
                        if ($CUploadedFile->size > 51200) {
                            return $this->echoJson(array(),100,"图片大小须控制在50K以内",true);
                        }
                        if ($width != 200 || $height != 200) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是200*200px",true);
                        }
                        break;
                    case 'award_pic':
                        if ($CUploadedFile->size > 51200) {
                            return $this->echoJson(array(),100,"图片大小须控制在50K以内",true);
                        }
                        if ($width != 160 || $height != 160) {
                            return $this->echoJson(array(),100,"图片的尺寸必须是160*160px",true);
                        }
                        break;
                }
                break;
            case 'picture'://网站内容管理
                $type = $_GET['picType'];
                $widthC = Yii::app()->c->params_picture[$type]['width'];
                $heightC = Yii::app()->c->params_picture[$type]['height'];
                $sizeC   = Yii::app()->c->params_picture[$type]['size'];
                $formatC = Yii::app()->c->params_picture[$type]['format'];
                if (!in_array($CUploadedFile->type, $formatC)) {
                    return $this->echoJson(array(),100,"上传格式不正确",true);
                }
                if ($CUploadedFile->size > $sizeC) {
                    $str = '图片大小须控制在'.($sizeC/1024).'K以内';
                    return $this->echoJson(array(),100,$str,true);
                }
                if ($width != $widthC || $height != $heightC) {
                    $str = '图片的尺寸必须是'.$widthC.'×'.$heightC.'px';
                    return $this->echoJson(array(),100,$str,true);
                }
                break;
            case 'package'://包管理
                $widthC = 590;
                $heightC = 214;
                $sizeC   = 51200;
                $formatC = array("image/png");
                if (!in_array($CUploadedFile->type, $formatC)) {
                    return $this->echoJson(array(),100,"上传格式不正确",true);
                }
                if ($CUploadedFile->size > $sizeC) {
                    $str = '图片大小须控制在'.($sizeC/1024).'K以内';
                    return $this->echoJson(array(),100,$str,true);
                }
                if ($width != $widthC || $height != $heightC) {
                    $str = '图片的尺寸必须是'.$widthC.'×'.$heightC.'px';
                    return $this->echoJson(array(),100,$str,true);
                }
                break;
            default:
                break;
        }
        //限制seo上传图片大小为100k
        if(($_GET['CKEditor'] == 'ItzSeoArticle_content')&&($CUploadedFile->size > 204800)){
            return $this->echoJson(array(),100,"file of size more than 200k",true);
        }

        if($CUploadedFile->size < 20000){ // /1024 Kb
        }

        if ($CUploadedFile->hasError){
            return $this->echoJson(array(),100,"file upload faild",true);
        }else{
            
           $time = time();
           $uniqid = $this->getGUID();//生成唯一值
            //上传到又拍云的类
            Yii::import("itzlib.plugins.upyun.ItzUpload");
            $ItzUpload = new ItzUpload(); //默认空间为itzstatic
            $upload_array = array(
                "source_file_path" => $CUploadedFile->tempName, //临时文件名
                "source_file_name" => $CUploadedFile->name,     //上传文件名
                "save_file_path"   => "/data/tmp/",
                "save_file_name"   => $CUploadedFile->name,
            );
            Yii::log('before upload:'.$uniqid."_".$upload_array["save_file_name"],"debug");
            $water_flag = ($_GET["water_flag"]==2)?TRUE:FALSE;
            $fix_width = 150;
            switch ($_GET["type"]) {
                case 'goodspic':   //商品图片
                    $upload_array["save_file_path"] = "/data/upfiles/goodspic/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "tmp_goodspic_".$uniqid.".jpg";
                    break; 
                case 'article':   //网站内容
                     $upload_array["save_file_path"] = "/data/upfiles/article/".date("Y-m")."/".date("d")."/";  
                     $upload_array["save_file_name"] = "tmp_article_".$uniqid.".jpg";
                     $fix_width = 230;
                     break; 
                case 'scrollpic':   //幻灯图片
                     $upload_array["save_file_path"] = "/data/upfiles/scrollpic/".date("Y-m")."/".date("d")."/";  
                     $upload_array["save_file_name"] = "tmp_scrollpic_".$uniqid.".jpg";
                     break; 
                case 'guarantor':   //担保公司附件
                     $upload_array["save_file_path"] = "/data/upfiles/guarantor/".date("Y-m")."/".date("d")."/";  
                     $upload_array["save_file_name"] = "tmp_guarantor_".$uniqid.".jpg";
                     break; 
                case 'card':   //身份证上传验证  
                     $upload_array["save_file_path"] = "/data/upfiles/card_pic/".date("Y-m")."/".date("d")."/";  
                     $upload_array["save_file_name"] = "tmp_".$this->user_id."_user_".$uniqid.".jpg";
                     break; 
                case 'avatar': //头像上传   
                     $upload_array["save_file_path"] = "/data/avatar/";
                     $upload_array["save_file_name"] = $this->user_id."_avatar_middle".$uniqid.".jpg";
                    break; 
                case 'borrowupload': //抵押图片   
                     $upload_array["save_file_path"] = "/data/upfiles/images/".date("Y-m")."/".date("d")."/"; 
                     $upload_array["save_file_name"] = (isset($_GET["borrow_id"])?$_GET["borrow_id"]:"0")."_borrowupload_".$uniqid.".jpg";
                    break; 
                case 'attestation': //附件图片   
                     $upload_array["save_file_path"] = "/data/upfiles/images/".date("Y-m")."/".date("d")."/"; 
                     $upload_array["save_file_name"] = (isset($_GET["borrow_id"])?$_GET["borrow_id"]:"0")."_attestation_".$uniqid.".jpg";
                    break; 
                case 'contentFile': //编辑器本地上传图片
                     $upload_array["save_file_path"] = "/data/upfiles/contentFile/".date("Y-m")."/".date("d")."/"; 
                     $upload_array["save_file_name"] = "contentFile_".$uniqid.".jpg";
                    break;
                case 'wapldp': //wap注册着落页头图
                    $upload_array["save_file_path"] = "/data/upfiles/wapldp/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "wapldp_".$uniqid.".jpg";
                    break;
                case 'advertpic': //移动端开屏图
                    $upload_array["save_file_path"] = "/data/upfiles/advertpic/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "tmp_advertpic_".$uniqid.".png";
                    break;
                case 'draw': //抽奖管理图片
                    $upload_array["save_file_path"] = "/data/upfiles/draw/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "tmp_draw_".$uniqid.".png";
                    break;
                case 'picture': //网站内容图片
                    $upload_array["save_file_path"] = "/data/upfiles/picture/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "tmp_picture_".$uniqid.".png";
                    break;
                case 'package': //网站内容图片
                    $upload_array["save_file_path"] = "/data/upfiles/package/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "tmp_package_".$uniqid.".png";
                    break;
                case 'logo': //主站logo图片
                    $upload_array["save_file_path"] = "/data/upfiles/logo/".date("Y-m")."/".date("d")."/";
                    $upload_array["save_file_name"] = "tmp_logo_".$uniqid.".png";
                    break;
                default:  break;
            } 
            $upresult = $ItzUpload->ItzWriteFile($upload_array,true,$water_flag,false,$fix_width);
            Yii::log('upload time:'.(time()-$time),"debug");
	        if($upresult != false){
                list($file_name,$suffix) = explode(".", $upload_array['source_file_name']);
                $upresult['file_alt'] = $file_name;
                if ($_GET["type"] == 'advertpic') {
                    $upresult['file_format'] = $width.'*'.$height;
                    $upresult['file_size'] = $CUploadedFile->size;
                }
                if($_GET['contentRes'] == 1){//编辑器本地上传
                    $imgUrl = '<input type="text" value="'.Yii::app()->c->yunUrl.$upresult['file_src'].'" name="returnUrl" size="50"/>';
                    echo $imgUrl;exit;
                }else if ($_GET['simditor'] == 1) { //simditor编辑器需要返回的参数
                    echo exit(json_encode(array('success' => true,'msg' => '上传成功','file_path' => Yii::app()->c->yunUrl.$upresult['file_src'])));
                }
                else{
                    if($_GET['type'] == 'borrowupload' && isset($_POST['type_id'])){
                        $upresult['type_id'] = $_POST['type_id'];
                    }
                    return $this->echoJson($upresult,0,"",true);
                }
            }else{
                if ($_GET['simditor'] == 1) {
                    echo exit(json_encode(array('success' => false,'msg' => '上传失败','file_path' => '')));
                } else {
                    return $this->echoJson(array(),100,"file upload faild",true);
                }
            }
        }
    }

    //上传文件至本地
    public function actionUploadLocalFile(){
        Yii::log(print_r($_FILES,true),"debug");
        
        $input_name = key($_FILES);
        if(!$input_name) {
            return $this->echoJson(array(),100,"params error : name",true);
        }
        $CUploadedFile = CUploadedFile::getInstanceByName($input_name);
        

        if($CUploadedFile->size < 20000){ // /1024 Kb
        }
        if ($CUploadedFile->hasError){
            return $this->echoJson(array(),100,"file upload faild",true);
        }else{
            $water_flag = ($_GET["water_flag"]==2)?TRUE:FALSE;
            $upresult   = Upload::createFile($CUploadedFile,'borrowInfo','create'); 
            if($upresult != false){
                return $this->echoJson($upresult,0,"",true);
            }else{
                return $this->echoJson(array(),100,"file upload faild",true);
            }
        }
    }
    /**
     * 多文件上传类
     */
    public function actionUploadFiles(){
        Yii::log(print_r($_FILES,true),"debug");
        //[files] = array([name] => $`F_$4E{9@0L9G(TRUB0%`E.jpg
        //[type] => image/jpeg
        //[tmp_name] => /tmp/phpf8qRBf
        //[error] => 0
        //[size] => 27590
        $files = CUploadedFile::getInstanceByname();
        Yii::log(print_r($files,true),"debug");
        die;
        $is_success = false;
        $desFilePath ="/tmp/tmp/";
        
        if ($_FILES["file"]["error"] > 0){
            return $this->echoJson(array(),100,"file upload faild");
        }else{
            $tmpFilePath = $_FILES["file"]["tmp_name"];
            $desFilePath = $desFilePath.$_FILES["file"]["name"];
            Yii::log(print_r($desFilePath,true),"debug");
            if (file_exists($desFilePath)){
                unlink($desFilePath);
                return $this->echoJson(array(),100,$_FILES["file"]["name"]."file already exists.");
            }
            else{
                move_uploaded_file($tmpFilePath, $desFilePath);
                return $this->echoJson(array("img_src"=>$desFilePath),0,"");
            }
        }
        
    }

    /**
     * Get guid
     * 
     * @return string
     */
    protected function getGUID(){
      $charid = strtoupper(md5(uniqid(mt_rand(), true)));
      $hyphen = chr(45);// "-"
      $uuid =substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);
      return $uuid;
    }

    /**
     * 获取图片二维码的url
     * @author JU
     * @date 2016/04/29
     */
    public function actionQrcode($i){
        require_once(WWW_DIR . '/itzlib/plugins/qrcode/qrcode.php');
        $QRcode = new QRcode;
        echo $QRcode->png($i, false, QR_ECLEVEL_L, 6, 0);//生成二维码
    }

}


<?php
/**
 * 上传文件 控制器
 * @author changlu
 * 2013年12月11日13:31:10
 *
 */
//FP::import("admin.Lib.COM.UploadFile.class");
FP::import("app.upload");
class uploadModule extends SiteBaseModule
{
    /**
     * 之前的上传
     */
    public function do_upload()
    {
        $this->do_upload_img();
    }
    
    /**
     * 之前的上传 房产认证 等等内容
     */
    public function do_upload_img()
    {
        if(intval($_REQUEST['upload_type'])==0){
            $result = $this->uploadImage($_FILES['imgFile']);
        }
        else{
            $result = $this->uploadImage($_FILES['imgFile']);
        }
        
        if($result['status'] == 1)
        {
            $file_url = $result['full_path'];
            $html = '<html>';
            $html.= '<head>';
            $html.= '<title>Insert Image</title>';
            $html.= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
            $html.= '</head>';
            $html.= '<body>';
            $html.= '<script type="text/javascript">';
            //$html.='alert("'.$_POST['id'].'");';
            //$html.='alert(parent.parent.document.getElementById("'.$_POST['id'].'").value);';
            //$html.='parent.parent.document.getElementById("'.$_POST['id'].'").value="'.$file_url.'";';
            $html.= 'parent.parent.KE.plugin["upload_image"].insert("' . $_POST['id'] . '", "' . $file_url . '","' . $_POST['imgTitle'] . '","' . $_POST['imgWidth'] . '","' . $_POST['imgHeight'] . '","' . $_POST['imgBorder'] . '","' . $_POST['align'] . '");';
            $html.= '</script>';
            $html.= '</body>';
            $html.= '</html>';
            echo $html;
        }
        else
        {
            echo "<script>alert('".$result['errors'][0]."');</script>";
        }
    }
    /**
     * changlu
     * ajax 上传图片 通行证
     * 2013年12月3日11:09:31
     */
    public function ajax_upload_img()
    {
        $n = getRequestInt('n',1);
        $priv = getRequestInt('priv', 0);
        $priv = $priv ? true : false;
        $result = $this->uploadFile(null, null, 1, $priv);
        $data = array();
        if($result['status'] == 1){
            $list = $result['data'];
            $file_url = "./".$result['full_path'];
            $msg = "上传成功！";
            require_once APP_ROOT_PATH . '/libs/common/functions.php';
            $file_url_img = get_attr($result['aid'],false,$priv);
            // if($priv) {
//                 $file_url_img = get_domain().'/attachment-view?file='.trim($file_url,'.');
//             }
            echo '<script type="text/javascript">parent.ajax_callback("'.$file_url.'","'.$msg.'",'.$n.',"'.$file_url_img.'")</script>';
            exit;
        }else{
            $msg  = $result['errors'][0];
            echo '<script type="text/javascript">parent.ajax_callback_error("'.$n.'","'.$msg.'")</script>';
            exit;
        }
    }
    
    /**
     * changlu 
     * 通用上传接口
     * 2014年1月6日17:38:03
     * 
     */
    public function upload(){
        $isimage = getRequestInt('image',0);
        $type    = getRequestString('type'); // ajax json 
        $app = getRequestString('app');
        $app = 'first';//暂时不用

        $result = uploadFile($app,'',$isimage,1);
        //存入数据库
        if($type == 'ajax'){
            $callback = getRequestString('callback');
            if($callback){
                echo '<script type="text/javascript">'.$callback.'('.json_encode($result).')</script>';
                exit;
            }
            echo '<script type="text/javascript">parent.ajax_callback('.json_encode($result).')</script>';
            exit;
        }
        if($type == 'json'){
            echo json_encode($result);
            exit;
        }
        exit;
    }

    /**
     * 上传图片的通公基础方法
     * @return array
     */
    protected function uploadFile($app='',$file='',$ismig=1, $priv = false)
    {
        $uploadFileInfo = array(
            'file' => $file,
            'isImage' => $ismig,
            'asPrivate' => $priv,
            'asAttachment' => 1,
            'app' => $app,
        );
        return uploadFile($uploadFileInfo);
    }

    public function deleteImg()
    {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $file = $_REQUEST['file'];
        $file = explode("..",$file);
        $file = $file[4];
        $file = substr($file,1);
        @unlink(get_real_path().$file);
        if(app_conf("PUBLIC_DOMAIN_ROOT")!='')
        {
            $syn_url = app_conf("PUBLIC_DOMAIN_ROOT")."/es_file.php?username=".app_conf("IMAGE_USERNAME")."&password=".app_conf("IMAGE_PASSWORD")."&path=".$file."&act=1";
            @file_get_contents($syn_url);
        }
        save_log(l("DELETE_SUCCESS"),1);
        $this->success(l("DELETE_SUCCESS"),$ajax);
    }
    
}
?>

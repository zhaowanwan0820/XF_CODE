<?php 
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
namespace {
    use libs\vfs\VfsHelper;

class FileAction extends CommonAction{
    /**
     * 富文本编辑框上传
     */
    public function do_upload()
    {
        $this->do_upload_img("image");
    }
    /**
     * 上传框
     * @param string $type
     */
    public function do_upload_img($type="upload_image")
    {
        $limitSizeInMB = floatval($_REQUEST['limit']);
        if (bccomp($limitSizeInMB, '0.00', 2) <= 0)
        {
            $limitSizeInMB = 1;
        }
        if(intval($_REQUEST['upload_type'])==0){
            $uploadFileInfo = array(
                'file' => $_FILES['imgFile'],
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => $limitSizeInMB,
            );
            $result = uploadFile($uploadFileInfo);
        }
        else{

            $uploadFileInfo = array(
                'file' => $_FILES['imgFile'],
                'isImage' => 0,
                'asAttachment' => 1,
                'asPrivate' => true,
            );
            $result = uploadFile($uploadFileInfo);
        }
        if($result['status'] == 1)
        {
            $list = $result['data'];
            $file_url = $result['full_path'];
            // $file_url = get_www_url().$file_url;
            //TODO vfs url access
            $file_url = VfsHelper::image($file_url, $result['is_priv']);
            $html = '<html>';
            $html.= '<head>';
            $html.= '<title>Insert Image</title>';
            $html.= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
            $html.= '</head>';
            $html.= '<body>';
            $html.= '<script type="text/javascript">';
            //$html.='alert(parent.parent.document.getElementById("'.$_POST['id'].'").value);';
            //$html.='parent.parent.document.getElementById("'.$_POST['id'].'").value="'.$file_url.'";';
            $html.= 'parent.parent.KE.plugin["'.$type.'"].insert("' . $_POST['id'] . '", "' . $file_url . '","' . $_POST['imgTitle'] . '","' . $_POST['imgWidth'] . '","' . $_POST['imgHeight'] . '","' . $_POST['imgBorder'] . '","' . $_POST['align'] . '");';
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
     * ajax 上传图片
     * 2013年12月3日11:09:31
     */
    public function ajax_upload_img()
    {
        $n = getRequestInt('n',1);
        $result = $this->uploadImage();

        $data = array();
        if($result['status'] == 1){
            $list = $result['data'];
            $file_url = ".".$list[0]['bigrecpath'].$list[0]['savename'];
            $file_url = str_replace("/public", "", $file_url);
            $msg = "上传成功！";
            $file_url_img = get_domain().trim($file_url,'.');
            echo '<script type="text/javascript">parent.ajax_callback("'.$file_url.'","'.$msg.'",'.$n.',"'.$file_url_img.'")</script>';
            exit;
        }else{
            $msg  = $result['info'];
            echo '<script type="text/javascript">parent.ajax_callback_error("'.$n.'","'.$msg.'")</script>';
            exit;
        }
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

}
?>

<?php
/**
 * 上传文件
 * @author wenyanlei@ucfgroup.com
 */

class UploadAction extends CommonAction
{

    public static $others = array('apk','puhui');

    public function index(){
        $other = isset($_REQUEST['other']) ? addslashes($_REQUEST['other']) :'apk';
        $file_arr = array();
        $model = M('Attachment');
        $rs = $model->query("SELECT * FROM __TABLE__ WHERE other = '{$other}' AND is_delete = 0 ORDER BY id DESC");
        if(is_array($rs)) {
               foreach ($rs as $fileinfo) {
                    $file_size_kb = $fileinfo['filesize']/1024;
                    $file_arr[] = array(
                        'id' => $fileinfo['id'],
                        'file_name' => $fileinfo['filename'],
                        'file_url' =>'http:' . \libs\vfs\Vfs::$staticHost . '/' . $fileinfo['attachment'],
                        'file_size' => number_format($file_size_kb/1024, 2).' M',
                        'file_size_kb' => number_format($file_size_kb, 2).' KB',
                        'file_time' => date("Y-m-d H:i:s",$fileinfo['create_time']),
                    );
                }
         }
        $this->assign ( 'post_max_size' , ini_get('post_max_size'));
        $this->assign ( 'filelist' , $file_arr);
        $this->assign ( 'other' , $other);
        $this->display('index');
    }

    public function puhui(){
        $_REQUEST['other'] = 'puhui';
        $this->index();
    }

    /**
     * 上传
     */
    public function fileUpload(){

        $other = isset($_REQUEST['other']) ? addslashes($_REQUEST['other']) :'apk';
        if(!in_array($other,self::$others)){
            $this->error('参数错误！');
        }

        $file = $_FILES['attach_file'];
        if(empty($file) || $file['error'] != 0){
            $this->error('上传失败！');
        }

        $uploadFileInfo = array(
            'file' => $file,
            'asAttachment' => 1,
            'other' => $other,
        );
        $result = uploadFile($uploadFileInfo);
        if(!empty($result['aid'])) {
            $this->success('上传成功！');
        }
        $this->error('上传失败！');
    }

    /**
     * 删除
     */
    public function fileDel(){
        $id = (int) $_GET['id'];
        $model = M('Attachment');
        $rs = $model->execute("UPDATE  __TABLE__ SET is_delete = 1 WHERE id={$id} AND other in('apk','puhui') AND is_delete = 0");
        if($rs){
            $this->success ('删除成功！');
        }else{
            $this->error('删除失败！');
        }
        exit;
    }

}
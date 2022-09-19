<?php
/**
 * 安卓包上传
 * @author longbo@ucfgroup.com
 */
use libs\utils\Logger;

class AndroidApkAction extends CommonAction
{

    const APP_NAME = 'androidapk';
    const CONFIG_KEY = 'android_common_config'; //api_conf配置名

    private $confModel;
    private $confObj;

    public function __construct()
    {
        $this->confModel = M('ApiConf');
        $condition['name'] = self::CONFIG_KEY;
        $this->confObj = $this->confModel->where($condition);
        parent::__construct();
    }

    public function index()
    {
        $vo = $this->confObj->find();
        if (empty($vo)) {
            $vo = array(
                'title' => '安卓渠道分发配置',
                'name' => self::CONFIG_KEY,
                'conf_type' => 3,
                'value' => '',
                'is_effect' => 0,
                'create_time' => time(),
            );
            $this->confModel->add($vo);
        }
        $confVal = json_decode($vo['value'], true);

        $file_arr = array();
        $fileModel = M('Attachment');
        $condition['app_name'] = self::APP_NAME;
        $rs = $fileModel->where($condition)->order("id desc")->findAll();

        if (is_array($rs)) {
            foreach ($rs as $fileinfo) {
                $apk = json_decode($fileinfo['description'], true);
                $file_size_kb = $fileinfo['filesize']/1024;
                $file_arr[] = array(
                        'id' => $fileinfo['id'],
                        'file_name' => $fileinfo['filename'],
                        'file_url' =>'http:' . \libs\vfs\Vfs::$staticHost . '/' . $fileinfo['attachment'],
                        'file_size' => number_format($file_size_kb/1024, 2).' M',
                        'file_size_kb' => number_format($file_size_kb, 2).' KB',
                        'file_time' => date("Y-m-d H:i:s",$fileinfo['create_time']),
                        'channel' => $apk['channel'],
                        'vcode' => $apk['vcode'],
                        'apk' => $fileinfo['description'],
                        'upgrade' => $apk['upgrade'] ? '是' : '否',
                        'is_effect' => $fileinfo['is_delete'] ? '无效':'有效',
                        );
            }
        }
        $this->assign ('filelist', $file_arr);
        $this->assign ('upgradeMsg', $confVal['upgradeMsg']);
        $this->assign ('minimumVer', $confVal['minimumVer']);
        $this->display('index');
    }

    public function saveCommonConfig()
    {
        B('FilterString');
        extract($_REQUEST);
        $data = array();
        $data['is_effect'] = intval($is_effect);
        $data['update_time'] = time();
        $data['value'] = json_encode(
            array(
                'upgradeMsg' => $upgradeMsg,
                'minimumVer' => $minimumVer,
            ),JSON_UNESCAPED_UNICODE
        );
        $res = $this->confObj->save($data);
        if (false !== $res) {
            save_log($data['value'].L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            save_log($data['value'].L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, L("UPDATE_FAILED"));
        }
    }

    public function add()
    {
        $this->assign ( 'post_max_size' , ini_get('post_max_size'));
        $this->display('add');
    }

    /**
     * 上传
     */
    public function apkUpload()
    {
        $file = $_FILES['apk_file'];
        if(empty($file) || $file['error'] != 0){
            $this->error('上传失败！');
        }
        $filename = basename($file['name'], ".apk");
        $fileInfo = explode('_', $filename);
        $apk = [];
        $apk['md5'] = md5_file($file['tmp_name']);
        $apk['upgrade'] = intval($_REQUEST['upgrade']);
        foreach ($fileInfo as $v) {
            if (strpos($v, '@') !== false) {
                $vinfo = explode('@', $v);
                if ($vinfo[0] == 'vcode') {
                    $apk['vcode'] = $vinfo[1];
                }
                if ($vinfo[0] == 'vname') {
                    $apk['vname'] = $vinfo[1];
                }
                if ($vinfo[0] == 'qd') {
                    $apk['channel'] = $vinfo[1];
                }
            }
        }

        $uploadFileInfo = array(
            'app' => self::APP_NAME,
            'desc' => json_encode($apk),
            'other' => $apk['channel'],
            'remark' => $apk['vcode'],
            'file' => $file,
            'asAttachment' => 1,
        );
        $result = uploadFile($uploadFileInfo);
        if(!empty($result['aid'])) {
            $this->success('上传成功！', 0, u("AndroidApk/index"));
        }
        $this->error('上传失败！');
    }

    /**
     * 删除
     */
    public function fileDel()
    {
        $id = (int) $_GET['id'];
        $model = M('Attachment');
        $app = self::APP_NAME;
        $rs = $model->execute("DELETE FROM __TABLE__ WHERE id={$id} AND app_name='{$app}'");
        if($rs){
            $this->success ('删除成功！');
        }else{
            $this->error('删除失败！');
        }
        exit;
    }

    public function upgrade()
    {
        $id = intval($_GET['id']);
        $condition['app_name'] = self::APP_NAME;
        $condition['id'] = $id;
        $desc = M('Attachment')->where($condition)->getField("description");
        $desc_arr = json_decode($desc, true);
        $desc_arr['upgrade'] = intval(!$desc_arr['upgrade']);
        $rs = M('Attachment')->where($condition)->setField("description", json_encode($desc_arr));
        if($rs){
            $this->success('更新成功！');
        }else{
            $this->error('更新失败！');
        }
        exit;
    }


    public function setEffect()
    {
        $id = intval($_GET['id']);
        $condition['app_name'] = self::APP_NAME;
        $condition['id'] = $id;

        $c_is_effect = M('Attachment')->where($condition)->getField("is_delete");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $rs = M('Attachment')->where($condition)->setField("is_delete",$n_is_effect);
        if($rs){
            $this->success('更新成功！');
        }else{
            $this->error('更新失败！');
        }
        exit;
    }
}

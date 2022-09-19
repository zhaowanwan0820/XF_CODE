<?php
/**
 * 客户端反馈
 * @author wenyanlei@ucfgroup.com
 **/

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Feedback extends AppBaseAction {

    private $word_num_min = 1;
    private $word_num_max = 500;
    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter'=>'string'),
            'mobile' => array('filter'=>'string'),
            'sysver' => array('filter'=>'string'),
            'softver' => array('filter'=>'string'),
            'models' => array('filter'=>'string'),
            'content' => array('filter'=>'string'),
            'imei' => array('filter'=>'string'),
        );
        $this->form->validate();
    }
    public function invoke() {

        $data = $this->form->data;
        $data['user_id'] = 0;
        $content_word = empty($data['content']) ? 0 : get_wordnum($data['content']);
        if($content_word < $this->word_num_min || $content_word > $this->word_num_max){
            $this->setErr('ERR_PARAMS_ERROR', "反馈内容字数为".$this->word_num_min."-".$this->word_num_max);
            return false;
        }

        if($data['token']){
            $info = $this->rpc->local('UserService\getUserByCode', array(htmlentities($data['token'])));
            if(!isset($info['code']) && isset($info['user']['id'])){
                $data['user_id'] = $info['user']['id'];
            }else{
                $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
                return false;
            }
        }

        //如果有上传图片
        $image_id = '';
        if ($_FILES) {
            if (count($_FILES) != 1) {
                $this->setErr('ERR_PARAMS_ERROR', "最多上传一张图片");
                return false;
            }
            $file = $_FILES['file'];
            $prefix = $this->getImagePostFix($file['tmp_name']);
            if(in_array($prefix, $this->allowPostfix)) {
                $uploadFileInfo = array(
                        'file' => $file,
                        'isImage' => 1,
                        'asAttachment' => 1,
                        'asPrivate' => 1,
                        'limitSizeInMB' => 1.5,
                        'userId' => $data['user_id'],
                );
                $result = uploadFile($uploadFileInfo);
                if(!empty($result['aid']) && $result['filename']) {
                    $image_id = $result['aid'];
                } else {
                    $this->setErr('ERR_PARAMS_ERROR', "图片尺寸不能大于1.5M，请重新上传图片");
                    return false;
                }
            } else {
                $this->setErr('ERR_PARAMS_ERROR', "图片格式仅限JPG、PNG，请重新上传图片");
                return false;
            }
        }
        $data['image_id'] = $image_id;
        if(!empty($data['mobile']) && !is_mobile($data['mobile'])){
            $this->setErr('ERR_PARAMS_ERROR', "手机号格式错误");
            return false;
        }

        $res = $this->rpc->local('UserFeedbackService\feedbackInsert', array($data));

        if($res === true){
            $this->json_data = '反馈提交成功';
        }else{
            $this->setErr('ERR_SYSTEM','反馈提交失败');
        }
    }
    /**
     * 通过二进制流 读取文件后缀信息
     * @param string $filename
     */
    function getImagePostFix($filename) {
        $file     = fopen($filename, "rb");
        $bin      = fread($file, 2); //只读2字节
        fclose($file);
        $strinfo  = @unpack("c2chars", $bin);
        $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
        $filetype = "";
        switch ($typecode) {
            case 7790: $filetype = 'exe';break;
            case 7784: $filetype = 'midi';break;
            case 8297: $filetype = 'rar';break;
            case 255216:$filetype = 'jpg';break;
            case 7173: $filetype = 'gif';break;
            case 6677: $filetype = 'bmp';break;
            case 13780:$filetype = 'png';break;
            default:   $filetype = 'unknown'.$typecode;
        }
        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
            return 'jpg';
        }
        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
            return 'png';
        }
        return $filetype;
    }
}

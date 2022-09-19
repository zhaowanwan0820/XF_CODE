<?php
/**
 * 上传图片，返回地址
 * User: yangshuo
 */
namespace api\controllers\valentine;

use api\controllers\AppBaseAction;
use libs\web\Form;
use libs\vfs\VfsHelper;

class UploadImage extends AppBaseAction
{

    public $pwd = '/tmp/';

    public function init(){
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'imgString' => array(
                'filter' => 'string',
                'message' => 'imgString is required',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $result = [];
        //上传图片
        if (isset($data['imgString'])) {
            $fileName = md5(time().rand(0, 1000)).".jpg";
            $fileName = $this->pwd.$fileName;
            $putResult = @file_put_contents("$fileName", base64_decode($data['imgString']));
            if ($putResult === false) {
                $this->setErr('ERR_SYSTEM', '写入图片失败');
                return false;
            }
            //print_r($_FILES);exit;
            //判断是否为图像
            if (!$this->IsImg($fileName)) {
                $this->setErr('ERR_PARAMS_ERROR', '图片格式错误');
                return false;
            }
            //$userInfo = isset($data['token']) ? $this->getUserByToken() : null;
            $file['error']          = '0';
            $file['name']           = 'abc';
            $file['type']           = 'image/jpg';
            $file['tmp_name']       = $fileName;
            $file['size']           = $putResult;
            $file['limitSizeInMB']  = 10;
            $file['asPrivate']      = 0;

            $fileInfo = [
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                //'userId' => $userInfo['id'],
            ];

            // 上传
            $results = uploadFile($fileInfo);
            if (empty($results['status']) || $results['status'] != 1) {
                $this->setErr('ERR_MANUAL_REASON', isset($results['errors'][0]) ? $results['errors'][0] : '上传失败');
                return false;
            }

            $imageId = !empty($results['aid']) ? $results['aid'] : 0;
            $fileUrl = sprintf($this->getHost().'/common/publicImage?image_id=%s', \libs\utils\Aes::encryptForDeal($imageId));

            $result['imgUrl'] = $fileUrl;
            $this->json_data = $result;
        }
    }

    public function IsImg($fileName)
    {
        if(file_exists($fileName))
        {
            return getimagesize($fileName) == false ? false : true;
        }
    }
}
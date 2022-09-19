<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use libs\vfs\VfsHelper;
use libs\vfs\Vfs;
use core\dao\AttachmentModel;

use core\service\speedLoan\LoanService;

/**
 * ApplyConfirm
 * 确认申请审核
 *
 * @uses BaseAction
 * @package default
 */
class ApplyConfirm extends SpeedLoanBaseAction
{

    const IS_H5 = true;
    private $allowPostfix =  array('jpg','jpeg','pjpeg');

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'frontPhoto' => array('filter' => 'string'),
            'backPhoto' => array('filter' => 'string'),
            'handHoldPhoto' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $userInfo['id'];
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, json_encode($data), json_encode($_FILES))));

        //上传照片
        $photoName = ['frontPhoto', 'backPhoto', 'handHoldPhoto'];
        $idcardPhoto = [];
        try {
            if ($this->app_version >= 473) {
                $idcardPhoto = $this->getImagePath($userId, $data, $photoName);
            } else {
                $idcardPhoto = $this->upload($userId, $photoName);
            }
        } catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, $e->getMessage(), json_encode($data))));
            $this->tpl->assign('tipText', $e->getMessage());
            $this->template = 'speedloan/notice.html';
            return;
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, 'idcardPhoto:' . json_encode($idcardPhoto))));

        // 银行卡信息
        $bankcardInfo = (new \core\service\UserBankcardService())->getCreditBankInfo($userId);
        // 借款期限信息
        $creditSerivce = new \core\service\speedLoan\LoanService();
        $creditUserInfo = $creditSerivce->getUserCreditInfo($userId);
        $loanDays = '0';
        $dealInfo = [
            'lastDate' => '19000101',
            'principalAmount' => '999999',
        ];
        $this->tpl->assign('loanDays', $loanDays);

        // 落单 并请求银行
        $loanGateway = new LoanService();
        $loanInfo = [
            'userId' => $userInfo['id'],
            'orderId' =>$creditUserInfo['order_id'],
            'userInfo' => $userInfo,
            'bankcardInfo' => $bankcardInfo,
            'dealInfo' => $dealInfo,
        ];
        try {
            $result = $loanGateway->updateCreditUser($loanInfo);
            //异步上传身份证照片
            $creditSerivce->asyncUploadToJF($userId, $idcardPhoto);

        } catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, $e->getMessage())));
            $this->tpl->assign('tipText', '审核申请失败，清稍后重试');
            $this->template = 'speedloan/notice.html';
            return;
        }
        $this->template = 'speedloan/apply_wait.html';
    }

    /**
     * 上传文件
     * v472版本，兼容
     */
    private function upload($userId, $photoName) {
        $idcardPhoto = [];
        foreach ($photoName as $name) {
            if (empty($_FILES[$name])) {
                throw new \Exception("请选择上传的图片");
            }
            $file = $_FILES[$name];
            $prefix = $this->getImagePostFix($file['tmp_name']);
            if (!in_array($prefix, $this->allowPostfix)) {
                throw new \Exception("图片格式仅限JPG，请重新上传图片");
            }
            //压缩图片
            $this->resizeImage($file['tmp_name'], 800, 600);
            try {
                $result = uploadFile(array(
                    'file' => $file, // 文件域信息数组
                    'isImage' => 1, // 是否是图片
                    'userId' => $userId,
                    'asAttachment' => 1,//是否是附件
                    'asPrivate' => 1, //是否私有
                ));
            } catch (\Exception $e) {
            }
            if (empty($result['status']) || $result['status'] != 1) {
                Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, '图片上传失败', $userId, 'file:' . json_encode($file), 'result: ' . json_encode($result))));
                throw new \Exception("图片上传失败，请稍后重试");
            }
            $idcardPhoto[$name] = $result['full_path'];
        }
        return $idcardPhoto;
    }

    /**
     * 获取图片路径
     * v473版本以上
     */
    private function getImagePath($userId, $data, $photoName) {
        $idcardPhoto = [];
        //473版本文件已经预先上传
        foreach($photoName as $name) {
            if (empty($data[$name]) || !is_numeric($data[$name])) {
                throw new \Exception("请选择上传的图片");
            }
            $imageId = $data[$name];//图片id
            $attachmentInfo = AttachmentModel::instance()->getAttachmentById($imageId);
            if (empty($attachmentInfo)) {
                throw new \Exception("文件上传失败");
            }
            if ($attachmentInfo['user_id'] != $userId) {
                throw new \Exception("用户上传文件有误");
            }

            //获取私有图片二进制流
            $imagePath = $attachmentInfo['attachment'];
            $streamContent = VfsHelper::image($imagePath, true);

            //写临时文件
            $tmpName = '/tmp/' . $userId;
            if (!file_put_contents($tmpName, $streamContent)) {
                throw new \Exception("非法文件");
            }
            $prefix = $this->getImagePostFix($tmpName);
            if (!in_array($prefix, $this->allowPostfix)) {
                throw new \Exception("图片格式仅限JPG，请重新上传图片");
            }

            //替换原始图片
            $res = Vfs::write($imagePath, $tmpName, true);

            unlink($tmpName);

            $idcardPhoto[$name] = $imagePath;
        }
        return $idcardPhoto;
    }

    /**
     * 等比例压缩图片
     * @param  String $src_imagename 源文件名,比如 “source.jpg”
     * @param  int    $maxwidth      压缩后最大宽度
     * @param  int    $maxheight     压缩后最大高度
     * @param  String $savename      保存的文件名,“d:save”
     * @param  String $filetype      保存文件的格式,比如 ”.jpg“
     */
    private function resizeImage($src_imagename, $maxwidth, $maxheight, $savename = '', $filetype = '') {
        $im = imagecreatefromjpeg($src_imagename);
        // 获取到当前图片的宽和高
        $current_width = imagesx($im);
        $current_height = imagesy($im);
        if (($maxwidth && $current_width > $maxwidth) || ($maxheight && $current_height > $maxheight)) {
            if ($maxwidth && $current_width > $maxwidth) {
                $widthratio = $maxwidth / $current_width;
                $resizewidth_tag = true;
            }

            if ($maxheight && $current_height > $maxheight) {
                $heightratio = $maxheight / $current_height;
                $resizeheight_tag = true;
            }

            // 计算压缩比例因子
            if ($resizewidth_tag && $resizeheight_tag) {
                $ratio = $widthratio < $heightratio ? $widthratio : $heightratio;
            }

            if ($resizewidth_tag && !$resizeheight_tag) {
                $ratio = $widthratio;
            }

            if($resizeheight_tag && !$resizewidth_tag) {
                $ratio = $heightratio;
            }

            $newwidth = $current_width * $ratio;
            $newheight = $current_height * $ratio;

            if (function_exists("imagecopyresampled")) {
                $newim = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
            } else {
                $newim = imagecreate($newwidth, $newheight);
                imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
            }

            if (empty($savename)) {
                $savename = $src_imagename;
            } else {
                $savename = $savename . $filetype;
            }

            imagejpeg($newim, $savename);
            imagedestroy($newim);
        } else {
            if (!empty($savename) && $savename.$filetype != $src_imagename) {
                $savename = $savename . $filetype;
                imagejpeg($im, $savename);
            }
        }
    }

    /**
     * 通过二进制流 读取文件后缀信息
     * @param string $filename
     */
    private function getImagePostFix($filename) {
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

<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestUploadImg;
use NCFGroup\Protos\Ptp\RequestLcsUploadAvatar;

use core\service\UserImageService;


/**
 * PtpImgService
 *
 * @uses ServiceBase
 * @package default
 */
class PtpImgService extends ServiceBase
{
    /**
     * 二进制上传图片接口
     * @param  RequestUploadImg $request contents为[name=>binary_data]
     */
    public function uploadViaBin(RequestUploadImg $request)
    {
        try {

            $contents = $request->contents;
            $response = new ResponseBase;

            $res = [];
            foreach ($contents as $name => $data) {
                // 构造file参数
                $file = $this->bin2file($name, $data);
                if (!$file) {
                    $res[$name] = ['errors' => ['not image']];
                    continue;
                }
                $ret = uploadFile([
                    'file' => $file,
                    'isImage' => 1,
                    'asAttachment' => 0,
                    'asPrivate' => 0,
                ]);
                $ret['host'] = app_conf('STATIC_HOST');
                $res[$name] = $ret;
                unlink($file['tmp_name']);
            }
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->res = $res;

        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode() ?: RPCErrorCode::FAILD;
            $response->errorMsg = $e->getMessage();
        }

        return $response;
    }

    /**
     * 理财师上传头像接口
     * @param  RequestLcsUploadAvatar $request [description]
     * @return [type]                          [description]
     */
    public function uploadUserAavatar(RequestLcsUploadAvatar $request)
    {
        try {

            $uid = $request->userId;
            $img = $request->img;
            $name = $request->imgName;
            $response = new ResponseBase;

            $file = $this->bin2file($name, $img);
            $res = (new UserImageService)->uploadUserImageInfo(['file' => $file, 'user_id' => $uid]);

            if ($res['respCode'] == '00') {
                $response->resCode = RPCErrorCode::SUCCESS;
                $response->img = $res['img'];
            } else {
                throw new \Exception($res['respMsg'], $res['resCode']);
            }

        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode() ?: RPCErrorCode::FAILD;
            $response->errorMsg = $e->getMessage();
        }

        return $response;
    }

    /**
     * 二进制数据转为文件形式，方便调用公共upload方法
     * @param  [type] $name 名称
     * @param  [type] $data 二进制数据
     */
    public function bin2file($name, $data)
    {
        $id = uniqid();
        $tmpfile = "/tmp/{$id}";
        $newFile = fopen($tmpfile, "w");//打开文件准备写入
        fwrite($newFile, $data);//写入二进制流到文件
        fclose($newFile);//关闭文件
        $res['tmp_name'] = $tmpfile;

        $imageinfo = getimagesize($tmpfile);
        $imgType = [
            1 => 'gif',
            2 => 'jpg',
            3 => 'png',
            6 => 'bmp',
        ];
        if (!array_key_exists($imageinfo[2], $imgType)) return false;

        $res['name'] = $name . ".jpg";
        $res['type'] = $imageinfo['mime'];
        $res['size'] = filesize($tmpfile);
        $res['error'] = UPLOAD_ERR_OK;
        return $res;
    }

}

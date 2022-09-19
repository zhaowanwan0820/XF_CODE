<?php

/**
 * 后台公用方法处理的service类
 * @file   FunctionService.php
 * @date   2016/09/26
 *
 **/
class FunctionsService extends ItzInstanceService
{
    protected $expire1 = 180;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 分片上传
     * @param $files $_FILES
     * @param $post  传递的参数数组
     * @return array
     */
    public function uploadSlice($files, $post, $get)
    {
        $returnResult = array('code' => 0, 'info' => '', 'data' => array());
        Yii::log('slice upload: the time1 upload to server is '.time());
        //merge标记
        $mergeTag = $post['merge'];
        //分片上传
        if (empty($mergeTag)) {
            if ($files['fileToUpload']['error'] > 0) {
                $returnResult['code'] = 4090;
                $returnResult['info'] = '分片上传文件失败！';
                return $returnResult;
            }
            try {
                //获取操作人id
                $user_id = Yii::app()->user->id;
                //分片文件的文件名
                $sliceName = $user_id . '_' . $post['date'];
                //分片文件存储目录
                $slicePath = APP_DIR . '/public/upload/slice/';
                if (!is_dir($slicePath)) {
                    if (!mkdir($slicePath) && !chmod($slicePath,0777)) {
                        $returnResult['code'] = 4091;
                        $returnResult['info'] = '创建分片文件存储路径失败！';
                        return $returnResult;
                    }
                }
                $sliceTarget = $slicePath . $sliceName . '_' . $post['index'];
                //将临时文件移动到设定好的存储目录
                move_uploaded_file($files['fileToUpload']['tmp_name'], $sliceTarget);

                $returnResult['code'] = 0;
                $returnResult['info'] = '分片上传成功！';
                $returnResult['data']['filename'] = $sliceName;
                return $returnResult;
            } catch (Exception $e) {
                $returnResult['code'] = 4090;
                $returnResult['info'] = $e->getMessage();
                return $returnResult;
            }
            //合并文件
        } else {
            //文件全名
            $fileName = $post['fileName'];
            //文件大小(bit)
            $fileSize = $post['fileSize'];
            //目标文件路径
            $target = APP_DIR . '/public/upload/slice/' . $fileName;
            //源文件路径
            $source = APP_DIR . '/public/upload/slice/' . $post['merge'];

            try {
                //以写入二进制方式打开
                $dst = fopen($target, 'wb');
                //循环将分片文件打开写入
                for ($i = 1; $i <= $post['count']; $i++) {
                    $slice = $source . '_' . $i;
                    $src = fopen($slice, 'rb');
                    stream_copy_to_stream($src, $dst);
                    fclose($src);
                    unlink($slice);
                }
                fclose($dst);
            } catch (Exception $e) {
                $returnResult['code'] = 4084;
                $returnResult['info'] = $e->getMessage();
                return $returnResult;
            }
            $fileMd5 = md5_file($target);

            //判断文件合并前后是否为同以文件
            if ($fileMd5 != $post['fileMd5']) {
                $returnResult['code'] = 4092;
                $returnResult['info'] = '合并分片文件失败！';
                return $returnResult;
            }
            $service = $post['service'];
            $function = $post['function'];
            $params = array_merge($post, $get);
            Yii::log('slice upload: the time2 merge the slice is '.time());
            $result = $service::getInstance()->$function($params);
            return $result;
        }
    }

    public function get($data)
    {
        $service = $data['service'];
        $function = $data['function'];
        $d = $service::getInstance()->$function(1);
        //$d = $re ->$function(1);
        //$re = PackageService::getInstance()->lists(1);
        return $d;
    }
}
<?php

namespace core\service\face;

use libs\utils\Logger;
use libs\face\Yitu;

class OcrService {

    /**
     * ocr 通用接口
     * @param $mode OCR内容模式，1: 上传图片进行查询 2: 上传特征进行查询
     * @param $userInfo array
     *                   image_content mode =1:经Base64编码的翻拍证件照的内容。照片要求为JPEG格式。mode =1时(默认)必填,mode =2时不填
     *                   feature_content mode = 2:经Base64编码的用户特征内容 默认下不填,mode =2时必填
     * @param $options  array
     *                   ocr_type 识别的类型,1: 翻拍身份证照 2：驾驶证 3：行驶证
     *                   auto_rotate 开启自动旋转矫正(目前只支持身份证OCR)
     *                   orc_mode 当ocr_type = 1时 1: 身份证正面识别 2: 身份证背面识别 3: auto 自动区分身份证正面背面双面 4: 身份证双面识别
     */
    public function ocr($mode, $userInfo, $options = []) {
        $params = [
            'mode' => $mode,
            'user_info' => $userInfo,
            'options' => $options
        ];

        $params = json_encode($params);
        Logger::info("OcrService. mode:$mode, ocr_type:{$options['ocr_type']}, ocr_mode:{$options['ocr_mode']}");

        //请求依图ocr接口
        $result = Yitu::ocr($params);

        if (empty($result)) {
            return [
                'code' => -1,
                'message' => '服务异常',
            ];
        }

        //依图错误代码字段rtn改成code字段
        $result['code'] = $result['rtn'];
        unset($result['rtn']);

        return $result;
    }

    /**
     * 上传翻拍身份证照进行查询
     * @param $image 经Base64编码的翻拍证件照的内容。照片要求为JPEG格式。
     * @param $ocr_mode 1: 身份证正面识别 2: 身份证背面识别 3: auto 自动区分身份证正面背面双面 4: 身份证双面识别
     * @param bool $auto_rotate 开启自动旋转矫正(目前只支持身份证OCR)
     * @return array
     */
    public function idCardOcr($image, $ocr_mode, $auto_rotate = false) {
        $user_info = [
            'image_content' => $image,
        ];
        $options = [
            'ocr_type' => 1,
            'auto_rotate' => $auto_rotate,
            'ocr_mode' => $ocr_mode
        ];

        return $this->ocr(1, $user_info, $options);
    }

    /**
     * 上传驾驶证进行查询
     * @param $image 驾驶证照片
     * @return array
     */
    public function drivingLicenseOcr($image) {
        $user_info = [
            'image_content' => $image,
        ];
        $options = [
            'ocr_type' => 2,
        ];

        return $this->ocr(1, $user_info, $options);
    }
}

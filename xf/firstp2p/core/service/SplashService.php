<?php

/**
 * @abstract 闪屏service
 * @author yutao
 * @date 2015-05-09
 */
namespace core\service;

use core\dao\SplashModel;
use core\dao\AttachmentModel;

/**
 * Class SplashService
 *
 * @package core\service
 */
class SplashService extends BaseService {

    /**
     * 根绝尺寸和平台获取闪屏信息
     *
     * @param type $os
     *            移动平台 android or ios
     * @param type $width
     *            分辨率 宽度
     * @param type $height
     *            分辨率 高度
     * @return type
     */
    public function getSplashInfo($os, $width, $height, $site_id , $num) {
        // 获取闪屏配置信息
        $allSplash = SplashModel::instance()->getSplash( $site_id );
        if (empty( $allSplash )) {
            return false;
        }
        $splashList = array();
        foreach ($allSplash as $splash){
            $linkJson = json_decode($splash['link'],true);
            if ( strtotime($linkJson['valid_time']['start_time']) < time() && strtotime($linkJson['valid_time']['end_time']) > time() ) {
                $attachmentIds = '';
                if ($os == 'Android') {
                    $attachmentIds = $splash['attachment_ids_android'];
                } elseif ($os == 'iOS') {
                    $attachmentIds = $splash['attachment_ids_ios'];
                } else {
                    return false;
                }
                $match = array();
                $attIdArray = explode( ',', $attachmentIds );
                foreach( $attIdArray as $value ) {
                    $screen = explode( ':', $value );
                    if (isset( $screen[0] ) && isset( $screen[1] )) {
                        if (strtolower( $os ) . '_' . $width . '_' . $height == $screen[0]) {
                            $splash['imageId'] = $screen[1];
                            break;
                        }
                        $match[$screen[0]] = $screen[1];
                    }
                }
                if (empty( $splash['imageId'] )) {
                    $splash['imageId'] = end( $match );
                }
                // 根据imageId获得图片信息
                if (empty( $splash['imageId'] )) {
                    return false;
                }
                $imageInfo = AttachmentModel::instance()->findViaSlave( $splash['imageId'] )->getRow();
                if (empty( $imageInfo )) {
                    return false;
                }
                $splash['imageurl'] = 'http:' . \libs\vfs\Vfs::$staticHost . '/' . $imageInfo['attachment'];
                $splashList[$splash['id']] = $splash;
            }
        }
        arsort($splashList);
        $result = (is_numeric($num) &&  $num < count($splashList)) ? array_slice($splashList,0, $num) : $splashList;
        return $result;
    }
}

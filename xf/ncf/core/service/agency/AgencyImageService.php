<?php
/**
 * [机构图片]
 * @author <fanjingwen@>
 */

namespace core\service\agency;

use core\dao\agency\AgencyImageModel;
use core\enum\AgencyImageEnum;
use core\service\BaseService;

class AgencyImageService extends BaseService
{
    /**
     * [根据机构id获取相关图片]
     * @author <fanjingwen@>
     * @param int [$agencyID]
     * @return array [see:$imgArr]
     */
    public function getAgencyImages($agencyID)
    {
        $imgArr = array(
            'logo'               => '',
            'license_img'        => '',
            'business_place_img' => array(),
        );

        $agencyImgs = AgencyImageModel::instance()->getAgencyImgByID($agencyID);
        foreach ($agencyImgs as $agencyImg) {
            switch ($agencyImg['type']) {
                case AgencyImageEnum::AGENCY_IMAGE_TYPE_LOGO:
                    $imgArr['logo']['full_path'] = $agencyImg->full_path;
                    $imgArr['logo']['thumb_path'] = $agencyImg->thumb_path;
                    break;
                case AgencyImageEnum::AGENCY_IMAGE_TYPE_LICENSE:
                    $imgArr['license_img']['full_path'] = $agencyImg->full_path;
                    $imgArr['license_img']['thumb_path'] = $agencyImg->thumb_path;
                    break;
                case AgencyImageEnum::AGENCY_IMAGE_TYPE_BUSINESS_PLACE:
                    $temp['full_path'] = $agencyImg->full_path;
                    $temp['thumb_path'] = $agencyImg->thumb_path;
                    $imgArr['business_place_img'][] = $temp; // agencyImg可为数组
                    break;
            }
        }

        return $imgArr;
    }

    /**
     * 根据机构 id 获取电子签章图片地址
     * @param int $agency_id
     * @return string 在图片服务器上的相对路径
     */
    public static function getSignImgNameByAgencyId($agency_id)
    {
        $sign_img_obj = AgencyImageModel::instance()->getAgencyImgInfoOfTheType($agency_id, AgencyImageEnum::AGENCY_IMAGE_TYPE_SIGN);
        $full_path = empty($sign_img_obj) ? '' : $sign_img_obj->full_path;
        return empty($full_path) ? 0 : basename(parse_url($full_path)['path']);
    }
}

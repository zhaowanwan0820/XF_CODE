<?php
/**
 * [机构图片相关]
 * @author <fanjingwen@>
 */

namespace core\dao;

class AgencyImageModel extends BaseModel
{
    // 机构相关图片类型（对应agencyImage表type）
    CONST AGENCY_IMAGE_TYPE_LOGO = 1; // logo
    CONST AGENCY_IMAGE_TYPE_LICENSE = 2; // 营业执照
    CONST AGENCY_IMAGE_TYPE_BUSINESS_PLACE = 3; // 经营场所图
    CONST AGENCY_IMAGE_TYPE_SIGN = 4; // 电子签章

    /**
     * [根据机构id获取其所有的图片]
     * @param int [agency id]\
     * @return array
     */
    public function getAgencyImgByID($agencyID)
    {
        $agencyID = intval($agencyID);
        $condition = sprintf("`agency_id`='%d'", $agencyID);
        return $this->findAllViaSlave($condition);
    }

    /**
     * 根据机构id，获取对应类型图片的全图路径
     * @param int $agency_id
     * @param int $img_type
     * @return object
     */
    public function getAgencyImgInfoOfTheType($agency_id, $img_type)
    {
        $condition = sprintf("`agency_id`='%d' AND `type` = %d", $agency_id, $img_type);
        return $this->findByViaSlave($condition);
    }
}

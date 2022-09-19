<?php
/**
 * [机构图片相关]
 * @author <fanjingwen@>
 */

namespace core\dao\agency;
use core\dao\BaseModel;
use core\enum\AgencyImageEnum;
class AgencyImageModel extends BaseModel
{
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

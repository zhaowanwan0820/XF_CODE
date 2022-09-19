<?php
/**
 * @author wangchuanlu@ucfgroup.com
 */

namespace core\dao\related;
use core\enum\RelatedEnum;
use core\dao\BaseModel;
use libs\utils\DBDes;

class RelatedCompanyModel extends BaseModel
{
    /**
     * 是否是关联企业
     * @param $license 注册/营业执照号
     * @param $channel 渠道
     * @return bool
     */
    public function isRelatedCompany($license,$channel) {
        $condition = sprintf("license='%s' AND status = %d AND channel = %d", DBDes::encryptOneValue($license),RelatedEnum::STATUS_USEFUL,$channel);
        $result = $this->findBy($condition, '*',array(), true);
        return $result ? true : false;
    }
}
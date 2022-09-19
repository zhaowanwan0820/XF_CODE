<?php
/**
 * @author wangchuanlu@ucfgroup.com
 */

namespace core\dao\related;
use core\dao\BaseModel;
use libs\utils\DBDes;
use core\enum\RelatedEnum;

class RelatedUserModel extends BaseModel
{
    /**
     * 是否是关联用户
     * @param $idno 身份证号
     * @param $channel 渠道
     * @return bool
     */
    public function isRelatedUser($idno,$channel) {
        $condition = sprintf("idno='%s' AND status = %d AND channel = %d", DBDes::encryptOneValue($idno),RelatedEnum::STATUS_USEFUL,$channel);
        $result = $this->findBy($condition, '*',array(), true);
        return $result ? true : false;
    }
}
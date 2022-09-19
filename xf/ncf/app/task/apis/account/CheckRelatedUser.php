<?php
/**
 * 检查用户是否是关联用户
 */
namespace task\apis\account;

use core\enum\RelatedEnum;
use task\lib\ApiAction;
use libs\utils\Logger;
use core\dao\related\RelatedUserModel;
use core\dao\related\RelatedCompanyModel;

class CheckRelatedUser extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($param))));
        if (!isset($param['idno']) || !isset($param['userType'])) {
            throw new \Exception('参数不正确！',1);
        }
        $idno = trim($param['idno']);
        $userType = intval($param['userType']);
        //是否是关联方
        $isRelated = false;
        if (RelatedEnum::USER_TYPE_USER == $userType) {
            $relatedUserModel = new RelatedUserModel();
            $isRelated = $relatedUserModel->isRelatedUser($idno,RelatedEnum::CHANNEL_NCFWX);
        } elseif (RelatedEnum::USER_TYPE_COMPANY == $userType) {
            $relatedCompanyModel = new RelatedCompanyModel();
            $isRelated = $relatedCompanyModel->isRelatedCompany($idno,RelatedEnum::CHANNEL_NCFWX);
        }
        $this->json_data = $isRelated ? 1 : 0;
    }
}

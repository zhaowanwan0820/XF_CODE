<?php

namespace core\service;

use core\dao\PaymentNoticeModel;
use core\dao\UserModel;
use libs\utils\PaymentApi;
use core\service\UserTagService;

/**
 * 用户信用记录
 */
class UserCreditService extends BaseService
{

    /**
     * 是否可信
     */
    public function isCredible($userId)
    {
        $tagService = new UserTagService();
        $staticWhitelistTag = 'SUPERVISION_STATIC_WHITELIST';
        return $tagService->getTagByConstNameUserId($staticWhitelistTag, $userId);
    }
}

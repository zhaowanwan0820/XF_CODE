<?php
/**
 * UserFreezeMoneyService.php
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\UserFreezeMoneyModel;

class UserFreezeMoneyService extends BaseService {
    /**
     * 批准或拒绝冻结申请
     * @param int $id
     * @param bool $is_passed true-批准 false-拒绝
     * @return array
     */
    public function verifyFreezeMoney($id, $is_passed) {
        $userFreezeModel = new UserFreezeMoneyModel();
        return $userFreezeModel->verifyFreezeMoney($id, $is_passed);
    }
}

<?php
/**
 * MoneyApplyService.php
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace core\service;

use core\dao\MoneyApplyModel;

/**
 * Class MoneyApplyService
 * @package core\service
 */
class MoneyApplyService extends BaseService {
    /**
     * 批准或拒绝提现申请      
     * @param int $id
     * @param bool $is_passed true-批准 false-拒绝
     * @return array
     */
    public function verifyMoneyApply($id, $is_passed) {
        $money_apply_dao = new MoneyApplyModel();   
        return $money_apply_dao->verifyMoneyApply($id, $is_passed);
    }
}

<?php
/**
 * @date 2014-04-24
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace core\service;

use core\dao\BanklistModel;
/**
 * @package core\service
 */
class BanklistService extends BaseService {

    /**
     * 获取网点列表
     * @return \libs\db\Model
     */
    public function getBanklist($city="", $p="",$b="") {
        if (empty($city)&&empty($p)&&empty($b)) {
            return false;
        }
        return BanklistModel::instance()->getBanklist($city, $p, $b);
    }

    /**
     * getBankIssueByName
     * 根据支行名称查询联行号
     * @param string $branchBankName 支行名称
     * @return string
     */
    public function getBankIssueByName($branchBankName) {
        $bankIssue = '';
        $bankInfo = BanklistModel::instance()->findBy(' name = ":name" ', 'bank_id', array(':name' => $branchBankName));
        $bank_id = $bankInfo->bank_id;
        if ($bankInfo && !empty($bank_id)) {
            $bankIssue  = $bank_id;
        }
        return $bankIssue;
    }

    /**
     * 更新银行支行信息
     * @param array $params
     */
    public function saveBankListById($params) {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        // 检查支行名称是否已更新
        $bankBranchName = BanklistModel::instance()->findViaSlave($id, 'name');
        if (!empty($bankBranchName) && $bankBranchName === trim($params['name'])) {
            return true;
        }

        $data = [];
        !empty($params['name']) && $data['name'] = addslashes(trim($params['name']));
        !empty($params['bank_id']) && $data['bank_id'] = addslashes(trim($params['bank_id']));
        !empty($params['branch']) && $data['branch'] = addslashes(trim($params['branch']));
        !empty($params['province']) && $data['province'] = addslashes(trim($params['province']));
        !empty($params['city']) && $data['city'] = addslashes(trim($params['city']));

        if (empty($data)) {
            return false;
        }
        return BanklistModel::instance()->saveBankListById($id, $data);
    }
}

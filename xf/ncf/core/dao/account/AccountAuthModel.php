<?php
/**
 * 账户授权表
 * @date 2017-12-30
 * @author weiwei12@ucfgroup.com>
 */

namespace core\dao\account;

use core\dao\BaseModel;

class AccountAuthModel extends BaseModel
{
    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 更换表名
     */
    public function tableName()
    {
        return self::$prefix . 'account_authorization';
    }

    /**
     * 根据账户ID查询授权
     * @param int $accountId 账户ID
     * @return \libs\db\model
     */
    public function getAuthListByAccountId($accountId)
    {
        $authList = $this->findAll('`account_id`=:account_id', true, '*', [':account_id'=>intval($accountId)]);
        $result = [];
        foreach ($authList as $auth) {
            $result[$auth['grant_type']] = $auth;
        }
        return $result;
    }

    /**
     * 添加账户授权
     * @param array $params
     * @return bool
     */
    public function addAuth($params)
    {
        if (empty($params)) {
            return false;
        }
        $data = [
            'account_id'        => (int) $params['accountId'],
            'user_id'           => (int) $params['userId'],
            'grant_type'        => (int) $params['grantType'],
            'grant_amount'      => (int) $params['grantAmount'], //单位 分/笔
            'grant_time'        => (int) $params['grantTime'], //授权到期时间
            'create_time'       => time(),
            'update_time'       => time(),
        ];
        $this->setRow($data);

        if ($this->insert()) {
            return $this->db->insert_id();
        } else {
            throw new \Exception('添加失败');
        }
    }

    /**
     * 删除账户授权
     * @param int $accountId
     * @return bool
     */
    public function deleteAuth($accountId, $grantTypeList = []) {
        $sql = "DELETE FROM " . $this->tableName() . " WHERE account_id = '" . intval($accountId) . "'";
        if (!empty($grantTypeList)) {
            $grantTypeList = array_map('intval', $grantTypeList);
            $sql .= sprintf(" AND grant_type in (%s)", implode(',', $grantTypeList));
        }
        $result = $this->db->query($sql);
        return $result;
    }

    /**
     * 获取账户具体权限
     */
    public function getAuthInfoByAccountId($accountId, $grantType)
    {
        return $this->findBy('`account_id`=:account_id AND `grant_type`=:grant_type', '*', array(':account_id'=>intval($accountId), ':grant_type' => $grantType));
    }

    /**
     * 更新授权
     * @param array $params
     * @return boolean
     */
    public function updateAuth($params)
    {
        if (empty($params)) {
            return false;
        }
        $this->updateBy(
            array(
                'grant_amount'      => (int) $params['grantAmount'], //单位 分/笔
                'grant_time'        => (int) $params['grantTime'], //授权到期时间
                'update_time'       => time(),
            ),
            sprintf('`account_id`=%d AND `grant_type`=%d', intval($params['accountId']), intval($params['grantType']))
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 保存账户授权
     */
    public function saveAuth($params) {
        if (empty($params)) {
            return false;
        }
        if ($this->getAuthInfoByAccountId($params['accountId'], $params['grantType'])) {
            $result = $this->updateAuth($params);
        } else {
            $result = $this->addAuth($params);
        }
        return $result;
    }

}

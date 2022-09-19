<?php
/**
 * 账户授权表
 * @date 2017-12-30
 * @author weiwei12@ucfgroup.com>
 */

namespace core\dao;

class AccountAuthorizationModel extends BaseModel
{
    /**
     * 授权类型
     * @var int
     */
    const GRANT_TYPE_INVEST = 1; //免密投标
    const GRANT_TYPE_REPAY = 2; //免密还款
    const GRANT_TYPE_PAYMENT = 3; //免密缴费

    /**
     * 投资类型映射
     * @var array
     */
    public static $grantTypeMap = [
        'INVEST'        => self::GRANT_TYPE_INVEST,
        'REPAY'         => self::GRANT_TYPE_REPAY,
        'SHARE_PAYMENT' => self::GRANT_TYPE_PAYMENT,
    ];

    /**
     * 投资类型映射
     * @var array
     */
    public static $grantTypeName = [
        self::GRANT_TYPE_INVEST => '免密投标授权',
        self::GRANT_TYPE_REPAY => '免密还款授权',
        self::GRANT_TYPE_PAYMENT => '免密缴费授权',
    ];

    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
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

<?php
/**
 * ApiConfModel class file.
 *
 * @author zhaohui3@ucfgroup.com
 **/

namespace core\dao;

/**
 * 后台api配置信息
 *
 * @author zhaohui3@ucfgroup.com
 **/
class ApiConfModel extends BaseModel
{

    //公告位置枚举值
    const NOTICE_PAGE_INDEX = 1;
    const NOTICE_PAGE_CHARGE = 7;
    const NOTICE_PAGE_CARRY = 8;
    const NOTICE_PAGE_CHARGEWX = 22;
    const NOTICE_PAGE_CHARGEP2P = 23;
    const NOTICE_PAGE_CARRYWX = 24;
    const NOTICE_PAGE_CARRYP2P = 25;

    //公告位置
    public static $noticePageList = [
        self::NOTICE_PAGE_INDEX     => '首页',
        self::NOTICE_PAGE_CHARGE    => '充值',
        self::NOTICE_PAGE_CARRY     => '提现',
        self::NOTICE_PAGE_CHARGEWX  => '网信账户充值',
        self::NOTICE_PAGE_CHARGEP2P => '网贷账户充值',
        self::NOTICE_PAGE_CARRYWX   => '网信账户提现',
        self::NOTICE_PAGE_CARRYP2P  => '网贷账户提现',
    ];

    /**
     * getConfInfoByCondition
     * 根据给定条件返回相应的配置信息
     * @access public
     * @param string $condition 查询条件
     * @param boolean $is_array 是否返回数组
     *
     */
    public function getConfInfoByCondition($condition,$is_array=true,$fields="*", $params = array()) {
        return $this->findAllViaSlave($condition, $is_array, $fields,$params);
    }

    public function addRecord($data) {
        $this->create_time = time();
        foreach ($data as $field => $value) {
            if ($value !== NULL && $value !== '') {
                $this->$field = $this->escape($value);
            }
        }

        if ($this->insert()) {
            return $this->db->insert_id();
        }
        return false;
    }
}

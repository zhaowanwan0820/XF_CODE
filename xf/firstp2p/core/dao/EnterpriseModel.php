<?php
/**
 * Enterprise class file.
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\UserModel;
use core\dao\EnterpriseContactModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class EnterpriseModel extends BaseModel {

    //***!!!!important
    //
    // 此常量已废弃，请转到NCFGroup\Protos\Ptp\Enum\UserAccountEnum中维护
    //
    //***
    const COMPANY_PURPOSE_MIX                  = 0; // 借贷混合用户
    const COMPANY_PURPOSE_INVESTMENT           = 1; // 投资户
    const COMPANY_PURPOSE_FINANCE              = 2; // 融资户
    const COMPANY_PURPOSE_ADVISORY             = 3; // 咨询户
    const COMPANY_PURPOSE_GUARANTEE            = 4; // 担保户
    const COMPANY_PURPOSE_CHANNEL              = 5; // 渠道户
    const COMPANY_PURPOSE_CHANNELFICTILE       = 6; // 渠道虚拟户
    const COMPANY_PURPOSE_PURCHASE             = 7; // 资产收购户
    const COMPANY_PURPOSE_REPLACEPAY           = 8; // 代垫户
    const COMPANY_PURPOSE_CAPITAL              = 9; // 受托资产管理户
    const COMPANY_PURPOSE_TRADECENTER          = 10; // 交易中心（所）
    const COMPANY_PURPOSE_PLATFORM             = 11; // 平台户
    const COMPANY_PURPOSE_DEPOSIT              = 12; // 保证金户
    const COMPANY_PURPOSE_PAY                  = 13; // 支付户
    const COMPANY_PURPOSE_COUPON               = 14; // 投资券户
    const COMPANY_PURPOSE_BONUS                = 15; // 红包户
    const COMPANY_PURPOSE_RECHARGE             = 16; // 代充值户
    const COMPANY_PURPOSE_LOAN                 = 17; // 放贷户

    /**
     * enterprise表中字段名称 - company_name
     * @var string
     */
    const TABLE_FIELD_COMPANY_NAME = 'company_name';

    /**
     * 企业证件有效截止时间默认值
     * @var string
     */
    public static $credentialsExpireAtDefault='2070-12-31';

    /**
     * 读取企业用户联系人信息
     */
    public function getContactInfo()
    {
        return EnterpriseContactModel::instance()->findByViaSlave(" user_id = '{$this->user_id}'");
    }

    /**
     * [根据企业用户id，获取企业用户信息]
     * @author <fanjingwen@ufcgroup.com>
     * @param int $userID
     * @return model [有实体就返回对象，否则返回空]
     */
    public function getEnterpriseInfoByUserID($userID)
    {
        $cond = "`user_id` = " . intval($userID);
        return self::findByViaSlave($cond);
    }

    /**
     * 获取最大的企业用户Id
     */
    public function getMaxEnterpriseId()
    {
        $sql = sprintf('SELECT MIN(user_id) AS minId,MAX(user_id) AS maxId FROM `%s`', $this->tableName());
        return $this->findBySqlViaSlave($sql);
    }

    /**
     * 企业用途映射表
     * @return array
     */
    public static function getCompanyPurposeMap()
    {
        $purposeMap = [];
        if (empty($GLOBALS['dict']['ENTERPRISE_PURPOSE'])) {
            return $purposeMap;
        }
        foreach ($GLOBALS['dict']['ENTERPRISE_PURPOSE'] as $item) {
            $purposeMap[$item['bizId']] = $item['bizName'];
        }
        return $purposeMap;
    }

    /**
     * 企业用户所需要的列表
     * @return array
     */
    public static function getCompanyPurposeList()
    {
        $purposeMap = [];
        $purposeList = $GLOBALS['dict']['ENTERPRISE_PURPOSE'];
        if (empty($purposeList)) {
            return $purposeMap;
        }
        foreach ($purposeList as $key => $item) {
            if ($item['bizId'] == self::COMPANY_PURPOSE_MIX) {
                unset($purposeList[$key]);
            }
        }
        return $purposeList;
    }

    /**
     * 企业用户所需要的列表
     * @return array
     */
    public static function getCompanyPurposeListCN()
    {
        $purposeMap = [];
        $purposeList = $GLOBALS['dict']['ENTERPRISE_PURPOSE_CN'];
        if (empty($purposeList)) {
            return $purposeMap;
        }
        foreach ($purposeList as $key => $item) {
            if ($item['bizId'] == self::COMPANY_PURPOSE_MIX) {
                unset($purposeList[$key]);
            }
        }
        return $purposeList;
    }

    /**
     * 更新企业用户存管系统id
     * @param int $supervisionUserId
     */
    public function updateEnterpriseSupervisionUserId($supervisionUserId)
    {
        $this->updateBy(array('supervision_user_id'=>$supervisionUserId, 'update_time'=>get_gmtime()), sprintf('user_id=%d AND supervision_user_id=0', $supervisionUserId));
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * [根据企业用户id，更新企业基本信息]
     * @author <liwenling@ufcgroup.com>
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function updateByUid($user_id = 0, $data)
    {
        $condition = "user_id = {$user_id}";
        return $this->updateBy($data, $condition);
    }

    /**
     * [根据企业名称获取数据]
     * @author <liwenling@ufcgroup.com>
     * @param int $user_id
     * @param array $company_name
     * @return array
     */
    public function getByCompanyName($company_name, $user_id = 0, $company_purpose = 0)
    {
        $params = array(':company_name' => $company_name);
        $condition = " company_name = ':company_name'";
        if ($user_id) {
            $condition .= ' AND user_id <> :user_id';
            $params = array_merge($params,array(':user_id' => $user_id));
        }

        if ($company_purpose > 0) {
            $condition .= ' AND company_purpose = :company_purpose';
            $params[':company_purpose'] = $company_purpose;
        }

        return $this->findAll($condition, true, '*',$params);
    }

    /**
     * [根据企业证件号获取数据]
     * @author <liwenling@ufcgroup.com>
     * @param int $user_id
     * @param array $credentials_no  证件号
     * @return array
     */
    public function getByCredentialsNo($credentials_no, $user_id = 0, $company_purpose = 0)
    {
        $params = array(':credentials_no' => $credentials_no);
        $condition = " credentials_no = ':credentials_no'";
        if ($user_id) {
            $condition .= " AND user_id <> ':user_id' ";
            $params = array_merge($params,array(':user_id' => $user_id));
        }

        if ($company_purpose > 0) {
            $condition .= ' AND company_purpose = :company_purpose';
            $params[':company_purpose'] = $company_purpose;
        }

        return $this->findAll($condition, true, '*',$params);
    }

    /**
     * 获取所有的企业用户
     */
    public function getAllEnterpriseUidList($fields = '*') {
        return $this->findAll('', true, $fields);
    }

    /**
     * 添加新企业用户的开户信息
     */
    public function addEnterpriseInfo($data) {
        // 判空
        if (!$this->checkEmpty($data)) {
            return false;
        }

        $this->create_time = time();
        $this->update_time = $this->create_time;
        $this->setRow($data);
        return $this->insert();
    }

    /**
     * 更新企业用户信息
     */
    public function updateEnterpriseInfo($data) {
        if (!$this->checkEmpty($data)) {
            return false;
        }

        $this->update_time = time();
        return $this->update($data);
    }
    /**
     * 检查参数是否为空
     */
    public function checkEmpty($data) {
        foreach ($data as $value) {
            if ($value === '' || $value === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取用户id
     * @param credentialsNo 证件号
     * @param companyName 公司名称
     * @param userName 用户名称
     * @return array
     */
    public function getUserIdByCCU($credentialsNo,$companyName,$userName){
       $sql = sprintf("SELECT u.`id` AS `id` FROM %s AS u LEFT JOIN %s AS e ON u.`id` = e.`user_id`
            WHERE e.`credentials_no` = '%s' AND e.`company_name` = '%s' AND u.`user_name` = '%s' ;",
             UserModel::instance()->tableName(), $this->tableName(), addslashes($credentialsNo), addslashes($companyName), addslashes($userName));
        $results = $this->findAllBySql($sql);
        $userIds = array();
        foreach($results as $value){
            $userIds[] = $value['id'];
        }
        return $userIds;
    }

}

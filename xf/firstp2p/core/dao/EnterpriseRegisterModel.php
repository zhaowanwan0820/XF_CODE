<?php
/**
 * Enterprise class file.
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\EnterpriseContactModel;

class EnterpriseRegisterModel extends BaseModel {

    const COMPANY_PURPOSE_OTHERS        = 0; // 其他
    const COMPANY_PURPOSE_INVESTMENT    = 1; // 投资
    const COMPANY_PURPOSE_FINANCE       = 2; // 融资
    const COMPANY_PURPOSE_ADVISORY      = 3; // 咨询
    const COMPANY_PURPOSE_GUARANTEE     = 4; // 担保
    const COMPANY_PURPOSE_CHANNEL       = 5; // 渠道

    const VERIFY_STATUS_NO_INFO         = 1; //信息未填写
    const VERIFY_STATUS_HAS_INFO        = 2; //信息已填写
    const VERIFY_STATUS_FIRST_PASS      = 3; //初审通过
    const VERIFY_STATUS_PASS            = 4; //审核通过
    const VERIFY_STATUS_FAILED          = 5; //审核失败

    public static $VERIFY_STATUS = array(
        self::VERIFY_STATUS_NO_INFO => '信息未填写',
        self::VERIFY_STATUS_HAS_INFO => '信息已填写',
        self::VERIFY_STATUS_FIRST_PASS => '初审通过',
        self::VERIFY_STATUS_PASS => '审核通过',
        self::VERIFY_STATUS_FAILED => '审核失败'
    );

    /**
     * enterprise表中字段名称 - company_name
     * @var string
     */
    const TABLE_FIELD_COMPANY_NAME = 'company_name';

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
     * 企业用途映射表
     * @return array
     */
    public function getCompanyPurposeMap()
    {
        return array(
            self::COMPANY_PURPOSE_OTHERS => '其它',
            self::COMPANY_PURPOSE_INVESTMENT => '投资',
            self::COMPANY_PURPOSE_FINANCE => '融资',
            self::COMPANY_PURPOSE_ADVISORY => '咨询',
            self::COMPANY_PURPOSE_GUARANTEE => '担保',
            self::COMPANY_PURPOSE_CHANNEL => '渠道',
        );
    }


    /**
     * 企业注册审核状态
     * @return array
     */
    public function getVerfiyStatus()
    {
        return [
            self::VERIFY_STATUS_NO_INFO => '信息未填写',
            self::VERIFY_STATUS_HAS_INFO => '信息已填写',
            self::VERIFY_STATUS_FIRST_PASS => '初审通过'
        ];
    }


    /**
     * 企业初审通过状态
     * @return array
     */
    public function getFirstPassStatus()
    {
        return [
            self::VERIFY_STATUS_FIRST_PASS => '初审通过'
        ];
    }

    // 通过用户id获取企业注册信息
    public function getInfoByUserID($userID) {
        $cond = "`user_id` = " . intval($userID);
        return self::findByViaSlave($cond);
    }

    /**
     * 更新审核状态
     * @param $userId int 用户id
     * @param $params array 更新数据
     * @return bool
     */
    public function updateVerifyStatus($userId, $params) {
        // 验证审核状态
        if (isset($params['verify_status']) && !array_key_exists($params['verify_status'], self::$VERIFY_STATUS)) {
            return false;
        }

        $info = $this->findBy(sprintf("user_id = '%s'", $userId));
        if ($info && is_array($params)) {
            if (isset($params['verify_status']) && $params['verify_status'] == self::VERIFY_STATUS_HAS_INFO) {
                // 只有在未填写资料状态下面，才能更新为资料填写
                if ($info['verify_status'] != self::VERIFY_STATUS_NO_INFO) {
                    unset($params['verify_status']);
                }
            }

            return $info->update($params);
        }
        return false;
    }
}

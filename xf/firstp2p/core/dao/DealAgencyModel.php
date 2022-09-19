<?php
/**
 * DealAgency class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * DealAgency class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealAgencyModel extends BaseModel {

    const TYPE_GUARANTEE    =  1; // 担保
    const TYPE_CONSULT      =  2; // 咨询
    const TYPE_PLATFORM     =  3; // 平台
    const TYPE_PAYMENT      =  4; // 支付
    const TYPE_MANAGEMENT   =  5; // 管理
    const TYPE_ADVANCE      =  6; // 垫付
    const TYPE_ENTRUST      =  7; // 受托
    const TYPE_RECHARGE     =  8; // 代充值
    const TYPE_JYS          =  9; // 交易所
    const TYPE_CANAL        = 10; // 渠道机构
    const TYPE_INVESTMENT   = 11; //投资顾问机构
    const TYPE_MANAGER      = 12; //业务管理方

    /**
     * 根据id获取机构信息
     * @param int $id
     * @return object
     */
    public function getDealAgencyById($id) {
        $id = intval($id);
        $condition = sprintf("`id`='%d' AND `is_effect`='1'", $id);
        return $this->findByViaSlave($condition);
    }

    /**
     * 根据type获取机构列表
     * @param int $type
     * @return array
     */
    public function getDealAgencyList($type) {
        $condition = sprintf("`is_effect`='1' AND `type`='%d' ORDER BY `sort` DESC", intval($type));
        $list = $this->findAllViaSlave($condition);
        $result = array();
        foreach ($list as $v) {
            $result[$v['id']] = $v;
        }
        return $result;
    }

    /**
     * 根据deal_id获取平台机构关联用户id
     * @param int $deal_id
     * @return int
     */
    public function getLoanAgencyUserId($deal_id) {
        $ds_model = new \core\dao\DealSiteModel();
        $row = $ds_model->getSiteByDeal($deal_id);
        $site_id = $row['site_id'];

        if ($site_id) {
            $condition = sprintf("`is_effect`='1' AND `is_icp`='1' AND `type`='3' AND `site_id`='%d'", intval($site_id));
            $row = $this->findByViaSlave($condition);
            if ($row && $row['user_id']) {
                return $row['user_id'];
            }
        }

        return app_conf('LOAN_FEE_USER_ID');
    }

    /**
     * 根据site id 获取平台机构关联用户id
     * 获取不到读公共配置
     * @param int $site_id
     */
    public function getLoanAgencyUserIdBySiteId($site_id){
        if ($site_id) {
            $condition = sprintf("`is_effect`='1' AND `is_icp`='1' AND `type`='3' AND `site_id`='%d'", intval($site_id));
            $row = $this->findByViaSlave($condition);
            if ($row && $row['user_id']) {
                return $row['user_id'];
            }
        }

        return app_conf('GOLD_LOAN_FEE_USER_ID');
    }

    /**
     * 根据deal_id获取平台机构信息
     * @param int $deal_id
     * @return array
     */
    public function getLoanAgencyByDealId($deal_id) {
        $ds_model = new \core\dao\DealSiteModel();
        $row = $ds_model->getSiteByDeal($deal_id);
        $site_id = $row['site_id'];

        if ($site_id) {
            $condition = sprintf("`is_effect`='1' AND `is_icp`='1' AND `type`='3' AND `site_id`='%d'", intval($site_id));
            $row = $this->findByViaSlave($condition);
            if ($row) {
                return $row;
            }
        }
        return array();
    }

    /**
     * 获取默认的支付机构id
     * @return int
     */
    public function getUcfPayAgencyId() {
        $condition = "`is_effect`='1' AND `type`='4' ORDER BY `id` ASC LIMIT 1";
        $row = $this->findByViaSlave($condition);
        return $row['id'];
    }

    /**
     * 机构类型映射表
     * @return array
     */
    public function getAgencyTypeMap() {
        return array(
            self::TYPE_GUARANTEE    => '担保/代偿I机构',
            self::TYPE_CONSULT      => '咨询机构',
            self::TYPE_PLATFORM     => '平台机构',
            self::TYPE_PAYMENT      => '支付机构',
            self::TYPE_MANAGEMENT   => '管理机构',
            self::TYPE_ADVANCE      => '担保/代偿II-b机构',
            self::TYPE_ENTRUST      => '受托机构',
            self::TYPE_RECHARGE     => '担保/代偿II-a机构',
            self::TYPE_JYS          => '交易所',
            self::TYPE_CANAL        => '渠道机构',
            self::TYPE_INVESTMENT   => '投资顾问机构',
            self::TYPE_MANAGER      => '业务管理方',
        );
    }


    /**
     * 机构类型映射表普惠
     * @return array
     */
    public function getAgencyTypeMapCn() {
        return array(
            self::TYPE_GUARANTEE    => '担保/代偿I机构',
            self::TYPE_CONSULT      => '咨询机构',
            self::TYPE_PAYMENT      => '支付机构',
            self::TYPE_MANAGEMENT   => '管理机构',
            self::TYPE_ADVANCE      => '担保/代偿II-b机构',
            self::TYPE_RECHARGE     => '担保/代偿II-a机构',
        );
    }


    /**
     * 根据type、机构名称，获取机构列表
     * @param int $type
     * @param string $name
     * @return array
     */
    public function getListByAgencyName($type, $name) {
        $condition = sprintf("`is_effect`='1' AND `type`='%d' AND `name`='%s'", intval($type), addslashes($name));
        return $this->findAllViaSlave($condition, true, 'id,type,name,user_id,agency_user_id,short_name,realname,mobile,license,site_id');
    }

    /**
     * 根据机构类型和名称（非必填，支持模糊查询）
     * @param int $type
     * @param $name
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getListByTypeName($type, $name, $page_num, $page_size, $is_credit_display = 0) {
        $limit = " LIMIT :prev_page , :curr_page";
        $params = array(
            ":prev_page" => ($page_num - 1) * $page_size,
            ":curr_page" => $page_size
        );
        $condition = sprintf("`is_effect` = '1' AND `type`='%d'", intval($type));
        if (!empty($name)) {
            $condition .= " AND `name` like " .'\'%'.htmlentities($name).'%\'';
        }
        if (!empty($is_credit_display)) {
            $condition .= " AND `is_credit_display` = 1 ";
        }
        $count = $this->findAllViaSlave($condition, true, 'count(*) as count',$params);
        $condition .= $limit;
        $list = $this->findAllViaSlave($condition, true, 'id, name',$params);
        $res['total_page'] = ceil(bcdiv($count[0]['count'],$page_size,2));
        $res['total_size'] = intval($count[0]['count']);
        $res['res_list'] = $list;
        return $res;
    }

    /**
     * 根据ids获取机构列表
     * @param string $ids
     * @return array
     */
    public function getDealAgencyByIds($ids) {
        $condition = sprintf("id IN (%s) AND is_effect=1", $this->escape($ids));
        return $this->findAllViaSlave($condition, true);
    }
}

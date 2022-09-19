<?php

namespace core\dao\deal;

use core\dao\BaseModel;
use core\dao\deal\DealSiteModel;
use core\enum\DealAgencyEnum;

class DealAgencyModel extends BaseModel {
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
     * 机构类型映射表普惠
     * @return array
     */
    public function getAgencyTypeMapCn() {
        return array(
            DealAgencyEnum::TYPE_GUARANTEE    => '担保/代偿I机构',
            DealAgencyEnum::TYPE_CONSULT      => '咨询机构',
            DealAgencyEnum::TYPE_PLATFORM      => '平台机构',
            DealAgencyEnum::TYPE_PAYMENT      => '支付机构',
            DealAgencyEnum::TYPE_MANAGEMENT   => '管理机构',
            DealAgencyEnum::TYPE_ADVANCE      => '担保/代偿II-b机构',
            DealAgencyEnum::TYPE_RECHARGE     => '担保/代偿II-a机构',

        );
    }

    /**
     * 获取默认的支付机构id
     * @return int
     */
    public function getUcfPayAgencyId() {
        $condition = sprintf("`is_effect`='1' AND `type`='%d' ORDER BY `id` ASC LIMIT 1", DealAgencyEnum::TYPE_PAYMENT);
        $row = $this->findByViaSlave($condition);
        return $row['id'];
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
        $ds_model = new DealSiteModel();
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
     * 根据deal_id获取平台机构信息
     * @param int $deal_id
     * @return array
     */
    public function getLoanAgencyByDealId($deal_id) {
        $ds_model = new DealSiteModel();
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
     * 根据代理人id获取机构用户
     * @param unknown $user_id
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    public function getAgencyByAgencyUserId($agency_user_id, $type = 1) {
        $condition = "agency_user_id=:agency_user_id AND type=:type";
        return $this->findBy($condition, '*', array(':agency_user_id' => intval($agency_user_id), ':type' => intval($type)));
    }


    /**
     * 根据机构类型和名称（非必填，支持模糊查询）
     * @param int $type
     * @param $name
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getListByTypeName($type, $name , $page_num, $page_size, $is_credit_display = 0) {
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



}

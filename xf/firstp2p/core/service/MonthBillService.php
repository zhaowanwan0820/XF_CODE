<?php
/**
 * UserService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;
use core\dao\MonthBillModel;


/**
 * Class UserService
 * @package core\service
 */
class MonthBillService extends BaseService {


    function insert($data) {
        $mb = new MonthBillModel();
        return $mb->insertData($data);
    }

    /**
     * 获取月对账单和附件 列表
     * @param int $condition['offset']     起始位置
     * @param int $condition['pagesize']   一页大小
     * @param int $condition['year']       年份
     * @param int $condition['month']      月份
     * @author zhanglei5@ucfgroup.com
     */
    public function getList($condition) {
        return MonthBillModel::instance()->getList($condition);
    }

    /**
     * 设置发送状态
     * @param array $uids  用户id
     * @author zhanglei5@ucfgroup.com
     */
	public function setSendByUids($uids) {
        return MonthBillModel::instance()->setSendByUids($uids);
    }

}

<?php
/**
 * 预约入口表
 * @date 2019-02-18
 * @author weiwei12@ucfgroup.com>
 */

namespace core\dao;

class ReservationEntraModel extends BaseModel
{
    private static $reserveEntraMap = [];

    /**
     * 更新时的参数列表
     * @var array
     */
    private $updateParams = array();

    //入口状态
    const STATUS_VALID = 1; //有效
    const STATUS_INVALID = 0; //无效

    public static $statusName = [
        self::STATUS_VALID => '有效',
        self::STATUS_INVALID => '无效',
    ];

    //默认最小预约金额
    const RESERVE_DEFAULT_MIN_AMOUNT = 1000;

    /**
     * 更换表名
     * 复用卡片表
     */
    public function tableName()
    {
        return self::$prefix . 'reservation_card';
    }

    /**
     * 获取全部预约入口列表
     * @param mix $status 入口状态
     * @return array
     */
    public function getReserveEntraList($status = self::STATUS_VALID, $limit = 0, $offset = 0)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE 1 = 1';
        if ($status >= 0) {
            $sql .= ' AND status = ' . intval($status);
        }
        $sql .= ' ORDER BY status DESC, invest_unit ASC, invest_line ASC, invest_rate ASC, min_amount ASC, id DESC';

        if ($limit > 0) {
            $offset = intval($offset);
            $limit = intval($limit);
            $sql .= " LIMIT {$offset}, {$limit} ";
        }
        return $this->db->getAll($sql);
    }

    /**
     * 根据投资期限获取预约入口
     * @return mix
     */
    public function getReserveEntra($investLine, $investUnit, $dealType, $investRate, $loantype, $status = self::STATUS_VALID, $id = 0)
    {
        if (empty($investLine) || empty($investUnit)) {
            return false;
        }

        $key = implode('_', func_get_args());
        if (isset(self::$reserveEntraMap[$key])) {
            return self::$reserveEntraMap[$key];
        }

        $whereParams = '`invest_line`=:invest_line AND `invest_unit`=:invest_unit AND `deal_type`=:deal_type';
        $whereValues = [
            ':invest_line' => intval($investLine),
            ':invest_unit' => intval($investUnit),
            ':deal_type' => intval($dealType),
        ];
        if ($investRate > 0) {
            $whereParams .= ' AND `invest_rate` = :invest_rate';
            $whereValues[':invest_rate'] = $investRate;
        }
        if (!empty($loantype)) {
            $whereParams .= ' AND `loantype` in :loantype';
            $whereValues[':loantype'] = '(0,' . intval($loantype) . ')';
        }
        if ($status >= 0) {
            $whereParams .= ' AND `status` = :status';
            $whereValues[':status'] = (int) $status;
        }
        if ($id >= 0) {
            $whereParams .= ' AND `id` <> :id';
            $whereValues[':id'] = (int) $id;
        }
        $whereParams .= ' ORDER BY `status` DESC';
        $data = $this->findBy($whereParams, '*', $whereValues, true);
        $data = !empty($data) && is_object($data) ? $data->getRow() : $data;
        self::$reserveEntraMap[$key] = $data;
        return $data;
    }

    /**
     * 根据id获取预约入口
     * @return mix
     */
    public function getReserveEntraById($id)
    {
        return $this->find($id);
    }

    /**
     * 批量
     * 根据ids获取预约入口
     * @return array
     */
    public function getReserveEntraByIds($ids)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . sprintf(' WHERE `id` IN (%s);', implode(',', $ids));
        return $this->db->getAll($sql);
    }

    /**
     * 创建预约入口
     * @return boolean
     */
    public function createReserveEntra($params)
    {
        $check = $this->_checkParams($params);
        if (!$check) {
            return false;
        }
        $this->create_time = time(); // 创建时间
        try {
            $result = $this->save();
            if(!$result) {
                throw new \Exception("create reservation_conf failed");
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 更新预约入口
     * @return boolean
     */
    public function updateReserveEntra($params)
    {
        $check = $this->_checkParams($params);
        if (!$check || empty($params['id'])) {
            return false;
        }
        $this->updateParams['update_time'] = time(); // 更新时间
        return $this->updateBy(
            $this->updateParams,
            sprintf('`id`=%d', intval($params['id']))
        );
    }

    /**
     * 操作数据前的参数校验
     */
    private function _checkParams($params)
    {
        if (empty($params) || !isset($params['dealType']) || !isset($params['status']) || !isset($params['loantype']) || empty($params['investRate'])
            || empty($params['investLine']) || empty($params['investUnit']) || empty($params['productGradeConf'])
            || empty($params['minAmount']) || empty($params['rateFactor']) || empty($params['description'])) {
            return false;
        }
        $this->deal_type = $this->updateParams['deal_type'] = intval($params['dealType']); // 贷款类型
        $this->status = $this->updateParams['status'] = $params['status'];
        $this->invest_line = $this->updateParams['invest_line'] = intval($params['investLine']); // 投资期限
        $this->invest_unit = $this->updateParams['invest_unit'] = intval($params['investUnit']); // 投资期限单位
        $this->invest_rate = $this->updateParams['invest_rate'] = $params['investRate']; // 投资预期年化
        $this->invest_interest = $this->updateParams['invest_interest'] = intval($params['investInterest']); //每万元投资利息
        $this->loantype = $this->updateParams['loantype'] = isset($params['loantype']) ? intval($params['loantype']) : 0; //还款方式
        $this->min_amount = $this->updateParams['min_amount'] = intval($params['minAmount']); // 最低预约金额，单位分
        $this->max_amount = $this->updateParams['max_amount'] = isset($params['maxAmount']) ? intval($params['maxAmount']) : 0; // 最高预约金额，单位分
        $this->visiable_group_ids = $this->updateParams['visiable_group_ids'] = isset($params['visiableGroupIds']) ? $params['visiableGroupIds'] : ''; // 可见组配置
        $this->rate_factor = $this->updateParams['rate_factor'] = $params['rateFactor']; //年化收益折算系数
        $this->product_grade_conf = $this->updateParams['product_grade_conf'] = json_encode($params['productGradeConf'], JSON_UNESCAPED_UNICODE); //产品结构化配置
        $this->label_before = $this->updateParams['label_before'] = isset($params['labelBefore']) ? $params['labelBefore'] : ''; //前标签
        $this->label_after = $this->updateParams['label_after'] = isset($params['labelAfter']) ? $params['labelAfter'] : ''; //后标签
        $this->display_people = $this->updateParams['display_people'] = intval($params['displayPeople']); //是否启用今天预约人数(0:不启用1:启用)
        $this->display_money = $this->updateParams['display_money'] = intval($params['displayMoney']); //是否启用今天预约总金额(0:不启用1:启用)
        $this->description = $this->updateParams['description'] = $params['description']; //产品详情
        $this->extra_conf = $this->updateParams['extra_conf'] = isset($params['extraConf']) ? $params['extraConf'] : ''; //扩展配置
        return true;
    }

}

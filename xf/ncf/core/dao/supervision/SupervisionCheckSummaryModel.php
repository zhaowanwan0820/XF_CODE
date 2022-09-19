<?php
/**
 * 存管对账错误记录表
 * SupervisionCheckSummaryModel class file.
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/

namespace core\dao\supervision;
use core\dao\BaseModel;
use core\dao\supervision\SupervisionCheckErrorModel;

/**
 * SupervisionCheckSummaryModel class
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/
class SupervisionCheckSummaryModel extends BaseModel {

    const ORDER_TOTAL = 11; //订单总数
    const ORDER_SUC = 12; //订单对账成功
    const RETURN_TOTAL = 14; //回单总数
    const RETURN_SUC = 15; //回单对账成功

    //状态
    const STATUS_NORMAL = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
    const STATUS_PROCSSING = 3;

    //状态描述
    public static $statusDesc = [
        self::STATUS_NORMAL => '已创建',
        self::STATUS_SUCCESS => '已完成',
        self::STATUS_FAILED => '出错',
        self::STATUS_PROCSSING => '运行中',
    ];

    //汇总对象
    private $summary;

    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 清理错误记录
     * @param string $date 日期 yyyy-mm-dd
     */
    public function clearSummary($date) {
        if (empty($date)) {
            return false;
        }
        $sql = sprintf("DELETE FROM %s WHERE `date` = '%s'", $this->tableName(), $date);
        return $this->execute($sql);
    }

    /**
     * 获取汇总记录
     */
    public function getSummaryByDate($date) {
        $condition = sprintf("`date` = '%s'", addslashes($date));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 初始化汇总
     * @param string $date 日期 yyyy-mm-dd
     */
    public function initSummary($date) {
        $data = [
            'date'                  => $date,
            'status'                => self::STATUS_NORMAL,
        ];
        $this->setRow($data);

        if($this->insert()){
            $id = $this->db->insert_id();
            //set summary
            $id && $this->summary = $this->find($id);
            return $id;
        }else{
            return false;
        }
    }

    /**
     * 开始汇总
     */
    public function startSummary() {
        if (!$this->summary instanceof SupervisionCheckSummaryModel) {
            return false;
        }
        $condition = sprintf("`id` = '%d'", intval($this->summary->id));
        $params = [
            'status'        => self::STATUS_PROCSSING,
            'start_time'    => time(),
        ];
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }


    /**
     * 更新汇总
     * @param int $code 错误码
     * @param int $amount 错误金额
     */
    public function updateSummary($code, $amount) {
        if (!$this->summary instanceof SupervisionCheckSummaryModel) {
            return false;
        }
        switch ($code) {
            case self::ORDER_TOTAL:
                $this->summary->order_total += 1;
                $this->summary->order_total_amount += $amount;
                break;
            case self::ORDER_SUC:
                $this->summary->order_suc += 1;
                $this->summary->order_suc_amount += $amount;
                break;
            case self::RETURN_TOTAL:
                $this->summary->return_total += 1;
                $this->summary->return_total_amount += $amount;
                break;
            case self::RETURN_SUC:
                $this->summary->return_suc += 1;
                $this->summary->return_suc_amount += $amount;
                break;
            case SupervisionCheckErrorModel::ERR_PENDING:
                $this->summary->pend += 1;
                $this->summary->pend_amount += $amount;
                break;
            case SupervisionCheckErrorModel::ERR_CANCEL:
                $this->summary->cancel += 1;
                $this->summary->cancel_amount += $amount;
                break;
            case SupervisionCheckErrorModel::ERR_MONEY:
                $this->summary->err_money += 1;
                $this->summary->err_money_amount += $amount;
                break;
            case SupervisionCheckErrorModel::ERR_STATUS:
                $this->summary->err_status += 1;
                $this->summary->err_status_amount += $amount;
                break;
            case SupervisionCheckErrorModel::ERR_NO_TRADE:
                $this->summary->err_no_trade += 1;
                $this->summary->err_no_trade_amount += $amount;
                break;
            default:
                throw new \Exception(sprintf('error code %s in updateSummary', $code));
        }
    }

    /**
     * 结束汇总
     * @return boolean
     */
    public function endSummary() {
        if (!$this->summary instanceof SupervisionCheckSummaryModel) {
            return false;
        }
        $this->summary->end_time = time();
        $this->summary->status = self::STATUS_SUCCESS;
        return $this->summary->save();
    }


    /**
     * 失败
     * @return boolean
     */
    public function failSummary($comment = null) {
        if (!$this->summary instanceof SupervisionCheckSummaryModel) {
            return false;
        }
        $this->summary->end_time = time();
        $this->summary->status = self::STATUS_FAILED;
        $this->summary->comment = mb_substr($comment, 0, 200);
        return $this->summary->save();

    }

}


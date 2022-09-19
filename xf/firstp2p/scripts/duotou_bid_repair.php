<?php
/**
 * 仅执行一次 修复异常数据
 * 如果中端执行，会记录执行成功的dealRepayId和size数据；
 * 如果要从中断执行的位置继续执行，则执行脚本时输入两个参数。
 * 第一个参数是日志中最后执行的dealRepayId的值，第二个这是日志中最后执行的size(第二个一般不用输入)。
 *
 */

//error_reporting(E_ALL);ini_set('display_errors', 1);
ini_set('memory_limit', '2048M');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../app/init.php');

use libs\utils\Logger;
use core\dao\DealRepayModel;
use core\dao\DealLoanTypeModel;

class DuotouBidRepair
{
    public $dealRepayId = 0; //一次循环中排序最后的dealRepayId的值
    public $size = 100; //limit的参数
    public $db;

    public function __construct()
    {
        $this->db = $GLOBALS["db"]; // master上的数据库
    }

    public function run()
    {
        $typeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XFD);
        $dealRepayModel = new DealRepayModel();
        while (true) {
            //获取dealRepayId 已报备，消费贷，还款类型为0, 还款状态为0
            $sql =sprintf("SELECT r.id as id FROM firstp2p_deal_repay as r INNER JOIN firstp2p_deal as d ON r.deal_id = d.id WHERE d.report_status = 1 AND d.type_id = %d AND r.`status` = %d AND r.repay_type = %d AND r.id > %d ORDER BY r.id ASC LIMIT %d", $typeId, DealRepayModel::STATUS_WAITING, DealRepayModel::DEAL_REPAY_TYPE_SELF, $this->dealRepayId, $this->size);
            $repayResult = $this->db->getAll($sql);
            if (empty($repayResult)) {
                //全部结束，记录日志
                Logger::info(implode(" | ", array("代扣还款上线刷数据 全部完成 all success")));
                break;
            }

            //修改dealRepay的repayType为代垫
            if (!empty($repayResult)) {
                foreach ($repayResult as $v) {
                    $condition = sprintf("`id` = %d", $v['id']);
                    $row = $dealRepayModel->findBy($condition, 'id');
                    $params = array(
                        'repay_type' => DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN,
                    );
                    $result = $row->updateOne($params);
                  //记录成功的和失败的repayId，以便于恢复
                    if (!$result) {
                        Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "代扣还款上线刷数据 失败 deal_repay_id: " . $v['id'])));
                        continue;
                    }
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "代扣还款上线刷数据 成功 deal_repay_id: " . $v['id'])));
                }
            }
            usleep(10000); // 休息10ms
            $this->dealRepayId = end($repayResult)['id'];
        }
    }
}

$obj = new DuotouBidRepair();
$obj->dealRepayId = isset($argv[1]) ? $argv[1] : $obj->dealRepayId;
$obj->size = isset($argv[2]) ? $argv[2] : $obj->size;

$obj->run();

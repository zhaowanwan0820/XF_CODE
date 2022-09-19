<?php
/**
 * UserRiskAssessmentLogModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

/**
 * 用户风险评估操作日志
 *
 * @author weiwei12@ucfgroup.com
 */
class UserRiskAssessmentLogModel extends BaseModel
{
    /**
     * 连firstp2p_payment库
     * RiskAssessmentQuestionsModel constructor.
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 添加日志
     * @param $user_id
     * @param $ques_id
     * @param $level_name
     * @param $score
     * @return bool
     */
    public function addLog($user_id, $ques_id, $level_name, $assess_time, $score, $site_id = 1)
    {
        $data = array(
            'user_id'       => $user_id,
            'ques_id'       => $ques_id,
            'level_name'    => $level_name,
            'score'         => $score,
            'assess_time'   => $assess_time,
            'log_time'      => time(),
            'site_id'       => $site_id,
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 获取时间范围的日志
     * @param $user_id
     * @param $ques_id
     * @param $start_time
     * @param $end_time
     * @return bool|\libs\db\Model
     */
    public function getLogRange($user_id, $ques_id, $start_time, $end_time)
    {
        $condition = sprintf("`user_id` = '%d' and `ques_id` = '%d' and `assess_time` >= '%d' and `assess_time` <= '%d'", intval($user_id), intval($ques_id), intval($start_time), intval($end_time));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

}

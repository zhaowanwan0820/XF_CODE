<?php
/**
 * RiskAssessmentQuestionsModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

/**
 * 风险评估问卷
 *
 * @author weiwei12@ucfgroup.com
 */
class RiskAssessmentQuestionsModel extends BaseModel
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
     * 获取所有的问卷
     * @param int $type 0:风险评估  1:网信业务调查问卷
     * @return bool|\libs\db\Model
     */
    public function getAllQuestions($type = 0)
    {
        $condition = "`is_delete` = 0 and `type` = $type";
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取有效的问卷
     * @param string $fields
     * @return bool|\libs\db\Model
     */
    public function getEnabledQuestion($type = 0)
    {
        $condition = "`is_delete` = 0 and `status` = 1  and `type` = $type ";
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 通过ID获取问卷
     * @param $ques_id
     * @return bool|\libs\db\Model
     */
    public function getQuestionById($ques_id)
    {
        $condition = sprintf("`is_delete` = 0 and `id` = '%d'", intval($ques_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 添加问卷
     * @param $status
     * @param $prompt
     * @param $remark
     * @param $limit_type
     * @param $limit_times
     * @param $limit_period
     * @param $total_score
     * @param $enable_time
     * @param $disable_time
     * @param $type 0:风险评估 1:网信业务调查问卷
     * @return bool
     */
    public function addQuestion($status, $prompt, $remark, $limit_type,
        $limit_times, $limit_period, $total_score, $enable_time, $disable_time, $type = 0, $expireDays = 0)
    {
        $data = array(
            'status'        => $status,
            'prompt'        => $prompt,
            'remark'        => $remark,
            'limit_type'    => $limit_type,
            'total_score'   => $total_score,
            'expire_days'   => $expireDays,
            'enable_time'   => $enable_time,
            'disable_time'  => $disable_time,
            'is_delete'     => 0,
            'create_time'   => time(),
            'update_time'   => time(),
            'type'          => $type,
        );
        if ($limit_type == 1) {
            $data = array_merge($data, array(
                'limit_times'   => $limit_times,
                'limit_period'  => $limit_period,
            ));
        }
        $this->setRow($data);
        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 开启问卷
     * @param $ques_id
     * @return bool
     */
    public function enableQuestion($ques_id)
    {
        $condition = sprintf("`id` = '%d'", intval($ques_id));
        $params = array(
            'status'        => 1,
            'enable_time'   => time(),
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 禁用问卷
     * @param $ques_id
     * @return bool
     */
    public function disableQuestion($ques_id)
    {
        $condition = sprintf("`id` = '%d'", intval($ques_id));
        $params = array(
            'status'        => 0,
            'disable_time'  => time(),
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 更新问卷
     * @param $ques_id
     * @param $status
     * @param $prompt
     * @param $remark
     * @param $limit_type
     * @param $limit_times
     * @param $limit_period
     * @param $total_score
     * @param $enable_time
     * @param $disable_time
     * @return bool
     */
    public function updateQuestion($ques_id, $status, $prompt, $remark, $limit_type
        , $limit_times, $limit_period, $total_score, $enable_time, $disable_time, $expireDays)
    {
        $condition = sprintf("`id` = '%d'", intval($ques_id));
        $params = array(
            'status'        => $status,
            'prompt'        => $prompt,
            'remark'        => $remark,
            'limit_type'    => $limit_type,
            'limit_times'   => $limit_times,
            'limit_period'  => $limit_period,
            'expire_days'   => $expireDays,
            'total_score'   => $total_score,
            'update_time'   => time(),
            'enable_time'   => $enable_time,
            'disable_time'   => $disable_time,
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 删除问卷
     * @param $ques_id
     * @return bool
     */
    public function deleteQuestion($ques_id)
    {
        $condition = sprintf("`id` = '%d'", intval($ques_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }


}

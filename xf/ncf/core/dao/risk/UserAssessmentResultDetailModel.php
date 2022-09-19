<?php
/**
 * UserAssessmentResultDetailModel class file.
 * 问卷调查结果明细表
 * @author duxuefeng@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

class UserAssessmentResultDetailModel extends BaseModel
{
    /**
     * 连firstp2p_payment库
     * UserAssessmentResultDetailModel constructor.
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 添加用户某次问卷某个问题的选择详情
     * @param $result_id
     * @param $ques_id
     * @param $sub_id
     * @param $answer
     * @param $score
     * @return bool
     */
    public function addDetail($result_id, $ques_id, $sub_id, $answer, $score)
    {
        $data = array(
            'result_id'         => $result_id,
            'ques_id'           => $ques_id,
            'sub_id'            => $sub_id,
            'answer'            => $answer,
            'score'             => $score,
            'create_time'       => time(),
            'update_time'       => time(),
        );
        $this->setRow($data);
        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 更新用户某次问卷某个问题的选择详情
     * @param $detail_id
     * @param $result_id
     * @param $ques_id
     * @param $sub_id
     * @param $answer
     * @param $score
     * @return bool
     */
    public function updateDetail($detail_id,$result_id, $ques_id, $sub_id, $answer, $score)
    {
        $condition = sprintf(" `id` = '%d'", intval($detail_id));
        $data = array(
            'result_id'           => $result_id,
            'ques_id'           => $ques_id,
            'sub_id'            => $sub_id,
            'answer'            => $answer,
            'score'             => $score,
            'update_time'       => time(),
        );
        return $this->updateAll($data, $condition);
    }

    /**
     * 获取用户某个选项的详细评估信息
     * @param $detail_id
     * @return bool|\libs\db\Model
     */
    public function getDetail($detail_id)
    {
        $condition = sprintf(" `id` = '%d'  ", intval($detail_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取某个问卷调查结果的详细信息
     * @param $result_id
     * @return bool|\libs\db\Model
     */
    public function getDetails($result_id)
    {
        $condition = sprintf(" `result_id` = '%d'  ", intval($result_id));
        $ret = $this->findAll($condition,true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }
}

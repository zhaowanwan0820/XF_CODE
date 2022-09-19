<?php
/**
 * RiskAssessmentSubjectsModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

/**
 * 风险评估问题
 *
 * @author weiwei12@ucfgroup.com
 */
class RiskAssessmentSubjectsModel extends BaseModel
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
     * 获取问题
     * @param string $fields
     * @return bool|\libs\db\Model
     */
    public function getSubject($sub_id)
    {
        $condition = sprintf("`is_delete` = 0 and `id` = '%d'", intval($sub_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取问卷的所有问题
     * @param $ques_id
     * @param string $fields
     */
    public function getSubjectsByQuesId($ques_id)
    {
        $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d' order by sort_order asc", intval($ques_id));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 添加问题
     * @param $ques_id
     * @param $title
     * @param $sort_order
     * @param $choose_item_num
     * @param $total_score
     * @return bool
     */
    public function addSubject($ques_id, $title, $sort_order, $choose_item_num, $total_score)
    {
        $data = array(
            'ques_id'           => $ques_id,
            'title'             => $title,
            'sort_order'        => $sort_order,
            'choose_item_num'   => $choose_item_num,
            'total_score'       => $total_score,
            'is_delete'         => 0,
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
     * 更新问题
     * @param $sub_id
     * @param $ques_id
     * @param $title
     * @param $sort_order
     * @param $choose_item_num
     * @param $total_score
     * @return bool
     */
    public function updateSubject($sub_id, $ques_id, $title, $sort_order, $choose_item_num, $total_score)
    {
        $condition = sprintf("`is_delete` = 0 and `id` = '%d' and `ques_id` = '%d'", intval($sub_id), intval($ques_id));
        $params = array(
            'title'             => $title,
            'sort_order'        => $sort_order,
            'choose_item_num'   => $choose_item_num,
            'total_score'       => $total_score,
            'update_time'       => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 删除问题
     * @param $sub_id
     * @return bool
     */
    public function deleteSubjectById($sub_id)
    {
        $condition = sprintf("`id` = '%d'", intval($sub_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 删除问卷下的问题
     * @param $ques_id
     * @return bool
     */
    public function deleteSubjectByQuesId($ques_id)
    {
        $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d'", intval($ques_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateAll($params, $condition);
    }
}

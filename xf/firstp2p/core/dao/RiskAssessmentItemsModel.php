<?php
/**
 * RiskAssessmentSubjectsModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao;

/**
 * 风险评估问题选项
 *
 * @author weiwei12@ucfgroup.com
 */
class RiskAssessmentItemsModel extends BaseModel
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
     * 添加选项
     * @param $ques_id
     * @param $sub_id
     * @param $content
     * @param $sort_order
     * @param $score
     * @return bool
     */
    public function addItem($ques_id, $sub_id, $content, $sort_order, $score)
    {
        $data = array(
            'ques_id'       => $ques_id,
            'sub_id'        => $sub_id,
            'content'       => $content,
            'sort_order'    => $sort_order,
            'score'         => $score,
            'is_delete'     => 0,
            'create_time'   => time(),
            'update_time'   => time(),
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 更新选项
     * @param $item_id
     * @param $ques_id
     * @param $sub_id
     * @param $content
     * @param $sort_order
     * @param $score
     * @return bool
     */
    public function updateItem($item_id, $ques_id, $sub_id, $content, $sort_order, $score)
    {
        $condition = sprintf("`id` = '%d' and ques_id = '%d' and sub_id = '%d'", intval($item_id), intval($ques_id), intval($sub_id));
        $params = array(
            'content'       => $content,
            'sort_order'    => $sort_order,
            'score'         => $score,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 获取问题的选项
     * @param $ques_id
     * @return bool|\libs\db\Model
     */
    public function getItemsBySubId($ques_id, $sub_id)
    {
        $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d' and `sub_id` = '%d' order by sort_order asc", intval($ques_id), intval($sub_id));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取问卷的所有选项
     * @param $ques_id
     * @return bool|\libs\db\Model
     */
    public function getItemsByQuesId($ques_id)
    {
        $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d' order by sort_order asc", intval($ques_id));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 删除选项
     * @param $item_id
     * @return bool
     */
    public function deleteItemById($item_id)
    {
        $condition = sprintf("`id` = '%d'", intval($item_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 删除问题的选项
     * @param $sub_id
     * @return bool
     */
    public function deleteItemBySubId($sub_id)
    {
        $condition = sprintf("`sub_id` = '%d'" , intval($sub_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateAll($params, $condition);
    }

    /**
     * 删除问卷的选项
     * @param $ques_id
     * @return bool
     */
    public function deleteItemByQuesId($ques_id)
    {
        $condition = sprintf("`ques_id` = '%d'", intval($ques_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateAll($params, $condition);
    }
}

<?php
/**
 * RiskAssessmentLevelsModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao;

use core\dao\RiskAssessmentQuestionsModel;

/**
 * 风险评估等级
 *
 * @author weiwei12@ucfgroup.com
 */
class RiskAssessmentLevelsModel extends BaseModel
{

    //等级表
    private static $riskLevels;

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
     * 添加等级
     * @param $ques_id
     * @param $name
     * @param $lowest_score
     * @param $sort_order
     * @return bool
     */
    public function addLevel($ques_id, $name, $lowest_score, $sort_order, $limitMoney)
    {
        $data = array(
            'ques_id'       => $ques_id,
            'name'          => $name,
            'lowest_score'  => $lowest_score,
            'sort_order'    => $sort_order,
            'is_delete'     => 0,
            'create_time'   => time(),
            'update_time'   => time(),
            'limit_money'   => $limitMoney
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 更新等级
     * @param $level_id
     * @param $ques_id
     * @param $name
     * @param $lowest_score
     * @param $sort_order
     * @return bool
     */
    public function updateLevel($level_id, $ques_id, $name, $lowest_score, $sort_order, $limitMoney)
    {
        $condition = sprintf("`id` = '%d' and `ques_id` = '%d'", intval($level_id), intval($ques_id));
        $params = array(
            'name'          => $name,
            'lowest_score'  => $lowest_score,
            'sort_order'    => $sort_order,
            'update_time'   => time(),
            'limit_money'   => $limitMoney
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 获取问卷下所有的等级
     * @param $ques_id
     * @return bool|\libs\db\Model
     */
    public function getLevelsByQues($ques_id)
    {
        $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d' order by sort_order asc", intval($ques_id));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取有效的等级表，按照分数线倒序
     * @return mix
     */
    public function getEnabledLevels()
    {
        if (isset(self::$riskLevels)) {
            return self::$riskLevels;
        }
        $questionsModel = new RiskAssessmentQuestionsModel();
        $questionInfo = $questionsModel->getEnabledQuestion();
        $ret = array();
        if (!empty($questionInfo)) {
            $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d' order by lowest_score desc", intval($questionInfo['id']));
            $ret = $this->findAll($condition, true);
        }
        self::$riskLevels = $ret;
        return $ret;
    }

    /**
     * 删除等级
     * @param $item_id
     * @return bool
     */
    public function deleteLevelById($level_id)
    {
        $condition = sprintf("`id` = '%d'", intval($level_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 删除问卷的等级
     * @param $ques_id
     * @return bool
     */
    public function deleteLevelByQuesId($ques_id)
    {
        $condition = sprintf("`ques_id` = '%d'", intval($ques_id));
        $params = array(
            'is_delete'     => 1,
            'update_time'   => time(),
        );
        return $this->updateAll($params, $condition);
    }

    /**
     * 评级
     */
    public function assessLevel($ques_id, $score)
    {
        $condition = sprintf("`is_delete` = 0 and `ques_id` = '%d' and lowest_score <= '%.2f' order by lowest_score desc limit 1", intval($ques_id), floatval($score));
        $ret = $this->findBy($condition);//获取最大的一行
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

}

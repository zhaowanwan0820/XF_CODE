<?php
/**
 * UserRiskAssessmentModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

/**
 * 用户风险评估
 *
 * @author weiwei12@ucfgroup.com
 */
class UserRiskAssessmentModel extends BaseModel
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
     * 保存用户评估
     * @param $user_id
     * @param $ques_id
     * @param $score
     * @param $level_name
     * @return \libs\db\Ambigous
     */
    public function saveURA($user_id, $ques_id, $score, $level_name, $assess_time, $site_id = 1)
    {
        $ura = $this->getURA($user_id);
        if ($ura) {
            $siteIdArray = !empty($ura['site_id']) ? explode(',', $ura['site_id']) : [];
            if (!in_array($site_id, $siteIdArray)) {
                $siteIdArray[] = $site_id;
                sort($siteIdArray);
            }
            $site_id = implode(',', $siteIdArray);
            return $this->updateURA($user_id, $ques_id, $score, $level_name, $assess_time, $site_id);
        } else {
            return $this->addURA($user_id, $ques_id, $score, $level_name, $assess_time, $site_id);
        }
    }

    /**
     * 更新用户评估
     * @param $user_id
     * @param $ques_id
     * @param $score
     * @return \libs\db\Ambigous
     */
    public function updateURA($user_id, $ques_id, $score, $level_name, $assess_time, $site_id = 1)
    {
        $condition = sprintf("`is_delete` = 0 and `user_id` = '%d'", intval($user_id));
        $params = array(
            'last_ques_id'      => $ques_id,
            'last_score'        => $score,
            'last_level_name'   => $level_name,
            'last_assess_time'  => $assess_time,
            'update_time'       => time(),
            'site_id'           => addslashes($site_id),
        );
        return $this->updateAll($params, $condition);
    }

    /**
     * 添加用户评估
     * @param $user_id
     * @param $ques_id
     * @param $score
     * @return bool
     */
    public function addURA($user_id, $ques_id, $score, $level_name, $assess_time, $site_id = 1)
    {
        $data = array(
            'user_id'           => $user_id,
            'last_ques_id'      => $ques_id,
            'last_score'        => $score,
            'last_level_name'   => $level_name,
            'last_assess_time'  => $assess_time,
            'is_delete'         => 0,
            'create_time'       => time(),
            'update_time'       => time(),
            'site_id'           => addslashes($site_id),
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 获取用户评估信息
     * @param $user_id
     * @return bool|\libs\db\Model
     */
    public function getURA($user_id)
    {
        $condition = sprintf("`is_delete` = 0 and `user_id` = '%d'", intval($user_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取用户评估信息列表
     */
     public function getURAList($offset=0, $page_size=10, $begin_time=0, $end_time=0)
     {
        $condition = "`is_delete` = 0";
        if ($begin_time && $end_time) {
            $condition .= sprintf(" and last_assess_time >= '%d' and last_assess_time <= '%d'", intval($begin_time), intval($end_time));
        }
        $condition .= sprintf(" limit %d, %d", intval($offset), intval($page_size));
        $ret = $this->findAll($condition, true);
        if (empty($ret)) {
            return false;
        }
        return $ret;
     }

    /**
     * 计算单笔投资金额
     */
    public function getLimitMoney($levels, $ura) {
        $limitMoney = 0;
        if (empty($levels) || empty($ura)) {
            return $limitMoney;
        }
        //计算单笔投资金额
        foreach ($levels as $level) {
            if (bccomp($ura['last_score'], $level['lowest_score'], 2) >= 0) {
                $limitMoney = isset($level['limit_money']) ? bcmul($level['limit_money'], 10000, 2) : 0;
                break;
            }
        }
        return $limitMoney;
    }

}

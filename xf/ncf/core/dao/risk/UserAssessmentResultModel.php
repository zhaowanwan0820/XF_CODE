<?php
/**
 * UserAssessmentResultModel class file.
 *
 * 问卷调查结果总表
 * @author duxuefeng@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

class UserAssessmentResultModel extends BaseModel
{
    const GET_AWARD = 1; //领券
    const NOT_AWARD = 0; //没有领券

    /**
     * 连firstp2p_payment库
     * UserAssessmentResultModel constructor.
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 添加用户问卷结果
     * @param $is_login 支持不登录就做调查问卷。
     * @param $user_id 用户ID，如果is_login=0，则代表临时用户,user_id为0；如果is_login=1,则user_id则为对应的user_id
     * @param $ques_id
     * @param $score
     * @param $level_name
     * @param $ip
     * @return bool
     */
    public function addResult($is_login, $user_id, $ques_id, $score, $level_name, $ip, $mobile = "", $is_award = 0)
    {
        $data = array(
            'is_login'      => $is_login,
            'is_award'      => $is_award,
            'user_id'       => $user_id,
            'ques_id'       => $ques_id,
            'score'         => $score,
            'level_name'    => $level_name,
            'ip'            => $ip,
            'mobile'        => $mobile,
            'create_time'   => time(),
            'update_time'   => time(),
        );
        $this->setRow($data);
        if ($this->insert()) {
            return $this->db->insert_id();
        } else {
            return false;
        }
    }

    /**
     * 更新用户评估
     * @param $result_id
     * @param $is_login 支持不登录就做调查问卷。
     * @param $user_id 用户ID，如果is_login=0，则代表临时用户,user_id为0；如果is_login=1,则user_id则为对应的user_id
     * @param $ques_id
     * @param $score
     * @param $level_name
     * @param $ip
     * @return \libs\db\Ambigous
     */
    public function updateResult($result_id, $is_login, $user_id, $ques_id, $score, $level_name, $ip, $mobile = "", $is_award = 0)
    {
        $condition = sprintf(" `id` = '%d' ", intval($result_id));
        $params = array(
            'is_login'      => $is_login,
            'is_award'      => $is_award,
            'user_id'       => $user_id,
            'ques_id'       => $ques_id,
            'score'         => $score,
            'level_name'    => $level_name,
            'ip'            => $ip,
            'mobile'        => $mobile,
            'update_time'   => time(),
        );
        return $this->updateAll($params, $condition);
    }

    /**
     * 更新用户评估
     * @param $result_id
     * @param $mobile
     */
    public function updateResultMobile($result_id, $mobile)
    {
        $condition = sprintf(" `id` = '%d' ", intval($result_id));
        $params = array(
            'mobile' => $mobile,
            'update_time' => time(),
        );
        return $this->updateAll($params, $condition);
    }

    /**
     * 更新领取礼券状态
     * @param $result_id
     * @param $mobile
     */
    public function updateResultAward($result_id, $is_award)
    {
        $condition = sprintf(" `id` = '%d' ", intval($result_id));
        $params = array(
            'is_award'    => $is_award,
            'update_time' => time(),
        );
        return $this->updateAll($params, $condition);
    }


    /**
     * 获取用户的评估信息
     * @param $result_id
     * @return bool|\libs\db\Model
     */
    public function getResult($result_id)
    {
        $condition = sprintf("`id` = '%d' ", intval($result_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取用户领券的评估信息
     * @param $mobile
     * @return bool|\libs\db\Model
     */
    public function getResultByMobile($mobile)
    {
        $condition = sprintf("`is_login` = 0 and `is_award` = 1  and `mobile` = '%s' order by id asc limit 1", $this->escape($mobile));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }


    /**
     * 获取用户最新的评估信息
     * @param $user_id
     * @return bool|\libs\db\Model
     */
    public function getRecentResult($user_id)
    {
        $condition = sprintf("`is_login` = 1 and `user_id` = '%d' order by id desc limit 1", intval($user_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取用户最开始领券的评估信息
     * @param $user_id
     * @return bool|\libs\db\Model
     */
    public function getFirstResult($user_id)
    {
        $condition = sprintf("`is_login` = 1 and `is_award` = 1 and `user_id` = '%d' order by id asc limit 1", intval($user_id));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 判断指定mobile的结果的个数(未登录)
     * @param string $mobile
     * @return integer
     */
    public function countResultsByMobile($mobile)
    {
        $condition = sprintf("`is_login` = 0 and `is_award` = 1  and `mobile` = '%s' ", $this->escape($mobile));
        return $this->count($condition);
    }

    /**
     * 判断指定user_id的结果的个数(登录)
     * @param int $user_id
     * @return integer
     */
    public function countResultsByUserId($user_id)
    {
        $condition = sprintf("`is_login` = 1 and `is_award` = 1 and `user_id` = '%d' ", intval($user_id));
        return $this->count($condition);
    }

}

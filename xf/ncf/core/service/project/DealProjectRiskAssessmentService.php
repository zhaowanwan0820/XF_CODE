<?php
/**
 * 项目风险评估服务
 *
 * @date 2017-2-15
 * @author xiaoan
 */

namespace core\service\project;

use core\service\BaseService;
use core\dao\project\DealProjectModel;
use core\service\user\UserService;
use core\service\risk\RiskAssessmentService;
use libs\utils\Logger;

class DealProjectRiskAssessmentService extends BaseService{

    // 投资风险要素 从高到低 (name与个人评估一致)
    public static $riskAssessment = array(
        5 => array(
            'name' => '进取型', // 风险承受要求
            'name_describe' => '本期和预期收益有可能均会受到较大波动', // 风险说明
            'class_describe' => '高风险产品', // 分类说明
            'max' => 1.5, // 分值区间
            'min' => 1.5
        ),
        4 => array(
            'name' => '策略型',
            'name_describe' => '本期和预期收益有可能均会受到波动',
            'class_describe' => '较高风险产品',
            'max' => 2.5,
            'min' => 1.5,
        ),
        3 => array(
            'name' => '稳健型', // 风险承受要求
            'name_describe' => '本金稳定，预期收益有可能产生波动',
            'class_describe' => '高风险产品',
            'max' => 3.5,
            'min' => 2.5,
        ),
        2 => array(
            'name' => '保守型', // 风险承受要求
            'name_describe' => '本金稳定，预期收益相对稳定',
            'class_describe' => '中等风险产品',
            'max' => 4.5,
            'min' => 3.5,
        ),
        1 => array(
            'name' => '风险厌恶型',
            'name_describe' => '本金及预期收益均相对稳定',
            'class_describe' => '低风险产品',
            'max' => 5,
            'min' => 4.5,
        ),
    );
    // 是否开始投资检查项目风险承受能力要求
    public static $is_bid_check_enable = 0;

    // 随心约 是否检查检查项目风险承受能力要求
    public static $is_reserve_check_enable = 0;

    // 启用检查项目风险承受能力要求
    const CHECK_ENABLE = 1;
    // 关闭检查项目风险承受能力要求
    const CHECK_DISABLE = 0;

    public function __construct(){
        if (app_conf("DEAL_PROJECT_RISK_TURN_ON") == 1){
            self::$is_bid_check_enable = self::CHECK_ENABLE;
        }
        if (app_conf("RESERVE_PROJECT_RISK_TURN_ON") == 1){
            self::$is_reserve_check_enable = self::CHECK_ENABLE;
        }
    }
    /**
     * 根据评分获取风险承受要求
     * @param $score
     * @return string
     */
    public function getByScoreAssesment($score){

        if (empty($score) || $score == 0.0 || !is_numeric($score)){
            return false;
        }
        $ret = array('id' => 0,'name' => '');

        $min = 1;

        foreach(self::$riskAssessment as $key => $v){
            // 最高
            if ($score < self::$riskAssessment[5]['max'] ){
                $ret['id'] = $key;
                $ret['name'] = $v['name'];
                break;
            }

            // 最低
            if ($min == $key && ($score >= $v['min'] && $score <= $v['max'])){
                $ret['id'] = $key;
                $ret['name'] = $v['name'];
                break;
            }

            if ($score >= $v['min'] && $score < $v['max']){
                $ret['id'] = $key;
                $ret['name'] = $v['name'];
            }

        }

        return $ret;

    }

    /**
     * 根据唯一标识获取承受要求和说明
     * @param $id
     * @param int $is_admin 是否后台查询
     * @return string
     */
    public function getAssesmentNameById($id, $is_admin = 0){
        if (!isset(self::$riskAssessment[$id])){
            return false;
        }
        $ret = array('name' => '', 'describe' => '');
        if ((self::$is_bid_check_enable == self::CHECK_DISABLE) && $is_admin == 0){
            return $ret;
        }
        $ret['name'] = empty(self::$riskAssessment[$id]['name']) ? '' : self::$riskAssessment[$id]['name'];
        $ret['describe'] = empty(self::$riskAssessment[$id]['name_describe']) ? '': self::$riskAssessment[$id]['name_describe'];

        return $ret;
    }

    /**
     * 根据风险能力名称获取id
     * @param $name
     */
    public function getAssesmentIdByName($name){
        $ret = 0;
        foreach(self::$riskAssessment as $key => $v){
            if ($name == $v['name']){
                $ret = $key;
                break;
            }
        }

        return $ret;
    }

    /**
     * 根据项目id和用户id查询风险结果 判断是否可投标
     * @param int $project_id
     * @param int $user_id
     * @param bool $is_find_enterprise 是否查询是企业用户
     * @return array result 是否可以投，remaining_assess_num 剩余评估次数
     */
    public function checkRiskBid($project_id, $user_id,$is_find_enterprise = true, $user_risk_data = []){

        if (!is_numeric($project_id) || empty($user_id)){
            return false;
        }
        $ret = array('result' => true,'remaining_assess_num' => 0,'user_risk_assessment' => '');

        if (self::$is_bid_check_enable == self::CHECK_DISABLE){
            return $ret;
        }
        $deal_project_model = new DealProjectModel();
        $project_info = $deal_project_model->find($project_id,'risk_bearing',true);
        if (!empty($project_info['risk_bearing'])){
            $ret = $this->checkUserProjectRisk($user_id, $project_info['risk_bearing'],$is_find_enterprise, $user_risk_data);
        }

        return $ret;
    }

    /**
     *  获取项目评级和个人评估效验
     * @param $user_id
     * @param $project_level
     * @param bool $is_find_enterprise
     * @param array $user_risk_data 用户评估数据
     * @return array
     */
    public function checkUserProjectRisk(
        $user_id,
        $project_level,
        $is_find_enterprise = true,
        $user_risk_data = array(),
        $isEnterprise = null
    ) {
        if (empty($user_id)) {
            return false;
        }

        $log_info = array(
            __CLASS__,
            __FUNCTION__,
            $user_id,
            $project_level,
            $is_find_enterprise,
            $isEnterprise,
            json_encode($user_risk_data)
        );
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));

        $ret = array('result' => true, 'remaining_assess_num' => 0, 'user_risk_assessment' => '');
        if ($is_find_enterprise) {
            // 企业用户不做效验
            if ($isEnterprise === null) {
                $isEnterprise = UserService::isEnterprise($user_id);
                if ($isEnterprise) return $ret;
            }
        }

        if (empty($user_risk_data)) {
            // 个人评估和项目评估对比
            $user_risk_service = new RiskAssessmentService();
            $user_risk = $user_risk_service->getUserRiskAssessmentData($user_id);
        } else {
            $user_risk = $user_risk_data;
        }
        // 评估剩余次数
        $ret['remaining_assess_num'] = isset($user_risk['remaining_assess_num']) ? $user_risk['remaining_assess_num'] : 0;

        if (!empty($user_risk['last_level_name'])) {
            $ret['user_risk_assessment'] = $user_risk['last_level_name'];
            $user_risk_project_level = $this->getAssesmentIdByName($user_risk['last_level_name']);
            if ($user_risk_project_level < $project_level) {
                Logger::info(implode(" | ", array_merge($log_info, array('user level '.$user_risk_project_level.' project level '.$project_level))));
                $ret['result'] = false;
            }

        } else {
            Logger::info(implode(" | ", array_merge($log_info, array('user level is null'))));
            $ret['result'] = false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array('end'))));
        return $ret;

    }


    public function checkReservationRisk($user_id, $score, $is_find_enterprise = true,$user_risk_data = array()){
        if (empty($user_id)){
            return false;
        }
        $log_info = array(__CLASS__, __FUNCTION__,$user_id,$score, $is_find_enterprise, json_encode($user_risk_data));
        $ret = array('result' => true,'remaining_assess_num' => 0,'user_risk_assessment' => '');

        if (self::$is_reserve_check_enable == self::CHECK_DISABLE){
            Logger::info(implode(" | ", array_merge($log_info, array('check_enable is '.self::$is_reserve_check_enable))));
            return $ret;
        }

        $project_level = $this->getByScoreAssesment($score);
        if (empty($project_level['id'])){
            $project_level = 0;
        }else{
            $project_level = $project_level['id'];
        }

        return $this->checkUserProjectRisk($user_id,$project_level,$is_find_enterprise,$user_risk_data);

    }


}

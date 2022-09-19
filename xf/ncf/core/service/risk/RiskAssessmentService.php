<?php
/**
 * RiskAssessmentService.php
 *
 * @date 2016-06-08
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */

namespace core\service\risk;


use core\service\BaseService;
use core\dao\risk\RiskAssessmentQuestionsModel;
use core\dao\risk\RiskAssessmentSubjectsModel;
use core\dao\risk\RiskAssessmentItemsModel;
use core\dao\risk\RiskAssessmentLevelsModel;
use core\dao\risk\RiskAssessmentOperLogModel;
use core\dao\risk\UserRiskAssessmentModel;
use core\dao\risk\UserRiskAssessmentLogModel;
use core\dao\risk\UserAssessmentResultModel;
use core\dao\risk\UserAssessmentResultDetailModel;
use libs\utils\Logger;
use core\service\user\UserService;
use core\service\user\UserTrackService;
use core\service\AdvService;
use core\service\account\AccountService;


/**
 * 风险评估服务
 *
 * Class RiskAssessmentService
 * @package core\service
 */
class RiskAssessmentService extends BaseService{

    //操作类型
    const OPER_CREATE = 1;
    const OPER_DELETE = 2;
    const OPER_ENABLE = 3;
    const OPER_DISABLE = 4;
    const OPER_UPDATE = 5;

    //对象类型
    const OBJECT_QUESTION = 1;
    const OBJECT_SUBJECT = 2;
    const OBJECT_ITERM = 3;

    //限制类型
    const LIMIT_TYPE_UNLIMITED = 0; //无限制
    const LIMIT_TYPE_LIMITED = 1; //有限制

    //限制周期
    const LIMIT_PERIOD_YEARLY = 1; //每年
    const LIMIT_PERIOD_MONTHLY = 2; //每月
    const LIMIT_PERIOD_WEEKLY = 3; //每周
    const LIMIT_PERIOD_DAILY = 4; //每日

    const DEFAULT_ADV_ID = 'risk_default_answer';//预设答案

    //问卷类型
    const TYPE_ASSESSMENT = 0; //风险评估问卷类型
    const TYPE_QUESTION = 1; //调查问卷类型

    static private $limit_period_desc = array(
        self::LIMIT_PERIOD_YEARLY => '今年',
        self::LIMIT_PERIOD_MONTHLY => '本月',
        self::LIMIT_PERIOD_WEEKLY => '本周',
        self::LIMIT_PERIOD_DAILY => '今天',
    );

    /**
     * 新建问卷
     * @param $status
     * @param $limit_type
     * @param $limit_times
     * @param $limit_period
     * @param $total_score
     * @param $subjects
     * @param $levels
     * @param $adm_id
     * @param $type 0:风险评估 1:网信业务调查问卷
     */
    public function newQuestion($status, $prompt, $remark, $limit_type, $limit_times,
                                $limit_period, $total_score, $subjects, $levels, $adm_id, $type = 0, $expireDays = 0)
    {
        try {

            $questions_model = new RiskAssessmentQuestionsModel();
            $subjects_model = new RiskAssessmentSubjectsModel();
            $items_model = new RiskAssessmentItemsModel();
            $levels_model = new RiskAssessmentLevelsModel();
            $oper_log_model = new RiskAssessmentOperLogModel();

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            if ($status == 1) {
                //若当前问卷使用状态为“使用中”,其他问卷使用状态将被置为“停用”
                $enabled_question = $questions_model->getEnabledQuestion($type);
                if (!empty($enabled_question)) {
                    $disable_result = $questions_model->disableQuestion($enabled_question['id']);
                    if (!$disable_result) {
                        throw new \Exception("禁用其他问卷失败");
                    }
                    $status_log_id = $oper_log_model->addLog($adm_id, self::OPER_DISABLE, self::OBJECT_QUESTION, $enabled_question['id']);
                    if (!$status_log_id) {
                        throw new \Exception("添加禁用其他问卷日志失败");
                    }
                }
            }

            $enable_time = $disable_time = 0;
            if ($status == 1) {
                $enable_time = time();
            } else {
                $disable_time = time();
            }
            //添加问卷
            $ques_id = $questions_model->addQuestion($status, $prompt, $remark, $limit_type,
                $limit_times, $limit_period, $total_score, $enable_time, $disable_time, $type, $expireDays);
            if (!$ques_id) {
                throw new \Exception("添加问卷失败");
            }

            //添加问题
            $sub_sort_order = 0;
            foreach ($subjects as $subject) {
                $sub_id = $subjects_model->addSubject($ques_id, $subject['title'], $sub_sort_order,
                    $subject['choose_item_num'], $subject['total_score']);
                if (!$sub_id) {
                    throw new \Exception("添加问题失败");
                }
                //添加选项
                $item_sort_order = 0;
                foreach ($subject['items'] as $item) {
                    $item_id = $items_model->addItem($ques_id, $sub_id, $item['content'],
                        $item_sort_order, $item['score']);
                    if (!$item_id) {
                        throw new \Exception("添加问题选项失败");
                    }
                    $item_sort_order++;
                }
                $sub_sort_order++;
            }

            //添加等级
            $level_sort_order = 0;
            foreach ($levels as $level) {
                $level_id = $levels_model->addLevel($ques_id, $level['name'], $level['lowest_score'], $level_sort_order, $level['limit_money'], $level['total_limit_money']);
                if (!$level_id) {
                    throw new \Exception("添加分类失败");
                }
                $level_sort_order++;
            }

            //记录操作日志
            $create_log_id = $oper_log_model->addLog($adm_id, self::OPER_CREATE, self::OBJECT_QUESTION, $ques_id);
            if (!$create_log_id) {
                throw new \Exception("添加创建问卷日志失败");
            }
            $oper_type = $status ? self::OPER_ENABLE : self::OPER_DISABLE;
            $enable_log_id = $oper_log_model->addLog($adm_id, $oper_type, self::OBJECT_QUESTION, $ques_id);
            if (!$enable_log_id) {
                throw new \Exception("添加问卷状态日志失败");
            }

            $db->commit();
            return $ques_id;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }

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
     * @param $subjects
     * @param $levels
     * @param $adm_id
     * @param $type 0:风险评估 1:网信业务调查问卷
     */
    public function updateQuestion($ques_id, $status, $prompt, $remark, $limit_type, $limit_times,
                                   $limit_period, $total_score, $subjects, $levels, $adm_id, $type = 0, $expireDays = 0)
    {
        try {
            $questions_model = new RiskAssessmentQuestionsModel();
            $subjects_model = new RiskAssessmentSubjectsModel();
            $items_model = new RiskAssessmentItemsModel();
            $levels_model = new RiskAssessmentLevelsModel();
            $oper_log_model = new RiskAssessmentOperLogModel();

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $question_info = $questions_model->getQuestionById($ques_id);
            if (empty($question_info)) {
                throw new \Exception("问卷不存在");
            }


            if ($question_info['status'] != $status && $status == 1) {
                //若当前问卷使用状态为“使用中”,其他问卷使用状态将被置为“停用”
                $enabled_question = $questions_model->getEnabledQuestion($type);
                if (!empty($enabled_question)) {
                    $disable_result = $questions_model->disableQuestion($enabled_question['id']);
                    if (!$disable_result) {
                        throw new \Exception("禁用其他问卷失败");
                    }
                    $status_log_id = $oper_log_model->addLog($adm_id, self::OPER_DISABLE, self::OBJECT_QUESTION, $enabled_question['id']);
                    if (!$status_log_id) {
                        throw new \Exception("添加禁用其他问卷日志失败");
                    }
                }
            }


            $enable_time = $question_info['enable_time'];
            $disable_time = $question_info['disable_time'];
            if ($question_info['status'] != $status) {
                if ($status == 1) {
                    $enable_time = time();
                } else {
                    $disable_time = time();
                }
            }
            //更新问卷
            $update_question = $questions_model->updateQuestion($ques_id, $status, $prompt, $remark,
                $limit_type, $limit_times, $limit_period, $total_score, $enable_time, $disable_time, $expireDays);
            if (!$update_question) {
                throw new \Exception("更新问卷失败");
            }

            //记录操作日志
            $update_log_id = $oper_log_model->addLog($adm_id, self::OPER_UPDATE, self::OBJECT_QUESTION, $ques_id);
            if (!$update_log_id) {
                throw new \Exception("添加更新日志失败");
            }
            if ($question_info['status'] != $status) {
                $oper_type = $status ? self::OPER_ENABLE : self::OPER_DISABLE;
                $status_log_id = $oper_log_model->addLog($adm_id, $oper_type, self::OBJECT_QUESTION, $ques_id);
                if (!$status_log_id) {
                    throw new \Exception("添加问卷状态日志失败");
                }
            }

            //更新问题
            $sub_sort_order = 0;
            foreach ($subjects as $subject) {
                if (empty($subject['id'])) {
                    $save_subject = $subjects_model->addSubject($ques_id, $subject['title'], $sub_sort_order,
                        $subject['choose_item_num'], $subject['total_score']);
                    $subject['id'] = $save_subject; //填写问题ID,后面创建选项使用
                } else {
                    $save_subject = $subjects_model->updateSubject($subject['id'], $ques_id, $subject['title'], $sub_sort_order,
                        $subject['choose_item_num'], $subject['total_score']);
                }
                if (!$save_subject) {
                    throw new \Exception("更新问题失败");
                }

                //添加选项
                $item_sort_order = 0;
                foreach ($subject['items'] as $item) {
                    if (empty($item['id'])) {
                        $save_item = $items_model->addItem($ques_id, $subject['id'], $item['content'],
                            $item_sort_order, $item['score']);
                    } else {
                        $save_item = $items_model->updateItem($item['id'], $ques_id, $subject['id'], $item['content'], $item_sort_order, $item['score']);
                    }
                    if (!$save_item) {
                        throw new \Exception("更新选项失败");
                    }
                    $item_sort_order++;
                }
                $sub_sort_order++;
            }

            //添加等级
            $level_sort_order = 0;
            foreach ($levels as $level) {
                if (empty($level['id'])) {
                    $save_level = $levels_model->addLevel($ques_id, $level['name'], $level['lowest_score'], $level_sort_order, $level['limit_money'], $level['total_limit_money']);
                } else {
                    $save_level = $levels_model->updateLevel($level['id'], $ques_id, $level['name'], $level['lowest_score'], $level_sort_order, $level['limit_money'], $level['total_limit_money']);
                }
                if (!$save_level) {
                    throw new \Exception("更新分类失败");
                }
                $level_sort_order++;
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 删除问卷
     * @param $ques_id
     */
    public function deleteQuestion($ques_id, $adm_id)
    {
        try {

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $questions_model = new RiskAssessmentQuestionsModel();
            $subjects_model = new RiskAssessmentSubjectsModel();
            $items_model = new RiskAssessmentItemsModel();
            $levels_model = new RiskAssessmentLevelsModel();
            $oper_log_model = new RiskAssessmentOperLogModel();

            $delete_question = $questions_model->deleteQuestion($ques_id);
            if (!$delete_question) {
                throw new \Exception("删除问卷失败");
            }
            $delete_subject = $subjects_model->deleteSubjectByQuesId($ques_id);
            if (!$delete_subject) {
                throw new \Exception("删除问卷的问题失败");
            }
            $delete_item = $items_model->deleteItemByQuesId($ques_id);
            if (!$delete_item) {
                throw new \Exception("删除问卷的选项失败");
            }
            $delete_level = $levels_model->deleteLevelByQuesId($ques_id);
            if (!$delete_level) {
                throw new \Exception("删除问卷的等级失败");
            }

            $del_log_id = $oper_log_model->addLog($adm_id, self::OPER_DELETE, self::OBJECT_QUESTION, $ques_id);
            if (!$del_log_id) {
                throw new \Exception("添加删除问卷日志失败");
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 开启问卷
     * @param $ques_id
     */
    public function enableQuestion($ques_id, $adm_id, $type = 0)
    {
        try {

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $questions_model = new RiskAssessmentQuestionsModel();
            $oper_log_model = new RiskAssessmentOperLogModel();
            //唯一启用逻辑:若当前问卷使用状态为“使用中”,其他问卷使用状态将被置为“停用”
            $enabled_question = $questions_model->getEnabledQuestion($type);
            if (!empty($enabled_question)) {
                $disable_result = $questions_model->disableQuestion($enabled_question['id']);
                if (!$disable_result) {
                    throw new \Exception("禁用其他问卷失败");
                }
                $status_log_id = $oper_log_model->addLog($adm_id, self::OPER_DISABLE, self::OBJECT_QUESTION, $enabled_question['id']);
                if (!$status_log_id) {
                    throw new \Exception("添加禁用其他问卷日志失败");
                }
            }

            $delete_question = $questions_model->enableQuestion($ques_id);
            if (!$delete_question) {
                throw new \Exception("开启问卷失败");
            }

            $del_log_id = $oper_log_model->addLog($adm_id, self::OPER_ENABLE, self::OBJECT_QUESTION, $ques_id);
            if (!$del_log_id) {
                throw new \Exception("添加开启问卷日志失败");
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 关闭问卷
     * @param $ques_id
     */
    public function disableQuestion($ques_id, $adm_id)
    {
        try {

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $questions_model = new RiskAssessmentQuestionsModel();
            $oper_log_model = new RiskAssessmentOperLogModel();

            $delete_question = $questions_model->disableQuestion($ques_id);
            if (!$delete_question) {
                throw new \Exception("关闭问卷失败");
            }

            $del_log_id = $oper_log_model->addLog($adm_id, self::OPER_DISABLE, self::OBJECT_QUESTION, $ques_id);
            if (!$del_log_id) {
                throw new \Exception("添加关闭问卷日志失败");
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 通过问卷id获取问卷信息
     * @param $ques_id
     */
    public function getQuestionById($ques_id)
    {
        $questions_model = new RiskAssessmentQuestionsModel();
        $subjects_model = new RiskAssessmentSubjectsModel();
        $items_model = new RiskAssessmentItemsModel();
        $levels_model = new RiskAssessmentLevelsModel();

        $question_info = $questions_model->getQuestionById($ques_id);
        $result = array();
        if (!empty($question_info)) {
            $result['id'] = $question_info['id'];
            $result['status'] = $question_info['status'];
            $result['enable_time'] = $question_info['enable_time'];
            $result['prompt'] = $question_info['prompt'];
            $result['remark'] = $question_info['remark'];
            $result['limit_type'] = $question_info['limit_type'];
            $result['limit_times'] = $question_info['limit_times'];
            $result['limit_period'] = $question_info['limit_period'];
            $result['expire_days'] = $question_info['expire_days'];
            $result['total_score'] = $question_info['total_score'];
            $result['total_score'] = $question_info['total_score'];
            $result['is_delete'] = $question_info['is_delete'];
            $result['create_time'] = $question_info['create_time'];
            $result['update_time'] = $question_info['update_time'];

            $subjects = $subjects_model->getSubjectsByQuesId($ques_id);

            /*效率较低
             * foreach ($subjects as $key => $subject) {
                 $items = $items_model->getItemsBySubId($ques_id, $subject['id']);
                 $subjects[$key]['items'] = $items;
        }
        $result['subjects'] = $subjects;*/
            $items = $items_model->getItemsByQuesId($ques_id);
            foreach ($subjects as $key => $subject) {
                $items_arr = array();
                foreach ($items as $item) {
                    if ($subject['id'] == $item['sub_id']) {
                        $item['alphabet'] = $this->toAlpha(count($items_arr));
                        $item['score'] = round($item['score'], 2);
                        $items_arr[] = $item;
                    }
                }
                $subjects[$key]['no'] = $key + 1;
                $subjects[$key]['items'] = $items_arr;
            }
            $result['subjects'] = $subjects;

            $levels = $levels_model->getLevelsByQues($ques_id);
            $result['levels'] = $levels;
        }
        return $result;
    }

    /**
     * 获取使用中的问题
     * @return array
     */
    public function getQuestion($type = 0)
    {
        $questions_model = new RiskAssessmentQuestionsModel();
        $subjects_model = new RiskAssessmentSubjectsModel();
        $items_model = new RiskAssessmentItemsModel();
        $levels_model = new RiskAssessmentLevelsModel();

        $question_info = $questions_model->getEnabledQuestion($type);
        $result = array();
        if (!empty($question_info)) {
            $result['id'] = $question_info['id'];
            $result['status'] = $question_info['status'];
            $result['enable_time'] = $question_info['enable_time'];
            $result['prompt'] = $question_info['prompt'];
            $result['remark'] = $question_info['remark'];
            $result['limit_type'] = $question_info['limit_type'];
            $result['limit_times'] = $question_info['limit_times'];
            $result['limit_period'] = $question_info['limit_period'];
            $result['total_score'] = $question_info['total_score'];
            $result['total_score'] = $question_info['total_score'];
            $result['is_delete'] = $question_info['is_delete'];
            $result['create_time'] = $question_info['create_time'];
            $result['update_time'] = $question_info['update_time'];

            $subjects = $subjects_model->getSubjectsByQuesId($question_info['id']);

            /*效率较低
             * foreach ($subjects as $key => $subject) {
                 $items = $items_model->getItemsBySubId($question_info['id'], $subject['id']);
                 $subjects[$key]['items'] = $items;
        }*/

            $items = $items_model->getItemsByQuesId($question_info['id']);
            foreach ($subjects as $key => $subject) {
                $items_arr = array();
                foreach ($items as $item) {
                    if ($subject['id'] == $item['sub_id']) {
                        $item['alphabet'] = $this->toAlpha(count($items_arr));
                        $item['score'] = round($item['score'], 2);
                        $items_arr[] = $item;
                    }
                }
                $subjects[$key]['no'] = $key + 1;
                $subjects[$key]['items'] = $items_arr;
            }

            $result['subjects'] = $subjects;
            $levels = $levels_model->getLevelsByQues($question_info['id']);
            $result['levels'] = $levels;
            $advService = new AdvService();
            $result['defaultAnswer'] = $advService->getAdv(self::DEFAULT_ADV_ID, 'default');
        }
        return $result;
    }

    /**
     * 删除选项
     * @param $item_id
     * @return bool
     */
    public function deleteItemById($item_id)
    {
        try {
            $items_model = new RiskAssessmentItemsModel();

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $deleteItem = $items_model->deleteItemById($item_id);
            if (!$deleteItem) {
                throw new \Exception("删除选项失败");
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 删除问题
     * @param $sub_id
     * @return bool
     */
    public function deleteSubjectById($sub_id)
    {
        try {
            $subjects_model = new RiskAssessmentSubjectsModel();
            $items_model = new RiskAssessmentItemsModel();

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $deleteSubject = $subjects_model->deleteSubjectById($sub_id);
            if (!$deleteSubject) {
                throw new \Exception("删除问题失败");
            }
            $deleteItem = $items_model->deleteItemBySubId($sub_id);
            if (!$deleteItem) {
                throw new \Exception("删除选项失败");
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 删除分类
     * @param $item_id
     * @return bool
     */
    public function deleteLevelById($item_id)
    {
        try {
            $levels_model = new RiskAssessmentLevelsModel();

            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $deleteLevel = $levels_model->deleteLevelById($item_id);
            if (!$deleteLevel) {
                throw new \Exception("删除选项失败");
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 生成对应的字母
     * @param $number
     * @return mixed|null
     */
    private function toAlpha($number)
    {
        $alphabets = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        return isset($alphabets[$number]) ? $alphabets[$number] : null;
    }

    /**
     * 获取用户评估数据
     * @param $user_id
     * @return array|bool
     */
    public function getUserRiskAssessmentData($user_id, $money = 0, $siteId = 0, $isEnterprise = null) {
        try {
            $data = array();

            //最新的评估数据
            $ura_model = new UserRiskAssessmentModel();
            $ura_info = $ura_model->getURA($user_id);
            $data['user_id'] = $user_id;
            $data['last_ques_id'] = isset($ura_info['last_ques_id']) ? $ura_info['last_ques_id'] : null;
            $data['last_score'] = isset($ura_info['last_score']) ? round($ura_info['last_score'], 2) : null;
            $data['last_assess_time'] = isset($ura_info['last_assess_time']) ? $ura_info['last_assess_time'] : null;
            $data['last_level_name'] = isset($ura_info['last_level_name']) ? $ura_info['last_level_name'] : null;
            $data['site_id'] = isset($ura_info['site_id']) ? explode(',', $ura_info['site_id']) : []; //评估分站

            //问卷数据
            $questions_model = new RiskAssessmentQuestionsModel();
            $question_info = $questions_model->getEnabledQuestion();
            $data['ques'] = array();

            $data['ques']['expire_days'] = empty($question_info['expire_days']) ? '360' : $question_info['expire_days'];
            //1在有效期内
            $data['isRiskValid'] = bccomp($data['ques']['expire_days']*86400 , bcsub(time(),$data['last_assess_time'])) >= 0 ? 1 : 0;

            if (!empty($question_info)) {
                $time_range = $this->getTimeRange($question_info['limit_period']);
                if (empty($time_range)) {
                    throw new \Exception("获取时间范围失败");
                }
                $data['ques']['id'] = $question_info['id'];
                $data['ques']['limit_type'] = $question_info['limit_type'];
                $data['ques']['prompt'] = $question_info['prompt'];
                $data['ques']['remark'] = $question_info['remark'];
                $log_model = new UserRiskAssessmentLogModel();
                $logs = $log_model->getLogRange($user_id, $question_info['id'], $time_range['start_time'], $time_range['end_time']);
                $data['assess_num'] = empty($logs) ? 0 : count($logs); //已评估数
                //如果超过有效期 已评估数-1
                $data['assess_num'] = ($data['isRiskValid'] == 0 && $data['assess_num']>=1) ? ($data['assess_num']-1) : $data['assess_num'];
                if ($question_info['limit_type'] == 1) {
                    $data['remaining_assess_num'] = max($question_info['limit_times'] - $data['assess_num'], 0);//剩余的评估数
                    //如果超过有效期剩余评估数加1
                    $data['remaining_assess_num'] = ($data['isRiskValid'] == 0 && $data['remaining_assess_num']==0) ? ($data['remaining_assess_num']+1) : $data['remaining_assess_num'];
                    $data['ques']['limit_period'] = $question_info['limit_period'];
                    $data['ques']['limit_period_desc'] = self::$limit_period_desc[$question_info['limit_period']];
                    $data['ques']['limit_times'] = $question_info['limit_times'];
                }
            }
            //有效期截止时间
            $data['riskValid'] = bcadd($data['last_assess_time'],$data['ques']['expire_days']*86400);
            //判断是否是企业用户
            if ($isEnterprise === null) {
                $isEnterprise = UserService::isEnterprise($user_id);
            }

            //单笔出借限额
            $data['limitMoneyData'] = array();
            $data['isLimitInvest'] = 0;
            //总出借限额
            $data['totalLimitMoneyData'] = [];
            $data['isTotalLimitInvest'] = 0;
            if (isset($ura_info['last_score']) && !empty($question_info) && !$isEnterprise) {
                //单笔出借限额
                $level_model = new RiskAssessmentLevelsModel();
                $level_info = $level_model->assessLevel($data['ques']['id'], $ura_info['last_score']);
                if (!empty($level_info) && isset($level_info['limit_money'])) {
                    $limitMoney = intval(bcmul($level_info['limit_money'], 10000));
                    if (!empty($limitMoney)) {
                        $data['limitMoneyData'] = array('limitMoney' => $limitMoney, 'levelName' => $data['last_level_name']);
                        isset($data['remaining_assess_num']) ? $data['limitMoneyData']['remainingAssessNum'] = $data['remaining_assess_num'] : '';
                    }
                }
                if (!empty($money)) {
                    $data['isLimitInvest'] = (intval($limitMoney) > 0 && $limitMoney < $money) ? 1 : 0;
                }

                //总出借限额
                if (!empty($level_info) && isset($level_info['total_limit_money'])) {
                    $totalLimitMoney = intval(bcmul($level_info['total_limit_money'], 10000));
                    $userSummary = AccountService::getUserSummary($user_id);
                    $accountMoney = AccountService::getAccountMoneyById($user_id);
                    $investMoney = bcadd($userSummary['corpus'], $userSummary['dt_load_money'], 2); //出借人已出借金额：出借人所出借的所有P2P处于还款中的在途标的；处于持有中，转让退出中的智多鑫产品；
                    $investMoney = bcadd($investMoney, $accountMoney['lockMoney'], 2); //这里暂时加冻结金额，后期优化 @todo
                    $data['totalLimitMoneyData'] = [
                        'levelName' => $data['last_level_name'],
                        'totalLimitMoney' => $totalLimitMoney,
                        'totalLimitMoneyFormat' => floatval($level_info['total_limit_money']) . '万元',
                        'investMoney' => $investMoney,
                    ];
                    isset($data['remaining_assess_num']) ? $data['totalLimitMoneyData']['remainingAssessNum'] = $data['remaining_assess_num'] : '';

                    $needLimitMoney = bcsub($totalLimitMoney, $investMoney, 2); //剩余投资限额
                    $data['isTotalLimitInvest'] = bccomp($money, $needLimitMoney, 2) === 1 ? 1 : 0; //是否限制
                }
            }

            //强制风险评级
            $data['needForceAssess'] = 0;
            if (app_conf('NEED_FORCE_ACCESS') == 1 && !empty($question_info) && !$isEnterprise ) {
                //没有做风险评级
                if (empty($ura_info)) {
                    $data['needForceAssess'] = 1;
                }

                //用户从普惠登录，但是没在普惠做风险评级，需做评级
                $siteId = !empty($siteId) ? $siteId : \libs\utils\Site::getId(); //访问站点
                $userTrackService = new UserTrackService();
                $loginSiteId = $userTrackService->getLoginSite($user_id); //登录站点
                if ($loginSiteId == $siteId && $siteId == $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn'] && !in_array($loginSiteId, $data['site_id'])) {
                    $data['needForceAssess'] = 1;
                }
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, json_encode($data))));
            return $data;
        } catch (\Exception $e) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, json_encode($data),'error : '.$e->getMessage())));
            return false;
        }
    }

    /**
     * 时间范围
     * @param $limit_period
     * @return array|null
     */
    private function getTimeRange($limit_period)
    {
        switch ($limit_period) {
            case self::LIMIT_PERIOD_YEARLY:
                return array(
                    'start_time' => mktime(0, 0, 0, 1, 1, date('Y')),
                    'end_time' => time(),
                );
            case self::LIMIT_PERIOD_MONTHLY:
                return array(
                    'start_time' => mktime(0, 0, 0, date('n'), 1, date('Y')),
                    'end_time' => time(),
                );
            case self::LIMIT_PERIOD_WEEKLY:
                return array(
                    'start_time' => mktime(0, 0, 0, date('n'), date('j') - date('N') + 1, date('Y')),
                    'end_time' => time(),
                );
            case self::LIMIT_PERIOD_DAILY:
                return array(
                    'start_time' => mktime(0, 0, 0, date('n'), date('j'), date('Y')),
                    'end_time' => time(),
                );
                break;
        }
        return null;
    }

    /**
     * 评估
     * @param $ques_id
     * @param $item_scores
     * @param $user_id
     */
    public function assess($user_id, $ques_id, $score, $site_id = 0)
    {
        try {
            $ura_model = new UserRiskAssessmentModel();
            $level_model = new RiskAssessmentLevelsModel();
            $log_model = new UserRiskAssessmentLogModel();
            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();

            $level_info = $level_model->assessLevel($ques_id, $score);
            if (empty($level_info)) {
                throw new \Exception("评级失败");
            }
            $site_id = !empty($site_id) ? $site_id : \libs\utils\Site::getId();
            $assess_time = time();
            $save_ura = $ura_model->saveURA($user_id, $ques_id, $score, $level_info['name'], $assess_time, $site_id);
            if (!$save_ura) {
                throw new \Exception("保存用户评估信息失败");
            }
            $add_log = $log_model->addLog($user_id, $ques_id, $level_info['name'], $assess_time, $score, $site_id);
            if (!$add_log) {
                throw new \Exception("添加用户评估日志失败");
            }

            $db->commit();

            $assess_result['name'] = $level_info['name'];
            $assess_result['assess_time'] = $assess_time;
            $assess_result['assess_date'] = date('Y-m-d', $assess_time);
            return $assess_result;
        } catch (\Exception $e) {
            Logger::error('ASSESS ERROR:' . $e->getMessage());
            $db->rollback();
            return false;
        }
    }

    /**
     * 保存问卷调查结果
     * @param $ques_id
     * @param $item_scores
     * @param $user_id
     * @param $subjects
     * @example
     * $subjects =  array(
     *      "12",
     *      "2",
     *      "2",
     *      "2",
     *     );
     */
    public function saveQuestionnaireResult($is_login, $user_id, $ques_id, $ip, $subjects, $mobile = "")
    {
        try {
            $uar_model = new UserAssessmentResultModel();
            $uard_model = new UserAssessmentResultDetailModel();
            $level_model = new RiskAssessmentLevelsModel();
            $rai_model = new RiskAssessmentItemsModel();
            $ras_model = new RiskAssessmentSubjectsModel();
            $db = \libs\db\Db::getInstance('firstp2p_payment');
            $db->startTrans();
            //获取sub_id
            $allSubjects = $ras_model->getSubjectsByQuesId($ques_id);
            $items = $rai_model->getItemsByQuesId($ques_id);
            $details = array();
            //获取detail的sub_id和answer
            for ($i = 0; $i < count($subjects); $i++) {
                foreach ($allSubjects as $k => $v) {
                    if (strval($i) == $v['sort_order']) {
                        $details[] = array(
                            'sub_id' => $v['id'],
                            'answer' => $subjects[$i],
                        );
                    }
                }
            }
            //获取detail的score
            foreach ($details as $key => $value) {
                //针对于多选情况
                if (strlen($value['answer']) > 1) {
                    $multiSelects = str_split($value['answer']);
                    $score = floatval("0");
                    foreach ($multiSelects as $select) {
                        foreach ($items as $k => $v) {
                            if ($value['sub_id'] == $v['sub_id'] && $select == $v['sort_order']) {
                                $score += floatval($v['score']);
                            }
                        }

                    }
                    $details[$key]['score'] = $score;
                } else {
                    foreach ($items as $k => $v) {
                        if ($value['sub_id'] == $v['sub_id'] && $value['answer'] == $v['sort_order']) {
                            $details[$key]['score'] = floatval($v['score']);
                        }
                    }
                }
            }
            //获取总分
            $score = floatval("0");
            foreach ($details as $v) {
                $score += $v['score'];
            }
            //根据分数获取评级
            $level_info = $level_model->assessLevel($ques_id, $score);
            if (empty($level_info)) {
                throw new \Exception("评级失败");
            }

            $result_id = $uar_model->addResult($is_login, $user_id, $ques_id, $score, $level_info['name'], $ip, $mobile);
            if (!$result_id) {
                throw new \Exception("问卷评估结果保存失败");
            }
            //添加结果详情
            $score = 0; // 总分
            foreach ($details as $detail) {
                $detail_id = $uard_model->addDetail($result_id, $ques_id, $detail['sub_id'], $detail['answer'], $detail['score']);
                if (!$detail_id) {
                    throw new \Exception("问卷评估结果详情保存失败");
                }
                $score = $score + intval($detail['score']);
            }
            $db->commit();

            $assess_result['result_id'] = $result_id;
            $assess_result['name'] = $level_info['name'];
            $assess_result['sort_order'] = $level_info['sort_order'];
            $assess_result['score'] = $score;
            return $assess_result;

        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $is_login, $user_id, $ques_id, $score, $level_name, $ip, $e->getMessage())));
            $db->rollback();
            return false;
        }
    }

    /**
     * 更新用户mobile
     * */
    public function updateAssessmentResultMobile($result_id, $mobile)
    {
        $ura_model = new UserAssessmentResultModel();
        return $ura_model->updateResultMobile($result_id, $mobile);
    }

    /**
     * 更新用户is_award 领券状态
     * */
    public function updateAssessmentResultAward($result_id, $is_award)
    {
        $ura_model = new UserAssessmentResultModel();
        return $ura_model->updateResultAward($result_id, $is_award);
    }

    /**
     * 获取指定type的有效问卷信息
     * 传输的数据较少
     * @param type 问卷类型
     */
    public function getEnabledQuestion($type = 0)
    {
        $questions_model = new RiskAssessmentQuestionsModel();
        return $questions_model->getEnabledQuestion($type);
    }

    /**
     * 判断指定mobile的结果的个数(未登录)
     * @param string $mobile
     * @return integer
     */
    public function countResultsByMobile($mobile)
    {
        $ura_model = new UserAssessmentResultModel();
        return $ura_model->countResultsByMobile($mobile);
    }

    /**
     * 判断指定user_id的结果的个数(登录)
     * @param int $user_id
     * @return integer
     */
    public function countResultsByUserId($user_id)
    {
        $ura_model = new UserAssessmentResultModel();
        return $ura_model->countResultsByUserId($user_id);
    }

    /**
     * 判断是否首次领券
     * 如果不是首次领券，则返回第一次的评估结果
     * @param int $user_id
     * @return integer
     */
    public function isFirstAssess($userId, $mobile = "")
    {
        $ura_model = new UserAssessmentResultModel();
        $count1 = $ura_model->countResultsByUserId($userId);
        $count2 = $ura_model->countResultsByMobile($mobile);
        $result['isFirst']= (intval($count1)+intval($count2)) == 0 ? true : false;
        //获取第一次问卷调查的结果,因为每个人最多领券一次，所以用户不是登录是领券就是使用手机号领券
        if($count1 > 0){
            $result['data'] = $ura_model->getFirstResult($userId)->getRow();
            return $result;
        }
        if($count2 > 0){
            $result['data'] = $ura_model->getResultByMobile($mobile)->getRow();
            return $result;
        }
        return $result;
    }

    /**
     * 获取用户的评估信息
     * 最初的一次答卷
     * @param $mobile
     * @return bool|\libs\db\Model
     */
    public function getResultByMobile($mobile)
    {
        $model = new UserAssessmentResultModel();
        return $model->getResultByMobile($mobile);
    }
}

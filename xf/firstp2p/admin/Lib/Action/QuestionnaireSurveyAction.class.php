<?php
/**
 * Created by PhpStorm.
 * User: duxuefeng@ucfgroup.com
 * Date: 2017年9月7日
 * Time: 上午11:15:35
 */

use core\dao\RiskAssessmentQuestionsModel;
use core\dao\UserRiskAssessmentModel;
use core\dao\UserModel;
use core\service\RiskAssessmentService;


class QuestionnaireSurveyAction extends CommonAction{
    /**
     * 首页
     */
    public function index()
    {
        $questionModel = new RiskAssessmentQuestionsModel();
        $questions = $questionModel->getAllQuestions(RiskAssessmentService::TYPE_QUESTION);

        $this->assign('questions', $questions);
        $this->display();
    }


    /**
     * 添加问卷页
     */
    public function add()
    {
        $this->display();
    }

    /**
     * 创新新问卷
     */
    public function insert()
    {
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        $limit_type = isset($_POST['limit_type']) ? intval($_POST['limit_type']) : 0;
        $limit_times = isset($_POST['limit_times']) ? intval($_POST['limit_times']) : 0;
        $limit_period = isset($_POST['limit_period']) ? intval($_POST['limit_period']) : 0;
        $sub_title = isset($_POST['sub_title']) ? $_POST['sub_title'] : array();
        // $sub_choose_item_num = isset($_POST['sub_choose_item_num']) ? $_POST['sub_choose_item_num'] : array();
        $sub_item_num = isset($_POST['sub_item_num']) ? $_POST['sub_item_num'] : array();
        $item_content = isset($_POST['item_content']) ? $_POST['item_content'] : array();
        $item_score = isset($_POST['item_score']) ? $_POST['item_score'] : array();
        $level_name = isset($_POST['level_name']) ? $_POST['level_name'] : array();
        $lowest_score = isset($_POST['lowest_score']) ? $_POST['lowest_score'] : array();
        $prompt = isset($_POST['prompt']) ? $_POST['prompt'] : '';
        $remark = isset($_POST['remark']) ? $_POST['remark'] : '';

        if (empty($sub_title)) {
            $this->error('请填写问题标题');
        }
        /*
        if (empty($sub_choose_item_num)) {
            $this->error('请填写问题选项的可选数目');
        }
        */
        if (empty($item_content) || empty($item_score)
            || count($item_content) != count($item_score)) {
            $this->error('请填写选项的内容和分值');
        }
        foreach ($item_score as $score) {
            if (!is_numeric($score)) {
                $this->error('选项分值格式错误,请填写数字');
            }
        }
        if (array_sum($sub_item_num) != count($item_score)) {
            $this->error('选项数目错误,请刷新重试');
        }
        if (empty($level_name) || empty($lowest_score) || count($level_name) != count($lowest_score)) {
            $this->error('请填写分类名称、最低分值和限额');
        }

        foreach ($lowest_score as $score) {
            if (!is_numeric($score)) {
                $this->error('分类最低分值格式错误,请填写数字');
            }
        }
        if (empty($prompt)) {
            $this->error('请填写提示信息');
        }
        if (empty($remark)) {
            $this->error('请填写备注');
        }

        $subjects = array(); //问题列表
        $start = 0; //用来定位问题包含的选项
        $total_score = 0; //问卷总分
        foreach ($sub_title as $key => $title) {
            $subject = array();
            $subject['title'] = $title;
            $subject['choose_item_num'] = 1; //写死

            $sum = $start + $sub_item_num[$key];
            $max_item_score = 0;
            for ($index = $start; $index < $sum; $index ++) {
                $item = array();
                $item['content'] = $item_content[$index];
                $item['score'] = $item_score[$index];
                if ($item['score'] > $max_item_score) {
                    $max_item_score = $item['score'];
                }
                $subject['items'][] = $item;
            }
            $subject['total_score'] = $max_item_score; //问题总分
            $total_score += $max_item_score;
            $start += $sub_item_num[$key];
            $subjects[] = $subject;
        }

        $levels = array();
        foreach ($level_name as $key => $name) {
            $level['name'] = $name;
            $level['lowest_score'] = $lowest_score[$key];
            $levels[] = $level;
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        $riskAssessmentService = new RiskAssessmentService();
        $ques_id = $riskAssessmentService->newQuestion($status, $prompt, $remark, $limit_type, $limit_times,
            $limit_period, $total_score, $subjects, $levels, $adm_id, RiskAssessmentService::TYPE_QUESTION);
        if (!$ques_id) {
            $this->error(L("INSERT_FAILED"));
        }
        $this->success(L("INSERT_SUCCESS", 0, '?m=QuestionnaireSurvey&a=index'));
    }

    /**
     * 编辑问卷页面
     */
    public function edit()
    {
        $ques_id = isset($_GET['ques_id']) ? intval($_GET['ques_id']) : 0;
        if (!$ques_id) {
            $this->error(L("ERROR"));
        }
        $riskAssessmentService = new RiskAssessmentService();
        $question = $riskAssessmentService->getQuestionById($ques_id);
        $this->assign('question', $question);
        $this->display();
    }

    /**
     * 更新问卷
     */
    public function update()
    {

        $ques_id = isset($_POST['ques_id']) ? intval($_POST['ques_id']) : 0;
        $sub_id = isset($_POST['sub_id']) ? $_POST['sub_id'] : array();
        $item_id = isset($_POST['item_id']) ? $_POST['item_id'] : array();
        $level_id = isset($_POST['level_id']) ? $_POST['level_id'] : array();
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        $limit_type = isset($_POST['limit_type']) ? intval($_POST['limit_type']) : 0;
        $limit_times = isset($_POST['limit_times']) ? intval($_POST['limit_times']) : 0;
        $limit_period = isset($_POST['limit_period']) ? intval($_POST['limit_period']) : 0;
        $sub_title = isset($_POST['sub_title']) ? $_POST['sub_title'] : array();
        // $sub_choose_item_num = isset($_POST['sub_choose_item_num']) ? $_POST['sub_choose_item_num'] : array();
        $sub_item_num = isset($_POST['sub_item_num']) ? $_POST['sub_item_num'] : array();
        $item_content = isset($_POST['item_content']) ? $_POST['item_content'] : array();
        $item_score = isset($_POST['item_score']) ? $_POST['item_score'] : array();
        $level_name = isset($_POST['level_name']) ? $_POST['level_name'] : array();
        $lowest_score = isset($_POST['lowest_score']) ? $_POST['lowest_score'] : array();
        $prompt = isset($_POST['prompt']) ? $_POST['prompt'] : '';
        $remark = isset($_POST['remark']) ? $_POST['remark'] : '';

        if (empty($ques_id)) {
            $this->error('缺少问卷编号');
        }

        if (empty($sub_id)) {
            $this->error('缺少问题编号');
        }

        if (empty($item_id)) {
            $this->error('缺少问题选项编号');
        }

        if (empty($level_id)) {
            $this->error('缺少问题选项编号');
        }

        if (empty($sub_title)) {
            $this->error('请填写问题标题');
        }
        /*
        if (empty($sub_choose_item_num)) {
            $this->error('请填写问题选项的可选数目');
        }
        */
        if (empty($item_content) || empty($item_score)
            || count($item_content) != count($item_score)) {
            $this->error('请填写选项的内容和分值');
        }
        foreach ($item_score as $score) {
            if (!is_numeric($score)) {
                $this->error('选项分值格式错误,请填写数字');
            }
        }
        if (array_sum($sub_item_num) != count($item_score)) {
            $this->error('选项数目错误,请刷新重试');
        }
        if (empty($level_name) || empty($lowest_score) || count($level_name) != count($lowest_score)){
            $this->error('请填写分类名称、最低分值和限额');
        }
        foreach ($lowest_score as $score) {
            if (!is_numeric($score)) {
                $this->error('分类最低分值格式错误,请填写数字');
            }
        }
        if (empty($prompt)) {
            $this->error('请填写提示信息');
        }
        if (empty($remark)) {
            $this->error('请填写备注');
        }

        $subjects = array(); //问题列表
        $start = 0; //用来定位问题包含的选项
        $total_score = 0; //问卷总分
        foreach ($sub_title as $key => $title) {
            $subject = array();
            $subject['id'] = $sub_id[$key];
            $subject['title'] = $title;
            $subject['choose_item_num'] = 1; //写死

            $sum = $start + $sub_item_num[$key];
            $max_item_score = 0;
            for ($index = $start; $index < $sum; $index ++) {
                $item = array();
                $item['id'] = $item_id[$index];
                $item['content'] = $item_content[$index];
                $item['score'] = $item_score[$index];
                if ($item['score'] > $max_item_score) {
                    $max_item_score = $item['score'];
                }
                $subject['items'][] = $item;
            }
            $subject['total_score'] = $max_item_score; //问题总分
            $total_score += $max_item_score;
            $start += $sub_item_num[$key];
            $subjects[] = $subject;
        }

        $levels = array();
        foreach ($level_name as $key => $name) {
            $level['id'] = $level_id[$key];
            $level['name'] = $name;
            $level['lowest_score'] = $lowest_score[$key];
            $levels[] = $level;
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        $riskAssessmentService = new RiskAssessmentService();
        $res = $riskAssessmentService->updateQuestion($ques_id, $status, $prompt, $remark, $limit_type, $limit_times,
            $limit_period, $total_score, $subjects, $levels, $adm_id, RiskAssessmentService::TYPE_QUESTION);
        if (!$res) {
            $this->error(L("UPDATE_FAILED"));
        }
        $this->success(L("UPDATE_SUCCESS"), 0, "?m=QuestionnaireSurvey&a=edit&ques_id=$ques_id");
    }

    /**
     * 删除问卷ajax
     */
    public function delete()
    {
        try {
            $ques_id = isset($_POST['ques_id']) ? intval($_POST['ques_id']) : 0;
            if (empty($ques_id)) {
                throw new \Exception('参数错误');
            }

            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $adm_id = intval($adm_session['adm_id']);

            $riskAssessmentService = new RiskAssessmentService();
            $deleteItem = $riskAssessmentService->deleteQuestion($ques_id, $adm_id);
            if (!$deleteItem) {
                throw new \Exception('删除问题失败');
            }
            $result = array(
                'status' => 1,
                'msg' => '删除问题成功',
            );
        } catch (\Exception $e) {
            $result = array(
                'status' => 0,
                'msg' => $e->getMessage(),
            );
        }
        echo json_encode($result);
    }

    /**
     * 开启问卷ajax
     */
    public function enable()
    {
        try {
            $ques_id = isset($_POST['ques_id']) ? intval($_POST['ques_id']) : 0;
            if (empty($ques_id)) {
                throw new \Exception('参数错误');
            }

            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $adm_id = intval($adm_session['adm_id']);

            $riskAssessmentService = new RiskAssessmentService();
            $deleteItem = $riskAssessmentService->enableQuestion($ques_id, $adm_id, RiskAssessmentService::TYPE_QUESTION);
            if (!$deleteItem) {
                throw new \Exception('开启问题失败');
            }
            $result = array(
                'status' => 1,
                'msg' => '开启问题成功',
            );
        } catch (\Exception $e) {
            $result = array(
                'status' => 0,
                'msg' => $e->getMessage(),
            );
        }
        echo json_encode($result);
    }

    /**
     * 关闭问卷ajax
     */
    public function disable()
    {
        try {
            $ques_id = isset($_POST['ques_id']) ? intval($_POST['ques_id']) : 0;
            if (empty($ques_id)) {
                throw new \Exception('参数错误');
            }

            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $adm_id = intval($adm_session['adm_id']);

            $riskAssessmentService = new RiskAssessmentService();
            $deleteItem = $riskAssessmentService->disableQuestion($ques_id, $adm_id);
            if (!$deleteItem) {
                throw new \Exception('关闭问题失败');
            }
            $result = array(
                'status' => 1,
                'msg' => '关闭问题成功',
            );
        } catch (\Exception $e) {
            $result = array(
                'status' => 0,
                'msg' => $e->getMessage(),
            );
        }
        echo json_encode($result);
    }

    /**
     * 删除选项
     */
    public function deleteItem()
    {
        try {
            $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
            if (!$item_id) {
                throw new \Exception('参数错误');
            }
            $riskAssessmentService = new RiskAssessmentService();
            $deleteItem = $riskAssessmentService->deleteItemById($item_id);
            if (!$deleteItem) {
                throw new \Exception('删除选项失败');
            }
            $result = array(
                'status' => 1,
                'msg' => '删除选项成功',
            );
        } catch (\Exception $e) {
           $result = array(
               'status' => 0,
               'msg' => $e->getMessage(),
           );
        }
        echo json_encode($result);
    }

    /**
     * 删除问题
     */
    public function deleteSubject()
    {

        try {
            $sub_id = isset($_POST['sub_id']) ? intval($_POST['sub_id']) : 0;
            if (!$sub_id) {
                throw new \Exception('参数错误');
            }
            $riskAssessmentService = new RiskAssessmentService();
            $deleteSubject = $riskAssessmentService->deleteSubjectById($sub_id);
            if (!$deleteSubject) {
                throw new \Exception('删除问题失败');
            }
            $result = array(
                'status' => 1,
                'msg' => '删除问题成功',
            );
        } catch (\Exception $e) {
            $result = array(
                'status' => 0,
                'msg' => $e->getMessage(),
            );
        }
        echo json_encode($result);
    }

    /**
     * 删除分类
     */
    public function deleteLevel()
    {

        try {
            $level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
            if (!$level_id) {
                throw new \Exception('参数错误');
            }
            $riskAssessmentService = new RiskAssessmentService();
            $deleteLevel = $riskAssessmentService->deleteLevelById($level_id);
            if (!$deleteLevel) {
                throw new \Exception('删除分类失败');
            }
            $result = array(
                'status' => 1,
                'msg' => '删除分类成功',
            );
        } catch (\Exception $e) {
            $result = array(
                'status' => 0,
                'msg' => $e->getMessage(),
            );
        }
        echo json_encode($result);
    }
}

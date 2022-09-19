<?php
/**
 * Created by PhpStorm.
 * User: weiwei12@ucfgroup.com
 * Date: 16/6/6
 * Time: 上午11:15
 */
use core\dao\risk\RiskAssessmentQuestionsModel;
use core\dao\risk\UserRiskAssessmentModel;
use core\service\user\UserService;
use core\service\risk\RiskAssessmentService;

class RiskAssessmentAction extends CommonAction{

    /**
     * 首页
     */
    public function index()
    {
        $questionModel = new RiskAssessmentQuestionsModel();
        $questions = $questionModel->getAllQuestions();

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
        $expire_days = isset($_POST['expire_days']) ? intval($_POST['expire_days']) : 0;
        $sub_title = isset($_POST['sub_title']) ? $_POST['sub_title'] : array();
        // $sub_choose_item_num = isset($_POST['sub_choose_item_num']) ? $_POST['sub_choose_item_num'] : array();
        $sub_item_num = isset($_POST['sub_item_num']) ? $_POST['sub_item_num'] : array();
        $item_content = isset($_POST['item_content']) ? $_POST['item_content'] : array();
        $item_score = isset($_POST['item_score']) ? $_POST['item_score'] : array();
        $level_name = isset($_POST['level_name']) ? $_POST['level_name'] : array();
        $lowest_score = isset($_POST['lowest_score']) ? $_POST['lowest_score'] : array();
        $prompt = isset($_POST['prompt']) ? $_POST['prompt'] : '';
        $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
        $limit_money = isset($_POST['limit_money']) ? $_POST['limit_money'] : array();
        $total_limit_money = isset($_POST['total_limit_money']) ? $_POST['total_limit_money'] : array();

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
        if (empty($level_name) || empty($lowest_score) || empty($limit_money) 
            || count($level_name) != count($lowest_score)|| count($limit_money) != count($lowest_score)) {
            $this->error('请填写分类名称、最低分值和限额');
        }
        foreach ($limit_money as $money) {
            if ($money > 100 || empty($money)) {
                $this->error('单笔投资限额不能为空，且单笔投资限额最高是100万元');
            }
            if(!is_numeric($money) || ( isset(explode('.', $money)['1']) && strlen(explode('.', $money)['1']) > 3 )){
                $this->error('必须为整数或小数,且最多输入三位小数');
            }
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
            $level['limit_money'] = $limit_money[$key];
            $level['total_limit_money'] = $total_limit_money[$key];
            $levels[] = $level;
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        $riskAssessmentService = new RiskAssessmentService();
        $ques_id = $riskAssessmentService->newQuestion($status, $prompt, $remark, $limit_type, $limit_times,
            $limit_period, $total_score, $subjects, $levels, $adm_id, 0, $expire_days);
        if (!$ques_id) {
            $this->error(L("INSERT_FAILED"));
        }
        $this->success(L("INSERT_SUCCESS", 0, '?m=RiskAssessment&a=index'));
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
        $expire_days = isset($_POST['expire_days']) ? intval($_POST['expire_days']) : 0;
        $sub_title = isset($_POST['sub_title']) ? $_POST['sub_title'] : array();
        // $sub_choose_item_num = isset($_POST['sub_choose_item_num']) ? $_POST['sub_choose_item_num'] : array();
        $sub_item_num = isset($_POST['sub_item_num']) ? $_POST['sub_item_num'] : array();
        $item_content = isset($_POST['item_content']) ? $_POST['item_content'] : array();
        $item_score = isset($_POST['item_score']) ? $_POST['item_score'] : array();
        $level_name = isset($_POST['level_name']) ? $_POST['level_name'] : array();
        $lowest_score = isset($_POST['lowest_score']) ? $_POST['lowest_score'] : array();
        $prompt = isset($_POST['prompt']) ? $_POST['prompt'] : '';
        $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
        $limit_money = isset($_POST['limit_money']) ? $_POST['limit_money'] : array();
        $total_limit_money = isset($_POST['total_limit_money']) ? $_POST['total_limit_money'] : array();

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
        if (empty($level_name) || empty($lowest_score) || empty($limit_money) 
            || count($level_name) != count($lowest_score)|| count($limit_money) != count($lowest_score)) {
            $this->error('请填写分类名称、最低分值和限额');
        }
        foreach ($limit_money as $money) {
            if ($money > 100 || empty($money)) {
                $this->error('单笔投资限额不能为空，且单笔投资限额最高是100万元');
            }
            if(!is_numeric($money) || ( isset(explode('.', $money)['1']) && strlen(explode('.', $money)['1']) > 3 )){
                $this->error('必须为整数或小数,且最多输入三位小数');
            }
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
            $level['limit_money'] = $limit_money[$key];
            $level['total_limit_money'] = $total_limit_money[$key];
            $levels[] = $level;
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        $riskAssessmentService = new RiskAssessmentService();
        $ques_id = $riskAssessmentService->updateQuestion($ques_id, $status, $prompt, $remark, $limit_type, $limit_times,
            $limit_period, $total_score, $subjects, $levels, $adm_id, 0, $expire_days);
        if (!$ques_id) {
            $this->error(L("UPDATE_FAILED"));
        }
        $this->success(L("UPDATE_SUCCESS"), 0, '?m=RiskAssessment&a=index');
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
            $deleteItem = $riskAssessmentService->enableQuestion($ques_id, $adm_id);
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

    /**
     * 导出数据
     */
    public function export()
    {
        $begin_time = isset($_REQUEST['begin']) ? strtotime($_REQUEST['begin']) : '';
        $end_time = isset($_REQUEST['end']) ? strtotime($_REQUEST['end']) : '';
        $offset = 0;
        $page_size = 1000;

        $userRiskAssessmentModel = new UserRiskAssessmentModel();

        $file_name = 'user_risk_assessment_data';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($file_name) . '.csv"');
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $title = array("问卷编号","投资人ID","姓名","完成时间","问卷结果");
        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));

        while ($list = $userRiskAssessmentModel->getURAList($offset, $page_size, $begin_time, $end_time)) {
            ob_flush();
            flush();
            foreach($list as $rsa) {
                $user_info = UserService::getUserById($rsa['user_id']);
                $row = sprintf("%s||%s||%s||%s||%s", $rsa['last_ques_id'], $rsa['user_id'], $user_info['real_name'], date('Y-m-d H:i:s', $rsa['last_assess_time']), $rsa['last_level_name']);
                fputcsv($fp, explode('||', iconv("utf-8", "gbk", $row)));
            }
            $offset += $page_size;
        }
        fclose($fp);
    }
}

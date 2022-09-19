<?php
/**
 * Created by PhpStorm.
 * User: qianyi
 * Date: 2018/11/27
 * Time: 15:36
 */


class DtRedemptionAction extends DtCommonAction
{

    public function index()
    {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $projectId = intval($_REQUEST['project_id']);

        $loanId = intval($_REQUEST['loanId']);
        $userId = intval($_REQUEST['userId']);
        $priority = $_REQUEST['priority'];
        $createTime_begin = trim($_REQUEST['create_time_begin']);
        $createTime_end = trim($_REQUEST['create_time_end']);

        if (empty($projectId)) {
            $this->error("参数错误");
        }

        $request = array(
            "pageNum" => $pageNum,
            "pageSize" => $pageSize,
            "projectId" => $projectId,
            "userId" => $userId,
            "loanId" => $loanId,
            "priority" => $priority,
            "createTimeBegin" => strtotime($createTime_begin),
            "createTimeEnd" => strtotime($createTime_end),
        );
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\RedemptionApply',
            'method' => 'getRedemptionDetail',
            'args' => $request,
        ));

        if (!$response) {
            $this->error("rpc请求失败");
        }
        if ($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:" . $response['errCode'] . " errMsg:" . $response['errMsg']);
        }
        $data = $response['data']['data'];

        $p = new Page($response['data']['totalNum'], $pageSize);
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->assign("projectId", $projectId);
        $this->assign("data", $data);
        $this->display();
    }

    public function change()
    {

        $loanId = intval($_REQUEST['loanId']);
        $userId = intval($_REQUEST['userId']);
        $projectId = intval($_REQUEST['projectId']);
        $priority = intval($_REQUEST['priority']);

        $this->assign("loanId",$loanId);
        $this->assign("userId",$userId);
        $this->assign("projectId",$projectId);
        $this->assign("priority",$priority);
        $this->display ();
    }
    public function doChange()
    {
        $userId = intval($_REQUEST['userId']);
        $priority = $_REQUEST['priority'];
        $loanId = intval($_REQUEST['loanId']);

        if($priority === ""){
            $this->ajaxReturn(false,'修改优先级不能为空',0);
        }
        $request = array(
            array(
            'priority' => $priority,
            'loanId' => $loanId,
            'userId' => $userId,
            ),
        );
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\RedemptionApply',
            'method' => 'changePriority',
            'args' => $request,
        ));

        if($response){
            $this->ajaxReturn($result,'操作成功',1);
        }else{
            $this->ajaxReturn($result,'操作失败',0);
        }

    }

    public function import()
    {
        if (empty ($_FILES ['upfile'] ['name'])) {
            $this->error('上传文件不能为空');
        }
        if (end(explode('.', $_FILES ['upfile'] ['name'])) != 'csv') {
            $this->error("请上传csv格式的文件！");
        }

        $csv_content = file_get_contents($_FILES ['upfile'] ['tmp_name']);
        $csv_content = trim($csv_content);
        if (empty ($csv_content)) {
            $this->error('文件内容不能为空');
        }
        $total_line = explode("\n", iconv('GBK', 'UTF-8', $csv_content));
        // 统计去掉第一个行Title
        $count_total_line = count($total_line) - 1;
        // 最后一行如果空行，不做计数
        if (empty ($total_line [$count_total_line])) {
            $count_total_line -= 1;
        }

        if (($handle = fopen($_FILES ['upfile'] ['tmp_name'], "r")) === false) {
            $this->error('文件不可读');
        }
        // 第一行是标题不放到数据列表里
        if (fgetcsv($handle) === false) {
            $this->error('数据读取错误');
        }
        $filename = basename($_FILES ['upfile'] ['name']);
        $j = 0;
        $err_msg = '';
        $error_total_num = 0;
        while (($row_data = fgetcsv($handle)) !== false) {

            //若必填项userID或者优先级为空
            if (trim($row_data[0]) === "" || trim($row_data[2]) === "") {
                $error_total_num++;
                $j++;
                $err_msg .=  implode(',', $row_data) . ' <br />';
                continue;
            }

            $csv_row[$j] = array(
                'userId' => $row_data[0],
                'loanId' => $row_data[1],
                'priority' => intval($row_data[2]),
            );

            if ($row_data[1] == '全部' || empty($row_data[1]))
                $csv_row[$j]['loanId'] = null;

            $j++;

        }
        fclose($handle);

        if (empty($csv_row)) {
            $err_msg = "文件名：{$filename}<br />
                    成功：0条<br />
                    失败明细：{$err_msg}
                ";

            $this->error($err_msg);
        }
        $request = $csv_row;

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\RedemptionApply',
            'method' => 'checkData',
            'args' => $request,
        ));
        $err_msg .= $response['data']['errMsg'];

        $error_total_num += $response['data']['num'] ;
        $j -= $error_total_num;

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\RedemptionApply',
            'method' => 'changePriority',
            'args' => $request,
        ));
        if($response['data'] == false){
            $err_msg = "更新失败请重试<br />";
            $this->error($err_msg);
        }

        $msg = "文件名：{$filename}
        <br />成功：{$j}条<br />
        失败：{$error_total_num}条<br />
        失败明细：{$err_msg}";
        $this->assign('waitSecond',"5");
        $this->success($msg);
    }
    function get_priority_update_csv_tpl(){

        header('Content-Type: text/csv;charset=utf8');
        header("Content-Disposition: attachment; filename=priority_update__template.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');
        $title = array('用户id（必填）', '投资记录id（非必填，若要全部改则不填）', '优先级（必填）');
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }

        fputcsv($fp, $title);
        exit;
    }

}
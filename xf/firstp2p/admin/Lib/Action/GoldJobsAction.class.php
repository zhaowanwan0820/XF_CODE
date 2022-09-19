<?php

use NCFGroup\Protos\Gold\RequestCommon;

class GoldJobsAction extends CommonAction{
    public function __construct() {
        parent::__construct();
    }

    public function wait() {
        $_GET['status'] = 0;
        $this->index();
    }
    public function process() {
        $_GET['status'] = 1;
        $this->index();
    }
    public function succ() {
        $_GET['status'] = 2;
        $this->index();
    }
    public function fail() {
        $_GET['status'] = 3;
        $this->index();
    }

    public function index() {
        $map = array();
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0; 
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $pageSize = C('PAGE_LISTROWS');
        $vars = array(
            'status' => $status,
            'pageNum' => $p,
            'pageSize' => $pageSize,
        );
        $request = new RequestCommon();
        $request->setVars($vars);

        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Jobs',
            'method' =>'listJobs',
            'args' => $request,
        ));

        $page = new Page($response['totalNum'], $pageSize);
        $this->assign('page', $page->show());
        $this->assign('nowPage', $p);
        $this->assign('data', $response['data']);
        $this->assign('status', $status);

        $this->display('index');
    }

    public function view() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }

        $vars = array(
            'id' => $id,
        );
        $request = new RequestCommon();
        $request->setVars($vars);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Jobs',
            'method' => 'getJobsById',
            'args' => $request,
        ));

        $job = $response;
        if (empty($job)) {
            $this->error('记录不存在');
        }
        
        $this->assign('job', $job);
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('p', $p);
        $this->display();
    }


    public function redo() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }

        $vars = array(
            'id' => $id,
        );
        $request = new RequestCommon();
        $request->setVars($vars);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\Jobs',
            'method' => 'redoJobs',
            'args' => $request,
        ));

        if (!$response) {
            $this->error('操作失败');
        } else {
            $this->success('加入队列成功'); 
        }
    }
}

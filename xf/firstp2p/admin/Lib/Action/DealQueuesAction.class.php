<?php

/**
 * DealQueuesAction 上标队列管理
 * @author  wangzhen3，chenyanbing
 */


use core\dao\DealQueuesModel;
use core\dao\DealQueueInfosModel;
use core\service\DealQueueService;
use core\dao\DealLoanTypeModel;

class DealQueuesAction extends CommonAction{

    /**
     * 队列首页
     */
    public function index(){
        $name=trim($_REQUEST['name']);

        $dealQueueService = new DealQueueService();
        $result=$dealQueueService->searchQueue($name);
        foreach ($result as $key => $item) {
            // 获取产品类别名称
            $dealTypeObj= $dealQueueService->getTypeInfoById($item['type_id']);
            if (empty($dealTypeObj)) {
                $result[$key]['type_name'] = '无';
            } else {
                $result[$key]['type_name'] = $dealTypeObj->name;
            }
        }
        $this->assign('list', $result);
        $this->display();
    }

    /**
     *新增队列补全数据
     */
    public function add()
    {
        $dealQueueService = new DealQueueService();
        // 获取产品类别
        $deal_type_tree=$dealQueueService->getProductName();
        $this->assign("deal_type_tree",$deal_type_tree);

        // get deal_params_conf
        $deal_params_conf_list = $dealQueueService->getDealParamsConf();
        $this->assign("deal_params_conf_list", $deal_params_conf_list);
        $this->display();
    }

    /**
     *创建队列
     */
    public function insert(){
        $name=$_REQUEST['name'];
        $note=$_REQUEST['note'];
        $is_effect=intval($_REQUEST['is_effect']);
        if(!$name){
            $this->error("队列名称不可为空");
        }
        $type_id=intval($_REQUEST['type_id']);
        $service_type=addslashes($_REQUEST['service_type']);
        $start_time=(''==trim($_REQUEST['start_time']))? 0:to_timespan($_REQUEST['start_time']);
        $sell_out=intval($_REQUEST['sell_out']);
        $data =array(
            "name"=>$name,
            "note"=>$note,
            "is_effect"=>$is_effect,
            "create_time"=>get_gmtime(),
            "type_id"=>$type_id,
            "service_type"=>$service_type,
            "start_time"=>$start_time,
            "sell_out"=>$sell_out,
            "deal_params_conf_id"=>intval($_REQUEST['deal_params_conf_id']),
        );
        $dealqueueservice=new DealQueueService();
        $countName=$dealqueueservice->getCntByName($data['name']);
        if(!$countName){
            $this->error("队列名称不唯一");
        }
        $result=$dealqueueservice->add($data);
        if(!$result){
            save_log("创建队列失败[{$data['name']},data:".json_encode($data)."]", 1);
            $this->error("操作失败");
        }else{
            save_log("创建队列成功[{$data['name']},data:".json_encode($data)."]", 1);
        }
        $this->redirect("/m.php?m=DealQueues&a=index&");
    }
    /**
     * 修改队列
     */
    public function save() {
        $name=$_REQUEST['name'];
        $note=$_REQUEST['note'];
        $is_effect=intval($_REQUEST['is_effect']);
        if(!$name){
            $this->error("队列名称不可为空");
        }
        $type_id=intval($_REQUEST['type_id']);
        $service_type=addslashes($_REQUEST['service_type']);
        $start_time=(''==trim($_REQUEST['start_time']))? 0:to_timespan($_REQUEST['start_time']);
        $sell_out=intval($_REQUEST['sell_out']);
        $data =array(
            "name"=>$name,
            "note"=>$note,
            "is_effect"=>$is_effect,
            "type_id"=>$type_id,
            "service_type"=>$service_type,
            "start_time"=>$start_time,
            "sell_out"=>$sell_out,
            "deal_params_conf_id"=>intval($_REQUEST['deal_params_conf_id']),
        );
        $id=intval($_REQUEST['id']);
        $dealQueueService=new DealQueueService($id);
        $countName=$dealQueueService->getCntByName($data['name'],$id);
        if(!$countName){
            $this->error("队列名称不唯一");
        }
        $result=$dealQueueService->updateQueue($data,$id);
        if($result){
            save_log("编辑队列成功[{$data['name']},data:".json_encode($data)."]", 1);
            //设置首标状态为进行中
            if($is_effect){
            //队列重新更新，所有再获取一次队列信息
            $dealQueueService=new DealQueueService($id);
            $dealQueueService->setHeadDealProcess();
            }
            $this->redirect("/m.php?m=DealQueues&a=index&");
        }else{
            save_log("编辑队列成功[{$data['name']},data:".json_encode($data)."]", 1);
        }
        $this->error("操作失败");
    }

    /**
     * 队列编辑页面补全数据
     */
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $dealQueueService = new DealQueueService();
        $vo = $dealQueueService->getQueueInfoById($id);
        $this->assign ( 'vo', $vo );

        // 获取产品类别
        $deal_type_tree=$dealQueueService->getProductName();
        $this->assign("deal_type_tree",$deal_type_tree);

        // get deal_params_conf
        $deal_params_conf_list = $dealQueueService->getDealParamsConf();
        $this->assign("deal_params_conf_list", $deal_params_conf_list);
        $this->display();
    }
    /**
     *删除队列
     */
    public function delete(){
        $ajax = intval($_REQUEST['ajax']);
        $queue_id_arr = isset($_REQUEST ['id']) ? explode(',', $_REQUEST ['id']) : array();
        $dealqueueservice=new DealQueueService();
        if (false === $dealqueueservice->deleteQueue($queue_id_arr)) {
            save_log("删除队列失败[{$_REQUEST ['id']}]", 1);
            $this->error ("操作失败");
        } else {
            save_log("删除队列成功[{$_REQUEST ['id']}]", 1);
            $this->success ("删除成功",$ajax);
        }
    }
    /**
     * 查看队列数据
     */
    public function show(){
        $id = intval($_REQUEST ['id']);
        $queueId = intval($_REQUEST['id']);
        $deal_id = intval($_REQUEST['deal_id']);
        if($queueId == 0) {
            $this->error("队列不存在");
        }
        $dealQueueService = new DealQueueService($queueId);
        $dealList = $dealQueueService->getQueueInfos($deal_id);
        $queueInfo=$dealQueueService->getQueueById($queueId);
        $this->assign("list",$dealList);
        $this->assign("queueName",$queueInfo['name']);
        $this->assign("queueId",$queueId);
        $this->display();
    }

    public function addDeal(){
        $queueId = intval($_REQUEST['queue_id']);
        if($queueId == 0) {
            $this->error("队列不存在");
        }

        $dealQueueService = new DealQueueService($queueId);
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $conditon = array(
                'pageSize' => $pageSize,
                'pageNum' => $pageNum,
                'dealStatus' => '0,1'
        );

        $dealId = intval($_REQUEST['dealId']);
        if($dealId != 0){
            $conditon['dealId'] = $dealId;
        }

        //对列中有的标不包含
        $dealIds = $dealQueueService->getDealIds();
        if(!empty($dealIds)){
            $conditon['noDealIds'] = $dealIds;
        }

        $dealList = $dealQueueService->getDealList($conditon);
        $p = new Page ($dealList['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $this->assign("list",$dealList['data']);
        $this->assign("queueId",$queueId);
        $this->assign("jumpId",0);
        $this->display();
    }

    /**
     * 标插入队列
     */
    public function insertDeals(){
        $ajax = intval($_REQUEST['ajax']);
        $queueId = intval($_REQUEST['queueId']);
        $dealIds = explode(',', $_REQUEST['dealIds']);
        $jumpId = intval($_REQUEST['jumpId']);
        $dealIds = array_map('intval', $dealIds);
        $dealQueueService = new DealQueueService($queueId);
        if (!empty($queueId) && !empty($dealIds)) {
            if(empty($jumpId)){
                $result = $dealQueueService->operationQueue($dealIds,DealQueueService::ADD);
            }else{
                $result = $dealQueueService->operationQueue(array('jumpId'=>$jumpId,'dealIds'=>$dealIds),DealQueueService::JUMP);
            }
            if ($result!==false) {
                $dealQueueService->setHeadDealProcess();//设置首标状态为进行中
                save_log("成功将标的 ".implode(',', $dealIds)."加入到队列 {$queueId} 中,jumpId:{$jumpId}", 1);
                $this->success ('操作成功',$ajax);
            } else {
                save_log("将标的 ".implode(',', $dealIds)."加入到队列 {$queueId} 中失败,jumpId:{$jumpId}",0);
                $this->error ('操作失败',$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    /**
     *移动队列
     */
    public function move_deal()
    {
        $ajax = intval($_REQUEST['ajax']);
        $deal_id = intval($_REQUEST['deal_id']);
        $queueId = intval($_REQUEST['queue_id']);
        $dealQueueService = new DealQueueService($queueId);
        $direction = intval($_REQUEST['direction']);
        if(!empty($deal_id)&&!empty($queueId)&&!empty($direction)){
            if($direction==2){
                $result=$dealQueueService->operationQueue($deal_id,DealQueueService::MOVEUP);
            }else{
                $result=$dealQueueService->operationQueue($deal_id,DealQueueService::MOVEDOWN);
            }
            if($result){
                save_log("移动队列元素成功[deal_id:{$deal_id},queueId:{$queueId},direction:{$direction}]", 1);
                $this->success ('操作成功',$ajax);
            }else{
                save_log("移动队列元素失败[deal_id:{$deal_id},queueId:{$queueId},direction:{$direction}]", 1);
                $this->error ('操作失败',$ajax);
            }
        }else{
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    /**
     *标的插队
     */
    public function moveTop(){
        $queueId = intval($_REQUEST['queue_id']);
        $jump_id = intval($_REQUEST['jump_id']);
        $dealQueueService = new DealQueueService($queueId);
        if(!empty($jump_id)&&!empty($queueId)){
            $result=$dealQueueService->operationQueue($jump_id,DealQueueService::TOP);
            if($result){
                save_log("队列元素置顶成功[jump_id:{$jump_id},queueId:{$queueId}]", 1);
                $this->success ('操作成功',$ajax);
            }else{
                save_log("队列元素置顶失败[jump_id:{$jump_id},queueId:{$queueId}]", 1);
                $this->error ('操作失败',$ajax);
            }
        }else{
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    /**
     *从队列删除标的
     */
    public function delete_deal(){
        $ajax = intval($_REQUEST['ajax']);
        $queueId = intval($_REQUEST['queue_id']);
        $deal_ids = $_REQUEST['deal_id'];
        $dealQueueService = new DealQueueService($queueId);
        if (!empty($deal_ids)&&!empty($queueId)) {
            $result=$dealQueueService->operationQueue($deal_ids,DealQueueService::DEL);
            if($result){
                save_log("删除队列元素成功[deal_ids:{$deal_ids},queueId:{$queueId}]", 1);
                $this->success ('操作成功',$ajax);
            }else{
                save_log("删除队列元素失败[deal_ids:{$deal_ids},queueId:{$queueId}]", 1);
                $this->error ('操作失败',$ajax);
            }
        }
    }

    public function jump(){
        $queueId = intval($_REQUEST['queue_id']);
        $jumpId = intval($_REQUEST['jump_id']);
        if($queueId == 0) {
            $this->error("队列不存在");
        }

        $dealQueueService = new DealQueueService($queueId);
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $conditon = array(
                'pageSize' => $pageSize,
                'pageNum' => $pageNum,
                'dealStatus' => '0,1'
        );

        $dealId = intval($_REQUEST['dealId']);
        if($dealId != 0){
            $conditon['dealId'] = $dealId;
        }

        //对列中有的标不包含
        $dealIds = $dealQueueService->getDealIds();
        if(!empty($dealIds)){
            $conditon['noDealIds'] = $dealIds;
        }

        $dealList = $dealQueueService->getDealList($conditon);
        $p = new Page ($dealList['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $this->assign("list",$dealList['data']);
        $this->assign("queueId",$queueId);
        $this->assign("jumpId",$jumpId);
        $this->display('addDeal');
    }

    /**
     * 重置队列
     */
    public function resetQueue(){
        $queueId = intval($_REQUEST['id']);
        $dealQueueService = new DealQueueService($queueId);
        $result = $dealQueueService->operationQueue($queueId,DealQueueService::RESET);
        if($result){
            save_log("重置队列成功[queueId:{$queueId}]", 1);
            $this->success ('操作成功',1);
        }else{
            save_log("重置队列失败[queueId:{$queueId}]", 1);
            $this->error ('操作失败',1);
        }
    }
}

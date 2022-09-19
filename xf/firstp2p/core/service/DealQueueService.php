<?php
/**
 * 上标队列服务化
 * @author wangzhen3，chenyanbing
 */


namespace core\service;

use libs\utils\Logger;
use core\dao\DealQueuesModel;
use core\service\GoldService;
use core\dao\DealLoanTypeModel;
use core\dao\DealParamsConfModel;
use core\dao\DealQueueInfosModel;
use libs\lock\LockFactory;

class DealQueueService
{
    const GOLD = 'GOLD'; //黄金队列类型
    const P2P = 'XD';//p2p队列类型

    /**队列操作类型**/
    const ADD = 1;//添加元素
    const DEL = 2;//删除元素
    const MOVEUP =3;//队列上移
    const MOVEDOWN=4;//队列下移
    const POP = 5;//队首元素出列
    const TOP = 6;//元素置顶
    const RESET =7;//重置队列
    const JUMP =8;//重置队列

    private $queueId = null;//队列id
    private $serviceType = null;//队列类型
    private $queueInfo = null;

    public function __construct($queueId = 0,$serviceType = null){
        $this->queueId = intval($queueId);
        $this->serviceType = addslashes($serviceType);
        if($this->queueId !== 0){
            $this->queueInfo = $this->getQueueById($this->queueId);
            if(!empty($this->queueInfo)){
                $this->serviceType = $this->queueInfo['service_type'];
            }
        }
    }

    /**
     * 触发自动上标
     * @param int $dealId
     * @return boolean
     */
    public function process($dealId){
        $result = true;
        $queueId = DealQueueInfosModel::instance()->getQueueIdByDealId($dealId,$this->serviceType);
        if(!empty($queueId)){
           $this->queueId = $queueId;
           $this->queueInfo = $this->getQueueById($this->queueId);
           //队列有效情况下才触发自动上标
           if($this->isEffect()){
               do{
                   $i = 0;
                   try {
                       sleep($i);
                       $GLOBALS['db']->startTrans();
                       //删除当前队列中的标id
                       $result = $this->operationQueue($dealId,self::DEL);
                       if(empty($result)){
                           throw new \Exception("操作队列失败");
                       }
                       //获取队列有意义的标,并更新为进行中
                       $processDeal = $this->getProcessDeal();
                       if($processDeal !== true){
                           $result = $this->updateDealStatus($processDeal['deal_id']);
                           if(empty($result)){
                               throw new \Exception("更新标状态失败");
                           }
                       }
                       $GLOBALS['db']->commit();
                   } catch (\Exception $e) {
                       $i++;
                       $GLOBALS['db']->rollback();
                       \libs\utils\Alarm::push('gold_exception',"触发自动上标失败:queueId:".$queueId.",dealId:".$dealId."error:".$e->getMessage(),json_encode($this->queueInfo));
                       Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,"queueId:".$queueId,"dealId:".$dealId,"queueInfo:".json_encode($this->queueInfo), "error:".$e->getMessage())));
                       $result = false;
                   }
               }while (!$result && $i<=5);//如果执行失败，重试5次，愿意可能是锁造成的
           }
        }
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,"queueId:".$queueId,"dealId:".$dealId, '自动上标成功')));
        return $result;
    }
    /**
     *通过名称搜索队列
     */
    public function searchQueue($name=''){
        $dealQueuesModel=new DealQueuesModel();
        $res=$dealQueuesModel->getQueueListByName($name);
        return $res;
    }
    /**
     * 通过队列id获取队列
     * @param intval $id
     */
    public function getQueueById($id){
        return DealQueuesModel::instance()->getById($id);
    }

    /**
     * 操作队列方法
     */
    public function operationQueue($data,$operation){
        try {
            $GLOBALS['db']->startTrans();
            // 悲观锁，以id为锁的键名
            $lockKey = __CLASS__.'-'.__FUNCTION__.'-'.$this->queueId;
            $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
            if (!$lock->getLock($lockKey, 300)) {
                throw new \Exception('加锁失败!');
            }

            switch ($operation){
                case self::ADD:
                    $this->push($data);
                    break;
                case self::DEL:
                    $this->del($data);
                    break;
                case self::MOVEUP:
                    $this->moveUp($data);
                    break;
                case self::MOVEDOWN:
                    $this->moveDown($data);
                    break;
                case self::TOP:
                    $this->top($data);
                    break;
                case self::RESET:
                    $this->reset($data);
                case self::JUMP:
                    $this->jump($data);
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,"operation:".$operation,"data:".json_encode($data), "error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception',"触发自动上标失败:queueId:".$this->queueId.",operation:".$operation.",data:".json_encode($data)."error:".$e->getMessage(),json_encode($this->queueInfo));
            $lock->releaseLock($lockKey); // 解锁
            return false;
        }
        $lock->releaseLock($lockKey); //解锁
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,"operation:".$operation,"data:".json_encode($data), "success")));
        return true;
    }

    /**
     * 通过id获取产品类别信息
     */
    public function getTypeInfoById($typeId){
        $res= DealLoanTypeModel::instance()->find($typeId);
        return $res;
    }

    /**
     * 增加队列
     */
    public function add($data){
        $dealQueuesModel=new DealQueuesModel();
        $dealQueuesModel->name=$data['name'];
        $dealQueuesModel->note=$data['note'];
        $dealQueuesModel->is_effect=$data['is_effect'];
        $dealQueuesModel->create_time=$data['create_time'];
        $dealQueuesModel->type_id=$data['type_id'];
        $dealQueuesModel->service_type=$data['service_type'];
        $dealQueuesModel->start_time=$data['start_time'];
        $dealQueuesModel->sell_out=$data['sell_out'];
        $dealQueuesModel->deal_params_conf_id=$data['deal_params_conf_id'];
        $result=$dealQueuesModel->insert();
        return $result;
    }

    /**
     * 通过id获取队列信息
     */
    public function getQueueInfoById($id){
        return DealQueuesModel::instance()->find($id);
    }

    /**
     * 删除队列
     */
    public function deleteQueue($queue_id){
        return DealQueuesModel::instance()->deleteQueues($queue_id);
    }

    /**
     * 编辑队列
     */
    public function updateQueue($data,$id){
        $dealQueuesModel=new DealQueuesModel();
        $result=$dealQueuesModel->updateQueue($data,$id);
        return $result;
    }
    /**
     *判断队列名称是否唯一
     */
    public function getCntByName($name,$id=false){
        $dealQueuesModel=new DealQueuesModel();
        if($id){
            $countName=$dealQueuesModel->getCntByName($name,$id);
        }else{
            $countName=$dealQueuesModel->getCntByName($name);
        }
        if($countName>0){
            return false;
        }
        return true;
    }
    /**
     * 关联参数配置方案
     */
    public function getDealParamsConf(){
        $paramsConfModel = new DealParamsConfModel();
        return $paramsConfModel->findAll();
    }

    /**
     * 产品类别
     */
    public  function getProductName(){
        return DealLoanTypeModel::instance()->getProName();
    }

    /**
     * 获取要插入队列的标
     */
    public function getDealList($condition){
        $dealList  = array();
        switch ($this->serviceType){
            case self::P2P:
                break;
            case self::GOLD:
                $dealList = $this->getGoldDealList($condition);
                break;
        }
        return $dealList;
    }



    /**
     * 根据队列id获取标信息
     * @param intval $queueId
     * @return array
     */
    public function getQueueInfos($dealId=''){
        $dealList = array();
        $result = $this->getQueueList($this->queueId);
        if(!empty($result)){
            $dealIds = $this->getDealIdsByQueueId($this->queueId);
            if(!empty($dealId)){
                if(in_array($dealId,$dealIds)){
                    $dealIds=array($dealId);
                }else{
                    $dealIds=array(0);
                }
            }
            $dealInfos = $this->getDealInfosByDealIds($dealIds);
            if(!empty($dealInfos)){
                foreach($result as $key => $val){
                    foreach ($dealInfos as $k => $deal){
                        if($val['deal_id'] == $deal['id']){
                            $dealList[] = $deal;
                            unset($dealInfos[$k]);
                            break;
                        }
                    }
                }
            }
        }
        return $dealList;
    }

    /**
     * 根据队列id获取队列中的标id
     * @param unknown $queueId
     */
    public function getDealIdsByQueueId($queueId){
        return DealQueueInfosModel::instance()->getDealIdsByQueueId($queueId);
    }

    public function getDealIds(){
        return DealQueueInfosModel::instance()->getDealIdsByServiceType($this->serviceType);
    }

    /**
     * 更新首标为进行中，队列是有效的情况下
     * @return boolean
     */
    public function setHeadDealProcess(){
        //获取队列有意义的标,并更新为进行中
        if($this->isEffect()){
            try {
                $GLOBALS['db']->startTrans();
                $processDeal = $this->getProcessDeal();
                if($processDeal !== true){
                    $result = $this->updateDealStatus($processDeal['deal_id']);
                    if(empty($result)){
                        throw new \Exception("更新标状态失败");
                    }
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                \libs\utils\Alarm::push('gold_exception',"触发自动上标失败:queueId:".$this->queueId.",dealId:".$processDeal['deal_id']."error:".$e->getMessage(),json_encode($this->queueInfo));
                Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,"queueId:".$this->queueId,"dealId:".$processDeal['deal_id'],"isEffect:".$this->isEffect, "error:".$e->getMessage())));
                $GLOBALS['db']->rollback();
                return false;
            }
        }
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,"queueId:".$this->queueId,"dealId:".$processDeal['deal_id'],"isEffect:".$this->isEffect, "success")));
        return true;
    }

    /**
     * 获取开始时间大于某个时间的队列
     * @param int $startTime
     */
    public function getQueueByStartTime($startTime){
        return DealQueuesModel::instance()->getQueueByStartTime($this->serviceType,$startTime);
    }

    /**
     * 获取队列
     */
    public function getQueuesList(){
        return DealQueuesModel::instance()->getQueuesList($this->serviceType);
    }

    /**
     * 队列是否有效
     * @return number
     */
    private function isEffect(){
            return $this->queueInfo['is_effect'] == 1 && $this->queueInfo['start_time'] <= get_gmtime();
    }

    /**
     * 获取黄金标列表
     */
    private function getGoldDealList($condition = array()){
        $goldService = new GoldService();
        $list = $goldService->getDealListByCondition($condition);
        return $list;
    }
    /**
     * 更加队列id获取标信息
     * @param unknown $dealIds
     * @return multitype:
     */
    private function getDealInfosByDealIds($dealIds){
        $dealInfos  = array();
        switch ($this->serviceType){
            case self::P2P:
                break;
            case self::GOLD:
                $goldService = new GoldService();
                $dealInfos = $goldService->getDealInfosByDealIds($dealIds);
                break;
        }
        return $dealInfos;
    }

    /**
     * 设置标的状态
     */
    private function getProcessDeal(){
        $head = $this->head();
        if(empty($head)){
            Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,"queueId:".$this->queueId, '队列为空')));
            return true;
        }

        //如果标状态是正常的，那个更新标状态，否则删除当前元素，
        if(!$this->checkDeal($head['deal_id'])){
            $result = $this->operationQueue($head['deal_id'],self::DEL);
            if(empty($result)){
                throw new \Exception("删除节点失败");
            }
            return $this->getProcessDeal();
        }else{
            return $head;
        }

    }

    /**
     * 更新标状态
     * @param int $dealId
     * @return boolean
     */
    private function updateDealStatus($dealId){
        $result = false;
        switch ($this->serviceType){
            case self::P2P:
                break;
            case self::GOLD:
                $result = $this->updateGoldDealStatus($dealId);
                break;
        }
        return $result;
    }

    /**
     *
     */
    private function checkDeal($dealId){
        $result = false;
        switch ($this->serviceType){
            case self::P2P:
                break;
            case self::GOLD:
                $result = $this->checkGoldDeal($dealId);
                break;
        }
        return $result;
    }

    /**
     * 检查标状态
     * @param intval $dealId
     */
    private function checkGoldDeal($dealId){
        $goldService = new GoldService();
        $response = $goldService->getDealById($dealId,0);

        /**
         * goldRpc 请求超时会返回false ，这时候验证标状态为有效，不从队列中删除标
         */
        if($response === false){
            return true;
        }

        $deal = $response['data'];

        if(empty($deal) || ($deal['dealStatus'] != 0 && $deal['dealStatus'] != 1) || $deal['isDelete'] == 1 || $deal['publishWait'] != 0 || $deal['isVisible'] != 1){
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,'dealId'.$dealId,"dealStatus:".$deal['dealStatus'],'isDelete:'. $deal['isDelete'],'publishWait:'.$deal['publishWait'],'isVisible:'.$deal['isVisible'],"error:标状态不正确")));
            return false;
        }
        return true;
    }

    /**
     * 更新黄金的标进行中
     * @param unknown $dealId
     * @param unknown $status
     */
    private function updateGoldDealStatus($dealId){
        $goldService = new GoldService();
        return $goldService->updateDealStatusById($dealId,1);
    }

    /**
     * 获取初始化好的队列
     * @param intval $queueId
     * @return array
     */
    private function getQueueList($queueId){
        $list = array();
        $result = DealQueueInfosModel::instance()->getByQueueId($queueId);
        if(!empty($result)){
            $list = $this->initQueueList($result);
        }
        return $list;
    }

    /**
     * 初始化队列
     * @param array $data
     * @param array $list
     * @return array
     */
    private function initQueueList($data,$list = array()){
        if(empty($data)){
            return $list;
        }else{
            foreach ($data as $key =>$val){
                if(empty($list) && $val['pre'] == 0){
                    $list[] = $val;
                    unset($data[$key]);
                    break;
                }else{
                    $end = end($list);
                    if($end['next'] == $val['deal_id']){
                        $list[] = $val;
                        unset($data[$key]);
                        break;
                    }
                }
            }
            return $this->initQueueList($data,$list);
        }
    }


    /**
     * 返回下一个节点
     * @param unknown $dealId
     */
    private function next($next){
        return DealQueueInfosModel::instance()->getByPre($next,$this->queueId);
    }

    /**
     * 返回前一个节点
     * @param unknown $dealId
     */
    private function pre($pre){
        return DealQueueInfosModel::instance()->getByNext($pre,$this->queueId);
    }

    /**
     * 返回当前节点
     * @param unknown $dealId
     */
    private function current($dealId){
        return DealQueueInfosModel::instance()->getByDealId($dealId,$this->queueId);
    }

    /**
     * 往队尾添加元素
     */
    private function push($dealIds){
        if(!empty($dealIds)){
            foreach ($dealIds as $dealId){
                $result = $this->current($dealId);
                if(!empty($result)){
                    throw new \Exception("添加节点已经存在");
                }
                //如果队列为空则获取不到队伍元素，择插入元素
                $tailNode = $this->tail();
                if(!empty($tailNode)){
                    //如果队尾元素不为空，把队伍元素的next 指针值修改为新插入的元素deal_id 值
                    $result = $this->setNode($tailNode['deal_id'],false,$dealId);
                    if(empty($result)){
                        throw new \Exception("修改节点失败");
                    }
                    //修改插入新的元素的的pre指针为tail 节点的deal_id,新节点的next 指针为空，表示为队尾
                    $result = $this->setNode($dealId,$tailNode['deal_id'],0);
                    if(empty($result)){
                        throw new \Exception("修改节点失败");
                    }
                }else{
                    //如果队列为空，那个插入新元素，pre,next 指针分别修改为0，新节节点即是头也是尾
                    $result = $this->setNode($dealId,0,0);
                    if(empty($result)){
                        throw new \Exception("修改节点失败");
                    }
                }
            }
        }
        return true;
    }

    /**
     * 队首元素出来
     * @param int $dealId
     */
    private function pop(){
        $head = $this->head();
        if(empty($head)){
            return true;
        }
        $result = $this->setNode($head['next'],0);
        if(empty($result)){
            throw new \Exception("修改节点失败");
        }
        $result = DealQueueInfosModel::instance()->deteteById($head['id']);
        if(empty($result)){
            throw new \Exception("删除节点失败");
        }
    }

    /**
     * 获取队首元素
     */
    private function head(){
        return DealQueueInfosModel::instance()->getByPre(0,$this->queueId);
    }

    /**
     * 获取队尾元素
     */
    private function tail(){
        return DealQueueInfosModel::instance()->getByNext(0,$this->queueId);
    }

    /**
     * 删除元素
     */
    private function del($dealIds){
        $list=explode(',',$dealIds);
        $list=array_map('intval',$list);
        foreach($list as $value){
            $current=$this->current($value);
            //删除的节点不存在则返回成功
            if(empty($current)){
                continue;
            }
            //修改前节点，前节点为空，则不修改
            $res=$this->setNode($current['pre'],false,$current['next']);
            if(empty($res)){
                throw new \Exception("修改节点失败");
            }
            //修改后节点，如果后节点为空，则不修改
            $res=$this->setNode($current['next'],$current['pre']);
            if(empty($res)){
                throw new \Exception("修改节点失败");
            }
        }
        $result = DealQueueInfosModel::instance()->deleteByDealIds($list);
        if(empty($result)){
            throw new \Exception("删除节点失败");
        }

        return true;
    }
    /**
     * 更新节点
     * @param int $dealId
     * @param 前指针 $pre
     * @param 后指针 $next
     */
    private function setNode($dealId,$pre = false,$next = false){
        if(empty($dealId)){
            return true;
        }
        return DealQueueInfosModel::instance()->updateByDealId($dealId,$this->queueId,$this->serviceType,$pre,$next);
    }
    /**
     * 队列的上移操作
     */
    private function moveUp($dealId){
        if(empty($dealId)){
            throw new \Exception("标ID不存在");
        }

        $current=$this->current($dealId);

        if(empty($current)){
            throw new \Exception("移动根节点不存在");
        }
        //如果操作的节点是头节点
        if($current['pre']==0){
            return true;
        }
        //查询操作的节点的前一个节点
        $pre=$this->pre($dealId);
        //修改前二个节点的后节点,节点为空,则不修改
        $res=$this->setNode($pre['pre'],false,$dealId);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改需要上移的节点的前后节点
        $res=$this->setNode($dealId,$pre['pre'],$current['pre']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改需要上移的节点的前一个节点的前后节点
        $res=$this->setNode($current['pre'],$dealId,$current['next']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改需要上移的节点的后一个节点的前节点,节点为空,则不修改
        $res=$this->setNode($current['next'],$current['pre']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        return true;
    }
    private function moveDown($dealId){

        if(empty($dealId)){
            throw new \Exception("标ID不存在");
        }
        $current=$this->current($dealId);
        if(empty($current)){
            throw new \Exception("移动节点不存在");
        }
        //如果操作的节点是尾节点
        if($current['next']==0){
            return true;
        }
        //查询操作的节点的前一个节点
        $next=$this->next($dealId);
        //更改需要下移的节点的前一个节点的后节点,节点为空,则不修改
        $res=$this->setNode($current['pre'],false,$current['next']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改需要下移的节点的前后节点
        $res=$this->setNode($dealId,$current['next'],$next['next']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改需要下移的节点的后一个节点的前后节点
        $res=$this->setNode($current['next'],$current['pre'],$dealId);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改需要下移的节点的后二个节点的前节点,节点为空,则不修改
        $res=$this->setNode($next['next'],$dealId);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        return true;
    }
    private function top($dealId){
        if(empty($dealId)){
            throw new \Exception("标ID不存在");
        }
        $current=$this->current($dealId);
        if(empty($current)){
            throw new \Exception("插队节点不存在");
        }
        //如果操作的节点是头节点
        if($current['pre']==0){
            return true;
        }
        $head=$this->head();
        //修改前节点，前节点为空，则不修改
        $res=$this->setNode($current['pre'],false,$current['next']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //修改后节点，如果后节点为空，则不修改
        $res=$this->setNode($current['next'],$current['pre']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改当前节点的前节点为0
        $res=$this->setNode($dealId,0,$head['deal_id']);
        if(empty($res)){
            throw new \Exception("修改节点失败");
        }
        //更改头节点的前节点为当前节点
        $res=$this->setNode($head['deal_id'],$dealId);
        if(empty($res)){
         throw new \Exception("修改节点失败");
        }
        return true;
    }

    /**
     * 重置队列
     */
    private function reset(){
        $dealIds = $this->getDealIdsByQueueId($this->queueId);
        if(!empty($dealIds)){
            $tail = 0;
            foreach ($dealIds as $dealId){
                $res=$this->setNode($dealId,$tail,0);
                if(empty($res)){
                    throw new \Exception("修改节点失败");
                }
                $res=$this->setNode($tail,false,$dealId);
                if(empty($res)){
                    throw new \Exception("修改节点失败");
                }
                $tail = $dealId;
            }
        }
        return true;
    }

    /**
     * 往队尾添加元素
     */
    private function jump($data){
        $dealIds = $data['dealIds'];
        $jumpId = $data['jumpId'];
        if(!empty($dealIds)){
            $jumpNode = $this->current($jumpId);
            if(empty($jumpNode)){
                throw new \Exception("要插队的节点不存在");
            }
            foreach ($dealIds as $dealId){
                $result = $this->current($dealId);
                if(!empty($result)){
                    throw new \Exception("添加节点已经存在");
                }
                $tailNode = $this->pre($jumpId);
                if(!empty($tailNode)){
                    $result = $this->setNode($tailNode['deal_id'],false,$dealId);
                    if(empty($result)){
                        throw new \Exception("修改节点失败");
                    }
                    $result = $this->setNode($dealId,$tailNode['deal_id'],$jumpId);
                    if(empty($result)){
                        throw new \Exception("修改节点失败");
                    }
                }else{
                    $result = $this->setNode($dealId,0,$jumpId);
                    if(empty($result)){
                        throw new \Exception("修改节点失败");
                    }
                }
                $result = $this->setNode($jumpId,$dealId);
                if(empty($result)){
                    throw new \Exception("修改节点失败");
                }
            }
        }
        return true;
    }

    /**
     *通过标ID和业务类型获取队列信息
     */
    public function getQueueInfoByDealId($dealId,$serviceType){
        $queueId=DealQueueInfosModel::instance()->getQueueIdByDealId($dealId,$serviceType);
        $queueInfo=array();
        if(empty($queueId)){
            return $queueInfo;
        }
        $queueInfo=DealQueuesModel::instance()->getById($queueId);
        return $queueInfo;
    }

    /**
     * 获取需要截标的标的信息
     */
     public function isDealSellOut(){
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,'start')));
        $effectQueue=DealQueuesModel::instance()->getSellOutQueues($this->serviceType);
        $preDeals=array();
        if(!empty($effectQueue)){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,'effectQueue'.json_encode($effectQueue))));
            $data=array('is_effect'=>0);
            foreach($effectQueue as $k => $v){
                $res=$this->updateQueue($data,$v['id']);
                if(empty($res)){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,'更新队列状态为无效失败，队列ID:'.$v['id'])));
                    return false;
                }
                $result=DealQueueInfosModel::instance()->getByPre(0,$v['id']);
                if(!empty($result['deal_id'])){
                    $preDeals[$k]['queue_name']=$v['name'];
                    $preDeals[$k]['deal_id']=$result['deal_id'];
                }
            }
        }
        return $preDeals;
     }

     /**
      *  发送短信
      */
      public function sendMessage($content,$key){
          Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $content, 'start')));
          \FP::import("libs.common.dict");
          $warn_list = \dict::get($key);
          foreach ($warn_list as $phoneNum) {
              if (is_numeric($phoneNum)) {
                  $rs = \SiteApp::init()->sms->send($phoneNum, $content, $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_EMAIL_QUEUE_WARN'], 0);
              }
          }
          Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $content, json_encode($rs))));
      }


}

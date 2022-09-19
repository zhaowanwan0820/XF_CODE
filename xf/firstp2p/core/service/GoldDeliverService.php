<?php
/**
 * 黄金提金
 * @data 2017.09.11
 * @author
 */


namespace core\service;

use core\tmevent\gold\UserDeliverEvent;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\dao\UserModel;
use core\dao\DealModel;
use core\tmevent\gold\DeliverEvent;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use core\service\MoneyOrderService;
use core\service\SupervisionFinanceService;
use core\service\P2pDealBidService;
use core\service\UserService;
use NCFGroup\Common\Library\Idworker;
use core\data\GoldUserData;
use core\exception\MoneyOrderException;

class GoldDeliverService extends GoldService{

    public static $fatal;

    private $goodsInfo;
    private $goodsAmount;
    private $userId;
    private $orderId;
    private $goodsDetails;

    public function __construct($userId='',$goodsAmount='',$goodsDetails='',$orderId=''){
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, $userId,$goodsAmount,$orderId,json_encode($goodsDetails))));
        $this->userId = $userId;
        //提金商品详情
        $this->goodsDetails=$goodsDetails;
        //提金数量
        $this->goodsAmount= $goodsAmount;
        $this->orderId = $orderId;
    }

    /**
     * 提金操作
     */
    public function doDeliver()
    {
        $response = array('errCode' => 0, 'msg' => '', 'data' => false);
        //验证信息
        try {
            $log_info = array(__CLASS__, __FUNCTION__,'userId:' . $this->userId, 'goodsAmount:' . $this->goodsAmount, 'orderId:' . $this->orderId, 'goodsDetails:' .json_encode($this->goodsDetails));
            Logger::info(implode(" | ", array_merge($log_info, array(' check start '))));
            self::$fatal = 1;
            $deliverData = new GoldUserData();
            $lock = $deliverData->enterPool($this->userId);
            if ($lock === false) {
                throw new \Exception('人数过多，请稍后再试');
            }
            register_shutdown_function(array($this, "errCatch"), $this->userId);
            $this->checkCanBid();
            $this->checkMoney();
            Logger::info(implode(" | ", array_merge($log_info, array(' check end '))));
        } catch (\Exception $e) {
            self::$fatal = 0;
            $deliverData->leavePool($this->userId);
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'userId:' . $this->userId, 'goodsAmount:' . $this->goodsAmount, 'orderId:' . $this->orderId, 'goodsDetails:' .json_encode($this->goodsDetails), "error:" . $e->getMessage())));
            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            return $response;
        }

        //提金相关
        try {

            //基于TM 的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('goldDeliver');
            $params = array(
                'orderId' =>$this->orderId,
                'userInfo' => $this->userInfo,
                'goodsDetails' =>$this->goodsInfo,
                'goodsAmount'=> $this->goodsAmount,
                'receiveFee' => $this->receiveFee
            );

            Logger::info(implode(" | ", array(__CLASS__,__FUNCTION__,'start','orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'])));
            //用户冻结资金
            $gtm->addEvent(new UserDeliverEvent($params));
            //冻结用户黄金
            $gtm->addEvent(new DeliverEvent($params));

            $deliverRes = $gtm->execute(); // 同步执行

            if($deliverRes === false){
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__, 'orderId:'.$this->orderId, 'userId:'.$this->userId, 'goodsAmount:'. $this->goodsAmount,  'goodsDetails:' .json_encode($this->goodsDetails),"msg:GTM事务处理失败")));
                throw new \Exception('提金失败');
            }
        } catch (\Exception $e) {
            self::$fatal = 0;
            $deliverData->leavePool($this->userId);
            Logger::error(implode('|',array(__CLASS__,__FUNCTION__,
                'orderId:'.$this->orderId,  'userId:'.$this->userId, 'goodsDetails' .json_encode($this->goodsDetails), 'receiveFee' . $this->receiveFee,
                "msg:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            return $response;
        }
        self::$fatal = 0;
        $deliverData->leavePool($this->userId);
        $deliverInfo=$this->getDeliverInfoByOrderId($this->orderId);
        Logger::info(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'userId:'.$this->userId, 'goodsAmount'.$this->goodsAmount,  'goodsDetails:' .json_encode($this->goodsDetails),'deliverInfo:'.json_encode($deliverInfo),"done ")));
        $data=array(
            'url'=>$deliverInfo['url'],
            'orderId'=>$deliverInfo['orderId']
        );
        $response['data']=$data;
        return $response;
    }

    public function errCatch($userId){
        $fatal = self::$fatal;
        if(!empty($userId) && !empty($fatal)){
            $deliverData = new GoldUserData();
            $deliverData->leavePool($userId);
            $lastErr = error_get_last();
            Logger::info("deliver err catch" ." lastErr: ". json_encode($lastErr) . " trace: ".json_encode(debug_backtrace()));
        }
    }
    /**
     * 投资操作
     * @param array $params
     * @throws \Exception
     * @return unknown
     */
    public function deliverEvent($params){
        try {
            $res = $this->deliver($params);
            if(empty($res)){
                throw new \Exception('提金失败');
            }
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'],"error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }
        Logger::info(implode(' | ',array(__CLASS__,__FUNCTION__,'orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'],'res:'.$res)));
        return true;
    }

    public function userDeliverEvent($params) {
        Logger::info(implode(" | ", array(__CLASS__,__FUNCTION__,' start ','orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'])));
        $userInfo = $params['userInfo'];
        $receiveFee= $params['receiveFee'];
        $msg = "订单号{$params['orderId']}";
        try {
            $GLOBALS['db']->startTrans();

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::DELIVERGOLDFEELOCK, $receiveFee, "提金手续费冻结", $msg, userModel::TYPE_LOCK_MONEY);

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,'orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'], "error:".$e->getMessage())));
            $GLOBALS['db']->rollback();
            //changeMoney捕获到订单已经存在的情况下，返回true,GTM 重试导致异常情况
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
                return true;
            }
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }

        Logger::info(implode(" | ", array(__CLASS__,__FUNCTION__,' done ','orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'])));
    }

    /**
     * 提金对用户操作回滚 资金冻结回滚
     */
    public function userDeliverRollbackEvent($params){

        $log_info = array(__CLASS__,__FUNCTION__,'orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee']);
        Logger::info(implode(" | ", array_merge($log_info, array(' start '))));
        //更改资金记录
        $userInfo = $params['userInfo'];
        $receiveFee= $params['receiveFee'];
        try {
            $GLOBALS['db']->startTrans();
            $msg = "订单号{$params['orderId']}";

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::DELIVERGOLDFEERELEASELOCK, -$receiveFee, "提金手续费解冻", $msg, userModel::TYPE_LOCK_MONEY);

            $GLOBALS['db']->commit();
            return true;
        }
        catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,'orderId:'.$params['orderId'],'goodsDetails:'.json_encode($params['goodsDetails']),'goodsAmount:'.$params['goodsAmount'],'receiveFee:'.$params['receiveFee'], "error:".$e->getMessage())));
            $GLOBALS['db']->rollback();
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
                return true;
            }
            return false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array(' done '))));
    }

    public function deliver($params){

        $log_info = array(__CLASS__,__FUNCTION__);

        $orderId = $params['orderId'];
        $userInfo = $params['userInfo'];
        $goodsAmount = $params['goodsAmount'];
        $goodsDetails = $params['goodsDetails'];
        $receiveFee = $params['receiveFee'];
        $idno=idnoNewFormat($userInfo['idno']);
        $request = new RequestCommon();
        $data = array(
            'orderId'=>$orderId,
            'userId'=>$userInfo['id'],
            'userName'=>$userInfo['user_name'],
            'idno'=>$idno,
            'realName'=>$userInfo['real_name'],
            'mobile'=>$userInfo['mobile'],
            'receiveFee'=>$receiveFee,
            'goodsAmount'=>$goodsAmount,
            'goodsDetails' => $goodsDetails,
        );
        Logger::info(implode(" | ", array_merge($log_info, array(' start ',json_encode($data)))));
        $request->setVars($data);
        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver', 'doDeliver', $request);
        if(!empty($response['errCode'])){
            throw new \Exception($response['errMsg']);
        }
        Logger::info(implode(" | ", array_merge($log_info, array(' done ',json_encode($data)))));
        return $response['data'];
    }

    /**
     * 更新黄金提金订单状态
     * @param int $orderId
     * @param string $outerOrderId
     * @param int $status
     * @return boolean
     */
    function updateOrderStatus($orderId,$outerOrderId,$status = 0){
        $request = new RequestCommon();
        $request->setVars(array("orderId"=>$orderId,"outerOrderId"=>$outerOrderId,"status"=>$status));
        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver','updateOrderStatus', $request);
        return $response;
    }

    public function checkCanBid(){
        $this->checkFirst();
        $this->checkFee();
        //检查商品是否有效
        $this->checkGoodsEffect();
        //检查数量是否正确
        $this->checkGoldAmount();
        //检查用户信息
        $this->checkUser();
    }

    /**
     * 验证提金手续费
     */
    protected function checkFee(){
        $response=$this->getDealCurrent();
        if(empty($response) ){
            throw new \Exception('获取提金手续费失败');
        }
        $this->fee = $response['receiveFee'];
        if(bccomp($this->fee,0,2) < 0){
            throw new \Exception('手续费不能为负');
        }
    }


    /**
     *检查商品是否有效
     */
    protected function checkGoodsEffect(){

        $result = $this->getGoodsByIds(array_keys($this->goodsDetails));
        if(empty($result['data'])){
            throw new \Exception('商品无效');
        }
        $goodsCount=0;
        $money=0;
        foreach ($this->goodsDetails as $key => $value){
            if(!isset($result['data'][$key]) || $result['data'][$key]['isEffect'] == 0){
                throw new \Exception('商品无效');
            }
            $goods=bcmul($result['data'][$key]['size'],$value);
            $goodsCount=bcadd($goodsCount,$goods);
            //如果该种类型金条的提金手续费为0，则取全局的手续费
            $fee=($result['data'][$key]['fee']==0)?$this->fee:$result['data'][$key]['fee'];
            $fees=bcmul($goods,$fee,4);
            $money=bcadd($fees,$money,4);
            $this->goodsInfo[] = array("model"=>$result['data'][$key]['size'],'amount'=>$value,'unit'=>'克','goodName'=>$result['data'][$key]['name']);
        }
        if(bccomp($goodsCount,$this->goodsAmount)!=0){
            throw new \Exception('商品数量不正确');
        }
        $this->receiveFee =floorfix($money,2);
    }

    /**
     * 验证参数
     */
    protected function checkFirst(){
        if(empty($this->userId)){
            throw new \Exception('用户id不能为空');
        }

        if(bccomp($this->goodsAmount,0,3) <= 0){
            throw new \Exception('提金数量不能为0');
        }

        if(empty($this->goodsDetails)){
            throw new \Exception('商品信息不能为空');
        }
    }

    /**
     * 检查用户提金数量
     */
    protected function checkGoldAmount(){
        $gold=$this->getGoldMasterByUserId($this->userId);
        $goodsAmount=$this->goodsAmount;
        if(bccomp($goodsAmount,$gold)>0){
            throw new \Exception('您的可提取克重不足，请修改提金克重');
        }
    }

    /**
     * 验证用户信息
     * @param array $userInfo
     */
    protected function checkUser(){
        $this->userInfo = (new UserModel())->find($this->userId);
        if(empty($this->userInfo)){
            throw new \Exception('用户不存在');
        }
    }

    /**
     * 验证用户账户余额，并尝试划转
     */
    protected function checkMoney(){
        if(empty($this->orderId)){
            throw new \Exception('订单ID不能为空');
        }
        $this->moneyInfo = $this->getMoneyInfo();
        $totalCanBidMoney = $this->moneyInfo['lc'];
        if((bccomp($this->receiveFee,$totalCanBidMoney,2) == 1)){
            throw new \Exception('余额不足，请充值');
        }
    }

    /**
     * 获取用户账户余额，如果大账户余额不够，需要从存管账转钱过来
     */
    protected function getMoneyInfo(){

        $sfService = new SupervisionFinanceService();
        $isNeedTip = $sfService->isPromptTransfer($this->userInfo['id']);

        if(!$isNeedTip){
            $p2pDealBidService = new P2pDealBidService();
            $bonusInfo = (new \core\service\BonusService())->getUsableBonus($this->userInfo['id'], false, 0,false);
            $transferMoney = $p2pDealBidService->needTransferMoney($this->userInfo,(new DealModel()),bcadd($this->receiveFee,$bonusInfo['money'],2));//黄金提金不能使用红包，计算划转金额的方法又把红包金额计算在内了，所有把需要划转的金额在加进去红包金额
            // 3、资金划转
            if($transferMoney && bccomp($transferMoney,'0.00',2) ==1){
                $transferOrderId = Idworker::instance()->getId();
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP,"userId:{$this->userId} 开始资金划转 金额：{$transferMoney}")));
                $transferRes = $p2pDealBidService->moneyTransfer($transferOrderId,$this->userInfo['id'],$transferMoney,false);
                if(!$transferRes){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $this->userId, $this->receiveFee, "资金划转失败 , transFerOrderId:".$transferOrderId)));
                    throw new \Exception("大账户余额不足，资金划转失败");
                }
                // 此处需要重新获取下用户信息,因为在资金划转后用户资金已经发生变化了
                $this->userInfo = UserModel::instance()->find($this->userId);
            }
        }

        $moneyInfo = (new UserService())->getMoneyInfo($this->userInfo ,$this->receiveFee,$this->orderId);
        return $moneyInfo;
    }

}

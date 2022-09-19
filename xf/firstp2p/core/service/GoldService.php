<?php
/**
 * 黄金项目service
 * @data 2017.05.16
 * @author zhaohui zhaohui3@ucfgroup.com
 */


namespace core\service;

use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\utils\Rpc;
use core\service\GoldDealService;
use core\service\ApiConfService;

class GoldService
{
    /**
     * 获取黄金标的列表
     * @param unknown $pageSize
     * @param unknown $pageNum
     * @return array
     */
    public function getDealList($pageSize, $pageNum)
    {
        $request = new RequestCommon();
        $request->setVars(array('pageSize' => $pageSize, 'pageNum' => $pageNum,'status'=>array(1,2,4,5)));
        $res = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getList', $request);
        if ($res['errCode'] != 0) {
            return $res;
        }
        $result = array();
        $result['totalPage'] = $res['data']['totalPage'];
        $result['totalNum'] = $res['data']['totalNum'];
        $result['list'] = array();
        //黄金售罄开关 1在售  0 售罄
        $switch = app_conf('GOLD_SALE_SWITCH');
        foreach ($res['data']['data'] as $key => $value) {
            $result['list'][$key]['id'] = $value['id'];
            $result['list'][$key]['deal_status'] = ($switch == 1) ? $value['dealStatus'] : 2;
            $result['list'][$key]['tag'] = $value['dealTagName'];
            $result['list'][$key]['tagNames'] = empty($value['dealTagName'])? array() : explode(',', $value['dealTagName']);
            $result['list'][$key]['gold_unit'] = '克/100克';
            $result['list'][$key]['period_unit'] = ($value['loantype'] == 5) ? '天' : '个月';
            $result['list'][$key]['name'] = $value['name'];
            $result['list'][$key]['annual_comp_rate'] = floorfix($value['rate'],3,6);
            $result['list'][$key]['period'] = $value['repayTime'];
            $result['list'][$key]['invest_quality'] = number_format($value['minLoanMoney'],3);
            $result['list'][$key]['total_quality'] = number_format(floorfix($value['borrowAmount'],3,6),3);
            $result['list'][$key]['usable_quality'] = number_format(floorfix($value['borrowAmount'] - $value['loadMoney'],3,6),3);
            $result['list'][$key]['point_percent'] = $value['pointPercent'];
        }
        return $result;
    }

    /**
     * 获取理财首页显示的两个标的
     * @param unknown $status
     * @param unknown $count
     * @return unknown|Ambigous <multitype:NULL , unknown>
     */
    public function getP2pDealList($status, $count)
    {
        $request = new RequestCommon();
        $request->setVars(array('status' => $status, 'count' => $count));
        $res = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealistByStatus', $request);
        $result = array();
        $result['list'] = array();
        //黄金售罄开关 1在售  0 售罄
        $switch = app_conf('GOLD_SALE_SWITCH');
        if ($res['errCode'] == 0 && !empty($res['data'])){
            foreach ($res['data'] as $key => $value) {
                $result['list'][$key]['id'] = $value['id'];
                $result['list'][$key]['deal_status'] = ($switch == 1) ? $value['dealStatus'] : 2;
                $result['list'][$key]['tag'] = $value['dealTagName'];
                $result['list'][$key]['tagNames'] = empty($value['dealTagName'])? array() : explode(',', $value['dealTagName']);
                $result['list'][$key]['gold_unit'] = '克/100克';
                $result['list'][$key]['period_unit'] = ($value['loantype'] == 5) ? '天' : '个月';
                $result['list'][$key]['name'] = $value['name'];
                $result['list'][$key]['annual_comp_rate'] = number_format($value['rate'],3);
                $result['list'][$key]['period'] = $value['repayTime'];
                $result['list'][$key]['invest_quality'] = number_format(floorfix($value['minLoanMoney'],3,6),3);
                $result['list'][$key]['total_quality'] = number_format(floorfix($value['borrowAmount'],3,6),3);
                $result['list'][$key]['usable_quality'] = number_format(floorfix($value['borrowAmount'] - $value['loadMoney'],3,6),3);
                $result['list'][$key]['point_percent'] = $value['pointPercent'];
            }
        }
        return $result;
    }

    /**
     * 获取慢标的标id
     * @return array
     */
    public function getLoanDealIds(){
        $request = new RequestCommon();
        $endTime  = strtotime(date("Y-m-d",time()));
        $request->setVars(array('status' => 4, 'count' => 100000,'startTime'=>$endTime-86400,'endTime'=>$endTime));
        $res = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealistByStatus', $request);
        $dealIds = array();
        if ($res['errCode'] == 0 && !empty($res['data'])) {
            foreach ($res['data'] as $value) {
                $dealIds[] = $value['id'];
            }
        }
        return $dealIds;
    }

    /**
     * 获取我的黄金页面数据
     * @param RequestCommon $request
     * @return unknown
     */
    public function myGold(RequestCommon $request)
    {
        $response = $this->requestGold('NCFGroup\Gold\Services\UserStats', 'getUserGoldInfo', $request,3,40,10);
        return $response;
    }


    /**
     * 根据标id获取标信息
     * @param intval $deal_id
     * @param intval $slave 1 从库 0 主库
     */
    public function getDealById($deal_id, $slave = 1)
    {
        $request = new RequestCommon();
        $request->setVars(array('deal_id' => intval($deal_id), 'slave' => intval($slave)));
        $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealById', $request);
        return $response;
    }

    /**
     * 黄金标打戳专用
     * @param array $deal_id
     * @return StdClass|boolean
     */
    public function getDealForTsaById($deal_id){
        $response = $this->getDealById($deal_id);
        if($response['errCode'] != 0 and !empty($response['data'])){
            $response['data']['dealType'] = 100;
            $response['data']['deal_type'] = 100;
            return (object) $response['data'];
        }
        return false;
    }

    /**
     * 获取用户单个标累计购买克重
     * @param int $userId
     * @param int $dealId
     */
    public function getUserLoadGoldByDealid($userId,$dealId){
        $request = new RequestCommon();
        $data = array(
            'deal_id' => $dealId,
            'user_id' => $userId
        );
        $request->setVars($data);

        $response = $this->requestGold('NCFGroup\Gold\Services\DealLoad','getUserLoadGoldByDealid',$request);

        return $response;
    }

    /**
     * 获取实时金价
     * @param RequestCommon $request
     * @return array
     */
    public function getGoldPrice($iscurrent = false)
    {
        $request = new RequestCommon();
        $request->setVars(array('iscurrent'=>$iscurrent));
        $response = $this->requestGold('NCFGroup\Gold\Services\GoldApi', 'currentGoldPrice', $request);
        return $response;
    }

    /**
     * 交易记录
     * @param unknown $userId
     * @param unknown $pageSize
     * @param unknown $pageNum
     * @return boolean|unknown
     */
    public function getTradList($userId, $pageSize, $pageNum, $logInfo)
    {
        if (empty($userId)) {
            return false;
        }
        $pageSize = $pageSize ? intval($pageSize) : '';
        $pageNum = $pageNum ? intval($pageNum) : '';
        $logInfo = $logInfo ? $logInfo : '';
        $request = new RequestCommon();
        $request->setVars(array('userId' => $userId, 'pageSize' => $pageSize, 'pageNum' => $pageNum, 'logInfo' => $logInfo));
        $res = $this->requestGold('NCFGroup\Gold\Services\User', 'getGoldUserLogList', $request);
        $data = array();
        $data['errCode'] = $res['errCode'];
        $data['errMsg'] = $res['errMsg'];
        if ($res['errCode'] == 0) {
            $data['data']['totalPage'] = $res['data']['totalPage'];
            $data['data']['totalNum'] = $res['data']['totalNum'];
            $data['data']['logType'] = $res['data']['logType'];
            foreach ($res['data']['data'] as $key => $value) {
                $data['data']['data'][$key]['id'] = $value['id'];
                $data['data']['data'][$key]['date'] = date("Y年n月j日", $value['logTime']);
                $data['data']['data'][$key]['type'] = $value['logInfo'];
                $data['data']['data'][$key]['gold'] = number_format($value['gold'],3);
                $data['data']['data'][$key]['label'] = $value['label'];
                $data['data']['data'][$key]['remainGold'] = number_format(floorfix($value['remainingTotalMoney'],3,6),3);
                $data['data']['data'][$key]['lockMoney'] = number_format($value['lockMoney'],2);
            }
            //$data['data']['data'] = $data['data'][$key]['label'];
        }
        return $data;
    }

    /**
     * 交易详情
     * @param unknown $userId
     * @param unknown $id
     * @return boolean|multitype:unknown
     */
    public function getTradDetail($userId, $id)
    {
        if (empty($userId) || empty($id)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('userId' => intval($userId), 'id' => intval($id)));
        $res = $this->requestGold('NCFGroup\Gold\Services\User', 'getGoldUserLogInfo', $request);
        $response = array();
        $response['errCode'] = $res['errCode'];
        $response['errMsg'] = $res['errMsg'];
        if ($res['errCode'] == 0) {
            $response['data']['fee'] = $res['data']['fee'];
            $response['data']['date'] = date("Y-m-d H:i:s", $res['data']['logTime']);
            $response['data']['type'] = $res['data']['logInfo'];
            $response['data']['gold'] = number_format($res['data']['gold'],3);
            $response['data']['note'] = $res['data']['note'];
            $response['data']['money'] = number_format($res['data']['money'],2);
            $response['data']['deal_load_id'] = $res['data']['dealLoadId'];
        }
        //获取成交价和手续费
        $request = new RequestCommon();
        $request->setVars(array('id' => intval($response['data']['deal_load_id'])));
        if ($res['data']['dealType'] == 1) {//读优金宝的记录
            $resDealLoad = $this->requestGold('NCFGroup\Gold\Services\DealLoadCurrent', 'getDealLoadById', $request);
        } elseif ($res['data']['dealType'] != 1 && $response['data']['deal_load_id'] != 0){//读优长今的记录
            $resDealLoad = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadById', $request);
        }

        if (isset($resDealLoad['data']) && $resDealLoad['errCode'] == 0) {
           $response['data']['fee'] = number_format($resDealLoad['data']['fee'],2) .'元';
           $response['data']['money'] = number_format($resDealLoad['data']['buyPrice'],2) .'元/克';
        }

        //如果查不到的购买记录，那么说明这条购买记录是失败的，前端展示的黄金克重变为0，文案显示买金失败
        if($res['data']['logInfo'] == "买金"){
            if(!isset($resDealLoad['data']) || empty($resDealLoad['data'])){
                $response['data']['gold'] = number_format(0,3);
                $response['data']['note'] = $res['data']['note']."，购金失败";
            }
        }

        //获取黄金变现相关的成交金价和手续费
        $withdrawLogInfo = array('change' => '黄金变现','freeze' => '黄金变现冻结');
        if (array_search($res['data']['logInfo'],$withdrawLogInfo)) {
            $patterns = "/\d+/";
            preg_match($patterns,$res['data']['note'],$arr);
            $request->setVars(array('orderId' => $arr['0']));
            $resOrderId = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'getWithdrawByOrderId', $request);
            $response['data']['fee'] = ($resOrderId['errCode'] == 0) ? (number_format($resOrderId['data']['fee_money'],2) .'元') : -1;
            $response['data']['money'] = ($resOrderId['errCode'] == 0) ? (number_format($resOrderId['data']['gold_price'],2).'元/克' ) : -1;
        }
        //获取黄金变现相关的成交金价和手续费end
        return $response;
    }

    /**
     * 获取存管量
     */
    public function sellAmount(){
        $request = new RequestCommon();
        $loadAmount=$this->requestGold('NCFGroup\Gold\Services\DealCurrent','getLoadAmount', $request);
        return $loadAmount;
    }

    /**
     * 获取特殊标的结息黑名单
     */
    public function getSpecialDealBlackList(){
        $request = new RequestCommon();
        $result=$this->requestGold('NCFGroup\Gold\Services\DealRepay','getBlackListUserIds', $request);
        return $result['data'];
    }

    /**
     * 授权方法
     * @param unknown $userId
     * @return boolean
     */
    public function Auth($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('user_id' => intval($userId)));
        return $this->requestGold('NCFGroup\Gold\Services\UserAuthorized', 'agreeAuthorized', $request);
    }

    /**
     * 检查是否授权
     * @param unknown $userId
     * @return boolean
     */
    public function isAuth($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('user_id' => intval($userId)));
        return $this->requestGold('NCFGroup\Gold\Services\UserAuthorized', 'checkIsAuthorized', $request);
    }

    /**
     * 获取购买记录详情
     * @param unknown $userId
     * @param unknown $pageSize
     * @param unknown $pageNum
     * @param unknown $type
     * @return boolean
     */
    public function getPurchaseDetail($userId, $pageSize, $pageNum, $type)
    {
        if (empty($userId)) {
            return false;
        }
        $pageSize = intval($pageSize) ? intval($pageSize) : 0;
        $pageNum = intval($pageNum) ? intval($pageNum) : 0;
        $request = new RequestCommon();
        $request->setVars(array('userId' => intval($userId), 'pageSize' => $pageSize, 'pageNum' => $pageNum, 'status' => $type));
        $resopne = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadDetailListByUserId', $request,3,40,10);
        return $resopne;
    }

    /**
     * 获得用户投资详情
     * @param unknown $dealLoadId
     * @param unknown $userId
     * @return boolean
     */
    public function getDealLoadDetail($dealLoadId, $userId)
    {
        if (empty($userId) || empty($dealLoadId)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('userId' => intval($userId), 'id' => intval($dealLoadId)));
        return $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadDetailByUserId', $request);
    }

    /**
     * @param 获取用户投标记录
     * @param $dealId  标ｉｄ
     * @param $IsFull = false　是否全部显示
     *
     */

    public function getDealLog($dealId, $IsFull = false)
    {
        if (empty($dealId)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('dealId' => $dealId, 'isFull' => $IsFull));
        $res = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadList', $request);
        $data = array();
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return $data;
        }
        if(!empty($res['data']['data'])){
            foreach ($res['data']['data'] as $key => $value) {
                $data['data'][$key]['id'] = $value['id'];
                $data['data'][$key]['userId'] = $value['userId'];
                $data['data'][$key]['userDealName'] = $value['userDealName'];
                $data['data'][$key]['buyAmount'] = number_format($value['buyAmount'],3);
                $data['data'][$key]['fee'] = $value['fee'];
                $data['data'][$key]['money'] = $value['money'];
                $data['data'][$key]['createTimeHis'] = date('Y-m-d H:i:s', $value['createTime']);
                $data['data'][$key]['createTime'] = date('Y-m-d', $value['createTime']);
            }
        }
        return $data;
    }
    /**
     * 获取活期用户投标记录
     */
    public function getDealCurrentLog()
    {
        $request = new RequestCommon();
        $res = $this->requestGold('NCFGroup\Gold\Services\DealLoadCurrent', 'getDealLoadList', $request);
        $data = array();
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return $data;
        }
        if(!empty($res['data']['data'])){
            foreach ($res['data']['data'] as $key => $value) {
                $data['data'][$key]['userDealName'] = $value['userDealName'];
                $data['data'][$key]['buyAmount'] = number_format($value['buyAmount'],3);
                $data['data'][$key]['createTimeHis'] = date('Y-m-d H:i', $value['createTime']);
            }
        }
        return $data;
    }

    /**
     * 判断用户是否投资了某个标的
     * @param unknown $userId
     * @param unknown $dealId
     * @return boolean|unknown
     */
    public function isUserDealLoad($userId,$dealId) {
        if (empty($dealId) || empty($userId)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('dealId' => $dealId, 'userId' => $userId));
        $res = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'isExist', $request);
        return $res;
    }
    /**
     * 判断用户是否投资
     * @param unknown $userId
     * @return boolean|unknown
     */
    public function isUserDealLoadByUserId($userId) {
        if (empty($userId)) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array( 'userId' => $userId));
        $res = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'isExist', $request);
        if (empty($res)) {
            return false;
        }
        return true;
    }
    /**
     * 黄金维护开关，1是开启，0是关闭
     */
    public function isGoldSaleSwitchOpen()
    {
        return (int)app_conf('GOLD_SALE_SWITCH');
    }
    /**
     *  GOLD_SALE_CURRENT_SWITCH 活期黄金在售开关 1为开关关闭
     */
    public function isSell($userId)
    {
        $res=array('errCode'=>0,'errMsg'=>'','data'=>false);
        if ((int)app_conf('GOLD_SALE_CURRENT_SWITCH')!=1) {
            return $res;
        }
        $request = new RequestCommon();
        $request->setVars(array('userId' => $userId));
        $data = array();
        $data = $this->requestGold('NCFGroup\Gold\Services\User', 'getGoldByUserId', $request);
        if ($data['errCode'] != 0) {
            return $data;
        }
        if ((double)$data['data'] <= 0) {
            return $res;
        }
        $res['data']=true;
        return $res;
    }
    /**
     * GOLD_SALE_CURRENT_USERID 活期黄金在售用户白名单开关 开关无值则所有用户均可以购买
     * 开关有值则只有在白名单的用户可以购买，其余用户显示售罄
     */
    public function  isSellByUserId($userId){
        $allUserId=app_conf('GOLD_SALE_CURRENT_USERID');
        $oneUserId=array();
        $oneUserId=explode(',',$allUserId);
        if(in_array($userId,$oneUserId)){
            return true;
        }
        return false;
    }

    /**
     * 根据标获取投资记录
     * @param int $dealId
     * @return boolean|unknown
     */
    public function getDealLoadByDealId($dealId){
        $request = new RequestCommon();
        $request->setVars(array('id' => $dealId));
        $res = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadByDealId', $request);

        if ($res['errCode'] != 0 || empty($res['data'])) {
            return false;
        }
        return $res['data'];
    }

    /**
     *  用户是否有借款
     * @param $userId
     * @return bool
     *
     */
    public function isHavedLoanByuserId($userId){
        $request = new RequestCommon();
        $request->setVars(array('user_id' => $userId));

        $res = $this->requestGold('NCFGroup\Gold\Services\Deal', 'isHavedLoanByuserId', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return false;
        }

        return true;
    }
    /**
     * 获取优金宝首页显示数据
     * @return boolean|array
     */
    public function getDealCurrent(){
        $request = new RequestCommon();
        $res = $this->requestGold('NCFGroup\Gold\Services\DealCurrent', 'getInfo', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return false;
        }
        unset($res['data']['note']);
        return $res['data'];
    }
    /**
     * 用户变现信息
     * @param unknown $userId
     * @return Ambigous <boolean, \NCFGroup\Common\Extensions\RPC\mixed, NULL>
     */
    public function getUserWithrawInfo($userId) {
        $request = new RequestCommon();
        $request->setVars(array('userId' => $userId));
        $res = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'getUserWithrawInfo', $request);
        return $res;
    }
    /**
     * 用户变现申请
     * @param unknown $userId
     * @param unknown $orderId 订单号
     * @param unknown $gold 变现克重
     * @param unknown $maxWithdrawGoldPerDay 每日最大变现克重
     * @param unknown $goldPrice 金价
     * @param unknown $goldPriceRate 浮动利率 ;
     * @param $withdrawMinFee 单笔变现最低手续费
     * @return Ambigous <boolean, \NCFGroup\Common\Extensions\RPC\mixed, NULL>
     */
    public function withdrawApply($userId,$orderId,$gold,$maxWithdrawGoldPerDay,$goldPrice,$goldPriceRate,$withdrawMinFee) {
        $request = new RequestCommon();
        $request->setVars(array('userId' => $userId,'orderId' => $orderId,'gold' => $gold,'maxWithdrawGoldPerDay' => $maxWithdrawGoldPerDay,'goldPrice'=>$goldPrice,'goldPriceRate'=>$goldPriceRate,'withdrawMinFee'=>$withdrawMinFee));
        $res = $this->requestGold('NCFGroup\Gold\Services\Withdraw', 'withdrawApply', $request);
        return $res;
    }
    public function requestGold($service, $method, $request ,$maxRetryTimes=3,$timeout=10,$connectTimeout=10)
    {
        $beginTime = microtime(true);
        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        Logger::info("[req]GoldService.{$className}.{$method}:" . json_encode($request, JSON_UNESCAPED_UNICODE));

        try {
            $rpc = new Rpc('goldRpc');
            $response = $rpc->go($service,$method,$request,$maxRetryTimes,$timeout,$connectTimeout);
        } catch (\Exception $e) {
            $exceptionName = get_class($e);
            \libs\utils\Alarm::push('gold_rpc_exception', $className.'_'.$method,
                    'request: '.json_encode($request, JSON_UNESCAPED_UNICODE).',ename:' .$exceptionName. ',msg: '.$e->getMessage());
            Logger::error("GoldService.$service.$method.$exceptionName:" . $e->getMessage());
            throw $e;
        }
        // TODO log response
        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($res == false) {
            \libs\utils\Alarm::push('gold_rpc_exception', $className.'_'.$method,'request: '.'time-out');
        }
        $res = ($res == false) ? 'invalid response: ' . var_export($response, true) : mb_substr($res, 0, 1000);
        $elapsedTime = round(microtime(true) - $beginTime, 3);
        Logger::info("[resp][cost:{$elapsedTime}]GoldService.{$className}.{$method}:" . $res);
        return $response;
    }

    /**
     * 获取用户收益明细
     * @param unknown $userId
     * @param unknown $pageSize
     * @param unknown $pageNo
     * @return boolean|unknown
     */
    public function getCurrentInterestLog($userId, $pageNo, $pageSize){
        $request = new RequestCommon();
        $request->setVars(array('user_id' => intval($userId), 'pageSize' => $pageSize, 'pageNum' => $pageNo));
        $res = $this->requestGold('NCFGroup\Gold\Services\CurrentInterestLog', 'getList', $request);

        $interestLog = array();
        $interestLog['errCode'] = $res['errCode'];
        $interestLog['errMsg'] = $res['errMsg'];
        if ($res['errCode'] != 0) {
            return $interestLog;
        }
        $data = array();
        $monthLog = array();
        foreach ($res['data']['data'] as $k => $v){
            $data['time'] = date('m-d', $v['logTime']);
            $data['month'] = date('Y年m月', $v['logTime']);
            //获取每个月的总收益
            if (!isset($monthLog[$data['month']])) {
                $firstDay = date('Y-m', $v['logTime']);
                $dateStartTmp = strtotime($firstDay);//获取当月第一天0:0:0时间
                $endDay = $firstDay.'-'.date('t',$dateStartTmp).' '.'23:59:59';
                $dateEndTmp = strtotime($endDay);//获取当月第一天23:59:59时间
                $request->setVars(array('userId' => intval($userId),'startTime' => $dateStartTmp,'endTime' => $dateEndTmp ));
                $totalMoney = $this->requestGold('NCFGroup\Gold\Services\CurrentInterestLog', 'getTotalInterestByUserIdTime', $request);
                $data['totalMoney'] = ($totalMoney['errCode'] == 0) ? number_format($totalMoney['data'],2).'元' :  -1;
                $monthLog[$data['month']] = $data['month'];
            }
            $interest = number_format(floorfix($v['interest'],2,6), 2);
            $data['interest'] = $interest.'元';
            $interestLog['list'][] = $data;
        }
        return $interestLog;
    }

    /**
     * 获取黄金资产
     * @param intval $userId
     * @return float
     */
    public function getGoldByUserId($userId){
        $request = new RequestCommon();
        $request->setVars(array('userId' => intval($userId)));
        $response = $this->requestGold('NCFGroup\Gold\Services\User', 'getGoldByUserId', $request);
        return $response['data'];
    }

    /**
     * 获取黄金资产,查主库
     * @param intval $userId
     * @return float
     */
    public function getGoldMasterByUserId($userId){
        $request = new RequestCommon();

        $request->setVars(array('userId' => intval($userId)));
        $response = $this->requestGold('NCFGroup\Gold\Services\User', 'getGoldMasterByUserId', $request);
        return $response['data'];
    }

    /**
     * 获取黄金资产详细记录
     * @param int $userId
     * @return array
     */
    public function getGoldInfoByUserId($userId) {
        $request = new RequestCommon();
        $request->setVars(array('userId' => (int)$userId));
        $res = $this->requestGold('NCFGroup\Gold\Services\GoldCharge', 'getGoldInfoByUserId', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return [];
        }
        return (array)$res['data'];
    }

    /**
     *通过用户id获取用户信息
     */
    public function getUserInfoByUserId($userId){
        $request = new RequestCommon();
        $request->setVars(array('userId' => (int)$userId));
        $res = $this->requestGold('NCFGroup\Gold\Services\User', 'getGoldInfoByUserId', $request);
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,'res : '.json_encode($res))));
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return [];
        }
        return $res['data'];
    }

    public function isWhite($userId){
        $userId = intval ($userId);
        $switch=intval(app_conf('GOLD_SALE_WHITE_USER'));
        $apiConfService = new ApiConfService();
        //黄金 api开关 关闭时仅白名单用户可见
        $feature_gold = $apiConfService -> getGoldSwitchStatus();
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,'feature_gold : '.$feature_gold,'switch : '.$switch)));
        // 黄金开关关,白名单开关开
        if( !$feature_gold && $switch==1 ){
            if(!empty($userId)){
                $result=$this->getUserInfoByUserId($userId);
                if(!empty($result)){
                    $isWhite = intval($result['isWhite'])==1 ? true :false;
                    return $isWhite;
                }
            }
            return false;
        }elseif( !$feature_gold && $switch==0 ){
            return false;
        }else{
            return true ;
        }
    }

    /**
     * 获取用户资产总额
     * @param int $userId
     * @return Ambigous <number, string>
     */
    public function getUserGoldAssets($userId){
        $goldAssets = 0;
        $userInfo = $this->getGoldInfoByUserId($userId);
        if(!empty($userInfo)){
            $goldAssets = bcadd($userInfo['gold'],$userInfo['lockGold'],5);
        }
        return $goldAssets;
    }

    /**
     * 获取优金宝详情
     */
    public function getInfo(){
        $request = new RequestCommon();
        $res = $this->requestGold('NCFGroup\Gold\Services\DealCurrent', 'getInfo', $request);

        $interestLog = array();
        if ($res['errCode'] != 0) {
            $interestLog['errCode'] = $res['errCode'];
            $interestLog['errMsg'] = $res['errMsg'];
            return $interestLog;
        }

        $interestLog['rate'] = floorfix($res['data']['rate'],2);
        $interestLog['minBuyAmount'] =floorfix($res['data']['minBuyAmount'],3);
        $interestLog['loadAmount'] = number_format(floorfix($res['data']['loadAmount'],3),3);
        $interestLog['buyerFee'] = floorfix($res['data']['buyerFee']);
        $interestLog['withdrawFee'] = floorfix($res['data']['withdrawFee']);
        $interestLog['receiveFee'] = floorfix($res['data']['receiveFee']);
        $interestLog['note'] = $res['data']['note'];
        $goldPrice = $this->getGoldPrice();
        $interestLog['goldPrice'] = floorfix($goldPrice['data']['gold_price']);
        return $interestLog;
    }

    /**
     * 获取当前日期内购买的黄金活期总额
     * @param string $date
     * @return float
     */
    public function getTotalAmountByDate($userId,$date){
        $request = new RequestCommon();
        $request->setVars(array('userId' => intval($userId),'date'=>$date));
        $response = $this->requestGold('NCFGroup\Gold\Services\DealLoadCurrent', 'getTotalAmountByDate', $request);
        return $response['data'];
    }


    /**
     * 通过订单id获取活期投资记录
     * @param unknown $orderId
     */
    public function getDealLoadCurrentByOrderId($orderId){
        $request = new RequestCommon();
        $request->setVars(array('orderId' => $orderId));
        $response = $this->requestGold('NCFGroup\Gold\Services\DealLoadCurrent', 'getDealLoadByOrderId', $request);
        return $response['data'];
    }

    /**
     * 通过订单id获取提金记录
     * @param unknown $orderId
     */
    public function getDeliverInfoByOrderId($orderId){
        $request = new RequestCommon();
        $request->setVars(array('orderId' => $orderId));
        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver', 'getDeliverInfoByOrderId', $request);
        return $response['data'];
    }

    /**
     * 更新/冻结黄金余额、资金记录
     * @param array $params
     * @return boolean
     */
    public function changMoney($params) {
        if (empty($params['userId']) || empty($params['gold']) || empty($params['message']) || empty($params['note'])) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars($params);
        $res = $this->requestGold('NCFGroup\Gold\Services\User', 'changMoney', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return false;
        }
        return $res['data'];
    }

    /**
     * 获取标的商户等配置信息
     * @return multitype:|array
     */
    public function getDealCurrentInfo() {
        $request = new RequestCommon();
        $res = $this->requestGold('NCFGroup\Gold\Services\DealCurrent', 'getDealCurrentInfo', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return [];
        }
        return (array)$res['data'];
    }

    /**
     * 获取黄金资产
     */
    public function getUserAssets(array $userIds) {

        $request = new RequestCommon();
        $request->setVars(['userIds' => $userIds]);
        try {
            $response = $this->requestGold('NCFGroup\Gold\Services\User', 'getUserAssets', $request);
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, "error:".$e->getMessage())));
            return [];
        }

        if (!empty($response['errCode'])) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, "error:".$response['errMsg'])));
            return [];
        }
        $data = $response['data']['data'];
        $price = $response['data']['price'];

        foreach ($data as $userId => $gold) {
            $assets[$userId]['avaliable'] = bcdiv(bcmul($gold['avaliable'], $price), 100, 2);
            $assets[$userId]['freeze'] = bcdiv(bcmul($gold['freeze'], $price), 100, 2);
            $assets[$userId]['total'] = bcadd($assets[$userId]['avaliable'], $assets[$userId]['freeze'], 2);
        }

        return $assets;
    }


    /**
     * 获取黄金标的列表
     * @param unknown $pageSize
     * @param unknown $pageNum
     * @return array
     */
    public function getGoldList($bidAmount, $bidDayLimit, $pageNum , $pageSize)
    {
        $request = new RequestCommon();
        $request->setVars(array('bidAmount' => $bidAmount, 'bidDayLimit' => $bidDayLimit, 'pageNum' => $pageNum, 'pageSize' => $pageSize));
        $res = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealListByDiscount', $request);
        if ($res['errCode'] != 0) {
            return $res;
        }
        $result = array();
        $result['totalPage'] = $res['data']['totalPage'];
        $result['totalNum'] = $res['data']['totalNum'];
        $result['list'] = array();
        //黄金售罄开关 1在售  0 售罄
        $switch = app_conf('GOLD_SALE_SWITCH');
        foreach ($res['data'] as $key => $value) {
            $result['list'][$key]['id'] = $value['id'];
            $result['list'][$key]['deal_status'] = ($switch == 1) ? $value['dealStatus'] : 2;
            $result['list'][$key]['tag'] = $value['dealTagName'];
            $result['list'][$key]['gold_unit'] = '克/100克';
            $result['list'][$key]['period_unit'] = ($value['loantype'] == 5) ? '天' : '个月';
            $result['list'][$key]['name'] = $value['name'];
            $result['list'][$key]['annual_comp_rate'] = floorfix($value['rate'],3,6);
            $result['list'][$key]['period'] = $value['repayTime'];
            $result['list'][$key]['invest_quality'] = number_format($value['minLoanMoney'],3);
            $result['list'][$key]['total_quality'] = number_format(floorfix($value['borrowAmount'],3,6),3);
            $result['list'][$key]['usable_quality'] = number_format(floorfix($value['borrowAmount'] - $value['loadMoney'],3,6),3);
            $result['list'][$key]['point_percent'] = $value['pointPercent'];
            $result['list'][$key]['buyerFee'] = $value['buyerFee'];
        }
        return $result;
    }

    /**
     * 获取优长今可投标的的数量
     * @return bool
     */
    public function getCountOnSale() {
        $request = new RequestCommon();
        $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getCountOnSale', $request);
        if ($response['errCode'] != 0) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, "error:".$response['errMsg'])));
            $response['data'] = 0;
        }
        return $response['data'];
    }

    /**
     * 更加条件获取标列表
     * @param unknown $condition
     * @return Ambigous <>
     */
    public function getDealListByCondition($condition = array()){
        $request = new RequestCommon();
        $request->setVars(array('condition'=>$condition));
        $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealListByCondition', $request);
        return $response['data'];
    }

    public function getDealInfosByDealIds($dealIds){
        $request = new RequestCommon();
        $request->setVars(array('dealIds'=>$dealIds));
        $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealsByIds', $request);
        return $response['data'];
    }
    /**
     * 自动上标管理更改标状态为进行中,并且标状态置成有效，注意，调用需谨慎
     */
    public function updateDealStatusById($dealId,$status){
        $request = new RequestCommon();
        $request->setVars(array('id'=>$dealId,'status'=>$status));
        $res = $this->requestGold('NCFGroup\Gold\Services\Deal', 'updateDealStatusById', $request);
        return $res['data'];
    }
    /**
     *优长金收益计划
     */
    public function getMoneyRepayByDealIdAndUserId($dealLoanId,$userId){
        if(empty($dealLoanId)||empty($userId)){
            return false;
        }
        $request = new RequestCommon();
        $request->setVars(array('userId'=>$userId,'dealLoanId'=>$dealLoanId));
        $result = $this->requestGold('NCFGroup\Gold\Services\DealRepay','getMoneyRepay',$request);
        $data=array();
        if ($result['errCode'] != 0||empty($result['data'])) {
            return $data;
        }
        foreach($result['data'] as $key => $value){
            $data[$key]['type']=$value['type'];
            $data[$key]['status']=$value['status'];
            if($value['status']==1){
                $data[$key]['time']=$value['real_time'];
            }else{
                $data[$key]['time']=$value['time'];
            }
            $data[$key]['money']=$value['money'];
        }
        return $data;
    }

    /**
     *判断用户是否是首投
     */
    public function isFirstBid($userId, $orderId) {
        if (empty($userId)){
            throw new \Exception('用户ID不能为空');
        }
        $request = new RequestCommon();
        $request->setVars(array('userId'=>$userId, 'orderId'=>$orderId));
        $response=$this->requestGold('NCFGroup\Gold\Services\Deal','isFirstBid', $request);
        if (empty($response)) {
            throw new \Exception('判断用户是否首投失败');
        }
        return $response['data'];
    }

    /*
     * 提金商品列表页
     */
    public function getGoodsList(){
        $request = new RequestCommon();
        $request->setVars(array('condition'=>'isEffect = 1'));
        $result = $this->requestGold('NCFGroup\Gold\Services\DeliverGoods','getList',$request);
        $data=array();

        if ($result['errCode'] != 0||empty($result['data'])) {
            return $data;
        }
        $fee=$this->getDealCurrent();
        foreach($result['data'] as $key => $value){
            $data[$key]['id']=$value['id'];
            $data[$key]['name']=$value['name'];
            $data[$key]['size']=$value['size'];
            $data[$key]['url']=$value['url'];
            $data[$key]['fee']=$value['fee']==0?$fee['receiveFee']:$value['fee'];
            $data[$key]['imagPath']=$value['imagPath'];
        }
        return $data;
    }

    /**
     * 获取提金订单列表
     */
    public function getDeliverList($userId,$status,$page,$pageSize){

        $request = new RequestCommon();
        $param = array(
            'userId' => $userId,
            'status' => intval($status),
            'pageNum' => intval($page),
            'pageSize' => intval($pageSize),
        );

        $request->setVars($param);

        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver','getList',$request);

        return $response;
    }

    /**
     * 获取某段时间内用户的logInfo汇总
     * @param $userId
     * @param $startTime
     * @param $endTime
     * @param $logInfo
     * @return bool
     * @throws \Exception
     */
    public function getTotalLogInfoByTime($userId,$startTime,$endTime,$logInfo) {
        $request = new RequestCommon();
        $request->setVars(array( 'userId' => $userId,'startTime' => $startTime, 'endTime' => $endTime,'logInfo' => $logInfo ));

        $total = 0;
        try{
            $res = $this->requestGold('NCFGroup\Gold\Services\User', 'getUserLogTotalGoldByUserIdTime', $request);
            if(!$res || !isset($res['data'])){
                throw new \Exception("获取黄金数据失败");
            }
            $total = $res['data'];
        }catch (\Exception $ex){
            Logger::info(implode(' | ', array(__CLASS__,__FUNCTION__,APP, "userId:{$userId},startTime:{$startTime},endTime:{$endTime},logInfo:{$logInfo}","errMsg:".$ex->getMessage())));
        }
        return $total;
    }


    /**
     * 获取最新的购买记录 type为0为活期，1为定期
     * @param int $count 默认5条
     * @param int $type
     * @param int int $limit_time 单位秒，默认24小时之内
     */
    public function getAllDealLoadListByLimit($count = 5,$type=0,$limit_time = 86400){

        if ($type !=0 && $type != 1){
            return fasle;
        }

        $ret = array();
        $request = new RequestCommon();
        $request->setVars(array('count' => intval($count),'limit_time' => intval($limit_time)));

        if ($type == 0){
            $service = 'NCFGroup\Gold\Services\DealLoadCurrent';
        }else{
            $service = 'NCFGroup\Gold\Services\DealLoad';
        }

        try{
            $res = $this->requestGold($service, 'getAllListByLimit', $request);
            if(!$res || !isset($res['data'])){
                throw new \Exception("get new dealLoad fail type ".$type.' count '.$count);
            }
            $ret = $res['data'];
        }catch (\Exception $e){
            Logger::error($e->getFile().' '.$e->getFile().' '.$e->getMessage());
        }

        return $ret;
    }

    /**
     * 根据ids获取商品信息
     * @param array $ids
     */
    public function getGoodsByIds($ids){
        $request = new RequestCommon();
        $request->setVars(array('ids' => $ids));
        $response = $this->requestGold('NCFGroup\Gold\Services\DeliverGoods', 'getGoodsByIds', $request);
        return $response;
    }

    /**
     *根据金条型号算提金手续费
     */
    public function getFeeByModel($data){
        $result = $this->getGoodsByIds(array_keys($data['goodsDetails']));
        if(empty($result['data'])){
            return false;
        }
        $money=0;
        foreach ($data['goodsDetails'] as $key => $value){
            $goods=bcmul($result['data'][$key]['size'],$value);
            $fee=($result['data'][$key]['fee']==0)?$data['fee']:$result['data'][$key]['fee'];
            $fees=bcmul($goods,$fee,4);
            $money=floorfix(bcadd($fees,$money,4),2);
        }
        return $money;
    }

    /**
     * 待提交（重新发起提交）
     * @param $userId
     * @param $orderId
     */
    public function deliverPending($userId,$orderId){

        $request = new RequestCommon();
        $param = array('userId' => $userId,'orderId' => $orderId);
        $request->setVars($param);

        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver', 'pending', $request);

        return $response;
    }

    public function deliverDetail($userId,$orderId){
        $request = new RequestCommon();
        $param = array('userId' => $userId,'orderId' => $orderId);
        $request->setVars($param);

        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver', 'detail', $request);

        return $response;
    }

    public function deliverLogistics($userId,$orderId){
        $request = new RequestCommon();
        $param = array('userId' => $userId,'orderId' => $orderId);
        $request->setVars($param);

        $response = $this->requestGold('NCFGroup\Gold\Services\Deliver', 'logistics', $request);

        return $response;
    }

    /**
     * 获取用户列表
     * @return array
     */
    public function getUserList($pageSize = 0, $pageNum = 0){
        $request = new RequestCommon();
        $request->setVars(array('pageSize' => $pageSize, 'pageNum' => $pageNum));
        $response = $this->requestGold('NCFGroup\Gold\Services\User', 'getUserList', $request);
        return $response;
    }

    /**
     * 根据时间获取金价
     * @param intval $dateTime
     * @return array
     */
    public function getGoldPriceByDate($dateTime=0){
        $request = new RequestCommon();
        $request->setVars(array('dateTime' => $dateTime));
        $response = $this->requestGold('NCFGroup\Gold\Services\GoldApi', 'getGoldPriceByDate', $request);
        return $response;
    }
    /**
     * 根据时间获取金价
     * @param intval $dateTime
     * @return array
     */
    /**
     * 合同服务满标时生成未签署记录
     * @param array $deal
     * @return bool
     */
    public function createNew($deal_id){
        $request = new RequestCommon();
        $request->setVars(array('dealId' => $deal_id));
        $response = $this->requestGold('NCFGroup\Gold\Services\GoldDealContract', 'createNew', $request);
        if ($response['errCode'] != 0||empty($response['data'])) {
            return true;
        }else{
            return false;
        }
    }
    /*
            * 更改dealContract表的签署记录状态
            */
    public function signGoldByRole($dealId, $userId, $isAgency=0,$agencyId = 0,$all=false, $admID = 0, $status = 1){
        $request = new RequestCommon();
        $request->setVars(array("dealId"=>$dealId));
        if($all){
            $request->setVars(array("all"=>true));
        }
        if(!empty($userId)){
            $request->setVars(array("userId"=>intval($userId)));
        }
        if(!empty($agencyId)){
            $request->setVars(array("agencyId"=>$agencyId));
        }
        if(!empty($admID)){
            $request->setVars(array("admID"=>$admID));
        }
        $response = $this->requestGold('NCFGroup\Gold\Services\GoldDealContract', 'signGoldByRole', $request);
        if ($response['errCode'] != 0||empty($response['data'])) {
            return true;
        }else{
            return false;
        }
    }

    /**
     *优金宝频道页新增提金克重接口
     */
    public function getMinSize(){
        $request = new RequestCommon();
        $response = $this->requestGold('NCFGroup\Gold\Services\DeliverGoods', 'getMinSize', $request);
        if ($response['errCode'] != 0 || empty($response['data'])) {
            return false;
        }
        return $response['data']['min_size'];
    }

    /**
    *修改白名单用户
     */
    public function changeWhiteUser($data){
        $request = new RequestCommon();
        Logger::info(implode(' | ',array(__CLASS__, __FUNCTION__,'data : '.json_encode($data))));
        $request->setVars(array("data"=>$data));
        $response = $this->requestGold('NCFGroup\Gold\Services\User', 'changeWhiteList', $request);
        if ($response['errCode'] != 0 || empty($response['data'])) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__,'response : '.json_encode($response))));
            return false;
        }
        return true;
    }

}

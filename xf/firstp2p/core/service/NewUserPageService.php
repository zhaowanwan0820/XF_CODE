<?php
/**
 * 新注册用户首页
 * @author jinhaidong
 * @date 2017-6-11 13:48:29
 */
namespace core\service;

use core\dao\NewUserPageModel;
use core\dao\AttachmentModel;
use core\dao\DealModel;
use \libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\web\Open;


class NewUserPageService extends BaseService {

    const KEY_PREFIX_PAGE_CACHE = "NEW_PAGE_INVITECODE_"; // 缓存key值
    const NUM_DEAL_LIST = 3;

    /**
     * 检查邀请码有效性
     * @param $inviteCode eg:$inviteCodes = array('F018A2','F017CA','111');
     */
    public function isSiteInviteCodes($inviteCodes){
        \libs\utils\PhalconRPCInject::init();
        $request = new SimpleRequestBase();
        $request->setParamArray(array('inviteCode'=>$inviteCodes));
        $response = $GLOBALS['openbackRpc']->callByObject(
            array('service'=>'NCFGroup\Open\Services\OpenApp',
                'method'=>'getSiteIdsByInviteCodes',
                'args'=>$request
            ));

        if(!$response->data){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",从open获取邀请码信息失败 inviteCodes:".json_encode($inviteCodes));
            return false;
        }
        $invalidCodes = array();
        foreach($response->data as $key=>$code){
            if($code === false){
                $invalidCodes[] = $key;
            }
        }
        return $invalidCodes;
    }

    /**
     * 检查是否已经配置过了邀请码
     * $inviteCodes
     * @param $inviteCodes
     */
    public static function checkExistsInviteCodes($pageId,Array $inviteCodes){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception("getRedisInstance failed");
        }
        $existsCodes = array();
        foreach($inviteCodes as $code){
            $code = trim($code);
            $key = self::KEY_PREFIX_PAGE_CACHE . $code;
            $cachePageId = $redis->get($key);

            if($pageId === false){
                if($cachePageId){
                    $existsCodes[] = $code;
                    continue;
                }
            }else{
                if($cachePageId  && $cachePageId != $pageId){
                    $existsCodes[]=$code;
                    continue;
                }
            }
        }
        return $existsCodes;
    }

    /**
     * 更新邀请码和分站id对应关系
     * @param $inviteCodes
     * @param $pageId
     * @return bool
     * @throws \Exception
     */
    public function updateInvitePageCache($inviteCodes,$pageId){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception("getRedisInstance failed");
        }
        foreach($inviteCodes as $code){
            $key = self::KEY_PREFIX_PAGE_CACHE . $code;
            $redis->set($key,$pageId);
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",邀请码缓存设置成功 inviteCode:{$code},pageId:{$pageId}");
        }
        return true;
    }

    public function flashPages($inviteCodes){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception("getRedisInstance failed");
        }
        foreach($inviteCodes as $code){
            $key = self::KEY_PREFIX_PAGE_CACHE . $code;
            $res = $redis->del($key);
            if($res){
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",邀请码缓存清除成功 key:{$key}");
            }else{
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",邀请码缓存清除失败 key:{$key}");
            }
        }
    }

    /**
     * 根据邀请码获取页面
     * @param $invite_code
     * @return array|bool
     */
    public function getPageInfoByInviteCode($invite_code=""){
        try{
            $invite_code = trim($invite_code);

            if(empty($invite_code)){
                throw new \Exception("邀请码为空 将使用默认配置");
            }

            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if (!$redis) {
                throw new \Exception("getRedisInstance failed");
            }

            $key = self::KEY_PREFIX_PAGE_CACHE . $invite_code;
            $pageId = $redis->get($key);
            if(!$pageId){
                throw new \Exception("未获取到 key：{$key} 对应的配置ID");
            }
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            $pageId = $this->getDefaultPageId();
        }
        return $this->getPageInfoById($pageId);
    }

    public function getDefaultPageId(){
        $condition = "is_default=1";
        $res = NewUserPageModel::instance()->findAllViaSlave($condition,true,'id');
        return isset($res[0]) ? $res[0]['id'] : false;
    }

    /**
     * 获取页面配置信息
     * @param $id
     * @return array|bool
     */
    public function getPageInfoById($id) {

        $pageInfo = NewUserPageModel::instance()->findViaSlave($id);
        if (empty( $pageInfo )) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",未获取到配置页面 pageId:{$id}");
            return false;
        }

        $pageInfo = $pageInfo->getRow();
        $conf_reg = explode(",",$pageInfo['conf_reg']);
        $conf_bid =  explode(",",$pageInfo['conf_bid']);
        $conf_invite =  explode(",",$pageInfo['conf_invite']);
        $conf_platform =  explode(",",$pageInfo['conf_platform']);

        $linkMore = explode(",",$pageInfo['link_more']);
        $data = array(
            'id' => $pageInfo['id'],
            'title' => $pageInfo['title'],
            'invite_codes' => $pageInfo['invite_codes'],
            'conf_reg_wa' =>  preg_replace ('/^http:|^https:/', "",$conf_reg[0]), // 解决https下加载http img显示问题
            'conf_reg_pc' =>  preg_replace ('/^http:|^https:/', "",$conf_reg[1]),
            'conf_bid_wa' =>  preg_replace ('/^http:|^https:/', "",$conf_bid[0]),
            'conf_bid_pc' =>  preg_replace ('/^http:|^https:/', "",$conf_bid[1]),
            'conf_invite_wa' =>  preg_replace ('/^http:|^https:/', "",$conf_invite[0]),
            'conf_invite_pc' =>  preg_replace ('/^http:|^https:/', "",$conf_invite[1]),
            'conf_platform_wa' =>  preg_replace ('/^http:|^https:/', "",$conf_platform[0]),
            'conf_platform_pc' =>  preg_replace ('/^http:|^https:/', "",$conf_platform[1]),
            'remark' => $pageInfo['remark'],
            'link_more_h5' => isset($linkMore[0]) ? $linkMore[0] :"",
            'link_more_pc' => isset($linkMore[1]) ? $linkMore[1] :"",
            'create_time' => date('Y-m-d H:i:s',$pageInfo['create_time']),
            'update_time' => date('Y-m-d H:i:s',$pageInfo['update_time']),
        );
        return $data;
    }


    /**
     * 获取用户进度
     * @return int 0-注册,1-已投资,2-已邀请好友
     */
    public function getNewUserProgress($userId){
        $isRegister = !empty($userId) ? 1 : 0;

        $inviteService = new O2OService();
        $invite = $inviteService->getUserFriendCount($userId);
        $isInvite =  $invite > 0 ? 1 : 0;

        $investService = new DealLoadService();
        $invest = $investService->getTotalLoanMoneyByUserId($userId,false,false,array(1,2,4,5));
        $isInvest =  $invest >= 500 ? 1 : 0;

        $progress = array('isRegister' =>$isRegister, 'isInvest' => $isInvest, 'isInvite' =>$isInvite );
        return $progress;
    }

     /*
     * 获取新首页标的列表
     * 1、先判断是否有新手专享
     * 2、$num = 有新手专享显示 ? 2 : 3;
     * 3、从专享标获取 $num 个数量标的 短期排在前面
     * @param $site_id
     */
    public function getNewUserDeals($siteId=1,$count=3){
        if($count == 0){
            return array();
        }
        $this->recoverSiteConf($siteId);

//        $deal_model = new DealModel();
//        $new_user_deal = $deal_model->getNewUserDealList();
//        $result = array();
//        $zxDealCount = $count;
//        if(!empty($new_user_deal)){
//            $result[] = $new_user_deal;
//            $zxDealCount = $count - 1;
//        }
//
//        if($zxDealCount > 0){
//            $min_loan_money = (APP == 'web') ? 0 : 100;
//            $list = $deal_model->getProcessingList($zxDealCount,$min_loan_money);
//            $result = array_merge($result,$list);
//        }
//
//        foreach ($result as $key => $deal) {
//            $result[$key] = DealModel::instance()->handleDealNew($deal);
//        }

        $deal_ncfph = new \core\service\ncfph\DealService() ;
        $result= $deal_ncfph->getNewUserDeals($siteId,$count);
        return $result['list'];
    }

    /**
     *
     * 新手专区的开关
     */
    public function isNewUserSwitchOpen(){
        $isNewUserSwitchOpen = app_conf('NEWUSER_CENTER_SWITCH');
        return $isNewUserSwitchOpen == 1 ? 1 : 0;
    }

    /**
     *
     * 用户是否是新手（注册30天以内，未完成投资或未完成邀请好友任务）
     */
    public function isNewUser($userId,$regTime){
        if($this->isNewUserSwitchOpen() !=1){
            return false;
        }
        if(empty($userId)){
            return true;
        }
        $regDays = ceil((time() - $regTime - 28800) / 86400);
        $isNewUser = false;
        if($regDays <= 30){//注册30天以内的用户并且未完成新手任务 需要显示新手专区
            $userProgress = $this->getNewUserProgress($userId);
            $isNewUser = ($userProgress['isInvest'] == 1 && $userProgress['isInvite'] == 1) ? false : true;
        }
        return $isNewUser;
    }

    public function recoverSiteConf($siteId){
        if ($siteId != 1 && !empty($GLOBALS['sys_config_db'][$siteId])) {
            $GLOBALS['sys_config'] = array_merge($GLOBALS['sys_config'], $GLOBALS['sys_config_db'][$siteId]);
            if (!empty($GLOBALS['sys_config_db'][0])) {
                $GLOBALS['sys_config'] = array_merge($GLOBALS['sys_config'], $GLOBALS['sys_config_db'][0]); // 公用配置
                $GLOBALS['sys_config']['TEMPLATE_ID'] = $siteId;
            }
        }
        $openAppConf = \libs\web\Open::getAppBySiteId($siteId);
        if (!empty($openAppConf)) {
            \libs\web\Open::coverSiteInfo($openAppConf);
        }
    }
}

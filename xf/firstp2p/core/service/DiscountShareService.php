<?php
/**
 * DiscountShareService.php
 *
 * @date 2016-07-13
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */

namespace core\service;

use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\lock\LockFactory;
use libs\utils\Logger;
use core\dao\BonusBindModel;
use core\service\WeiXinService;
use core\service\WeixinInfoService;
use libs\utils\Curl;

class DiscountShareService extends BaseService
{
    static private $cache_time = 300;

    /**
     * 活动状态
     */
    const STATUS_NO_EXIST = -1;//活动不存在
    const STATUS_START = 0; //活动已经开始
    const STATUS_NO_START = 1;//活动未开始
    const STATUS_FINISH = 2;//活动结束
    const STATUS_OVER = 3;//券已经领完
    const STATUS_INVALID = 4;//活动无效

    static public $STATUS_NAMES = array(
            self::STATUS_NO_EXIST => '活动不存在',
            self::STATUS_START => '活动已经开始',
            self::STATUS_NO_START => '活动未开始',
            self::STATUS_FINISH => '活动已结束',
            self::STATUS_OVER => '券已经领完',
            self::STATUS_INVALID => '活动无效'
    );

    static public $DES_INFO = array(
            "有钱一起赚，友谊的小船才不会翻。",
            "一言不合就发券，二话不说我先抢。",
            "哎呦这么巧，你也在这里投资理财。",
            "胜“券”在握，有什么理由不理财。",
    );

    /**
     * 活动限制
     */
    const NORMAL = 0;//不限制
    const REGISTERED = 2;//注册用户
    const UNREGISTERED = 1;//未注册用户


    /**
     * 通过活动id获取活动信息
     * @param intval $id
     * @return array
     */
    public function getById($id){
        return $this->call('NCFGroup\Marketing\Services\DiscountShare','getById',array('id' => intval($id)));
    }


    /**
     * 获取活动信息通过缓存
     * @param int $id
     */
    public function getByIdViaCache($id){
        return \SiteApp::init()->dataCache->call(new DiscountShareService(), 'getById', array('id'=>$id), 5);
    }

    /**
     * 检查活动状态
     */
    public function checkDisCountShare($id){

        $data = array('status'=>self::STATUS_NO_EXIST,'disCountShareInfo'=>array());
        $disCountShareInfo = $this->getByIdViaCache($id);

        if(!empty($disCountShareInfo)){
            $data['disCountShareInfo'] = $disCountShareInfo;
            $now = time();

            if($disCountShareInfo['status'] == 0){//活动无效
                $data['status'] = self::STATUS_INVALID;
            }elseif($disCountShareInfo['timeStart'] > $now){//活动未开始
                $data['status'] = self::STATUS_NO_START;
            }elseif($disCountShareInfo['timeStart'] <= $now && $disCountShareInfo['timeEnd'] >= $now){//活动开始
                if(empty($disCountShareInfo['inventory'])){//活动没有库存
                    $data['status'] = self::STATUS_OVER;
                }else{
                    $data['status'] = self::STATUS_START;
                }
            }elseif($disCountShareInfo['timeEnd'] < $now){//活动已结束
                $data['status'] = self::STATUS_FINISH;
            }
        }

        return $data;
    }

    /**
     * 领券接口
     * @param intval $id 活动id
     * @param intval $mobile 手机号
     * @param intval $user_id 用户id
     */
    public function obtain($id,$mobile,$userId = 0){

        try {
            if(empty($mobile) || empty($id)){
                throw new \Exception('参数错误!');
            }

            // 悲观锁，以id为锁的键名
            $lockKey = "discountShare_obtain_evenId_".$id;
            $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
            if (!$lock->getLock($lockKey, 300)) {
                throw new \Exception('加锁失败!');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array('eventId' => intval($id),'mobile'=>$mobile ,'userId' =>intval($userId)));
            $response = $GLOBALS['marketingRpc']->callByObject(array(
                    'service' => 'NCFGroup\Marketing\Services\DiscountShare',
                    'method' => 'acquireCoupon',
                    'args' => $request
            ));

        } catch (\Exception $e) {
            $response['code'] = 1;
            $response['msg'] = $e->getMessage();
            $lock->releaseLock($lockKey);
        }

        $lock->releaseLock($lockKey); // 解锁
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'id:'.$id,'user_id:'.$userId,'mobile:'.$mobile,'code:'.$response['code'],'msg:'.$response['msg'])));
        return $response['code'] == 0;
    }

    /**
     * 获取最近的领取记录
     * @param inval $id
     * @param inval $size
     * @return array
     */
    public function getDiscountListById($id){

        $params = array('eventId'=>intval($id));
        $result =  $this->getDisCountListByParamsViaCache($params);

        if(!empty($result)){
            $weixin_info_service = new WeixinInfoService();
            $bind_model = new BonusBindModel();
            foreach($result as $key => $val){
                $list[$key]['mobile'] = moblieFormat($val['mobile']);
                $list[$key]['time'] = date('Y-m-d H:i:s',$val['createTime']);
                $list[$key]['desc_info'] = self::$DES_INFO[array_rand(self::$DES_INFO,1)];
                $list[$key]['weixin_user_info'] = false;
                // 微信信息
                $condition = "mobile=':mobile' AND openid IS NOT NULL ";
                $param = array(
                    ':mobile' => $val['mobile']
                );
                $bind_info = $bind_model->findByViaSlave($condition, 'openid',$param);
                if (!empty($bind_info)){
                    $weixin_info = $weixin_info_service->getWeixinInfo($bind_info['openid']);
                    $list[$key]['weixin_user_info'] = $weixin_info['user_info'];
                }
            }

        }

        return $list;
    }


    /**
     * 获取用户领取券列表
     * @param int $id
     * @param string $mobile
     * @return boolean
     */
    public function getDiscountListByIdAndMobile($id,$mobile){

        if(empty($mobile) || empty($id)){
            return false;
        }
        $params = array('eventId'=>intval($id),'mobile'=>$mobile);
        return $this->getDisCountListByParamsViaCache($params);
    }


    public function getDisCountListByParamsViaCache($params) {
        return \SiteApp::init()->dataCache->call(new DiscountShareService(), 'getDisCountListByParams', array($params), self::$cache_time);
    }

    public function getDisCountListByParams($params){
        $response = $this->call('NCFGroup\Marketing\Services\AcquireLog','getAcquireLogList',$params);
        return $response['data'];
    }


    /**
     * 调用市场rpc接口
     * @param string $service
     * @param string $method
     * @param array $params
     * @return array
     */
    public function call($service,$method,$params){

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $GLOBALS['marketingRpc']->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
            ));
        } catch (\Exception $e) {
            \libs\utils\Alarm::push('marketRpc', 'marketRpcError', 'error:'.$e->getMessage().',Params:'.json_encode($params));
            Logger::error(implode(" | ",array(__CLASS__, __FUNCTION__,'error:'.$e->getMessage(),'Params:'.json_encode($params))));
            return array();
        }

        return $response;
    }


    /**
     * 微信授权绑定
     *
     * @param string $code 微信回调参数
     * @param int $mobile
     * @return bool
     */

     public function weiXinAuthorized($code, $mobile){
         if (empty($code)){
             return $code;
         }

         $wxService = new WeiXinService();
         $log_info = array(__CLASS__, __FUNCTION__, $mobile, $wxService::TYPE_MARKETING);
         $wxService->clearCookie($wxService::TYPE_MARKETING);
         try {
             // 获取微信信息
             $ret = $wxService->winXinCallback($code, $wxService::TYPE_MARKETING);
             if (!empty($ret['err_code'])) {
                 $log_info[] = 'weixin callback error info '.json_encode($ret);
                 Logger::error(implode(" | ", $log_info));
                 return false;
             }
         } catch (\Exception $e) {
             $log_info[] = 'weixin callback fail';
             Logger::error(implode(" | ", $log_info));
             return false;
         }
        // 绑定
        $BonusBindService = new BonusBindService();
        $ret = $BonusBindService->bindUser($wxService::$openId, $mobile);
         if (empty($ret)) {
             $log_info[] = 'bind fail';
             Logger::error(implode(" | ", $log_info));
             return false;
         }

         return true;
    }

    /**
     * curl请求
     * @param string $url
     * @param array $post
     */

    public function Curl($url, $post){
        if (empty($url)){
            return false;
        }
        $curl = new Curl();
        $ret = $curl->post($url, $post);
        if (!empty($curl::$errno)){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,'curl result errno '.$curl::$errno.' httpcode '.$curl::$httpCode.' error '.$curl::$error)));
            return false;
        }

        return $ret;
    }

/**
 *
 */

}

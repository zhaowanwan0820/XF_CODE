<?php
/**
 * MedalService.php
 * @date 2015-12-25
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace core\service;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\O2O\RequestGetCouponGroup;
use NCFGroup\Protos\O2O\RequestGetGroupList;
use libs\utils\Logger;
use NCFGroup\Protos\Medal\RequestGetMedal;
use NCFGroup\Protos\Medal\RequestMedalUser;

class MedalService extends BaseService {

    const MEDAL_ID_AES_KEY = 'medal@1@#IJK1234';

    const MEDAL_BEGINNER_DESCRIPTION = "%s年%s月注册用户专享 | 剩余完成时间%s天%s小时";

    const MEDAL_BEGINNER_HINT_FORMAT = "/list/medal_beginner/%s"; //新手提示
    const MEDAL_BUBBLE_HINT_FORMAT = "/string/medal_bubble/%s"; //新手进度气泡
    const MEDAL_HIDDEN_CIRCLE = "hidden_medal_circle";
    public static $shareConf = array();

    public function createUserMedalRequestParameter($userId, $medalId = 0, $isSlave = true) {
        $userId = intval($userId);
        $request = new RequestMedalUser();
        $request->setUserId($userId);
        $request->setMedalId(intval($medalId));
        $userTagService = new UserTagService();
        $userTagData = $userTagService->getTags($request->getUserId(), $isSlave);
        $userTags = array();
        if($userTagData) {
            foreach($userTagData as $userTag) {
                $userTags[] = $userTag['const_name'];
            }
        }
        $couponBindService = new CouponBindService();
        $bindResult = $couponBindService->getByUserId($userId);
        $inviterId = isset($bindResult['refer_user_id']) ? intval($bindResult['refer_user_id']) : 0;
        $inviterTags = array();
        if($inviterId > 0) {
            $inviterTagData = $userTagService->getTags($inviterId, $isSlave);
            foreach($inviterTagData as $inviterTag) {
                $inviterTags[] = $inviterTag['const_name'];
            }
        }
        $request->setUserTag($userTags);
        $request->setInviterTag($inviterTags);
        return $request;
    }

    /**
     * getMedalList
     * 获取勋章列表
     * @param $request
     * @return $response
     */
    public function getMedalList($request){
        $userId = $request->getUserId();
        $medalList = $this->getMedalListByMethod($request,'getMedalList');

        $medals = isset($medalList['medal']) ? $medalList['medal'] : array();
        $beginnerFlag = isset($medalList['isShowBeginner']) ? $medalList['isShowBeginner'] : false;
        $result['current'] = array();
        $result['history'] = array();
        foreach ($medals as $medal) {
            if($beginnerFlag && $medal['isForBeginner']) {
                $result['beginner'][] = $medal;
            } else if (!$medal['isHistory']) {
                $result['current'][] = $medal;
            } elseif ($medal['isHistory'] && $medal['isOwned']) {
                $result['current'][] = $medal;
            } else {
                $result['history'][] = $medal;
            }
        }
        if($beginnerFlag) {
            $result['beginnerPics'] = array(
                "appPicUrl" => $medalList['appPicUrl'],
                "webPicUrl" => $medalList['webPicUrl'],
            );
            $remainTime = intval($medalList['remainTime']);
            $remainDay = intval($remainTime / 86400);
            $remainHour = intval(($remainTime % 86400) / 3600);
            $userService = new UserService();
            $userInfo = $userService->getUserViaSlave($userId);
            //用户注册时间
            $registerTime = $userInfo['create_time'] + 28800;
            $registerYear = date("Y", $registerTime);
            $registerMonth = date("m", $registerTime);
            $result['beginnerDescription'] = array(
                'registerYear' => $registerYear,
                'registerMonth' => $registerMonth,
                'remainDay' => $remainDay,
                'remainHour' => $remainHour,
            );
        }

        return $result;
    }

    public function fetchMedalMessage($request) {
        $result = array();
        // 普惠不提示勋章
        if (\libs\utils\Site::getId() == 100) {
            return $result;
        }
        try {
            $response = $this->requestMedal('NCFGroup\Medal\Services\MedalMessage', 'fetchMedalMessage', $request);
        } catch(\Exception $e) {
            return $result;
        }
        $medalList = $response->getList();
        if($medalList) {
            foreach($medalList as $medal) {
                $result[] = $this->getOneMedalInfo($medal);
            }
        }
        return $result;
    }

    public function getMedalBubbleInfo($userId) {
        $result = array(
            'isShowMedalBubble' => false,
            "beginnerMedalCount" => 0,
            "userBeginnerMedalCount" => 0,
        );
        // 普惠不提示勋章
        if (\libs\utils\Site::getId() == 100) {
            return $result;
        }
        $request = $this->createUserMedalRequestParameter($userId);
        $progress = $this->getMedalProgress($request);
        if($progress['isBeginner']) {
            if($progress['beginnerMedalCount'] > $progress['userBeginnerMedalCount']) {
                $result['isShowMedalBubble'] = true;
                $result['beginnerMedalCount'] = $progress['beginnerMedalCount'];
                $result['userBeginnerMedalCount'] = $progress['userBeginnerMedalCount'];
            }
        }
        return $result;
    }

    public function getMedalProgress($request) {
        $result = array(
            'isBeginner' => false,
            'beginnerMedalCount' => 0,
            'userBeginnerMedalCount' => 0,
            'unawardedBeginnerMedals' => array(),
            'unawardedMedals' => array(),
        );
        // 普惠不提示勋章
        if (\libs\utils\Site::getId() == 100) {
            return $result;
        }
        try {
            $userId = $request->getUserId();
            $userModel = new \core\dao\UserModel();
            $userInfo = $userModel->find($userId, 'create_time', true);
            $registerTime = !empty($userInfo) ? $userInfo['create_time'] + 28800 : 0;
            $request->setUserRegisterTime($registerTime);
            $response = $this->requestMedal('NCFGroup\Medal\Services\MedalUser', 'getMedalProgress', $request);
            if (is_object($response)) {
                return $response->toArray();
            }
        } catch(\Exception $e) {
            $result['errCode'] = -1;
            $result['errMsg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * getUserMedalList
     * 获取用户勋章列表
     * @param $request
     * @return $response
     */
    public function getUserMedalList($request){
        $log_info = array(__CLASS__, __FUNCTION__, 'user_id='.$request->getUserId());

        $result = $this->getMedalListByMethod($request,'getUserMedalList');
        return isset($result['medal']) ? $result['medal'] : array();
    }
    /**
     * getMedalListByMethod
     * 根据不同的方法获取相应勋章列表
     * @param $request $method
     * @return $response
     */
    public function getMedalListByMethod($request,$method){
        try{
            $response = $this->requestMedal('NCFGroup\Medal\Services\MedalInfo', $method, $request);
        } catch(\Exception $e) {
            return array();
        }

        $medals =  $response->getList();

        $result['isShowBeginner'] = $response->getIsShowBeginner();
        $result['appPicUrl'] = $response->getAppPicUrl();
        $result['webPicUrl'] = $response->getWebPicUrl();
        if(APP == 'web') {
            //删除http:这个协议头
            $result['appPicUrl'] = substr($result['appPicUrl'], 5);
            $result['webPicUrl'] = substr($result['webPicUrl'], 5);
        }
        $result['remainTime'] = $response->getRemainingTime();
        $result['medal'] = array();
        foreach ($medals as $key => $medal) {
            $result['medal'][$key] = $this->getOneMedalInfo($medal);
        }
        return $result;
    }

    public function getOneMedalDetail($request) {
        $response = $this->requestMedal('NCFGroup\Medal\Services\MedalInfo', 'getOneMedalDetails', $request);
        if($response) {
            return $this->getOneMedalInfo($response);
        }
        return false;
    }

    public function getOneMedalInfo($medal) {
        $result = array(
            'medalId' => $medal->id,
            'name' => $medal->name,
            'isOwned' => $medal->isOwned,
            //    'icon' => array('iconsmallLightened' => $medal->smallLightenedImg,
            //                    'iconmediumLightened' => $medal->mediumLightenedImg,
            //                    'iconbigLightenedImg' => $medal->bigLightenedImg,
            //                    'iconsmallUnlightened' => $medal->smallUnlightenedImg,
            //                    'iconmediumUnlightened' => $medal->mediumUnlightenedImg,
            //                    'iconbigUnlightened' =>$medal->bigUnlightenedImg,
            //                   ),

//            'icon' => array('iconLarge' =>$medal->bigLightenedImg,
//                'iconLargeUnlighted' =>$medal->bigUnlightenedImg,
//                'iconMedium' => $medal->mediumLightenedImg,
//                'iconMediumUnlighted' => $medal->mediumUnlightenedImg,
//                'iconSmall' => $medal->smallLightenedImg,
//                'iconSmallUnlighted' => $medal->smallUnlightenedImg,
//            ),
            'isHistory' => $medal->isHistory,
            'hasPrize' => $medal->isAwarded,
            'hasLimit' => $medal->isLimited,
            'limitCount' => $medal->num,
            'medalSketch' => $medal->description,
            'startTime' => date('Y-m-d', $medal->startTime),
            'endTime' => date('Y-m-d', $medal->endTime),
            'description' => $medal->details,
            'progressTitle' => '',
            // 'prizeNeedSelect' => $medal->isAllTaken,
            'progress' => '',
            'prizeTitle' =>'勋章奖励(可选择'.$medal->prizeNum.'个奖品)',
            'prizeSelectCount' => $medal->prizeNum,
            'prizes' => array(),
            'deadlineHint' =>'请在'.date('Y-m-d', $medal->prizeDeadline + 86400).'前领取',
            'myStatus' => '',
            'isForBeginner' => $medal->isForBeginner,
            'remark' => $medal->remark, //弹窗显示的内容
            'grantTime' => $medal->grantTime
            //'shareinfo' =>'',
        );
        if(APP == "web") {
            $result['icon'] = array(
                'iconLarge' => substr($medal->bigLightenedImg, 5),
                'iconLargeUnlighted' => substr($medal->bigUnlightenedImg, 5),
                'iconMedium' => substr($medal->mediumLightenedImg, 5),
                'iconMediumUnlighted' => substr($medal->mediumUnlightenedImg, 5),
                'iconSmall' => substr($medal->smallLightenedImg, 5),
                'iconSmallUnlighted' => substr($medal->smallUnlightenedImg, 5),
            );
        } else {
            $result['icon'] = array(
                'iconLarge' =>$medal->bigLightenedImg,
                'iconLargeUnlighted' =>$medal->bigUnlightenedImg,
                'iconMedium' => $medal->mediumLightenedImg,
                'iconMediumUnlighted' => $medal->mediumUnlightenedImg,
                'iconSmall' => $medal->smallLightenedImg,
                'iconSmallUnlighted' => $medal->smallUnlightenedImg,
            );
        }
        if ($medal->isLimited == 2) {
            $result['hasLimit'] = 0;//不限
        } elseif ($medal->isLimited == 1){
            $result['hasLimit'] = 1;//限量
        }
        //根据标识位，给进度标题名赋值
        if ($medal->optional == 1) {
            $result['progressTitle'] = '完成以下所有条件';
        } elseif ($medal->optional == 2) {
            $result['progressTitle'] = '完成以下条件之一';
        } else {
            $result['progressTitle'] = '';
        }
        //完成进度
        $complete_flag = 0;//如果完成以下条件之一的情况有一个达成条件，则置一个标志位为1,其余的条件达成与否都设置为空
        if ($medal->progresses) {
            foreach ($medal->progresses as $pro) {
                if ($pro['unitType'] == 2) {
                    $pNameStatus_current = $pro['currentValue']/100 > 10000 ? $pro['currentValue']/(100*10000).'万' : $pro['currentValue']/100;
                    $pNameStatus_expect = $pro['expectedValue']/100 > 10000 ? $pro['expectedValue']/(100*10000).'万' : $pro['expectedValue']/100;
                } else {
                    $pNameStatus_current = $pro['currentValue'];
                    $pNameStatus_expect = $pro['expectedValue'];
                }
                if (($medal->isOwned && !$medal->isHistory && $medal->optional == 1) || $pro['isCompleted']) {//如果已经达成了条件则，把条件打满，写死。适用于完成所有条件的情况
                    $pNameStatus_current = $pNameStatus_expect;                                               //如果是多选一，则完成一个条件后，其余条件完成与否都取消，置为空
                    $pro['isCompleted'] = true;

                    if ($pro['isCompleted'] && $medal->optional == 2) {
                        $complete_flag += 1;
                    }
                }
                if ($medal->isHistory && !$medal->isOwned) {//如果是过往勋章，并且没有获得， 则在进度显示中显示空，写死
                    $result['progress'][] = array('pName' => $pro['progressName'],
                        'pNameStatus' => '',
                        'isCompleted' => false,
                    );
                } else {
                    $result['progress'][] = array('pName' => $pro['progressName'],
                        'pNameStatus' => $pNameStatus_current.'/'.$pNameStatus_expect,
                        'isCompleted' => $pro['isCompleted'],
                    );
                }
            }
            //单独处理完成以下条件之一个情况，如果多个条件中有一个完成了，在将其打满，其余的未完成的设置显示为空
            if ($medal->isOwned && !$medal->isHistory && $medal->optional == 2 && $complete_flag >= 1) {
                $keys = 0;
                foreach ($result['progress'] as $com_value) {
                    if ($com_value['isCompleted']) {
                        $result['progress'][$keys] = array('pName' => $com_value['pName'],
                            'pNameStatus' => $com_value['pNameStatus'],
                            'isCompleted' => $com_value['isCompleted'],
                        );
                    } else {
                        $result['progress'][$keys] = array('pName' => $com_value['pName'],
                            'pNameStatus' => '',
                            'isCompleted' => false,
                        );
                    }
                    $keys += 1;
                }
            }
        } else {
            $result['progress'][] = array();
        }
        //根据标识位，给奖励标题名赋值
        //          if ($medal->isAllTaken) {
        //              $result[$key]['prizeTitle'] = '奖励';
        //          } else {
        //              $result[$key]['prizeTitle'] = '奖励(多选一)';
        //          }
        //领取进度 type=2,有时间限制，endtime不为0;如果为1，则无时间限制，endtime为0
        //mystatus设置规则：
//             1.未点亮勋章未过期：未点亮 && 统计时间未过期&&有剩余数量
//             2.未点亮勋章已过期：未点亮 &&（统计时间过期 || 没有剩余数量）
//             3.已点亮：已点亮 &&（（有奖励 && 未领奖 && 领奖时间未过期） ｜｜无奖励）
//             4.已点亮奖励已过期：已点亮 && 有奖励 && 未领奖&&领奖时间过期
//             5.已点亮已领奖：已点亮&&已领奖
        if ($medal->isLimited == 1 && $medal->num == 0) {//如果勋章有数量限制，则判断勋章的数量，如果为0，则置标识位为false
            $num_flag = 1;//勋章数量标识位，说明勋章数量有限制，并且数量为0
        } else {
            $num_flag = 2;//勋章无限制或者剩余数量不为0
        }
        if ($medal->type == 2) {//有时间限制
            if (!$medal->isOwned && $medal->endTime > time() && $num_flag == 2)//未点亮勋章未过期,并且勋章数量不为0
                $result['myStatus'] = 1;
            elseif (!$medal->isOwned && ($medal->endTime < time() || $num_flag == 1))//未点亮勋章已过期，时间过期或者勋章数量为0
                $result['myStatus'] = 2;
            elseif ($medal->isOwned && !$medal->isAwarded && $medal->prizeDeadline > time() && $medal->hasAwards == 1)//已点亮未领奖未过期
                $result['myStatus'] = 3;
            elseif ($medal->isOwned && $medal->hasAwards == 2)//已点亮无奖励
                $result['myStatus'] = 3;
            elseif ($medal->isOwned && !$medal->isAwarded && $medal->prizeDeadline < time() && $medal->hasAwards == 1)//已点亮未领奖奖励已过期（有奖励hasAwards == 1）
                $result['myStatus'] = 4;
            elseif ($medal->isOwned && $medal->isAwarded)//已点亮已领奖
                $result['myStatus'] = 5;
            else
                $result['myStatus'] = '';
        } else {//无时间限制
            if (!$medal->isOwned && $num_flag == 2)//未点亮勋章未过期
                $result['myStatus'] = 1;
            elseif (!$medal->isOwned && $num_flag == 1)
                $result['myStatus'] = 2;
            elseif ($medal->isOwned && !$medal->isAwarded && $medal->prizeDeadline > time())//已点亮未领奖未过期
                $result['myStatus'] = 3;
            elseif ($medal->isOwned && $medal->hasAwards == 2)//已点亮无奖励
                $result['myStatus'] = 3;
            elseif ($medal->isOwned && !$medal->isAwarded && $medal->prizeDeadline < time() && $medal->hasAwards == 1)//已点亮未领奖已过期
                $result['myStatus'] = 4;
            elseif ($medal->isOwned && $medal->isAwarded)//已点亮已领奖
                $result['myStatus'] = 5;
            else
                $result['myStatus'] = '';
        }

        //获取分享信息
        $share_info = $this->getShareConf($medal->id, $medal->name);
        if ($share_info) {
            $result['shareinfo'] = $share_info;
        }

        //根据券组ID获取奖品列表
        if ($medal->prizeList) {
            $prizeList = array($medal->prizeList);
            $request_o2o = new RequestGetGroupList();
            $request_o2o->setGroupIds($prizeList);
            try {
                $o2oService = new \core\service\O2OService();
                $response_o2o = $o2oService->requestO2O('NCFGroup\O2O\Services\CouponGroup', 'getGroupList', $request_o2o);
                if ($request_o2o && count($response_o2o['groupList']) > 0) {
                    foreach ($response_o2o['groupList'] as $group_name) {
                        $result['prizes'][] = array(
                            'id' => $group_name['id'],
                            'prizes' => $group_name['productName'],
                        );
                    }
                }
            } catch(\Exception $e) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $e->getLine(), $e->getMessage())));
            }
        }
        return $result;
    }

    /**
     * getAwards
     * 领取奖励
     * @param $request
     * @return $response
     */
    public function getAwards($request){
        try {
            $response = $this->requestMedal('NCFGroup\Medal\Services\Medal', 'acquireUserMedalAwards', $request);
        } catch (\Exception $e) {
            return false;
        }
        return $response;
    }

    /**
     * getMedal
     * 分享勋章详情专用
     *
     * @param integer $medalId
     * @access public
     * @return void
     */
    public function getMedal($medalId, $openapi = false) {
        $result = array();
        $request = new RequestGetMedal();
        $request->setMedalId(intval($medalId));
        try {
            $response = $this->requestMedal('NCFGroup\Medal\Services\Medal', 'getMedal', $request);
            if (empty($response)) {
                throw new \Exception('Medal getMedalInfo Response Error!');
            }

            $result = $openapi ? $this->buildOpenApiMedalInfo($response) : $this->buildP2PMedalInfo($response);
            return $result;
        } catch (\Exception $e) {
            //TODO LOG
            return false;
        }
        return false;
    }

    public function buildP2PMedalInfo($response) {

        $response = $response->toArray();
        $medalInfo = $response['medalInfo'];
        $medalRules = $response['medalRules'];
        $result = $medalInfo;
        $result['isLimited'] = $medalInfo['isLimited'] == 1 ? 1 : 0;
        $result['hasAwards'] = $medalInfo['hasAwards'] == 1 ? 1 : 0;
        //根据标识位，给进度标题名赋值
        $result['progressTitle'] = $medalInfo['optional'] == 1 ? '完成以下所有条件' : '完成以下条件之一';
        foreach ($medalRules as $rule) {
            $result['rules'][] = array(
                'name' => $rule['name'],
            );
        }
        if ($result['prizeList']) {
            $prizeList = explode(',', $result['prizeList']);
            $requestO2O = new RequestGetGroupList();
            $requestO2O->setGroupIds($prizeList);
            $responseO2O = $GLOBALS['o2oRpc']->callByObject(array(
                'service' => 'NCFGroup\O2O\Services\CouponGroup',
                'method' => 'getGroupList',
                'args' => $requestO2O,
            ));
            if ($responseO2O->getGroupList()) {
                foreach ($responseO2O->getGroupList() as $group) {
                    $result['prizes'][] = array(
                        'id' => $group['id'],
                        'name' => $group['productName'],
                    );
                }
            }
        }
        return $result;
    }

    // 这个方法为了先修复bug，先这样，这块得重构，太蛋疼了
    public function buildOpenApiMedalInfo($response) {
        $medal = $response->medalInfo;
        $medalRules = $response->medalRules;
        $result = array(
            'medalId' => $medal->id,
            'name' => $medal->name,
            'isOwned' => 0,
            'isHistory' => 0,
            'hasPrize' => 0,
            'hasLimit' => $medal->isLimited,
            'limitCount' => $medal->num,
            'medalSketch' => $medal->description,
            'startTime' => date('Y-m-d', $medal->startTime),
            'endTime' => date('Y-m-d', $medal->endTime),
            'description' => $medal->details,
            'progressTitle' => '',
            'progress' => '',
            'prizeTitle' =>'勋章奖励(可选择'.$medal->prizeNum.'个奖品)',
            'prizeSelectCount' => $medal->prizeNum,
            'prizes' => array(),
            'deadlineHint' =>'',
            'myStatus' => '',
            'isForBeginner' => $medal->isForBeginner,
            'remark' => "", //弹窗显示的内容
        );

        $result['icon'] = array(
            'iconLarge' =>$medal->bigLightenedImg,
            'iconLargeUnlighted' =>$medal->bigUnlightenedImg,
            'iconMedium' => $medal->mediumLightenedImg,
            'iconMediumUnlighted' => $medal->mediumUnlightenedImg,
            'iconSmall' => $medal->smallLightenedImg,
            'iconSmallUnlighted' => $medal->smallUnlightenedImg,
        );

        if ($medal->isLimited == 2) {
            $result['hasLimit'] = 0;//不限
        } elseif ($medal->isLimited == 1){
            $result['hasLimit'] = 1;//限量
        }

        //根据标识位，给进度标题名赋值
        if ($medal->optional == 1) {
            $result['progressTitle'] = '完成以下所有条件';
        } elseif ($medal->optional == 2) {
            $result['progressTitle'] = '完成以下条件之一';
        } else {
            $result['progressTitle'] = '';
        }

        // 条件
        foreach ($medalRules as $rule) {
            $result['progress'][] = array('pName' => $rule->name,
                'pNameStatus' => '',
                'isCompleted' => false,
            );
        }

        if ($medal->isLimited == 1 && $medal->num == 0) {//如果勋章有数量限制，则判断勋章的数量，如果为0，则置标识位为false
            $numFlag = 1;//勋章数量标识位，说明勋章数量有限制，并且数量为0
        } else {
            $numFlag = 2;//勋章无限制或者剩余数量不为0
        }

        // 勋章可不可用(这段其实没用，稍微梳理下看看)
        if (($medal->type == 2 && $medal->endTime < time()) || $numFlag == 1) {
            $result['myStatus'] = 2;
        } else {
            $result['myStatus'] = 1;
        }

        //获取分享信息
        $result['shareinfo'] = $this->getShareConf($medal->id, $medal->name);

        //根据券组ID获取奖品列表
        if ($medal->prizeList) {
            $prizeList = array($medal->prizeList);
            $request_o2o = new RequestGetGroupList();
            $request_o2o->setGroupIds($prizeList);
            try {
                $o2oService = new \core\service\O2OService();
                $response_o2o = $o2oService->requestO2O('NCFGroup\O2O\Services\CouponGroup', 'getGroupList', $request_o2o);
                if ($request_o2o && count($response_o2o['groupList']) > 0) {
                    foreach ($response_o2o['groupList'] as $group_name) {
                        $result['prizes'][] = array(
                            'id' => $group_name['id'],
                            'prizes' => $group_name['productName'],
                        );
                    }
                }
            } catch(\Exception $e) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $e->getLine(), $e->getMessage())));
            }
        }
        return $result;
    }

    public function getShareConf($medalId, $medalName) {
        if (empty(self::$shareConf)) {
            $siteId = \libs\utils\Site::getId();
            self::$shareConf = array(
                'url' => get_config_db('API_BONUS_SHARE_HOST', $siteId),
                'title' =>  get_config_db('MEDAL_INFO_SHARE_TITLE', $siteId),
                'content' => get_config_db('MEDAL_INFO_SHARE_CONTENT', $siteId),
            );
        }

        if (empty(self::$shareConf)) {
            return false;
        }

        $sn = \libs\utils\Aes::encode($medalId, self::MEDAL_ID_AES_KEY);
        return array(
            'url' => self::$shareConf['url'] . '/medal/Info?sn='. urlencode($sn),
            'title' => str_replace('{$MEDAL_NAME}', $medalName, self::$shareConf['title']),
            'content' => str_replace('{$MEDAL_NAME}', $medalName, self::$shareConf['content']),
        );
    }

    public function requestMedal($service, $method, $request, $timeOut = 2) {
        if (app_conf('MEDAL_SERVICE_ENABLE') == 0) {
            throw new \Exception('Medal Service is down');
        }

        $beginTime = microtime(true);
        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        if ($request instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase) {
            // 在底层请求里面统一传递，o2o对分站的支持
            $request->_site_id_ = \libs\utils\Site::getId();
            // 跨系统日志id的统一
            $request->_log_id_ = Logger::getLogId();

            if (method_exists($request, 'getUserId')) {
                $userService = new UserService();
                $userInfo = $userService->getUserViaSlave($request->getUserId());
                //用户注册时间
                $request->_register_time_ = $userInfo['create_time'] + 28800;
            } else {
                $request->_register_time_ = 0;
            }
        }

        Logger::info("[req]MedalService.{$className}.{$method}:".json_encode($request, JSON_UNESCAPED_UNICODE));

        try {
            $GLOBALS['medalRpc']->setTimeout($timeOut);
            $response = $GLOBALS['medalRpc']->callByObject(array(
                'service' => $service,
                'method' => $method,
                'args' => $request
            ));
        } catch (\Exception $e) {
            $exceptionName = get_class($e);
            \libs\utils\Alarm::push('medal_exception', $className.'_'.$method,
                'request: '.json_encode($request, JSON_UNESCAPED_UNICODE).',ename:' .$exceptionName. ',msg: '.$e->getMessage());

            Logger::error("MedalService.$service.$method.$exceptionName:".$e->getMessage());
            throw $e;
        }
        // TODO log response
        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        $res = ($res === false) ? 'invalid response: '.var_export($response, true) : mb_substr($res, 0, 1000);
        $elapsedTime = round(microtime(true) - $beginTime, 3);
        Logger::info("[resp][cost:{$elapsedTime}]MedalService.{$className}.{$method}:".$res);
        return $response;
    }
}

<?php

/**
 * ApiAdvConfAction.class.php
 *
 * Filename: ApiAdvConfAction.class.php
 * Descrition: 客户端广告位配置
 * Author: yanjun5@ucfgroup.com
 * Date: 16-10-18
 */
use core\service\BwlistService;
class ApiAdvConfAction extends CommonAction {

    private static $turnType = array(//跳转类型
            '0' => '网页', '1' => '理财', '2' => 'p2p理财列表', '3' => '专享列表', '5' => '交易中心列表',
            '6' => '基金', '7' => '证券', '8' => '借款','9' => '钱包','10' => '发现', '11' => '资讯',
            '12' => '媒体报道', '13' => '走进我们', '14' => '精彩活动','15' => '邀请好友', '16' => '红包',
            '17' => '礼券', '18' => '投资券', '19' => '勋章', '4' => '智多新', '26' => '健步', '29' => '网信出行',
            '30' => '领券中心', '34' => '任务中心',
            '-1' => '无',
            '25' => '充值',
    );
    private static $advType = array(//广告类型
            'home_carousel' => '首页轮播', 'home_recommond' => '首页推荐', 'discover_carousel' => '发现轮播',
            'discover_recommond' => '发现推荐', 'finance_carousel' => '理财页轮播', 'home_suspend_icon' => '首页悬浮图标',
            'center_suspend_icon' => '个人中心悬浮图标','p2p_carousel' => 'p2p理财页轮播','gold_carousel' => '黄金页轮播'
            ,'vip_account_adv' => '会员中心广告位', 'pop_window' => '弹窗广告', 'home_trip' => '首页出行',
            'vip_deal_carousel' => '尊享专区轮播', 'candy_carousel' => '信宝专区轮播', 'p2p_zone_carousel' => '网贷专区轮播',
            'home_carousel_second' => '首页轮播(v4.1)', 'market_suspend_icon' => '市场页悬浮图标', 'bonus_suspend_icon' => '红包页悬浮图标',
            'candy_suspend_icon' => '信宝中心悬浮图标',
    );

    // 悬浮标仅有一个
    private static $arrayIcon = array("pop_window", "home_suspend_icon", "center_suspend_icon", 'home_trip', 'market_suspend_icon', 'bonus_suspend_icon', 'candy_suspend_icon');

    public function index() {

        $condition['conf_type'] = 2;
        $condition['site_id'] = 1;
        //接收筛选条件
        $name = $_REQUEST['name'];
        if(array_key_exists($name, self::$advType)){
            $condition['name'] = $name;
        }
        $status = $_REQUEST['status'];
        if(!empty($status)){
            $condition['is_effect'] = intval($status)== 1 ? 1 :0;
        }
        //查列表
        $this->_list(M('ApiConf'), $condition,'id',true);
        $apiAdvlist = $this->get('list');
        //将广告内容json串转成数组
        $list = array();
        if(count($apiAdvlist) > 0) {
            foreach ($apiAdvlist as $k => $v){
                $v['value'] = json_decode($v['value'], true);
                $list[] = $v;
            }
        }

        $this->assign('list',$list);
        $this->assign('advType',self::$advType);
        $this->assign('turnType',self::$turnType);
        $this->display('index');
    }

    public function edit() {
        $condition['name'] = $_REQUEST['id'];
        if(!array_key_exists($condition['name'], self::$advType)){
            $this->error("非法操作！");
        }
        $condition['conf_type'] = 2;
        $advConf = M('ApiConf')->where($condition)->find();
        $advContent = json_decode($advConf['value'], true);//广告数组

        //跳转的类型
        $this->assign('turnType',self::$turnType);
        $this->assign('advContent',$advContent);
        $this->assign('name',$condition['name']);
        $this->assign('advType',self::$advType[$condition['name']]);
        $this->assign('status',$advConf['is_effect']);
        $this->assign('arrayIcon',self::$arrayIcon);
        $this->assign("jumpUrl",u("ApiAdvConf/index"));
        $this->display();
    }

    public function update() {
        $condition['name'] = $_REQUEST['name'];
        $data['value'] = $_REQUEST['value'];
        $status = $_REQUEST['status'];
        $data['is_effect'] = intval($status)== 1 ? 1 : 0;
        $verifyValue = json_decode($data['value']);
        $verifyValueForWhite = json_decode($data['value'],true);
        $flag = 1;
        $errorWhiteList = "";
        foreach ($verifyValueForWhite as $k => $v){
           if($v['userType'] == 4 && !BwlistService::isWhiteListExist($v['white_list'])){
               $flag = 0;
               $errorWhiteList = $v['white_list'];
               break;
           }
        }
        if($flag == 0 ) {
            if(empty($errorWhiteList)) $this->error("白名单为空！");
            else $this->error("白名单 {$errorWhiteList} 不存在！");
        }
        if(!array_key_exists($condition['name'], self::$advType) || empty($verifyValue)){
            $this->error("数据为空！");
        }
        $condition['conf_type'] = 2;
        // 保存
        $result = M('ApiConf')->where($condition)->save($data);
        //日志信息
        if (false !== $result) {
            //成功提示
            save_log(self::$advType[$condition['name']].L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log(self::$advType[$condition['name']].L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"));
        }
    }

    //判断图片的大小
    public function loadFile() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            $rel = array("code" => 0,"message" => "图片为空");
        }

        if (!empty($file)) {
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(600 / 1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
        }
        if(!empty($result['aid']) && empty($result['errors'])){
            $imgUrl = get_attr($result['aid'],1,false);
            $rel = array("code" => 1,"imgUrl" => $imgUrl);
        }else if(!empty($result['errors'])){
            $rel = array("code" => 0,"message" => end($result['errors']));
        }else{
            $rel = array("code" => 0,"message" => "图片上传失败");
        }
        echo  json_encode($rel);
    }
    public function index2() {
        $this->index();
    }
}

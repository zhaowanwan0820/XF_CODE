<?php

/**
 * ApiTabConfAction.class.php
 *
 * Filename: ApiTabConfAction.class.php
 * Descrition: 客户端tab图片和背景图片配置
 * Author: yanjun5@ucfgroup.com
 * Date: 16-11-24
 */
class ApiTabConfAction extends CommonAction {

    const TAB_IMG_SIZE = 50;
    const BACK_IMG_SIZE = 150;
    const TAB_CONF_TYPE = 4;
    const TAB_KEY = 'tab_back_img';

    private static $tabIcon = array('我的','投资','借款','钱包','发现');
    public function index() {

        $condition['conf_type'] = self::TAB_CONF_TYPE;
        $condition['site_id'] = 1;
        $condition['name'] = "tab_back_img";

        $apiTablist = M('ApiConf')->where($condition)->find();
        $img = json_decode($apiTablist['value'], true);

        $this->assign('tabImg',$img['tabImg']);
        $this->assign('backImg',$img['backImg']);
        $this->assign('startTime',$img['startTime']);
        $this->assign('endTime',$img['endTime']);
        $this->assign('status',$apiTablist['is_effect']);
        $this->assign('tabIcon',self::$tabIcon);
        $this->display();
    }

    public function update() {
        //参数校验
        if(empty($_POST['tabImg']) && empty($_POST['backImg']) ){
            $this->error("数据为空！");
        }

        if(empty($_POST['startTime']) || empty($_POST['endTime']) || (intval($_POST['endTime']) < intval($_POST['startTime']))){
            $this->error("请重新选择时间！");
        }

        //有效期
        $time['startTime'] = intval($_POST['startTime']);
        $time['endTime'] = intval($_POST['endTime']);

        //tab图片 和 背景图片
        $tabImg['tabImg'] = count($_POST['tabImg']) == 5 ? $_POST['tabImg'] : null;
        $backImg['backImg'] = !empty($_POST['backImg']) ? $_POST['backImg']: null;

        $value = array_merge($tabImg,$backImg,$time);

        $data['value'] = json_encode($value, JSON_UNESCAPED_SLASHES);
        $data['is_effect'] = intval($_POST['status']) == 1 ? 1 : 0;

        $condition['name'] = self::TAB_KEY;
        $condition['conf_type'] = self::TAB_CONF_TYPE;
        $condition['site_id'] = 1;
        $result = M('ApiConf')->where($condition)->data($data)->save();

        //日志信息
        if (false !== $result) {
            //成功提示
            save_log("客户端TAB和背景图片配置成功",1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log("客户端TAB和背景图片配置失败",0);
            $this->error(L("UPDATE_FAILED"));
        }
    }

    //判断tab图片
    public function tabLoadFile() {
        $this->loadFile(self::TAB_IMG_SIZE);
    }

    //判断背景图片
    public function backLoadFile() {
        $this->loadFile(self::BACK_IMG_SIZE);
    }

    public function loadFile($size) {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            $rel = array("code" => 0,"message" => "图片为空");
        }

        if (!empty($file)) {
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(intval($size) / 1024, 2),
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
}

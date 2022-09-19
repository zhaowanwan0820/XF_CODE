<?php
/**
 *2018年会导出用户头像临时功能
 */

use libs\utils\Logger;
use core\service\UserProfileService;

class PartyAction extends CommonAction {

    public function index() {
        $this->display ();
        return;
    }

    private static function _iframe_alert($msg, $is_reload = 0) {
        printf('<script>window.parent.party_alert("%s","%s")</script>', $msg, $is_reload);
        exit;
    }

    private function _make_csv_data() {
        $csv_data = array();

        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== false) {
            if(fgetcsv($handle) !== false){ //第一行是标题不放到数据列表里
                while (($row_data = fgetcsv($handle)) !== false) {

                    if (count($row_data) != 3) {
                        self::_iframe_alert(sprintf("序号%d，数据应该是3列，该行是 %s列！", $row_data[0], count($row_data)));
                    }

                    $csv_data[$row_data[0]] = array(
                            'csv_key' => $row_data[0],
                            'mobile' => $row_data[1] ? trim($row_data[1]) : '',
                            'name' => $row_data[2] ? trim($row_data[2]) : '',
                            );
                }
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        return $csv_data;
    }

    public function importCsvShortAlias() {
        if ($_FILES['upfile']['error'] == 4) {
            self::_iframe_alert("请选择文件！");
            exit();
        }
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            self::_iframe_alert("请上传csv格式的文件！");
            exit();
        }
        $csv_data = $this->_make_csv_data();
        if (empty ( $csv_data )) {
            self::_iframe_alert("文件内容不能为空");
            exit();
        }

        $error = array();
        $correct = array();
        foreach($csv_data as $data){
            if (empty ( $data ['mobile'] )) {
                $error[]= sprintf("序号:%s，手机号不能为空",$data['csv_key']);
            }else{
                $userModel = new \core\dao\UserModel ();
                $userInfo = $userModel->getUserinfoByUsername ( $data ['mobile'] );
                if(empty ( $userInfo )){
                    $error[]= sprintf("序号:%s，手机号：%s，用户不存在",$data['csv_key'],$data['mobile']);
                }elseif(!empty($data['name'])&&(empty ( $userInfo['real_name'] ) ||$userInfo['real_name'] != iconv ( 'GBK', 'UTF-8', $data['name'] ))) {
                    $error[]=sprintf("序号:%s，手机号：%s，用户名：%s，根据手机号获取的用户名：%s",$data['csv_key'],$data['mobile'],$data['name'],$userInfo['real_name']);
                }else{
                    $userImageInfo['attachment']='';
                    $userImageModel = new \core\dao\UserImageModel ();
                    $result=$userImageModel->getUserImageInfo($userInfo['id']);
                    if($result && !empty($result['attachment'])){
                        if (stripos($result['attachment'], 'http') === 0) {
                            $userImageInfo['attachment'] = $result['attachment'];
                        } else {
                            $userImageInfo['attachment'] = 'http:' . (isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : '//static.firstp2p.com') . '/' . $result['attachment'];
                        }
                    }else{
                        $userProfileService = new UserProfileService();
                        $result=$userProfileService->getUserHeadImg($data['mobile']);
                        $userImageInfo['attachment']=$result['headimgurl'];
                    }
                    $data['id']=$userInfo['id'];
                    $data['url']=$userImageInfo['attachment'];
                    unset($data['name']);
                    $correct[]=$data;
                }
            }
            unset($data);
        }
        if(!empty($error)){
            self::_iframe_alert(implode("\\n", $error));
        }elseif(empty($correct)){
            self::_iframe_alert('没有正确数据',1);
        }else{
            $content = implode ( ',', array (
                        '序号',
                        '手机号',
                        '用户ID',
                        '头像存储地址'
                        ) ) . "\n";
            foreach($correct as $k){
                $content .= implode(",",$k)."\n";
            }
            $datatime = date ( "YmdHis", get_gmtime () );
            header ( "Content-Disposition: attachment; filename=user_image_url_data_{$datatime}.csv" );
            echo iconv ( 'utf-8', 'gbk//ignore', $content );
        }
    }
}

<?php
/**
 * 广告联盟
 * @author daiyuxin@ucfgroup.com
 * @modify daiyuxin 2014-7-14
 */
use core\dao\AdunionAdunitTplModel;
use core\dao\AdunionAdunitModel;
use core\dao\AdunionChannelModel;
use core\dao\AdunionPubModel;

class AdUnitAction extends CommonAction{



    public function index() {
        echo "test";
    }

    private function getMockData(){
        $mockData = array(
            '{{deal.title}}' => '100起投<特惠标>，车易盈123-2',
            '{{deal.rate}}' => '12',
            '{{deal.timelimit}}' => '3个月',
            '{{deal.total}}' => '100.00万',
            '{{commonData.total_amount}}' => '1279,150,000',
            '{{commonData.total_amount_w}}' => '127,915.00',
            '{{commonData.total_amount_y}}' => '12.79',
            '{{commonData.total_profit}}' => '7,200,000',
            '{{commonData.total_profit_w}}' => '720.00',

            '{{deal.repayment}}' => '到期支付本金收益',


        );
        return $mockData;
    }
    private function mockAd($adContent){
            $adContent = str_replace("href=\"/static/css", "href=\"http://u.firstp2p.com/static/css", $adContent);
            $adContent = str_replace("src=\"", "src=\"http://u.firstp2p.com", $adContent);
            $mockData = $this->getMockData();
            foreach($mockData as $k => $v){
                $adContent = str_replace($k, $v, $adContent);
            }

            $adContent = preg_replace('/\{%[\s\S]*?%\}/', '', $adContent);

            return $adContent;
    }

    public function listAdTpl(){
        $adunionAdunitTplModel = new AdunionAdunitTplModel;

        $data = $adunionAdunitTplModel->getTplList();


        $mockData = $this->getMockData();
        foreach($data as &$item){
            $item['content'] = $this->mockAd($item['content']);
            $adTypes[] = $item['name'];

        }

        $this->assign ( 'list', $data );
        $this->assign ( 'adTypes', $adTypes);

        $this->display();
    }


    public function addAdTpl(){
        $this->display();
    }

    public function doAddAdTpl(){

        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $ret = $adunionAdunitTplModel->insertTpl($_POST);
        if($ret > 0){
            echo "<script>alert('Success');</script>";
        }else{
            echo "<script>alert('Fail');</script>";
        }
        echo "<script>location.href='/m.php?m=AdUnit&a=addAdTpl';</script>";

    }

    public function editAdTpl(){
        $adId = $_GET['adId'];
        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $adTpl = $adunionAdunitTplModel -> getTplById("id = $adId");
        $adTpl = $adTpl[0];
        $this->assign('adTpl', $adTpl);
        $this->display();
    }

    public function doEditAdTpl(){
        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $ret = $adunionAdunitTplModel->updateByAdId($_POST, $_POST['id']);
        if($ret > 0){
            echo "<script>alert('Success');</script>";
        }else{
            echo "<script>alert('Fail');</script>";
        }
        echo "<script>location.href='/m.php?m=AdUnit&a=listAdTpl'</script>";
    }

    public function doDelAdTpl(){

        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $ret = $adunionAdunitTplModel->updateByAdId(array('is_delete' => 1), $_GET['adId']);
        if($ret > 0){
            echo "<script>alert('Success');</script>";
        }else{
            echo "<script>alert('Fail');</script>";
        }
        echo "<script>location.href='/m.php?m=AdUnit&a=listAdTpl'</script>";
    }



    public function addAd(){

        $pubId = $_GET['pubId'];
        $adunionChannelModel = new AdunionChannelModel;
        $channels = $adunionChannelModel->getChannels("pub_id = $pubId");
        $this->assign('pubId', $pubId);
        $this->assign('channels', $channels);
        $this->display();
    }



    public function getAdTypes(){
        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $adTypes = $adunionAdunitTplModel->getAdTypes();

        echo json_encode($adTypes);
    }

    public function getAdSizeColorByTplId(){
        $tplId = urldecode($_GET['tplId']);
        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $ret = $adunionAdunitTplModel->getAdSizeColorByTplId($tplId);
        $size = explode(",",$ret[0]['size']);
        $color = explode(",",$ret[0]['color']);
        $rows = $ret[0]['rows'];
        echo json_encode(array('size'=>$size, 'color'=>$color, 'rows'=>$rows));
    }

    public function previewAd(){
        $tplId = urldecode($_GET['tplId']);
        $adunionAdunitTplModel = new AdunionAdunitTplModel;
        $ret = $adunionAdunitTplModel->getAdContentByTplId($tplId);

        $adContent = $this->mockAd($ret[0]['content']);
        echo $adContent;

    }


    public function saveAd(){
        $data = $_POST;
        $data['create_time'] = time();
        $adunionAdunitModel = new AdunionAdunitModel;
        $ret = $adunionAdunitModel->insertAd($data);
        if($ret > 0){
            echo json_encode(array('errorno' => 0));
        }else{
            echo json_encode(array('errorno' => 1));
        }
    }


    public function listAd(){
        $pubId = $_GET['pubId'];
        if(!empty($pubId)) {
            $condition = "pub_id = $pubId";
        }else{
            $condition = "";
        }
        
        $adunionAdunitModel = new AdunionAdunitModel;


        $ret = $adunionAdunitModel->getAdById($condition);  


        $pubIdsSet = array();
        $channelIdsSet = array();

        foreach($ret as $item){
            $pubIdsSet[] = $item['pub_id'];
            $channelIdsSet[] = $item['channel_id'];
        }

        $pubIdsSet = array_unique($pubIdsSet);
        $channelIdsSet = array_unique($channelIdsSet);


        $pubIds = implode(",", $pubIdsSet);
        $channelIds = implode(",", $channelIdsSet);


        $adunionPubModel = new AdunionPubModel;
        $pubRet = $adunionPubModel->getPubByPubId("id IN ($pubIds)");
        $pub = array();
        foreach($pubRet as $item){
            $pub[$item['id']] = $item;
        }



        $adunionChannelModel = new AdunionChannelModel;
        $channelRet = $adunionChannelModel->getChannels("id IN ($channelIds)");
        // var_dump($channelRet);
        $channel = array();
        foreach($channelRet as $item){
            $channel[$item['id']] = $item;
        }



        foreach($ret as &$item){
            $item['channel_name'] = $channel[$item['channel_id']]['name'];
            $item['pub_name'] = $pub[$item['pub_id']]['name'];
            $item['link_coupon'] = $channel[$item['channel_id']]['link_coupon'];
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);
        }

        

        // var_dump($channelIds);exit;
        $this->assign('pubId', $pubId);
        $this->assign('list', $ret);
        $this->display();

    }



    public function previewRealAd(){
        
        $code = base64_decode($_GET['code']);
        $code = urldecode($code);
        $code = str_replace("wm.js", "wm.preview.js", $code);
        echo sprintf("<html><head><title>preview</title></head><body>%s</body></html>", $code);

    }


    public function doDelAd(){

        $adunionAdunitModel = new AdunionAdunitModel;
        $ret = $adunionAdunitModel->updateById(array('is_delete' => 1), $_GET['id']);
        if($ret > 0){
            echo "<script>alert('Success');</script>";
        }else{
            echo "<script>alert('Fail');</script>";
        }
        echo "<script>location.href='/m.php?m=AdUnit&a=listAd'</script>";
    }


}
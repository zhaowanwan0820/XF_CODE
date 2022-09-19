<?php
/**
 * Created by PhpStorm.
 * User: qianyi
 * Date: 2018/9/29
 * Time: 19:31
 */
class DbMonitorAction extends CommonAction{

    private static $db_list = array(
        'firstp2p','firstp2p_deleted','firstp2p_moved','firstp2p_payment','duotou','contract','ncfph','msg_box','profile','itil','vip','candy','ncfwx_div','ncfph_div','ncfph_moved_div'
    );

    public function index(){
        foreach(self::$db_list as $db_name) {

            $result = \SiteApp::init()->dataCache->getRedisInstance()->hGetAll($db_name);
            if($result == null)
                continue;
            foreach ($result as $key => $value){
                $v[] = json_decode($value,true);
            }
        }
        foreach ($v as $key=>$value){
            $addcount[$key] = $value['addCount'];
        }
        array_multisort($addcount,SORT_NUMERIC,SORT_DESC,$v);

        $this->assign("data",$v);
        $this->display ();
    }
}

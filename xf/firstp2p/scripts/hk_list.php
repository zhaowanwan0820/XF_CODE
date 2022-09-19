<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/11/9
 * Time: 14:02
 */

require(dirname(__FILE__) . '/../app/init.php');

use libs\utils\Logger;
class Hklist {

    public function run(){
        $ps =  10;
        for($i=0;$i<=1;$i++){
            $isFirstp2p = ($i ? true : false);
            for($pn=1;$pn<=2;$pn++){
                $this->genList($pn,$ps,$isFirstp2p);
            }
        }
    }

    public function genList($pn,$ps,$isFirstp2p){
        $rpc = new \libs\rpc\Rpc();
        $ret = \SiteApp::init()->dataCache->call($rpc, 'local', array('DealRepayService\getRepayDealListV2', array($ps,$pn,$isFirstp2p)), 3600);
        if(!$ret){
            Logger::error('Hklist gen fail page:'.$pn);
        }else{
            Logger::info('Hklist gen succ page:'.$pn);
        }
    }
}

$class = new Hklist();
$res = $class->run();
<?php

/* php scripts/onetime/flush_deal.php 1  刷放款时间为1501488000,1477900800和1516780800的标的
/* php scripts/onetime/flush_deal.php 2   刷指定dealIds的标的，使用逗号区分
 * 刷产品大类
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';

use core\dao\DealModel;
use core\dao\DealProjectModel;
use libs\utils\Logger;
use \libs\Db\Db;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(isset($argv[1])){
    // 用于区分不同操作类型
    $operationType = intval($argv[1]);
}else{
    echo '操作类型参数错误!';
    exit;
}

if(isset($argv[2])){
    $dealStart = intval($argv[2]);
}else{
    echo '标的起点参数错误!';
    exit;
}

if(isset($argv[3])){
    $limit = intval($argv[3]);
}else{
    $limit = 1000;
}


while(true){
    if($operationType == 1){
        $sql = "SELECT a.*,b.id as project_id,b.product_name,b.product_class FROM `firstp2p_deal` a LEFT JOIN `firstp2p_deal_project` b on a.project_id=b.id  WHERE a.id > ".$dealStart."  AND a.project_id!=0   and a.`deal_type` = 0 AND a.`repay_start_time` IN (1501488000, 1477900800, 1516780800)  AND  a.product_class_type = 0 AND a.loan_user_customer_type = 0 AND a.deal_status  IN (3,5) ORDER BY a.id ASC LIMIT ".$limit;
    }elseif($operationType == 2){
       $sql = sprintf("SELECT a.*,b.id as project_id,b.product_name,b.product_class FROM `firstp2p_deal` a LEFT JOIN `firstp2p_deal_project` b on a.project_id=b.id  WHERE a.id IN (%s)" , $argv[2]);
    }else{
        break;
    }

    $deals = Db::getInstance('firstp2p','slave')->getAll($sql);

    if(empty($deals)){
        break;
    }

    foreach($deals as $deal){
        $product_class_type = 0;
        $loan_user_customer_type = 0;
        $product_class = 0;

        try {
            //$dealProject = DealProjectModel::instance()->find($deal['project_id']);

            $dealStart = $deal['id'];
            $product_name = $deal['product_name'];
            $product_class = $deal['product_class'];

            if($product_name == ''){
                Logger::info("flush deal product name is null :".$deal['id']);
                continue;
            }

            Logger::info("flush deal start ".$deal['id']);

            //产品大类
            //消费贷
            if (in_array($deal['product_name'], array("首转1号", "信盈1号", "通贝1号", "首信1号", "首信3号（1）", "首信3号（2）", "首信7号（1）", "首信7号（2）", "首信9号（1）", "首信9号（2）", "首信6号（1）", "首信3号（3）", "首信6号（3）", "首信6号（2）", "闪电借款", "新立盈1号", "勿删汇赢3号", "云祥5号", "功夫贷", "信石1号", "优易借", "闪电借款（2）"))) {
                $product_class_type = 5;
                $product_class = "消费贷";
            }

            //消费贷
            if (in_array($deal['product_name'], array("车贷通（1）", "车贷通（2）", "车贷通（3）"))) {
                $product_class_type = 5;
                $product_class = "消费贷";
            }

            //个体经营贷
            if (in_array($deal['product_name'], array("易车贷"))) {
                if (in_array($deal['product_class'], array("产融贷"))) {
                    if(inCrdTime($deal['repay_start_time'])) {
                        $product_class_type = 232;
                        $product_class = "个体经营贷";
                    }
                }
            }

            //个体经营贷
            if (in_array($deal['product_name'], array("首信2号"))) {
                if (in_array($deal['product_class'], array("消费贷"))) {
                    if(inXfdTime($deal['repay_start_time'])){
                        $product_class_type = 232;
                        $product_class = "个体经营贷";
                    }
                }
            }

        //供应链
        if (in_array($deal['product_name'], array("云祥1号", "汇赢3号", "长兴8号", "友居贷3号", "优享1号", "金信1号", "金钰1号", "金樽1号", "宝宝钱包", "影娱贷1号", "鼎鑫1号", "易收通5号", "信和2号", "艺财1号"))) {
            if (in_array($deal['product_class'], array("产融贷"))) {
                if(inCrdTime($deal['repay_start_time'])) {
                    $product_class_type = 223;
                    $product_class = "供应链";
                }
            }
        }

            //供应链
            if (in_array($deal['product_name'], array("电商贷"))) {
                $product_class_type = 223;
            }


            //借款客群
            //普通消费者
            if (in_array($deal['product_name'], array("闪电借款", "闪电借款（2）", "优易借", "信石1号"))) {
                if(inXfdTime($deal['repay_start_time'])) {
                    $loan_user_customer_type = 1;
                }
            }

            //小微企业
            if (in_array($deal['product_name'], array("云祥1号", "汇赢3号", "长兴8号", "友居贷3号", "优享1号", "金信1号", "金钰1号", "金樽1号", "宝宝钱包", "影娱贷1号", "鼎鑫1号", "易收通5号", "信和2号", "艺财1号"))) {
                if(inCrdTime($deal['repay_start_time'])) {
                    $loan_user_customer_type = 4;
                }
            }

            //个体工商户
            if (in_array($deal['product_name'], array("首信2号"))) {
                if(inXfdTime($deal['repay_start_time'])) {
                    $loan_user_customer_type = 1;
                }
            }

            //自就业者
            if (in_array($deal['product_name'], array("首信1号", "首信3号（1）", "首信3号（2）", "首信7号（1）", "首信7号（2）", "首信9号（1）", "首信9号（2）", "首信6号（1）", "首信3号（3）", "首信6号（3）", "首信6号（2）", "车贷通（1）", "车贷通（2）", "车贷通（3）", "功夫贷", "易车贷"))) {
                if(($deal['product_name'] == '易车贷') && (inCrdTime($deal['repay_start_time']))){
                    $loan_user_customer_type = 3;
                }elseif(inXfdTime($deal['repay_start_time'])){
                    $loan_user_customer_type = 3;
                }
            }

            //更新deal project
            if ($product_class) {
                $projectSql = "UPDATE `firstp2p_deal_project` SET product_class='{$product_class}' WHERE id=".$deal['project_id'];

                $projectRes = Db::getInstance('firstp2p')->query($projectSql);
                if(!$projectRes){
                    throw new \Exception("保存deal project 失败!");
                }else{
                    Logger::info("flush project ".$deal['project_id']." success!");
                }
            }

            if (($loan_user_customer_type > 0) || ($product_class_type > 0)) {
                if ($loan_user_customer_type > 0) {
                    $dealData['loan_user_customer_type'] = $loan_user_customer_type;
                }

                if ($product_class_type > 0) {
                    $dealData['product_class_type'] = $product_class_type;
                }

                $dealRes = Db::getInstance('firstp2p')->update('firstp2p_deal',$dealData,"id=".$deal['id']);

                if(!$dealRes){
                    throw new \Exception("保存deal失败!");
                }else{
                    Logger::info("flush deal ".$deal['id']." success!");
                }
            }


        }catch (\Exception $ex){
            Logger::info("flush deal ".$deal['id']." fail!".$ex->getMessage());
        }
    }
    // operationType 为2，则直接退出循环
    if($operationType == 2){
        break;
    }
}


function inCrdTime($time){
    $crdStart = 1501488000;
    $crdEnd = 1516780800;

    if(($time >= $crdStart) AND ($time <= $crdEnd)){
        return true;
    }else{
        return false;
    }
}

function inXfdTime($time){
    $xfdStart = 1477900800;
    $xfdEnd = 1516780800;

    if(($time >= $xfdStart) AND ($time <= $xfdEnd)){
        return true;
    }else{
        return false;
    }
}

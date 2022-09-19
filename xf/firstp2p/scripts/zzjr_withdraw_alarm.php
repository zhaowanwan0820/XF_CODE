<?php
/**
 * zzjr_withdraw_alarm.php
 *
 * @date 2017年05月08日
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 * #定时任务脚本，检查掌众提现接口回调状态长时间处于待处理或者处理中标的，如果超过规定时间，则发出相应的告警
 */


require(dirname(__FILE__) . '/../app/init.php');
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;

echo "\t*** start ".date('Y-m-d H:i:s')." ***\n";

set_time_limit(0);

class ZzjrWithdrawStatus {

    public function run() {
        $start = microtime(true);
        //查询近5天的数据
        $checkTime = time() - 5*24*3600;
        $res = array();
        try {
            $sql = 'SELECT sup.id ,sup.withdraw_status,sup.out_order_id,sup.create_time,sup.bid,d.approve_number from '.DB_PREFIX.'supervision_withdraw sup LEFT JOIN '.DB_PREFIX.'deal d ON sup.bid = d.id
                    WHERE sup.bid != 0 AND d.type_id = 34 AND sup.create_time >'.$checkTime.' AND (sup.withdraw_status=3 OR sup.withdraw_status=0)';
            $res = $GLOBALS['db']->get_slave()->getAll($sql);
            if (!empty($res)) {
                $cmpTime = time();
                $count = 0;
                $flag = false;
                foreach ($res as $key=>$value) {
                    //如果状态持续10分钟，则记录告警
                    if ($cmpTime - $value['create_time'] > 600) {
                        if (!$this->checkValueCache($value['id'])) continue;
                        $flag = true;
                        $count++;
                        $content .= 'id:'.$value['id'].',out_order_id:'.$value['out_order_id'].',deal_id:'.$value['bid'].',approve_number:'.$value['approve_number'].',回调状态：'.$value['withdraw_status'].',创建时间：'.date ( "Y-m-d H:i:s",$value['create_time']).' | ';
                    }
                }
                if ($flag) {
                    $content = $count.'条超时==>>'.$content;
                    $mails = \dict::get("ZZJR_WITHDRAW_ALARM_EMAIL");//相应业务人员发送邮件名单，后台配置
                    $phones = \dict::get("ZZJR_WITHDRAW_ALARM_PHONE");//相应业务人员发送短信手机号，后台配置
                    $title = '掌众提现支付回调超时';
                    \libs\utils\Alarm::push('ZzjrWithdrawStatus', $title, $content);
                    $this->send_mail_sms($mails, $phones,$title,$content);
                }
            }

            //检查标的列表里含有的掌众标的createtime>15min && supervision_withdraw 表中不存在，存在这种情况则报警
            $checkZzTimeMax = time() - 900 - 28800;//deal表中的数据超过10分钟没有写进supervision_withdraw
            $checkTimeSup = time() - 3*24*3600 - 28800;//最近3天的记录，deal表差八小时
            $zz_sql = 'SELECT d.id,d.approve_number,d.success_time from '.DB_PREFIX.'deal d LEFT JOIN '.DB_PREFIX.'supervision_withdraw sup ON d.id = sup.bid
                     WHERE d.type_id = 34 AND d.create_time > '.$checkTimeSup.' AND d.create_time < '.$checkZzTimeMax.' AND sup.id is null';
            $zz_res = $GLOBALS['db']->get_slave()->getAll($zz_sql);
            if (!empty($zz_res)) {
                $count = 0;
                $zz_content = 'deal_id--approve_number:';
                $zz_overtime_content = 'deal_id--approve_number:';
                foreach ($zz_res as $key=>$value) {
                    if (!$this->checkValueCache($value['id'])) continue;
                    if ($value['success_time'] > 0 )
                        $zz_overtime[] = $value['id'].'--'.$value['approve_number'];
                    else
                        $zz_match[] = $value['id'].'--'.$value['approve_number'];
                    $count++;
                    if ($count >10) break;
                }
                $mails = \dict::get("ZZJR_WITHDRAW_ALARM_EMAIL");//相应业务人员发送邮件名单，后台配置
                $phones = \dict::get("ZZJR_WITHDRAW_ALARM_PHONE");//相应业务人员发送短信手机号，后台配置
                if (count($zz_overtime) > 0 ){
                    $zz_overtime_content .= implode(',',$zz_overtime);
                    $zz_overtime_content = count($zz_overtime).'条掌众放款超时==>>'.$zz_overtime_content;
                    $title = '掌众放款超时';
                    \libs\utils\Alarm::push('ZzjrWithdrawStatus', $title, $zz_overtime_content);
                    $this->send_mail_sms($mails, $phones,$title,$zz_overtime_content);
                }
                if (count($zz_match) > 0 ) {
                    $zz_content .= implode(',',$zz_match);
                    $zz_content = count($zz_match).'条掌众匹配超时==>>'.$zz_content;
                    $title = '掌众匹配超时';
                    \libs\utils\Alarm::push('ZzjrWithdrawStatus', $title, $zz_content);
                    $this->send_mail_sms($mails, $phones,$title,$zz_content);
                }
            }
            $time = round(microtime(true) - $start, 4);
            Logger::info(implode('|', array(__FILE__,__CLASS__,__FUNCTION__,'耗时：'.$time,$content,$zz_content)));

        } catch(Exception $e) {
            \libs\utils\Alarm::push('ZzjrWithdrawStatus','脚本执行异常', __FILE__.__CLASS__.__FUNCTION__.$e->getMessage());
            Logger::info(implode(" | ",array(__FILE__,__CLASS__,__FUNCTION__,'脚本执行异常',$e->getMessage())));
        }
    }
    public function send_mail_sms($mails,$phones,$title,$content) {
        $msgcenter = new msgcenter();
        if ($mails) {
            foreach ($mails as $value) {
                $msgcenter->setMsg($value, '', $content, false,$title);
            }
        }
        if ($phones) {
            foreach ($phones as $value) {
                $msgcenter->setMsg($value, '', array($title.$content), 'TPL_SMS_ZZ_ALARM_NOTIFY');
            }
        }
        $result = $msgcenter->save();
        return $result;
    }
    public function checkValueCache($value) {
        if (empty($value)) {
            return false;
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key = 'script-ZzjrWithdrawStatus-checkValueCache';
        $count = 100;
        $expire = 86400;
        $key_value = $redis->get($key);
        if (!$key_value)
        {
            $key_value = array();
        }else {
            $key_value = json_decode($key_value,true);
        }
        
        //如果已经存在缓存了，则不再加入告警队列
        if (in_array($value,$key_value)) {
            return false;
        }
        //缓存多于100个，则不再缓存
        if (count($key_value) > 100) {
            return true;
        }
        array_push($key_value,$value);
        if (count($key_value) == 1) {
            $redis->setex($key,$expire,json_encode($key_value));
            return true;
        }
        $redis->set($key,json_encode($key_value));
        return true;
    }

}
echo "\t*** end ".date('Y-m-d H:i:s')." ***\n";
(new ZzjrWithdrawStatus)->run();


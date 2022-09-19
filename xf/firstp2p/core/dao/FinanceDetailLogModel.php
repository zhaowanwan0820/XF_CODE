<?php
namespace core\dao;
use libs\caching\RedisCache;

class FinanceDetailLogModel extends BaseModel {

    protected $qname = '_queue_log:_transfer';
    /**
    提供cron数据收集入口
    */
    public function quickLog($data) {
        $redis = \SiteApp::init()->cache;
        if (is_array($data)) {
             $data = json_encode($data);
        }
        $ret = $redis->lpush($this->qname, $data);
        if (!$ret)  {
            \libs\utils\Alarm::push('payment', 'transfer.notice', '对账详情入redis队列失败'  . $data);
        }
        // var_dump($ret);
        // var_dump('redis lenth ' . $redis->llen($this->qname));
        // var_dump('data ===========', json_decode($redis->rpop($this->qname), true));
        return $ret > 0;
    }

    public function consume($count = 20) {
        $counter = 0;
        $itemCount  = 0;
        $redis = \SiteApp::init()->cache;
        echo '队列长度' . $redis->llen($this->qname) . "\n";
        if ($redis->llen($this->qname)) {
            while( $counter ++  < $count) {
                $data = $redis->rpop($this->qname);
                $data = json_decode($data, true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        $itemCount ++;
                        $this->setRow(array_merge(array('create_time' => time()), $item )) ;
                        $last_insert_id = $this->insert();
                        if(!$last_insert_id) {
                            \libs\utils\Alarm::push('payment', 'transfer.notice', '对账详情入详情数据表失败'  . json_encode($item));
                        }
                    }
                }
                else {
                    break;
                }
            }
        }
        echo '处理了' . $itemCount . '条记录' . "\n";
    }
}

<?php

class DebtExchangeNoticeCommand extends CConsoleCommand
{
    private $fnLockRun1 = '/tmp/DebtExchangeNoticeCommandRun1.pid';
    private $fnLockRun2 = '/tmp/DebtExchangeNoticeCommandRun2.pid';
    private $fnLockRun3 = '/tmp/DebtExchangeNoticeCommandRun3.pid';

    /**
     * 跑脚本加锁
     */
    private static function enterLock($config)
    {
        if (empty($config['fnLock'])) {
            return false;
        }
        $fnLock = $config['fnLock'];
        $fpLock = fopen($fnLock, 'w+');
        if ($fpLock) {
            if (flock($fpLock, LOCK_EX | LOCK_NB)) {
                return $fpLock;
            }
            fclose($fpLock);
            $fpLock = null;
        }

        return false;
    }

    /**
     * 检查跑脚本加锁
     */
    private static function releaseLock($config)
    {
        if (!$config['fpLock']) {
            return;
        }
        $fpLock = $config['fpLock'];
        $fnLock = $config['fnLock'];
        flock($fpLock, LOCK_UN);
        fclose($fpLock);
        unlink($fnLock);
    }

    //一直执行
    public function actionRun1()
    {
        $fpLock = self::enterLock(['fnLock' => $this->fnLockRun1]);
        if (!$fpLock) {
            exit(__CLASS__.' '.__METHOD__.'  running!!!');
        }
        self::echoLog('Handel_1 running');
        $now = time();
        $before5  = $now - 300;//
        $sql = "select * from xf_debt_exchange_notice where status in (0,2) and notice_time_1 > {$before5}  order by id desc";
        $notices  = XfDebtExchangeNotice::model()->findAllBySql($sql);
        if (!empty($notices)) {
            self::notify($notices);
        } else {
            self::echoLog('Handel_1 no data');
        }
        self::releaseLock(['fnLock' => $this->fnLockRun1, 'fpLock' => $fpLock]);
    }

    //一直执行
    public function actionRun2()
    {
        $fpLock = self::enterLock(['fnLock' => $this->fnLockRun2]);
        if (!$fpLock) {
            exit(__CLASS__.' '.__METHOD__.'  running!!!');
        }
        self::echoLog('Handel_2 running');
        $now = time();
        $before5  = $now - 300;//
        $sql = "select * from xf_debt_exchange_notice where status in (0,2) and notice_time_2 > {$before5} and notice_time_2 <= {$now}";
        $notices  = XfDebtExchangeNotice::model()->findAllBySql($sql);
        if (!empty($notices)) {
            self::notify($notices);
        } else {
            self::echoLog('Handel_2 no data');
        }
        sleep(1);
        self::releaseLock(['fnLock' => $this->fnLockRun2, 'fpLock' => $fpLock]);
    }
    //每分钟
    public function actionRun3()
    {
        $fpLock = self::enterLock(['fnLock' => $this->fnLockRun3]);
        if (!$fpLock) {
            exit(__CLASS__.' '.__METHOD__.'  running!!!');
        }
        self::echoLog('Handel_3 running');
        $now = time();
        $before5  = $now - 300;//
        $sql = "select * from xf_debt_exchange_notice where status in (0,2) and notice_time_3 > {$before5} and notice_time_3 <= {$now}";
        $notices  = XfDebtExchangeNotice::model()->findAllBySql($sql);
        if (!empty($notices)) {
            self::notify($notices);
        } else {
            self::echoLog('Handel_3 no data');
        }
        self::releaseLock(['fnLock' => $this->fnLockRun3, 'fpLock' => $fpLock]);
    }

    private static function notify($notices)
    {
        foreach ($notices as $n) {
            $platform = XfDebtExchangePlatform::model()->findByPk($n->appid);
            $secret = $platform->secret;
            $data = [
                'exchange_no' => $n->order_id,
                'createTime' => $n->created_at,
                'amount' => $n->amount,
                'status'=>'1',
            ];
            for ($i = 0 ;$i<=2;$i++) {
                $_notice['result'] = DES3::encrypt($data, $secret);
                $res = CurlRequest::send(urldecode($n->notify_url), 'POST', $_notice, [], false);
                
                if (strtoupper($res)==='SUCCESS') {
                    $n->status += 1;
                    $n->save();
                    break;
                } else {
                    self::echoLog('order_id:'.$n->order_id.' notify  result :'.print_r($res, true), 'error');
                }
            }
        }
    }

    /**
     * 日志记录.
     *
     * @param $yiilog
     * @param string $level
     */
    public static function echoLog($yiilog, $level = 'info')
    {
        echo date('Y-m-d H:i:s ').' '.microtime()."DebtExchangeNotice {$yiilog} \n";
        if ($level=='error') {
            Yii::log("DebtExchangeNotice: {$yiilog}", 'error', 'DebtExchangeNotice');
        }
    }
}

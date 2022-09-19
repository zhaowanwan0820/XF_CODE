<?php
/**
 * PaymentRemoteLog 远程日志
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */

namespace libs\utils;

use libs\utils\Logger;

class PaymentRemoteLog
{

    //请求超时时间 (微秒)
    const TIME_OUT_USEC = 300000;

    //写日志命令字
    const CMD_WRITE = 0x1;

    //协议开始标志
    const STX = 0xa;

    //协议结束标志
    const ETX = 0x3;

    //最大包长度 (约50k)
    const MAX_PACKAGE_LENGTH = 50000;

    //最大单条日志长度 (约20k)
    const MAX_SINGLE_LOG_LENGTH = 20000;

    //最大日志条数 (100个)
    const MAX_LOG_COUNT = 100;

    //最多重试次数
    const MAX_RETRY_TIMES = 2;

    private static $instance = array();

    /**
     * 单例入口
     * @string $ip 服务IP
     * @string $port 服务的端口
     * @return object
     */
    public static function instance($ip, $port)
    {
        $key = $ip.':'.$port;

        if (!isset(self::$instance[$key]))
        {
            self::$instance[$key] = new self($ip, $port);
        }

        return self::$instance[$key];
    }

    private $ip = '';

    private $port = '';

    private function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    //日志总长度
    private $length = 0;

    private $logInfo = array();

    /**
     * 添加日志 (加到缓存, 超过长度时提交)
     *
     * @param string $level 级别(如ERR, INFO...)
     * @param string $content 日志内容(如ERR, INFO...)
     * @return object
     */
    public function add($level, $content)
    {
        $content = strtr($content, "\n", ' ');

        //对长日志进行分片，短日志不会处理
        $contentArray = $this->split($content);

        foreach ($contentArray as $value)
        {
            //包过大分片提交
            if ($this->length + strlen($value) > self::MAX_PACKAGE_LENGTH || count($this->logInfo) >= self::MAX_LOG_COUNT)
            {
                $this->flush();
            }

            $this->logInfo[] = array(
                'level' => $level,
                'time' => time(),
                'content' => '['.Logger::getLogId().'] ['.$level.'] '.$value,
            );

            $this->length += strlen($value);
        }

        return $this;
    }

    /**
     * 切割日志内容为数组
     */
    private function split($content)
    {
        $length = strlen($content);
        if ($length <= self::MAX_SINGLE_LOG_LENGTH)
        {
            return array($content);
        }

        $contentArray = array();
        for ($i = 0; $i < $length; $i += self::MAX_SINGLE_LOG_LENGTH)
        {
            $contentArray[] = "[CHUNK:{$i}/{$length}] ".substr($content, $i, self::MAX_SINGLE_LOG_LENGTH);
        }

        return $contentArray;
    }

    /**
     * 清空日志缓冲区，远程上报
     */
    private function flush()
    {
        if (empty($this->logInfo))
        {
            return false;
        }

        $requestData = $this->packResquestData();

        $start = microtime(true);

        //Socket请求, 最多重试三次
        for ($i = 0; $i < self::MAX_RETRY_TIMES; $i++)
        {
            $responseData = $this->request($requestData);

            //请求成功则跳出循环
            if ($this->checkResponseData($responseData) === true)
            {
                break;
            }

            $this->log("Response error. retry:$i", Logger::ERR);
            if ($i === self::MAX_RETRY_TIMES - 1)
            {
                $this->log("Dump logs. ".json_encode($this->logInfo), Logger::ERR);
            }
        }

        //抽样记录请求时间日志
        if (mt_rand(0, 10000) === 0)
        {
            $this->log('RequestSample. length:'.strlen($requestData).', count:'.count($this->logInfo).', cost:'.round(microtime(true) - $start, 5));
        }

        $this->length = 0;
        $this->logInfo = array();

        return true;
    }

    /**
     * 发起Socket请求
     */
    private function request($requestData)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        $timeout = array('sec' => 0, 'usec' => self::TIME_OUT_USEC);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);

        socket_connect($socket, $this->ip, $this->port);
        $ret = socket_write($socket, $requestData, strlen($requestData));

        $responseData = '';
        while (($buffer = socket_read($socket, 65535)) !== false)
        {
            $responseData .= $buffer;
            break;
        }

        //错误日志
        $errno = socket_last_error($socket);
        if ($errno)
        {
            $this->log("Socket error. errno:$errno, error:".socket_strerror($errno), Logger::ERR);
            socket_clear_error($socket);
        }

        return $responseData;
    }

    /**
     * 打包请求包
     */
    private function packResquestData()
    {
        //协议: cStx + cCmd + dwSequence + cLogNum + stLogInfo[cLogNum]
        //stLogInfo = cstrType + dwTime + wstrContent
        $requestData = '';
        $requestData .= pack('C', self::STX);
        $requestData .= pack('C', self::CMD_WRITE);
        $requestData .= pack('N', self::getSequence());
        $requestData .= pack('C', count($this->logInfo));

        foreach ($this->logInfo as $log)
        {
            $requestData .= pack('C', strlen($log['level']));
            $requestData .= $log['level'];
            $requestData .= pack('N', $log['time']);
            $requestData .= pack('n', strlen($log['content']));
            $requestData .= $log['content'];
        }

        $requestData .= pack('C', self::ETX);

        return $requestData;
    }

    /**
     * 检查返回包
     */
    private function checkResponseData($responseData)
    {
        //协议: cStx + cCmd + dwSequence + cResult + dwReserved1 + dwReserved2 + cEtx
        if (strlen($responseData) === 0)
        {
            $this->log('Unpack error. response is empty.', Logger::ERR);
            return false;
        }

        $unpack = unpack('Cstx/Ccmd/Nsequence/Cresult', $responseData);

        //包开始标志是否正确
        if ($unpack['stx'] != self::STX)
        {
            $this->log("Unpack error. start char is error. length:".strlen($responseData), Logger::ERR);
            return false;
        }

        //请求序号是否正确
        if ($unpack['sequence'] != self::getSequence())
        {
            $this->log("Unpack error. sequence is not equal", Logger::ERR);
            return false;
        }

        //返回结果码是否成功
        if ($unpack['result'] != 0)
        {
            $this->log("Response result error. result:".$unpack['result'], Logger::ERR);
            return false;
        }

        return true;
    }

    /**
     * 静态成员，全局共享
     */
    private static $sequence = 0;

    /**
     * 获取日志序号，随机生成
     */
    public static function getSequence()
    {
        if (self::$sequence === 0)
        {
            //前17位是当天秒数，后15位存随机数
            self::$sequence = (time() % 86400) << 15 | mt_rand(0, 30000);
        }

        return self::$sequence;
    }

    /**
     * 本地日志
     */
    private function log($body, $level = Logger::INFO)
    {
        $destination = APP_ROOT_PATH.'/log/logger/paymentremotelog.'.date('y_m').'.log';
        Logger::wLog($body, $level, Logger::FILE, $destination);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->flush();
    }

}

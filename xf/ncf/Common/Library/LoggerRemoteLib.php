<?php

namespace NCFGroup\Common\Library;

class LoggerRemoteLib
{

    //请求超时时间 (微秒)
    const TIME_OUT_USEC = 200000;

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

    private $ip = '';

    private $port = '';

    private $errorlog = '';

    private $sequence = '';

    public function __construct($config)
    {
        $this->ip = $config->ip;
        $this->port = $config->port;
        $this->errorlog = $config->errorlog;
        $this->sequence = (time() % 86400) << 15 | mt_rand(0, 30000);
    }

    //日志总长度
    private $length = 0;

    private $logInfo = array();

    public function write($content, $level)
    {
        $this->add($content, $level);
    }

    /**
     * 添加日志 (加到缓存, 超过长度时提交)
     *
     * @param string $level 级别(如ERR, INFO...)
     * @param string $content 日志内容(如ERR, INFO...)
     * @return object
     */
    private function add($content, $level)
    {
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
                'content' => $value,
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

        //Socket请求, 最多重试三次
        for ($i = 0; $i < self::MAX_RETRY_TIMES; $i++)
        {
            $responseData = $this->request($requestData);

            //请求成功则跳出循环
            if ($this->checkResponseData($responseData) === true)
            {
                break;
            }

            $this->errorlog("Response error. retry:$i");
            if ($i === self::MAX_RETRY_TIMES - 1)
            {
                $this->errorlog("Dump logs. ".json_encode($this->logInfo));
            }
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
            $this->errorlog("Socket error. errno:$errno, error:".socket_strerror($errno).", host:{$this->ip}, port:{$this->port}");
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
        $requestData .= pack('N', $this->sequence);
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
            $this->errorlog('Unpack error. response is empty.');
            return false;
        }

        $unpack = unpack('Cstx/Ccmd/Nsequence/Cresult', $responseData);

        //包开始标志是否正确
        if ($unpack['stx'] != self::STX)
        {
            $this->errorlog("Unpack error. start char is error. length:".strlen($responseData));
            return false;
        }

        //请求序号是否正确
        if ($unpack['sequence'] != $this->sequence)
        {
            $this->errorlog("Unpack error. sequence is not equal");
            return false;
        }

        //返回结果码是否成功
        if ($unpack['result'] != 0)
        {
            $this->errorlog("Response result error. result:".$unpack['result']);
            return false;
        }

        return true;
    }

    /**
     * 本地日志
     */
    private function errorlog($content)
    {
        file_put_contents($this->errorlog, sprintf("[%s] [%x] [ERROR] %s\n", date('Y-m-d H:i:s'), $this->sequence, $content), FILE_APPEND);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->flush();
    }

}

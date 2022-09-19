<?php
namespace NCFGroup\Task\Gearman;

use Assert\Assertion;
use NCFGroup\Protos\FundGate\ProtoSendEmail;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Protos\FundGate\RequestSendSync;
use NCFGroup\Task\Services\EmailService;

class GearManJobServer
{
    public $ip;
    public $port;
    private $jobServerFunctionList = array();

    const GEARMAN_ADMIN_CMD = '/apps/product/gearman/bin/gearadmin';

    public function __construct($ip, $port = 4730)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function isAlive()
    {
        $cmd = self::GEARMAN_ADMIN_CMD." --status -h {$this->ip} -p {$this->port}";
        $live = trim(exec($cmd));

        return $live;
    }

    public function dropFunction($functionName)
    {
        $cmd = self::GEARMAN_ADMIN_CMD." -h {$this->ip} -p {$this->port} --drop-function {$functionName}";
        $ok = trim(exec($cmd));
        if ($ok == 'OK') {
            return true;
        }

        return false;
    }

    public static function getJobServerList()
    {
        $jobServerList = array();

        $jobServerInfos = \Phalcon\DI::getDefault()->get('config')->taskGearman->serverInfos;
        foreach ($jobServerInfos as $jobServerInfo) {
            $port = isset($jobServerInfo['port']) ? $jobServerInfo['port'] : 4730;
            $jobServerList[] = new self($jobServerInfo['ip'], $port);
        }

        return $jobServerList;
    }

    public static function getAllDeadJobServerList()
    {
        $deadJobServerList = array();
        foreach (self::getJobServerList() as $jobServer) {
            if (false == $jobServer->isAlive()) {
                $deadJobServerList[] = $jobServer;
            }
        }

        return $deadJobServerList;
    }

    public static function getAllAliveJobServerList()
    {
        $aliveJobServerList = array();
        foreach (self::getJobServerList() as $jobServer) {
            if ($jobServer->isAlive()) {
                $aliveJobServerList[] = $jobServer;
            }
        }

        return $aliveJobServerList;
    }

    public function getWorkerInfo()
    {
        $cmd = self::GEARMAN_ADMIN_CMD." --worker -h {$this->ip} -p {$this->port}";
        exec($cmd, $lines);

        return $lines;
    }

    public static function getAllServerWorkerInfo()
    {
        $serverIp_workerInfo = array();
        foreach (self::getAllAliveJobServerList() as $jobServer) {
            $serverIp_workerInfo[$jobServer->ip] = $jobServer->getWorkerInfo();
        }

        return $serverIp_workerInfo;
    }

    /**
     * getRandOneLiveJobServer 随机获得一个活着的job server
     *
     * @static
     * @access public
     */
    public static function getRandOneLiveJobServer()
    {
        $allAliveJobServerList = self::getAllAliveJobServerList();
        //Assertion::notEmpty($allAliveJobServerList, '没有任何job server活着, 请启动job server');

        //return $allAliveJobServerList[array_rand($allAliveJobServerList)];
        return $allAliveJobServerList[0];
    }

    /**
     * alert 如果有死的server报警
     *
     * @static
     * @access public
     */
    private static function tryAlert4DeadServer()
    {
        if (trim(self::getAlertMessage4DeadServer()) != '') {
            $emailSvc = new EmailService();
            $emailSvc->sendSync(array('liaoyebin@ucfgroup.com', 'quanhengzhuang@ucfgroup.com'), "job server挂了", XDateTime::now()->toString()."  job server挂了");
        }
    }

    private static function getAlertMessage4DeadServer()
    {
        $deadJobServerList = self::getAllDeadJobServerList();
        if (empty($deadJobServerList)) {
            return '';
        }

        $content = '';
        foreach ($deadJobServerList as $jobServer) {
            $content .= "{$jobServer->ip}挂了     ";
        }

        return $content;
    }

    /**
     * tryAlert4WrongWorker 当server中worker数不对时, 报警
     *
     * @static
     * @access public
     */
    private static function tryAlert4WrongWorkerCnt()
    {
        $reportContent = self::getAlertMessage4WrongWorkerCnt();
        if (trim($reportContent) != '') {
            $emailSvc = new EmailService();
            $emailSvc->sendSync(array('liaoyebin@ucfgroup.com', 'quanhengzhuang@ucfgroup.com'), "gearman job server中worker数不对", $reportContent);

            //发短信
            //sendSMS($reportContent);
        }
    }

    private static function getAlertMessage4WrongWorkerCnt()
    {
        $reportContent = '';
        $warningWorkerCnt = \Phalcon\DI::getDefault()->get('config')->taskGearman->warningWorkerCnt;
        foreach (self::getAllAliveJobServerList() as $jobServer) {
            if ($jobServer->getTotalWorkerCnt() < $warningWorkerCnt) {
                $reportContent .= '     '.$jobServer->ip."woker数为".$jobServer->getTotalWorkerCnt()."\n";
            }
        }

        return $reportContent;
    }

    public static function tryAlert()
    {
        self::tryAlert4DeadServer();
        self::tryAlert4WrongWorkerCnt();
    }

    public static function getAlertMessage()
    {
        return self::getAlertMessage4DeadServer().self::getAlertMessage4WrongWorkerCnt();
    }

    public function getJobServerFunctionList()
    {
        //Assertion::true($this->isAlive() == true, '此server是挂掉的');

        if (empty($this->jobServerFunctionList)) {
            $cmd = self::GEARMAN_ADMIN_CMD." --status -h {$this->ip} -p {$this->port}";
            exec($cmd, $lines);
            foreach ($lines as $line) {
                $jobServerFunction = JobServerFunction::createByLineStr($line);
                if ($jobServerFunction) {
                    $this->jobServerFunctionList[] = $jobServerFunction;
                }
            }
        }

        return $this->jobServerFunctionList;
    }

    /**
     * getTotalWorkerCnt 拿到注册到此server上的所有方法的所有worker的总数
     *
     */
    public function getTotalWorkerCnt()
    {
        $totalCnt = 0;
        foreach ($this->getJobServerFunctionList() as $jobServerFunction) {
            $totalCnt += $jobServerFunction->workerCnt;
        }

        return $totalCnt;
    }

}

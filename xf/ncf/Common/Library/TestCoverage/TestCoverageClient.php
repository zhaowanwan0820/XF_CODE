<?php
namespace NCFGroup\Common\Library\TestCoverage;

/**
 * 测试覆盖client类.
 */
class TestCoverageClient
{
    private $data;
    private $rootPath;
    private $commit;
    private $branchName;
    private $projectName;
    private $hostName;
    private $ip;
    private $enable = false;

    private $testCoverageServer;

    public static function create($testCoverageServer, $rootPath, $projectName)
    {/*{{{*/
        $self = new TestCoverageClient();
        $self->rootPath = $rootPath;
        $self->testCoverageServer = $testCoverageServer;

        $env = get_cfg_var("phalcon.env");
        if (!$env || ($env != "test" && $env != "pdtest")) {
            return $self;
        }

        if (!extension_loaded('xdebug')) {
            $self->log("requires Xdebug");

            return $self;
        }

        if (version_compare(phpversion('xdebug'), '2.2.1', '>=') &&
            !ini_get('xdebug.coverage_enable')) {
            $self->log("xdebug.coverage_enable=On has to be set in php.ini");

            return $self;
        }

        if (!file_exists($self->getVersionFilePath())) {
            $self->log('version.txt不存在');

            return $self;
        }

        $versionArr = file($self->getVersionFilePath());
        $self->branchName = trim($versionArr[0]);
        $self->commit = trim($versionArr[1]);

        if (strlen($self->commit) != 40) {
            $self->log('commit有误');

            return $self;
        }

        $self->projectName = $projectName;
        $self->hostName = gethostname();
        $self->ip = str_replace("\n", "", shell_exec("/sbin/ifconfig eth0 | grep 'inet addr' | awk -F':' {'print $2'} | awk -F' ' {'print $1'}"));
        $self->enable = true;

        return $self;
    }/*}}}*/

    private function getVersionFilePath()
    {
        return $this->rootPath."/version.txt";
    }

    public function start()
    {
        if (!$this->enable) {
            return;
        }

        xdebug_start_code_coverage(XDEBUG_CC_UNUSED|XDEBUG_CC_DEAD_CODE);
    }

    public function stop()
    {
        if (!$this->enable) {
            return;
        }

        $this->data = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        $this->commit();
    }

    private function commit()
    {
        $reqCommit = $this->getReqCommit();
        self::post_json($this->testCoverageServer."/commit", $reqCommit);
    }

    private function getReqCommit()
    {
        return array(
            "commit" => $this->commit,
            "reqFiles" => $this->data,
            "branchName" => $this->branchName,
            "projectName" => $this->projectName,
            "host" => $this->hostName,
            "ip" => $this->ip,
            "rootPath" => $this->rootPath,
        );
    }

    private static function post_json($url, $data, $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result =  curl_exec($ch);

        return json_decode($result, true);
    }

    private function log($logStr)
    {
        file_put_contents($this->rootPath."/test_coverage_err.log", date("[ c ]: ").$logStr."\n", FILE_APPEND);
    }
}

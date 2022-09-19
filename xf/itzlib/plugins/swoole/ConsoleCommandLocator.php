<?php

/**
 * 命令行定位器
 * User: Devon
 * Date: 3/16/2016
 * Time: 4:10 PM
 */
class ConsoleCommandLocator
{
    public $yiicPath = '.';
    /**
     * @var string 执行的 command
     */
    public $command = "help";
    /**
     * @var string 执行的 action
     */
    public $action = "run";
    /**
     * @var array 执行的 params， key => value 形式
     */
    public $params = [];

    public function __construct($command = "", $action = "", $params = [])
    {
        $this->command = $command ?: $this->command;
        $this->action = $action ?: $this->action;
        $this->params = $params ?: $this->params;
    }

    public static function createInstanceFromJson($data)
    {
        $attr = json_decode($data);
        $cmd = new self($attr->command, $attr->action, $attr->params);
        return $cmd;
    }

    public static function getFormattedParamList($params = [])
    {
        $result = [];
        foreach ($params as $key => $value) {
            $result[] = "--{$key}=$value";
        }

        return $result;
    }

    public static function getRunArgv($command = "", $action = "", $params = [])
    {
        $argv = [0 => 'yiic'];
        $paramList = self::getFormattedParamList($params);
        return array_merge($argv, [$command, $action], $paramList);
    }

    public function preRun()
    {
        $argv = self::getRunArgv($this->command, $this->action, $this->params);
        $_SERVER['argv'] = $argv;
        $_SERVER['argc'] = count($argv);
    }

    /**
     * 该方法在未加载任何 YII 文件的情况下可调用
     * @return mixed
     */
    public function run()
    {
        $this->preRun();
        return require("{$this->yiicPath}/yiic.php");
    }
}


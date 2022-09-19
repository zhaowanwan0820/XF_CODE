<?php
namespace NCFGroup\Task\Events;

class TestEvent implements AsyncEvent,EventInterceptor
{
    private $executeReturnResult;
    /**
     * mustRunTime 此任务一定会运行多少次
     *
     * @var float
     * @access private
     */
    private $mustRunTime;
    private $msg;
    private $paralleledTest;
    public $beforeDone = false;
    public $afterDone = false;

    const PARALLELED_TEST_FILE = '/tmp/paralleled_test_file.txt';

    public function __construct($executeReturnResult = true, $mustRunTime = 1, $msg = '', $paralleledTest = false)
    {
        $this->executeReturnResult = $executeReturnResult;
        $this->mustRunTime = $mustRunTime;
        $this->msg = $msg;
        $this->paralleledTest = $paralleledTest;
    }

    public function execute()
    {
        if ($this->paralleledTest) {
            file_put_contents(self::PARALLELED_TEST_FILE, "ok\n", FILE_APPEND | LOCK_EX);
            sleep(3);
        }

        if ($this->mustRunTime == 1) {
            //trigger_error('jxjxjjxxjxjxjxjx',  E_USER_ERROR);
            //$hostName = gethostname();
            //EmailClient::getInstance()->sendSync(array('yangshiqi@haodf.com'), "测试event [{$hostName}] [{$this->msg}]", $this->msg);
        }

        if ($this->executeReturnResult == true) {
            file_put_contents('/tmp/TestEvent.txt', serialize($this));
        }

        $this->mustRunTime = $this->mustRunTime - 1;
        if ($this->mustRunTime > 0) {
            throw new \Exception($this->msg);
        }
        //self::jisuan();
        return $this->executeReturnResult;
    }

    /**
     *
     * @return Msg
     */
    public function getMsg()
    {
        return $this->msg;
    }

    public function alertMails()
    {
        return array(
            'jingxu@ucfgroup.com','dengyi@ucfgroup.com'
        );
    }

    public function before()
    {
        $this->beforeDone = true;
    }

    public function after()
    {
        $this->afterDone = true;
    }
}

<?php
use NCFGroup\Task\Events\TestEvent;
use NCFGroup\Task\Services\TaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Models\TestModel;
use NCFGroup\Task\Instrument\TimedTask;
use NCFGroup\Task\Models\TaskFail;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Library\Date\XDateTime;

//是否由gearman来处理任务
//如果为false则任务不由gearman处理, 而由本地处理
define('TO_GEARMAN', true);

//$appName = 'fund';
$appName = 'p2p';
getDI()->get('config')->taskGearman->appName = $appName;
if (!class_exists('NCFGroup\Common\Phalcon\Bootstrap')) {
    if ($appName == 'fund') {
        require '/home/dev/git/fundgate/backend/app/tasks/init4worker.php';
    } elseif ($appName == 'p2p') {
        require '/home/dev/git/firstp2p/scripts/init.php';
    } else {
        exit("appName有误\n");
    }
}

//基金与p2p启动不同的worker来进行测试
//每次测试前重启gearman-manager 是为了让gearman worker进程中的代码是最新的
if ($appName == 'fund') {
    system('/etc/rc.d/init.d/gearman-manager-p2p stop &> /dev/null');
    if (TO_GEARMAN) {
        system('/etc/init.d/gearman-manager-fund restart &> /dev/null');
    } else {
        system('/etc/init.d/gearman-manager-fund stop &> /dev/null');
    }
} elseif ($appName == 'p2p') {
    system('/etc/init.d/gearman-manager-fund stop &> /dev/null');
    if (TO_GEARMAN) {
        system('/etc/init.d/gearman-manager-p2p restart &> /dev/null');
    } else {
        system('/etc/rc.d/init.d/gearman-manager-p2p stop &> /dev/null');
    }
}

/**
 * @author jingxu
 *
 * @backupGlobals disabled
 */
class TaskTest extends PHPUnit_Framework_TestCase
{
    /**
     *  如果设置为false, 得把gearman-manager关闭
     */
    const TO_GEARMAN = TO_GEARMAN;

    public function setUp()
    {/*{{{*/
        $di = \Phalcon\DI::getDefault();
        $di->getTaskDb()->execute('DELETE FROM `task`');
        $di->getTaskDb()->execute('DELETE FROM `task_fail`');
        $di->getTaskDb()->execute('DELETE FROM `task_success`');
        system('rm -rf /tmp/TestEvent.txt');
        system('rm -rf ' . APP_ROOT_DIR . '/cache/metadata/*');
        $metaDataDir = getDI()->get('config')->application->metaDataDir;
        system("rm -rf $metaDataDir/*");
    }/*}}}*/

    public function testDoBackGround()
    {/*{{{*/
        $taskSrc = new TaskService();

        $randKey = rand();
        $expectedTestEvent = new TestEvent(true, 1, 'jx' . $randKey);
        $expectedTaskId = $taskSrc->doBackground($expectedTestEvent);
        if (!self::TO_GEARMAN) {
            $tasks = Task::find();
            $task = $tasks->getFirst();
            $task->setTimed();
            $task->save();
            TimedTask::execute(self::TO_GEARMAN);
        }

        $this->sleep();

        $actualTestEvent = unserialize(file_get_contents('/tmp/TestEvent.txt'));
        $this->assertInstanceOf('NCFGroup\Task\Events\TestEvent', $actualTestEvent);
        $this->assertEquals('jx' . $randKey, $expectedTestEvent->getMsg());

        //$this->assertEquals($expectedTaskId, $successTask->taskId);
        //global $appName;
        //$this->assertEquals($appName, $successTask->appName);
    }/*}}}*/

    public function testDoBackGroundRetry()
    {/*{{{*/
        $expectedTestEvent = new TestEvent(true, 3, 'jx');
        $mqSrc = new TaskService();
        //第一次运行
        $expectedTaskId = $mqSrc->doBackground($expectedTestEvent, 10, Task::PRIORITY_HIGH);
        if (!self::TO_GEARMAN) {
            $tasks = Task::find();
            $task = $tasks->getFirst();
            $task->setTimed();
            $task->save();
            TimedTask::execute(self::TO_GEARMAN);
        }
        $this->sleep();

        $tasks = Task::find();
        $task = $tasks->getFirst();
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(1, $task->nowtry);

        //第二次运行
        TimedTask::execute(self::TO_GEARMAN);
        $this->sleep();
        $tasks = Task::find();
        $task = $tasks->getFirst();
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(2, $task->nowtry);

        //第三次运行
        TimedTask::execute(self::TO_GEARMAN);
        $this->sleep();
        $tasks = Task::find();
        $task = $tasks->getFirst();
        $this->assertFalse($task);

        //$this->assertEquals($expectedTaskId, $successTask->taskId);
        //global $appName;
        //$this->assertEquals($appName, $successTask->appName);
    }/*}}}*/

    public function testRetryFail()
    {/*{{{*/
        $expectedTestEvent = new TestEvent(false, 3, 'jx');
        $mqSrc = new TaskService();
        //第一次运行
        $expectedTaskId = $mqSrc->doBackground($expectedTestEvent, 3, Task::PRIORITY_HIGH);
        if (!self::TO_GEARMAN) {
            $tasks = Task::find();
            $task = $tasks->getFirst();
            $task->setTimed();
            $task->save();
            TimedTask::execute(self::TO_GEARMAN);
        }
        $this->sleep();

        $tasks = Task::find();
        $task = $tasks->getFirst();
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(1, $task->nowtry);

        //第二次运行
        TimedTask::execute(self::TO_GEARMAN);
        $this->sleep();
        $tasks = Task::find();
        $task = $tasks->getFirst();
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(2, $task->nowtry);

        //global $appName;
        //$this->assertEquals($appName, $task->appName);

        //第三次运行
        TimedTask::execute(self::TO_GEARMAN);
        $this->sleep();
        $tasks = Task::find();
        $this->assertEquals(0, count($tasks));

        $failTasks = TaskFail::find();
        $failTask = $failTasks->getFirst();
        $this->assertInstanceOf('NCFGroup\Task\Events\TestEvent', $failTask->event);

        //$this->assertEquals($expectedTaskId, $failTask->taskId);
        //global $appName;
        //$this->assertEquals($appName, $failTask->appName);

    }/*}}}*/

    /**
     * testFailTaskRun_fail
     *
     * 手动重试任务--失败
     *
     * @access public
     */
    public function testFailTaskRun_fail()
    {/*{{{*/
        $taskSrc = new TaskService();

        $randKey = rand();
        //这里设置为3,
        //第1次运行, 抛异常, 失败 进入失败表
        //失败表 手动重试 又抛异常, 失败
        //(以下单元测试中没有)
        //如果再手动重试, 这时mustruntime值为1, 成功
        $expectedTestEvent = new TestEvent(true, 3, 'jx' . $randKey);
        $expectedTaskId = $taskSrc->doBackground($expectedTestEvent);
        if (!self::TO_GEARMAN) {
            $tasks = Task::find();
            $task = $tasks->getFirst();
            $task->setTimed();
            $task->save();
            TimedTask::execute(self::TO_GEARMAN);
        }

        $this->sleep();

        $failTasks = TaskFail::find();
        $failTask = $failTasks->getFirst();

        $this->assertInstanceOf('NCFGroup\Task\Events\TestEvent', $failTask->event);
        $this->assertEquals(1, count($failTasks));
        $this->assertEquals($expectedTaskId, $failTask->taskId);

        $taskSrc->failTaskRun($failTask->id, self::TO_GEARMAN);

        $this->sleep();

        $failTasks = TaskFail::find();
        $failTask = $failTasks->getFirst();
        $this->assertEquals($expectedTaskId, $failTask->taskId);
        $this->assertEquals(TaskFail::RUNNING_NO, $failTask->running);
        $this->assertEquals(1, count($failTasks));

    }/*}}}*/

    /**
     * testFailTaskRun_succ
     *
     * 手动重试任务--成功
     * @access public
     */
    public function testFailTaskRun_succ()
    {/*{{{*/
        $taskSrc = new TaskService();

        $randKey = rand();
        //这里设置为2
        //第1次运行, 抛异常, 失败, 进入失败表
        //手动重试, 因为这时mustRunTime已经减为1, 所以返回true, 成功
        $expectedTestEvent = new TestEvent(true, 2, 'jx' . $randKey);
        $expectedTaskId = $taskSrc->doBackground($expectedTestEvent);
        if (!self::TO_GEARMAN) {
            $tasks = Task::find();
            $task = $tasks->getFirst();
            $task->setTimed();
            $task->save();
            TimedTask::execute(self::TO_GEARMAN);
        }

        $this->sleep();

        $failTasks = TaskFail::find();
        $failTask = $failTasks->getFirst();
        $event = $failTask->event;
        $this->assertInstanceOf('NCFGroup\Task\Events\TestEvent', $failTask->event);
        $this->assertEquals(1, count($failTasks));
        $this->assertEquals($expectedTaskId, $failTask->taskId);

        $taskSrc->failTaskRun($failTask->id, self::TO_GEARMAN);

        $this->sleep();

        $failTasks = TaskFail::find();
        $this->assertEquals(0, count($failTasks));

    }/*}}}*/

    public function testParalleled()
    {/*{{{*/
        system('rm -rf ' . TestEvent::PARALLELED_TEST_FILE);
        if (self::TO_GEARMAN) {
            $expectedTestEvent = new TestEvent(true, 1, 'jx', true);
            //同时插入两个任务, 每个任务都是给文件加一行ok.
            //每个任务插入入ok都会sleep 3秒
            //所以当插入两个任务后2秒, 去检测应该只能检测到一行. 
            //然后再过5秒, (2 + 2 > 3) 这时再去检测, 文件应该有两行, 因为3秒发经, 第二个任务开始执行了

            $taskSvc = new TaskService();
            $taskSvc->doBackground($expectedTestEvent, 2, Task::PRIORITY_NORMAL, null, WxGearManWorker::DOTASK_BASE, false);
            $taskSvc->doBackground($expectedTestEvent, 2, Task::PRIORITY_NORMAL, null, WxGearManWorker::DOTASK_BASE, false);
            sleep(2);
            //顺序执行, 当检测时, 只能检测到一个任务的执行结果, 也就是此文件中只有一行
            $this->assertEquals(1, count(file(TestEvent::PARALLELED_TEST_FILE)));
            sleep(2);
            $this->assertEquals(2, count(file(TestEvent::PARALLELED_TEST_FILE)));
        }
    }/*}}}*/

    /**
     * testTimedTask 测试定时的任务
     * 这里有一个问题, 就是一起跑测试时, 会出问题
     */
    public function testTimedTask()
    {
        if (self::TO_GEARMAN) {
            $expectedTestEvent = new TestEvent(true, 1, 'jx');
            $mqSrc = new TaskService();
            $expectedTaskId = $mqSrc->doBackground($expectedTestEvent, 10, Task::PRIORITY_HIGH, XDateTime::now()->addMinute(-1));
            $this->sleep();
            $tasks = Task::find();
            $this->assertEquals(1, count($tasks));

            TimedTask::execute(true);
            $this->sleep();

            $tasks = Task::find();
            $this->assertEquals(0, count($tasks));
        }
    }

    public function testTrySaveFromLog()
    {
    }

    private function sleep()
    {
        if (self::TO_GEARMAN) {
            sleep(4);
        }
    }

    public function testRegisterTask()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
    }

    public function testSettingWatingStatus()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event, 1, Task::PRIORITY_NORMAL, null, true, Task::STATUS_WAITING);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(Task::STATUS_WAITING, $task->status);
    }

    public function testNotifyTask()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event, 1, Task::PRIORITY_NORMAL, null, true, Task::STATUS_WAITING);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(Task::STATUS_WAITING, $task->status);
        $result = TaskService::notifyTask($task->id);
        $this->assertEquals(true, $result);
    }

    public function testUpdateStatus()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event, 1, Task::PRIORITY_NORMAL, null, true, Task::STATUS_WAITING);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $this->assertEquals(Task::STATUS_WAITING, $task->status);
        $result = $task->updateStatus(Task::STATUS_WAITING, Task::STATUS_RUN_NOW, XDateTime::now()->toString());
        $this->assertEquals(true, $result);
        $task = Task::findFirst($task->id);
        $this->assertEquals(Task::STATUS_RUN_NOW, $task->status);
    }

    public function testQueryWaitingTask()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event, 1, Task::PRIORITY_NORMAL, null, true, Task::STATUS_WAITING);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $result = TaskService::queryWaitingTasks();
    }

    public function testCancelTask()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event, 1, Task::PRIORITY_NORMAL, null, true, Task::STATUS_WAITING);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $result = TaskService::cancelTask($task->id);
        $this->assertEquals(true, $result);
    }

    public function testGetWaitingTaskById()
    {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = TaskService::registerTask($event, 1, Task::PRIORITY_NORMAL, null, true, Task::STATUS_WAITING);
        $task = Task::findFirst($taskId);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $task);
        $result = Task::getWaitingTaskById($task->id);
        $this->assertInstanceOf('NCFGroup\Task\Models\Task', $result);
    }

    public function testIsBeyondFrequency() {
        $di = \Phalcon\DI::getDefault();
        //没有超过限制
        $result = $di->get('frequencyHandler')->isBeyondFrequency("testForIsBeyondFrequency", 2, 60);
        $this->assertEquals(false, $result);
        //没有超过限制
        $result = $di->get('frequencyHandler')->isBeyondFrequency("testForIsBeyondFrequency", 2, 60);
        $this->assertEquals(false, $result);
        //超过限制
        $result = $di->get('frequencyHandler')->isBeyondFrequency("testForIsBeyondFrequency", 2, 60);
        $this->assertEquals(true, $result);
    }

    public function testSaveMessage() {
        $idGenerator = new \NCFGroup\Task\Instrument\BigIntegerIdentityGenerator(getDI()->get('taskRedis'));
        $id = $idGenerator->generate();
        $event = new TestEvent(true, 1, 'dengyi', false);
        $task = new \NCFGroup\Task\Models\RedisTask($id, $event, "p2p_domq", null, 1);
        $result = \NCFGroup\Task\Services\RedisTaskService::saveMessage($task);
        $this->assertEquals(1, $result);
        $result = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($task->id);
        $this->assertInstanceOf('\NCFGroup\Task\Models\RedisTask', $result);
    }

    public function testEnqueueDequeue() {
        $idGenerator = new \NCFGroup\Task\Instrument\BigIntegerIdentityGenerator(getDI()->get('taskRedis'));
        $id = $idGenerator->generate();
        $event = new TestEvent(true, 1, 'dengyi', false);
        $task = new \NCFGroup\Task\Models\RedisTask($id, $event, "p2p_domq", null, 1);
        $result = \NCFGroup\Task\Services\RedisTaskService::enqueue($task, Task::STATUS_WAITING);
        $this->assertEquals(1, $result);
        $result = \NCFGroup\Task\Services\RedisTaskService::dequeue($task, Task::STATUS_WAITING);
        $this->assertEquals(1, $result);
    }

    public function testPushPop() {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $id = \NCFGroup\Task\Services\RedisTaskService::push($event);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($id);
        $this->assertInstanceOf('\NCFGroup\Task\Models\RedisTask', $task);
        $result = NCFGroup\Task\Services\RedisTaskService::pop($task);
        $this->assertEquals(1, $result);
    }

    public function testConsume() {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $id = \NCFGroup\Task\Services\RedisTaskService::push($event);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($id);
        $this->assertInstanceOf('\NCFGroup\Task\Models\RedisTask', $task);
        sleep(1);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($id);
        $this->assertEquals(false, $task);
    }

    public function testRedisTaskRegisterNotify() {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = \NCFGroup\Task\Services\RedisTaskService::registerRedisTask($event, 1, 'normal', null, true, Task::STATUS_WAITING);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($taskId);
        $this->assertInstanceOf('\NCFGroup\Task\Models\RedisTask', $task);
        $result = \NCFGroup\Task\Services\RedisTaskService::notifyRedisTask($taskId, 'domq');
        $this->assertEquals(true, $result);
        sleep(1);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($taskId);
        $this->assertEquals(false, $task);
    }

    public function testRedisTaskRegisterCancel() {
        $event = new TestEvent(true, 1, 'dengyi', false);
        $taskId = \NCFGroup\Task\Services\RedisTaskService::registerRedisTask($event, 1, 'normal', null, true, Task::STATUS_WAITING);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($taskId);
        $this->assertInstanceOf('\NCFGroup\Task\Models\RedisTask', $task);
        $result = \NCFGroup\Task\Services\RedisTaskService::cancelRedisTask($taskId);
        $this->assertEquals(true, $result);
        $task = \NCFGroup\Task\Services\RedisTaskService::fetchMessage($taskId);
        $this->assertEquals(false, $task);
    }
}

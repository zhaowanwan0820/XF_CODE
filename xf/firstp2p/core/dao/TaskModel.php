<?php
namespace core\dao;

use libs\event\AsyncEvent;
use libs\utils\XDateTime;
use libs\utils\Logger;
use libs\utils\DBC;

/**
 * task class
 * 处理后台异步任务
 * TODO(jingxu): 默认改为1
 * @author jingxu <jingxu@ucfgroup.com>
 **/
class TaskModel extends BaseModel
{

    const DEFAULT_MAX_RETRY_TIME = 1;
    const EXECUTING_NO = 'no';
    const EXECUTING_YES = 'yes';

    private static $lastExceptionLogContent = '';

    public static function create(AsyncEvent $event, $maxTry = self::DEFAULT_MAX_RETRY_TIME, XDateTime $executeTime = null) 
    {
        $self = new self();
        $self->nowtry = 0;
        $self->maxtry = $maxTry;
        $self->event = base64_encode(serialize($event));
        $self->eventtype = strtr(get_class($event), '\\', '_');
        if ($executeTime == null) {
            $self->execute_time = XDateTime::now()->toString();
        } else {
            $self->execute_time = $executeTime->toString();
        }
        $self->create_time = XDateTime::now()->toString();
        $self->update_time = $self->create_time;
        $self->executing = self::EXECUTING_NO;
        return $self;
    }

    public function getExecuteTime()
    {
        return XDateTime::valueOf($this->execute_time);
    }

    public function getCreateTime()
    {
        return XDateTime::valueOf($this->create_time);
    }

    public function getUpdateTime()
    {
        return XDateTime::valueOf($this->update_time);
    }

    public function setExecuting()
    {
        $this->executing = self::EXECUTING_YES;
    }

    public function setUnExecuting()
    {
        $this->executing = self::EXECUTING_NO;
    }

    /**
     * run 任务运行
     */
    public function run()
    {
        try {
            $this->nowtry ++;
            $successFul = $this->getEvent()->execute();

            if ($successFul !== true && $successFul !== false) {
                trigger_error('事件返回参数错误', E_USER_WARNING);
            }

            if ($successFul) {
                $this->dealWithSuccess();
            } else {
                $this->dealWithFail();
            }
        } catch (\Exception $exception) {
            $this->logException($exception);
            $this->dealWithFail();
        }
    }

    private function logException(\Exception $exception) 
    {
        $info = array('mqid'=>$this->id, 'nowtry'=>$this->nowtry, 'time' => XDateTime::now()->toString(),'eventType' => $this->eventtype, 'exception' => $exception);
        self::$lastExceptionLogContent = print_r($info, true);
        Logger::wLog(self::$lastExceptionLogContent);
    }

    /**
     * dealWithSuccess 处理成功时情况
     */
    private function dealWithSuccess()
    {
        $this->remove();
    }

    /**
     * dealWithFail 处理失败时情况
     */
    private function dealWithFail()
    {
        if ($this->isNeedRetry()) {
            $this->setUnExecuting();
            $this->save();
        } else {
            $this->toFailTask();
        }
    }

    /**
     * isNeedRetry 是否需要重试
     */
    private function isNeedRetry()
    {
        if (method_exists($this->getEvent(), 'isNeedRetry')) {
            return $this->getEvent()->isNeedRetry();
        }

        return !$this->hasReachedMaxTry();
    }

    /**
     * hasReachedMaxTry 是否已达到最高重试次数
     */
    private function hasReachedMaxTry()
    {
        return $this->nowtry >= $this->maxtry;
    }

    /**
     * toFailTask 移到失败任务里
     */
    private function toFailTask()
    {
        $this->alert();
        $GLOBALS['db']->startTrans();
        try {
            $successFul = TaskFailModel::create($this)->save();
            if (!$successFul) {
                throw new \Exception('fail task create fail');
            }

            if (!$this->remove()) {
                throw new \Exception('fail task create fail');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::wLog('toFailTask 失败'.print_r($e, true));
            return false;
        }

        return true;
    }

    /**
     * alert 报警
     */
    private function alert()
    {
        require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
        $msgcenter = new \Msgcenter();

        $msgcenter->setMsg('jingxu@ucfgroup.com', 0, "task |{$this->eventtype}|{$this->event}| id:{$this->id}", false, '异步任务失败');
        $msgcenter->save();
    }

    /**
     * getEvent 获得事件
     */
    public function getEvent()
    {
        return unserialize(base64_decode($this->event));
    }

    //************************************sql**********************************
    /**
     * getTaskBeforeTheTimeAndByExecuting
     * 查询在datetime之前状态为executing的所有task
     * 
     * @param string $executing 是否执行了
     * @param XDateTime $dateTime 时间
     * @access public
     * @return array
     */
    public function getTaskBeforeExecuteTimeAndByExecuting($executing, XDateTime $executeDateTime)
    {
        DBC::requireNotEmptyString($executing, 'executing不能为空');

        $sql = "SELECT\n".
            "   *\n".
            "FROM\n".
            "   `".DB_PREFIX."task`\n".
            "WHERE\n".
            "   executing = ':executing'\n".
            "AND execute_time < ':executedatetime' ORDER BY id LIMIT 1000";

        $binds = array(
            ':executing' => $executing,
            ':executedatetime' => $executeDateTime->toString(),
            );

        return $this->findAllBySql($sql, false, $binds);
    }

    public function getExecutingTaskCntBeforeUpdateTime(XDateTime $updateTime)
    {
        $sql = "SELECT\n".
            "   count(*)\n".
            "FROM\n".
            "   firstp2p_task t\n".
            "WHERE\n".
            "   t.executing = ':executing_yes'\n".
            "AND t.update_time < ':update_time'";

        $binds = array(
            ':executing_yes' => self::EXECUTING_YES,
            ':update_time' => $updateTime->toString(),
            );
        return $this->countBySql($sql, $binds);
    }
}

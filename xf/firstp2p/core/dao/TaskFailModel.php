<?php
namespace core\dao;
use libs\event\AsyncEvent;
use libs\utils\XDateTime;

class TaskFailModel extends BaseModel
{
    public static function create(TaskModel $task)
    {
        $item = new self();
        $item->event = $task->event;
        $item->eventtype = $task->eventtype;
        $item->trycnt = $task->nowtry;
        $item->execute_time = $task->execute_time;
        $item->create_time = XDateTime::now()->toString();
        return $item;
    }

    public function getEvent()
    {
        return unserialize(base64_decode($this->event));
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/12/7
 * Time: 13:59
 */

namespace NCFGroup\Task\Instrument;

class TaskException extends \Exception{
    const SAVE_FAILURE = 1;
    const COMMIT_FAILURE = 2;
}
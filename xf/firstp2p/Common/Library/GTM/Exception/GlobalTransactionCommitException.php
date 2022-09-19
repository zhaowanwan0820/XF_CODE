<?php

namespace NCFGroup\Common\Library\GTM\Exception;

/**
 * 提交阶段异常
 * (如果捕获此异常，说明事务将重试至最终成功)
 */
class GlobalTransactionCommitException extends \Exception { }

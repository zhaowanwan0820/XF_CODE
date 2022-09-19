<?php

namespace NCFGroup\Common\Library\GTM\Exception;

/**
 * 回滚阶段异常
 * (如果捕获此异常，说明事务将重试至最终失败)
 */
class GlobalTransactionRollbackException extends \Exception { }

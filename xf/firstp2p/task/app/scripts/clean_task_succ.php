<?php
require __DIR__.'/init.php';

//删除7天以前的旧数据
NCFGroup\Task\Models\TaskSuccess::deleteBeforeDateTime(NCFGroup\Common\Library\Date\XDateTime::now()->addDay(-7));

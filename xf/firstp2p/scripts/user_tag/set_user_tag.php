<?php
/**
 *----------------------------------------------------------
 * (根据sql 找出指定用户打标记）
 *----------------------------------------------------------
 * @version V1.0
 */

require_once dirname(__FILE__).'/../../app/init.php';

ini_set('memory_limit', '512M');
set_time_limit(0);
include('config.php');
$limit = 10000;
if (count($argv) == 1) {
    $keys = array_keys($config);
    $tag_name = array_pop($keys);   //默认最后一条   是新加的

} elseif(count($argv) == 2) {   // 手动传入tag 在后台的KEY
    $tag_name = $argv[1];
}

$user_tag_service = new \core\service\UserTagService();

$num_success = 0;
$tag_ids = $user_tag_service->getTagIdsByConstName($tag_name);

$sqls = $config[$tag_name];
$sql_cnt = $sqls['cnt'];
$sql_exec = $sqls['exec'];

if (!empty($tag_ids) && is_array($tag_ids)) {
    if (strpos($sql_exec,'limit') !== false) {  // 有limit的无须分页
        $list = $GLOBALS['db']->getAll($sql_exec);
        foreach ($list as $row) {
            $result = $user_tag_service->addUserTags($row['user_id'], $tag_ids);
            $num_success++;
        }
        echo "共{$num_success}个用户。\n";
    } else {
        if (is_numeric($sql_cnt)) {
            $cnt = $sql_cnt;
        } else {
            $cnt = $GLOBALS['db']->getOne($sql_cnt);
        }
        if ($cnt > $limit) {
            $page_cnt = $cnt / $limit + 2;
            for ($i = 0 ;$i < $page_cnt; $i++) {
                $sql = "$sql_exec LIMIT %s,%s";
                $sql = sprintf($sql, ($i * $limit), $limit);

        //        echo $sql."\n";  continue;
                $list = $GLOBALS['db']->getAll($sql);
                foreach ($list as $row) {
                    $result = $user_tag_service->addUserTags($row['user_id'], $tag_ids);
                    $num_success++;
                }
            }

            echo "共{$num_success}个用户。\n";
        } else {    // 总数小于分页数 无须分页
            $list = $GLOBALS['db']->getAll($sql_exec);
            foreach ($list as $row) {
                $result = $user_tag_service->addUserTags($row['user_id'], $tag_ids);
                $num_success++;
            }
            echo "共{$num_success}个用户。\n";
        }
    }
} else {
    echo "标签不存在 “{$tag_name}” \n";
}



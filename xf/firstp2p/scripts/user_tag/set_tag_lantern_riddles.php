<?php
/**
 *----------------------------------------------------------
 * 标记元宵节猜灯谜活动中奖用户
 *----------------------------------------------------------
 * @version V1.0
 */
require_once dirname(__FILE__).'/../../app/init.php';

$cwd = dirname(__FILE__);
ini_set('memory_limit', '1024M');
set_time_limit(0);
$user_tag_service = new \core\service\UserTagService();
$user_model = \core\dao\UserModel::instance();
$files = array('gmbw_10_name' => 'gmbw_10', 'gmbw_10_mobile' => 'gmbw_10',
    'lmhb_10_name' => 'lmhb_10', 'lmhb_10_mobile' => 'lmhb_10',
    'mssj_10_name' => 'mssj_10', 'mssj_10_mobile' => 'mssj_10',
    'mdl_10_name' => 'mdl_10', 'mdl_10_mobile' => 'mdl_10',
    'ljx_10_name' => 'ljx_10', 'ljx_10_mobile' => 'ljx_10',
    'tzd_50_name' => 'tzd_50', 'tzd_50_mobile' => 'tzd_50');

$num_success = 0;
foreach ($files as $file_name => $tag_name) {
    $tag_name = strtoupper($tag_name);
    $file = $cwd . '/users/' . $file_name . '.txt';
    if (!is_file($file)) {
        echo "文件不存在$file\n";
        break;
    }
    $column = (strpos($file_name, 'name') !== false) ? 'user_name' : 'mobile';
    $tag_id = $user_tag_service->getTagIdsByConstName($tag_name);
    if (!empty($tag_id) && is_array($tag_id)) {
        $handle = fopen($file, 'r');
        while (!feof($handle)) {
            $user_name = trim(fgets($handle, 1024));
            if (!preg_match('/^[a-zA-Z_0-9-]+$/', $user_name)) {
                var_dump($user_name);//输出错误数据
                continue;
            }
            $result = $user_model->findBy("$column='$user_name'", 'id');
            $uid = $result['id'];
            if ($uid <= 0) {
                continue;
            }
            $result = $user_tag_service->addUserTags($uid, $tag_id);
            $num_success++;
        }
        echo "共{$num_success}个用户。\n";
        $num_success = 0;
    } else {
        echo "标签不存在$tag_name ";
    }
}

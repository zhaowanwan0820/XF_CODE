<?php
namespace core\service\attachment;

/**
 * Android Upgrade Service
 * @author longbo
 */
use core\service\BaseService;
use core\dao\attachment\AttachmentModel;
use core\dao\conf\ApiConfModel;
use libs\utils\Logger;
use libs\vfs\VfsHelper;

class AndroidUpgradeService extends BaseService
{
    const APP_NAME = 'androidapk';
    const CONFIG_KEY = 'android_common_config';

    public function getPackage($vcode, $channel)
    {
        $data = ['vcode' => 0];
        if (empty($vcode) || empty($channel)) return $data;

        $condition = ' app_name="'.self::APP_NAME.'" and is_delete=0 and other=":channel" and remark>":vcode" order by remark desc ';
        $params = [
            ':channel' => $channel,
            ':vcode' => $vcode,
        ];

        $attach = AttachmentModel::instance()->findBy($condition, '*', $params, true);

        if ($attach) {
            $attachArr= $attach->getRow();
            $res = json_decode($attachArr['description'], true);
            $config = $this->getCommonConfig();
            if (intval($vcode) < intval($config['minimumVer'])) {
                $res['upgrade'] = 1;
            }
            $res['package'] = VfsHelper::image($attachArr['attachment']);
            return array_merge($res, $config);
        } else {
            return $data;
        }
    }

    private function getCommonConfig()
    {
        $condition = 'name = "'.self::CONFIG_KEY.'"';
        $config = ApiConfModel::instance()->findBy($condition, '*', [], true);
        if (isset($config['value'])) {
            return json_decode($config['value'], true);
        }
        return [];
    }


}



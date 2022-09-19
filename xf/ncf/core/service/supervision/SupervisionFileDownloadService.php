<?php
/**
 * 存管对账文件下载服务类
 *
 * @date 2017-02-17
 * @author weiwei12@ucfgroup.com
 */

namespace core\service\supervision;

use libs\utils\Logger;
use libs\vfs\fds\FdsFTP;
use libs\utils\PaymentApi;
use core\service\supervision\SupervisionBaseService AS SupervisionBase;

class SupervisionFileDownloadService extends SupervisionBase
{

    //对账日期
    private $date;

    /**
     * 构造函数
     * @param string $date 对账日期 yyyy-mm-dd
     */
    public function __construct($date) {
        parent::__construct();
        $this->date = $date;
    }

    /**
     * 下载对账文件
     * @return string 本地文件路径
     */
    public function download()
    {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('begin download supervision file, date: %s', $this->date))));
        //获取商户号
        $merchantId = $this->api->getMerchantId();

        //获取配置
        if (empty($GLOBALS['sys_config']['SUPERVISION']['check'])) {
            throw new \Exception(sprintf('download failed, miss supervision check config'));
        }
        $ftpConf = $GLOBALS['sys_config']['SUPERVISION']['check']['ftp_config'];
        $files = $GLOBALS['sys_config']['SUPERVISION']['check']['files'];

        $ftp = new FdsFTP($ftpConf);

        $date = date('Ymd', strtotime($this->date));
        $year = date('Y', strtotime($this->date));
        $month = date('m', strtotime($this->date));
        foreach ($files as $type => $value) {
            $localFile = sprintf($value['local'], $date);
            $remoteFile = sprintf($value['remote'], $year, $month, $date, $merchantId);
            $ftp->download($remoteFile, $localFile);
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('end download supervision file, date: %s', $this->date))));
        return true;
    }
}

<?php

namespace NCFGroup\Common\Library;

/**
 * BankCodeToHundSunMap
 * 银行编码对应map，恒生提供，与支付端编码对应，邮件为准
 *
 * @final
 * @package default
 * @author wangjiansong@
 */
final class BankCodeToHundSunMap
{
    // 如果找不到在恒生的对应编码，默认传998
    private $defaultHundSun = '998';

    // 编码对应map
    private $payToHundSunMap = array(
        'QDSB' => '898',
        'CSBC' => '901',
        'KMSB' => '902',
        'SRCB' => '903',
        'NJBC' => '904',
        'JHBC' => '905',
        'WZBC' => '907',
        'DGNSB' => '909',
        'GZSB' => '910',
        'HZBC' => '911',
        'ZSBC' => '915',
        'YTCB' => '916',
        'HKSH' => '917',
        'DZBC' => '918',
        'FDB' => '919',
        'PAB' => '920',
        'NBBC' => '921',
        'BCCB' => '922',
        'NCBC' => '926',
        'NTSB' => '927',
        'SZLGBC' => '928',
        'WHNX' => '929',
        'SCCN' => '930',
        'WHNSB' => '931',
        'CNCB' => '932',
        'PSBC' => '934',
        'BOHC' => '938',
        'SZNX' => '939',
        'CQBC' => '940',
        'DLBC' => '941',
        'HRBBC' => '942',
        'JSBC' => '943',
        'LYBC' => '944',
        'QSBC' => '945',
        'QDBC' => '946',
        'BRCB' => '947',
        'WSBC' => '948',
        'WHBC' => '949',
        'DGSB' => '950',
        'ZJCZSB' => '951',
        'ZJMTSB' => '952',
        'LFSBC' => '953',
        'ZJGNSB' => '954',
        'CTSH' => '955',
        'HSSH' => '956',
        'BEAI' => '957',
        'JXSB' => '958',
        'SXSB' => '959',
        'TZBC' => '960',
        'ZJTLBC' => '961',
        'GZBC' => '962',
        'XMBC' => '963',
        'ICBC' => '002',
        'ABC' => '003',
        'BOC' => '004',
        'CCB' => '005',
        'BOCOM' => '006',
        'CMB' => '007',
        'CEB' => '009',
        'SPDB' => '010',
        'CIB' => '011',
        'HXB' => '012',
        'SDB' => '013',
        'CMBC' => '014',
        'GDB' => '016',
        'BOS' => '017',
        'HBBC' => '020',
        'SJBC' => '024',
        'TJBC' => '025',
        'ZJNX' => '026',
        'HXBC' => '027',
        'NEBC' => '028',
        'CABC' => '029',
        'EBCL' => '032',
        'SZBC' => '033',
    );

    public function __construct()
    {
    }

    /**
     * payToHundSun
     * 转换支付端的银行编码到恒生系统中的编码
     *
     * @param mixed $code
     * @access public
     * @return void
     */
    public function payToHundSun($code)
    {
        if (isset($this->payToHundSunMap[$code])) {
            return $this->payToHundSunMap[$code];
        }
        return $this->defaultHundSun;
    }

}

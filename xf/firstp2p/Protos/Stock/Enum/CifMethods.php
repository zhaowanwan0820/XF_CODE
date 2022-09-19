<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CifMethods extends AbstractEnum
{
    const CONTRACT = 'cifCXHTXYWB';//查询合同协议文本
    const RISKTESTQUESTION = 'cifCXFXCPTM';//查询风险测评题目
    const SCORECRITERIA= 'cifQueryWJPFBZ';//查询问卷评分标准
    const GENERALQUERY = 'cifQuery';//通用查询
    const ACCOUNTDEFAULTATTRIBUTE = 'cifQueryKHMRSX';//查询开户默认属性

    const ACCOUNTVERIFY = 'cifwsFXCKH_KHYZ';//非现场开户_开户验证
    const UPLOADMEDIA = 'cifwsMedia_Upload';//上传media
    const REGISTER= 'cifwsFXCKH_KHSQ'; //非现场开户_开户申请
    const DOWNLOADMEDIA = 'cifwsMedia_Download';//下载media

    const BANKDEPOSITORYSTATE = 'cifCXZDCGYHJG';//查询指定存管银行结果
    const BANKDEPOSITORY = 'cifwsCGYW_ZD'; //存管业务_指定存管银行
    const BANKDEPOSITORYBYKHH = 'cifCXCGKHSQ'; //cif查询存管开户申请

    const BANKPARAM = 'cifQueryYHCS';//查询银行参数
    const DATADICTIONARY = 'cifQuerySJZD';//查询数据字典
    const REGISTERRESULT = 'cifCXFXCKHSQ'; //查询非现场开户申请
    const USERZDINFO = 'cifwsZDYZ_ZDZHCX'; //中登验证_中登账户查询
    //const ZJZHIXX= 'cifQueryZJZHXX';//查询客户资金账户信息
    const GDZHXX = 'cifQueryGDZHXX'; //cif查询股东信息
    const ZJZHXX= 'cifQueryZJZHXX';//查询客户资金账户信息
    const SHAREHOLDERINFO='cifQueryZDGDCXJG';//查询中登股东查询结果
    const GMSFYZ = 'cifwsGMSFYZ_FSCX'; //公民身份验证_发送查询
    const KHQZ = 'cifQueryKHQZ'; //cif查询客户群组
    const GYXX = 'cifQueryGYXX'; //获取柜员信息
    const CLJG = 'cifCXFXCKHZGYJ'; //cif查询非现场开户整改意见
    const CXRMM = 'cifCXRMMCS'; //cif查询弱密码参数
    const JJGSCS = 'cifQueryJJGSCS'; //cif查询基金公司参数
    const SELYYB = 'cifCXKCZYYB'; //cif查询可操作营业部

    protected static $details = [
        self::BANKDEPOSITORYBYKHH=> [
            'type' => CifWay::QUERY,
            'args' => [
                'KHH',
            ]
        ],
        self::JJGSCS => [
            'type' => CifWay::QUERY,
            'args' => [
            ],
        ],
        self::CXRMM => [
            'type' => CifWay::QUERY,
            'args' => [
            ],
        ],
        self::CLJG => [
            'type' => CifWay::QUERY,
            'args' => [
                'BDID',
            ],
        ],
        self::GYXX => [
            'type' => CifWay::QUERY,
            'args' => [
                'FLAG',
                'USERID',
            ],
        ],
        self::KHQZ => [
            'type' => CifWay::QUERY,
            'args' => [
                'YYB',
                'KHFS',
            ],
        ],
        self::GMSFYZ => [
            'type' => CifWay::EXECUTE,
            'args' => [
                'GYID',
                'ZJLB',
                'ZJBH',
                'KHXM',
                'CXLX',
                'YYB',
            ],
        ],
        self::GDZHXX => [
            'type' => CifWay::QUERY,
            'args' => [
                'KHH',
            ],
        ],
        self::USERZDINFO => [
            'type' => CifWay::QUERY,
            'args' => [
                'ZJLB',
                'ZJBH',
                'GJDM',
                'CFBZ',
            ],
        ],
        self::REGISTERRESULT => [
            'type' => CifWay::QUERY,
            'args' => [
                'FLAG',
                'SQID',
                'KHFS',
            ],
        ],
        self::REGISTER => [
            'type' => CifWay::EXECUTE,
            'args' => [
                'TJFS',
                'SPJZBZ',
                'KHFS',
                'KHZD',
                'JGBZ',
                'YYB',
                'KHLY',
                'KHXM',
                'KHQC',
                'DN',
                'ZJLB',
                'ZJBH',
                'XB',
                'CSRQ',
                'ZJYXQ',
                'ZJDZ',
                'ZJYZLY',
                'EDZ',
                'DZ',
                'YZBM',
                'ZYDM',
                'XL',
                'GJ',
                'BZ',
                'SFTB',
                'JMLX',
                'JYMM',
                'ZJMM',
                'GDKH_SH',
                'GDKH_HJ',
                'GDKH_HB',
                'GDKH_SZ',
                'GDKH_SJ',
                'GDKH_SB',
                'GDKH_TA',
                'GDKH_TU',
                'CYBKT',
                'KHZP',
                'CZZD',
                'WJID',
                'TMDAC',
                'YXSTR',
                'XYSTR',
                'CGYHSTR',
                'ZJZP',
                'HFJG',
                'KHQZ',
                'SJ',
                'JZR',
                'KHSP',
                'WTFS',
                'YXRY',
                'GDJYQX_SH',
                'GDJYQX_HJ',
                'GDJYQX_SZ',
                'GDJYQX_SJ',
            ],
        ],
        self::CONTRACT => [
            'type' => CifWay::QUERY,
            'args' => [
                'YWKM',
                'XYLX',
                'XYZT',
                'KHFS',
            ],
        ],
        self::RISKTESTQUESTION => [
            'type' => CifWay::QUERY,
            'args' => [
                'WJBM',
                'JGBZ',
            ],
        ],
        self::SCORECRITERIA => [
            'type' => CifWay::QUERY,
            'args' => [
                'WJID',
            ],
        ],
	    self::GENERALQUERY => [
            'type' => CifWay::QUERY,
            'args' => [
                'DXID',
                'CXTJ',
                'TOKEN',
            ],
        ],
        self::ACCOUNTDEFAULTATTRIBUTE => [
            'type' => CifWay::QUERY,
            'args' => [
                'KHFS',
                'JGBZ',
            ],
        ],
        self::ACCOUNTVERIFY => [
            'type' => CifWay::EXECUTE,
            'args' => [
                'ZJLB',
                'ZJBH',
                'YYB',
            ],
        ],
        self::UPLOADMEDIA => [
            'type' => CifWay::EXECUTE,
            'args' => [
                'BASE64STR',
                'FILENAME',
            ],
        ],
        self::BANKDEPOSITORYSTATE => [
            'type' => CifWay::QUERY,
            'args' => [
                 'KHH',
                 'CGYH',
                 'YHZH',
            ],
        ],
        self::BANKDEPOSITORY => [
            'type' => CifWay::EXECUTE,
            'args' => [
                 'KHH',
                 'ZJZH',
                 'YHDM',
                 'YHZH',
                 'YHMM',
                 'BZ',
                 'JMLX',
                 'ZZHBZ',
                 'CZGY',
                 'SHGY',
                 'CZZD',
                 'ZY',
                 'WF_ID',
              ],
          ],
          self::BANKPARAM => [
            'type' => CifWay::QUERY,
            'args' => [
                 'YHDM'
               ],
          ],
          self::DATADICTIONARY => [
            'type' => CifWay::QUERY,
            'args' => [
                 'FLDM',
                 'IBM',
               ],
          ],
        self::DOWNLOADMEDIA=> [
            'type' => CifWay::EXECUTE,
            'args' => [
                'FILEPATH',
            ],
        ],
        self::ZJZHXX => [
            'type' => CifWay::QUERY,
            'args' => [
                'KHH',
                'ZJZH',
                'BZ',
                'ZHLB',
            ],
        ],
        self::SHAREHOLDERINFO => [
            'type' => CifWay::QUERY,
            'args' => [
               'ZJBH',
               'ZJLB',
               'GDH',
            ],
        ],
        self::SELYYB => [
            'type' => CifWay::QUERY,
            'args' => [
                'YWSHDM',
                ],
       ],
    ];

    public function getArgs()
    {
        return self::$details[$this->getValue()]['args'];
    }

    public function isQuery()
    {
        return $this->getType() == CifWay::QUERY;
    }

    public function isExecute()
    {
        return $this->getType() == CifWay::EXECUTE;
    }

    private function getType()
    {
        return self::$details[$this->getValue()]['type'];
    }
}

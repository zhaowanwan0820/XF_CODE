<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ChengHaoYYBEnum extends AbstractEnum
{
    //营业部编码
    const YYB_RNL = '0010';
    const YYB_NS = '0020';
    const YYB_QGJ = '0030';
    const YYB_CSDL = '0040';
    const YYB_XSC = '0050';
    const YYB_QY = '0060';
    const YYB_XHJ = '0070';
    const YYB_YK = '0080';
    const YYB_JZ = '0090';
    const YYB_CY = '0100';
    const YYB_DD = '0110';
    const YYB_BJFGS = '1001';
    const YYB_QDDHXL = '0120';
    const YYB_XMGYS = '0130';
    const YYB_HEBCJL = '0140';
    const YYB_CYMNDJ = '0150';
    const YYB_XYL = '0160';
    const YYB_SZZXL = '0170';
    const YYB_TJJZHDJ = '0180';
    const YYB_SHKXBL = '0190';
    const YYB_CCWSL = '0200';
    const YYB_WHXGL = '0210';
    const YYB_SHNGL = '0220';
    const YYB_SZGYYQHCJ = '0230';
    const YYB_HZQCDL = '0240';
    const YYB_DWDCDWDD = '0250';
    const YYB_XAGXL = '0260';
    const YYB_CDNEH = '0270';
    const YYB_RNLZQ = '0280';
    const YYB_SZFGS = '1002';
    const YYB_ZYCZ = '5555';

  //  const CHZQ = '9999';
  //  const CHZQBF = '7999';

    //营业部ID
    const RNL_ID = '2';
    const NS_ID = '4';
    const QGJ_ID = '5';
    const CSDL_ID = '6';
    const XSC_ID = '7';
    const QY_ID = '8';
    const XHJ_ID = '9';
    const YK_ID = '10';
    const JZ_ID = '11';
    const CY_ID = '12';
    const DD_ID = '13';
    const BJFGS_ID = '14';
    const QDDHXL_ID = '16';
    const XMGYS_ID = '20';
    const HEBCJL_ID = '19';
    const CYMNDJ_ID = '17';
    const XYL_ID = '18';
    const SZZXL_ID = '21';
    const TJJZHDJ_ID = '22';
    const SHKXBL_ID = '23';
    const CCWSL_ID = '24';
    const WHXGL_ID = '25';
    const SHNGL_ID = '26';
    const SZGYYQHCJ_ID = '29';
    const HZQCDL_ID = '30';
    const DWDCDWDD_ID = '31';
    const XAGXL_ID = '32';
    const CDNEH_ID = '33';
    const RNLZQ_ID = '35';
    const SZFGS_ID = '28';
    const ZYCZ_ID = '34';

    private static $_details = array(
        self::YYB_RNL => '网信证券热闹路营业部',
        self::YYB_NS => '网信证券宁山中路营业部',
        self::YYB_QGJ => '网信证券启工街营业部',
        self::YYB_CSDL => '网信证券崇山东路营业部',
        self::YYB_XSC => '网信证券西顺城街营业部',
        self::YYB_QY => '网信证券泉园街营业部',
        self::YYB_XHJ => '网信证券兴华南街营业部',
        self::YYB_YK => '网信证券营口营业部',
        self::YYB_JZ => '网信证券锦州和平路营业部',
        self::YYB_CY => '网信证券朝阳朝阳大街营业部',
        self::YYB_DD => '网信证券丹东江城大街营业部',
        self::YYB_BJFGS => '网信证券北京分公司',
        self::YYB_QDDHXL => '网信证券青岛东海西路证券营业部',
        self::YYB_XMGYS => '网信证券厦门观音山证券营业部',
        self::YYB_HEBCJL => '网信证券哈尔滨长江路证券营业部',
        self::YYB_CYMNDJ => '网信证券北京朝阳门南大街证券营业部',
        self::YYB_XYL => '网信证券北京霄云路证券营业部',
        self::YYB_SZZXL => '网信证券深圳中心路证券营业部',
        self::YYB_TJJZHDJ => '网信证券天津金钟河大街证券营业部',
        self::YYB_SHKXBL => '网信证券上海凯旋北路证券营业部',
        self::YYB_CCWSL => '网信证券长春蔚山路证券营业部',
        self::YYB_WHXGL => '网信证券武汉香港路证券营业部',
        self::YYB_SHNGL => '网信证券上海宁国路证券营业部',
        self::YYB_SZGYYQHCJ => '网信证券苏州工业园区华池街证券营业部',
        self::YYB_HZQCDL => '网信证券杭州庆春东路证券营业部',
        self::YYB_DWDCDWDD => '网信证券东莞东城东莞大道证券营业部',
        self::YYB_XAGXL => '网信证券西安高新路证券营业部',
        self::YYB_CDNEH => '网信证券成都南二环证券营业部',
        self::YYB_RNLZQ => '网信证券热闹路证券营业部',
        self::YYB_SZFGS => '网信证券深圳分公司营业部',
        self::YYB_ZYCZ => '质押处置',
   //     self::CHZQ => '网信证券',
   //     self::CHZQBF => '网信证券北京分公司',
    );

    private static $_map = array(
        self::RNL_ID => '网信证券热闹路营业部',
        self::NS_ID => '网信证券宁山中路营业部',
        self::QGJ_ID => '网信证券启工街营业部',
        self::CSDL_ID => '网信证券崇山东路营业部',
        self::XSC_ID => '网信证券西顺城街营业部',
        self::QY_ID => '网信证券泉园街营业部',
        self::XHJ_ID => '网信证券兴华南街营业部',
        self::YK_ID => '网信证券营口营业部',
        self::JZ_ID => '网信证券锦州和平路营业部',
        self::CY_ID => '网信证券朝阳朝阳大街营业部',
        self::DD_ID => '网信证券丹东江城大街营业部',
        self::BJFGS_ID => '网信证券北京分公司',
        self::QDDHXL_ID => '网信证券青岛东海西路证券营业部',
        self::XMGYS_ID => '网信证券厦门观音山证券营业部',
        self::HEBCJL_ID => '网信证券哈尔滨长江路证券营业部',
        self::CYMNDJ_ID => '网信证券北京朝阳门南大街证券营业部',
        self::XYL_ID => '网信证券北京霄云路证券营业部',
        self::SZZXL_ID => '网信证券深圳中心路证券营业部',
        self::TJJZHDJ_ID => '网信证券天津金钟河大街证券营业部',
        self::SHKXBL_ID => '网信证券上海凯旋北路证券营业部',
        self::CCWSL_ID => '网信证券长春蔚山路证券营业部',
        self::WHXGL_ID => '网信证券武汉香港路证券营业部',
        self::SHNGL_ID => '网信证券上海宁国路证券营业部',
        self::SZGYYQHCJ_ID => '网信证券苏州工业园区华池街证券营业部',
        self::HZQCDL_ID => '网信证券杭州庆春东路证券营业部',
        self::DWDCDWDD_ID => '网信证券东莞东城东莞大道证券营业部',
        self::XAGXL_ID => '网信证券西安高新路证券营业部',
        self::CDNEH_ID => '网信证券成都南二环证券营业部',
        self::RNLZQ_ID => '网信证券热闹路证券营业部',
        self::SZFGS_ID => '网信证券深圳分公司营业部',
        self::ZYCZ_ID => '质押处置',
   //     self::CHZQ => '网信证券',
   //     self::CHZQBF => '网信证券北京分公司',
    );


    public static function getName($yybCode)
    {
        if(isset(self::$_details[$yybCode])) {
            return self::$_details[$yybCode];
        } else {
            return '';
        }
    }

    public static function getNameById($yybId)
    {
        if(isset(self::$_map[$yybId])) {
            return self::$_map[$yybId];
        } else {
            return '';
        }
    }
}

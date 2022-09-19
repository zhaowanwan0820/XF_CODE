<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * OpenAppMake
 *
 * 由代码生成器生成, 不可人为修改
 * @author lvbaosong
 */
class ProtoOpenAppMake extends ProtoBufferBase
{
    /**
     * userId
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * siteId
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * 包名
     *
     * @var string
     * @required
     */
    private $packageName;

    /**
     * app中文名称
     *
     * @var string
     * @required
     */
    private $appName;

    /**
     * 微信appid
     *
     * @var string
     * @required
     */
    private $wechatappid;

    /**
     * 微信secret
     *
     * @var string
     * @required
     */
    private $wechatsecret;

    /**
     * appScheme
     *
     * @var string
     * @required
     */
    private $appScheme;

    /**
     * 友盟AppKey(Android)
     *
     * @var string
     * @optional
     */
    private $umengappkeyAndroid = '';

    /**
     * 友盟AppKey(ios)
     *
     * @var string
     * @optional
     */
    private $umengappkeyIos = '';

    /**
     * 讯飞AppKey(Android)
     *
     * @var string
     * @optional
     */
    private $xfappkeyAndroid = '';

    /**
     * cid
     *
     * @var int
     * @optional
     */
    private $cid = 0;

    /**
     * appstoreurl
     *
     * @var string
     * @optional
     */
    private $appstoreurl = '';

    /**
     * guanwangPath
     *
     * @var string
     * @optional
     */
    private $guanwangPath = '';

    /**
     * faqPath
     *
     * @var string
     * @optional
     */
    private $faqPath = '';

    /**
     * downloadPath
     *
     * @var string
     * @optional
     */
    private $downloadPath = '';

    /**
     * tel
     *
     * @var string
     * @optional
     */
    private $tel = '';

    /**
     * daihao
     *
     * @var string
     * @optional
     */
    private $daihao = '';

    /**
     * square icon
     *
     * @var string
     * @optional
     */
    private $icon1 = '';

    /**
     * round icon
     *
     * @var string
     * @required
     */
    private $icon2;

    /**
     * launcher640X960
     *
     * @var string
     * @required
     */
    private $launcher1;

    /**
     * launcher640X1136
     *
     * @var string
     * @optional
     */
    private $launcher2 = '';

    /**
     * launcher750X1334
     *
     * @var string
     * @optional
     */
    private $launcher3 = '';

    /**
     * launcher838X1258
     *
     * @var string
     * @required
     */
    private $launcher4;

    /**
     * launcher1080X1920
     *
     * @var string
     * @required
     */
    private $launcher5;

    /**
     * launcher1242X2208
     *
     * @var string
     * @optional
     */
    private $launcher6 = '';

    /**
     * logo343x124
     *
     * @var string
     * @optional
     */
    private $logo1 = '';

    /**
     * logo449x158
     *
     * @var string
     * @optional
     */
    private $logo2 = '';

    /**
     * logo685x243
     *
     * @var string
     * @optional
     */
    private $logo3 = '';

    /**
     * couponlogo
     *
     * @var string
     * @optional
     */
    private $logo4 = '';

    /**
     * coupon wechat pic
     *
     * @var string
     * @required
     */
    private $logo5;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ProtoOpenAppMake
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return ProtoOpenAppMake
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     * @return ProtoOpenAppMake
     */
    public function setPackageName($packageName)
    {
        \Assert\Assertion::string($packageName);

        $this->packageName = $packageName;

        return $this;
    }
    /**
     * @return string
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * @param string $appName
     * @return ProtoOpenAppMake
     */
    public function setAppName($appName)
    {
        \Assert\Assertion::string($appName);

        $this->appName = $appName;

        return $this;
    }
    /**
     * @return string
     */
    public function getWechatappid()
    {
        return $this->wechatappid;
    }

    /**
     * @param string $wechatappid
     * @return ProtoOpenAppMake
     */
    public function setWechatappid($wechatappid)
    {
        \Assert\Assertion::string($wechatappid);

        $this->wechatappid = $wechatappid;

        return $this;
    }
    /**
     * @return string
     */
    public function getWechatsecret()
    {
        return $this->wechatsecret;
    }

    /**
     * @param string $wechatsecret
     * @return ProtoOpenAppMake
     */
    public function setWechatsecret($wechatsecret)
    {
        \Assert\Assertion::string($wechatsecret);

        $this->wechatsecret = $wechatsecret;

        return $this;
    }
    /**
     * @return string
     */
    public function getAppScheme()
    {
        return $this->appScheme;
    }

    /**
     * @param string $appScheme
     * @return ProtoOpenAppMake
     */
    public function setAppScheme($appScheme)
    {
        \Assert\Assertion::string($appScheme);

        $this->appScheme = $appScheme;

        return $this;
    }
    /**
     * @return string
     */
    public function getUmengappkeyAndroid()
    {
        return $this->umengappkeyAndroid;
    }

    /**
     * @param string $umengappkeyAndroid
     * @return ProtoOpenAppMake
     */
    public function setUmengappkeyAndroid($umengappkeyAndroid = '')
    {
        $this->umengappkeyAndroid = $umengappkeyAndroid;

        return $this;
    }
    /**
     * @return string
     */
    public function getUmengappkeyIos()
    {
        return $this->umengappkeyIos;
    }

    /**
     * @param string $umengappkeyIos
     * @return ProtoOpenAppMake
     */
    public function setUmengappkeyIos($umengappkeyIos = '')
    {
        $this->umengappkeyIos = $umengappkeyIos;

        return $this;
    }
    /**
     * @return string
     */
    public function getXfappkeyAndroid()
    {
        return $this->xfappkeyAndroid;
    }

    /**
     * @param string $xfappkeyAndroid
     * @return ProtoOpenAppMake
     */
    public function setXfappkeyAndroid($xfappkeyAndroid = '')
    {
        $this->xfappkeyAndroid = $xfappkeyAndroid;

        return $this;
    }
    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @param int $cid
     * @return ProtoOpenAppMake
     */
    public function setCid($cid = 0)
    {
        $this->cid = $cid;

        return $this;
    }
    /**
     * @return string
     */
    public function getAppstoreurl()
    {
        return $this->appstoreurl;
    }

    /**
     * @param string $appstoreurl
     * @return ProtoOpenAppMake
     */
    public function setAppstoreurl($appstoreurl = '')
    {
        $this->appstoreurl = $appstoreurl;

        return $this;
    }
    /**
     * @return string
     */
    public function getGuanwangPath()
    {
        return $this->guanwangPath;
    }

    /**
     * @param string $guanwangPath
     * @return ProtoOpenAppMake
     */
    public function setGuanwangPath($guanwangPath = '')
    {
        $this->guanwangPath = $guanwangPath;

        return $this;
    }
    /**
     * @return string
     */
    public function getFaqPath()
    {
        return $this->faqPath;
    }

    /**
     * @param string $faqPath
     * @return ProtoOpenAppMake
     */
    public function setFaqPath($faqPath = '')
    {
        $this->faqPath = $faqPath;

        return $this;
    }
    /**
     * @return string
     */
    public function getDownloadPath()
    {
        return $this->downloadPath;
    }

    /**
     * @param string $downloadPath
     * @return ProtoOpenAppMake
     */
    public function setDownloadPath($downloadPath = '')
    {
        $this->downloadPath = $downloadPath;

        return $this;
    }
    /**
     * @return string
     */
    public function getTel()
    {
        return $this->tel;
    }

    /**
     * @param string $tel
     * @return ProtoOpenAppMake
     */
    public function setTel($tel = '')
    {
        $this->tel = $tel;

        return $this;
    }
    /**
     * @return string
     */
    public function getDaihao()
    {
        return $this->daihao;
    }

    /**
     * @param string $daihao
     * @return ProtoOpenAppMake
     */
    public function setDaihao($daihao = '')
    {
        $this->daihao = $daihao;

        return $this;
    }
    /**
     * @return string
     */
    public function getIcon1()
    {
        return $this->icon1;
    }

    /**
     * @param string $icon1
     * @return ProtoOpenAppMake
     */
    public function setIcon1($icon1 = '')
    {
        $this->icon1 = $icon1;

        return $this;
    }
    /**
     * @return string
     */
    public function getIcon2()
    {
        return $this->icon2;
    }

    /**
     * @param string $icon2
     * @return ProtoOpenAppMake
     */
    public function setIcon2($icon2)
    {
        \Assert\Assertion::string($icon2);

        $this->icon2 = $icon2;

        return $this;
    }
    /**
     * @return string
     */
    public function getLauncher1()
    {
        return $this->launcher1;
    }

    /**
     * @param string $launcher1
     * @return ProtoOpenAppMake
     */
    public function setLauncher1($launcher1)
    {
        \Assert\Assertion::string($launcher1);

        $this->launcher1 = $launcher1;

        return $this;
    }
    /**
     * @return string
     */
    public function getLauncher2()
    {
        return $this->launcher2;
    }

    /**
     * @param string $launcher2
     * @return ProtoOpenAppMake
     */
    public function setLauncher2($launcher2 = '')
    {
        $this->launcher2 = $launcher2;

        return $this;
    }
    /**
     * @return string
     */
    public function getLauncher3()
    {
        return $this->launcher3;
    }

    /**
     * @param string $launcher3
     * @return ProtoOpenAppMake
     */
    public function setLauncher3($launcher3 = '')
    {
        $this->launcher3 = $launcher3;

        return $this;
    }
    /**
     * @return string
     */
    public function getLauncher4()
    {
        return $this->launcher4;
    }

    /**
     * @param string $launcher4
     * @return ProtoOpenAppMake
     */
    public function setLauncher4($launcher4)
    {
        \Assert\Assertion::string($launcher4);

        $this->launcher4 = $launcher4;

        return $this;
    }
    /**
     * @return string
     */
    public function getLauncher5()
    {
        return $this->launcher5;
    }

    /**
     * @param string $launcher5
     * @return ProtoOpenAppMake
     */
    public function setLauncher5($launcher5)
    {
        \Assert\Assertion::string($launcher5);

        $this->launcher5 = $launcher5;

        return $this;
    }
    /**
     * @return string
     */
    public function getLauncher6()
    {
        return $this->launcher6;
    }

    /**
     * @param string $launcher6
     * @return ProtoOpenAppMake
     */
    public function setLauncher6($launcher6 = '')
    {
        $this->launcher6 = $launcher6;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogo1()
    {
        return $this->logo1;
    }

    /**
     * @param string $logo1
     * @return ProtoOpenAppMake
     */
    public function setLogo1($logo1 = '')
    {
        $this->logo1 = $logo1;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogo2()
    {
        return $this->logo2;
    }

    /**
     * @param string $logo2
     * @return ProtoOpenAppMake
     */
    public function setLogo2($logo2 = '')
    {
        $this->logo2 = $logo2;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogo3()
    {
        return $this->logo3;
    }

    /**
     * @param string $logo3
     * @return ProtoOpenAppMake
     */
    public function setLogo3($logo3 = '')
    {
        $this->logo3 = $logo3;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogo4()
    {
        return $this->logo4;
    }

    /**
     * @param string $logo4
     * @return ProtoOpenAppMake
     */
    public function setLogo4($logo4 = '')
    {
        $this->logo4 = $logo4;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogo5()
    {
        return $this->logo5;
    }

    /**
     * @param string $logo5
     * @return ProtoOpenAppMake
     */
    public function setLogo5($logo5)
    {
        \Assert\Assertion::string($logo5);

        $this->logo5 = $logo5;

        return $this;
    }

}
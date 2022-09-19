<?php

//namespace App\Services\Message;

//use App\Jobs\SendSms;

class SmsSenderUtil
{
    const SMmsEncryptKey = 'SMmsEncryptKey';

    public function dealReturnData($result)
    {
        $data = [
        'data' => [],
        'code' => 100,
        'info' => 'success',
    ];
        if (!is_array($result)) {
            $result = json_decode($result, true);
        }

        if (isset($result['Result']) && 'succ' === $result['Result']) {
            $data['code'] = 0;

            return $data;
        }
        Yii::log(' send sms error:'.print_r($result, true).' reason:'.$result['Reason']);
        $data['info'] = $result['Reason'];

        return $data;
    }

    /**
     * 生成随机数.
     *
     * @return number
     */
    public function getRandom()
    {
        return rand(100000, 999999);
    }

    public function getPassword($password)
    {
        return strtoupper(md5($password.self::SMmsEncryptKey));
    }

    /**
     * 生成签名.
     *
     * @param string 账号
     * @param string 密码
     * @param long 随机数
     * @param long Linux 时间戳
     *
     * @return string 授权结果
     */
    public function calculateSignPuller($account, $password, $random, $time)
    {
        return hash('sha256', 'AccountId='.$account.'&Password='.$this->getPassword($password)
.'&Random='.$random.'&Timestamp='.$time);
    }

    /**
     * 生成签名.
     *
     * @param string 账号
     * @param string 密码
     * @param long 随机数
     * @param long Linux 时间戳
     * @param long 手机号码
     *
     * @return string 授权结果
     */
    public function calculateSignSender($account, $password, $random, $time, $phoneNumber)
    {
        return hash('sha256', 'AccountId='.$account.'&PhoneNos='.$phoneNumber.'&Password='.$this->getPassword($password)
.'&Random='.$random.'&Timestamp='.$time);
    }

    /**
     * 生成签名.
     *
     * @param string $account  账号
     * @param string $password 密码
     * @param long   $random   随机数
     * @param long   $time     时间戳
     *
     * @return string 授权结果
     */
    public function calculateSignIndividuationSender($account, $password, $random, $time)
    {
        return hash('sha256', 'AccountId='.$account.'&Password='.$this->getPassword($password)
.'&Random='.$random.'&Timestamp='.$time);
    }

    /**
     * @param string $account  账号
     * @param string $password 密码
     * @param long   $random   随机数
     * @param long   $time     时间戳
     * @param long 手机号码
     * @param int 模板Id
     *
     * @return string 授权结果
     */
    public function calculateSignTemplateSender($account, $password, $random, $time, $phoneNumber, $tempCode)
    {
        return hash('sha256', 'AccountId='.$account.'&PhoneNos='.$phoneNumber.'&Password='.$this->getPassword($password)
.'&Random='.$random.'&TempCode='.$tempCode.'&Timestamp='.$time);
    }

    /**
     * 处理字符串.
     *
     * @param $data
     *
     * @return array
     */
    public function replaceCode2Name($data, array $waitReplace)
    {
        $content = $waitReplace['content'];
        if (!isset($data['data'])) {
            return $content;
        }
        $matches = [];
        preg_match_all('/%[A-Za-z0-9_]+%/', $content, $matches);
        foreach ($matches[0] as $key => $value) {
            $dkey = str_replace('%', '', $value);
            if (array_key_exists($dkey, $data['data'])) {
                $content = str_replace($value, $data['data'][$dkey], $content);
            }
        }

        return $content;
    }

    /**
     * 获取触发点配置内容.
     *
     * @param $code
     *
     * @return array
     */
    public function getMessageConfByCode($code)
    {
        //载入配置
        $_configPath = dirname(dirname(__FILE__)).'/config/message.php';
        if (file_exists($_configPath)) {
            $config = include $_configPath;
            if (isset($config[$code])) {
                return $config[$code];
            }
        }

        return [];
    }

    /**
     * 发送请求
     *
     * @param string $url     请求地址
     * @param array  $dataObj 请求内容
     *
     * @return string 应答Json字符串
     */
    public function sendCurlPost($url, $dataObj)
    {
        $headers = [
            'Content-type: application/json;charset="utf-8"',
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataObj));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $ret = curl_exec($curl);
        if (false == $ret) {
            $result = '{ "State":'.-2 .',"MsgState":"'.curl_error($curl).'"}';
        } else {
            $rsp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = '{ \"State\":'.-1 .',"MsgState":"'.$rsp.' '.curl_error($curl).'"}';
            } else {
                $result = $ret;
            }
        }
        curl_close($curl);

        return $result;
    }
}

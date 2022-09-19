<?php

class overall
{
    static public function formatNumber($string)
    {
        $output = substr(sprintf("%.3f", $string), 0, -1);
        if ($output != '0.00' && $output % 10000 == 0 && $output >= 10000) {
            $output = intval($output / 10000) . '万';
            return $output;
        }
        return $output . '元';
    }

    static public function encryptPhoneNumber($string)
    {
        $output = substr_replace($string, '****', 3, 4);
        return $output;
    }

    //姓名
    static public function encryptName($string)
    {
        $string1 = substr($string, 0, 1);
        if (ord($string1) > 127)
            $string1 = mb_substr($string, 0, 1, 'utf-8');
        $output = $string1 . '**';
        return $output;
    }

    //验证码
    static public function encryptContent($string, $strtype)
    {
        $stype = ['gaibank','gai_phone','pfind','cvcode','pvcode','vcode','direct','mob_auth','admnPwdSms','dualFactor','admRsetPwd','fvcode','updatepwd'];
        if (in_array($strtype,$stype))
            $output = preg_replace('/[a-zA-Z0-9~!@#$%^*&?\/<>+-|{}();=]{6,}/', '******', $string); ///^[_0-9a-z]{6,16}$/i
        else
            $output = $string;
        return $output;
    }

    static public function addunit($string)
    {
        return $string . '元';
    }

    /**
     * 获取手机号所属运营商
     * @param: string $phone
     * @return string $operator (CT：China Telecom  CM：China Mobile  CU：China Unicom)
     **/
    static public function GetMobileOperator($phone)
    {
        $CM = array('134', '135', '136', '137', '138', '139', '150', '151', '152', '158', '159', '157', '187', '188', '147');
        $CU = array('130', '131', '132', '155', '156', '185', '186');
        $CT = array('133', '153', '180', '189');

        $operator = '';

        if (in_array(substr($phone, 0, 3), $CT))
            $operator = 'CT';
        if (in_array(substr($phone, 0, 3), $CM))
            $operator = 'CM';
        if (in_array(substr($phone, 0, 3), $CU))
            $operator = 'CU';

        return $operator;
    }

    /**
     * 字符串截取
     */
    public static function truncate_utf8_string($string, $length, $etc = '...')
    {
        $result = '';
        $string = html_entity_decode(trim(strip_tags($string)), ENT_QUOTES, 'UTF-8');
        $strlen = strlen($string);
        for ($i = 0; (($i < $strlen) && ($length > 0)); $i++) {
            if ($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')) {
                if ($length < 1.0) {
                    break;
                }
                $result .= substr($string, $i, $number);
                $length -= 1.0;
                $i += $number - 1;
            } else {
                $result .= substr($string, $i, 1);
                $length -= 0.5;
            }
        }
        $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        if ($i < $strlen) {
            $result .= $etc;
        }
        return $result;
    }

    /**
     * 汉字首字母获取
     * @param: string $str
     */
    public static function getinitial($str)
    {
        $asc = ord(substr($str, 0, 1));  //ord()获取ASCII
        if ($asc < 160) //非中文
        {
            if ($asc >= 48 && $asc <= 57) {
                return '1';  //数字
            } elseif ($asc >= 65 && $asc <= 90) {
                return chr($asc);   // A--Z chr将ASCII转换为字符
            } elseif ($asc >= 97 && $asc <= 122) {
                return chr($asc - 32); // a--z
            } else {
                return '~'; //其他
            }
        } else   //中文
        {
            $asc = $asc * 1000 + ord(substr($str, 1, 1));
            //获取拼音首字母A--Z
            if ($asc >= 176161 && $asc < 176197) {
                return 'A';
            } elseif ($asc >= 176197 && $asc < 178193) {
                return 'B';
            } elseif ($asc >= 178193 && $asc < 180238) {
                return 'C';
            } elseif ($asc >= 180238 && $asc < 182234) {
                return 'D';
            } elseif ($asc >= 182234 && $asc < 183162) {
                return 'E';
            } elseif ($asc >= 183162 && $asc < 184193) {
                return 'F';
            } elseif ($asc >= 184193 && $asc < 185254) {
                return 'G';
            } elseif ($asc >= 185254 && $asc < 187247) {
                return 'H';
            } elseif ($asc >= 187247 && $asc < 191166) {
                return 'J';
            } elseif ($asc >= 191166 && $asc < 192172) {
                return 'K';
            } elseif ($asc >= 192172 && $asc < 194232) {
                return 'L';
            } elseif ($asc >= 194232 && $asc < 196195) {
                return 'M';
            } elseif ($asc >= 196195 && $asc < 197182) {
                return 'N';
            } elseif ($asc >= 197182 && $asc < 197190) {
                return 'O';
            } elseif ($asc >= 197190 && $asc < 198218) {
                return 'P';
            } elseif ($asc >= 198218 && $asc < 200187) {
                return 'Q';
            } elseif ($asc >= 200187 && $asc < 200246) {
                return 'R';
            } elseif ($asc >= 200246 && $asc < 203250) {
                return 'S';
            } elseif ($asc >= 203250 && $asc < 205218) {
                return 'T';
            } elseif ($asc >= 205218 && $asc < 206244) {
                return 'W';
            } elseif ($asc >= 206244 && $asc < 209185) {
                return 'X';
            } elseif ($asc >= 209185 && $asc < 212209) {
                return 'Y';
            } elseif ($asc >= 212209) {
                return 'Z';
            } else {
                return '~';
            }
        }
    }

    //累计投资
    static public function getInvestedMoney($user_id)
    {
        $timeSql = "SELECT user_id,SUM(account) AS invested_money FROM dw_borrow_tender where user_id=" . $user_id;
        $timeResult = Yii::app()->dwdb->createCommand($timeSql)->query();
        $invested_money = 0;
        foreach ($timeResult as $row) {
            $invested_money = $row['invested_money'];
        }
        return overall::formatNumber($invested_money);
    }

    //累计充值
    static public function getRechargeAmount($user_id)
    {
        $paySql = "SELECT SUM(money) as recharge_amount ,user_id from dw_account_recharge where status=1 AND user_id =" . $user_id;
        $payResult = Yii::app()->dwdb->createCommand($paySql)->query();
        $recharge_amount = 0;
        foreach ($payResult as $row) {
            $recharge_amount = $row['recharge_amount'];
        }
        return overall::formatNumber($recharge_amount);
    }

    //优惠券总额
    static public function getAmounts($user_id)
    {
        $couponSql = 'SELECT SUM(amount) as amounts FROM dw_coupon where user_id = ' . $user_id . ' AND status not in(2,3)';
        $couponResult = Yii::app()->dwdb->createCommand($couponSql)->query();
        if (isset($couponResult)) {
            foreach ($couponResult as $k => $v) {
                $use_virtual_money = $v['amounts'];
            }
        } else {
            $use_virtual_money = 0;
        }
        return overall::formatNumber($use_virtual_money);
    }
    static public function getPointName($code) {
        $name = current(Yii::app()->dwdb->createCommand()->select('name')->where('code=:code', [':code' => $code])->from('itz_trigger_point')->queryColumn());
        if(!$name) return $code;
        return $name;
    }
}
<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-23 10:09:06
 * @encode UTF-8编码
 */
class P_Crypt_Xid extends P_Crypt_Abstract {

    const DEFAULT_KEY = 'phoenix';
    const FAST_CRYPT_RAW_SECRET_MAX_SIZE = 600;
    const FAST_D2HSTR_MAX_DATA_LEN = 600;
    const SIZE_OF_INT = 4;

    private $_token = false;
    private $_raw_secret = "\xA1\xB6\xC0\xC1\xA2\xBA\xAE\xC7\xEF\x0D\x0A\xCF\xE6\xBD\xAD\xB1\xB1\x3D\xC8\xA5\x0D\x0A\xE9\xD9\xD7\xD3\xD6\xDE\xCD\xB7\x0D\x0A\xBF\xB4\xB2\xCD\xF2\xC9\xBD\xBA\xEC\xB1\xE9\x0D\x0A\xB2\xE3\xC1\xD6\xBE\xA1\xC8\xA5\x0D\x0A\xE9\xD9\xD7\xD3\xD6\xDE\xCD\xB7\x0D\x0A\xBF\xB4\xCD\xF2\xC9\xBD\xBA\xEC\xB1\xE9\x0D\x0A\xB2\xE3\xC1\xD6\xBE\xA1\xC8\xBE\x0D\x0A\xC2\xFE\xBD\xAD\xB1\xCC\xCD\xB8\x0D\x0A\xB0\xD9\xF4\xB4\xD5\xF9\xC1\xF7\x0D\x0A\xD3\xA5\xBB\xF7\xB3\xA4\xBF\xD5\x0D\x0A\xD3\xE3\xCF\xE8\xC7\xB3\xB5\xD7\x0D\x0A\xCD\xF2\xCE\xEF\xCB\xAA\xCC\xEC\xBE\xBA\xD7\xD4\xD3\xC9\x0D\x0A\xE2\xEA\xC1\xC8\xC0\xAA\x0D\x0A\xCE\xCA\xB2\xD4\xC3\xA3\xB4\xF3\xB5\xD8\x0D\x0A\xCB\xAD\xD6\xF7\xB3\xC1\xB8\xA1\x0D\x0A\xD0\xAF\xC0\xB4\xB0\xD9\xC2\xC2\xD4\xF8\xD3\xCE\x0D\x0A\xD2\xE4\xCD\xF9\xCE\xF4\xE1\xBF\xE1\xC9\xCB\xEA\xD4\xC2\xB3\xED\x0D\x0A\xC7\xA1\xCD\xAC\xD1\xA7\xC9\xD9\xC4\xEA\x0D\x0A\xB7\xE7\xBB\xAA\xD5\xFD\xC3\xAF\x0D\x0A\xCA\xE9\xC9\xFA\xD2\xE2\xC6\xF8\x0D\x0A\xBB\xD3\xB3\xE2\xB7\xBD\xE5\xD9\x0D\x0A\xD6\xB8\xB5\xE3\xBD\xAD\xC9\xBD\x0D\x0A\xBC\xA4\xD1\xEF\xCE\xC4\xD7\xD6\x0D\x0A\xB7\xE0\xCD\xC1\xB5\xB1\xC4\xEA\xCD\xF2\xBB\xA7\xBA\xEE\x0D\x0A\xD4\xF8\xBC\xC7\xB7\xF1\x0D\x0A\xB5\xBD\xD6\xD0\xC1\xF7\xBB\xF7\xCB\xAE\x0D\x0A\xC0\xCB\xB6\xF4\xB7\xC9\xD6\xDB\x0D\x0A\xBA\xE1\xBF\xD5\xB3\xF6\xCA\xC0\x0D\x0A\xC3\xA7\xC0\xA5\xC2\xD8\x0D\x0A\xD4\xC4\xBE\xA1\xC8\xCB\xBC\xE4\xB4\xBA\xC9\xAB\x0D\x0A\xB7\xC9\xC6\xF0\xD3\xF1\xC1\xFA\xC8\xFD\xB0\xD9\xCD\xF2\x0D\x0A\xBD\xC1\xB5\xC3\xD6\xDC\xCC\xEC\xBA\xAE\xB3\xB9\x0D\x0A\xCF\xC4\xC8\xD5\xCF\xFB\xC8\xDA\x0D\x0A\xBD\xAD\xBA\xD3\xBA\xE1\xD2\xE7\x0D\x0A\xC8\xCB\xBB\xF2\xCE\xAA\xD3\xE3\xB1\xEE\x0D\x0A\xC7\xA7\xC7\xEF\xB9\xA6\xD7\xEF\x0D\x0A\xCB\xAD\xC8\xCB\xD4\xF8\xD3\xEB\xC6\xC0\xCB\xB5\x0D\x0A\xB6\xF8\xBD\xF1\xCE\xD2\xCE\xBD\xC0\xA5\xC2\xD8\x0D\x0A\xB2\xBB\xD2\xAA\xD5\xE2\xB8\xDF\x0D\x0A\xB2\xBB\xD2\xAA\xD5\xE2\xB6\xE0\xD1\xA9\x0D\x0A\xB0\xB2\xB5\xC3\xD2\xD0\xCC\xEC\xB3\xE9\xB1\xA6\xBD\xA3\x0D\x0A\xB0\xD1\xC8\xEA\xB2\xC3\xCE\xAA\xC8\xFD\xBD\xD8\x0D\x0A\xD2\xBB\xBD\xD8\xD2\xC5\xC5\xB7\x0D\x0A\xD2\xBB\xBD\xD8\xD4\xF9\xC3\xC0\x0D\x0A\xD2\xBB\xBD\xD8\xBB\xB9\xB6\xAB\xB9\xFA\x0D\x0A\xCC\xAB\xC6\xBD\xCA\xC0\xBD\xE7\x0D\x0A\xBB\xB7\xC7\xF2\xCD\xAC\xB4\xCB\xC1\xB9\xC8\xC8";
    private $_raw_dest;

    public function __construct($key = self::DEFAULT_KEY, $token = false) {
        $this->_token = (false === $token) ? time() : intval($token);
        parent::__construct($key);
    }

    protected function _decrypt($data, $key = false) {
        $data = trim(strval($data));
        if (strlen($this->_raw_dest) == 0 || empty($data) || strlen($data) < 8) {
            return false;
        }
        $len = strlen($data);
        if ($len > (self::FAST_D2HSTR_MAX_DATA_LEN + 4) * 2) {
            return -2;
        }
        $buff = "";
        for ($i = 0; $i < $len; $i = $i + 2) {
            $tmp = substr($data, $i, 2);
            if (strlen($tmp) != 2) {
                $tmp = "\0";
            }
            $buff .= pack('C', (hexdec($tmp)));
        }
        $len = $buff_len = strlen($buff);
        $ret = $tail = "";
        $magic = 0;
        if ($len >= 2) {
            $rdata = unpack('va', substr($buff, $len - 2, 2));
            $unit = $len & 0xff;
            $tmp = unpack('va', substr($this->_raw_dest, 2 * $unit, 2));
            $wdata = $rdata['a'] ^ $tmp['a'];
            $tail = pack('v', $wdata);
            $magic = $len + $wdata;
            $len -= 2;
        }
        $unit = 0;
        while ($len > 1) {
            $rdata = unpack('va', substr($buff, $unit, 2));
            $unit += 2;
            $tmp = unpack('va', substr($this->_raw_dest, 2 * (($magic++) & 0xff), 2));
            $ret .= pack('v', $rdata['a'] ^ $tmp['a']);
            $len -= 2;
        }
        if ($len > 0) {
            $rdata = unpack('Ca', substr($buff, $unit, 1));
            $tmp = unpack('va', substr($this->_raw_dest, 2 * ($magic & 0xff), 2));
            $ret .= pack('C', ($rdata['a'] ^ ($tmp['a'] & 0xff)) & 0xff);
        }
        $ret .= $tail;
        $ret_len = strlen($ret);
        $old_sum = unpack('La', substr($ret, $ret_len - 4, 4));
        $sum = $this->_checksum_int(substr($ret, 0, $ret_len - 4));
        $sum = ($sum >> 16) | (($sum << 16) & 0xffff0000);
        $sum = unpack('La', pack('L', $sum));
        if ($sum['a'] != $old_sum['a']) {
            return false;
        }
        if (($buff_len - 4) != 2 * self::SIZE_OF_INT) {
            return false;
        }
        $id = unpack('L2a', substr($ret, 0, 8));
        return $id['a2'];
    }

    protected function _encrypt($data, $key = false) {
        $data = pack('L', $this->_token) . pack('L', intval($data));
        $buff = $data;
        $sum = $this->_checksum_int($data);
        $sum = ($sum >> 16) | (($sum << 16) & 0xffff0000);
        $buff .= pack('L', $sum);
        $len = strlen($buff);
        $ret = $last = "";
        if ($len >= 2) {
            $data_unpack = unpack('va', substr($buff, $len - 2, 2));
            $magic = $len + $data_unpack['a'];
            $index = $len & 0xff;
            $dest_unpack = unpack('va', substr($this->_raw_dest, $index * 2, 2));
            $last = pack('v', $data_unpack['a'] ^ $dest_unpack['a']);
            $len -= 2;
        }
        $count = 0;
        while ($len > 1) {
            $index = $magic & 0xff;
            $data_unpack = unpack('va', substr($buff, $count, 2));
            $dest_unpack = unpack('va', substr($this->_raw_dest, $index * 2, 2));
            $ret .= pack('v', ($data_unpack['a'] ^ $dest_unpack['a']));
            $len -= 2;
            $count += 2;
            $magic++;
        }
        if ($len > 0) {
            $index = $magic & 0xff;
            $data_unpack = unpack('Ca', substr($buff, $count, 1));
            $dest_unpack = unpack('va', substr($this->_raw_dest, $index * 2, 2));
            $ret .= pack('C', ($data_unpack['a'] ^ $data_unpack['a']) & 0xff);
        }
        return strtolower(bin2hex($ret . $last));
    }

    private function _checksum_int($key) {
        $sum = 0;
        $len = strlen($key);
        $start = 0;
        if ($len % 2 != 0) {
            $key = str_pad($key, $len + 1, "\x00");
        }
        while ($len > 1) {
            $tmp = unpack('va', substr($key, $start, 2));
            $len -= 2;
            $start += 2;
            $sum += $tmp['a'];
        }
        if ($len > 0) {
            $tmp = unpack('va', substr($key, $start, 2));
            $sum += ($tmp['a'] & 0xff);
        }
        return $sum;
    }

    protected function _generate_key($key) {
        mt_srand($this->_checksum_int($key));
        $raw_secret_len = strlen($this->_raw_secret);
        $this->_raw_secret = str_pad($this->_raw_secret, self::FAST_CRYPT_RAW_SECRET_MAX_SIZE, "\x00");
        $count = intval(self::FAST_CRYPT_RAW_SECRET_MAX_SIZE / self::SIZE_OF_INT);
        for ($i = 0; $i < $count; $i++) {
            $rand = mt_rand();
            if ($i * self::SIZE_OF_INT < $raw_secret_len) {
                $tmp = unpack('la', substr($this->_raw_secret, $i * self::SIZE_OF_INT, self::SIZE_OF_INT));
                $rand = $rand + intval($tmp['a']);
            }
            $this->_raw_dest .=pack('l', $rand);
        }
        return $this->_raw_dest;
    }

    public function get_error() {
        
    }

}

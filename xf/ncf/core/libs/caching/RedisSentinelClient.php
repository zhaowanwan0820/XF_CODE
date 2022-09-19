<?php
namespace libs\caching;

/*!
 * @file RedisSentinelClient.php
 * @author Ryota Namiki <ryo180@gmail.com>
 */
class RedisSentinelClientNoConnectionExecption extends \Exception { }
/*!
 * @class RedisSentinelClient
 *
 * Redis Sentinel クライアントクラス
 */
class RedisSentinelClient
{
    protected $_socket;
    protected $_host;
    protected $_port;
    protected $_timeout; // float in seconds

    public function __construct($h, $p = 26379, $t = 1) {
        $this->_host = $h;
        $this->_port = $p;
    }
    public function __destruct() {
        if ($this->_socket) {
            $this->_close();
        }
    }

    /*!
     * PING コマンド発行
     *
     * @retval boolean true 疎通成功
     * @retval boolean false 疎通失敗
     */
    public function ping() {
        if ($this->_connect()) {
            $this->_write('PING');
            $this->_write('QUIT');
            $data = $this->_get();
            $this->_close();
            return ($data === '+PONG');
        } else {
            return false;
        }
    }

    /*!
     * SENTINEL masters コマンド発行
     *
     * @retval array サーバからの返却値を読みやすくした値
     * @code
     * array (
     *   [0]  => // master の index
     *     array(
     *       'name' => 'mymaster',
     *       'host' => 'localhost',
     *       'port' => 6379,
     *       ...
     *     ),
     *   ...
     * )
     * @endcode
     */
    public function masters() {
        if($this->_connect()) {
            $this->_write('SENTINEL masters');
            $this->_write('QUIT');
            $data = $this->_extract($this->_get());
            $this->_close();
            return $data;
        } else {
            throw new RedisSentinelClientNoConnectionExecption;
        }
    }

    /*!
     * SENTINEL slaves コマンド発行
     *
     * @param [in] $master string マスター名称
     * @retval array サーバからの返却値を読みやすくした値
     * @code
     * array (
     *   [0]  =>
     *     array(
     *       'name' => 'mymaster',
     *       'host' => 'localhost',
     *       'port' => 6379,
     *       ...
     *     ),
     *   ...
     * )
     * @endcode
     */
    public function slaves($master) {
        if ($this->_connect()) {
            $this->_write('SENTINEL slaves ' . $master);
            $this->_write('QUIT');
            $data = $this->_extract($this->_get());
            $this->_close();
            return $data;
        } else {
            throw new RedisSentinelClientNoConnectionExecption;
        }
    }

    /*!
     * SENTINEL is-master-down-by-addr コマンド発行
     *
     * @param [in] $ip   string  対象サーバIPアドレス
     * @param [in] $port integer ポート番号
     * @retval array サーバからの返却値を読みやすくした値
     * @code
     * array (
     *   [0]  => 1
     *   [1]  => leader
     * )
     * @endcode
     */
    public function is_master_down_by_addr($ip, $port) {
        if ($this->_connect()) {
            $this->_write('SENTINEL is-master-down-by-addr ' . $ip . ' ' . $port);
            $this->_write('QUIT');
            $data = $this->_get();
            $lines = explode("\r\n", $data, 4);
            list (/* elem num*/, $state, /* length */, $leader) = $lines;
            $this->_close();
            return array(ltrim($state, ':'), $leader);
        } else {
            throw new RedisSentinelClientNoConnectionExecption;
        }
    }

    /*!
     * SENTINEL get-master-addr-by-name コマンド発行
     *
     * @param [in] $master string マスター名称
     * @retval array サーバからの返却値を読みやすくした値
     * @code
     * array (
     *   [0]  =>
     *     array(
     *       '<IP ADDR>' => '<PORT>',
     *     )
     * )
     * @endcode
     */
    public function get_master_addr_by_name($master) {
        if ($this->_connect()) {
            $this->_write('SENTINEL get-master-addr-by-name ' . $master);
            $this->_write('QUIT');
            $data = $this->_extract($this->_get());
            $this->_close();
            return $data;
        } else {
            throw new RedisSentinelClientNoConnectionExecption;
        }
    }

    /*!
     * SENTINEL reset コマンド発行
     *
     * @param [in] $pattern string マスター名称パターン(globスタイル)
     * @retval integer pattern にマッチしたマスターの数
     */
    public function reset($pattern) {
        if ($this->_connect()) {
            $this->_write('SENTINEL reset ' . $pattern);
            $this->_write('QUIT');
            $data = $this->_get();
            $this->_close();
            return ltrim($data, ':');
        } else {
            throw new RedisSentinelClientNoConnectionExecption;
        }
    }

    /*!
     * Sentinel サーバとの接続を行う
     *
     * @retval boolean true  接続成功
     * @retval boolean false 接続失敗
     */
    protected function _connect() {
        // @TODO: timeout ?
        $this->_socket = @fsockopen($this->_host, $this->_port, $en, $es);

        return !!($this->_socket);
    }

    /*!
     * Sentinel サーバとの接続を切断する
     *
     * @retval boolean true  切断成功
     * @retval boolean false 切断失敗
     */
    protected function _close() {
        $ret = @fclose($this->_socket);
        $this->_socket = null;
        return $ret;
    }

    /*!
     * Sentinel サーバからの返却がまだあるか
     *
     * @retval boolean true  残データ有り
     * @retval boolean false 残データ無し
     */
    protected function _receiving() {
        return !feof($this->_socket);
    }

    /*!
     * Sentinel サーバへコマンド発行
     *
     * @param [in] $c string コマンド文字列
     * @retval mixed integer 書き込んだバイト数
     * @retval mixed boolean false エラー発生
     */
    protected function _write($c) {
        return fwrite($this->_socket, $c . "\r\n");
    }

    /*!
     * Sentinel サーバからの返却値を取得
     *
     * @retval string 返却値
     */
    protected function _get() {
        $buf = '';
        while($this->_receiving()) {
            $buf .= fgets($this->_socket);
        }
        return rtrim($buf, "\r\n+OK\n");
    }

    /*!
     * 多次元階層を表す Redis レスポンス文字列を配列へ変換
     *
     * @param [in] $data string サーバからの返却値文字列
     * @retval array 配列1
     */
    protected function _extract($data) {
        if (!$data) return array();
        $lines = explode("\r\n", $data);
        $is_root = $is_child = false;
        $c = count($lines);
        $results = $current = array();
        for ($i = 0; $i < $c; $i++) {
            $str = $lines[$i];
            $prefix = substr($str, 0, 1);
            if ($prefix === '*') {
                if (!$is_root) {
                    $is_root = true;
                    $current = array();
                    continue;
                } else if (!$is_child) {
                    $is_child = true;
                    continue;
                } else {
                    $is_root = $is_child = false;
                    $results[] = $current;
                    continue;
                }
            }
            $keylen = $lines[$i++];
            $key    = $lines[$i++];
            $vallen = $lines[$i++];
            $val    = $lines[$i++];
            $current[$key] = $val;

            --$i;
        }
        $results[] = $current;
        return $results;
    }
}

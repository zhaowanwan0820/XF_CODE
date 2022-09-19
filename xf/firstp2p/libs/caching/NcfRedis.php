<?php
namespace libs\caching;

require __DIR__ . '/predis/src/Autoloader.php';
\Predis\Autoloader::register();

class NcfRedis
{
    const REDIS_HOST = 'host';
    const REDIS_PORT = 'port';

    private $sentinels = array();

    private $sentinelConnection = null;

    public function getSingleConnection($hostname, $port, $persistent = true)
    {
        $params = array(
            'scheme' => 'tcp',
            'host'   => $hostname,
            'port'   => $port,
        );
        $options = array();
        if($persistent == true) {
            $params['connection_persistent'] = true;
            $options = array(
                'connections' => array(
                    'tcp' => 'Predis\Connection\PhpiredisStreamConnection',
                ),
            );
        }
        return new \Predis\Client($params, $options);
    }

    public function getClusterConnection($masterName, &$clusters = array(), $database = 0, $forceRecache = false)
    {
        if(rand() % 1000 == 0 || $forceRecache) {
            $sentinelClient = $this->getSentinelConnection();
            $masters = $sentinelClient->sentinel('masters');
            $slaves = $sentinelClient->sentinel('slaves', $masterName);
            $clusters = array();
            $master = reset($masters);
            $clusters[] = array(
                'scheme' => 'tcp',
                'host'   => $master['ip'],
                'port'   => $master['port'],
                'alias'  => 'master',
                'database' => $database,
                'connection_persistent' => true,
                'read_write_timeout' => 10,
            );
            // Make master readable
            $clusters[] = array(
                'scheme' => 'tcp',
                'host'   => $master['ip'],
                'port'   => $master['port'],
                'alias'  => 'slave-0',
                'database' => $database,
                'connection_persistent' => true,
                'read_write_timeout' => 10,
            );
            foreach ($slaves as $k => $slave) {
                // var_dump($slave);
                $flags = explode(",", $slave['flags']);
                // Check slave status
                if(in_array('s_down', $flags)
                   || in_array('o_down', $flags)
                   || in_array('disconnected', $flags))
                {
                    // do nothing
                } else {
                    $clusters[] = array(
                        'scheme' => 'tcp',
                        'host'   => $slave['ip'],
                        'port'   => $slave['port'],
                        'alias'  => 'slave-' . ($k+1),
                        'database' => $database,
                        'connection_persistent' => true,
                        'read_write_timeout' => 10,
                    );
                }
            }
        }
        $options = array(
            'connections' => array(
                'tcp' => 'Predis\Connection\PhpiredisStreamConnection',
            ),
            'replication' => true,
        );

        return new \Predis\Client($clusters, $options);
    }

    public function setSentinel(array $sentinel)
    {
        if(!empty($sentinel[self::REDIS_HOST]) && !empty($sentinel[self::REDIS_PORT])) {
            array_push($this->sentinels,
                       array(
                           self::REDIS_HOST => $sentinel[self::REDIS_HOST],
                           self::REDIS_PORT => $sentinel[self::REDIS_PORT]
                       )
            );
        } else {
            throw new \InvalidArgumentException();
        }
    }

    public function setSentinels(array $sentinels)
    {
        foreach ($sentinels as $sentinel) {
            $this->setSentinel($sentinel);
        }
    }

    public function getSentinels($idx = null, $key = '')
    {
        if(is_null($idx)) {
            return $this->sentinels;
        }

        if(isset($this->sentinels[$idx])) {
            $sNode = $this->sentinels[$idx];
            if(isset($sNode[$key])) {
                return $sNode[$key];
            } else {
                return $sNode;
            }
        }
        throw new \OutOfBoundsException();
    }

    public function getSentinelConnection()
    {
        if (empty($this->sentinels)) {
            throw new \UnderflowException("需要先设置哨兵(setSentinels)才能获取哨兵连接");
        }

        if($this->sentinelConnection === null) {
            $i = 0;
            $idx = null;
            do {
                if(++$i > 1) {
                    unset($this->sentinels[$idx]);
                }
                $idx = array_rand($this->sentinels);
                $host = $this->getSentinels($idx, self::REDIS_HOST);
                $port = $this->getSentinels($idx, self::REDIS_PORT);
                $this->sentinelConnection = new \Predis\Client(
                    array(
                        'scheme' => 'tcp',
                        'host'   => $host,
                        'port'   => $port,
                    )
                );
                try {
                    $ret = $this->sentinelConnection->ping();
                } catch(\Exception $e) {
                    \libs\utils\Logger::error("redis sentinel connection may down: " . $e->getMessage());
                    continue;
                }
            } while ($i <= 3 && $ret == false);
        }
        return $this->sentinelConnection;
    }

}

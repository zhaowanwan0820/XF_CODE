<?php

class ZookeeperWrapper extends CApplicationComponent
{

    /**
     * @var string
     */
    public $address = 'localhost:2181';
    /**
     * @var int timeout
     */
    public $timeout = 100000;
    /**
     * @var bool
     */
    public $watch = null;

    /**
     * @var Zookeeper
     */
    protected $zookeeper;

    /**
     * @var Callback container
     */
    private $callback = [];

    public function init()
    {
        $this->zookeeper = new Zookeeper($this->address, $this->watch, $this->timeout);
        return parent::init();
    }

    /**
     * Set a node to a value. If the node doesn't exist yet, it is created.
     * Existing values of the node are overwritten
     *
     * @param string $path The path to the node
     * @param mixed $value The new value for the node
     *
     * @return mixed previous value if set, or null
     */
    public function set($path, $value)
    {
        if (!$this->zookeeper->exists($path)) {
            $this->makePath($path);
            return $this->makeNode($path, $value);
        } else {
            return $this->zookeeper->set($path, $value);
        }
    }

    /**
     * Equivalent of "mkdir -p" on ZooKeeper
     *
     * @param string $path The path to the node
     * @param string $value The value to assign to each new node along the path
     *
     * @return bool
     */
    public function makePath($path, $value = '')
    {
        $parts = explode('/', $path);
        $parts = array_filter($parts);
        $subpath = '';
        while (count($parts) > 1) {
            $subpath .= '/' . array_shift($parts);
            if (!$this->zookeeper->exists($subpath)) {
                $this->makeNode($subpath, $value);
            }
        }
    }

    /**
     * Create a node on ZooKeeper at the given path
     *
     * @param string $path The path to the node
     * @param string $value The value to assign to the new node
     * @param array $params Optional parameters for the Zookeeper node.
     *                       By default, a public node is created
     *
     * @return string the path to the newly created node or null on failure
     */
    public function makeNode($path, $value, $perms = "", array $params = [])
    {
        $perms = $perms ? $perms : \Zookeeper::PERM_ALL;
        if (empty($params)) {
            $params = [
                [
                    'perms' => $perms,
                    'scheme' => 'world',
                    'id' => 'anyone',
                ]
            ];
        }
        if ($perms == \Zookeeper::EPHEMERAL) {
            $this->zookeeper->create($path, $value, $params, $perms);
        } else {
            return $this->zookeeper->create($path, $value, $params);
        }
    }

    /**
     * Get the value for the node
     *
     * @param string $path the path to the node
     *
     * @return string|null
     */
    public function get($path)
    {
        if (!$this->zookeeper->exists($path)) {
            return null;
        }
        return $this->zookeeper->get($path);
    }

    /**
     * List the children of the given path, i.e. the name of the directories
     * within the current node, if any
     *
     * @param string $path the path to the node
     *
     * @return array the subpaths within the given node
     */
    public function getChildren($path)
    {
        if (strlen($path) > 1 && preg_match('@/$@', $path)) {
            // remove trailing /
            $path = substr($path, 0, -1);
        }
        return $this->zookeeper->getChildren($path);
    }

    public function getState()
    {
        return $this->zookeeper->getState();
    }

    /**
     * Delete the node if it does not have any children
     *
     * @param string $path the path to the node
     *
     * @return true if node is deleted else null
     */

    public function deleteNode($path)
    {
        if (!$this->zookeeper->exists($path)) {
            return null;
        } else {
            return $this->zookeeper->delete($path);
        }
    }

    /**
     * Wath a given path
     * @param string $path the path to node
     * @param callable $callback callback function
     * @return string|null
     */
    public function watch($path, $callback)
    {
        if (!is_callable($callback)) {
            return null;
        }

        if ($this->zookeeper->exists($path)) {
            if (!isset($this->callback[$path])) {
                $this->callback[$path] = [];
            }
            if (!in_array($callback, $this->callback[$path])) {
                $this->callback[$path][] = $callback;
                return $this->zookeeper->get($path, [$this, 'watchCallback']);
            }
        }
    }

    /**
     * Wath event callback warper
     * @param int $event_type
     * @param int $stat
     * @param string $path
     * @return the return of the callback or null
     */
    public function watchCallback($event_type, $stat, $path)
    {
        if (!isset($this->callback[$path])) {
            return null;
        }

        foreach ($this->callback[$path] as $callback) {
            $this->zookeeper->get($path, [$this, 'watchCallback']);
            return call_user_func($callback);
        }
    }

    /**
     * Delete watch callback on a node, delete all callback when $callback is null
     * @param string $path
     * @param callable $callback
     * @return boolean|NULL
     */
    public function cancelWatch($path, $callback = null)
    {
        if (isset($this->callback[$path])) {
            if (empty($callback)) {
                unset($this->callback[$path]);
                $this->zookeeper->get($path); //reset the callback
                return true;
            } else {
                $key = array_search($callback, $this->callback[$path]);
                if ($key !== false) {
                    unset($this->callback[$path][$key]);
                    return true;
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }
    }

    public function connect()
    {
        return $this->zookeeper->connect($this->address);
    }
}

//$zk = new ZookeeperWrapper();
//$zk->address = "192.168.22.116:2181";
//$zk->connect();

//获得一个节点的值
//$ls = $zk->get("/zk");
//var_export($ls);

////注册一个持久节点
//$zk->makeNode("/test/".time(),'hello');
//
////获得子节点
//$ls = $zk->getChildren("/test");
//var_export($ls);

//注册一个临时节点
//$res = $zk->makeNode("/counter/" . "192.168.1." . rand(1, 254) . ":1338", date("H:i:s"));
//
//$count = 10;
//while ($count > 0) {
//    echo $count--, "\n";
//    sleep(1);
//}

//echo "<HR>";
//var_export(get_class_methods($zk->zookeeper));

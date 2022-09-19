<?php
/**
 * 2016年4月18日
 * 完善权重规则。
 * 读的时候failover增加主库。
 * 增加gone away简单调用方法。
 * @author gaozhichang
 *
 */
class ItzDbConnection extends CDbConnection {
    
	/**
     * 主库连接对象
     * 
     * @var PDO
     */
    public $master;
    
	/**
     * 从库连接对象
     * 
     * @var PDO
     */
    public $slave;
    
    /**
     * servers 
     * db服务器配置
	 * 当masterServerConf不存在时候，master使用该配置
	 * 当slaveServerConf不存在时slave使用该配置
     * 
     * @var Array
     * @access public
     */
    public $servers;
    
	/**
     * masterServerConf 
     * db主库服务器配置
     * 
     * @var Array
     * @access public
     */
    public $masterServerConf;
    
	/**
     * slaveServerConf
     * db从库服务器配置
     * 
     * @var Array
     * @access public
     */
    public $slaveServerConf;

	/**
	 * 以下两个见cdbConnection注释
	 */
	public $emulatePrepare=true;
	public $enableProfiling=true;

    /**
     * curSql 
     * 当前要执行的sql
     * 
     * @var mixed
     * @access public
     */
    public $curSql;
    
	/**
     * 事务层级，用来支持嵌套事务
     * 
     * @var int
     */
    public $transactionCounter = 0;
    
	/**
     * 当前的pdo
     * 
     * @var PDO
     */
    public $curPdo;
    
    public function __construct($dsn = 'mysql:', $username = '', $password = '') {
        parent::__construct ( $dsn, $username, $password );
    }
    
    /**
     * setActive 
     * 覆盖此方法，避免在里面进行数据库的连接，从而进行laze connect
     * 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function setActive($value) {
    }
    
    /**
     * getLastInsertID 
     * 
     * @param string $sequenceName 
     * @access public
     * @return void
     */
    public function getLastInsertID($sequenceName = '') {
        $pdo = $this->getMasterPdoInstance ();
        return $pdo->lastInsertId ( $sequenceName );
    }
    public function quoteValue($str) {
        if (is_int ( $str ) || is_float ( $str ))
            return $str;
        
        $pdo = $this->getMasterPdoInstance ();
        if (($value = $pdo->quote ( $str )) !== false)
            return $value;
        else // the driver doesn't support quote (e.g. oci)
            return "'" . addcslashes ( str_replace ( "'", "''", $str ), "\000\n\r\\\032" ) . "'";
    }
    public function getAttribute($name) {
        $this->setActive ( true );
        return $this->curPdo->getAttribute ( $name );
    }
    
    /**
     * 创建命令对象
     * @see CDbConnection::createCommand()
     */
    public function createCommand($query = null) {
        return new ItzDbCommand ( $this, $query );
    }
    
    public function setSql($sql) {
        $this->curSql = $sql;
    }
    /**
     * begin一个事务
     * 
     */
    public function beginTransaction() {
        $this->transactionCounter ++;
        if ($this->transactionCounter > 1) {
            return;
        }
        Yii::trace ( 'Starting transaction', 'system.db.CDbConnection' );
        $pdo = $this->getMasterPdoInstance ();
        $pdo->beginTransaction ();
    }
    /**
     * commit一个事务
     * 
     */
    public function commit() {
        $this->transactionCounter --;
        if ($this->transactionCounter > 0) {
            return;
        }
        Yii::trace ( 'Committing transaction', 'system.db.CDbTransaction' );
        $pdo = $this->getMasterPdoInstance ();
        $pdo->commit ();
    }
    /**
     * rollback一个事务
     */
    public function rollback() {
        $this->transactionCounter --;
        if ($this->transactionCounter > 0) {
            return;
        }
        Yii::trace ( 'Rolling back transaction', 'system.db.CDbTransaction' );
        $pdo = $this->getMasterPdoInstance ();
        $pdo->rollback ();
    }
    /**
    * 切换到读主库
    **/
    public function switchToMaster() {
        $this->curPdo = $this->getMasterPdoInstance ();
        return $this->curPdo;
    }

    /**
    * 切换到读从库
    **/
    public function switchToSlave() {
        $this->master = null;
        $this->curPdo = $this->getSlavePdoInstance();
        return $this->curPdo;
    }

    
    /**
     * 创建PDO实例
     * 这里实现的读写分离策略：
     * 如果有显示指定走主库或从库，则遵循指定；否则进行以下策略：
     * 如果是更新类sql，则走主库；
     * 如果之前已经走过主库，否则后续的sql全走主库；
     * 如果是查询类sql且之前未走过主库，则走从库
     * 
     * @return PDO the pdo instance
     */
    public function getPdoInstance() {
        $pdoClass = $this->pdoClass;
        if ($this->isMasterConnecton ( $this->curSql )) {
            $this->curPdo = $this->getMasterPdoInstance ();
            return $this->curPdo;
        } else {
            $this->curPdo = $this->getSlavePdoInstance ();
            return $this->curPdo;
        }
    }
    /**
     * 判断此次命令是否需要走主库
     * 
     * @param String $sql
     */
    protected function isMasterConnecton($sql) {
        if ($this->master != null) {
            return true;
        }
        $sql = trim ( $sql );
        if (preg_match ( "/^(SELECT|SHOW|DESCRIBE|DESC)/i", $sql ) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * getMasterPdoInstance 
     * 获取主库连接对象
     * 
     * @access protected
     * @return 主库的pdo连接句柄
     */
    protected function getMasterPdoInstance() {
        if ($this->master == null) {
            $this->master = $this->createMasterPdoInstance ();
        }
        return $this->master;
    }

	protected function getPdoConnection($servers,$dbname,$username,$password,$_attributes,$isSlave=false)
	{
		if(empty($servers)){
			throw new CDbException ( Yii::t ( 'yii', 'empty servers' ) );
		}
        #shuffle ( $servers );
        $servers = $this->shuffleWithWeight($servers);
        
        if($isSlave){//如果是从库创建连接，主库也放到failover池子中，保证从库失败连接主库
        	array_push($servers,$this->servers['0']);
        }
       
        for($i = 0; $i < count ( $servers ); $i ++) {
            try {
            	//异常捕获，并连接下一个
                $server = $servers [$i];
				if(isset($server['connectionString'])){
					$connectionString = $server['connectionString'];
				}else{
					$connectionString = "mysql:host=".$server['ip'].";dbname=".$dbname;
				}
                $pdo = $this->_createPdoInstance ( $connectionString, $username, $password, $_attributes );
                unset($server['password']);
                Yii::log ( "connect server succ:" . print_r($server,true), CLogger::LEVEL_TRACE, __METHOD__ );
                return $pdo;
            } catch ( Exception $e ) {
                unset($server['password']);
                Yii::log ( "connect server fail:" . print_r($server,true), CLogger::LEVEL_WARNING, __METHOD__ );
            }
        }
        // 记录 log 时过滤掉 密码
        for($i = 0; $i < count($servers); $i++) {
            unset($servers [$i]['password']);
        }
        Yii::log ( "server:" . print_r($servers,true) . ",msg:" . $e->getMessage (), "connect server fail", CLogger::LEVEL_ERROR, __METHOD__ );
        throw new CDbException ( Yii::t ( 'yii', 'connect all server fail' ) );
	}
    
	/**
	 * 按照权重生成随机数组：
	 * （1）如果有一个从库没有配置weight 按照原来的规则处理。
	 * （2）如果weight=0 跳过此配置
	 * （3）如果配置的都是weight=0 按照原来的规则。
	 * @param unknown $servers
	 * @return unknown
	 */
	protected function shuffleWithWeight($servers){
		$nums = count($servers);
		if($nums>1){
			$sumWeight = 0;//权重求和
			$seed = '';//随机字符串种子
			for ($i=0;$i<$nums;$i++){
				if(!isset($servers[$i]['weight'])){
					shuffle ( $servers );
					return $servers;
				}elseif (isset($servers[$i]['weight']) && $servers[$i]['weight']==0){//如果权重为0跳过
					#unset($servers[$i]);
					continue;
				}
				$sumWeight += $servers[$i]['weight'];
				$seed.=str_repeat(chr(65+$i), $servers[$i]['weight']);
			}
			
			//如果都为0，按照原来规则
			if($sumWeight==0){
				shuffle ( $servers );
				return $servers;
			}
			
			//打乱顺序
			$seed =  str_shuffle($seed);
	
			$newkeys = array();
			for($i=0;$i<strlen($seed);$i++){
				if(!in_array($seed[$i],$newkeys)){
					$newkeys[] = $seed[$i];
					$newservers[] = $servers[ord($seed[$i])-65];
				}
				if(count($newkeys)==$nums) break;
			}
			return $newservers;
		}
		return $servers;
	}
	
	/**
     * getSlavePdoInstance 
     * 获取从库连接对象
     * 
     * @access protected
     * @return 从库的pdo连接句柄
     */
    protected function getSlavePdoInstance() {
        if ($this->slave == null) {
            $this->slave = $this->createSlavePdoInstance ();
        }
        return $this->slave;
    }
    
	/**
     * 创建主库连接对象
     * 
     * @throws CDbException
     */
    protected function createMasterPdoInstance() {
		if (empty($this->masterServerConf)){
			if (! is_array ( $this->servers ) || count ( $this->servers ) == 0) {
				throw new CDbException ( Yii::t ( 'yii', 'config error for DbConnection::connectionStrings' ) );
			}
			$server = $this->servers [0];
			$username = $server['username'];
			$password = $server['password'];
			$dbname = "";
			$_attributes = array();
			if ( isset ( $server['_attributes'] )) {
				$_attributes = $server['_attributes'];
			}
			$masterServers = array($server);
		}else{
			if(!isset($this->masterServerConf['serverConf'])){
				throw new CDbException( Yii::t ( 'yii', ' no serverConf error for DbConnection' ) );
			}
			$commonServer = new CommonServer($this->masterServerConf['serverConf']);
			$masterServers = $commonServer->getServiceList();
			$dbname = $this->masterServerConf['dbname'];
			$username = $this->masterServerConf['username'];
			$password = $this->masterServerConf['password'];
			$dbname = $this->masterServerConf['dbname'];
			$_attributes = array();
			if ( isset ( $this->masterServerConf['_attributes'] )) {
				$_attributes = $this->masterServerConf['_attributes'];
			}
		}
		return $this->getPdoConnection($masterServers,$dbname,$username,$password,$_attributes);
		
	}
	/**
	 * 获取从库连接对象
	 * 
	 * @throws CDbException
	 */
	protected function createSlavePdoInstance() {
		if (empty($this->slaveServerConf)){
			if (! is_array ( $this->servers ) || count ( $this->servers ) == 0) {
				throw new CDbException ( Yii::t ( 'yii', 'config error for DbConnection::connectionStrings' ) );
			}
			if (count ( $this->servers ) == 1) {
				return $this->createMasterPdoInstance ();
			}
			$slaveServers = $this->servers;
			array_shift ( $slaveServers );
			$username = $slaveServers[0]['username'];
			$password = $slaveServers[0]['password'];
			$dbname = "";
			$_attributes = array();
			if ( isset ( $slaveServers[0]['_attributes'] )) {
				$_attributes = $slaveServers[0]['_attributes'];
			}
		}else{
			if(!isset($this->slaveServerConf['serverConf'])){
				throw new CDbException( Yii::t ( 'yii', ' no serverConf error for DbConnection' ) );
			}
			$commonServer = new CommonServer($this->slaveServerConf['serverConf']);
			$slaveServers = $commonServer->getServiceList();
			$dbname = $this->slaveServerConf['dbname'];
			$username = $this->slaveServerConf['username'];
			$password = $this->slaveServerConf['password'];
			$dbname = $this->slaveServerConf['dbname'];
			$_attributes = array();
			if ( isset ( $this->slaveServerConf['_attributes'] )) {
				$_attributes = $this->slaveServerConf['_attributes'];
			}
		}
		return $this->getPdoConnection($slaveServers,$dbname,$username,$password,$_attributes,true);
    }
    
	/**
     * 创建一个数据库连接
     * 
     * @param String $connStr
     * @param String $userName
     * @param String $password
     * @param Array $attributes
     */
    protected function _createPdoInstance($connStr, $userName, $password, $attributes) {
    	//如果没有设置连接超时时间，设置为10秒。现在db里面没有设置
    	if(empty($attributes) or !isset($attributes['PDO::ATTR_TIMEOUT'])){
    		$attributes = array(PDO::ATTR_TIMEOUT=>1);
    	}
    	
        $pdo = new Pdo ( $connStr, $userName, $password, $attributes );
        $this->initConnection ( $pdo );
        
        return $pdo;
    }
    
    /**
     * 解决goneaway 问题，调用：
     * Yii::app()->dwdb->master = null;
     * Yii::app()->dwdb->slave = null;
     * 改成：
     * Yii::app()->flushPdoInstance();
     */
    public function flushPdoInstance(){
    	$this->master = null;
    	$this->slave = null;
    }
}

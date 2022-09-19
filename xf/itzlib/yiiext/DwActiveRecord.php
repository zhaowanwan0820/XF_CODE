<?php
/**
 * @file DwActiveRecord.php
 * @author (kuangjun@xxx.com)
 * @date 2013/12/25
 *
 **/
class DwActiveRecord extends CActiveRecord {

	/**
	 * 缓存数据库连接
	 */
	protected static $dbConnections = array ();
	public $attrCreateTime='addtime';
    public $attrModifyTime='modtime';
	public $attrCreateIp='addip';

     /**
	 * 数据库名称，默认为'db'
	 */
	public $dbname = 'db';

	/**
	 * 重写此方法，支持多个数据库连接
	 */
	public function getDbConnection() {
		$dbname = $this->dbname;

		if (isset ( self::$dbConnections [$dbname] ))
			return self::$dbConnections [$dbname];

		else {
			if ($dbname === 'db')
				$db = Yii::app ()->getDb ();
			else
				//throws Exception if 'dbname' CDbConnection not defined
				$db = Yii::app ()->$dbname;

			if ($db instanceof CDbConnection) {
				self::$dbConnections [$dbname] = $db;
				self::$dbConnections [$dbname]->setActive ( true );
				return self::$dbConnections [$dbname];
			} else
				throw new CDbException ( Yii::t ( 'yii', 'Active Record requires a "db" CDbConnection application component.' ) );
		}
	}

	public function tableName() {
		$name = $this->getClass ();
		if (strpos ( $name, "Model" ) === strlen ( $name ) - 5) {
			return str_replace ( "Model", "", $name );
		} else {
			return $name;
		}
	}

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            // 1. 默认情况没有设置变量，执行 XSS 过滤；
            // 2. 设置了变量，且为 true ，执行 XSS 过滤。
            $key = 'NEED_XSS_PREVENT';
            if (!isset($GLOBALS[$key]) || $GLOBALS[$key] === true) {
                $metaData = $this->getMetaData();
                $htmlPurifier = new CHtmlPurifier;
                foreach ($this->getAttributes() as $key => $value) {
                    if (empty($value)) continue;
                    if (false == isset($metaData->columns) || $metaData->columns[$key]->type === 'string') {
                        $this->$key = $htmlPurifier->purify($this->$key);
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

	public function beforeSave() {
		$metaData = $this->getMetaData ();
		if ($this->getIsNewRecord ()) {
			if (isset ( $metaData->columns [$this->attrCreateTime] ) && !empty($this->attrCreateTime)) {
				if(!$this->getAttribute($this->attrCreateTime)){
				    $this->setAttribute($this->attrCreateTime,time());
                }
			}
            if (isset ( $metaData->columns [$this->attrCreateIp] ) && !empty($this->attrCreateIp)) {
            	$ip_address = FunctionUtil::ip_address();
            	if(empty($ip_address)){
            		$ip_address = $this->getAttribute($this->attrCreateIp);
            	}
                $this->setAttribute($this->attrCreateIp,$ip_address);
            }
		}
		if (isset ( $metaData->columns [$this->attrModifyTime] ) && !empty($this->attrModifyTime)) {
			$this->setAttribute($this->attrModifyTime,time());
		}
		return parent::beforeSave ();
	}
}

<?php
/**
 * @file ItzActiveRecord.php
 * @author (kuangjun@xxx.com)
 * @date 2013/10/25
 *  
 **/
class ItzActiveRecord extends CActiveRecord {

	/**
	 * 缓存数据库连接
	 */
	protected static $dbConnections = array ();
	public $attrCreateTime='add_time';
	public $attrModifyTime='mod_time';

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
	public function beforeSave() {
		$metaData = $this->getMetaData ();
		if ($this->getIsNewRecord ()) {
			if (isset ( $metaData->columns [$this->attrCreateTime] ) && !empty($this->attrCreateTime)) {
				$this->setAttribute($this->attrCreateTime,date('Y-m-d H:i:s'));
			}
		}
		if (isset ( $metaData->columns [$this->attrModifyTime] ) && !empty($this->attrModifyTime)) {
			$this->setAttribute($this->attrModifyTime,date('Y-m-d H:i:s'));
		}
		return parent::beforeSave ();
	}
    
}

<?php

/**
 * This is the model class for table "contract_task".
 *
 * The followings are the available columns in table 'contract_task':
 * @property string $task_id
 * @property integer $type
 * @property integer $borrow_id
 * @property integer $tender_id
 * @property integer $user_id
 * @property integer $status
 * @property string  $version
 * @property string  $download
 * @property integer $investtime
 * @property integer $addtime
 * @property integer $handletime
 * @property string $addip
 */
class XfUserContract extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ContractTask the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_user_contract';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, status, platform_no, type, status, addtime', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id,user_id,status,platform_no,type,fdd_download,oss_download,data_json,handletime,addtime,addip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}


}
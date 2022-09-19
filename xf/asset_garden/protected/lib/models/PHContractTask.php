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
 * @property string $version
 * @property string $download
 * @property integer $investtime
 * @property integer $addtime
 * @property integer $handletime
 */
class PHContractTask extends CActiveRecord
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
		return Yii::app()->phdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'firstp2p_contract_task';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, borrow_id, tender_id, user_id, status, investtime, addtime, handletime', 'numerical', 'integerOnly'=>true),
            array('version', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('platform_no,task_id, type, borrow_id, tender_id, user_id, status, version, download, investtime, addtime, handletime', 'safe', 'on'=>'search'),
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

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'task_id' => 'Task',
			'type' => 'Type',
			'borrow_id' => 'Borrow',
			'tender_id' => 'Tender',
			'user_id' => 'User',
			'status' => 'Status',
			'version' => 'Version',
			'download' => 'Download',
			'investtime' => 'Investtime',
			'addtime' => 'Addtime',
			'handletime' => 'Handletime',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('task_id',$this->task_id,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('tender_id',$this->tender_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('version',$this->version,true);
		$criteria->compare('download',$this->download,true);
		$criteria->compare('investtime',$this->investtime);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('handletime',$this->handletime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
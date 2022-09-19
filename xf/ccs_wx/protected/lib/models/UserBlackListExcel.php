<?php

/**
 * This is the model class for table "itzblacklistexcel".
 *
 * The followings are the available columns in table 'itzblacklistexcel':
 * @property integer $id
 * @property integer $batch_id
 * @property string $file_path
 * @property integer $status
 * @property string $remark
 * @property integer $op_user_id
 * @property string $result
 * @property integer $addtime
 * @property string $addip
 */
class UserBlackListExcel extends EMongoDocument
{
    //public $dbname = 'dwdb';
    
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserBlackListExcel the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function collectionName()
	{
		return 'itzblacklistexcel';
	}
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('batch_id, file_path', 'required'),
			array('status, op_user_id, addtime', 'numerical', 'integerOnly'=>true),
			array('file_path, remark', 'length', 'max'=>255),
			array('result', 'length', 'max'=>4096),
			array('batch_id, addip', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, batch_id, file_path, status, remark, op_user_id, result, addtime, addip', 'safe', 'on'=>'search'),
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
			'id' => 'ID',
			'batch_id' => 'Batch',
			'file_path' => 'File Path',
			'status' => 'Status',
			'remark' => 'Remark',
			'op_user_id' => 'Op User',
			'result' => 'Result',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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

		$criteria=new EMongoCriteria;
		
		return new EMongoDataProvider($this, array(
			'criteria' =>$criteria,
			'slaveOkay' => true,
		));
	}
}
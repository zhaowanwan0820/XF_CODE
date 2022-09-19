<?php

/**
 * This is the model class for table "dw_attestation_type".
 *
 * The followings are the available columns in table 'dw_attestation_type':
 * @property string $type_id
 * @property string $name
 * @property string $order
 * @property integer $status
 * @property integer $use
 * @property integer $jifen
 * @property string $summary
 * @property string $remark
 * @property string $addtime
 * @property string $addip
 */
class AttestationType extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AttestationType the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_attestation_type';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('use', 'required'),
			array('status, use, jifen', 'numerical', 'integerOnly'=>true),
			array('name, addtime, addip', 'length', 'max'=>50),
			array('order', 'length', 'max'=>10),
			array('summary, remark', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('type_id, name, order, status, use, jifen, summary, remark, addtime, addip', 'safe', 'on'=>'search'),
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
			'type_id' => 'Type',
			'name' => 'Name',
			'order' => 'Order',
			'status' => 'Status',
			'use' => 'Use',
			'jifen' => 'Jifen',
			'summary' => 'Summary',
			'remark' => 'Remark',
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

		$criteria=new CDbCriteria;

		$criteria->compare('type_id',$this->type_id,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('order',$this->order,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('use',$this->use);
		$criteria->compare('jifen',$this->jifen);
		$criteria->compare('summary',$this->summary,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
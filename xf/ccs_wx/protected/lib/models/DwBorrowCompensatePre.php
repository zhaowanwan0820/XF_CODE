<?php

/**
 * This is the model class for table "dw_borrow_compensate_pre".
 *
 * The followings are the available columns in table 'dw_borrow_compensate_pre':
 * @property integer $id
 * @property integer $borrow_id
 * @property integer $status
 * @property integer $op_user_id
 * @property string $remark
 * @property integer $addtime
 * @property string $addip
 */
class DwBorrowCompensatePre extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DwBorrowCompensatePre the static model class
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
		return 'dw_borrow_compensate_pre';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('borrow_id, status, op_user_id, addtime', 'numerical', 'integerOnly'=>true),
			array('remark', 'length', 'max'=>512),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, borrow_id, status, type,op_user_id, remark, addtime, addip', 'safe'),
			array('id, borrow_id, status, type,op_user_id, remark, addtime, addip', 'safe', 'on'=>'search'),
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
			'borrow_id' => 'Borrow',
			'status' => 'Status',
			'op_user_id' => 'Op User',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('status',$this->status);
        $criteria->compare('type',$this->type);
		$criteria->compare('op_user_id',$this->op_user_id);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
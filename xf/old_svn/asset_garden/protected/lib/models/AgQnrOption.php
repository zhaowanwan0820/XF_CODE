<?php

/**
 * This is the model class for table "ag_qnr_option".
 *
 * The followings are the available columns in table 'ag_qnr_option':
 * @property integer $id
 * @property integer $qst_id
 * @property integer $type
 * @property string $serial
 * @property string $option
 * @property string $option_extra
 * @property integer $point
 * @property integer $status
 * @property integer $addtime
 * @property integer $updatetime
 */
class AgQnrOption extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgQnrOption the static model class
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
        return Yii::app()->agdb;
    }
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_qnr_option';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('qst_id, type, point, status, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('serial', 'length', 'max'=>10),
			array('option, option_extra', 'length', 'max'=>100),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, qst_id, type, serial, option, option_extra, point, status, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'qst_id' => 'Qst',
			'type' => 'Type',
			'serial' => 'Serial',
			'option' => 'Option',
			'option_extra' => 'Option Extra',
			'point' => 'Point',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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
		$criteria->compare('qst_id',$this->qst_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('serial',$this->serial,true);
		$criteria->compare('option',$this->option,true);
		$criteria->compare('option_extra',$this->option_extra,true);
		$criteria->compare('point',$this->point);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
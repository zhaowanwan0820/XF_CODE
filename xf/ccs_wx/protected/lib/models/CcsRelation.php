<?php

/**
 * This is the model class for table "ccs_relation".
 *
 * The followings are the available columns in table 'ccs_relation':
 * @property integer $id
 * @property integer $admin_id
 * @property string $admin_name
 * @property integer $ag_id
 * @property integer $ag_num
 * @property string $ag_name
 * @property string $ag_password
 * @property integer $ag_role
 * @property integer $ag_user_role
 * @property integer $ag_belong_queues
 * @property string $ag_phone
 * @property integer $addtime
 * @property integer $updatetime
 */
class CcsRelation extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CcsRelation the static model class
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
		return Yii::app()->ccsdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ccs_relation';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('admin_name', 'required'),
			array('admin_id, ag_id, ag_num, ag_role, ag_user_role, ag_belong_queues, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('admin_name, ag_name, ag_password', 'length', 'max'=>50),
			array('ag_phone', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, admin_id, admin_name, ag_id, ag_num, ag_name, ag_password, ag_role, ag_user_role, ag_belong_queues, ag_phone, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'admin_id' => 'Admin',
			'admin_name' => 'Admin Name',
			'ag_id' => 'Ag',
			'ag_num' => 'Ag Num',
			'ag_name' => 'Ag Name',
			'ag_password' => 'Ag Password',
			'ag_role' => 'Ag Role',
			'ag_user_role' => 'Ag User Role',
			'ag_belong_queues' => 'Ag Belong Queues',
			'ag_phone' => 'Ag Phone',
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
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('admin_name',$this->admin_name,true);
		$criteria->compare('ag_id',$this->ag_id);
		$criteria->compare('ag_num',$this->ag_num);
		$criteria->compare('ag_name',$this->ag_name,true);
		$criteria->compare('ag_password',$this->ag_password,true);
		$criteria->compare('ag_role',$this->ag_role);
		$criteria->compare('ag_user_role',$this->ag_user_role);
		$criteria->compare('ag_belong_queues',$this->ag_belong_queues);
		$criteria->compare('ag_phone',$this->ag_phone,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
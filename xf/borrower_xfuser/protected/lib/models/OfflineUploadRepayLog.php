<?php

/**
 * This is the model class for table "offline_upload_repay_log".
 *
 * The followings are the available columns in table 'offline_upload_repay_log':
 * @property string $id
 * @property integer $platform_id
 * @property string $file_id
 * @property string $repay_log_id
 * @property string $order_sn
 * @property string $object_sn
 * @property string $old_user_id
 * @property string $mobile_phone
 * @property string $idno
 * @property string $capital
 * @property string $interest
 * @property string $total_money
 * @property integer $old_repay_num
 * @property integer $repay_status
 * @property integer $time
 * @property integer $real_time
 * @property integer $status
 * @property integer $deal_status
 * @property integer $create_time
 * @property string $update_time
 * @property string $remark
 */
class OfflineUploadRepayLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineUploadRepayLog the static model class
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
		return Yii::app()->offlinedb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'offline_upload_repay_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('capital, interest, total_money', 'required'),
			array('platform_id, old_repay_num, repay_status, time, real_time, status, deal_status, create_time', 'numerical', 'integerOnly'=>true),
			array('file_id, old_user_id, update_time', 'length', 'max'=>11),
			array('repay_log_id, order_sn, object_sn, idno', 'length', 'max'=>50),
			array('mobile_phone, capital, interest, total_money', 'length', 'max'=>20),
			array('remark', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, platform_id, file_id, repay_log_id, order_sn, object_sn, old_user_id, mobile_phone, idno, capital, interest, total_money, old_repay_num, repay_status, time, real_time, status, deal_status, create_time, update_time, remark', 'safe', 'on'=>'search'),
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
			'platform_id' => 'Platform',
			'file_id' => 'File',
			'repay_log_id' => 'Repay Log',
			'order_sn' => 'Order Sn',
			'object_sn' => 'Object Sn',
			'old_user_id' => 'Old User',
			'mobile_phone' => 'Mobile Phone',
			'idno' => 'Idno',
			'capital' => 'Capital',
			'interest' => 'Interest',
			'total_money' => 'Total Money',
			'old_repay_num' => 'Old Repay Num',
			'repay_status' => 'Repay Status',
			'time' => 'Time',
			'real_time' => 'Real Time',
			'status' => 'Status',
			'deal_status' => 'Deal Status',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'remark' => 'Remark',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('file_id',$this->file_id,true);
		$criteria->compare('repay_log_id',$this->repay_log_id,true);
		$criteria->compare('order_sn',$this->order_sn,true);
		$criteria->compare('object_sn',$this->object_sn,true);
		$criteria->compare('old_user_id',$this->old_user_id,true);
		$criteria->compare('mobile_phone',$this->mobile_phone,true);
		$criteria->compare('idno',$this->idno,true);
		$criteria->compare('capital',$this->capital,true);
		$criteria->compare('interest',$this->interest,true);
		$criteria->compare('total_money',$this->total_money,true);
		$criteria->compare('old_repay_num',$this->old_repay_num);
		$criteria->compare('repay_status',$this->repay_status);
		$criteria->compare('time',$this->time);
		$criteria->compare('real_time',$this->real_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('deal_status',$this->deal_status);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('remark',$this->remark,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
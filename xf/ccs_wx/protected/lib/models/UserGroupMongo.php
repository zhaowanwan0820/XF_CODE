<?php

/**
 * This is the model class for table "itz_user_group".
 *
 * The followings are the available columns in table 'itz_user_group':
 * @property string $id
 * @property string $name
 * @property string $screening_conditions
 * @property integer $adduser
 * @property integer $addtime
 * @property string $addip
 * @property integer $result
 * @property integer $status
 * @property string $excel_dowload_url
 * @property integer $trial_count
 * @property integer $trial_time
 * @property integer $update_time
 * @property integer $handle_time
 * @property integer $pid
 */
class UserGroupMongo extends EMongoDocument
{
	public function behaviors()
	{
	    return array(
	        'EMongoTimestampBehaviour'
	    );
	}
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserGroupMongo the static model class
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
		return Yii::app()->businessLog;
	}
	/**
	 * @return string the associated database collection name
	 */
    public function collectionName()
    {
        return 'UserGroup';
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('adduser, addtime, result, status, trial_count, trial_time, update_time, handle_time, pid', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>63),
			array('id', 'length', 'max'=>15),
			array('password', 'length', 'max'=>15),
			array('addip', 'length', 'max'=>15),
			array('excel_dowload_url', 'length', 'max'=>500),
			array('screening_conditions', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, screening_conditions, adduser, addtime, addip, result, status, excel_dowload_url, trial_count, trial_time, update_time, handle_time, pid, password', 'safe', 'on'=>'search'),
		);
	}
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'screening_conditions' => 'Screening Conditions',
			'adduser' => 'Adduser',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'result' => 'Result',
			'status' => 'Status',
			'excel_dowload_url' => 'Excel Dowload Url',
			'trial_count' => 'Trial Count',
			'trial_time' => 'Trial Time',
			'update_time' => 'Update Time',
			'handle_time' => 'Handle Time',
			'pid' => 'Pid',
			'password' => 'Password',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search($query = [], $project = [], $partialMatch = false, $sort = [])
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new EMongoCriteria;

        $criteria->compare('_id',$this->_id,true);
		$criteria->compare('id',$this->id,true);
		$criteria->compare('name',(String) $this->name,true);
		$criteria->compare('screening_conditions',$this->screening_conditions,true);
		$criteria->compare('adduser',(String) $this->adduser 
			);
		$criteria->compare('addtime',(String) $this->addtime);
		$criteria->compare('addip',(String) $this->addip,true);
		$criteria->compare('result',(String) $this->result);
		$criteria->compare('status',(String) $this->status);
		$criteria->compare('excel_dowload_url',(String) $this->excel_dowload_url,true);
		$criteria->compare('trial_count',(String) $this->trial_count);
		$criteria->compare('trial_time',(String) $this->trial_time);
		$criteria->compare('update_time',(String) $this->update_time);
		$criteria->compare('handle_time',(String) $this->handle_time);
		$criteria->compare('pid',(String) $this->pid);
		$criteria->compare('password',(String) $this->password);

		return new EMongoDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}
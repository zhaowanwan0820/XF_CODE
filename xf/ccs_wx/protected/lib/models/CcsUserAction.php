<?php

/**
 * This is the model class for table "ccs_user_action".
 *
 * The followings are the available columns in table 'ccs_user_action':
 * @property string $id
 * @property integer $user_id
 * @property string $user_name
 * @property string $user_phone
 * @property integer $user_sex
 * @property integer $user_type
 * @property string $trade_no
 * @property integer $action_type
 * @property integer $action_detail
 * @property string $action_detail_describe
 * @property integer $action_system
 * @property string $action_money
 * @property string $action_payment
 * @property integer $action_time
 * @property integer $action_status
 * @property string $result
 * @property integer $call_status
 * @property integer $admin_id
 * @property integer $addtime
 * @property integer $updatetime
 */
class CcsUserAction extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return CcsUserAction the static model class
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
        return 'ccs_user_action';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, user_sex, user_type, action_type, action_detail, action_system, action_time, action_status, call_status, admin_id, addtime, updatetime', 'numerical', 'integerOnly'=>true),
            array('user_name', 'length', 'max'=>50),
            array('user_phone', 'length', 'max'=>15),
            array('trade_no', 'length', 'max'=>100),
            array('action_detail_describe, result', 'length', 'max'=>200),
            array('action_money, action_payment', 'length', 'max'=>20),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, user_id, user_name, user_phone, user_sex, user_type, trade_no, action_type, action_detail, action_detail_describe, action_system, action_money, action_payment, action_time, action_status, result, call_status, admin_id, addtime, updatetime', 'safe', 'on'=>'search'),
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
            'user_id' => 'User',
            'user_name' => 'User Name',
            'user_phone' => 'User Phone',
            'user_sex' => 'User Sex',
            'user_type' => 'User Type',
            'trade_no' => 'Trade No',
            'action_type' => 'Action Type',
            'action_detail' => 'Action Detail',
            'action_detail_describe' => 'Action Detail Describe',
            'action_system' => 'Action System',
            'action_money' => 'Action Money',
            'action_payment' => 'Action Payment',
            'action_time' => 'Action Time',
            'action_status' => 'Action Status',
            'result' => 'Result',
            'call_status' => 'Call Status',
            'admin_id' => 'Admin',
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

        $criteria->compare('id',$this->id,true);
        $criteria->compare('user_id',$this->user_id);
        $criteria->compare('user_name',$this->user_name,true);
        $criteria->compare('user_phone',$this->user_phone,true);
        $criteria->compare('user_sex',$this->user_sex);
        $criteria->compare('user_type',$this->user_type);
        $criteria->compare('trade_no',$this->trade_no,true);
        $criteria->compare('action_type',$this->action_type);
        $criteria->compare('action_detail',$this->action_detail);
        $criteria->compare('action_detail_describe',$this->action_detail_describe,true);
        $criteria->compare('action_system',$this->action_system);
        $criteria->compare('action_money',$this->action_money,true);
        $criteria->compare('action_payment',$this->action_payment,true);
        $criteria->compare('action_time',$this->action_time);
        $criteria->compare('action_status',$this->action_status);
        $criteria->compare('result',$this->result,true);
        $criteria->compare('call_status',$this->call_status);
        $criteria->compare('admin_id',$this->admin_id);
        $criteria->compare('addtime',$this->addtime);
        $criteria->compare('updatetime',$this->updatetime);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}
<?php

/**
 * This is the model class for table "xf_exclusive_purchase".
 *
 * The followings are the available columns in table 'xf_exclusive_purchase':
 * @property integer $id
 * @property integer $user_id
 * @property string $real_name
 * @property string $mobile_phone
 * @property string $idno
 * @property string $bank_name
 * @property string $bank_card
 * @property string $wait_capital
 * @property string $discount
 * @property string $purchase_amount
 * @property integer $status
 * @property string $order_no
 * @property string $credentials_url
 * @property string $yibao_response
 * @property integer $purchase_user_id
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $add_user_id
 * @property string $add_user_name
 * @property string $add_ip
 * @property integer $add_time
 */
class XfExclusivePurchase extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return XfExclusivePurchase the static model class
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
        return 'xf_exclusive_purchase';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('start_time, end_time', 'required'),
            array('user_id, status, purchase_user_id, start_time, end_time, add_user_id, add_time', 'numerical', 'integerOnly'=>true),
            array('bankcode, real_name, mobile_phone, idno, bank_name, bank_card, order_no, credentials_url, add_user_name, add_ip', 'length', 'max'=>255),
            array('wait_capital, purchase_amount', 'length', 'max'=>11),
            array('discount', 'length', 'max'=>4),
            array('yibao_response', 'length', 'max'=>1000),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('recharge_withdrawal_difference, bankcode, id, user_id, real_name, mobile_phone, idno, bank_name, bank_card, wait_capital, discount, purchase_amount, status, order_no, credentials_url, yibao_response, purchase_user_id, start_time, end_time, add_user_id, add_user_name, add_ip, add_time', 'safe', 'on'=>'search'),
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
            'real_name' => 'Real Name',
            'mobile_phone' => 'Mobile Phone',
            'idno' => 'Idno',
            'bank_name' => 'Bank Name',
            'bankcode' => 'Bank Code',
            'bank_card' => 'Bank Card',
            'wait_capital' => 'Wait Capital',
            'discount' => 'Discount',
            'purchase_amount' => 'Purchase Amount',
            'status' => 'Status',
            'order_no' => 'Order No',
            'credentials_url' => 'Credentials Url',
            'yibao_response' => 'Yibao Response',
            'purchase_user_id' => 'Purchase User',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'add_user_id' => 'Add User',
            'add_user_name' => 'Add User Name',
            'add_ip' => 'Add Ip',
            'add_time' => 'Add Time',
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

        $criteria->compare('id', $this->id);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('real_name', $this->real_name, true);
        $criteria->compare('mobile_phone', $this->mobile_phone, true);
        $criteria->compare('idno', $this->idno, true);
        $criteria->compare('bank_name', $this->bank_name, true);
        $criteria->compare('bank_card', $this->bank_card, true);
        $criteria->compare('wait_capital', $this->wait_capital, true);
        $criteria->compare('discount', $this->discount, true);
        $criteria->compare('purchase_amount', $this->purchase_amount, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('order_no', $this->order_no, true);
        $criteria->compare('credentials_url', $this->credentials_url, true);
        $criteria->compare('yibao_response', $this->yibao_response, true);
        $criteria->compare('purchase_user_id', $this->purchase_user_id);
        $criteria->compare('start_time', $this->start_time);
        $criteria->compare('end_time', $this->end_time);
        $criteria->compare('add_user_id', $this->add_user_id);
        $criteria->compare('add_user_name', $this->add_user_name, true);
        $criteria->compare('add_ip', $this->add_ip, true);
        $criteria->compare('add_time', $this->add_time);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}

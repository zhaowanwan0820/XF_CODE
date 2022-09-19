<?php

/**
 * This is the model class for table "firstp2p_debt_exchange_log".
 *
 * The followings are the available columns in table 'firstp2p_debt_exchange_log':
 * @property string $id
 * @property integer $user_id
 * @property integer $tender_id
 * @property string $order_id
 * @property string $debt_account
 * @property integer $addtime
 * @property integer $status
 * @property integer $successtime
 * @property integer $borrow_id
 * @property integer $return_status
 */
class DebtExchangeLog extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return DebtExchangeLog the static model class
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
        return Yii::app()->db;
    }
    
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'firstp2p_debt_exchange_log';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, tender_id, addtime, status, successtime, borrow_id, return_status', 'numerical', 'integerOnly'=>true),
            array('order_id', 'length', 'max'=>100),
            array('debt_account', 'length', 'max'=>11),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('order_sn,order_info,return_time,debt_src,buyer_uid,id, user_id, tender_id, order_id, debt_account, addtime, status, successtime, borrow_id, return_status', 'safe', 'on'=>'search'),
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
            'tender_id' => 'Tender',
            'order_id' => 'Order',
            'debt_account' => 'Debt Account',
            'addtime' => 'Addtime',
            'status' => 'Status',
            'successtime' => 'Successtime',
            'borrow_id' => 'Borrow',
            'return_status' => 'Return Status',
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

        $criteria->compare('id', $this->id, true);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('tender_id', $this->tender_id);
        $criteria->compare('order_id', $this->order_id, true);
        $criteria->compare('debt_account', $this->debt_account, true);
        $criteria->compare('addtime', $this->addtime);
        $criteria->compare('status', $this->status);
        $criteria->compare('successtime', $this->successtime);
        $criteria->compare('borrow_id', $this->borrow_id);
        $criteria->compare('return_status', $this->return_status);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}

<?php
class UserSmsLog extends EMongoDocument
{
    /** @virtual */
    public function behaviors()
    {
        return array('EMongoTimestampBehaviour');
    }
    public function rules()
    {
        return array(array('user_id, mobile, content', 'required'), array('status', 'numerical', 'integerOnly' => true), array('stype', 'length', 'max' => 50), array('mobile', 'length', 'max' => 12), array('content', 'length', 'max' => 200), array('createtime', 'length', 'max' => 50), array('_id, user_id, mobile, content, stype, status, createtime, gateway, relation_id', 'safe', 'on' => 'search'));
    }
    public function collectionName()
    {
        return 'itzsms';
    }
    /**
     * 短信类别
     */
    public function getType()
    {
        $result = Yii::app()->dwdb->createCommand()->select('name, code')->from('itz_trigger_point')->queryAll();
        $temp = [];
        foreach($result as $key => $value) {
            $temp[$value['code']] = $value['name'];
        }
        return $temp;
    }
    public function StrType()
    {
        $usersrcs = $this->getType();
        if (isset($usersrcs[$this->stype])) {
            return $usersrcs[$this->stype];
        } else {
            $usersrcs = Yii::app()->params['type'];
        }
        return $usersrcs[$this->stype] ? $usersrcs[$this->stype] : $this->stype;

    }
    public function getStrType($key)
    {
        $res = $this->getType();
        return array_key_exists($key, $res ? $res[$key] : '');
    }
    /**
     * 审核状态
     */
    protected $_guarantorStatus = array(2 => 'fail', 1 => 'success');
    public function getGuarantorStatus()
    {
        return $this->_guarantorStatus;
    }
    public function StrGuarantorStatus()
    {
        return $this->_guarantorStatus[$this->status];
    }
    public function getStrGuarantorStatus($key)
    {
        return array_key_exists($key, $this->_guarantorStatus) ? $this->_guarantorStatus[$key] : '';
    }
    public function relation() {
        if($this->viceGateway == $this->gateway){
            return '备';
        } else {
            return '主';
        }
    }
    public function getRelations() {
        return [1 => '主', 2 => '备'];
    }
    public function attributeLabels()
    {
        return array('_id' => 'ID', 'stype' => '类型', 'user_id' => '用户ID', 'mobile' => '手机号', 'sync' => '是否等待发送结果', 'content' => '短信内容', 'callback' => '回调地址', 'gateway' => '短信通道', 'relation_id' => '主备', 'status' => '发送结果', 'createtime' => '发送时间', 'lasttime' => '最后更新时间', 'hash_verify' => '短信完整性校验');
    }
    /**
     * Returns the static model of the specified AR class.
     * @return User the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.
        $criteria = new EMongoCriteria();
        if ($this->status != NULL && $this->status != '' || is_int($this->status)) {
            $criteria->compare('status', $this->status);
        }
        if (!empty($this->mobile)) {
            $criteria->compare('mobile', (string) $this->mobile);
        }
        if (!empty($this->stype)) {
            $criteria->compare('stype', (string) $this->stype);
        }
        if (!empty($this->gateway)) {
            $criteria->compare('gateway', (string) $this->gateway);
            isset(Yii::app()->c->gatewayList[$this->gateway]) && $this->gateway = Yii::app()->c->gatewayList[$this->gateway];
        }
        if (!empty($this->relation_id)) {
            $this->relation_id == 1 && $criteria->compare('relation_id', null);
            $this->relation_id == 2 && $criteria->addCondition('relation_id', [
                '$ne' => null
            ]);
        }

        //'lvfujun@itouzi.com'
        if (!empty($this->user_id)) {
            $criteria->compare('user_id', (string) $this->user_id);
        }
        if (!empty($this->content)) {
            $criteria->compare('content', $this->content, true);
        }
        if (!empty($this->createtime)) {
            $criteria->compare('createtime', array(strtotime($this->createtime), strtotime($this->createtime) + 86400));
        }else{
        	$monthAgo = time()-15*86400;//默认查15天
        	$criteria->compare('createtime', array($monthAgo, time()+8*3600));
        }
        $criteria->setSort(['createtime' => 'desc']);
        return new EMongoDataProvider($this, array('criteria' => $criteria));
    }
}
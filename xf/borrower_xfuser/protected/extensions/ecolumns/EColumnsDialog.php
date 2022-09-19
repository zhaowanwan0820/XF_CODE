<?php
  
Yii::import('zii.widgets.jui.CJuiDialog');  

class EColumnsDialog extends CJuiDialog
{
    /**
    * internal EColumns object
    * 
    * @var mixed
    */
    private $_ecolumns;
    
    /**
    * array of params for EColumns
    * 
    * @var mixed
    */
    public $ecolumns = array(); 
    
    public function init()
    {
        //gridId is required
        if(empty($this->ecolumns['gridId'])) throw new CException('You must provide gridId');
        if($this->getId(false) === null) {
            $this->setId($this->ecolumns['gridId'].'-ecolumns-dlg');
        }
        
        //prepare EColumns params
        if(!isset($this->ecolumns['buttonCancel'])) {
           $this->ecolumns['buttonCancel'] = CHtml::button('关闭', array('type' => 'button', 'onclick' => '$("#'.$this->getId().'").dialog("close"); return false;', 'style' => 'float: right'));
        }
        if(!isset($this->ecolumns['buttonApply'])) {
           $this->ecolumns['buttonApply'] = CHtml::button('设置', array('type' => 'submit', 'onclick' => '$("#'.$this->getId().'").dialog("close")', 'style' => 'float: left'));
        }        
        
        //create EColumns object
        $this->_ecolumns = $this->owner->createWidget('ecolumns.EColumns', $this->ecolumns);
             
        parent::init();
       
        $this->_ecolumns->run(); 
        
        //handler for click on link
        yii::app()->clientScript->registerScript($this->getLinkId(), "
           jQuery('#{$this->getLinkId()}').live('click', function() { jQuery('#{$this->getId()}').dialog('open'); return false; })
        ", CClientScript::POS_READY);       
    }    
    
    public function columns()
    {
        return $this->_ecolumns->columns();
    }
    
    public function link($text = '自定义栏目显示')
    {
        return CHtml::link($text, '#', array(
             'class' => 'ecolumns-link',
             'id'    => $this->getLinkId(),
        ));
    }  
    
    public function getLinkId()
    {
       return $this->getId().'-link';
    }  
}

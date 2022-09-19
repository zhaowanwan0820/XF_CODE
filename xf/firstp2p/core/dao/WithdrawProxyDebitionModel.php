<?php
namespace core\dao;

class WithdrawProxyDebitionModel extends BaseModel
{

    /**
     * 根据出让人id查询债权信息
     * @param integer $transferorUserId 出让人用户id
     * @return array 债权信息
     */
    public static function findDebitionByTransferorId($transferorUserId)
    {
        $record = self::instance()->findBy(" transferor_user_id = {$transferorUserId}");
        if (!$record)
        {
            return array();
        }
        return $record->getRow();
    }

    /**
     * 更新债权信息
     * @param integer $transferorUserId 出让方用户id
     * @param integer $debitionAmount 还款金额
     * @return boolean
     */
    public static function updateDebitionAmountByTransferorId($transferorUserId, $debitionAmount, $projectId = 0)
    {
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        try {
            $db->startTrans();
            $debitionRecord = self::findDebitionByTransferorId($transferorUserId);
            if ($debitionRecord['amount'] == $debitionAmount)
            {
                $sqlDebition = "DELETE FROM firstp2p_withdraw_proxy_debition WHERE transferor_user_id = {$transferorUserId}";
            } else {
                $sqlDebition = "UPDATE firstp2p_withdraw_proxy_debition SET amount = amount - {$debitionAmount} WHERE transferor_user_id = {$transferorUserId} AND amount >= {$debitionAmount}";
            }
            $db->query($sqlDebition);
            $affRows = $db->affected_rows();
            if ($affRows < 1)
            {
                throw new \Exception('债权信息更新失败');
            }
            // 写债权更新日志
            $logMdl = new WithdrawProxyDebitionLogModel();
            $debitionRecord['remain_amount'] = $debitionRecord['amount'] - $debitionAmount;
            $debitionRecord['amount'] = $debitionAmount;
            $debitionRecord['type'] = WithdrawProxyDebitionLogModel::TYPE_REPAY;
            $debitionRecord['project_id'] = $projectId;
            $debitionRecord['create_time'] = time();
            $debitionRecord['create_admin_name'] = 'system';
            unset($debitionRecord['id']);
            $logMdl->setRow($debitionRecord);
            if (!$logMdl->save())
            {
                throw new \Exception('债权信息操作记录写入失败');
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }

    }
}

<?php
namespace core\service;

use core\dao\WithdrawProxyDebitionModel;
use core\dao\WithdrawProxyDebitionLogModel;
use NCFGroup\Common\Library\StandardApi as Api;
use NCFGroup\Common\Library\Idworker;

class WithdrawProxyDebitionService extends BaseService
{

    /**
     * 创建debition记录
     */
    public static function createDebition($debition)
    {
        self::checkCanCreate($debition);

        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $db->startTrans();
        try {
            $debitionMdl = new WithdrawProxyDebitionModel();
            if (empty($debition['create_time']))
            {
                $debition['create_time'] = time();
            }
            $debitionMdl->setRow($debition);

            $debitionLogMdl = new WithdrawProxyDebitionLogModel();
            $debitionLog = $debition;
            $debitionLog['create_admin_name'] = $debition['create_admin_name'];
            $debitionLog['type'] = WithdrawProxyDebitionLogModel::TYPE_CREATE;
            $debitionLog['remain_amount'] = $debition['amount'];
            $debitionLog['amount'] = 0;
            $debitionLogMdl->setRow($debitionLog);
            if (!$debitionLogMdl->save())
            {
                throw new \Exception('保存债权信息日志失败');
            }

            if (!$debitionMdl->save())
            {
                throw new \Exception('保存债权信息失败');
            }
            $db->commit();
            return $debitionMdl->id;
        } catch (\Exception $e) {
            $db->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 删除债权信息
     * @param integer $id 债权主键
     * @param string $memo 置为无效是的备注信息
     * @return boolean
     */
    public static function deleteDebition($id, $memo = '', $adminName = '')
    {
        $debition = WithdrawProxyDebitionModel::instance()->find($id);
        // 如果记录不存在
        if (!$debition)
        {
            return true;
        }
        $debition->db->startTrans();
        try {
            $debitionLog = $debition->getRow();
            $debition->remove();
            $debitionLog['create_time'] = time();
            $debitionLog['type'] = WithdrawProxyDebitionLogModel::TYPE_DISABLED;
            $debitionLog['create_admin_name'] = $adminName;
            $debitionLog['remain_amount'] = $debitionLog['amount'];
            $debitionLog['memo'] = $memo;
            $debitionLog['amount'] = 0;
            unset($debitionLog['id']);
            $debitionLogMdl = new WithdrawProxyDebitionLogModel();
            $debitionLogMdl->setRow($debitionLog);
            if (!$debitionLogMdl->save())
            {
                throw new \Exception('保存债权信息日志失败');
            }
            $debition->db->commit();
            return true;
        } catch (\Exception $e) {
            $debition->db->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 通过出让人删除债权信息
     * @param integer $transferorUserId 出让人用户id
     * @return boolean
     */
    public static function deleteDebitionByTransferorId($transferorUserId)
    {
        $deleteSQL = "DELETE FROM firstp2p_withdraw_proxy_debition WHERE transferor_user_id = '{$transferorUserId}'";
        return \libs\db\Db::getInstance('firstp2p', 'master')->query($deleteSQL);
    }


    /**
     * 计算代发时,需要代发债权关系金额
     * @param array $transferorInfo
     *      integer $transferor_user_id 出让人用户id
     *      integer $amount 出让金额
     * @return boolean
     */
    public static function caculateDebitionAmount($transferorInfo = [])
    {
        $debition = WithdrawProxyDebitionModel::instance()->findBy('transferor_user_id = '.intval($transferorInfo['transferor_user_id']));
        if (!$debition)
        {
            // 如果不需要还债权, 则认为所有金额都可以进行代发
            return false;
        }
        // 如果存在债权信息则进行实际还款额和债权信息匹配
        if ($transferorInfo['amount'] >= $debition['amount'])
        {
            return $debition['amount'];
        }
        return $transferorInfo['amount'];
    }

    /**
     * 更新债权信息
     * @param array $withdrawRecord
     * @return boolean
     */
    public static function updateDebition($withdrawRecord)
    {
        $transferorUserId = $withdrawRecord['user_id'];
        if (!is_numeric($transferorUserId))
        {
            throw new \Exception('出让方用户id不正确');
        }
        $debitionRecord = WithdrawProxyDebitionModel::findDebitionByTransferorId($transferorUserId);
        if(empty($debitionRecord))
        {
            throw new \Exception('债权信息不存在');
        }
        $debitionAmount = $withdrawRecord['amount'];
        if (!WithdrawProxyDebitionModel::updateDebitionAmountByTransferorId($transferorUserId, $debitionAmount, $withdrawRecord['project_id']))
        {
            throw new \Exception('更新债权信息失败');
        }
        return true;
    }


    /**
     * 检查是否可以创建debition记录
     * @param array $debition 提供的debition数据表单
     */
    public static function checkCanCreate($debition)
    {
        return true;
    }

}

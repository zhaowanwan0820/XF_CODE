<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CifStep extends AbstractEnum
{
    const INIT = -9;    // 等待见证（无）
    const SAVE = 0; //开户保存(无)
    const WAIT_VERY = 1; //等待审核(等待审核)
    const WAIT_REVIEW = 2;//等待复核（等待审核）
    const WAIT_QUEST= 3;//等待回访（等待审核）
    const WAIT_ACTIV = 4;//等待激活（等待审核）
    const SYS = 5;//系统处理(申请已提交: 查询三方存管结果)
    const SUCC = 6;//流程结束（1.查询三方存管结果;2.若股东账户不为空，则开户成功；）
    const QUEST_VERY = 7;//回访审核（等待审核）
    const REGI_BACK = 11;//开户退回（等待审核）
    const QUEST_BACK = 12;//回访退回（等待审核）
    const QUEST_FAIL = 98;//回访失败（等待审核）
    const STOP = 99;//开户终止（审核不通过）

    public static function getWaitStatus()
    {
        return array(CifStep::INIT, CifStep::SAVE, CifStep::WAIT_VERY, CifStep::WAIT_REVIEW, CifStep::WAIT_QUEST,
        CifStep::WAIT_ACTIV, CifStep::SYS, CifStep::QUEST_VERY);
    }

    public static function getSuccStatus()
    {
        return array(CifStep::SUCC);
    }

    public static function getFailStatus()
    {
        return array(CifStep::QUEST_VERY, CifStep::REGI_BACK, CifStep::QUEST_BACK, CifStep::QUEST_FAIL, CifStep::STOP);
    }

    public static function getStatus($id)
    {
        $id = (int)$id;
        if (in_array($id, self::getSuccStatus())) {
            return 1;
        } elseif (in_array($id, self::getFailStatus())) {
            return 2;
        } elseif (in_array($id, self::getWaitStatus())) {
            return 3;
        } else {
            return 0;
        }
    }
}

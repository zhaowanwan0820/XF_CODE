<?php

/**
 * Class WithdrawProxyAction
 *
 */
use core\dao\WithdrawProxyModel;
use core\dao\UserModel;
use core\dao\WithdrawProxyCheckModel;
use core\dao\WithdrawProxyDebitionModel;
use core\service\WithdrawProxyService;
use core\service\WithdrawProxyDebitionService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\StandardApi as Api;
use NCFGroup\Common\Library\services\UcfpayGateway;


class WithdrawProxyDebitionAction extends CommonAction
{
    public function index()
    {
        $map = $this->_getDebitionMap();
        $this->assign("default_map", $map);
        parent::index();
    }

    /**
     * 添加债权信息页面
     */
    public function addDebition()
    {
        $this->display();
    }

    /**
     * 提交债权信息添加
     */
    public function doAddDebition()
    {
        try {
            $response = [
                'errCode' => 0,
                'errMsg'    => '',
            ];

            $debition = [];
            $adminSession= \es_session::get(md5(conf("AUTH_KEY")));
            $debition['create_admin_name'] = $adminSession['adm_name'];

            // 检查出让方用户是否存在
            $userId = !empty($_REQUEST['transferor_user_id']) ? intval($_REQUEST['transferor_user_id']) : 0;
            if (empty($userId))
            {
                throw new \Exception('出让方不存在');
            }
            $debition['transferor_user_id'] = $userId;

            $transferorUser = UserModel::instance()->find($userId);
            if (!$transferorUser)
            {
                throw new \Exception('出让方不存在');
            }
            $transferorName= $transferorUser->real_name;
            if (empty($transferorName))
            {
                throw new \Exception('出让方不存在');
            }
            $debition['transferor_name'] = $transferorName;

            $transfereeName = !empty($_REQUEST['transferee_name']) ? addslashes(trim($_REQUEST['transferee_name'])) : '';
            if (empty($transfereeName))
            {
                throw new \Exception('受让方账户名称不能为空');
            }
            $debition['transferee_name'] = $transfereeName;

            $transfereeAccount = !empty($_REQUEST['transferee_account']) ? addslashes(trim($_REQUEST['transferee_account'])) : '';
            if (empty($transfereeAccount))
            {
                throw new \Exception('受让方账户不能为空');
            }
            $debition['transferee_account'] = $transfereeAccount;

            $transfereeBankCode= !empty($_REQUEST['transferee_bank_code']) ? addslashes(trim($_REQUEST['transferee_bank_code'])) : '';
            if (empty($transfereeBankCode))
            {
                throw new \Exception('受让方账户银行编码不能为空');
            }
            $debition['transferee_bank_code'] = $transfereeBankCode;

            $amount = !empty($_REQUEST['amount']) ? floatval($_REQUEST['amount']) : 0;
            if ($amount < 0)
            {
                throw new \Exception('出让金额不能为负');
            }
            $debition['amount'] = bcmul($amount, 100, 0);

            $transfereeUserType = !empty($_REQUEST['transferee_user_type']) ? intval($_REQUEST['transferee_user_type']) : 1;
            $debition['transferee_user_type'] = $transfereeUserType == 1 ? 1 : 2;

            $transfereeIssuer = !empty($_REQUEST['transferee_issuer']) ? addslashes(trim($_REQUEST['transferee_issuer'])) : '';
            if ($transfereeUserType == 2 && empty($transfereeIssuer))
            {
                throw new \Exception('受让方银行联行号不能为空');
            }
            $debition['transferee_issuer'] = $transfereeIssuer;
            $recordId = WithdrawProxyDebitionService::createDebition($debition);
            if (!is_numeric($recordId))
            {
                throw new \Exception('创建债权信息失败');
            }
            $response['recordId'] = $recordId;
        } catch (\Exception $e) {
            $response['errCode'] = 1;
            $response['errMsg'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    /**
     * 设置债权信息无效
     */
    public function disableDebition()
    {
        $response = [
            'errCode' => 0,
            'errMsg' => '操作成功',
        ];
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $memo = isset($_REQUEST['memo']) ? trim($_REQUEST['memo']) : '';
        try {
            if (empty($id))
            {
                throw new \Exception('债权信息不能为空');
            }
            $adminSession= \es_session::get(md5(conf("AUTH_KEY")));
            if (!WithdrawProxyDebitionService::deleteDebition($id, $memo, $adminSession['adm_name']))
            {
                throw new \Exception('置为债权信息失败');
            }

        } catch (\Exception $e) {
            $response['errCode'] = 1;
            $response['errMsg'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    /**
     * 读取用户真实姓名
     */
    public function getUserName()
    {
        try {
            $response['errCode'] = 0;
            $response['errMsg'] = '';
            $userId = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if (empty($userId))
            {
                throw new \Exception('用户不存在');
            }
            $userInfo = UserModel::instance()->find($userId);
            if (!$userInfo)
            {
                throw new \Exception('用户不存在');
            }
            $response['name'] = $userInfo->real_name;

        } catch (\Exception $e) {
            $response['errCode'] = 1;
            $response['errMsg'] = $e->getMessage();
        }
        echo json_encode($response);
    }

    private function _getDebitionMap()
    {
        $map = [];
        // 出让方用户id
        if(!empty($_REQUEST['transferor_user_id']))
        {
            $map['transferor_user_id'] = intval($_REQUEST['transferor_user_id']);
        }
        // 受让方账户
        if (!empty($_REQUEST['transferee_account']))
        {
            $map['transferee_account'] = addslashes(trim($_REQUEST['transferee_account']));
        }

        // 创建时间
        $applyTimeStart = $applyTimeEnd = 0;
        if (!empty($_REQUEST['apply_time_start']))
        {
            $applyTimeStart = strtotime($_REQUEST['apply_time_start']);
            $map['create_time'] = array('egt', $apply_time);
        }
        if (!empty($_REQUEST['apply_time_end']))
        {
            $applyTimeEnd = strtotime($_REQUEST['apply_time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $applyTimeStart, $applyTimeEnd));
        }

        return $map;
    }
}

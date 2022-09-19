<?php
/**
 * 黄金活期标相关操作
 */
use core\service\DealTypeGradeService;
use NCFGroup\Protos\Gold\RequestCommon;

class GoldDealCurrentAction extends CommonAction {
    public function edit(){
        $product_mix_1  = '稀贵商品';//产品一级
        $product_mix_2  = '优金宝';//产品二级 
        $dealTypeGradeService = new DealTypeGradeService();
        $product_mix_id_2 = $dealTypeGradeService->findIdByName($product_mix_2);
        if(empty($product_mix_id_2)){
            exit("请正确配置三级名称");
        }
        $product_mix_3_list = $dealTypeGradeService -> getbyParentId($product_mix_id_2);
        $this->assign('product_mix_3_list', $product_mix_3_list);
        $deal_type_tree = $this->getDealLoanTypeList();
        $this->assign("deal_type_tree",$deal_type_tree);
        $request = new RequestCommon();
        $response = $this->getRpc('goldRpc')->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\DealCurrent',
                'method' => 'getInfo',
                'args' => $request,
        ));
        $this->assign("data",$response['data']);
        $this->display('edit');
    }
    public function save(){
        $data = array(
                'productMix1' => '稀贵商品',
                'productMix2' => '优金宝',
                'productMix3' => addslashes(trim($_POST['product_mix_3'])),
                'userId' => intval($_POST['user_id']),
                'name' => '优金宝',
                'interestUserId' => intval($_POST['interest_user_id']),
                'withdrawUserId' => intval($_POST['withdraw_user_id']),
                'withdrawFeeUserId' => intval($_POST['withdraw_fee_user_id']),
                'feeUserId' => intval($_POST['fee_user_id']),
                'loanUserId' => intval($_POST['loan_user_id']),
                'techUserId' => intval($_POST['tech_user_id']),
                'manageUserId' => intval($_POST['manage_user_id']),
                'typeId' => intval($_POST['type_id']),
                'tagName' => addslashes(trim($_POST['tag_name'])),
                'minBuyAmount' => floatval($_POST['min_buy_amount']),
                'maxBuyAmount' => floatval($_POST['max_buy_amount']),
                'withdrawFee' => floatval($_POST['withdraw_fee']),
                'buyerFee' => floatval($_POST['buyer_fee']),
                'receiveFee' => floatval($_POST['receive_fee']),
                'rate' => floatval($_POST['rate']),
                'techFeeRate' => floatval($_POST['tech_fee_rate']),
                'loanFeeRate' => floatval($_POST['loan_fee_rate']),
                'note' => $_POST['note'],
                'createTime' => time(),
        );

        if(!empty($_POST['id'])){
            $data['id'] = intval($_POST['id']);
        }
        $request = new RequestCommon();
        $request->setVars($data);
        $response = $this->getRpc('goldRpc')->callByObject(array(
                'service' => 'NCFGroup\Gold\Services\DealCurrent',
                'method' => 'save',
                'args' => $request,
        ));
        if($response[errCode] != 0) {
            $this->error("操作失败");
        }else{
            $this->success("操作成功");
        }
    }
    
    /**
     * getDealLoanTypeList
     * 获取借款用途列表
     *
     * @access public
     * @return void
     */
    public function getDealLoanTypeList() {
        //借款用途
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL'")->order('sort desc')->findAll();
        $deal_type_tree = D("DealLoanType")->toFormatTree($deal_type_tree,'name');
        return $deal_type_tree;
    }
    
}

<?php
/**
 * 合同变量释义
 * @author wenyanlei
 */
return array(

    //借款合同
    'TPL_LOAN_CONTRACT' => array(

            '{$notice.number}' => '合同编号',
            '{$notice.loan_real_name}' => '出借人姓名',
            '{$notice.loan_user_name}' => '出借人用户名',
            '{$notice.loan_user_idno}' => '出借人身份证号',
            '{$notice.loan_bank_user}' => '出借人银行卡开户名',
            '{$notice.loan_bank_card}' => '出借人银行卡号',
            '{$notice.loan_bank_name}' => '出借人银行卡开户行',

            '{$notice.borrow_bank_user}' => '借款人银行卡开户名',
            '{$notice.borrow_bank_card}' => '借款人银行卡号',
            '{$notice.borrow_bank_name}' => '借款人银行卡开户行',

            '{$notice.loan_money}' => '出借金额',
            '{$notice.loan_money_uppercase}' => '出借金额大写',

            '{$notice.repay_time}' => '借款期限(仅数字)',
            '{$notice.repay_time_unit}' => '借款期限(x个月/x天)',
            '{$notice.start_time}' => '开始时间(合同生成时间或者放款时间)',
            '{$notice.end_time}' => '结束时间(自start_time加上借款期限)',
            '{$notice.rate}' => '借款年利率',
            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',
            '{$notice.loantype}' => '还款方式',
            '{$notice.sign_time}' => '出借人签署日期',
            '{$notice.borrow_sign_time}' => '借款人签署日期',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',
            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',

            '{$notice.loan_money_repay}' => '当前投资最终收到的回款金额（本金+利息）',
            '{$notice.loan_money_repay_uppercase}' => '当前投资最终收到的回款金额（大写）',
            '{$notice.loan_money_earning}' => '当前投资最终收到的利息',
            '{$notice.loan_money_earning_uppercase}' => '当前投资最终收到的利息（大写）',

            '{$notice.company_address_current}' => '借款公司住所地',
            '{$notice.prepayment_day_restrict}' => '提前还款限制',
            '{$notice.overdue_ratio}' => '逾期还款违约金系数',
            '{$notice.prepay_penalty_days}' => '提前还款罚息天数',
            '{$notice.prepayment_penalty_ratio}' => '提前还款违约金系数',
            '{$notice.overdue_break_days}' => '逾期还款T日解除合同',

            '{$notice.entrusted_loan_entrusted_contract_num}' => '委托贷款委托合同的合同编号',
            '{$notice.entrusted_loan_borrow_contract_num}' => '委托贷款借款合同的合同编号',
            '{$notice.base_contract_repay_time}' => '基础合同的借款到期日',

            '{$notice.redemption_period}' => '[通知贷]赎回周期',
            '{$notice.lock_period}' => '[通知贷]锁定周期',
            '{$notice.rate_day}' => '[通知贷]日利率',

            '{$notice.platform_name}' => '平台方',
            '{$notice.platform_license}' => '平台方营业执照号',
            '{$notice.platform_agency_realname}' => '平台方代理人',
            '{$notice.platform_agency_username}' => '平台方代理人平台用户名',
            '{$notice.platform_agency_idno}' => '平台方代理人身份证号',
            '{$notice.platform_agency_username}：平台方代理人平台用户名',
            '{$notice.advisory_name}' => '资产管理方',
            '{$notice.advisory_license}' => '资产管理方营业执照号',
            '{$notice.advisory_agent_real_name}' => '资产管理方代理人',
            '{$notice.agency_agent_user_name}' => '资产管理方代理人平台用户名',
            '{$notice.agency_agent_user_idno}：资产管理方代理人身份证号',
            '{$notice.platform_show_name}' => '运营的平台方',
            '{$notice.platform_domain}' => '平台域名',
            '{$notice.loan_type_mark}' => '偿还借款本息的方式',
            '{$notice.guarantee_fee_rate_type}' => '年化借款担保费收费方式',
            '{$notice.platform_name}' => '平台方的签署名称',
            '{$notice.advisory_name}' => '资产管理方的签署名称',
            '{$notice.advisory_sign_time}' => '资产管理方的签署日期',
            '{$notice.agency_sign_time}' => '保证方的签署日期',
            '{$notice.contract_transfer_type}' => '转让资产类别',
            '{$notice.base_deal_num}' => '基础合同的编号',
            '{$notice.leasing_contract_title}' => '基础合同名称',
            '{$notice.lessee_real_name}' => '原始债务人',
            '{$notice.borrow_money}' => '所转让总金额',
            '{$notice.uppercase_borrow_money}' => '所转让总金额大写',
            '{$notice.deal_repay_time}' => '借款/转让期限',
            '{$notice.guarantee_fee_rate_type}' => '担保费收费方式',

    ),

    //委托担保合同
    'TPL_ENTRUST_WARRANT_CONTRACT' => array(

            '{$notice.number}' => '合同编号',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.agency_name}' => '担保公司名称',
            '{$notice.agency_address}' => '担保公司注册地址',
            '{$notice.agency_user_realname}' => '担保公司法定代表人',
            '{$notice.agency_mobile}' => '担保公司联系电话',
            '{$notice.agency_postcode}' => '担保公司邮政编码',

            '{$notice.loan_real_name}' => '出借人姓名',

            '{$notice.loan_contract_num}' => '借款合同编号',
            '{$notice.uppercase_borrow_money}' => '合同金额人民币大写',
            '{$notice.start_time}' => '开始时间(合同生成时间或者放款时间)',
            '{$notice.end_time}' => '结束时间(自start_time加上借款期限)',
            '{$notice.guarantor_name}' => '保证人姓名',
            '{$notice.sign_time}' => '签署时间(合同生成时间或者放款时间)',
            '{$notice.review}' => '担保公司评审费',
            '{$notice.premium}' => '担保公司保费',
            '{$notice.caution_money}' => '履约保证金',
            '{$notice.guarantee_fee_rate}' => '借款担保费（区间）',
            '{$notice.guarantee_fee_rate_year}' => '借款担保费（年化）',

            '{$notice.rate}' => '借款年利率',
            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',
            '{$notice.loan_fee_rate}' => '借款手续费(区间)',
            '{$notice.loan_fee_rate_year}' => '借款手续费(年化)',
            '{$notice.repay_time}' => '借款期限(仅数字)',
            '{$notice.repay_time_unit}' => '借款期限(x个月/x天)',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',

            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',

            '{$notice.loan_money_repay}' => '当前投资最终收到的回款金额（本金+利息）',
            '{$notice.loan_money_repay_uppercase}' => '当前投资最终收到的回款金额（大写）',
            '{$notice.loan_money_earning}' => '当前投资最终收到的利息',
            '{$notice.loan_money_earning_uppercase}' => '当前投资最终收到的利息（大写）',

            '{$notice.company_address_current}' => '借款公司住所地',
            '{$notice.borrow_bank_user}' => '借款人银行卡开户名',
            '{$notice.borrow_bank_card}' => '借款人银行卡号',
            '{$notice.borrow_bank_name}' => '借款人银行卡开户行',
            '{$notice.agency_license}' => '担保公司营业执照号',
            '{$notice.agency_agent_real_name}' => '担保公司代理人真实姓名',
            '{$notice.agency_agent_user_name}' => '担保公司代理人网信理财网站用户名',
            '{$notice.agency_agent_user_idno}' => '担保公司代理人身份证号',
            '{$notice.overdue_ratio}' => '逾期还款违约金系数',
            '{$notice.overdue_break_days}' => '逾期还款T日解除合同',
            '{$notice.prepayment_penalty_ratio}' => '提前还款违约金系数',
            '{$notice.prepay_penalty_days}' => '提前还款罚息天数',

            '{$notice.entrusted_loan_entrusted_contract_num}' => '委托贷款委托合同的合同编号',
            '{$notice.entrusted_loan_borrow_contract_num}' => '委托贷款借款合同的合同编号',
            '{$notice.base_contract_repay_time}' => '基础合同的借款到期日',

    ),

    //保证反担保合同
    'TPL_WARRANDICE_CONTRACT' => array(

            '{$notice.number}' => '合同编号',
            '{$notice.guarantor_name}' => '保证人姓名',
            '{$notice.guarantor_address}' => '保证人住址',
            '{$notice.guarantor_mobile}' => '保证人手机号',
            '{$notice.guarantor_email}' => '保证人邮箱',
            '{$notice.guarantor_idno}' => '保证人身份证号',
            '{$notice.number}' => '合同编号',

            '{$notice.agency_name}' => '担保公司名称',
            '{$notice.agency_address}' => '担保公司注册地址',
            '{$notice.agency_user_realname}' => '担保公司法定代表人',
            '{$notice.agency_mobile}' => '担保公司联系电话',

            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',
            '{$notice.loan_real_name}' => '出借人姓名',
            '{$notice.loan_user_idno}' => '出借人身份证号',
            '{$notice.sign_time}' => '签署时间（合同生成时间）',
            '{$notice.loan_contract_num}' => '借款合同编号',
            '{$notice.warrant_contract_num}' => '保证合同编号',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',

            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',
    ),

    //保证合同
    'TPL_WARRANT_CONTRACT' => array(

            '{$notice.number}' => '合同编号',
            '{$notice.agency_name}' => '担保公司名称',
            '{$notice.agency_address}' => '担保公司注册地址',
            '{$notice.agency_user_realname}' => '担保公司法定代表人',
            '{$notice.agency_mobile}' => '担保公司联系电话',
            '{$notice.agency_postcode}' => '担保公司邮政编码',
            '{$notice.agency_fax}' => '担保公司传真',

            '{$notice.loan_real_name}' => '出借人姓名',
            '{$notice.loan_user_idno}' => '出借人身份证号',
            '{$notice.loan_user_address}' => '出借人住址',
            '{$notice.loan_user_mobile}' => '出借人手机号',
            '{$notice.loan_user_postcode}' => '出借人邮编',
            '{$notice.loan_user_email}' => '出借人邮箱',

            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',
            '{$notice.loan_money}' => '出借金额',
            '{$notice.loan_money_up}' => '出借金额大写',
            '{$notice.uppercase_borrow_money}' => '借款金额大写',

            '{$notice.start_time}' => '开始时间(合同生成时间或者放款时间)',
            '{$notice.end_time}' => '结束时间(自start_time加上借款期限)',
            '{$notice.sign_time}' => '签署时间',
            '{$notice.loan_contract_num}' => '借款合同编号',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',

            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',

            '{$notice.loan_money_repay}' => '当前投资最终收到的回款金额（本金+利息）',
            '{$notice.loan_money_repay_uppercase}' => '当前投资最终收到的回款金额（大写）',
            '{$notice.loan_money_earning}' => '当前投资最终收到的利息',
            '{$notice.loan_money_earning_uppercase}' => '当前投资最终收到的利息（大写）',

            '{$notice.agency_license}' => '担保公司营业执照号',
            '{$notice.agency_agent_real_name}' => '担保公司代理人真实姓名',
            '{$notice.agency_agent_user_name}' => '担保公司代理人网信理财网站用户名',
            '{$notice.agency_agent_user_idno}' => '担保公司代理人身份证号',
            '{$notice.loan_user_name}' => '出借人网信理财网站用户名',
            '{$notice.loan_bank_user}' => '出借人银行卡开户名',
            '{$notice.loan_bank_card}' => '出借人银行卡号',
            '{$notice.loan_bank_name}' => '出借人银行卡开户行',
            '{$notice.overdue_compensation_time}' => '逾期还款代偿期限',
            '{$notice.overdue_break_days}' => '逾期还款T日解除合同',
            '{$notice.prepayment_penalty_ratio}' => '提前还款违约金系数',
            '{$notice.prepay_penalty_days}' => '提前还款罚息天数',

            '{$notice.entrusted_loan_entrusted_contract_num}' => '委托贷款委托合同的合同编号',
            '{$notice.entrusted_loan_borrow_contract_num}' => '委托贷款借款合同的合同编号',
            '{$notice.base_contract_repay_time}' => '基础合同的借款到期日',

    ),

    //出借人平台服务协议
    'TPL_LENDER_PROTOCAL' => array(
            '{$notice.number}' => '合同编号',
            '{$notice.loan_real_name}' => '出借人姓名',
            '{$notice.loan_user_idno}' => '出借人身份证号',
            '{$notice.loan_address}' => '出借人住址',
            '{$notice.loan_phone}' => '出借人手机号',
            '{$notice.loan_email}' => '出借人邮箱',
            '{$notice.manage_fee_rate}' => '平台管理费',
            '{$notice.manage_fee_text}' => '平台管理费描述',
            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',

            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',

            '{$notice.manage_fee_rate_part}' => '出借人平台管理费（期间）',
            '{$notice.manage_fee_rate_part_prepayment}' => '出借人平台管理费-提前还款（期间）',
            '{$notice.loan_user_name}' => '出借人网信理财网站用户名',
            '{$notice.sign_time}' => '日期',

            '{$notice.entrusted_loan_entrusted_contract_num}' => '委托贷款委托合同的合同编号',
            '{$notice.entrusted_loan_borrow_contract_num}' => '委托贷款借款合同的合同编号',
            '{$notice.base_contract_repay_time}' => '基础合同的借款到期日',
            '{$notice.prepay_penalty_days}' => '提前还款罚息天数',
    ),

    //借款人平台服务协议
    'TPL_BORROWER_PROTOCAL' => array(
            '{$notice.number}' => '合同编号',
            '{$notice.loan_money}' => '借款金额',
            '{$notice.repay_time}' => '借款期限(仅数字)',
            '{$notice.repay_time_unit}' => '借款期限(x个月/x天)',
            '{$notice.loan_fee_rate}' => '借款手续费(年化)',
            '{$notice.loan_fee_rate_part}' => '借款手续费(区间)',
            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',

            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',

            '{$notice.consulting_company_name}' => '咨询公司名称',
            '{$notice.consulting_company_address}' => '咨询公司地址',
            '{$notice.consulting_company_tel}' => '咨询公司电话',
            '{$notice.consulting_company_bank_user}' => '咨询公司银行卡开户名',
            '{$notice.consulting_company_bank_card}' => '咨询公司银行卡号',
            '{$notice.consulting_company_bank_name}' => '咨询公司银行卡开户行',
            '{$notice.consulting_company_agent_real_name}' => '咨询公司代理人真实姓名',
            '{$notice.consulting_company_agent_user_name}' => '咨询公司代理人网信理财网站用户名',
            '{$notice.consulting_company_agent_user_idno}' => '咨询公司代理人身份证号',
            '{$notice.company_address_current}' => '借款公司住所地',
            '{$notice.borrow_bank_user}' => '借款人银行卡开户名',
            '{$notice.borrow_bank_card}' => '借款人银行卡号',
            '{$notice.borrow_bank_name}' => '借款人银行卡开户行',
            '{$notice.sign_time}' => '日期',

            '{$notice.entrusted_loan_entrusted_contract_num}' => '委托贷款委托合同的合同编号',
            '{$notice.entrusted_loan_borrow_contract_num}' => '委托贷款借款合同的合同编号',
            '{$notice.base_contract_repay_time}' => '基础合同的借款到期日',
            '{$notice.prepay_penalty_days}' => '提前还款罚息天数',
    ),

    //付款委托书
    'TPL_DEAL_PAYMENT_ORDER_HY' => array(
            '{$notice.borrow_money_up}' => '借款金额大写',
            '{$notice.borrow_money}' => '借款金额',
            '{$notice.money_up}' => '实得金额大写',
            '{$notice.money}' => '实得金额',
            '{$notice.loan_list}' => '投标记录（数组）',
            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
            '{$notice.consult_fee_rate_part}' => '借款咨询费（区间）',

            '{$notice.borrow_real_name}' =>    '借款人真实姓名',
            '{$notice.borrow_user_name}' =>    '借款人用户名',
            '{$notice.borrow_user_idno}' =>    '借款人身份证',
            '{$notice.borrow_address}' => '借款人住址',
            '{$notice.borrow_mobile}' => '借款人手机号',
            '{$notice.borrow_postcode}' => '借款人邮箱',
            '{$notice.borrow_email}' =>    '借款人邮箱',

            '{$notice.company_name}' =>    '借款公司名称',
            '{$notice.company_address}' => '公司地址',
            '{$notice.company_legal_person}' =>    '公司法定代表人',
            '{$notice.company_tel}' => '公司联系电话',
            '{$notice.company_license}' => '公司营业执照号',
            '{$notice.company_description}' => '公司简介',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',

            '{$notice.leasing_contract_num}' => '[中租]基础合同（融资租赁合同）的编号',
            '{$notice.lessee_real_name}' => '[中租]基础合同项下的承租人名称',
            '{$notice.leasing_money}' => '[中租]基础合同交易金额(小写)',
            '{$notice.leasing_money_uppercase}' => '[中租]基础合同交易金额(大写)',
            '{$notice.repay_money}' => '[中租]资产出让方到期赎回的金额(小写)',
            '{$notice.repay_money_uppercase}' => '[中租]资产出让方到期赎回的金额(大写)',

            '{$notice.prepay_penalty_days}' => '提前还款罚息天数',
    ),

    //见证人证明书  借款合同
    'TPL_DEAL_LOAN_PROVE' => array(
            '{$notice.borrow_real_name}' => '借款人姓名',
            '{$notice.loan_real_name}' => '出借人姓名',
            '{$notice.year}' => '年',
            '{$notice.month}' => '月',
            '{$notice.day}' => '日',

            '{$notice.contract_num}' => '合同编号',
            '{$notice.money}' => '合同金额（出借金额）',
            '{$notice.repay_time}' => '借款期限(仅数字)',
            '{$notice.repay_time_unit}' => '借款期限(x个月/x天)',
            '{$notice.sign_time}' => '签署日期',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',
            '{$notice.consult_fee_rate}' => '借款咨询费（年化）',
    ),

    //见证人证明书  保证合同
    'TPL_DEAL_WARRANT_PROVE' => array(
            '{$notice.loan_real_name}' => '出借人姓名',
            '{$notice.agency_name}' => '担保公司名称',
            '{$notice.year}' => '年',
            '{$notice.month}' => '月',
            '{$notice.day}' => '日',

            '{$notice.contract_num}' =>    '合同编号',
            '{$notice.money}' => '合同金额（出借金额）',
            '{$notice.repay_time}' => '借款期限(仅数字)',
            '{$notice.repay_time_unit}' => '借款期限(x个月/x天)',
            '{$notice.sign_time}' => '签署日期',

            '{$notice.use_info}' => '借款用途详述',
            '{$notice.house_address}' => '房产地址',
            '{$notice.house_sn}' => '房产证编号',
            '{$notice.consult_fee_rate}' => '借款咨询费',
    ),
);
?>

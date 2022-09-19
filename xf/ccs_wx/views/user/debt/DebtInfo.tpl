<!DOCTYPE html>
<html class="x-admin-sm">
  <head>
    <meta charset="UTF-8">
    <title>查看债转记录</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <link rel="stylesheet" href="<{$CONST.jsPath}>/element-ui/index.css">
    <script src="<{$CONST.jsPath}>/jquery-2.1.4.min.js"></script>
    <script src="<{$CONST.jsPath}>/vue/vue.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="<{$CONST.jsPath}>/element-ui/index.js" type="text/javascript" charset="utf-8"></script>
    <script src="<{$CONST.jsPath}>/dayjs.min.js"></script>
    <style>
      [v-cloak] {
        display: none;
      }
      #info-detail {
        background: #F0F0F0;
        padding: 15px;
      }
      .title-wrapper,
      .debt-info-wrapper,
      .pay-info-wrapper,
      .order-info-wrapper,
      .trade-info-wrapper,
      .btn-box{
        background: #fff;
        padding: 20px;
        margin-bottom: 20px;
      }
      p {
        margin-top: 0;
        font-size: 20px;
        font-weight: bold;
      }
      .image-list {
        display: flex;
      }
      .img-box {
        width: 100px;
        margin-bottom: 20px;
        margin-right: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .el-row {
        margin-bottom: 10px;
      }
      .title-wrapper {
        font-size: 30px;
        font-weight: bold;
        padding: 15px;
      }
      .name {
        text-align: right;
      }
      .btn-box {
        text-align: center;
      }
      .tip {
        font-size: 16px;
        padding-bottom: 20px;
      }
    </style>
    <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
    <!--[if lt IE 9]>
        <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
        <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  
  <body>
    <div class="" id="info-detail" v-cloak>
      <!-- status info -->
      <div class="title-wrapper" v-if="operation == 1">
        状态：{{ fmtStatus(info.status) }}
      </div>

      <!-- debt info -->
      <div class="debt-info-wrapper">
        <p>债转信息</p>
        <el-row :gutter="20">
          <el-col :span="6">
            <el-col :span="12" class="name">转让id：</el-col>
            <el-col :span="12" class="num">{{ info.debt_id }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">待还本金：</el-col>
            <el-col :span="12" class="num">{{ info.money }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">转让折扣：</el-col>
            <el-col :span="12" class="num">{{ info.discount }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">转让价格：</el-col>
            <el-col :span="12" class="num">{{ transferPrice }}</el-col>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="6">
            <el-col :span="12" class="name">转让人手机号：</el-col>
            <el-col :span="12" class="num">{{ info.mobile }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">转让人姓名：</el-col>
            <el-col :span="12" class="num">{{ info.real_name }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">受让人手机号：</el-col>
            <el-col :span="12" class="num">{{ info.t_mobile }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">受让人姓名：</el-col>
            <el-col :span="12" class="num">{{ info.t_real_name }}</el-col>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="6">
            <el-col :span="12" class="name">债转类型：</el-col>
            <el-col :span="12" class="num">{{ fmtType(info.deal_type) }}</el-col>
          </el-col>
          <el-col :span="6">
            <el-col :span="12" class="name">借款标题：</el-col>
            <el-col :span="12" class="num">{{ info.name }}</el-col>
          </el-col>
        </el-row>
      </div>

      <!-- pay info -->
      <div class="pay-info-wrapper">
        <p>付款信息</p>
        <div class="image-list" v-if="srcList.length">
          <div class="img-box" v-for="(item, index) in srcList" :key="index">
            <el-image 
              style="width: 100px; height: 100px"
              :src="item" 
              :preview-src-list="srcList">
            </el-image>
            <span>付款凭证</span>
          </div>
        </div>

        <div class="account-info">
          <el-row :gutter="20">
            <el-col :span="6">
              <el-col :span="12" class="name">付款人：</el-col>
              <el-col :span="12" class="num">{{ info.payer_name }}</el-col>
            </el-col>
            <el-col :span="6">
              <el-col :span="8" class="name">开户行：</el-col>
              <el-col :span="16" class="num">{{ info.payer_bankzone }}</el-col>
            </el-col>
            <el-col :span="6">
              <el-col :span="8" class="name">银行卡号：</el-col>
              <el-col :span="16" class="num">{{ info.payer_bankcard }}</el-col>
            </el-col>
            <el-col :span="6">
              <el-col :span="12" class="name">付款金额：</el-col>
              <el-col :span="12" class="num">{{ info.action_money }}</el-col>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="6">
              <el-col :span="12" class="name">收款人：</el-col>
              <el-col :span="12" class="num">{{ info.payee_name }}</el-col>
            </el-col>
            <el-col :span="6">
              <el-col :span="8" class="name">开户行：</el-col>
              <el-col :span="16" class="num">{{ info.payee_bankzone }}</el-col>
            </el-col>
            <el-col :span="6">
              <el-col :span="8" class="name">银行卡号：</el-col>
              <el-col :span="16" class="num">{{ info.payee_bankcard }}</el-col>
            </el-col>
            <el-col :span="6">
              <el-col :span="12" class="name">收款金额：</el-col>
              <el-col :span="12" class="num">{{ info.action_money }}</el-col>
            </el-col>
          </el-row>
        </div>
      </div>

      <!-- order info -->
      <div class="order-info-wrapper">
        <p>订单信息</p>
        <el-row :gutter="20">
          <el-col :span="8" class="name">订单id：</el-col>
          <el-col :span="16" class="num">{{ info.debt_id }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">申请转让时间：</el-col>
          <el-col :span="16" class="num">{{ formatDate(info.debt_addtime) }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">下单时间：</el-col>
          <el-col :span="16" class="num">{{ formatDate(info.addtime) }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">支付方式：</el-col>
          <el-col :span="16" class="num">线下支付</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">提交付款确认时间：</el-col>
          <el-col :span="16" class="num">{{ formatDate(info.submit_paytime) }}</el-col>
        </el-row>
      </div>

      <!-- trade info -->
      <div class="trade-info-wrapper" v-if="operation == 1">
        <p>交易判定</p>
        <el-row :gutter="20">
          <el-col :span="8" class="name">用户描述：</el-col>
          <el-col :span="16" class="num">{{ info.decision_outaccount }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">操作人：</el-col>
          <el-col :span="16" class="num">{{ info.decision_maker }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">操作时间：</el-col>
          <el-col :span="16" class="num">{{ formatDate(info.decision_time) }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">操作结果：</el-col>
          <el-col :span="16" class="num">{{ fmtResult(info.decision_status) }}</el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8" class="name">原因说明：</el-col>
          <el-col :span="16" class="num">{{ info.decision_outcomes }}</el-col>
        </el-row>
      </div>

      <!-- btn box -->
      <div class="btn-box" v-if="operation == 2">
        <el-button type="warning" @click="invalidTrade">无效交易</el-button>
        <el-button type="primary" @click="effectiveTrade">有效交易</el-button>
      </div>

      <!-- btn box -->
      <div class="btn-box" v-if="operation == 1">
        <el-button type="default" @click="goBack">返回</el-button>
      </div>

      <!-- dialog -->
      <el-dialog title="提示" :visible.sync="showDialog" center :close-on-click-modal="false">
        <div class="tip">{{ tipText }}</div>
        <div class="content">
          <el-input v-model="remarks" type="textarea" :rows="3" maxlength="100" show-word-limit></el-input>
        </div>
        <div slot="footer" class="dialog-footer">
          <el-button @click="showDialog = false">取 消</el-button>
          <el-button type="primary" @click="submit">{{ confirmText }}</el-button>
        </div>
      </el-dialog>
    </div>

    <script>
      let app = new Vue({
        el: '#info-detail',
        data() {
          return {
            debt_id: '', // 债转id
            deal_type: '',  // 类型: 1 -> 尊享, 2 -> 普惠
            operation: '', // 操作: 1 -> 查看, 2 -> 判定
            staticData: {
              status: ['转让中', '交易成功', '交易取消', '已过期', '待买方付款', '待收款'],  // 债转状态
              deal_type: ['尊享', '普惠'], // 债转类型
              decision_status: ['客服介入待处理', '判定有效交易', '判定无效交易'] // 操作结果
            },
            msg: {
              debt_id: "-", // 债转记录ID
              name: "-", // 项目名称
              status: "-", // 债转状态：1-转让中，2-交易成功，3-交易取消，4-已过期，5-待买方付款，6-待收款
              money: "-", // 债转总金额
              discount: "-", // 折扣
              buy_code: "-", // 认购码
              endtime: "-", // 债转结束时间
              cancel_time: "-", // 待买方付款过期时间戳 或者 待收款过期时间戳（不存在为0）
              payee_name: "-", // 收款人姓名
              payee_bankzone: "-", // 收款人开户行
              payee_bankcard: "-", // 收款人卡号
              serial_number: "-", // 债转编号
              action_money: '-', // 实际交易金额
              payer_name: "-", // 付款人姓名
              payer_bankzone: "-", // 付款人开户行
              payer_bankcard: "-", // 付款人卡号
              payment_voucher: [ // 付款凭证

              ],
              debt_addtime: '-', // 申请转让时间
              addtime: "-", // 下单时间（不存在为0）
              submit_paytime: "-", // 付款时间（不存在为0）
              successtime: "-", // 交易成功时间（不存在为0）
              user_id: "-", // 转让人ID
              real_name: "-", // 转让人姓名
              mobile: "-", // 转让人手机号
              arrival_amount: "-", // 到账金额
              debt_tender_id: "-", // 认购记录ID
              deal_type: "-", // 项目类型：1-尊享，2-普惠
              is_appeal: "-", // 是否客服介入：0-未介入，1-已介入
              appeal_addtime: "-", // 平台介入时间（不存在为0）
              decision_maker: "-", // 操作人姓名（不存在为空字符串）
              decision_time: "-", // 操作时间（不存在为0）
              decision_status: "-", // 操作结果（不存在为0）1-客服介入待处理 2-判定有效交易 3-判定无效交易
              t_user_id: "-", // 受让人ID（不存在为空字符串）
              t_real_name: "-", // 受让人姓名（不存在为空字符串）
              t_mobile: "-" // 受让人手机号（不存在为空字符串）
            },
            showDialog: false,
            remarks: '',
            trade: undefined, // 2: 无效, 1: 有效
            srcList: []
          }
        },
        computed: {
          transferPrice() {
            if (this.info.money && this.info.discount) {
              return (Number(this.info.money) * Number(this.info.discount) / 10).toFixed(2)
            }
          },
          tipText() {
            return this.trade == 2 ? '判定无效交易之前请确保已与交易双方资金并未到账' : '判定有效交易之前请确保已与交易双方确认资金已到账'
          },
          confirmText() {
            return this.trade == 2 ? '判定无效交易' : '确定有效交易'
          },
          info() {
            let clone = JSON.parse(JSON.stringify(this.msg))
            for(let k in clone) {
              if(k !== 'is_appeal') {
                if(!clone[k] || clone[k] == 'null' || clone[k] == 'false' || clone[k] == '0' || clone[k] == 'undefined') {
                  clone[k] = '-'
                }
              }
            }
            return clone
          }
        },
        created() {
          let url = window.location.search.replace('?', '')
          let obj = {}
          url.split('&').forEach(item => {
            let key = item.split('=')[0]
            let value = item.split('=')[1]
            obj[key] = value
          })
          this.debt_id = obj.id
          this.deal_type = obj.deal_type
          this.operation = obj.operation
          this.getInfo()
        },
        methods: {
          getInfo() {
            let _this = this
            $.ajax({
              url:'/user/Debt/DebtInfo',
              type:'post',
              data:{
                'deal_type': _this.deal_type,
                'debt_id': _this.debt_id
              },
              dataType:'json',
              success:function(res) {
                if (res['code'] === 0) {
                  _this.msg = { ...res.data }
                  _this.srcList = res.data.payment_voucher

                } else {
                  let msg = ''
                  if(res['info']) {
                    msg = res['info']
                  }else {
                    msg = '系统异常，请稍后再试'
                  }
                  _this.$message.error(msg);
                }
              },
              error: function(err) {
                _this.$message.error('系统异常，请稍后再试');
              }
            });
          },
          invalidTrade() {
            this.trade = 2
            this.openDialog()
          },
          effectiveTrade() {
            this.trade = 1
            this.openDialog()
          },
          openDialog() {
            this.showDialog = true
            this.remarks = ''
          },
          submit() {
            let _this = this
            $.ajax({
              url:'/user/Debt/DebtJudge',
              type:'post',
              data:{
                'deal_type': _this.deal_type,
                'debt_id': _this.debt_id,
                'operation': _this.trade,
                'outcomes': _this.remarks
              },
              dataType:'json',
              success:function(res) {
                if (res['code'] === 0) {
                  _this.$message.success(res.info)
                  _this.showDialog = false
                  xadmin.close()
                  parent.location.reload();
                } else {
                  let msg = ''
                  if(res['info']) {
                    msg = res['info']
                  }else {
                    msg = '系统异常，请稍后再试'
                  }
                  _this.$message.error(msg);
                }
              },
              error: function(err) {
                _this.$message.error('系统异常，请稍后再试');
              }
            });
          },
          // 时间转换
          formatDate(time) {
            if (time && time.toString().length >= 10) {
              return dayjs(time * 1e3).format('YYYY-MM-DD HH:mm:ss')
            }
            return time
          },
          // 状态转换
          fmtStatus(s) {
            if(this.notEmpty(s)) {
              return this.staticData.status[Number(s) - 1]
            }
            return s
          },
          // 类型转换
          fmtType(t) {
            if(this.notEmpty(t)) {
              return this.staticData.deal_type[Number(t) - 1]
            }
            return t
          },
          // 结果转换
          fmtResult(r) {
            if(this.notEmpty(r)) {
              return this.staticData.decision_status[Number(r) - 1]
            }
            return r
          },
          // 非空判断
          notEmpty(key) {
            return key != '-'
          },
          goBack() {
            xadmin.close()
          }
        }
      })
    </script>
  </body>
</html>
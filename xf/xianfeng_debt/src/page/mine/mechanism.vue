<template>
  <div class="container">
    <div class="assets">
      <div class="nav-title" @click="goBack">
        <p>{{ title }}</p>
      </div>
      <div class="assets-box">
        <div class="assets-box-title">
          <p>资产(元)</p>
          <h1>{{ utils.toThousands(total) }}</h1>
        </div>
        <div class="assets-box-foot">
          <p>待还本金:</p>
          <h2><span>¥</span>{{ utils.toThousands(wait_acount) }}</h2>
        </div>
      </div>
    </div>
    <div class="counter">
      <div class="title">待还本金</div>
      <div class="box" @click="goToProject(1)" v-for="(item, index) in arrList" :key="index">
        <div class="box-name">{{ item.name }}</div>
        <div class="box-text">
          <p><span>¥</span>{{ item.confirm }}</p>
          <img src="../../assets/image/mine/right-3.png" alt="" />
        </div>
      </div>
      <!--            <div class="box" @click="goToProject(0)">-->
      <!--                <div class="box-name">尊享</div>-->
      <!--                <div class="box-text">-->
      <!--                    <p><span>¥</span>100,000.00</p>-->
      <!--                    <img src="../../assets/image/mine/right-3.png" alt="">-->
      <!--                </div>-->
      <!--            </div>-->
    </div>
  </div>
</template>

<script>
import { getAccount } from '../../api/mine'
import { Toast } from 'vant'
export default {
  name: 'mechanism',
  data() {
    return {
      params: {
        platform_id: 0,
        platform_user_id: 0
      },
      arrList: [],
      total: 0,
      wait_acount: 0
    }
  },
  computed: {},
  created() {
    let obj = this.$route.params.id.split('&')
    this.params.platform_id = obj[0]
    this.params.platform_user_id = obj[1]
    this.title = this.$route.meta.title
    this.getData()
  },
  methods: {
    goBack() {
      console.log(1)
      this.$router.go(-1)
    },
    getData() {
      getAccount(this.params)
        .then(res => {
          if (res.code == 0) {
            this.arrList = res.data.list.project
            this.total = res.data.total
            this.wait_acount = res.data.wait_acount
          } else {
            Toast(res.info)
          }
        })
        .catch(err => {})
    },
    goToProject(t) {
      if (t) {
        this.$router.push({ name: 'project', params: { id: this.params } })
      } else {
        this.$router.push({ name: 'project', params: { id: this.params } })
      }
    }
  }
}
</script>

<style lang="less" scoped>
.nav-title {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  margin: 10px 0;
  text-align: center;
  p {
    font-size: 18px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(255, 255, 255, 1);
    position: relative;
    &::after {
      content: '';
      width: 9px;
      height: 16px;
      background: url('../../assets/image/mine/right-4.png') no-repeat;
      background-size: cover;
      position: absolute;
      top: 4px;
      left: 20px;
    }
  }
}
.container {
  background-color: #fff;
  .assets {
    height: 72px;
    padding-top: 60px;
    background: url('../../assets/image/mine/back-1.png') no-repeat;
    background-size: cover;
    .assets-box {
      width: 90%;
      height: 142px;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 0px 10px 0px rgba(3, 189, 165, 0.3);
      border-radius: 8px;
      margin: 0 auto;
      .assets-box-title {
        padding-top: 20px;
        margin-left: 22px;
        margin-bottom: 16px;
        p {
          height: 22px;
          font-size: 14px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(153, 153, 153, 1);
          line-height: 24px;
        }
        h1 {
          height: 37px;
          font-size: 32px;
          font-family: DINAlternate-Bold, DINAlternate;
          font-weight: bold;
          color: rgba(64, 64, 64, 1);
          line-height: 37px;
        }
      }
      .assets-box-foot {
        display: flex;
        /*align-items: center;*/
        margin-left: 22px;
        p {
          height: 22px;
          font-size: 12px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(153, 153, 153, 1);
          line-height: 24px;
        }
        h2 {
          height: 21px;
          font-size: 16px;
          font-family: DINPro-Regular, DINPro;
          font-weight: 400;
          color: rgba(64, 64, 64, 1);
          line-height: 21px;
          margin-left: 8px;
          span {
            height: 15px;
            font-size: 12px;
            font-family: DINPro-Regular, DINPro;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            line-height: 15px;
            margin-right: 3px;
          }
        }
      }
    }
  }
  .counter {
    margin-top: 102px;
    .title {
      height: 22px;
      font-size: 16px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(64, 64, 64, 1);
      line-height: 22px;
      padding-bottom: 10px;
      margin: 0 15px;
      border-bottom: 1px dashed rgba(244, 244, 244, 1);
    }
    .box {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 0 24px 0 21px;
      padding: 15px 0;
      border-bottom: 1px dashed rgba(244, 244, 244, 1);
      .box-name {
        height: 20px;
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(102, 102, 102, 1);
        line-height: 20px;
      }
      .box-text {
        display: flex;
        align-items: center;
        p {
          font-size: 16px;
          font-family: DINPro-Regular, DINPro;
          font-weight: 400;
          color: rgba(102, 102, 102, 1);
          margin-right: 10px;
          span {
            font-size: 14px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(102, 102, 102, 1);
            margin-right: 3px;
          }
        }
        img {
          width: 8px;
          height: 13px;
        }
      }
    }
  }
}
</style>

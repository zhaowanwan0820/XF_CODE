<template>
  <div class="container" :class="{ isheight: isShow }">
    <div class="title-top">
      <div class="title-top-user">
        <img class="header" src="../../../assets/image/mine/user.jpeg" alt="" />
        <p>{{ phone }}</p>
        <img class="st" src="../../../assets/image/mine/s.png" alt="" />
      </div>
      <div class="title-top-icon">
        <img src="../../../assets/image/mine/mine-icon.png" alt="" @click="goToSetting" />
      </div>
    </div>
    <div class="title-info" @click="goToAssets">
      <div class="title-info-text">
        <div class="text-name">总资产(元)</div>
        <div class="text-number">{{ isShow ? utils.toThousands(total) : total }}</div>
        <div class="principal" v-if="isShow">
          <span>待还本金:</span>
          <p><span>¥</span>{{ utils.toThousands(wait_acount) }}</p>
        </div>
      </div>
      <div class="title-info-right">
        <span>查看详情</span>
        <img src="../../../assets/image/mine/right.png" alt="" />
      </div>
    </div>
    <div class="title-box" :class="{ istop: !isShow }">
      <div class="box-title">
        <div class="title-box-left">
          <img src="../../../assets/image/mine/mine-f.png" alt="" />
          <div class="title-box-text" @click="goTolink(0)">
            <h4>资产确权</h4>
            <div>
              <span>立即确权</span>
              <img src="../../../assets/image/mine/right-5.png" alt="" />
            </div>
          </div>
        </div>
        <div class="title-box-left">
          <img src="../../../assets/image/mine/mine-r.png" alt="" />
          <div class="title-box-text" @click="goTolink(1)">
            <h4>我的债转</h4>
            <div>
              <span>查看详情</span>
              <img src="../../../assets/image/mine/right-5.png" alt="" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getUser, getTotal } from '../../../api/mine'
import { Toast } from 'vant'
export default {
  name: 'MineTitle',
  data() {
    return {
      phone: '',
      // phone:'188****2089',
      isShow: false,
      total: '暂无资产',
      wait_acount: 0
    }
  },
  computed: {},
  created() {
    this.getData()
    console.log(this.$store.state)
  },
  methods: {
    getData() {
      getUser()
        .then(res => {
          if (res.code == 0) {
            this.phone = res.data.userInfo.phone
          } else {
            Toast(res.info)
          }
        })
        .catch(err => {})
      //获取总资产
      getTotal()
        .then(res => {
          res.data.total == 0 ? (this.isShow = false) : (this.isShow = true)
          this.total = res.data.total == 0 ? '暂无资产' : res.data.total
          this.wait_acount = res.data.wait_acount
        })
        .catch(err => {})
    },
    goToSetting() {
      this.$router.push({ name: 'setting' })
    },
    goToAssets() {
      this.$router.push({ name: 'assets' })
    },
    goTolink(n) {
      if (n) {
        this.$router.push({ name: 'mytransfer' })
      } else {
        this.$router.push({ name: 'choosePlatForConfirm' })
      }
    }
  }
}
</script>

<style lang="less" scoped>
.container {
  width: 100%;
  height: 194px;
  background: #fff url('../../../assets/image/mine/mine-title.png') no-repeat;
  background-size: cover;
  .title-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 15px 20px;
    .title-top-user {
      display: flex;
      align-items: center;
      .header {
        width: 29px;
        height: 29px;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, 0.9);
        background-color: #fff;
      }
      p {
        height: 21px;
        font-size: 18px;
        font-family: DINAlternate-Bold, DINAlternate;
        font-weight: bold;
        color: rgba(255, 255, 255, 1);
        line-height: 21px;
        margin-left: 8px;
      }
      .st {
        width: 5px;
        height: 5px;
        margin-left: 7px;
      }
    }
    .title-top-icon {
      img {
        width: 18px;
        height: 18px;
      }
    }
  }
  .title-info {
    display: flex;
    justify-content: space-between;
    margin: 0 15px 0 31px;
    .title-info-text {
      .text-name {
        height: 20px;
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.8);
        line-height: 20px;
      }
      .text-number {
        height: 40px;
        font-size: 28px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(255, 255, 255, 1);
        line-height: 40px;
      }
      .principal {
        display: flex;
        align-items: center;
        margin: 16px 0 16px;
        span {
          font-size: 14px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(255, 255, 255, 0.8);
        }
        p {
          font-size: 18px;
          font-family: DINPro-Regular, DINPro;
          font-weight: 400;
          color: rgba(255, 255, 255, 1);
          span {
            font-size: 14px;
            font-family: DINPro-Regular, DINPro;
            font-weight: 400;
            color: rgba(255, 255, 255, 1);
            margin-left: 8px;
            margin-right: 5px;
          }
        }
      }
    }
    .title-info-right {
      display: flex;
      align-items: center;
      height: 20px;
      font-size: 14px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(255, 255, 255, 0.8);
      line-height: 20px;
      img {
        width: 7px;
        height: 14px;
        margin-left: 9px;
      }
    }
  }
  .title-box {
    padding: 0 15px 0;
    .box-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .title-box-left {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 165px;
      height: 69px;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 4px 15px 0px rgba(4, 177, 164, 0.21);
      border-radius: 6px;
      img {
        width: 37px;
        height: 37px;
        margin-left: 17px;
      }
      .title-box-text {
        margin-right: 26px;
        h4 {
          height: 22px;
          font-size: 16px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(64, 64, 64, 1);
          line-height: 22px;
          margin-bottom: 3px;
        }
        div {
          display: flex;
          align-items: center;
          span {
            font-size: 14px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
          }
          img {
            width: 7px;
            height: 14px;
            margin-left: 7px;
          }
        }
      }
    }
    .title-box-right {
      display: flex;
      align-items: center;
      margin-right: 16px;
      span {
        height: 22px;
        font-size: 16px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(4, 177, 164, 1);
        line-height: 22px;
      }
      img {
        width: 7px;
        height: 14px;
        margin-left: 8px;
      }
    }
  }
  .istop {
    margin-top: 24px;
  }
}
.isheight {
  height: 226px;
}
</style>

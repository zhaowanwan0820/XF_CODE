<template>
  <div class="seller-said">
    <div class="name">
      <img src="../../../assets/image/hh-icon/icon-header-返回.svg" class="back-icon" @click="goBack" />
      <span class="avator"></span>
      <p class="txt">{{ sellerName }} 分享的商品</p>
    </div>
    <div class="said">
      <span class="blank"></span>
      <p class="txt t-say">{{ sellerSaid }}</p>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  data() {
    return {}
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    }),
    sellerName() {
      let n = 'XXX****XXXX'
      if (this.detailInfo.seller) {
        n = this.detailInfo.seller.nickname
      }
      return n
    },
    sellerSaid() {
      let n = `${this.utils.mlmUserName}没有对该商品进行描述哦~`
      if (this.detailInfo.seller) {
        n = this.detailInfo.seller.remark
      }
      return n
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
p {
  margin: 0;
  padding: 0;
}
.seller-said {
  background-color: #fff;
  padding: 13px 15px 10px;
}
.name,
.said {
  display: flex;
  justify-content: flex-start;
  align-items: center;

  .back-icon {
    margin-right: 15px;
  }
  .avator,
  .blank {
    display: block;
    width: 22px;
    height: 22px;
  }
  .avator {
    background: url('../../../assets/image/hh-icon/mlm/mlm-buyer-avator@3x.png') no-repeat 50% 50%;
    background-size: contain;
  }
  .txt {
    flex: 1;
    padding-left: 12px;
    font-size: 12px;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 17px;
  }
  .t-say {
    margin: 0;
    font-size: 12px;
    font-weight: bold;
    color: rgba(64, 64, 64, 1);
    line-height: 17px;
  }
}
.said {
  margin-top: 8px;
}
</style>

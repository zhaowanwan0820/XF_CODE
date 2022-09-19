<template>
  <div class="container">
    <mt-header class="header" title="店铺信息">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="shop-head">
      <div class="img-wrapper">
        <img :src="getUrl" v-if="supplier.icon" />
        <img src="../../assets/image/hh-icon/supplier/icon-shop.png" v-else alt="" />
      </div>
      <p class="shop-name">{{ supplier.name }}</p>
      <p class="shop-signature">{{ supplier.signature }}</p>
      <div class="supplier-type">
        <span v-if="supplier.type == 1" class="type type1"><span>&nbsp;积分商家&nbsp;</span></span>
        <span v-if="supplier.type == 3" class="type type3"><span>&nbsp;积分商家&nbsp;</span></span>
        <span v-if="supplier.type == 5" class="type type5"><span>&nbsp;个人商家&nbsp;</span></span>
      </div>
    </div>
    <div class="shop-detail-info">
      <p class="detail-title">基础信息</p>
      <info-item title="店铺介绍" :content="supplier.desc" v-if="supplier.desc"></info-item>
      <!-- <info-item title="保证金" :content="bondMoney"></info-item> -->
      <info-item
        title="认证方式"
        :content="supplier.type == 2 ? '企业认证' : '个人认证'"
        v-if="[2, 5].indexOf(supplier.type) > -1"
      ></info-item>
      <info-item title="营业执照" type="business" :goto="true" v-if="supplier.business_license"></info-item>
      <info-item
        title="品牌及授权"
        type="brand"
        :goto="true"
        v-if="supplier.brand_license && supplier.brand_license.length"
      ></info-item>
      <info-item
        title="开店时间"
        :content="utils.formatDate('YYYY-MM-DD HH:mm', supplier.create_time)"
        v-if="supplier.create_time"
      ></info-item>
      <info-item title="备注" :content="supplier.remark" v-if="supplier.remark"></info-item>
      <div class="line"></div>
      <p class="detail-title">商家服务</p>
      <info-item title="客服电话" :content="supplier.service_tel" v-if="supplier.service_tel"></info-item>
      <info-item title="客服QQ" :content="supplier.service_qq" v-if="supplier.service_qq"></info-item>
      <info-item title="工作日" :content="`${serviceWeek}\xa0\xa0\xa0\xa0${serviceHoliday}`"></info-item>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { shopGet } from '../../api/shop'
import InfoItem from './child/InfoItem'
export default {
  name: 'supplierInfo',
  components: { InfoItem },
  data() {
    return {
      id: this.$route.query.id ? this.$route.query.id : null,
      supplier: {},
      isIos: false
    }
  },
  created() {
    this.getSupplierInfo()
    this.isIos = 1 == this.utils.getOpenBrowser() ? true : false
  },
  computed: {
    getUrl() {
      return this.supplier.icon
    },
    serviceWeek() {
      let time = this.svcTime
      if (time.weekdays_s) {
        return '工作日：' + time.weekdays_s + '-' + time.weekdays_e
      } else {
        return ''
      }
    },
    serviceHoliday() {
      let time = this.svcTime
      if (time.holiday_s) {
        return '节假日：' + time.holiday_s + '-' + time.holiday_e
      } else {
        return ''
      }
    },
    svcTime() {
      let data = this.supplier.svc_time || {}
      let weekdays = data.weekdays || {}
      let holiday = data.holiday || {}
      return {
        weekdays_s: weekdays.s,
        weekdays_e: weekdays.e,
        holiday_s: holiday.s,
        holiday_e: holiday.e
      }
    },
    bondMoney() {
      return Number(this.supplier.bond_money) > 0
        ? `已缴纳${this.utils.formatMoney(this.supplier.bond_money)}元`
        : '未缴'
    }
  },
  methods: {
    getSupplierInfo() {
      shopGet(this.id).then(
        res => {
          this.supplier = res
        },
        error => {
          console.log(error)
        }
      )
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background: #fff;
}
.header {
  @include header;
  @include thin-border($lineColor);
}
.shop-head {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-bottom: 16px;
  @include thin-border($lineColor);
  .img-wrapper {
    width: 72px;
    height: 72px;
    margin-top: 15px;
    border-radius: 3px;
    box-shadow: 0px 2px 8px 0px rgba(216, 216, 216, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    img {
      width: 48px;
      height: 48px;
    }
  }
  .shop-name {
    font-size: 13px;
    color: #404040;
    line-height: 18px;
    margin-top: 12px;
  }
  .shop-signature {
    @include sc(11px, #999) font-weight: 400;
    line-height: 16px;
    margin-top: 2px;
  }
  .supplier-type {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    margin-top: 11px;
    .type {
      height: 14px;
      padding: 0;
      border-radius: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
      span {
        margin: 0 -6px;
        line-height: 1;
        @include sc(8px, #ffffff, center center);
      }
      &.type1 {
        background-color: #d8aab7;
      }
      &.type2 {
        background-color: #c2b5cf;
      }
      &.type3 {
        background-color: #d8aab7;
      }
      &.type5 {
        background-color: #b5c884;
      }
    }
    .supplier-desc {
      @include sc(11px, #999999, left center);
      width: 150px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
}
.shop-desc-wrapper {
  padding: 15px 0 8px;
  margin: 0 15px;
  @include thin-border(rgba(85, 46, 32, 0.2), 0, auto, true);
  .desc-title {
    font-size: 13px;
    font-weight: 400;
    color: #404040;
    line-height: 18px;
  }
  .shop-desc {
    margin-top: 7px;
    font-size: 12px;
    font-weight: 300;
    color: #999;
    line-height: 16px;
  }
}

.shop-detail-info {
  margin: 0 15px;
  .detail-title {
    margin-top: 15px;
    height: 18px;
    font-size: 13px;
    font-weight: bold;
    color: #404040;
    line-height: 18px;
  }
  .line {
    margin-top: 15px;
    border: 0.5px dotted rgba(85, 46, 32, 0.1);
  }
}

.service {
  margin: 0 15px;

  .serviceType-wrapper {
    display: block;
    text-decoration: none;
  }

  .content-line {
    display: flex;
    justify-content: space-between;
    @include thin-border(rgba(85, 46, 32, 0.2), 0, auto, true);

    p {
      font-size: 13px;
      line-height: 20px;
    }
    .content-title {
      padding-top: 16px;
      margin-bottom: 8px;
      font-size: 13px;
      font-weight: 400;
      color: #404040;
      line-height: 18px;
    }
    .content-num {
      font-size: 13px;
      font-weight: 400;
      color: #333;
      line-height: 20px;
      margin: 0;
    }
    .content-time {
      @include sc(11px, #999, left);
      font-weight: 400;
      line-height: 20px;
      padding-bottom: 8px;
    }
    img {
      width: 30px;
      height: 30px;
      padding-top: 47px;
    }
  }
  .content-none {
    height: 85px;
    display: flex;
    justify-content: space-between;
    align-items: center;

    p {
      margin: 0;
    }
  }
}
.remark-wrapper {
  padding: 15px;
  .remark-title {
    font-size: 13px;
    font-weight: 400;
    color: #333333;
    line-height: 18px;
  }
  .remark {
    font-size: 12px;
    font-weight: 400;
    color: #333;
    line-height: 20px;
    margin-top: 8px;
  }
}
</style>

<template>
  <div class="bill-item">
    <span class="title">{{ title }}</span>
    <span class="sub-title">{{ fmtSubTitle }}</span>
  </div>
</template>

<script>
import { SHIPPING_STATUS } from '../static'
export default {
  name: 'BillItem',

  data() {
    return {}
  },

  props: ['title', 'subTitle'],
  computed: {
    fmtSubTitle() {
      if (this.title.endsWith('状态')) {
        let status = ''
        SHIPPING_STATUS.forEach(item => {
          if (item.id === this.subTitle) {
            status = item.name
          }
        })
        return status
      }
      if (this.title.endsWith('时间')) {
        return this.utils.formatDate('YYYY-MM-DD HH:mm', this.subTitle)
      }
      return this.subTitle
    }
  }
}
</script>

<style lang="scss" scoped>
.bill-item {
  padding: 0 15px;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-top: 15px;
  .title {
    font-size: 14px;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 20px;
    width: 115px;
  }
  .sub-title {
    width: 230px;
    text-align: right;
    font-size: 14px;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 22px;
    display: -webkit-box;
    -webkit-line-clamp: 2; // 显示两行
    /*! autoprefixer: ignore next */
    -webkit-box-orient: vertical;
    text-overflow: ellipsis;
    overflow: hidden;
  }
}
</style>

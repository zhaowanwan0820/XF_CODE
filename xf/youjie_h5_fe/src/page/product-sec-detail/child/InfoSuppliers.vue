<!-- Detailinfo.vue -->
<template>
  <div
    class="detailinfo"
    :class="['type-' + this.detailInfo.suppliers_type]"
    v-if="detailInfo && detailInfo.suppliers_type"
  >
    <template v-if="this.detailInfo.suppliers_type == s1">
      该商品为专区商品
    </template>
    <!-- <template v-else-if="this.detailInfo.suppliers_type == s2">
      商品由第三方商家提供
    </template> -->
    <template v-else-if="this.detailInfo.suppliers_type == s3">
      该商品为专区商品
    </template>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { ENUM } from '../../../const/enum'

export default {
  data() {
    return {
      s1: ENUM.SUPPLIERS_TYPE.DEFAUTL,
      s2: ENUM.SUPPLIERS_TYPE.COLLABORATOR,
      s3: ENUM.SUPPLIERS_TYPE.AGENT
    }
  },
  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    })
  }
}
</script>

<style lang="scss" scoped>
.detailinfo {
  font-size: 12px;
  height: 30px;
  line-height: 30px;
  padding-left: 80px;
  color: #666;
}
.type-1 {
  background: #f9f9f9;
  background-image: url('../../../assets/image/hh-icon/c0-goods/pointsBuy@3x.png');
  background-position: 15px center;
  background-size: 55px;
  background-repeat: no-repeat;
}
.type-2 {
  background: #fbfbfb;
  background-image: url('../../../assets/image/hh-icon/c0-goods/三方商家@3x.png');
  background-position: 15px center;
  background-size: 55px;
  background-repeat: no-repeat;
}
.type-3 {
  background: #fffcf7;
  background-image: url('../../../assets/image/hh-icon/c0-goods/pointsBuy@3x.png');
  background-position: 15px center;
  background-size: 55px;
  background-repeat: no-repeat;
}
</style>

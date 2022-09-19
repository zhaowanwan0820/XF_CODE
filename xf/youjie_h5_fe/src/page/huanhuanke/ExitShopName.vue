<template>
  <div>
    <mt-header class="header" title="小店昵称">
      <header-item slot="left" :isBack="true" @onclick="goBack"></header-item>
    </mt-header>
    <div class="shop-name">
      <div class="name-wrapper">
        <input type="text" v-model="s_name" />
        <span>的小店</span>
      </div>
      <p>4～12个字符，支持中英文、数字</p>
      <button @click="saveShopName">保存</button>
    </div>
  </div>
</template>
<script>
import HeaderItem from '../../components/common/HeaderItem'
import { setShopBaseInfo } from '../../api/huanhuanke'
import { mapState, mapMutations } from 'vuex'
import { Toast } from 'mint-ui'
export default {
  name: 'ExitShopName',
  data() {
    return {
      s_name: ''
    }
  },
  created() {
    this.s_name = this.shop_base_infos.shop_name
  },
  computed: {
    ...mapState({
      shop_base_infos: state => state.mystore.shop_base_infos
    })
    // 当客户没保存时，不能将信息存store，所以，这里不能直接使用get，set方式，小店迎宾语同理
  },
  methods: {
    ...mapMutations(['setShopBaseInfos']),
    goBack() {
      this.$_goBack()
    },
    saveShopName() {
      let reg = /^(\s+)|(\s+)$/g
      if (reg.test(this.s_name)) {
        Toast('小店昵称首尾不可有空格')
        return
      }
      if (this.s_name.length < 4 || this.s_name.length > 12) {
        Toast('请输入4~12位的小店昵称')
        return
      }
      setShopBaseInfo({ shop_name: this.s_name }).then(res => {
        this.$router.replace({ name: 'shopInfo' })
        this.shop_base_infos.shop_name = this.s_name
        this.setShopBaseInfos(this.shop_base_infos)
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.header {
  @include header;
  border-bottom: 0.5px solid rgba(85, 46, 32, 0.2);
}
.shop-name {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-weight: 400;

  .name-wrapper {
    margin-top: 44px;
    width: 355px;
    height: 46px;
    background: #fff;
    border-radius: 2px;
    position: relative;

    input {
      width: 282px;
      height: 100%;
      border: none;
      box-sizing: border-box;
      padding: 10px 13px;
    }

    span {
      position: absolute;
      right: 12px;
      font-size: 19px;
      color: #999;
      // width: 1%;
      white-space: nowrap;
      padding: 10px 0;
      height: 26px;
      line-height: 26px;
      letter-spacing: 1px;
    }
  }

  p {
    width: 345px;
    margin-top: 8px;
    height: 17px;
    font-size: 12px;
    color: #888;
    line-height: 17px;
  }

  button {
    margin-top: 77px;
    width: 327px;
    height: 46px;
    background: #772508;
    border-radius: 2px;
    color: #fff;
    font-size: 18px;
  }
}
</style>

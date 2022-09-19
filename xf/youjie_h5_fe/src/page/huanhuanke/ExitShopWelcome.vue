<template>
  <div>
    <mt-header class="header" title="小店迎宾语">
      <header-item slot="left" :isBack="true" @onclick="goBack"></header-item>
    </mt-header>
    <div class="shop-welcome">
      <p>分享小店后，营销文案可以被顾客看到哦～</p>
      <div class="welcome-wrapper">
        <!-- <input type="textarea" rows="4"/> -->
        <mt-field type="textarea" rows="4" v-model="s_desc"></mt-field>
        <span>{{ s_desc.length }}/30</span>
      </div>
      <button @click="saveShopDesc">保存</button>
    </div>
  </div>
</template>
<script>
import HeaderItem from '../../components/common/HeaderItem'
import { setShopBaseInfo } from '../../api/huanhuanke'
import { mapState, mapMutations } from 'vuex'
import { Toast } from 'mint-ui'
export default {
  name: 'ExitShopWelcome',
  data() {
    return {
      s_desc: ''
    }
  },
  created() {
    this.s_desc = this.shop_base_infos.shop_desc
  },
  computed: {
    ...mapState({
      shop_base_infos: state => state.mystore.shop_base_infos
    })
  },
  methods: {
    ...mapMutations(['setShopBaseInfos']),
    goBack() {
      this.$_goBack()
    },
    saveShopDesc() {
      let reg = /^(\s+)|(\s+)$/g
      if (reg.test(this.s_desc)) {
        Toast('迎宾语首尾不可有空格')
        return
      }
      if (this.s_desc.length > 30) {
        Toast('迎宾语不得超过30个字')
        return
      }
      setShopBaseInfo({ shop_desc: this.s_desc }).then(res => {
        this.shop_base_infos.shop_desc = this.s_desc
        this.setShopBaseInfos(this.shop_base_infos)
        this.$_goBack()
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
.shop-welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-weight: 400;

  p {
    margin: 14px 0;
    width: 347px;
    height: 17px;
    font-size: 12px;
    color: #888;
    line-height: 17px;
  }

  div.welcome-wrapper {
    width: 355px;
    position: relative;

    span {
      position: absolute;
      color: rgba(64, 64, 64, 0.4);
      right: 10px;
      bottom: 8px;
      font-size: 14px;
    }
  }

  button {
    margin-top: 49px;
    width: 327px;
    height: 46px;
    background: #772508;
    border-radius: 2px;
    color: #fff;
    font-size: 18px;
  }
}
</style>
<style lang="scss">
.welcome-wrapper {
  .mint-field {
    border-radius: 2px;
    .mint-cell-value {
      border: none;
    }
    textarea.mint-field-core {
      font-size: 14px;
    }
  }
}
</style>

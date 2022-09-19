<template>
  <div class="container">
    <p class="title">请选择需进行资产确权的机构</p>
    <template v-for="item in list">
      <div class="plat-wrapper" :key="item.platform_id" @click="goConfirm(item.platform_id)">
        <p class="name">{{ item.name }}</p>
        <div class="info">
          <label>未确权待还本金总额</label>
          <span class="unit">¥</span>
          <span class="num">{{ item.wait_money }}</span>
        </div>
      </div>
    </template>
    <div class="btn">
      <button @click="addNew">新增确权机构</button>
    </div>
  </div>
</template>
<script>
import { getUser, getPlatformConfirm } from '../../api/mine'
import { mapState, mapMutations } from 'vuex'
export default {
  name: 'choosePlatForConfirm',
  data() {
    return {
      list: [],
      plat_list: []
    }
  },
  computed: {
    ...mapState({})
  },
  created() {
    this.getUserInfo()
  },
  methods: {
    ...mapMutations({
      saveAuthPlateInfo: 'saveAuthPlateInfo'
    }),
    getUserInfo() {
      this.$loading.open()
      let p0 = getPlatformConfirm()
      let p1 = getUser()
      Promise.all([p0, p1])
        .then(res => {
          if (res[0].data['1']) this.list = [res[0].data['1']]
          this.plat_list = [...res[1].data.bindPlatform]
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    goConfirm(id) {
      let item = this.plat_list.filter(item => {
        return id === item.platform_id
      })[0]
      this.saveAuthPlateInfo(item)
      this.$router.push({ name: 'confirmation' })
    },
    addNew() {
      this.$router.push({ name: 'AuthChooseOgnztion' })
    }
  }
}
</script>

<style lang="less" scoped>
.container {
  box-sizing: border-box;
  height: 100%;
  padding: 15px;
  position: relative;
  .title {
    font-size: 12px;
    line-height: 17px;
  }
}
.plat-wrapper {
  background-color: #fff;
  margin-top: 15px;
  padding: 16px 15px 15px;
  .name {
    font-size: 16px;
    color: #333;
    line-height: 22px;
  }
  .info {
    margin-top: 15px;
    display: flex;
    align-items: flex-end;
    label {
      font-size: 14px;
      color: #999;
      line-height: 20px;
    }
    .unit {
      margin-left: 10px;
      font-size: 14px;
      line-height: 20px;
    }
    .num {
      margin-left: 5px;
      font-size: 18px;
      line-height: 23px;
    }
  }
}
.btn {
  position: absolute;
  bottom: 138px;
  left: 0;
  right: 0;
  text-align: center;
  button {
    width: 280px;
    height: 45px;
    background: @themeColor;
    border-radius: 6px;
    font-size: 16px;
    color: #fff;
  }
}
</style>

<template>
  <div v-if="popupVisible" class="home-popup-wrapper">
    <mt-popup v-model="popupVisible" position="center">
      <div>
        <div class="title">
          <img src="../../../assets/image/hh-icon/plus-close.png" @click="close" />
        </div>
        <img :src="imgSrc" @click="goPage" />
      </div>
    </mt-popup>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { HOME_POPUP, HOME_POPUP_2020 } from './const'

export default {
  name: 'HomePopup',
  data() {
    return {
      popupVisible: false,
      popupEvent: ''
    }
  },

  created() {
    this.checkPopup()
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user
    }),
    imgSrc() {
      return (HOME_POPUP[this.popupEvent] && HOME_POPUP[this.popupEvent]['src']) || HOME_POPUP_2020['offline']['src']
    }
  },

  methods: {
    ...mapMutations({
      setIsTop: 'SET_IS_TOP'
    }),
    ...mapActions({
      fetchUserInfos: 'fetchUserInfos'
    }),
    /**
     * 检测是否弹窗
     */
    checkPopup() {
      if (this.isOnline) {
        // 敬老专区 弹出
        this.fetchUserInfos().then(data => {
          // console.log(data)
          this.popupEvent = data.popup == 2 || data.popup == 5 || data.popup == 6 || data.popup == 7 || data.popup == 8|| data.popup == 9 || data.popup == 999 ? 'meiJingOne2One' : data.popup == 3 ? 'respectToOldMan' :  ''
          // this.popupEvent = data.popup == 2 ? 'meiJingOne2One' : data.popup == 3 ? 'respectToOldMan' : data.popup == 5 ? 'meiJingOne2One' : data.popup == 6 ? 'limitedSelection' : data.popup == 7 ? 'meiJingOne2One' : data.popup == 8 ? 'meiJingOne2One' : ''

          this.popupVisible = this.popupEvent ? true : false
        })
      } else {
        this.popupVisible = HOME_POPUP_2020.switch
        // 其他弹窗
        // this.normalPopup()
      }
    },

    normalPopup() {
      let popup_storage = (window.localStorage.getItem('h_p_l') || '').split(',')

      // 当天没有弹过就弹出
      if (popup_storage.length > 0) {
        if (popup_storage[0] < this.dayStart || popup_storage[0] > this.dayEnd) {
          popup_storage = []
        }
      }

      // 弹过3次不再弹出
      if (popup_storage.length >= 3) {
        return
      }
      let now = new Date().getTime()
      let last = popup_storage[popup_storage.length - 1] || 0

      // 距离上次不足10分钟不弹出
      if (popup_storage.length > 0 && now - last <= 10 * 60 * 1000) {
        return
      }

      // 弹出并记录localStorage
      this.$nextTick(() => {
        this.popupVisible = true
        popup_storage.push(now)
        window.localStorage.setItem('h_p_l', popup_storage)
      })
    },

    close() {
      this.popupVisible = false
    },

    goPage() {
      this.setIsTop(false)
      this.popupVisible = false
      let url = (HOME_POPUP[this.popupEvent] && HOME_POPUP[this.popupEvent]['url']) || HOME_POPUP_2020['offline']['url']
      if (url) window.location.href = url
    }
  }
}
</script>

<style lang="scss" scoped>
.home-popup-wrapper {
  .mint-popup {
    background: transparent;
  }
  .title {
    font-size: 0;
    text-align: right;
    img {
      width: 26px;
    }
  }
  img {
    width: 270px;
  }
  /deep/ .v-modal {
    opacity: 0.55;
  }
}
</style>

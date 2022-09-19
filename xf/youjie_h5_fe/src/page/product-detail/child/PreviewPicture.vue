<!-- PreviewPicture.vue -->
<template>
  <div v-if="photosList && photosList.length">
    <mt-popup v-model="isshow" popup-transition="popup-fade">
      <div class="preview-picture">
        <div class="picture-body">
          <mt-swipe
            :auto="0"
            :show-indicators="true"
            :default-index="defaultindex"
            class="ui-common-swiper"
            :prevent="false"
            :stop-propagation="true"
            @change="handleChange"
          >
            <mt-swipe-item v-for="(item, index) in photosList" v-bind:key="index">
              <img
                v-lazy="{
                  src: item.large,
                  error: require('../../../assets/image/change-icon/default_image_02@3x.png'),
                  loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-large.png')
                }"
                @click="closePopup()"
              />
            </mt-swipe-item>
            <mt-swipe-item v-if="!photosList || photosList.length <= 0">
              <img
                src="../../../assets/image/change-icon/default_image_02@3x.png"
                class="product-img"
                @click="closePopup()"
              />
            </mt-swipe-item>
          </mt-swipe>
        </div>
      </div>
    </mt-popup>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  // data() {
  //   return {
  //     currentIndex: this.defaultindex
  //   }
  // },

  props: {
    isshow: {
      type: Boolean,
      default: false
    },
    defaultindex: {
      type: Number,
      default: 0
    }
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo,
      imagePopupType: state => state.product.imagePopupType,
      img_arr: state => state.product.comments.img_arr
    }),
    photosList() {
      let photosArr
      switch (this.imagePopupType) {
        case 'comments':
          photosArr = this.img_arr
          break
        default:
          photosArr = this.detailInfo.photos
      }
      return photosArr
    }
  },

  methods: {
    ...mapMutations({
      setisPreviewPicture: 'setisPreviewPicture'
    }),

    /*
      handleChange: 
      @params: index 当前滑动的index
     */
    handleChange(index) {
      // this.currentIndex = index
    },

    /*
     *  closePopup: 关闭图片预览
     */
    closePopup() {
      this.setisPreviewPicture(false)
      // this.$parent.$emit('hide-priview-picture', false)
    }
  }
}
</script>

<style lang="scss" scoped>
.swipe-wrapper {
  width: 100%;
}
.mint-popup {
  width: 100%;
  height: 100%;
  background-color: #000;
}

.mint-swipe,
.mint-swipe-items-wrap {
  position: static;
}
.preview-picture {
  width: 100%;
  height: 100%;
  position: fixed;
  z-index: 10;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: #000;

  .picture-body {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    align-content: center;
    align-items: center;
  }
}
</style>

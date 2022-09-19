<!-- Detailinfo.vue -->
<template>
  <div
    class="serve-tag"
    v-if="serveTags && serveTags.length > 0"
    @click="showServeTag"
    v-stat="{ id: 'infos_btn_service' }"
  >
    <span class="serve-tag-header">服务</span>
    <div class="serve-tag-body">
      <template v-for="item in serveTags">
        <span v-if="item.banner" :key="item.id">{{ item.banner }}</span>
      </template>
    </div>
    <img src="../../../assets/image/change-icon/icon_more.png" />
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'

export default {
  data() {
    return {}
  },
  computed: {
    ...mapState({
      serveTags: state => state.detail.detailInfo.base_set
    })
  },
  methods: {
    ...mapMutations({
      saveServeTagPopupState: 'saveServeTagPopupState'
    }),

    showServeTag() {
      this.saveServeTagPopupState(true)
    }
  }
}
</script>

<style lang="scss" scoped>
.serve-tag {
  margin-top: 10px;
  height: 50px;
  display: flex;
  justify-content: space-around;
  align-items: center;
  background: #fff;
  padding: 0 15px;
  .serve-tag-header {
    color: #999999;
    font-size: 14px;
  }
  .serve-tag-body {
    flex: 1;
    margin-left: 10px;
    font-size: 13px;
    color: #666666;
    span {
      letter-spacing: 1px;
      font-weight: bold;
      & + span {
        &:before {
          display: inline-block;
          content: '·';
          text-align: center;
          width: 12px;
        }
      }
    }
  }
  img {
    width: 19px;
  }
}
</style>

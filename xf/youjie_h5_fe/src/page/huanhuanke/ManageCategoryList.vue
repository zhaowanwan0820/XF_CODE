<template>
  <div>
    <mt-header class="header" title="分类列表管理">
      <header-item slot="left" :isBack="true" @onclick="goBack"></header-item>
    </mt-header>
    <div class="sort-category-list" ref="sort_category_list">
      <div
        :class="{ 'category-wrapper': true, 'can-move': item.cat_id !== 9999 }"
        v-for="item in categorylists_copy"
        :key="item.cat_id"
        :drag-id="item.cat_id + ':' + item.cat_name"
      >
        {{ item.cat_name }}
        <img
          v-if="item.cat_id !== 9999"
          class="blank-wrapper"
          src="../../assets/image/hh-icon/myStore/icon-blank@2x.png"
          alt=""
          @click="exitCatName(item.cat_name, item.cat_id)"
        />
        <img
          v-if="item.cat_id !== 9999"
          class="move-wrapper"
          src="../../assets/image/hh-icon/myStore/icon-move@2x.png"
          alt=""
        />
      </div>
    </div>
    <button @click="saveCategorySort">保存</button>
  </div>
</template>
<script>
import Sortable, { MultiDrag, Swap } from 'sortablejs'
import HeaderItem from '../../components/common/HeaderItem'
import { manageCategoryList } from '../../api/huanhuanke'
import { mapState, mapMutations } from 'vuex'
import { MessageBox, Toast } from 'mint-ui'
export default {
  name: 'ManageCategoryList',
  data() {
    return {
      isChangeCatName: false,
      categorylists_copy: [], // clone分类，然后统一提交给后台
      send_category: [] // 排序后数组
    }
  },
  created() {
    this.categorylists_copy = JSON.parse(JSON.stringify(this.categorylists))
  },
  mounted() {
    let _this = this
    Sortable.create(this.$refs.sort_category_list, {
      animation: 150,
      draggable: '.can-move',
      handle: '.move-wrapper',
      filter: '.blank-wrapper', // 过滤掉不可以拖拽元素，如编辑的图片，这个问题困扰了好长时间，在移动端，图片点击无效问题
      onEnd: function(evt) {
        _this.send_category = []
        for (let i = 0; i < evt.from.children.length; i++) {
          let key_value = evt.from.children[i].getAttribute('drag-id')
          let cat_id = key_value.split(':')[0]
          let cat_name = key_value.split(':')[1]
          let obj = {}
          obj.cat_id = cat_id
          obj.cat_name = cat_name
          _this.send_category.push(obj)
        }
      }
    })
  },
  computed: {
    ...mapState({
      shop_sn: state => state.mystore.shop_base_infos.id,
      categorylists: state => state.mystore.categorylists
    })
  },
  watch: {
    categorylists_copy: {
      handler() {
        this.isChangeCatName = true
      },
      deep: true
    }
  },
  methods: {
    ...mapMutations({
      setCategoryLists: 'setCategoryLists'
    }),
    goBack() {
      this.$_goBack()
    },
    exitCatName(name, id) {
      MessageBox({
        $type: 'prompt', // => 等价于MessageBox.prompt, 但直接这么写，无法设置输入框初始值
        message: '请键入分类名',
        showInput: true,
        inputValue: name,
        showCancelButton: true
      }).then(({ value, action }) => {
        this.categorylists_copy.forEach(function(item) {
          if (item.cat_id === id) {
            item.cat_name = value
          }
        })
      })
    },
    saveCategorySort() {
      // 向后台保存排序信息
      let params = {}
      params.shop_sn = this.shop_sn
      if (!this.send_category.length) {
        this.send_category = [...this.categorylists_copy]
      }
      params.category = this.send_category
      manageCategoryList(params).then(res => {
        Toast('保存成功')
        this.$router.replace({ name: 'shopInfo' })
        this.setCategoryLists(this.send_category)
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
.sort-category-list {
  display: flex;
  flex-direction: column;
  align-items: center;

  .category-wrapper {
    width: 345px;
    height: 50px;
    background-color: #fff;
    line-height: 50px;
    text-align: center;
    font-size: 14px;
    font-family: PingFangSC-Regular;
    font-weight: 400;
    color: #552e20;
    margin-top: 8px;
    position: relative;

    img.blank-wrapper {
      position: absolute;
      width: 17px;
      height: 16px;
      top: 17px;
      right: 55px;
    }

    img.move-wrapper {
      position: absolute;
      width: 14px;
      height: 15px;
      top: 17px;
      right: 20px;
    }
  }

  div:first-child {
    margin-top: 11px;
  }

  .can-move {
  }
}

button {
  margin-top: 20px;
  margin-left: 24px;
  width: 327px;
  height: 46px;
  background: #772508;
  border-radius: 2px;
  color: #fff;
  font-size: 18px;
}
</style>

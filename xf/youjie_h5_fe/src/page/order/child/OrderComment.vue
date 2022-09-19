<!-- OrderComment.vue -->
<template>
  <div class="container">
    <!-- header -->
    <mt-header class="header" title="评价">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
      <header-item slot="right" title="发布" v-on:onclick="submit" titleColor="#552E20" v-if="!isReview"> </header-item>
      <header-item slot="right" title="发布追评" v-on:onclick="submitReview" titleColor="#552E20" v-else> </header-item>
    </mt-header>
    <!-- body -->
    <div class="body">
      <div class="order-comment-body" v-for="(item, index) in order.goods" :key="index">
        <div class="body-list">
          <div class="image">
            <img v-bind:src="item.thumb" v-if="item.thumb" />
            <img src="../../../assets/image/change-icon/default_image_02@2x.png" v-if="item.length <= 0" />
          </div>
          <div class="comment">
            <span>{{ item.name }}</span>
            <ul v-if="!isReview">
              <li class="good" v-for="(image, indexs) in IMAGE" v-on:click="changeImage(image.id)" :key="indexs">
                <img :src="image.activeImg" v-if="params.comment_rank == image.id" />
                <img :src="image.img" v-else />
                <label>{{ image.name }}</label>
              </li>
            </ul>
          </div>
        </div>
        <div class="enter">
          <textarea
            placeholder="说说你对商品的感受，对其他小伙伴会有很大帮助哦～"
            v-model="params.content"
            @blur="correctPosition()"
          ></textarea>
        </div>
        <div class="file-wrapper">
          <div class="img-item-wrapper" v-for="(item, index) in fileUrls" :key="index">
            <img
              src="../../../assets/image/hh-icon/comment/icon-delete.png"
              alt=""
              class="delete"
              @click="deleteImg(index)"
            />
            <img class="img-item" :src="item.src" alt="" />
          </div>
          <div class="add" @click="goAdd" v-if="!isFull">
            <img src="../../../assets/image/hh-icon/comment/icon-add.png" alt="" />
            <span>{{ addTxt }}</span>
          </div>
          <input type="file" ref="file" @change="selectImg" accept="image/jpg,image/png,image/jpeg" />
        </div>

        <template v-if="!isReview">
          <!-- 追评时不展示 -->
          <!-- 评分部分 -->
          <div class="star-wrapper">
            <comment-star
              class="star-item"
              title="商品评分"
              :score="params.goods_score"
              keyword="goods_score"
              @set="setStar"
            ></comment-star>
            <comment-star
              class="star-item"
              title="物流评分"
              :score="params.shipping_score"
              keyword="shipping_score"
              @set="setStar"
            ></comment-star>
            <comment-star
              class="star-item"
              title="服务评分"
              :score="params.service_score"
              keyword="service_score"
              @set="setStar"
            ></comment-star>
          </div>

          <div class="hide-name">
            <label>
              <img src="../../../assets/image/hh-icon/comment/icon-check-on.png" v-if="isCheck" alt="" />
              <img src="../../../assets/image/hh-icon/comment/icon-check-off.png" v-else alt="" />
            </label>
            <input type="checkbox" v-model="isCheck" @change="checkHide" />
            <span>匿名评价</span>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import CommentStar from './CommentStar'
import { Header, Toast, Indicator, MessageBox } from 'mint-ui'
import { orderGet, commentSave, commentAppendSave } from '../../../api/order' //评价晒单
import { IMAGE } from '../static'
import { ENUM } from '../../../const/enum'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {
      id: this.$route.query.order ? this.$route.query.order : '',
      IMAGE: IMAGE,
      order: {},
      isCheck: true, //匿名单选框选中状态
      params: {
        comment_rank: 5, // 商品评分（5=好评，3=中评，1=差评）
        content: '', //  商品感受
        goods_score: 5, // 商品评分
        shipping_score: 0, // 物流评分
        service_score: 0, // 服务评分
        hidden_name_is: 1 // 是否匿名，1=是，0=否
      },
      files: [],
      fileUrls: []
    }
  },
  created() {
    this.getOrder()
  },
  computed: {
    addTxt() {
      return this.fileUrls.length ? this.fileUrls.length + '/6' : '添加图片'
    },
    isFull() {
      // 已添加图片数量是否达到上限
      return this.fileUrls.length > 5 ? true : false
    },
    isReview() {
      // 是否为追评
      return this.$route.query.isReview
    }
  },
  components: {
    CommentStar
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    getOrder() {
      Indicator.open()
      orderGet(this.id)
        .then(res => {
          this.order = res
        })
        .finally(() => {
          Indicator.close()
        })
    },

    // 提交评论
    submit() {
      if (!this.params.shipping_score) {
        Toast('请对物流进行评分')
        return
      }
      if (!this.params.service_score) {
        Toast('请对服务进行评分')
        return
      }
      let data = { ...this.params }
      data['order_id'] = this.id
      data['img'] = this.files
      commentSave(data).then(res => {
        this.$router.push('orderSubmit')
      })
    },
    // 发布追评
    submitReview() {
      if (!this.params.content && !this.files.length) {
        Toast('请填写追评内容')
        return
      }
      let data = {}
      data['order_id'] = this.id
      data['img'] = [...this.files]
      data['content'] = this.params.content
      commentAppendSave(data).then(res => {
        this.$router.push('orderSubmit')
      })
    },

    changeImage(imageid) {
      this.params.comment_rank = imageid
    },
    correctPosition() {
      window.scroll(0, 0) // 兼容ios输入后页面不回弹到底部
    },
    setStar(key, index) {
      // 评分部分
      this.params[key] = index + 1
    },
    checkHide() {
      //选择匿名
      this.params.hidden_name_is = this.isCheck ? 1 : 0
    },

    goAdd() {
      this.$refs.file[0].click()
    },
    deleteImg(index) {
      MessageBox.confirm('确定删除图片吗', '').then(action => {
        if (action === 'confirm') {
          this.files.splice(index, 1)
          this.fileUrls.splice(index, 1)
        }
      })
    },
    // 选择上传图片
    selectImg() {
      const files = this.$refs.file[0].files
      if (!files.length || this.isFull) return

      const list = this.$refs.file[0].files
      const item = {
        name: list[0].name,
        file: list[0]
      }
      let file = files[0]
      let name = files[0].name
      let that = this

      let reader = new FileReader() //异步读取计算机文件信息
      reader.readAsDataURL(file)
      reader.onload = function(e) {
        that.$set(item, 'src', e.target.result)
        that.fileUrls.push(item)
        that.utils.getImgToBase64(e.target.result, function(data) {
          that.files.push(data)
        })
      }

      this.$refs.file.value = ''
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  height: 100%;

  .header {
    @include header;
    border-bottom: 1px solid #e8eaed;
  }
  .body {
    flex-grow: 1;
    overflow-y: auto;
    .order-comment-body {
      box-sizing: border-box;
      height: 100%;
      background: rgba(255, 255, 255, 1);
      padding: 15px;
      .body-list {
        display: flex;
        padding-bottom: 15px;
        @include thin-border(#e8eaed);
      }
      .image {
        width: 80px;
        height: 80px;
        background-color: #fff;
        border-radius: 2px;
        img {
          width: 80px;
          height: 80px;
        }
      }
      .comment {
        flex: 1;
        padding-left: 15px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        span {
          font-size: 13px;
          font-weight: 300;
          color: rgba(64, 64, 64, 1);
          line-height: 16px;

          word-break: break-word;
          overflow: hidden;
          text-overflow: ellipsis;
          display: -webkit-box;
          /*! autoprefixer: ignore next */
          -webkit-box-orient: vertical;
          -webkit-line-clamp: 2;
        }
        ul {
          display: flex;
          justify-content: space-between;
          align-content: center;
          align-items: center;
          li {
            display: flex;
            align-items: center;
            img {
              margin-right: 7px;
              width: 25px;
              height: 25px;
              flex-shrink: 0;
            }
            label {
              font-size: 14px;
              color: #404040;
            }
          }
        }
      }
      .enter {
        padding: 9px 0;
        textarea {
          width: 100%;
          height: 120px;
          box-sizing: border-box;
          padding: 10px;
          border: none;
          font-size: 14px;
          line-height: 20px;
          -webkit-appearance: none;
          outline: none;
          &::-webkit-input-placeholder {
            color: #bbbbbb;
          }
        }
      }
      .file-wrapper {
        display: flex;
        flex-wrap: wrap;
        @include thin-border(#f4f4f4);
        padding-bottom: 15px;
        .img-item-wrapper {
          position: relative;
          @include wh(80px, 80px);
          border-radius: 2px;
          margin: 0 15px 15px 0;
          // margin-right: 15px;
          .delete {
            position: absolute;
            @include wh(18px, 18px);
            top: -5px;
            right: -4px;
          }
          .img-item {
            @include wh(80px, 80px);
          }
        }
        .add {
          display: flex;
          flex-direction: column;
          align-items: center;
          width: 80px;
          height: 80px;
          border: 1px dotted rgba(85, 46, 32, 0.3);
          img {
            width: 30px;
            height: 24px;
            margin-top: 16px;
          }
          span {
            font-size: 14px;
            font-weight: 400;
            color: #bbbbbb;
            line-height: 20px;
            margin-top: 10px;
          }
        }
        input[type='file'] {
          display: none;
        }
      }
      .star-wrapper {
        padding: 20px 0;
        @include thin-border(#f4f4f4);
        .star-item {
          margin-bottom: 25px;
          &:nth-last-child(1) {
            margin-bottom: 0;
          }
        }
      }
      .hide-name {
        position: relative;
        padding-top: 19px;
        display: flex;
        align-items: center;
        label {
          font-size: 0;
          @include wh(14px, 14px);
          margin-right: 7px;
          img {
            @include wh(14px, 14px);
          }
        }
        input {
          position: absolute;
          opacity: 0;
          @include wh(14px, 14px);
        }
        span {
          font-size: 14px;
          font-weight: 400;
          color: #404040;
          line-height: 20px;
        }
      }
    }
  }
}
</style>

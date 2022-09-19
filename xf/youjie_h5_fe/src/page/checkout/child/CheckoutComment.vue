<template>
  <div class="comment-container">
    <label class="title">买家留言：</label>
    <div class="bottom-wrapper">
      <textarea
        rows="1"
        placeholder="给商家留言，最多100个字"
        @input="onInput($event.target.value)"
        ref="liuyan"
        @focus="keepScroll()"
        :value="item.comment"
      ></textarea>
    </div>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'
export default {
  props: {
    item: {
      type: Object
    }
  },
  model: {
    prop: 'item',
    event: 'input'
  },
  methods: {
    onInput(val) {
      this.$emit('input', { ...this.item, comment: val })
    },
    keepScroll() {
      this.$refs.liuyan.scrollIntoView()
    }
  }
}
</script>

<style lang="scss" scoped>
.comment-container {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  background-color: #fff;
  margin-top: 0;
  height: 43px;
  padding: 0 15px;
  @include thin-border(#f4f4f4, 15px);
  .title {
    font-size: 13px;
    line-height: 18px;
    color: $baseColor;
    font-weight: 300;
  }
  .bottom-wrapper {
    flex: 1;
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    align-items: stretch;
    textarea {
      flex: 1;
      font-size: 13px;
      background-color: #fff;
      padding: 0;
      border: none;
      resize: none;
      color: $subbaseColor;
      &::-webkit-input-placeholder {
        color: #c0c0c0;
        font-size: 13px;
      }
      &:focus {
        outline-style: none;
      }
    }
  }
}
</style>

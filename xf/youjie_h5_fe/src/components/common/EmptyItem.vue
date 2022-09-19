<template>
  <div class="ui-empty-page">
    <img class="photo" :src="photo" />
    <div>
      <label class="title">{{ title }}</label>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmptyItem',
  props: {
    photo: {
      type: String
    },
    title: {
      type: String,
      default: '暂无内容'
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-empty-page {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
.photo {
  width: 75px;
  height: 75px;
  margin-top: -80px;
  margin-bottom: 25px;
}
.title {
  font-size: 17px;
  color: #a4aab3;
  line-height: 17px;
}
</style>

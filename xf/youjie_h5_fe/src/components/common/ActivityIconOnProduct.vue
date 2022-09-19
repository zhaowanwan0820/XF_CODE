<template>
  <span v-if="typeCode != 0" :class="getClass"></span>
</template>

<script>
export default {
  name: 'ActivityIconOnProduct',
  data() {
    return {}
  },
  props: {
    size: {
      type: String,
      default: 'small'
    },
    offset: {
      type: Boolean,
      default: false
    },
    typeCode: {
      type: Number,
      default: 0
    }
  },
  computed: {
    getClass() {
      return (
        'icon-act ' +
        (this.size == 'small' ? 'size-small ' : 'size-big ') +
        (this.offset ? 'offset ' : '') +
        'type-' +
        this.typeCode
      )
    }
  }
}
</script>

<style lang="scss" scoped>
.icon-act {
  position: absolute;
  left: 0px;
  bottom: 0px;
  background-repeat: no-repeat;
  background-size: 100%;

  &.size-small {
    width: 68px;
    height: 17px;

    &.type-1 {
      background-image: url('../../assets/image/hh-icon/activity/616/list-61601.png');
    }
    &.type-2 {
      background-image: url('../../assets/image/hh-icon/activity/616/list-61602.png');
    }
    &.type-3 {
      background-image: url('../../assets/image/hh-icon/activity/616/list-61603.png');
    }
    &.type-4 {
      background-image: url('../../assets/image/hh-icon/activity/616/list-61604.png');
    }
    &.type-5 {
      background-image: url('../../assets/image/hh-icon/activity/616/list-61605.png');
    }
  }
  &.size-big {
    width: 170px;
    height: 35px;

    &.type-1 {
      background-image: url('../../assets/image/hh-icon/activity/616/detail-61601.png');
    }
    &.type-2 {
      background-image: url('../../assets/image/hh-icon/activity/616/detail-61602.png');
    }
    &.type-3 {
      background-image: url('../../assets/image/hh-icon/activity/616/detail-61603.png');
    }
    &.type-4 {
      background-image: url('../../assets/image/hh-icon/activity/616/detail-61604.png');
    }
    &.type-5 {
      background-image: url('../../assets/image/hh-icon/activity/616/detail-61605.png');
    }
  }

  &.offset {
    left: -1px;
    bottom: -1px;
  }
}
</style>

<template>
    <div class="carzt">
        <mt-header class="header" title="汽车专区">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="carzt-body">
            <img src="../../assets/image/hh-icon/carZt/carzt1.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt2.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt3.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt4.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt5.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt6.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt7.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt8.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt9.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt10.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt11.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt12.png" alt="" />
            <img src="../../assets/image/hh-icon/carZt/carzt13.png"  @click="toDetails" alt="" />
        </div>
    </div>
</template>

<script>
export default {
    methods: {
        goBack() {
            this.$_goBack()
        },
        toDetails(){
            let id = '804'
            this.$router.push({ name: 'product', query: { id:id  } })
        }
    }
}
</script>

<style lang="scss" scoped>
.header {
  @include thin-border(#f4f4f4);
}
.carzt {
  height: 100%;
  background-color: #fff;
  display: flex;
  flex-direction: column;
}
.carzt-body {
  flex: 1;
  overflow: auto;
  font-size: 0;
  img {
    width: 100%;
    display: block;
  }
}
</style>
<template>
    <div class="container">
        <mine-title></mine-title>
        <mine-list></mine-list>
    </div>
</template>

<script>
  import { mapState, mapMutations, mapActions } from 'vuex'
  import MineTitle from './child/MineTitle'
  import MineList from './child/MineList'
  export default {
    name: 'mine',
    components: {
      MineTitle,
      MineList
    },
    
  }
   
</script>

<style lang="less" scoped>
    .container.app-container {
        background-color: #fff;
    }
</style>
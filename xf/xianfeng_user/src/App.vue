<template>
  <div id='app'>
    <!-- 用户中心首页--> 
    <router-view></router-view>
  </div>
</template>

<script> 
export default { 
  name: 'app',
  data(){
    return{

    }
  }
}
</script>

<style>
  body{
    margin: 0;
  }
  #app { 
  position: relative;
  height: 100%;
  overflow-x: hidden;
}
</style>

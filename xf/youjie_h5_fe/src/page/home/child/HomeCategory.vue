<template>
  <!-- <div class="home-category">
    <div class="home-c-col">
      <router-link
        tag="div"
        :to="{ name: 'products', query: { category: 26 } }"
        class="home-c-row home-c-group-1 appliance"
      >
        <div class="home-c-title-box">
          <p class="home-c-title">���õ���</p>
          <p class="home-c-subtitle">�Ƽ���ů����</p>
        </div>
      </router-link>
      <div class="home-c-row home-c-group-2-wrap">
        <router-link
          tag="div"
          :to="{ name: 'products', query: { category: 36 } }"
          class="home-c-col home-c-group-2 makeup"
        >
          <p class="home-c-title">��ױ����</p>
        </router-link>
        <router-link
          tag="div"
          :to="{ name: 'products', query: { category: 60 } }"
          class="home-c-col home-c-group-2 electronic"
        >
          <p class="home-c-title">��������</p>
        </router-link>
      </div>
    </div>
    <div class="home-c-col">
      <router-link
        tag="div"
        :to="{ name: 'products', query: { category: 38 } }"
        class="home-c-row home-c-group-1 fresh"
      >
        <div class="home-c-title-box">
          <p class="home-c-title">����ʳƷ</p>
          <p class="home-c-subtitle">Ӫ����ζ����</p>
        </div>
      </router-link>
      <div class="home-c-row home-c-group-2-wrap">
        <router-link
          tag="div"
          :to="{ name: 'products', query: { category: 16 } }"
          class="home-c-col home-c-group-2 costume"
        >
          <p class="home-c-title">ʱ�з���</p>
        </router-link>
        <router-link
          tag="div"
          :to="{ name: 'products', query: { category: 45 } }"
          class="home-c-col home-c-group-2 wine"
        >
          <p class="home-c-title">��ˮ��Ʒ</p>
        </router-link>
      </div>
    </div>
  </div> -->
  <div class="home-category">
    <template v-for="item in CATEGORY_CONFIG">
      <router-link
        tag="div"
        :to="{ name: 'products', query: { category: item.category } }"
        class="home-c-col home-c-group-2"
        :class="`home-${item.type}`"
      >
        <p class="home-c-title">{{ item.name }}</p>
      </router-link>
    </template>
  </div>
</template>

<script>
import { CATEGORY_CONFIG } from './const'
export default {
  name: 'HomeCategory',
  data() {
    return {
      CATEGORY_CONFIG
    }
  },
  computed: {},
  methods: {}
}
</script>

<style lang="scss" scoped>
.home-category {
  display: flex;
  padding: 15px 15px 0;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: space-between;
  background-color: #ffffff;
  .home-c-col {
    margin-bottom: 15px;
    height: 75px;
    width: 75px;
    background-color: #f9f9f9;
    background-size: 100%;
    background-position: bottom;
    background-repeat: no-repeat;
    &.home-appliance {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-appliance.png');
    }
    &.home-makeup {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-makeup.png');
    }
    &.home-foodfresh {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-foodfresh.png');
    }
    &.home-fashion {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-fashion.png');
    }
    &.home-infant {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-infant.png');
    }
    &.home-costume {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-costume.png');
    }
    &.home-sundries {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-sundries.png');
    }
    &.home-3c {
      background-image: url('../../../assets/image/hh-icon/b0-home/home-3c.png');
    }
  }
  .home-c-title {
    @include sc(12px, #404040);
    text-align: center;
    margin-top: 7px;
    line-height: 1.5;
  }
}
</style>
<!-- <style lang="scss" scoped>
  .home-category {
    padding: 15px;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    background-color: #ffffff;
    margin-bottom: 10px;
    .home-c-col {
      display: flex;
      flex: 1;
      flex-direction: column;
      justify-content: space-between;
      & + .home-c-col {
        margin-left: 5px;
      }
      & > .home-c-icon-box {
        width: 111px;
        height: 80px;
      }
    }
    .home-c-row {
      flex: 1;
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      & + .home-c-row {
        margin-top: 5px;
      }
      & > .home-c-icon-box {
        height: 64px;
        width: 141px;
      }
    }
    .home-c-group-1 {
      flex-basis: 100px;
      position: relative;
      background-repeat: no-repeat;
      background-size: 75px;
      background-position: right bottom;
      .home-c-title-box {
        position: absolute;
        top: 15px;
        left: 10px;
      }
      p.home-c-title {
        font-size: 14px;
        color: #404040;
        line-height: 1.5;
      }
      p.home-c-subtitle {
        @include sc(10px, rgba(0, 0, 0, 0.3), left center);
      }
      &.appliance {
        background-image: url('../../../assets/image/hh-icon/b0-home/home-appliance.png');
        background-color: #f9f7f6;
        p.home-c-subtitle {
          color: #876f67;
        }
      }
      &.fresh {
        background-image: url('../../../assets/image/hh-icon/b0-home/home-fresh.png');
        background-color: #f6f5fa;
        p.home-c-subtitle {
          color: #897590;
        }
      }
    }
    .home-c-group-2-wrap {
      flex-basis: 90px;
    }
    .home-c-group-2 {
      background-color: #f9f9f9;
      background-repeat: no-repeat;
      background-size: 100%;
      background-position: center bottom;
      p.home-c-title {
        @include sc(12px, #404040);
        text-align: center;
        margin-top: 7px;
        line-height: 1.5;
      }
      &.makeup {
        background-image: url('../../../assets/image/hh-icon/b0-home/home-makeup.png');
      }
      &.electronic {
        background-image: url('../../../assets/image/hh-icon/b0-home/home-electronic.png');
      }
      &.costume {
        background-image: url('../../../assets/image/hh-icon/b0-home/home-costume.png');
      }
      &.wine {
        background-image: url('../../../assets/image/hh-icon/b0-home/home-wine.png');
      }
    }
    .wine {
      background-color: #faf9f2;
      .home-c-subtitle {
        color: #9b9075;
      }
      .home-c-icon-box {
        background: url('../../../assets/image/hh-icon/b0-home/home-wine.png') no-repeat;
        background-size: 100%;
      }
    }
  }
</style> -->

<template>
  <div class="container">
    <div class="content">
      <div class="steps">
        <steps :step="3" v-if="!isFromProfile"></steps>
      </div>
      <div class="c-project">
        <div class="project-title">待还本金确权</div>
        <div class="project-content">
          <template v-for="(item, index) in project">
            <div class="project-item" @click="goConfirmProjectList(item)" :key="index">
              <p class="project-title">{{ item.name }}</p>
              <div class="has-confirm ml">
                <span>已确权</span>
                <div class="item-per">
                  <label class="fc">￥{{ utils.formatFloat(item.confirm) }}</label>
                  <label>/￥{{ item.total }}</label>
                </div>
              </div>
              <img src="../../assets/image/confirmation/icon-tip.png" alt="" />
            </div>
          </template>
        </div>
      </div>
    </div>
    <!-- <serve-icon right="12px" bottom="17px"></serve-icon> -->
  </div>
</template>
<script>
import Steps from '../../components/common/Steps'
import ServeIcon from '../../components/common/ServeIcon'
import { mapState } from 'vuex'
import { getConfirmInfo } from '../../api/confirmation.js'
import $cookie from 'js-cookie'
export default {
  name: 'Confirmation',
  data() {
    return {
      from: '',
      project: []
    }
  },
  components: {
    Steps,
    ServeIcon
  },
  created() {
    this.getInfo()
  },
  beforeRouteEnter(to, from, next) {
    if (['AuthCheckResult', 'choosePlatForConfirm'].indexOf(from.name) !== -1) {
      $cookie.set('fromConfirmation', from['name'])
    }
    if (['AuthCheckResult', 'login'].indexOf(from.name) !== -1) {
      $cookie.set('needBack', from['name'])
    }
    next()
  },
  computed: {
    ...mapState({
      p_info: state => state.auth.platInfo
    }),
    isFromProfile() {
      return this.$cookie.get('fromConfirmation') !== 'AuthCheckResult'
    }
  },
  methods: {
    getInfo() {
      this.$loading.open()
      getConfirmInfo()
        .then(res => {
          this.project = res.data.project
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    goConfirmProjectList(item) {
      this.$router.push({ name: 'confirmationList' })
    },
    goBack() {
      if (this.$cookie.get('needBack')) {
        this.$router.push({ name: 'mine' })
      } else {
        this.$_goBack()
      }
    }
  },
  beforeRouteLeave(to, from, next) {
    this.$cookie.remove('needBack')
    if (to.name === 'AuthCheckResult') {
      this.$router.push({ name: 'home' })
    }
    next()
  }
}
</script>

<style lang="less" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  flex: 1;
  overflow: auto;
}
.steps {
  margin: 22px 0 5px;
}
.confirm-title {
  margin: 15px 7px 0 12px;
  font-size: 15px;
  line-height: 17px;
  text-align: center;
}
.gray-desc {
  margin: 15px 7px 0 12px;
  font-size: 12px;
  line-height: 17px;
  color: #999;
}
.null-btn {
  margin-top: 63px;
  text-align: center;
  button {
    width: 280px;
    height: 45px;
    background: @themeColor;
    border-radius: 6px;

    font-size: 16px;
    font-weight: 400;
    color: #fff;
  }
}
.c-project {
  padding: 16px 12px 4px;
  .project-title {
    font-size: 13px;
    font-weight: 500;
    color: #666;
    line-height: 20px;
  }
  .project-content {
    margin-top: 11px;
    background: rgba(255, 255, 255, 1);
    border-radius: 6px;
    .project-item {
      padding: 12px 15px;
      position: relative;
      border-bottom: 0.5px #e5e5e5 solid;
      &:last-child {
        border: none;
      }
      img {
        width: 7px;
        height: 12px;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
      }
      .item-line {
        margin-top: 6px;
        display: flex;
        align-items: center;
        .has-confirm {
          margin-left: 30px;
        }
      }
      .has-confirm {
        display: flex;
        align-items: center;
        span {
          .sc(10px);
          color: #666;
          font-weight: 400;
          line-height: 14px;
        }
        .item-per {
          margin-left: 5px;
          font-size: 0;
          label {
            font-size: 15px;
            font-weight: 500;
            color: #999;
            line-height: 21px;
            &.fc {
              color: @themeColor;
            }
          }
        }
        &.ml {
          margin-top: 4px;
        }
        &.mt {
          margin-top: 10px;
        }
        &.ml,
        &.mt {
          label:last-child {
            margin-left: 3px;
          }
        }
      }
    }
  }
}
.van-dialog {
  width: 80%;
  overflow: auto;
  .msgbox-message {
    padding: 50px 20px 40px;
    border-bottom: 0.5px solid #eaebec;
    min-height: 36px;
    position: relative;
    background: #fff;
  }
  p {
    font-size: 14px;
    color: #333;
    text-align: left;
    margin-bottom: 10px;
  }
  .rules {
    font-size: 14px;
    color: #333;
    text-align: center;
    padding: 0;
  }
}
</style>

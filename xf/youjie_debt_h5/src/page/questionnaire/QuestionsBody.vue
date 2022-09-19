<!-- 问卷组件 -->
<template>
  <div class="q-body">
    <!-- <transition-group :name="trans_name" tag="div"> -->
    <!-- <div class="list-item" v-for="(item, index) in list" :key="item.qst_id" v-show="index == c_index"> -->
    <h3>{{ c_index + 1 }}、{{ c_question.question }}{{ question_type }}</h3>
    <template v-if="!isCompletion">
      <div
        class="q-item"
        v-for="q_item in c_question.data"
        :key="q_item.qto_id"
        :class="{ active: c_answer.indexOf(q_item.qto_id) != -1 }"
      >
        <p @click="choose(c_question.qst_id, q_item.qto_id)">{{ q_item.serial }}.{{ q_item.option }}</p>
      </div>
    </template>
    <template v-else>
      <div class="textarea-wrapper">
        <textarea name="" id="" cols="30" rows="10" v-model="answer[c_question.qst_id]"></textarea>
      </div>
    </template>
    <div class="progress">
      <van-progress :percentage="percentage" :show-pivot="false" color="#fc810c" />
    </div>
    <div class="q-footer">
      <span :class="{ opa: !c_index }" @click="preve">上一题</span>
      <div class="q-f-progress">
        <span>{{ c_index + 1 }}</span>
        <span class="gray">/{{ list.length }}</span>
      </div>
      <span :class="{ opa: isSingle }" @click="next">下一题</span>
    </div>
    <div class="btn">
      <button v-if="c_index == list.length - 1 && hasComplete" @click="submit">
        确认答案并提交
      </button>
    </div>
    <!-- </div> -->
    <!-- </transition-group> -->
  </div>
</template>

<script>
export default {
  name: 'QuestionsBody',
  data() {
    return {
      c_index: 0, // 当前答题index
      answer: {},
      timer: null, // 点击延时一秒计时器
      // trans_name: 'next',

      start: 0, // 答题开始时间
      end: 0 // 答题结束时间
    }
  },
  created() {
    this.start = this.getSysTime()
  },
  props: {
    list: {
      type: Array,
      default: []
    }
  },
  computed: {
    c_question() {
      // 当前问题
      return this.list[this.c_index]
    },
    c_answer() {
      // 当前已选选项
      return this.answer[this.c_question.qst_id] || []
    },
    c_hasComplete() {
      // 当前问题是否已完成
      if (this.isCompletion) {
        // 填空题提交
        return !!this.c_answer
      } else {
        return this.c_answer.length
      }
    },
    isSingle() {
      // 0选择题，1填空题，2多选题
      return !Number(this.c_question.type)
    },
    isCompletion() {
      // true 填空题
      return this.c_question.type == 1
    },
    percentage() {
      // 当前答题进度
      return Math.min(((Math.max(this.c_index, Object.values(this.answer).length) + 1) / this.list.length) * 100, 100)
    },
    hasComplete() {
      // 是否已完成所有题目
      return (
        Object.keys(this.answer).length === this.list.length &&
        this.answer_format.every(item => {
          return item.qto_id.length
        })
      )
    },
    answer_format() {
      let data = []
      Object.keys(this.answer).forEach(item => {
        if (this.answer[item] instanceof Array && this.answer[item].length) {
          // 选择题
          this.answer[item].forEach(a_item => {
            let obj = { qst_id: null, qto_id: null }
            obj.qst_id = item
            obj.qto_id = a_item
            data.push(obj)
          })
        } else {
          // 填空题
          data.push({ qst_id: item, qto_id: this.answer[item] })
        }
      })
      return data
    },
    answer_time() {
      let rest = (this.end - this.start) / 1e3
      return rest <= 0 ? 0 : rest
    },
    question_type() {
      let txt = ''
      switch (Number(this.c_question.type)) {
        case 1:
          txt = '填空题'
          break
        case 2:
          txt = '多选题'
          break
        default:
          txt = '单选题'
      }
      return '(' + txt + ')'
    }
  },
  methods: {
    // 选择选项
    choose(id, value) {
      // if (this.trans_name !== 'next') this.trans_name = 'next'
      if (this.isSingle) {
        // 单选
        this.singleChoose(id, value)
      } else {
        // 多选
        this.multChoose(id, value)
      }
    },
    singleChoose(id, value) {
      // 第一次选择 使用this.$set添加新属性
      if (this.answer[id]) {
        this.answer[id] = [value]
      } else {
        this.$set(this.answer, id, [value])
      }

      if (this.timer) clearTimeout(this.timer)
      this.timer = setTimeout(() => {
        this.next()
      }, 400)
    },
    multChoose(id, value) {
      // 第一次选择 使用this.$set添加新属性
      if (this.answer[id]) {
        if (this.answer[id].indexOf(value) == -1) {
          // 选中
          this.answer[id].push(value)
        } else {
          // 取消
          this.answer[id].splice(this.answer[id].indexOf(value), 1)
        }
      } else {
        this.$set(this.answer, id, [value])
      }
    },
    next() {
      if (!this.c_hasComplete) return
      // 下一题
      if (this.c_index < this.list.length - 1) this.c_index++
    },
    preve() {
      // 上一题
      // if (this.trans_name !== 'preve') this.trans_name = 'preve'
      if (this.c_index > 0) this.c_index--
    },
    submit() {
      // 提交
      if (!this.end) this.end = this.getSysTime()
      this.$emit('submit', { answerArr: this.answer_format, answer_time: this.answer_time })
    }
  }
}
</script>
<style lang="less" scoped>
.q-body {
  height: 100%;
  background-color: #fff;
  h3 {
    padding: 25px 27px 24px 25px;
    font-size: 18px;
    font-weight: 500;
    line-height: 25px;
  }
  .q-item {
    padding: 13px 25px 22px;
    p {
      font-size: 16px;
      line-height: 22px;
    }
    &.active {
      background-color: @themeColorOpacity3;
      color: @themeColor;
    }
  }
  .textarea-wrapper {
    padding: 0 25px;
    margin-top: -7px;
    textarea {
      resize: none;
      width: 325px;
      height: 159px;
      border: 1px solid rgba(153, 153, 144, 0.5);
    }
  }
  .progress {
    padding: 0 25px;
    margin-top: 13px;
    .van-progress {
      background-color: @themeColorOpacity3; // 轨道底色
    }
  }
  .q-footer {
    padding: 0 25px;
    margin-top: 17px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    span {
      font-size: 14px;
      color: @themeColor;
      line-height: 20px;
      &.gray {
        color: #999;
      }
      &.opa {
        opacity: 0;
      }
    }
  }
  .btn {
    margin-top: 107px;
    text-align: center;
    button {
      width: 280px;
      height: 45px;
      background: @themeColor;
      border-radius: 6px;

      color: #fff;
      font-size: 16px;
      line-height: 22px;
      &.disabled {
        pointer-events: none;
      }
    }
  }
}

// 过度css
// .next-leave-active {
//   position: absolute;
//   transition: all 0.8s;
// }

// .next-leave-to {
//   transform: translateX(-375px);
// }

// .preve-enter-active {
//   position: absolute;
//   transition: all 0.8s;
// }
// .preve-enter {
//   transform: translateX(-375px);
// }
// .preve-enter-to {
//   transform: translateX(0);
// }
// .preve-leave-active {
//   opacity: 1;
//   transition: opacity 0.8s;
// }
</style>

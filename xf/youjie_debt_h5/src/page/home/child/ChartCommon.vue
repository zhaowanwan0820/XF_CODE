<template>
  <div class="chart-wrapper">
    <canvas ref="chartMountNode"></canvas>
  </div>
</template>

<script>
import F2 from '@antv/f2'

export default {
  name: 'ChartCommon',
  data() {
    return {
      f2Instance: null
    }
  },
  props: {
    chartData: Array,
    type: {
      type: String,
      default: 'timeCat'
    }
  },
  watch: {
    chartData: function(val) {
      if (!this.f2Instance) {
        this.initChart(val)
      } else {
        this.f2Instance.changeData(val)
      }
    }
  },
  mounted() {
    this.chartData.length && this.initChart(this.chartData)
  },
  methods: {
    initChart(data) {
      // 初始化
      const chart = (this.f2Instance = new F2.Chart({
        id: this.$refs.chartMountNode,
        pixelRatio: window.devicePixelRatio
      }))

      // 数据
      chart.source(data, {
        xdata: {
          type: this.type,
          sortable: false,
          tickCount: 3,
          range: [0, 1]
        },
        ydata: {
          tickCount: 5,
          min: 0,
          formatter: val => {
            return Number(val).toFixed(2)
          }
        }
      })
      // 坐标轴 X
      chart.axis('xdata', {
        labelOffset: 12,
        line: { stroke: '#F2F2F6' },
        label: function(text, index, total) {
          const conf = { textAlign: index === 0 ? 'left' : index === total - 1 ? 'right' : null }
          conf['fontSize'] = 12
          // conf['stroke'] = 'rgba(9, 23, 51, 1)'
          return conf
        }
      })
      // 坐标轴 Y
      chart.axis('ydata', {
        labelOffset: 12,
        line: { stroke: '#F2F2F6' },
        label: function(text, index, total) {
          const conf = {}
          conf['fontSize'] = 12
          // conf['stroke'] = 'rgba(9, 23, 51, 1)'
          return conf
        },
        grid: {
          lineDash: null,
          stroke: '#F2F2F6',
          lineWidth: 1
        }
      })
      // 提示信息
      chart.tooltip({
        showItemMarker: false,
        showCrosshairs: true,
        crosshairsStyle: {
          stroke: '#fc810c',
          lineWidth: 1
        },
        onShow: function(ev) {
          var items = ev.items
          items[0].name = null
          items[0].value = '折扣（' + items[0].value + '）/' + items[0].title
          items.splice(1) //F2 bug... fix by 只保留一个 item
        }
      })
      // 开始画图
      // 区域渐变
      // chart
      //   .area()
      //   .position('xdata*ydata')
      //   // .shape('smooth')
      //   .style({
      //     fill: 'l(90) 0:#04B1A4 1:#ffffff',
      //     fillOpacity: 0.1
      //   })
      // 曲线
      chart
        .line()
        .position('xdata*ydata')
        .color('#fc810c')
        // .shape('smooth')
        .style({
          lineWidth: 2
        })
      // 点
      chart
        .point()
        .position('xdata*ydata')
        .style({
          fill: '#ffffff',
          stroke: '#fc810c',
          lineWidth: 1
        })
      // 渲染
      chart.render()
    }
  }
}
</script>

<style lang="less" scoped>
.chart-wrapper {
  background-color: #fff;
  display: flex;
  justify-content: space-around;
  padding: 10px 0 5px;

  canvas {
    width: 370px;
    height: 200px;
  }
}
</style>

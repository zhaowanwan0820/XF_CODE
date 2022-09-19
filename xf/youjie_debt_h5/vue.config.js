const path = require('path')

// 记录编译时间
process.env.VUE_APP_BUILDTIME = new Date().getTime()

const isProd = 'production' === process.env.NODE_ENV

module.exports = {
  lintOnSave: !isProd,
  outputDir: '../ecshop/debt',
  assetsDir: 'static',
  publicPath: isProd ? 'https://cdn.youjiemall.com/debt/' : '/',
  productionSourceMap: false,

  devServer: {
    host: process.env.VUE_APP_DEVSERVER_HOST,
    port: process.env.VUE_APP_DEVSERVER_PORT,
    overlay: {
      warnings: false,
      errors: true
    },
    proxy: {
      // Java接口不支持开发环境的跨域调试，so proxy it...
      '/confirmation': {
        target: 'https://wxcheck.huanhuanyiwu.com',
        changeOrigin: true,
        secure: false,
        cookieDomainRewrite: 'localhost'
      }
    }
  },

  css: {
    sourceMap: isProd ? false : true,
    loaderOptions: {
      less: {
        globalVars: {
          // 前置 http://lesscss.org/usage/#less-options Modify Variables
          hack: `true; @import "~@/assets/style/variable.less"; @import "~@/assets/style/mixin.less";`
        },
        modifyVars: {
          // 后置
          hack: `true; @import "~@/theme/theme.less";`
        }
      }
    }
  },

  chainWebpack: config => {
    if (isProd) {
      // 图片 6kb 以下base64 else file-loader
      config.module
        .rule('images')
        .use('url-loader')
        .loader('url-loader')
        .tap(options => ({ ...options, limit: 6144 }))

      // svg 6kb 以下使用svg-url-loader encode else file-loader
      const svgRule = config.module.rule('svg')
      // 清除已有的所有 loader
      svgRule.uses.clear()
      // 添加要替换的 loader
      svgRule
        .use('svg-url-loader')
        .loader('svg-url-loader')
        .options({
          limit: 6144,
          noquotes: true,
          iesafe: true,
          name: 'static/img/[name].[hash:8].[ext]' // 这个配置搞得好痛苦。。。 static 为 上面的assetsDir配置
        })

      // 图片压缩
      config.module
        .rule('images')
        .use('image-webpack-loader')
        .loader('image-webpack-loader')
        .options({
          bypassOnDebug: true
        })
        .end()

      // 去除console代码：drop_console in 'terser-webpack-plugin' ,当前v4版本不支持直接修改config.optimization.minimizer 的配置项，只能重新定义全新的minimizer，太丑陋，故可等待V5版本修改配置再说。。

      // 提取 manifest 文件
      // config.optimization.runtimeChunk({ name: 'manifest' })

      // 将manifest文件inline嵌入 index.html
      // config
      //  .plugin('inlineManifest')
      //  .after('html')
      //  .use(InlineManifestWebpackPlugin, 'manifest')

      // 隐藏 webpack mini-css-extract-plugin 警告信息 Conflicting order between
      config.plugin('friendly-errors').tap(args => {
        // the actual transformer defined by vue-cli-3
        const vueCli3Transformer = args[0].additionalTransformers[0]
        args[0].additionalTransformers = [
          // use the actual transformer
          vueCli3Transformer,
          // add an other transformer that 'empty' the desired error
          error => {
            const regexp = /\[mini-css-extract-plugin\]\nConflicting order between:/
            if (regexp.test(error.message)) return {}
            return error
          }
        ]
        return args
      })
    }
  }
}

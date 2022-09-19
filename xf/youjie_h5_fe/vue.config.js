const path = require('path')

// 记录编译时间
process.env.VUE_APP_BUILDTIME = new Date().getTime()
// 插件：将manifest文件inline进index.html
// const InlineManifestWebpackPlugin = require('inline-manifest-webpack-plugin')

const isProd = 'production' === process.env.NODE_ENV

module.exports = {
  lintOnSave: !isProd,
  outputDir: '../ecshop/h5',
  assetsDir: 'static',
  publicPath: isProd ? 'https://cdn.youjiemall.com/h5/' : '/',
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
      scss: {
        data: `
              @import "~@/assets/style/_variable.scss";
              @import "~@/assets/style/mixin.scss";
              @import "~@/components/style/var.scss";
              `
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
  },

  // 多页配置
  pages: {
    // 商城
    index: {
      // page 的入口
      entry: 'src/main.js',
      // 模板来源
      template: 'public/index.html',
      // 在 dist/index.html 的输出
      filename: 'index.html'
    },
    // 运营周边
    operation: {
      // page 的入口
      entry: 'src/MultiPage/Operation/main.js',
      // 模板来源
      template: 'public/operation.html',
      // 在 dist/index.html 的输出
      filename: 'operation.html'
    }
  }
}

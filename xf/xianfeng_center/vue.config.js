/**
 * Override convention configuration
 * https://cli.vuejs.org/config/
 */

const path = require('path')
const autoprefixer = require('autoprefixer')
const pxtorem = require('postcss-pxtorem')

const isProd = process.env.NODE_ENV === 'production'
const assetsDir = 'static'
function resolve(dir) {
  return path.join(__dirname, dir)
}

module.exports = {
  outputDir: 'dist',
  publicPath: '', // for relative path
  assetsDir,
  lintOnSave: !isProd,
  productionSourceMap: false,

  devServer: {
    host: process.env.VUE_APP_DEVSERVER_HOST,
    port: process.env.VUE_APP_DEVSERVER_PORT,
    overlay: {
      warnings: false,
      errors: true,
    },
    headers: { 'Access-Control-Allow-Origin': '*' },
  },
  css: {
    sourceMap: !isProd,
    loaderOptions: {
      postcss: {
        plugins: [
          autoprefixer(),
          pxtorem({
            rootValue: 16, // 换算的基数(设计图375的根字体为16)
            selectorBlackList: [], // 忽略转换正则匹配项
            propList: ['*'], // 要转换的匹配项
          }),
        ],
      },
    },
  },
  chainWebpack: config => {
    config.resolve.alias
      .set('@', resolve('src'))
      .set('assets', resolve('src/assets'))
      .set('components', resolve('src/components'))
      .set('images', resolve('src/assets/images'))

    if (isProd) {
      // 图片 6kb 以下base64 else file-loader
      config.module
        .rule('images')
        .use('url-loader')
        .loader('url-loader')
        .tap(options => ({
          ...options,
          limit: 6144,
          fallback: {
            loader: 'file-loader',
            options: {
              name: '[hash:16].[ext]',
              outputPath: `${assetsDir}/img/`,
            },
          },
        }))

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
          name: '[hash:16].[ext]',
          outputPath: `${assetsDir}/img/`,
        })

      // 图片压缩
      config.module
        .rule('images')
        .use('image-webpack-loader')
        .loader('image-webpack-loader')
        .options({
          bypassOnDebug: true,
        })
        .end()

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
          },
        ]
        return args
      })
    }
  },
}

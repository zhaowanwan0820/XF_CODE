<template>
  <div class="container">
    <mt-header class="header" title="协议">
      <header-item slot="left" v-bind:isClose="true" v-on:onclick="goProfile"></header-item>
    </mt-header>
    <div class="agreement-wrapper">
      <div class="agreement-content-wrapper">
        <div class="content-wrapper" v-on:scroll.passive="handleScroll">
          <div class="content">
            <h2>《{{ utils.storeName }}平台注册协议》</h2>
            <section v-for="(a1, i1) in rules" :key="i1">
              <template v-for="(a2, i2) in a1">
                <p class="title" v-if="typeof a2 == 'string'" :key="i2">{{ a2 }}</p>
                <template v-for="(a3, i3) in a2" v-else>
                  <p class="info" v-if="typeof a3 == 'string'" :key="i3">{{ i3 + 1 }}、{{ a3 }}</p>
                  <template v-for="(a4, i4) in a3" v-else>
                    <p class="son" :key="i4">（{{ i4 + 1 }}）{{ a4 }}</p>
                  </template>
                </template>
              </template>
            </section>
          </div>
        </div>
        <div class="content-mask mask-1" v-if="maskStatus > 0"></div>
        <div class="content-mask mask-2" v-if="maskStatus < 2"></div>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem, Button } from '../../components/common'
import { Header } from 'mint-ui'
export default {
  name: 'agreement',
  data() {
    return {
      from: this.$route.params.from || '',
      maskStatus: 0,
      rules: [
        [
          '一、服务条款的确认及接受',
          [
            `${this.utils.storeName}平台（指此处https://www.youjiemall.com/及其移动客户端软件、应用程序，以下称“本网站”）各项电子服务运作权归属于“${this.utils.storeName}”所有；本网站提供的服务将完全按照其发布的服务条款和操作规则严格执行。您确认好所有服务条款并完成注册程序时，同时您成为本网站正式用户。`,
            `根据国家法律法规变化及本网站运营需要，${this.utils.storeName}有权对本协议条款及相关规则进行相应的修改，修改后的内容会在网站公告或给您发站内信，您若不同意修改内容，可以在公示期内注销平台用户身份，若在公示期内不注销平台用户身份，视为同意修改内容，敬请多关注本网站公告、提示信息及协议、规则等相关内容的变动。`
          ]
        ],
        [
          '二、服务需知',
          [
            '基于本网站所提供的网络服务的重要性，您确认并同意：',
            [
              '提供的注册资料真实、准确、完整、合法、有效，注册资料如有变动的，应及时更新。',
              `如果您提供的注册资料不合法、不真实、不准确、不详尽的，您需承担因此引起的相应责任及后果，并且${this.utils.storeName}保留终止您使用本网站各项服务的权利。`
            ]
          ]
        ],
        [
          '三、订单',
          [
            '使用本网站下订单，您应具备购买相关商品的权利能力和行为能力，如果您在18周岁以下，您需要在父母或监护人的监护参与下才能使用本网站。在下订单的同时，即视为您满足上述条件，并对您在订单中提供的所有信息的真实性负责。',
            '在您下订单时，请您仔细确认所购商品的名称、价格、数量、型号、规格、尺寸、联系地址、电话、收货人等信息。您填写的收货人信息与您本人不一致的，收货人的行为和意思表示视为您的行为和意思表示，您应对收货人的行为及意思表示的法律后果承担连带责任。',
            '尽管销售商做出最大的努力，但由于市场变化及各种以合理商业努力难以控制因素的影响，本网站无法避免您提交的订单信息中的商品出现缺货、价格标示重大错误等情况；如您下单所购买的商品出现以上情况，您有权取消订单，或选购其他物品。'
          ]
        ],
        [
          '四、用户个人信息保护及授权',
          [
            `您知悉并同意，为方便您使用本网站相关服务，本网站将存储您在使用时的必要信息，包括但不限于您的真实姓名、性别、生日、配送地址、联系方式、通讯录、相册、日历、定位信息等。除法律法规规定的情形外，未经您的许可${this.utils.storeName}不会向第三方公开、透露您的个人信息。${this.utils.storeName}对相关信息采取专业加密存储与传输方式，利用合理措施保障用户个人信息的安全。`,
            `您知悉并确认，您在注册帐号或使用本网站的过程中，需要提供真实的身份信息，${this.utils.storeName}将根据国家法律法规相关要求，进行基于移动电话号码的真实身份信息认证。若您提供的信息不真实、不完整，则无法使用本网站或在使用过程中受到限制，同时，由此产生的不利后果，由您自行承担。`,
            '您在使用本网站某一特定服务时，该服务可能会另有单独的协议、相关业务规则等（以下统称为“单独协议”），您在使用该项服务前请阅读并同意相关的单独协议；您使用前述特定服务，即视为您已阅读并同意接受相关单独协议。',
            '您充分理解并同意：',
            [
              '接收通过邮件、短信、电话，站内信、站内公告等形式，向在本网站注册、购物的用户、收货人发送的订单信息、促销活动等内容。',
              `为配合行政监管机关、司法机关执行工作，在法律规定范围内${this.utils.storeName}有权向上述行政、司法机关提供您在使用本网站时所储存的相关信息，包括但不限于您的注册信息等，或使用相关信息进行证据保全，包括但不限于公证、见证等。`,
              `${this.utils.storeName}依法保障您在安装或使用过程中的知情权和选择权，在您使用本网站服务过程中，涉及您设备自带功能的服务会提前征得您同意，您一经确认，${this.utils.storeName}有权开启包括但不限于收集地理位置、读取通讯录、使用摄像头、启用录音等提供服务必要的辅助功能。`,
              `${this.utils.storeName}有权根据实际情况，在法律规定范围内自行决定单个用户在本网站及服务中数据的最长储存期限以及用户日志的储存期限，并在服务器上为其分配数据最大存储空间等。`
            ]
          ]
        ],
        [
          '五、用户行为规范',
          [
            '本协议依据国家相关法律法规规章制定，您同意严格遵守以下义务：',
            [
              '不得传输或发表：煽动抗拒、破坏宪法和法律、行政法规实施的言论，煽动颠覆国家政权，推翻社会主义制度的言论，煽动分裂国家、破坏国家统一的言论，煽动民族仇恨、民族歧视、破坏民族团结的言论；',
              '从中国大陆向境外传输资料信息时必须符合中国有关法规；',
              '不得利用本网站从事洗钱、窃取商业秘密、窃取个人信息等违法犯罪活动；',
              '不得干扰本网站的正常运转，不得侵入本网站及国家计算机信息系统；',
              '不得传输或发表任何违法犯罪的、骚扰性的、中伤他人的、辱骂性的、恐吓性的、伤害性的、庸俗的，淫秽的、不文明的等信息资料；',
              '不得传输或发表损害国家社会公共利益和涉及国家安全的信息资料或言论；',
              '不得教唆他人从事本条所禁止的行为；',
              '不得利用在本网站注册的账户进行牟利性经营活动；',
              '不得发布任何侵犯他人隐私、个人信息、著作权、商标权等知识产权或合法权利的内容；'
            ],
            '您须对自己在网上的言论和行为承担法律责任，您若在本网站上散布和传播反动、色情或其它违反国家法律的信息，本网站的系统记录有可能作为您违反法律的证据。'
          ]
        ],
        [
          '六、本网站使用规范',
          [
            `除非法律允许或${this.utils.storeName}书面许可，您使用本网站过程中不得从事下列行为：`,
            [
              '删除本网站及其副本上关于著作权的信息；',
              '对本网站进行反向工程、反向汇编、反向编译，或者以其他方式尝试发现本网站的源代码；',
              `对${this.utils.storeName}拥有知识产权的内容进行使用、出租、出借、复制、修改、链接、转载、汇编、发表、出版、建立镜像站点等；`,
              `对本网站或者本网站运行过程中释放到任何终端内存中的数据、网站运行过程中客户端与服务器端的交互数据，以及本网站运行所必需的系统数据，进行复制、修改、增加、删除、挂接运行或创作任何衍生作品，形式包括但不限于使用插件、外挂或非经${this.utils.storeName}授权的第三方工具/服务接入本网站和相关系统；`,
              '通过修改或伪造网站运行中的指令、数据，增加、删减、变动网站的功能或运行效果，或者将用于上述用途的软件、方法进行运营或向公众传播，无论这些行为是否为商业目的；',
              `通过非${this.utils.storeName}开发、授权的第三方软件、插件、外挂、系统，登录或使用本网站及服务，或制作、发布、传播上述工具；`,
              '自行或者授权他人、第三方软件对本网站及其组件、模块、数据进行干扰。'
            ]
          ]
        ],
        [
          '七、违约责任',
          [
            `如果${this.utils.storeName}发现或收到他人举报投诉您违反本协议约定或存在任何恶意行为的，${this.utils.storeName}有权不经通知随时对相关内容进行删除、屏蔽，并视行为情节对违规帐号处以包括但不限于警告、限制或禁止使用部分或全部功能、帐号封禁、注销等处罚，并公告处理结果。`,
            `${this.utils.storeName}有权依据合理判断对违反有关法律法规或本协议规定的行为采取适当的法律行动，并依据法律法规保存有关信息向有关部门报告等，您应独自承担由此而产生的一切法律责任。`,
            `您理解并同意，因您违反本协议或相关服务条款的规定，导致或产生第三方主张的任何索赔、要求或损失，您应当独立承担责任；${this.utils.storeName}因此遭受损失的，您也应当一并赔偿。`,
            `除非另有明确的书面说明,${this.utils.storeName}不对本网站的运营及其包含在本网站上的信息、内容、材料、产品（包括软件）或服务作任何形式的、明示或默示的声明或担保（根据中华人民共和国法律另有规定的以外）。`
          ]
        ],
        [
          '八、所有权及知识产权',
          [
            `除法律另有强制性规定外，未经${this.utils.storeName}明确的特别书面许可,任何单位或个人不得以任何方式非法地全部或部分复制、转载、引用、链接、抓取或以其他方式使用本网站的信息内容，否则，${this.utils.storeName}有权追究其法律责任。`,
            `本网站所刊登的资料信息（诸如文字、图表、标识、按钮图标、图像、声音文件片段、数字下载、数据编辑和软件），均是${this.utils.storeName}或其内容提供者的财产、本网站上所有内容的汇编是${this.utils.storeName}的排他财产、本网站上所有软件都是${this.utils.storeName}或其关联公司或其软件供应商的财产，均受法律保护。`
          ]
        ],
        [
          '九、法律管辖适用及其他',
          [
            '本协议的订立、执行和解释及争议的解决均应适用中国法律。如双方就本协议内容或其执行发生任何争议，双方应尽力友好协商解决；协商不成时，应向协议签订地有管辖权的人民法院提起诉讼。本协议签订地为中华人民共和国北京市海淀区。',
            '如果本协议中任何一条被视为废止、无效或因任何理由不可执行，该条应视为可分的且并不影响任何其余条款的有效性和可执行性。',
            `本协议未明示授权的其他权利仍由${this.utils.storeName}保留，您在行使这些权利时须另外取得${this.utils.storeName}的书面许可。${this.utils.storeName}如果未行使前述任何权利，并不构成对该权利的放弃。`
          ]
        ]
      ]
    }
  },
  methods: {
    handleScroll(e) {
      this.maskStatus = Math.ceil(
        e.target.scrollTop / (e.target.scrollHeight - Math.max(e.target.clientHeight, e.target.offsetHeight) - 9)
      )
      // 上面这行等价于下面
      // if(e.target.scrollTop == 0) {
      //   this.maskStatus = 0;
      // } else if(e.target.scrollTop >= e.target.scrollHeight - Math.max(e.target.clientHeight, e.target.offsetHeight)) {
      //   this.maskStatus = 2;
      // } else {
      //   this.maskStatus = 1;
      // }
    },
    goProfile() {
      if (this.from) {
        this.$router.replace(this.from)
      } else {
        this.$_goBack()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  width: 100%;
  height: 100%;
  background-color: $mainbgColor;
  display: flex;
  flex-direction: column;

  .header {
    @include header;
    width: 100%;
  }
  .button {
    @include button($margin: 23px 20px 28px, $radius: 23px, $spacing: 12px);
  }
  .agreement-wrapper {
    flex: 1 0 0;
    width: 100%;
    margin-top: 10px;
    padding-top: 26px;
    background-color: #fff;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  .agreement-content-wrapper {
    margin: 0 9px 0 15px;
    position: relative;
  }
  .content {
    font-size: 14px;
    line-height: 20px;
    color: $subbaseColor;
    text-align: justify;
    padding-right: 10px;
    margin-top: -15px;
    padding-bottom: 20px;
    h2 {
      margin-top: 20px;
      margin-bottom: 30px;
      text-align: center;
    }
    p {
      margin-top: 15px;
    }
    .title {
      color: $baseColor;
    }
    .info {
      text-indent: -22px;
      margin-left: 22px;
    }
    .son {
      text-indent: -37px;
      margin-left: 37px;
    }
  }
  .content-mask {
    width: 100%;
    height: 20px;
    background-color: #fff;
    position: absolute;
    left: -6px;
    z-index: 1;
    &.mask-1 {
      @include linear-vgradient(#fff, rgba(255, 255, 255, 0));
      top: 0;
    }
    &.mask-2 {
      @include linear-vgradient(rgba(255, 255, 255, 0), #fff);
      bottom: 0;
    }
  }
}
</style>

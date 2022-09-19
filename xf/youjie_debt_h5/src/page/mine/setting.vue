<template>
    <div class="container">
        <div class="setting-box">
            <div class="box" style="padding: 17px 0 9px 0;">
                <div class="setting-user">
                    <img src="../../assets/image/mine/user.jpeg" alt="" />
                    <p>{{ phone }}</p>
                </div>
                <div class="setting-text">
                    <img src="../../assets/image/mine/s-2.png" alt="" />
                </div>
            </div>
        </div>
        <div class="setting-box">
            <div class="box" @click="goToEdit">
                <div class="setting-user">
                    <span>风险评级</span>
                </div>
                <div class="setting-text">
                    <img src="../../assets/image/mine/s-2.png" alt="" />
                </div>
            </div>
        </div>
        <div class="setting-box">
            <div class="box border-b" @click="settingPass">
                <div class="setting-user">
                    <span>交易密码</span>
                </div>
                <div class="setting-text">
                    <span v-if="!is_set_pay_password">{{ text }}</span>
                    <img src="../../assets/image/mine/s-2.png" alt="" />
                </div>
            </div>
        </div>
        <div class="btn" @click="dropOut">退出登录</div>
    </div>
</template>

<script>
import { getUser } from '../../api/mine'
import { Toast } from 'vant'
export default {
    name: 'setting',
    data() {
        return {
            phone: '',
            is_set_pay_password: 0,
            text: ''
        }
    },
    computed: {},
    created() {
        this.getData()
    },
    methods: {
        getData() {
            getUser()
                .then(res => {
                    this.phone = res.data.userInfo.phone
                    this.is_set_pay_password = res.data.userInfo.is_set_pay_password
                    if (!res.data.userInfo.is_set_pay_password) {
                        this.text = '未设置'
                    }
                })
                .catch(err => {})
        },
        settingPass() {
            if (this.is_set_pay_password) {
                this.$router.push({ name: 'editpass' })
            } else {
                this.$router.push({ name: 'settpass' })
            }
        },
        goToEdit() {
            this.$router.push({ name: 'evaluationResult' })
        },
        dropOut() {
            this.$store.commit('removeuser')
            this.$router.push({ name: 'login' })
            Toast(`退出成功`)
        }
    }
}
</script>

<style lang="less" scoped>
.container {
    .setting-box {
        background-color: #fff;
        padding: 0 15px;
        .box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 17px 0;
            border-bottom: 1px dashed rgba(85, 46, 32, 0.2);
            .setting-user {
                display: flex;
                align-items: center;
                img {
                    width: 29px;
                    height: 29px;
                    border: 1px solid rgba(255, 255, 255, 0.9);
                    border-radius: 50%;
                }
                p {
                    height: 21px;
                    font-size: 18px;
                    font-family: DINAlternate-Bold, DINAlternate;
                    font-weight: bold;
                    color: rgba(64, 64, 64, 1);
                    line-height: 21px;
                    margin-left: 8px;
                }
                span {
                    height: 21px;
                    font-size: 15px;
                    font-family: PingFangSC-Regular, PingFang SC;
                    font-weight: 400;
                    color: rgba(51, 51, 51, 1);
                    line-height: 21px;
                }
            }
            .setting-text {
                display: flex;
                align-items: center;
                span {
                    height: 18px;
                    font-size: 13px;
                    font-family: PingFangSC-Regular, PingFang SC;
                    font-weight: 400;
                    color: rgba(4, 177, 164, 1);
                    line-height: 18px;
                    margin-right: 6px;
                }
                img {
                    width: 5px;
                    height: 5px;
                }
            }
        }
        .border-b {
            border-bottom: 0;
        }
    }
    .btn {
        width: 100%;
        height: 55px;
        line-height: 55px;
        text-align: center;
        background-color: #fff;
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(51, 51, 51, 1);
        margin-top: 10px;
    }
}
</style>

<template>
	<div class="">
		<div class="container entrance-wrapper">
			<div class="item transfer" @click="toSellDebt">
				<div class="text">
					<span class="title">我要转让</span>
					<p class="desc">轻松转让债权</p>
				</div>
				<div class="picture">
					<img src="../../../assets/image/home/entrance/zhuanrang-pic.png" alt="我要转让" />
				</div>
			</div>
			<div class="item plan" @click="toBuyDebt">
				<div class="text">
					<span class="title">我要认购</span>
					<p class="desc">满足债权需求</p>
				</div>
				<div class="picture">
					<img src="../../../assets/image/home/entrance/rengou-pic.png" alt="我要认购" />
				</div>
			</div>
		</div>
		<!-- <div class="checkInfo">
			<div class="wrap">
				<div class="transferInfo box" @click="myTransfer">
					<div class="icon">
						<img src="../../../assets/image/home/entrance/zhuanrang.png" alt="" />
					</div>
					<div class="text">查看我的转让</div>
					<div class="arrow">
						<img src="../../../assets/image/home/entrance/arrow-zhuanrang.png" alt="" />
					</div>
				</div>
				<div class="planInfo box" @click="mySubscription">
					<div class="icon">
						<img src="../../../assets/image/home/entrance/rengou.png" alt="" />
					</div>
					<div class="text">查看我的认购</div>
					<div class="arrow">
						<img src="../../../assets/image/home/entrance/arrow-rengou.png" alt="" />
					</div>
				</div>
			</div>
		</div> -->
	</div>
</template>

<script>
import { mapState } from 'vuex'
	export default {
		name: 'ListEntrance',
    inject: ['verifiedModal'],
		data() {
			return {}
		},
		 computed: {
			...mapState({
			isOnline: state => state.auth.isOnline
			})
		},
		methods: {
			toSellDebt() {
        if(!this.isOnline){
					window.location.href = '/#/login?from=debtMarket'
					return
				}
        const isBreak = this.verifiedModal.showVerifiedModal()
        if (isBreak) return;
        this.$router.push({
          name: 'projectList'
        })
			},
			toBuyDebt() {
        if(!this.isOnline){
					window.location.href = '/#/login?from=debtMarket'
					return
				}
        const isBreak = this.verifiedModal.showVerifiedModal()
        if (isBreak) return;
				this.$router.push({
					name: 'targetList'
				})
			},
			myTransfer(){
				 if(!this.isOnline){
					window.location.href = '/#/login?from=debtMarket'
					return
				}
				this.$router.push({
					name: 'mytransfer'
				})
			},
			mySubscription(){
				 if(!this.isOnline){
					window.location.href = '/#/login?from=debtMarket'
					return
				}
				this.$router.push({
					name: 'mysubscription'
				})
			}

		}
	}
</script>

<style lang="less" scoped>
	.container {
		background: #FFFFFF;
	}

	.entrance-wrapper {
		display: flex;
		justify-content: space-between;
		align-items: stretch;
		padding: 20px 9px 20px 9px;
	}

	.transfer {
		background: linear-gradient(315deg, rgba(237, 233, 255, 1) 0%, rgba(247, 245, 255, 1) 100%);
		border-radius: 2px;
		margin-right: 7px;

		.title {
			color: rgba(73, 67, 108, 1);
		}

		.desc {
			color: rgba(144, 136, 187, 1);
		}
	}

	.plan {
		background: linear-gradient(134deg, rgba(255, 245, 239, 1) 0%, rgba(255, 215, 190, 1) 100%);
		border-radius: 2px;

		.title {
			color: rgba(125, 74, 43, 1);
		}

		.desc {
			color: rgba(188, 155, 135, 1);
		}
	}

	.item {
		background-color: #fff;
		box-sizing: border-box;
		flex: 1 0 0;
		overflow: hidden;
		display: flex;
		align-items: stretch;
		padding-bottom: 12px;

		.text {
			flex: 1 0 0;
			padding: 30px 0 0 12px;

			.title {
				font-size: 14px;
				font-weight: bold;
				// color: rgba(64, 64, 64, 1);
				line-height: 20px;
			}

			.desc {
				margin-top: 3px;
				font-size: 12px;
				// color: rgba(153, 153, 153, 1);
				line-height: 17px;
			}
		}

		.picture {
			width: 86px;
			height: 86px;

			// flex-basis: 90px;
			// overflow: hidden;
			img {
				display: block;
				width: 100%;
			}
		}
	}

	.checkInfo {

		.wrap {
			display: flex;
			flex-direction: row;
			align-items: center;
			padding: 15px 9px;
			height: 18px;
			line-height: 18px;
			.transferInfo{
				.text{
					font-size:13px;
					font-family:PingFangSC-Light,PingFang SC;
					font-weight:300;
					color:rgba(73,67,108,1);
				}
			}
			.planInfo {
				margin-left: 7px;
				.text{
					font-size:13px;
					font-family:PingFangSC-Light,PingFang SC;
					font-weight:300;
					color:rgba(125,74,43,1);
				}
			}
		}

		.box {
			display: flex;
			flex-direction: row;
			align-items: center;
			flex: 1;

			.icon {
				width: 13px;
				height: 13px;
				margin-left: 12px;

				img {
					width: 100%;
					height: 100%;
				}
			}

			.text {
				font-size: 13px;
				font-family: PingFangSC-Light, PingFang SC;
				font-weight: 300;
				color: rgba(73, 67, 108, 1);
				flex: 1;
				margin-left: 5px;
			}

			.arrow {

				margin-right: 3px;

				img {
					width: 5px;
					height: 8px;
				}
			}
		}
	}
</style>

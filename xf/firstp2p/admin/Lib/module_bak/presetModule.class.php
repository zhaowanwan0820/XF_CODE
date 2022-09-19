<?php
FP::import("app.deal");
class presetModule extends SiteBaseModule
{
	/**
	 * 预约信息
	 * @author guomumin aaron8573@gmail.com
	 * @date 2013-8-14
	 * @see SiteBaseModule::index()
	 */
	public function index(){
		return app_redirect(url('huiying'));
	}
}
?>
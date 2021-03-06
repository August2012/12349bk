/**
 * 管理用户数据
 *
 * @author zhaozl
 * @since  2015-07-02
 */
jQuery(document).ready(function($) {

	jQuery(".stdform").validate({
		rules: {
			admin_name: "required",
			admin_password: {
				required: true,
				minlength: 6	
			},
			phone_mob: "required"
		},
		messages: {
			admin_name: "请填写用户名",
			admin_password: {
				required: true,
				minlength: "密码长度不小于6"
			},
			phone_mob: "请填写手机号"
		}
	});

});
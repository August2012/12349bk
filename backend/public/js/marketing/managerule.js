/**
 * 管理用户数据
 *
 * @author zhaozl
 * @since  2015-07-02
 */
jQuery(document).ready(function($) {

	// 初始化默认值
	if($('#hiden_types').val()) {
		var splitArray  = $('#hiden_types').val().split(',');
		$('#item_ids').val(splitArray);
		$('#item_ids').trigger('change');


		$('#timesField').html($('#hideTable').html());
		$('#hideTable').remove();

	}

	jQuery(".chzn-select").chosen({no_results_text: "找不到选项!", include_group_label_in_selected: true}).change(function(event) {
		var id = $(this).attr("id");
        if($(this).valid())
            $("#"+id+"_chosen").removeClass("error");
        else
            $("#"+id+"_chosen").addClass("error");

        if(this.id == 'item_ids') {
        	// 回去已经选择的数据项
    		var strHtml = '';
    		var selectedValue = $(this).val();
        	if($('.search-choice').length > 0) {
        		strHtml += '<table width="30%">';
        		$.each($('.search-choice'), function(index, val) {
        			var name = $(val).find('span').html();
        			var id = selectedValue[index];

        			var curTime = 1;
        			if($('input[id="times['+id+']"]').length > 0) {
	        			curTime = $('input[id="times['+id+']"]').val();
	       			}

        			strHtml += '<tr><td><span style="width: 200px;">'+name+'</span>:</td><td> <input type="text" name="times['+id+']" id="times['+id+']" value="'+curTime+'" style="width: 100px;"/>次</td></tr>';
        		});

        		strHtml += '</table>';
        	}

        	$('#timesField').html(strHtml);

        }

	});

	jQuery( "#begintime" ).datepicker({
        dateFormat: "yy-mm-dd",
        showClearButton: true
    }).change(function(event) {

        if($( "#begintime" ).datepicker( "getDate" )) {
            var currentDate1 = $( "#begintime" ).datepicker( "getDate" ).getTime()/1000;
        }
        if($( "#endtime" ).datepicker( "getDate" )) {
            var currentDate2 = $( "#endtime" ).datepicker( "getDate" ).getTime()/1000;
        }

        if(currentDate1 && currentDate1 && currentDate1 > currentDate2) {
            jAlert("开始日期必须小于结束日期");
            $( "#begintime" ).datepicker('setDate', null)
        }

    });

    jQuery( "#endtime" ).datepicker({
        dateFormat: "yy-mm-dd",
        showClearButton: true
    }).change(function(event) {
        if($( "#begintime" ).datepicker( "getDate" )) {
            var currentDate1 = $( "#begintime" ).datepicker( "getDate" ).getTime()/1000;
        }

        if($( "#endtime" ).datepicker( "getDate" )) {
            var currentDate2 = $( "#endtime" ).datepicker( "getDate" ).getTime()/1000;
        }

        if(currentDate1 && currentDate1 && currentDate1 > currentDate2) {
            jAlert("结束日期必须大于开始日期");
            $( "#endtime" ).datepicker('setDate', null)
        }

    });

	jQuery(".stdform").validate({
		ignore: ":hidden:not(select)",
        errorPlacement: function(error, element) {
            if(element.hasClass('chzn-select')) {
                var id = element.next().attr("id");
                $("#"+id).addClass("error");
                $("#"+id).after(error);
            }else{
				element.after(error);
            }
        },
		rules: {
			coupon_name: "required",
			maxnum: "required",
			prefix: "required"
		},
		messages: {
			coupon_name: "请填写券码名称",
			maxnum: "请填写最大发放量",
			prefix: "请填写券码前缀"
		}
	});

	function changeCouponType() {

		$('.p_items').removeClass('show').addClass('hide');
		$('.p_money').removeClass('show').addClass('hide');
		$('.p_minprice').removeClass('show').addClass('hide');

		$('#money').rules("remove");	
		$('#minprice').rules("remove");	
		$('#item_ids').rules("remove");	
		
		var coup_type = $('#coupon_type').val();
		switch(coup_type) {
			case '0':
				$('#money').rules("add", { required: true, messages: { required: "请填写优惠金额"} });
				$('.p_money').removeClass('hide').addClass('show');
				break;
			case '1':
				$('#money').rules("add", { required: true, messages: { required: "请填写优惠金额"} });
				$('#minprice').rules("add", { required: true, messages: { required: "请填写最低消费金额"} });
				$('.p_money').removeClass('hide').addClass('show');
				$('.p_minprice').removeClass('hide').addClass('show');

				break;
			case '2':
				$('.p_items').removeClass('hide').addClass('show');
				$('#item_ids').rules("add", { required: true, messages: { required: "请选择服务项目"} });

				break;
		}

	}

	function changeDayType() {
		var day_type = $('#canusetype').val();

		if(day_type == 1) {
			$('.p_canuseday').removeClass('hide').addClass('show');
			$('.p_begintime').removeClass('show').addClass('hide');
			$('.p_endtime').removeClass('show').addClass('hide');

			$('#canuseday').rules("add", {required: true, messages: {required: "请填写有效天数"}});
			$('#begintime').rules("remove");
			$('#endtime').rules("remove");
		}else{
			$('.p_canuseday').removeClass('show').addClass('hide');
			$('.p_begintime').removeClass('hide').addClass('show');
			$('.p_endtime').removeClass('hide').addClass('show');

			$('#canuseday').rules("remove");
			$('#begintime').rules("add", {required: true, messages: {required: "请选择开始时间"}});
			$('#endtime').rules("add", {required: true, messages: {required: "请选择结束时间"}});
		}

	}


	$('#coupon_type').change(function(event) {
		changeCouponType();
	});

	$('#canusetype').change(function(event) {
		changeDayType();
	});

	//默认加载
	changeCouponType();
	changeDayType();



});
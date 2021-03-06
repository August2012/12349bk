jQuery(document).ready(function($) {

	var coupRuleTable = jQuery('#couprulelist').dataTable({
		"bProcessing": true,
        "bServerSide": true,
        "bFilter": true,
        "bSort": true,
        "aaSorting": [[ 0, "desc" ]],
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [0, 1, 2, 3, 4 ] },
            { "bSearchable": false, "aTargets": [ 0, 1, 3, 4 ]}
        ],
        "sDom": '<"top"f>rt<"bottom"lip><"clear">',
        "aoColumns": [
			{ "sTitle": "#", "sClass" : "head0", "mDataProp" : "id" },
			{ "sTitle": "券码类型", "sClass" : "head1", "mDataProp" : "coupon_type"},
			{ "sTitle": "券码说明(名称)", "sClass" : "head0", "mDataProp" : "coupon_name" },
            { "sTitle": "券码规则", "sClass" : "head1", "mDataProp" : "rule" },
            { "sTitle": "操作", "sClass" : "head0", "mDataProp" : "coupon_id" },
        ],
        "sAjaxSource": "/marketing/getRules",
        "oLanguage": {
            "sLengthMenu": "每页 _MENU_ 记录",
            "sZeroRecords": "抱歉找不到数据",
            "sInfo": "展示 _START_ 到 _END_ ，共 _TOTAL_ 数据",
            "sInfoEmpty": "0条记录展示",
            "sInfoFiltered": "(从 _MAX_ 记录中筛选)",
            "sZeroRecords": "找不到匹配的数据",
            "sEmptyTable": "无可用数据",
            "sLoadingRecords": "数据加载中...",
            "sProcessing": "数据加载中...",
            "sSearch": "搜索",
            "oPaginate": {
				"sFirst":    "首页",
				"sPrevious": "前一页",
				"sNext":     "后一页",
				"sLast":     "尾页"
			},
        },
        "sPaginationType": "full_numbers",
        "fnRowCallback": function( nRow, aData, iDisplayIndex ) {
            /* Append the grade to the default row class name */

            $('td:eq(4)', nRow).html("<a class='btn_link edit_btn' href='javascript:void(0);' data-id='"+aData['coupon_id']+"'><span>编辑</span></a> &nbsp;&nbsp;&nbsp;"+
            "<a class='btn_link del_btn' href='javascript:void(0);' data-id='"+aData['coupon_id']+"'><span>删除</span></a>&nbsp;&nbsp;&nbsp;"+
            "<a class='btn_link new_btn' href='javascript:void(0);' data-id='"+aData['coupon_id']+"'><span>生成券码</span></a>&nbsp;&nbsp;&nbsp;"+
            "<a class='btn_link exp_btn' href='../marketing/exportCodes' target='_blank'><span>导出券码</span></a>"
            );

            return nRow;
        },
		"fnDrawCallback": function(oSettings) {
			jQuery('.edit_btn').click(function(event) {
				window.location.href = '/marketing/editRule?coupon_id='+jQuery(this).attr('data-id');
			});

			jQuery('.del_btn').click(function(event) {

				var coupon_id = jQuery(this).attr('data-id');
				jConfirm('你确认删除此优惠券？', '删除确认', function(r) {
					if(r) {
						jQuery.post('../marketing/delRule', {coupon_id: coupon_id}, function(data, textStatus, xhr) {
							if(data.success) {
								jQuery.jGrowl("删除成功");
								window.location.reload();
							}else{
								jAlert(data.msg);
							}
						}, 'json');
					}
				});
			});
            
            $('.new_btn').click(function(event) {
                var coupon_id = jQuery(this).attr('data-id');

                jPrompt('请输入生成券码的个数', '', '新增券码', function(r) {
                    if( r ) {
                        if(Number(r) > 0) {
                            jQuery.post('../marketing/addCouponCode', {coupon_id: coupon_id, number: Number(r)}, function(data, textStatus, xhr) {
                                jAlert(data.msg);
                            }, 'json');
                        }
                    }
                });
            });

        }
    });


    // 自定义toolbar
    jQuery('div.top').prepend('<div class="tableoptions"><a class="stdbtn btn_lime" id="add_coupon">新增</a></div>');


    $('#add_coupon').click(function(event) {
        window.location.href = "../marketing/addRule";
    });


    $('#serchtype').change(function(event) {
        coupRuleTable.fnFilter( this.value, 1 );
    });


});
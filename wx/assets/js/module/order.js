$(function() {
    /**
     * 得到订单列表
     * @return {[type]} [description]
     */
    function getOrderList() {

        $("#order .container").empty();
        var userId = 1;
        $.ajax({
            url: api['getOrder'],
            type: 'post',
            dataType: 'json',
            data: {
                userid: userId
            },
            success: function(data) {



            },
            error: function(err) {

                notice("订单获取失败,请重新试试看!~");

            }

        })
    }
})

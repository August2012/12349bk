$(function() {
    //绑定手机
    $(document).on("click", ".btn-bind", function() {
        var tel  = $(".login-tel").val();
        var code = $(".login-num").val();

        if (!tel || !code) {
            notice("请填入完整信息");
            return 0;
        }

        $.ajax({
            url: "MW.php",
            type: 'post',
            dataType: 'json',
            data: {
                "app" : "",
                "act" : ""
            },
            success: function(data) {


            },
            error: function(err) {
                
            }

        })
    })

})
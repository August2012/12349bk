$(function() {

    /**
     * Banner图初始化
     */
    var sW = $(window).width();
    $(".slider").yxMobileSlider({
        width: sW,
        height: 150,
        during: 3000
    });


    /**
     * 获取用户信息
     */
    function getUserInfo(){
        var userid = 1;
        var token  = 10;
        $.ajax({
            url : api['getUserInfo'],
            type : "POST",
            dataType: 'json',
            data :{
                "userid" :  userid,
                "token"  :  token
            },
            success : function(data){
                if( data['success'] == "true" ){
                    //有用户信息
                    console.dir(data['data']);

                }else{
                    //没有用户信息
                }
            },  
            error : function(err){

            }
        });
    }

    //getUserInfo();


    /**
     * 获取二级子类
     */
    function getItems(){
        alert(1);
        var token  = 10;
        $.ajax({
            url : api['getItem'],
            type : "POST",
            dataType: 'json',
            data :{
                "token"  :  token
            },
            success : function(data){

                if( data['success'] == "true" ){
                    //有用户信息
                    console.dir(data['data']);

                }else{
                    //没有用户信息
                }
            },  
            error : function(err){

            }
        });
    }


    /**
     * ISCROLL
     */
    
 

     

 

    $(".footer h4").click(function() {
        $(".footer h4").removeClass('active');
        $(this).addClass('active');
    });

})

window.onload = function(){
    var myScrollLen = 14
     ,   myScroll    = [];  

     for(var i = 1 ; i < myScrollLen; i+=1){
        var tmp = 'container-'+i
         myScroll[i] = new IScroll('#c'+i, {

                scrollbars: true,
                mouseWheel: true,
                interactiveScrollbars: true,
                shrinkScrollbars: 'scale',
                fadeScrollbars: true
            });
     }
}

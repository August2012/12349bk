$(function() {
    //mobilebone初始化
    Mobilebone.init();
    //fastclick初始化
    FastClick(document.body);
    document.addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, false);



    function getHash() {
        var hash = window.location.hash;
        var hashID = '';
        if (hash.indexOf(".php") != -1) {
            hashID = (window.location.hash.split("#&")[1]).split(".php")[0];
        } else {
            hashID = window.location.hash.split("#&")[1];
        }

        return hashID;
    }


    /**
     * 检测hash变化
     * @param  {[type]} page_in [description]
     * @return {[type]}         [description]
     */
    function doCheck(page_in) {
        var pageID = page_in;
        var moduleList = ['index', 'feedback', 'order'];

        $(".footer h4").removeClass('active');
        if (pageID == "index") {
            $(".footer h4").eq(0).addClass('active');
        } else if (pageID == "order") {
            $(".footer h4").eq(1).addClass('active');
        } else if (pageID == "my") {
            $(".footer h4").eq(2).addClass('active');
        }

        // for (var i = 0; i < moduleList.length; i += 1) {
        //     if (pageID == moduleList[i]) {
        //         modulePath = "./module/" + pageID;
        //         require.async(modulePath, function(ex) {
        //             if (ex) ex.init(page_in);
        //         });
        //         break;
        //     }
        // }


        $(".page").removeClass("in").addClass('out');
        $("#" + pageID).removeClass("out").addClass('in');
    }


    Mobilebone.animationend = function() {};
    Mobilebone.animationstart = function(page_in) {};
    Mobilebone.onpagefirstinto = function(page_in) {


    };
    Mobilebone.fallback = function(page_in, page_out) {};


    Mobilebone.callback = function(page_in) {

        doCheck(getHash());

    }



    doCheck(getHash());
})

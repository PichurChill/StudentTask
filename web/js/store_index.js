var MouseEvent = function(e){
    this.x = e.pageX
    this.y = e.pageY
}
var Mouse = function(e){
    var kdheight =  $(document).scrollTop();
    mouse = new MouseEvent(e);
    leftpos = mouse.x+15;
    toppos = mouse.y-kdheight+10;
}
$(function(){
    getStores()
    //更改审核的按钮
    $(".glyphicon-wrench").bind("click",function(e){
        Mouse(e);
        $("#msg_show").html("")
        $("#req_id").empty()
        $("#status_op").css({top:toppos,left:leftpos}).fadeIn(100);
        $("#req_id").html($(this).parent().parent().children('td:eq(0)').html());
    })
    //添加物料的按钮
    $("#addNewStore").bind("click",function(e){
        $("#msg_store_add").html("")
        $("#store_add_name").val('');
        $("#store_add_num").val('');
        Mouse(e);
        $("#store_op_add").css({top:toppos,left:leftpos}).fadeIn(100);
    });
    //数量更改按钮
    //aaa=$(".glyphicon-plus").bind("click",function(e){
    //   alert('12');
    //})
    //关闭
    $(".glyphicon-remove").bind("click",function(e){
        $(this).parent().hide();
    })
})

//状态修改提交
function req_submit(){
    $.ajax({
        url: 'index.php?r=store/reqstatus',
        type: 'POST',
        dataType: 'json',
        data: {id: $("#req_id").html(),
               status: $("#status_change").val()
        },
    })
        .done(function(data) {
            if(data.success==true){
                var id=$("#req_id").html()
                $("#status_change").val()
                $("tr").each(function(){
                    if($(this).children('td:eq(0)').html()==id){
                        $(this).children('td:eq(5)').html(data.status);
                    }
                })
                $("#msg_show").html(data.msg)
            }else{
                $("#msg_show").html(data.msg)
            }
        })
        .fail(function() {
            console.log("error");
        })

}
//显示所有库存
function getStores(){
    var tab=' <table border="1"> <tr><th hidden="hidden">货物id</th><th>物料名</th><th>数量</th><th>操作</th></tr>';
    $.ajax({
        url: 'index.php?r=store/getstores',
        type: 'GET',
        dataType: 'json',
    })
        .done(function(data) {
            if(data.success==true){
                for(var i=0;i<data.stores.length;i++){
                    tab+='<tr><td>'+data.stores[i]['store_name']+'</td><td>'+data.stores[i]['store_num']+'</td>' +
                        '<td><span class="glyphicon glyphicon-plus"></span><span class="glyphicon glyphicon-minus"></span>' +
                        '<span class="glyphicon glyphicon-trash" onclick="store_delete('+data.stores[i]["id"]+')"></span></td></tr>';
                }
                tab+='</table>';
                $("#store_tab").html(tab)
                bindBtn()
            }else{
                $("#store_tab").html(data.msg)
            }
        })

}
//添加新物料
function store_add_submit(){
    $.ajax({
        url: 'index.php?r=store/addstore',
        type: 'POST',
        dataType: 'json',
        data: {store_name: $("#store_add_name").val(),
               store_num:  Number($("#store_add_num").val()),
        },
    })
        .done(function(data) {
            if(data.success==true){
                getStores();
                $("#store_add_name").val('');
                $("#store_add_num").val('');
                $("#msg_store_add").html(data.msg)
            }else{
                $("#msg_store_add").html(data.msg)
            }
        })
        .fail(function() {
            console.log("error");
        })

}
/**
 * 物料数量更改
 * @param id
 */
function store_PM_submit(){
}

/**
 * 删除库存
 * @param id
 */
function store_delete(id){
    var isdel=confirm("您确定要删除该库存吗");
    if(isdel==true){
        $.ajax({
            url: 'index.php?r=store/delstore',
            type: 'POST',
            dataType: 'json',
            data: {store_id: id,
            },
        })
            .done(function(data) {
                if(data.success==true){
                    alert('删除成功！')
                    getStores();
                }else{
                }
            })
            .fail(function() {
                console.log("error");
            })

    }
}
function bindBtn(){
    aaa=$(".glyphicon-plus").bind("click",function(e){
        alert('12');
    })
}
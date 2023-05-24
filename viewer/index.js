$(function() {
    $("#datepicker_st").datepicker({dateFormat: "yy-mm-dd"});
    $("#datepicker_ed").datepicker({dateFormat: "yy-mm-dd"});

    $('#tbl_gannt tbody th, #tbl_gannt tbody td').on('click', function(e) {
        $('#tbl_gannt tr').removeClass('highlighted');
        $(this).parent('tr').addClass('highlighted');
    })
});
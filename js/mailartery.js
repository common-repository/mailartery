jQuery(document).ready(function(){
    jQuery('#myTable').DataTable({
        "order":[[0,'DESC']],
        "bStateSave":true
    });
});
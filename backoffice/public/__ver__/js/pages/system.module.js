$(function() {
    /** Datatable configuration */
    if (jQuery().dataTable) {
        gTable = $('#dbDatatable').dataTable({
            bFilter: false,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: true,
            bStateSave: true,
            iDisplayLength: 10,
            sAjaxSource: null,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: filesAaData,
            aoColumns:[
                {
                    name: "file"
                }, {
                    name: "action",
                    sortable: false,
                    width: "1%"
                }
            ]
        });
    }

    $('#generate_sitemap').click(function () {
        var spinner = $(this).children(".glyphicon");
        spinner.addClass("animate-spin");
        $.ajax({
            type: "POST",
            url: GENERATE_SITEMAP_URL,
            data: {},
            dataType: "json",
            success: function(data) {
                spinner.removeClass("animate-spin");
                    notification(data);
                    $('#generate_sitemap').removeClass('disabled');
            },
            error: function(data) {
                spinner.removeClass("animate-spin");
                notification({
                    status: "error",
                    msg: "Failed to generate sitemap. Please contact developers."
                    });
                $('#generate_sitemap').removeClass('disabled');
            },
            beforeSend: function ( xhr ) {
                    $('#generate_sitemap').addClass('disabled');
            }
        });
    });

    $('#create-new-database').click(function () {
        $.ajax({
            type: "POST",
            url: CREATE_NEW_DATABASE_URL,
            data: {},
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    $('#create-new-database').html('Page reloading...');
                    window.location.reload();
                } else {
                    notification(data);
                    $('#create-new-database').html('Error :(');
                    $('#create-new-database').removeClass('disabled');
                }
            },
            beforeSend: function(xhr) {
                $('#create-new-database').addClass('disabled');
                $('#create-new-database').html('Creating...');
            }
        });
    });
    
    $('.database-file-download').click(function () {
        var button = $(this);
        var bottonDefaultValue = button.html();
        
        button.addClass('disabled');
        button.append(' - Downloading...');
        
        window.location=DOWNLOAD_DATABASE_BACKUP_URL + '?file=' + button.attr('data-link');
        
        button.html(bottonDefaultValue).append(' - Downloaded!');
        button.removeClass('disabled');
    });
    
    $('.database-file-delete').click(function () {
        var button = $(this);
        
        $.ajax({
            type: "POST",
            url: DELETE_DATABASE_BACKUP_URL,
            data: {file:button.attr('data-link')},
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    //button.html('Page reloading...');
                    window.location.reload();
                } else {
                    notification(data);
                    button.html('Error :(');
                    button.removeClass('disabled');
                }
            },
            beforeSend: function(xhr) {
                button.addClass('disabled');
                button.html('Deleting...');
            }
        });
    });
});
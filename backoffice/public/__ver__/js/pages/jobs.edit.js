$(function() {
    if ($('.tinymce').length > 0) {
        var tinymceObj = {
            selector: ".tinymce",
            skin: "clean",
            plugins: [
                "code", "autoresize", "link"
            ],
            extended_valid_elements : "i[*]",
            verify_html : false,
            menu : {},
            toolbar: "undo redo | styleselect | bold italic underline |  aligncenter alignjustify alignleft alignright | bullist numlist outdent indent | link | print | fontsizeselect | code | removeformat",
            autoresize_min_height:280,
            browser_spellcheck : true,
            init_instance_callback: function(){
               ac = tinyMCE.activeEditor;
               ac.dom.setStyle(ac.getBody(), 'fontSize', '13px');
            }
        };
        tinymce.init(
            tinymceObj
        );
    }

    $('#save_button').click(function() {
        var validate = $('#job_manage_table').validate();

        if ($('#job_manage_table').valid()) {

            var btn = $('#save_button');
            tinymce.triggerSave();
            btn.button('loading');
            var cityName = $('select#city_id option:selected').text();
            var city = {"city": cityName};
            var obj = $('#job_manage_table').serialize();
            obj += '&city=' + cityName;
            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_DATA,
                data: obj,
                dataType: "json",
                success: function(data) {
                    if (data.status == 'success') {
                        if (parseInt(data.id) > 0) {
                            window.location.href = GLOBAL_BASE_PATH + 'recruitment/jobs/edit/' + data.id;
                        } else {
                            notification(data);
                        }

                    } else {
                        notification(data);
                    }
                    btn.button('reset');

                }
            });
        } else {
            validate.focusInvalid();
        }
    });

    function populateProvinceOptions($countryID) {
        $.getJSON(GET_PROVINCE_LIST + '?country=' + $countryID, function(data) {
            var html = '';
            for (var i in data) {
                var item = data[i];
                html += '<option value="' + item.id + '">' + item.name + '</option>';
            }
            $('select#province_id').html(html);

            $( "select#province_id" ).trigger('change');
        });
    }

    function populateCityOptions($provinceID) {
        $.getJSON(GET_CITY_LIST + '?province=' + $provinceID, function(data) {
            var html = '';

            for (var i in data) {
                var item = data[i];
                html += '<option value="' + item.id + '">' + item.name + '</option>';
            }

            $('select#city_id').html(html);
        });
    }



    $( "select#country_id" ).change(function() {
        var $countryID = $(this).val();
        populateProvinceOptions($countryID);
    });

    $( "select#province_id" ).change(function() {
        var $provinceID = $(this).val();
        populateCityOptions($provinceID);
    });

    $('#job_delete_button').on('click', function() {
        var id = $('#job_id').val();
        if (parseInt(id) > 0) {
            $.ajax({
                type: "POST",
                url: DELETE_JOB,
                data: {id:id},
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
                        window.location.href = GLOBAL_BASE_PATH + 'recruitment/jobs';
                    } else {
                        notification(data);
                    }
                }
            });
        }
    });

    $('#job_deactivate_button, #job_activate_button').on('click', function() {
        var id = $('#job_id').val();
        var jobStatus = $(this).data('jobStatus');

        if (parseInt(id) > 0) {
            $.ajax({
                type: "POST",
                url: ACT_DEACT_JOB,
                data: {id:id , jobStatus: jobStatus},
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
                        window.location.href = GLOBAL_BASE_PATH + 'recruitment/jobs';
                    } else {
                        notification(data);
                    }
                }
            });
        }
    });



});
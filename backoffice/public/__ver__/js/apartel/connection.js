$(function() {
    var navListItems = $('ul.setup-panel li a'),
        allWells = $('.setup-content');

    allWells.hide();

    navListItems.click(function(e)
    {
        e.preventDefault();
        var $target = $($(this).attr('href')),
            $item = $(this).closest('li');

        if (!$item.hasClass('disabled')) {
            navListItems.closest('li').removeClass('active');
            $item.addClass('active');
            allWells.hide();
            $target.show();
        }
    });

    $('ul.setup-panel li.active a').trigger('click');
    new Connection('credentials-block');

    // OTA connection start
    $('#ota-form').validate({
        rules: {
            ota_name:{
                required: true,
                digits: true,
                min: 1
            },
            ota_ref:{
                required: true
            },
            ota_url:{
                required: true,
                url: true
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
        }
    });

    $('#ota-button').click(function(e) {
        var validate = $('#ota-form').validate();
        if ($('#ota-form').valid()) {
            var btn = $('#ota-button');
            btn.button('loading');
            var url = $('#ota-form').attr('action');
            var obj = $('#ota-form').serializeArray();
            $.ajax({
                type: "POST",
                url: url,
                data: obj,
                dataType: "json",
                success: function(data) {
                    if(data.status == 'success'){
                        location.reload();
                    } else {
                        notification(data);
                        btn.button('reset');
                    }
                }
            });
        } else {
            validate.focusInvalid();
        }
    });

    $('.check-button').click(function(e) {
        e.preventDefault();

        var btn = $(this);
        btn.button('loading');

        $.ajax({
            type: "POST",
            url: $(this).attr('data-url'),
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    location.reload();
                } else {
                    notification(data);
                    btn.button('reset');
                }
            }
        });
    });

    $(".delete-ota").click(function() {
        $('#OTADeleteModal').modal();
        var url = $(this).attr('data-url');
        $('#deleteOTAButton').prop('href', url);
    });
    // end OTA connection
});

//if($(".panel-type").length){
//    $( ".panel-type" ).click(function() {
//        var partRate = $(this).closest('.type-part').find('.rate-part');
//
//        if(partRate.hasClass('soft-hide')) {
//            partRate.removeClass('soft-hide')
//        } else {
//            partRate.addClass('soft-hide')
//        }
//
//    });
//}

function getRatesByTypeId(obj, cubilisTypeId) {
    var typeId = obj.value;
    var html = '<select class="form-control" name="ginosi_rate_id[' + cubilisTypeId + '][]"><option value="0">-- Select Rate --</option>';
    for(var t in GLOBAL_TYPE_RATE_LIST) {
        var type = GLOBAL_TYPE_RATE_LIST[t];
        if (type.type_id == typeId) {
            for(var r in type.rate_list) {
                var rate = type.rate_list[r];
                html += '<option value="' + rate.rate_id + '">' + rate.rate_name + '</option>';
            }
        }
    }
    html += '</select>';
    $(obj).closest('.type-part').find('.rate-select-part').html(html);
}

for(var v in GLOBAL_TYPE_RATE_LIST) {
    var item = GLOBAL_TYPE_RATE_LIST[v];
}

var Class = function(methods) {
    var klass = function() {
        this.initialize.apply(this, arguments);
    };

    for (var property in methods) {
        klass.prototype[property] = methods[property];
    }

    if (!klass.prototype.initialize)  {
        klass.prototype.initialize = function(){};
    }

    return klass;
};

var Connection = Class({
    state: 0, // default state before initialization
    checked: 0,
    debug: true,
    connectionFields: ['cubilis_id', 'cubilis_username', 'cubilis_password'],
    startingFieldsData: {},
    url: {
        save: null,
        connect: null,
        testPull: null,
        testAvailability: null,
        testList: null
    },

    UNKNOWN: 0,
    SYNCED: 1,
    UNSYNCED: 2,

    initialize: function(parent, debug) {
        var self = this;

        this.debug = debug | false;
        this.target = $('#' + parent);
        this.startupDefinitions()

        $('#save_button').click(function(e) {
            e.preventDefault();

            if (self.isChecked()) {
                self.save();
            } else {
                self.checkConnection(function() {
                    self.log('Checking finished');

                    self.changeStatus('Checked', 'success');
                    self.defineSaveAction('save');
                    self.defineConnectAction('connect');
                    self.setAsChecked();
                })
            }
        });

        $('#connect_button').click(function(e) {
            e.preventDefault();

            if (self.getState() == self.UNSYNCED) {
                self.defineConnectAction('connecting');
            } else {
                self.defineConnectAction('disconnecting');
            }

            self.connect();
        });


        $('#sync-clone').click(function(e) {
            e.preventDefault();
            $('#sync-clone').button('loading');
            $('#link-rates').submit();
        });
    },
    connect: function() {
        var self = this,
            connectStatus = (self.getState() == self.SYNCED);

        self.defineConnectAction(
            self.getState() == self.UNSYNCED
                ? 'connecting'
                : 'disconnecting'
        );

        $.ajax({
            url: self.url.connect,
            type: "POST",
            data: {
                connect: connectStatus ? 0 : 1,
                data: self.prepareFieldsData()
            },
            cache: false,
            success: function(data) {
                if (data.status == 'success') {
                    window.location.reload();
                } else {
                    self.defineConnectAction('connect');
                    notification(data);
                }
            }
        });
    },
    save: function() {
        var self = this;

        self.defineSaveAction('saving');

        $.ajax({
            url: self.url.save,
            type: "POST",
            data: self.prepareFieldsData(),
            cache: false,
            success: function(data) {
                if (data.status == 'success') {
                    window.location.reload();
                } else {
                    self.defineSaveAction('save');
                    notification(data);
                }
            }
        });
    },
    prepareFieldsData: function() {
        var fields = this.getConnectionFields(),
            fieldsData = {};

        for (var i = 0; i < fields.length; i++) {
            fieldsData[fields[i]] = $('#' + fields[i]).val();
        }

        return fieldsData;
    },
    prepareStartingFieldsData: function() {
        var fields = this.getConnectionFields(),
            fieldsData = {};

        for (var i = 0; i < fields.length; i++) {
            fieldsData[fields[i]] = $('#' + fields[i]).val();
        }

        this.startingFieldsData = fieldsData;
    },
    getStartingFieldsData: function() {
        return this.startingFieldsData;
    },
    startupDefinitions: function() {
        var connections = $('.link-rates');

        this.url.save = this.target.attr('data-url-save');
        this.url.connect = this.target.attr('data-url-connect');
        this.url.testPull = this.target.attr('data-url-test-pull');
        this.url.testAvailability = this.target.attr('data-url-test-availability');
        this.url.testList = this.target.attr('data-url-test-list');

        this.prepareStartingFieldsData();

        if (parseInt(this.target.attr('data-cubilis-sync'))) {
            this.log('Status: Connected');
            this.defineSaveAction('hide');
            this.setState(this.SYNCED);
            this.defineConnectAction('disconnect');
            this.changeStatus('Connected', 'success');

            connections.show();
        } else {
            this.log('Status: Not Connected');
            this.defineSaveAction('check');
            this.setState(this.UNSYNCED);
            this.defineConnectAction('hide');

            connections.hide();
        }
    },
    controlFields: function(action) {
        var elements = this.getConnectionFields();

        for (var i = 0; i < elements.length; i++) {
            if (action == 'lock') {
                $('#' + elements[i]).attr('disabled', true);
            } else {
                $('#' + elements[i]).attr('disabled', false);
            }
        }
    },
    defineConnectAction: function(action) {
        var connectButton = $('#connect_button');
        $('#sync-clone').hide();
        switch (action) {
            case 'hide':
                connectButton.hide();

                break;
            case 'connect':
                connectButton
                    .show()
                    .attr('disabled', false)
                    .removeClass('btn-danger')
                    .addClass('btn-success')
                    .text('Save & Connect');

                break;
            case 'connecting':
                connectButton
                    .show()
                    .attr('disabled', true)
                    .removeClass('btn-danger')
                    .addClass('btn-success')
                    .text('Connecting...');
                break;
            case 'disconnect':
                connectButton
                    .show()
                    .attr('disabled', false)
                    .removeClass('btn-success')
                    .addClass('btn-danger')
                    .text('Disconnect');
                $('#sync-clone').show();
                break;
            case 'disconnecting':
                connectButton
                    .show()
                    .attr('disabled', true)
                    .removeClass('btn-success')
                    .addClass('btn-danger')
                    .text('Disconnecting...');

                break;
        }
    },
    defineSaveAction: function(status) {
        var self = this,
            saveButton = $('#save_button');

        switch (status) {
            case 'hide':
                self.controlFields('lock');
                saveButton.hide();

                break;
            case 'check':
                self.log('Action: ready to check');
                self.controlFields('unlock');
                saveButton.attr('disabled', false).val('Check Connection');

                break;
            case 'checking':
                self.log('Action: checking...');
                self.controlFields('lock');
                saveButton.attr('disabled', true).val('Checking...');

                break;
            case 'save':
                self.log('Action: ready to save');
                self.controlFields('lock');
                saveButton.attr('disabled', false).val('Save');

                break;
            case 'saving':
                self.log('Action: saving...');
                self.controlFields('lock');
                saveButton.attr('disabled', true).val('Saving...');

                break;
        }
    },
    changeStatus: function(subject, status) {
        $('#connection-status-value').find('span')
            .text(subject)
            .removeClass('label-info')
            .removeClass('label-warning')
            .removeClass('label-success')
            .removeClass('label-danger')
            .addClass('label-' + status)
    },
    checkConnection: function(callback) {
        var self = this;

        self.log('Checking connection...');

        self.changeStatus('Checking', 'info');
        self.defineSaveAction('checking');

        self.testPrepareEnviroment(function(data) {
            if (data.status == 'success') {
                self.log('Step #1: Checked');

                self.testPullReservations(function(data) {
                    if (data.status == 'success') {
                        self.log('Step #2: Checked');

                        self.testUpdateAvailability(function(data) {
                            if (data.status == 'success') {
                                self.log('Step #3: Checked');

                                self.testFetchList(function(data) {
                                    if (data.status == 'success') {
                                        self.log('Step #4: Checked');

                                        self.testRollbackEnviroment(function(data) {
                                            if (data.status == 'success') {
                                                self.log('Step #5: Checked');

                                                callback();
                                            } else {
                                                self.log('Step #5: Failed');

                                                self.changeStatus('Error', 'important');
                                                self.defineSaveAction('check');

                                                notification(data);
                                            }
                                        });
                                    } else {
                                        self.log('Step #4: Failed');

                                        self.changeStatus('Error', 'important');
                                        self.defineSaveAction('check');
                                        self.completeRollBack();

                                        notification(data);
                                    }
                                });
                            } else {
                                self.log('Step #3: Failed');

                                self.changeStatus('Error', 'important');
                                self.defineSaveAction('check');
                                self.completeRollBack();

                                notification(data);
                            }
                        });
                    } else {
                        self.log('Step #2: Failed');

                        self.changeStatus('Error', 'important');
                        self.defineSaveAction('check');
                        self.completeRollBack();

                        notification(data);
                    }
                });
            } else {
                self.log('Step #1: Failed');

                self.changeStatus('Error', 'important');
                self.defineSaveAction('check');

                notification(data);
            }
        });
    },
    completeRollBack: function() {
        var self = this;

        self.testRollbackEnviroment(function(data) {
            if (data.status == 'success') {
                self.log('Rolled to back!');
            } else {
                self.log('Last Rollback: Failed');
                self.changeStatus('Error', 'important');

                notification(data);
            }
        });
    },
    testPullReservations: function(callback) {
        var self = this;

        $.ajax({
            url: self.url.testPull,
            type: "POST",
            cache: false,
            success: function(data) {
                callback(data);
            }
        });
    },
    testUpdateAvailability: function(callback) {
        var self = this;

        $.ajax({
            url: self.url.testAvailability,
            type: "POST",
            cache: false,
            success: function(data) {
                callback(data);
            }
        });
    },
    testFetchList: function(callback) {
        var self = this;

        $.ajax({
            url: self.url.testList,
            type: "POST",
            cache: false,
            success: function(data) {
                callback(data);
            }
        });
    },
    testPrepareEnviroment: function(callback) {
        var self = this,
            prepared = self.prepareFieldsData();

        prepared['prepare'] = 1;

        $.ajax({
            url: self.url.save,
            type: "POST",
            cache: false,
            data: prepared,
            success: function(data) {
                callback(data);
            }
        });
    },
    testRollbackEnviroment: function(callback) {
        var self = this,
            prepared = self.prepareFieldsData();

        prepared['rollback'] = 1;

        $.ajax({
            url: self.url.save,
            type: "POST",
            cache: false,
            data: prepared,
            success: function(data) {
                callback(data);
            }
        });
    },
    getConnectionFields: function() {
        return this.connectionFields;
    },
    getState: function() {
        this.log('State is ' + ( this.state == this.SYNCED ? 'synced' : ( this.state == this.UNSYNCED ? 'unsynced' : 'unknown')));
        return this.state;
    },
    setState: function(state) {
        this.log('State set as ' + (state == this.SYNCED ? 'synced' : (state == this.UNSYNCED ? 'unsynced' : 'unknown')));
        this.state = state;
    },
    setAsChecked: function() {
        this.checked = 1;
    },
    isChecked: function() {
        return this.checked;
    },
    log: function(message) {
        if (this.debug) {
        }
    }
});

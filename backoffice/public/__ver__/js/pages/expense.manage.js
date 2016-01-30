$(function () {
    window.expense = new Expense();
});

var Class = function (methods) {
    var classObject = function () {
        this.initialize.apply(this, arguments);
    };

    for (var property in methods) {
        if (methods.hasOwnProperty(property)) {
            classObject.prototype[property] = methods[property];
        }
    }

    if (!classObject.prototype.initialize) {
        classObject.prototype.initialize = function () {
        };
    }

    return classObject;
};

var Expense = new Class({
    // Entry point
    initialize: function () {
        var self = this;

        self.generalSetup();

        self.defineElements();
        self.definePageState();

        if (self.isEdit) {
            self.defineLockingMechanizme();
        }

        self.preSetup();
        self.setupSimplifiedView();
        self.setupFormValidator();

        self.setupResources(function () {
            self.drawCurrencies();

            self.setupListeners();
            self.setupElements();

            self.postSetup();
        });
    },

    generalSetup: function () {
        this.itemCount = 0;
        this.reservoir = {};
        this.currencyList = {};
        this.transactionCount = 0;
        this.generalIterator = 100;
        this.optimalUploadCount = 5;
        this.months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        this.expenseSummary = {
            depositBalance: 0,
            currency: null,
            limit: false,
            balance: 0,
            item: {
                balance: 0
            },
            transaction: {
                balance: 0
            }
        };
        this.checkItemSearchClickManual = true;
        this.checkTransactionSearchClickManual = true;
    },

    defineElements: function () {
        this.templatesElem = $('#templates');
        this.itemContainer = $('.item-container');
        this.transactionContainer = $('.transaction-container');
        this.transactionAdd = $('.transaction-add');
        this.itemAdd = $('.item-add');
        this.form = $('#expense-form');
        this.submit = $('.submit');
        this.delete = $('.delete');
        this.duplicate = $('.duplicate');
        this.attachments = $('#attachments');
        this.attachmentPreview = $('.attachments-preview');
        this.modal = $('#areYouSure');
        this.checkedStatusesCount = $('#item-statuses:checked').size();
    },

    definePageState: function () {
        this.isUnlocker = parseInt(this.templatesElem.attr('data-is-unlocker')) ? true : false;
        this.isFinance = parseInt(this.templatesElem.attr('data-is-finance')) ? true : false;
        this.isManager = parseInt(this.templatesElem.attr('data-is-manager')) ? true : false;

        this.isSimplified = !this.isFinance;

        this.ticketId = parseInt(this.templatesElem.attr('data-ticket-id'));
        this.statusId = parseInt(this.templatesElem.attr('data-status-id'));
        this.financeStatusId = parseInt(this.templatesElem.attr('data-finance-status-id'));
        this.userId = parseInt(this.templatesElem.attr('data-user-id'));

        this.isEdit = (this.ticketId ? true : false);
    },

    lock: function (except) {
        except = except || [];

        if (typeof except !== 'object') {
            return;
        }

        // lock them all
        $('#expense-form').find('input, textarea, select, button').each(function () {
            var $that = $(this);

            // Take into account exceptions
            if ($.inArray($that.attr('name'), except) == -1) {
                // Ignore modal's buttons
                if (typeof $that.closest('.modal-content').attr('class') !== 'undefined') {
                    return false;
                }

                // Ignore search filters
                if (typeof $that.closest('.search-filters').attr('class') !== 'undefined') {
                    return false;
                }

                $that.prop('disabled', true);
            }
        });

        // Hide add buttons
        $('.item-add, .transaction-add').hide();

        // Hide remove buttons
        $('.remove').hide();
    },

    // The mechanizme below is a trick, to define for what permissions what kind of
    // operations available to user and what not
    defineLockingMechanizme: function () {
        var exceptions = [],

            statusAwaiting = this.statusId == 1,
            statusApproved = this.statusId == 2,
            statusRejeted = this.statusId == 3,

            financeStatusNew = this.financeStatusId == 1,
            financeStatusRendered = this.financeStatusId == 3,
            financeStatusSettled = this.financeStatusId == 4;

        dontLock: do {
            do {
                if (this.isUnlocker) {
                    break dontLock;
                }

                if (this.isFinance) {
                    if (financeStatusSettled) {
                        exceptions.push('comment_writer');

                        break;
                    }

                    break dontLock;
                }

                if (this.isManager) {
                    if (financeStatusSettled) {
                        break;
                    }

                    if (!financeStatusNew) {
                        exceptions.push('comment_writer');
                        exceptions.push('title');

                        break;
                    }

                    break dontLock;
                }
            } while (false);

            // Lock everything but exceptions
            this.lock(exceptions);
        } while (false);
    },

    preSetup: function () {
        var $balance = $('.balances'),
            $purpose = $('#purpose');

        // Store predefined values to easy track changes
        if (this.isEdit && this.isSimplified) {
            $purpose.attr('data-predefined', $purpose.val());
        }

        Array.prototype.equals = function (array) {
            if (!array) {
                return false;
            }

            if (this.length != array.length) {
                return false;
            }

            for (var i = 0, l = this.length; i < l; i++) {
                if (this[i] instanceof Array && array[i] instanceof Array) {
                    if (!this[i].equals(array[i])) {
                        return false;
                    }
                } else if (this[i] != array[i]) {
                    return false;
                }
            }

            return true;
        };

        Object.size = function (obj) {
            var size = 0, key;

            for (key in obj) {
                if (obj.hasOwnProperty(key)) {
                    size++;
                }
            }

            return size;
        };

        // Floating blaance
        $balance.affix({
            offset: {
                top: 110
            }
        });

        // Freeze budget if ticket is approved
        if (this.statusId == 2) {
            $('#budget').attr('disabled', true);
            $('.limit').attr('disabled', true);
            $('.limit-balance').addClass('limit-approved');
        } else {
            $('.limit-balance').addClass('limit-not-approved');
        }
    },

    setupSimplifiedView: function () {
        if (this.isFinance) {
            $('.transaction-container').removeClass('hide');
            $('.search-filters').removeClass('hide');
        }
    },

    setupFormValidator: function () {
        var self = this;

        $.validator.addMethod("dateEx", function (value, element) {
            return this.optional(element) || /^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{2},\s[0-9]{4}$/i.test(value);
        }, 'Date is invalid.');

        $.validator.addMethod('amount', function (value, element) {
            return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
        }, 'Amount is invalid.');

        $.validator.addMethod('greatThan', function (value, element) {
            // Approved
            if (self.statusId == 2) {
                return true;
            }

            var limit = parseFloat(parseFloat(value).toFixed(2)),
                itemBalance = parseFloat((self.expenseSummary.item.balance).toFixed(2));

            if (limit < itemBalance) {
            }

            return limit >= itemBalance;
        }, 'Limit is less than item balance.');

        $.validator.setDefaults({
            ignore: ':hidden:not(*)'
        });

        this.form.validate({
            onfocusout: false,
            invalidHandler: function (form, validator) {
                var errors = validator.numberOfInvalids();

                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                'ticket_manager': {
                    required: true,
                    number: true,
                    min: 1
                },
                'limit': {
                    required: true,
                    amount: true,
                    greatThan: true,
                    min: 0
                },
                'purpose': {
                    required: self.isSimplified
                },
                'expected_completion_date': {
                    required: self.isManager
                },
                'budget': {
                    required: $('#budget').length ? true : false,
                    number: true,
                    min: 1
                }
            },
            highlight: function (element, errorClass, validClass) {
                if ($(element).prop('tagName') == 'TEXTAREA') {
                    $(element).addClass('has-error');
                } else {
                    $(element).parent().addClass('has-error');
                }
            },
            unhighlight: function (element, errorClass, validClass) {
                if ($(element).prop('tagName') == 'TEXTAREA') {
                    $(element).removeClass('has-error');
                } else {
                    $(element).parent().removeClass('has-error');
                }
            },
            success: function (label) {
                $(label).closest('form').find('.valid').removeClass('invalid');
            },
            errorPlacement: function (error, element) {
                // do nothing
            }
        });

        this.applyValidaton();
    },

    applyValidaton: function () {
        var self = this,
            forceRequired = function ($element) {
                return $element.closest('#templates') == undefined;
            },
            fullAccessValidator = function (element) {
                return (
                    element.closest('#templates') == undefined && self.isFinance
                );
            };

        $('input.amount').each(function () {
            $(this).rules('add', {
                required: forceRequired,
                amount: true,
                min: 0
            });
        });

        $('select.account').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator,
                number: true,
                min: 1
            });
        });

        $('select.item-cost-centers').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator
            });
        });

        $('select.category').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator,
                number: true,
                min: 1
            });
        });

        $('select.sub-category').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator,
                number: true,
                min: 1
            });
        });

        $('select.account-from').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator,
                number: true,
                min: 1
            });
        });

        $('select.account-to').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator,
                number: true,
                min: 1
            });
        });

        $('input.transaction-date').each(function () {
            $(this).rules('add', {
                required: fullAccessValidator,
//                dateEx: true
            });
        });
    },

    postSetup: function () {
        var $manager = $('#ticket-manager'),
            $budget = $('#budget');

        if (!this.isEdit) {
            $manager[0].selectize.clear();

            if ($budget.length) {
                $budget[0].selectize.clear();
            }
        }

        $('.action-buttons').removeClass('hide');

        if (GLOBAL_ITEM_ID > 0) {
            this.checkItemSearchClickManual = false;
            $('.item-search').trigger('click');
        }

        if (GLOBAL_TRANSACTION_ID > 0) {
            this.checkTransactionSearchClickManual = false;
            $('.transaction-search').trigger('click');
        }
    },

    setupListeners: function () {
        this._listenerAddTransaction();
        this._listenerAddItem();
        this._listenerIsStartup();
        this._listenerIsDeposit();
        this._listenerIsRefund();
        this._listenerRemoveTemplateElem();
        this._listenerRemoveAttachmentTicket();
        this._listenerRemoveAttachmentItems();
        this._listenerRestoreAttachment();
        this._listenerDetectAmountChanges();
        this._listenerCustomSelect();
        this._listenerExpenseCurrency();
        this._listenerSubmitButton();
        this._listenerAttachmentsTicket();
        this._listenerAttachmentsItems();
        this._listenerUniversalModal();
        this._listenerRevoke();
        this._listenerVerify();
        this._listenerReady();
        this._listenerSettle();
        this._listenerUnsettle();
        this._listenerShowLockedCostCenters();
        this._listenerExpenseAttachment();
        this._listenerConnections();
        this._listenerLimit();
        this._listenerTitle();

        $(document).trigger('listenersAreReady');
    },

    setupResources: function (callback) {
        var self = this;

        // Office List
        $.ajax({
            url: self.templatesElem.attr('data-office-url'),
            type: 'POST',
            error: function () {
                notification({
                    status: 'error',
                    msg: 'ERROR! Something went wrong (office list)'
                });
            },
            success: function (data) {
                if (data.status == 'success') {
                    self.reservoir.officeList = data.data;
                } else {
                    notification(data);
                }
            }
        });

        // Money Account List
        $.ajax({
            url: self.templatesElem.attr('data-money-account-url'),
            type: 'POST',
            data: {},
            error: function () {
                notification({
                    status: 'error',
                    msg: 'ERROR! Something went wrong (money account list)'
                });
            },
            success: function (data) {
                if (data.status == 'success') {
                    self.reservoir.moneyAccountList = data.data;
                    self.reservoir.allowedMoneyAccountList = [];

                    $.each(data.data, function(idx, moneyAccount) {
                        if ('undefined' != typeof moneyAccount && 'false' != moneyAccount.access) {
                            self.reservoir.allowedMoneyAccountList.push(moneyAccount);
                        }
                    });
                } else {
                    notification(data);
                }
            }
        });

        // Sub Category List
        $.ajax({
            url: self.templatesElem.attr('data-sub-category-url'),
            type: 'POST',
            error: function () {
                notification({
                    status: 'error',
                    msg: 'ERROR! Something went wrong (sub category list)'
                });
            },
            success: function (data) {
                if (data.status == 'success') {
                    data = data.data;

                    var categories = [],
                        subCategoriesSimple = [],
                        subCategoryAndCategories = [],
                        subCategories = {},
                        order = 1;

                    for (var categoryId in data) {
                        if (data.hasOwnProperty(categoryId)) {
                            categories.push({
                                value: categoryId,
                                text: data[categoryId].name
                            });

                            subCategoryAndCategories.push({
                                id: categoryId,
                                name: data[categoryId].name,
                                type: 1,
                                order: order++
                            });

                            subCategories[categoryId] = [];

                            for (var subCategoryId in data[categoryId].sub) {
                                if (data[categoryId].sub.hasOwnProperty(subCategoryId)) {
                                    subCategories[categoryId].push({
                                        value: data[categoryId].sub[subCategoryId].id,
                                        text: data[categoryId].sub[subCategoryId].name
                                    });

                                    subCategoryAndCategories.push({
                                        id: data[categoryId].sub[subCategoryId].id,
                                        name: data[categoryId].sub[subCategoryId].name,
                                        type: 2,
                                        order: order++
                                    });

                                    subCategoriesSimple.push({
                                        value: data[categoryId].sub[subCategoryId].id,
                                        text: data[categoryId].sub[subCategoryId].name,
                                        categoryId: categoryId
                                    });
                                }
                            }
                        }
                    }

                    self.reservoir.categoryList = categories;
                    self.reservoir.subCategoryList = subCategories;
                    self.reservoir.subCategoryListSimple = subCategoriesSimple;
                    self.reservoir.subCategoryAndCategoryList = subCategoryAndCategories;
                } else {
                    notification(data);
                }
            }
        });

        // Currency List
        var dateList = [
            self.templatesElem.attr('data-expense-creation-date')
        ];

        $('.template').each(function () {
            if ($.inArray($(this).attr('data-date'), dateList) == -1) {
                dateList.push(
                    $(this).attr('data-date')
                )
            }
        });

        $.ajax({
            url: self.templatesElem.attr('data-currency-url'),
            data: {
                dateList: jQuery.unique(dateList)
            },
            type: 'POST',
            error: function () {
                notification({
                    status: 'error',
                    msg: 'ERROR! Something went wrong (currency list)'
                });
            },
            success: function (data) {
                if (data.status == 'success') {
                    data = data.data;

                    self.reservoir.currencyList = {};
                    self.reservoir.currencyListEx = data;

                    for (var date in data) {
                        for (var currency in data[date]) {
                            self.reservoir.currencyList[data[date][currency]['code']] = {
                                id: data[date][currency]['id'],
                                code: data[date][currency]['code'],
                                symbol: data[date][currency]['symbol']
                            };
                        }

                        break;
                    }

                    setTimeout(callback, 100);
                } else {
                    notification(data);
                }
            }
        });
    },

    setupElements: function () {
        var $expectedCompletionDate = $('#expected-completion-date'),
            $budget = $('#budget');

        this.__setupCustomSelect(); // Expense currency only
        this.__setupCheckboxes();
        this.__setupPredefinedAttachments();

        // Setup budget
        if ($budget.length) {
            $budget.selectize();
        }

        // Init expected completion date
         $expectedCompletionDate.daterangepicker({
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            startDate: moment().subtract(29, 'days'),
            endDate: moment(),
            format: globalDateFormat
        });

        if (this.isEdit) {
            this.delete.removeClass('hide');
            this.duplicate.removeClass('hide');

            // Save Changes button becomes Resubmit for rejected ticket
            if (this.statusId == 3) {
                this.submit.text('Resubmit');
            } else {
                this.submit.text('Save Changes');
            }

            this.__setupSearchInputs();
        } else {
            this.submit.text('Create Expense');
        }
    },

    bytesToSize: function (bytes) {
        if (bytes == 0) {
            return '0 Byte';
        }

        var k = 1000,
            sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
            i = Math.floor(Math.log(bytes) / Math.log(k));

        return (bytes / Math.pow(k, i)).toPrecision(3) + sizes[i];
    },

    drawCurrencies: function () {
        var self = this,
            $customSelect = $('.custom-select');

        $customSelect.each(function () {
            var $element = $(this),
                currencies = self.reservoir.currencyList;

            for (var i in currencies) {
                if (currencies.hasOwnProperty(i)) {
                    $element.find('ul').append([
                        '<li><a href="#" data-currency-id="', currencies[i].id, '" data-value="', currencies[i].code.toLowerCase(), '">', currencies[i].code.toUpperCase(), '</a></li>'
                    ].join(''));
                }
            }
        });
    },

    drawItem: function () {
        var $templateItem = this.templatesElem.find('.item'),
            $item = $templateItem.clone(),
            $account = $item.find('.account'),
            $costCenter = $item.find('.item-cost-centers'),
            self = this;

        // These operation required, because of same name element validation issue
        // Need more description? Then look at annotations and ask corresponding person about
        $item.find('.amount').attr('name', 'amount' + this.iter());
        $item.find('.period').attr('name', 'period' + this.iter());
        $item.find('.account').attr('name', 'account' + this.iter());
        $item.find('.item-cost-centers').attr('name', 'cost_centers[' + this.iter() + ']');
        $item.find('.category').attr('name', 'category' + this.iter());
        $item.find('.sub-category').attr('name', 'sub_category' + this.iter());
        $item.find('.type').attr('name', 'type' + this.iter());

        $item.addClass('added');

        $item.find('.type').selectize();

        $('.add-item-inner-container').prepend(
            $item.hide()
        );

        $item.find('.new-item-transaction-id').selectize({
            create: true,
            persist: false
        });

        $item.show('fast');

        // Init basic selectize element
        $costCenter.selectize();

        $account.selectize({
            valueField: 'unique_id',
            labelField: 'name',
            searchField: ['name'],
            delimiter: ',',
            render: {
                option: function (item, escape) {
                    // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                    var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                    return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                },

                item: function (item, escape) {
                    /** @property {string} account_id */
                    var id = self.isEdit ? item.id : item.account_id;

                    return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                }
            },
            load: function (query, callback) {
                if (query.length < 2) {
                    return callback();
                }

                $.ajax({
                    url: self.templatesElem.attr('data-account-url'),
                    type: 'POST',
                    data: {'q': encodeURIComponent(query)},
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        callback(res.data);
                    }
                });
            },
            persist: false,
            hideSelected: true,
            highlight: false,
            onItemRemove: function (value) {
                costCenterSelectize.disable();
            }
        });

        var costCenterSelectize = $costCenter[0].selectize,
            accountSelectize = $account[0].selectize;

        accountSelectize.focus();
        costCenterSelectize.disable();

        accountSelectize.on('item_add', function (value, $selectizeItem) {
            costCenterSelectize.enable();

            $costCenter.attr('placeholder', 'Cost Center');
            costCenterSelectize.destroy();

            $costCenter.selectize({
                plugins: ['remove_button'],
                valueField: 'unique_id',
                searchField: ['name', 'label'],
                hideSelected: true,
                highlight: false,
                score: function () {
                    return function (item) {
                        return item.type * 1000 + item.id;
                    };
                },
                render: {
                    option: function (item, escape) {
                        // Type definition: 1 - apartment, 2 - office, 3 - group
                        var type = parseInt(item.type),
                            label = (type == 1 ? 'primary' : (type == 2 ? 'success' : 'info'));

                        return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                    },
                    item: function (item, escape) {
                        // Type definition: 1 - apartment, 2 - office, 3 - group
                        var type = parseInt(item.type),
                            label = (type == 1 ? 'primary' : (type == 2 ? 'success' : 'info')),
                            accountType = parseInt($selectizeItem.attr('data-type')) == 4 ? 'supplier' : 'affiliate';

                        return '<div data-account="' + accountType + '" data-type="' + escape(type) + '" data-id="' + escape(item.id) + '" data-currency-id="' + escape(item.currency_id) + '"><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                    }
                },
                load: function (query, callback) {
                    if (query.length < 2) {
                        return callback();
                    }

                    $.ajax({
                        url: self.templatesElem.attr('data-cost-center-url'),
                        type: 'POST',
                        data: {'q': encodeURIComponent(query)},
                        error: function () {
                            callback();
                        },
                        success: function (res) {
                            callback(res.data);
                        }
                    });
                }
            });

            // Refresh selectize object
            costCenterSelectize = $costCenter[0].selectize;
        });

        $item.find('.period').daterangepicker({
            startDate: moment(),
            endDate: moment(),
            format: 'YYYY-MM-DD',
            drops: 'up',
            locale: {
                firstDay: 1
            }
        });

        return $item;
    },

    iter: function () {
        return this.generalIterator++;
    },

    drawTransaction: function () {
        var $templateTransaction = this.templatesElem.find('.transaction'),
            $transaction = $templateTransaction.clone(),
            $accountTo = $transaction.find('.account-to'),
            self = this,
            pad = function (str, max) {
                str = str.toString();

                return str.length < max ? pad('0' + str, max) : str;
            };

        $transaction.find('.amount').attr('name', 'amount' + this.iter());
        $transaction.find('.transaction-date').attr('name', 'transaction_date' + this.iter());
        $transaction.find('.account-from').attr('name', 'account_from' + this.iter());
        $transaction.find('.account-to').attr('name', 'account_to' + this.iter());

        $transaction.addClass('added');

        $('.add-transaction-inner-container').prepend(
            $transaction.hide()
        );

        $accountTo.attr('placeholder', 'To Supplier');

        $transaction.find('.user-icon').tooltip({
            container: 'body'
        });

        $transaction.find('.id').text('TMP' + pad(parseInt(self.transactionCount) + 1, 3));

        $accountTo.selectize({
            valueField: 'unique_id',
            labelField: 'name',
            searchField: ['name'],
            delimiter: ',',
            render: {
                option: function (item, escape) {
                    // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                    var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                    return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                },

                item: function (item, escape) {
                    /** @property {string} account_id */
                    var id = self.isEdit ? item.id : item.account_id;

                    return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                }
            },
            load: function (query, callback) {
                if (query.length < 2) {
                    return callback();
                }

                $.ajax({
                    url: self.templatesElem.attr('data-account-url'),
                    type: 'POST',
                    data: {'q': encodeURIComponent(query)},
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        callback(res.data);
                    }
                });
            },
            persist: false,
            hideSelected: true,
            highlight: false
        });

        $transaction.show('fast');

        return $transaction;
    },

    swapAccount: function (target) {
        if (target.hasClass('transaction')) {
            var $accountFromContainer = target.find('.account-from-container'),
                $accountToContainer = target.find('.account-to-container'),
                $accountFrom = target.find('.account-from'),
                $accountTo = target.find('.account-to');

            if (target.hasClass('refund')) {
                $accountToContainer
                    .append($accountFrom)
                    .removeClass('col-sm-7')
                    .addClass('col-sm-5');
                $accountFromContainer
                    .append($accountTo)
                    .removeClass('col-sm-5')
                    .addClass('col-sm-7');
            } else {
                $accountToContainer
                    .append($accountTo)
                    .removeClass('col-sm-5')
                    .addClass('col-sm-7');
                $accountFromContainer
                    .append($accountFrom)
                    .removeClass('col-sm-7')
                    .addClass('col-sm-5');
            }
        }
    },

    checkForStartup: function (elem) {
        if (elem.hasClass('item')) {
            if (elem.find('.is_deposit').is(':checked') && elem.find('.is_refund').is(':checked')) {
                var $label = elem.find('.startup label'),
                    $checkbox = elem.find('.is_startup');

                if ($checkbox.is(':checked')) {
                    $checkbox.trigger('click');
                }

                $label.hide();
            } else {
                elem.find('.startup label').show();
            }
        }
    },

    markAsChecked: function (elem) {
        elem.closest('.col').attr('data-is-checked', elem.is(':checked') ? 1 : 0);
    },

    calculateBalance: function () {
        var self = this,
            isAnyDeposit = false,

            $limit = $('.limit'),
            $balances = $('.balances'),
            $itemBalance = $('.item-balance'),
            $transactionBalance = $('.transaction-balance'),
            $depositBalance = $('.deposit-balance'),
            $limitBalance = $('.limit-balance'),

            initialBalance = parseFloat($balances.attr('data-initial-balance')),
            initialDepositBalance = parseFloat($balances.attr('data-initial-deposit-balance')),
            initialItemBalance = parseFloat($itemBalance.attr('data-initial-amount')),
            initialTransactionBalance = parseFloat($transactionBalance.attr('data-initial-amount')),
            initialCurrency = $balances.attr('data-initial-currency'),

            limitCurrency = $limit.attr('data-currency').toString(),
            limit = $limit.val(),

            totalBalance = initialBalance,
            depositBalance = initialDepositBalance,

            itemBalance = initialItemBalance,
            transactionBalance = initialTransactionBalance;

        limit = limit ? limit : 0;

        if (isNaN(limit)) {
            limit = 0;
        }
        totalBalance = self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), totalBalance, initialCurrency, self.expenseSummary.currency);
        depositBalance = self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), depositBalance, initialCurrency, self.expenseSummary.currency);
        itemBalance = self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), itemBalance, initialCurrency, self.expenseSummary.currency);
        transactionBalance = self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), transactionBalance, initialCurrency, self.expenseSummary.currency);
        limit = self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), limit, limitCurrency, self.expenseSummary.currency);
        $('.limit').attr('data-currency', self.expenseSummary.currency.toUpperCase());

        var searchedItemBalance = $('.item-search-balance').attr('data-value');
        searchedItemBalance = parseFloat(self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), searchedItemBalance, initialCurrency, self.expenseSummary.currency));

        var searchedTransactionBalance = $('.transaction-search-balance').attr('data-value');
        searchedTransactionBalance = parseFloat(self.convertCurrency(self.templatesElem.attr('data-expense-creation-date'), searchedTransactionBalance, initialCurrency, self.expenseSummary.currency));

        this.itemContainer.find('.template.item.added').each(function (index, elem) {
            var date = $(this).attr('data-date'),
                amount = $(this).find('.amount').val(),
                currency = $(this).find('.currency').attr('data-value'),
                $expenseValue = $(this).find('.expense-value'),
                isDeposit = $(this).hasClass('deposit'),
                isRefund = $(this).hasClass('refund');

            if (!amount || !currency || !self.expenseSummary.currency) {
                amount = 0;
            } else {
                amount = parseFloat(amount);
            }

            var itemValue = self.convertCurrency(date, parseFloat(amount), currency, self.expenseSummary.currency);

            if (isDeposit && !isRefund) {
                depositBalance += itemValue;
            } else if (isDeposit && isRefund) {
                depositBalance -= itemValue;
            }

            if (isRefund) {
                totalBalance -= itemValue;
                itemBalance -= itemValue
            } else {
                totalBalance += itemValue;
                itemBalance += itemValue
            }

            if (isDeposit) {
                isAnyDeposit = true;
            }

            if (self.expenseSummary.currency && currency != self.expenseSummary.currency) {
                $expenseValue.text(self.formatAmount(itemValue) + self.expenseSummary.currency.toUpperCase());
            } else {
                $expenseValue.text('');
            }
        });

        this.transactionContainer.find('.transaction.added').each(function (index, elem) {
            if (parseInt($(this).attr('data-existing')) && parseInt($(this).attr('data-is-voided'))) {
                // skip deleted transactions
            } else {
                var date = $(this).attr('data-date'),
                    amount = $(this).find('.amount').val(),
                    currency = $(this).find('.currency-sufix').text(),
                    $expenseValue = $(this).find('.expense-value'),
                    isRefund = $(this).hasClass('refund'),
                    transactionValue;

                if (!amount || currency == '' || !self.expenseSummary.currency) {
                    amount = 0;
                } else {
                    amount = parseFloat(amount);
                }

                if (currency.length < 1) {
                    // do nothing
                } else {
                    currency = currency.toLowerCase();
                    transactionValue = self.convertCurrency(date, parseFloat(amount), currency, self.expenseSummary.currency);

                    // Depends on transaction type, balance calculation can change
                    if (isRefund) {
                        totalBalance += transactionValue;
                        transactionBalance += transactionValue;
                    } else {
                        totalBalance -= transactionValue;
                        transactionBalance -= transactionValue;
                    }

                    if (currency != self.expenseSummary.currency && amount) {
                        $expenseValue.text(self.formatAmount(transactionValue) + self.expenseSummary.currency.toUpperCase());
                    } else {
                        $expenseValue.text('');
                    }
                }
            }
        });

        self.expenseSummary.balance = totalBalance;
        self.expenseSummary.depositBalance = depositBalance;
        self.expenseSummary.limit = limit;
        self.expenseSummary.item.balance = itemBalance;
        self.expenseSummary.transaction.balance = transactionBalance;
        $itemBalance.attr('data-amount', itemBalance);

        var limitMinusItemBalance = limit - itemBalance;
        limitMinusItemBalance = limitMinusItemBalance.toFixed(2);
        $('.limit-minus-item-balance').text(self.formatAmount(parseFloat(limitMinusItemBalance), 0));

        totalBalance = self.formatAmount(totalBalance);
        depositBalance = self.formatAmount(depositBalance);
        itemBalance = self.formatAmount(itemBalance);
        transactionBalance = self.formatAmount(Math.abs(transactionBalance));

        limit = limit.toFixed(2);

        $('.balance .balance-amount').text(totalBalance);
        $depositBalance.find('.balance-amount').text(self.formatAmount(parseFloat(depositBalance), 0));
        var depositBalanceClass = 'hidden';
        if (depositBalance > 0) {
            depositBalanceClass = 'color-success';
        } else if(depositBalance > 0) {
            depositBalanceClass = 'color-danger';
        }
        $depositBalance.removeClass('hidden').removeClass('label-success').removeClass('label-danger').addClass(depositBalanceClass);
        $depositBalance.find('.balance-amount').removeClass('hidden').removeClass('label-success').removeClass('label-danger').addClass(depositBalanceClass);

        $limitBalance.find('.balance-amount').text(self.formatAmount(parseFloat(limit), 0));
        $itemBalance.text(itemBalance);
        $transactionBalance.text(transactionBalance);
        $limit.val(limit);
        $('.item-search-balance').text(self.formatAmount(searchedItemBalance));
        $('.transaction-search-balance').text(self.formatAmount(searchedTransactionBalance));
    },

    convertCurrency: function (date, sourceAmount, sourceCurrency, destinationCurrency) {
        if (sourceCurrency == '' || destinationCurrency == '' || destinationCurrency == null) {
            return sourceAmount;
        }

        if (date == undefined || this.reservoir.currencyListEx[date] == undefined) {
            date = this.templatesElem.attr('data-expense-creation')
        }

        return sourceAmount
            * this.reservoir.currencyListEx[date][destinationCurrency.toUpperCase()]['value']
            / this.reservoir.currencyListEx[date][sourceCurrency.toUpperCase()]['value'];
    },

    formatAmount: function (amount, numberOfDecimals) {
        numberOfDecimals = typeof numberOfDecimals !== 'undefined' ?  numberOfDecimals + 1 : 2;
        var formatted = amount.toFixed(numberOfDecimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');

        // It's a javascript specific fix
        if (parseFloat(formatted) == 0 && formatted.indexOf('-') !== false) {
            formatted = formatted.replace('-', '');
        }

        if (numberOfDecimals == 1) {
            formatted = formatted.substring(0, formatted.length - 2)
        }

        return formatted;
    },

    formIsValid: function () {
        return this.form.valid();
    },

    checkForOptimalUploadCount: function () {
        var self = this,
            filesCount = self.attachments.get(0).files.length;

        $('.template.item').each(function () {
            var $attachment = $(this).find('.items-attachments');

            if ($(this).parent().attr('id') == '#templates' || $(this).hasClass('not-editable')) {
                return true; //continue
            }

            var itemFiles = $attachment.get(0).files;

            if (itemFiles.length && (typeof itemFiles[0] != 'undefined') && (itemFiles[0] instanceof File)) {
                if (++filesCount >= self.optimalUploadCount) {
                    notification({
                        'status': 'warning',
                        'msg': 'It is highly recommended not to upload more then ' + self.optimalUploadCount + ' files at once'
                    });

                    return false;
                }
            }
        });
    },

    submitTicket: function (data) {
        var self = this,
            formData = new FormData(),
            files = this.attachments.get(0).files,
            k = 0;

        formData.append('data', JSON.stringify(data));

        if (files.length) {
            for (var i = 0; i < files.length; i++) {
                formData.append('files[' + (i) + ']', files[i]);
            }
        }

        $('.template.item.added').each(function () {
            var $item = $(this),
                $attachment = $item.find('.items-attachments'),
                itemFiles = $attachment.get(0).files;

            if ($item.parent().attr('id') == '#templates' || $item.hasClass('not-editable')) {
                return true; // continue
            }

            if (!itemFiles.length || (typeof itemFiles[0] == 'undefined') || !(itemFiles[0] instanceof  File)) {
                k++;
                return true; // continue
            }

            formData.append('files[item_' + (k++) + ']', itemFiles[0]);
        });

        this.submit.button('loading');

        $.ajax({
            url: this.templatesElem.attr('data-save-url'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            error: function () {
                notification({
                    status: 'error',
                    msg: 'ERROR! Something went wrong.'
                });
            },
            success: function (data) {
                if (data.status == 'success') {
                    if (self.isEdit) {
                        location.reload();
                    } else {
                        location.href = location.pathname + '/' + data.ticketId;
                    }
                } else {
                    notification(data);
                    self.submit.button('reset');
                }
            }
        });
    },

    prepareDataToSubmit: function () {
        var self = this,
            $purpose = $('#purpose'),
            $title = $('#title'),
            $manager = $('#ticket-manager'),
            $comment = $('#comment-writer'),
            $expectedCompletionDate = $('#expected-completion-date'),
            $budget = $('#budget'),
            $currency = $('.expense-currency .display'),
            isRejected = (self.statusId == 3);

        return {
            isEdit: this.isEdit,
            ticket: {
                id: this.ticketId,
                limit: this.expenseSummary.limit,
                currencyId: $currency.attr('data-currency-id'),
                managerId: $manager.val(),
                title: $title.val(),
                purpose: $purpose.val(),
                comment: $comment.val(),
                budget: $budget.val(),
                expectedCompletionDate: $expectedCompletionDate.val(),
                resubmission: isRejected ? 1 : 0,
                balance: {
                    ticket: this.expenseSummary.balance,
                    deposit: this.expenseSummary.depositBalance,
                    item: this.expenseSummary.item.balance,
                    transaction: this.expenseSummary.transaction.balance
                }
            },
            items: this.getItemData(),
            transactions: this.getTransactionData(),
            attachments: this.getDeletedAttachments()
        }
    },

    getItemData: function () {
        var order = 0,
            items = {
                add: []
            };

        this.itemContainer.find('.template.added').each(function () {
            var $element = $(this),
                $transaction = $element.find('.new-item-transaction-id'),
                $amount = $element.find('.amount'),
                $account = $element.find('.account'),
                $currency = $element.find('.currency'),
                $period = $element.find('.period'),
                $type = $element.find('.type'),
                $subCategory = $element.find('.sub-category'),
                $accountReference = $element.find('.supplier-reference'),
                $accountComment = $element.find('.item-comment'),
                $costCenter = $element.find('.item-cost-centers'),
                $costCenterElements = $costCenter.find('.selectize-input > div'),
                accountReferenceVal = $accountReference.val(),
                accountCommentVal = $accountComment.val(),
                costCenterList = [],
                data = {
                    transactionId: $transaction.val(),
                    accountId: $account[0].selectize.getValue(),
                    accountReference: accountReferenceVal,
                    accountComment: accountCommentVal,
                    costCenters: costCenterList,
                    amount: $amount.val(),
                    currencyId: $currency.find('.display').attr('data-currency-id'),
                    subCategoryId: $subCategory.val(),
                    period: $period.val(),
                    type: $type.val(),
                    isStartup: $element.find('.is_startup').is(':checked') ? 1 : 0,
                    isDeposit: $element.find('.is_deposit').is(':checked') ? 1 : 0,
                    isRefund: $element.find('.is_refund').is(':checked') ? 1 : 0,
                    order: order++
                };

            $costCenterElements.each(function () {
                switch ($(this).attr('data-account')) {
                    case 'people':
                        costCenterList.push({
                            id: $(this).attr('data-section-id'),
                            type: 'officeSection',
                            currencyId: $(this).attr('data-currency-id'),
                            unique_id: '2_' + $(this).attr('data-section-id')
                        });

                        break;
                    case 'supplier':
                    case 'affiliate':
                        costCenterList.push({
                            id: $(this).attr('data-id'),
                            type: $(this).attr('data-type') == '1' ? 'apartment' : $(this).attr('data-type') == '2' ? 'officeSection' : 'group',
                            currencyId: $(this).attr('data-currency-id'),
                            unique_id: $(this).attr('data-type') + '_' + $(this).attr('data-id')
                        });

                        break;
                }
            });

            items['add'].push({
                id: null,
                data: data
            });
        });

        return items;
    },

    getTransactionData: function () {
        var transactions = {
            add: []
        };

        if (this.isFinance) {
            this.transactionContainer.find('.template.added').each(function () {
                var $element = $(this),
                    isVerified = $element.attr('data-is-verified'),
                    isVoided = $element.attr('data-is-voided'),
                    $transaction = $element.find('.id'),
                    $accountFrom = $element.find('.account-from'),
                    $accountTo = $element.find('.account-to'),
                    $amount = $element.find('.amount'),
                    $date = $element.find('.transaction-date'),
                    accountToSelectize = $accountTo[0].selectize,
                    accountToVal = accountToSelectize.getValue(),
                    data = {
                        accountFrom: {
                            id: $accountFrom.val()
                        },
                        accountTo: {
                            id: accountToSelectize.sifter.items[accountToVal].account_id,
                            type: accountToSelectize.sifter.items[accountToVal].type,
                            transactionAccountId: accountToVal
                        },
                        tmpId: $transaction.text(),
                        amount: $amount.val(),
                        date: $date.val(),
                        isRefund: $element.find('.is_refund').is(':checked') ? 1 : 0,
                        isVerified: isVerified ? parseInt(isVerified) : 0,
                        isVoided: isVoided ? parseInt(isVoided) : 0
                    };

                transactions['add'].push(data);
            });
        }

        return transactions;
    },

    getDeletedAttachments: function () {
        var self = this,
            list = [];

        self.attachmentPreview.find('li.deleted').each(function () {
            list.push($(this).attr('data-id'));
        });

        return list;
    },


    _listenerRemoveAttachmentTicket: function () {
        var self = this;

        $(document).delegate('.attachments-preview .remove', 'click', function (e) {
            var $li = $(this).closest('li');

            if (!$li.hasClass('new')) {
                $li.addClass('deleted');

                // delete by id
            } else {
                self.attachments.val(null);
                self.attachmentPreview.find('li:not(.predefined)').remove();
            }
        });
    },
    _listenerRemoveAttachmentItems: function () {
        $(document).delegate('.attachments-items-preview .remove-file', 'click', function (e) {
            var $li = $(this).closest('li');
            var $container = $li.closest('ul.list-inline');
            var $item = $container.closest('.item');
            var $attachment = $item.find('.items-attachments');

            $attachment.val(null);
            $container.empty();
        });
    },
    _listenerRestoreAttachment: function () {
        $(document).delegate('.attachments-preview .restore', 'click', function (e) {
            var $li = $(this).closest('li');

            if (!$li.hasClass('new')) {
                $li.removeClass('deleted');

                // unmark from deletion
            } else {
                // add to buffer
            }
        });
    },
    _listenerDetectAmountChanges: function () {
        var self = this;

        $(document).delegate('.amount', 'keyup', function (e) {
            e.preventDefault();

            self.calculateBalance();
        });
    },

    _listenerAddItem: function () {
        var self = this;

        self.itemAdd.on('click', function (e) {
            e.preventDefault();

            // Draw item and init category
            var $item = self.drawItem(),
                $poCurrencyElement = $('.expense-currency'),
                $subCategory = $item.find('.sub-category').selectize({
                    options: self.reservoir.subCategoryListSimple,
                    onChange: function (value) {
                        if (!value.length) {
                            return;
                        }

                        $item.find('.category')[0].selectize.addItem(
                            this.sifter.items[value].categoryId, true
                        );
                    }
                });

            $item.find('.category').selectize({
                options: self.reservoir.categoryList,
                onChange: function (value) {
                    var subCategorySelectize = $subCategory[0].selectize;

                    if (!value.length) {
                        subCategorySelectize.disable();
                        subCategorySelectize.clearOptions();

                        self.reservoir.subCategoryListSimple.forEach(function (item, value) {
                            subCategorySelectize.addOption(item);
                        });

                        subCategorySelectize.refreshOptions(false);
                        subCategorySelectize.enable();

                        return;
                    }

                    subCategorySelectize.disable();
                    subCategorySelectize.clearOptions();

                    for (var i in self.reservoir.subCategoryList[value]) {
                        if (self.reservoir.subCategoryList[value].hasOwnProperty(i)) {
                            subCategorySelectize.addOption(self.reservoir.subCategoryList[value][i]);
                        }
                    }

                    subCategorySelectize.refreshOptions(false);
                    subCategorySelectize.enable();
                    subCategorySelectize.focus();
                }
            });

            // Reincarnate currency
            self._listenerCustomSelect();
            self.__setupCustomSelect($item);

            // Reincarnate item creator
            $item.find('.item-creator').tooltip();

            self.itemCount++;

            if ($poCurrencyElement.length) {
                $item.find('.currency a[data-currency-id="' + $poCurrencyElement.attr('data-currency-id') + '"]').trigger('click');
            }

            self.applyValidaton();
        });
    },

    _listenerAddTransaction: function () {
        var self = this;

        if (self.isFinance) {
            self.transactionAdd.on('click', function (e) {
                e.preventDefault();

                // Draw transaction
                var $transaction = self.drawTransaction(),
                    $accountFrom = $transaction.find('.account-from');

                // Init datepicker
                var $dp = $transaction.find('.transaction-date').daterangepicker({
                    'singleDatePicker': true,
                    'format': globalDateFormat
                });

                $dp.on('changeDate', function (e) {
                    $(this).focus();
                }).on('blur', function (e) {
                    var val = $(this).val(),
                        matches = val.match(/^([0-9]{2})[.:-\\\/]([0-9]{2})[.:-\\\/]([0-9]{4})$/);

                    if ($.isArray(matches) && matches.length == 4) {
                        var day = matches[1],
                            month = parseInt(matches[2]),
                            year = matches[3];

                        if (day > 0 && day < 31 && month > 0 && month < 13 && year > 2000 && year < 3000) {
                            $(this).val(matches[1] + ' ' + months[parseInt(matches[2])] + ' ' + matches[3]).focus();
                        }
                    }
                });

                // Recover elements
                $accountFrom.selectize({
                    plugins: ['remove_button'],
                    valueField: 'id',
                    labelField: 'name',
                    sortField: 'name',
                    searchField: ['name'],
                    persist: false,
                    hideSelected: true,
                    highlight: false,
                    options: self.reservoir.allowedMoneyAccountList,
                    render: {
                        option: function (item, escape) {
                            return '<div><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
                        },
                        item: function (item, escape) {
                            $transaction.find('.currency-sufix').text(escape(item.currency));

                            return '<div data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
                        }
                    },
                    onItemAdd: function (value, item) {
                        self.calculateBalance();
                    },
                    onItemRemove: function (value) {
                        self.calculateBalance();
                    }
                });

                // Reincarnate currency
                self._listenerCustomSelect();
                self.__setupCustomSelect($transaction);

                self.transactionCount++;

                self.applyValidaton();
            });
        }
    },

    _listenerIsStartup: function () {
        var self = this;

        $(document).delegate('.is_startup', 'change', function (e) {
            e.preventDefault();

            $(this).closest('.item').toggleClass('startup');

            if($(this).is(':checked')) {
                self.checkedStatusesCount++;
            } else {
                self.checkedStatusesCount--;
            }

            checkAvailableChackboxesCount(self.checkedStatusesCount);

            self.calculateBalance();
            self.markAsChecked($(this));
        });
    },

    _listenerIsDeposit: function () {
        var self = this;

        $(document).delegate('.is_deposit', 'change', function (e) {
            e.preventDefault();

            var $item = $(this).closest('.item');

            $item.toggleClass('deposit');

            if($(this).is(':checked')) {
                self.checkedStatusesCount++;
            } else {
                self.checkedStatusesCount--;
            }

            checkAvailableChackboxesCount(self.checkedStatusesCount);

            self.calculateBalance();
            self.markAsChecked($(this));
        });
    },

    _listenerIsRefund: function () {
        var self = this;

        $(document).delegate('.is_refund', 'change', function (e) {
            e.preventDefault();

            var $template = $(this).closest('.template');

            $template.toggleClass('refund');

            if($(this).is(':checked')) {
                self.checkedStatusesCount++;
            } else {
                self.checkedStatusesCount--;
            }

            checkAvailableChackboxesCount(self.checkedStatusesCount);

            self.calculateBalance();
            self.swapAccount($(this).closest('.template'));
            self.markAsChecked($(this));
        });
    },

    _listenerRemoveTemplateElem: function () {
        var self = this;

        $(document).delegate('.template .remove.noselect', 'click', function (e) {
            e.preventDefault();

            var $templateElem = $(this).closest('.template');

            if ($templateElem.hasClass('added')) {
                if ($templateElem.hasClass('item')) {
                    self.itemCount--;
                } else if ($templateElem.hasClass('transaction')) {
                    self.transactionCount--;
                }

                $templateElem.hide('fast', function () {
                    $templateElem.remove();
                    self.calculateBalance();
                });
            }
        });
    },

    _listenerCustomSelect: function () {
        var self = this;

        $('.custom-select li a').on('click', function (e, manual) {
            e.preventDefault();

            var $element = $(this).closest('.custom-select'),
                $poCurrencyElement = $('.expense-currency'),
                currencyId = $(this).attr('data-currency-id'),
                currencyText = $(this).text(),
                currencyValue = $(this).attr('data-value');

            $element.attr('data-value', currencyValue);
            $element.find('.display').text(currencyText);
            $element.find('.display').attr('data-currency-id', currencyId);
            $element.trigger('change');

            // Use first item currency as a ticket currency
            if (manual == undefined && self.itemCount == 1 && !parseInt(self.templatesElem.attr('data-item-count'))) {
                $poCurrencyElement.attr('data-value', currencyValue);
                $poCurrencyElement.attr('data-currency-id', currencyId);
                $poCurrencyElement.find('.display')
                    .text(currencyText)
                    .attr('data-currency-id', currencyId);

                $poCurrencyElement.trigger('change');
            }

            self.calculateBalance();
        });
    },

    _listenerExpenseCurrency: function () {
        var self = this;

        $('.expense-currency').on('change', function (e) {
            e.preventDefault();

            self.expenseSummary.currency = $(this).attr('data-value');
            self.expenseSummary.currencyId = parseInt($(this).find('.display').attr('data-currency-id'));

            $('.expense-ticket-currency').text(
                $(this).attr('data-value').toUpperCase()
            );

            $('.limit-currency').text(
                $(this).attr('data-value').toUpperCase()
            );

            if (self.expenseSummary.currency && self.expenseSummary.balance) {
                self.calculateBalance();
            }

        });
    },

    _listenerSubmitButton: function () {
        var self = this;
        self.submit.on('click', function (e) {
            e.preventDefault();

            var $limit = $('.limit'),
                limit = $limit.length ? parseFloat($limit.val()) : 0,
                itemBalance = parseFloat($('.item-balance').attr('data-amount')),
                isAwaitingApproval = (self.statusId == 1);

            if (self.formIsValid()) {
                if (limit && limit < itemBalance && !isAwaitingApproval) {
                    $('.limit-exceeded-btn').trigger('click');
                } else {
                    var ticketData = self.prepareDataToSubmit();

                    self.submitTicket(ticketData);
                }
            }
        });
    },

    _listenerAttachmentsTicket: function () {
        var self = this;

        this.attachments.on('change', function (e) {
            var files = e.target.files,
                output = [],
                $container = self.attachmentPreview.find('ul.list-inline');

            // remove all non-predefined attachment thumbs
            self.attachmentPreview.find('li').not('.predefined').remove();

            if (!$container.length) {
                $container = $('<ul class="list-inline"></ul>').appendTo(self.attachmentPreview);
            }

            for (var i = 0, f; f = files[i]; i++) {
                if (f.type.match(/image.*/)) {
                    var reader = new FileReader();

                    reader.onload = (function (file, oOutput) {
                        return function (e) {
                            var remove = '<div class="remove transition noselect" title="Remove"><i class="glyphicon glyphicon-remove"></i></div>',
                                size = ['<div class="size transition">', self.bytesToSize(file.size), '</div>'].join(''),
                                bg = ['background-image:url(', e.target.result, ')'].join('');

                            $container.append(['<li class="new transition" style="', bg, '">', remove, size, '</li>'].join(''));
                        };
                    })(f, output);

                    reader.readAsDataURL(f);
                } else {
                    var remove = '<div class="remove transition noselect" title="Remove"><i class="glyphicon glyphicon-remove"></i></div>',
                        size = ['<div class="size transition">', self.bytesToSize(f.size), '</div>'].join(''),
                        extension = ['<strong class="transition">', escape(f.name.split('.').pop()), '</strong>'].join('');

                    $container.append(['<li class="new transition">', remove, extension, size, '</li>'].join(''));
                }
                self.checkForOptimalUploadCount();
            }
        });
    },

    _listenerAttachmentsItems: function () {
        var self = this;

        $(document).delegate('.upload-item-attachment', 'click', function (e) {
            e.preventDefault();

            $(this).closest('.row').find('.items-attachments').trigger('click');
        });

        $(document).delegate('.items-attachments', 'change', function (e) {
            var files = e.target.files,
                filename = 'Attach';

            if (files.length) {
                filename = files[0].name;

                if (filename.length > 20) {
                    filename = $.trim(filename.substr(0, 10)) + '...' + $.trim(filename.substr(-8));
                }
            }

            $(this).closest('.row').find('.upload-item-attachment span').text(filename);
        });
    },

    _listenerUniversalModal: function () {
        var self = this;

        // Custom handling
        $('.approve').on('click', function (e) {
            if (self.form.valid()) {
                var $targetElement = this,
                    limit = parseFloat(parseFloat($('.limit').val()).toFixed(2)),
                    itemBalance = parseFloat((self.expenseSummary.item.balance).toFixed(2));

                if (limit < itemBalance) {
                    $targetElement = $('.limit-exceeded-btn');
                }

                self.modal.modal('toggle', $targetElement);
            }
        });

        self.modal.on('show.bs.modal', function (event) {
            var $button = $(event.relatedTarget),
                $modal = $(this),
                $actionButton = $modal.find('.modal-footer .action'),
                title = $button.attr('data-title'),
                body = $button.attr('data-body'),
                context = $button.attr('data-context'),
                action = $button.attr('data-action'),
                actionName = $button.attr('data-action-name'),
                actionState = $button.attr('data-action-state'),
                url = $button.attr('data-url'),
                data = {};

            $modal.find('.modal-title')
                .text(title)
                .parent()
                .removeClass('bg-primary')
                .removeClass('bg-danger')
                .removeClass('text-danger')
                .addClass('bg-' + actionState);
            $modal.find('.modal-footer .action')
                .text(actionName)
                .removeClass('btn-primary')
                .removeClass('btn-danger')
                .addClass('btn-' + actionState);

            if (body) {
                $modal.find('.modal-body').html(body);
            } else {
                $modal.find('.modal-body').text('Are you sure?');
            }

            if (action == 'delete' || action == 'confirm-limit-exceedance') {
                $modal.find('.modal-title').parent().addClass('text-danger');
            }

            if (action == 'approve' && !self.form.valid()) {
                $actionButton.prop('disabled', true);
            } else {
                $actionButton.prop('disabled', false);
            }

            $actionButton.click(function () {
                $actionButton.off('click');

                if (context == 'ticket') {
                    if (action == 'confirm-limit-exceedance') {
                        var ticketData = self.prepareDataToSubmit();

                        self.submitTicket(ticketData);
                    } else {
                        if (action == 'approve') {
                            data = {
                                limit: self.expenseSummary.limit
                            };
                        }

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: data,
                            error: function () {
                                notification({
                                    status: 'error',
                                    msg: 'Something went wrong'
                                });
                            },
                            success: function (data) {
                                if (data.status == 'success') {
                                    var path = location.pathname,
                                        pathSegments = path.split('/');

                                    if ($.inArray(action, ['approve', 'reject', 'close']) !== -1) {
                                        // do nothing. just reload page as it is
                                    } else {
                                        pathSegments.pop();

                                        if (action == 'duplicate') {
                                            pathSegments.push(data.ticketId);
                                        } else if (action == 'delete') {
                                            pathSegments.pop();
                                        }
                                    }

                                    location.href = pathSegments.join('/');
                                } else {
                                    notification(data);
                                    $modal.modal('hide');
                                }
                            }
                        });
                    }
                } else if (context == 'ticket-element') {
                    var $templateElem = $button.closest('.template');

                    if (action == 'verify') {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            error: function () {
                                notification({
                                    status: 'error',
                                    msg: 'Something went wrong (transaction verification)'
                                });
                            },
                            success: function (data) {
                                if (data.status == 'success') {
                                    $('.transaction-search').trigger('click');
                                }

                                notification(data);
                            }
                        });
                    } else if (action == 'void') {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            error: function () {
                                notification({
                                    status: 'error',
                                    msg: 'Something went wrong (transaction void)'
                                });
                            },
                            success: function (data) {
                                if (data.status == 'success') {
                                    $('.balances').attr('data-initial-balance', data.data.ticket_balance);
                                    $('.transaction-balance').attr('data-initial-amount', data.data.transaction_balance);
                                    self.calculateBalance();

                                    $('.transaction-search').trigger('click');
                                }

                                notification(data);
                            }
                        });
                    } else {
                        if ($templateElem.hasClass('item')) {
                            self.itemCount--;

                            $.ajax({
                                url: $templateElem.attr('data-delete-url'),
                                type: 'POST',
                                data: {
                                    id: $templateElem.attr('data-item-id')
                                },
                                error: function () {
                                    notification({
                                        status: 'error',
                                        msg: 'Something went wrong (item deletion)'
                                    });
                                },
                                success: function (data) {
                                    if (data.status == 'success') {
                                        var $balances = $('.balances'),
                                            $itemBalance = $('.item-balance');

                                        $templateElem.hide('fast', function () {
                                            var $itemCount = $('.item-count');

                                            $templateElem.remove();
                                            $itemCount.text(
                                                parseInt($itemCount.text()) - 1
                                            );
                                        });

                                        $balances.attr('data-initial-balance', data.data.ticket_balance);
                                        $balances.attr('data-initial-deposit-balance', data.data.deposit_balance);
                                        $itemBalance.attr('data-initial-amount', data.data.item_balance);

                                        self.calculateBalance();
                                    }

                                    notification(data);
                                }
                            });
                        } else if ($templateElem.hasClass('transaction')) {
                            self.transactionCount--;
                        } else {
                            return false;
                        }
                    }

                    $modal.modal('hide');
                }
            });
        });
    },

    // Transaction Verification
    _listenerVerify: function () {
        if (this.isFinance) {
            $('.verify').on('click', function () {
                var $button = $(this),
                    $templateElem = $button.closest('.template');

                $templateElem.attr('data-is-verified', 1);
                $button.closest('.header').find('a').each(function () {
                    $(this).hide('fast', function () {
                        $button.closest('.header').html('<span class="label label-success"><i class="glyphicon glyphicon-ok-sign"></i> Verified</span>')
                    });
                });
            });
        }
    },

    // Revoke approvments
    _listenerRevoke: function () {
        var $revoke = $('.revoke');

        $revoke.click(function (e) {
            e.preventDefault();

            var button = $(this),
                url = button.attr('data-href');

            button.button('loading');

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                success: function (data) {
                    if (data.status == 'success') {
                        location.reload();
                    } else {
                        button.button('reset');
                        notification(data);
                    }
                }
            });
        });
    },

    // Expense readiness
    _listenerReady: function () {
        var $ready = $('.ready');

        $ready.click(function (e) {
            e.preventDefault();

            var button = $(this),
                url = button.attr('data-href');

            button.button('loading');

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                success: function (data) {
                    if (data.status == 'success') {
                        location.reload();
                    } else {
                        button.button('reset');
                        notification(data);
                    }
                }
            });
        });
    },

    // Expense settlement
    _listenerSettle: function () {
        if (this.isFinance) {
            var $settle = $('.settle');

            $settle.click(function (e) {
                e.preventDefault();

                var button = $(this),
                    url = button.attr('data-href');

                button.button('loading');

                $.ajax({
                    type: 'POST',
                    url: url,
                    dataType: 'json',
                    success: function (data) {
                        if (data.status == 'success') {
                            location.reload();
                        } else {
                            button.button('reset');
                            notification(data);
                        }

                        $('.item-inner-container .remove').remove();
                        $('.btn-void-transaction').remove();
                    }
                });
            });
        }
    },

    // Remove expense settlement
    _listenerUnsettle: function () {
        if (this.isUnlocker) {
            var $unsettle = $('.unsettle');

            $unsettle.click(function (e) {
                e.preventDefault();

                var button = $(this),
                    url = button.attr('data-href');

                button.button('loading');

                $.ajax({
                    type: 'POST',
                    url: url,
                    dataType: 'json',
                    success: function (data) {
                        if (data.status == 'success') {
                            location.reload();
                        } else {
                            button.button('reset');
                            notification(data);
                        }
                    }
                });
            });
        }
    },

    // Show item cost centers
    _listenerShowLockedCostCenters: function () {
        $(document).on('click', '.see-all', function (e) {
            e.preventDefault();

            $(this).parent().find('.hide').removeClass('hide');
            $(this).remove();
        })
    },

    // Upload attachment
    _listenerExpenseAttachment: function () {
        $('.upload-attachment').click(function (e) {
            e.preventDefault();

            $('#attachments').trigger('click');
        });
    },

    // Find similar items/transactions
    _listenerConnections: function () {
        $('.item-container').on('click', '.linked-items', function (e) {
            e.preventDefault();

            var template = $(this).closest('.template'),
                classes = template.attr('class'),
                transactions = /transaction-\d+/g.exec(classes);

            if (transactions && transactions.length) {
                $('.transaction').hide();
                $('.item').hide();

                $('.transaction.' + transactions[0]).show();
                template.show();
            }
        });

        $('.transaction-container').on('click', '.linked-transactions', function (e) {
            e.preventDefault();

            var template = $(this).closest('.template'),
                classes = template.attr('class'),
                items = classes.match(/item-\d+/g);

            if (items && items.length) {
                $('.transaction').hide();
                $('.item').hide();

                template.show();

                for (var i in items) {
                    if (items.hasOwnProperty(i)) {
                        $('.item.' + items[i]).show();
                    }
                }
            }
        });
    },

    // On manual change
    _listenerLimit: function () {
        var self = this,
            isAwaitingApproval = (self.statusId == 1),
            isRejected = (self.statusId == 3);

        $('.limit').on('blur', function () {
            if (!self.isEdit || (self.isEdit && (isAwaitingApproval || isRejected))) {
                self.calculateBalance();
            }
        });
    },

    // PO title border effect
    _listenerTitle: function () {
        $('.po-title').on('change, input', function () {
            if ($(this).val() == '') {
                $(this).removeClass('filled');
            } else {
                $(this).addClass('filled');
            }
        }).trigger('change');
    },

    // All currencies
    __setupCustomSelect: function ($element) {
        if ($element) {
            $element = $element.find('li a:first');
        } else {
            if (this.isEdit) {
                var ticketCurrencyElem = $('.custom-select:first'),
                    ticketCurrency = ticketCurrencyElem.attr('data-currency-id');

                if (ticketCurrency) {
                    ticketCurrencyElem.find('li a').each(function () {
                        if ($(this).attr('data-currency-id') == ticketCurrency) {
                            $element = $(this);
                        }
                    });
                }
            } else {
                $element = $('.custom-select li a:first');
            }
        }

        $element.trigger('click', [false]);
    },

    // Item and Transaction footer checkboxes
    __setupCheckboxes: function () {
        var $element = $('.template').find(':checkbox'),
            isChecked = 0;

        if ($element.is(':checked')) {
            isChecked = 1;
        }

        $element.closest('label').attr('data-is-checked', isChecked);
    },

    __setupPredefinedAttachments: function () {
        var attachments = jQuery.parseJSON(this.attachmentPreview.attr('data-attachments')),
            previewUrl = this.attachmentPreview.attr('data-preview-url'),
            downloadUrl = this.attachmentPreview.attr('data-download-url');

        if (attachments && attachments.length) {
            var $container = $('<ul class="list-inline"></ul>').appendTo(this.attachmentPreview);

            for (var index in attachments) {
                if (attachments.hasOwnProperty(index)) {
                    var imgUrl = previewUrl + '?id=' + attachments[index]['id'],
                        remove = '<div class="remove transition noselect" title="Remove"><i class="glyphicon glyphicon-remove"></i></div>',
                        restore = '<div class="restore transition noselect" title="Restore"><i class="glyphicon glyphicon-share-alt"></i></div>',
                        extension = ['<strong class="transition">', attachments[index]['extension'], '</strong>'].join(''),
                        download = ['<a class="download transition" title="Download" href="', downloadUrl, '?id=', attachments[index]['id'], '"><i class="glyphicon glyphicon-download"></i></a>'].join(''),
                        size = ['<div class="size transition">', attachments[index]['size'], '</div>'].join(''),
                        id = [' data-id="', attachments[index]['id'], '"'].join(''),
                        bg = ['background:url(', imgUrl, ') no-repeat center center;'].join('');

                    if (parseInt(attachments[index]['isImage'])) {
                        $container.append(['<li class="transition predefined"', id, ' style="', bg, '">', remove, restore, size, download, '</li>'].join(''));
                    } else {
                        $container.append(['<li class="transition predefined"', id, '>', remove, restore, extension, size, download, '</li>'].join(''));
                    }
                }
            }
        }
    },

    __setupSearchInputs: function () {
        var self = this,
            $searchFilters = $('.search-filters'),
            $supplier = $('.item-search-supplier'),
            $costCenter = $('.item-search-cost-center'),
            $category = $('.item-search-category'),
            $period = $('.item-search-period'),
            $accountFrom = $('.transaction-search-account-from'),
            $accountTo = $('.transaction-search-account-to');

        // Setup date pickers
        $searchFilters.find('.dp').daterangepicker({
            'singleDatePicker': true,
            'format': globalDateFormat
        });

        // Setup daterangepicker
        $period.daterangepicker({
            format: globalDateFormat,
            drops: 'up',
            locale: {
                firstDay: 1
            }
        });

        // Supllier
        $supplier.selectize({
            valueField: 'unique_id',
            labelField: 'name',
            searchField: ['name'],
            render: {
                option: function (item, escape) {
                    // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                    var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                    return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                },

                item: function (item, escape) {
                    return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                }
            },
            load: function (query, callback) {
                if (query.length < 2) {
                    return callback();
                }

                $.ajax({
                    url: self.templatesElem.attr('data-account-url'),
                    type: 'POST',
                    data: {'q': encodeURIComponent(query)},
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        callback(res.data);
                    }
                });
            },
            onType: function () {
                $supplier[0].selectize.clearOptions();
            },
            persist: false,
            hideSelected: true,
            highlight: false
        });

        // Cost Center
        $costCenter.selectize({
            plugins: ['remove_button'],
            valueField: 'unique_id',
            searchField: ['name', 'label'],
            persist: false,
            hideSelected: true,
            highlight: false,
            score: function () {
                return function (item) {
                    return item.type * 1000 + item.id;
                };
            },
            render: {
                option: function (item, escape) {
                    // Type definition: 1 - apartment, 2 - office, 3 - group
                    var type = parseInt(item.type),
                        label = (type == 1 ? 'primary' : (type == 2 ? 'success' : 'info'));

                    // Don't show groups
                    if (type == 3) {
                        return '';
                    }

                    return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                },
                item: function (item, escape) {
                    // Type definition: 1 - apartment, 2 - office, 3 - group
                    var type = parseInt(item.type),
                        label = (type == 1 ? 'primary' : (type == 2 ? 'success' : 'info'));

                    return '<div data-account="supplier" data-type="' + escape(type) + '" data-id="' + escape(item.id) + '" data-currency-id="' + escape(item.currency_id) + '"><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                }
            },
            load: function (query, callback) {
                if (query.length < 2) {
                    return callback();
                }

                $.ajax({
                    url: self.templatesElem.attr('data-cost-center-url'),
                    type: 'POST',
                    data: {'q': encodeURIComponent(query)},
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        callback(res.data);
                    }
                });
            },
            onType: function () {
                $costCenter[0].selectize.clearOptions();
            }
        });

        // Category
        $category.selectize({
            valueField: 'order',
            labelField: 'name',
            searchField: ['name'],
            sortField: [{
                field: 'order'
            }],
            options: self.reservoir.subCategoryAndCategoryList,
            render: {
                option: function (item, escape) {
                    var option = escape(item.name);

                    // 1 - Category, 2 - Sub Category
                    if (item.type == 1) {
                        option = '<strong>' + option + '</strong>';
                    } else {
                        option = '<span style="padding-left: 10px;">' + option + '</strong>';
                    }

                    return '<div>' + option + '</div>';
                },
                item: function (item, escape) {
                    return '<div data-id="' + escape(item.id) + '" data-type="' + escape(item.type) + '">' + escape(item.name) + '</div>';
                }
            },
            persist: false,
            hideSelected: false
        });

        // Account From
        $accountFrom.selectize({
            plugins: ['remove_button'],
            valueField: 'id',
            labelField: 'name',
            sortField: 'name',
            searchField: ['name'],
            persist: false,
            hideSelected: true,
            highlight: false,
            options: this.reservoir.moneyAccountList,
            render: {
                option: function (item, escape) {
                    return '<div><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
                },
                item: function (item, escape) {
                    return '<div data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.currency) + '</span> ' + escape(item.name) + ' <small class="text-info">' + escape(item.bank_name) + '</small></div>';
                }
            }
        });

        // Account To
        $accountTo.selectize({
            valueField: 'unique_id',
            labelField: 'name',
            searchField: ['name'],
            delimiter: ',',
            render: {
                option: function (item, escape) {
                    // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                    var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                    return '<div><span class="label label-' + label + '">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                },

                item: function (item, escape) {
                    return '<div data-name="' + escape(item.name) + '" data-type="' + escape(item.type) + '" data-id="' + escape(item.id) + '"><span class="label label-primary">' + escape(item.label) + '</span> ' + escape(item.name) + '</div>';
                }
            },
            load: function (query, callback) {
                if (query.length < 2) {
                    return callback();
                }

                $.ajax({
                    url: self.templatesElem.attr('data-account-url'),
                    type: 'POST',
                    data: {'q': encodeURIComponent(query)},
                    error: function () {
                        callback();
                    },
                    success: function (res) {
                        callback(res.data);
                    }
                });
            },
            onType: function () {
                $accountTo[0].selectize.clearOptions();
            },
            persist: false,
            hideSelected: true,
            highlight: false
        });

        // Event handling
        $('.item-search').click(function (e) {
            e.preventDefault();

            var costCenter = $costCenter[0].selectize.sifter.items[$costCenter.val()],
                category = $category[0].selectize.sifter.items[$category.val()],
                btn = $(this);

            costCenter = costCenter !== undefined ? costCenter.id + '_' + costCenter.type : '';
            category = category !== undefined ? category.id + '_' + category.type : '';

            btn.button('loading');

            var sendData = {
                poId: self.templatesElem.attr('data-ticket-id'),
                supplier: $supplier.val(),
                period: $period.val(),
                costCenter: costCenter,
                category: category,
                creationDate: $('.item-search-creation-date').val(),
                reference: $('.item-search-reference').val(),
                amount: $('.item-search-amount').val()
            };

            if (!self.checkItemSearchClickManual && GLOBAL_ITEM_ID > 0) {
                sendData.item_id = GLOBAL_ITEM_ID;
            }

            GLOBAL_ITEM_ID = 0;
            self.checkItemSearchClickManual = true;

            $.ajax({
                url: $searchFilters.attr('data-get-items-url'),
                type: 'POST',
                data: sendData,
                error: function () {
                    notification({
                        status: 'error',
                        msg: 'ERROR! Something went wrong (item list)'
                    });

                    btn.button('reset');
                },
                success: function (data) {
                    if (data.status = 'success') {
                        $('.item-inner-container').html(data.data.items);
                        $('.item-search-count').text(data.data.itemCount);
                        $('.item-search-balance').text(data.data.itemBalance);
                        $('.item-search-balance').attr('data-value', data.data.itemBalance.replace(/,/g, ''));

                        if (data.data.transactions) {
                            $('.transaction-inner-container').html(data.data.transactions);
                            $('.transaction-search-count').text(data.data.transactionCount);
                            $('.transaction-search-balance').text(Math.abs(data.data.transactionBalance));
                            $('.transaction-search-balance').attr('data-value', Math.abs(data.data.transactionBalance.replace(/,/g, '')));
                            self.drawTransactions(data);
                        }

                        self.drawItems(data);
                        self.calculateBalance();
                    }

                    btn.button('reset');
                }
            });
        });

        $('.transaction-search').click(function (e) {
            e.preventDefault();

            var btn = $(this);
            var sendData = {
                poId: self.templatesElem.attr('data-ticket-id'),
                accountFrom: $accountFrom.val(),
                accountTo: $accountTo.val(),
                transactionDate: $('.transaction-search-transaction-date').val(),
                creationDate: $('.transaction-search-creation-date').val(),
                amount: $('.transaction-search-amount').val()
            };

            if (!self.checkTransactionSearchClickManual && GLOBAL_TRANSACTION_ID > 0) {
                sendData.transactionId = GLOBAL_TRANSACTION_ID;
            }

            GLOBAL_TRANSACTION_ID = 0;
            self.checkTransactionSearchClickManual = true;

            btn.button('loading');

            $.ajax({
                url: $searchFilters.attr('data-get-transactions-url'),
                type: 'POST',
                data: sendData,
                error: function () {
                    notification({
                        status: 'error',
                        msg: 'ERROR! Something went wrong (transaction list)'
                    });

                    btn.button('reset');
                },
                success: function (data) {
                    if (data.status = 'success') {
                        $('.transaction-inner-container').html(data.data.transactions);
                        $('.transaction-search-count').text(data.data.transactionCount);
                        $('.transaction-search-balance').text(Math.abs(data.data.transactionBalance));
                        $('.transaction-search-balance').attr('data-value', Math.abs(data.data.transactionBalance.replace(/,/g, '')));

                        if (data.data.items) {
                            $('.item-inner-container').html(data.data.items);
                            $('.item-search-count').text(data.data.itemCount);
                            $('.item-search-balance').text(data.data.itemBalance);
                            $('.item-search-balance').attr('data-value', data.data.itemBalance.replace(/,/g, ''));
                            self.drawItems();
                        }

                        self.drawTransactions(data);
                        self.calculateBalance();

                    }

                    btn.button('reset');
                }
            });
        });
    },

    // After Item Search
    drawItems: function () {
        var self = this;
        var $transactionIdFields = $('.item-transaction-id');
        if (self.financeStatusId == 4) {
            $transactionIdFields.prop('disabled', 'disabled');
        }

        $transactionIdFields.each(function () {
            var $itemElem = $(this);

            $itemElem.selectize({
                create: true,
                persist: true,
                onItemAdd: function (value, $item) {
                    if (self.financeStatusId != 4) {
                        $.ajax({
                            url: $item.closest('.template').attr('data-attach-transaction-url'),
                            type: 'POST',
                            data: {
                                transactionId: value
                            },
                            error: function () {
                                notification({
                                    status: 'error',
                                    msg: 'ERROR! Something went wrong (attach item)'
                                });
                            },
                            success: function (data) {
                                if (data.status == 'error') {
                                    $itemElem[0].selectize.removeItem(value, true);
                                }

                                notification(data);
                            }
                        });
                    } else {
                        notification({
                            status: "error",
                            msg: "This PO is already settled."
                        })
                    }
                },
                onItemRemove: function (value, $item) {
                    $.ajax({
                        url: $('.item.item-' + $item.attr('data-item-id')).attr('data-detach-transaction-url'),
                        type: 'POST',
                        data: {
                            transactionId: value
                        },
                        error: function () {
                            notification({
                                status: 'error',
                                msg: 'ERROR! Something went wrong (detach item)'
                            });
                        },
                        success: function (data) {
                            if (data.status == 'error') {
                                $itemElem[0].selectize.addItem(value, true);
                            }

                            notification(data);
                        }
                    });
                },
                render: {
                    item: function (data) {
                        return "<div data-item-id='" + this.$control.closest('.template').attr('data-item-id') + "'>" + data.text + " </div>";
                    }
                }
            });
        });
    },

    // After Transaction Search
    drawTransactions: function (data) {
        $('.item-list').each(function () {
            var itemElem = $(this);

            itemElem.selectize({
                plugins: ['remove_button'],
                create: true,
                persist: true,
                onItemAdd: function (value, $item) {
                    $.ajax({
                        url: $item.closest('.transaction').attr('data-attach-item-url'),
                        type: 'POST',
                        data: {
                            itemId: value
                        },
                        error: function () {
                            notification({
                                status: 'error',
                                msg: 'ERROR! Something went wrong (attach item)'
                            });
                        },
                        success: function (data) {
                            if (data.status == 'error') {
                                itemElem[0].selectize.removeItem(value, true);
                            }

                            notification(data);
                        }
                    });
                },
                onItemRemove: function (value, $item) {
                    $.ajax({
                        url: $('.transaction.transaction-' + $item.attr('data-transaction-id')).attr('data-detach-item-url'),
                        type: 'POST',
                        data: {
                            itemId: value
                        },
                        error: function () {
                            notification({
                                status: 'error',
                                msg: 'ERROR! Something went wrong (detach item)'
                            });
                        },
                        success: function (data) {
                            if (data.status == 'error') {
                                itemElem[0].selectize.addItem(value, true);
                            }

                            notification(data);
                        }
                    });
                },
                render: {
                    item: function (data) {
                        return "<div data-transaction-id='" + this.$control.closest('.transaction').attr('data-transaction-id') + "'>" + data.text + " </div>";
                    }
                }
            });
        });
    }
});

function checkAvailableChackboxesCount(checkedStatusesCount) {
    if (checkedStatusesCount > 1) {
        $('#item-statuses :checkbox:not(:checked)').attr('disabled', 'disabled');
    } else {
        $('#item-statuses :checkbox').attr('disabled', false);
    }
}

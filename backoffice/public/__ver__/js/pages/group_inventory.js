var availabilityTable,
    cellWidth = 60,
    cellHeight = 25,
    cellThHeight = 50,
    paddingH = 2,
    paddingV = 2,
    borderSize = 1,
    apartmentIds;

$(function () {
    $apartmentGroupId = $('#apartment_group_id');

    var NOT_MOVABLE_BACKGROUND = '#f5f5f5 url(\'' + VERSION_PUBLIC_PATH + 'img/bg-dashed.png\')';

    if (jQuery().daterangepicker) {
        $dateRangePickeroptions = {
            ranges: {
                'Today': [moment(), moment()],
                'Next 7 Days': [moment(), moment().subtract(-6, 'days')],
                'Next 30 Days': [moment(), moment().subtract(-29, 'days')],
                'Until The End Of This Month': [moment(), moment().endOf('month')]
            },
            startDate: moment(),
            endDate: moment().subtract(-1, 'months'),
            format: 'YYYY-MM-DD'
        };

        $('#inventory_date_range').daterangepicker(
            $dateRangePickeroptions,
            function (start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
        );
    }

    $apartmentGroupId.selectize({
        create: false,
        plugins: ['remove_button'],
        valueField: 'id',
        labelField: 'name',
        searchField: ['name'],
        sortField: [
            {
                field: 'name'
            }
        ]
    });
    $apartmentGroupId[0].selectize.clear();

    $("#group-inventory-from").ajaxForm({
        // dataType identifies the expected content type of the server response
        dataType: 'json',
        beforeSubmit: formValidator,
        // success identifies the function to invoke when the server response
        // has been received
        success: function (data) {
            if (data.status == 'success') {
                // Save trap
                $('#save-btn').attr('data-group-id', $apartmentGroupId.val());

                // Process
                var main_output = '<table class="table table-striped" id="apartel_inventory_tbl"><tr>',
                        html_tbl_left = '<td><table class="table table-hover table-bordered left-tbl" style="border-right:none" id="left-tbl">';

                html_tbl_left += '<tr>\n\
                    <th data-name="Name" height="' + cellThHeight + 'px">Name</th>\n\
                    <th data-name="PAX" class="expandable" height="' + cellThHeight + 'px">PAX</th>\n\
                    <th data-name="Rooms" class="expandable" height="' + cellThHeight + 'px">Rooms</th>\n\
                    <th data-name="Floor" class="expandable" height="' + cellThHeight + 'px">Floor</th>\n\
                    <th data-name="Unit&nbsp;#" class="expandable" height="' + cellThHeight + 'px">Unit&nbsp;#</th>\n\
                    <th data-name="Building" class="expandable" height="' + cellThHeight + 'px">Building</th>\n\
                </tr>';

                var html_tbl_right = '<td style="overflow-x: scroll !important;">' +
                        '<div id="date-div">' +
                        '<table class="table table-bordered right-tbl" style="width: ' + (data.days.length * (cellWidth - (-borderSize)) - (-borderSize)) + 'px; margin-bottom: ' + (cellHeight - 2*borderSize) + 'px;">',
                        html_colorful_layer = '',
                        dailyAvailabilities = [],
                        relatedReservationColors = ['#DCE775', '#FF8A65', '#FFF176', '#4FC3F7', '#A1887F', '#4DB6AC', '#FFD54F', '#E57373', '#9575CD', '#7986CB'];

                apartmentIds = new Array(data.days.length);
                availabilityTable = new Array(data.days.length);

                for (i = 0; i < data.days.length; i++) {
                    availabilityTable[i] = new Array(data.list.length);

                    for (j = 0; j < data.list.length; j++) {
                        availabilityTable[i][j] = 0;
                    }
                }

                var tr_date = '<tr>';

                for (var rd in data.days) {
                    var date = data.days[rd],
                        str = (date.month) + '/' + date.day;
                    tr_date += '<th width="' + cellWidth + 'px" height="' + cellThHeight + 'px"><p class="text-muted">' + date.dayOfWeek.toUpperCase() + '</p>' + str + '</th>';
                }

                tr_date += '</tr>';
                html_tbl_right += '<thead>' + tr_date + '</thead>';

                var j = 1;

                for (var row_for_date in data.list) {
                    var item_date = data.list[row_for_date];
                    apartmentIds[j - 1] = item_date.id;

                    var html_tbl_right_row = [],
                            html_colorful_layer_row = [];

                    html_tbl_right += '<tr>';

                    var res_check = '',
                            i = 0,
                            first = true,
                            last = false;

                    for (var rw in data.days) {
                        var day = data.days[rw].raw,
                            daysObj = item_date[day],
                            resData = null,
                            isKIViewed = null,
                            movable,
                            resNumber = null,
                            color_td = '#81C784';

                        last = (i + 1 == data.days.length);

                        if(typeof daysObj != 'undefined') {
                            resData = daysObj.reservation_data
                        }

                        if (resData != null) {
                            var resDataParts = resData.split('_'),
                                dateFrom          = resDataParts[2],
                                dateTo            = resDataParts[3],
                                freeRes           = (resDataParts[4] == '0' ? 1 : 0),
                                usage_apartel     = (freeRes ? 'title="Non Apartel" ' : ''),
                                occupancy         = resDataParts[5],
                                guestBalance      = resDataParts[6],
                                resLocked         = resDataParts[7],
                                overbookingStatus = resDataParts[8];
                                resNumber         = resDataParts[0];
                                isKIViewed        = resDataParts[1];
                                channelResId      = resDataParts[9];
                        }

                        if (typeof daysObj === "undefined") {
                            html_tbl_right_row[i] = '<td height="' + cellHeight + 'px">&nbsp</td>';
                        } else {
                            var av = daysObj.av,
                                    clearing = '';

                            if (av == 1) {
                                html_tbl_right_row[i] = '<td height="' + cellHeight + 'px">&nbsp</td>';

                                if (dailyAvailabilities[day] === undefined) {
                                    dailyAvailabilities[day] = 0;
                                }

                                dailyAvailabilities[day]++;
                                res_check = '';
                            } else {
                                availabilityTable[i][j - 1] = 1;

                                // if on the edge, make not movable
                                movable = !(i == 0 || i == data.days.length - 1 || resLocked == '1');

                                if (first && movable == false) {
                                    clearing = ' clear-left';
                                }

                                if (last && movable == false) {
                                    clearing = ' clear-right';
                                }

                                color_td = (freeRes ? '#90a4ae' : color_td);

                                var ressplate = resNumber,
                                    resNumberFundament = '';
                                if (res_check != resNumber) {

                                    if (ressplate !== null) {
                                        html_tbl_right_row[i] = '<td width="' + cellWidth + 'px" height="' + cellHeight + 'px">&nbsp</td>';
                                        html_colorful_layer_row[i] = '<div ' +
                                                'style="' +
                                                'background:' + (movable ? color_td : NOT_MOVABLE_BACKGROUND) + ';' +
                                                'width: ' + (cellWidth - paddingH * 2) + 'px;' +
                                                'height: ' + (cellHeight - borderSize - paddingV * 2) + 'px;' +
                                                'line-height: ' + (cellHeight - paddingV * 2) + 'px;' +
                                                'margin: 2px 2px;' +
                                                'top: ' + (cellHeight + (j * cellHeight) - (-borderSize)) + 'px;' +
                                                'left: ' + (i * (cellWidth - (-borderSize)) - (-borderSize)) + 'px;" ' +
                                                'class="reservation-item' + (movable ? '' : ' unmovable') + (guestBalance < 0 ? ' text-danger' : '') + clearing + '" ' +
                                                'data-length="1" ' +
                                                'data-is-ki-viewed="' + isKIViewed + '" ' +
                                                'data-real-length="' + dateDiff(dateFrom, dateTo) + '" ' +
                                                'data-apartment-origin="' + apartmentIds[j - 1] + '" ' +
                                                'data-res-number="' + resNumber + '" ' +
                                                'data-channel-res-id="' + channelResId + '"' +
                                                usage_apartel +
                                                '>' +
                                                '<a href="/booking/edit/' + resNumber + '" target="_blank"> ' +
                                                '[' + occupancy + ']' +
                                                ((resLocked == '1') ? ' <span class="glyphicon glyphicon-lock"></span>' : '') + ' </a>' +
                                                '</div>';
                                    } else {
                                        html_tbl_right_row[i] = '<td width="' + cellWidth + 'px" height="' + cellHeight + 'px">&nbsp</td>';
                                        html_colorful_layer_row[i] = '<div ' +
                                                'style="' +
                                                'width: ' + (cellWidth) + 'px;' +
                                                'height: ' + (cellHeight - borderSize) + 'px;' +
                                                'top: ' + (cellHeight + (j * cellHeight) - (-borderSize)) + 'px;' +
                                                'left: ' + (i * (cellWidth - (-borderSize)) - (-borderSize)) + 'px;" ' +
                                                'class="unmovable closed-day" ' +
                                                'data-length="1" ' +
                                                '>' +
                                                'Closed' +
                                                '</div>';
                                    }
                                    // Same reservation -> combine together
                                } else {
                                    if (ressplate !== null) {
                                        ressplate = ressplate.split("-");
                                        resNumberFundament = ressplate[0];
                                    }

                                    // combine 2 cells <=> increase the width of res. cell
                                    var matches = html_colorful_layer_row[i - 1].match(/width: (.+?)px/);
                                    html_colorful_layer_row[i] = html_colorful_layer_row[i - 1].replace(matches[0], 'width: ' + (matches[1] - (-cellWidth - borderSize)) + 'px');
                                    // Change Title adding res. number
                                    matches = html_colorful_layer_row[i - 1].match(/> \[(.+?)\] </);
                                    if (matches) {
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(matches[0], '> ' + resNumberFundament + ' [' + matches[1] + '] <');
                                    }

                                    // increase data-length value by 1
                                    matches = html_colorful_layer_row[i].match(/data-length="(.+?)"/);
                                    html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(matches[0], 'data-length="' + (matches[1] - (-1)) + '"');

                                    if (!movable) {
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('class="reservation-item"', 'class="reservation-item unmovable"');
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(color_td, NOT_MOVABLE_BACKGROUND);

                                        if (last) {
                                            html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('reservation-item', 'reservation-item clear-right');
                                        }
                                    }

                                    // Process boundaries
                                    var xLength = html_colorful_layer_row[i].match(/data-length="(\d+)"/),
                                            yLength = html_colorful_layer_row[i].match(/data-real-length="(\d+)"/);

                                    if (xLength) {
                                        xLength = xLength[1];
                                    }

                                    if (yLength) {
                                        yLength = yLength[1];
                                    }

                                    if ((yLength == 1 || xLength == yLength) &&  resLocked != '1') {
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('clear-right', '');
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('clear-left', '');
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(' unmovable', ' movable');
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(NOT_MOVABLE_BACKGROUND, color_td);
                                    } else {
                                        if (last) {
                                            html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('reservation-item', 'reservation-item unmovable');
                                        }
                                    }

                                    // Detect non-apartel reservations and recolor
                                    if (freeRes) {
                                        if (xLength == yLength) {
                                            html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('clear-right', '');
                                            html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('clear-left', '');
                                        }

                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(color_td, '#90a4ae');
                                    }

                                    html_colorful_layer_row[i - 1] = '';
                                    html_tbl_right_row[i] = '<td width="' + cellWidth + 'px" height="' + cellHeight + '">&nbsp</td>';
                                }

                                // Single reservation always movable
                                yLength = html_colorful_layer_row[i].match(/data-real-length="(\d+)"/);

                                if (yLength) {
                                    yLength = yLength[1];
                                }

                                if (yLength == 1) {
                                    html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('clear-right', '');
                                    html_colorful_layer_row[i] = html_colorful_layer_row[i].replace('clear-left', '');

                                    if (!freeRes) {
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(' unmovable', ' movable');
                                        html_colorful_layer_row[i] = html_colorful_layer_row[i].replace(NOT_MOVABLE_BACKGROUND, color_td);
                                    }
                                }

                                res_check = resNumber;
                            }
                        }

                        if (first) {
                            first = false;
                        }

                        i++;
                    }

                    html_colorful_layer += html_colorful_layer_row.join(' ');
                    html_tbl_right += html_tbl_right_row.join(' ');
                    html_tbl_right += '</tr>';
                    j++;
                }

                html_tbl_right += html_colorful_layer;

                var overbookingsDrawIndexByDay = [];
                for  (var i in data.days) {
                    overbookingsDrawIndexByDay[i++] = data.list.length + 7;
                }
                var overbookedLayer = [];
                $.each(data.overbookings, function (index, overbookedRes) {
                    var clearing = '';
                    var border_class = '';
                    var movable = (parseInt(overbookedRes.is_locked) == 0 && overbookedRes.draw_length == overbookedRes.res_length);

                    if (overbookedRes.apartel_id == '0') {
                        border_class = ' overbooking-direct';
                    }

                    if (parseInt(overbookedRes.draw_start) != parseInt(overbookedRes.res_start)) {
                        clearing = ' clear-left';
                    }

                    if ((parseInt(overbookedRes.draw_start) + parseInt(overbookedRes.draw_length)) != (parseInt(overbookedRes.res_start) + parseInt(overbookedRes.res_length))) {
                        clearing = ' clear-right';
                    }

                    var topIndex = Math.max.apply(Math, overbookingsDrawIndexByDay.slice(overbookedRes.draw_start, parseInt(overbookedRes.draw_start) + parseInt(overbookedRes.draw_length)));

                    overbookedLayer[index] = '<div ' +
                    'style="' +
                    (!movable ? 'background:' + NOT_MOVABLE_BACKGROUND + ';' : '') +
                    'width: ' + (overbookedRes.draw_length * (cellWidth + borderSize) - paddingH * 2) + 'px;' +
                    'height: ' + (cellHeight - borderSize - paddingV * 2) + 'px;' +
                    'line-height: ' + (cellHeight - paddingV * 4) + 'px;' +
                    'margin: 2px 2px;' +
                    'top: ' + ( + cellHeight + (topIndex * cellHeight) - (-borderSize)) + 'px;' +
                    'left: ' + (overbookedRes.draw_start * (cellWidth - (-borderSize)) - (-borderSize)) + 'px;" ' +
                    'class="reservation-overbooking reservation-item' + (movable ? '' : ' unmovable') + (overbookedRes.guest_balance < 0 ? ' text-danger' : '') + clearing + border_class + '" ' +
                    'data-is-ki-viewed="' + overbookedRes.ki_viewed + '" ' +
                    'data-real-length="' + overbookedRes.res_length + '" ' +
                    'data-length="' + overbookedRes.draw_length + '" ' +
                    'data-apartment-origin="' + overbookedRes.apartment_id + '" ' +
                    'data-res-number="' + overbookedRes.res_number + '" ' +
                    'data-overbooking="1"' +
                    'data-channel-res-id="' + overbookedRes.channel_res_id + '"' +
                    (overbookedRes.apartel_id == '0' ? 'title="Direct Reservation on ' + overbookedRes.apartment_name + '" ' : '') +
                    '>' +
                    '<a href="/booking/edit/' + overbookedRes.res_number + '" target="_blank"> ' +
                    (overbookedRes.draw_length > 1 ? overbookedRes.res_number : '') +
                    '[' + overbookedRes.occupancy + ']' +
                    ((overbookedRes.is_locked == '1') ? ' <span class="glyphicon glyphicon-lock"></span>' : '') + ' </a>' +
                    '</div>';

                    for (var j = parseInt(overbookedRes.draw_start); j < parseInt(overbookedRes.draw_start) + parseInt(overbookedRes.draw_length); j++) {
                        overbookingsDrawIndexByDay[j] = topIndex + 1;
                    }
                });

                html_tbl_right += overbookedLayer.join('');

                var footerDateAndAvailabilityRow = '</tbody><tfoot><tr>';

                for (var eachDate in data.days) {
                    var fullDate = data.days[eachDate].raw;

                    if (dailyAvailabilities[fullDate] === undefined) {
                        dailyAvailabilities[fullDate] = 0;
                    }

                    footerDateAndAvailabilityRow += '<th width="' + cellWidth + 'px" height="' + cellHeight + 'px">' + dailyAvailabilities[fullDate] + '</th>';
                }

                footerDateAndAvailabilityRow += '</tr>';

                html_tbl_right += footerDateAndAvailabilityRow;
                html_tbl_right += tr_date + '</tfoot>';

                html_tbl_right += '</table>';
                html_tbl_right += '<div id="temp-dropzone" style="' +
                        'width: ' + (data.days.length * (cellWidth - (-borderSize)) - (-borderSize)) + 'px; ' +
                        '">' +
                '<h3 class="text-center" style="height: ' + cellThHeight + 'px; line-height: ' + cellThHeight + 'px;">Homeless Reservations</h3>' +
                '<table class="table table-bordered homeless-tbl"><tbody>';
                var homelessRowsCount = Math.max(4, Math.max.apply(Math, overbookingsDrawIndexByDay) - data.list.length - 6);
                for (var i = 0; i < homelessRowsCount; i++) {
                    html_tbl_right += '<tr>';
                    for (var j =0; j < data.days.length; j++) {
                        html_tbl_right += '<td height="' + cellHeight + 'px">&nbsp;</td>';
                    }
                    html_tbl_right += '</tr>';
                }
                html_tbl_right += '</tbody><tfoot>' + tr_date + '</tfoot>';

                html_tbl_right += '</table></div>';

                html_tbl_right += '</div></td>';

                for (var row in data.list) {
                    var item = data.list[row];

                    if (item.block) {
                        block = '&nbsp;(' + item.block + ')';
                    } else {
                        block = '';
                    }

                    var webLink = '';
                    if (item.links != '') {
                        webLink = '<a href="' + item.links + '" target="_blank">(web)</a>';
                    }

                    html_tbl_left += '<tr>' +
                            '<td height="' + cellHeight + 'px">' +
                            '<a href="/apartment/' + item.id + '" target="_blank">' + item.name + '</a> ' +
                            webLink +
                            '</td>' +
                            '<td height="' + cellHeight + 'px">' + item.max_capacity + '</td>' +
                            '<td height="' + cellHeight + 'px">' + item.bedroom + '</td>' +
                            '<td height="' + cellHeight + 'px">' + item.floor + '</td>' +
                            '<td height="' + cellHeight + 'px">' + item.unit_number + block + '</td>' +
                            '<td height="' + cellHeight + 'px">' + (item.building_name == null ? '' : item.building_name) + '</td>';

                    html_tbl_left += '</tr>';
                }

                html_tbl_left += '</table></td>';
                main_output += html_tbl_left + html_tbl_right + '</tr></table>';

                $('#result_view').html(main_output);

                // Re-color related reservations
                (function recolorRelatedReservations() {
                    var $field = $('.reservation-item[data-channel-res-id]').first();
                    var channelResId = $field.attr('data-channel-res-id');
                    var $relatedReservations;

                    if ($field.length) {
                        $relatedReservations = $('.reservation-item[data-channel-res-id="' + channelResId + '"]');
                        if (parseInt(channelResId) && $relatedReservations.length > 1) {
                            $relatedReservations.css('background', relatedReservationColors.shift());
                        }
                        $relatedReservations.removeAttr('data-channel-res-id');
                        setTimeout(recolorRelatedReservations, 0);
                    }
                })();
                var collapsed_columns = localStorage.getItem("ai_collapsed_columns");

                if (collapsed_columns != null) {
                    collapsed_columns = collapsed_columns.split(',');

                    $.each(collapsed_columns, function (index, col) {
                        if (col != '') {
                            collapse_column(col);
                        }
                    });
                }
            } else {
                notification(data);
            }

            $('#apartel_inventory_go').button('reset');

            var startingPosition,
                    dropped = false;

            $(".reservation-item:not(.unmovable)").draggable({
                axis: "y",
                cursor: "move",
                append: "body",
                scope: "inventory",
                grid: [0, cellHeight],
                opacity: 0.5,
                scroll: true,
                start: function (event, ui) {
                    startingPosition = {
                        left: $(this).position().left,
                        top: $(this).position().top
                    };
                },
                revert: function (dropeZone) {
                    if (dropeZone) {
                        dropped = true;

                        return false;
                    } else {
                        $(this).animate(startingPosition);
                        dropped = false;

                        return false;
                    }
                },
                stop: function (event, ui) {
                    if (dropped) {
                        var landingField,
                                startingField,
                                resLength,
                                self = $(this);

                        startingField = getFieldByPosition(startingPosition);
                        landingField = getFieldByPosition(ui.position);
                        resLength = $(this).attr("data-length");
                        // Not in temp zone
                        if (typeof (availabilityTable[landingField.x][landingField.y]) !== "undefined") {
                            //Check fields availability
                            for (i = 0; i < resLength; i++) {
                                if (availabilityTable[landingField.x - (-i)][landingField.y]) {
                                    self.animate(startingPosition);
                                    return true;
                                }
                            }

                            self.removeClass("moved-reservation");
                            self.removeClass("homeless-reservation");

                            //Update field availabilities
                            for (i = 0; i < resLength; i++) {
                                availabilityTable[landingField.x - (-i)][landingField.y] = 1;
                            }

                            if (   self.attr("data-apartment-origin") == apartmentIds[landingField.y]
                                && self.attr("data-overbooking") != 1
                            ) {
                                self.removeAttr("data-moved-to");
                            } else {
                                self.addClass("moved-reservation");
                                self.attr("data-moved-to", apartmentIds[landingField.y]);

                                if (parseInt($(this).attr('data-is-ki-viewed'))) {
                                    notification({
                                        status: 'warning',
                                        msg: 'Attention! Key Instructions has already been viewed for reservation #' + $(this).attr('data-res-number')
                                    });
                                }
                            }
                        } else {
                            self.removeClass("moved-reservation");
                            self.removeClass("homeless-reservation");

                            if (self.attr("data-overbooking") != 1) {
                                self.addClass("homeless-reservation");
                            }

                        }

                        // Not from temp zone
                        if (typeof (availabilityTable[startingField.x][startingField.y]) !== "undefined") {
                            for (i = 0; i < resLength; i++) {
                                availabilityTable[startingField.x - (-i)][startingField.y] = 0;
                            }
                        } else {

                        }
                    } else {

                    }
                }
            });

            $("#date-div tbody td, #temp-dropzone tbody td").droppable({
                accept: ".reservation-item",
                scope: "inventory"
            });
        }
    });

    $("#result_view").delegate("#left-tbl th.expandable", "click", function () {
        var collapsed_columns = localStorage.getItem("ai_collapsed_columns");

        if (collapsed_columns == null) {
            collapsed_columns = [];
        } else {
            collapsed_columns = collapsed_columns.split(',');
        }

        var curr_col = $(this).index();

        if ($(this).attr('data-expand') == "off") {
            $.each(collapsed_columns, function (index, col) {
                if (col == curr_col) {
                    delete collapsed_columns[col];
                }
            });
            expand_column(curr_col);
        } else {
            collapsed_columns[$(this).index()] = $(this).index();
            collapse_column($(this).index());
        }

        localStorage.setItem("ai_collapsed_columns", collapsed_columns.join(','));
    });

    $("#save-btn").click(function () {
        if ($(".homeless-reservation").length) {
            $('#save-warning-btn').trigger('click');
        } else {
            moveResercations($(this).attr('data-group-id'));
        }
    });

    $('#move-reservation').click(function () {
        moveResercations($('#save-btn').attr('data-group-id'));
    });

    function moveResercations(groupId) {
        if ($(".moved-reservation").length || $(".homeless-reservation").length) {
            var moves = new Array($(".moved-reservation").length),
                homelessMoves = new Array($(".moved-reservation").length),
                dateRange     = $('#inventory_date_range').val(),
                roomCount     = $('#date_to').val(),
                sort          = $('#sort').val(),
                url           = window.location.origin + window.location.pathname;

            groupId   = '?group_id=' + groupId;
            dateRange = (dateRange ? '&date_range=' + dateRange : '');
            roomCount = (roomCount ? '&room_count=' + roomCount : '');
            sort      = (sort ? '&sort=' + sort : '');

            if ($(".moved-reservation").length) {
                $(".moved-reservation").each(function (index) {
                    moves[index] = {
                        resNumber: $(this).attr("data-res-number"),
                        moveTo: $(this).attr("data-moved-to")
                    };
                });
            }

            if ($(".homeless-reservation").length) {
                $(".homeless-reservation").each(function (index) {
                    homelessMoves[index] = {
                        resNumber: $(this).attr("data-res-number"),
                        moveTo: ''
                    };
                });
            }

            $.merge(moves, homelessMoves);

            var btn = $('#save-btn');

            btn.button('loading');

            $.ajax({
                type: "POST",
                url: GLOBAL_SAVE_MOVES,
                data: {moves: moves},
                dataType: "json",
                success: function (data) {
                    if (data.status == 'success') {
                        window.location.href = url + groupId + dateRange + roomCount + sort;
                    } else {
                        notification(data);
                        btn.button('reset');
                    }
                }
            });
        } else {
            notification({
                status: "warning",
                msg: "No moves were made. Nothing to save."
            });
        }
    }

    // Detect group id to make auto submit
    var groupId = getParameterByName('group_id'),
        roomCount = getParameterByName('room_count'),
        dateRange = getParameterByName('date_range'),
        sort = getParameterByName('sort');

    if (groupId || dateRange) {
        if (groupId) {
            $apartmentGroupId[0].selectize.addItem(parseInt(groupId));
        }

        if (roomCount) {
            $('#date_to').val(roomCount);
        }

        if (dateRange) {
            $('#inventory_date_range').val(dateRange);
        }

        if (sort) {
            $('#sort').val(sort);
        }

        $('#apartel_inventory_go').trigger('click');
    }
});

function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");

    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);

    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

function getFieldByPosition(position) {
    var left = position.left,
            top = position.top;
    return {
        x: (left - borderSize) / (cellWidth - (-borderSize)),
        y: (top - borderSize) / (cellHeight) - 2
    };
}

function expand_column(col) {
    var th_elem = $("#left-tbl th:nth-child(" + (col - (-1)) + ")");

    th_elem.html(th_elem.attr("data-name"));
    th_elem.attr("data-expand", "on");

    $("#left-tbl td:nth-child(" + (col - (-1)) + ")").css("font-size", "12px");
}

function collapse_column(col) {
    var th_elem = $("#left-tbl th:nth-child(" + (col - (-1)) + ")");

    th_elem.html('<span class="glyphicon glyphicon-plus"></span>');
    th_elem.attr("data-expand", "off");

    $("#left-tbl td:nth-child(" + (col - (-1)) + ")").css("font-size", "0");
}

function dateDiff(dateold, datenew) {
    dateold = new Date(dateold);
    datenew = new Date(datenew);

    var d1 = dateold.getTime() / 86400000,
            d2 = datenew.getTime() / 86400000;

    return new Number(d2 - d1).toFixed(0);
}

function formValidator() {

    $("#group-inventory-from").validate({
        ignore: [],
        errorClass: "invalidField",
        rules: {
            apartment_group_id: {
                required: true
            },
            room_count: {
                required: false
            },
            inventory_date_range: {
                required: false
            },
            sort: {
                required: false
            }
        },
        messages: {
            apartment_group_id: 'Please select group'
        },
        highlight: function(element, errorClass, validClass) {
            $(element).closest('div').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).closest('div').removeClass('has-error').addClass('has-success');
        },
        success: function(label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });

    if ($("#group-inventory-from").valid()) {
        $('#apartel_inventory_go').button('loading');
        return true;
    } else {
        return false;
    }
}

$('#apartment_group_id').change(function () {
    var groupId = this.value;

    $.ajax({
        type: "POST",
        url: GLOBAL_ROOM_TYPE,
        dataType: "json",
        data: {group_id : groupId},
        success: function(data) {
            if (data.status == 'success') {
               if (!data.room_types.length) {
                   $('#room_type').val(0).prop('disabled', true);

                   return;
               }

                $('#room_type').prop('disabled', false);
               var html = '<option value="0">Room Type</option>';
               for (var i in data.room_types) {
                   var item = data.room_types[i];
                   html += '<option value="' + item.id + '">' + item.name + '</option>';
               }
               html += '</select>';
               $('#room_type').html(html);
            }
        }
    });
});

Mautic.contactclientEventsDatatable = function () {
    var $sourceTarget = mQuery('#contactClientEventsTable:first:not(.table-initialized)');
    if ($sourceTarget.length && typeof tableData !== 'undefined') {
        $sourceTarget.each(function () {
            // dependent files loaded, now get the data and render
            var $table = mQuery(this),
                sortCol = (tableData.labels[1] ? 1 : 0);
            $table.DataTable({
                language: {
                    emptyTable: 'No results found for this date range and filters.'
                },
                data: tableData.data,
                autoFill: true,
                columns: tableData.labels,
                //bSort : false,
                order: [sortCol, 'asc'],
                bLengthChange: true,
                dom: '<<lBf>rtip>',
                buttons: [
                    'excelHtml5',
                    'csvHtml5'
                ],
                footerCallback: function (row, data, start, end, display) {
                    if (data && data.length === 0 || typeof data[0] === 'undefined') {
                        $table.hide();
                        return;
                    }
                    try {
                        // Add table footer if it doesnt exist
                        var columns = data[0].length;
                        if (mQuery('tr.pageTotal').length === 0) {
                            var footer = mQuery('<tfoot></tfoot>'),
                                tr = mQuery('<tr class=\'pageTotal\' style=\'font-weight: 600; background: #fafafa;\'></tr>'),
                                tr2 = mQuery('<tr class=\'grandTotal\' style=\'font-weight: 600; background: #fafafa;\'></tr>');
                            tr.append(mQuery('<td colspan=\'1\'>Page totals</td>'));
                            tr2.append(mQuery('<td colspan=\'1\'>Grand totals</td>'));
                            for (var i = 1; i < columns; i++) {
                                tr.append(mQuery('<td class=\'td-right\'></td>'));
                                tr2.append(mQuery('<td class=\'td-right\'></td>'));
                            }
                            footer.append(tr);
                            footer.append(tr2);
                            $table.append(footer);
                        }

                        var api = this.api();

                        // Remove the formatting to get
                        // integer data for summation
                        var intVal = function (i) {
                            return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                        };

                        var total = $table.find('thead th').length,
                            footer1 = $table.find('tfoot tr:nth-child(1)'),
                            footer2 = $table.find('tfoot tr:nth-child(2)');
                        for (var j = 1; j < total; j++) {
                            var pageSum = api
                                .column(j, {page: 'current'})
                                .data()
                                .reduce(function (a, b) {
                                    return intVal(a) + intVal(b);
                                }, 0);
                            var sum = api
                                .column(j)
                                .data()
                                .reduce(function (a, b) {
                                    return intVal(a) + intVal(b);
                                }, 0);
                            footer1.find('td:nth-child(' + (j + 1) + ')').html(pageSum);
                            footer2.find('td:nth-child(' + (j + 1) + ')').html(sum);
                        }
                        mQuery('#global-builder-overlay').hide();

                    }
                    catch (e) {
                        console.warn(e);
                    }
                } // FooterCallback
            });
            mQuery('#contactClientEventsTable_wrapper .dt-buttons').css({
                float: 'right',
                marginLeft: '10px'
            });
        }).addClass('table-initialized');
    }
};
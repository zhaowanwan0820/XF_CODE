$(function() {
    var tableData = [];
    $('.sort, .sort-num').each(function(i, ele) {
        var obj = $(ele);
        obj.css('text-decoration', 'underline');
        obj.css('cursor', 'pointer');
        tableData[i] = {};
        tableData[i].ele = obj;
        tableData[i].rows = obj.parent().nextAll();
        var num = 0;
        if (obj.hasClass('sort-num')) {
            num = 1;
        }
        obj.toggle(function() {
            tableSort(i, 0, num);
        }, function() {
            tableSort(i, 1, num);
        }, function() {
            tableData[i].rows.remove();
            obj.parent().parent().append(tableData[i].rows);
        });
    });

    var tableSort = function(i, reverse, num) {
        var offset = tableData[i].ele.prevAll().size();
        var values = [];
        //value
        tableData[i].rows.each(function() {
            var td = $(this).children().eq(offset);
            if (td.length > 0) {
                var value = td.text();
                if (num) {
                    value = value == '' ? 0 : parseFloat(value.replace(',', ''));
                }
                values[values.length] = [values.length, value, this];
            }
        });
        tableData[i].rows.remove();
        //sort
        values.sort(function(a, b) {
            if (a[1] > b[1]) {
                return reverse ? 1 : -1;
            }
            return reverse ? -1 : 1;
        });
        //show
        for (var j = 0; j < values.length; j++) {
            tableData[i].ele.parent().parent().append(values[j][2]);
        }
    };
});

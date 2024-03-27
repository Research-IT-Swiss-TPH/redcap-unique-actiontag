"use strict";
class UniqueActionTag {
    constructor(data) {
        this.data = data;
        this.data = data;
        this.ob = document.getElementsByName(this.data.field)[0];
    }
    init() {
        this.writeLabels();
        this.writeTagErrors();
    }
    writeLabels() {
        if (!window.STPH_UAT.params.show_labels)
            return;
        let label = $('#label-' + this.data.field + ' tr').find('td:first');
        label.html('<p>' + label.text() + '</p><p style="font-weight:100;font-size:12px;">(' + this.data.tag + ')</p>');
    }
    writeTagErrors() {
        console.log(this.data.errors);
    }
}
window.STPH_UAT.log = function () {
    if (!this.params.show_debug)
        return;
    switch (arguments.length) {
        case 1:
            console.log(arguments[0]);
            return;
        case 2:
            console.log(arguments[0], arguments[1]);
            return;
        case 3:
            console.log(arguments[0], arguments[1], arguments[2]);
            return;
        case 4:
            console.log(arguments[0], arguments[1], arguments[2], arguments[3]);
            return;
        default:
            console.log(arguments);
    }
};
window.STPH_UAT.init = function () {
    console.log(this.data);
    this.writeGlobalErrors();
    Object.keys(window.STPH_UAT.data.fields).forEach(function (field) {
        Object.keys(window.STPH_UAT.data.fields[field]).forEach(function (tagname) {
            let data = window.STPH_UAT.data.fields[field][tagname];
            new UniqueActionTag(data).init();
        });
    });
};
window.STPH_UAT.writeGlobalErrors = function () {
    if (window.STPH_UAT.data.errors.not_allowed_flat.length > 0 || window.STPH_UAT.data.errors.not_allowed_multiple.length > 0) {
        $('#dataEntryTopOptions')
            .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!</div>');
        Object.keys(this.data.errors).forEach(error => {
            console.log(error);
        });
    }
};

class UniqueActionTag {
    constructor(data) {
        this.data = data;
        this.data = data;
        this.ob = document.getElementsByName(this.data.field)[0];
    }
    init() {
        this.writeLabels();
        this.initiateFields();
        this.writeTagErrors();
        this.checkUnique();
    }
    writeLabels() {
        if (!DTO_STPH_UAT.params.show_labels)
            return;
        let label = $('#label-' + this.data.field + ' tr').find('td:first');
        label.append('<p style="font-weight:100;font-size:12px;">(' + this.data.tag + ')</p>');
    }
    initiateFields() {
        this.ob.classList.add('form-control');
        let divLoadingHelp = '<div class="loadingHelp form-text">checking for uniqueness...</div>';
        let divValidFeedback = '<div class="valid-feedback">Field is unique.</div>';
        let divInvalidFeedback = '<div class="invalid-feedback">Field is not unique.</div>';
        $(this.ob).parent().append(divLoadingHelp);
        $(this.ob).parent().append(divValidFeedback);
        $(this.ob).parent().append(divInvalidFeedback);
    }
    writeTagErrors() {
    }
    checkUnique() {
        if (this.ob.value.length === 0)
            return;
        this.renderUI('start-load');
    }
    renderUI(phase) {
        switch (phase) {
            case 'start-load':
                $(this.ob).addClass('loading-unique');
                $(this.ob).parent().find('.loadingHelp').addClass('is-loading');
                $(this.ob).prop("disabled", true);
                break;
            default:
                DTO_STPH_UAT.log("Invalid phase in toggle UI.");
                break;
        }
    }
}
DTO_STPH_UAT.log = function () {
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
DTO_STPH_UAT.init = function () {
    console.log(JSO_STPH_UAT);
    console.log(DTO_STPH_UAT);
    this.writeGlobalErrors();
    this.writeInstances();
};
DTO_STPH_UAT.writeGlobalErrors = function () {
    if (this.errors.not_allowed_flat.length > 0 || this.errors.not_allowed_multiple.length > 0) {
        $('#dataEntryTopOptions')
            .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!</div>');
        Object.keys(this.errors).forEach(error => {
            console.log(error);
        });
    }
};
DTO_STPH_UAT.writeInstances = function () {
    Object.keys(this.data).forEach((field) => {
        Object.keys(this.data[field]).forEach((tagname) => {
            let data = this.data[field][tagname];
            new UniqueActionTag(data).init();
        });
    });
};
DTO_STPH_UAT.init();
export {};

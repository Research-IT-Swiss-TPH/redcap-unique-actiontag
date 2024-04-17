var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
class UniqueActionTag {
    constructor(data) {
        this.data = data;
        this.data = data;
        this.ob = document.getElementsByName(this.data.field)[0];
        this.value = this.ob.value;
    }
    init() {
        this.initiateFields();
        this.checkOnLoad();
    }
    initiateFields() {
        this.ob.classList.add('form-control');
        let divLoadingHelp = '<div class="loadingHelp form-text">Checking for duplicates...</div>';
        let divValidFeedback = '<div class="valid-feedback">Field has no duplicates.</div>';
        let divInvalidFeedback = '<div class="invalid-feedback">Field has duplicates.</div>';
        $(this.ob).parent().append(divLoadingHelp + divValidFeedback + divInvalidFeedback);
    }
    checkOnLoad() {
        if (this.value.length === 0)
            return;
        this.renderUI('start-load');
        this.ajax_check_unique();
    }
    renderUI(phase) {
        switch (phase) {
            case 'start-load':
                $(this.ob).addClass('loading-unique');
                $(this.ob).parent().find('.loadingHelp').addClass('is-loading');
                $(this.ob).prop("disabled", true);
                break;
            case 'stop-load':
                $(this.ob).removeClass('loading-unique');
                $(this.ob).parent().find('.loadingHelp').removeClass('is-loading');
                $(this.ob).prop("disabled", false);
                break;
            case 'set-valid':
                $(this.ob).addClass("is-valid");
                break;
            case 'set-invalid':
                $(this.ob).addClass("is-invalid");
                break;
            default:
                DTO_STPH_UAT.log("Invalid phase.");
                break;
        }
    }
    ajax_check_unique() {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                let payload = [
                    this.data,
                    this.value
                ];
                const response = yield JSO_STPH_UAT.ajax('check-unique', payload);
                this.process_uniqueness(response);
            }
            catch (error) {
                console.log(error);
            }
        });
    }
    process_uniqueness(duplicates) {
        this.renderUI('stop-load');
        if (duplicates.length == 0) {
            this.renderUI('set-valid');
        }
        else {
            console.log("Duplicates", duplicates);
            this.renderUI('set-invalid');
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
    console.log(DTO_STPH_UAT);
    this.writeTagErrors();
    this.writeInstances();
    this.enablePopovers();
};
DTO_STPH_UAT.writeTagErrors = function () {
    console.log(this.errors);
    if (this.errors.length > 0) {
        $('#dataEntryTopOptions')
            .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!<br><br><ul id="uat-global-errors"></ul></div>');
        this.errors.forEach(error => {
            $('#uat-global-errors').append("<li>In field(s) <b>" + error.fields.join(", ") + "</b> for actiontag <code>" + error.tag_name + "</code> there is an error of type: <b>" + error.error_type + "</b>.</li>");
        });
    }
};
DTO_STPH_UAT.writeInstances = function () {
    Object.keys(this.data).forEach((field) => {
        if (DTO_STPH_UAT.params.show_labels) {
            let emlabel = '<p class="uat-field-label" style="font-weight:100;font-size:12px;"><i class="fa-solid fa-cube text-info me-2"></i><small>This field is modified by <b>Unique Action Tag.</b></small></p>';
            $('#label-' + field + ' tr').find('td:first').append(emlabel);
        }
        Object.keys(this.data[field]).forEach((tagname) => {
            var _a;
            let data = this.data[field][tagname];
            if (DTO_STPH_UAT.params.show_labels) {
                if (Object.keys(data.errors).length !== 0) {
                    let errors = '<small>';
                    Object.entries(data.errors).forEach(([key, value]) => {
                        errors += key + ": <code>" + value + "</code><br>";
                    });
                    errors += "</small>";
                    let errorsPopover = '<span tabindex="0" style="text-decoration:underline;"  data-bs-placement="bottom" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-html="true" data-bs-title="Errors for ' + tagname + ' in ' + field + '" data-bs-content="' + errors + '"><small>Errors</small></span>';
                    $('#label-' + field + ' tr .uat-field-label').append('<p><small>❌ The tag <code>' + tagname + '</code> could not be initiated. ' + errorsPopover + '</small></p>');
                }
                else {
                    let details = "<small>";
                    details += data.params.with_all_records ? "with_all_records: <code>true</code><br>" : "with_all_records: <code>false</code><br>";
                    details += data.params.with_all_intances ? "with_all_intances: <code>true</code><br>" : "with_all_intances: <code>false</code><br>";
                    details += data.params.with_all_events ? "with_all_events: <code>true</code><br>" : "with_all_events: <code>false</code><br>";
                    details += data.params.targets ? "targets: <code>" + ((_a = data.params.targets) === null || _a === void 0 ? void 0 : _a.join(", ")) + "</code><br>" : "targets: <code>None</code><br>";
                    details += data.params.title ? "title: <code>" + data.params.title + "</code><br>" : "title: <code>none</code><br>";
                    details += data.params.message ? "message: <code>" + data.params.message + "</code><br>" : "message: <code>none</code><br>";
                    details += "</small>";
                    let detailsPopover = '<span tabindex="0" style="text-decoration:underline;"  data-bs-placement="bottom" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-html="true" data-bs-title="Details for ' + tagname + ' in ' + field + '" data-bs-content="' + details + '"><small>Details</small></span>';
                    $('#label-' + field + ' tr .uat-field-label').append('<p><small>✔️ The tag <code>' + tagname + '</code> is active. ' + detailsPopover + '</small></p>');
                    new UniqueActionTag(data).init();
                }
            }
        });
    });
};
DTO_STPH_UAT.enablePopovers = function () {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
};
DTO_STPH_UAT.init();
export {};

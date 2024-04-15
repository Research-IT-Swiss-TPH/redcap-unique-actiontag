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
        this.writeTagErrors();
        this.checkOnLoad();
    }
    initiateFields() {
        this.ob.classList.add('form-control');
        let divLoadingHelp = '<div class="loadingHelp form-text">Checking for duplicates...</div>';
        let divValidFeedback = '<div class="valid-feedback">Field has no duplicates.</div>';
        let divInvalidFeedback = '<div class="invalid-feedback">Field has duplicates.</div>';
        $(this.ob).parent().append(divLoadingHelp + divValidFeedback + divInvalidFeedback);
    }
    writeTagErrors() {
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
                $(this.ob).trigger('select');
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
    this.writeTagErrors();
    this.writeInstances();
    this.enablePopovers();
};
DTO_STPH_UAT.writeTagErrors = function () {
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
        if (DTO_STPH_UAT.params.show_labels) {
            let emlabel = '<p class="uat-field-label" style="font-weight:100;font-size:12px;"><i class="fa-solid fa-cube text-info me-2"></i><small>This field is modified by <b>Unique Action Tag.</b></small></p>';
            $('#label-' + field + ' tr').find('td:first').append(emlabel);
        }
        Object.keys(this.data[field]).forEach((tagname) => {
            let data = this.data[field][tagname];
            console.log(data.params['with_all_records']);
            if (DTO_STPH_UAT.params.show_labels) {
                if (Object.keys(data.errors).length !== 0) {
                    $('#label-' + field + ' tr .uat-field-label').append("<p><small>❌ The tag <code>" + tagname + "</code> could not be initiated due to <b>errors</b>.</small></p>");
                }
                else {
                    let details = 'No details available.';
                    if (Object.keys(data.params).length > 0) {
                        details = '';
                        Object.keys(data.params).forEach(param => {
                            details += param ? " " + param : " ";
                        });
                    }
                    let detailsButton = '<button class="btn btn-xs btn-secondary" data-bs-placement="bottom" type="button" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-content="' + details + '"><small>?</small></button>';
                    $('#label-' + field + ' tr .uat-field-label').append('<p><small>✔️ The tag <code>' + tagname + '</code> is active.<small> ' + detailsButton + '</p>');
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

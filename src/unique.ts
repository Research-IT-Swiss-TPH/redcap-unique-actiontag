export {};  //  indicate that the file is a module

declare const DTO_STPH_UAT: UAT_Module
declare const JSO_STPH_UAT: any

declare const bootstrap: any

interface UAT_Module {
    //data: Record<string, Record<string, UAT_Tag>>,
    data: UAT_Tag[],
    params: UAT_Params,
    errors: UAT_Error[],
    summary: {
        duplicates: UAT_Duplicate[],
        queue: { field:string, tag:string, checked: boolean }[]
    }
    
    log: Function,
    init: Function,
    writeTagErrors: Function,
    writeInstances: Function,
    enablePopovers: Function,
    modal: bootstrap.Modal,
    updateModal: Function,
    showModal: Function,
    createModal: Function,
    displaySummary: Function
}

interface UAT_Error {
    error_type: string,
    tag_name: string,
    fields: string[]
}

interface UAT_Tag  {
    errors: UAT_TagErrors
    flat: boolean
    params: {
        title?: string
        message?: string
        targets?: string[]
        with_all_records?: boolean
        with_all_intances?: boolean
        with_all_events?: boolean

    }
    tag: string,
    field: string,
    field_type: string
}

interface UAT_TagErrors {
    param_missing_required?: string[]
    param_wrong_type?: string[]
}

interface UAT_Params {
    show_debug: boolean,
    show_erors: boolean,
    show_labels: boolean,
    enable_hard_check: boolean
}


interface UAT_Duplicate {
    event_id: number,
    field_name: string,
    instance: number|null,
    project_id: number,
    record: string,
    value: string,
    trigger: {
        field: string,
        tag: string
    }
}

class UniqueActionTag {

    private ob: HTMLInputElement
    private value: string
    private hasErrors: boolean

    constructor(private data:UAT_Tag) {
        this.data = data
        this.ob = document.getElementsByName(this.data.field)[0] as HTMLInputElement
        this.value = this.ob.value
        this.hasErrors = Object.keys(this.data.errors).length !== 0
        this.init()
    }

    init(){        
        this.writeLabels()

        if(!this.hasErrors) {
            this.initiateFields()
            this.checkOnLoad()
        }
    }

    writeLabels() {

        //  Show and write lables if enabled
        if(DTO_STPH_UAT.params.show_labels) {

            if(this.hasErrors) {
                let errors = '<small>';

                Object.entries(this.data.errors).forEach(([key,value]) => {
                    errors += key + ": <code>" + value + "</code><br>"
                })
                errors += "</small>"

                let errorsPopover  = '<span tabindex="0" style="text-decoration:underline;"  data-bs-placement="bottom" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-html="true" data-bs-title="Errors for '+this.data.tag+' in '+this.data.field+'" data-bs-content="'+errors+'"><small>Errors</small></span>'

                $('#label-'+this.data.field+' tr .uat-field-label').append('<p><small>❌ The tag <code>'+this.data.tag+'</code> could not be initiated. '+errorsPopover+'</small></p>')
            } else {

                let details = "<small>"
                details += this.data.params.with_all_records ? "with_all_records: <code>true</code><br>": "with_all_records: <code>false</code><br>"
                details += this.data.params.with_all_intances ? "with_all_intances: <code>true</code><br>": "with_all_intances: <code>false</code><br>" 
                details += this.data.params.with_all_events ? "with_all_events: <code>true</code><br>": "with_all_events: <code>false</code><br>"
                details += this.data.params.targets ? "targets: <code>" + this.data.params.targets?.join(", ") + "</code><br>" : "targets: <code>None</code><br>"
                details += this.data.params.title ? "title: <code>"+this.data.params.title+"</code><br>": "title: <code>none</code><br>"
                details += this.data.params.message ? "message: <code>"+this.data.params.message+"</code><br>": "message: <code>none</code><br>"
                details += "</small>"

                let detailsPopover = '<span tabindex="0" style="text-decoration:underline;"  data-bs-placement="bottom" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-html="true" data-bs-title="Details for '+this.data.tag+' in '+this.data.field+'" data-bs-content="'+details+'"><small class="show-actiontag-detail">Details</small></span>'

                $('#label-'+this.data.field+' tr .uat-field-label').append('<p><small>✔️ The tag <code>'+this.data.tag+'</code> is active. '+detailsPopover+'</small></p>')
            }
        }        
    }

    initiateFields() {
        this.ob.classList.add('form-control')
        let divLoadingHelp = '<div class="loadingHelp form-text">Checking for duplicates...</div>'
        let divValidFeedback = '<div class="valid-feedback">Field has no duplicates.</div>'
        let divInvalidFeedback = '<div class="invalid-feedback">Field has duplicates. '

        $(this.ob).parent().append(divLoadingHelp + divValidFeedback + divInvalidFeedback)
    }

    checkOnLoad() {
        this.renderUI('start-load')
        this.ajax_check_unique()
    }

    renderUI(phase: String) {
        switch(phase) {

            case 'start-load':
                $(this.ob).addClass('loading-unique')
                $(this.ob).parent().find('.loadingHelp').addClass('is-loading')
                $(this.ob).prop("disabled", true)
                break

            case 'stop-load':
                $(this.ob).removeClass('loading-unique')
                $(this.ob).parent().find('.loadingHelp').removeClass('is-loading')
                $(this.ob).prop("disabled", false)
                break

            case 'set-valid':
                $(this.ob).addClass("is-valid")
                break

            case 'set-invalid':
                $(this.ob).addClass("is-invalid")
                //$(this.ob).trigger('select')
                break
            
            default:
                DTO_STPH_UAT.log("Invalid phase.")
                break
        }
    }

    async ajax_check_unique() {

        try {
            let payload = [this.data, this.value]
            const response  = await JSO_STPH_UAT.ajax('check-unique', payload)

            this.update_summary(response);
        
        } catch (error) {
            console.log(error)
        }
    }

    update_summary(duplicates: UAT_Duplicate[]) {


        DTO_STPH_UAT.summary.queue.forEach(el => {
            if(el.field === this.data.field && el.tag === this.data.tag) {
                el.checked = true
            }
        })

        this.renderUI('stop-load')

        if(duplicates.length > 0) {
            this.renderUI('set-invalid')
            duplicates.forEach(duplicate => {
                DTO_STPH_UAT.summary.duplicates.push(duplicate)
            })
        } else {
            this.renderUI('set-valid')
        }

        if( DTO_STPH_UAT.summary.duplicates.length > 0 && DTO_STPH_UAT.summary.queue.every(x => x.checked === true) ) {
            DTO_STPH_UAT.displaySummary()
        }

    }
}

/**
 * Data Transfer Object functions
 * 
 */
DTO_STPH_UAT.init = function() {

    this.writeTagErrors()
    this.writeInstances()
    this.enablePopovers()
}


DTO_STPH_UAT.log = function() {

    if(!this.params.show_debug) return;

    switch(arguments.length) {
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
}

//  Write global errors to log
DTO_STPH_UAT.writeTagErrors = function() {
    //console.log(this.errors)
    if( this.errors.length > 0) {
        $('#dataEntryTopOptions')
        .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!<br><br><ul id="uat-global-errors"></ul></div>')

        this.errors.forEach( error => {
            $('#uat-global-errors').append("<li>In field(s) <b>" + error.fields.join(", ") + "</b> for actiontag <code>" + error.tag_name + "</code> there is an error of type: <b>"+ error.error_type +"</b>.</li>")
        });

    }
}

DTO_STPH_UAT.writeInstances = function () {

    if(DTO_STPH_UAT.params.show_labels){

        DTO_STPH_UAT.data.forEach(el => {
        let emlabel = '<p class="uat-field-label" style="font-weight:100;font-size:12px;"><i class="fa-solid fa-cube text-info me-2"></i><small>This field is modified by <b>Unique Action Tag.</b></small></p>';
        $('#label-'+el.field+' tr').find('td:first').append(emlabel);
        })
    }

    this.data.forEach((el)=> {
        new UniqueActionTag(el)
    })

    
}

DTO_STPH_UAT.enablePopovers = function() {
    //  Enable popovers
    //  https://getbootstrap.com/docs/5.3/components/popovers/#enable-popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))
}

DTO_STPH_UAT.displaySummary = function () {

    this.updateModal()
    this.createModal()
    this.showModal()

    console.log(DTO_STPH_UAT.summary.duplicates)

}

DTO_STPH_UAT.updateModal = function() {

    $(".modal-body-title").append(`There have been <b>${DTO_STPH_UAT.summary.duplicates.length} duplicates</b> detected:`)

    DTO_STPH_UAT.summary.duplicates.forEach((duplicate, idx) => {
        let accordionItem = 
        `<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#duplicate-${idx}" aria-expanded="false" aria-controls="#duplicate-${idx}">
                ${duplicate.trigger.field} : ${duplicate.trigger.tag}
                </button>
            </h2>
            <div id="duplicate-${idx}" class="accordion-collapse collapse" data-bs-parent="#accordionDuplicates">
            <div class="accordion-body">
            <ul>
                <li>Value: ${duplicate.value}</li>
                <li>Project: ${duplicate.project_id}</li>
                <li>Event: ${duplicate.event_id}</li>
                <li>Record: ${duplicate.record}</li>
                <li>Instance: ${duplicate.instance ?? "1"}</li>
            </ul>
            </div>
            </div>
        </div>`
        $(accordionItem).appendTo("#uat-modal .modal-body .accordion")

        const show = '<small onClick="DTO_STPH_UAT.showModal('+idx+')" class="show-duplicate-detail">Show</small>'
        $('input[name="'+duplicate.trigger.field+'"]').parent().find("div.invalid-feedback").append(show)

    })

}

DTO_STPH_UAT.createModal = function() {

    this.modal = new bootstrap.Modal('#uat-modal', {
        backdrop: "static"
    })

    //  breaks things if we use jquery here, that's why we stay vanilla
    document.getElementById('uat-modal')!.addEventListener('hidden.bs.modal', (event:Event) => {
        $('[data-bs-target*="#duplicate-"]').addClass("collapsed").attr("aria-expanded","false")
        $(`[id*=duplicate-]`).removeClass("show")
    })
}

DTO_STPH_UAT.showModal = function(idx:number=0) {


    $(`#duplicate-${idx}`).addClass("show")
    $(`[data-bs-target="#duplicate-${idx}"]`).removeClass("collapsed").attr("aria-expanded","true")

    this.modal.show()
   
}

//  Initiate the script
DTO_STPH_UAT.init()
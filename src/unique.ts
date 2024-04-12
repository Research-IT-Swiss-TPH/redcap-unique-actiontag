export {};  //  indicate that the file is a module

declare const DTO_STPH_UAT: UAT_Module
declare const JSO_STPH_UAT: any

interface UAT_Module {
    data: Record<string, Record<string, UAT_Tag>>
    params: UAT_Params,
    errors: Record<string, string[]>
    log: Function
    init: Function,
    writeGlobalErrors: Function,
    writeInstances: Function
}

interface UAT_Tag  {
    errors: UAT_TagErrors
    flat: boolean
    params: {
        strict?: boolean
        title?: string
        message?: string
        targets?: []
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

class UniqueActionTag {

    private ob
    private value

    constructor(private data:UAT_Tag) {
        this.data = data
        this.ob = document.getElementsByName(this.data.field)[0] as HTMLInputElement
        this.value = this.ob.value
    }

    init(){
        this.writeLabels()
        this.initiateFields()
        this.writeTagErrors()
        this.checkOnLoad()
    }

    writeLabels(){
        if(!DTO_STPH_UAT.params.show_labels) return

        let label = $('#label-'+this.data.field+' tr').find('td:first');

        // tbd: move to global context so that this label is only written once.
        let emlabel = '<p style="font-weight:100;font-size:12px;"><i class="fa-solid fa-cube text-info me-2"></i><small>This field is modified by <b>Unique Action Tag</b></small></p>';
        label.append(emlabel);

        //label.append('<p style="font-weight:100;font-size:12px;">('+this.data.tag+')</p>')    // this is tag specific label and my be added
    }

    initiateFields() {
        this.ob.classList.add('form-control')
        let divLoadingHelp = '<div class="loadingHelp form-text">checking for uniqueness...</div>'
        let divValidFeedback = '<div class="valid-feedback">Field is unique.</div>'
        let divInvalidFeedback = '<div class="invalid-feedback">Field is not unique.</div>'

        $(this.ob).parent().append(divLoadingHelp + divValidFeedback + divInvalidFeedback)
    }


    writeTagErrors(){
        //console.log(this.data.errors)
    }

    checkOnLoad() {
        //  skip empty values
        if(this.value.length === 0) return

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
                $(this.ob).trigger('select')
                break
            
            default:
                DTO_STPH_UAT.log("Invalid phase.")
                break
        }
    }

    async ajax_check_unique() {
        try {
            let payload = [
                this.data, 
                this.value
            ]
            const response  = await JSO_STPH_UAT.ajax('check-unique', payload)
            this.process_uniqueness(response)
        
        } catch (error) {
            console.log(error)
        }
    }

    process_uniqueness(duplicates: Object[]) {
        this.renderUI('stop-load')
        if(duplicates.length == 0) {
            console.log("no duplicates")
            this.renderUI('set-valid')
        } else {
            console.log("there are duplicates!")
            console.log(duplicates)
            this.renderUI('set-invalid')
        }
    }
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

DTO_STPH_UAT.init = function() {
   
    console.log(JSO_STPH_UAT)
    console.log(DTO_STPH_UAT)

    this.writeGlobalErrors()
    this.writeInstances()
}

//  Write global errors to log
DTO_STPH_UAT.writeGlobalErrors = function() {
    if( this.errors.not_allowed_flat.length > 0 || this.errors.not_allowed_multiple.length > 0) {
        $('#dataEntryTopOptions')
        .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!</div>')
        Object.keys(this.errors).forEach(error => {
            console.log(error)
        });
    }
}

DTO_STPH_UAT.writeInstances =function (){
    // Loop over all fields and tags, and create a new class for each
    Object.keys(this.data).forEach((field)=> {
        //console.log(field)
        Object.keys(this.data[field]).forEach((tagname)=>{
            //console.log(tagname)
            let data = this.data[field][tagname]
            new UniqueActionTag(data).init()
        })
        
    })
}

//  Initiate the script
DTO_STPH_UAT.init();

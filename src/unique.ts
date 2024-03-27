export {};  //  indicate that the file is a module

/**
 * properly type window with our global UAT object
 * https://www.totaltypescript.com/how-to-properly-type-window
 */
declare global {
    interface Window {
        STPH_UAT_DTO: UAT_Module
    }
}

interface UAT_Module {
    data: UAT_Data
    params: UAT_Params,
    log: Function
    init: Function,
    writeGlobalErrors: Function
}

interface UAT_Data {
    fields: Record<string, Record<string, UAT_Tag>>
    errors: {
        not_allowed_flat: string[]
        not_allowed_multiple: string[]
    }
}

interface UAT_Tag  {
    errors: UAT_TagErrors
    flat: boolean
    params: {
        strict: boolean
        title: string
        message: string
        targets: []
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

//  Deep clone DTO by value into local variable
let STPH_UAT: UAT_Module = JSON.parse(JSON.stringify(window.STPH_UAT_DTO))

class UniqueActionTag {

    private ob

    constructor(private data:UAT_Tag) {
        this.data = data
        this.ob = document.getElementsByName(this.data.field)[0]
    }

    init(){
        this.writeLabels()
        this.writeTagErrors()
    }

    writeLabels(){
        if(!STPH_UAT.params.show_labels) return
        let label = $('#label-'+this.data.field+' tr').find('td:first');
        label.html('<p>'+label.text() + '</p><p style="font-weight:100;font-size:12px;">('+this.data.tag+')</p>')
    }


    writeTagErrors(){
        console.log(this.data.errors)
    }

}


STPH_UAT.log = function() {

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

STPH_UAT.init = function() {
    console.log(this.data)
    this.writeGlobalErrors()

    // Loop over all fields and tags, and create a new class for each
    Object.keys(this.data.fields).forEach((field)=> {
        //console.log(field)
        Object.keys(this.data.fields[field]).forEach((tagname)=>{
            //console.log(tagname)
            let data = this.data.fields[field][tagname]
            new UniqueActionTag(data).init()
        })
        
    })
}

STPH_UAT.writeGlobalErrors = function() {
    if( this.data.errors.not_allowed_flat.length > 0 || this.data.errors.not_allowed_multiple.length > 0) {
        $('#dataEntryTopOptions')
        .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!</div>')
        Object.keys(this.data.errors).forEach(error => {
            console.log(error)
        });
    }
}




$(function() {
    $(document).ready(function(){
        STPH_UAT.init();
    })
});
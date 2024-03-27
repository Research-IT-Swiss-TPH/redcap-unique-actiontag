export {};  //  indicate that the file is a module

/**
 * properly type-extend window with our global UAT Data Transfer Object
 * https://www.totaltypescript.com/how-to-properly-type-window
 */
declare global {
    interface Window {
        DTO_STPH_UAT: UAT_Module
    }
}

interface UAT_Module {
    data: Record<string, Record<string, UAT_Tag>>
    params: UAT_Params,
    errors: Record<string, string[]>
    log: Function
    init: Function,
    writeGlobalErrors: Function
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
let STPH_UAT: UAT_Module = JSON.parse(JSON.stringify(window.DTO_STPH_UAT))

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
    Object.keys(this.data).forEach((field)=> {
        //console.log(field)
        Object.keys(this.data[field]).forEach((tagname)=>{
            //console.log(tagname)
            let data = this.data[field][tagname]
            new UniqueActionTag(data).init()
        })
        
    })
}

STPH_UAT.writeGlobalErrors = function() {
    if( this.errors.not_allowed_flat.length > 0 || this.errors.not_allowed_multiple.length > 0) {
        $('#dataEntryTopOptions')
        .append('<div class="alert alert-warning"><b>Unique Action Tag - External Module</b><br>Errors detected!</div>')
        Object.keys(this.errors).forEach(error => {
            console.log(error)
        });
    }
}


//  Initiate the script
STPH_UAT.init();

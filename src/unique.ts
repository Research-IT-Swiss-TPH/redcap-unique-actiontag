interface Window  {
    STPH_UAT: UAT_Module
}

interface UAT_Module {
    data: UAT_Data
    params: UAT_Params,
    log: Function
    init: Function
}

interface UAT_Data {
    fields: Record<string, Record<string, UAT_Tag>>
    errors: {
        not_allowed_flat: string[]
        not_allowed_multiple: string[]
    }
}

interface UAT_Tag  {
    errors: []
    flat: boolean
    params: {
        strict: boolean
        title: string
        message: string
        targets: []
    }
    tag: string
}

interface UAT_Params {
    show_debug: boolean,
    show_erors: boolean,
    show_labels: boolean,
    enable_hard_check: boolean
}


window.STPH_UAT.log = function() {

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

window.STPH_UAT.init = function() {
    this.log(this.data)
}

window.STPH_UAT.init()

// Object.keys(window.STPH_UAT.data.fields).forEach(function(key) {
//     console.log(window.STPH_UAT.data.fields[key])
// })


interface Window  {
    STPH_UAT: UATData
}

interface UATData {
    data: {
        fields: Record<string, Record<string, UATTag>>
        errors: {
            not_allowed_flat: string[]
            not_allowed_multiple: string[]
        }
    },
    params: {
        show_debug: boolean,
        show_erors: boolean,
        show_labels: boolean,
        enable_hard_check: boolean
    },
    log: Function
}

interface UATTag  {
    errors: []
    flat: boolean
    params: UATParams
    tag: string
}

interface UATParams {
    strict: boolean
    title: string
    message: string
    targets: []
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




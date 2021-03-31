/**
 * Unique Action Tag - A REDCap external module providing action tags that make fields unique.
 * Author: Ekin Tertemiz
 * 
 * 
 * Developer Note: Babel Javascript compilation
 * The module is using a Babel compiled version of this code to ensure modern Javascript can be used without losing Browser Support. 
 * Run `npm run build` or `npm run dev` after making code changes to your Javacsript.
 * Also ensure that you have Node.js running on your local development machine 
 * and have installed all necessary local dependencies with `npm install`.
 * 
 * Credits: 
 * - Günther Rezniczek; A lot of this code is based on the REDCap Autofill External Module. (https://github.com/grezniczek/redcap_autofill)
 * - Andy Martin; To parse ActionTags from the field annotations ActionTagHelper class is used. (https://gist.github.com/123andy/dd262439c0478ffbd37e4685bc8017ac)
 * 
 */

 var STPH_UniqueAT = STPH_UniqueAT || {};

 // Debug logging
 STPH_UniqueAT.log = function() {
    if (STPH_UniqueAT.params.debug) {
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
};

// Initialization
STPH_UniqueAT.init = function() {

    STPH_UniqueAT.log("Unique Action Tag - Initializing", STPH_UniqueAT);

    //  This is not clean, but cannot otherwise prevent form submit on enter, i.e.preventDefault() on keydown has no effect on form submission. Would be happy about a fix :)
    $('#field_validation_error_state').val(1)

    // Check for errors
    if (STPH_UniqueAT.params.errors) {
        //showError(STPH_UniqueAT.params.unique);        
    }

    // Loop over all Action Tags and initiate a class for each field that has a valid Action Tag
    Object.keys(STPH_UniqueAT.params.actionTags).forEach(function(actionTagName){
        var actionTagsObject = STPH_UniqueAT.params.actionTags[actionTagName];
        Object.values(actionTagsObject).forEach( (fieldInfo) => {

            //  Only accept field type "text"
            if(fieldInfo.type == "text") {
                for (var i = 0; i < fieldInfo.tagPerFieldCount; i++) {
                    if (fieldInfo[i]) {
                        new STPH_UniqueAT.ActionTagClass(fieldInfo[i], fieldInfo.tag).init();  
                    }
                }
            }
        })
    });

}

STPH_UniqueAT.ActionTagClass = class {

    constructor(actionTagValue, actionTagLiteral) {
        this.atv = actionTagValue;
        this.ob = document.getElementsByName(this.atv.field)[0];
        this.request = STPH_UniqueAT.request,
        this.requestUrl =  STPH_UniqueAT.request.url,
        this.requestData = {
            tag: actionTagLiteral,
            value: null,
            field: this.atv.field,
            targets: this.atv.targets,
            pid:  this.request.pid,
            record:  this.request.record,
            event_id:  this.request.event_id,
            instance:  this.request.instance
        }
    }

    init() {
        this.writeLabels();
        this.bindOnChange();
        this.bindOnBlur();
        this.ajaxCheckUnique('on-load');
    }

    writeLabels() {
        if (STPH_UniqueAT.params.labels) {
            STPH_UniqueAT.log('['+ this.requestData.tag +'][' + this.atv.field + '] Apply actiontag with target(s): ' + this.atv.targets + '.)');
            var label = $('#label-'+this.atv.field+' tr').find("td:first");
            label.html(label.text() + '<div style="font-weight:100;font-size:12px;">('+this.requestData.tag+')</div>')
        }

    }

    bindOnChange() {
        //  Only for @UNIQUE-STRICT tags
        if(this.requestData.tag == "@UNIQUE-STRICT") {
            var obj = this.requestData.targets;
            for (const prop in obj) {
                if ( obj[prop] != this.requestData.field ) {
                    //  obj[prop] != this.requestData.field
                    $('input[name=' + obj[prop] + ']').bind('change', () => {
                        this.onPageCheckUnique(obj[prop]);
                    });
                } else {
                    $('input[name=' + obj[prop] + ']').bind('afterAjaxCheck', () => {
                        console.log("After ajax done.");
                        this.onPageCheckUnique(obj[prop]);
                    });
                }
              }
        }
    }

    onPageCheckUnique() {
        if(this.requestData.value.length > 0 ) {            
            this.toggleUI('start-load');
            
            var warnings = [];            
            var filteredTargets = this.requestData.targets.filter((item) => {
                return item !== this.requestData.field;
            })

            filteredTargets.forEach( (item) => { 
                var itemObj = $('input[name='+item+']');
                if( itemObj.val() == this.requestData.value ) {
                    itemObj.css("background-color", "#FFB7BE");
                    itemObj.css("font-weight", "bold");
                    warnings.push(item);
                } else {
                    itemObj.css("background-color", "#FFFFFF");
                    itemObj.css("font-weight", "normal");
                }
            })

            if(warnings.length > 0){
                this.toggleUI('show-warning', true, warnings);
            } else {
                this.toggleUI('remove-warning');
            }
            
            this.toggleUI('stop-load');

        } else {
            this.toggleUI('remove-warning');
        }
    }

    bindOnBlur() {
        STPH_UniqueAT.log('['+ this.requestData.tag +'][' + this.atv.field + '] Bind "onblur" event.');        
        $('input[name=' + this.atv.field + ']').bind('blur', () => {
            this.ajaxCheckUnique('on-blur');
        });
    }

    ajaxCheckUnique(trigger) {
        var dialog = trigger == 'on-blur' ? true : false;
        this.requestData.value = trim(this.ob.value);

        if(this.requestData.value.length > 0 ) {
            this.toggleUI('start-load');

            $.post( 
                this.requestUrl, 
                { 
                    data: this.requestData 
                } 
            )
            .done((response) => {

                if(response != 0) {
                    this.toggleUI('show-duplicate', dialog)
                } else {
                    this.toggleUI('remove-duplicate');
                    $('input[name=' + this.requestData.field + ']').trigger("afterAjaxCheck");
                }
                this.toggleUI('stop-load');
            })
            .fail( (error) => {
                STPH_UniqueAT.log(error);
                alert(woops);
                this.toggleUI('stop-load');
            });
        }
        else {
            this.toggleUI('remove-duplicate');
        }
    }

    toggleUI(phase, dialog = false, warnings = null){
        switch(phase) {
            case 'start-load':
                this.ob.classList.add('loading-unique');
                $('#formSaveTip button, #form :input[name="'+ this.atv.field +'"], #form button').prop("disabled", true);
                break;
                
            case 'stop-load': 
                this.ob.classList.remove('loading-unique');
                $('#formSaveTip button, #form :input[name="'+ this.atv.field+'"], #form button').prop("disabled", false);
                break;
            
            case 'show-duplicate':                
                STPH_UniqueAT.log('Detect duplicate for field ' + this.atv.field );
                this.ob.style.fontWeight = 'bold';                
                this.ob.style.backgroundColor = '#FFB7BE';
                if(dialog){
                    simpleDialog(
                        'The current field is a unique field (@UNIQUE action tag)'+lang.data_entry_107+' '+lang.data_entry_109+' '+lang.data_entry_110+' ' + lang.data_entry_111+' (' + this.ob.value + ') '+lang.period+' '+lang.data_entry_108,
                        lang.data_entry_105, 
                        'suf_warning_dialog', 
                        500,
                        "$('#form :input[name="+this.atv.field+"]').focus();", 
                        lang.calendar_popup_01
                    );     
                }                   
                break;

            case 'show-warning':
                STPH_UniqueAT.log('Warn of duplicate for field ' + this.atv.field );
                this.ob.style.fontWeight = 'bold';        
                this.ob.style.backgroundColor = '#FFB7BE';
     
                if(dialog){
                    simpleDialog('Warning: You have entered a duplicate value in field(s) '+ warnings +'  in conflict to ' + this.atv.field);
                }
                break;

            case 'remove-warning':
                this.ob.style.fontWeight = 'normal';
                this.ob.style.backgroundColor = '#FFFFFF';
                break;
            

            case 'remove-duplicate':
                this.ob.style.fontWeight = 'normal';
                this.ob.style.backgroundColor = '#FFFFFF';                
                break;
        }
    }
}
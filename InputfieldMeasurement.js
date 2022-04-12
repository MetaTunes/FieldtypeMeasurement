/*
We need to tell htmx that ProcessWire expects Ajax calls to send "X-Requested-With"
 */

const InputfieldMeasurement = {
    initHTMXXRequestedWithXMLHttpRequest: function () {
        console.log("initHTMXXRequestedWithXMLHttpRequest - configRequest")
        document.body.addEventListener("htmx:configRequest", (event) => {
            event.detail.headers["X-Requested-With"] = "XMLHttpRequest"
        })
    },

    listenToHTMXRequests: function () {
        // before send - Just an example to show you where to hook BEFORE SEND htmx request
        htmx.on("htmx:beforeSend", function (event) {
            console.log("InputfieldMeasurement - listenToHTMXRequests - event", event)
        })
    },
}


/**
 * DOM ready
 *
 */
document.addEventListener("DOMContentLoaded", function (event) {
    if (typeof htmx !== "undefined") {
        // CHECK THAT htmx is available
        console.log("HTMX!")
        // init htmx with X-Requested-With
        InputfieldMeasurement.initHTMXXRequestedWithXMLHttpRequest()
        // just for testing
        InputfieldMeasurement.listenToHTMXRequests()
    } else {
        console.log("NO HTMX!")
    }
})

// To make sure that hx- attributes fire in lazy-loaded repeater fields
$(document).on('reloaded', '.InputfieldRepeater', function (event) {
    htmx.process(this);
})

/*******************
*Only trigger the Ajax call if there is an old value to convert from and the field settings require it
 ******************/
$(document).on('change', '.InputfieldMeasurement select', function(event) {
    var pwConfig = ProcessWire.config.InputfieldMeasurement;
    var enable_conversion = pwConfig['enable_conversion_' + this.id];
    console.log(enable_conversion, 'convert?');
    // enable_conversion: 0 = never convert, 1 = always convert, 2 = ask
    if (getOldUnit(this)) {
        if ((enable_conversion === 1) || (enable_conversion === 2 && confirm(ProcessWire.config.InputfieldMeasurement.confirm))) htmx.trigger(this, 'confirmed');
    }
});

function getOldUnit(el) {
    return $(el).closest('li').next('li').find('input').val();
}
/*****************************/

/***********************
 * We need to put the current selected unit into the 'oldUnit' field so that it can be used by htmx
 * I tried to do this in hyperscript, but it did not work for (lazy loaded) repeater fields and there did not seem to be the equivalent of the above .process method for hyperscript
 ************************/

$(document).on('click', '.InputfieldMeasurement select', setOldUnit);

function setOldUnit(event) {
    val = $(this).val();
    console.log('old val is ' + val);
    $(this).closest('li').next('li').find('input').val(val);
}
 /***************************/
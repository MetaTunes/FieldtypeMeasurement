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
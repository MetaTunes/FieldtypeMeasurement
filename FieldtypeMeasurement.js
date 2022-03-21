// Apply the 'tooltip' class to the nearest interactive parent of a 'tooltiptext' class
function addTooltipClass() {
    var tooltips = document.getElementsByClassName("tooltiptext");
    for (const t of tooltips) {
        var parent = t.parentElement;
        // We want the nearest interactive parent to activate the tooltip
        while (parent.nodeName === 'SPAN' || parent.nodeName === 'DIV') {   //ToDo - are there other node types that should be bypassed?
            parent = parent.parentElement;
        }
        parent.classList.add('tooltip');
    }
    return true;
}

document.addEventListener('DOMContentLoaded',() => {
    addTooltipClass();
});
/*
NOT IMPLEMENTED AT PRESENT - 2 PROBLEMS:
1. element has no id or header so dragging kills resizing
2. if el is dragged away from its 'host' element then can't cursor over to it to adjust it before it is invisible
The first can be fixed by adding an element id and header child in the addTooltipClass function, but the second is harder

// Make the tooltiptext element draggable:
const tooltips = document.getElementsByClassName("tooltiptext");
Array.from(tooltips).forEach(dragElement)

function dragElement(elmnt) {
    console.log('drag');
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    if (document.getElementById(elmnt.id + "header")) {
        // if present, the header is where you move the DIV from:
        document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
    } else {
        // otherwise, move the DIV from anywhere inside the DIV:
        elmnt.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        // get the mouse cursor position at startup:
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        // call a function whenever the cursor moves:
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        // calculate the new cursor position:
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        // set the element's new position:
        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        // stop moving when mouse button is released:
        document.onmouseup = null;
        document.onmousemove = null;
    }
}
*/

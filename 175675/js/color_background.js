let computed = false;   // was: computed = false
let decimal = 0;        // 0 = no decimal typed yet, 1 = already typed

/**
 * Convert value from one unit to another based on <select> option values.
 * Assumes the form has inputs named: input, display
 * and <select> elements passed in as fromSelect, toSelect.
 */
function convert(entryForm, fromSelect, toSelect) {
    const fromIdx = fromSelect.selectedIndex;
    const toIdx = toSelect.selectedIndex;

    const inputVal = Number(entryForm.input.value || 0);
    const fromFactor = Number(fromSelect.options[fromIdx].value || 1);
    const toFactor = Number(toSelect.options[toIdx].value || 1);

    // avoid divide-by-zero
    let result = 0;
    if (toFactor !== 0) {
        result = (inputVal * fromFactor) / toFactor;
    }

    entryForm.display.value = String(result);
}

/**
 * Append a character to an input field (like a simple keypad).
 * Prevents multiple leading zeros and more than one decimal point.
 * Calls convert() automatically if the form has measure selects.
 */
function addChar(inputEl, ch) {
    if (typeof ch !== "string" || ch.length === 0) return;

    const isDot = ch === ".";
    const isDigit = ch >= "0" && ch <= "9";

    if (!isDot && !isDigit) return; // ignore anything else

    // prevent second decimal point
    if (isDot) {
        if (decimal === 1) return;
        decimal = 1;
    }

    // build the value without leading junk
    let cur = String(inputEl.value || "");
    if (cur === "0" && !isDot) {
        // replace leading zero with the new digit
        cur = ch;
    } else {
        cur += ch;
    }
    inputEl.value = cur;

    computed = true;

    // auto-convert if the form has selects named measure1/measure2
    const form = inputEl.form;
    if (form && form.measure1 && form.measure2) {
        convert(form, form.measure1, form.measure2);
    }
}

/**
 * Open a small popup window (kept for parity with original example).
 */
function openVothcom() {
    window.open(
        "",
        "display window",
        "toolbar=no,directories=no,menubar=no,width=480,height=360"
    );
}

/**
 * Clear the form’s input/display and reset flags.
 * Expects fields named: input, display
 */
function clearForm(form) {
    if (!form) return;
    form.input && (form.input.value = "0");
    form.display && (form.display.value = "0");
    computed = false;
    decimal = 0;
}

function changeBackground(hexColor) {
    document.body.style.backgroundColor = hexColor;
}

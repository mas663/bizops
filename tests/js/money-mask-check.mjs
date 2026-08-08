// Faithful port of the Alpine `x-mask` money formatter as vendored in
// vendor/livewire/livewire/dist/livewire.js (functions `formatMoney` and
// `formatInput`, and the `regexes` placeholder table). Reproduced here so
// MoneyInputMaskTest can simulate real keystroke-by-keystroke typing
// against the exact `$money(...)` mask expression configured in
// App\Support\MoneyInput, without needing a browser/DOM.
//
// If Livewire's bundled mask plugin ever changes, re-sync this against
// the `formatMoney`/`formatInput` functions in the vendored livewire.js.
//
// Usage: node money-mask-check.mjs '<js expression using $input and $money>' '3333'
// Prints JSON: { steps: [...], final: "...", stripped: "..." }

const regexes = {
    9: /[0-9]/,
    a: /[a-zA-Z]/,
    '*': /[a-zA-Z0-9]/,
};

function formatInput(template, input) {
    let templateMark = 0;
    let inputMark = 0;
    let output = '';
    while (templateMark < template.length && inputMark < input.length) {
        const templateChar = template[templateMark];
        const inputChar = input[inputMark];
        if (templateChar in regexes) {
            if (regexes[templateChar].test(inputChar)) {
                output += inputChar;
                templateMark++;
            }
            inputMark++;
        } else {
            output += templateChar;
            templateMark++;
            if (templateChar === input[inputMark]) inputMark++;
        }
    }
    return output;
}

function formatMoney(input, delimiter = '.', thousands, precision = 2) {
    if (input === '-') return '-';
    if (/^\D+$/.test(input)) return '9';
    if (thousands === null || thousands === undefined) {
        thousands = delimiter === ',' ? '.' : ',';
    }
    const addThousands = (input2, thousands2) => {
        let output = '';
        let counter = 0;
        for (let i = input2.length - 1; i >= 0; i--) {
            if (input2[i] === thousands2) continue;
            if (counter === 3) {
                output = input2[i] + thousands2 + output;
                counter = 0;
            } else {
                output = input2[i] + output;
            }
            counter++;
        }
        return output;
    };
    const minus = input.startsWith('-') ? '-' : '';
    const strippedInput = input.replaceAll(new RegExp(`[^0-9\\${delimiter}]`, 'g'), '');
    let template = Array.from({ length: strippedInput.split(delimiter)[0].length }).fill('9').join('');
    template = `${minus}${addThousands(template, thousands)}`;
    if (precision > 0 && input.includes(delimiter)) template += `${delimiter}` + '9'.repeat(precision);
    // The real implementation also nudges cursor position past a trailing
    // delimiter via `this.el` inside a queueMicrotask; irrelevant to the
    // resulting formatted value, so we bind a harmless stub `el` below.
    return template;
}

const [, , expression, digits] = process.argv;

const el = { value: '', selectionStart: 0, setSelectionRange() {} };
const boundFormatMoney = formatMoney.bind({ el });
const evaluate = new Function('$input', '$money', `return ${expression};`);

let value = '';
const steps = [];

for (const char of digits) {
    // Cursor stays at the end while typing forward, so each keystroke
    // simply appends to the previously *rendered* (masked) value — this
    // is what actually reproduces the reported corruption, since a wrong
    // mask feeds back into the next keystroke's starting value.
    const rawInput = value + char;
    const template = evaluate(rawInput, boundFormatMoney);
    value = formatInput(template, rawInput);
    steps.push(value);
}

process.stdout.write(JSON.stringify({
    steps,
    final: value,
    stripped: value.replaceAll('.', ''),
}));

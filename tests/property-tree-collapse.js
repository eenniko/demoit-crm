const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'views', 'panel', 'property', 'index.php'), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];

function fixture() {
    const rows = [
        {id: 'property-node-1', dataset: {parent: '0'}, hidden: false},
        {id: 'property-node-2', dataset: {parent: '1'}, hidden: true},
        {id: 'property-node-3', dataset: {parent: '2'}, hidden: true},
        {id: 'property-node-4', dataset: {parent: '3'}, hidden: true},
        {id: 'property-node-5', dataset: {parent: '1'}, hidden: true},
        {id: 'property-node-6', dataset: {parent: '0'}, hidden: false},
    ];
    rows.forEach((row, index) => {
        const hasChildren = [0, 1, 2].includes(index);
        const span = {textContent: '+'};
        const attributes = {'aria-expanded': 'false'};
        row.toggle = hasChildren ? {
            getAttribute: name => attributes[name],
            setAttribute: (name, value) => { attributes[name] = value; },
            querySelector: () => span,
        } : null;
        row.querySelector = () => row.toggle;
    });
    let click;
    const table = {
        querySelectorAll: () => rows,
        addEventListener: (name, listener) => { click = listener; },
    };
    vm.runInNewContext(script, {document: {getElementById: () => table}});
    return {
        rows,
        toggle: index => click({target: {closest: () => rows[index].toggle}}),
        action: () => click({target: {closest: () => null}}),
        visible: () => rows.filter(row => !row.hidden).map(row => row.id),
    };
}

const tree = fixture();
assert.deepEqual(tree.visible(), ['property-node-1', 'property-node-6']);
tree.toggle(0);
assert.deepEqual(tree.visible(), ['property-node-1', 'property-node-2', 'property-node-5', 'property-node-6']);
assert.equal(tree.rows[0].toggle.getAttribute('aria-expanded'), 'true');
assert.equal(tree.rows[0].toggle.querySelector().textContent, '-');
tree.toggle(1);
assert.deepEqual(tree.visible(), ['property-node-1', 'property-node-2', 'property-node-3', 'property-node-5', 'property-node-6']);
tree.toggle(2);
assert.equal(tree.rows[3].hidden, false);
tree.toggle(0);
assert.deepEqual(tree.visible(), ['property-node-1', 'property-node-6']);
assert.equal(tree.rows[0].toggle.querySelector().textContent, '+');
tree.toggle(0);
assert.equal(tree.rows[3].hidden, false);
tree.toggle(1);
assert.equal(tree.rows[2].hidden, true);
assert.equal(tree.rows[3].hidden, true);
tree.action();
assert.equal(tree.rows[2].hidden, true);
assert.deepEqual(fixture().visible(), ['property-node-1', 'property-node-6']);
console.log('PASS: property tree expand/collapse, nested visibility, independent roots, action clicks and reload defaults.');

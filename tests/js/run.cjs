const fs = require('node:fs');
const path = require('node:path');
const run = require('./monitor_scenarios.cjs');
run(fs.readFileSync(path.join(__dirname, '../../amd/src/monitor.js'), 'utf8')).then(results => {
    results.forEach(name => console.log('PASS ' + name));
    console.log(results.length + ' monitor regression scenarios passed');
}).catch(error => {
    console.error(error);
    process.exitCode = 1;
});

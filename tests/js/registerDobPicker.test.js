import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('register page uses VueDatePicker for date of birth with an age-safe starting year', () => {
    const registerPage = read('resources/js/Pages/Auth/Register.vue');

    assert.match(registerPage, /import VueDatePicker from '@vuepic\/vue-datepicker';/);
    assert.match(registerPage, /import '@vuepic\/vue-datepicker\/dist\/main\.css';/);
    assert.match(registerPage, /<VueDatePicker[\s\S]*v-model="dateOfBirth"/);
    assert.match(registerPage, /:enable-time-picker="false"/);
    assert.match(registerPage, /:teleport="true"/);
    assert.match(registerPage, /auto-apply/);
    assert.match(registerPage, /:max-date="maxDateOfBirth"/);
    assert.match(registerPage, /:start-date="maxDateOfBirth"/);
    assert.doesNotMatch(registerPage, /import \{ Calendar \} from ['"]@\/Components\/ui\/calendar['"]/);
    assert.doesNotMatch(registerPage, /<Calendar v-model="dateOfBirth"/);
});

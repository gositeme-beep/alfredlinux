/* ==========================================================
   Alfred CLI — Configuration Management
   ========================================================== */

import Conf from 'conf';

const DEFAULTS = {
  apiKey: '',
  baseUrl: 'https://gositeme.com/api/v1/',
  outputFormat: 'text'    // 'text' or 'json'
};

export class Config {
  constructor() {
    this.store = new Conf({
      projectName: 'alfred-cli',
      defaults: DEFAULTS,
      schema: {
        apiKey:       { type: 'string' },
        baseUrl:      { type: 'string', format: 'uri' },
        outputFormat: { type: 'string', enum: ['text', 'json'] }
      }
    });
  }

  get(key) {
    return this.store.get(key);
  }

  set(key, value) {
    this.store.set(key, value);
  }

  clear() {
    this.store.clear();
  }

  get path() {
    return this.store.path;
  }
}

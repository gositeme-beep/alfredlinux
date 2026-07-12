/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "net"
/*!**********************!*\
  !*** external "net" ***!
  \**********************/
(module) {

module.exports = require("net");

/***/ },

/***/ "process"
/*!**************************!*\
  !*** external "process" ***!
  \**************************/
(module) {

module.exports = require("process");

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
var exports = __webpack_exports__;
/*!************************************************************************************************!*\
  !*** ../../node_modules/@theia/dev-container/lib/dev-container-server/dev-container-server.js ***!
  \************************************************************************************************/

// *****************************************************************************
// Copyright (C) 2024 Typefox and others.
//
// This program and the accompanying materials are made available under the
// terms of the Eclipse Public License v. 2.0 which is available at
// http://www.eclipse.org/legal/epl-2.0.
//
// This Source Code may also be made available under the following Secondary
// Licenses when the conditions for such availability set forth in the Eclipse
// Public License v. 2.0 are satisfied: GNU General Public License, version 2
// with the GNU Classpath Exception which is available at
// https://www.gnu.org/software/classpath/license.html.
//
// SPDX-License-Identifier: EPL-2.0 OR GPL-2.0-only WITH Classpath-exception-2.0
// *****************************************************************************
Object.defineProperty(exports, "__esModule", ({ value: true }));
const net_1 = __webpack_require__(/*! net */ "net");
const process_1 = __webpack_require__(/*! process */ "process");
/**
 * this node.js Program is supposed to be executed by an docker exec session inside a docker container.
 * It uses a tty session to listen on stdin and send on stdout all communication with the theia backend running inside the container.
 */
let backendPort = undefined;
process_1.argv.slice(2).forEach(arg => {
    if (arg.startsWith('-target-port')) {
        backendPort = parseInt(arg.split('=')[1]);
    }
});
if (!backendPort) {
    throw new Error('please start with -target-port={port number}');
}
if (process_1.stdin.isTTY) {
    process_1.stdin.setRawMode(true);
}
const connection = (0, net_1.createConnection)(backendPort, '0.0.0.0');
connection.pipe(process_1.stdout);
process_1.stdin.pipe(connection);
connection.on('error', error => {
    console.error('connection error', error);
});
connection.on('close', () => {
    console.log('connection closed');
    process.exit(0);
});
// keep the process running
setInterval(() => { }, 1 << 30);

})();

/******/ })()
;
//# sourceMappingURL=dev-container-server.js.map
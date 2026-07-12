/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "../../node_modules/@stroncium/procfs/lib/parsers sync recursive ^\\.\\/.*$"
/*!***********************************************************************!*\
  !*** ../../node_modules/@stroncium/procfs/lib/parsers/ sync ^\.\/.*$ ***!
  \***********************************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./cgroups": "../../node_modules/@stroncium/procfs/lib/parsers/cgroups.js",
	"./cgroups.js": "../../node_modules/@stroncium/procfs/lib/parsers/cgroups.js",
	"./config": "../../node_modules/@stroncium/procfs/lib/parsers/config.js",
	"./config.js": "../../node_modules/@stroncium/procfs/lib/parsers/config.js",
	"./cpuinfo": "../../node_modules/@stroncium/procfs/lib/parsers/cpuinfo.js",
	"./cpuinfo.js": "../../node_modules/@stroncium/procfs/lib/parsers/cpuinfo.js",
	"./devices": "../../node_modules/@stroncium/procfs/lib/parsers/devices.js",
	"./devices.js": "../../node_modules/@stroncium/procfs/lib/parsers/devices.js",
	"./diskstats": "../../node_modules/@stroncium/procfs/lib/parsers/diskstats.js",
	"./diskstats.js": "../../node_modules/@stroncium/procfs/lib/parsers/diskstats.js",
	"./filesystems": "../../node_modules/@stroncium/procfs/lib/parsers/filesystems.js",
	"./filesystems.js": "../../node_modules/@stroncium/procfs/lib/parsers/filesystems.js",
	"./loadavg": "../../node_modules/@stroncium/procfs/lib/parsers/loadavg.js",
	"./loadavg.js": "../../node_modules/@stroncium/procfs/lib/parsers/loadavg.js",
	"./meminfo": "../../node_modules/@stroncium/procfs/lib/parsers/meminfo.js",
	"./meminfo.js": "../../node_modules/@stroncium/procfs/lib/parsers/meminfo.js",
	"./partitions": "../../node_modules/@stroncium/procfs/lib/parsers/partitions.js",
	"./partitions.js": "../../node_modules/@stroncium/procfs/lib/parsers/partitions.js",
	"./processAutogroup": "../../node_modules/@stroncium/procfs/lib/parsers/processAutogroup.js",
	"./processAutogroup.js": "../../node_modules/@stroncium/procfs/lib/parsers/processAutogroup.js",
	"./processCgroups": "../../node_modules/@stroncium/procfs/lib/parsers/processCgroups.js",
	"./processCgroups.js": "../../node_modules/@stroncium/procfs/lib/parsers/processCgroups.js",
	"./processCmdline": "../../node_modules/@stroncium/procfs/lib/parsers/processCmdline.js",
	"./processCmdline.js": "../../node_modules/@stroncium/procfs/lib/parsers/processCmdline.js",
	"./processEnviron": "../../node_modules/@stroncium/procfs/lib/parsers/processEnviron.js",
	"./processEnviron.js": "../../node_modules/@stroncium/procfs/lib/parsers/processEnviron.js",
	"./processExe": "../../node_modules/@stroncium/procfs/lib/parsers/processExe.js",
	"./processExe.js": "../../node_modules/@stroncium/procfs/lib/parsers/processExe.js",
	"./processFd": "../../node_modules/@stroncium/procfs/lib/parsers/processFd.js",
	"./processFd.js": "../../node_modules/@stroncium/procfs/lib/parsers/processFd.js",
	"./processFdinfo": "../../node_modules/@stroncium/procfs/lib/parsers/processFdinfo.js",
	"./processFdinfo.js": "../../node_modules/@stroncium/procfs/lib/parsers/processFdinfo.js",
	"./processFds": "../../node_modules/@stroncium/procfs/lib/parsers/processFds.js",
	"./processFds.js": "../../node_modules/@stroncium/procfs/lib/parsers/processFds.js",
	"./processGidMap": "../../node_modules/@stroncium/procfs/lib/parsers/processGidMap.js",
	"./processGidMap.js": "../../node_modules/@stroncium/procfs/lib/parsers/processGidMap.js",
	"./processIo": "../../node_modules/@stroncium/procfs/lib/parsers/processIo.js",
	"./processIo.js": "../../node_modules/@stroncium/procfs/lib/parsers/processIo.js",
	"./processLimits": "../../node_modules/@stroncium/procfs/lib/parsers/processLimits.js",
	"./processLimits.js": "../../node_modules/@stroncium/procfs/lib/parsers/processLimits.js",
	"./processMountinfo": "../../node_modules/@stroncium/procfs/lib/parsers/processMountinfo.js",
	"./processMountinfo.js": "../../node_modules/@stroncium/procfs/lib/parsers/processMountinfo.js",
	"./processNetDev": "../../node_modules/@stroncium/procfs/lib/parsers/processNetDev.js",
	"./processNetDev.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetDev.js",
	"./processNetTcp4": "../../node_modules/@stroncium/procfs/lib/parsers/processNetTcp4.js",
	"./processNetTcp4.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetTcp4.js",
	"./processNetTcp6": "../../node_modules/@stroncium/procfs/lib/parsers/processNetTcp6.js",
	"./processNetTcp6.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetTcp6.js",
	"./processNetUdp4": "../../node_modules/@stroncium/procfs/lib/parsers/processNetUdp4.js",
	"./processNetUdp4.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetUdp4.js",
	"./processNetUdp6": "../../node_modules/@stroncium/procfs/lib/parsers/processNetUdp6.js",
	"./processNetUdp6.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetUdp6.js",
	"./processNetUnix": "../../node_modules/@stroncium/procfs/lib/parsers/processNetUnix.js",
	"./processNetUnix.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetUnix.js",
	"./processNetWireless": "../../node_modules/@stroncium/procfs/lib/parsers/processNetWireless.js",
	"./processNetWireless.js": "../../node_modules/@stroncium/procfs/lib/parsers/processNetWireless.js",
	"./processStat": "../../node_modules/@stroncium/procfs/lib/parsers/processStat.js",
	"./processStat.js": "../../node_modules/@stroncium/procfs/lib/parsers/processStat.js",
	"./processStatm": "../../node_modules/@stroncium/procfs/lib/parsers/processStatm.js",
	"./processStatm.js": "../../node_modules/@stroncium/procfs/lib/parsers/processStatm.js",
	"./processStatus": "../../node_modules/@stroncium/procfs/lib/parsers/processStatus.js",
	"./processStatus.js": "../../node_modules/@stroncium/procfs/lib/parsers/processStatus.js",
	"./processThreads": "../../node_modules/@stroncium/procfs/lib/parsers/processThreads.js",
	"./processThreads.js": "../../node_modules/@stroncium/procfs/lib/parsers/processThreads.js",
	"./processUidMap": "../../node_modules/@stroncium/procfs/lib/parsers/processUidMap.js",
	"./processUidMap.js": "../../node_modules/@stroncium/procfs/lib/parsers/processUidMap.js",
	"./processes": "../../node_modules/@stroncium/procfs/lib/parsers/processes.js",
	"./processes.js": "../../node_modules/@stroncium/procfs/lib/parsers/processes.js",
	"./stat": "../../node_modules/@stroncium/procfs/lib/parsers/stat.js",
	"./stat.js": "../../node_modules/@stroncium/procfs/lib/parsers/stat.js",
	"./swaps": "../../node_modules/@stroncium/procfs/lib/parsers/swaps.js",
	"./swaps.js": "../../node_modules/@stroncium/procfs/lib/parsers/swaps.js",
	"./uptime": "../../node_modules/@stroncium/procfs/lib/parsers/uptime.js",
	"./uptime.js": "../../node_modules/@stroncium/procfs/lib/parsers/uptime.js",
	"./utils": "../../node_modules/@stroncium/procfs/lib/parsers/utils.js",
	"./utils.js": "../../node_modules/@stroncium/procfs/lib/parsers/utils.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "../../node_modules/@stroncium/procfs/lib/parsers sync recursive ^\\.\\/.*$";

/***/ },

/***/ "../../node_modules/@theia/core/node_modules/@parcel/watcher sync recursive"
/*!*************************************************************************!*\
  !*** ../../node_modules/@theia/core/node_modules/@parcel/watcher/ sync ***!
  \*************************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/@theia/core/node_modules/@parcel/watcher sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "../../node_modules/@theia/core/node_modules/express/lib sync recursive"
/*!*********************************************************************!*\
  !*** ../../node_modules/@theia/core/node_modules/express/lib/ sync ***!
  \*********************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/@theia/core/node_modules/express/lib sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "../../node_modules/@theia/core/node_modules/yargs-parser sync recursive"
/*!**********************************************************************!*\
  !*** ../../node_modules/@theia/core/node_modules/yargs-parser/ sync ***!
  \**********************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/@theia/core/node_modules/yargs-parser sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "../../node_modules/@theia/core/node_modules/yargs/build/lib sync recursive"
/*!*************************************************************************!*\
  !*** ../../node_modules/@theia/core/node_modules/yargs/build/lib/ sync ***!
  \*************************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/@theia/core/node_modules/yargs/build/lib sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "../../node_modules/@theia/core/node_modules/yargs sync recursive"
/*!***************************************************************!*\
  !*** ../../node_modules/@theia/core/node_modules/yargs/ sync ***!
  \***************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/@theia/core/node_modules/yargs sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "../../node_modules/require-main-filename sync recursive"
/*!******************************************************!*\
  !*** ../../node_modules/require-main-filename/ sync ***!
  \******************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/require-main-filename sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "../../node_modules/scanoss/build/module/sdk/Utils sync recursive"
/*!***************************************************************!*\
  !*** ../../node_modules/scanoss/build/module/sdk/Utils/ sync ***!
  \***************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/scanoss/build/module/sdk/Utils sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "./lib/backend/native-webpack-plugin/bindings.js"
/*!*******************************************************!*\
  !*** ./lib/backend/native-webpack-plugin/bindings.js ***!
  \*******************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

module.exports = function (jsModule) {
    switch (jsModule) {
        case 'drivelist': return __webpack_require__(/*! ../../node_modules/drivelist/build/Release/drivelist.node */ "../../node_modules/drivelist/build/Release/drivelist.node");
    }
    throw new Error(`unhandled module: "${jsModule}"`);
}

/***/ },

/***/ "./lib/backend/native-webpack-plugin/conpty.js"
/*!*****************************************************!*\
  !*** ./lib/backend/native-webpack-plugin/conpty.js ***!
  \*****************************************************/
(module) {


module.exports = require('./native/conpty.node');


/***/ },

/***/ "./lib/backend/native-webpack-plugin/ripgrep.js"
/*!******************************************************!*\
  !*** ./lib/backend/native-webpack-plugin/ripgrep.js ***!
  \******************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


const path = __webpack_require__(/*! path */ "path");

exports.rgPath = path.join(__dirname, `./native/rg${process.platform === 'win32' ? '.exe' : ''}`);


/***/ },

/***/ "./src-gen/backend/main.js"
/*!*********************************!*\
  !*** ./src-gen/backend/main.js ***!
  \*********************************/
(__unused_webpack_module, __unused_webpack_exports, __webpack_require__) {

// @ts-check
const { BackendApplicationConfigProvider } = __webpack_require__(/*! @theia/core/lib/node/backend-application-config-provider */ "../../node_modules/@theia/core/lib/node/backend-application-config-provider.js");
const main = __webpack_require__(/*! @theia/core/lib/node/main */ "../../node_modules/@theia/core/lib/node/main.js");

BackendApplicationConfigProvider.set({
    "singleInstance": true,
    "frontendConnectionTimeout": 3000,
    "configurationFolder": ".gocodeme",
    "warnOnPotentiallyInsecureHostPattern": false,
    "startupTimeout": -1,
    "resolveSystemPlugins": false
});

globalThis.extensionInfo = [
    {
        "name": "@theia/electron",
        "version": "1.68.2"
    },
    {
        "name": "@theia/core",
        "version": "1.68.2"
    },
    {
        "name": "@theia/variable-resolver",
        "version": "1.68.2"
    },
    {
        "name": "@theia/editor",
        "version": "1.68.2"
    },
    {
        "name": "@theia/filesystem",
        "version": "1.68.2"
    },
    {
        "name": "@theia/workspace",
        "version": "1.68.2"
    },
    {
        "name": "@theia/markers",
        "version": "1.68.2"
    },
    {
        "name": "@theia/outline-view",
        "version": "1.68.2"
    },
    {
        "name": "@theia/monaco",
        "version": "1.68.2"
    },
    {
        "name": "@theia/output",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-core",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-anthropic",
        "version": "1.68.2"
    },
    {
        "name": "@theia/process",
        "version": "1.68.2"
    },
    {
        "name": "@theia/file-search",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-chat",
        "version": "1.68.2"
    },
    {
        "name": "@theia/navigator",
        "version": "1.68.2"
    },
    {
        "name": "@theia/editor-preview",
        "version": "1.68.2"
    },
    {
        "name": "@theia/userstorage",
        "version": "1.68.2"
    },
    {
        "name": "@theia/preferences",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-chat-ui",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-claude-code",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-code-completion",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-openai",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-codex",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-copilot",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-core-ui",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-editor",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-google",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-history",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-huggingface",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-mcp",
        "version": "1.68.2"
    },
    {
        "name": "@theia/console",
        "version": "1.68.2"
    },
    {
        "name": "@theia/terminal",
        "version": "1.68.2"
    },
    {
        "name": "@theia/task",
        "version": "1.68.2"
    },
    {
        "name": "@theia/test",
        "version": "1.68.2"
    },
    {
        "name": "@theia/debug",
        "version": "1.68.2"
    },
    {
        "name": "@theia/scm",
        "version": "1.68.2"
    },
    {
        "name": "@theia/search-in-workspace",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-ide",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-llamafile",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-mcp-server",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-mcp-ui",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-ollama",
        "version": "1.68.2"
    },
    {
        "name": "@theia/scanoss",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-scanoss",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-terminal",
        "version": "1.68.2"
    },
    {
        "name": "@theia/ai-vercel-ai",
        "version": "1.68.2"
    },
    {
        "name": "@theia/bulk-edit",
        "version": "1.68.2"
    },
    {
        "name": "@theia/callhierarchy",
        "version": "1.68.2"
    },
    {
        "name": "@theia/collaboration",
        "version": "1.68.2"
    },
    {
        "name": "@theia/remote",
        "version": "1.68.2"
    },
    {
        "name": "@theia/dev-container",
        "version": "1.68.2"
    },
    {
        "name": "@theia/external-terminal",
        "version": "1.68.2"
    },
    {
        "name": "@theia/keymaps",
        "version": "1.68.2"
    },
    {
        "name": "@theia/mini-browser",
        "version": "1.68.2"
    },
    {
        "name": "@theia/preview",
        "version": "1.68.2"
    },
    {
        "name": "@theia/getting-started",
        "version": "1.68.2"
    },
    {
        "name": "@theia/memory-inspector",
        "version": "1.68.2"
    },
    {
        "name": "@theia/messages",
        "version": "1.68.2"
    },
    {
        "name": "@theia/metrics",
        "version": "1.68.2"
    },
    {
        "name": "@theia/notebook",
        "version": "1.68.2"
    },
    {
        "name": "@theia/timeline",
        "version": "1.68.2"
    },
    {
        "name": "@theia/typehierarchy",
        "version": "1.68.2"
    },
    {
        "name": "@theia/plugin-ext",
        "version": "1.68.2"
    },
    {
        "name": "@theia/plugin-dev",
        "version": "1.68.2"
    },
    {
        "name": "@theia/plugin-ext-vscode",
        "version": "1.68.2"
    },
    {
        "name": "@theia/property-view",
        "version": "1.68.2"
    },
    {
        "name": "@theia/secondary-window",
        "version": "1.68.2"
    },
    {
        "name": "@theia/terminal-manager",
        "version": "1.68.2"
    },
    {
        "name": "@theia/toolbar",
        "version": "1.68.2"
    },
    {
        "name": "@theia/vsx-registry",
        "version": "1.68.2"
    },
    {
        "name": "theia-ide-product-ext",
        "version": "1.68.201"
    }
];

const serverModule = __webpack_require__(/*! ./server */ "./src-gen/backend/server.js");
const serverAddress = main.start(serverModule());

serverAddress.then((addressInfo) => {
    if (process && process.send && addressInfo) {
        process.send(addressInfo);
    }
});

globalThis.serverAddress = serverAddress;


/***/ },

/***/ "./src-gen/backend/server.js"
/*!***********************************!*\
  !*** ./src-gen/backend/server.js ***!
  \***********************************/
(module, __unused_webpack_exports, __webpack_require__) {

// @ts-check
__webpack_require__(/*! reflect-metadata */ "../../node_modules/reflect-metadata/Reflect.js");

// Erase the ELECTRON_RUN_AS_NODE variable from the environment, else Electron apps started using Theia will pick it up.
if ('ELECTRON_RUN_AS_NODE' in process.env) {
    delete process.env.ELECTRON_RUN_AS_NODE;
}

const path = __webpack_require__(/*! path */ "path");
process.env.THEIA_APP_PROJECT_PATH = path.resolve(__dirname, '..', '..')
const express = __webpack_require__(/*! @theia/core/shared/express */ "../../node_modules/@theia/core/shared/express/index.js");
const { Container } = __webpack_require__(/*! @theia/core/shared/inversify */ "../../node_modules/@theia/core/shared/inversify/index.js");
const { BackendApplication, BackendApplicationServer, CliManager } = __webpack_require__(/*! @theia/core/lib/node */ "../../node_modules/@theia/core/lib/node/index.js");
const { backendApplicationModule } = __webpack_require__(/*! @theia/core/lib/node/backend-application-module */ "../../node_modules/@theia/core/lib/node/backend-application-module.js");
const { messagingBackendModule } = __webpack_require__(/*! @theia/core/lib/node/messaging/messaging-backend-module */ "../../node_modules/@theia/core/lib/node/messaging/messaging-backend-module.js");
const { loggerBackendModule } = __webpack_require__(/*! @theia/core/lib/node/logger-backend-module */ "../../node_modules/@theia/core/lib/node/logger-backend-module.js");

const container = new Container();
container.load(backendApplicationModule);
container.load(messagingBackendModule);
container.load(loggerBackendModule);

function defaultServeStatic(app) {
    app.use(express.static(path.resolve(__dirname, '../../lib/frontend')))
}

function load(raw) {
    return Promise.resolve(raw).then(
        module => container.load(module.default)
    );
}

async function start(port, host, argv = process.argv) {
    if (!container.isBound(BackendApplicationServer)) {
        container.bind(BackendApplicationServer).toConstantValue({ configure: defaultServeStatic });
    }
    let result = undefined;
    await container.get(CliManager).initializeCli(argv.slice(2), 
        () => container.get(BackendApplication).configured,
        async () => {
            result = container.get(BackendApplication).start(port, host);
        });
    if (result) {
        return result;
    } else {
        return Promise.reject(0);
    }
}

module.exports = async (port, host, argv) => {
    try {
        await load(__webpack_require__(/*! @theia/core/lib/node/i18n/i18n-backend-module */ "../../node_modules/@theia/core/lib/node/i18n/i18n-backend-module.js"));
        await load(__webpack_require__(/*! @theia/core/lib/node/hosting/backend-hosting-module */ "../../node_modules/@theia/core/lib/node/hosting/backend-hosting-module.js"));
        await load(__webpack_require__(/*! @theia/core/lib/node/request/backend-request-module */ "../../node_modules/@theia/core/lib/node/request/backend-request-module.js"));
        await load(__webpack_require__(/*! @theia/editor/lib/node/editor-backend-module */ "../../node_modules/@theia/editor/lib/node/editor-backend-module.js"));
        await load(__webpack_require__(/*! @theia/filesystem/lib/node/filesystem-backend-module */ "../../node_modules/@theia/filesystem/lib/node/filesystem-backend-module.js"));
        await load(__webpack_require__(/*! @theia/filesystem/lib/node/download/file-download-backend-module */ "../../node_modules/@theia/filesystem/lib/node/download/file-download-backend-module.js"));
        await load(__webpack_require__(/*! @theia/workspace/lib/node/workspace-backend-module */ "../../node_modules/@theia/workspace/lib/node/workspace-backend-module.js"));
        await load(__webpack_require__(/*! @theia/markers/lib/node/problem-backend-module */ "../../node_modules/@theia/markers/lib/node/problem-backend-module.js"));
        await load(__webpack_require__(/*! @theia/output/lib/node/output-backend-module */ "../../node_modules/@theia/output/lib/node/output-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-core/lib/node/ai-core-backend-module */ "../../node_modules/@theia/ai-core/lib/node/ai-core-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-anthropic/lib/node/anthropic-backend-module */ "../../node_modules/@theia/ai-anthropic/lib/node/anthropic-backend-module.js"));
        await load(__webpack_require__(/*! @theia/process/lib/common/process-common-module */ "../../node_modules/@theia/process/lib/common/process-common-module.js"));
        await load(__webpack_require__(/*! @theia/process/lib/node/process-backend-module */ "../../node_modules/@theia/process/lib/node/process-backend-module.js"));
        await load(__webpack_require__(/*! @theia/file-search/lib/node/file-search-backend-module */ "../../node_modules/@theia/file-search/lib/node/file-search-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-chat/lib/node/ai-chat-backend-module */ "../../node_modules/@theia/ai-chat/lib/node/ai-chat-backend-module.js"));
        await load(__webpack_require__(/*! @theia/navigator/lib/node/navigator-backend-module */ "../../node_modules/@theia/navigator/lib/node/navigator-backend-module.js"));
        await load(__webpack_require__(/*! @theia/editor-preview/lib/node/editor-preview-backend-module */ "../../node_modules/@theia/editor-preview/lib/node/editor-preview-backend-module.js"));
        await load(__webpack_require__(/*! @theia/preferences/lib/node/preference-backend-module */ "../../node_modules/@theia/preferences/lib/node/preference-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-claude-code/lib/node/claude-code-backend-module */ "../../node_modules/@theia/ai-claude-code/lib/node/claude-code-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-code-completion/lib/node/ai-code-completion-backend-module */ "../../node_modules/@theia/ai-code-completion/lib/node/ai-code-completion-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-openai/lib/node/openai-backend-module */ "../../node_modules/@theia/ai-openai/lib/node/openai-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-codex/lib/node/codex-backend-module */ "../../node_modules/@theia/ai-codex/lib/node/codex-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-copilot/lib/node/copilot-backend-module */ "../../node_modules/@theia/ai-copilot/lib/node/copilot-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-google/lib/node/google-backend-module */ "../../node_modules/@theia/ai-google/lib/node/google-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-huggingface/lib/node/huggingface-backend-module */ "../../node_modules/@theia/ai-huggingface/lib/node/huggingface-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-mcp/lib/node/mcp-backend-module */ "../../node_modules/@theia/ai-mcp/lib/node/mcp-backend-module.js"));
        await load(__webpack_require__(/*! @theia/terminal/lib/node/terminal-backend-module */ "../../node_modules/@theia/terminal/lib/node/terminal-backend-module.js"));
        await load(__webpack_require__(/*! @theia/task/lib/node/task-backend-module */ "../../node_modules/@theia/task/lib/node/task-backend-module.js"));
        await load(__webpack_require__(/*! @theia/test/lib/node/test-backend-module */ "../../node_modules/@theia/test/lib/node/test-backend-module.js"));
        await load(__webpack_require__(/*! @theia/debug/lib/node/debug-backend-module */ "../../node_modules/@theia/debug/lib/node/debug-backend-module.js"));
        await load(__webpack_require__(/*! @theia/scm/lib/node/scm-backend-module */ "../../node_modules/@theia/scm/lib/node/scm-backend-module.js"));
        await load(__webpack_require__(/*! @theia/search-in-workspace/lib/node/search-in-workspace-backend-module */ "../../node_modules/@theia/search-in-workspace/lib/node/search-in-workspace-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-ide/lib/node/backend-module */ "../../node_modules/@theia/ai-ide/lib/node/backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-llamafile/lib/node/llamafile-backend-module */ "../../node_modules/@theia/ai-llamafile/lib/node/llamafile-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-mcp-server/lib/node/mcp-backend-module */ "../../node_modules/@theia/ai-mcp-server/lib/node/mcp-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-ollama/lib/node/ollama-backend-module */ "../../node_modules/@theia/ai-ollama/lib/node/ollama-backend-module.js"));
        await load(__webpack_require__(/*! @theia/scanoss/lib/node/scanoss-backend-module */ "../../node_modules/@theia/scanoss/lib/node/scanoss-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-scanoss/lib/node/ai-scanoss-backend-module */ "../../node_modules/@theia/ai-scanoss/lib/node/ai-scanoss-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-terminal/lib/node/ai-terminal-backend-module */ "../../node_modules/@theia/ai-terminal/lib/node/ai-terminal-backend-module.js"));
        await load(__webpack_require__(/*! @theia/ai-vercel-ai/lib/node/vercel-ai-backend-module */ "../../node_modules/@theia/ai-vercel-ai/lib/node/vercel-ai-backend-module.js"));
        await load(__webpack_require__(/*! @theia/collaboration/lib/node/collaboration-backend-module */ "../../node_modules/@theia/collaboration/lib/node/collaboration-backend-module.js"));
        await load(__webpack_require__(/*! @theia/mini-browser/lib/node/mini-browser-backend-module */ "../../node_modules/@theia/mini-browser/lib/node/mini-browser-backend-module.js"));
        await load(__webpack_require__(/*! @theia/preview/lib/node/preview-backend-module */ "../../node_modules/@theia/preview/lib/node/preview-backend-module.js"));
        await load(__webpack_require__(/*! @theia/getting-started/lib/node/getting-started-backend-module */ "../../node_modules/@theia/getting-started/lib/node/getting-started-backend-module.js"));
        await load(__webpack_require__(/*! @theia/messages/lib/node/messages-backend-module */ "../../node_modules/@theia/messages/lib/node/messages-backend-module.js"));
        await load(__webpack_require__(/*! @theia/metrics/lib/node/metrics-backend-module */ "../../node_modules/@theia/metrics/lib/node/metrics-backend-module.js"));
        await load(__webpack_require__(/*! @theia/notebook/lib/node/notebook-backend-module */ "../../node_modules/@theia/notebook/lib/node/notebook-backend-module.js"));
        await load(__webpack_require__(/*! @theia/plugin-ext/lib/plugin-ext-backend-module */ "../../node_modules/@theia/plugin-ext/lib/plugin-ext-backend-module.js"));
        await load(__webpack_require__(/*! @theia/plugin-dev/lib/node/plugin-dev-backend-module */ "../../node_modules/@theia/plugin-dev/lib/node/plugin-dev-backend-module.js"));
        await load(__webpack_require__(/*! @theia/plugin-ext-vscode/lib/node/plugin-vscode-backend-module */ "../../node_modules/@theia/plugin-ext-vscode/lib/node/plugin-vscode-backend-module.js"));
        await load(__webpack_require__(/*! @theia/toolbar/lib/node/toolbar-backend-module */ "../../node_modules/@theia/toolbar/lib/node/toolbar-backend-module.js"));
        await load(__webpack_require__(/*! @theia/vsx-registry/lib/common/vsx-registry-common-module */ "../../node_modules/@theia/vsx-registry/lib/common/vsx-registry-common-module.js"));
        await load(__webpack_require__(/*! @theia/vsx-registry/lib/node/vsx-registry-backend-module */ "../../node_modules/@theia/vsx-registry/lib/node/vsx-registry-backend-module.js"));
        return await start(port, host, argv);
    } catch (error) {
        if (typeof error !== 'number') {
            console.error('Failed to start the backend application:');
            console.error(error); 
            process.exitCode = 1;
        }
        throw error;
    }
}


/***/ },

/***/ "../../node_modules/vscode-languageserver-types/lib/umd sync recursive"
/*!********************************************************************!*\
  !*** ../../node_modules/vscode-languageserver-types/lib/umd/ sync ***!
  \********************************************************************/
(module) {

function webpackEmptyContext(req) {
	var e = new Error("Cannot find module '" + req + "'");
	e.code = 'MODULE_NOT_FOUND';
	throw e;
}
webpackEmptyContext.keys = () => ([]);
webpackEmptyContext.resolve = webpackEmptyContext;
webpackEmptyContext.id = "../../node_modules/vscode-languageserver-types/lib/umd sync recursive";
module.exports = webpackEmptyContext;

/***/ },

/***/ "assert"
/*!*************************!*\
  !*** external "assert" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("assert");

/***/ },

/***/ "async_hooks"
/*!******************************!*\
  !*** external "async_hooks" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("async_hooks");

/***/ },

/***/ "buffer"
/*!*************************!*\
  !*** external "buffer" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("buffer");

/***/ },

/***/ "child_process"
/*!********************************!*\
  !*** external "child_process" ***!
  \********************************/
(module) {

"use strict";
module.exports = require("child_process");

/***/ },

/***/ "cluster"
/*!**************************!*\
  !*** external "cluster" ***!
  \**************************/
(module) {

"use strict";
module.exports = require("cluster");

/***/ },

/***/ "constants"
/*!****************************!*\
  !*** external "constants" ***!
  \****************************/
(module) {

"use strict";
module.exports = require("constants");

/***/ },

/***/ "crypto"
/*!*************************!*\
  !*** external "crypto" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("crypto");

/***/ },

/***/ "dns"
/*!**********************!*\
  !*** external "dns" ***!
  \**********************/
(module) {

"use strict";
module.exports = require("dns");

/***/ },

/***/ "events"
/*!*************************!*\
  !*** external "events" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("events");

/***/ },

/***/ "fs"
/*!*********************!*\
  !*** external "fs" ***!
  \*********************/
(module) {

"use strict";
module.exports = require("fs");

/***/ },

/***/ "fs/promises"
/*!******************************!*\
  !*** external "fs/promises" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("fs/promises");

/***/ },

/***/ "http"
/*!***********************!*\
  !*** external "http" ***!
  \***********************/
(module) {

"use strict";
module.exports = require("http");

/***/ },

/***/ "http2"
/*!************************!*\
  !*** external "http2" ***!
  \************************/
(module) {

"use strict";
module.exports = require("http2");

/***/ },

/***/ "https"
/*!************************!*\
  !*** external "https" ***!
  \************************/
(module) {

"use strict";
module.exports = require("https");

/***/ },

/***/ "module"
/*!*************************!*\
  !*** external "module" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("module");

/***/ },

/***/ "net"
/*!**********************!*\
  !*** external "net" ***!
  \**********************/
(module) {

"use strict";
module.exports = require("net");

/***/ },

/***/ "node:assert"
/*!******************************!*\
  !*** external "node:assert" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:assert");

/***/ },

/***/ "node:async_hooks"
/*!***********************************!*\
  !*** external "node:async_hooks" ***!
  \***********************************/
(module) {

"use strict";
module.exports = require("node:async_hooks");

/***/ },

/***/ "node:buffer"
/*!******************************!*\
  !*** external "node:buffer" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:buffer");

/***/ },

/***/ "node:child_process"
/*!*************************************!*\
  !*** external "node:child_process" ***!
  \*************************************/
(module) {

"use strict";
module.exports = require("node:child_process");

/***/ },

/***/ "node:console"
/*!*******************************!*\
  !*** external "node:console" ***!
  \*******************************/
(module) {

"use strict";
module.exports = require("node:console");

/***/ },

/***/ "node:crypto"
/*!******************************!*\
  !*** external "node:crypto" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:crypto");

/***/ },

/***/ "node:diagnostics_channel"
/*!*******************************************!*\
  !*** external "node:diagnostics_channel" ***!
  \*******************************************/
(module) {

"use strict";
module.exports = require("node:diagnostics_channel");

/***/ },

/***/ "node:dns"
/*!***************************!*\
  !*** external "node:dns" ***!
  \***************************/
(module) {

"use strict";
module.exports = require("node:dns");

/***/ },

/***/ "node:events"
/*!******************************!*\
  !*** external "node:events" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:events");

/***/ },

/***/ "node:fs"
/*!**************************!*\
  !*** external "node:fs" ***!
  \**************************/
(module) {

"use strict";
module.exports = require("node:fs");

/***/ },

/***/ "node:fs/promises"
/*!***********************************!*\
  !*** external "node:fs/promises" ***!
  \***********************************/
(module) {

"use strict";
module.exports = require("node:fs/promises");

/***/ },

/***/ "node:http"
/*!****************************!*\
  !*** external "node:http" ***!
  \****************************/
(module) {

"use strict";
module.exports = require("node:http");

/***/ },

/***/ "node:http2"
/*!*****************************!*\
  !*** external "node:http2" ***!
  \*****************************/
(module) {

"use strict";
module.exports = require("node:http2");

/***/ },

/***/ "node:https"
/*!*****************************!*\
  !*** external "node:https" ***!
  \*****************************/
(module) {

"use strict";
module.exports = require("node:https");

/***/ },

/***/ "node:net"
/*!***************************!*\
  !*** external "node:net" ***!
  \***************************/
(module) {

"use strict";
module.exports = require("node:net");

/***/ },

/***/ "node:os"
/*!**************************!*\
  !*** external "node:os" ***!
  \**************************/
(module) {

"use strict";
module.exports = require("node:os");

/***/ },

/***/ "node:path"
/*!****************************!*\
  !*** external "node:path" ***!
  \****************************/
(module) {

"use strict";
module.exports = require("node:path");

/***/ },

/***/ "node:perf_hooks"
/*!**********************************!*\
  !*** external "node:perf_hooks" ***!
  \**********************************/
(module) {

"use strict";
module.exports = require("node:perf_hooks");

/***/ },

/***/ "node:process"
/*!*******************************!*\
  !*** external "node:process" ***!
  \*******************************/
(module) {

"use strict";
module.exports = require("node:process");

/***/ },

/***/ "node:querystring"
/*!***********************************!*\
  !*** external "node:querystring" ***!
  \***********************************/
(module) {

"use strict";
module.exports = require("node:querystring");

/***/ },

/***/ "node:readline"
/*!********************************!*\
  !*** external "node:readline" ***!
  \********************************/
(module) {

"use strict";
module.exports = require("node:readline");

/***/ },

/***/ "node:sqlite"
/*!******************************!*\
  !*** external "node:sqlite" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:sqlite");

/***/ },

/***/ "node:stream"
/*!******************************!*\
  !*** external "node:stream" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:stream");

/***/ },

/***/ "node:stream/promises"
/*!***************************************!*\
  !*** external "node:stream/promises" ***!
  \***************************************/
(module) {

"use strict";
module.exports = require("node:stream/promises");

/***/ },

/***/ "node:stream/web"
/*!**********************************!*\
  !*** external "node:stream/web" ***!
  \**********************************/
(module) {

"use strict";
module.exports = require("node:stream/web");

/***/ },

/***/ "node:string_decoder"
/*!**************************************!*\
  !*** external "node:string_decoder" ***!
  \**************************************/
(module) {

"use strict";
module.exports = require("node:string_decoder");

/***/ },

/***/ "node:timers"
/*!******************************!*\
  !*** external "node:timers" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("node:timers");

/***/ },

/***/ "node:tls"
/*!***************************!*\
  !*** external "node:tls" ***!
  \***************************/
(module) {

"use strict";
module.exports = require("node:tls");

/***/ },

/***/ "node:url"
/*!***************************!*\
  !*** external "node:url" ***!
  \***************************/
(module) {

"use strict";
module.exports = require("node:url");

/***/ },

/***/ "node:util"
/*!****************************!*\
  !*** external "node:util" ***!
  \****************************/
(module) {

"use strict";
module.exports = require("node:util");

/***/ },

/***/ "node:util/types"
/*!**********************************!*\
  !*** external "node:util/types" ***!
  \**********************************/
(module) {

"use strict";
module.exports = require("node:util/types");

/***/ },

/***/ "node:worker_threads"
/*!**************************************!*\
  !*** external "node:worker_threads" ***!
  \**************************************/
(module) {

"use strict";
module.exports = require("node:worker_threads");

/***/ },

/***/ "node:zlib"
/*!****************************!*\
  !*** external "node:zlib" ***!
  \****************************/
(module) {

"use strict";
module.exports = require("node:zlib");

/***/ },

/***/ "os"
/*!*********************!*\
  !*** external "os" ***!
  \*********************/
(module) {

"use strict";
module.exports = require("os");

/***/ },

/***/ "path"
/*!***********************!*\
  !*** external "path" ***!
  \***********************/
(module) {

"use strict";
module.exports = require("path");

/***/ },

/***/ "perf_hooks"
/*!*****************************!*\
  !*** external "perf_hooks" ***!
  \*****************************/
(module) {

"use strict";
module.exports = require("perf_hooks");

/***/ },

/***/ "pnpapi"
/*!*************************!*\
  !*** external "pnpapi" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("pnpapi");

/***/ },

/***/ "process"
/*!**************************!*\
  !*** external "process" ***!
  \**************************/
(module) {

"use strict";
module.exports = require("process");

/***/ },

/***/ "punycode"
/*!***************************!*\
  !*** external "punycode" ***!
  \***************************/
(module) {

"use strict";
module.exports = require("punycode");

/***/ },

/***/ "querystring"
/*!******************************!*\
  !*** external "querystring" ***!
  \******************************/
(module) {

"use strict";
module.exports = require("querystring");

/***/ },

/***/ "readline"
/*!***************************!*\
  !*** external "readline" ***!
  \***************************/
(module) {

"use strict";
module.exports = require("readline");

/***/ },

/***/ "stream"
/*!*************************!*\
  !*** external "stream" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("stream");

/***/ },

/***/ "string_decoder"
/*!*********************************!*\
  !*** external "string_decoder" ***!
  \*********************************/
(module) {

"use strict";
module.exports = require("string_decoder");

/***/ },

/***/ "timers"
/*!*************************!*\
  !*** external "timers" ***!
  \*************************/
(module) {

"use strict";
module.exports = require("timers");

/***/ },

/***/ "tls"
/*!**********************!*\
  !*** external "tls" ***!
  \**********************/
(module) {

"use strict";
module.exports = require("tls");

/***/ },

/***/ "tty"
/*!**********************!*\
  !*** external "tty" ***!
  \**********************/
(module) {

"use strict";
module.exports = require("tty");

/***/ },

/***/ "url"
/*!**********************!*\
  !*** external "url" ***!
  \**********************/
(module) {

"use strict";
module.exports = require("url");

/***/ },

/***/ "util"
/*!***********************!*\
  !*** external "util" ***!
  \***********************/
(module) {

"use strict";
module.exports = require("util");

/***/ },

/***/ "util/types"
/*!*****************************!*\
  !*** external "util/types" ***!
  \*****************************/
(module) {

"use strict";
module.exports = require("util/types");

/***/ },

/***/ "v8"
/*!*********************!*\
  !*** external "v8" ***!
  \*********************/
(module) {

"use strict";
module.exports = require("v8");

/***/ },

/***/ "worker_threads"
/*!*********************************!*\
  !*** external "worker_threads" ***!
  \*********************************/
(module) {

"use strict";
module.exports = require("worker_threads");

/***/ },

/***/ "zlib"
/*!***********************!*\
  !*** external "zlib" ***!
  \***********************/
(module) {

"use strict";
module.exports = require("zlib");

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
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = __webpack_module_cache__;
/******/ 	
/******/ 	// the startup function
/******/ 	__webpack_require__.x = () => {
/******/ 		// Load entry module and return exports
/******/ 		var __webpack_exports__ = __webpack_require__.O(undefined, ["vendors-node_modules_theia_core_lib_common_index_js-node_modules_theia_core_lib_common_prefer-47cb78","vendors-node_modules_theia_plugin-ext_lib_common_plugin-api-rpc_js","vendors-node_modules_theia_core_lib_common_file-uri_js-node_modules_theia_core_node_modules_y-da088d","vendors-node_modules_theia_core_lib_node_messaging_ipc-channel_js-node_modules_theia_core_lib-0e805d","vendors-node_modules_theia_core_lib_common_collections_js-node_modules_theia_core_lib_common_-4c8d5b","vendors-node_modules_theia_filesystem_lib_node_parcel-watcher_parcel-filesystem-service_js","vendors-node_modules_drivelist_build_Release_drivelist_node-node_modules_stroncium_procfs_lib-dcd987"], () => (__webpack_require__("./src-gen/backend/main.js")))
/******/ 		__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 		return __webpack_exports__;
/******/ 	};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/create fake namespace object */
/******/ 	(() => {
/******/ 		var getProto = Object.getPrototypeOf ? (obj) => (Object.getPrototypeOf(obj)) : (obj) => (obj.__proto__);
/******/ 		var leafPrototypes;
/******/ 		// create a fake namespace object
/******/ 		// mode & 1: value is a module id, require it
/******/ 		// mode & 2: merge all properties of value into the ns
/******/ 		// mode & 4: return value when already ns object
/******/ 		// mode & 16: return value when it's Promise-like
/******/ 		// mode & 8|1: behave like require
/******/ 		__webpack_require__.t = function(value, mode) {
/******/ 			if(mode & 1) value = this(value);
/******/ 			if(mode & 8) return value;
/******/ 			if(typeof value === 'object' && value) {
/******/ 				if((mode & 4) && value.__esModule) return value;
/******/ 				if((mode & 16) && typeof value.then === 'function') return value;
/******/ 			}
/******/ 			var ns = Object.create(null);
/******/ 			__webpack_require__.r(ns);
/******/ 			var def = {};
/******/ 			leafPrototypes = leafPrototypes || [null, getProto({}), getProto([]), getProto(getProto)];
/******/ 			for(var current = mode & 2 && value; (typeof current == 'object' || typeof current == 'function') && !~leafPrototypes.indexOf(current); current = getProto(current)) {
/******/ 				Object.getOwnPropertyNames(current).forEach((key) => (def[key] = () => (value[key])));
/******/ 			}
/******/ 			def['default'] = () => (value);
/******/ 			__webpack_require__.d(ns, def);
/******/ 			return ns;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks and chunks that the entrypoint depends on
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return "" + chunkId + ".js";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.nmd = (module) => {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		__webpack_require__.p = "";
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/require chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded chunks
/******/ 		// "1" means "loaded", otherwise not loaded yet
/******/ 		var installedChunks = {
/******/ 			"main": 1
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.O.require = (chunkId) => (installedChunks[chunkId]);
/******/ 		
/******/ 		var installChunk = (chunk) => {
/******/ 			var moreModules = chunk.modules, chunkIds = chunk.ids, runtime = chunk.runtime;
/******/ 			for(var moduleId in moreModules) {
/******/ 				if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 					__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 				}
/******/ 			}
/******/ 			if(runtime) runtime(__webpack_require__);
/******/ 			for(var i = 0; i < chunkIds.length; i++)
/******/ 				installedChunks[chunkIds[i]] = 1;
/******/ 			__webpack_require__.O();
/******/ 		};
/******/ 		
/******/ 		// require() chunk loading for javascript
/******/ 		__webpack_require__.f.require = (chunkId, promises) => {
/******/ 			// "1" is the signal for "already loaded"
/******/ 			if(!installedChunks[chunkId]) {
/******/ 				if(true) { // all chunks have JS
/******/ 					var installedChunk = require("./" + __webpack_require__.u(chunkId));
/******/ 					if (!installedChunks[chunkId]) {
/******/ 						installChunk(installedChunk);
/******/ 					}
/******/ 				} else installedChunks[chunkId] = 1;
/******/ 			}
/******/ 		};
/******/ 		
/******/ 		// no external install chunk
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/startup chunk dependencies */
/******/ 	(() => {
/******/ 		var next = __webpack_require__.x;
/******/ 		__webpack_require__.x = () => {
/******/ 			__webpack_require__.e("vendors-node_modules_theia_core_lib_common_index_js-node_modules_theia_core_lib_common_prefer-47cb78");
/******/ 			__webpack_require__.e("vendors-node_modules_theia_plugin-ext_lib_common_plugin-api-rpc_js");
/******/ 			__webpack_require__.e("vendors-node_modules_theia_core_lib_common_file-uri_js-node_modules_theia_core_node_modules_y-da088d");
/******/ 			__webpack_require__.e("vendors-node_modules_theia_core_lib_node_messaging_ipc-channel_js-node_modules_theia_core_lib-0e805d");
/******/ 			__webpack_require__.e("vendors-node_modules_theia_core_lib_common_collections_js-node_modules_theia_core_lib_common_-4c8d5b");
/******/ 			__webpack_require__.e("vendors-node_modules_theia_filesystem_lib_node_parcel-watcher_parcel-filesystem-service_js");
/******/ 			__webpack_require__.e("vendors-node_modules_drivelist_build_Release_drivelist_node-node_modules_stroncium_procfs_lib-dcd987");
/******/ 			return next();
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// module cache are used so entry inlining is disabled
/******/ 	// run startup
/******/ 	var __webpack_exports__ = __webpack_require__.x();
/******/ 	
/******/ })()
;
//# sourceMappingURL=main.js.map
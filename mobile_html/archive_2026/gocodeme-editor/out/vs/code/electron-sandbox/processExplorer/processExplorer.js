"use strict";
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/
/* eslint-disable no-restricted-globals */
(async function () {
    const bootstrapWindow = window.MonacoBootstrapWindow; // defined by bootstrap-window.ts
    const { result, configuration } = await bootstrapWindow.load('vs/code/electron-sandbox/processExplorer/processExplorerMain', {
        configureDeveloperSettings: function () {
            return {
                forceEnableDeveloperKeybindings: true
            };
        },
    });
    result.startup(configuration);
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoicHJvY2Vzc0V4cGxvcmVyLmpzIiwic291cmNlUm9vdCI6ImZpbGU6Ly8vaG9tZS9nb3NpdGVtZS9kb21haW5zL2dvc2l0ZW1lLmNvbS9wdWJsaWNfaHRtbC9nb2NvZGVtZS1lZGl0b3Ivc3JjLyIsInNvdXJjZXMiOlsidnMvY29kZS9lbGVjdHJvbi1zYW5kYm94L3Byb2Nlc3NFeHBsb3Jlci9wcm9jZXNzRXhwbG9yZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBOzs7Z0dBR2dHO0FBRWhHLDBDQUEwQztBQUUxQyxDQUFDLEtBQUs7SUFNTCxNQUFNLGVBQWUsR0FBc0IsTUFBYyxDQUFDLHFCQUFxQixDQUFDLENBQUMsaUNBQWlDO0lBRWxILE1BQU0sRUFBRSxNQUFNLEVBQUUsYUFBYSxFQUFFLEdBQUcsTUFBTSxlQUFlLENBQUMsSUFBSSxDQUEyRCw4REFBOEQsRUFBRTtRQUN0TCwwQkFBMEIsRUFBRTtZQUMzQixPQUFPO2dCQUNOLCtCQUErQixFQUFFLElBQUk7YUFDckMsQ0FBQztRQUNILENBQUM7S0FDRCxDQUFDLENBQUM7SUFFSCxNQUFNLENBQUMsT0FBTyxDQUFDLGFBQWEsQ0FBQyxDQUFDO0FBQy9CLENBQUMsRUFBRSxDQUFDLENBQUMifQ==
/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/

import { WindowService } from '@theia/core/lib/browser/window/window-service';
import * as React from 'react';

export interface ExternalBrowserLinkProps {
    text: string;
    url: string;
    windowService: WindowService;
}

export function renderProductName(): React.ReactNode {
    return <h1>GoCodeMe <span className="gs-blue-header">IDE</span></h1>;
}

function BrowserLink(props: ExternalBrowserLinkProps): JSX.Element {
    return <a
        role={'button'}
        tabIndex={0}
        href={props.url}
        target='_blank'
    >
        {props.text}
    </a>;
}

export function renderWhatIs(windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            What is GoCodeMe?
        </h3>
        <div>
            GoCodeMe is a full AI coding platform — your browser-based IDE with an autonomous AI coding agent.
            Write code, get AI suggestions, or let the AI agent build entire features for you.
        </div>
        <div>
            Your files are live on your <BrowserLink text="GoSiteMe hosting"
                url="https://gositeme.com" windowService={windowService} ></BrowserLink> account.
            Changes you make here are instantly live on your domain — no deploy step needed.
        </div>
    </div>;
}

export function renderExtendingCustomizing(windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Extensions
        </h3>
        <div >
            You can extend GoCodeMe by installing VS Code extensions from the <BrowserLink text="OpenVSX registry" url="https://open-vsx.org/"
                windowService={windowService} ></BrowserLink>. Just open the extension view to browse and install.
        </div>
    </div>;
}

export function renderSupport(windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Support
        </h3>
        <div>
            Need help? Visit the <BrowserLink text="GoSiteMe support center" url="https://gositeme.com/whmcs/submitticket.php"
                windowService={windowService} ></BrowserLink> or call us 24/7.
        </div>
    </div>;
}

export function renderTickets(windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Feedback & Bug Reports
        </h3>
        <div >
            Found a bug or have a feature request? <BrowserLink text="Submit a support ticket" url="https://gositeme.com/whmcs/submitticket.php"
                windowService={windowService} ></BrowserLink> and our team will get back to you.
        </div>
    </div>;
}

export function renderSourceCode(_windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Powered By
        </h3>
        <div >
            <a href="https://gocodeme.com" target="_blank" rel="noopener noreferrer">GoCodeMe</a> — AI-powered cloud IDE by GoSiteMe.
        </div>
    </div>;
}

export function renderDocumentation(windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Documentation
        </h3>
        <div >
            See the <BrowserLink text="GoCodeMe getting started guide" url="https://gositeme.com/whmcs/knowledgebase"
                windowService={windowService} ></BrowserLink> to learn how to use the IDE and AI agent.
        </div>
    </div>;
}

export function renderCollaboration(windowService: WindowService): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Collaboration
        </h3>
        <div >
            The IDE features a built-in collaboration feature.
            You can share your workspace with others and work together in real-time by clicking on the <i>Collaborate</i> item in the status bar.
            The collaboration feature is powered by
            the <BrowserLink text="Open Collaboration Tools" url="https://www.open-collab.tools/" windowService={windowService} /> project
            and uses their public server infrastructure.
        </div>
    </div>;
}

export function renderDownloads(): React.ReactNode {
    return <div className='gs-section'>
        <h3 className='gs-section-header'>
            Your Plan
        </h3>
        <div className='gs-action-container'>
            Manage your GoCodeMe subscription, token usage, and billing from your
            GoSiteMe client area at <a href="https://gositeme.com/whmcs/clientarea.php" target="_blank">gositeme.com</a>.
        </div>
    </div>;
}

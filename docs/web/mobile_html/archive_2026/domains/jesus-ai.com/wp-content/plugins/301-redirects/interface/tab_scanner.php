<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_scanner extends WF301
{
    static function display()
    {
        echo '<div class="wf301-tab-title"><i class="wf301-icon wf301-search"></i> Link Scanner</div>';

        if(LinkHero::is_localhost()){
            ?>
            <div id="wpel-checker-consent">
            <div class="notice-box-error">
              The <b>Link Checking &amp; Analysis</b> is not available for websites running on localhost or on non-publicly accessible hosts.<br>This service is a SaaS and needs to be able to access your site in order to analyze links on it.
            </div>
                            
            <p><b>What data am I sharing with you?</b><br>
            Absolutely no data is taken directly from WP and shared with our service! No emails, no post lists, no post content, no links - nothing! We'll access your site just like any other visitor does and check links on every post. We'll only have access to thinks that are publically available - nothing else. Obviously, if you don't agree with this please don't use the service.</p>
        
            <p><b>What are the benefits of using the Link Scanner?</b><br>
            You can look at it as a broken link checker, but it's a lot more.<br>
            After grabbing your sitemap the service will visit every page, post, product, and oder content listed on the sitemap and then check each link in that content. For each link it checks if it's alive, if it's redirect, what are its target and rel attributes. That way you can quickly check all the links on all of your pages in a matter of minutes and modify them.<br>
            This is also a great way to check if the settings you applied in the plugin are working and properly applied on all links.</p>
        
            <p><b>Will the Scanner slow down my site?</b><br>
            It's designed not to. We carefully pace out all requests so that we don't create too much traffic/load on your site in a short period of time. While the scanner is not running it's not using any resources at all.</p>
        
            <p><b>How long does a scan take?</b><br>
            For a site with an average number of links - about two minutes. However that depends on the speed of your site, the speed of the sites you link to, and the total number of links on your site we need to check.</p>
        
            </div>
            <?php
        } else {
            $linkhero = get_option('wf301-linkhero', array('checker' => array(), 'enabled' => false));
            if(!isset($linkhero['enabled']) || $linkhero['enabled'] != true){ ?>
            <div id="wpel-checker-consent">
              <div class="notice-box-info">
                <p>The Link Checker service uses a 3rd party SaaS owned and operated by <a href="https://www.webfactoryltd.com/" target="_blank">WebFactory Ltd</a> to scan/crawl your website's pages and check/analyze all links. <b>Absolutely no private information from the website is shared or transferred to WebFactory.</b> Only publicly accessible content will be checked. Posts, pages and other content that's not published will not be analyzed.<br><br>
              More details are available below. If you're not sure if you should anaylze links with this service or anything's not clear please contact us.</p>
            </div>
            </div>
            <?php } ?>
        
            <div class="lh-buttons-bar">
                <a href="#" class="button button-primary button-green check-links">Check &amp; Analyze all site's Links <i class="wf301-icon wf301-search"></i></a>
                <a href="#" class="button button-primary check-links" style="background: #F00; border: 1px solid #e30f0f; float:right;" data-force="true">Clear Results &amp; Cache</a>
            </div>

            <div class="lh-results-topbar">
                <div class="lh-search-wrapper">
                    <input placeholder="Filter pages" type="text" id="lh-search" value="" />
                    
                </div>
                <div class="lh-results-left-wrapper">
                    <div class="lh-pagination-pages"></div>
                    <div class="lh-per-page-wrapper">
                        <label for="lh-page-order">Sort by:</label>
                        <select style="width:200px;" id="lh-page-order">
                            <option value="errors">Pages with errors first</option>
                            <option value="title">Page title</option>
                            <option value="links">Pages with most links</option>
                        </select>
                        <label for="lh-per-page">Per Page:</label>
                        <select style="width:60px;" id="lh-per-page">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="lh-pagination"></div>
                </div>
            </div>
            

            <div class="lh-results-stats"></div>
        
            <div id="lh-progress-bar-wrapper">
                <div id="lh-progress-bar"></div>
            </div>
            <table id="lh_results"></table>
        
               
            <div id="lh_details">
                <div class="lh-close"></div>
                <div id="lh_details_title"></div>
                <div id="lh_page_details_wrapper">
                    <table id="lh_page_details" style="width:100%">
                        <thead>
                            <th><span class="dashicons dashicons-admin-links"></span></th>
                            <th>Anchor text/url</th>
                            <th>Page title</th>
                            <th>Target<br />Rel Attributes</th>
                            <th>Redirected</th>
                            <th>Google Web Risk</th>
                            <th>Adult Content</th>
                            <th>Domain Inbound Links</th>
                            <th>Domain Alexa Rank</th>
                            <th>Domain Language</th>
                            <th>Domain LinkHero Malware</th>
                            <th>Domain LinkHero Adult</th>
                        </thead>
                        <tbody>
        
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php
        }
        
    } // display
} // class WF301_tab_support

<div id="settings-controller-cms-content" class="has-panel cms-content flexbox-area-grow fill-width fill-height $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">
    <div class="fill-width fill-height flexbox-area-grow">
        $Tools

        <div class="fill-height flexbox-area-grow">
            <div class="cms-content-header north">
                <div class="cms-content-header-nav fill-width vertical-align-items">
                    <% include SilverStripe\\Admin\\CMSBreadcrumbs %>

                    <div class="cms-content-header-tabs cms-tabset">
                        <ul class="cms-tabset-nav-primary nav nav-tabs">
                            <li class="nav-item content-listview<% if $TabIdentifier == 'settings' %> ui-tabs-active<% end_if %>">
                                <a href="$LinkSiteConfigEdit" class="nav-link cms-panel-link" title="Edit site config">
                                    <%t SilverStripe\\CMS\\Controllers\\CMSMain.TabSettings 'Settings' %>
                                </a>
                            </li>
                            <li class="nav-item content-listview<% if $TabIdentifier == 'history' %> ui-tabs-active<% end_if %>">
                                <a href="$LinkSiteConfigHistory" class="nav-link cms-panel-link" title="Site config history">
                                    <%t SilverStripe\\CMS\\Controllers\\CMSMain.TabHistory 'History' %>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flexbox-area-grow fill-height">
                $EditForm
            </div>
        </div>
    </div>
</div>

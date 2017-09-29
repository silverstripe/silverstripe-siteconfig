<table id="siteconfig-history-versions">
    <thead>
        <tr>
            <th class="ui-helper-hidden"></th>
            <th class="first-column">ID</th>
            <th><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.WHEN 'When' %></th>
            <th><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.AUTHOR 'Author' %></th>
        </tr>
    </thead>

    <tbody>
        <% loop $Versions %>
        <tr id="siteconfig-version-$Version" class="$EvenOdd<% if $Active %> active<% end_if %>">
            <td class="ui-helper-hidden"><input type="checkbox" name="Versions[]" id="cms-version-{$Version}" value="$Version"<% if $Active %> checked="checked"<% end_if %> /></td>
            <td class="first-column">$Version</td>
            <% with $LastEdited %>
                <td class="last-edited" title="$Ago - $Nice">$Nice</td>
            <% end_with %>
            <td class="last-column"><% if $Author %>$Author.FirstName $Author.Surname.Initial<% else %><%t SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.UNKNOWN 'Unknown' %><% end_if %></td>
        </tr>
        <% end_loop %>
    </tbody>
</table>

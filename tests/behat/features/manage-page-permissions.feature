Feature: Manage global page permissions
  As an administrator
  I want to manage view and edit permission defaults on pages
  In order to set good defaults and avoid repeating myself on each page

  Background:
    Given a "page" "Home" with "Content"="<p>Welcome</p>"
    And a "group" "AUTHOR" has permissions "Access to 'Pages' section"
    And a "group" "SECURITY" has permissions "Access to 'Security' section"
    # Have to supply an email address like this for "I am logged in as a member of <name> group" to find this user
    And a "member" "AUTHOR" belonging to "AUTHOR" with "Email"="AUTHOR@example.org"
    And a "member" "SECURITY" belonging to "SECURITY" with "Email"="SECURITY@example.org"
    And I am logged in with "ADMIN" permissions
    And I go to "admin/settings"
    And I click the "Access" CMS tab

  Scenario: I can open global view permissions to everyone
    Given I select "Anyone" from "Who can view pages on this site?" input group
    And I press the "Save" button
    When I am not logged in
    And I go to the homepage
    Then I should see "Welcome"

  Scenario: I can limit global view permissions to logged-in users
    Given I select "Logged-in users" from "Who can view pages on this site?" input group
    And I press the "Save" button
    When I am not logged in
    And I go to the homepage
    Then I should see a log-in form
    When I am logged in as a member of "AUTHOR" group
    And I go to the homepage
    Then I should see "Welcome"

  Scenario: I can limit global view permissions to certain groups
    Given I select "Only these groups (choose from list)" from "Who can view pages on this site?" input group
    And I select "AUTHOR" from "Viewer Groups" with javascript
    And I press the "Save" button
    When I am not logged in
    And I go to the homepage
    Then I should see a log-in form
    When I am logged in as a member of "SECURITY" group
    And I go to the homepage
    Then I will see a "warning" log-in message
    When I am not logged in
    And I am logged in as a member of "AUTHOR" group
    And I go to the homepage
    Then I should see "Welcome"

  Scenario: I can limit global view permissions to certain members
    Given I select "Only these users (choose from list)" from "Who can view pages on this site?" input group
    And I select "AUTHOR" from "Viewer Users" with javascript
    And I press the "Save" button
    When I am not logged in
    And I go to the homepage
    Then I should see a log-in form
    When I am logged in as a member of "SECURITY" group
    And I go to the homepage
    Then I will see a "warning" log-in message
    When I am not logged in
    And I am logged in as a member of "AUTHOR" group
    And I go to the homepage
    Then I should see "Welcome"

  Scenario: I can open global edit permissions to everyone
    Given I select "Anyone who can log-in to the CMS" from "Who can edit pages on this site?" input group
    And I press the "Save" button
    Then pages should be editable by "AUTHOR"
    # "anyone" doesn't override actual permissions
    And pages should not be editable by "SECURITY"

  Scenario: I can limit global edit permissions to logged-in users
    Given I am not logged in
    And I am logged in as a member of "AUTHOR" group
    And I go to the homepage
    And I am not logged in
    And I am logged in with "ADMIN" permissions
    And I go to "admin/settings"
    And I click the "Access" CMS tab
    Given I select "Anyone who can log-in to the CMS" from "Who can edit pages on this site?" input group
    And I press the "Save" button
    Then pages should be editable by "AUTHOR"
    And pages should be editable by "ADMIN"
    And pages should not be editable by "SECURITY"

  Scenario: I can limit global edit permissions to certain groups
    When I select "Only these groups (choose from list)" from "Who can edit pages on this site?" input group
    And I select "ADMIN group" from "Editor Groups" with javascript
    And I press the "Save" button
    Then pages should not be editable by "AUTHOR"
    And pages should not be editable by "SECURITY"
    But pages should be editable by "ADMIN"

  Scenario: I can limit global edit permissions to certain members
    Given I select "Only these users (choose from list)" from "Who can edit pages on this site?" input group
    And I select "ADMIN" from "Editor Users" with javascript
    And I press the "Save" button
    Then pages should not be editable by "AUTHOR"
    And pages should not be editable by "SECURITY"
    But pages should be editable by "ADMIN"

  Scenario: I should only see member/group fields when I am limiting access to members/groups (View)
    Given I select "Anyone" from "Who can view pages on this site?" input group
    # Need to wait a beat after each selection so the animation has time to finish
    And I wait for 1 second
    Then I should not see "Viewer Groups"
    And I should not see "Viewer Users"
    When I select "Logged-in users" from "Who can view pages on this site?" input group
    And I wait for 1 second
    Then I should not see "Viewer Groups"
    And I should not see "Viewer Users"
    When I select "Only these groups (choose from list)" from "Who can view pages on this site?" input group
    And I wait for 1 second
    Then I should see "Viewer Groups"
    And I should not see "Viewer Users"
    When I select "Only these users (choose from list)" from "Who can view pages on this site?" input group
    And I wait for 1 second
    Then I should not see "Viewer Groups"
    And I should see "Viewer Users"
    When I select "Logged-in users" from "Who can view pages on this site?" input group
    And I wait for 1 second
    Then I should not see "Viewer Groups"
    And I should not see "Viewer Users"
    # Avoids having a toast which crashes the test
    When I press the "Save" button

  Scenario: I should only see member/group fields when I am limiting access to members/groups (Edit)
    Given I select "Anyone who can log-in to the CMS" from "Who can edit pages on this site?" input group
    # Need to wait a beat after each selection so the animation has time to finish
    And I wait for 1 second
    Then I should not see "Editor Groups"
    And I should not see "Editor Users"
    When I select "Logged-in users" from "Who can edit pages on this site?" input group
    And I wait for 1 second
    Then I should not see "Editor Groups"
    And I should not see "Editor Users"
    When I select "Only these groups (choose from list)" from "Who can edit pages on this site?" input group
    And I wait for 1 second
    Then I should see "Editor Groups"
    And I should not see "Editor Users"
    When I select "Only these users (choose from list)" from "Who can edit pages on this site?" input group
    And I wait for 1 second
    Then I should not see "Editor Groups"
    And I should see "Editor Users"
    When I select "Anyone who can log-in to the CMS" from "Who can edit pages on this site?" input group
    And I wait for 1 second
    Then I should not see "Editor Groups"
    And I should not see "Editor Users"
    # Avoids having a toast which crashes the test
    When I press the "Save" button

  Scenario: I should only see member/group fields when I am limiting access to members/groups (Create)
    Given I select "Anyone who can log-in to the CMS" from "Who can create pages in the root of the site?" input group
    # Need to wait a beat after each selection so the animation has time to finish
    And I wait for 1 second
    Then I should not see "Top level creator groups"
    And I should not see "Top level creator users"
    When I select "Logged-in users" from "Who can create pages in the root of the site?" input group
    And I wait for 1 second
    Then I should not see "Top level creator groups"
    And I should not see "Top level creator users"
    When I select "Only these groups (choose from list)" from "Who can create pages in the root of the site?" input group
    And I wait for 1 second
    Then I should see "Top level creator groups"
    And I should not see "Top level creator users"
    When I select "Only these users (choose from list)" from "Who can create pages in the root of the site?" input group
    And I wait for 1 second
    Then I should not see "Top level creator groups"
    And I should see "Top level creator users"
    When I select "Anyone who can log-in to the CMS" from "Who can create pages in the root of the site?" input group
    And I wait for 1 second
    Then I should not see "Top level creator groups"
    And I should not see "Top level creator users"
    # Avoids having a toast which crashes the test
    When I press the "Save" button

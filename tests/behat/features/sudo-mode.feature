@retry @job3
Feature: Form sudo mode
  As an site owner
  I want to have to re-enter my password to make changes to site config data
  So that my site is more secure

  Background:
    # Explicitly test with an "ADMIN" user as that's the most important user to test has sudo mode active
    Given I am logged in with "ADMIN" permissions

  Scenario: Sensitive data is protected by sudo mode

    When I go to "/admin/settings"
    Then I should see "Verify to continue"
    And I should see a "#Form_EditForm_action_save_siteconfig[readonly]" element

    # Test other tabs
    When I click on the ".ui-tabs-tab[aria-controls=Root_Access]" element
    Then I should see "Verify to continue"
    And I should see a "#Form_EditForm_action_save_siteconfig[readonly]" element

  Scenario: Data can be edited after activating sudo mode

    When I go to "/admin/settings"
    And I click on the ".sudo-mode-password-field__notice-button" element
    And I fill in "SudoModePassword" with "Secret!123"
    And I click on the ".sudo-mode-password-field__verify-button" element
    And I wait for 2 seconds
    Then I should not see a "#action_save[readonly]" element
    
    # Test other tabs
    When I click on the ".ui-tabs-tab[aria-controls=Root_Access]" element
    Then I should not see "Verify to continue"
    And I should not see a "#Form_EditForm_action_save_siteconfig[readonly]" element

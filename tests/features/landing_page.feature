Feature: The Site is accessible from a browser
   Given that we want to be able to provide
   a online experience a user should be able to access 
   the website's landing page.

@PerceptualDiff
Scenario: Access the homepage
  Given I am on "/"
  Then I should see the text "Welcome to"

@api @PerceptualDiff
Scenario: I can Login
  Given I am logged in as a user with the "administrator" role
  When I am on "/"

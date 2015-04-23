<?php
require_once 'WembassyBaseContext.php';

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Behat\Behat\Exception\Exception;
use Behat\BehatBundle\Context\MinkContext;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends WembassyBaseContext
{
  /**
   * Initializes context.
   * Every scenario gets it's own context object.
   *
   * @param array $parameters context parameters (set them up through behat.yml)
   */
  public function __construct(array $parameters)
  {
      // Initialize your context here

  }

  /**
   * Get the instance variable to use in Javascript.
   *
   * @param string
   *   The instanceId used by the WYSIWYG module to identify the instance.
   *
   * @throws Exeception
   *   Throws an exception if the editor doesn't exist.
   *
   * @return string
   *   A Javascript expression representing the WYSIWYG instance.
   */
  protected function getWysiwygInstance($instanceId) {
    $instance = "CKEDITOR.instances['$instanceId']";

    if (!$this->getSession()->evaluateScript("return !!$instance")) {
      throw new \Exception(sprintf('The editor "%s" was not found on the page %s', $instanceId, $this->getSession()->getCurrentUrl()));
    }

    return $instance;
  }

  /**
   * @Given /^I switch to the frame "([^"]*)"$/
   */
  public function iSwitchToTheFrame($frame) {
    if ($this->getSession()->getPage()->find("xpath", "//*[contains(@id, '$frame')]")) {
      $this->getSession()->switchToIFrame($frame);
    }
    else {
      throw new \LogicException('Could not find Media iFrame.');
    }
  }

  /**
   * @Then /^I should see the frame "([^"]*)"$/
   */
  public function iShouldSeeTheFrame($frame) {
    if ($this->getSession()->getPage()->find("xpath", "//*[contains(@id, '$frame')]")) {
      $this->getSession()->switchToIFrame($frame);
    }
    else {
      throw new \LogicException('Could not find Media iFrame.');
    }
  }

  /**
   * @Then /^I should see a CkEditor for the "([^"]*)" field$/
   */
  public function iShouldSeeACkeditorForTheField($field_name) {
    $session = $this->getSession();

    // 1. First convert the field name into it's correct form element ID.
    $form_field_id = 'cke_edit-' . str_replace('_', '-', $field_name) . '-und-0-value';
    $this->getSession()->wait(2500, '(0 === jQuery.ajax.active)');
    $ckEditor = $this->getSession()->getPage()->find('xpath', '//*[contains(@id, "' . $form_field_id .'")]');
    if ($ckEditor == null) {
      throw new \LogicException('Could not find CkEditor for ' . $field_name);
    }
  }

  /**
   * @Given /^the following buttons should exist:$/
   */
  public function theFollowingButtonsShouldExist(TableNode $buttonTable) {
    // 1. first get the Page Object.
    $session = $this->getSession();
    if ($page = $session->getPage()) {

      foreach ($buttonTable->getRows() as $id => $row) {
        // 2. Loop through each button in the table and see if it exists in the page.
        if ($row[0] !== "Button") {
          $item = $page->find('xpath', "//a[@title='$row[0]']" );
          if (!$item) {
            throw new \LogicException('Could not find Button ' . $row[0]);
          }
        }
      }
    }
    else {
      // 1. a. Could not retrieve the page object from our session, then stop testing.
      throw new \LogicException('Could not retrieve Page object from session.');
    }

  }

  /**
   * @Given /^I should also see the map iframe with src "([^"]*)"$/
   */
  public function iShouldAlsoSeeTheMapIframeWithSrc($src) {
    $session = $this->getSession();

    // 1. Get the Page Object
    if ($page = $session->getPage()) {

      // 2. Find iframe with src attribute on the page
      if ($iframe = $page->find('xpath', "//iframe[@src='$src']")) {

        return true;

      }
      else {
        // 2. b. If we failed to retrieve iframe with the correct src from our page, then stop testing.
        throw new \LogicException('Could not find iframe with src=' . $field_name . ' in the content of the page.');
      }

    }
    else {
      // 1. b. If we failed to retrieve the Page Object from our session, then stop testing.
      throw new \LogicException('Could not retrieve Page object from session.');
    }

    throw new PendingException(); // If we Ever get here then we have missed a step or logic for our test.
  }

  /**
   * @Then /^I should see the Site Logo with an image src "([^"]*)"$/
   */
  public function iShouldSeeTheSiteLogoWithAnImageSrc($src) {
    $session = $this->getSession();
    if ($page = $session->getPage()) {
      if ($site_logo = $page->find('xpath', "//img[@src='$src']")) {
        return true;
      }
      else {
        throw new \LogicException('Could not find Site logo with src=' . $src . ' in the content of the page.');
      }
    }
    else {
      throw new \LogicException('Could not retrieve Page object from session.');
    }

    throw new PendingException();
  }

  /**
   * @Given /^the "([^"]*)" header wrapper should have a CSS style of margin top "([^"]*)" pixels$/
   */
  public function theHeaderWrapperShouldHaveACssStyleOfMarginTopPixels($wrapperClass, $marginTop) {
    $session = $this->getSession();

    if ($page = $session->getPage()) {
      if ($header_wrapper = $page->find("xpath", "//div[@class='$wrapperClass']")) {
        // we use a bit of jquery to test the space between the logo
        // from the top viewport
        $javascipt = <<<HEREDOC
        // get the first class brow which is the header wrapper for the logo
        var result = jQuery('div.$wrapperClass').eq(0).offset().top;

        // means the wrapper has 10px in between the top viewport
        // and should say it's for passing test
        if (result == $marginTop) {
          return 'passed';
        }
        // else should say it's for failing test
        else {
          return 'failed';
        }
HEREDOC;

        // get the result message
        $result = $this->getMainContext()->getSession()->evaluateScript($javascipt);

        if ($result == 'passed') {
          return true;
        }
        else {
          throw new \LogicException('The site logo does not have a margin top of 10px');
        }
      }
      else {
        throw new \LogicException('Could not find the Header wrapper class ' . $wrapperClass . ' in the page');
      }
    }
    else {
      throw new \LogicException('Could not retrieve Page object from session.');
    }

    throw new PendingException();
  }

  /**
   * Get screen shot
   *
   * @param string $filename
   *
   * @When /^(?:|I )capture the screen to "([^"]+)"$/
   */
  public function captureScreen($filename)
  {
    $image_data = $this->getSession()->getDriver()->getScreenshot();
    $file_and_path = getcwd() . '/images/live/' . $filename;
    file_put_contents($file_and_path, $image_data);
  }

  /**
   * @Given /^The expected "([^"]*)" screenshot matches the "([^"]*)" screenshot$/
   */
  public function theExpectedScreenshotMatchesTheScreenshot($expected_result, $live_screenshot) {
    $mockup_file = getcwd() . '/images/mockups/$expected_result';
    $live_file = getcwd() . '/images/live/$live_screenshot';
    $evaluation_file = getcwd() . '/images/diff/' . time() . ".png";

    $command = "php test.php $mockup_file $live_file $evaluation_file";

    shell_exec($command);
    if (PHP_OS === "Darwin" && PHP_SAPI === "cli" && file_exists($evaluation_file) ) {
        exec('open -a "Preview.app" ' . $evaluation_file);
        throw new \LogicException('Image is not expected');
    }
    else if ( file_exists($evaluation_file) ){
        throw new \LogicException('Image is not expected');
    }

  }

  /**
   * @When /^I click the "([^"]*)" button in the "([^"]*)" WYSIWYG editor$/
   */
  public function iClickTheButtonInTheWysiwygEditor($action, $instanceId) {
    $driver = $this->getSession()->getDriver();

    $instance = $this->getWysiwygInstance($instanceId);
    //$editorType = $this->getSession()->evaluateScript("return $instance.editor");
    //$toolbarElement = $this->getWysiwygToolbar($instanceId, $editorType);

    // Simulate click using ExecCommand.
    $this->getSession()->executeScript("CKEDITOR.instances[\"$instanceId\"].execCommand(\"$action\");");

    // Click the action button.
    //$button = $toolbarElement->find("xpath", "//a[starts-with(@title, '$action')]");
    //if (!$button) {
    //  throw new \Exception(sprintf('Button "%s" was not found on the page %s', $action, $this->getSession()->getCurrentUrl()));
    //}
    //$button->click();
    $driver->wait(1000, TRUE);
  }

  /**
   * @Given /^the "([^"]*)" should stay inside the box whenever the viewport size is at "([^"]*)" pixels$/
   */
  public function theShouldStayInsideTheBoxWheneverTheViewportSizeIsAtPixels($logo, $windowSize) {
    $session = $this->getSession();

    if ($page = $session->getPage()) {
      if ($site_logo = $page->find("xpath", "//h1[@class='$logo']")) {
        // resize the window

        $this->getSession()->resizeWindow((int)$windowSize, 768, 'current');

        $javascript=<<<HEREDOC
        if (jQuery('.$logo').offset().top == 0) {
          return 'passed';
        }
        else {
          return 'failed';
        }
HEREDOC;

        $result = $this->getMainContext()->getSession()->evaluateScript($javascript);

        $this->getSession()->wait(5000);

        if ($result == 'passed') {
          return true;
        }
        else {
          throw new \LogicException('Site logo is going out of the box holder');
        }
      }
      else {
        throw new \LogicException('Could not find the Site Logo');
      }
    }
    else {
      throw new \LogicException('Could not retrieve Page object from session.');
    }

    throw new PendingException();
  }

}

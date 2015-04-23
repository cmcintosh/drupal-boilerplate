<?php

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
 * Base Behat Context.
 */
class WembassyBaseContext extends Drupal\DrupalExtension\Context\DrupalContext
{


  /***************************************************
  *
  * Adaptive Testing 320px, 480px, 1024px, 1400px
  *
  ****************************************************/


    /**
    * @BeforeScenario @minimalScreen
    */
    function minimalScreen ($event) {
      $this->getSession()->resizeWindow(320, 480, 'current');
    }


    /**
    * @BeforeScenario @smallScreen
    */
    function smallScreen ($event) {
      $this->getSession()->resizeWindow(480, 320, 'current');
    }

    /**
    * @BeforeScenario @averageScreen
    */
    function averageScreen ($event) {
      $this->getSession()->resizeWindow(1024, 800, 'current');
    }

    /**
    * @BeforeScenario @largeScreen
    */
    function largeScreen ($event) {
      $this->getSession()->resizeWindow(1400, 1024, 'current');
    }



  /***************************************************
  *
  * Perceptual Diff Related Hooks
  *
  ****************************************************/




    /**
    * @AfterStep @RecordFailedSteps
    * This will run after a step has failed.
    */
    public function recordFailedSteps($scope) {
      $result = $scope->getResult();
      if ($result == 4) {
        // The step has failed.
        $image_data = $this->getSession()->getDriver()->getScreenshot();
        $file_and_path = getcwd() . '/images/failed/' . time() . '.png';
        file_put_contents($file_and_path, $image_data);
        print "Created screenshot for failed step, located: $file_and_path \n";
      }
    }

    /**
    * @AfterScenario @PerceptualDiff
    */
    public function perceptualDiffScenario($event) {
      $scenario = str_replace(' ', '-', $event->getScenario()->getTitle() );

      // 1. Check if a file exists for this scenario yet
      $mockup = getcwd() . '/images/mockups/' . $scenario . '.png';
      if ( file_exists($mockup) ) {
        // a. If the File exists continue with comparing it to the live version.

        // 2. Create the image for the current version of the test
        $live = getcwd() . '/images/live/' . $scenario . '.png';
        $image_data = $this->getSession()->getDriver()->getScreenshot();
        file_put_contents($live, $image_data);

        // 3. Depending on the platform create the perceptual diff
        $diff = getcwd() . '/images/diff/' . $scenario . '-' . time() . '.png';

        switch (PHP_OS) {
          case 'WIN32':
          case 'Windows':
            /* windows needs the executable */
            $build = 'vendor/perceptualdiff/perceptualdiff-win.exe';
          break;
          default:
            /* *nix and Mac systems can use the same script */
            $build = 'vendor/perceptualdiff/perceptualdiff';
          break;
        }
        $command = $build . ' ' . $mockup . ' ' . $live . ' -output ' . $diff;
        shell_exec( $command );

      }
      else {
        $image_data = $this->getSession()->getDriver()->getScreenshot();
        file_put_contents($mockup, $image_data);
        print "There is no mockup for $mockup.\n";
      }

    }



}

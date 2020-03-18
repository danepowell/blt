<?php

namespace Acquia\Blt\Tests\BltProject;

use Drupal\BuildTests\Framework\BuildTestBase;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class AcCloudHooksTest.
 */
class AcCloudHooksTest extends BuildTestBase {

  /**
   * Tests recipes:cloud-hooks:init command.
   */
  public function testSetupCloudHooks() {
    $this->copyCodebase();

    $this->executeCommand($this->getDrupalRoot() . '/../vendor/bin/blt recipes:cloud-hooks:init');
    $this->assertCommandSuccessful();

    $this->assertFileExists($this->getDrupalRoot() . '/../hooks');

    $commonPostCodeDeployScript = $this->getDrupalRoot() . '/../hooks/common/post-code-deploy/post-code-deploy.sh';
    $this->assertFileExists($commonPostCodeDeployScript);

    $filePermissions = substr(sprintf('%o', fileperms($commonPostCodeDeployScript)), -4);
    $this->assertEquals('0755', $filePermissions);

    // You can keep the process object for later assertions or manipulations.
    $this->executeCommand(
      $this->getDrupalRoot() . '/core/scripts/drupal install minimal'
    );
    $this->assertCommandSuccessful();

  }

}

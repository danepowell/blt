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

    $this->executeCommand('blt recipes:cloud-hooks:init');
    $this->assertCommandSuccessful();

    $this->assertFileExists($this->getDrupalRoot() . '/hooks');

    $commonPostCodeDeployScript = $this->getDrupalRoot() . '/hooks/common/post-code-deploy/post-code-deploy.sh';
    $this->assertFileExists($commonPostCodeDeployScript);

    $filePermissions = substr(sprintf('%o', fileperms($commonPostCodeDeployScript)), -4);
    $this->assertEquals('0755', $filePermissions);

    $finder = new PhpExecutableFinder();
    // You can keep the process object for later assertions or manipulations.
    $this->executeCommand(
      $finder->find() . ' ./core/scripts/drupal install minimal'
    );
    $this->assertCommandSuccessful();

    // Mimics hooks/post-code-deploy/post-code-deploy.sh.
    $this->executeCommand("blt artifact:ac-hooks:post-code-update", [
      'site' => 's1',
      'target_env' => 'dev',
      'source_branch' => 'master',
      'deployed_tag' => 'master',
      'repo_url' => 's1@svn-3.bjaspan.hosting.acquia.com:s1.git',
      'repo_type' => 'git',
    ]);
    $this->assertCommandSuccessful();
    $this->assertCommandOutputContains('Finished updates for environment: dev');
  }

}

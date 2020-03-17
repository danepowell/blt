<?php

namespace Acquia\Blt\Tests;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Manage BLT testing sandbox.
 */
class SandboxManager {

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * BLT dir.
   *
   * @var bool|string
   */
  protected $bltDir;

  /**
   * Sandbox master.
   *
   * @var string
   */
  protected $sandboxMaster;

  /**
   * Sandbox instance.
   *
   * @var string
   */
  protected $sandboxInstance;

  /**
   * @var \Symfony\Component\Console\Output\ConsoleOutput
   */
  protected $output;

  /**
   * Temp.
   *
   * @var string
   */
  protected $tmp;

  /**
   * SandboxManager constructor.
   */
  public function __construct() {
    $this->output = new ConsoleOutput();
    $this->fs = new Filesystem();
    $this->tmp = sys_get_temp_dir();
    $this->sandboxMaster = $this->tmp . "/blt-sandbox-master";
    $this->sandboxInstance = $this->tmp . "/blt-sandbox-instance";
    $this->bltDir = realpath(dirname(__FILE__) . '/../../../');
  }

  /**
   * Outputs debugging message.
   *
   * @param string $message
   *   Message.
   */
  public function debug($message) {
    if (getenv('BLT_PRINT_COMMAND_OUTPUT')) {
      $this->output->writeln($message);
    }
  }

  /**
   * Makes sandbox instance writable.
   */
  public function makeSandboxInstanceWritable() {
    $sites_dir = $this->sandboxInstance . "/docroot/sites";
    if (file_exists($sites_dir)) {
      $this->fs->chmod($sites_dir, 0755, 0000, TRUE);
    }
  }

  /**
   * Overwrites all files in sandbox instance.
   */
  public function resetSandbox() {

  }

  /**
   * Get sandbox instance.
   *
   * @return mixed
   *   Mixed.
   */
  public function getSandboxInstance() {
    return $this->sandboxInstance;
  }

  /**
   * Updates composer.json in sandbox master to reference BLT via symlink.
   */
  protected function updateSandboxMasterBltRepoSymlink() {
    $composer_json_path = $this->sandboxMaster . "/composer.json";
    $composer_json_contents = json_decode(file_get_contents($composer_json_path));
    $composer_json_contents->repositories->blt = (object) [
      'type' => 'path',
      'url' => $this->bltDir,
      'options' => [
        'symlink' => TRUE,
      ],
    ];
    $composer_json_contents->require->{'acquia/blt'} = '*@dev';
    $this->fs->dumpFile($composer_json_path,
      json_encode($composer_json_contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

  /**
   * Installs composer dependencies in sandbox master dir.
   *
   * @throws \Exception
   */
  protected function installSandboxMasterDependencies() {
    $command = '';
    $drupal_core_version = getenv('DRUPAL_CORE_VERSION');
    if ($drupal_core_version && $drupal_core_version != 'default') {
      $command .= 'composer require "drupal/core:' . $drupal_core_version . '" --no-update --no-interaction && ';
    }
    $command .= 'composer install --prefer-dist --no-progress --no-suggest';

    $process = new Process($command, $this->sandboxMaster);
    $process->setTimeout(60 * 60);
    $process->run(function ($type, $buffer) {
      $this->output->write($buffer);
    });
    if (!$process->isSuccessful()) {
      throw new \Exception("Composer installation failed.");
    }
  }

}

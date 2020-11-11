<?php

namespace Drupal\drd_agent\Agent\Action;


use Drupal\Core\Database\Database as CoreDatabase;

/**
 * Provides a 'Database' download file.
 */
class Database extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $databases = CoreDatabase::getAllConnectionInfo();

    exec('mysqldump --version', $output, $ret);
    $mysql = ($ret === 0);
    if ($mysql) {
      foreach ($databases as $key => $info) {
        foreach ($info as $target => $config) {
          $file = $this->realPath($this->fileSystem->tempnam('temporary://', implode('-', [
            'drd',
            'db',
            $target,
            $key,
            '',
          ])) . '.sql');
          $credentialsfile = $this->realPath($this->fileSystem->tempnam('temporary://', 'mysqldump'));

          $cmd = [
            'mysqldump',
            '--defaults-extra-file=' . $credentialsfile,
            $config['database'],
            '>' . $file,
          ];
          $credentials = [
            '[mysqldump]',
            'host = ' . $config['host'],
            'port = ' . $config['port'],
            'user = ' . $config['username'],
            'password = "' . $config['password'] . '"',
          ];

          file_put_contents($credentialsfile, implode("\n", $credentials));
          chmod($credentialsfile, 0600);
          exec(implode(' ', $cmd), $output, $ret);
          unlink($credentialsfile);

          $databases[$key][$target]['file'] = $file;
        }
      }
      return $databases;
    }
    return FALSE;
  }

}

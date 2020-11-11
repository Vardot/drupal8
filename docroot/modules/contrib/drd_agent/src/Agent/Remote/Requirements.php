<?php

namespace Drupal\drd_agent\Agent\Remote;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Exception;
use GuzzleHttp\Client;

/**
 * Implements the Requirements class.
 */
class Requirements extends Base {

  /**
   * {@inheritdoc}
   */
  public function collect(): array {
    $requirements = [];

    $info = $this->database->getConnectionOptions();
    $info += [
      'driver' => '',
      'namespace' => '',
      'host' => '',
      'port' => '',
      'database' => '',
      'username' => '',
      'password' => '',
    ];
    $info['prefix'] = $info['prefix']['default'];
    foreach ($info as $key => $value) {
      unset($info[$key]);
      if (is_scalar($value)) {
        $info['@' . $key] = $value;
      }
    }
    $requirements['drd_agent.database'] = [
      'title' => t('Database setup'),
      'value' => t('<table>
        <tr><td>Driver</td><td>@driver</td></tr>
        <tr><td>Namespace</td><td>@namespace</td></tr>
        <tr><td>Host</td><td>@host</td></tr>
        <tr><td>Port</td><td>@port</td></tr>
        <tr><td>Database</td><td>@database</td></tr>
        <tr><td>username</td><td>@username</td></tr>
        <tr><td>password</td><td>@password</td></tr>
        <tr><td>Prefix</td><td>@prefix</td></tr>
        </table>', $info),
      'severity' => REQUIREMENT_INFO,
      'description' => t('These are the database settings that have been configured for this site in settings.php.'),
    ];

    $analytics = (
      $this->moduleHandler->moduleExists('google_analytics') ||
      $this->moduleHandler->moduleExists('google_tag') ||
      $this->moduleHandler->moduleExists('piwik') ||
      $this->moduleHandler->moduleExists('matomo')
    );
    $requirements['drd_agent.module.analytics'] = [
      'title' => t('Analytics module installed'),
      'value' => $analytics ? t('Yes') : t('No'),
      'severity' => $analytics ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $analytics ?
      t('This site uses an analytics tool, go here to configure
          <a href="@url1">Google Analytics</a>,
          <a href="@url2">Google Tag Manager</a> or
          <a href="@url4">Matomo</a> or
          <a href="@url3">Piwik</a>.', [
            '@url1' => Url::fromUserInput('/admin/config/system/google-analytics')->toUriString(),
            '@url2' => Url::fromUserInput('/admin/config/system/google_tag')->toUriString(),
            '@url3' => Url::fromUserInput('/admin/config/system/piwik')->toUriString(),
            '@url4' => Url::fromUserInput('/admin/config/system/matomo')->toUriString(),
          ]) :
      t('For SEO improvements you should use an analytics tool like
          <a href="@url1">Google Analytics</a>,
          <a href="@url2">Google Tag Manager</a> or
          <a href="@url4">Matomo</a> or
          <a href="@url3">Piwik</a>.', [
            '@url1' => Url::fromUri('https://www.drupal.org/project/google_analytics', ['external' => TRUE])->toUriString(),
            '@url2' => Url::fromUri('https://www.drupal.org/project/google_tag', ['external' => TRUE])->toUriString(),
            '@url3' => Url::fromUri('https://www.drupal.org/project/piwik', ['external' => TRUE])->toUriString(),
            '@url4' => Url::fromUri('https://www.drupal.org/project/matomo', ['external' => TRUE])->toUriString(),
          ]),
    ];

    $devel = !$this->moduleHandler->moduleExists('devel');
    $requirements['drd_agent.module.devel'] = [
      'title' => t('Devel module disabled'),
      'value' => $devel ? t('Yes') : t('No'),
      'severity' => $devel ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => t('On production sites the <a href="@url">Devel module</a> should be disabled for security and performance reasons.', ['@url' => Url::fromUserInput('/admin/modules')->toUriString()]),
    ];

    $metatag = $this->moduleHandler->moduleExists('metatag');
    $requirements['drd_agent.module.metatag'] = [
      'title' => t('Module MetaTag installed'),
      'value' => $metatag ? t('Yes') : t('No'),
      'severity' => $metatag ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $metatag ?
      t('The MetaTag module is enabled, its <a href="@url">settings can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/search/metatags')->toUriString()]) :
      t('For SEO improvements you should use the <a href="@url">MetaTag</a> module.', ['@url' => Url::fromUri('https://www.drupal.org/project/metatag')->toUriString()]),
    ];

    $pathauto = $this->moduleHandler->moduleExists('pathauto');
    $requirements['drd_agent.module.pathauto'] = [
      'title' => t('Module PathAuto installed'),
      'value' => $pathauto ? t('Yes') : t('No'),
      'severity' => $pathauto ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $pathauto ?
      t('The PathAuto module is enabled, it <a href="@url">can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/search/path/patterns')->toUriString()]) :
      t('For SEO improvements you should use the <a href="@url">PathAuto</a> module.', ['@url' => Url::fromUri('https://www.drupal.org/project/pathauto', ['external' => TRUE])->toUriString()]),
    ];

    $php = !$this->moduleHandler->moduleExists('php');
    $requirements['drd_agent.module.php'] = [
      'title' => t('Module PHP Filter disabled'),
      'value' => $php ? t('Yes') : t('No'),
      'severity' => $php ? REQUIREMENT_OK : REQUIREMENT_ERROR,
      'description' => $php ?
      t('For security reasons you should try to avoid using the <a href="@url">PHP Filter</a> module.', ['@url' => Url::fromUserInput('/admin/modules')->toUriString()]) :
      t('For security reasons you should keep the <a href="@url">PHP Filter</a> module disabled.', ['@url' => Url::fromUserInput('/admin/modules')->toUriString()]),
    ];

    $sitemap = (
      $this->moduleHandler->moduleExists('simple_sitemap') ||
      $this->moduleHandler->moduleExists('sitemap') ||
      $this->moduleHandler->moduleExists('xmlsitemap')
    );
    $requirements['drd_agent.module.xmlsitemap'] = [
      'title' => t('Sitemap module installed'),
      'value' => $sitemap ? t('Yes') : t('No'),
      'severity' => $sitemap ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $sitemap ?
      t('A sitemap module is enabled.') :
      t('For SEO improvements you should use one of these <a href="@url">sitemap</a> modules:
        <a href="@url1">Simple sitemap</a>,
        <a href="@url2">Sitemap</a>,
        <a href="@url3">XML-Sitemap</a>.', [
          '@url1' => Url::fromUri('https://www.drupal.org/project/simple_sitemap', ['external' => TRUE])->toUriString(),
          '@url2' => Url::fromUri('https://www.drupal.org/project/xmlitemap', ['external' => TRUE])->toUriString(),
          '@url3' => Url::fromUri('https://www.drupal.org/project/xmlsitemap', ['external' => TRUE])->toUriString(),
        ]),
    ];

    if ($user1 = User::load(1)) {
      $user1_ok = !in_array(strtolower($user1->getAccountName()), [
        'admin',
        'root',
        'superadmin',
        'manager',
        'administrator',
        'adm',
      ]);
      $requirements['drd_agent.user1'] = [
        'title' => t('Name of user 1'),
        'value' => $user1_ok ? t('Good') : t('Too obvious'),
        'severity' => $user1_ok ? REQUIREMENT_OK : REQUIREMENT_WARNING,
        'description' => $user1_ok ?
        t('The name of user 1 is uncommon enough to not be a very obvious security risk') :
        t('For security reasons the name of user 1 should not be so obvious as it is now.'),
      ];
    }

    try {
      $admin_roles = $this->entityTypeManager->getStorage('user_role')
        ->getQuery()
        ->condition('is_admin', TRUE)
        ->execute();
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    if (empty($admin_roles)) {
      $count_admin = 0;
    }
    else {
      /* @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->database->select('user__roles', 'ur')
        ->fields('ur', ['entity_id'])
        ->condition('ur.roles_target_id', $admin_roles, 'IN');
      /** @noinspection NullPointerExceptionInspection */
      $count_admin = $query
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    $requirements['drd_agent.admincount'] = [
      'title' => t('Number of admins'),
      'value' => ($count_admin <= 3) ? t('Good (@count)', ['@count' => $count_admin]) : t('Too many (@count)', ['@count' => $count_admin]),
      'severity' => ($count_admin <= 3) ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => t('For security reasons you should only have a small amount of users with an administer role.'),
    ];

    $css = $this->configFactory->get('system.performance')->get('css.preprocess');
    $requirements['drd_agent.compress.css'] = [
      'title' => t('Aggregate and compress CSS files'),
      'value' => $css ? t('Yes') : t('No'),
      'severity' => $css ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $css ?
      t('The CSS is aggregated on this site. <a href="@url">Performance settings can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]) :
      t('For performance reasons you should allow your <a href="@url">CSS to be aggregated</a> on production sites.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]),
    ];

    $js = $this->configFactory->get('system.performance')->get('js.preprocess');
    $requirements['drd_agent.compress.js'] = [
      'title' => t('Aggregate JavaScript files'),
      'value' => $js ? t('Yes') : t('No'),
      'severity' => $js ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $js ?
      t('The JS is aggregated on this site. <a href="@url">Performance settings can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]) :
      t('For performance reasons you should allow your <a href="@url">JS to be aggregated</a> on production sites.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]),
    ];

    $page = $this->configFactory->get('system.performance')->get('response.gzip');
    $requirements['drd_agent.compress.page'] = [
      'title' => t('Compress cached pages'),
      'value' => $page ? t('Yes') : t('No'),
      'severity' => $page ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $page ?
      t('The pages are being compressed on this site. <a href="@url">Performance settings can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]) :
      t('For performance reasons you should allow <a href="@url">cached pages to be compressed</a> on production sites.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]),
    ];

    $page403 = $this->configFactory->get('system.site')->get('page.403');
    $requirements['drd_agent.defined.403'] = [
      'title' => t('Default 403 (access denied) page'),
      'value' => empty($page403) ? t('Undefined') : $page403,
      'severity' => empty($page403) ? REQUIREMENT_WARNING : REQUIREMENT_OK,
      'description' => $page403 ?
      t('There is a 403 page defined. <a href="@url">The 403 page can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/system/site-information')->toUriString()]) :
      t('For improved user experience you should define a <a href="@url">default 403 (Access denied)</a> page.', ['@url' => Url::fromUserInput('/admin/config/system/site-information')->toUriString()]),
    ];

    $page404 = $this->configFactory->get('system.site')->get('page.404');
    $requirements['drd_agent.defined.404'] = [
      'title' => t('Default 404 (not found) page'),
      'value' => empty($page404) ? t('Undefined') : $page404,
      'severity' => empty($page404) ? REQUIREMENT_WARNING : REQUIREMENT_OK,
      'description' => $page404 ?
      t('There is a 404 page defined. <a href="@url">The 404 page can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/system/site-information')->toUriString()]) :
      t('For improved user experience you could define a <a href="@url">default 404 (Not found)</a> page.', ['@url' => Url::fromUserInput('/admin/config/system/site-information')->toUriString()]),
    ];

    $cache = $this->configFactory->get('system.performance')->get('cache.page');
    $requirements['drd_agent.enable.cache'] = [
      'title' => t('Cache pages for anonymous users'),
      'value' => $cache ? t('Yes') : t('No'),
      'severity' => $cache ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $cache ?
      t('The pages are being cached. <a href="@url">Performance settings can be managed here</a>.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]) :
      t('For performance reasons you should <a href="@url">cache pages for anonymous users</a> on production sites.', ['@url' => Url::fromUserInput('/admin/config/development/performance')->toUriString()]),
    ];

    $warnings = $this->configFactory->get('system.logging')->get('error_level');
    $requirements['drd_agent.hidden.warnings'] = [
      'title' => t('Error messages to display'),
      'description' => t('For security reasons you should <a href="@url">write all errors and warnings</a> to the log.', ['@url' => Url::fromUserInput('/admin/config/development/logging')->toUriString()]),
    ];

    switch ($warnings) {
      case ERROR_REPORTING_HIDE:
        $requirements['drd_agent.hidden.warnings']['value'] = t('None');
        $requirements['drd_agent.hidden.warnings']['severity'] = REQUIREMENT_OK;
        break;

      case ERROR_REPORTING_DISPLAY_SOME:
        $requirements['drd_agent.hidden.warnings']['value'] = t('Errors and warnings');
        $requirements['drd_agent.hidden.warnings']['severity'] = REQUIREMENT_WARNING;
        break;

      default:
        $requirements['drd_agent.hidden.warnings']['value'] = t('All messages');
        $requirements['drd_agent.hidden.warnings']['severity'] = REQUIREMENT_ERROR;
    }

    $txtfiles = [];
    $files_to_remove = [
      'CHANGELOG.txt',
      'COPYRIGHT.txt',
      'INSTALL.mysql.txt',
      'INSTALL.pgsql.txt',
      'INSTALL.txt',
      'LICENSE.txt',
      'MAINTAINERS.txt',
      'README.txt',
      'UPGRADE.txt',
    ];
    foreach ($files_to_remove as $file) {
      if (file_exists(DRUPAL_ROOT . '/' . $file)) {
        $txtfiles[] = $file;
      }
    }
    $requirements['drd_agent.removed.txtfiles'] = [
      'title' => t('Info files to be removed'),
      'value' => empty($txtfiles) ? t('All info files properly removed') : implode(', ', $txtfiles),
      'severity' => empty($txtfiles) ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $requirements ?
      t('The info files of Drupal Core are removed.') :
      t('The info files in the Drupal Core could be removed to expose less about which version Drupal is running.'),
    ];

    $robotsurl = Url::fromUri('base://robots.txt', ['absolute' => TRUE, 'language' => (object) ['language' => LanguageInterface::LANGCODE_NOT_SPECIFIED]])->toString();
    try {
      $client = new Client([
        'base_uri' => $robotsurl,
        'timeout' => 2,
        'allow_redirects' => FALSE,
      ]);
      $response = $client->request('get');
    }
    catch (Exception $ex) {
      // Ignore.
    }
    $robots = (isset($response) && $response->getStatusCode() === 200);
    $requirements['drd_agent.robots.txt'] = [
      'title' => t('File robots.txt is available'),
      'value' => $robots ? t('Yes') : t('No'),
      'severity' => $robots ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => $robots ?
      t('This site contains a <a href="@url">robots.txt</a> file', ['@url' => Url::fromUserInput('/robots.txt')->toUriString()]) :
      t('For SEO reasons this site should have a <a href="@url">robots.txt</a> file in the Drupal Core.', ['@url' => Url::fromUri('https://www.drupal.org/project/robotstxt', ['external' => TRUE])->toUriString()]),
    ];

    $themeregistry = $this->configFactory->get('devel.settings')->get('rebuild_theme');
    $requirements['drd_agent.theme.registry'] = [
      'title' => t('Rebuild theme registry on each page load'),
      'value' => $themeregistry ? t('Yes') : t('No'),
      'severity' => $themeregistry ? REQUIREMENT_WARNING : REQUIREMENT_OK,
      'description' => $themeregistry ?
      t('Your site is not rebuilding the them registry on each page load. Thats good.') :
      t('For performance reasons this site should not <a href="@url">rebuild the theme registry</a> on each page load.', ['@url' => Url::fromUserInput('/admin/appearance/settings')->toUriString()]),
    ];

    $watchdog = $this->configFactory->get('dblog.settings')->get('row_limit');
    $requirements['drd_agent.trim.watchdog'] = [
      'title' => t('Database log messages to keep'),
      'value' => empty($watchdog) ? t('All') : $watchdog,
      'severity' => ($watchdog <= 1000 && $watchdog > 0) ? REQUIREMENT_OK : REQUIREMENT_WARNING,
      'description' => t('For performance reasons the <a href="@url">database log</a> should not be bigger then 1000 messages.', ['@url' => Url::fromUserInput('/admin/config/development/logging')->toUriString()]),
    ];

    return $requirements;
  }

}

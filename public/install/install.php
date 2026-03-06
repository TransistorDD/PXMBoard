<?php
/**
 * PXMBoard Installer
 *
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

$sPublicDirPath  = dirname(__DIR__);
$sBaseDirPath    = dirname($sPublicDirPath);
$sConfigFile     = $sBaseDirPath . '/config/pxmboard-config.php';
$sBaseDirFile    = $sPublicDirPath . '/pxmboard-basedir.php';
$sSqlFile        = $sBaseDirPath . '/install/sql/pxmboard-mysql.sql';
$bXslAvailable   = extension_loaded('xsl');

// -------------------------------------------------------------------------
// Self-deletion action
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_installer') {
    $bDeleted = @unlink(__FILE__);
    @rmdir(__DIR__);
    if ($bDeleted) {
        header('Location: ../pxmboard.php');
        exit;
    }
    $sDeleteError = 'Deletion failed. Please remove the directory <code>public/install/</code> manually.';
}

// -------------------------------------------------------------------------
// Lock page: already installed
// -------------------------------------------------------------------------
if (file_exists($sConfigFile)) {
    renderPage('Already installed', function () use ($sConfigFile): void { ?>
        <div class="pxm-alert pxm-alert--warning">
            <strong>The board is already configured.</strong><br>
            To run the installer again, delete
            <code><?= htmlspecialchars($sConfigFile) ?></code> and reload this page.
        </div>
        <a href="../pxmboard.php" class="pxm-btn pxm-btn--primary">Go to board</a>
    <?php });
    exit;
}

// -------------------------------------------------------------------------
// Default form values
// -------------------------------------------------------------------------
$arrValues = [
    'db_type'        => 'MySql',
    'db_host'        => 'localhost',
    'db_user'        => 'pxmboard',
    'db_pass'        => '',
    'db_name'        => 'pxmboard',
    'template_types' => ['Smarty'],
    'search_type'    => 'MySql',
    'es_host'        => 'https://localhost:9200',
    'es_index'       => 'pxmboard_messages',
    'es_api_key'     => '',
    'session_name'   => 'brdsid',
    'basedir'        => $sBaseDirPath,
    'admin_user'     => 'Webmaster',
    'admin_pass'     => '',
    'install_sql'    => 'yes',
];

$arrErrors  = [];
$bSuccess   = false;
$arrSqlErrors = [];
$bAdminCreated = false;
$bSqlRun    = false;

// -------------------------------------------------------------------------
// POST processing
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete_installer') {

    // Extract and sanitise input
    $arrValues['db_type']        = in_array($_POST['db_type'] ?? '', ['MySql', 'PostgreSql'], true)
                                    ? $_POST['db_type'] : 'MySql';
    $arrValues['db_host']        = trim($_POST['db_host'] ?? '');
    $arrValues['db_user']        = trim($_POST['db_user'] ?? '');
    $arrValues['db_pass']        = $_POST['db_pass'] ?? '';
    $arrValues['db_name']        = trim($_POST['db_name'] ?? '');
    $arrValues['template_types'] = array_filter(
        array_map('trim', (array)($_POST['template_types'] ?? [])),
        fn ($t) => in_array($t, ['Smarty', 'Xslt'], true)
    );
    $arrValues['search_type']    = in_array($_POST['search_type'] ?? '', ['MySql', 'ElasticSearch'], true)
                                    ? $_POST['search_type'] : 'MySql';
    $arrValues['es_host']        = trim($_POST['es_host'] ?? '');
    $arrValues['es_index']       = trim($_POST['es_index'] ?? '');
    $arrValues['es_api_key']     = trim($_POST['es_api_key'] ?? '');
    $arrValues['session_name']   = trim($_POST['session_name'] ?? '');
    $arrValues['basedir']        = rtrim(trim($_POST['basedir'] ?? ''), '/\\');
    $arrValues['admin_user']     = trim($_POST['admin_user'] ?? '');
    $arrValues['admin_pass']     = $_POST['admin_pass'] ?? '';
    $arrValues['install_sql']    = ($_POST['install_sql'] ?? 'no') === 'yes' ? 'yes' : 'no';

    // Validation
    if (empty($arrValues['db_host'])) {
        $arrErrors['db_host']   = 'DB host is required.';
    }
    if (empty($arrValues['db_user'])) {
        $arrErrors['db_user']   = 'DB user is required.';
    }
    if (empty($arrValues['db_name'])) {
        $arrErrors['db_name']   = 'Database name is required.';
    }
    if (empty($arrValues['template_types'])) {
        $arrErrors['template_types'] = 'At least one template engine must be selected.';
    }
    if ($arrValues['search_type'] === 'ElasticSearch' && empty($arrValues['es_host'])) {
        $arrErrors['es_host'] = 'ElasticSearch host is required.';
    }
    if ($arrValues['search_type'] === 'ElasticSearch' && empty($arrValues['es_index'])) {
        $arrErrors['es_index'] = 'ElasticSearch index is required.';
    }
    if (empty($arrValues['session_name'])) {
        $arrErrors['session_name'] = 'Session name is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $arrValues['session_name'])) {
        $arrErrors['session_name'] = 'Session name may only contain letters and digits.';
    }
    if (empty($arrValues['basedir'])) {
        $arrErrors['basedir'] = 'Project path is required.';
    } elseif (!is_dir($arrValues['basedir'])) {
        $arrErrors['basedir'] = 'Path does not exist or is not a directory.';
    }
    if (empty($arrValues['admin_user'])) {
        $arrErrors['admin_user'] = 'Admin username is required.';
    }
    if ($arrValues['install_sql'] === 'yes' && empty($arrValues['admin_pass'])) {
        $arrErrors['admin_pass'] = 'Admin password is required when installing the SQL schema.';
    }

    // Test DB connection (only if no field validation errors yet)
    $objDb = null;
    if (empty($arrErrors)) {
        if ($arrValues['db_type'] === 'MySql') {
            $objDb = @new mysqli(
                $arrValues['db_host'],
                $arrValues['db_user'],
                $arrValues['db_pass'],
                $arrValues['db_name']
            );
            if ($objDb->connect_error) {
                $arrErrors['db_host'] = 'Database connection failed: ' . htmlspecialchars($objDb->connect_error);
                $objDb = null;
            } else {
                $objDb->set_charset('utf8mb4');
            }
        } else {
            // PostgreSQL: skip connection test, note for user
        }
    }

    // Write files and execute SQL
    if (empty($arrErrors)) {
        $sActualBasedir = $arrValues['basedir'];
        $sActualConfigFile = $sActualBasedir . '/config/pxmboard-config.php';
        $sActualBaseDirFile = $sPublicDirPath . '/pxmboard-basedir.php';

        // 1. Write pxmboard-basedir.php
        $sBaseDirContent = '<?php' . PHP_EOL .
            'define(\'BASEDIR\', ' . var_export($sActualBasedir, true) . ');' . PHP_EOL .
            '?>' . PHP_EOL;
        if (file_put_contents($sActualBaseDirFile, $sBaseDirContent) === false) {
            $arrErrors['basedir'] = 'Could not write pxmboard-basedir.php. Check write permissions for ' . htmlspecialchars($sPublicDirPath) . '/';
        }

        // 2. Build search engine config
        if ($arrValues['search_type'] === 'ElasticSearch') {
            $arrSearchConf = [
                'type'    => 'ElasticSearch',
                'host'    => $arrValues['es_host'],
                'index'   => $arrValues['es_index'],
                'api_key' => $arrValues['es_api_key'],
            ];
        } else {
            $arrSearchConf = ['type' => 'MySql'];
        }

        // 3. Write config/pxmboard-config.php
        $sConfigContent = generateConfig(
            $arrValues['db_type'],
            $arrValues['db_host'],
            $arrValues['db_user'],
            $arrValues['db_pass'],
            $arrValues['db_name'],
            array_values($arrValues['template_types']),
            $arrSearchConf,
            $arrValues['session_name']
        );
        if (empty($arrErrors) && file_put_contents($sActualConfigFile, $sConfigContent) === false) {
            $arrErrors['basedir'] = 'Could not write config/pxmboard-config.php. Check write permissions for ' . htmlspecialchars(dirname($sActualConfigFile)) . '/';
        }

        // 4. Execute SQL and create admin user
        if (empty($arrErrors) && $arrValues['install_sql'] === 'yes' && $arrValues['db_type'] === 'MySql' && $objDb !== null) {
            $sActualSqlFile = $sActualBasedir . '/install/sql/pxmboard-mysql.sql';
            if (!file_exists($sActualSqlFile)) {
                $arrSqlErrors[] = 'SQL file not found: ' . htmlspecialchars($sActualSqlFile);
            } else {
                $arrSqlErrors = executeSqlFile($objDb, $sActualSqlFile);
                $bSqlRun = true;
                if (empty($arrSqlErrors)) {
                    // Create admin user
                    $sPasswordHash = password_hash($arrValues['admin_pass'], PASSWORD_DEFAULT);
                    $sPasswordKey  = bin2hex(random_bytes(16));
                    $iNow          = time();
                    $sAdminUser    = $objDb->real_escape_string($arrValues['admin_user']);
                    $sSql = "INSERT INTO pxm_user
                                (u_username, u_password, u_passwordkey, u_registrationtstmp, u_status, u_admin, u_post, u_edit)
                             VALUES
                                ('{$sAdminUser}', '{$sPasswordHash}', '{$sPasswordKey}', {$iNow}, 1, 1, 1, 1)";
                    if ($objDb->query($sSql)) {
                        $bAdminCreated = true;
                    } else {
                        $arrSqlErrors[] = 'Could not create admin user: ' . htmlspecialchars($objDb->error);
                    }
                }
            }
        }

        if (empty($arrErrors)) {
            $bSuccess = true;
        }
    }

    if ($objDb !== null) {
        $objDb->close();
    }
}

// -------------------------------------------------------------------------
// Render
// -------------------------------------------------------------------------
if ($bSuccess) {
    renderPage('Installation complete', function () use (
        $arrValues,
        $bAdminCreated,
        $arrSqlErrors
    ): void {
        ?>
        <div class="pxm-alert pxm-alert--success">
            <strong>Installation completed successfully.</strong>
        </div>

        <?php if (!empty($arrSqlErrors)): ?>
        <div class="pxm-alert pxm-alert--error">
            <strong>SQL errors (configuration was written anyway):</strong>
            <ul style="margin:8px 0 0 0; padding-left: 20px;">
                <?php foreach ($arrSqlErrors as $sErr): ?>
                    <li><?= htmlspecialchars($sErr) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($arrValues['install_sql'] === 'no' || !empty($arrSqlErrors)): ?>
        <div class="pxm-alert pxm-alert--warning">
            <?php if ($arrValues['install_sql'] === 'no'): ?>
                The database schema was <strong>not</strong> executed. Please run
                <code>install/sql/pxmboard-mysql.sql</code> manually and create an admin user via SQL afterwards.
            <?php else: ?>
                Admin user was <strong>not</strong> created due to SQL errors.
                Please create it manually.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="pxm-admin-card">
            <div class="pxm-admin-card__header">Summary</div>
            <div class="pxm-admin-card__body">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <?php $rows = [
                        'Database'       => $arrValues['db_type'] . ' @ ' . $arrValues['db_host'] . '/' . $arrValues['db_name'],
                        'Template engine' => implode(', ', $arrValues['template_types']),
                        'Search engine'  => $arrValues['search_type'],
                        'Session name'   => $arrValues['session_name'],
                        'BASEDIR'        => $arrValues['basedir'],
                        'Admin user'     => $bAdminCreated ? htmlspecialchars($arrValues['admin_user']) . ' (created)' : 'not created',
                    ];
        foreach ($rows as $label => $value): ?>
                    <tr>
                        <td style="padding:4px 12px 4px 0; font-weight:500; white-space:nowrap; color:#555; width:160px;"><?= htmlspecialchars($label) ?></td>
                        <td style="padding:4px 0;"><?= $value ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="pxm-alert pxm-alert--error">
            <strong>Security notice:</strong> Delete the directory
            <code>public/install/</code> now to disable the installer!
        </div>

        <div class="pxm-btn-row">
            <form method="post">
                <input type="hidden" name="action" value="delete_installer">
                <button type="submit" class="pxm-btn pxm-btn--danger">
                    Delete installer files
                </button>
            </form>
            <a href="../pxmboard.php" class="pxm-btn pxm-btn--primary">Go to board</a>
        </div>
        <?php
    });
    exit;
}

$sDeleteErrorMsg = $sDeleteError ?? null;
renderPage('Installation', function () use ($arrValues, $arrErrors, $bXslAvailable, $sDeleteErrorMsg): void {
    if (!empty($arrErrors)): ?>
    <div class="pxm-alert pxm-alert--error">
        <strong>Please correct the highlighted fields:</strong>
    </div>
    <?php endif;

    if (!empty($sDeleteErrorMsg)): ?>
    <div class="pxm-alert pxm-alert--error"><?= $sDeleteErrorMsg ?></div>
    <?php endif; ?>

    <form method="post" novalidate>

        <?php /* ---- Section 1: Database ---- */ ?>
        <div class="pxm-admin-card">
            <div class="pxm-admin-card__header">1. Database</div>
            <div class="pxm-admin-card__body">

                <div class="pxm-form-group">
                    <label>Database type</label>
                    <div class="pxm-field">
                        <div class="pxm-radio-group">
                            <label><input type="radio" name="db_type" value="MySql" <?= $arrValues['db_type'] === 'MySql' ? 'checked' : '' ?>> MySQL / MariaDB</label>
                            <label><input type="radio" name="db_type" value="PostgreSql" <?= $arrValues['db_type'] === 'PostgreSql' ? 'checked' : '' ?>> PostgreSQL (experimental)</label>
                        </div>
                    </div>
                </div>

                <?php formField('db_host', 'Host', $arrValues, $arrErrors, 'localhost') ?>
                <?php formField('db_user', 'User', $arrValues, $arrErrors, 'pxmboard') ?>
                <?php formField('db_pass', 'Password', $arrValues, $arrErrors, '', 'password') ?>
                <?php formField('db_name', 'Database name', $arrValues, $arrErrors, 'pxmboard') ?>

            </div>
        </div>

        <?php /* ---- Section 2: Template Engine ---- */ ?>
        <div class="pxm-admin-card">
            <div class="pxm-admin-card__header">2. Template Engine</div>
            <div class="pxm-admin-card__body">
                <div class="pxm-form-group">
                    <label>Template types</label>
                    <div class="pxm-field">
                        <div class="pxm-checkbox-group">
                            <label>
                                <input type="checkbox" name="template_types[]" value="Smarty"
                                    <?= in_array('Smarty', $arrValues['template_types'], true) ? 'checked' : '' ?>>
                                Smarty
                            </label>
                            <label class="<?= $bXslAvailable ? '' : 'pxm-greyed' ?>">
                                <input type="checkbox" name="template_types[]" value="Xslt"
                                    <?= in_array('Xslt', $arrValues['template_types'], true) ? 'checked' : '' ?>
                                    <?= $bXslAvailable ? '' : 'disabled' ?>>
                                XSLT <?= $bXslAvailable ? '' : '<span style="font-size:0.8em">(PHP extension ext-xsl not loaded)</span>' ?>
                            </label>
                        </div>
                        <?php fieldError('template_types', $arrErrors) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php /* ---- Section 3: Search Engine ---- */ ?>
        <div class="pxm-admin-card">
            <div class="pxm-admin-card__header">3. Search Engine</div>
            <div class="pxm-admin-card__body">
                <div class="pxm-form-group">
                    <label>Engine</label>
                    <div class="pxm-field">
                        <div class="pxm-radio-group">
                            <label>
                                <input type="radio" name="search_type" value="MySql" id="searchMysql"
                                    <?= $arrValues['search_type'] === 'MySql' ? 'checked' : '' ?>>
                                MySQL FULLTEXT (default)
                            </label>
                            <label>
                                <input type="radio" name="search_type" value="ElasticSearch" id="searchEs"
                                    <?= $arrValues['search_type'] === 'ElasticSearch' ? 'checked' : '' ?>>
                                ElasticSearch
                            </label>
                        </div>
                    </div>
                </div>

                <div id="esFields" class="pxm-sub-fields" style="<?= $arrValues['search_type'] === 'ElasticSearch' ? '' : 'display:none' ?>">
                    <?php formField('es_host', 'ES host', $arrValues, $arrErrors, 'https://localhost:9200') ?>
                    <?php formField('es_index', 'ES index', $arrValues, $arrErrors, 'pxmboard_messages') ?>
                    <?php formField('es_api_key', 'API key', $arrValues, $arrErrors, '(optional)') ?>
                </div>
            </div>
        </div>

        <?php /* ---- Section 4: Session & Paths ---- */ ?>
        <div class="pxm-admin-card">
            <div class="pxm-admin-card__header">4. Session &amp; Paths</div>
            <div class="pxm-admin-card__body">
                <?php formField('session_name', 'Session name', $arrValues, $arrErrors, 'brdsid', 'text', 'Letters and digits only.') ?>
                <?php formField('basedir', 'Project path (BASEDIR)', $arrValues, $arrErrors, '', 'text', 'Absolute path to the project directory.') ?>
            </div>
        </div>

        <?php /* ---- Section 5: Admin User ---- */ ?>
        <div class="pxm-admin-card">
            <div class="pxm-admin-card__header">5. Admin User</div>
            <div class="pxm-admin-card__body">
                <?php formField('admin_user', 'Username', $arrValues, $arrErrors, 'Webmaster') ?>
                <?php formField('admin_pass', 'Password', $arrValues, $arrErrors, '', 'password', 'Only required when the SQL schema is executed below.') ?>
            </div>
        </div>

        <?php /* ---- Section 6: Database Schema ---- */ ?>
        <div class="pxm-admin-card" id="sqlCard">
            <div class="pxm-admin-card__header">6. Database Schema</div>
            <div class="pxm-admin-card__body">
                <div id="sqlMysqlBlock">
                    <div class="pxm-form-group">
                        <label>Install schema</label>
                        <div class="pxm-field">
                            <div class="pxm-radio-group">
                                <label>
                                    <input type="radio" name="install_sql" value="yes"
                                        <?= $arrValues['install_sql'] === 'yes' ? 'checked' : '' ?>>
                                    Yes – run <code>install/sql/pxmboard-mysql.sql</code> and create admin user
                                </label>
                                <label>
                                    <input type="radio" name="install_sql" value="no"
                                        <?= $arrValues['install_sql'] === 'no' ? 'checked' : '' ?>>
                                    No – install schema manually later
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="sqlPgsqlBlock" style="display:none">
                    <div class="pxm-alert pxm-alert--warning" style="margin:0">
                        No automatic installation script is available for PostgreSQL.
                        Please run the schema manually.
                    </div>
                </div>
            </div>
        </div>

        <div class="pxm-btn-row">
            <button type="submit" class="pxm-btn pxm-btn--primary">Start installation</button>
        </div>

    </form>

    <script>
    (function() {
        // ElasticSearch sub-fields
        document.querySelectorAll('input[name="search_type"]').forEach(function(r) {
            r.addEventListener('change', function() {
                document.getElementById('esFields').style.display =
                    this.value === 'ElasticSearch' ? '' : 'none';
            });
        });
        // PostgreSQL: hide SQL block
        document.querySelectorAll('input[name="db_type"]').forEach(function(r) {
            r.addEventListener('change', function() {
                var isPg = this.value === 'PostgreSql';
                document.getElementById('sqlMysqlBlock').style.display = isPg ? 'none' : '';
                document.getElementById('sqlPgsqlBlock').style.display = isPg ? '' : 'none';
                if (isPg) {
                    document.querySelector('input[name="install_sql"][value="no"]').checked = true;
                }
            });
        });
        // Trigger on load for PostgreSQL pre-selection
        var dbType = document.querySelector('input[name="db_type"]:checked');
        if (dbType && dbType.value === 'PostgreSql') {
            document.getElementById('sqlMysqlBlock').style.display = 'none';
            document.getElementById('sqlPgsqlBlock').style.display = '';
        }
    })();
    </script>
    <?php
});

// =========================================================================
// Helper functions
// =========================================================================

/**
 * Render the full HTML page frame with header, container, and content.
 */
function renderPage(string $sTitle, callable $fnContent): void
{
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($sTitle) ?> – PXMBoard Installer</title>
    <link rel="stylesheet" href="../css/pxm_admin.css">
</head>
<body>
<header class="pxm-admin-header">
    <img src="../images/pxmboard_logo.png" alt="PXMBoard">
</header>
<main class="pxm-admin-container">
    <h1><?= htmlspecialchars($sTitle) ?></h1>
    <?php $fnContent(); ?>
</main>
</body>
</html><?php
}

/**
 * Render a text/password form field row with label, input, hint and error.
 *
 * @param string $sName    Field name
 * @param string $sLabel   Display label
 * @param array<string, mixed>  $arrValues Current form values
 * @param array<string, string>  $arrErrors Current validation errors
 * @param string $sPlaceholder Placeholder text
 * @param string $sType    'text' or 'password'
 * @param string $sHint    Optional hint text
 */
function formField(
    string $sName,
    string $sLabel,
    array $arrValues,
    array $arrErrors,
    string $sPlaceholder = '',
    string $sType = 'text',
    string $sHint = ''
): void {
    $sValue = $sType === 'password' ? '' : htmlspecialchars($arrValues[$sName] ?? '');
    $bError = isset($arrErrors[$sName]);
    ?>
    <div class="pxm-form-group">
        <label for="field_<?= $sName ?>"><?= htmlspecialchars($sLabel) ?></label>
        <div class="pxm-field">
            <input type="<?= $sType ?>"
                   id="field_<?= $sName ?>"
                   name="<?= $sName ?>"
                   value="<?= $sValue ?>"
                   placeholder="<?= htmlspecialchars($sPlaceholder) ?>"
                   <?= $bError ? 'style="border-color:var(--pxm-danger)"' : '' ?>>
            <?php if ($sHint): ?>
                <span class="pxm-hint"><?= htmlspecialchars($sHint) ?></span>
            <?php endif; ?>
            <?php fieldError($sName, $arrErrors) ?>
        </div>
    </div>
    <?php
}

/**
 * Render inline error message for a field if present.
 *
 * @param string $sName   Field name
 * @param array<string, string>  $arrErrors Validation errors
 */
function fieldError(string $sName, array $arrErrors): void
{
    if (isset($arrErrors[$sName])) {
        echo '<span class="pxm-inline-error">' . htmlspecialchars($arrErrors[$sName]) . '</span>';
    }
}

/**
 * Generate the content for config/pxmboard-config.php.
 *
 * @param string $sDbType        Database type
 * @param string $sDbHost        Database host
 * @param string $sDbUser        Database user
 * @param string $sDbPass        Database password
 * @param string $sDbName        Database name
 * @param array<string>  $arrTemplates   Template types
 * @param array<string, mixed>  $arrSearch      Search engine config
 * @param string $sSessionName   Session name
 * @return string PHP file content
 */
function generateConfig(
    string $sDbType,
    string $sDbHost,
    string $sDbUser,
    string $sDbPass,
    string $sDbName,
    array $arrTemplates,
    array $arrSearch,
    string $sSessionName
): string {
    $sDate      = date('Y-m-d H:i:s');
    $sTemplates = implode("', '", array_map('addslashes', $arrTemplates));
    $sSearch    = var_export($arrSearch, true);

    return <<<PHP
<?php
/**
 * PXMBoard runtime configuration
 * Generated by installer on {$sDate}
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

return [
    'database' => [
        'type' => '{$sDbType}',
        'host' => '{$sDbHost}',
        'user' => '{$sDbUser}',
        'pass' => '{$sDbPass}',
        'name' => '{$sDbName}',
    ],
    'template_types' => ['{$sTemplates}'],
    'search_engine' => {$sSearch},
    'session_name' => '{$sSessionName}',
];
?>
PHP;
}

/**
 * Execute a MySQL SQL file against the given connection.
 * Returns an array of error strings (empty if all succeeded).
 *
 * @param mysqli $objDb    Active database connection
 * @param string $sFile    Path to SQL file
 * @return string[] Errors
 */
function executeSqlFile(mysqli $objDb, string $sFile): array
{
    $sContent = file_get_contents($sFile);
    if ($sContent === false) {
        return ['Could not read SQL file: ' . $sFile];
    }

    // Remove comments and split into statements
    $sContent = preg_replace('/^--.*$/m', '', $sContent);
    $sContent = preg_replace('/^#.*$/m', '', $sContent);
    $arrStatements = array_filter(
        array_map('trim', explode(';', $sContent)),
        fn ($s) => $s !== ''
    );

    $arrErrors = [];
    foreach ($arrStatements as $sStatement) {
        if (!$objDb->query($sStatement)) {
            // Ignore "table already exists" etc. to stay idempotent
            if ($objDb->errno !== 1050 && $objDb->errno !== 1060 && $objDb->errno !== 1061) {
                $arrErrors[] = '[' . $objDb->errno . '] ' . $objDb->error . ' — ' . mb_substr($sStatement, 0, 80);
            }
        }
    }
    return $arrErrors;
}

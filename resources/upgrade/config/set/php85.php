<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php85\Rector\ArrayDimFetch\ArrayFirstLastRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Class_\SleepToSerializeRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Class_\WakeupToUnserializeRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\ClassMethod\NullDebugInfoReturnRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\ArrayKeyExistsNullToEmptyStringRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\ChrArgModuloRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\OrdSingleByteRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\RemoveFinfoBufferContextArgRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\ShellExec\ShellExecFunctionCallOverBackticksRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Switch_\ColonAfterSwitchCaseRector;
use Flames\Code\Upgrade\Rules\Removing\Rector\FuncCall\RemoveFuncCallArgRector;
use Flames\Code\Upgrade\Rules\Removing\Rector\FuncCall\RemoveFuncCallRector;
use Flames\Code\Upgrade\Rules\Removing\ValueObject\RemoveFuncCallArg;
use Flames\Code\Upgrade\Rules\Renaming\Rector\Cast\RenameCastRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\ConstFetch\RenameConstantRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\MethodCall\RenameMethodRector;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\MethodCallRename;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameCast;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameClassAndConstFetch;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([ArrayFirstLastRector::class, RemoveFinfoBufferContextArgRector::class, NullDebugInfoReturnRector::class, ColonAfterSwitchCaseRector::class, ArrayKeyExistsNullToEmptyStringRector::class, ChrArgModuloRector::class, SleepToSerializeRector::class, OrdSingleByteRector::class, WakeupToUnserializeRector::class, ShellExecFunctionCallOverBackticksRector::class, AddOverrideAttributeToOverriddenPropertiesRector::class]);
    $rectorConfig->ruleWithConfiguration(RemoveFuncCallArgRector::class, [
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_key_length_parameter_of_openssl_pkey_derive
        new RemoveFuncCallArg('openssl_pkey_derive', 2),
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_the_exclude_disabled_parameter_of_get_defined_functions
        new RemoveFuncCallArg('get_defined_functions', 0),
    ]);
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_splobjectstoragecontains_splobjectstorageattach_and_splobjectstoragedetach
        new MethodCallRename('SplObjectStorage', 'contains', 'offsetExists'),
        new MethodCallRename('SplObjectStorage', 'attach', 'offsetSet'),
        new MethodCallRename('SplObjectStorage', 'detach', 'offsetUnset'),
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_driver_specific_pdo_constants_and_methods
        new MethodCallRename('PDO', 'pgsqlCopyFromArray', 'copyFromArray'),
        new MethodCallRename('PDO', 'pgsqlCopyFromFile', 'copyFromFile'),
        new MethodCallRename('PDO', 'pgsqlCopyToArray', 'copyToArray'),
        new MethodCallRename('PDO', 'pgsqlCopyToFile', 'copyToFile'),
        new MethodCallRename('PDO', 'pgsqlGetNotify', 'getNotify'),
        new MethodCallRename('PDO', 'pgsqlGetPid', 'getPid'),
        new MethodCallRename('PDO', 'pgsqlLOBCreate', 'lobCreate'),
        new MethodCallRename('PDO', 'pgsqlLOBOpen', 'lobOpen'),
        new MethodCallRename('PDO', 'pgsqlLOBUnlink', 'lobUnlink'),
        new MethodCallRename('PDO', 'sqliteCreateAggregate', 'createAggregate'),
        new MethodCallRename('PDO', 'sqliteCreateCollation', 'createCollation'),
        new MethodCallRename('PDO', 'sqliteCreateFunction', 'createFunction'),
    ]);
    $rectorConfig->ruleWithConfiguration(RenameFunctionRector::class, [
        // https://wiki.php.net/rfc/deprecations_php_8_5#formally_deprecate_socket_set_timeout
        'socket_set_timeout' => 'stream_set_timeout',
        // https://wiki.php.net/rfc/deprecations_php_8_5#formally_deprecate_mysqli_execute
        'mysqli_execute' => 'mysqli_stmt_execute',
    ]);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_driver_specific_pdo_constants_and_methods
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_CONNECTION_TIMEOUT', \Pdo\Dblib::class, 'ATTR_CONNECTION_TIMEOUT'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_QUERY_TIMEOUT', \Pdo\Dblib::class, 'ATTR_QUERY_TIMEOUT'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER', \Pdo\Dblib::class, 'ATTR_STRINGIFY_UNIQUEIDENTIFIER'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_VERSION', \Pdo\Dblib::class, 'ATTR_VERSION'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_TDS_VERSION', \Pdo\Dblib::class, 'ATTR_TDS_VERSION'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_SKIP_EMPTY_ROWSETS', \Pdo\Dblib::class, 'ATTR_SKIP_EMPTY_ROWSETS'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_DATETIME_CONVERT', \Pdo\Dblib::class, 'ATTR_DATETIME_CONVERT'), new RenameClassAndConstFetch('PDO', 'FB_ATTR_DATE_FORMAT', \Pdo\Firebird::class, 'ATTR_DATE_FORMAT'), new RenameClassAndConstFetch('PDO', 'FB_ATTR_TIME_FORMAT', \Pdo\Firebird::class, 'ATTR_TIME_FORMAT'), new RenameClassAndConstFetch('PDO', 'FB_ATTR_TIMESTAMP_FORMAT', \Pdo\Firebird::class, 'ATTR_TIMESTAMP_FORMAT'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_USE_BUFFERED_QUERY', \Pdo\Mysql::class, 'ATTR_USE_BUFFERED_QUERY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_LOCAL_INFILE', \Pdo\Mysql::class, 'ATTR_LOCAL_INFILE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_LOCAL_INFILE_DIRECTORY', \Pdo\Mysql::class, 'ATTR_LOCAL_INFILE_DIRECTORY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_INIT_COMMAND', \Pdo\Mysql::class, 'ATTR_INIT_COMMAND'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_MAX_BUFFER_SIZE', \Pdo\Mysql::class, 'ATTR_MAX_BUFFER_SIZE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_READ_DEFAULT_FILE', \Pdo\Mysql::class, 'ATTR_READ_DEFAULT_FILE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_READ_DEFAULT_GROUP', \Pdo\Mysql::class, 'ATTR_READ_DEFAULT_GROUP'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_COMPRESS', \Pdo\Mysql::class, 'ATTR_COMPRESS'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_DIRECT_QUERY', \Pdo\Mysql::class, 'ATTR_DIRECT_QUERY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_FOUND_ROWS', \Pdo\Mysql::class, 'ATTR_FOUND_ROWS'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_IGNORE_SPACE', \Pdo\Mysql::class, 'ATTR_IGNORE_SPACE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_KEY', \Pdo\Mysql::class, 'ATTR_SSL_KEY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CERT', \Pdo\Mysql::class, 'ATTR_SSL_CERT'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CA', \Pdo\Mysql::class, 'ATTR_SSL_CA'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CAPATH', \Pdo\Mysql::class, 'ATTR_SSL_CAPATH'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CIPHER', \Pdo\Mysql::class, 'ATTR_SSL_CIPHER'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', \Pdo\Mysql::class, 'ATTR_SSL_VERIFY_SERVER_CERT'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SERVER_PUBLIC_KEY', \Pdo\Mysql::class, 'ATTR_SERVER_PUBLIC_KEY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_MULTI_STATEMENTS', \Pdo\Mysql::class, 'ATTR_MULTI_STATEMENTS'), new RenameClassAndConstFetch('PDO', 'ODBC_ATTR_USE_CURSOR_LIBRARY', \Pdo\Odbc::class, 'ATTR_USE_CURSOR_LIBRARY'), new RenameClassAndConstFetch('PDO', 'ODBC_ATTR_ASSUME_UTF8', \Pdo\Odbc::class, 'ATTR_ASSUME_UTF8'), new RenameClassAndConstFetch('PDO', 'ODBC_SQL_USE_IF_NEEDED', \Pdo\Odbc::class, 'SQL_USE_IF_NEEDED'), new RenameClassAndConstFetch('PDO', 'ODBC_SQL_USE_DRIVER', \Pdo\Odbc::class, 'SQL_USE_DRIVER'), new RenameClassAndConstFetch('PDO', 'ODBC_SQL_USE_ODBC', \Pdo\Odbc::class, 'SQL_USE_ODBC'), new RenameClassAndConstFetch('PDO', 'PGSQL_ATTR_DISABLE_PREPARES', \Pdo\Pgsql::class, 'ATTR_DISABLE_PREPARES'), new RenameClassAndConstFetch('PDO', 'SQLITE_ATTR_EXTENDED_RESULT_CODES', \Pdo\Sqlite::class, 'ATTR_EXTENDED_RESULT_CODES'), new RenameClassAndConstFetch('PDO', 'SQLITE_ATTR_OPEN_FLAGS', \Pdo\Sqlite::class, 'OPEN_FLAGS'), new RenameClassAndConstFetch('PDO', 'SQLITE_ATTR_READONLY_STATEMENT', \Pdo\Sqlite::class, 'ATTR_READONLY_STATEMENT'), new RenameClassAndConstFetch('PDO', 'SQLITE_DETERMINISTIC', \Pdo\Sqlite::class, 'DETERMINISTIC'), new RenameClassAndConstFetch('PDO', 'SQLITE_OPEN_READONLY', \Pdo\Sqlite::class, 'OPEN_READONLY'), new RenameClassAndConstFetch('PDO', 'SQLITE_OPEN_READWRITE', \Pdo\Sqlite::class, 'OPEN_READWRITE'), new RenameClassAndConstFetch('PDO', 'SQLITE_OPEN_CREATE', \Pdo\Sqlite::class, 'OPEN_CREATE')]);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_non-standard_cast_names
    $rectorConfig->ruleWithConfiguration(RenameCastRector::class, [new RenameCast(Int_::class, Int_::KIND_INTEGER, Int_::KIND_INT), new RenameCast(Bool_::class, Bool_::KIND_BOOLEAN, Bool_::KIND_BOOL), new RenameCast(Double::class, Double::KIND_DOUBLE, Double::KIND_FLOAT), new RenameCast(String_::class, String_::KIND_BINARY, String_::KIND_STRING)]);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_no-op_functions_from_the_resource_to_object_conversion
    // these function have no effect when use
    $rectorConfig->ruleWithConfiguration(RemoveFuncCallRector::class, ['curl_close', 'curl_share_close', 'finfo_close', 'imagedestroy', 'xml_parser_free']);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_filter_default_constant
    $rectorConfig->ruleWithConfiguration(RenameConstantRector::class, ['FILTER_DEFAULT' => 'FILTER_UNSAFE_RAW']);
};

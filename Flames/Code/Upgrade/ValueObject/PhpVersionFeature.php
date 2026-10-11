<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject;

/**
 * @api
 */
final class PhpVersionFeature
{
    public const int PROPERTY_MODIFIER = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_52;
    public const int CONTINUE_TO_BREAK = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_52;
    public const int NO_REFERENCE_IN_NEW = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_53;
    public const int SERVER_VAR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_53;
    public const int DIR_CONSTANT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_53;
    public const int ELVIS_OPERATOR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_53;
    public const int ANONYMOUS_FUNCTION_PARAM_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_53;
    public const int NO_ZERO_BREAK = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_54;
    public const int NO_REFERENCE_IN_ARG = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_54;
    public const int SHORT_ARRAY = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_54;
    public const int DATE_TIME_INTERFACE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_55;
    /**
     * @see https://wiki.php.net/rfc/class_name_scalars
     */
    public const int CLASSNAME_CONSTANT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_55;
    public const int PREG_REPLACE_CALLBACK_E_MODIFIER = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_55;
    public const int EXP_OPERATOR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_56;
    public const int REQUIRE_DEFAULT_VALUE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_56;
    public const int SCALAR_TYPES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int HAS_RETURN_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NULL_COALESCE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int LIST_SWAP_ORDER = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int SPACESHIP = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int DIRNAME_LEVELS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int CSPRNG_FUNCTIONS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int THROWABLE_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_LIST_SPLIT_STRING = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_BREAK_OUTSIDE_LOOP = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_PHP4_CONSTRUCTOR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_CALL_USER_METHOD = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_EREG_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int VARIABLE_ON_FUNC_CALL = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_MKTIME_WITHOUT_ARG = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_EMPTY_LIST = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    /**
     * @see https://php.watch/versions/8.0/non-static-static-call-fatal-error
     * Deprecated since PHP 7.0
     */
    public const int STATIC_CALL_ON_NON_STATIC = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int INSTANCE_CALL = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int NO_MULTIPLE_DEFAULT_SWITCH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int WRAP_VARIABLE_VARIABLE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int ANONYMOUS_FUNCTION_RETURN_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_70;
    public const int ITERABLE_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int VOID_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int CONSTANT_VISIBILITY = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int ARRAY_DESTRUCT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int MULTI_EXCEPTION_CATCH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int NO_ASSIGN_ARRAY_TO_STRING = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int BINARY_OP_NUMBER_STRING = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int NO_EXTRA_PARAMETERS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int RESERVED_OBJECT_KEYWORD = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int DEPRECATE_EACH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int OBJECT_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int NO_EACH_OUTSIDE_LOOP = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int DEPRECATE_CREATE_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int NO_NULL_ON_GET_CLASS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int INVERTED_BOOL_IS_OBJECT_INCOMPLETE_CLASS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int RESULT_ARG_IN_PARSE_STR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int STRING_IN_FIRST_DEFINE_ARG = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int STRING_IN_ASSERT_ARG = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int NO_UNSET_CAST = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int IS_COUNTABLE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int ARRAY_KEY_FIRST_LAST = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    /**
     * @see https://php.watch/versions/8.5/array_first-array_last
     */
    public const int ARRAY_FIRST_LAST = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    public const int JSON_EXCEPTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int SETCOOKIE_ACCEPT_ARRAY_OPTIONS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int DEPRECATE_INSENSITIVE_CONSTANT_NAME = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int ESCAPE_DASH_IN_REGEX = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int DEPRECATE_INSENSITIVE_CONSTANT_DEFINE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int DEPRECATE_INT_IN_STR_NEEDLES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int SENSITIVE_HERE_NOW_DOC = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_73;
    public const int ARROW_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int LITERAL_SEPARATOR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int NULL_COALESCE_ASSIGN = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int TYPED_PROPERTIES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    /**
     * @see https://wiki.php.net/rfc/covariant-returns-and-contravariant-parameters
     */
    public const int COVARIANT_RETURN = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int ARRAY_SPREAD = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int DEPRECATE_CURLY_BRACKET_ARRAY_STRING = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int DEPRECATE_REAL = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int DEPRECATE_MONEY_FORMAT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int ARRAY_KEY_EXISTS_TO_PROPERTY_EXISTS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int FILTER_VAR_TO_ADD_SLASHES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int CHANGE_MB_STRPOS_ARG_POSITION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int RESERVED_FN_FUNCTION_NAME = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int REFLECTION_TYPE_GETNAME = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int EXPORT_TO_REFLECTION_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int DEPRECATE_NESTED_TERNARY = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int DEPRECATE_RESTORE_INCLUDE_PATH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int DEPRECATE_HEBREVC = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_74;
    public const int UNION_TYPES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int CLASS_ON_OBJECT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int STATIC_RETURN_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int NO_FINAL_PRIVATE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int DEPRECATE_REQUIRED_PARAMETER_AFTER_OPTIONAL = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int STATIC_VISIBILITY_SET_STATE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int NULLSAFE_OPERATOR = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int IS_ITERABLE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int NULLABLE_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    public const int PARENT_VISIBILITY_OVERRIDE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_72;
    public const int COUNT_ON_NULL = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_71;
    /**
     * @see https://wiki.php.net/rfc/constructor_promotion
     */
    public const int PROPERTY_PROMOTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://wiki.php.net/rfc/attributes_v2
     */
    public const int ATTRIBUTES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int STRINGABLE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int PHP_TOKEN = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int STR_ENDS_WITH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int STR_STARTS_WITH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int STR_CONTAINS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int GET_DEBUG_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://wiki.php.net/rfc/noreturn_type
     */
    public const int NEVER_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/variadics
     */
    public const int VARIADIC_PARAM = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_56;
    /**
     * @see https://wiki.php.net/rfc/readonly_and_immutable_properties
     */
    public const int READONLY_PROPERTY = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/final_class_const
     */
    public const int FINAL_CLASS_CONSTANTS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/enumerations
     */
    public const int ENUM = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/match_expression_v2
     */
    public const int MATCH_EXPRESSION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://wiki.php.net/rfc/non-capturing_catches
     */
    public const int NON_CAPTURING_CATCH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://www.php.net/manual/en/migration80.incompatible.php#migration80.incompatible.resource2object
     */
    public const int PHP8_RESOURCE_TO_OBJECT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://wiki.php.net/rfc/lsp_errors
     */
    public const int FATAL_ERROR_ON_INCOMPATIBLE_METHOD_SIGNATURE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://www.php.net/manual/en/migration81.incompatible.php#migration81.incompatible.resource2object
     */
    public const int PHP81_RESOURCE_TO_OBJECT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/new_in_initializers
     */
    public const int NEW_INITIALIZERS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/pure-intersection-types
     */
    public const int INTERSECTION_TYPES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://php.watch/versions/8.2/dnf-types
     */
    public const int UNION_INTERSECTION_TYPES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://wiki.php.net/rfc/array_unpacking_string_keys
     */
    public const int ARRAY_SPREAD_STRING_KEYS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/internal_method_return_types
     */
    public const int RETURN_TYPE_WILL_CHANGE_ATTRIBUTE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/first_class_callable_syntax
     */
    public const int FIRST_CLASS_CALLABLE_SYNTAX = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/deprecate_dynamic_properties
     */
    public const int DEPRECATE_DYNAMIC_PROPERTIES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://wiki.php.net/rfc/readonly_classes
     */
    public const int READONLY_CLASS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://www.php.net/manual/en/migration83.new-features.php#migration83.new-features.core.readonly-modifier-improvements
     */
    public const int READONLY_ANONYMOUS_CLASS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://wiki.php.net/rfc/json_validate
     */
    public const int JSON_VALIDATE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://wiki.php.net/rfc/mixed_type_v2
     */
    public const int MIXED_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    /**
     * @see https://3v4l.org/OWtO5
     */
    public const int ARRAY_ON_ARRAY_MERGE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
    public const int DEPRECATE_NULL_ARG_IN_STRING_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_81;
    /**
     * @see https://wiki.php.net/rfc/remove_utf8_decode_and_utf8_encode
     */
    public const int DEPRECATE_UTF8_DECODE_ENCODE_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://www.php.net/manual/en/filesystemiterator.construct
     */
    public const int FILESYSTEM_ITERATOR_SKIP_DOTS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://wiki.php.net/rfc/null-false-standalone-types
     * @see https://wiki.php.net/rfc/true-type
     */
    public const int NULL_FALSE_TRUE_STANDALONE_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://wiki.php.net/rfc/redact_parameters_in_back_traces
     */
    public const int SENSITIVE_PARAMETER_ATTRIBUTE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://wiki.php.net/rfc/deprecate_dollar_brace_string_interpolation
     */
    public const int DEPRECATE_VARIABLE_IN_STRING_INTERPOLATION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_82;
    /**
     * @see https://wiki.php.net/rfc/marking_overriden_methods
     */
    public const int OVERRIDE_ATTRIBUTE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://wiki.php.net/rfc/typed_class_constants
     */
    public const int TYPED_CLASS_CONSTANTS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://wiki.php.net/rfc/dynamic_class_constant_fetch
     */
    public const int DYNAMIC_CLASS_CONST_FETCH = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://wiki.php.net/rfc/deprecate-implicitly-nullable-types
     */
    public const int DEPRECATE_IMPLICIT_NULLABLE_PARAM_TYPE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://wiki.php.net/rfc/new_without_parentheses
     */
    public const int NEW_METHOD_CALL_WITHOUT_PARENTHESES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://wiki.php.net/rfc/correctly_name_the_rounding_mode_and_make_it_an_enum
     */
    public const int ROUNDING_MODES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://php.watch/versions/8.4/csv-functions-escape-parameter
     */
    public const int REQUIRED_ESCAPE_PARAMETER = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://www.php.net/manual/en/migration83.deprecated.php#migration83.deprecated.ldap
     */
    public const int DEPRECATE_HOST_PORT_SEPARATE_ARGS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://www.php.net/manual/en/migration83.deprecated.php#migration83.deprecated.core.get-class
     * @see https://php.watch/versions/8.3/get_class-get_parent_class-parameterless-deprecated
     */
    public const int DEPRECATE_GET_CLASS_WITHOUT_ARGS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_83;
    /**
     * @see https://wiki.php.net/rfc/deprecated_attribute
     */
    public const int DEPRECATED_ATTRIBUTE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
     */
    public const int ARRAY_FIND = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
     */
    public const int ARRAY_FIND_KEY = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
     */
    public const int ARRAY_ALL = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://php.watch/versions/8.4/array_find-array_find_key-array_any-array_all
     */
    public const int ARRAY_ANY = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_the_context_parameter_for_finfo_buffer
     */
    public const int DEPRECATE_FINFO_BUFFER_CONTEXT = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_debuginfo_returning_null
     */
    public const int DEPRECATED_NULL_DEBUG_INFO_RETURN = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_semicolon_after_case_in_switch_statement
     */
    public const int COLON_AFTER_SWITCH_CASE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_using_values_null_as_an_array_offset_and_when_calling_array_key_exists
     */
    public const int DEPRECATE_NULL_ARG_IN_ARRAY_KEY_EXISTS_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#eprecate_passing_integers_outside_the_interval_0_255_to_chr
     */
    public const int DEPRECATE_OUTSIDE_INTERVEL_VAL_IN_CHR_FUNCTION = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_the_sleep_and_wakeup_magic_methods
     */
    public const int DEPRECATED_METHOD_SLEEP = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_the_sleep_and_wakeup_magic_methods
     */
    public const int DEPRECATED_METHOD_WAKEUP = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_passing_string_which_are_not_one_byte_long_to_ord
     */
    public const int DEPRECATE_ORD_WITH_MULTIBYTE_STRING = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/property-hooks
     */
    public const int PROPERTY_HOOKS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_84;
    /**
     * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_backticks_as_an_alias_for_shell_exec
     */
    public const int DEPRECATE_BACKTICKS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/pipe-operator-v3
     */
    public const int PIPE_OPERATOER = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/override_properties
     */
    public const int OVERRIDE_ATTRIBUTE_ON_PROPERTIES = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_85;
    /**
     * @see https://wiki.php.net/rfc/clamp_v2
     */
    public const int CLAMP = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_86;
    /**
     * @see https://wiki.php.net/rfc/readonly_property_defaults
     */
    public const int READONLY_PROPERTY_DEFAULT_VALUE = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_86;
    /**
     * @see https://php.watch/versions/8.0/named-parameters
     */
    public const int NAMED_ARGUMENTS = \Flames\Code\Upgrade\ValueObject\PhpVersion::PHP_80;
}

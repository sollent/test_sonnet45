<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRules([
        // PSR-12 as base
        '@PSR12' => true,
        '@Symfony' => true,

        // PHP 8.3 modern features
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,

        // Array syntax
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'no_trailing_comma_in_singleline' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],

        // Binary operators
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],
        'unary_operator_spaces' => true,

        // Blank lines
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'try', 'throw', 'declare', 'if', 'switch', 'for', 'foreach', 'while', 'do'],
        ],
        'no_extra_blank_lines' => [
            'tokens' => ['extra', 'throw', 'use'],
        ],

        // Casing
        'constant_case' => ['case' => 'lower'],
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'native_type_declaration_casing' => true,

        // Cast
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,
        'no_short_bool_cast' => true,
        'short_scalar_cast' => true,

        // Class notation
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'class_definition' => [
            'single_line' => true,
            'space_before_parenthesis' => false,
        ],
        'final_internal_class' => false,
        'no_blank_lines_after_class_opening' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'protected_to_private' => true,
        'self_accessor' => true,
        'self_static_accessor' => true,
        'single_class_element_per_statement' => true,
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],

        // Comments
        'comment_to_phpdoc' => true,
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],

        // Control structures
        'control_structure_continuation_position' => ['position' => 'same_line'],
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_control_parentheses' => [
            'statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield'],
        ],
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'switch_continue_to_break' => true,
        'yoda_style' => false,

        // Function notation
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'type_declaration_spaces' => true,
        'lambda_not_used_import' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_spaces_after_function_name' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_line_throw' => false,
        'void_return' => false,

        // Import
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,

        // Language construct
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'declare_equal_normalize' => ['space' => 'none'],
        'dir_constant' => true,
        'explicit_indirect_variable' => true,
        'function_to_constant' => true,
        'is_null' => true,
        'no_unset_on_property' => true,
        'single_space_around_construct' => true,

        // List notation
        'list_syntax' => ['syntax' => 'short'],

        // Namespace
        'blank_line_after_namespace' => true,
        'no_leading_namespace_whitespace' => true,

        // Operator
        'increment_style' => ['style' => 'post'],
        'logical_operators' => true,
        'new_with_parentheses' => true,
        'not_operator_with_successor_space' => false,
        'object_operator_without_whitespace' => true,
        'operator_linebreak' => ['only_booleans' => true],
        'standardize_increment' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'ternary_to_elvis_operator' => true,
        'ternary_to_null_coalescing' => true,

        // PHP tag
        'blank_line_after_opening_tag' => true,
        'echo_tag_syntax' => ['format' => 'short'],
        'full_opening_tag' => true,
        'linebreak_after_opening_tag' => true,
        'no_closing_tag' => true,

        // PHPDoc
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
        'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'package']],
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => false,
        ],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => false,
        'phpdoc_tag_casing' => true,
        'phpdoc_tag_type' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,

        // Return notation
        'no_useless_return' => true,
        'return_assignment' => true,
        'simplified_null_return' => false,

        // Semicolon
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],

        // String notation
        'string_implicit_backslashes' => true,
        'explicit_string_variable' => true,
        'heredoc_to_nowdoc' => true,
        'no_binary_string' => true,
        'simple_to_complex_string_variable' => true,
        'single_quote' => true,

        // Whitespace
        'compact_nullable_type_declaration' => true,
        'heredoc_indentation' => ['indentation' => 'start_plus_one'],
        'method_chaining_indentation' => true,
        'no_spaces_around_offset' => true,
        'no_whitespace_in_blank_line' => true,
        'types_spaces' => ['space' => 'none'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);

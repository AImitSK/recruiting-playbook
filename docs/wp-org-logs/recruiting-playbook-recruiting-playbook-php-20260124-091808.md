# Plugin Check Report

**Plugin:** Recruiting Playbook
**Generated at:** 2026-01-24 09:18:08


## `src/Services/EmailService.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 155 | 35 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;rp_email_content&quot;. |  |
| 179 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $app_table used in $wpdb-&gt;get_row($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT a.*, c.salutation, c.first_name, c.last_name, c.email, c.phone\r\n\t\t\t\tFROM {$app_table} a\r\n\t\t\t\tLEFT JOIN {$cand_table} c ON a.candidate_id = c.id\r\n\t\t\t\tWHERE a.id = %d&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t))\n$app_table assigned unsafely at line 175:\n $app_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$cand_table assigned unsafely at line 176:\n $cand_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$row assigned unsafely at line 179:\n $row = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT a.*, c.salutation, c.first_name, c.last_name, c.email, c.phone\r\n\t\t\t\tFROM {$app_table} a\r\n\t\t\t\tLEFT JOIN {$cand_table} c ON a.candidate_id = c.id\r\n\t\t\t\tWHERE a.id = %d&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t),\r\n\t\t\tARRAY_A\r\n\t\t)\n$application_id used without escaping. |  |
| 182 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$app_table} at \t\t\t\tFROM {$app_table} a\r\n |  |
| 183 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$cand_table} at \t\t\t\tLEFT JOIN {$cand_table} c ON a.candidate_id = c.id\r\n |  |
| 288 | 32 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 288 | 36 | ERROR | WordPress.WP.I18n.UnorderedPlaceholdersText | Multiple placeholders in translatable strings should be ordered. Expected "%1$s, %2$s", but got "%s, %s" in 'Guten Tag %s %s'. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#variables) |
| 289 | 32 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 313 | 32 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 313 | 36 | ERROR | WordPress.WP.I18n.UnorderedPlaceholdersText | Multiple placeholders in translatable strings should be ordered. Expected "%1$s, %2$s", but got "%s, %s" in 'Guten Tag %s %s'. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#variables) |
| 314 | 32 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 381 | 4 | WARNING | WordPress.PHP.DevelopmentFunctions.error_log_error_log | error_log() found. Debug code should not normally be used in production. |  |
| 392 | 20 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;rp_email_sent&quot;. |  |

## `src/Admin/Menu.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 152 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 152 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 158 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 233 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 233 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 240 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 240 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 271 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 395 | 39 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_var(&quot;SELECT COUNT(*) FROM {$applications_table}&quot;)\n$applications_table assigned unsafely at line 384:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$table_exists assigned unsafely at line 386:\n $table_exists = $wpdb-&gt;get_var(\r\n\t\t\t$wpdb-&gt;prepare( &#039;SHOW TABLES LIKE %s&#039;, $applications_table )\r\n\t\t) === $applications_table |  |
| 397 | 37 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_var($wpdb-&gt;prepare( &quot;SELECT COUNT(*) FROM {$applications_table} WHERE status = %s&quot;, &#039;new&#039; ))\n$applications_table assigned unsafely at line 384:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$table_exists assigned unsafely at line 386:\n $table_exists = $wpdb-&gt;get_var(\r\n\t\t\t$wpdb-&gt;prepare( &#039;SHOW TABLES LIKE %s&#039;, $applications_table )\r\n\t\t) === $applications_table |  |
| 398 | 21 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT COUNT(*) FROM {$applications_table} WHERE status = %s&quot; |  |
| 529 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 530 | 99 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 532 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 533 | 74 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 552 | 20 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results(&quot;SELECT status, COUNT(*) as count FROM {$table} GROUP BY status&quot;)\n$table assigned unsafely at line 548:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$counts assigned unsafely at line 552:\n $counts = $wpdb-&gt;get_results(\r\n\t\t\t&quot;SELECT status, COUNT(*) as count FROM {$table} GROUP BY status&quot;,\r\n\t\t\tOBJECT_K\r\n\t\t) |  |
| 553 | 4 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT status, COUNT(*) as count FROM {$table} GROUP BY status&quot; |  |
| 591 | 30 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$links'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |

## `src/Admin/Settings.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 421 | 35 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'self'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 421 | 61 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$id'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 422 | 35 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$id'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 423 | 35 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'url_to_postid'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 424 | 35 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '__'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |

## `src/Admin/Pages/ApplicationList.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 109 | 32 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_var(&quot;SELECT COUNT(a.id)\r\n\t\t\t\t FROM {$applications_table} a\r\n\t\t\t\t LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id&quot;)\n$applications_table assigned unsafely at line 102:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidates_table assigned unsafely at line 103:\n $candidates_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$where_data[&#039;values&#039;] used without escaping. |  |
| 109 | 34 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 109 | 34 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 111 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at \t\t\t\t FROM {$applications_table} a\r\n |  |
| 112 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$candidates_table} at \t\t\t\t LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id&quot; |  |
| 117 | 32 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_var($wpdb-&gt;prepare(\r\n\t\t\t\t\t&quot;SELECT COUNT(a.id)\r\n\t\t\t\t\t FROM {$applications_table} a\r\n\t\t\t\t\t LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n\t\t\t\t\t WHERE {$where_data[&#039;clause&#039;]}&quot;,\r\n\t\t\t\t\t...$where_data[&#039;values&#039;]\r\n\t\t\t\t))\n$applications_table assigned unsafely at line 102:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidates_table assigned unsafely at line 103:\n $candidates_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$where_data[&#039;values&#039;] used without escaping. |  |
| 117 | 34 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 117 | 34 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 120 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at \t\t\t\t\t FROM {$applications_table} a\r\n |  |
| 121 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$candidates_table} at \t\t\t\t\t LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n |  |
| 122 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$where_data[&#039;clause&#039;]} at \t\t\t\t\t WHERE {$where_data[&#039;clause&#039;]}&quot; |  |
| 122 | 37 | WARNING | WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare | Replacement variables found, but no valid placeholders found in the query. |  |
| 133 | 24 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 133 | 24 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 133 | 25 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT a.*, c.first_name, c.last_name, c.email, c.phone\r\n\t\t\t\t FROM {$applications_table} a\r\n\t\t\t\t LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n\t\t\t\t WHERE {$where_sql}\r\n\t\t\t\t ORDER BY {$orderby} {$order}\r\n\t\t\t\t LIMIT %d OFFSET %d&quot;,\r\n\t\t\t\t...$query_values\r\n\t\t\t))\n$applications_table assigned unsafely at line 102:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidates_table assigned unsafely at line 103:\n $candidates_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$where_data[&#039;values&#039;] used without escaping. |  |
| 134 | 4 | WARNING | WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber | Incorrect number of replacements passed to $wpdb-&gt;prepare(). Found 1 replacement parameters, expected 2. |  |
| 136 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at \t\t\t\t FROM {$applications_table} a\r\n |  |
| 137 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$candidates_table} at \t\t\t\t LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n |  |
| 138 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$where_sql} at \t\t\t\t WHERE {$where_sql}\r\n |  |
| 139 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$orderby} at \t\t\t\t ORDER BY {$orderby} {$order}\r\n |  |
| 139 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$order} at \t\t\t\t ORDER BY {$orderby} {$order}\r\n |  |
| 165 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 165 | 89 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 167 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 175 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 177 | 28 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 181 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 183 | 76 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 191 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 193 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 197 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 199 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 218 | 21 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 218 | 75 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 229 | 19 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 229 | 83 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 327 | 25 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $documents_table used in $wpdb-&gt;get_var($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT COUNT(*) FROM {$documents_table} WHERE application_id = %d&quot;,\r\n\t\t\t\tabsint( $item[&#039;id&#039;] )\r\n\t\t\t)) |  |
| 329 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$documents_table} at &quot;SELECT COUNT(*) FROM {$documents_table} WHERE application_id = %d&quot; |  |
| 424 | 28 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 424 | 81 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 454 | 25 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 454 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 460 | 17 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$job'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 468 | 23 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 468 | 79 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 469 | 23 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 469 | 77 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |

## `src/Frontend/Shortcodes.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 433 | 21 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$query'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |

## `recruiting-playbook.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 27 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_VERSION&quot;. |  |
| 28 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_PLUGIN_FILE&quot;. |  |
| 29 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_PLUGIN_DIR&quot;. |  |
| 30 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_PLUGIN_URL&quot;. |  |
| 31 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_PLUGIN_BASENAME&quot;. |  |
| 34 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_MIN_PHP_VERSION&quot;. |  |
| 35 | 8 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound | Global constants defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;RP_MIN_WP_VERSION&quot;. |  |
| 52 | 17 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'RP_MIN_PHP_VERSION'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 68 | 17 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'RP_MIN_WP_VERSION'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 69 | 17 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$wp_version'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |

## `templates/single-job_listing.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 42 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_min&quot;. |  |
| 43 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_max&quot;. |  |
| 44 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_currency&quot;. |  |
| 45 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_period&quot;. |  |
| 46 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$hide_salary&quot;. |  |
| 47 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$deadline&quot;. |  |
| 48 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$contact_person&quot;. |  |
| 49 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$contact_email&quot;. |  |
| 50 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$contact_phone&quot;. |  |
| 51 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$remote_option&quot;. |  |
| 52 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$start_date&quot;. |  |
| 55 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$locations&quot;. |  |
| 56 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$types&quot;. |  |
| 57 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$categories&quot;. |  |
| 60 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_display&quot;. |  |
| 62 | 9 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$period_labels&quot;. |  |
| 67 | 9 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$period_label&quot;. |  |
| 70 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_display&quot;. |  |
| 72 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_display&quot;. |  |
| 74 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$salary_display&quot;. |  |
| 79 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$remote_labels&quot;. |  |
| 86 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$tracking_category&quot;. |  |
| 87 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$tracking_location&quot;. |  |
| 88 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$tracking_type&quot;. |  |
| 290 | 45 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'RecruitingPlaybook'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 291 | 45 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'RecruitingPlaybook'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 490 | 49 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$privacy_url&quot;. |  |
| 492 | 53 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$privacy_link&quot;. |  |
| 498 | 53 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$privacy_link&quot;. |  |

## `src/Frontend/JobSchema.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 83 | 30 | ERROR | WordPress.DateTime.RestrictedFunctions.date_date | date() is affected by runtime timezone changes which can cause date/time to be incorrectly displayed. Use gmdate() instead. |  |
| 123 | 35 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;the_content&quot;. |  |

## `src/Services/DocumentService.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 360 | 4 | ERROR | WordPress.WP.AlternativeFunctions.file_system_operations_chmod | File operations should use WP_Filesystem methods instead of direct PHP filesystem calls. Found: chmod(). |  |
| 479 | 26 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_var($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT candidate_id FROM {$applications_table} WHERE id = %d&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t))\n$applications_table assigned unsafely at line 477:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidate_id assigned unsafely at line 479:\n $candidate_id = $wpdb-&gt;get_var(\r\n\t\t\t$wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT candidate_id FROM {$applications_table} WHERE id = %d&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t)\r\n\t\t)\n$application_id used without escaping. |  |
| 481 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT candidate_id FROM {$applications_table} WHERE id = %d&quot; |  |
| 489 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 527 | 21 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id, document_type as type, file_name as filename, original_name, file_type as mime_type, file_size as size, created_at\r\n\t\t\t\tFROM {$table}\r\n\t\t\t\tWHERE application_id = %d AND is_deleted = 0\r\n\t\t\t\tORDER BY created_at ASC&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t))\n$table assigned unsafely at line 524:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$results assigned unsafely at line 527:\n $results = $wpdb-&gt;get_results(\r\n\t\t\t$wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id, document_type as type, file_name as filename, original_name, file_type as mime_type, file_size as size, created_at\r\n\t\t\t\tFROM {$table}\r\n\t\t\t\tWHERE application_id = %d AND is_deleted = 0\r\n\t\t\t\tORDER BY created_at ASC&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t),\r\n\t\t\tARRAY_A\r\n\t\t)\n$application_id used without escaping. |  |
| 530 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at \t\t\t\tFROM {$table}\r\n |  |
| 553 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $document_id ))\n$table assigned unsafely at line 550:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$row assigned unsafely at line 553:\n $row = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $document_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$document_id used without escaping. |  |
| 554 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} WHERE id = %d&quot; |  |

## `readme.txt`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | readme_short_description_non_official_language | The readme short description contains unofficial language. It must be written in standard English. | [Docs](https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/) |
| 0 | 0 | ERROR | readme_description_non_official_language | The readme description contains unofficial language. It must be written in standard English. | [Docs](https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/) |
| 0 | 0 | WARNING | readme_parser_warnings_trimmed_short_description | The "Short Description" section is too long and was truncated. A maximum of 150 characters is supported. |  |

## `src/Admin/Pages/ApplicationDetail.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 25 | 16 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 25 | 40 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 290 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $id ))\n$table assigned unsafely at line 287:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$id used without escaping. |  |
| 291 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} WHERE id = %d&quot; |  |
| 308 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $id ))\n$table assigned unsafely at line 305:\n $table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$id used without escaping. |  |
| 309 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} WHERE id = %d&quot; |  |
| 326 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id, original_name, document_type as type, file_size as size, file_path, created_at\r\n\t\t\t\t FROM {$table}\r\n\t\t\t\t WHERE application_id = %d AND is_deleted = 0\r\n\t\t\t\t ORDER BY created_at ASC&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t))\n$table assigned unsafely at line 323:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$application_id used without escaping. |  |
| 329 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at \t\t\t\t FROM {$table}\r\n |  |
| 350 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT * FROM {$table}\r\n\t\t\t\t WHERE object_type = &#039;application&#039; AND object_id = %d\r\n\t\t\t\t ORDER BY created_at DESC\r\n\t\t\t\t LIMIT 50&quot;,\r\n\t\t\t\t$application_id\r\n\t\t\t))\n$table assigned unsafely at line 347:\n $table = $wpdb-&gt;prefix . &#039;rp_activity_log&#039;\n$application_id used without escaping. |  |
| 352 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table}\r\n |  |
| 383 | 23 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 383 | 23 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 383 | 24 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_var($wpdb-&gt;prepare( &quot;SELECT status FROM {$table} WHERE id = %d&quot;, $id ))\n$table assigned unsafely at line 382:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$old_status assigned unsafely at line 383:\n $old_status = $wpdb-&gt;get_var(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT status FROM {$table} WHERE id = %d&quot;, $id )\r\n\t\t)\n$id used without escaping. |  |
| 384 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT status FROM {$table} WHERE id = %d&quot; |  |
| 391 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 391 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 396 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |

## `src/Admin/SetupWizard/SetupWizard.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 95 | 15 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 147 | 32 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 148 | 20 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 803 | 28 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;email&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |

## `src/Core/Plugin.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 232 | 16 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |

## `src/Services/DocumentDownloadService.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |
| 122 | 22 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $document_id ))\n$table assigned unsafely at line 119:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$document assigned unsafely at line 122:\n $document = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $document_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$document_id used without escaping. |  |
| 123 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} WHERE id = %d&quot; |  |
| 163 | 10 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;query($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;UPDATE {$table} SET download_count = download_count + 1 WHERE id = %d&quot;,\r\n\t\t\t\t$document_id\r\n\t\t\t))\n$table assigned unsafely at line 119:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$document assigned unsafely at line 122:\n $document = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $document_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$document_id used without escaping. |  |
| 165 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;UPDATE {$table} SET download_count = download_count + 1 WHERE id = %d&quot; |  |
| 260 | 25 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 260 | 49 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 261 | 25 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 261 | 77 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |

## `src/Taxonomies/EmploymentType.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |

## `src/Admin/Export/BackupExporter.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 164 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results(&quot;SELECT * FROM {$table}&quot;)\n$table assigned unsafely at line 161:\n $table = $wpdb-&gt;prefix . &#039;rp_candidates&#039; |  |
| 178 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results(&quot;SELECT * FROM {$table}&quot;)\n$table assigned unsafely at line 175:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039; |  |
| 192 | 23 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results(&quot;SELECT * FROM {$table}&quot;)\n$table assigned unsafely at line 189:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$documents assigned unsafely at line 192:\n $documents = $wpdb-&gt;get_results( &quot;SELECT * FROM {$table}&quot;, ARRAY_A ) ?: [] |  |
| 214 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d&quot;, 1000 ))\n$table assigned unsafely at line 210:\n $table = $wpdb-&gt;prefix . &#039;rp_activity_log&#039; |  |
| 215 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d&quot; |  |

## `src/Admin/MetaBoxes/JobMeta.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 253 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[$key] |  |

## `src/Api/ApplicationController.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 745 | 27 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_results(&quot;SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status&quot;)\n$applications_table assigned unsafely at line 741:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$status_counts assigned unsafely at line 745:\n $status_counts = $wpdb-&gt;get_results(\r\n\t\t\t&quot;SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status&quot;,\r\n\t\t\tOBJECT_K\r\n\t\t) |  |
| 746 | 4 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status&quot; |  |
| 752 | 26 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_results(&quot;SELECT DATE(created_at) as date, COUNT(*) as count\r\n\t\t\t FROM {$applications_table}\r\n\t\t\t WHERE created_at &gt;= DATE_SUB(NOW(), INTERVAL 30 DAY)\r\n\t\t\t GROUP BY DATE(created_at)\r\n\t\t\t ORDER BY date ASC&quot;)\n$applications_table assigned unsafely at line 741:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$status_counts assigned unsafely at line 745:\n $status_counts = $wpdb-&gt;get_results(\r\n\t\t\t&quot;SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status&quot;,\r\n\t\t\tOBJECT_K\r\n\t\t) |  |
| 754 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at \t\t\t FROM {$applications_table}\r\n |  |
| 762 | 22 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_results(&quot;SELECT job_id, COUNT(*) as count\r\n\t\t\t FROM {$applications_table}\r\n\t\t\t GROUP BY job_id\r\n\t\t\t ORDER BY count DESC\r\n\t\t\t LIMIT 5&quot;)\n$applications_table assigned unsafely at line 741:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$status_counts assigned unsafely at line 745:\n $status_counts = $wpdb-&gt;get_results(\r\n\t\t\t&quot;SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status&quot;,\r\n\t\t\tOBJECT_K\r\n\t\t) |  |
| 764 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at \t\t\t FROM {$applications_table}\r\n |  |

## `src/Services/ApplicationService.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 75 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 108 | 20 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;rp_application_created&quot;. |  |
| 125 | 25 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $id ))\n$table assigned unsafely at line 122:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$application assigned unsafely at line 125:\n $application = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$id used without escaping. |  |
| 126 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} WHERE id = %d&quot; |  |
| 209 | 25 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_var($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT COUNT(*) FROM {$table} a\r\n\t\t\t\tLEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n\t\t\t\tWHERE {$where_clause}&quot;,\r\n\t\t\t\t...$values\r\n\t\t\t))\n$table assigned unsafely at line 161:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidates_table assigned unsafely at line 162:\n $candidates_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$where assigned unsafely at line 182:\n $where[] = &#039;(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)&#039;\n$search assigned unsafely at line 181:\n $search = &#039;%&#039; . $wpdb-&gt;esc_like( $args[&#039;search&#039;] ) . &#039;%&#039;\n$args[&#039;search&#039;] used without escaping. |  |
| 211 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT COUNT(*) FROM {$table} a\r\n |  |
| 212 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$candidates_table} at \t\t\t\tLEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n |  |
| 213 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$where_clause} at \t\t\t\tWHERE {$where_clause}&quot; |  |
| 213 | 27 | WARNING | WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare | Replacement variables found, but no valid placeholders found in the query. |  |
| 220 | 21 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT a.*, c.first_name, c.last_name, c.email, c.phone, c.salutation\r\n\t\t\t\tFROM {$table} a\r\n\t\t\t\tLEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n\t\t\t\tWHERE {$where_clause}\r\n\t\t\t\tORDER BY {$orderby} {$order}\r\n\t\t\t\tLIMIT %d OFFSET %d&quot;,\r\n\t\t\t\t...array_merge( $values, [ $per_page, $offset ] )\r\n\t\t\t))\n$table assigned unsafely at line 161:\n $table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidates_table assigned unsafely at line 162:\n $candidates_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$where assigned unsafely at line 182:\n $where[] = &#039;(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)&#039;\n$search assigned unsafely at line 181:\n $search = &#039;%&#039; . $wpdb-&gt;esc_like( $args[&#039;search&#039;] ) . &#039;%&#039;\n$args[&#039;search&#039;] used without escaping. |  |
| 221 | 4 | WARNING | WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber | Incorrect number of replacements passed to $wpdb-&gt;prepare(). Found 1 replacement parameters, expected 2. |  |
| 223 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at \t\t\t\tFROM {$table} a\r\n |  |
| 224 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$candidates_table} at \t\t\t\tLEFT JOIN {$candidates_table} c ON a.candidate_id = c.id\r\n |  |
| 225 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$where_clause} at \t\t\t\tWHERE {$where_clause}\r\n |  |
| 226 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$orderby} at \t\t\t\tORDER BY {$orderby} {$order}\r\n |  |
| 226 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$order} at \t\t\t\tORDER BY {$orderby} {$order}\r\n |  |
| 337 | 20 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;rp_application_status_changed&quot;. |  |
| 356 | 22 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_var($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id FROM {$table} WHERE email = %s&quot;,\r\n\t\t\t\t$email\r\n\t\t\t))\n$table assigned unsafely at line 351:\n $table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$email assigned unsafely at line 352:\n $email = strtolower( trim( $data[&#039;email&#039;] ) )\n$data[&#039;email&#039;] used without escaping. |  |
| 358 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT id FROM {$table} WHERE email = %s&quot; |  |
| 394 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 419 | 17 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$table} WHERE id = %d&quot;, $id ))\n$table assigned unsafely at line 416:\n $table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$id used without escaping. |  |
| 420 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT * FROM {$table} WHERE id = %d&quot; |  |

## `src/Services/GdprService.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 28 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 28 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 43 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 43 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 75 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 75 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 76 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 76 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 83 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 83 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 99 | 31 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 99 | 31 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 99 | 32 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_col($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id FROM {$applications_table} WHERE candidate_id = %d&quot;,\r\n\t\t\t\t$candidate_id\r\n\t\t\t))\n$applications_table assigned unsafely at line 98:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$application_ids assigned unsafely at line 99:\n $application_ids = $wpdb-&gt;get_col(\r\n\t\t\t$wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id FROM {$applications_table} WHERE candidate_id = %d&quot;,\r\n\t\t\t\t$candidate_id\r\n\t\t\t)\r\n\t\t)\n$candidate_id used without escaping. |  |
| 101 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT id FROM {$applications_table} WHERE candidate_id = %d&quot; |  |
| 113 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 113 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 129 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 129 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 147 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 147 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 159 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 159 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 159 | 29 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_col($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id FROM {$applications_table} WHERE candidate_id = %d&quot;,\r\n\t\t\t\t$candidate_id\r\n\t\t\t))\n$applications_table assigned unsafely at line 146:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidate_id used without escaping. |  |
| 161 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT id FROM {$applications_table} WHERE candidate_id = %d&quot; |  |
| 184 | 23 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results($wpdb-&gt;prepare( &quot;SELECT file_path FROM {$table} WHERE application_id = %d&quot;, $application_id ))\n$table assigned unsafely at line 181:\n $table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$documents assigned unsafely at line 184:\n $documents = $wpdb-&gt;get_results(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT file_path FROM {$table} WHERE application_id = %d&quot;, $application_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$application_id used without escaping. |  |
| 185 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$table} at &quot;SELECT file_path FROM {$table} WHERE application_id = %d&quot; |  |
| 207 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 207 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 221 | 29 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 221 | 29 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 221 | 30 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $candidates_table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$candidates_table} WHERE id = %d&quot;, $candidate_id ))\n$candidates_table assigned unsafely at line 220:\n $candidates_table = $wpdb-&gt;prefix . &#039;rp_candidates&#039;\n$candidate assigned unsafely at line 221:\n $candidate = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$candidates_table} WHERE id = %d&quot;, $candidate_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$candidate_id used without escaping. |  |
| 222 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$candidates_table} at &quot;SELECT * FROM {$candidates_table} WHERE id = %d&quot; |  |
| 232 | 31 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 232 | 31 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 232 | 32 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_results($wpdb-&gt;prepare( &quot;SELECT * FROM {$applications_table} WHERE candidate_id = %d&quot;, $candidate_id ))\n$applications_table assigned unsafely at line 231:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$applications assigned unsafely at line 232:\n $applications = $wpdb-&gt;get_results(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$applications_table} WHERE candidate_id = %d&quot;, $candidate_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$candidate_id used without escaping. |  |
| 233 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT * FROM {$applications_table} WHERE candidate_id = %d&quot; |  |
| 239 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 239 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 239 | 29 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $documents_table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT d.* FROM {$documents_table} d\r\n\t\t\t\t JOIN {$applications_table} a ON d.application_id = a.id\r\n\t\t\t\t WHERE a.candidate_id = %d&quot;,\r\n\t\t\t\t$candidate_id\r\n\t\t\t))\n$documents_table assigned unsafely at line 238:\n $documents_table = $wpdb-&gt;prefix . &#039;rp_documents&#039;\n$documents assigned unsafely at line 239:\n $documents = $wpdb-&gt;get_results(\r\n\t\t\t$wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT d.* FROM {$documents_table} d\r\n\t\t\t\t JOIN {$applications_table} a ON d.application_id = a.id\r\n\t\t\t\t WHERE a.candidate_id = %d&quot;,\r\n\t\t\t\t$candidate_id\r\n\t\t\t),\r\n\t\t\tARRAY_A\r\n\t\t)\n$applications_table assigned unsafely at line 231:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidate_id used without escaping.\n$applications assigned unsafely at line 232:\n $applications = $wpdb-&gt;get_results(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$applications_table} WHERE candidate_id = %d&quot;, $candidate_id ),\r\n\t\t\tARRAY_A\r\n\t\t) |  |
| 241 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$documents_table} at &quot;SELECT d.* FROM {$documents_table} d\r\n |  |
| 242 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at \t\t\t\t JOIN {$applications_table} a ON d.application_id = a.id\r\n |  |
| 262 | 25 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $log_table used in $wpdb-&gt;get_results($wpdb-&gt;prepare(\r\n\t\t\t\t\t&quot;SELECT * FROM {$log_table}\r\n\t\t\t\t\t WHERE object_type = &#039;application&#039; AND object_id IN ({$ids_placeholder})&quot;,\r\n\t\t\t\t\t...$application_ids\r\n\t\t\t\t))\n$log_table assigned unsafely at line 255:\n $log_table = $wpdb-&gt;prefix . &#039;rp_activity_log&#039;\n$application_ids assigned unsafely at line 256:\n $application_ids = array_column( $applications ?: [], &#039;id&#039; )\n$applications assigned unsafely at line 232:\n $applications = $wpdb-&gt;get_results(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$applications_table} WHERE candidate_id = %d&quot;, $candidate_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$applications_table assigned unsafely at line 231:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$candidate_id used without escaping. |  |
| 262 | 27 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 262 | 27 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 264 | 6 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$log_table} at &quot;SELECT * FROM {$log_table}\r\n |  |
| 265 | 1 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$ids_placeholder} at \t\t\t\t\t WHERE object_type = &#039;application&#039; AND object_id IN ({$ids_placeholder})&quot; |  |
| 265 | 79 | WARNING | WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare | Replacement variables found, but no valid placeholders found in the query. |  |
| 316 | 31 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 316 | 31 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 316 | 32 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_row($wpdb-&gt;prepare( &quot;SELECT * FROM {$applications_table} WHERE id = %d&quot;, $application_id ))\n$applications_table assigned unsafely at line 315:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$application assigned unsafely at line 316:\n $application = $wpdb-&gt;get_row(\r\n\t\t\t$wpdb-&gt;prepare( &quot;SELECT * FROM {$applications_table} WHERE id = %d&quot;, $application_id ),\r\n\t\t\tARRAY_A\r\n\t\t)\n$application_id used without escaping. |  |
| 317 | 20 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT * FROM {$applications_table} WHERE id = %d&quot; |  |
| 353 | 9 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 382 | 30 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $applications_table used in $wpdb-&gt;get_col($wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id FROM {$applications_table}\r\n\t\t\t\t WHERE status = &#039;deleted&#039; AND updated_at &lt; %s&quot;,\r\n\t\t\t\t$cutoff_date\r\n\t\t\t))\n$applications_table assigned unsafely at line 379:\n $applications_table = $wpdb-&gt;prefix . &#039;rp_applications&#039;\n$old_applications assigned unsafely at line 382:\n $old_applications = $wpdb-&gt;get_col(\r\n\t\t\t$wpdb-&gt;prepare(\r\n\t\t\t\t&quot;SELECT id FROM {$applications_table}\r\n\t\t\t\t WHERE status = &#039;deleted&#039; AND updated_at &lt; %s&quot;,\r\n\t\t\t\t$cutoff_date\r\n\t\t\t)\r\n\t\t) |  |
| 384 | 5 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$applications_table} at &quot;SELECT id FROM {$applications_table}\r\n |  |

## `src/Services/SpamProtection.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 198 | 3 | WARNING | WordPress.PHP.DevelopmentFunctions.error_log_error_log | error_log() found. Debug code should not normally be used in production. |  |
| 209 | 20 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;rp_spam_blocked&quot;. |  |

## `src/Database/Migrator.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 57 | 23 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 57 | 23 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 82 | 11 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;query(&quot;DROP TABLE IF EXISTS {$table}&quot;) |  |
| 82 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 82 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 82 | 27 | WARNING | WordPress.DB.DirectDatabaseQuery.SchemaChange | Attempting a database schema change is discouraged. |  |

## `templates/archive-job_listing.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 45 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$settings&quot;. |  |
| 46 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$company&quot;. |  |
| 66 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$job_ids&quot;. |  |
| 91 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$categories&quot;. |  |
| 116 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$locations&quot;. |  |
| 130 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$types&quot;. |  |
| 143 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$remote&quot;. |  |
| 145 | 41 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$remote_labels&quot;. |  |

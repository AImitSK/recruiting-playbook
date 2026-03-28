#!/bin/bash
################################################################################
# WordPress.org Compliance Test Script
#
# Testet ein Free-Plugin ZIP gegen alle WordPress.org Review Issues
# Basierend auf: [WordPress Plugin Directory] Review vom 26. März 2026
#
# Usage:
#   ./wordpress-org-compliance-test.sh path/to/recruiting-playbook-free.zip
#
# Exit Codes:
#   0 = Alle Tests bestanden
#   1 = Mindestens ein Test fehlgeschlagen
################################################################################

# Continue on error to show all test results
# set -e  # Disabled to allow all tests to run

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_WARNING=0

# Helper Functions
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_test() {
    echo -e "${YELLOW}→ Testing: $1${NC}"
}

print_pass() {
    echo -e "${GREEN}✓ PASS:${NC} $1"
    ((TESTS_PASSED++))
}

print_fail() {
    echo -e "${RED}✗ FAIL:${NC} $1"
    ((TESTS_FAILED++))
}

print_warning() {
    echo -e "${YELLOW}⚠ WARNING:${NC} $1"
    ((TESTS_WARNING++))
}

print_info() {
    echo -e "${BLUE}ℹ INFO:${NC} $1"
}

# Check arguments
if [ $# -eq 0 ]; then
    echo "Usage: $0 <path-to-plugin.zip>"
    echo "Example: $0 recruiting-playbook-free.1.3.0.zip"
    exit 1
fi

PLUGIN_ZIP="$1"

if [ ! -f "$PLUGIN_ZIP" ]; then
    echo -e "${RED}Error: File not found: $PLUGIN_ZIP${NC}"
    exit 1
fi

print_header "WordPress.org Compliance Test"
print_info "Plugin: $(basename "$PLUGIN_ZIP")"
print_info "Date: $(date '+%Y-%m-%d %H:%M:%S')"

# Create temp directory
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

print_info "Extracting plugin to: $TEMP_DIR"
unzip -q "$PLUGIN_ZIP" -d "$TEMP_DIR"

# Find plugin root directory
PLUGIN_DIR=$(find "$TEMP_DIR" -maxdepth 1 -type d -name "recruiting-playbook*" | head -1)

if [ -z "$PLUGIN_DIR" ]; then
    echo -e "${RED}Error: Could not find plugin directory in ZIP${NC}"
    exit 1
fi

print_info "Plugin directory: $PLUGIN_DIR"

################################################################################
# TEST 1: Trialware / Locked Features (CRITICAL)
################################################################################
print_header "TEST 1: Trialware / Locked Features (Guideline 5)"

# 1.1 Check for Premium-Only Files (should NOT exist in Free version)
print_test "Checking for Premium-Only Files"

PREMIUM_FILES=(
    "src/Admin/Pages/KanbanBoard.php"
    "src/Admin/Pages/TalentPoolPage.php"
    "src/Admin/Pages/ReportingPage.php"
    "src/Admin/Pages/EmailSettingsPage.php"
    "src/Admin/Pages/FormBuilderPage.php"
    "src/Services/TalentPoolService.php"
    "src/Services/NoteService.php"
    "src/Services/RatingService.php"
    "src/Services/EmailTemplateService.php"
    "src/Services/EmailService.php"
    "src/Api/TalentPoolController.php"
    "src/Api/NoteController.php"
    "src/Api/RatingController.php"
    "src/Api/EmailTemplateController.php"
)

PREMIUM_FILES_FOUND=0
for file in "${PREMIUM_FILES[@]}"; do
    if [ -f "$PLUGIN_DIR/$file" ]; then
        print_fail "Premium file exists: $file"
        ((PREMIUM_FILES_FOUND++))
    fi
done

if [ $PREMIUM_FILES_FOUND -eq 0 ]; then
    print_pass "No premium-only files found in Free version"
else
    print_fail "$PREMIUM_FILES_FOUND premium files found (should be 0)"
fi

# 1.2 Check for rp_can() Feature Gates
print_test "Checking for rp_can() feature gates"

RRP_CAN_COUNT=$(find "$PLUGIN_DIR/src" -type f -name "*.php" -exec grep -l "rp_can(" {} \; 2>/dev/null | wc -l)

if [ "$RRP_CAN_COUNT" -gt 0 ]; then
    print_warning "$RRP_CAN_COUNT files contain rp_can() checks"
    print_info "This is OK if features are completely removed from Free version"
    print_info "But NOT OK if premium code exists in Free version"
else
    print_pass "No rp_can() feature gates found"
fi

# 1.3 Check for is__premium_only() calls
print_test "Checking for is__premium_only() runtime checks"

IS_PREMIUM_COUNT=$(find "$PLUGIN_DIR/src" -type f -name "*.php" -exec grep -l "is__premium_only()" {} \; 2>/dev/null | wc -l)

if [ "$IS_PREMIUM_COUNT" -gt 0 ]; then
    print_info "$IS_PREMIUM_COUNT files use is__premium_only() - OK if code blocks are empty"

    # Check if code blocks are actually removed
    for file in $(find "$PLUGIN_DIR/src" -type f -name "*.php" -exec grep -l "is__premium_only()" {} \; 2>/dev/null); do
        # Simple heuristic: Check if there's actual code after is__premium_only() check
        CODE_LINES=$(grep -A 5 "is__premium_only()" "$file" | grep -v "//" | grep -v "^\s*$" | wc -l)
        if [ "$CODE_LINES" -gt 2 ]; then
            print_warning "$(basename "$file") may contain premium code after is__premium_only() check"
        fi
    done
else
    print_pass "No is__premium_only() calls found"
fi

# 1.4 Check for specific premium features in code
print_test "Checking for premium feature keywords in code"

PREMIUM_KEYWORDS=(
    "kanban_board"
    "talent_pool"
    "email_templates"
    "csv_export"
    "api_access"
    "custom_fields"
    "advanced_reporting"
)

for keyword in "${PREMIUM_KEYWORDS[@]}"; do
    KEYWORD_COUNT=$(grep -r "$keyword" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | wc -l)
    if [ "$KEYWORD_COUNT" -gt 5 ]; then
        print_warning "Keyword '$keyword' found $KEYWORD_COUNT times (may indicate locked feature)"
    fi
done

################################################################################
# TEST 2: REST API Permission Callbacks
################################################################################
print_header "TEST 2: REST API Permission Callbacks"

print_test "Checking get_company permission callback"

if [ -f "$PLUGIN_DIR/src/Api/SettingsController.php" ]; then
    if grep -q "get_company_permissions_check" "$PLUGIN_DIR/src/Api/SettingsController.php"; then
        if grep -A 5 "get_company_permissions_check" "$PLUGIN_DIR/src/Api/SettingsController.php" | grep -q "manage_options"; then
            print_pass "get_company uses manage_options permission"
        else
            print_fail "get_company does NOT use manage_options (too permissive)"
        fi
    else
        print_warning "get_company_permissions_check not found"
    fi
else
    print_warning "SettingsController.php not found"
fi

# Check all REST API Controllers for permission callbacks
print_test "Checking all REST API endpoints for permission callbacks"

API_CONTROLLERS=$(find "$PLUGIN_DIR/src/Api" -name "*Controller.php" 2>/dev/null)
MISSING_PERMISSIONS=0

for controller in $API_CONTROLLERS; do
    # Check if register_routes exists
    if grep -q "register_routes" "$controller"; then
        # Check for permission_callback in all endpoints
        ROUTES=$(grep -A 10 "register_rest_route" "$controller" | grep "permission_callback" | wc -l)
        ENDPOINTS=$(grep "register_rest_route" "$controller" | wc -l)

        if [ "$ROUTES" -lt "$ENDPOINTS" ]; then
            print_warning "$(basename "$controller"): Possible missing permission_callback"
            ((MISSING_PERMISSIONS++))
        fi
    fi
done

if [ $MISSING_PERMISSIONS -eq 0 ]; then
    print_pass "All REST API endpoints have permission callbacks"
fi

################################################################################
# TEST 3: Freemius SDK Version
################################################################################
print_header "TEST 3: Freemius SDK Version"

print_test "Checking Freemius SDK version"

if [ -f "$PLUGIN_DIR/composer.json" ]; then
    FREEMIUS_VERSION=$(grep "freemius/wordpress-sdk" "$PLUGIN_DIR/composer.json" | grep -oP '"\K[0-9.]+')

    if [ ! -z "$FREEMIUS_VERSION" ]; then
        print_info "Found Freemius SDK version: $FREEMIUS_VERSION"

        # Check if version is >= 2.13.1
        MAJOR=$(echo "$FREEMIUS_VERSION" | cut -d. -f1)
        MINOR=$(echo "$FREEMIUS_VERSION" | cut -d. -f2)
        PATCH=$(echo "$FREEMIUS_VERSION" | cut -d. -f3)

        if [ "$MAJOR" -gt 2 ] || ([ "$MAJOR" -eq 2 ] && [ "$MINOR" -gt 13 ]) || ([ "$MAJOR" -eq 2 ] && [ "$MINOR" -eq 13 ] && [ "$PATCH" -ge 1 ]); then
            print_pass "Freemius SDK version $FREEMIUS_VERSION is up to date (>= 2.13.1)"
        else
            print_fail "Freemius SDK version $FREEMIUS_VERSION is outdated (need >= 2.13.1)"
        fi
    else
        print_warning "Could not parse Freemius SDK version from composer.json"
    fi
else
    print_warning "composer.json not found"
fi

################################################################################
# TEST 4: External Services Documentation
################################################################################
print_header "TEST 4: External Services Documentation"

print_test "Checking readme.txt for External Services section"

if [ -f "$PLUGIN_DIR/readme.txt" ]; then
    if grep -q "== External Services ==" "$PLUGIN_DIR/readme.txt"; then
        print_pass "External Services section found in readme.txt"

        # Check for specific services
        SERVICES=(
            "developer.recruiting-playbook.de"
            "adaptivecards.io"
            "Freemius"
            "Terms of Service"
            "Privacy Policy"
        )

        for service in "${SERVICES[@]}"; do
            if grep -q "$service" "$PLUGIN_DIR/readme.txt"; then
                print_pass "Service documented: $service"
            else
                print_warning "Service NOT documented: $service"
            fi
        done
    else
        print_fail "External Services section MISSING in readme.txt"
    fi
else
    print_fail "readme.txt not found"
fi

# Check for undocumented external URLs in code
print_test "Checking for undocumented external service URLs in code"

EXTERNAL_URLS=$(grep -rh "https\?://" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | \
    grep -oP 'https?://[^"'\''<> ]+' | \
    grep -v "wordpress.org" | \
    grep -v "recruiting-playbook.com" | \
    sort -u)

if [ ! -z "$EXTERNAL_URLS" ]; then
    print_info "Found external URLs in code:"
    echo "$EXTERNAL_URLS" | while read url; do
        if grep -q "$url" "$PLUGIN_DIR/readme.txt" 2>/dev/null; then
            print_pass "URL documented: $url"
        else
            print_warning "URL NOT documented: $url"
        fi
    done
fi

################################################################################
# TEST 5: Prefixing (4+ characters)
################################################################################
print_header "TEST 5: Prefixing (4+ characters)"

print_test "Checking function prefixes"

# Get all function names
FUNCTIONS=$(grep -rh "function [a-z_]" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | \
    grep -oP 'function \K[a-z_]+' | \
    grep -v "^__" | \
    sort -u)

BAD_PREFIXES=0
if [ ! -z "$FUNCTIONS" ]; then
    echo "$FUNCTIONS" | while read func; do
        # Check if function starts with rp_ or recpl_ or is in namespace
        if [[ ! "$func" =~ ^rp_ ]] && [[ ! "$func" =~ ^recpl_ ]]; then
            # Check if it's a class method (has namespace)
            if ! grep -q "namespace RecruitingPlaybook" $(grep -l "function $func" "$PLUGIN_DIR/src"/*.php 2>/dev/null) 2>/dev/null; then
                print_warning "Function '$func' may lack proper prefix"
                ((BAD_PREFIXES++))
            fi
        fi
    done
fi

if [ $BAD_PREFIXES -eq 0 ]; then
    print_pass "All functions have proper prefixes or are namespaced"
fi

print_test "Checking constant prefixes"

# Check for defines without proper prefix
CONSTANTS=$(grep -rh "define(" "$PLUGIN_DIR" --include="*.php" 2>/dev/null | \
    grep -oP "define\(\s*'?\K[A-Z_]+" | \
    sort -u)

BAD_CONSTANTS=0
if [ ! -z "$CONSTANTS" ]; then
    echo "$CONSTANTS" | while read const; do
        if [[ ! "$const" =~ ^RP_ ]] && [[ ! "$const" =~ ^RECPL_ ]] && [[ ! "$const" =~ ^WP_ ]] && [[ ! "$const" =~ ^ABSPATH ]]; then
            print_warning "Constant '$const' may lack proper prefix"
            ((BAD_CONSTANTS++))
        fi
    done
fi

if [ $BAD_CONSTANTS -eq 0 ]; then
    print_pass "All constants have proper prefixes"
fi

################################################################################
# TEST 6: Additional Checks
################################################################################
print_header "TEST 6: Additional Checks"

# Check for WP_DEBUG compatibility
print_test "Checking for common WP_DEBUG issues"

# Check for @-suppression
SUPPRESSION_COUNT=$(grep -r "@" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | grep -v "phpcs" | grep -v "param" | grep -v "return" | grep -v "var" | grep -v "package" | wc -l)
if [ "$SUPPRESSION_COUNT" -gt 10 ]; then
    print_warning "Found $SUPPRESSION_COUNT error suppression operators (@) - avoid in production"
fi

# Check for var_dump, print_r, console.log
DEBUG_COUNT=$(grep -r "var_dump\|print_r\|console\.log" "$PLUGIN_DIR" --include="*.php" --include="*.js" 2>/dev/null | wc -l)
if [ "$DEBUG_COUNT" -gt 0 ]; then
    print_warning "Found $DEBUG_COUNT debug statements (var_dump/print_r/console.log)"
else
    print_pass "No debug statements found"
fi

# Check for direct database queries without $wpdb->prepare()
print_test "Checking for unsafe database queries"

UNSAFE_QUERIES=$(grep -r "\$wpdb->query\|->get_results\|->get_row" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | \
    grep -v "prepare(" | \
    wc -l)

if [ "$UNSAFE_QUERIES" -gt 0 ]; then
    print_warning "Found $UNSAFE_QUERIES potential unsafe database queries"
else
    print_pass "No unsafe database queries detected"
fi

# Check for nonce verification
print_test "Checking for proper nonce verification"

FORM_SUBMISSIONS=$(grep -r "\$_POST\|\$_GET" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | wc -l)
NONCE_CHECKS=$(grep -r "wp_verify_nonce\|check_admin_referer" "$PLUGIN_DIR/src" --include="*.php" 2>/dev/null | wc -l)

if [ "$FORM_SUBMISSIONS" -gt 0 ] && [ "$NONCE_CHECKS" -eq 0 ]; then
    print_warning "Found $_POST/$_GET usage but no nonce verification"
fi

################################################################################
# SUMMARY
################################################################################
print_header "TEST SUMMARY"

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED + TESTS_WARNING))

echo -e "${GREEN}✓ Passed:${NC}   $TESTS_PASSED"
echo -e "${RED}✗ Failed:${NC}   $TESTS_FAILED"
echo -e "${YELLOW}⚠ Warnings:${NC} $TESTS_WARNING"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Total:${NC}     $TOTAL_TESTS"

# Determine exit code
if [ $TESTS_FAILED -gt 0 ]; then
    echo -e "\n${RED}❌ COMPLIANCE TEST FAILED${NC}"
    echo -e "${RED}The plugin does NOT meet WordPress.org guidelines.${NC}"
    exit 1
elif [ $TESTS_WARNING -gt 5 ]; then
    echo -e "\n${YELLOW}⚠️  COMPLIANCE TEST PASSED WITH WARNINGS${NC}"
    echo -e "${YELLOW}Review warnings before submitting to WordPress.org${NC}"
    exit 0
else
    echo -e "\n${GREEN}✅ COMPLIANCE TEST PASSED${NC}"
    echo -e "${GREEN}The plugin meets WordPress.org guidelines!${NC}"
    exit 0
fi

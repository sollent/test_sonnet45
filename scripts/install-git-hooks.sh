#!/bin/bash

# Script to install Git hooks for quality checks
# Run this after cloning the repository

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Installing Git hooks...${NC}\n"

# Get the project root directory
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOOKS_DIR="$PROJECT_ROOT/.git/hooks"

# Check if .git directory exists
if [ ! -d "$PROJECT_ROOT/.git" ]; then
    echo -e "${RED}Error: .git directory not found. Are you in a Git repository?${NC}"
    exit 1
fi

# Create pre-commit hook
cat > "$HOOKS_DIR/pre-commit" << 'EOF'
#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Running pre-commit checks...${NC}\n"

# Get list of changed PHP files
CHANGED_PHP_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' | grep '^apps/backend/')

if [ -z "$CHANGED_PHP_FILES" ]; then
    echo -e "${GREEN}✓ No PHP files changed, skipping checks${NC}"
    exit 0
fi

echo -e "${YELLOW}Changed PHP files:${NC}"
echo "$CHANGED_PHP_FILES"
echo ""

# Check if Docker container is running
if ! docker ps | grep -q backend-php83; then
    echo -e "${RED}✗ Docker container 'backend-php83' is not running${NC}"
    echo -e "${YELLOW}Please start Docker containers: docker compose up -d${NC}"
    exit 1
fi

# Run PHP-CS-Fixer check
echo -e "${YELLOW}Running PHP-CS-Fixer...${NC}"
docker exec backend-php83 vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

if [ $? -ne 0 ]; then
    echo -e "\n${RED}✗ PHP-CS-Fixer found issues${NC}"
    echo -e "${YELLOW}Run 'make cs-fixer-fix' to auto-fix or 'docker exec backend-php83 vendor/bin/php-cs-fixer fix' manually${NC}"
    exit 1
fi

echo -e "${GREEN}✓ PHP-CS-Fixer passed${NC}\n"

# Run PHPStan
echo -e "${YELLOW}Running PHPStan...${NC}"
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=1G --no-progress

if [ $? -ne 0 ]; then
    echo -e "\n${RED}✗ PHPStan found issues${NC}"
    echo -e "${YELLOW}Fix the issues manually or run 'make phpstan' for detailed output${NC}"
    exit 1
fi

echo -e "${GREEN}✓ PHPStan passed${NC}\n"

# All checks passed
echo -e "${GREEN}✓ All pre-commit checks passed!${NC}"
exit 0
EOF

# Make hook executable
chmod +x "$HOOKS_DIR/pre-commit"

echo -e "${GREEN}✓ Git hooks installed successfully!${NC}\n"
echo -e "Pre-commit hook will now run:"
echo -e "  - PHP-CS-Fixer (code style check)"
echo -e "  - PHPStan (static analysis)"
echo -e "\nTo bypass hooks (not recommended): git commit --no-verify"

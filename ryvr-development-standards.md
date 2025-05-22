# Ryvr Development Standards

## Table of Contents
1. [Code Style & Formatting](#code-style--formatting)
2. [Naming Conventions](#naming-conventions)
3. [Documentation Standards](#documentation-standards)
4. [Security Practices](#security-practices)
5. [Testing Guidelines](#testing-guidelines)
6. [Git Workflow](#git-workflow)
7. [Performance Considerations](#performance-considerations)
8. [Code Organization](#code-organization)

## Code Style & Formatting

### PHP

- **PSR-12** compliant code.
- 4 spaces for indentation, no tabs.
- Maximum line length of 120 characters.
- Use type declarations where possible (PHP 8.2).
- Prefer strict typing (`declare(strict_types=1)`).
- Single quotes for strings unless interpolation is needed.
- Always use braces for control structures, even for single-line statements.
- Always use full PHP tags (`<?php`, not short tags).
- End each file with a single newline character.

```php
<?php
declare(strict_types=1);

namespace Ryvr\Connectors;

/**
 * DataForSEO connector implementation.
 */
class DataForSEOConnector implements RyvrConnectorInterface
{
    private string $apiKey;
    private array $config;
    
    public function init(array $authMeta): void
    {
        $this->apiKey = $authMeta['api_key'] ?? '';
        $this->config = $authMeta['config'] ?? [];
    }
    
    // Other methods...
}
```

### JavaScript

- Use ES6+ features.
- 2 spaces for indentation.
- Prefer `const` over `let`. Avoid `var`.
- Semicolons at the end of statements.
- Use arrow functions for anonymous functions.
- Prefer template literals over string concatenation.

```javascript
const fetchWorkflow = async (id) => {
  try {
    const response = await fetch(`/api/workflows/${id}`);
    const data = await response.json();
    return data;
  } catch (error) {
    console.error(`Error fetching workflow: ${error.message}`);
    return null;
  }
};
```

### CSS/SCSS

- 2 spaces for indentation.
- Use BEM (Block Element Modifier) naming convention.
- Prefer SCSS where possible.
- Mobile-first responsive design.

```scss
.workflow {
  &__header {
    display: flex;
    margin-bottom: 1rem;
    
    &--active {
      background-color: $color-success-light;
    }
  }
  
  &__title {
    font-size: 1.2rem;
    font-weight: 600;
  }
}
```

## Naming Conventions

### PHP

- **Classes**: PascalCase, descriptive nouns
  - `DataForSEOConnector`, `WorkflowRunner`, `ApiKeyManager`
- **Interfaces**: PascalCase, prefixed with verb or suffixed with Interface
  - `RyvrConnectorInterface`, `Processable`, `RunnerInterface`
- **Methods**: camelCase, start with verb
  - `fetchData()`, `runWorkflow()`, `validateCredentials()`
- **Properties**: camelCase, descriptive
  - `$apiKey`, `$lastResponse`, `$configData`
- **Constants**: UPPER_SNAKE_CASE
  - `API_ENDPOINT`, `MAX_RETRY_ATTEMPTS`

### JavaScript

- **Functions**: camelCase, start with verb
  - `fetchWorkflow()`, `runStep()`, `validateInput()`
- **Variables**: camelCase, descriptive
  - `userData`, `apiResponse`, `isLoading`
- **Components**: PascalCase
  - `WorkflowBuilder`, `ConnectorCard`, `StepEditor`

### Database

- **Tables**: snake_case, prefixed with 'ryvr_'
  - `ryvr_workflows`, `ryvr_api_keys`, `ryvr_logs`
- **Columns**: snake_case
  - `created_at`, `user_id`, `workflow_json`

## Documentation Standards

### PHP DocBlocks

- Every class, method, and property should have a DocBlock.
- Describe parameters, return types, and thrown exceptions.
- Include `@since` tags for versioning.

```php
/**
 * Runs a workflow step and returns the result.
 *
 * @param int    $workflowId The ID of the workflow to run
 * @param string $stepId     The step ID within the workflow
 * @param array  $context    The execution context data
 *
 * @return array The step execution result
 * @throws WorkflowException If the step or workflow does not exist
 * @throws ConnectorException If the connector operation fails
 *
 * @since 1.0.0
 */
public function runStep(int $workflowId, string $stepId, array $context = []): array
{
    // Implementation...
}
```

### README Files

- Each module/component should have a README.md explaining:
  - Purpose
  - Usage examples
  - Configuration options
  - Dependencies

### In-Code Comments

- Add comments for complex logic.
- Explain "why" not "what" (the code shows what, comments explain why).
- Mark TODOs with `// TODO: description` (but prefer creating issues).

## Security Practices

### Data Validation & Sanitization

- Validate all input data at the point of entry.
- Use WordPress sanitization functions for user inputs.
- Validate API responses before processing.

### Authentication & Authorization

- Never store plaintext credentials.
- Use WordPress encryption functions for storing sensitive data.
- Implement nonces for AJAX requests.
- Apply capability checks for all admin actions.

```php
// Check user capabilities before processing
if (!current_user_can('manage_ryvr_workflows')) {
    wp_die(__('You do not have permission to access this page.', 'ryvr'));
}

// Use nonces for form submissions
wp_nonce_field('ryvr_save_workflow', 'ryvr_workflow_nonce');

// Verify nonces when processing
if (!isset($_POST['ryvr_workflow_nonce']) || 
    !wp_verify_nonce($_POST['ryvr_workflow_nonce'], 'ryvr_save_workflow')) {
    wp_die(__('Security check failed.', 'ryvr'));
}
```

### API Communication

- Use HTTPS for all API requests.
- Implement rate limiting for outgoing requests.
- Handle API errors gracefully with appropriate logging.
- Validate API responses before processing.

## Testing Guidelines

### Unit Tests

- Create tests for all connector methods.
- Mock external dependencies and API responses.
- Aim for at least 70% code coverage.
- Test both success and failure cases.

### Integration Tests

- Test workflows end-to-end where possible.
- Use fixture data for consistent testing.

### Manual Testing Checklist

- Test on multiple WordPress versions.
- Test with both minimal and maximal configurations.
- Verify all error states and recovery.

## Git Workflow

### Branches

- `main`: Production-ready code
- `dev`: Main development branch
- Feature branches: `feature/feature-name`
- Bug fixes: `fix/bug-description`

### Commits

- Use clear, descriptive commit messages.
- Reference issue numbers when applicable.
- Keep commits focused on single changes.

```
feat: Add DataForSEO connector implementation (#12)
fix: Resolve API rate limiting issues on OpenAI connector (#15)
docs: Update workflow creation documentation
```

### Pull Requests

- Create meaningful PR descriptions.
- Reference related issues.
- Ensure all tests pass before merging.
- Request reviews from appropriate team members.

## Performance Considerations

### Database Queries

- Minimize database queries.
- Use appropriate indexes.
- Batch operations where possible.
- Avoid direct SQL unless necessary; use WordPress functions.

### API Requests

- Implement caching for API responses.
- Use batching when making multiple similar requests.
- Implement exponential backoff for retries.

### Assets

- Minify JavaScript and CSS for production.
- Defer non-critical JavaScript.
- Load assets only when needed.

## Code Organization

### Plugin Structure

Follow the structure defined in the scope document:

```
ryvr-core/
├─ src/
│   ├─ Connectors/
│   │   └─ [ConnectorName]Connector.php
│   ├─ Engine/
│   │   ├─ Context.php
│   │   └─ Runner.php
│   ├─ Workflows/
│   │   └─ (YAML templates)
│   └─ RyvrServiceProvider.php
├─ assets/
│   └─ editor.js
├─ ryvr-core.php
└─ composer.json
```

### Modular Design

- Each connector should be a standalone class.
- Separate business logic from presentation.
- Create reusable utilities where appropriate.
- Follow SOLID principles, especially single responsibility.

---

These development standards should be followed throughout the project to ensure code quality, maintainability, and security. Regular code reviews will enforce these standards and help maintain consistency across the codebase. 
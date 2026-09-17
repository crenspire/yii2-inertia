# Contributing

Thank you for considering contributing to Yii2 Inertia!

## Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/yii2-inertia.git`
3. Install dependencies: `composer install`
4. Run tests: `composer test`

The test suite starts a local PHP web server to exercise the SSR gateway, so it needs a free local port.

To try changes in a browser, use the example application in `examples/basic` (see the README).

## Code Style

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Add type hints and return types to all methods
- Write clear docblocks for public methods

## Testing

- Write tests for new features
- Ensure all tests pass on every supported PHP version: `composer test`
- Deprecations, notices and warnings fail the test suite
- Aim for high code coverage

## Pull Request Process

1. Create a feature branch from `main`
2. Make your changes
3. Add or update tests
4. Ensure all tests pass
5. Update documentation if needed
6. Submit a pull request with a clear description

## Semantic Versioning

We follow [Semantic Versioning](https://semver.org/):
- **MAJOR** version for incompatible API changes
- **MINOR** version for new functionality in a backwards compatible manner
- **PATCH** version for backwards compatible bug fixes

## Commit Messages

Use clear, descriptive commit messages:
- Use present tense ("Add feature" not "Added feature")
- Reference issues when applicable: "Fix #123"

## Questions?

Feel free to open an issue for any questions or concerns.


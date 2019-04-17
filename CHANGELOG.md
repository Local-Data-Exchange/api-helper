# CHANGELOG

## 0.1.0
- Helpers, Config, Events added for ApiBuilder.php .
- Created Facade and Service provide for package.

## 0.1.1
- Added ApiResponse.php now response will be object of ApiResponse.
- Auto package discovery added, now no need to register for provider those who have laravel version >= 5.5 and having dev environment.

## 0.1.2
- Updated README.md file with new document structure.
- Remove addHeader() from ApiBuilder.
- Added new dependency in composer.json file for the package.
- Update api_helper.php config file for root elements.
- Updated call() and xmlParsing() to get root element from the config file.
- Added phpunit test cases in "tests" folder.
- Created phpunit.xml file so test cases can be run on any cloud server.
- Added orchestra/testbench with phpunit for package testing.
- Updated TestCase added methods for provider and alias, also now able to mock config.

## 0.1.3
- Upgrade laravel/framework version, because incompatible with orchestra/testbench package.
- Downgrade compatible version of adbario/php-dot-notation from "2.x-dev" to "^2.2.0".
- Added unit testing with circle ci.

## 0.1.4
- Added validation in escapeSpecialCharacters() and checkBool() to return value if it is not empty.

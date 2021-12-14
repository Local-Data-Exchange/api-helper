# CHANGELOG

## 7.0.0

**Upgrade package to support laravel 7** [Ref](https://laravel.com/docs/7.x/upgrade)

- Upgrade packages to support laravel 7.
- php `^7.2.5` or higher
- array-to-xml `~2.14.0`,
- php-dot-notation `^2.x.dev`
- phpunit `8.5` 


## 6.0.0

**Upgrade package to support laravel 6**

- Syantaxt upgraded to support laravel 6.*


## 0.1.30
- Fixed return type issue when json_decode fails. [Hotfix]

## 0.1.29
- Fixed json mapping condition for empty body. [Hotfix]

## 0.1.28
- Perform json mapping if body is given.

## 0.1.27
- Bug fix of xml boolean assign.

## 0.1.26
- Adding support of boolean in query param.

## 0.1.25
- Add support of optional query param.

## 0.1.24
- Adding logs in json mapping, so we can figure out if something is wrong with data.

## 0.1.23 
- Added form_params support.
- Update circle ci config to test with laravel 6.
- Update circle ci to run testcases smoothly and fast.

## 0.1.22
- This version include support of call back function for prometheus config.

## 0.1.21
- Add support for metrics, which will use app prometheus to export metrics.

## 0.1.20
- Add option to add status code to not retry, this option you can set inside your api config.

## 0.1.19
- Bump symfony/http-foundation from 4.2.6 to 4.4.1
- Remove space from `&amp;` inside processXmlMappings fucntion.

## 0.1.18
- Updated document and added new docker image files in config for testing different environment.
- When value is empty or null at that time we were sending UNKNOWN which is now replaced with null in process json mappings function.
- For process xml mappings, when it have empty or null values we are removing from that string. Instead of returning UNKNOWN.

## 0.1.17
- Added new ability in json mappings, now it support nullable fields, which can be define in api_helper.

## 0.1.16
- Added variable type checking in checkbool method, it will only check if it is string.

## 0.1.15
- Callable method return false due to invalid method name.

## 0.1.14
- Bug solved of variable naming convention.

## 0.1.13
- Added custom method support for escaping string in processXmlMappings methods.
- Added parameter in api_helper config file which defines custom escape string method of user.

## 0.1.12
- Remove space from processXmlMappings methods.

## 0.1.11
- Converted output of simplexml_load_string into array.

## 0.1.10
- Remove constructor, also removed redundant code for improvements.

## 0.1.9
- Bug Fixed guzzle http response show 200 even it is fail.

## 0.1.8
- Added new logic for combining additional headers, and deafult headers.
 
## 0.1.7
- Due to default headers there is possibility of duplicate headers that is fixed in this version.

## 0.1.6
- Applied solution in version 0.1.5 not working properly so now, it is updated with different code flow.

## 0.1.5
- Fixing header has been overwritten by api method, when headers is being set using addHeaders.

## 0.1.4
- Added validation in escapeSpecialCharacters() and checkBool() to return value if it is not empty.

## 0.1.3
- Upgrade laravel/framework version, because incompatible with orchestra/testbench package.
- Downgrade compatible version of adbario/php-dot-notation from "2.x-dev" to "^2.2.0".
- Added unit testing with circle ci.

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

## 0.1.1
- Added ApiResponse.php now response will be object of ApiResponse.
- Auto package discovery added, now no need to register for provider those who have laravel version >= 5.5 and having dev environment.

## 0.1.0
- Helpers, Config, Events added for ApiBuilder.php .
- Created Facade and Service provide for package.


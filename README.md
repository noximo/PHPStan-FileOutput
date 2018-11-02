# PHPStan FileOutput
An error formatter for PHPStan that exports analysis result into HTML file

## Installation
```
composer require noximo/phpstan-fileoutput
```

## Usage
Edit or create your phpstan.neon file and register new error formatter. 
First two parameters are mandatory.

- First specifies path to file in which data will be outputed.

- Second specifies which other formatter will be used with FileOutput formatter running silently in the background. You can set it to null if you wish to only work with FileOutput-generated files.
```
services:
	errorFormatter.fileoutput:  # Can be any name after errorFormatter
		class: noximo\FileOutput(./example/phpstan.html, null) 
```

You can (and should) specify second parameter to use one of the other formatters as well, so console output will be unaffected:
```
class: noximo\FileOutput(./example/phpstan.html, @errorFormatter.raw) 
```
At the time of writing of this readme these formatters were available by default in PHPStan:
- ```errorFormatter.checkstyle```,  
- ```errorFormatter.json```, 
- ```errorFormatter.prettyJson```, 
- ```errorFormatter.raw```, 
- ```errorFormatter.table```

_Check [PHPStan repository](https://github.com/phpstan/phpstan) for possible updates._ 

Third parameter sets custom output template. 
```
class: noximo\FileOutput(./example/phpstan.html, null, ./tests/alternative_table.phtml) 
```
See [table.phtml](/src/table.phtml) for implementation details and data structure. 

## Output
FileOutputer will generate HTML file (assuming default template) with hyperlinks directly into PHP files where errors were encountered. If you want to leverage clickable links, set up your enviromenent according to this article: [https://tracy.nette.org/en/open-files-in-ide](https://tracy.nette.org/en/open-files-in-ide)

Note: When you fix an error, file structure and line numbers can no longer correspond to line number at the time of analysis. You'll need to re-run PHPStan to regenerate output file. Errors are outputed in descending order (line-number wise) so there's bigger chance that you won't lines out of their current positions. 

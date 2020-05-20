# ILIAS ServerCode Question Plugin

**Author**: Stefan Schweizer    <schweizs@informatik.uni-freiburg.de>

**Version**: 1.0.0

**Supports**: ILIAS 5.4


## Installation
1. Copy the `assServerCodeQuestion` directory to your ILIAS installation at the following path 
(create subdirectories, if neccessary):
`Customizing/global/plugins/Modules/TestQuestionPool/Questions/assServerCodeQuestion`
2. Go to Administration > Plugins
3. Choose **Update** for the `assServerCodeQuestion` plugin
4. Choose **Activate** for the `assServerCodeQuestion` plugin
5. Choose **Refresh** for the `assServerCodeQuestion` plugin languages

## Usage
This plugin enables source code questions. It basically provides a textarea with syntax
highlighting for various languages (based on Highlight.js and CodeMirror).

Code will be sent to an given URL. The plugin expects an answer and will print it out.

## JSON-DATA
### Output
~~~~
{
  "code": str,
  "test": str,
}
~~~~

### Input
~~~~
{
  "test": str,
  "points": number, [optional]
}
~~~~

# Acknowledgements
* this plugin is based on [ilias-asscodequestion](https://github.com/frankbauer/ilias-asscodequestion) created by Frank Bauer (<frank.bauer@fau.de>)

## Version History
### Version 1.0.0
* Initial Version
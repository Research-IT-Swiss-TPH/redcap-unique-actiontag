# Unique

A REDCap external module providing action tags that make fields unique.

## Requirements

- REDCAP 10.1.0 or newer.

## Installation

Automatic installation:

- Install this module from the REDCap External Module Repository and enable it.

## Configuration

- Labels indicating action tag information can be shown on the data entry form fields.
- Debug information can be output to the browser's console by enabling the JavaScript Debug option.


## Action Tags

### @UNIQUE

Makes the field unique.

Usage:

```JS
// without parameters; The field itself becomes unique.
@UNIQUE
// with parameters; The field itself becomes unique and also in relation to all listed field_names (field_1, field_2 and field_3)
@UNIQUE="field_1, field_2, field_3"
```

### @UNIQUE-STRICT

Makes the field unique also within the record itself.

Usage:

```JS
// Can only be used with parameters;  Checks for uniqueness of the current field value within and outside the record for given field_names (field_1, field_2).
@UNIQUE-STRICT="field_1, field_2"
```

## Developer Notice

**Feature and pull requests** are welcome!

The module is using a Babel compiler to ensure modern Javascript can be used without losing Browser Support. 
Run `npm run build` or `npm run dev` after making code changes to your Javacsript.
Also ensure that you have Node.js running on your local development machine and have installed all necessary local dependencies with `npm install`.


## Changelog

Version | Description
------- | --------------------
v1.0.0  | Initial release.

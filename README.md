# Unique Action Tag

A REDCap external module providing action tags that render fields unique with custom conditions.
The External Module version 3 has been rewritten from scratch and reduces all ActionTags to a single one with an extended API.

*Warning:* If you are upgrading from version 2 to version 3 there are breaking changes in the way the ActionTag is beeing used. Please ensure you migrate accordingly to the new @UNIQUE API.

## Default behaviour
The **new** default behaviour of the @UNIQUE Action Tag is:

- check uniqueness against all records except current record
- check uniqueness against current instance
- check uniqueness against current event

## Usage
The "with_" flags can be used to enable complete checks against all records , instances or events:
- check uniqueness against all records inlcuding current record, `with_all_records`:

`"with_all_records":true`

- check uniqueness against all instances, `with_all_instances`:

`"with_all_instances":true`

- check uniqueness against all events, `with_all_events`:

`"with_all_events":true`


Additional customizations can be configured with:

- check uniqueness against additional fields with `targets`:

`"targets": ["field_1", "field_2"]`

- define custom dialog with `title` and `message`:

`"title": "This is a title"`

`"message": "This is a message."`

## Examples

Example 1: Checks uniqueness across all records except within current record, for the current instance and event.

```JavaScript
@UNIQUE
```

Example 2: Checks uniqueness within same record against addtional fields "field_1" and "field_2", across all instances and events. A custom dialog with "title" and "message" will be triggered.

```JavaScript
@UNIQUE={
    "with_all_records": true,
    "with_all_instances": true,
    "with_all_events": true,
    "targets": ["field_1", "field_2"],
    "title": "This is a title",
    "message": "This is a message."
}
```
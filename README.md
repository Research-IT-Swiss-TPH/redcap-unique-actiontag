# Unique Action Tag

A REDCap external module providing action tags that render fields unique with custom conditions.
The External Module version 3 has been rewritten from scratch and reduces all ActionTags to a single one with an extended API.

*Warning:* If you are upgrading from version 2 to version 3 there are breaking changes in the way the ActionTag is beeing used. Please ensure you migrate accordingly to the new @UNIQUE API.

The **new** default behaviour of the @UNIQUE Action Tag is:

- check uniqueness against all records
- check uniqueness against all instances
- check uniqueness against all events
- check uniqueness against all arms

Additional customizations can be configured with:

- check uniqueness within same record with `strict`:
`"strict":true`

- check uniqueness against additional fields with `targets`:
`"tartgets": ["field_1", "field_2"]`

- define custom dialog with `title` and `message`:
`"title": "This is a title"`
`"message": "This is a message."`


The "ignore_" flags can be used to disable checks against instances, events or arms.

Example 1: Checks uniqueness across records, instances, events and arms. Since all options have defaults values, it is not necessary to supply them always.

```JavaScript
@UNIQUE
```

Example 2: Checks uniqueness within same record against addtional fields "field_1" and "field_2", while ignoring instances, events and arms. A custom dialog with "title" and "message" will be triggered.

```JavaScript
@UNIQUE={
    "strict": true,
    "tartgets": ["field_1", "field_2"],
    "ignore_instance": true,
    "ignore_event": true,
    "ignore_arm": true,
    "title": "This is a title",
    "message": "This is a message."
}
```
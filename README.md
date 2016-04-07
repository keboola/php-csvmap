# Usage

## Configuration

The module's configuration must be an object where each item's key is a path in the parsed object (`.` delimited for nesting by default).

For example, getting `X` from object `A`:

```
{
    "A": {
        "X": "this"
    }
}
```

Would be done by defining an item with a key `A.X`.

Each item must contain the information below:

- `type`: Optional, `column` by default. Possible values: `column`, `table`, `user`.
    - `column` will store the value from its key into a CSV column
    - `table` will create a "child" CSV and link through a primary key or a hash, if no primary key is defined
    - `user` will look into an array in the second argument of the parse function and fill a CSV column with its value

### Column configuration

- `mapping`: Required, must contain `destination`:
    - `destination`: Target column in the output CSV file
    - `primaryKey`: Optional, boolean. If set to true, the column will be included in the primary key

### Table configuration

- `destination`: Required, a target CSV file name
- `tableMapping`: Required, mapping of all child table's columns
    - This is a recursive configuration object that must contain settings by the same definition as the "root" of this configuration
- `parentKey`: Optional, can be used to set the parent/child link as a primary key in the chuld or override the link's column name in the child
    - `primaryKey`: boolean, same as in `column`
    - `destination`: Name of the link column (if not used, name of the parent table . `_pk` is used by default)
    - `disable`: boolean, if set to non-false value, the parent key in the child table, as well as the column in the parent will not be saved

- If the `destination` is the same as the current parsed 'type' (destination of the parent), `parentKey.disable` **must** be true to preserve consistency of structure of the child and parent

### User configuration

Same as `column`, except the **key** of the object is not searched for in the parsed data, but in an array passed to the parser to inject user data

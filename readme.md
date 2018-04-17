# Propel Real Enum Behavior

## Requirements

This Behavior was developed for **Propel 2**.  
It was also only tested for **MySQL** databases, if you wish to test it or adjust it for another database type, feel free to open an issue.

## Installation

```bash
composer require effenti/propel-real-enum-behavior
```

#### schema.xml
Add the behavior either to the root of your database or on the target table.  
You also need to specify the `phpType` property to `string` on you enum columns.  
Here is an example :

```XML
<database ...>
    <!-- This will add the real-enum behavior for all enums in the database -->
    <behavior name="real-enum"/>
    <table name="my_table">        
        <column name="my_enum" type="ENUM" valueSet="FIRST,SECOND,THIRD" phpType="string"/>
    </table>
</database>
```

#### Usage

This behavior does 2 things to make usage of `ENUMS` easier : 

- You will now see the ENUM value from the `valueSet` in the database instead of a number.
- Model classes will now have constants to easily access the enum values. Using the example above we could get a value from the value set like this : 
`MyTable::MY_ENUM_SECOND`
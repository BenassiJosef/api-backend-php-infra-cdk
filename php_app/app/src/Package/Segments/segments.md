segments
========

Segments is a way of allowing for user created sub-sets of their data.
Essentially, it works by translating json that looks like this:

```json
{
  "weekStart": "monday",
  "dateFormat": "Y-m-d",
  "baseQueryType": "organization-registration",
  "root": {
    "operator": "and",
    "nodes": [
      {
        "comparison": "like",
        "value": "io",
        "field": "email",
        "mode": "contains"
      },
      {
        "comparison": "==",
        "value": "this-month",
        "field": "birthday"
      }
    ]
  }
}
```

Into Doctrine's intermediary language DQL, which would look something like this:
```sql
SELECT 
       MAX(DISTINCT up.id), 
       MIN(or1.createdAt), 
       GROUP_CONCAT(DISTINCT up.email), 
       GROUP_CONCAT(DISTINCT up.first), 
       GROUP_CONCAT(DISTINCT up.last), 
       MAX(DISTINCT or1.profileId), 
       MAX(up.birthDay), 
       MAX(up.birthMonth) 
FROM App\\Models\\DataSources\\OrganizationRegistration or1 
LEFT JOIN App\\Models\\UserProfile up 
    WITH or1.profileId = up.id 
WHERE or1.organizationId = :organizationId 
AND or1.dataOptInAt IS NOT NULL 
AND or1.emailOptInAt IS NOT NULL 
AND (
    up.email LIKE :email 
    AND (
            (up.birthMonth = :month AND up.birthDay = :day) 
            OR (up.birthMonth = :month_1 AND up.birthDay = :day_1) 
            OR (up.birthMonth = :month_2 AND up.birthDay = :day_2) 
            OR (up.birthMonth = :month_3 AND up.birthDay = :day_3) 
            OR (up.birthMonth = :month_4 AND up.birthDay = :day_4) 
            OR (up.birthMonth = :month_5 AND up.birthDay = :day_5) 
            OR (up.birthMonth = :month_6 AND up.birthDay = :day_6) 
            OR (up.birthMonth = :month_7 AND up.birthDay = :day_7) 
            OR (up.birthMonth = :month_8 AND up.birthDay = :day_8) 
            OR (up.birthMonth = :month_9 AND up.birthDay = :day_9) 
            OR (up.birthMonth = :month_10 AND up.birthDay = :day_10) 
            OR (up.birthMonth = :month_11 AND up.birthDay = :day_11) 
            OR (up.birthMonth = :month_12 AND up.birthDay = :day_12) 
            OR (up.birthMonth = :month_13 AND up.birthDay = :day_13) 
            OR (up.birthMonth = :month_14 AND up.birthDay = :day_14) 
            OR (up.birthMonth = :month_15 AND up.birthDay = :day_15) 
            OR (up.birthMonth = :month_16 AND up.birthDay = :day_16) 
            OR (up.birthMonth = :month_17 AND up.birthDay = :day_17) 
            OR (up.birthMonth = :month_18 AND up.birthDay = :day_18) 
            OR (up.birthMonth = :month_19 AND up.birthDay = :day_19) 
            OR (up.birthMonth = :month_20 AND up.birthDay = :day_20) 
            OR (up.birthMonth = :month_21 AND up.birthDay = :day_21) 
            OR (up.birthMonth = :month_22 AND up.birthDay = :day_22) 
            OR (up.birthMonth = :month_23 AND up.birthDay = :day_23) 
            OR (up.birthMonth = :month_24 AND up.birthDay = :day_24) 
            OR (up.birthMonth = :month_25 AND up.birthDay = :day_25) 
            OR (up.birthMonth = :month_26 AND up.birthDay = :day_26) 
            OR (up.birthMonth = :month_27 AND up.birthDay = :day_27) 
            OR (up.birthMonth = :month_28 AND up.birthDay = :day_28) 
            OR (up.birthMonth = :month_29 AND up.birthDay = :day_29)
        )
    ) 
GROUP BY or1.profileId 
ORDER BY or1.createdAt ASC
```

And eventually into SQL like this 
```sql
SELECT 
       MAX(DISTINCT u0_.id) AS sclr_0, 
       MIN(o1_.created_at) AS sclr_1, 
       GROUP_CONCAT(DISTINCT u0_.email) AS sclr_2, 
       GROUP_CONCAT(DISTINCT u0_.first) AS sclr_3, 
       GROUP_CONCAT(DISTINCT u0_.last) AS sclr_4, 
       MAX(DISTINCT o1_.profile_id) AS sclr_5, 
       MAX(u0_.birth_day) AS sclr_6, 
       MAX(u0_.birth_month) AS sclr_7 
FROM organization_registration o1_ 
    LEFT JOIN user_profile u0_ 
        ON (o1_.profile_id = u0_.id) 
WHERE o1_.organization_id = ? 
  AND o1_.data_opt_in_at IS NOT NULL 
  AND o1_.email_opt_in_at IS NOT NULL 
  AND (u0_.email LIKE ? 
           AND (
                    (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?)
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?)
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?)
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?)
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?)
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?) 
                    OR (u0_.birth_month = ? AND u0_.birth_day = ?)
               )
      ) 
GROUP BY o1_.profile_id 
ORDER BY o1_.created_at ASC 
LIMIT 25
```

# Structure
The segments package itself is broken into three distinct layers:

## Metadata
The `/organisations/{orgId}/segments/metadata` route will return you
all of the supported fields, types, and operators for segments. 

This is useful for keeping the UI up to date with what operations are
supported by which fields etc...

It will return this.
```json
{
  "fields": {
    "id": {
      "key": "id",
      "type": "integer"
    },
    "rating": {
      "key": "rating",
      "type": "integer"
    },
    "sentiment": {
      "key": "sentiment",
      "type": "string"
    },
    "reviewcreatedat": {
      "key": "reviewCreatedAt",
      "type": "datetime"
    },
    "stamps": {
      "key": "stamps",
      "type": "integer"
    },
    "loyaltycreatedat": {
      "key": "loyaltyCreatedAt",
      "type": "datetime"
    },
    "loyaltylaststampedat": {
      "key": "loyaltyLastStampedAt",
      "type": "datetime"
    },
    "giftamount": {
      "key": "giftAmount",
      "type": "integer"
    },
    "giftactivatedat": {
      "key": "giftActivatedAt",
      "type": "datetime"
    },
    "giftredeemedat": {
      "key": "giftRedeemedAt",
      "type": "datetime"
    },
    "lastinteractedat": {
      "key": "lastInteractedAt",
      "type": "datetime"
    },
    "createdat": {
      "key": "createdAt",
      "type": "datetime"
    },
    "isvisit": {
      "key": "isVisit",
      "type": "boolean"
    },
    "serial": {
      "key": "serial",
      "type": "string"
    },
    "datasource": {
      "key": "dataSource",
      "type": "string"
    },
    "email": {
      "key": "email",
      "type": "string"
    },
    "first": {
      "key": "first",
      "type": "string"
    },
    "last": {
      "key": "last",
      "type": "string"
    },
    "phone": {
      "key": "phone",
      "type": "string"
    },
    "postcode": {
      "key": "postcode",
      "type": "string"
    },
    "birthday": {
      "key": "birthday",
      "type": "yeardate"
    },
    "gender": {
      "key": "gender",
      "type": "string"
    },
    "country": {
      "key": "country",
      "type": "string"
    }
  },
  "types": [
    {
      "name": "string",
      "operators": [
        {
          "operator": "=="
        },
        {
          "operator": "<>"
        },
        {
          "operator": "like",
          "modes": [
            "contains",
            "starts-with",
            "ends-with"
          ]
        },
        {
          "operator": "not-like",
          "modes": [
            "contains",
            "starts-with",
            "ends-with"
          ]
        }
      ]
    },
    {
      "name": "integer",
      "operators": [
        {
          "operator": "=="
        },
        {
          "operator": "<>"
        },
        {
          "operator": ">"
        },
        {
          "operator": ">="
        },
        {
          "operator": "<"
        },
        {
          "operator": "<="
        }
      ]
    },
    {
      "name": "boolean",
      "operators": [
        {
          "operator": "=="
        },
        {
          "operator": "<>"
        }
      ]
    },
    {
      "name": "datetime",
      "operators": [
        {
          "operator": "=="
        },
        {
          "operator": "<>"
        },
        {
          "operator": ">"
        },
        {
          "operator": ">="
        },
        {
          "operator": "<"
        },
        {
          "operator": "<="
        }
      ],
      "specialValues": [
        "hour",
        "day",
        "week",
        "month",
        "quarter",
        "six-months",
        "year",
        "two-years"
      ]
    },
    {
      "name": "yeardate",
      "operators": [
        {
          "operator": "=="
        },
        {
          "operator": "<>"
        }
      ],
      "specialValues": [
        "yesterday",
        "today",
        "tomorrow",
        "last-week",
        "this-week",
        "next-week",
        "last-month",
        "this-month",
        "next-month"
      ]
    }
  ]
}
```

## Segment

A segment is a set of predicate logic on a set of fields, you're 
allowed perform a set of logical comparisons against a set of fields
in your database.

### Fields

Segments allows you to query a set of abstract fields attached to a profile
the supported fields are located in:
`App\Package\Segments\Fields\FieldList::default`

A field has:
- A name
- An attached Entity 
- Propertie(s) referenced on the entity

Fields determine how a field looks in the database.


### Operator
#### Comparison
The operators `==`,`<>`,`>`,`>=`,`<`, `<=`, `like` and, `not-like` are available
the `like` and `not-like`, operators also take an additional `mode` argument.

Which you can see in the above example.

#### Logic

We also support `and`, and `or` operators for combining fields, and other 
subsections of logic.

### Domain Segment
The external segment type gets converted into an in memory intermediary "Segment"
this type is inflated logical operators, and fields.


## Database & Parsing

As the Segment is a recursive data-structure, we need to recursively parse it. We 
have a set of parsers. Parsers convert sections of the in memory domain segment into
fragments of Doctrine DQL.

We recursively visit every node on the tree of the segment and have the appropriate
parser return dql for this subsection.

It's essentially a finite state machine, where each parser knows which other parsers
are allowed for its subtree.

### Context
We need to keep track of context throughout the parsing process, e.g. the alias we
have given a table, in order to avoid joining the table multiple time, and so as to
avoid ambiguity over column names.

#### SubContext
When we're checking a value, that is on a to many table in the query, we're actually 
checking if there is a value that `EXISTS` or `NOT EXISTS` in the joined subset. This is
so that we can check if they have visited a specific venue or not, in our denormalised 
structure.

The table referenced in an exists query is not the one joined earlier in the query, it has to
have it's own unique alias, as such we create a subcontext, with a new alias overriding the old
one, but only visible to parsers that are given the subcontext.

The root context can still give all available aliases.
### To Many vs To One
When we're joining against a to one table, it's pretty simple, we're just checking if a feild
is equal to a value.

With a to-many table, it's a bit more complicated. See this [PR](https://github.com/blackbx/backend/pull/725)

### Lazy joining
Tables are lazily joined in, meaning that if a field is not referenced, then it's assocaiated table is not
inluded in the query.
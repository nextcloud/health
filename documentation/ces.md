# Context-Entity Store
Let's store datasets grouped by contexts, without limitations to column and type definitions.

## Contexts
Contexts can be everything if it is not null.
It is represented by a json-string, and it can be searched for parts in there.
Contexts can never be listed or shown to the user, except to administrators.

## Flow
![ces Flow](ces%20flow.png)

## Filter
Filter can be used for contexts and entities and  have to be formed like the example:

    {
        "key": "value"
    }

## API
### Get and create data
To get and create data, just use one route: *domain.tld/index.php/apps/health/ces* via post-method.
Therefore you have to define a request json like the following:

Ask for all items in the context app: health, module: feeling, type: datasets
If the context is not present, it will be created.

This will create a new item with the entityData as given:

    {
        "data": {
            "contextFilter": {
                "app": "health",
                "module": "feeling",
                "type": "datasets"
            },
            "entityData": {
                "mood": 1,
                "comment": "my comment"
            }
        }
    }

This will look for all items within the context(s) and the entityFilter:

    {
        "data": {
            "contextFilter": {
                "app": "health",
                "module": "weight"
            },
            "entityFilter": {
                "weight": 88
            }
        }
    }


This will update a known item with the id 5 and will only update the mood and the comment:

    {
        "data": {
            "contextFilter": {
                "app": "health",
                "module": "feeling",
                "type": "datasets"
            },
            "entityFilter": {
                "id": 5
            },
            "entityData": {
                "mood": 1,
                "comment": "my comment"
            }
        }
    }


This will update all items with mood=3 and datetime=2020-11-22 20:44:53 and will only update the mood and the comment:

    {
        "data": {
            "contextFilter": {
                "app": "health",
                "module": "feeling",
                "type": "datasets"
            },
            "entityFilter": {
                "mood": 3,
                "date": "2020-11-22 20:44:53"
            },
            "entityData": {
                "mood": 1,
                "comment": "my comment"
            }
        }
    }


### special methods for contexts
#### update context
TODO

#### delete context
TODO


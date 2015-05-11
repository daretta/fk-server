# FK RESTful API Services

## Feedback

### GET /api/feedbacks

Retrive feedbacks list

#### Request
_METHOD_: **GET**  
_URL_: https://APISERVER/**api/feedbacks**  
_PARAMETERS_:  

+ **page** // optional, default = 1 , indicates the page you want to obtain
+ **pageResults** // optional, default = 50 , indicates the results number you want per page

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "feedbacks":[ // array, feedbacks list
        {
            "id":"%i", // integer, feedback unique key
            "url":"%s", // string, URL the feedback is from
            "data": // object, feedback form data. The data object depends on Form configuration
                { // this is a default form example
                    "message":"%s", // string, feedback message
                    "mail":"%s" // string, feedback contact mail
                },
                "created":"%i", // timestamp, creation date
                "ip":"%s", // string, the IP address the feedback comes from
            },
        },
        {
            "id":"%i", // integer, feedback unique key
            "url":"%s", // string, URL the feedback is from
            "data": // object, feedback form data. The data object depends on Form configuration
                { // this is a default form example
                    "message":"%s", // string, feedback message
                    "mail":"%s" // string, feedback contact mail
                },
                "created":"%i", // timestamp, creation date
                "ip":"%s", // string, the IP address the feedback comes from
            },
        }
    ],
    "_links": // HAL standard pagination link
        {
            "self":
                {
                    "href":"%s" // URL that represents this same resource
                }
            "prev": // optional, this only if there are previuous resources
                {
                    "href":"%s" // URL to call to have the previous resources
                }
            "next": // optional, this only if there are remaining resources
                {
                    "href":"%s" // URL to call to have the next resources
                }
            "first": // optional, this only if there are more than 2 pages before the one you requested for
                {
                    "href":"%s" // URL that represents the first resources page
                }
            "last": // optional, this only if there are more than 2 pages before the last
                {
                    "href":"%s" // URL that represents the last resources page
                }
        }
}
```
___

### GET api/feedbacks/:feedback_id

Retrive a single feedback detail

#### Request
_METHOD_: **GET**  
_URL_: https://APISERVER/**api/feedbacks/:feedback_id**  

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "feedback": // object, feedback detail
        {
            "id":"%i", // integer, feedback unique key
            "url":"%s", // string, URL the feedback are from
            "data": // object, feedback form data. The data object depends on Form configuration
                { // this is a default form example
                    "message":"%s", // string, feedback message
                    "mail":"%s" // string, feedback contact mail
                },
                "created":"%i", // timestamp, creation date
                "ip":"%s", // string, the IP address the feedback comes from
            },
        }
}
```
___

### POST api/feedbacks

Create a feedback

#### Request
_METHOD_: **POST**  
_URL_: https://APISERVER/**api/feedbacks**  
_BODY_:  
```
{
    "url":"%s", // string, URL the feedback is from
    "data": // object, feedback form data. The data object depends on Form configuration
        { // this is a default form example
            "message":"%s", // string, feedback message
            "mail":"%s" // string, feedback contact mail
        },
    },
}
```

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "feedback": // object, created feedback details
        {
            "id":"%i", // integer, feedback unique key
            "url":"%s", // string, URL the feedback are from
            "data": // object, feedback form data. The data object depends on Form configuration
                { // this is a default form example
                    "message":"%s", // string, feedback message
                    "mail":"%s" // string, feedback contact mail
                },
                "created":"%i", // timestamp, creation date
                "ip":"%s", // string, the IP address the feedback comes from
            },
        }
}
```
___

### PUT api/feedbacks/:feedback_id

Modify a feedback

#### Request
_METHOD_: **PUT**  
_URL_: https://APISERVER/**api/feedbacks/:feedback_id**  
_BODY_:  
```
{
    "data": // object, feedback form data. The data object depends on Form configuration
        { // this is a default form example
            "message":"%s", // string, feedback message
            "mail":"%s" // string, feedback contact mail
        },
    },
}
```

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "feedback": // object, modified feedback details
        {
            "id":"%i", // integer, feedback unique key
            "url":"%s", // string, URL the feedback are from
            "data": // object, feedback form data. The data object depends on Form configuration
                { // this is a default form example
                    "message":"%s", // string, feedback message
                    "mail":"%s" // string, feedback contact mail
                },
                "created":"%i", // timestamp, creation date
                "ip":"%s", // string, the IP address the feedback comes from
            },
        }
}
```
___

### DELETE api/feedbacks/:feedback_id

Remove a feedback

#### Request
_METHOD_: **DELETE**  
_URL_: https://APISERVER/**api/feedbacks/:feedback_id**  

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
  Status:  
    200 // delete confirm  
_BODY_:  
```
{
    "message": '%s' // string, confirm message
}
```
___

## Form

### GET /api/forms

Retrive forms list

#### Request
_METHOD_: **GET**  
_URL_: https://APISERVER/**api/forms**  
_PARAMETERS_:  

+ **page** // optional, default = 1 , indicates the page you want to obtain
+ **pageResults** // optional, default = 50 , indicates the results number you want per page

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "forms":[ // array, forms list
        {
            "id":"%i", // integer, form unique key
            "url":"%s", // string, URL the form is associated with
            "fields":[ // array of fields composing the form, this is a default form example
                {
                    "type":"text", // string, field type selection ['text, textarea, select, options']
                    "label":"Mail", // string, field label
                    "id":"mail", // string, field id
                    "required":true // optional, boolean, required field indication
                },
                {
                    "type":"textarea", 
                    "label":"Feedback",
                    "id":"message"
                }
            ],
            "created":"%i", // timestamp, creation date
            "ip":"%s", // string, the IP address the form comes from
        },
        {
            "id":"%i", // integer, form unique key
            "url":"%s", // string, URL the form is associated with
            "fields":[ // array of fields composing the form, this is a default form example
                {
                    "type":"text", // string, field type selection ['text, textarea, select, options']
                    "label":"Mail", // string, field label
                    "id":"mail", // string, field id
                    "required":true // optional, boolean, required field indication
                },
                {
                    "type":"textarea", 
                    "label":"Feedback",
                    "id":"message"
                }
            ],
            "created":"%i", // timestamp, creation date
            "ip":"%s", // string, the IP address the form comes from
        }
    ],
    "_links": // HAL standard pagination link
        {
            "self":
                {
                    "href":"%s" // URL that represents this same resource
                }
            "prev": // optional, this only if there are previuous resources
                {
                    "href":"%s" // URL to call to have the previous resources
                }
            "next": // optional, this only if there are remaining resources
                {
                    "href":"%s" // URL to call to have the next resources
                }
            "first": // optional, this only if there are more than 2 pages before the one you requested for
                {
                    "href":"%s" // URL that represents the first resources page
                }
            "last": // optional, this only if there are more than 2 pages before the last
                {
                    "href":"%s" // URL that represents the last resources page
                }
        }
}
```
___

### GET api/forms/:form_id

Retrive a single form detail
:form_id should be "**this**" and the service try to respond with the form associated with the referer URL 

#### Request
_METHOD_: **GET**  
_URL_: https://APISERVER/**api/forms/:form_id**  

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "form":
    {
        "id":"%i", // integer, form unique key
        "url":"%s", // string, URL the form is associated with
        "fields":[ // array of fields composing the form, this is a default form example
            {
                "type":"text", // string, field type selection ['text, textarea, select, options']
                "label":"Mail", // string, field label
                "id":"mail", // string, field id
                "required":true // optional, boolean, required field indication
            },
            {
                "type":"textarea", 
                "label":"Feedback",
                "id":"message"
            }
        ],
        "created":"%i", // timestamp, creation date
        "ip":"%s", // string, the IP address the form comes from
    }
}
```
___

### POST api/forms

Create a form

#### Request
_METHOD_: **POST**  
_URL_: https://APISERVER/**api/forms**  
_BODY_:  
```
{
    "url":"%s", // string, URL the feedback is from
    "fields":[ // array of fields composing the form, this is a default form example
        {
            "type":"%s", // string, field type selection ['text, textarea, select, options']
            "label":"%s", // string, field label
            "id":"%s", // string, field id
            "required":%b // optional, boolean, required field indication
        },
        {
            "type":"%s",
            "label":"%s",
            "id":"%s",
        }
    ]
}
```

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
_BODY_:  
```
{
    "form":
    {
        "id":"%i", // integer, form unique key
        "url":"%s", // string, URL the form is associated with
        "fields":[ // array of fields composing the form, this is a default form example
            {
                "type":"%s", // string, field type selection ['text, textarea, select, options']
                "label":"%s", // string, field label
                "id":"%s", // string, field id
                "required":%b // optional, boolean, required field indication
            },
            {
                "type":"%s",
                "label":"%s",
                "id":"%s",
            }
        ],
        "created":"%i", // timestamp, creation date
        "ip":"%s", // string, the IP address the form comes from
    }
}
```
___

### DELETE api/forms/:form_id

Remove a form

#### Request
_METHOD_: **DELETE**  
_URL_: https://APISERVER/**api/forms/:form_id**  

#### Response
_HEADERS_:  
  Content-Type: **application/json**  
  Status:  
    200 // delete confirm  
_BODY_:  
```
{
    "message": '%s' // string, confirm message
}
```
___

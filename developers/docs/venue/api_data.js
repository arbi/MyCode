define({ "api": [
  {
    "type": "post",
    "url": "API_DOMAIN/file/attachments",
    "title": "Attachment",
    "version": "1.0.0",
    "name": "UploadImage",
    "group": "Attachments",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "description": "<p>This method is used for uploading attachments to a module</p> ",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "attachemnt",
            "description": "<p>This is the byte array representation of the file</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "attachmentType",
            "description": "<p>This is the type of attachment. Possible values are in /warehouse/configs under attachmentTypes</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "entityId",
            "description": "<p>This is the entity identification for the given module</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "moduleId",
            "description": "<p>This is the module identification. Possible values are in /warehouse/configs under moduleTypes</p> "
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "Status",
            "description": "<p>Success</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"Status\": \"Success\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Application/src/Application/Controller/AttachmentsController.php",
    "groupTitle": "Attachments"
  },
  {
    "type": "post",
    "url": "API_DOMAIN/auth/authorization",
    "title": "Authorization",
    "version": "1.0.0",
    "name": "Authorization",
    "group": "Authentication",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          }
        ]
      }
    },
    "description": "<p>This is the function of specifying access rights to resources and access control in particular. This method returns a valid OAuth 2.0 token set for future authentication requests based on the provider parameter</p> ",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "provider",
            "description": "<p>This is the authorization provider string. The possible values are Ginosi and Google</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": true,
            "field": "username",
            "description": "<p>The username of the account for Ginosi provider</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": true,
            "field": "password",
            "description": "<p>The password of the account for Ginosi provider</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "grant_type",
            "description": "<p>This is the OAuth 2.0 grant_type parameter. The possible values are password and refresh_token</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "client_id",
            "description": "<p>This is the OAuth 2.0 client_id parameter. The value for this parameter can be found in credentials set</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "client_secret",
            "description": "<p>This is the OAuth 2.0 client_secret parameter. The value for this parameter can be found in credentials set</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Ginosi Sample Request:",
          "content": "{\n  \"provider\": \"ginosi\",\n  \"username\": \"app.user@ginosi.com\",\n  \"password\": \"XXX\"\n  \"grant_type\": \"password\",\n  \"client_id\": \"XXX\",\n  \"client_secret\": \"XXX\"\n}",
          "type": "json"
        },
        {
          "title": "Google Sample Request:",
          "content": "{\n  \"provider\": \"google\",\n  \"token\": \"32c6f1120265f610bcf6829148a5c01c19ea2013\",\n  \"grant_type\": \"password\",\n  \"client_id\": \"XXX\",\n  \"client_secret\": \"XXX\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"access_token\": \"32c6f1120265f610bcf6829148a5c01c19ea2013\",\n    \"expires_in\": 86400,\n    \"token_type\": \"Bearer\",\n    \"scope\": null,\n    \"refresh_token\": \"a9a36d81152a2e06945646ab74924bc5d50ceedf\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Application/src/Application/Controller/AuthenticateController.php",
    "groupTitle": "Authentication"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/auth/login",
    "title": "Login",
    "version": "1.0.0",
    "name": "Login",
    "group": "Authentication",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "description": "<p>This method is used to login into Ginosi system. It requires a valid access_token retrieved from /auth/authorization end point</p> ",
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"id\": \"12\",\n    \"firstname\": \"App\",\n    \"lastname\": \"User\",\n    \"email\": \"app.user@ginosi.com\",\n    \"cityId\": \"62\",\n    \"countryId\": \"21\",\n    \"permissions\": {\n        \"warehouse\": true,\n        \"incident\": true\n    },\n    \"profileUrl\": \"https://images.ginosi.com/profile/12/144342324403_0_150.png\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Application/src/Application/Controller/AuthenticateController.php",
    "groupTitle": "Authentication"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/auth/logout",
    "title": "Logout",
    "version": "1.0.0",
    "name": "Logout",
    "group": "Authentication",
    "description": "<p>This method is used to logout from the Ginosi system</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"response\": \"Success\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Application/src/Application/Controller/AuthenticateController.php",
    "groupTitle": "Authentication"
  },
  {
    "type": "post",
    "url": "API_DOMAIN/auth/refresh-token",
    "title": "Refresh Token",
    "version": "1.0.0",
    "name": "RefreshToken",
    "group": "Authentication",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          }
        ]
      }
    },
    "description": "<p>This method returns a new valid OAuth 2.0 token set based on the refresh_token</p> ",
    "parameter": {
      "examples": [
        {
          "title": "Sample Request:",
          "content": "{\n    \"grant_type\": \"refresh_token\",\n    \"refresh_token\": \"2b2cf16711c98ced53e17c14c2016e5259491fb4\",\n    \"client_id\": \"XXX\",\n    \"client_secret\": \"XXX\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n     \"access_token\": \"32c6f1120265f610bcf6829148a5c01c19ea2013\",\n     \"expires_in\": 86400,\n     \"token_type\": \"Bearer\",\n     \"scope\": null,\n     \"refresh_token\": \"a9a36d81152a2e06945646ab74924bc5d50ceedf\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Application/src/Application/Controller/AuthenticateController.php",
    "groupTitle": "Authentication"
  },
  {
    "type": "get",
    "url": "Errors",
    "title": "",
    "version": "1.0.0",
    "name": "ErrorList",
    "group": "Error",
    "success": {
      "examples": [
        {
          "title": "Error Types:",
          "content": "400: 'Bad Request'\n401: 'Unauthorized'\n409: 'Duplicate Request'\n500: 'Server side problem'\n600: 'Invalid or expired token'\n601: 'Image file not found'\n602: 'File extension is incorrect'\n603: 'File size limit exceeded'\n604: 'Cannot upload file'\n605: 'File not found'\n606: 'User not found'\n607: 'Some parameters are not set'\n608: 'Status not found'\n609: 'Invalid valuable asset status'\n610: 'Asset not found'\n611: 'Location not found'\n612: 'Module not found'\n613: 'Duplicate category'\n614: 'Duplicate user hash'",
          "type": "json"
        }
      ]
    },
    "error": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 404 Not Found\n{\n    \"type\": \"http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html\",\n    \"title\": \"Not Found\",\n    \"status\": 404,\n    \"detail\": {\n        \"code\": 605,\n        \"message\": \"File not found\"\n    }\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Application/src/Application/Entity/Error.php",
    "groupTitle": "Error"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/ginosi-link/users",
    "title": "User List",
    "version": "1.0.0",
    "name": "GetUser",
    "group": "User",
    "description": "<p>This method returns the list of all users</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "id",
            "description": "<p>The unique identification of the user</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "firstname",
            "description": "<p>The first name of the user</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "lastname",
            "description": "<p>The last name of the user</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "avatar",
            "description": "<p>The profile picture of the user</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n[\n    {\n        \"id\": \"12\",\n        \"firstname\": \"App\",\n        \"lastname\": \"User\",\n        \"avatar\": \"https://images.ginosi.com/profile/12/1439802421_0_150.png\"\n    }\n]",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiLink/src/GinosiLink/V1/Rest/Users/UsersResource.php",
    "groupTitle": "User"
  },
  {
    "type": "post",
    "url": "API_DOMAIN/ginosi-link/user-hashes",
    "title": "New Hash",
    "version": "1.0.0",
    "name": "NewHash",
    "group": "User",
    "description": "<p>This method is used for creating a new hash code in user devices. It returns the newly created user device data</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "uuid",
            "description": "<p>The Universal Unique Identifiers generated in the mobile device to prevent duplicate requests</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "userId",
            "description": "<p>This is the type indicator for the user</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "hash",
            "description": "<p>This is the hash code</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Request:",
          "content": "{\n    \"uuid\": \"XXX\",\n    \"userId\": 1,\n    \"hash\" : \"XXX\",\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Hash Code Response:",
          "content": "HTTP/1.1 201 Created\n{\n    \"id\": 1,\n    \"user_id\": 1,\n    \"hash\" : \"XXX\",\n    \"date_added\" : \"2015-11-20 15:44:32\",\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiLink/src/GinosiLink/V1/Rest/UserHashes/UserHashesResource.php",
    "groupTitle": "User"
  },
  {
    "type": "delete",
    "url": "API_DOMAIN/ginosi-link/user-hashes/:user_hashes_id",
    "title": "Unlink Hash",
    "version": "1.0.0",
    "name": "UnlinkHash",
    "group": "User",
    "description": "<p>This method is used for unlink hash code in user devices.</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "user_hashes_id",
            "description": "<p>This is a user device identification number</p> "
          }
        ]
      }
    },
    "success": {
      "examples": [
        {
          "title": "Hash Code Response:",
          "content": "HTTP/1.1 204 Deleted",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiLink/src/GinosiLink/V1/Rest/UserHashes/UserHashesResource.php",
    "groupTitle": "User"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/ginosi-tally/user/:user_id/pin/:pin",
    "title": "User Pins",
    "version": "1.0.0",
    "name": "UserPins",
    "group": "User",
    "description": "<p>This method check user pin code and return status.</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>Boolean</p> ",
            "optional": false,
            "field": "status",
            "description": "<p>Response status</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "message",
            "description": "<p>Response message</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"status\":  true,\n    \"message\": \"Success\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiTally/src/GinosiTally/V1/Rest/UserPins/UserPinsResource.php",
    "groupTitle": "User"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/ginosi-link/user-hashes/:user_hash",
    "title": "Get User by Hash",
    "version": "1.0.0",
    "name": "getUserDeviceByHash",
    "group": "User",
    "description": "<p>This method return user device data for GinosiLink. The user_hash parameter contains the value of user hash.</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "id",
            "description": "<p>User Device identification number</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "user_id",
            "description": "<p>User Id</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n     \"id\": \"1\",\n     \"user_id\": \"12\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiLink/src/GinosiLink/V1/Rest/UserHashes/UserHashesResource.php",
    "groupTitle": "User"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/ginosi-link/users/:users_id",
    "title": "User Details",
    "version": "1.0.0",
    "name": "userDetails",
    "group": "User",
    "description": "<p>This method return user details data for GinosiLink.</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n     \"id\": \"12\",\n     \"firstname\": \"App\",\n     \"lastname\": \"User\",\n     \"phone\": \"37455555555\",\n     \"email\": \"app.user@ginosi.com\",\n     \"avatar\": \"https://images.ginosi.com/profile/387/1447849917_0_150.png\",\n     \"department\": \"Engineering\",\n     \"manager\": \"Tigran Petrosyan\"\n }",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiLink/src/GinosiLink/V1/Rest/Users/UsersResource.php",
    "groupTitle": "User"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/ginosi-tally/venue-charges/:venue_id",
    "title": "Venue Charges",
    "version": "1.0.0",
    "name": "venueCharges",
    "group": "Venue",
    "description": "<p>This method return Venue Charges and Items.</p> ",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Content-Type",
            "description": "<p>application/json</p> "
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "Authorization",
            "description": "<p>Bearer ACCESS_TOKEN</p> "
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "id",
            "description": "<p>The unique identification of the user</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Datetime</p> ",
            "optional": false,
            "field": "date_created_server",
            "description": "<p>The date of server request</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Datetime</p> ",
            "optional": false,
            "field": "date_created_client",
            "description": "<p>The date of client request</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "user_id",
            "description": "<p>The user identification</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Array</p> ",
            "optional": false,
            "field": "items",
            "description": "<p>The items list</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "item_id",
            "description": "<p>The item identification</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "item_name",
            "description": "<p>The item name</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "item_quantity",
            "description": "<p>The item quantity</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "item_price",
            "description": "<p>The price for single item</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "currency_code",
            "description": "<p>The venue currency code</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "currency_id",
            "description": "<p>The venue currency identification</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n     \"1\": {\n          \"id\": \"1\",\n          \"date_created_server\": \"2015-11-20 09:49:23\",\n          \"date_created_client\": \"2015-11-20 09:49:23\",\n          \"user_id\": \"306\",\n          \"items\": []\n      },\n      \"2\": {\n              \"id\": \"2\",\n              \"date_created_server\": \"2015-11-20 09:51:57\",\n              \"date_created_client\": \"2015-11-20 09:51:57\",\n              \"user_id\": \"387\",\n              \"items\": [\n                  {\n                      \"item_id\": \"5\",\n                      \"item_name\": \"salat\",\n                      \"item_quantity\": \"1\",\n                      \"item_price\": \"200.00\",\n                      \"currency_code\": \"GBP\",\n                      \"currency_id\": \"53\"\n                  },\n                  {\n                      \"item_id\": \"6\",\n                      \"item_name\": \"borsh\",\n                      \"item_quantity\": \"1\",\n                      \"item_price\": \"1000.00\",\n                      \"currency_code\": \"GBP\",\n                      \"currency_id\": \"53\"\n                  }\n              ]\n      },\n }",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/GinosiTally/src/GinosiTally/V1/Rest/VenueCharges/VenueChargesResource.php",
    "groupTitle": "Venue"
  }
] });
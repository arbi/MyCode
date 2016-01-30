define({ "api": [
  {
    "type": "get",
    "url": "API_DOMAIN/warehouse/assets/:assets_id",
    "title": "Asset Details",
    "version": "1.0.0",
    "name": "AssetDetails",
    "group": "Asset",
    "description": "<p>This method is used for checking whether an asset with a given barcode exists. The assets_id parameter contains the value of asset barcode</p> ",
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
          "title": "Valuable Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"id\": \"4\",\n    \"status\": \"6\",\n    \"categoryId\": \"2\",\n    \"locationEntityId\": \"33\",\n    \"locationEntityType\": \"22\",\n    \"serialNumber\": \"erterertre\",\n    \"locationName\": \"Chicago Storage\",\n    \"categoryName\": \"Computer\",\n    \"firstnameLastUpdated\": \"App\",\n    \"lastnameLastUpdated\": \"User\",\n    \"statusName\": \"New\",\n    \"assetTypeId\": \"2\"\n}",
          "type": "json"
        },
        {
          "title": "Consumable Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n     \"id\": \"1\",\n     \"categoryId\": \"1\",\n     \"locationEntityId\": \"3\",\n     \"locationEntityType\": \"2\",\n     \"quantity\": \"45020\",\n     \"description\": \"This is the Kleenex description\",\n     \"locationName\": \"Chicago Storage\",\n     \"skuName\": \"Kleenex\",\n     \"skuId\": \"3\",\n     \"categoryName\": \"Toilet Paper\",\n     \"firstnameLastUpdated\": \"App\",\n     \"lastnameLastUpdated\": \"User\",\n     \"assetTypeId\": \"1\"\n }",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Assets/AssetsResource.php",
    "groupTitle": "Asset"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/warehouse/assets",
    "title": "Asset List",
    "version": "1.0.0",
    "name": "AssetList",
    "group": "Asset",
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
    "description": "<p>This method returns the assets list. The possible values for assetTypeId are Consumable (1) and Valuable (2)</p> ",
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n[\n    {\n        \"id\": \"4\",\n        \"name\": \"Toshiba Laptop\",\n        \"categoryId\": \"2\",\n        \"locationEntityId\": \"3\",\n        \"locationEntityType\": \"2\",\n        \"serialNumber\": \"5dsf4g35d4f3sd4f3sd4f34s\",\n        \"categoryName\": \"Computer\",\n        \"statusName\": \"New\",\n        \"asigneeFirstname\": \"App\",\n        \"asigneeLastname\": \"User\",\n        \"locationName\": \"Chicago Storage\",\n        \"assetTypeId\": \"2\"\n    },\n    {\n        \"id\": \"1\",\n        \"quantity\": \"450\",\n        \"lastUpdatedById\": \"234\",\n        \"shipmentStatus\": \"1\",\n        \"locationEntityId\": \"3\",\n        \"locationEntityType\": \"2\",\n        \"categoryId\": \"1\",\n        \"skues\": [\n            \"5454534544354\",\n            \"54354354543544\",\n            \"fdssdf4sd54f5ds33\"\n        ],\n        \"categoryName\": \"Toilet Paper\",\n        \"locationName\": \"Chicago Storage\",\n        \"assetTypeId\": \"1\"\n    }\n]",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Assets/AssetsResource.php",
    "groupTitle": "Asset"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/warehouse/assets/:assets_id/histories",
    "title": "Asset History",
    "version": "1.0.0",
    "name": "GetHistory",
    "group": "Asset",
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
    "description": "<p>This method returns a Valuable asset history list</p> ",
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "title",
            "description": "<p>This is the asset modification title</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "titleType",
            "description": "<p>This is the asset modification type. The possible values are in /warehouse/configs under assetChangeTypes</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "username",
            "description": "<p>The username of the user who added the comment</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "description",
            "description": "<p>The description text about the action</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "date",
            "description": "<p>The date and time when the comment was added</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"_links\": {\n        \"self\": {\n            \"href\": \"https://API_DOMAIN/warehouse/assets/1/histories?page=1\"\n        },\n       \"first\": {\n           \"href\": \"https://API_DOMAIN/warehouse/assets/1/histories\"\n       },\n       \"last\": {\n           \"href\": \"https://API_DOMAIN/warehouse/assets/1/histories?page=2\"\n       },\n       \"next\": {\n           \"href\": \"https://API_DOMAIN/warehouse/assets/1/histories?page=2\"\n       }\n    },\n    \"_embedded\": {\n        \"histories\": [\n            {\n                \"title\": \"Status Changes\",\n                \"titleType\": 155,\n                \"username\": \"app.user@ginosi.com\",\n                \"description\": \"We changed this item because it was broken\",\n                \"date\": \"2015-11-11 12:12:12\"\n            }\n        ]\n    },\n    \"page_count\": 2,\n    \"page_size\": 25,\n    \"total_items\": 43,\n    \"page\": 1\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Histories/HistoriesResource.php",
    "groupTitle": "Asset"
  },
  {
    "type": "post",
    "url": "API_DOMAIN/warehouse/assets",
    "title": "New Asset",
    "version": "1.0.0",
    "name": "NewAsset",
    "group": "Asset",
    "description": "<p>This method is used for creating a new asset item. It returns the newly created asset's properties and data</p> ",
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
            "field": "assetType",
            "description": "<p>This is the type indicator for the asset. The possible values are Consumable (1) and Valuable (2)</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "locationEntityId",
            "description": "<p>The identification of the asset location</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "locationEntityType",
            "description": "<p>This is the type indicator for the asset location. The possible values are in /warehouse/configs under locationTypes</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "quantity",
            "description": "<p>This is the quantity indicator for the asset. The possible value for Valuable assets is only 1</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "categoryId",
            "description": "<p>The identification for the asset category</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "status",
            "description": "<p>This is the asset status indicator bit. The possible values for Consumable assets is always 0 and for Valuable assets is the asset type</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "barcode",
            "description": "<p>This is the barcode identification of the asset</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "assigneeId",
            "description": "<p>The user identification of assigned user</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "name",
            "description": "<p>This is the name for the asset. The possible values are the actual asset name for Valuable and empty string for Consumable assets</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "shipmentStatus",
            "description": "<p>This is the shipment status indicator bit. If set to 1 it indicates new asset from a received order</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "comment",
            "description": "<p>The attached user comments</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Request:",
          "content": "{\n    \"uuid\": \"XXX\",\n    \"assetType\": 1,\n    \"locationEntityId\" : 1,\n    \"locationEntityType\": 2,\n    \"quantity\": 3000,\n    \"categoryId\": 1,\n    \"status\": 0,\n    \"barcode\": \"XXX\",\n    \"assigneeId\": 12,\n    \"name\": \"Toilet Paper\",\n    \"shipmentStatus\": 1,\n    \"comment\": \"This is my comments\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Consumable Sample Response:",
          "content": "HTTP/1.1 201 Created\n{\n    \"id\": \"7\",\n    \"locationEntityId\": \"1\",\n    \"locationEntityType\": \"3\",\n    \"categoryId\": \"1\",\n    \"quantity\": \"1100\"\n}",
          "type": "json"
        },
        {
          "title": "Valuable Sample Response:",
          "content": "HTTP/1.1 201 Created\n{\n    \"id\": \"7\",\n    \"locationEntityId\": \"1\",\n    \"locationEntityType\": \"3\",\n    \"categoryId\": \"1\",\n    \"status\": \"4\",\n    \"barcode\": \"XXX\",\n    \"assigneeId\": \"12\",\n    \"name\": \"Toilet Paper\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Assets/AssetsResource.php",
    "groupTitle": "Asset"
  },
  {
    "type": "patch",
    "url": "API_DOMAIN/warehouse/assets/:assets_id",
    "title": "Update Asset",
    "version": "1.0.0",
    "name": "UpdateAsset",
    "group": "Asset",
    "description": "<p>This method is used for updating an existing asset item. It returns the modified asset's properties and data</p> ",
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
            "field": "assetType",
            "description": "<p>This is the type indicator for the asset. The possible values are Consumable (1) and Valuable (2)</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "locationEntityId",
            "description": "<p>The identification of the asset location</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "locationEntityType",
            "description": "<p>This is the type indicator for the asset location. The possible values are in /warehouse/configs under locationTypes</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "quantity",
            "description": "<p>This is the quantity indicator for the asset. The possible value for Valuable assets is only 1</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "categoryId",
            "description": "<p>The identification for the asset category</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "status",
            "description": "<p>This is the asset status indicator bit. The possible values for Consumable assets is always 0 and for Valuable assets is the asset type</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "barcode",
            "description": "<p>This is the barcode identification of the asset</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "assigneeId",
            "description": "<p>The user identification of assigned user</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "name",
            "description": "<p>This is the name for the asset. The possible values are the actual asset name for Valuable and empty string for Consumable assets</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "shipmentStatus",
            "description": "<p>This is the shipment status indicator bit. If set to 1 it indicates new asset from a received order</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "comment",
            "description": "<p>The attached user comments</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Request:",
          "content": "{\n  \"uuid\": \"we354fwe534ffew54\",\n  \"assetType\": 1,\n  \"locationEntityId\" : 1,\n  \"locationEntityType\": 2,\n  \"quantity\": 3000,\n  \"categoryId\": 1,\n  \"status\": 0,\n  \"barcode\": \"s3d54fs3d54f3s\",\n  \"assigneeId\": \"12\",\n  \"name\": \"Toilet Paper\",\n  \"shipmentStatus\": 1,\n  \"comment\": \"This is my comments\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Consumable Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"id\": \"7\",\n    \"locationEntityId\": \"1\",\n    \"locationEntityType\": \"3\",\n    \"categoryId\": \"1\",\n    \"quantity\": \"1100\"\n}",
          "type": "json"
        },
        {
          "title": "Valuable Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"id\": \"7\",\n    \"locationEntityId\": \"1\",\n    \"locationEntityType\": \"3\",\n    \"categoryId\": \"1\",\n    \"categoryName\": \"Computer\",\n    \"status\": \"1\",\n    \"statusName\": \"Working\",\n    \"barcode\": \"XXX\",\n    \"assigneeId\": \"12\",\n    \"name\": \"Toilet Paper\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Assets/AssetsResource.php",
    "groupTitle": "Asset"
  },
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
    "url": "API_DOMAIN/warehouse/categories",
    "title": "Category List",
    "version": "1.0.0",
    "name": "GetCategories",
    "group": "Category",
    "description": "<p>This method returns all category list</p> ",
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
            "description": "<p>The category identification</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "name",
            "description": "<p>The category name</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "type",
            "description": "<p>The category type. The possible values are Consumable (1) and Valuable (2)</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String[]</p> ",
            "optional": false,
            "field": "skues",
            "description": "<p>This is the list of SKUes for the given category</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>String[]</p> ",
            "optional": false,
            "field": "aliases",
            "description": "<p>This is the list of aliases for the given category</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"id\": \"1\",\n    \"name\": \"Toilet Paper\",\n    \"type\": \"1\",\n    \"skues\": [\n        \"4ds3f4sd3fde4\",\n        \"sa53d4sa534d4\"\n    ],\n    \"aliases\": [\n        \"Kleenex\",\n        \"Royale\",\n        \"Delica\"\n    ]\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Categories/CategoriesResource.php",
    "groupTitle": "Category"
  },
  {
    "type": "post",
    "url": "API_DOMAIN/warehouse/categories",
    "title": "New Category",
    "version": "1.0.0",
    "name": "NewCategory",
    "group": "Category",
    "description": "<p>This method is used for creating a new category</p> ",
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
            "field": "type",
            "description": "<p>The category type. The possible values are in /warehouse/configs under assetTypes</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "name",
            "description": "<p>The category name</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Request:",
          "content": "{\n    \"uuid\": \"XXX\",\n    \"type\": 1,\n    \"name\": \"Toilet Paper\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"id\": \"1\",\n    \"name\": \"Toilet Paper\",\n    \"type\": \"1\"\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Categories/CategoriesResource.php",
    "groupTitle": "Category"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/warehouse/configs",
    "title": "Configs List",
    "version": "1.0.0",
    "name": "GetConfigs",
    "group": "Config",
    "description": "<p>This method returns the list of all warehouse configurations</p> ",
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
            "field": "assets",
            "description": "<p>The update interval for updating assets list. The value is in seconds</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "locations",
            "description": "<p>The update interval for updating locations list. The value is in seconds</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "users",
            "description": "<p>The update interval for updating users list. The value is in seconds</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "categories",
            "description": "<p>The update interval for updating category list. The value is in seconds</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "configs",
            "description": "<p>The update interval for updating configurations. The value is in seconds</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "version",
            "description": "<p>The current API version</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "requestTTL",
            "description": "<p>The value indicating how long should a request stay in the mobile</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "assetTypes",
            "description": "<p>All possible values for asset types</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "assetValuableStatuses",
            "description": "<p>All possible values for Valuable asset statuses</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "locationTypes",
            "description": "<p>All possible values for location types</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "assetChangeTypes",
            "description": "<p>All possible values for asset change types</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "attachmentTypes",
            "description": "<p>All possible values for attachment types</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "moduleTypes",
            "description": "<p>All possible values for module types</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "imageConfigs",
            "description": "<p>The maximum width and height for images. Anything larger is compressed on the client to match this criteria.</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n{\n    \"updateIntervals\": {\n        \"assets\": 86400,\n        \"locations\": 86400,\n        \"users\": 86400,\n        \"categories\": 86400,\n        \"configs\": 86400\n    },\n    \"requestTTL\": 604800,\n    \"version\": 1,\n    \"assetTypes\": {\n         \"1\": \"Consumable\",\n         \"2\": \"Valuable\"\n    },\n    \"assetValuableStatuses\": {\n        \"1\": \"Working\",\n        \"2\": \"Broken\",\n        \"3\": \"Lost\",\n        \"4\": \"Retired\",\n        \"5\": \"Expunged\",\n        \"6\": \"New\",\n        \"7\": \"Repair\"\n    },\n    \"locationTypes\": {\n        \"1\": \"Apartment\",\n        \"2\": \"Storage\",\n        \"3\": \"Office\",\n        \"4\": \"Building\"\n    },\n    \"assetChangeTypes\" : {\n        \"155\": \"Status Change\",\n        \"156\": \"Assignee Change\",\n        \"157\": \"Location Change\",\n        \"158\": \"Added Comment\"\n    },\n    \"attachmentTypes\": {\n        \"1\": \"All\",\n        \"2\": \"Image\",\n        \"3\": \"Document\"\n    },\n    \"moduleTypes\": {\n        \"1\": \"Incident\"\n    },\n    \"imageConfigs\": {\n        \"maxWidth\": 1024,\n        \"maxHeight\": 1024\n    }\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Warehouse/src/Warehouse/V1/Rest/Configs/ConfigsResource.php",
    "groupTitle": "Config"
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
    "type": "post",
    "url": "API_DOMAIN/task/incidents",
    "title": "New Incident",
    "version": "1.0.0",
    "name": "NewIncident",
    "group": "Incident",
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
    "description": "<p>This method is used for creating an incident report</p> ",
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
            "field": "locationEntityType",
            "description": "<p>This is the type for the location. The possible values are in /warehouse/configs under locationTypes</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "locationEntityId",
            "description": "<p>The identification of the asset location</p> "
          },
          {
            "group": "Parameter",
            "type": "<p>String</p> ",
            "optional": false,
            "field": "description",
            "description": "<p>This is the description text about the incident</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Request:",
          "content": "{\n    \"uuid\": \"XXX\",\n    \"locationEntityType\": 1,\n    \"locationEntityId\" : 42,\n    \"description\": \"The storage door was broken\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "entityId",
            "description": "<p>The newly created incident identification</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "moduleId",
            "description": "<p>The newly created incident type identification</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Int</p> ",
            "optional": false,
            "field": "attachmentType",
            "description": "<p>The valid attachment file type for this incident report</p> "
          },
          {
            "group": "Success 200",
            "type": "<p>Object</p> ",
            "optional": false,
            "field": "_links",
            "description": "<p>This is used to add attachment(s) to the incident report</p> "
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 201 Created\n{\n    \"entityId\": 33444,\n    \"moduleId\": 1,\n    \"attachmentType\": 2,\n    \"_links\": {\n        \"attachment\": \"API_DOMAIN/file/attachment\"\n    }\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Task/src/Task/V1/Rest/Incidents/IncidentsResource.php",
    "groupTitle": "Incident"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/locations",
    "title": "Location List",
    "version": "1.0.0",
    "name": "LocationList",
    "group": "Location",
    "description": "<p>This method returns the list of all locations for the authenticated user</p> ",
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
          "content": "HTTP/1.1 200 OK\n{\n    \"apartments\": [\n        {\n            \"id\": \"42\",\n            \"name\": \"Test Apartment n1\",\n            \"address\": \"104 Andranik st\",\n            \"buildingId\": \"1\",\n            \"cityId\": \"6\",\n            \"locationType\": \"1\"\n        }\n    ],\n    \"buildings\": [\n        {\n            \"id\": \"1\",\n            \"name\": \"Test Apartment Group\",\n            \"countryId\": \"2\",\n            \"locationType\": \"4\"\n        }\n    ],\n    \"storages\": [\n        {\n            \"id\": \"3\",\n            \"name\": \"Chicago Storage\",\n            \"locationType\": \"2\"\n        }\n    ],\n    \"offices\": [\n        {\n            \"id\": \"1\",\n            \"name\": \"Yerevan Office\",\n            \"description\": \"Head office of Ginosi located in Yerevan city\",\n            \"address\": \"K. Ulnetsi 31\",\n            \"countryId\": \"2\",\n            \"cityId\": \"6\",\n            \"locationType\": \"3\"\n        }\n    ]\n}",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Common/src/Common/V1/Rest/Locations/LocationsResource.php",
    "groupTitle": "Location"
  },
  {
    "type": "get",
    "url": "API_DOMAIN/users",
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
          }
        ]
      },
      "examples": [
        {
          "title": "Sample Response:",
          "content": "HTTP/1.1 200 OK\n[\n    {\n        \"id\": \"12\",\n        \"firstname\": \"App\",\n        \"lastname\": \"User\",\n    }\n]",
          "type": "json"
        }
      ]
    },
    "filename": "/ginosi/api/module/Common/src/Common/V1/Rest/Users/UsersResource.php",
    "groupTitle": "User"
  }
] });